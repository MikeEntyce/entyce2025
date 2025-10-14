<?php

/**
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

namespace Duplicator\Addons\ProBase\Models;

use DateTime;
use DUP_PRO_Global_Entity;
use DUP_PRO_Log;
use Duplicator\Addons\ProBase\License\License;
use Duplicator\Core\Models\AbstractEntitySingleton;
use Duplicator\Installer\Addons\ProBase\AbstractLicense;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Utils\Crypt\CryptBlowfish;
use Duplicator\Utils\ExpireOptions;
use Error;
use Exception;
use VendorDuplicator\Amk\JsonSerialize\JsonSerialize;
use WP_Error;

class LicenseData extends AbstractEntitySingleton
{
    /**
     * GENERAL SETTINGS
     */
    const LICENSE_CACHE_TIME          = 7 * DAY_IN_SECONDS;
    const LICENSE_FAILURE_DELAY_TIME  = 60 * MINUTE_IN_SECONDS;
    const LICENSE_FAILURE_OPT_KEY     = 'license_failure_request';
    const FAILURE_SKIP_EXCEPTION_CODE = 100;
    const LICENSE_OLD_KEY_OPTION_NAME = 'duplicator_pro_license_key';

    /**
     * LICENSE STATUS
     */
    const STATUS_UNKNOWN       = -1;
    const STATUS_VALID         = 0;
    const STATUS_INVALID       = 1;
    const STATUS_INACTIVE      = 2;
    const STATUS_DISABLED      = 3;
    const STATUS_SITE_INACTIVE = 4;
    const STATUS_EXPIRED       = 5;

    /**
     * ACTIVATION REPONSE
     */
    const ACTIVATION_RESPONSE_OK      = 0;
    const ACTIVATION_REQUEST_ERROR    = -1;
    const ACTIVATION_RESPONSE_INVALID = -2;

    const DEFAULT_LICENSE_DATA = [
        'success'            => false,
        'license'            => 'invalid',
        'item_id'            => false,
        'item_name'          => '',
        'checksum'           => '',
        'expires'            => '',
        'payment_id'         => -1,
        'customer_name'      => '',
        'customer_email'     => '',
        'license_limit'      => -1,
        'site_count'         => -1,
        'activations_left'   => -1,
        'price_id'           => AbstractLicense::TYPE_UNLICENSED,
        'activeSubscription' => false,
    ];

    /** @var string */
    protected $licenseKey = '';
    /** @var int */
    protected $status = self::STATUS_INVALID;
    /** @var int */
    protected $type = AbstractLicense::TYPE_UNKNOWN;
    /** @var array<string,scalar> License remote data */
    protected $data = self::DEFAULT_LICENSE_DATA;
    /** @var string timestamp YYYY-MM-DD HH:MM:SS UTC */
    protected $lastRemoteUpdate = '';
    /** @var string timestamp YYYY-MM-DD HH:MM:SS UTC */
    protected $lastFailureTime = '';

    /**
     * Last error request
     *
     * @var array{code:int, message: string, details: string, requestDetails: string}
     */
    protected $lastRequestError = [
        'code'           => 0,
        'message'        => '',
        'details'        => '',
        'requestDetails' => '',
    ];

    /**
     * Class constructor
     */
    protected function __construct()
    {
    }

    /**
     * Return entity type identifier
     *
     * @return string
     */
    public static function getType()
    {
        return 'LicenseDataEntity';
    }

    /**
     * Will be called, automatically, when Serialize
     *
     * @return array<string, mixed>
     */
    public function __serialize() // phpcs:ignore PHPCompatibility.FunctionNameRestrictions.NewMagicMethods.__serializeFound
    {
        $data = JsonSerialize::serializeToData($this, JsonSerialize::JSON_SKIP_MAGIC_METHODS |  JsonSerialize::JSON_SKIP_CLASS_NAME);
        if (DUP_PRO_Global_Entity::getInstance()->crypt) {
            $data['licenseKey'] = CryptBlowfish::encrypt($data['licenseKey'], null, true);
            $data['status']     = CryptBlowfish::encrypt($data['status'], null, true);
            $data['type']       = CryptBlowfish::encrypt($data['type'], null, true);
            $data['data']       = CryptBlowfish::encrypt(JsonSerialize::serialize($this->data), null, true);
        }
        unset($data['lastRequestError']);
        return $data;
    }

    /**
     * Serialize
     *
     * Wakeup method.
     *
     * @return void
     */
    public function __wakeup()
    {
        if (DUP_PRO_Global_Entity::getInstance()->crypt) {
            $this->licenseKey = CryptBlowfish::decrypt((string) $this->licenseKey, null, true);
            $this->status     = (int) CryptBlowfish::decrypt((string) $this->status, null, true);
            $this->type       = (int) CryptBlowfish::decrypt((string) $this->type, null, true);
            /** @var string  PHP stan fix*/
            $dataString = $this->data;
            $this->data = JsonSerialize::unserialize(CryptBlowfish::decrypt($dataString, null, true));
        }

        if (!is_array($this->data)) {
            $this->data = self::DEFAULT_LICENSE_DATA;
        }
    }

    /**
     * Set license key
     *
     * @param string $licenseKey License key, if empty the license key will be removed
     *
     * @return bool return true if license key is valid and set
     */
    public function setKey($licenseKey)
    {
        if ($this->licenseKey === $licenseKey) {
            return true;
        }
        if ($this->getStatus() === self::STATUS_VALID) {
            // Deactivate old license
            $this->deactivate();
        }
        if (preg_match('/^[a-f0-9]{32}$/i', $licenseKey) === 1) {
            $this->licenseKey = $licenseKey;
        } else {
            $this->licenseKey = '';
        }
        return $this->clearCache();
    }

    /**
     * Get license key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->licenseKey;
    }

    /**
     * Reset license data cache
     *
     * @param bool $save if true save the entity
     *
     * @return bool return true if license data cache is reset
     */
    public function clearCache($save = true)
    {
        $this->data             = self::DEFAULT_LICENSE_DATA;
        $this->status           = self::STATUS_INVALID;
        $this->type             = AbstractLicense::TYPE_UNKNOWN;
        $this->lastRemoteUpdate = '';
        $this->lastFailureTime  = '';
        return ($save ? $this->save() : true);
    }

    /**
     * Get license data.
     * This function manage the license data cache.
     *
     * @return array<string,scalar> License data
     */
    public function getLicenseData()
    {
        if ($this->licenseKey === '') {
            return $this->data;
        }

        $currentTime = (int) strtotime(gmdate("Y-m-d H:i:s"));

        $lastFailureTime = (int) ($this->lastFailureTime === '' ? 0 : strtotime($this->lastFailureTime));
        if (($currentTime - $lastFailureTime) < self::LICENSE_FAILURE_DELAY_TIME) {
            return $this->data;
        }

        $updatedTime = (int) ($this->lastRemoteUpdate === '' ? 0 : strtotime($this->lastRemoteUpdate));
        if ($this->data['license'] == 'valid') {
            if ($this->data['expires'] === 'lifetime') {
                $expireTime = PHP_INT_MAX;
            } elseif (empty($this->data['expires'])) {
                $expireTime = 0;
            } else {
                $expireTime = (int) strtotime($this->data['expires']);
            }
            // Recheck expired if the license is expired with more than 1 day to avoid unnecessary requests
            $recheckExpired = ($expireTime < ($currentTime - DAY_IN_SECONDS));
        } else {
            $recheckExpired = false;
        }

        if (
            ($currentTime - $updatedTime) > self::LICENSE_CACHE_TIME ||
            $recheckExpired
        ) {
            try {
                $api_params = array(
                    'edd_action' => 'check_license',
                    'license'    => $this->licenseKey,
                    'item_name'  => urlencode(License::EDD_DUPPRO_ITEM_NAME),
                    'url'        => (is_multisite() ? network_home_url() : home_url()),
                );

                if (($remoteData = $this->request($api_params)) === false) {
                    DUP_PRO_Log::trace("Error getting license check response for " . substr($this->licenseKey, 0, 5) . "*** so leaving status alone");
                    $this->lastFailureTime = gmdate("Y-m-d H:i:s");
                } else {
                    $this->clearCache(false);
                    foreach (self::DEFAULT_LICENSE_DATA as $key => $value) {
                        if (isset($remoteData->{$key})) {
                            $this->data[$key] = $remoteData->{$key};
                        }
                    }

                    $this->status           = self::getStatusFromEDDStatus($remoteData->license);
                    $this->type             = (int) (property_exists($remoteData, 'price_id') ? $remoteData->price_id : AbstractLicense::TYPE_UNLICENSED);
                    $this->lastRemoteUpdate = gmdate("Y-m-d H:i:s");
                }
            } finally {
                $this->save();
            }
        }

        return $this->data;
    }

    /**
     * Activate license key
     *
     * @return int license status
     */
    public function activate()
    {
        if (strlen($this->licenseKey) == 0) {
            return self::ACTIVATION_REQUEST_ERROR;
        }

        $api_params = array(
            'edd_action' => 'activate_license',
            'license'    => $this->licenseKey,
            'item_name'  => urlencode(License::EDD_DUPPRO_ITEM_NAME), // the name of our product in EDD,
            'url'        => (is_multisite() ? network_home_url() : home_url()),
        );

        $this->clearCache();
        if (($responseData = $this->request($api_params)) === false) {
            return self::ACTIVATION_REQUEST_ERROR;
        }

        if ($responseData->license == 'valid') {
            DUP_PRO_Log::trace("License Activated " . substr($this->licenseKey, 0, 5) . "***");
            return self::ACTIVATION_RESPONSE_OK;
        } else {
            DUP_PRO_Log::traceObject("Problem activating license " . substr($this->licenseKey, 0, 5) . "***", $responseData);
            return self::ACTIVATION_RESPONSE_INVALID;
        }
    }

    /**
     * Get license status
     *
     * @return int ENUM self::STATUS_*
     */
    public function getStatus()
    {
        $this->getLicenseData();
        return $this->status;
    }

    /**
     * Get license type
     *
     * @return int ENUM AbstractLicense::TYPE_*
     */
    public function getLicenseType()
    {
        $this->getLicenseData();
        return $this->type;
    }

    /**
     * Get license websites limit
     *
     * @return int<0, max>
     */
    public function getLicenseLimit()
    {
        $this->getLicenseData();
        return (int) max(0, (int) $this->data['license_limit']);
    }

    /**
     * Get site count
     *
     * @return int<-1, max>
     */
    public function getSiteCount()
    {
        $this->getLicenseData();
        return (int) max(-1, (int) $this->data['site_count']);
    }

    /**
     * Deactivate license key
     *
     * @return int license status
     */
    public function deactivate()
    {
        if (strlen($this->licenseKey) == 0) {
            return self::ACTIVATION_RESPONSE_OK;
        }

        $api_params = array(
            'edd_action' => 'deactivate_license',
            'license'    => $this->licenseKey,
            'item_name'  => urlencode(License::EDD_DUPPRO_ITEM_NAME), // the name of our product in EDD,
            'url'        => (is_multisite() ? network_home_url() : home_url()),
        );

        $this->clearCache();
        if (($responseData = $this->request($api_params)) === false) {
            return self::ACTIVATION_REQUEST_ERROR;
        }

        if ($responseData->license == 'deactivated') {
            DUP_PRO_Log::trace("Deactivated license " . $this->licenseKey);
            return self::ACTIVATION_RESPONSE_OK;
        } else {
            DUP_PRO_Log::traceObject("Problems deactivating license " . $this->licenseKey, $responseData);
            return self::ACTIVATION_RESPONSE_INVALID;
        }
    }

    /**
     * Get expiration date format
     *
     * @param string $format date format
     *
     * @return string return expirtation date formatted, Unknown if license data is not available or Lifetime if license is lifetime
     */
    public function getExpirationDate($format = 'Y-m-d')
    {
        $this->getLicenseData();
        if ($this->data['expires'] === 'lifetime') {
            return 'Lifetime';
        }
        if (empty($this->data['expires'])) {
            return 'Unknown';
        }
        $expirationDate = new DateTime($this->data['expires']);
        return $expirationDate->format($format);
    }

    /**
     * Return expiration license days, if is expired a negative number is returned
     *
     * @return false|int reutrn false on fail or number of days to expire, PHP_INT_MAX is filetime
     */
    public function getExpirationDays()
    {
        $this->getLicenseData();
        if ($this->data['expires'] === 'lifetime') {
            return PHP_INT_MAX;
        }
        if (empty($this->data['expires'])) {
            return false;
        }
        $expirationDate = new DateTime($this->data['expires']);
        return (-1 * intval($expirationDate->diff(new DateTime())->format('%r%a')));
    }

    /**
     * check is have no activations left
     *
     * @return bool
     */
    public function haveNoActivationsLeft()
    {
        return ($this->getStatus() === self::STATUS_SITE_INACTIVE && $this->data['activations_left'] === 0);
    }

    /**
     * Return true if have active subscription
     *
     * @return bool
     */
    public function haveActiveSubscription()
    {
        $this->getLicenseData();
        return $this->data['activeSubscription'];
    }

    /**
     * Get a license rquest
     *
     * @param mixed[] $params request params
     *
     * @return false|object
     */
    public function request($params)
    {
        global $wp_version;

        DUP_PRO_Log::trace('LICENSE REMOTE REQUEST CMD: ' . $params['edd_action']);

        $postParams = array(
            'timeout'    => 15,
            'sslverify'  => false,
            'user-agent' => "WordPress/" . $wp_version,
            'body'       => $params,
        );

        $requestDetails = JsonSerialize::serialize([
            'url'         => License::EDD_DUPPRO_STORE_URL,
            'curlEnabled' => SnapUtil::isCurlEnabled(true),
            'params'      => $postParams,
        ], JSON_PRETTY_PRINT);

        $this->lastRequestError['code']           = 0;
        $this->lastRequestError['message']        = '';
        $this->lastRequestError['details']        = '';
        $this->lastRequestError['requestDetails'] = '';

        try {
            if (ExpireOptions::get(self::LICENSE_FAILURE_OPT_KEY, false)) {
                $endTime = human_time_diff(time(), ExpireOptions::getExpireTime(self::LICENSE_FAILURE_OPT_KEY));
                // Wait before try again
                $this->lastRequestError['code']           = 1;
                $this->lastRequestError['message']        = sprintf(__('License request failed recently. Wait %s and try again.', 'duplicator-pro'), $endTime);
                $this->lastRequestError['details']        = '';
                $this->lastRequestError['requestDetails'] = '';
                throw new Exception($this->lastRequestError['message'], self::FAILURE_SKIP_EXCEPTION_CODE);
            } else {
                $response = wp_remote_get(License::EDD_DUPPRO_STORE_URL, $postParams);
            }

            if (is_wp_error($response)) {
                /** @var WP_Error  $response */
                $this->lastRequestError['code']           = $response->get_error_code();
                $this->lastRequestError['message']        = $response->get_error_message();
                $this->lastRequestError['details']        = JsonSerialize::serialize($response->get_error_data(), JSON_PRETTY_PRINT);
                $this->lastRequestError['requestDetails'] = $requestDetails;
                throw new Exception($this->lastRequestError['message']);
            } elseif ($response['response']['code'] < 200 || $response['response']['code'] >= 300) {
                $this->lastRequestError['code']           = $response['response']['code'];
                $this->lastRequestError['message']        = $response['response']['message'];
                $this->lastRequestError['details']        = JsonSerialize::serialize($response, JSON_PRETTY_PRINT);
                $this->lastRequestError['requestDetails'] = $requestDetails;
                throw new Exception($this->lastRequestError['message']);
            }

            $data = json_decode(wp_remote_retrieve_body($response));
            if (!is_object($data) || !property_exists($data, 'license')) {
                $this->lastRequestError['code']           = -1;
                $this->lastRequestError['message']        = __('Invalid license data.', 'duplicator-pro');
                $this->lastRequestError['details']        = 'Response: ' . wp_remote_retrieve_body($response);
                $this->lastRequestError['requestDetails'] = $requestDetails;
                throw new Exception($this->lastRequestError['message']);
            }
        } catch (Exception $e) {
            if ($e->getCode() !== self::FAILURE_SKIP_EXCEPTION_CODE) {
                ExpireOptions::set(self::LICENSE_FAILURE_OPT_KEY, true, self::LICENSE_FAILURE_DELAY_TIME);
            }
            DUP_PRO_Log::trace('License request failed: ' . $e->getMessage());
            return false;
        } catch (Error $e) {
            ExpireOptions::set(self::LICENSE_FAILURE_OPT_KEY, true, self::LICENSE_FAILURE_DELAY_TIME);
            DUP_PRO_Log::trace('License request failed: ' . $e->getMessage());
            return false;
        }

        return $data;
    }

    /**
     * Get last error request
     *
     * @return array{code:int, message: string, details: string}
     */
    public function getLastRequestError()
    {
        return $this->lastRequestError;
    }

    /**
     * Get license status from status by string
     *
     * @param string $eddStatus license status string
     *
     * @return int
     */
    private static function getStatusFromEDDStatus($eddStatus)
    {
        switch ($eddStatus) {
            case 'valid':
                return self::STATUS_VALID;
            case 'invalid':
                return self::STATUS_INVALID;
            case 'expired':
                return self::STATUS_EXPIRED;
            case 'disabled':
                return self::STATUS_DISABLED;
            case 'site_inactive':
                return self::STATUS_SITE_INACTIVE;
            case 'inactive':
                return self::STATUS_INACTIVE;
            default:
                return self::STATUS_UNKNOWN;
        }
    }

    /**
     * Return license statu string by status
     *
     * @return string
     */
    public function getLicenseStatusString()
    {
        switch ($this->getStatus()) {
            case self::STATUS_VALID:
                return __('Valid', 'duplicator-pro');
            case self::STATUS_INVALID:
                return __('Invalid', 'duplicator-pro');
            case self::STATUS_EXPIRED:
                return __('Expired', 'duplicator-pro');
            case self::STATUS_DISABLED:
                return __('Disabled', 'duplicator-pro');
            case self::STATUS_SITE_INACTIVE:
                return __('Site Inactive', 'duplicator-pro');
            case self::STATUS_EXPIRED:
                return __('Expired', 'duplicator-pro');
            default:
                return __('Unknown', 'duplicator-pro');
        }
    }
}

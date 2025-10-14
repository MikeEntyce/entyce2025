<?php

/**
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

namespace Duplicator\Addons\AmazonS3Addon\Models;

use DUP_PRO_Global_Entity;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Models\DynamicGlobalEntity;
use Duplicator\Models\Storages\AbstractStorageEntity;
use Exception;

class AmazonS3Storage extends AbstractStorageEntity
{
    /** @var int */
    const DEFAULT_UPLOAD_CHUNK_SIZE_IN_KB = 6000;
    /** @var int */
    const UPLOAD_CHUNK_MIN_SIZE_IN_KB = 5 * 1024;

    /**
     * Get default config
     *
     * @return array<string,scalar>
     */
    protected static function getDefaultConfig()
    {
        $config = parent::getDefaultConfig();
        $config = array_merge(
            $config,
            [
                'access_key'       => '',
                'bucket'           => '',
                'region'           => '',
                'endpoint'         =>   '',
                'secret_key'       => '',
                'storage_class'    => 'STANDARD',
                'ACL_full_control' => true,
            ]
        );
        return $config;
    }

    /**
     * Return the field label
     *
     * @param string $field Field name
     *
     * @return string
     */
    public static function getFieldLabel($field)
    {
        switch ($field) {
            case 'accessKey':
                return __('Access Key', 'duplicator-pro');
            case 'secretKey':
                return __('Secret Key', 'duplicator-pro');
            case 'region':
                return __('Region', 'duplicator-pro');
            case 'endpoint':
                return __('Endpoint', 'duplicator-pro');
            case 'bucket':
                return __('Bucket', 'duplicator-pro');
            case 'aclFullControl':
                return __('Additional Settings', 'duplicator-pro');
            default:
                throw new Exception("Unknown field: $field");
        }
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
        parent::__wakeup();

        if ($this->legacyEntity) {
            // Old storage entity
            $this->legacyEntity = false;
            // Make sure the storage type is right from the old entity
            $this->storage_type = $this->getSType();
            $this->config       = [
                'storage_folder'   => $this->s3_storage_folder,
                'max_packages'     => $this->s3_max_files,
                'access_key'       => $this->s3_access_key,
                'bucket'           => $this->s3_bucket,
                'region'           => $this->s3_region,
                'endpoint'         => $this->s3_endpoint,
                'secret_key'       => $this->s3_secret_key,
                'storage_class'    => $this->s3_storage_class,
                'ACL_full_control' => $this->s3_ACL_full_control,
            ];
            // reset old values
            $this->s3_storage_folder   = '';
            $this->s3_max_files        = 10;
            $this->s3_access_key       = '';
            $this->s3_bucket           = '';
            $this->s3_provider         = 'amazon';
            $this->s3_region           = '';
            $this->s3_endpoint         = '';
            $this->s3_secret_key       = '';
            $this->s3_storage_class    = 'STANDARD';
            $this->s3_ACL_full_control = true;
        }
    }

    /**
     * Return the storage type
     *
     * @return int
     */
    public static function getSType()
    {
        return 4;
    }

    /**
     * Returns the storage type icon.
     *
     * @return string Returns the icon
     */
    public static function getStypeIcon()
    {
        return '<img src="' . esc_url(static::getIconUrl()) . '" class="dup-storage-icon" alt="' . esc_attr(static::getStypeName()) . '" />';
    }

    /**
     * Returns the storage type icon url.
     *
     * @return string The icon url
     */
    protected static function getIconUrl()
    {
        return DUPLICATOR_PRO_IMG_URL . '/aws.svg';
    }


    /**
     * Returns the storage type name.
     *
     * @return string
     */
    public static function getStypeName()
    {
        return __('Amazon S3', 'duplicator-pro');
    }

    /**
     * Get priority, used to sort storages.
     * 100 is neutral value, 0 is the highest priority
     *
     * @return int
     */
    public static function getPriority()
    {
        return 150;
    }

    /**
     * Get storage location string
     *
     * @return string
     */
    public function getLocationString()
    {
        $params = [
            'region' => $this->config['region'],
            'bucket' => $this->config['bucket'],
            'prefix' => $this->getStorageFolder(),
        ];

        return 'https://console.aws.amazon.com/s3/home' . '?' . http_build_query($params);
    }

    /**
     * Returns an html anchor tag of location
     *
     * @return string Returns an html anchor tag with the storage location as a hyperlink.
     *
     * @example
     * OneDrive Example return
     * <a target="_blank" href="https://1drv.ms/f/sAFrQtasdrewasyghg">https://1drv.ms/f/sAFrQtasdrewasyghg</a>
     */
    public function getHtmlLocationLink()
    {
        return '<a href="' . esc_url($this->getLocationString()) . '" target="_blank" >' . esc_html($this->getLocationLabel()) . '</a>';
    }

    /**
     * Returns the storage location label.
     *
     * @return string The storage location label
     */
    protected function getLocationLabel()
    {
        return 's3://' . $this->config['bucket'] . $this->getStorageFolder();
    }

    /**
     * Check if storage is supported
     *
     * @return bool
     */
    public static function isSupported()
    {
        return SnapUtil::isCurlEnabled(true, true);
    }

    /**
     * Get supported notice, displayed if storage isn't supported
     *
     * @return string html string or empty if storage is supported
     */
    public static function getNotSupportedNotice()
    {
        if (static::isSupported()) {
            return '';
        }

        if (!SnapUtil::isCurlEnabled()) {
            $result = sprintf(
                __(
                    "The Storage %s requires the PHP cURL extension and related functions to be enabled.",
                    'duplicator-pro'
                ),
                static::getStypeName()
            );
        } elseif (!SnapUtil::isCurlEnabled(true, true)) {
            $result = sprintf(
                __(
                    "The Storage %s requires 'curl_multi_' type functions to be enabled. One or more are disabled on your server.",
                    'duplicator-pro'
                ),
                static::getStypeName()
            );
        } else {
            $result = sprintf(
                __(
                    'The Storage %s is not supported on this server.',
                    'duplicator-pro'
                ),
                static::getStypeName()
            );
        }

        return esc_html($result);
    }

    /**
     * Check if storage is valid
     *
     * @return bool Return true if storage is valid and ready to use, false otherwise
     */
    public function isValid()
    {
        return $this->getAdapter()->isValid();
    }


    /**
     * Get action key text
     *
     * @param string $key Key name (action, pending, failed, cancelled, success)
     *
     * @return string
     */
    protected function getActionKeyText($key)
    {
        switch ($key) {
            case 'action':
                return sprintf(
                    __('Transferring to %1$s folder:<br/> <i>%2$s</i>', "duplicator-pro"),
                    $this->getStypeName(),
                    $this->getStorageFolder()
                );
            case 'pending':
                return sprintf(
                    __('Transfer to %1$s folder %2$s is pending', "duplicator-pro"),
                    $this->getStypeName(),
                    $this->getStorageFolder()
                );
            case 'failed':
                return sprintf(
                    __('Failed to transfer to %1$s folder %2$s', "duplicator-pro"),
                    $this->getStypeName(),
                    $this->getStorageFolder()
                );
            case 'cancelled':
                return sprintf(
                    __('Cancelled before could transfer to %1$s folder %2$s', "duplicator-pro"),
                    $this->getStypeName(),
                    $this->getStorageFolder()
                );
            case 'success':
                return sprintf(
                    __('Transferred package to %1$s folder %2$s', "duplicator-pro"),
                    $this->getStypeName(),
                    $this->getStorageFolder()
                );
            default:
                throw new Exception('Invalid key');
        }
    }

    /**
     * Render form config fields
     *
     * @param bool $echo Echo or return
     *
     * @return string
     */
    public function renderConfigFields($echo = true)
    {
        return TplMng::getInstance()->render(
            'amazons3addon/configs/amazon_s3',
            [
                'storage'        => $this,
                'maxPackages'    => $this->config['max_packages'],
                'storageFolder'  => $this->config['storage_folder'],
                'accessKey'      => $this->config['access_key'],
                'bucket'         => $this->config['bucket'],
                'region'         => $this->config['region'],
                'endpoint'       => $this->config['endpoint'],
                'secretKey'      => $this->config['secret_key'],
                'storageClass'   => $this->config['storage_class'],
                'aclFullControl' => $this->config['ACL_full_control'],
                'regionOptions'  => self::regionOptions(),
            ],
            $echo
        );
    }

    /**
     * Update data from http request, this method don't save data, just update object properties
     *
     * @param string $message Message
     *
     * @return bool True if success and all data is valid, false otherwise
     */
    public function updateFromHttpRequest(&$message = '')
    {
        if ((parent::updateFromHttpRequest($message) === false)) {
            return false;
        }

        $this->config['max_packages']   = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 's3_max_files', 10);
        $this->config['storage_folder'] = self::getSanitizedInputFolder('_s3_storage_folder');

        $this->config['access_key'] = SnapUtil::sanitizeTextInput(SnapUtil::INPUT_REQUEST, 's3_access_key');
        $secretKey                  = SnapUtil::sanitizeTextInput(SnapUtil::INPUT_REQUEST, 's3_secret_key');
        if (strlen($secretKey) > 0) {
            $this->config['secret_key'] = $secretKey;
        }
        $this->config['region']        = SnapUtil::sanitizeTextInput(SnapUtil::INPUT_REQUEST, 's3_region');
        $this->config['storage_class'] = SnapUtil::sanitizeTextInput(SnapUtil::INPUT_REQUEST, 's3_storage_class');
        $this->config['bucket']        = SnapUtil::sanitizeTextInput(SnapUtil::INPUT_REQUEST, 's3_bucket');


        $message = sprintf(
            __('Storage Updated.', 'duplicator-pro'),
            $this->config['server'],
            $this->getStorageFolder()
        );
        return true;
    }

    /**
     * Get full s3 client
     *
     * @return S3StorageAdapter
     */
    protected function getAdapter()
    {
        $global = DUP_PRO_Global_Entity::getInstance();
        return new S3StorageAdapter(
            $this->config['access_key'],
            $this->config['secret_key'],
            $this->config['region'],
            $this->config['bucket'],
            $this->config['storage_folder'],
            $this->config['endpoint'],
            $this->config['storage_class'],
            $global->ipv4_only,
            !$global->ssl_disableverify,
            ($global->ssl_useservercerts ? '' : DUPLICATOR_PRO_CERT_PATH),
            $this->config['ACL_full_control']
        );
    }

    /**
     * Returns value => label pairs for region drop-down options for S3 Amazon Direct storage type
     *
     * @return string[]
     */
    protected static function regionOptions()
    {
        return array(
            "us-east-1"      => __("US East (N. Virginia)", 'duplicator-pro'),
            "us-east-2"      => __("US East (Ohio)", 'duplicator-pro'),
            "us-west-1"      => __("US West (N. California)", 'duplicator-pro'),
            "us-west-2"      => __("US West (Oregon)", 'duplicator-pro'),
            "af-south-1"     => __("Africa (Cape Town)", 'duplicator-pro'),
            "ap-east-1"      => __("Asia Pacific (Hong Kong)", 'duplicator-pro'),
            "ap-south-1"     => __("Asia Pacific (Mumbai)", 'duplicator-pro'),
            "ap-northeast-1" => __("Asia Pacific (Tokyo)", 'duplicator-pro'),
            "ap-northeast-2" => __("Asia Pacific (Seoul)", 'duplicator-pro'),
            "ap-northeast-3" => __("Asia Pacific (Osaka-Local)", 'duplicator-pro'),
            "ap-southeast-1" => __("Asia Pacific (Singapore)", 'duplicator-pro'),
            "ap-southeast-2" => __("Asia Pacific (Sydney)", 'duplicator-pro'),
            "ap-southeast-3" => __("Asia Pacific (Jakarta)", 'duplicator-pro'),
            "ca-central-1"   => __("Canada (Central)", 'duplicator-pro'),
            "cn-north-1"     => __("China (Beijing)", 'duplicator-pro'),
            "cn-northwest-1" => __("China (Ningxia)", 'duplicator-pro'),
            "eu-central-1"   => __("EU (Frankfurt)", 'duplicator-pro'),
            "eu-west-1"      => __("EU (Ireland)", 'duplicator-pro'),
            "eu-west-2"      => __("EU (London)", 'duplicator-pro'),
            "eu-west-3"      => __("EU (Paris)", 'duplicator-pro'),
            "eu-south-1"     => __("Europe (Milan)", 'duplicator-pro'),
            "eu-north-1"     => __("Europe (Stockholm)", 'duplicator-pro'),
            "me-south-1"     => __("Middle East (Bahrain)", 'duplicator-pro'),
            "sa-east-1"      => __("South America (Sao Paulo)", 'duplicator-pro'),
        );
    }

    /**
     * Register storage type
     *
     * @return void
     */
    public static function registerType()
    {
        parent::registerType();

        add_action('duplicator_update_global_storage_settings', function () {
            $dGlobal = DynamicGlobalEntity::getInstance();

            foreach (static::getDefaultSettings() as $key => $default) {
                $value = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, $key, $default);
                $dGlobal->setVal($key, $value);
            }
            $dGlobal->save();
        });
    }

    /**
     * Get upload chunk size in bytes
     *
     * @return int bytes
     */
    public function getUploadChunkSize()
    {
        $dGlobal = DynamicGlobalEntity::getInstance();
        return $dGlobal->getVal('s3_upload_part_size_in_kb', self::DEFAULT_UPLOAD_CHUNK_SIZE_IN_KB) * 1024;
    }

    /**
     * Get upload chunk timeout in seconds
     *
     * @return int timeout in microseconds, 0 unlimited
     */
    public function getUploadChunkTimeout()
    {
        $global = DUP_PRO_Global_Entity::getInstance();
        return (int) ($global->php_max_worker_time_in_sec <= 0 ? 0 :  $global->php_max_worker_time_in_sec * SECONDS_IN_MICROSECONDS);
    }

    /**
     * Get default settings
     *
     * @return array<string, scalar>
     */
    protected static function getDefaultSettings()
    {
        return ['s3_upload_part_size_in_kb' => 6000];
    }

    /**
     * @return void
     */
    public static function renderGlobalOptions()
    {
        if (self::class !== static::class) {
            return;
        }
        $values  = static::getDefaultSettings();
        $dGlobal = DynamicGlobalEntity::getInstance();
        foreach ($values as $key => $default) {
            $values[$key] = $dGlobal->getVal($key, $default);
        }
        ?>
        <h3 class="title"><?php esc_html_e("Amazon S3", 'duplicator-pro') ?></h3>
        <hr size="1" />
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label><?php esc_html_e("Upload Chunk Size", 'duplicator-pro'); ?></label></th>
                <td>
                    <input
                        class="dup-narrow-input text-right"
                        name="s3_upload_part_size_in_kb"
                        id="s3_upload_part_size_in_kb"
                        type="number"
                        min="<?php echo self::UPLOAD_CHUNK_MIN_SIZE_IN_KB; ?>"
                        max="5243000"
                        data-parsley-required
                        data-parsley-type="number"
                        data-parsley-errors-container="#s3_upload_chunksize_in_kb_error_container"
                        value="<?php echo $values['s3_upload_part_size_in_kb']; ?>"
                    >&nbsp;<b>KB</b>
                    <div id="s3_upload_chunksize_in_kb_error_container" class="duplicator-error-container"></div>
                    <p class="description">
                        <?php esc_html_e('How much should be uploaded to Amazon S3 per attempt. Higher=faster but less reliable.', 'duplicator-pro'); ?>
                        <?php echo esc_html(sprintf(__('Min size %skb.', 'duplicator-pro'), self::UPLOAD_CHUNK_MIN_SIZE_IN_KB)); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Purge old multipart uploads
     *
     * @return void
     */
    public function purgeMultipartUpload()
    {
        $this->getAdapter()->abortMultipartUploads(2);
    }
}

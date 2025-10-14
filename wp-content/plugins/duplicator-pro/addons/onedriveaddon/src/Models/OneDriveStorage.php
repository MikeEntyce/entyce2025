<?php

/**
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

namespace Duplicator\Addons\OneDriveAddon\Models;

use DUP_PRO_Global_Entity;
use DUP_PRO_Log;
use Duplicator\Addons\OneDriveAddon\OnedriveAdapter;
use Duplicator\Addons\OneDriveAddon\OneDriveStoragePathInfo;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Models\DynamicGlobalEntity;
use Duplicator\Models\Storages\AbstractStorageEntity;
use Duplicator\Models\Storages\StorageAuthInterface;
use Duplicator\Utils\OAuth\TokenEntity;
use Duplicator\Utils\OAuth\TokenService;
use Exception;

class OneDriveStorage extends AbstractStorageEntity implements StorageAuthInterface
{
    const GRAPH_SCOPES = [
        'openid',
        'offline_access',
        'files.readwrite.appfolder',
    ];

    const CLIENT_ID = '15fa3a0d-b7ee-447c-8093-7bfcf30b0797';

    const LOGOUT_REDIRECT_URI = 'https://snapcreek.com/misc/onedrive/redir3.php';

    /**
     * @var null|OneDriveAdapter Storage adapter
     */
    protected $adapter = null;

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
                'endpoint_url'           => '',
                'resource_id'            => '',
                'access_token'           => '',
                'refresh_token'          => '',
                'token_obtained'         => 0,
                'storage_folder_id'      => '',
                'storage_folder_web_url' => '',
                'all_folders_perm'       => false,
                'authorized'             => false,
            ]
        );


        return $config;
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
                'endpoint_url'           => $this->onedrive_endpoint_url,
                'resource_id'            => $this->onedrive_resource_id,
                'access_token'           => $this->onedrive_access_token,
                'refresh_token'          => $this->onedrive_refresh_token,
                'token_obtained'         => $this->onedrive_token_obtained,
                'storage_folder'         => ltrim($this->onedrive_storage_folder, '/\\'),
                'max_packages'           => $this->onedrive_max_files,
                'storage_folder_id'      => $this->onedrive_storage_folder_id,
                'storage_folder_web_url' => $this->onedrive_storage_folder_web_url,
                'authorized'             => ($this->onedrive_authorization_state == 1),
            ];
            // reset old values
            $this->onedrive_endpoint_url           = '';
            $this->onedrive_resource_id            = '';
            $this->onedrive_access_token           = '';
            $this->onedrive_refresh_token          = '';
            $this->onedrive_token_obtained         = 0;
            $this->onedrive_storage_folder         = '';
            $this->onedrive_max_files              = 10;
            $this->onedrive_storage_folder_id      = '';
            $this->onedrive_authorization_state    = 0;
            $this->onedrive_storage_folder_web_url = '';
        }
    }

    /**
     * Will be called, automatically, when Serialize
     *
     * @return array<string, mixed>
     */
    public function __serialize() // phpcs:ignore PHPCompatibility.FunctionNameRestrictions.NewMagicMethods.__serializeFound
    {
        $data = parent::__serialize();
        unset($data['client']);
        return $data;
    }

    /**
     * Return the storage type
     *
     * @return int
     */
    public static function getSType()
    {
        return 7;
    }

    /**
     * Returns the storage type icon.
     *
     * @return string Returns the storage icon
     */
    public static function getStypeIcon()
    {
        $imgUrl = DUPLICATOR_PRO_IMG_URL . '/onedrive.svg';
        return '<img src="' . esc_url($imgUrl) . '" class="dup-storage-icon" alt="' . esc_attr(static::getStypeName()) . '" />';
    }

    /**
     * Returns the storage type name.
     *
     * @return string
     */
    public static function getStypeName()
    {
        return __('OneDrive', 'duplicator-pro');
    }

    /**
     * Get storage location string
     *
     * @return string
     */
    public function getLocationString()
    {
        if (!$this->isAuthorized()) {
            return __("Not Authenticated", "duplicator-pro");
        } else {
            return $this->config['storage_folder_web_url'];
        }
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
        if ($this->isAuthorized()) {
            return '<a href="' . esc_url($this->getLocationString()) . '" target="_blank" >' . esc_html($this->getLocationString()) . '</a>';
        } else {
            return $this->getLocationString();
        }
    }

    /**
     * Check if storage is supported
     *
     * @return bool
     */
    public static function isSupported()
    {
        return SnapUtil::isCurlEnabled();
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

        return esc_html__('OneDrive requires the PHP CURL extension enabled.', 'duplicator-pro');
    }

    /**
     * Check if storage is valid
     *
     * @return bool Return true if storage is valid and ready to use, false otherwise
     */
    public function isValid()
    {
        return $this->isAuthorized();
    }

    /**
     * Is autorized
     *
     * @return bool
     */
    public function isAuthorized()
    {
        return $this->config['authorized'];
    }

    /**
     * Get the chunk size bytes
     *
     * @return int
     */
    public function getUploadChunkSize()
    {
        return DynamicGlobalEntity::getInstance()->getVal('onedrive_upload_chunksize_in_kb') * KB_IN_BYTES;
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
     * Authorized from HTTP request
     *
     * @param string $message Message
     *
     * @return bool True if authorized, false if failed
     */
    public function authorizeFromRequest(&$message = '')
    {
        try {
            if (($refreshToken = SnapUtil::sanitizeTextInput(SnapUtil::INPUT_REQUEST, 'auth_code')) === '') {
                throw new Exception(__('Authorization code is empty', 'duplicator-pro'));
            }

            $this->name                     = SnapUtil::sanitizeTextInput(SnapUtil::INPUT_REQUEST, 'name', '');
            $this->notes                    = SnapUtil::sanitizeDefaultInput(SnapUtil::INPUT_REQUEST, 'notes', '');
            $this->config['max_packages']   = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'max_packages', 10);
            $this->config['storage_folder'] = self::getSanitizedInputFolder('storage_folder', 'remove');

            $this->revokeAuthorization();

            $token = (new TokenEntity(self::getSType(), ['refresh_token' => $refreshToken]));
            if (! $token->refresh(true)) {
                throw new Exception(__('Failed to fetch information from OneDrive. Make sure the token is valid.', 'duplicator-pro'));
            }

            $this->config['access_token']   = $token->getAccessToken();
            $this->config['refresh_token']  = $token->getRefreshToken();
            $this->config['token_obtained'] = $token->getCreated();
            $this->config['endpoint_url']   = $this->config['resource_id'] = '';
            $this->config['authorized']     = true;


            DUP_PRO_Log::traceObject("OneDrive App folder: ", $storageFolder = $this->getOneDriveStorageFolder());

            if (! $storageFolder) {
                throw new Exception("Failed to fetch information from OneDrive. Make sure the token is valid.");
            }

            // Get the storage folder id
            $this->config['storage_folder_id']      = $storageFolder->id;
            $this->config['storage_folder_web_url'] = $storageFolder->webUrl;
        } catch (Exception $e) {
            DUP_PRO_Log::trace("Problem authorizing OneDrive access token msg: " . $e->getMessage());
            $message = $e->getMessage();
            return false;
        }

        $message = __('OneDrive is connected successfully and Storage Provider Updated.', 'duplicator-pro');
        return true;
    }

    /**
     * Revokes authorization
     *
     * @param string $message Message
     *
     * @return bool True if authorized, false if failed
     */
    public function revokeAuthorization(&$message = '')
    {
        if (!$this->isAuthorized()) {
            $message = __('Onedrive isn\'t authorized.', 'duplicator-pro');
            return true;
        }

        $this->config['endpoint_url']           = '';
        $this->config['resource_id']            = '';
        $this->config['access_token']           = '';
        $this->config['refresh_token']          = '';
        $this->config['token_obtained']         = 0;
        $this->config['storage_folder_id']      = '';
        $this->config['storage_folder_web_url'] = '';
        $this->config['authorized']             = false;

        $message = __('Onedrive is disconnected successfully.', 'duplicator-pro');
        return true;
    }

    /**
     * Get external revoke url
     *
     * @return string
     */
    public function getExternalRevokeUrl()
    {
        $base_url   = "https://login.microsoftonline.com/common/oauth2/v2.0/logout";
        $fields_arr = [
            "client_id"                => self::CLIENT_ID,
            "post_logout_redirect_uri" => self::LOGOUT_REDIRECT_URI,
        ];
        $query      = http_build_query($fields_arr);

        return $base_url . "?$query";
    }

    /**
     * Get authorization URL
     *
     * @return string
     */
    public function getAuthorizationUrl()
    {
        return (new TokenService(static::getSType()))->getRedirectUri();
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
     * Update all permissions
     *
     * @param bool $allFoldersPerm All folders permission
     *
     * @return bool True if success, false otherwise
     */
    public function setAllPermissions($allFoldersPerm)
    {
        $this->config['all_folders_perm'] = $allFoldersPerm;
        return $this->save();
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
        $hasError = $this->isAuthorized() && !$this->getAdapter()->isValid();

        return TplMng::getInstance()->render(
            'onedriveaddon/configs/onedrive',
            [
                'storage'           => $this,
                'storageFolder'     => $this->config['storage_folder'],
                'maxPackages'       => $this->config['max_packages'],
                'allFolderPers'     => $this->config['all_folders_perm'],
                'accountInfo'       => $this->getAccountInfo(),
                'hasError'          => $hasError,
                'externalRevokeUrl' => $this->getExternalRevokeUrl(),
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

        $this->config['max_packages']   = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'onedrive_msgraph_max_files', 10);
        $oldFolder                      = $this->config['storage_folder'];
        $this->config['storage_folder'] = self::getSanitizedInputFolder('_onedrive_msgraph_storage_folder', 'remove');

        if ($this->isAuthorized() && $oldFolder != $this->config['storage_folder']) {
            $this->config['storage_folder_id'] = '';
            // Create new folder
            $folder                            = $this->getOneDriveStorageFolder();
            $this->config['storage_folder_id'] = $folder->id;
            $this->config['storage_folder_web_url'] = $folder->webUrl;
            $this->save();
        }

        $message = sprintf(
            __('OneDrive Storage Updated.', 'duplicator-pro'),
            $this->getStorageFolder()
        );
        return true;
    }

    /**
     * Get account info
     *
     * @return false|object
     */
    protected function getAccountInfo()
    {
        if (! $this->isAuthorized()) {
            return false;
        }

        $storageFolder = $this->getOneDriveStorageFolder();

        if (!$storageFolder || empty($storageFolder->user)) {
            return false;
        }

        return (object) $storageFolder->user;
    }

    /**
     * Get onedrive storage folder
     *
     * @return OneDriveStoragePathInfo|false
     */
    protected function getOneDriveStorageFolder()
    {
        $adapter = $this->getAdapter();

        if (! $adapter->isValid()) {
            DUP_PRO_Log::trace("OneDrive adapter is not valid, can't get storage folder.");
            return false;
        }

        if (!$this->config['storage_folder_id']) {
            if (! $adapter->initialize($error)) {
                DUP_PRO_Log::trace("Failed to initialize OneDrive adapter: $error");
                return false;
            }
            $folder                            = $adapter->getPathInfo('/');
            $this->config['storage_folder_id'] = $folder->id;
            $this->save();
        } else {
            $folder = $adapter->getPathInfo('/');
        }

        return $folder;
    }

    /**
     * Get stoage adapter
     *
     * @return OnedriveAdapter
     */
    protected function getAdapter()
    {
        if (!$this->adapter) {
            $global        = DUP_PRO_Global_Entity::getInstance();
            $token         = $this->getTokenFromConfig();
            $this->adapter = new OneDriveAdapter(
                $token,
                $this->config['storage_folder'],
                $this->config['storage_folder_id'],
                !$global->ssl_disableverify,
                ($global->ssl_useservercerts ? '' : DUPLICATOR_PRO_CERT_PATH)
            );
            if (! $this->adapter->initialize($error)) {
                DUP_PRO_Log::trace("Failed to initialize OneDrive adapter: $error");
            }
        }
        return $this->adapter;
    }

    /**
     * Get the token entity using config values
     *
     * @return TokenEntity|false
     */
    public function getTokenFromConfig()
    {
        $token = new TokenEntity(self::getSType(), [
            'created'       => $this->config['token_obtained'],
            'expires_in'    => 3600,
            'scope'         => self::GRAPH_SCOPES,
            'access_token'  => $this->config['access_token'],
            'refresh_token' => $this->config['refresh_token'],
        ]);

        if ($token->isAboutToExpire()) {
            $token->refresh(true);
            if (!$token->isValid()) {
                return false;
            }
            $this->config['token_obtained'] = $token->getCreated();
            $this->config['refresh_token']  = $token->getRefreshToken();
            $this->config['access_token']   = $token->getAccessToken();
            $this->save();
        }

        return $token;
    }

    /**
     * @return void
     * @throws Exception
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
        });
    }

    /**
     * Get default settings
     *
     * @return array<string, scalar>
     */
    protected static function getDefaultSettings()
    {
        return ['onedrive_upload_chunksize_in_kb' => DUPLICATOR_PRO_ONEDRIVE_UPLOAD_CHUNK_DEFAULT_SIZE_IN_KB];
    }

    /**
     * @return void
     */
    public static function renderGlobalOptions()
    {
        $values  = static::getDefaultSettings();
        $dGlobal = DynamicGlobalEntity::getInstance();
        foreach ($values as $key => $default) {
            $values[$key] = $dGlobal->getVal($key, $default);
        }
        ?>
        <h3 class="title"><?php echo esc_html(static::getStypeName()) ?></h3>
        <hr size="1" />
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label><?php esc_html_e("Upload Chunk Size", 'duplicator-pro'); ?></label></th>
                <td>
                    <input 
                        type="number"
                        name="onedrive_upload_chunksize_in_kb"
                        id="onedrive_upload_chunksize_in_kb"
                        class="dup-narrow-input text-right"
                        step="320"
                        min="<?php echo intval(DUPLICATOR_PRO_ONEDRIVE_UPLOAD_CHUNK_MIN_SIZE_IN_KB); ?>"
                        data-parsley-required
                        data-parsley-type="number"
                        data-parsley-errors-container="#onedrive_upload_chunksize_in_kb_error_container"
                        value="<?php echo (int) $values['onedrive_upload_chunksize_in_kb']; ?>" 
                    >&nbsp;<b>KB</b>
                    <div id="onedrive_upload_chunksize_in_kb_error_container" class="duplicator-error-container"></div>
                    <p class="description">
                        <?php printf(esc_html__(
                            'How much should be uploaded to OneDrive per attempt. It should be multiple of %dkb. Higher=faster but less reliable.',
                            'duplicator-pro'
                        ), (int) DUPLICATOR_PRO_ONEDRIVE_UPLOAD_CHUNK_MIN_SIZE_IN_KB);
                        // https://docs.microsoft.com/en-us/onedrive/developer/rest-api/api/driveitem_createuploadsession?view=odsp-graph-online#upload-bytes-to-the-upload-session
                        printf(
                            esc_html__('Default size %1$dkb. Min size %2$dkb.', 'duplicator-pro'),
                            (int) DUPLICATOR_PRO_ONEDRIVE_UPLOAD_CHUNK_DEFAULT_SIZE_IN_KB,
                            (int) DUPLICATOR_PRO_ONEDRIVE_UPLOAD_CHUNK_MIN_SIZE_IN_KB
                        ); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }
}

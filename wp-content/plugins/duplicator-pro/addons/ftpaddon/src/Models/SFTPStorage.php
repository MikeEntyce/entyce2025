<?php

/**
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

namespace Duplicator\Addons\FtpAddon\Models;

use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Models\Storages\AbstractStorageEntity;
use Exception;

class SFTPStorage extends AbstractStorageEntity
{
    const MIN_UPLOAD_CHUNK_SIZE_IN_MB     = 1;
    const DEFAULT_UPLOAD_CHUNK_SIZE_IN_MB = 5;
    const MAX_UPLOAD_CHUNK_SIZE_IN_MB     = 100;

    /**
     * Get stoage adapter
     *
     * @return SFTPStorageAdapter
     */
    protected function getAdapter()
    {
        return new SFTPStorageAdapter(
            $this->config['server'],
            $this->config['port'],
            $this->config['username'],
            $this->config['password'],
            $this->config['storage_folder'],
            $this->config['private_key'],
            $this->config['private_key_password'],
            $this->config['timeout_in_secs']
        );
    }

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
                'server'               => '',
                'port'                 => 22,
                'username'             => '',
                'password'             => '',
                'private_key'          => '',
                'private_key_password' => '',
                'timeout_in_secs'      => 15,
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
                'server'               => $this->sftp_server,
                'port'                 => $this->sftp_port,
                'username'             => $this->sftp_username,
                'password'             => $this->sftp_password,
                'private_key'          => $this->sftp_private_key,
                'private_key_password' => $this->sftp_private_key_password,
                'storage_folder'       => '/' . ltrim($this->sftp_storage_folder, '/\\'),
                'max_packages'         => $this->sftp_max_files,
                'timeout_in_secs'      => $this->sftp_timeout_in_secs,
            ];
            // reset old values
            $this->sftp_server                = '';
            $this->sftp_port                  = 22;
            $this->sftp_username              = '';
            $this->sftp_password              = '';
            $this->sftp_private_key           = '';
            $this->sftp_private_key_password  = '';
            $this->sftp_storage_folder        = '';
            $this->sftp_max_files             = 10;
            $this->sftp_timeout_in_secs       = 15;
            $this->sftp_disable_chunking_mode = false;
        }

        // For legacy entities, we need to make sure the config is up to date
        $this->config['port']            = (int) $this->config['port'];
        $this->config['max_packages']    = (int) $this->config['max_packages'];
        $this->config['timeout_in_secs'] = (int) $this->config['timeout_in_secs'];
    }

    /**
     * Return the storage type
     *
     * @return int
     */
    public static function getSType()
    {
        return 5;
    }

    /**
     * Returns the FontAwesome storage type icon.
     *
     * @return string Returns the font-awesome icon
     */
    public static function getStypeIcon()
    {
        return '<i class="fas fa-network-wired fa-fw"></i>';
    }

    /**
     * Returns the storage type name.
     *
     * @return string
     */
    public static function getStypeName()
    {
        return __('SFTP', 'duplicator-pro');
    }

    /**
     * Get storage location string
     *
     * @return string
     */
    public function getLocationString()
    {
        return $this->config['server'] . ":" . $this->config['port'];
    }

    /**
     * Get priority, used to sort storages.
     * 100 is neutral value, 0 is the highest priority
     *
     * @return int
     */
    public static function getPriority()
    {
        return 90;
    }

    /**
     * Check if storage is supported
     *
     * @return bool
     */
    public static function isSupported()
    {
        return extension_loaded('gmp');
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

        return sprintf(
            _x(
                'SFTP requires the %1$sgmp extension%2$s. Please contact your host to install.',
                '1: <a> tag, 2: </a> tag',
                'duplicator-pro'
            ),
            '<a href="http://php.net/manual/en/book.gmp.php" target="_blank">',
            '</a>'
        );
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
                    __('Transferring to SFTP server %1$s in folder:<br/> <i>%2$s</i>', "duplicator-pro"),
                    $this->config['server'],
                    $this->getStorageFolder()
                );
            case 'pending':
                return sprintf(
                    __('Transfer to SFTP server %1$s in folder %2$s is pending', "duplicator-pro"),
                    $this->config['server'],
                    $this->getStorageFolder()
                );
            case 'failed':
                return sprintf(
                    __('Failed to transfer to SFTP server %1$s in folder %2$s', "duplicator-pro"),
                    $this->config['server'],
                    $this->getStorageFolder()
                );
            case 'cancelled':
                return sprintf(
                    __('Cancelled before could transfer to SFTP server:<br/>%1$s in folder %2$s', "duplicator-pro"),
                    $this->config['server'],
                    $this->getStorageFolder()
                );
            case 'success':
                return sprintf(
                    __('Transferred package to SFTP server:<br/>%1$s in folder %2$s', "duplicator-pro"),
                    $this->config['server'],
                    $this->getStorageFolder()
                );
            default:
                throw new Exception('Invalid key');
        }
    }

    /**
     * List quick view
     *
     * @param bool $echo Echo or return
     *
     * @return string
     */
    public function getListQuickView($echo = true)
    {
        ob_start();
        ?>
        <div>
            <label><?php esc_html_e('Server', 'duplicator-pro'); ?>:</label>
            <?php echo esc_html($this->config['server']); ?>: <?php echo intval($this->config['port']);  ?>  <br/>
            <label><?php esc_html_e('Location', 'duplicator-pro') ?>:</label>
            <?php
                echo wp_kses(
                    $this->getHtmlLocationLink(),
                    [
                        'a' => [
                            'href'   => [],
                            'target' => [],
                        ],
                    ]
                );
            ?>
        </div>
        <?php
        if ($echo) {
            ob_end_flush();
            return '';
        } else {
            return ob_get_clean();
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
            'ftpaddon/configs/sftp',
            [
                'storage'       => $this,
                'server'        => $this->config['server'],
                'port'          => $this->config['port'],
                'username'      => $this->config['username'],
                'password'      => $this->config['password'],
                'privateKey'    => $this->config['private_key'],
                'privateKeyPwd' => $this->config['private_key_password'],
                'storageFolder' => $this->config['storage_folder'],
                'maxPackages'   => $this->config['max_packages'],
                'timeout'       => $this->config['timeout_in_secs'],
            ],
            $echo
        );
    }

    /**
     * Get upload chunk size in bytes
     *
     * @return int bytes
     */
    public function getUploadChunkSize()
    {
        return -1;
    }

    /**
     * Get upload chunk timeout in seconds
     *
     * @return int timeout in microseconds, 0 unlimited
     */
    public function getUploadChunkTimeout()
    {
        return (int) ($this->config['timeout_in_secs'] <= 0 ? 0 :  $this->config['timeout_in_secs'] * SECONDS_IN_MICROSECONDS);
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

        $this->config['max_packages'] = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'sftp_max_files', 10);
        $this->config['server']       = SnapUtil::sanitizeTextInput(SnapUtil::INPUT_REQUEST, 'sftp_server', '');
        $this->config['port']         = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'sftp_port', 10);
        $this->config['username']     = SnapUtil::sanitizeTextInput(SnapUtil::INPUT_REQUEST, 'sftp_username', '');
        $password                     = SnapUtil::sanitizeTextInput(SnapUtil::INPUT_REQUEST, 'sftp_password', '');
        $password2                    = SnapUtil::sanitizeTextInput(SnapUtil::INPUT_REQUEST, 'sftp_password2', '');
        $this->config['private_key']  = SnapUtil::sanitizeDefaultInput(SnapUtil::INPUT_REQUEST, 'sftp_private_key', '');
        $keyPassword                  = SnapUtil::sanitizeTextInput(SnapUtil::INPUT_REQUEST, 'sftp_private_key_password', '');
        $keyPassword2                 = SnapUtil::sanitizeTextInput(SnapUtil::INPUT_REQUEST, 'sftp_private_key_password2', '');
        if (strlen($password) > 0) {
            if ($password !== $password2) {
                $message = __('Passwords do not match', 'duplicator-pro');
                return false;
            }
            $this->config['password'] = $password;
        } elseif (strlen($keyPassword) > 0) {
            if ($keyPassword !== $keyPassword2) {
                $message = __('Priva key Passwords do not match', 'duplicator-pro');
                return false;
            }
            $this->config['private_key_password'] = $keyPassword;
        }
        $this->config['storage_folder']  = self::getSanitizedInputFolder('_sftp_storage_folder', 'add');
        $this->config['timeout_in_secs'] = max(10, SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'sftp_timeout_in_secs', 15));

        $errorMsg = '';
        if ($this->getAdapter()->initialize($errorMsg) === false) {
            $message = sprintf(
                __('Failed to connect to SFTP server with message: %1$s', 'duplicator-pro'),
                $errorMsg
            );
            return false;
        }

        $message = sprintf(
            __('SFTP Storage Updated - Server %1$s, Folder %2$s was created.', 'duplicator-pro'),
            $this->config['server'],
            $this->getStorageFolder()
        );
        return true;
    }
}

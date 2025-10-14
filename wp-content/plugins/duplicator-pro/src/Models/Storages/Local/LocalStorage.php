<?php

/**
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

namespace Duplicator\Models\Storages\Local;

use DUP_PRO_Global_Entity;
use DUP_PRO_Installer;
use DUP_PRO_Log;
use DUP_PRO_Package;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapIO;
use Duplicator\Libs\Snap\SnapOS;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Models\DynamicGlobalEntity;
use Duplicator\Models\Storages\AbstractStorageEntity;
use Duplicator\Models\Storages\AbstractStorageAdapter;
use Duplicator\Utils\PathUtil;
use Exception;

class LocalStorage extends AbstractStorageEntity
{
    const LOCAL_STORAGE_CHUNK_SIZE_IN_MB = 16;

    /**
     * Get default config
     *
     * @return array<string,scalar>
     */
    protected static function getDefaultConfig()
    {
        $config                      = parent::getDefaultConfig();
        $config['storage_folder']    = '';
        $config['filter_protection'] = true;
        return $config;
    }

    /**
     * Wakeup method
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
                'storage_folder'    => '/' . ltrim($this->local_storage_folder, '/\\'),
                'max_packages'      => $this->local_max_files,
                'filter_protection' => $this->local_filter_protection,
            ];

            // reset old values
            $this->local_storage_folder    = '';
            $this->local_max_files         = 0;
            $this->local_filter_protection = true;
        }
    }

    /**
     * Return the storage type
     *
     * @return int
     */
    public static function getSType()
    {
        return 0;
    }

    /**
     * Returns the FontAwesome storage type icon.
     *
     * @return string Returns the font-awesome icon
     */
    public static function getStypeIcon()
    {
        return '<i class="fas fa-hdd fa-fw"></i>';
    }

    /**
     * Returns the storage type name.
     *
     * @return string
     */
    public static function getStypeName()
    {
        return __('Local', 'duplicator-pro');
    }

    /**
     * Get priority, used to sort storages.
     * 100 is neutral value, 0 is the highest priority
     *
     * @return int
     */
    public static function getPriority()
    {
        return 50;
    }

    /**
     * Returns true if storage type is local
     *
     * @return bool
     */
    public static function isLocal()
    {
        return true;
    }

    /**
     * Get storage location string
     *
     * @return string
     */
    public function getLocationString()
    {
        return $this->getStorageFolder();
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
        return '<span>' . $this->getStorageFolder() . '</span>';
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
     * Delete view
     *
     * @param bool $echo Echo or return
     *
     * @return string HTML string
     */
    public function getDeleteView($echo = true)
    {
        ob_start();
        ?>
        <div class="item">
            <span class="lbl">Name:</span><?php echo esc_html($this->getName()); ?><br>
            <span class="lbl">Type:</span>
            <?php echo wp_kses($this->getStypeIcon(), ['i' => ['class' => []]]); ?>&nbsp;<?php echo esc_html($this->getStypeName()); ?><br>
            <span class="lbl">Folder:</span><?php echo esc_html($this->getLocationString()); ?><br>
            <span class="lbl">Note:</span><span class="maroon">
                <i class="fas fa-exclamation-triangle"></i>
                &nbsp;<?php esc_html_e('By removing this storage all backups inside it will be deleted.', 'duplicator-pro') ?>
            </span><br>

        </div>
        <?php
        if ($echo) {
            ob_end_flush();
            return '';
        } else {
            return (string) ob_get_clean();
        }
    }

    /**
     * Is filter protection enabled
     *
     * @return bool
     */
    public function isFilterProtection()
    {
        return $this->config['filter_protection'];
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
                return __('Copying to directory:', 'duplicator-pro') . '<br>' . $this->getStorageFolder();
            case 'pending':
                return sprintf(__('Copy to directory %1$s is pending', "duplicator-pro"), $this->getStorageFolder());
            case 'failed':
                return sprintf(__('Failed to copy to directory %1$s', "duplicator-pro"), $this->getStorageFolder());
            case 'cancelled':
                return sprintf(__('Cancelled before could copy to directory %1$s', "duplicator-pro"), $this->getStorageFolder());
            case 'success':
                return sprintf(__('Copied package to directory %1$s', "duplicator-pro"), $this->getStorageFolder());
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
            'admin_pages/storages/configs/local',
            [
                'storage'            => $this,
                'maxPackages'        => $this->config['max_packages'],
                'isFilderProtection' => $this->config['filter_protection'],
                'storageFolder'      => $this->config['storage_folder'],
            ],
            $echo
        );
    }

    /**
     * Get stoage adapter
     *
     * @return AbstractStorageAdapter
     */
    protected function getAdapter()
    {
        if ($this->adapter == null) {
            $this->adapter = new LocalStorageAdapter($this->getStorageFolder());
        }
        return $this->adapter;
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
        $this->config['filter_protection'] = SnapUtil::sanitizeBoolInput(SnapUtil::INPUT_REQUEST, '_local_filter_protection');
        $this->config['max_packages']      = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'local_max_files', 10);


        if (SnapOS::isWindows()) {
            $newFolder = self::getSanitizedInputFolder('_local_storage_folder', 'none');
        } else {
            $newFolder = self::getSanitizedInputFolder('_local_storage_folder', 'add');
        }

        if ($this->updateFolderCheck($newFolder, $message) === false) {
            return false;
        }

        if ($this->initStorageDirectory() == false) {
            $message = sprintf(
                __('Storage Provider Updated - Unable to init folder %1$s.', 'duplicator-pro'),
                $this->config['storage_folder']
            );
            return false;
        }

        $message = sprintf(
            __('Storage Provider Updated - Folder %1$s was created.', 'duplicator-pro'),
            $this->config['storage_folder']
        );
        return true;
    }

    /**
     * Update folder
     *
     * @param string $newFolder New folder
     * @param string $message   Error message
     *
     * @return bool
     */
    protected function updateFolderCheck($newFolder, &$message = '')
    {
        if ($this->config['storage_folder'] === $newFolder) {
            return true;
        }
        $this->config['storage_folder'] = $newFolder;
        if (strlen($this->config['storage_folder']) == 0) {
            $message = __('Local storage path can\'t be empty.', 'duplicator-pro');
            return false;
        }
        if (PathUtil::isPathInCoreDirs($this->config['storage_folder'])) {
            $message = __(
                'This storage path can\'t be used because it is a core WordPress directory or a sub-path of a core directory.',
                'duplicator-pro'
            );
            return false;
        }
        if ($this->isPathRepeated()) {
            $message = __(
                'A local storage already exists or current folder is a child of another existing storage folder.',
                'duplicator-pro'
            );
            return false;
        }
        if (!self::haveExtraFilesInFolder($this->config['storage_folder'])) {
            $message = __('Selected storage path already exists and isn\'t empty select another path.', 'duplicator-pro') . ' ' .
                __('Select another folder or remove all files that are not backup archives.', 'duplicator-pro');
            return false;
        }

        return true;
    }

    /**
     * Creates the snapshot directory if it doesn't already exists
     *
     * @param bool $skipIfExists If true it will skip creating the directory if it already exists
     *
     * @return bool True if success, false otherwise
     */
    public function initStorageDirectory($skipIfExists = false)
    {
        $adapter = $this->getAdapter();
        if ($adapter->isValid() && $skipIfExists) {
            return true;
        }

        $errorMsg = '';
        if ($adapter->initialize($errorMsg) == false) {
            DUP_PRO_Log::infoTrace($errorMsg);
            return false;
        }

        self::setupStorageHtaccess($adapter);
        self::setupStorageIndex($adapter);
        self::setupStorageDirRobotsFile($adapter);
        self::performHardenProcesses($adapter);

        return true;
    }

    /**
     * Get upload chunk size in bytes
     *
     * @return int bytes
     */
    public function getUploadChunkSize()
    {
        return DynamicGlobalEntity::getInstance()->getVal('local_upload_chunksize_in_MB') * MB_IN_BYTES;
    }

    /**
     * Return package transfer files
     *
     * @param DUP_PRO_Package $package the package
     *
     * @return array<string,string> return array from => to
     */
    protected function getPackageTransferFiles(DUP_PRO_Package $package)
    {
        return array(
            $package->Installer->getSafeFilePath() => basename($package->Installer->getSafeFilePath()),
            $package->Archive->getSafeFilePath()   => basename($package->Archive->getSafeFilePath()),
        );
    }

    /**
     * Delete this storage
     *
     * @return bool True on success, or false on error.
     */
    public function delete()
    {
        if (parent::delete() === false) {
            return false;
        }

        $adapter = $this->getAdapter();
        if (self::haveExtraFilesInFolder($adapter)) {
            $adapter->destroy();
        } else {
            // Don't delete the folder if it's not empty but don't show an error
            DUP_PRO_Log::infoTrace("Storage folder is not empty, can't delete it");
        }

        return true;
    }

    /**
     * Checks if the storage path is already used by another local storage or is a child of another local storage
     *
     * @return bool Whether the storage path is already used by another local storage
     */
    protected function isPathRepeated()
    {
        $storages = self::getAll();
        foreach ($storages as $storage) {
            if (
                !$storage->isLocal() ||
                $storage->id == $this->id
            ) {
                continue;
            }
            if (
                SnapIO::isChildPath(
                    $this->config['storage_folder'],
                    $storage->getStorageFolder(),
                    false,
                    true,
                    true
                )
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Attempts to create a secure .htaccess file in the download directory
     *
     * @param AbstractStorageAdapter $adapter Storage adapter
     *
     * @return bool True if success, false otherwise
     */
    protected static function setupStorageHtaccess(AbstractStorageAdapter $adapter)
    {
        try {
            $fileName = '.htaccess';

            if (DUP_PRO_Global_Entity::getInstance()->storage_htaccess_off) {
                $adapter->delete($fileName);
            } elseif (!file_exists($fileName)) {
                $fileContent = <<<FILECONTENT
# Duplicator config, In case of file downloading problem, you can disable/enable it in Settings/Sotrag plugin settings

Options -Indexes
<IfModule mod_headers.c>
    <FilesMatch "\.(daf)$">
        ForceType application/octet-stream
        Header set Content-Disposition attachment
    </FilesMatch>
</IfModule>
FILECONTENT;
                if ($adapter->createFile($fileName, $fileContent) === false) {
                    throw new Exception('Can\'t create ' . $fileName);
                }
            }
        } catch (Exception $ex) {
            DUP_PRO_Log::Trace("Unable create file htaccess {$fileName} msg:" . $ex->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Attempts to create an index.php file in the backups directory
     *
     * @param AbstractStorageAdapter $adapter Storage adapter
     *
     * @return bool True if success, false otherwise
     */
    protected static function setupStorageIndex(AbstractStorageAdapter $adapter)
    {
        $fileName    = 'index.php';
        $fileContent = <<<INDEXPHP
<?php
// silence
INDEXPHP;
        if ($adapter->createFile($fileName, $fileContent) === false) {
            DUP_PRO_Log::Trace("Unable create index.php at {$fileName}");
            return false;
        }
        return true;
    }

    /**
    * Attempts to create a robots.txt file in the backups directory
    * to prevent search engines
    *
    * @param AbstractStorageAdapter $adapter Storage adapter
    *
    * @return bool True if success, false otherwise
    */
    protected static function setupStorageDirRobotsFile(AbstractStorageAdapter $adapter)
    {
        try {
            $fileName = 'robots.txt';

            if (!file_exists($fileName)) {
                $fileContent = <<<FILECONTENT
User-agent: *
Disallow: /
FILECONTENT;
                if ($adapter->createFile($fileName, $fileContent) === false) {
                    throw new Exception('Can\'t create ' . $fileName);
                }
            }
        } catch (Exception $ex) {
            DUP_PRO_Log::Trace("Unable create robots.txt {$fileName} msg:" . $ex->getMessage());
            return false;
        }

        return true;
    }

    /**
    * Run various secure processes to harden the backups dir
    *
    * @param AbstractStorageAdapter $adapter Storage adapter
    *
    * @return bool True if success, false otherwise
    */
    public static function performHardenProcesses(AbstractStorageAdapter $adapter)
    {
        try {
            //Edge Case: Remove any installer dirs
            $adapter->delete('dup-installer', true);

            foreach ($adapter->scanDir('', true, false) as $path) {
                if (preg_match('/^.+_installer.*\.php$/', $path) !== 1) {
                    continue;
                }
                $parts   = pathinfo($path);
                $newPath = ltrim($parts['dirname'], '/\\.') . '/' . $parts['filename'] . DUP_PRO_Installer::INSTALLER_SERVER_EXTENSION;
                $adapter->move($path, $newPath);
            }
        } catch (Exception $ex) {
            DUP_PRO_Log::Trace("Unable to cleanup the storage folder msg:" . $ex->getMessage());
            return false;
        }

        return true;
    }

    /**
    * Check if folder is empty or have only package files
    *
    * @param string|AbstractStorageAdapter $path The folder path
    *
    * @return bool True is ok, false otherwise
    */
    protected static function haveExtraFilesInFolder($path)
    {
        if (!$path instanceof LocalStorageAdapter) {
            $adapter = new LocalStorageAdapter($path);
        } else {
            $adapter = $path;
        }

        return $adapter->isDirEmpty(
            '',
            [
                'index.php',
                'robots.txt',
                '.htaccess',
                'index.html',
                DUPLICATOR_PRO_GEN_FILE_REGEX_PATTERN,
            ]
        );
    }

    /**
     * @return array<string,scalar>
     */
    protected static function getDefaultSettings()
    {
        return [
            'local_upload_chunksize_in_MB' => LocalStorage::LOCAL_STORAGE_CHUNK_SIZE_IN_MB,
        ];
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
        <h3 class="title"><?php esc_html_e("Local Storage", 'duplicator-pro') ?></h3>
        <hr size="1" />
        <table class="form-table">
        <table class="form-table">
            <tr valign="top">
                <th scope="row">
                    <label><?php esc_html_e("Upload Chunk Size", 'duplicator-pro'); ?></label>
                </th>
                <td>
                    <input 
                        name="local_upload_chunksize_in_MB"
                        id="local_upload_chunksize_in_MB"
                        class="dup-narrow-input text-right"
                        type="number"
                        min="<?php echo 1; ?>"
                        max="<?php echo 1024; ?>"
                        data-parsley-required
                        data-parsley-type="number"
                        data-parsley-errors-container="#local_upload_chunksize_in_MB_error_container"
                        value="<?php echo (int) $values['local_upload_chunksize_in_MB']; ?>" 
                    >&nbsp;<b>MB</b>
                    <div id="local_upload_chunksize_in_MB_error_container" class="duplicator-error-container"></div>
                    <p class="description">
                        <?php esc_html__('How much should be copied to Local Storages per attempt. Higher=faster but less reliable.', 'duplicator-pro'); ?>
                        <?php
                        printf(
                            esc_html__('Default size %1$dMB. Min size %2$dMB.', 'duplicator-pro'),
                            (int) LocalStorage::LOCAL_STORAGE_CHUNK_SIZE_IN_MB,
                            1
                        );
                        ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }
}

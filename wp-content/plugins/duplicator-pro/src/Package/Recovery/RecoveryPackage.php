<?php

/**
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

namespace Duplicator\Package\Recovery;

use DUP_PRO_Global_Entity;
use DUP_PRO_Log;
use DUP_PRO_Package;
use DUP_PRO_Package_File_Type;
use DUP_PRO_PackageStatus;
use Duplicator\Controllers\RecoveryController;
use Duplicator\Libs\Snap\SnapIO;
use Duplicator\Libs\Snap\SnapURL;
use Duplicator\Package\Recovery\RecoveryStatus;
use Duplicator\Utils\PHPExecCheck;
use Error;
use Exception;

class RecoveryPackage extends BackupPackage
{
    const MAX_PACKAGES_LIST         = 50;
    const OPTION_RECOVER_PACKAGE_ID = 'duplicator_pro_recover_point';
    const OUT_TO_HOURS_LIMIT        = 43200; // Seconds in 12 hours

    /** @var ?array<int, array{id: int, created: string, nameHash: string, name: string}> */
    protected static $recoveablesPackages = null;
    /** @var ?self */
    protected static $instance = null;

    /**
     *
     * @return int
     */
    public function getPackageId()
    {
        return $this->package->ID;
    }

    /**
     * Return package life
     *
     * @param string $type can be hours,human,timestamp
     *
     * @return int|string package life in hours, timestamp or human readable format
     */
    public function getPackageLife($type = 'timestamp')
    {
        $created = strtotime($this->getCreated());
        $current = strtotime(gmdate("Y-m-d H:i:s"));
        $delta   = $current - $created;

        switch ($type) {
            case 'hours':
                return max(0, floor($delta / 60 / 60));
            case 'human':
                return human_time_diff($created, $current);
            case 'timestamp':
            default:
                return $delta;
        }
    }

    /**
     * This function check if package is importable from scan info
     *
     * @param string $failMessage message if isn't importable
     *
     * @return bool
     */
    public function isImportable(&$failMessage = null)
    {
        if (parent::isImportable($failMessage) === false) {
            return false;
        }

        //The scan logic is going to be refactored, so only use info from the scan.json, if it's too complex to use the
        // archive config info
        if ($this->package->Archive->hasWpCoreFolderFiltered()) {
            $failMessage = __(
                'The package is missing WordPress core folder(s)! It must include wp-admin, wp-content, wp-includes, uploads, plugins, and themes folders.',
                'duplicator-pro'
            );
            return false;
        }

        if ($this->info->mu_mode !== 0 && $this->info->mu_is_filtered) {
            $failMessage = __('The package is missing some subsites.', 'duplicator-pro');
            return false;
        }

        if ($this->info->dbInfo->tablesBaseCount != $this->info->dbInfo->tablesFinalCount) {
            $failMessage = __('The package is missing some of the site tables.', 'duplicator-pro');
            return false;
        }

        $failMessage = '';
        return true;
    }

    /**
     *
     * @return bool
     */
    public function isOutToDate()
    {
        return $this->getPackageLife() > self::OUT_TO_HOURS_LIMIT;
    }

    /**
     * Return installer folder path
     *
     * @return string|false false if impossibile exec the installer
     */
    public function getInstallerFolderPath()
    {
        switch ($this->getPathMode()) {
            case self::PATH_MODE_BACKUP:
                return DUPLICATOR_PRO_PATH_RECOVER;
            case self::PATH_MODE_CUSTOM:
                return DUP_PRO_Global_Entity::getInstance()->getRecoveryCustomPath();
            case self::PATH_MODE_BRIDGE:
            case self::PATH_MODE_HOME:
            case self::PATH_MODE_CLASSIC:
            case self::PATH_MODE_NONE:
            default:
                return false;
        }
    }

    /**
     * Return installer filder url
     *
     * @return string|false false if impossibile exec the installer
     */
    public function getInstallerFolderUrl()
    {
        switch ($this->getPathMode()) {
            case self::PATH_MODE_BACKUP:
                return DUPLICATOR_PRO_URL_RECOVER;
            case self::PATH_MODE_CUSTOM:
                return DUP_PRO_Global_Entity::getInstance()->getRecoveryCustomURL();
            case self::PATH_MODE_BRIDGE:
            case self::PATH_MODE_HOME:
            case self::PATH_MODE_CLASSIC:
            case self::PATH_MODE_NONE:
            default:
                return false;
        }
    }

    /**
     * return true if path have a recovery point sub path
     *
     * @param string $path path to check
     *
     * @return boolean
     */
    public static function isRecoverPath($path)
    {
        $result = preg_match(
            '/[\/]' . preg_quote(DUPLICATOR_PRO_SSDIR_NAME, '/') . '[\/]' . preg_quote(DUPLICATOR_PRO_RECOVER_DIR_NAME, '/') . '[\/]/',
            $path
        );
        return ($result === 1);
    }

    /**
     * Return installer link
     *
     * @return string
     */
    public function getInstallLink()
    {
        $queryStr = http_build_query(array(
            'archive'    => dirname($this->archive),
            'dup_folder' => 'dup-installer-' . $this->info->packInfo->secondaryHash,
        ));
        return $this->getInstallerFolderUrl() . '/' . $this->getInstallerName() . '?' . $queryStr;
    }

    /**
     * Get HTML launcher fil name
     *
     * @return string
     */
    public function getLauncherFileName()
    {

        $parseUrl     = SnapURL::parseUrl(get_home_url());
        $siteFileName = str_replace(array(':', '\\', '/', '.'), '_', $parseUrl['host'] . $parseUrl['path']);
        sanitize_file_name($siteFileName);

        return 'recover_' . sanitize_file_name($siteFileName) . '_' . date("Ymd_His", strtotime($this->getCreated())) . '.html';
    }


    /**
     * Init recovery package by id
     *
     * @param int $packageId package id
     *
     * @return boolean|self
     */
    protected static function getInitRecoverPackageById($packageId)
    {
        try {
            if (!($package = DUP_PRO_Package::get_by_id($packageId))) {
                throw new Exception('Invalid packag id');
            }

            if (($archivePath = $package->getLocalPackageFilePath(DUP_PRO_Package_File_Type::Archive)) == false) {
                throw new Exception('Archive file not found');
            }

            $result = new self($archivePath, $package);
        } catch (Exception $e) {
            DUP_PRO_Log::trace('ERROR ON RECOVER PACKAGE ID, msg:' . $e->getMessage());
            return false;
        }

        return $result;
    }

    /**
     *
     * @param boolean $reset if true reset package
     *
     * @return false|self return false if recover package isn't set or recover package object
     */
    public static function getRecoverPackage($reset = false)
    {
        if (is_null(self::$instance) || $reset) {
            if (($packageId = get_option(self::OPTION_RECOVER_PACKAGE_ID)) == false) {
                self::$instance = null;
                return false;
            }

            if (!self::isPackageIdRecoveable($packageId, $reset)) {
                self::$instance = null;
                return false;
            }

            self::$instance = self::getInitRecoverPackageById($packageId);
        }

        return self::$instance;
    }

    /**
     * Get recover package id
     *
     * @return false|int return false if not set or package id
     */
    public static function getRecoverPackageId()
    {
        if (RecoveryController::isDisallow()) {
            return false;
        }

        $recoverPackage = self::getRecoverPackage();
        if ($recoverPackage instanceof self) {
            return $recoverPackage->getPackageId();
        } else {
            return false;
        }
    }

    /**
     * Reset recovery package
     *
     * @param bool $emptyDir if true remove recovery package files
     *
     * @return void
     */
    public static function resetRecoverPackage($emptyDir = false)
    {
        self::$instance = null;

        if ($emptyDir) {
            static::cleanFolder();
        }

        if (($recoverPackageId = get_option(self::OPTION_RECOVER_PACKAGE_ID)) !== false) {
            delete_option(self::OPTION_RECOVER_PACKAGE_ID);
            $package = DUP_PRO_Package::get_by_id($recoverPackageId);
            if ($package instanceof DUP_PRO_Package) {
                $package->save();
            }
        }
    }

    /**
     * Set recoveable package
     *
     * @param false|int $id           if empty reset package
     * @param ?string   $errorMessage error message
     *
     * @return bool false if fail
     */
    public static function setRecoveablePackage($id, &$errorMessage = null)
    {
        $id = (int) $id;

        self::resetRecoverPackage(true);

        if (empty($id)) {
            return true;
        }

        try {
            if (!self::isPackageIdRecoveable($id, true)) {
                throw new Exception('Package isn\'t in recoverable list');
            }

            $recoverPackage = self::getInitRecoverPackageById($id);
            if (!$recoverPackage instanceof self) {
                throw new Exception('Can\'t initialize recovery package');
            }

            if (!SnapIO::mkdir($recoverPackage->getInstallerFolderPath(), 0755, true)) {
                throw new Exception('Can\'t create recovery package folder or set its permissions to 0755');
            }
            SnapIO::createSilenceIndex($recoverPackage->getInstallerFolderPath());

            // Checks if php is executable in the recover folder
            $path     = $recoverPackage->getInstallerFolderPath();
            $url      = $recoverPackage->getInstallerFolderUrl();
            $phpCheck = new PHPExecCheck($path, $url);
            if ($phpCheck->check() != PHPExecCheck::PHP_OK) {
                throw new Exception($phpCheck->getLastError());
            }

            $recoverPackage->prepareToInstall();

            if (!update_option(self::OPTION_RECOVER_PACKAGE_ID, $id)) {
                delete_option(self::OPTION_RECOVER_PACKAGE_ID);
                throw new Exception('Can\'t update ' . self::OPTION_RECOVER_PACKAGE_ID . ' option');
            }

            $package = DUP_PRO_Package::get_by_id($id);
            $package ->save();
        } catch (Exception $e) {
            delete_option(self::OPTION_RECOVER_PACKAGE_ID);
            $errorMessage = $e->getMessage();
            return false;
        } catch (Error $e) {
            delete_option(self::OPTION_RECOVER_PACKAGE_ID);
            $errorMessage = $e->getMessage();
            return false;
        }

        return true;
    }

    /**
     *
     * @param bool $removeArchive not used, always removes the archives in the recovery folder
     *
     * @return bool
     */
    public static function cleanFolder($removeArchive = false)
    {
        $customFolder = DUP_PRO_Global_Entity::getInstance()->getRecoveryCustomPath();
        if (strlen($customFolder) > 0) {
            $path = $customFolder;
        } else {
            $path = DUPLICATOR_PRO_PATH_RECOVER;
        }

        if (!file_exists($path) && !wp_mkdir_p($path)) {
            throw new Exception('Can\'t create ' . $path);
        }
        SnapIO::emptyDir($path, ['index.php']);

        return true;
    }

    /**
     * Get error message if installer path couldn't be determined
     *
     * @return string
     */
    protected static function getNotExecPhpErrorMessage()
    {
        $customFolder = DUP_PRO_Global_Entity::getInstance()->getRecoveryCustomPath();
        if (strlen($customFolder) > 0) {
            $path = $customFolder;
        } else {
            $path = DUPLICATOR_PRO_PATH_RECOVER;
        }

        return sprintf(
            __(
                'Duplicator cannot set Recovery Point because on this Server it isn\'t possible to determine installer path %s',
                'duplicator-pro'
            ),
            $path
        );
    }

    /**
     * Determine possible path for installer.
     * If is none the installer can't be executed
     *
     * @return string can be duplicator, home, none
     */
    protected function getPathMode()
    {
        if (strlen(DUP_PRO_Global_Entity::getInstance()->getRecoveryCustomPath()) > 0) {
            return self::PATH_MODE_CUSTOM;
        }
        return (self::isPathBackupAvailable() ? self::PATH_MODE_BACKUP : self::PATH_MODE_NONE);
    }

    /**
     * Return recoverable packages list
     *
     * @param bool $reset if true reset packages list
     *
     * @return array<int, array{id: int, created: string, nameHash: string, name: string}>
     */
    public static function getRecoverablesPackages($reset = false)
    {
        if (is_null(self::$recoveablesPackages) || $reset) {
            self::$recoveablesPackages = array();
            DUP_PRO_Package::by_status_callback(
                array(
                    __CLASS__,
                    'recoverablePackageCheck',
                ),
                array(
                    array(
                        'op'     => '>=',
                        'status' => DUP_PRO_PackageStatus::COMPLETE,
                    ),
                ),
                self::MAX_PACKAGES_LIST,
                0,
                '`created` DESC'
            );
        }
        self::addRecoverPackageToListIfNotExists();

        return self::$recoveablesPackages;
    }

    /**
     * Add current recovery package in list if not exists
     *
     * @return bool  Returns true if it does not exist
     */
    protected static function addRecoverPackageToListIfNotExists()
    {
        if (($recoverPackageId = get_option(self::OPTION_RECOVER_PACKAGE_ID)) === false) {
            return true;
        }

        if (in_array($recoverPackageId, array_keys(self::$recoveablesPackages))) {
            return true;
        }

        $recoverPackage = DUP_PRO_Package::get_by_id($recoverPackageId);
        if (!$recoverPackage instanceof DUP_PRO_Package) {
            return false;
        }

        return self::recoverablePackageCheck($recoverPackage);
    }

    /**
     * return true if packages id is recoverable
     *
     * @param int     $id    package id
     * @param boolean $reset if true reset packages list
     *
     * @return boolean
     */
    public static function isPackageIdRecoveable($id, $reset = false)
    {
        if (RecoveryController::isDisallow()) {
            return false;
        }

        return in_array($id, self::getRecoverablesPackagesIds($reset));
    }

    /**
     * Get recoverable package ids
     *
     * @param bool $reset if true reset list
     *
     * @return int[]
     */
    public static function getRecoverablesPackagesIds($reset = false)
    {
        return array_keys(self::getRecoverablesPackages($reset));
    }

    /**
     * Check if package is recoverable
     *
     * @param DUP_PRO_Package $package package to check
     *
     * @return bool true if is added
     */
    public static function recoverablePackageCheck(DUP_PRO_Package $package)
    {
        $status = new RecoveryStatus($package);
        if (!$status->isRecoveable()) {
            return false;
        }

        self::$recoveablesPackages[$package->ID] = array(
            'id'       => $package->ID,
            'created'  => $package->getCreated(),
            'nameHash' => $package->NameHash,
            'name'     => $package->Name,
        );
        return true;
    }

    /**
     * Remove recovery folders
     *
     * @return void
     */
    public static function removeRecoveryFolder()
    {
        if (file_exists(DUPLICATOR_PRO_PATH_RECOVER)) {
            SnapIO::rrmdir(DUPLICATOR_PRO_PATH_RECOVER);
        }

        if (strlen(DUP_PRO_Global_Entity::getInstance()->getRecoveryCustomPath()) > 0) {
            $customFolder = DUP_PRO_Global_Entity::getInstance()->getRecoveryCustomPath();
            if (file_exists($customFolder)) {
                SnapIO::rrmdir($customFolder);
            }
        }
    }
}

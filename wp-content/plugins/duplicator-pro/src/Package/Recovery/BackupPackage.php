<?php

/**
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

namespace Duplicator\Package\Recovery;

use DUP_PRO_Archive;
use DUP_PRO_Log;
use DUP_PRO_Package;
use Duplicator\Installer\Core\Params\PrmMng;
use Duplicator\Models\Storages\StoragesUtil;
use Duplicator\Package\Import\PackageImporter;
use Exception;

class BackupPackage extends PackageImporter
{
    /** @var DUP_PRO_Package */
    protected $package = null;

    /**
     * Used for restor backup button
     *
     * @param string          $path    Archiv path
     * @param DUP_PRO_Package $package Recovery package
     */
    public function __construct($path, DUP_PRO_Package $package)
    {
        $this->package    = $package;
        $this->archivePwd = $this->package->Archive->getArchivePassword();
        parent::__construct($path);
    }

    /**
     * This function extract archive info package and read it, After initializing the information deletes the file.
     *
     * @return bool true on success, or false on failure
     */
    public function loadInfo()
    {
        try {
            $this->info    = $this->getObjectFromJson('dup-installer/dup-archive__' . $this->hash . '.txt', true);
            $this->isValid = true;
        } catch (Exception $ex) {
            DUP_PRO_Log::trace("Couldn't initialize the info object: " . $ex->getMessage());
            $this->notValidMessage = $ex->getMessage();
            $this->isValid         = false;
        }
        return $this->isValid;
    }

    /**
     * Return overwrite param for recovery
     *
     * @return array<string, array{value: mixed, formStatus?: string}>
     */
    public function getOverwriteParams()
    {
        $params  = parent::getOverwriteParams();
        $updDirs = wp_upload_dir();
        $result  = array(
            PrmMng::PARAM_TEMPLATE           => array('value' => 'recovery'),
            PrmMng::PARAM_RECOVERY_LINK      => array('value' => ''),
            PrmMng::PARAM_SITE_URL           => array(
                'value'      => site_url(),
                'formStatus' => 'st_infoonly',
            ),
            PrmMng::PARAM_PATH_WP_CORE_NEW   => array(
                'value'      => DUP_PRO_Archive::getOriginalPaths('abs'),
                'formStatus' => 'st_infoonly',
            ),
            PrmMng::PARAM_URL_CONTENT_NEW    => array(
                'value'      => content_url(),
                'formStatus' => 'st_infoonly',
            ),
            PrmMng::PARAM_PATH_CONTENT_NEW   => array(
                'value'      => DUP_PRO_Archive::getOriginalPaths('wpcontent'),
                'formStatus' => 'st_infoonly',
            ),
            PrmMng::PARAM_URL_UPLOADS_NEW    => array(
                'value'      => $updDirs['baseurl'],
                'formStatus' => 'st_infoonly',
            ),
            PrmMng::PARAM_PATH_UPLOADS_NEW   => array(
                'value'      => DUP_PRO_Archive::getOriginalPaths('uploads'),
                'formStatus' => 'st_infoonly',
            ),
            PrmMng::PARAM_URL_PLUGINS_NEW    => array(
                'value'      => plugins_url(),
                'formStatus' => 'st_infoonly',
            ),
            PrmMng::PARAM_PATH_PLUGINS_NEW   => array(
                'value'      => DUP_PRO_Archive::getOriginalPaths('plugins'),
                'formStatus' => 'st_infoonly',
            ),
            PrmMng::PARAM_URL_MUPLUGINS_NEW  => array(
                'value'      => WPMU_PLUGIN_URL,
                'formStatus' => 'st_infoonly',
            ),
            PrmMng::PARAM_PATH_MUPLUGINS_NEW => array(
                'value'      => DUP_PRO_Archive::getOriginalPaths('muplugins'),
                'formStatus' => 'st_infoonly',
            ),
        );

        $result = array_merge($params, $result);
        foreach (StoragesUtil::getLocalStoragesPaths() as $path) {
            $result[PrmMng::PARAM_OVERWRITE_SITE_DATA]['value']['removeFilters']['dirs'][] = $path;
        }

        return $result;
    }
}

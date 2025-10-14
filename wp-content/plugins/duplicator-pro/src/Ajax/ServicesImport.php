<?php

/**
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

namespace Duplicator\Ajax;

use Duplicator\Addons\ProBase\License\License;
use Duplicator\Ajax\AjaxWrapper;
use Duplicator\Ajax\FileTransfer\ImportUpload;
use Duplicator\Controllers\ImportPageController;
use Duplicator\Core\CapMng;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Package\Import\PackageImporter;
use Exception;

class ServicesImport extends AbstractAjaxService
{
    /**
     * Init ajax calls
     *
     * @return void
     */
    public function init()
    {
        if (!License::can(License::CAPABILITY_PRO_BASE)) {
            return;
        }
        $this->addAjaxCall('wp_ajax_duplicator_pro_import_upload', 'importUpload');
        $this->addAjaxCall('wp_ajax_duplicator_pro_import_package_delete', 'deletePackage');
        $this->addAjaxCall('wp_ajax_duplicator_pro_import_set_view_mode', 'setViewMode');
        $this->addAjaxCall('wp_ajax_duplicator_pro_import_remote_download', 'remoteDownload');
        $this->addAjaxCall('wp_ajax_duplicator_pro_import_set_archive_password', 'setArchivePassword');
    }

    /**
     * Import upload callback logic
     *
     * @return mixed[]
     */
    public static function importUploadCallback()
    {
        $uploader = new ImportUpload(ImportUpload::MODE_UPLOAD_LOCAL);
        return $uploader->exec();
    }

    /**
     * Import upload action
     *
     * @return void
     */
    public function importUpload()
    {
        AjaxWrapper::json(
            array(
                __CLASS__,
                'importUploadCallback',
            ),
            'duplicator_pro_import_upload',
            $_POST['nonce'],
            CapMng::CAP_IMPORT
        );
    }

    /**
     * Import download remote callback logic
     *
     * @return mixed[]
     */
    public static function remoteDownloadCallback()
    {
        $uploader = new ImportUpload(ImportUpload::MODE_DOWNLOAD_REMOTE);
        return $uploader->exec();
    }

    /**
     * Import download remote action
     *
     * @return void
     */
    public function remoteDownload()
    {
        AjaxWrapper::json(
            array(
                __CLASS__,
                'remoteDownloadCallback',
            ),
            'duplicator_pro_remote_download',
            $_POST['nonce'],
            CapMng::CAP_IMPORT
        );
    }

    /**
     * Import delete package callback
     *
     * @return bool
     */
    public static function deletePackageCallback()
    {
        $inputData = filter_input_array(INPUT_POST, array(
            'path' => array(
                'filter'  => FILTER_SANITIZE_SPECIAL_CHARS,
                'flags'   => FILTER_REQUIRE_SCALAR,
                'options' => array('default' => ''),
            ),
        ));

        if (empty($inputData['path'])) {
            throw new Exception(__("Invalid Request!", 'duplicator-pro'));
        }

        if (in_array($inputData['path'], PackageImporter::getArchiveList())) {
            if (unlink($inputData['path']) == false) {
                throw new Exception(__("Can\'t remove archvie!", 'duplicator-pro'));
            }
            PackageImporter::cleanFolder();
        }

        return true;
    }

    /**
     * Import delete backage action
     *
     * @return void
     */
    public function deletePackage()
    {
        AjaxWrapper::json(
            array(
                __CLASS__,
                'deletePackageCallback',
            ),
            'duplicator_pro_import_package_delete',
            $_POST['nonce'],
            CapMng::CAP_IMPORT
        );
    }

    /**
     * Set import view mode callback
     *
     * @return string
     */
    public static function setViewModeCallback()
    {
        $viewMode = filter_input(INPUT_POST, 'view_mode', FILTER_SANITIZE_SPECIAL_CHARS);

        switch ($viewMode) {
            case ImportPageController::VIEW_MODE_ADVANCED:
            case ImportPageController::VIEW_MODE_BASIC:
                break;
            default:
                throw new Exception(__('Invalid view mode', 'duplicator-pro'));
        }

        if (!($userId = get_current_user_id())) {
            throw new Exception(__('Invalid current urser id', 'duplicator-pro'));
        }

        $archives = PackageImporter::getArchiveList();
        if ($viewMode == ImportPageController::VIEW_MODE_BASIC && count($archives) > 1) {
            update_user_meta($userId, ImportPageController::USER_META_VIEW_MODE, ImportPageController::VIEW_MODE_ADVANCED);
            $message = __(
                'It is not possible to set the view mode to basic if the number of packages is more than one.',
                'duplicator-pro'
            ) . ' ' .
            __('Remove packages before performing this action.', 'duplicator-pro');
            throw new Exception($message);
        }

        if ($viewMode != ImportPageController::getViewMode()) {
            if (update_user_meta($userId, ImportPageController::USER_META_VIEW_MODE, $viewMode) == false) {
                throw new Exception(__('Can\'t update user meta value', 'duplicator-pro'));
            }
        }

        return ImportPageController::getViewMode();
    }

    /**
     * Set import view mode action
     *
     * @return void
     */
    public function setViewMode()
    {
        AjaxWrapper::json(
            array(
                __CLASS__,
                'setViewModeCallback',
            ),
            'duplicator_pro_import_set_view_mode',
            $_POST['nonce'],
            CapMng::CAP_IMPORT
        );
    }

    /**
     * Set import view mode callback
     *
     * @return mixed[]
     */
    public static function setArchivePasswordCallback()
    {
        $result = [];

        $archiveFile = filter_input(INPUT_POST, 'archive', FILTER_CALLBACK, ['options' => [SnapUtil::class, 'sanitizeNSCharsNewlineTabs']]);
        $password    = filter_input(INPUT_POST, 'password', FILTER_CALLBACK, ['options' => [SnapUtil::class, 'sanitizeNSCharsNewlineTrim']]);

        if (preg_match('/^.*\.(zip|daf)$/', $archiveFile) !== 1) {
            throw new Exception('Invalid archive name "' . $archiveFile . '"');
        }

        $importObj = new PackageImporter($archiveFile);
        $errMsg    = '';
        if (!$importObj->encryptCheck($errMs)) {
            throw new Exception($errMsg);
        } elseif ($importObj->passwordCheck($password)) {
            $importObj->updatePasswordCookie();
            $uploader = new ImportUpload(ImportUpload::MODE_UPLOADED, $archiveFile);
            $result   = $uploader->exec();
        } else {
            throw new Exception('Invalid password');
        }

        return $result;
    }

    /**
     * Set import view mode action
     *
     * @return void
     */
    public function setArchivePassword()
    {
        AjaxWrapper::json(
            array(
                __CLASS__,
                'setArchivePasswordCallback',
            ),
            'duplicator_pro_import_set_archive_password',
            $_POST['nonce'],
            CapMng::CAP_IMPORT
        );
    }
}

<?php

namespace Duplicator\Controllers;

use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Package\Recovery\RecoveryPackage;
use Error;
use Exception;

class RecoveryController
{
    const VIEW_WIDGET_NO_PACKAGE_SET = 'nop';
    const VIEW_WIDGET_NOT_VALID      = 'notvalid';
    const VIEW_WIDGET_VALID          = 'valid';

    /** @var bool */
    protected static $isError = false;
    /** @var string */
    protected static $errorMessage = '';

    /**
     * @return bool check if package is disallow from wp-config.php
     */
    public static function isDisallow()
    {
        return (bool) DUPLICATOR_PRO_DISALLOW_RECOVERY;
    }

    /**
     *
     * @return string
     */
    public static function getErrorMessage()
    {
        return self::$errorMessage;
    }

    /**
     * Get recovery page link
     *
     * @return string
     */
    public static function getRecoverPageLink()
    {
        return ControllersManager::getMenuLink(ControllersManager::TOOLS_SUBMENU_SLUG, ToolsPageController::L2_SLUG_RECOVERY);
    }

    /**
     * Reset recovery point
     *
     * @return bool
     */
    public static function actionResetRecoveryPoint()
    {
        try {
            RecoveryPackage::removeRecoveryFolder();
            RecoveryPackage::setRecoveablePackage(false);
        } catch (Exception $e) {
            self::$isError      = true;
            self::$errorMessage = $e->getMessage();
            return false;
        } catch (Error $e) {
            self::$isError      = true;
            self::$errorMessage = $e->getMessage();
            return false;
        }

        return true;
    }

    /**
     * Render recovery widget
     *
     * @param array<string, mixed> $options widget options
     * @param bool                 $echo    echo or return
     *
     * @return string
     */
    public static function renderRecoveryWidged($options = array(), $echo = true)
    {
        ob_start();

        $options = array_merge(
            array(
                'details'    => true,
                'selector'   => false,
                'subtitle'   => '',
                'copyLink'   => false,
                'copyButton' => true,
                'launch'     => true,
                'download'   => false,
                'info'       => true,
            ),
            (array) $options
        );

        $recoverPackage     = RecoveryPackage::getRecoverPackage();
        $recoverPackageId   = RecoveryPackage::getRecoverPackageId();
        $recoveablePackages = RecoveryPackage::getRecoverablesPackages();
        $displayDetails     = $options['details'];
        $selector           = $options['selector'];
        $subtitle           = $options['subtitle'];
        $displayCopyLink    = $options['copyLink'];
        $displayCopyButton  = $options['copyButton'];
        $displayLaunch      = $options['launch'];
        $displayDownload    = $options['download'];
        $displayInfo        = $options['info'];
        $importFailMessage  = '';

        if (!$recoverPackage instanceof RecoveryPackage) {
            $viewMode = self::VIEW_WIDGET_NO_PACKAGE_SET;
        } elseif (!$recoverPackage->isImportable($importFailMessage)) {
            $viewMode = self::VIEW_WIDGET_NOT_VALID;
        } else {
            $viewMode = self::VIEW_WIDGET_VALID;
        }

        require(DUPLICATOR____PATH . '/views/tools/recovery/widget/recovery-widget.php');

        if ($echo) {
            ob_end_flush();
            return '';
        } else {
            return ob_get_clean();
        }
    }
}

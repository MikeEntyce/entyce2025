<?php

/**
 * Version Pro Base addon class
 *
 * Name: Duplicator PRO base
 * Version: 1
 * Author: Duplicator
 * Author URI: http://snapcreek.com
 *
 * PHP version 5.6.20
 *
 * @category  Duplicator
 * @package   Plugin
 * @author    Duplicator
 * @copyright 2011-2021  Snapcreek LLC
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @version   GIT: $Id$
 * @link      https://duplicator.com/
 */

namespace Duplicator\Addons\ProBase;

// phpcs:disable
require_once __DIR__ . '/vendor/edd/EDD_SL_Plugin_Updater.php';
// phpcs:enable

use Duplicator\Controllers\SchedulePageController;
use Duplicator\Addons\ProBase\License\License;
use Duplicator\Addons\ProBase\License\LicenseNotices;
use Duplicator\Addons\ProBase\Models\LicenseData;
use Duplicator\Core\Controllers\AbstractMenuPageController;
use Duplicator\Core\MigrationMng;
use Duplicator\Installer\Models\MigrateData;
use Duplicator\Libs\Snap\SnapLog;

/**
 * Version Pro Base addon class
 *
 * @category Duplicator
 * @package  Plugin
 * @author   Snapcreek <admin@snapcreek.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     http://snapcreek.com
 */
class ProBase extends \Duplicator\Core\Addons\AbstractAddonCore
{
    /**
     * @return void
     */
    public function init()
    {
        add_action('init', array($this, 'hookInit'));
        add_action('duplicator_unistall', array($this, 'unistall'));

        add_filter('duplicator_main_menu_label', function () {
            return 'Duplicator Pro';
        });

        add_filter('duplicator_menu_pages', array($this, 'addScheduleMenuField'));

        add_action(MigrationMng::HOOK_FIRST_LOGIN_AFTER_INSTALL, function (MigrateData $migrationData) {
            License::clearVersionCache();
        });

        add_action('duplicator_pro_on_upgrade_version', [$this, 'onUpgradePlugin'], 10, 2);

        add_action('duplicator_before_update_crypt_setting', [__CLASS__, 'beforeCryptUpdateSettings']);
        add_action('duplicator_after_update_crypt_setting', [__CLASS__, 'afterCryptUpdateSettings']);

        LicenseNotices::init();
        LicensingController::init();
    }

    /**
     * Unistall
     *
     * @return void
     */
    public function unistall()
    {
        if (strlen(LicenseData::getInstance()->getKey()) > 0) {
            switch (LicenseData::getInstance()->deactivate()) {
                case LicenseData::ACTIVATION_RESPONSE_OK:
                    break;
                case LicenseData::ACTIVATION_REQUEST_ERROR:
                    SnapLog::phpErr("Error deactivate license: ACTIVATION_RESPONSE_POST_ERROR");
                    break;
                case LicenseData::ACTIVATION_RESPONSE_INVALID:
                default:
                    SnapLog::phpErr("Error deactivate license: ACTIVATION_RESPONSE_INVALID");
                    break;
            }
        }
    }

    /**
     * Add schedule menu page
     *
     * @param array<string, AbstractMenuPageController> $basicMenuPages menu pages
     *
     * @return array<string, AbstractMenuPageController>
     */
    public function addScheduleMenuField($basicMenuPages)
    {
        $page = SchedulePageController::getInstance();

        $basicMenuPages[$page->getSlug()] = $page;
        return $basicMenuPages;
    }

    /**
     * Function calle on duplicator_addons_loaded hook
     *
     * @return void
     */
    public function hookInit()
    {
        License::check();
    }

    /**
     * Function called on plugin upgrade
     *
     * @param false|string $currentVersion current version
     * @param string       $newVersion     new version
     *
     * @return void
     */
    public function onUpgradePlugin($currentVersion, $newVersion)
    {
        if ($currentVersion !== false && version_compare($currentVersion, '4.5.16-beta1', '<')) {
            $legacyKey = get_option(LicenseData::LICENSE_OLD_KEY_OPTION_NAME, '');
            if (!empty($legacyKey)) {
                LicenseData::getInstance()->setKey($legacyKey);
            }
            delete_option(LicenseData::LICENSE_OLD_KEY_OPTION_NAME);
        }
        License::clearVersionCache();
    }

    /**
     * Before crypt update settings
     *
     * @return void
     */
    public static function beforeCryptUpdateSettings()
    {
        // make sure the license date si reade before the settings are updated
        LicenseData::getInstance();
    }

    /**
     * After crypt update settings
     *
     * @return void
     */
    public static function afterCryptUpdateSettings()
    {
        LicenseData::getInstance()->save();
    }

    /**
     *
     * @return string
     */
    public static function getAddonPath()
    {
        return __DIR__;
    }

    /**
     *
     * @return string
     */
    public static function getAddonFile()
    {
        return __FILE__;
    }
}

<?php

/**
 * Tools page controller
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

namespace Duplicator\Controllers;

use Duplicator\Core\MigrationMng;
use DUP_PRO_Package;
use DUP_PRO_Server;
use Duplicator\Addons\ProBase\License\License;
use Duplicator\Core\CapMng;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Controllers\AbstractMenuPageController;
use Duplicator\Core\Controllers\PageAction;
use Duplicator\Core\Controllers\SubMenuItem;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapIO;
use Duplicator\Views\AdminNotices;

class ToolsPageController extends AbstractMenuPageController
{
    const NONCE_ACTION = 'duppro-settings-package';

    /**
     * tabs menu
     */
    const L2_SLUG_DISAGNOSTIC = 'diagnostics';
    const L2_SLUG_TEMPLATE    = 'templates';
    const L2_SLUG_RECOVERY    = 'recovery';

    /**
     * disagnostic
     */
    const L3_SLUG_DISAGNOSTIC_DIAGNOSTIC = 'diagnosticsdiagnostic';
    const L3_SLUG_DISAGNOSTIC_LOG        = 'log';
    const L3_SLUG_DISAGNOSTIC_PHPLOGS    = 'phplogs';
    const L3_SLUG_DISAGNOSTIC_SUPPORT    = 'support';

    const ACTION_PURGE_ORPHANS = 'purge-orphans';
    const ACTION_CLEAN_CACHE   = 'tmp-cache';
    const ACTION_INSTALLER     = 'installer';

    /**
     * Class constructor
     */
    protected function __construct()
    {
        $this->parentSlug   = ControllersManager::MAIN_MENU_SLUG;
        $this->pageSlug     = ControllersManager::TOOLS_SUBMENU_SLUG;
        $this->pageTitle    = __('Tools', 'duplicator-pro');
        $this->menuLabel    = __('Tools', 'duplicator-pro');
        $this->capatibility = CapMng::CAP_BASIC;
        $this->menuPos      = 50;

        add_filter('duplicator_sub_menu_items_' . $this->pageSlug, array($this, 'getBasicSubMenus'));
        add_filter('duplicator_sub_level_default_tab_' . $this->pageSlug, array($this, 'getSubMenuDefaults'), 10, 2);
        add_action('duplicator_render_page_content_' . $this->pageSlug, array($this, 'renderContent'), 10, 2);
        add_filter('duplicator_page_actions_' . $this->pageSlug, array($this, 'pageActions'));
    }

    /**
     * Return actions for current page
     *
     * @param PageAction[] $actions actions lists
     *
     * @return PageAction[]
     */
    public function pageActions($actions)
    {
        $actions[] = new PageAction(
            self::ACTION_PURGE_ORPHANS,
            [
                $this,
                'actionPurgeOrphans',
            ],
            [$this->pageSlug]
        );
        $actions[] = new PageAction(
            self::ACTION_CLEAN_CACHE,
            [
                $this,
                'actionCleanCache',
            ],
            [$this->pageSlug]
        );
        $actions[] = new PageAction(
            self::ACTION_INSTALLER,
            [
                $this,
                'actionInstaller',
            ],
            [$this->pageSlug]
        );
        return $actions;
    }

    /**
     * Return sub menus for current page
     *
     * @param SubMenuItem[] $subMenus sub menus list
     *
     * @return SubMenuItem[]
     */
    public function getBasicSubMenus($subMenus)
    {
        $subMenus[] = new SubMenuItem(self::L2_SLUG_DISAGNOSTIC, __('General', 'duplicator-pro'));
        $subMenus[] = new SubMenuItem(self::L2_SLUG_TEMPLATE, __('Templates', 'duplicator-pro'), '', CapMng::CAP_CREATE);
        $subMenus[] = new SubMenuItem(self::L2_SLUG_RECOVERY, __('Recovery', 'duplicator-pro'), '', CapMng::CAP_BACKUP_RESTORE);

        $subMenus[] = new SubMenuItem(self::L3_SLUG_DISAGNOSTIC_DIAGNOSTIC, __('Information', 'duplicator-pro'), self::L2_SLUG_DISAGNOSTIC);
        $subMenus[] = new SubMenuItem(self::L3_SLUG_DISAGNOSTIC_LOG, __('Duplicator Logs', 'duplicator-pro'), self::L2_SLUG_DISAGNOSTIC, CapMng::CAP_CREATE);
        $subMenus[] = new SubMenuItem(self::L3_SLUG_DISAGNOSTIC_PHPLOGS, __('PHP Logs', 'duplicator-pro'), self::L2_SLUG_DISAGNOSTIC, CapMng::CAP_CREATE);
        $subMenus[] = new SubMenuItem(self::L3_SLUG_DISAGNOSTIC_SUPPORT, __('Support', 'duplicator-pro'), self::L2_SLUG_DISAGNOSTIC);

        return $subMenus;
    }

    /**
     * Return slug default for parent menu slug
     *
     * @param string $slug   current default
     * @param string $parent parent for default
     *
     * @return string default slug
     */
    public function getSubMenuDefaults($slug, $parent)
    {
        switch ($parent) {
            case '':
                return self::L2_SLUG_DISAGNOSTIC;
            case self::L2_SLUG_DISAGNOSTIC:
                return self::L3_SLUG_DISAGNOSTIC_DIAGNOSTIC;
            default:
                return $slug;
        }
    }

    /**
     * Action purge orphans
     *
     * @return array{purgeOrphansSuccess: bool ,purgeOrphansFiles: array<string, bool>}
     */
    public function actionPurgeOrphans()
    {
        $orphaned_filepaths = DUP_PRO_Server::getOrphanedPackageFiles();

        $result = [
            'purgeOrphansFiles'   => [],
            'purgeOrphansSuccess' => true,
        ];

        foreach ($orphaned_filepaths as $filepath) {
            $result['purgeOrphansFiles'][$filepath] = (is_writable($filepath) ? unlink($filepath) : false);
            if ($result['purgeOrphansFiles'][$filepath] == false) {
                $result['purgeOrphansSuccess'] = false;
            }
        }

        return $result;
    }

    /**
     * Action clean cache
     *
     * @return array<string, mixed>
     */
    public function actionCleanCache()
    {
        $result = [
            'tmpCleanUpSuccess' => DUP_PRO_Package::tmp_cleanup(true),
        ];
        return $result;
    }

    /**
     * Action installer
     *
     * @return array<string, mixed>
     */
    public function actionInstaller()
    {
        $files       = MigrationMng::cleanMigrationFiles();
        $removeError = false;

        foreach ($files as $success) {
            if ($success ==  false) {
                $removeError = true;
            }
        }

        $result = [
            'isMigrationSuccessNotice' => get_option(AdminNotices::OPTION_KEY_MIGRATION_SUCCESS_NOTICE),
            'isInstallerCleanup'       => true,
            'installerCleanupFiles'    => $files,
            'installerCleanupError'    => $removeError,
            'installerCleanupPurge'    => MigrationMng::purgeCaches(),
        ];

        if ($removeError == false) {
            delete_option(AdminNotices::OPTION_KEY_MIGRATION_SUCCESS_NOTICE);
        }

        return $result;
    }

    /**
     * Render page content
     *
     * @param string[] $currentLevelSlugs current menu slugs
     * @param string   $innerPage         current inner page, empty if not set
     *
     * @return void
     */
    public function renderContent($currentLevelSlugs, $innerPage)
    {
        switch ($currentLevelSlugs[1]) {
            case self::L2_SLUG_DISAGNOSTIC:
                $this->renderDiagnostic($currentLevelSlugs);
                break;
            case self::L2_SLUG_TEMPLATE:
                TplMng::getInstance()->setGlobalValue('blur', !License::can(License::CAPABILITY_TEMPLATE));
                require(DUPLICATOR____PATH . '/views/tools/templates/main.php');
                break;
            case self::L2_SLUG_RECOVERY:
                TplMng::getInstance()->render(
                    'admin_pages/tools/recovery/recovery',
                    [
                        'blur' => !License::can(License::CAPABILITY_PRO_BASE),
                    ]
                );
                break;
        }
    }

    /**
     * Render diagnostic sub tab
     *
     * @param string[] $currentLevelSlugs current page menu levels slugs
     *
     * @return void
     */
    protected function renderDiagnostic($currentLevelSlugs)
    {
        require DUPLICATOR____PATH . '/views/tools/diagnostics/main.php';

        switch ($currentLevelSlugs[2]) {
            case self::L3_SLUG_DISAGNOSTIC_DIAGNOSTIC:
                wp_enqueue_script('dup-pro-handlebars');
                if (!TplMng::getInstance()->hasGlobalValue('isMigrationSuccessNotice')) {
                    TplMng::getInstance()->setGlobalValue(
                        'isMigrationSuccessNotice',
                        get_option(AdminNotices::OPTION_KEY_MIGRATION_SUCCESS_NOTICE)
                    );
                }
                require DUPLICATOR____PATH . '/views/tools/diagnostics/diagnostic.php';
                break;
            case self::L3_SLUG_DISAGNOSTIC_LOG:
                require DUPLICATOR____PATH . '/views/tools/diagnostics/log.php';
                break;
            case self::L3_SLUG_DISAGNOSTIC_PHPLOGS:
                require DUPLICATOR____PATH . '/views/tools/diagnostics/phplogs.php';
                break;
            case self::L3_SLUG_DISAGNOSTIC_SUPPORT:
                require DUPLICATOR____PATH . '/views/tools/diagnostics/support.php';
                break;
        }
    }

    /**
     * Return template edit URL
     *
     * @param false|int $templateId template ID, if false return base template edit url
     *
     * @return string
     */
    public static function getTemplateEditURL($templateId = false)
    {
        $data = [ControllersManager::QUERY_STRING_INNER_PAGE => 'edit'];
        if ($templateId !== false) {
            $data['package_template_id'] = $templateId;
        }
        return ControllersManager::getMenuLink(
            ControllersManager::TOOLS_SUBMENU_SLUG,
            self::L2_SLUG_TEMPLATE,
            null,
            $data
        );
    }

    /**
     * Return clean installer files action URL
     *
     * @param bool $relative if true return relative URL else absolute
     *
     * @return string
     */
    public function getCleanFilesAcrtionUrl($relative = true)
    {
        if (($action = $this->getActionByKey(self::ACTION_INSTALLER)) === false) {
            return '';
        }

        return ControllersManager::getMenuLink(
            ControllersManager::TOOLS_SUBMENU_SLUG,
            self::L2_SLUG_DISAGNOSTIC,
            self::L3_SLUG_DISAGNOSTIC_DIAGNOSTIC,
            array(
                'action'   => $action->getKey(),
                '_wpnonce' => $action->getNonce(),
            ),
            $relative
        );
    }

    /**
     * Return packages log list
     *
     * @return string[]
     */
    public static function getLogsList()
    {
        $result = SnapIO::regexGlob(DUPLICATOR_PRO_SSDIR_PATH, [
            'regexFile'   => '/(\.log|_log\.txt)$/',
            'regexFolder' => false,
        ]);
        usort($result, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        return $result;
    }

    /**
     * Return remove cache action URL
     *
     * @return string
     */
    public function getRemoveCacheActionUrl()
    {
        if (($action = $this->getActionByKey(self::ACTION_CLEAN_CACHE)) === false) {
            return '';
        }

        return ControllersManager::getMenuLink(
            ControllersManager::TOOLS_SUBMENU_SLUG,
            self::L2_SLUG_DISAGNOSTIC,
            self::L3_SLUG_DISAGNOSTIC_DIAGNOSTIC,
            array(
                'action'   => $action->getKey(),
                '_wpnonce' => $action->getNonce(),
            )
        );
    }

    /**
     * Return purge orphan packages action URL
     *
     * @return string
     */
    public function getPurgeOrphanActionUrl()
    {
        if (($action = $this->getActionByKey(self::ACTION_PURGE_ORPHANS)) === false) {
            return '';
        }

        return ControllersManager::getMenuLink(
            ControllersManager::TOOLS_SUBMENU_SLUG,
            self::L2_SLUG_DISAGNOSTIC,
            self::L3_SLUG_DISAGNOSTIC_DIAGNOSTIC,
            array(
                'action'   => $action->getKey(),
                '_wpnonce' => $action->getNonce(),
            )
        );
    }

    /**
     *
     * @return boolean
     */
    public static function isToolPage()
    {
        return ControllersManager::isCurrentPage(ControllersManager::TOOLS_SUBMENU_SLUG);
    }

    /**
     *
     * @return boolean
     */
    public static function isDiagnosticPage()
    {
        return ControllersManager::isCurrentPage(
            ControllersManager::TOOLS_SUBMENU_SLUG,
            ToolsPageController::L2_SLUG_DISAGNOSTIC,
            ToolsPageController::L3_SLUG_DISAGNOSTIC_DIAGNOSTIC
        );
    }

    /**
     * Return true if current page is recovery page
     *
     * @return bool
     */
    public static function isRecoveryPage()
    {
        return ControllersManager::isCurrentPage(
            ControllersManager::TOOLS_SUBMENU_SLUG,
            self::L2_SLUG_RECOVERY
        );
    }
}

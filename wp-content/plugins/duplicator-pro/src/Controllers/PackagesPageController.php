<?php

/**
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

namespace Duplicator\Controllers;

use DUP_PRO_Package;
use Duplicator\Addons\ProBase\License\License;
use Duplicator\Ajax\ServicesRecovery;
use Duplicator\Core\CapMng;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Controllers\AbstractMenuPageController;
use Duplicator\Core\Controllers\PageAction;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Views\PackageScreen;
use Error;
use Exception;

/**
 * Packages page controller
 */
class PackagesPageController extends AbstractMenuPageController
{
    const L2_SLUG_PACKAGE_LIST = 'packages';

    const LIST_INNER_PAGE_LIST      = 'list';
    const LIST_INNER_PAGE_NEW_STEP1 = 'new1';
    const LIST_INNER_PAGE_NEW_STEP2 = 'new2';
    const LIST_INNER_PAGE_DETAILS   = 'detail';
    const LIST_INNER_PAGE_TRANSFER  = 'transfer';

    /*
     * action types
     */
    const ACTION_SET_RECOVERY_POINT = 'set_recovery_point';

    /**
     * Class constructor
     */
    protected function __construct()
    {
        $this->parentSlug   = ControllersManager::MAIN_MENU_SLUG;
        $this->pageSlug     = ControllersManager::PACKAGES_SUBMENU_SLUG;
        $this->pageTitle    = __('Packages', 'duplicator-pro');
        $this->menuLabel    = __('Packages', 'duplicator-pro');
        $this->capatibility = CapMng::CAP_BASIC;
        $this->menuPos      = 10;

        add_action('duplicator_render_page_content_' . $this->pageSlug, array($this, 'renderContent'), 10, 2);
        add_filter('duplicator_page_template_data_' . $this->pageSlug, array($this, 'updatePackagePageTitle'));
        add_filter('set_screen_option_package_screen_options', array(PackageScreen::class, 'setScreenOptions'), 11, 3);
        add_filter('duplicator_page_actions_' . $this->pageSlug, array($this, 'pageActions'));
    }

    /**
     * Set package page title
     *
     * @param array<string, mixed> $tplData template global data
     *
     * @return array<string, mixed>
     */
    public function updatePackagePageTitle($tplData)
    {
        $innerPage = $this->getCurrentInnerPage();
        switch ($innerPage) {
            case self::LIST_INNER_PAGE_DETAILS:
            case self::LIST_INNER_PAGE_TRANSFER:
                $tplData['pageTitle'] = $this->getPackageDetailTitle();
                break;
            case self::LIST_INNER_PAGE_NEW_STEP1:
            case self::LIST_INNER_PAGE_NEW_STEP2:
            case self::LIST_INNER_PAGE_LIST:
            default:
                $tplData['pageTitle'] = $this->getPackageListTitle();
                break;
        }
        return $tplData;
    }

    /**
     * Capability check
     *
     * @return void
     */
    protected function capabilityCheck()
    {
        parent::capabilityCheck();

        $capOk     = true;
        $innerPage = $this->getCurrentInnerPage();
        switch ($innerPage) {
            case self::LIST_INNER_PAGE_DETAILS:
                break;
            case self::LIST_INNER_PAGE_TRANSFER:
                $capOk = CapMng::can(CapMng::CAP_CREATE, false);
                break;
            case self::LIST_INNER_PAGE_NEW_STEP1:
                $capOk = (CapMng::can(CapMng::CAP_CREATE, false) && wp_verify_nonce($_GET['_wpnonce'], 'new1-package'));
                break;
            case self::LIST_INNER_PAGE_NEW_STEP2:
                $capOk = (CapMng::can(CapMng::CAP_CREATE, false) && wp_verify_nonce($_GET['_wpnonce'], 'new2-package'));
                break;
            case self::LIST_INNER_PAGE_LIST:
            default:
                break;
        }

        if (!$capOk) {
            self::notPermsDie();
        }
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
            self::ACTION_SET_RECOVERY_POINT,
            array(
                $this,
                'setRecoveryPoint',
            ),
            array($this->pageSlug)
        );
        return $actions;
    }

    /**
     * Save general settings
     *
     * @return array<string, mixed>
     */
    public function setRecoveryPoint()
    {
        $result = ['recoverySet' => false];

        try {
            $recoveryData             = ServicesRecovery::setRecoveryCallback();
            $result['recoverySet']    = true;
            $result['successMessage'] = $recoveryData['adminMessage'];
        } catch (Exception $e) {
            $result['recoverySet']  = false;
            $result['errorMessage'] = $e->getMessage();
            return $result;
        } catch (Error $e) {
            $result['recoverySet']  = false;
            $result['errorMessage'] = $e->getMessage();
            return $result;
        }

        return $result;
    }

    /**
     * Return create package link
     *
     * @return string
     */
    public function getPackageBuildS1Url()
    {
        return $this->getMenuLink(
            null,
            null,
            array(
                ControllersManager::QUERY_STRING_INNER_PAGE => self::LIST_INNER_PAGE_NEW_STEP1,
                '_wpnonce'                                  => wp_create_nonce('new1-package'),
            )
        );
    }

    /**
     * Return create package link step2
     *
     * @return string
     */
    public function getPackageBuildS2Url()
    {
        return $this->getMenuLink(
            null,
            null,
            array(
                ControllersManager::QUERY_STRING_INNER_PAGE => self::LIST_INNER_PAGE_NEW_STEP2,
                '_wpnonce'                                  => wp_create_nonce('new2-package'),
            )
        );
    }

    /**
     * called on admin_print_styles-[page] hook
     *
     * @return void
     */
    public function pageStyles()
    {
        wp_enqueue_style('dup-pro-packages');
    }

    /**
     * Get package detail title page
     *
     * @return string
     */
    protected function getPackageDetailTitle()
    {
        $package_id = isset($_REQUEST["id"]) ? sanitize_text_field($_REQUEST["id"]) : 0;
        $package    = \DUP_PRO_Package::get_by_id($package_id);
        if (!is_object($package)) {
            return __('Package Details » Not Found', 'duplicator-pro');
        } else {
            return sprintf(__('Package Details » %1$s', 'duplicator-pro'), $package->Name);
        }
    }

    /**
     * Get package list title page
     *
     * @return string
     */
    protected function getPackageListTitle()
    {
        $postfix = '';
        switch ($this->getCurrentInnerPage()) {
            case self::LIST_INNER_PAGE_NEW_STEP1:
            case self::LIST_INNER_PAGE_NEW_STEP2:
                $postfix = __('New', 'duplicator-pro');
                break;
            case self::LIST_INNER_PAGE_LIST:
            default:
                $postfix = __('All', 'duplicator-pro');
                break;
        }
        return __('Packages', 'duplicator-pro') . " » " . $postfix;
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
        switch ($innerPage) {
            case self::LIST_INNER_PAGE_DETAILS:
                $packageId = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'id', 0);
                if ($packageId == 0 || ($package = DUP_PRO_Package::get_by_id($packageId)) == false) {
                    TplMng::getInstance()->render(
                        'admin_pages/packages/details/no_package_found',
                        ['packageId' => $packageId]
                    );
                } else {
                    TplMng::getInstance()->render(
                        'admin_pages/packages/details/detail',
                        array(
                            'package' => $package,
                            'blur'    => !License::can(License::CAPABILITY_PRO_BASE),
                        )
                    );
                }
                break;
            case self::LIST_INNER_PAGE_TRANSFER:
                $packageId = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'id', 0);
                if ($packageId == 0 || ($package = DUP_PRO_Package::get_by_id($packageId)) == false) {
                    TplMng::getInstance()->render(
                        'admin_pages/packages/details/no_package_found',
                        ['packageId' => $packageId]
                    );
                } else {
                    TplMng::getInstance()->render(
                        'admin_pages/packages/details/transfer',
                        array(
                            'package' => $package,
                            'blur'    => !License::can(License::CAPABILITY_PRO_BASE),
                        )
                    );
                }
                break;
            case self::LIST_INNER_PAGE_NEW_STEP1:
                include(DUPLICATOR____PATH . '/views/packages/main/s1.setup0.base.php');
                break;
            case self::LIST_INNER_PAGE_NEW_STEP2:
                include(DUPLICATOR____PATH . '/views/packages/main/s2.scan1.base.php');
                break;
            case self::LIST_INNER_PAGE_LIST:
            default:
                include(DUPLICATOR____PATH . '/views/packages/main/packages.php');
                break;
        }
    }

    /**
     * Get package detail url
     *
     * @param false|int $package_id package id, if false return base url without id
     *
     * @return string
     */
    public function getPackageDetailsUrl($package_id = false)
    {
        $data = [
            ControllersManager::QUERY_STRING_INNER_PAGE => self::LIST_INNER_PAGE_DETAILS,
        ];
        if ($package_id !== false) {
            $data['id'] = $package_id;
        }
        return $this->getMenuLink(null, null, $data);
    }

    /**
     * Get package detail url
     *
     * @param false|int $package_id package id, if false return base url without id
     *
     * @return string
     */
    public function getPackageTransferUrl($package_id = false)
    {
        $data = [
            ControllersManager::QUERY_STRING_INNER_PAGE => self::LIST_INNER_PAGE_TRANSFER,
        ];
        if ($package_id !== false) {
            $data['id'] = $package_id;
        }
        return $this->getMenuLink(null, null, $data);
    }

    /**
     * Get packages inner page
     *
     * @return string
     */
    public function getPackagesInnerPage()
    {
        return $this->getCurrentInnerPage();
    }
}

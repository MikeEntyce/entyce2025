<?php

/**
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

defined('ABSPATH') || defined('DUPXABSPATH') || exit;

use Duplicator\Controllers\ToolsPageController;
use Duplicator\Core\CapMng;
use Duplicator\Core\Views\TplMng;
use Duplicator\Core\Views\Notifications;
use Duplicator\Models\SystemGlobalEntity;
use Duplicator\Views\PackagesHelper;

require_once(DUPLICATOR____PATH . '/classes/class.package.pagination.php');
require_once(DUPLICATOR____PATH . '/classes/ui/class.ui.dialog.php');

global $packagesViewData;

$tplMng = TplMng::getInstance();

if (isset($_REQUEST['create_from_temp'])) {
    //Takes temporary package and inserts it into the package table
    $package = DUP_PRO_Package::get_temporary_package(false);
    if ($package != null) {
        $package->save();
    }
    unset($_REQUEST['create_from_temp']);
    unset($package);
}

$system_global = SystemGlobalEntity::getInstance();

if (!empty($_REQUEST['action'])) {
    if (CapMng::can(CapMng::CAP_CREATE, false) && $_REQUEST['action'] == 'stop-build') {
        $package_id = (int) $_REQUEST['action-parameter'];
        DUP_PRO_Log::trace("stop build of $package_id");
        $action_package = DUP_PRO_Package::get_by_id($package_id);
        if ($action_package != null) {
            DUP_PRO_Log::trace("set $action_package->ID for cancel");
            $action_package->set_for_cancel();
        } else {
            DUP_PRO_Log::trace(
                "could not find package so attempting hard delete. "
                . "Old files may end up sticking around although chances are there isnt much if we couldnt nicely cancel it."
            );
            $result = DUP_PRO_Package::force_delete($package_id);
            ($result) ? DUP_PRO_Log::trace("Hard delete success") : DUP_PRO_Log::trace("Hard delete failure");
        }
        unset($action_package);
    } elseif ($_REQUEST['action'] == 'clear-messages') {
        $system_global->clearFixes();
        $system_global->save();
    }
}

$packagesViewData = array(
    'pending_cancelled_package_ids' => DUP_PRO_Package::get_pending_cancellations(),
    'rowCount'                      => 0,
);

$totalElements = DUP_PRO_Package::getNumPackages();
$statusActive  = DUP_PRO_Package::isPackageRunning();

$pager       = new DUP_PRO_Package_Pagination();
$perPage     = $pager->get_per_page();
$currentPage = ($statusActive >= 1) ? 1 : $pager->get_pagenum();
$offset      = ($currentPage - 1) * $perPage;

$global = DUP_PRO_Global_Entity::getInstance();

$orphan_info        = DUP_PRO_Server::getOrphanedPackageInfo();
$orphan_display_msg = $orphan_info['count'];

if ($orphan_display_msg) {
    $toolOrpahnPurgeURL = ToolsPageController::getInstance()->getMenuLink(
        ToolsPageController::L2_SLUG_DISAGNOSTIC,
        null,
        ['orphanpurge' => 1 ]  // Tools section opened
    )
    ?>
    <div id='dpro-error-orphans' class="error">
        <p>
            <?php
            $orphan_msg  = __(
                'There are currently (%1$s) orphaned package files taking up %2$s of space. 
                These package files are no longer visible in the packages list below and are safe to remove.',
                'duplicator-pro'
            ) . '<br/>';
            $orphan_msg .= __('Go to: Tools > General > Information > Stored Data > look for the [Delete Package Orphans] button for more details.', 'duplicator-pro') . '<br/>';
            $orphan_msg .= '<a href=' . esc_url($toolOrpahnPurgeURL) . '>' .
                __('Take me there now!', 'duplicator-pro') .
                '</a>';
            printf($orphan_msg, $orphan_info['count'], DUP_PRO_U::byteSize($orphan_info['size']));
            ?>
            <br />
        </p>
    </div>
<?php } ?>

<?php do_action(Notifications::DUPLICATOR_PRO_BEFORE_PACKAGES_HOOK); ?>

<form id="form-duplicator" method="post">
    <?php wp_nonce_field('dpro_package_form_nonce'); ?>
    <?php $tplMng->render('admin_pages/packages/toolbar'); ?>

    <table class="widefat dup-packtbl striped" aria-label="Packages List">
        <?php
        $tplMng->render(
            'admin_pages/packages/packages_table_head',
            array('totalElements' => $totalElements)
        );

        if ($totalElements == 0) {
            $tplMng->render('admin_pages/packages/no_elements_row');
        } else {
            DUP_PRO_Package::by_status_callback(
                array(
                    PackagesHelper::class,
                    'tablePackageRow',
                ),
                array(),
                $perPage,
                $offset,
                '`id` DESC'
            );
        }
        $tplMng->render(
            'admin_pages/packages/packages_table_foot',
            array('totalElements' => $totalElements)
        ); ?>
    </table>
</form>

<?php if ($totalElements > $perPage) { ?>
    <form id="form-duplicator-nav" method="post">
        <?php wp_nonce_field('dpro_package_form_nonce'); ?>
        <div class="dup-paged-nav tablenav">
            <?php if ($statusActive > 0) : ?>
                <div id="dpro-paged-progress" style="padding-right: 10px">
                    <i class="fas fa-circle-notch fa-spin fa-lg fa-fw"></i>
                    <i><?php esc_html_e('Paging disabled during build...', 'duplicator-pro'); ?></i>
                </div>
            <?php else : ?>
                <div id="dpro-paged-buttons">
                    <?php $pager->display_pagination($totalElements, $perPage); ?>
                </div>
            <?php endif; ?>
        </div>
    </form>
<?php } else { ?>
    <div style="float:right; padding:10px 5px">
        <?php echo $totalElements . '&nbsp;' . __("items", 'duplicator-pro'); ?>
    </div>
    <?php
}

$tplMng->render(
    'admin_pages/packages/packages_scripts',
    [
        'perPage'     => $perPage,
        'offset'      => $offset,
        'currentPage' => $currentPage,
    ]
);

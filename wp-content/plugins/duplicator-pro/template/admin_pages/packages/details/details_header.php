<?php

/**
 * @package Duplicator
 */

use Duplicator\Controllers\PackagesPageController;
use Duplicator\Core\CapMng;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 * @var array<string, mixed> $tplData
 * @var DUP_PRO_Package $package
 */

$package = $tplData['package'];
/** @var string */
$innerPage           = $tplData['currentInnerPage'];
$enable_transfer_tab = (
    $package->getLocalPackageFilePath(DUP_PRO_Package_File_Type::Installer) !== false &&
    $package->getLocalPackageFilePath(DUP_PRO_Package_File_Type::Archive) !== false
);

$packagesListUrl   = PackagesPageController::getInstance()->getMenuLink();
$packgeDefailsUrl  = PackagesPageController::getInstance()->getPackageDetailsUrl($package->ID);
$packgeTransferUrl = PackagesPageController::getInstance()->getPackageTransferUrl($package->ID);
?>
<h2 class="nav-tab-wrapper">  
    <a 
        href="<?php echo esc_url($packgeDefailsUrl); ?>" 
        class="nav-tab <?php echo ($innerPage == PackagesPageController::LIST_INNER_PAGE_DETAILS) ? 'nav-tab-active' : '' ?>"
    > 
        <?php esc_html_e('Details', 'duplicator-pro'); ?>
    </a> 
    <?php if (CapMng::can(CapMng::CAP_CREATE, false)) { ?>
        <a 
            href="<?php echo esc_url($packgeTransferUrl); ?>" 
            class="nav-tab <?php echo ($innerPage == PackagesPageController::LIST_INNER_PAGE_TRANSFER) ? 'nav-tab-active' : '' ?>"
            <?php if ($enable_transfer_tab === false) { ?>
                onclick="DupPro.Pack.TransferDisabled(); return false;" 
            <?php } ?>
        > 
            <?php esc_html_e('Transfer', 'duplicator-pro'); ?>
        </a>  
    <?php } ?>  
</h2>
<div class="dup-details-packages-list">
    <a href="<?php echo esc_url($packagesListUrl); ?>">[<?php esc_html_e('Packages', 'duplicator-pro'); ?>]</a>
</div>

<?php if ($package->Status == DUP_PRO_PackageStatus::ERROR) { ?>
<div id='dpro-error' class="error">
    <p>
        <b>
            <?php
                echo esc_html__('Error encountered building package, please review ', 'duplicator-pro') .
                '<a target="_blank" href="' . esc_url($package->get_log_url()) . '">' . esc_html__('package log', 'duplicator-pro') . '</a>' .
                esc_html__(' for details.', 'duplicator-pro');
            ?> 
        </b>
        <br/>
        <?php
            echo esc_html__('For more help read the ', 'duplicator-pro') .
            '<a target="_blank" href="' . esc_url(DUPLICATOR_PRO_TECH_FAQ_URL) . '">' . esc_html__('FAQ pages', 'duplicator-pro') . '</a>' .
            esc_html__(' or submit a ', 'duplicator-pro') .
            '<a target="_blank" href="' . esc_url(DUPLICATOR_PRO_BLOG_URL . 'my-account/support/') . '">' .
            esc_html__('help ticket', 'duplicator-pro') . '</a>';
        ?>.
    </p>
</div>
    <?php
}

$alertTransferDisabled          = new DUP_PRO_UI_Dialog();
$alertTransferDisabled->title   = __('Transfer Error', 'duplicator-pro');
$alertTransferDisabled->message = __('No package in default location so transfer is disabled.', 'duplicator-pro');
$alertTransferDisabled->initAlert();
?>
<script>
    DupPro.Pack.TransferDisabled = function() {
        <?php $alertTransferDisabled->showAlert(); ?>
    }
</script>
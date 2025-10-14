<?php

/**
 * @package Duplicator
 */

use Duplicator\Controllers\PackagesPageController;
use Duplicator\Core\CapMng;
use Duplicator\Package\Recovery\RecoveryPackage;
use Duplicator\Views\ViewHelper;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 * @var array<string, mixed> $tplData
 * @var ?DUP_PRO_Package $package
 */

$package = $tplData['package'];

$pack_dbonly       = $package->isDBOnly();
$pack_format       = strtolower($package->Archive->Format);
$packageDetailsURL = PackagesPageController::getInstance()->getPackageDetailsURL($package->ID);
$txt_DBOnly        = __('DB Only', 'duplicator-pro');
$archive_exists    = ($package->getLocalPackageFilePath(DUP_PRO_Package_File_Type::Archive) != false);
$isRecoveable      = RecoveryPackage::isPackageIdRecoveable($package->ID);

?>
<td colspan="8">
    <div  class="dup-package-row-details-wrapper" >
        <div class="dup-ovr-hdr">
            <label  onclick="DupPro.Pack.openLinkDetails()">
                <i class="fas fa-archive"></i>
                <?php esc_html_e('Package Overview', 'duplicator-pro'); ?>
            </label>
        </div>

        <div class="dup-ovr-bar-flex-box">
            <div class="divider">
                <label><?php esc_html_e('WordPress', 'duplicator-pro');?></label><br/>
                <?php echo esc_html($package->VersionWP); ?> &nbsp;
            </div>
            <div>
                <label><?php esc_html_e('Format', 'duplicator-pro');?></label><br/>
                <?php echo esc_html(strtoupper($pack_format)); ?>
            </div>
            <div>
                <label><?php esc_html_e('Files', 'duplicator-pro');?></label><br/>
                <?php echo ($pack_dbonly)
                    ? '<i>' . esc_html($txt_DBOnly) . '</i>'
                    : number_format($package->Archive->FileCount); ?>
            </div>
            <div class="divider">
                <label><?php esc_html_e('Folders', 'duplicator-pro');?></label><br/>
                <?php echo ($pack_dbonly)
                    ? '<i>' . esc_html($txt_DBOnly) . '</i>'
                    :  number_format($package->Archive->DirCount) ?>
            </div>
            <div class="divider">
                <label><?php esc_html_e('Tables', 'duplicator-pro');?></label><br/>
                <?php echo (int) $package->Database->info->tablesFinalCount . 'of' . (int) $package->Database->info->tablesBaseCount; ?>
            </div>
        </div>

        <div class="dup-ovr-ctrls-flex-box">

            <div class="flex-item">
            <?php
            if (CapMng::can(CapMng::CAP_EXPORT, false)) {
                $tplMng->render('admin_pages/packages/row_parts/details_download_block');
            }
            ?>
            </div>

            <!-- OPTIONS -->
            <div class="flex-item dup-ovr-opts">
                <div class="dup-ovr-ctrls-hdrs">
                    <br/><b><?php esc_html_e('Options', 'duplicator-pro');?></b>
                </div>
                <a
                    aria-label="<?php esc_attr_e("Go to package details screen", 'duplicator-pro') ?>"
                    class="button dup-details"
                    href="<?php echo esc_url($packageDetailsURL); ?>"
                >
                    <span><i class="fas fa-search"></i> <?php esc_html_e("View Details", 'duplicator-pro') ?></span>
                </a>
                <?php if (CapMng::can(CapMng::CAP_STORAGE, false)) { ?>
                    <?php if ($archive_exists) : ?>
                        <button class="button dup-transfer"
                            aria-label="<?php esc_attr_e('Go to package transfer screen', 'duplicator-pro') ?>"
                            onclick="DupPro.Pack.OpenPackTransfer(<?php echo (int) $package->ID; ?>); return false;">
                            <span><i class="fa fa-exchange-alt fa-fw"></i> <?php esc_html_e("Transfer Package", 'duplicator-pro') ?></span>
                        </button>
                    <?php else : ?>
                        <span title="<?php esc_attr_e('Transfer packages requires the use of built-in default storage!', 'duplicator-pro') ?>">
                            <button class="button disabled" >
                                <span><i class="fa fa-exchange-alt fa-fw"></i> <?php esc_html_e("Transfer Package", 'duplicator-pro') ?></span>
                            </button>
                        </span>
                    <?php endif; ?>
                <?php } ?>

                <?php if (CapMng::can(CapMng::CAP_BACKUP_RESTORE, false)) { ?>
                    <button
                        aria-label="<?php esc_attr_e("Recover this Package", 'duplicator-pro') ?>"
                        class="button dpro-btn-open-recovery-box <?php echo ($isRecoveable) ? '' : 'maroon'?>"
                        data-package-id="<?php echo (int) $package->ID; ?>"
                    >
                        <?php ViewHelper::disasterIcon(true); ?>
                        <?php esc_html_e("Disaster Recovery", 'duplicator-pro'); ?>
                    </button>
                <?php } ?>
            </div>
        </div>
    </div>
</td>

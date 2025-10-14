<?php

/**
 * @package Duplicator
 */

use Duplicator\Core\CapMng;

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

/** @var int */
$status = $tplData['status'];

if ($status <= DUP_PRO_PackageStatus::PRE_PROCESS || $status >= DUP_PRO_PackageStatus::COMPLETE) {
    return;
}
?>
<tr class="dup-row-progress">
    <td colspan="11">
        <div class="wp-filter dup-build-msg">
            <?php if ($status < DUP_PRO_PackageStatus::STORAGE_PROCESSING) : ?>
                <!-- BUILDING PROGRESS-->
                <div id='dpro-progress-status-message-build'>
                    <div class='status-hdr'>
                        <?php esc_html_e('Building Package', 'duplicator-pro'); ?>&nbsp;
                        <i class="fa fa-cog fa-sm fa-spin"></i>&nbsp;
                        <span id="status-<?php echo (int) $package->ID; ?>"><?php echo (int) $status; ?></span>%
                    </div>
                    <small>
                        <?php esc_html_e('Please allow it to finish before creating another one.', 'duplicator-pro'); ?>
                    </small>
                </div>
            <?php else : ?>
                <!-- TRANSFER PROGRESS -->
                <div id='dpro-progress-status-message-transfer'>
                    <div class='status-hdr'>
                        <?php esc_html_e('Transferring Package', 'duplicator-pro'); ?>&nbsp;
                        <i class="fa fa-sync fa-sm fa-spin"></i>&nbsp;
                        <span id="status-progress-<?php echo (int) $package->ID; ?>">0</span>%
                        <span id="status-<?php echo (int) $package->ID; ?>" class="no-display" >
                            <?php echo (int) $status; ?>
                        </span>
                    </div>
                    <small id="dpro-progress-status-message-transfer-msg">
                        <?php esc_html_e('Getting Transfer State...', 'duplicator-pro'); ?>
                    </small>
                </div>
            <?php endif; ?>
            <div id="dup-progress-bar-area">
                <div class="dup-pro-meter-wrapper">
                    <div class="dup-pro-meter blue dup-pro-fullsize">
                        <span></span>
                    </div>
                    <span class="text"></span>
                </div>
            </div>
            <?php if (CapMng::can(CapMng::CAP_CREATE, false)) { ?>
            <button onclick="DupPro.Pack.StopBuild(<?php echo (int) $package->ID; ?>); return false;" class="button button-large dup-build-stop-btn">
                <i class="fa fa-times fa-sm"></i>&nbsp;
                <?php
                if ($status >= 75) {
                    esc_html_e('Stop Transfer', 'duplicator-pro');
                } elseif ($status > 0) {
                    esc_html_e('Stop Build', 'duplicator-pro');
                } else {
                    esc_html_e('Cancel Pending', 'duplicator-pro');
                }
                ?>
            </button>
            <?php } ?>
        </div>
    </td>
</tr>

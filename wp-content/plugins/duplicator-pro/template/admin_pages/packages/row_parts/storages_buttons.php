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

$package         = $tplData['package'];
$storage_problem = $package->transferWasInterrupted();
$remote_style    = ($storage_problem) ? 'remote-data-fail' : '';

if (!CapMng::can(CapMng::CAP_STORAGE, false)) {
    ?>
    <td></td>
    <?php
    return;
}

if ($storage_problem) { ?>
    <td class="dup-cell-btns dup-cell-store-btn"
        aria-label="<?php esc_attr_e("Remote Storages", 'duplicator-pro') ?>"
        onclick="DupPro.Pack.ShowRemote(<?php echo (int) $package->ID; ?>, '<?php echo esc_js($package->NameHash); ?>');"
        title="<?php esc_attr_e("Error during storage transfer.", 'duplicator-pro') ?>">
        <span class="button button-link">
            <i class="fas fa-server <?php echo esc_attr($remote_style); ?>"></i>
        </span>
    </td>
<?php } else { ?>
    <td class="dup-cell-btns dup-cell-store-btn"
        onclick="DupPro.Pack.ShowRemote(<?php echo (int) $package->ID; ?>, '<?php echo esc_js($package->NameHash); ?>');"
        aria-label="<?php esc_attr_e("Remote Storages", 'duplicator-pro') ?>">
        <span class="button button-link">
            <i class="fas fa-server <?php echo esc_attr($remote_style); ?>"></i>
        </span>
    </td>
<?php } ?>
</td>
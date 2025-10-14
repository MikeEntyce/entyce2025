<?php

/**
 * @package Duplicator
 */

use Duplicator\Core\CapMng;
use Duplicator\Package\Recovery\BackupPackage;
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

$package         = $tplData['package'];
$storage_problem = $package->transferWasInterrupted();

if (!CapMng::can(CapMng::CAP_BACKUP_RESTORE, false)) {
    return;
}

?>
<button 
    type="button" class="button button-link dup-restore-backup"
    data-package-id="<?php echo (int) $package->ID; ?>"
    <?php disabled($package->haveLocalStorage(), false); ?>
    aria-label="<?php esc_attr_e("Restore backup", 'duplicator-pro') ?>"
    title="<?php esc_attr_e("Restore backup.", 'duplicator-pro') ?>"
    >
    <?php ViewHelper::restoreIcon(); ?> <?php esc_html_e("Restore", 'duplicator-pro'); ?>
</button>
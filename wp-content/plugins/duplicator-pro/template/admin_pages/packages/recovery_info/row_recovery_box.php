<?php

/**
 * Duplicator package row in table packages list
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

use Duplicator\Package\Recovery\RecoveryPackage;
use Duplicator\Views\ViewHelper;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 * @var array<string, mixed> $tplData
 * @var \DUP_PRO_Package $package
 */

$package      = $tplData['package'];
$isRecoveable = RecoveryPackage::isPackageIdRecoveable($package->ID);

if ($isRecoveable) {
    $tplMng->render('admin_pages/packages/recovery_info/row_recovery_box_available');
} else {
    $tplMng->render('admin_pages/packages/recovery_info/row_recovery_box_unavailable');
}
?>
<hr class="margin-top-1 margin-bottom-1" >
<small><i>
<?php
echo wp_kses(
    __(
        'The <b>Disaster Recovery</b> is a streamlined Restore Backup system used to rapidly restore your site from a disaster. 
        A functioning <b>WordPress backend is not required</b>, only the LINK or the Launcher provided with the Disaster Recovery 
        is needed to restore the Backup.',
        'duplicator-pro'
    ),
    ViewHelper::GEN_KSES_TAGS
);
?>
<br>
<?php
printf(
    wp_kses(
        _x(
            'Backups that are not Disaster Recovery eligible will can to use the <b>Restore Backup button</b> 
            or %1$sstandard install modes%2$s for re-deployment.',
            '%1$s and %2$s represents the opening and closing HTML tags for an anchor or link',
            'duplicator-pro'
        ),
        ViewHelper::GEN_KSES_TAGS
    ),
    '<a href="' . esc_url(DUPLICATOR_PRO_DUPLICATOR_DOCS_URL . 'recover-a-backup') . '" target="_blank">',
    '</a>'
);
?>
</i></small>
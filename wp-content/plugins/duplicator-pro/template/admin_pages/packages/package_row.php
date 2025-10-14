<?php

/**
 * @package Duplicator
 */

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

global $packagesViewData;

// If its in the pending cancels consider it stopped
if (in_array($package->ID, $packagesViewData['pending_cancelled_package_ids'])) {
    $status = DUP_PRO_PackageStatus::PENDING_CANCEL;
} else {
    $status = $package->Status;
}

if ($package->Status >= DUP_PRO_PackageStatus::COMPLETE) {
    $tplMng->render('admin_pages/packages/package_row_complete', ['status' => $status]);
} else {
    $tplMng->render('admin_pages/packages/package_row_incomplete', ['status' => $status]);
}
$tplMng->render('admin_pages/packages/package_row_building', ['status' => $status]);

$packagesViewData['rowCount']++;

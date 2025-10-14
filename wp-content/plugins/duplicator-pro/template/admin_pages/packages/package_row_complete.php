<?php

/**
 * @package Duplicator
 */

use Duplicator\Controllers\PackagesPageController;
use Duplicator\Package\Recovery\RecoveryPackage;
use Duplicator\Views\UserUIOptions;

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

if ($status < DUP_PRO_PackageStatus::COMPLETE) {
    return;
}

$global = DUP_PRO_Global_Entity::getInstance();
global $packagesViewData;


$isRecoveable      = RecoveryPackage::isPackageIdRecoveable($package->ID);
$isRecoverPoint    = (RecoveryPackage::getRecoverPackageId() === $package->ID);
$pack_name         = $package->Name;
$pack_archive_size = $package->Archive->Size;
$pack_dbonly       = $package->isDBOnly();
$brand             = $package->Brand;

//Links
$uniqueid         = $package->NameHash;
$archive_exists   = ($package->getLocalPackageFilePath(DUP_PRO_Package_File_Type::Archive) != false);
$installer_exists = ($package->getLocalPackageFilePath(DUP_PRO_Package_File_Type::Installer) != false);
$progress_error   = '';

//ROW CSS
$rowClasses   = array('');
$rowClasses[] = 'dup-row';
$rowClasses[] = 'dup-row-complete';
$rowClasses[] = ($packagesViewData['rowCount'] % 2 == 0) ? 'dup-row-alt-dark' : 'dup-row-alt-light';
$rowClasses[] = ($isRecoverPoint) ? 'dup-recovery-package' : '';
$rowCSS       = trim(implode(' ', $rowClasses));


//ArchiveInfo
$archive_name         = $package->Archive->File;
$archiveDownloadURL   = $package->getLocalPackageFileURL(DUP_PRO_Package_File_Type::Archive);
$installerDownloadURL = $package->getLocalPackageFileURL(DUP_PRO_Package_File_Type::Installer);
$installerFullName    = $package->Installer->getInstallerName();

//Lang Values
$txt_DatabaseOnly = __('Database Only', 'duplicator-pro');

switch ($package->Type) {
    case DUP_PRO_PackageType::MANUAL:
        $package_type_string = __('Manual', 'duplicator-pro');
        break;
    case DUP_PRO_PackageType::SCHEDULED:
        $package_type_string = __('Schedule', 'duplicator-pro');
        break;
    case DUP_PRO_PackageType::RUN_NOW:
        $lang_schedule       = __('Schedule', 'duplicator-pro');
        $lang_title          = __('This package was started manually from the schedules page.', 'duplicator-pro');
        $package_type_string = "{$lang_schedule}<span><sup>&nbsp;<i class='fas fa-cog fa-sm pointer' title='{$lang_title}'></i>&nbsp;</sup><span>";
        break;
    default:
        $package_type_string = __('Unknown', 'duplicator-pro');
        break;
}

$packageDetailsURL = PackagesPageController::getInstance()->getPackageDetailsURL($package->ID);
$createdFormat     = UserUIOptions::getInstance()->get(UserUIOptions::VAL_CREATED_DATE_FORMAT);

?>
<tr 
    id="dup-row-pack-id-<?php echo (int) $package->ID; ?>" 
    data-package-id="<?php echo (int) $package->ID; ?>" 
    class="<?php echo esc_attr($rowCSS); ?>" >
    <td class="dup-check-column dup-cell-chk">
        <label for="<?php echo (int) $package->ID; ?>">
        <input 
            name="delete_confirm" 
            type="checkbox" 
            id="<?php echo (int) $package->ID; ?>" 
            data-archive-name="<?php echo esc_attr($archive_name); ?>" 
            data-installer-name="<?php echo esc_attr($installerFullName); ?>" />
        </label>
    </td>
    <td class="dup-name-column dup-cell-name">
        <?php echo esc_html($pack_name); ?>
    </td>
    <td class="dup-note-column">
        <?php echo esc_html($package->notes); ?>
    </td>
    <td class="dup-storages-column">
    </td>
    <td class="dup-flags-column">
        <?php $tplMng->render('admin_pages/packages/row_parts/falgs_cell'); ?>
    </td>
    <td class="dup-created-column" >
        <?php echo esc_html(DUP_PRO_Package::format_and_get_local_date_time($package->getCreated(), $createdFormat)); ?>
    </td>
    <td class="dup-age-column">
        <?php echo esc_html($package->getPackageLife('human')); ?>
    </td>
    <td class="dup-size-column" >
        <?php echo esc_html(DUP_PRO_U::byteSize($pack_archive_size)); ?>
    </td>
    <td class="dup-cell-btns dup-download-column">
        <?php $tplMng->render('admin_pages/packages/row_parts/download_buttons'); ?>
    </td>
    <td class="dup-cell-btns dup-restore-column" >
        <?php $tplMng->render('admin_pages/packages/row_parts/restore_backup_button'); ?>
    </td>
    <td class="dup-cell-btns dup-cell-toggle-btn dup-toggle-details dup-details-column">
        <span class="button button-link">
            <i class="fas fa-chevron-left"></i>
        </span>
    </td>
</tr>
<tr id="dup-row-pack-id-<?php echo (int) $package->ID; ?>-details" class="dup-row-details">
    <?php $tplMng->render('admin_pages/packages/row_parts/details_package'); ?>
</tr>


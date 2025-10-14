<?php

/**
 * Duplicator package row in table packages list
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

use Duplicator\Addons\ProBase\License\License;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng $tplMng
 * @var array<string, mixed> $tplData
 */

$global = DUP_PRO_Global_Entity::getInstance();

if ($global->installer_name_mode == DUP_PRO_Global_Entity::INSTALLER_NAME_MODE_SIMPLE) {
    $packageExeNameModeMsg = __(
        "When clicking the Installer download button, the 'Save as' dialog is currently defaulting the name to 'installer.php'. 
        To improve the security and get more information, go to: 
        Settings > Packages Tab > Installer > Name option or click on the gear icon at the top of this page.",
        'duplicator-pro'
    );
} else {
    $packageExeNameModeMsg = __(
        "When clicking the Installer download button, the 'Save as' dialog is defaulting the name to '[name]_[hash]_[date]_installer.php'. 
        This is the secure and recommended option.  
        For more information, go to: Settings > Packages Tab > Installer > Name or click on the gear icon at the top of this page.<br/><br/>
        To quickly copy the hashed installer name, to your clipboard use the copy icon link or click the installer name and manually copy the selected text.",
        'duplicator-pro'
    );
}

global $packagesViewData;

$tooltipContent = $tplMng->render('admin_pages/packages/packages_table_head_status_icons', [], false);
?>
<h2 class="screen-reader-text">Packages list</h2>
<thead>
    <tr>
        <th class="dup-check-column" style="width:10px;">
            <input 
                type="checkbox" 
                id="dup-chk-all" 
                title="<?php esc_attr_e("Select all packages", 'duplicator-pro') ?>" 
                style="margin-left:15px" onclick="DupPro.Pack.SetDeleteAll()" />
        </th>
        <th class="dup-name-column" >
            <?php esc_html_e("Backup Name", 'duplicator-pro') ?>
        </th>
        <th class="dup-note-column">
            <?php esc_html_e("Note", 'duplicator-pro') ?>
        </th>
        <th class="dup-storages-column">
            <?php esc_html_e("Storages", 'duplicator-pro') ?>
        </th>
        <th class="dup-flags-column">
            <?php esc_html_e("Status", 'duplicator-pro') ?>&nbsp;
            <i 
                class="fa-solid fa-circle-info"
                data-tooltip-title="<?php esc_attr_e("Status Icons", 'duplicator-pro'); ?>"
                data-tooltip="<?php echo esc_attr($tooltipContent); ?>"
            ></i>
        </th>
        <th class="dup-created-column">
            <?php esc_html_e("Created", 'duplicator-pro') ?>
        </th>
        <th class="dup-age-column">
            <?php esc_html_e("Age", 'duplicator-pro') ?>
        </th>
        <th class="dup-size-column">
            <?php esc_html_e("Size", 'duplicator-pro') ?>
        </th>
        <th class="dup-download-column" style="width:75px;"></th>
        <th class="dup-restore-column" style="width:25px;"></th>
        <th id="dup-header-chkall" class="dup-details-column" >
        <?php if ($tplData['totalElements'] > 0) { ?>
                <a href="javascript:void(0)" class="button button-link"><i class="fas fa-chevron-left"></i></a>
        <?php } ?>
        </th>
    </tr>
</thead>

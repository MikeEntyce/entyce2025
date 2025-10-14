<?php

/**
 * Duplicator package row in table packages list
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

use Duplicator\Controllers\ImportPageController;
use Duplicator\Controllers\SettingsPageController;
use Duplicator\Controllers\StoragePageController;
use Duplicator\Core\CapMng;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 * @var array<string, mixed> $tplData
 * @var ?DUP_PRO_Package $package
 */

$global               = DUP_PRO_Global_Entity::getInstance();
$package              = $tplData['package'];
$archive_exists       = ($package->getLocalPackageFilePath(DUP_PRO_Package_File_Type::Archive) != false);
$installer_exists     = ($package->getLocalPackageFilePath(DUP_PRO_Package_File_Type::Installer) != false);
$archiveDownloadURL   = $package->getLocalPackageFileURL(DUP_PRO_Package_File_Type::Archive);
$installerDownloadURL = $package->getLocalPackageFileURL(DUP_PRO_Package_File_Type::Installer);
$defaultStorageUrl    = StoragePageController::getEditDefaultUrl();

$txt_RequiresRemote = sprintf(
    "%s <a href='{$defaultStorageUrl}' target='_blank'>%s <i class='far fa-hdd fa-fw fa-sm'></i></a>",
    __('This option requires the package to use the built-in default', 'duplicator-pro'),
    __('storage location', 'duplicator-pro')
);

?>
<div class="dup-ovr-ctrls-hdrs">
    <i class="fas fa-link fa-fw"></i>
    <b><?php esc_html_e('Install Resources', 'duplicator-pro');?> <br/></b>
    <span class="dup-info-msg01">
        <?php esc_html_e('Links are sensitive. Keep them safe!', 'duplicator-pro');?>
    </span>

    <?php if (CapMng::can(CapMng::CAP_STORAGE, false)) { ?>
        <a class="dup-ovr-ref-links-more no-outline" href="javascript:void(0)"
        onclick="DupPro.Pack.ShowRemote(<?php echo (int) $package->ID; ?>, '<?php echo esc_js($package->NameHash); ?>');">
            <i class="fas fa-server fa-xs"></i>
            <?php esc_html_e('Storages ...', 'duplicator-pro');?>
        </a>
    <?php } ?>

</div>


<!-- =======================
ARCHIVE FILE: -->
<div class="dup-ovr-copy-flex-box">
<div class="flex-item">
    <i class="far fa-file-archive fa-fw"></i>
    <b><?php esc_html_e('Archive File', 'duplicator-pro');?></b>
    <sup>
        <?php
        $archiveFileToolTipTitle = sprintf(
            __('This link is used with the <a href=\'%1$s\'>%2$s</a> %3$s', 'duplicator-pro'),
            esc_url(ImportPageController::getInstance()->getMenuLink()),
            __('Import Link Install', 'duplicator-pro'),
            __(
                'feature. Use the Copy Link button to copy this URL archive file link to import on another WordPress site.',
                'duplicator-pro'
            )
        );?>
        <i class="fas fa-question-circle fa-xs fa-fw dup-archive-help"
            data-tooltip-title="<?php esc_attr_e("Archive File", 'duplicator-pro'); ?>"
            data-tooltip="<?php echo esc_attr($archiveFileToolTipTitle);?>"></i>
    </sup>


</div>
<div class="flex-item"></div>
</div>

<div class="dup-ovr-copy-flex-box dup-box-file">
<?php if ($archive_exists) : ?>
    <div class="flex-item">
    <input type="text" class="dup-ovr-ref-links" readonly="readonly"
        value="<?php echo esc_attr($archiveDownloadURL); ?>"
        title="<?php echo esc_attr($archiveDownloadURL); ?>"
        onfocus="jQuery(this).select();" />
    <span class="fas fa-arrow-alt-circle-down dup-ovr-ref-links-icon"
            title="<?php esc_attr_e('Archive Import Link (URL)', 'duplicator-pro');?>"></span>
    </div>
    <div class="flex-item">
    <span onclick="jQuery(this).parent().parent().find('.dup-ovr-ref-links').select();">
        <span data-dup-copy-value="<?php echo esc_attr($archiveDownloadURL); ?>"
                class="dup-ovr-ref-copy no-select">
            <i class='far fa-copy dup-cursor-pointer'></i>
            <?php esc_html_e('Copy Link', 'duplicator-pro');?>
        </span>
    </span>
    <span class="dup-ovr-ref-dwnld"
        aria-label="<?php esc_html_e("Download Archive", 'duplicator-pro') ?>"
        onclick="DupPro.Pack.DownloadFile('<?php echo esc_attr($archiveDownloadURL); ?>',
                '<?php echo esc_attr($package->get_archive_filename()); ?>');">
        <i class="fas fa-download"></i> <?php esc_html_e('Download', 'duplicator-pro');?>
    </span>
    </div>
<?php else : ?>
    <div class="flex-item maroon">
        <?php
        echo wp_kses(
            $txt_RequiresRemote,
            [
                'a' => [
                    'href'   => [],
                    'target' => [],
                ],
                'i' => [
                    'class' => [],
                ],
            ]
        );
        ?>.
    </div>
<?php endif; ?>
</div><br/>

<!-- =======================
ARCHIVE INSTALLER: -->
<?php
switch ($global->installer_name_mode) {
    case DUP_PRO_Global_Entity::INSTALLER_NAME_MODE_SIMPLE:
        $settingsPackageUrl    = SettingsPageController::getInstance()->getMenuLink(SettingsPageController::L2_SLUG_PACKAGE);
        $lockIcon              = 'fa-lock-open';
        $installerToolTipTitle = sprintf(
            __(
                'Using standard installer name. To improve security, switch to hashed change in %1$sSettings%2$s',
                'duplicator-pro'
            ),
            '<a href="' . esc_url($settingsPackageUrl) . '" >',
            '</a>'
        );
        break;

    case DUP_PRO_Global_Entity::INSTALLER_NAME_MODE_WITH_HASH:
    default:
        $lockIcon              = 'fa-lock';
        $installerToolTipTitle = __('Using more secure, hashed installer name.', 'duplicator-pro');
        break;
}
$installerName = $package->Installer->getDownloadName();
?>

<i class="fas fa-bolt fa-fw"></i>
<b><?php esc_html_e('Archive Installer', 'duplicator-pro');?></b>
<sup>
<i class="fas <?php echo esc_attr($lockIcon); ?> dup-cursor-pointer fa-fw fa-xs dup-installer-help"
style="padding-left:3px"
data-tooltip="<?php echo esc_html($installerToolTipTitle); ?>"></i>
</sup>
<div class="dup-ovr-copy-flex-box dup-box-installer">
<?php if ($installer_exists) : ?>
    <div class="flex-item">
        <input type="text" class="dup-ovr-ref-links" readonly="readonly"
            value="<?php echo esc_attr($installerName); ?>"
            title="<?php echo esc_attr($installerName); ?>"
            onfocus="jQuery(this).select();" /><br/>
        <span class="dup-info-msg01">
        &nbsp;<?php esc_html_e('These links contain highly sensitive data. Share with extra caution!', 'duplicator-pro');?>
        </span>
    </div>
    <div class="flex-item">
        <span onclick="jQuery(this).parent().parent().find('.dup-ovr-ref-links').select();">
            <span data-dup-copy-value="<?php echo esc_attr($installerName); ?>" class="dup-ovr-ref-copy no-select">
                <i class='far fa-copy dup-cursor-pointer'></i>
                <?php esc_html_e('Copy Name', 'duplicator-pro');?>
            </span>
        </span>
        <span class="dup-ovr-ref-dwnld"
            aria-label="<?php esc_html_e("Download Installer", 'duplicator-pro') ?>"
            onclick="DupPro.Pack.DownloadFile('<?php echo esc_attr($installerDownloadURL); ?>');">
            <i class="fas fa-download"></i> <?php esc_html_e('Download', 'duplicator-pro');?>
        </span>
    </div>
<?php else : ?>
    <div class="flex-item maroon">
        <?php
        echo wp_kses(
            $txt_RequiresRemote,
            [
                'a' => [
                    'href'   => [],
                    'target' => [],
                ],
                'i' => [
                    'class' => [],
                ],
            ]
        );
        ?>.
    </div>
<?php endif; ?>
</div>

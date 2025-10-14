<?php

/**
 * Duplicator package row in table packages list
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

use Duplicator\Controllers\ImportPageController;
use Duplicator\Controllers\PackagesPageController;
use Duplicator\Controllers\SettingsPageController;
use Duplicator\Controllers\ToolsPageController;
use Duplicator\Core\CapMng;
use Duplicator\Package\Recovery\RecoveryPackage;
use Duplicator\Views\ViewHelper;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng $tplMng
 * @var array<string, mixed> $tplData
 */

$settingsUrl = esc_url($ctrlMng->getMenuLink($ctrlMng::SETTINGS_SUBMENU_SLUG, SettingsPageController::L2_SLUG_PACKAGE));
$templateUrl = esc_url($ctrlMng->getMenuLink($ctrlMng::TOOLS_SUBMENU_SLUG, ToolsPageController::L2_SLUG_TEMPLATE));
$recoveryUrl = esc_url($ctrlMng->getMenuLink($ctrlMng::TOOLS_SUBMENU_SLUG, ToolsPageController::L2_SLUG_RECOVERY));
?>
<input type="hidden" id="action" name="action" />
<input type="hidden" id="action-parameter" name="action-parameter" />

<!-- ====================
TOOL-BAR -->
<table class="dpro-edit-toolbar">
<tr>
    <td>
        <label for="dup-pack-bulk-actions" class="screen-reader-text">Select bulk action</label>
        <select id="dup-pack-bulk-actions">
            <option value="-1" selected="selected">
                <?php esc_html_e("Bulk Actions", 'duplicator-pro') ?>
            </option>
            <?php if (CapMng::can(CapMng::CAP_CREATE, false)) { ?>
            <option value="delete" title="<?php esc_attr_e("Delete selected package(s)", 'duplicator-pro') ?>">
                <?php esc_html_e("Delete", 'duplicator-pro') ?>
            </option>
            <?php } ?>
        </select>
        <input type="button" id="dup-pack-bulk-apply" class="button action"
            value="<?php esc_attr_e("Apply", 'duplicator-pro') ?>"
            onclick="DupPro.Pack.ConfirmDelete()" />

        <span class="btn-separator"></span>

        <span class="button grey-icon dup-contextual-help"
            title="<?php esc_attr_e("Tutorial", 'duplicator-pro') ?>"
            onclick="DupPro.Pack.openLinkDetails()">
            <i class="far fa-lightbulb fa-fw"></i>
        </span>

        <?php if (CapMng::can(CapMng::CAP_SETTINGS, false)) { ?>
        <a href="<?php echo esc_url($settingsUrl); ?>"
           class="button grey-icon dpro-toolbar-settings"
           title="<?php esc_attr_e("Package Settings", 'duplicator-pro') ?>" >
            <i class="fas fa-sliders-h fa-fw"></i>
        </a>
        <?php } ?>
        <?php if (CapMng::can(CapMng::CAP_CREATE, false)) { ?>
        <a href="<?php echo esc_url($templateUrl); ?>" 
           class="button dpro-toolbar-templates"
           title="<?php esc_attr_e("Templates", 'duplicator-pro') ?>" >
            <i class="far fa-clone fa-fw"></i>
        </a>
        <?php } ?>

        <span class="btn-separator"></span>

        <?php if (CapMng::can(CapMng::CAP_IMPORT, false)) { ?>
        <a href="<?php echo esc_url(ImportPageController::getImportPageLink()); ?>" 
           id="btn-logs-dialog"
           class="button dpro-toolbar-import" 
           title="<?php esc_attr_e("Import", 'duplicator-pro') ?>">
           <i class="fas fa-arrow-alt-circle-down fa-fw"></i>
        </a>
        <?php } ?>
        <?php if (CapMng::can(CapMng::CAP_BACKUP_RESTORE, false)) { ?>
        <span 
            class="dpro-toolbar-recovery-info button <?php echo (RecoveryPackage::getRecoverPackageId() === false ? 'dup-recovery-unset' : ''); ?>" 
            title="<?php esc_attr_e("Disaster Recovery", 'duplicator-pro') ?>"
        >
            <?php ViewHelper::disasterIcon(); ?> 
        </span>
        <?php } ?>
    </td>
    <td>
        <?php
        if (CapMng::can(CapMng::CAP_CREATE, false)) {
            $tipContent = __(
                'This will create a new package. If a package is currently running then this button will be disabled.',
                'duplicator-pro'
            );
            ?>
        <div class="btnnav">
            <span
                class="dup-new-package-wrapper"
                data-tooltip-title="<?php esc_attr_e('Package creation', 'duplicator-pro'); ?>"
                data-tooltip="<?php echo esc_attr($tipContent); ?>"
            >
                <a  
                    href="<?php echo esc_url(PackagesPageController::getInstance()->getPackageBuildS1Url()); ?>"
                    id="dup-pro-create-new" 
                    class="button <?php echo DUP_PRO_Package::isPackageRunning() ? 'disabled' : ''; ?>"
                >
                <?php esc_html_e('Create New', 'duplicator-pro'); ?>
                </a>
            </span>
        </div>
        <?php } ?>
    </td>
</tr>
</table>

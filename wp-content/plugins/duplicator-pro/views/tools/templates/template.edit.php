<?php

/**
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

defined('ABSPATH') || defined('DUPXABSPATH') || exit;

use Duplicator\Addons\ProBase\License\License;
use Duplicator\Controllers\SettingsPageController;
use Duplicator\Controllers\ToolsPageController;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapJson;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Models\BrandEntity;

$tplMng = TplMng::getInstance();
/** @var bool */
$blur = TplMng::getInstance()->getGlobalValue('blur');

$templates_tab_url = ControllersManager::getMenuLink(
    ControllersManager::TOOLS_SUBMENU_SLUG,
    ToolsPageController::L2_SLUG_TEMPLATE
);
$edit_template_url =  ControllersManager::getMenuLink(
    ControllersManager::TOOLS_SUBMENU_SLUG,
    ToolsPageController::L2_SLUG_TEMPLATE,
    null,
    array('inner_page' => 'edit')
);

$bandListUrl = ControllersManager::getMenuLink(
    ControllersManager::SETTINGS_SUBMENU_SLUG,
    SettingsPageController::L2_SLUG_PACKAGE,
    SettingsPageController::L3_SLUG_PACKAGE_BRAND
);

$brandDefaultEditUrl = ControllersManager::getMenuLink(
    ControllersManager::SETTINGS_SUBMENU_SLUG,
    SettingsPageController::L2_SLUG_PACKAGE,
    SettingsPageController::L3_SLUG_PACKAGE_BRAND,
    [
        'view'   => 'edit',
        'action' => 'default',
    ]
);

$brandBaseEditUrl = ControllersManager::getMenuLink(
    ControllersManager::SETTINGS_SUBMENU_SLUG,
    SettingsPageController::L2_SLUG_PACKAGE,
    SettingsPageController::L3_SLUG_PACKAGE_BRAND,
    [
        'view'   => 'edit',
        'action' => 'edit',
    ]
);

global $wp_version;
global $wpdb;
$global = DUP_PRO_Global_Entity::getInstance();

$nonce_action = 'duppro-template-edit';

$was_updated         = false;
$package_template_id = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'package_template_id', -1);
if (($package_templates      = DUP_PRO_Package_Template_Entity::getAll()) === false) {
    $package_templates = array();
}
$package_template_count = count($package_templates);

// For now not including in filters since don't want to encourage use
// with schedules since filtering creates incomplete multisite
$displayMultisiteTab = (is_multisite() && License::can(License::CAPABILITY_MULTISITE_PLUS));

$view_state     = DUP_PRO_UI_ViewState::getArray();
$ui_css_archive = (DUP_PRO_UI_ViewState::getValue('dup-template-archive-panel') ? 'display:block' : 'display:none');
$ui_css_install = (DUP_PRO_UI_ViewState::getValue('dup-template-install-panel') ? 'display:block' : 'display:none');

if (
    $package_template_id == -1 ||
    ($package_template = DUP_PRO_Package_Template_Entity::getById($package_template_id)) == false
) {
    $package_template = new DUP_PRO_Package_Template_Entity();
}
DUP_PRO_Log::traceObject("getting template $package_template_id", $package_template);

if (!empty($_REQUEST['action'])) {
    DUP_PRO_U::verifyNonce($_REQUEST['_wpnonce'], $nonce_action);
    if ($_REQUEST['action'] == 'save') {
        DUP_PRO_Log::traceObject('request', $_REQUEST);

        // Checkboxes don't set post values when off so have to manually set these
        $package_template->setFromInput(SnapUtil::INPUT_REQUEST);
        $package_template->save();
        $was_updated = true;
    } elseif ($_REQUEST['action'] == 'copy-template') {
        $source_template_id = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'duppro-source-template-id', -1);

        if ($source_template_id > 0) {
            $package_template->copy_from_source_id($source_template_id);
            $package_template->save();
        }
    }
}

$installer_cpnldbaction = $package_template->installer_opts_cpnl_db_action;
$upload_dir             = DUP_PRO_Archive::getArchiveListPaths('uploads');
$content_path           = DUP_PRO_Archive::getArchiveListPaths('wpcontent');
$archive_format         = ($global->getBuildMode() == DUP_PRO_Archive_Build_Mode::DupArchive ? 'daf' : 'zip');
?>

<form 
    id="dpro-template-form" 
    class="dup-monitored-form <?php echo ($blur ? 'dup-mock-blur' : ''); ?>"
    data-parsley-validate data-parsley-ui-enabled="true" 
    action="<?php echo esc_url($edit_template_url); ?>" 
    method="post"
>
<?php wp_nonce_field($nonce_action); ?>
<input type="hidden" id="dpro-template-form-action" name="action" value="save">
<input type="hidden" name="package_template_id" value="<?php echo intval($package_template->getId()); ?>">

<!-- ====================
SUB-TABS -->
<?php if ($was_updated) : ?>
    <div class="notice notice-success is-dismissible dpro-wpnotice-box">
        <p>
            <?php esc_html_e('Template Updated', 'duplicator-pro'); ?>
        </p>
    </div>
<?php endif; ?>

<!-- ====================
TOOL-BAR -->
<table class="dpro-edit-toolbar">
    <tr>
        <td>
            <?php
            if ($package_template_count > 0) :
                $general_templates  = array();
                $existing_templates = array();
                foreach ($package_templates as $copy_package_template) {
                    if ($copy_package_template->getId() != $package_template->getId()) {
                        if ($copy_package_template->is_default || $copy_package_template->is_manual) {
                            $general_templates[$copy_package_template->getId()] = $copy_package_template->is_manual
                                ? __("Active Build Settings", 'duplicator-pro')
                                : $copy_package_template->name;
                        } else {
                            $existing_templates[$copy_package_template->getId()] = $copy_package_template->name;
                        }
                    }
                }
                ?>

                <select name="duppro-source-template-id">
                    <option value="-1"><?php esc_html_e("Copy From", 'duplicator-pro'); ?></option>
                    <?php
                    if (!empty($general_templates)) {
                        asort($general_templates);
                        ?>
                        <optgroup label="<?php esc_attr_e("General Templates", 'duplicator-pro'); ?>">
                            <?php
                            foreach ($general_templates as $id => $val) {
                                ?>
                                <option value="<?php echo $id; ?>"><?php echo esc_html($val); ?></option>
                                <?php
                            }
                            ?>
                        </optgroup>
                        <?php
                    }
                    ?>
                    <?php
                    if (!empty($existing_templates)) {
                        asort($existing_templates);
                        ?>
                        <optgroup label="<?php esc_attr_e("Existing Templates", 'duplicator-pro'); ?>">
                            <?php
                            foreach ($existing_templates as $id => $val) {
                                ?>
                                <option value="<?php echo $id; ?>"><?php echo esc_html($val); ?></option>
                                <?php
                            }
                            ?>
                        </optgroup>
                        <?php
                    }
                    ?>
                </select>
                <input type="button" class="button action" value="<?php esc_attr_e("Apply", 'duplicator-pro') ?>" onclick="DupPro.Template.Copy()">
            <?php else : ?>
                <select disabled="disabled"><option value="-1" selected="selected"><?php _e('Copy From', 'duplicator-pro'); ?></option></select>
                <input type="button" class="button action" value="<?php esc_attr_e("Apply", 'duplicator-pro') ?>" onclick="DupPro.Template.Copy()"  disabled="disabled">
            <?php endif; ?>
        </td>
        <td>
            <div class="btnnav">
                <a href="<?php echo esc_url($templates_tab_url); ?>" class="button dup-goto-templates-btn">
                    <i class="far fa-clone"></i> <?php esc_html_e('Templates', 'duplicator-pro'); ?>
                </a>
                <?php if ($package_template_id != -1) : ?>
                    <a href="<?php echo esc_url($edit_template_url); ?>" class="button">
                        <?php esc_html_e("Add New", 'duplicator-pro'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </td>
    </tr>
</table>
<hr class="dpro-edit-toolbar-divider"/>

<div class="dpro-template-general">

    <div class="margin-b-10px">
        <label><?php _e("Recovery Status", 'duplicator-pro'); ?>:</label> &nbsp;
        <?php $package_template->recoveableHtmlInfo(); ?> <br/><br/>
    </div>

    <label><?php _e("Template", 'duplicator-pro'); ?>:</label>
    <input type="text" id="template-name" name="name" data-parsley-errors-container="#template_name_error_container"
           data-parsley-required="true" value="<?php echo esc_attr($package_template->name); ?>" autocomplete="off" maxlength="125">
    <div id="template_name_error_container" class="duplicator-error-container"></div>

    <label><?php _e("Notes", 'duplicator-pro'); ?>:</label> <br/>
    <textarea id="template-notes" name="notes" style="height:50px"><?php echo esc_textarea($package_template->notes); ?></textarea>
</div>



<!-- ===============================
ARCHIVE -->
<div class="dup-box dup-archive-filters-wrapper">
<div class="dup-box-title">
    <i class="far fa-file-archive fa-sm"></i> <?php esc_html_e('Archive', 'duplicator-pro') ?>
            <sup class="dup-box-title-badge">
            <?php echo esc_html($archive_format); ?>
        </sup> &nbsp; &nbsp;
    <button class="dup-box-arrow">
        <span class="screen-reader-text"><?php esc_html_e('Toggle panel:', 'duplicator-pro') ?> <?php esc_html_e('Archive', 'duplicator-pro') ?></span>
    </button>
</div>
<div class="dup-box-panel" id="dup-template-archive-panel" style="<?php echo esc_attr($ui_css_archive); ?>">

<!-- ===================
NESTED TABS -->
<div data-dpro-tabs="true">
    <ul>
        <li class="filter-files-tab"><?php esc_html_e('Files', 'duplicator-pro') ?></li>
        <li class="filter-db-tab"><?php esc_html_e('Database', 'duplicator-pro') ?></li>
        <?php if ($displayMultisiteTab) { ?>
            <li class="filter-mu-tab"><?php esc_html_e('Multisite', 'duplicator-pro') ?></li>
        <?php } ?>
        <li class="archive-setup-tab"><?php esc_html_e('Setup', 'duplicator-pro') ?></li>
    </ul>

    <!-- ===================
    TAB1: FILES -->
    <div class="filter-files-tab-content" >
        <?php $tplMng->render(
            'parts/filters/package_components',
            array(
                'archiveFilterOn'         => $package_template->archive_filter_on,
                'archiveFilterDirs'       => $package_template->archive_filter_dirs,
                'archiveFilterFiles'      => $package_template->archive_filter_files,
                'archiveFilterExtensions' => $package_template->archive_filter_exts,
                'components'              => $package_template->components,
            )
        ); ?>
    </div>

    <!-- ===================
    TAB2: DATABASE -->
    <div>
        <div class="dup-template-db-area">
            <?php
            $tableList = explode(',', $package_template->database_filter_tables);
            $tplMng->render(
                'parts/filters/tables_list_filter',
                array(
                    'dbFilterOn'        => $package_template->database_filter_on,
                    'dbPrefixFilter'    => $package_template->databasePrefixFilter,
                    'dbPrefixSubFilter' => $package_template->databasePrefixSubFilter,
                    'tablesSlected'     => $tableList,
                )
            );
            ?><br/>

            <div class="dup-form-item">
                <span class="title">
                    <?php esc_html_e("Compatibility Mode", 'duplicator-pro') ?>
                    <i class="fas fa-question-circle fa-sm"
                        data-tooltip-title="<?php esc_attr_e("Legacy Support", 'duplicator-pro'); ?>"
                        data-tooltip="<?php
                            esc_attr_e(
                                'This option is not available as a template setting. 
                                It can only be used when creating a new package.  Please see the FAQ for a full overview of using this feature.',
                                'duplicator-pro'
                            ); ?>"
                    >
                    </i>
                </span>
            </div>

            <i><?php
                $url = "<a href='" . DUPLICATOR_PRO_DUPLICATOR_DOCS_URL . "how-to-fix-database-write-issues' target='_blank'>"
                    . esc_html__('FAQ details', 'duplicator-pro') . "</a>";
                printf(esc_html__("Not enabled for template settings. Please see the full %s", 'duplicator-pro'), $url);
            ?>
            </i>
       </div>
    </div>

    <!-- ===================
    MULTI-SITE TAB 3:  -->
    <?php if ($displayMultisiteTab) : ?>
        <div>
            <div class="dup-template-mu-area">
               <?php esc_html_e("Support for multisite filters is only available when creating a new package.", 'duplicator-pro'); ?> <br/>
               <?php esc_html_e("To create a new package goto the Packages screen and click the 'Create New' button.", 'duplicator-pro'); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ===================
    SETUP TAB 4:  -->
    <?php
        $tplMng->render(
            'admin_pages/packages/setup/archive-setup-tab',
            [
                'secureOn'   => $package_template->installer_opts_secure_on,
                'securePass' => $package_template->installerPassowrd,
            ]
        );
        ?>
</div> 
<!-- end tab control -->

</div>
</div>
<br />


<!-- ===============================
INSTALLER -->
<div class="dup-box">
    <div class="dup-box-title">
        <i class="fa fa-bolt fa-sm"></i> <?php esc_html_e('Installer', 'duplicator-pro') ?>
          <button class="dup-box-arrow">
            <span class="screen-reader-text"><?php esc_html_e('Toggle panel:', 'duplicator-pro') ?> <?php esc_html_e('Installer', 'duplicator-pro') ?></span>
        </button>
    </div>
    <div class="dup-box-panel" id="dup-template-install-panel" style="<?php echo esc_attr($ui_css_install); ?>">

        <div class="dpro-panel-optional-txt">
            <b><?php esc_html_e('All values in this section are', 'duplicator-pro'); ?> <u><?php esc_html_e('optional', 'duplicator-pro'); ?></u></b>
            <i class="fas fa-question-circle fa-sm"
               data-tooltip-title="<?php esc_attr_e("Setup/Prefills", 'duplicator-pro'); ?>"
               data-tooltip="<?php
                esc_attr_e(
                    'All values in this section are OPTIONAL! If you know ahead of time the database input fields the installer will use, 
                    then you can optionally enter them here and they will be prefilled at install time. 
                    Otherwise you can just enter them in at install time and ignore all these options in the Installer section.',
                    'duplicator-pro'
                );
                ?>"></i>

        </div>

        <table class="dpro-install-setup"  style="margin-top:-10px">
            <tr>
                <td colspan="2"><div class="dup-package-hdr-1"><?php esc_html_e("Setup", 'duplicator-pro') ?></div></td>
            </tr>
            <tr>
                <td style="width:130px"><b><?php esc_html_e("Branding", 'duplicator-pro') ?>:</b></td>
                <td>
                    <?php
                    if (License::can(License::CAPABILITY_BRAND)) :
                        $brands = BrandEntity::getAllWithDefault();
                        ?>
                        <select name="installer_opts_brand" id="installer_opts_brand" onchange="DupPro.Template.BrandChange();">
                            <?php
                            $active_brand_id = $package_template->installer_opts_brand;
                            foreach ($brands as $i => $brand) :
                                ?>
                                <option value="<?php echo $brand->getId(); ?>" title="<?php echo esc_attr($brand->notes); ?>"<?php if (isset($_REQUEST['inner_page']) && $_REQUEST['inner_page'] == 'edit') {
                                    selected($brand->getId(), $active_brand_id);
                                               } ?>>
                                    <?php echo esc_html($brand->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
  
                        <a href="javascript:void(0)" target="_blank" class="button" id="brand-preview">
                            <?php esc_html_e("Preview", 'duplicator-pro'); ?>
                        </a> &nbsp;
                        <i class="fas fa-question-circle fa-sm"
                           data-tooltip-title="<?php esc_attr_e("Choose Brand", 'duplicator-pro'); ?>"
                           data-tooltip="<?php esc_attr_e('This option changes the branding of the installer file.  Click the preview button to see the selected style.', 'duplicator-pro'); ?>"></i>
                    <?php else : ?>
                        <a href="<?php echo esc_url($bandListUrl); ?>" class="upgrade-link" target="_blank">
                            <?php esc_html_e("Enable Branding", 'duplicator-pro'); ?>
                        </a>
                    <?php endif; ?>
                    <br/><br/>
                </td>
            </tr>
        </table>
        <br/>

        <table style="width:100%">
            <tr>
                <td colspan="2"><div class="dup-package-hdr-1"><?php esc_html_e("Prefills", 'duplicator-pro') ?></div></td>
            </tr>
        </table>

        <!-- ===================
        STEP1 TABS -->
        <div data-dpro-tabs="true">
            <ul>
                <li><?php esc_html_e('Basic', 'duplicator-pro') ?></li>
                <li id="dpro-cpnl-tab-lbl"><?php esc_html_e('cPanel', 'duplicator-pro') ?></li>
            </ul>

            <!-- ===================
            TAB1: Basic -->
            <div class="dup-template-basic-tab">
                <table class="form-table" role="presentation">
                    <tr>
                        <td colspan="2">
                            <b class="dpro-hdr"><?php esc_html_e('MySQL Server', 'duplicator-pro'); ?></b>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e("Host", 'duplicator-pro'); ?>:</th>
                        <td><input type="text" placeholder="localhost" name="installer_opts_db_host" value="<?php echo esc_attr($package_template->installer_opts_db_host); ?>"></td>
                    </tr>
                    <tr>
                        <th><label><?php _e("Database", 'duplicator-pro'); ?>:</label></th>
                        <td><input type="text" placeholder="<?php esc_attr_e('valid database name', 'duplicator-pro'); ?>" name="installer_opts_db_name" value="<?php echo esc_attr($package_template->installer_opts_db_name); ?>"></td>
                    </tr>
                    <tr>
                        <th><label><?php _e("User", 'duplicator-pro'); ?>:</label></th>
                        <td><input type="text" placeholder="<?php esc_attr_e('valid database user', 'duplicator-pro'); ?>" name="installer_opts_db_user" value="<?php echo esc_attr($package_template->installer_opts_db_user); ?>"></td>
                    </tr>
                </table>
                <br/><br/>
            </div>

            <!-- ===================
            TAB2: cPanel -->
            <div class="dup-template-cpanel-tab">
                <table class="form-table" role="presentation">
                    <tr>
                        <td colspan="2"><b class="dpro-hdr"><?php esc_html_e('cPanel Login', 'duplicator-pro'); ?></b></td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php esc_html_e("Automation", 'duplicator-pro'); ?>:</label></th>
                        <td>
                            <input type="checkbox" name="installer_opts_cpnl_enable" id="installer_opts_cpnl_enable" <?php checked($package_template->installer_opts_cpnl_enable); ?> >
                            <label for="installer_opts_cpnl_enable">Auto Select cPanel</label>
                            <i 
                                class="fas fa-question-circle fa-sm" 
                                data-tooltip-title="Auto Select cPanel:" 
                                data-tooltip="<?php esc_attr_e('Enabling this options will automatically select the cPanel tab when step one of the installer is shown.', 'duplicator-pro'); ?>" >
                            </i>
                            &nbsp; &nbsp;
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php esc_html_e("Host", 'duplicator-pro'); ?>:</label></th>
                        <td><input type="text" name="installer_opts_cpnl_host" value="<?php echo esc_attr($package_template->installer_opts_cpnl_host); ?>"  placeholder="<?php esc_attr_e('valid cpanel host address', 'duplicator-pro'); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php esc_html_e("User", 'duplicator-pro'); ?>:</label></th>
                        <td><input type="text" name="installer_opts_cpnl_user" value="<?php echo esc_attr($package_template->installer_opts_cpnl_user); ?>"  placeholder="<?php esc_attr_e('valid cpanel user login', 'duplicator-pro'); ?>"></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <b class="dpro-hdr"><?php esc_html_e('MySQL Server', 'duplicator-pro'); ?></b>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e("Action", 'duplicator-pro'); ?>:</label></th>
                        <td>
                            <select name="installer_opts_cpnl_db_action" id="cpnl-dbaction">
                                <option value="create" <?php echo ($installer_cpnldbaction == 'create') ? 'selected' : ''; ?>>Create A New Database</option>
                                <option value="empty"  <?php echo ($installer_cpnldbaction == 'empty') ? 'selected' : ''; ?>>Connect to Existing Database and Remove All Data</option>
                                <!--option value="rename">Connect to Existing Database and Rename Existing Tables</option-->
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e("Host", 'duplicator-pro'); ?>:</label></th>
                        <td><input type="text" name="installer_opts_cpnl_db_host" value="<?php echo esc_attr($package_template->installer_opts_cpnl_db_host); ?>" placeholder="<?php esc_attr_e('localhost', 'duplicator-pro'); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e("Database", 'duplicator-pro'); ?>:</label></th>
                        <td><input type="text" name="installer_opts_cpnl_db_name" value="<?php echo esc_attr($package_template->installer_opts_cpnl_db_name); ?>" placeholder="<?php esc_attr_e('valid database name', 'duplicator-pro'); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e("User", 'duplicator-pro'); ?>::</label></th>
                        <td><input type="text" name="installer_opts_cpnl_db_user" value="<?php echo esc_attr($package_template->installer_opts_cpnl_db_user); ?>" placeholder="<?php esc_attr_e('valid database user', 'duplicator-pro'); ?>" /></td>
                    </tr>
                </table>
            </div>
        </div><br/>
        <small><?php esc_html_e("All other inputs can be entered at install time.", 'duplicator-pro') ?></small>
        <br/><br/>

    </div>
</div>
<br/>

<button 
    class="button button-primary dup-save-template-btn" 
    type="submit"
>
    <?php esc_html_e('Save Template', 'duplicator-pro'); ?>
</button>
</form>




<?php
$alert1          = new DUP_PRO_UI_Dialog();
$alert1->title   = __('Transfer Error', 'duplicator-pro');
$alert1->message = __('You can\'t exclude all sites!', 'duplicator-pro');
$alert1->initAlert();
?>

<script>
    jQuery(document).ready(function ($) {

        /* When installer brand changes preview button is updated */
        DupPro.Template.BrandChange = function ()
        {
            var $brand = $("#installer_opts_brand");
            var $id = $brand.val();
            var $url = new Array();

            $url = [
                <?php echo SnapJson::jsonEncode($brandDefaultEditUrl); ?>,
                <?php echo SnapJson::jsonEncode($brandBaseEditUrl); ?> + '&id=' + $id
            ];

            $("#brand-preview").attr('href', $url[ $id > 0 ? 1 : 0 ]);
        };

        /* Enables strike through on excluded DB table */
        DupPro.Template.ExcludeTable = function (check)
        {
            var $cb = $(check);
            if ($cb.is(":checked")) {
                $cb.closest("label").css('textDecoration', 'line-through');
            } else {
                $cb.closest("label").css('textDecoration', 'none');
            }
        }

        /* Used to duplicate a template */
        DupPro.Template.Copy = function ()
        {
            $("#dpro-template-form-action").val('copy-template');
            $("#dpro-template-form").parsley().destroy();
            $("#dpro-template-form").submit();
        };

        //INIT
        $('#template-name').focus().select();
        // $('#_archive_filter_files').val($('#_archive_filter_files').val().trim());
        //Default to cPanel tab if used
        $('#cpnl-enable').is(":checked") ? $('#dpro-cpnl-tab-lbl').trigger("click") : null;
        DupPro.EnableInstallerPassword();
        DupPro.Template.BrandChange();

        //MU-Transfer buttons
        $('#mu-include-btn').click(function () {
            return !$('#mu-exclude option:selected').remove().appendTo('#mu-include');
        });

        $('#mu-exclude-btn').click(function () {
            var include_all_count = $('#mu-include option').length;
            var include_selected_count = $('#mu-include option:selected').length;

            if (include_all_count > include_selected_count) {
                return !$('#mu-include option:selected').remove().appendTo('#mu-exclude');
            } else {
                <?php $alert1->showAlert(); ?>
            }
        });

        $('#dpro-template-form').submit(function () {
            DupPro.Pack.FillExcludeTablesList();
        });

        //Defaults to Installer cPanel tab if 'Auto Select cPanel' is checked
        $('#installer_opts_cpnl_enable').is(":checked") ? $('#dpro-cpnl-tab-lbl').trigger("click") : null;

    });
</script>

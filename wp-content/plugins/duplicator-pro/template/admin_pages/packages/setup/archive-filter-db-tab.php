<?php

/**
 * @package Duplicator
 */

use Duplicator\Controllers\SettingsPageController;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 * @var array<string, mixed> $tplData
 */

$dbbuild_mode       = DUP_PRO_DB::getBuildMode();
$settingsPackageUrl = SettingsPageController::getInstance()->getMenuLink(SettingsPageController::L2_SLUG_PACKAGE);
?>
<div class="filter-db-tab-content">
    <?php $tplMng->render('parts/filters/tables_list_filter'); ?>
    <br/><br/>

    <div class="dup-package-hdr-1">
        <?php esc_html_e("Configuration", 'duplicator-pro') ?>
    </div>

    <div class="dup-form-item">
        <span class="title"><?php esc_html_e("SQL Mode", 'duplicator-pro') ?>:</span>
        <span class="input">
            <a href="<?php echo esc_url($settingsPackageUrl); ?>" target="settings">
                <?php echo esc_html($dbbuild_mode); ?>
            </a>
        </span>
    </div>
    <div class="dup-form-item">
        <span class="title">
            <?php esc_html_e("Compatibility Mode", 'duplicator-pro') ?>:
            <?php
            $tipCont = __(
                'This is an advanced database backwards compatibility feature that should ONLY be used if having problems installing packages. 
                If the database server version is lower than the version where the package was built then these options may help generate 
                a script that is more compliant with the older database server. It is recommended to try each option separately starting with mysql40.',
                'duplicator-pro'
            );
            ?>
            <i class="fas fa-question-circle fa-sm"
                data-tooltip-title="<?php esc_attr_e("Compatibility Mode", 'duplicator-pro'); ?>"
                data-tooltip="<?php echo esc_attr($tipCont); ?>">
            </i>                    
        </span>
    </div>

    <?php
    if ($dbbuild_mode == 'MYSQLDUMP') :?>
        <?php
            $modes       = isset($Package) ? explode(',', $Package->Database->Compatible) : array();
            $is_mysql40  = in_array('mysql40', $modes);
            $is_no_table = in_array('no_table_options', $modes);
            $is_no_key   = in_array('no_key_options', $modes);
            $is_no_field = in_array('no_field_options', $modes);
        ?>
        <div class="dup-form-horiz-opts">
            <span>
                <input type="checkbox" name="dbcompat[]" id="dbcompat-mysql40" value="mysql40" <?php echo $is_mysql40 ? 'checked="true"' : ''; ?> >
                <label for="dbcompat-mysql40"><?php esc_html_e("mysql40", 'duplicator-pro') ?></label> 
            </span>
            <span>
                <input 
                    type="checkbox" 
                    name="dbcompat[]" 
                    id="dbcompat-no_table_options" 
                    value="no_table_options" 
                    <?php echo $is_no_table ? 'checked="true"' : ''; ?>
                >
                <label for="dbcompat-no_table_options"><?php esc_html_e("no_table_options", 'duplicator-pro') ?></label>
            </span>
            <span>
                <input type="checkbox" name="dbcompat[]" id="dbcompat-no_key_options" value="no_key_options" <?php echo $is_no_key ? 'checked="true"' : ''; ?>>
                <label for="dbcompat-no_key_options"><?php esc_html_e("no_key_options", 'duplicator-pro') ?></label>
            </span>
            <span>
                <input 
                    type="checkbox" 
                    name="dbcompat[]" 
                    id="dbcompat-no_field_options" 
                    value="no_field_options" 
                    <?php echo $is_no_field ? 'checked="true"' : ''; ?>
                >
                <label for="dbcompat-no_field_options"><?php esc_html_e("no_field_options", 'duplicator-pro') ?></label>
            </span>
        </div>
        <div class="dup-tabs-opts-help">
            <?php esc_html_e("Compatibility mode settings are not persistent.  They must be enabled with every new build.", 'duplicator-pro'); ?>&nbsp;
            <a href="<?php echo esc_url(DUPLICATOR_PRO_DUPLICATOR_DOCS_URL . 'how-to-fix-database-write-issues');?>" target="_blank">
                [<?php esc_html_e('full overview', 'duplicator-pro'); ?>]
            </a>
        </div>
    <?php else :?>
        &nbsp; &nbsp; <i><?php esc_html_e("This option is only available with mysqldump mode.", 'duplicator-pro'); ?></i>
    <?php endif; ?>
    <br/>
</div>

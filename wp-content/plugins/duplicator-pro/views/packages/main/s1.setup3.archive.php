<?php

/**
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

defined('ABSPATH') || defined('DUPXABSPATH') || exit;

use Duplicator\Core\Views\TplMng;

$tplMng = TplMng::getInstance();

$global = DUP_PRO_Global_Entity::getInstance();

$ui_css_archive = (DUP_PRO_UI_ViewState::getValue('dup-pack-archive-panel') ? 'display:block' : 'display:none');
$multisite_css  = is_multisite() ? '' : 'display:none';

$archive_format = ($global->getBuildMode() == DUP_PRO_Archive_Build_Mode::DupArchive ? 'daf' : 'zip');
?>

<!-- ===================
 META-BOX: ARCHIVE -->
<div class="dup-box dup-archive-filters-wrapper">
    <div class="dup-box-title" >
        <i class="far fa-file-archive fa-sm"></i> <?php esc_html_e('Archive', 'duplicator-pro') ?> 
        <sup class="dup-box-title-badge">
            <?php echo esc_html($archive_format); ?>
        </sup> &nbsp; &nbsp;
        <span class="dup-archive-filters-icons">
            <span id="dup-archive-filter-file" title="<?php esc_attr_e('Folder/File Filter Enabled', 'duplicator-pro') ?>">
                <span class="btn-separator"></span>
                <i class="fas fa-folder-open fa-fw"></i>
                <sup><i class="fas fa-filter fa-xs"></i></sup>
            </span>
            <span id="dup-archive-filter-db" title="<?php esc_attr_e('Database Table Filter Enabled', 'duplicator-pro') ?>">
                <span class="btn-separator"></span>
                <i class="fas fa-table fa-fw"></i>
                <sup><i class="fas fa-filter fa-xs"></i></sup>
            </span>
            <span id="dup-archive-db-only" title="<?php esc_attr_e('Archive Only the Database', 'duplicator-pro') ?>">
                <span class="btn-separator"></span>
                <i class="fas fa-database fa-fw"></i>
                <?php esc_html_e('Database Only', 'duplicator-pro') ?>
            </span>
            <span id="dup-archive-media-only" title="<?php esc_attr_e('Archive Only Media files', 'duplicator-pro') ?>">
                <span class="btn-separator"></span>
                <i class="fas fa-file-image fa-fw"></i>
                <?php esc_html_e('Media Only', 'duplicator-pro') ?>
            </span>
            <span id="dpro-install-secure-lock" title="<?php esc_attr_e('Archive password protection is on', 'duplicator-pro') ?>">
                <span class="btn-separator"></span>
                <i class="fas fa-lock fa-fw"></i>
                <?php esc_html_e('Requires Password', 'duplicator-pro') ?>
            </span>
        </span>
        <button class="dup-box-arrow">
            <span class="screen-reader-text"><?php esc_html_e('Toggle panel:', 'duplicator-pro') ?> <?php esc_html_e('Archive Settings', 'duplicator-pro') ?></span>
        </button>
    </div>
    
    <div class="dup-box-panel" id="dup-pack-archive-panel" style="<?php echo esc_attr($ui_css_archive); ?>">
        <input type="hidden" name="archive-format" value="ZIP" />

        <!-- ===================
        NESTED TABS -->
        <div data-dpro-tabs="true">
            <ul>
                <li class="filter-files-tab"><?php esc_html_e('Files', 'duplicator-pro') ?></li>
                <li class="filter-db-tab"><?php esc_html_e('Database', 'duplicator-pro') ?></li>
                <?php if (is_multisite()) { ?>
                <li class="filter-mu-tab" style="<?php echo $multisite_css ?>"><?php esc_html_e('Multisite', 'duplicator-pro') ?></li>
                <?php } ?>
                <li class="archive-setup-tab"><?php esc_html_e('Security', 'duplicator-pro') ?></li>
            </ul>

            <?php
                $tplMng->render('admin_pages/packages/setup/archive-filter-files-tab');
                $tplMng->render('admin_pages/packages/setup/archive-filter-db-tab');
            if (is_multisite()) {
                $tplMng->render('admin_pages/packages/setup/archive-filter-mu-tab');
            }
                $tplMng->render('admin_pages/packages/setup/archive-setup-tab');
            ?>
        </div>
    </div>
</div>

<div class="duplicator-error-container"></div>
<?php
    $alert1          = new DUP_PRO_UI_Dialog();
    $alert1->title   = __('ERROR!', 'duplicator-pro');
    $alert1->message = __('You can\'t exclude all sites.', 'duplicator-pro');
    $alert1->initAlert();
?>
<script>
//INIT
jQuery(document).ready(function($) 
{
    //MU-Transfer buttons
    $('#mu-include-btn').click(function() {
        return !$('#mu-exclude option:selected').remove().appendTo('#mu-include');  
    });

    $('#mu-exclude-btn').click(function() {
        var include_all_count = $('#mu-include option').length;
        var include_selected_count = $('#mu-include option:selected').length;

        if(include_all_count > include_selected_count) {
            return !$('#mu-include option:selected').remove().appendTo('#mu-exclude');
        } else {
            <?php $alert1->showAlert(); ?>
        }
    });

});
</script>

<?php
defined("ABSPATH") or die("");

use Duplicator\Addons\ProBase\License\License;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Utils\Settings\MigrateSettings;

/* FOR PERSONAL LICENSE JUST SHOW MESSAGE */
if (!License::can(License::CAPABILITY_IMPORT_SETTINGS)) : ?>
    <div class="width-large" >
        <p>
            <?php _e(
                "The migrate settings screen allows you to import or export Duplicator Pro settings from one site to another.",
                'duplicator-pro'
            ); ?>
        </p>
        <p>
            <?php _e(
                "For example, if you have several storage locations that you use on multiple WordPress sites such as Google Drive or 
                Dropbox and you simply want to copy the profiles from this instance of Duplicator Pro to another instance then simply 
                export the data here and import it on the other instance of Duplicator Pro.",
                'duplicator-pro'
            ); ?>
        </p>
            <p>
            <?php
                printf(
                    __(
                        'This option isn\'t available at the <b>%1$s</b> license level.',
                        'duplicator-pro'
                    ),
                    License::getLicenseToString()
                );
            ?>
            <b>
            <?php
                printf(
                    _x(
                        'To enable this option %1$supgrade%2$s the License.',
                        '%1$s and %2$s represents the opening and closing HTML tags for an anchor or link',
                        'duplicator-pro'
                    ),
                    '<a href="' . esc_url(License::getUpsellURL()) . '" target="_blank">',
                    '</a>'
                );
            ?>
            </b>
        </p>
    </div>

<?php else :
    /* LET'S PERFORM FREELANCE+ SETTINGS */

    $nonce = wp_create_nonce('duplicator_pro_import_export_settings');

    $view_state          = DUP_PRO_UI_ViewState::getArray();
    $ui_css_export_panel = (isset($view_state['dpro-tools-export-panel']) && $view_state['dpro-tools-export-panel']) ? 'display:block' : 'display:block';
    $ui_css_import_panel = (isset($view_state['dpro-tools-import-panel']) && $view_state['dpro-tools-import-panel']) ? 'display:block' : 'display:block';

    // POST BACK
    $_REQUEST['action'] = !empty($_REQUEST['action']) ? $_REQUEST['action'] : 'display';

    $error_message   = null;
    $success_message = null;

    switch ($_REQUEST['action']) {
        case 'dpro-export':
        case 'dpro-import':
            try {
                if (MigrateSettings::import($_FILES['import-file']['tmp_name'], $_POST['import-opts']) == false) {
                    throw new Exception('Import failed.');
                }
                $success_message = 'Successfully imported.';
            } catch (Exception $ex) {
                $error_message = 'Import Error: ' . $ex->getMessage() . "<br>\n" . $ex->getFile() . ':'  . $ex->getLine();
            }
            break;
    }
    ?>

<style>
    <?php echo isset($css_hide_msg) ? $css_hide_msg : ''; ?>
    div.dup-box {margin-top:20px}
    div#message {margin:0px 0px 10px 0px}
    div.success {color:#4A8254}
    div.failed {color:#BB1506}
    table.dpro-check-tbl td {padding:5px 30px 10px 10px}
    div#message {margin-top:10px !important}
    div#TB_ajaxContent p {font-size:14px !important}
</style>

    <?php
    if ($error_message !== null) {
        echo "<div id='message' class='below-h2 error'><p>{$error_message}</p></div>";
    } elseif ($success_message !== null) {
        echo "<div id='message' class='below-h2 updated'><p>{$success_message}</p></div>";
    }
    ?>
<br/>

    <?php
    esc_html_e(
        "The migrate settings screen allows you to import or export Duplicator Pro settings from one site to another. 
        For example if you have several storage locations 
        that you use on multiple WordPress sites such as Google Drive or Dropbox and you simply want to copy 
        the profiles from this instance of Duplicator Pro to another instance 
        then simply export the data here and import it on the other instance of Duplicator Pro.",
        'duplicator-pro'
    ); ?>
    <br>

<!-- ==============================
EXPORT -->
<!-- action is unnecessary, uses ajax -->
<form id="dup-tools-form-export" method="post">
    <?php wp_nonce_field('dpro_tools_data_export'); ?>  
    <input type="hidden"  name="action" value="dpro-export">
    <div class="dup-box">
        <div class="dup-box-title">
            <i class="fa fa-upload"></i>
            <?php esc_html_e("Export Settings", 'duplicator-pro') ?>
            <button class="dup-box-arrow">
                <span class="screen-reader-text"><?php esc_html_e('Toggle panel:', 'duplicator-pro') ?> <?php esc_html_e('Export Settings', 'duplicator-pro') ?></span>
            </button>
        </div>
        <div class="dup-box-panel" id="dpro-tools-export-panel" style="<?php echo esc_attr($ui_css_export_panel); ?>">
            <?php
            esc_html_e(
                "Exports all schedules, storage locations, templates and settings from this Duplicator Pro instance into a downloadable export file.",
                'duplicator-pro'
            );
            ?>
            <br/>
            <?php
            esc_html_e(
                "The export file can then be used to import data settings from this instance of Duplicator Pro into another plugin instance of Duplicator Pro.",
                'duplicator-pro'
            );
            ?>      
            <br/><br/><br/>

            <input type="button" class="button button-primary" value="<?php esc_attr_e("Export Data", 'duplicator-pro'); ?>" onclick="return DupPro.Tools.ExportDialog();" />
            <br/><br/>
        </div> 
    </div> 
</form>
    
<!-- ==============================
IMPORT -->
<form enctype="multipart/form-data" id="dup-tools-form-import" action="<?php echo ControllersManager::getCurrentLink(); ?>" method="post" data-parsley-validate data-parsley-ui-enabled="true" >
    <?php wp_nonce_field('dpro_tools_data_import'); ?>
<input type="hidden"  name="action" value="dpro-import">
<div class="dup-box">
    <div class="dup-box-title">
        <i class="fa fa-download"></i>
        <?php esc_html_e("Import Settings", 'duplicator-pro'); ?>
        <button class="dup-box-arrow">
            <span class="screen-reader-text"><?php esc_html_e('Toggle panel:', 'duplicator-pro') ?> <?php esc_html_e('Import Settings', 'duplicator-pro') ?></span>
        </button>
    </div>
    <div class="dup-box-panel" id="dpro-tools-import-panel" style="<?php echo esc_attr($ui_css_import_panel); ?>" >
        <?php _e('Import settings from another Duplicator Pro plugin into this instance of Duplicator Pro.', 'duplicator-pro'); ?>
        <br>
        <?php _e('Schedule, storage and template data will be appended to current data, while existing settings will be replaced.', 'duplicator-pro'); ?>
        <br>
        <b>
            <?php _e('For security reasons, capabilities, license data and license visibility will not be imported.', 'duplicator-pro'); ?>
        </b>
        <br>
        <i>
            <?php
            _e(
                "Schedules depend on storage and templates so importing schedules will require that storage and templates be checked.",
                'duplicator-pro'
            ); ?>
        </i>
        <br/>
        <br/>
        <br/>

        <label for="import-file"><b><?php esc_html_e("Choose Duplicator Data File", 'duplicator-pro'); ?></b> </label><br/>
        <input type="file" accept=".dup" name="import-file" id="import-file" required="true" />
        <br/><br/>

        <b><?php esc_html_e("Include in Import", 'duplicator-pro'); ?>:</b>
        <table class="dpro-check-tbl">
            <tr>
                <td>
                    <input onclick="DupPro.Tools.ChangeImportButtonState();DupPro.Tools.SchedulesClicked();" type="checkbox" name="import-opts[]" id="import-schedules" value="schedules" />
                    <label for="import-schedules"><?php esc_html_e("Schedules", 'duplicator-pro'); ?></label>
                </td>
                <td>
                    <input onclick="DupPro.Tools.ChangeImportButtonState();" type="checkbox" name="import-opts[]" id="import-storages" value="storages" />
                    <label for="import-storages"><?php esc_html_e("Storage", 'duplicator-pro'); ?></label>
                </td>
                <td>
                    <input onclick="DupPro.Tools.ChangeImportButtonState();" type="checkbox" name="import-opts[]" id="import-templates" value="templates" />
                    <label for="import-templates"><?php esc_html_e("Templates", 'duplicator-pro'); ?></label>
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <input onclick="DupPro.Tools.ChangeImportButtonState();" type="checkbox" name="import-opts[]" id="import-settings" value="settings" />
                    <label for="import-settings"><?php esc_html_e("Settings", 'duplicator-pro'); ?></label>
                </td>
            </tr>
        </table>
        <br/>

        <input id="import-button" type="button" class="button button-primary" value="<?php esc_attr_e("Import Data", 'duplicator-pro'); ?>" onclick="return DupPro.Tools.ImportDialog();" disabled/>
        <br/><br/>
    </div>
</div>
</form>
<br/><br/>

    <?php add_thickbox(); ?>

<!-- EXPORT DIALOG -->
<div id="modal-window-export" style="display:none;">
    <h2><?php esc_html_e("Export Duplicator Pro Data?", 'duplicator-pro') ?></h2>
    <p>
        <?php esc_html_e("This process will:", 'duplicator-pro') ?><br/><br/>
        <i class="far fa-check-circle"></i> <?php esc_html_e("Export schedules, storage and templates to a file for import into another Duplicator instance.", 'duplicator-pro'); ?> <br/>
        <span style="color:#BB1506"><i class="fas fa-exclamation-triangle fa-sm"></i></i> <?php esc_html_e("For security purposes, restrict access to this file and delete after use.", 'duplicator-pro'); ?></span> <br/>
        <br/>
        <?php esc_html_e("Click the 'Run Export' button to generate and download the export file.", 'duplicator-pro') ?><br/><br/>
    </p>
    <div style="position:absolute; right:10px; bottom: 10px">
        <input type="button" class="button" value="<?php esc_attr_e("Run Export", 'duplicator-pro') ?>" onclick="DupPro.Tools.ExportProcess();setTimeout(function() { tb_remove(); }, 4000);" />
        <input type="button" class="button" value="<?php esc_attr_e("Cancel", 'duplicator-pro') ?>" onclick="tb_remove();" />
    </div>
</div>

<!-- IMPORT DIALOG -->
<div id="modal-window-import" style="display:none;">
    <h2><?php esc_html_e("Import Duplicator Pro Data?", 'duplicator-pro') ?></h2>
    <p>
        <?php esc_html_e("This process will:", 'duplicator-pro') ?><br/><br/>
        <i class="far fa-check-circle"></i> <?php esc_html_e("Append schedules, storage and templates if those options are checked.", 'duplicator-pro'); ?> <br/>      
        <i class="far fa-check-circle"></i> <?php esc_html_e("Overwrite current settings data if the settings option is checked.", 'duplicator-pro'); ?> <br/>
        <span style="color:#BB1506"><i class="fas fa-exclamation-triangle fa-sm"></i> <?php esc_html_e("Review templates and local storages after import to ensure correct path values.", 'duplicator-pro'); ?> <br/></span>
        <br/>
        <?php esc_html_e("Click the 'Run Import' button to process the import file.", 'duplicator-pro') ?><br/><br/>
    </p>
    <div style="position:absolute; right:10px; bottom: 10px">
        <input type="button" class="button" value="<?php esc_attr_e("Run Import", 'duplicator-pro') ?>" onclick="DupPro.Tools.ImportProcess();" />
        <input type="button" class="button" value="<?php esc_attr_e("Cancel", 'duplicator-pro') ?>" onclick="tb_remove();" />
    </div>
</div>


<script>
DupPro.Tools.ExportProcess = function () 
{
    var actionLocation = ajaxurl + '?action=duplicator_pro_export_settings' + '&nonce=' + '<?php echo $nonce; ?>';
    location.href = actionLocation;
}

DupPro.Tools.ExportDialog = function () 
{
    var url = "#TB_inline?width=610&height=250&inlineId=modal-window-export";
    tb_show("<?php esc_html_e("Export Data", 'duplicator-pro') ?>", url);
    return false;
}   

DupPro.Tools.ImportProcess = function () 
{
    jQuery('#dup-tools-form-import').submit();
}

DupPro.Tools.ImportDialog = function () 
{
    var url = "#TB_inline?width=610&height=300&inlineId=modal-window-import";
    tb_show("<?php esc_html_e("Import Data", 'duplicator-pro') ?>", url);
    return false;
}   

//PAGE INIT
jQuery(document).ready(function ($) 
{
    DupPro.Tools.ChangeImportButtonState = function()
    {
        var filename = $('#import-file').val();
        var disabled = (filename == '');

        disabled = disabled || 
            (
                !document.getElementById('import-templates').checked && 
                !document.getElementById('import-storages').checked && 
                !document.getElementById('import-schedules').checked && 
                !document.getElementById('import-settings').checked
            );

        $('#import-button').prop('disabled', disabled);
    }

    DupPro.Tools.SchedulesClicked = function()
    {
        if(document.getElementById('import-schedules').checked)
        {
            document.getElementById('import-templates').checked = true;
            document.getElementById('import-storages').checked = true;
            document.getElementById('import-templates').disabled = true;
            document.getElementById('import-storages').disabled = true;
        }
        else {
            document.getElementById('import-templates').disabled = false;
            document.getElementById('import-storages').disabled = false;
        }
    }

    $("#dpro-tools-import-panel").on("change", "#import-file", function() { DupPro.Tools.ChangeImportButtonState(); });
});
</script>
<?php endif; ?>

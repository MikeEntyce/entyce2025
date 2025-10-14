<?php

use Duplicator\Core\CapMng;

defined("ABSPATH") or die("");

if (!CapMng::can(CapMng::CAP_CREATE, false)) {
    return;
}
?>

<style>
    div#hb-result {padding: 10px 5px 0 5px; line-height:20px; font-size: 12px}
</style>

<!-- ==========================================
THICK-BOX DIALOGS: -->
<?php
    $confirm1             = new DUP_PRO_UI_Dialog();
    $confirm1->title      = __('Run Validator', 'duplicator-pro');
    $confirm1->message    = __('This will run the scan validation check.  This may take several minutes.  Do you want to Continue?', 'duplicator-pro');
    $confirm1->progressOn = false;
    $confirm1->jsCallback = 'DupPro.Tools.runScanValidator()';
    $confirm1->initConfirm();
?>

<!-- ==============================
SCAN VALIDATOR -->
<div class="dup-box">
    <div class="dup-box-title">
        <i class="far fa-check-square"></i>
        <?php esc_html_e("Scan Validator", 'duplicator-pro'); ?>
        <button class="dup-box-arrow">
            <span class="screen-reader-text">
                <?php esc_html_e('Toggle panel:', 'duplicator-pro') ?> <?php esc_html_e('Scan Validator', 'duplicator-pro') ?>
            </span>
        </button>
    </div>
    <div class="dup-box-panel" style="display: none;">
        <?php
            esc_html_e(
                "This utility will help to find unreadable files and sys-links in your environment that can lead to issues during the scan process.",
                'duplicator-pro'
            );
            echo ' ';
            esc_html_e(
                "The utility will also shows how many files and directories you have in your system. This process may take several minutes to run.",
                'duplicator-pro'
            );
            echo ' ';
            esc_html_e(
                "If there is a recursive loop on your system then the process has a built in check to stop after a large set 
                of files and directories have been scanned.",
                'duplicator-pro'
            );
            echo ' ';
            esc_html_e(
                "A message will show indicated that that a scan depth has been reached. 
                If you have issues with the package scanner (step 2) during the build process then try to add 
                The paths below to your file filters to allow the scanner to finish.",
                'duplicator-pro'
            );
            ?>
        <br/><br/>


        <button id="scan-run-btn" type="button" class="button button-large button-primary" onclick="DupPro.Tools.ConfirmScanValidator()">
            <?php esc_html_e("Run Scan Integrity Validation", 'duplicator-pro'); ?>
        </button>

        <script id="hb-template" type="text/x-handlebars-template">
            <b><?php esc_html_e('Scan Paths:', 'duplicator-pro'); ?></b><br/>
            {{#if scanData.scanPaths}}
                {{#each scanData.scanPaths}}
                    &nbsp; &nbsp; {{@index}} : {{this}}<br/>
                {{/each}}
            {{else}}
                <i><?php esc_html_e('Empty scan path', 'duplicator-pro'); ?></i> <br/>
            {{/if}}
            <br/>
            <b><?php esc_html_e('Scan Results', 'duplicator-pro'); ?></b><br/>
            <table>
                <tr>
                    <td><b><?php esc_html_e('Files:', 'duplicator-pro'); ?></b></td>
                    <td>{{scanData.fileCount}} </td>
                    <td> &nbsp; </td>
                    <td><b><?php esc_html_e('Dirs:', 'duplicator-pro'); ?></b></td>
                    <td>{{scanData.dirCount}} </td>
                </tr>
            </table>
            <br/>

            <b>Unreadable Dirs/Files:</b> <br/>
            {{#if scanData.unreadable}}
                {{#each scanData.unreadable}}
                    &nbsp; &nbsp; {{@index}} : {{this}}<br/>
                {{/each}}
            {{else}}
                <i><?php esc_html_e('No Unreadable items found', 'duplicator-pro'); ?></i> <br/>
            {{/if}}
            <br/>

            <b><?php esc_html_e('Symbolic Links:', 'duplicator-pro'); ?></b> <br/>
            {{#if scanData.symLinks}}
                {{#each scanData.symLinks}}
                    &nbsp; &nbsp; {{@index}} : {{this}}<br/>
                {{/each}}
            {{else}}
                <i>No Sym-links found</i> <br/>
                <small> <?php esc_html_e("Note: Symlinks are not discoverable on Windows OS with PHP", 'duplicator-pro'); ?></small> <br/>
            {{/if}}
            <br/>

            <b>Directory Name Checks:</b> <br/>
            {{#if scanData.nameTestDirs}}
                {{#each scanData.nameTestDirs}}
                    &nbsp; &nbsp; {{@index}} : {{this}}<br/>
                {{/each}}
            {{else}}
                <i><?php esc_html_e('No name check warnings located for directory paths', 'duplicator-pro'); ?></i> <br/>
            {{/if}}
            <br/>

            <b>File Name Checks:</b> <br/>
            {{#if scanData.nameTestFiles}}
                {{#each scanData.nameTestFiles}}
                    &nbsp; &nbsp; {{@index}} : {{this}}<br/>
                {{/each}}
            {{else}}
                <i><?php esc_html_e('No name check warnings located for directory paths', 'duplicator-pro'); ?></i> <br/>
            {{/if}}

            <br/>
        </script>
        <div id="hb-result"></div>

    </div>
</div>
<br/>

<script>
jQuery(document).ready(function($)
{
    DupPro.Tools.ConfirmScanValidator = function()
    {
        <?php $confirm1->showConfirm(); ?>
    }


    //Run request to: admin-ajax.php?action=DUP_CTRL_Tools_runScanValidator
    DupPro.Tools.runScanValidator = function()
    {
        tb_remove();
        var data = {
            action : 'DUP_PRO_CTRL_Tools_runScanValidator',
            nonce: '<?php echo wp_create_nonce('DUP_PRO_CTRL_Tools_runScanValidator'); ?>',
            'scan-recursive': 1
        };

        $('#hb-result').html('<?php esc_html_e("Scanning Environment... This may take a few minutes.", 'duplicator-pro'); ?>');
        $('#scan-run-btn').html('<i class="fas fa-circle-notch fa-spin fa-fw"></i> Running Please Wait...');

        $.ajax({
            type: "POST",
            url: ajaxurl,
            dataType: "text",
            data: data,
            success: function(respData) {
                try {
                    var data = DupPro.parseJSON(respData);
                } catch(err) {
                    console.error(err);
                    console.error('JSON parse failed for response data: ' + respData);
                    console.log(respData);
                    return false;
                }
                DupPro.Tools.IntScanValidator(data);
            },
            error: function(data) {console.log(data)},
            done: function(data) {console.log(data)}
        });
    }

    //Process Ajax Template
    DupPro.Tools.IntScanValidator= function(data)
    {
        var template = $('#hb-template').html();
        var templateScript = Handlebars.compile(template);
        var html = templateScript(data);
        $('#hb-result').html(html);
        $('#scan-run-btn').html('<?php esc_html_e("Run Scan Integrity Validation", 'duplicator-pro'); ?>');
    }
});
</script>


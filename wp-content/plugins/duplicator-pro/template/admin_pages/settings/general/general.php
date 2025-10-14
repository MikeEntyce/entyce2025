<?php

/**
 * @package Duplicator
 */

use Duplicator\Addons\ProBase\License\License;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapJson;
use Duplicator\Utils\ExpireOptions;
use Duplicator\Controllers\SettingsPageController;
use Duplicator\Core\Controllers\PageAction;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 * @var array<string, mixed> $tplData
 */

$global = DUP_PRO_Global_Entity::getInstance();

/** @var PageAction */
$resetAction = $tplData['actions'][SettingsPageController::ACTION_RESET_SETTINGS];

$trace_log_enabled       = (bool) get_option('duplicator_pro_trace_log_enabled');
$send_trace_to_error_log = (bool) get_option('duplicator_pro_send_trace_to_error_log');

if ($trace_log_enabled) {
    $logging_mode = ($send_trace_to_error_log) ?  'enhanced' : 'on';
} else {
    $logging_mode = 'off';
}

?>

<form id="dup-settings-form" action="<?php echo esc_url(ControllersManager::getCurrentLink()); ?>" method="post" data-parsley-validate>
    <?php $tplData['actions'][SettingsPageController::ACTION_GENERAL_SAVE]->getActionNonceFileds(); ?>
    <!-- =============================== PLUG-IN SETTINGS -->
    <h3 class="title"><?php esc_html_e("Plugin", 'duplicator-pro') ?> </h3>
    <hr size="1" />
    <table class="form-table">
        <tr valign="top">
            <th scope="row">
                <label>
                    <?php
                        esc_html_e("Version", 'duplicator-pro');
                    ?>
                </label>
            </th>
            <td>
                <?php
                    echo esc_html(DUPLICATOR_PRO_VERSION);
                ?>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label><?php esc_html_e("Uninstall", 'duplicator-pro'); ?></label></th>
            <td>
                <input
                    type="checkbox"
                    name="uninstall_settings"
                    id="uninstall_settings"
                    value="1"
                    <?php checked($global->uninstall_settings); ?>
                >
                <label for="uninstall_settings"><?php esc_html_e("Delete plugin settings", 'duplicator-pro'); ?> </label><br />

                <input
                    type="checkbox"
                    name="uninstall_packages"
                    id="uninstall_packages"
                    value="1"
                    <?php checked($global->uninstall_packages); ?>
                >
                <label for="uninstall_packages"><?php esc_html_e("Delete entire storage directory", 'duplicator-pro'); ?></label><br />

            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label><?php esc_html_e("Encrypt Settings", 'duplicator-pro'); ?></label></th>
            <td>
                <input
                    type="checkbox"
                    name="crypt"
                    id="crypt"
                    value="1"
                    <?php checked($global->crypt); ?>
                >
                <label for="crypt"><?php esc_html_e("Enable settings encryption", 'duplicator-pro'); ?> </label><br />
                <p class="description">
                    <?php esc_html_e("Only uncheck if machine doesn't support PCrypt.", 'duplicator-pro'); ?>
                </p>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label><?php esc_html_e("Usage statistics", 'duplicator-pro'); ?></label></th>
            <td>
                <?php if (DUPLICATOR_USTATS_DISALLOW) {  // @phpstan-ignore-line ?>
                    <span class="maroon">
                        <?php esc_html_e('Usage statistics are hardcoded disallowed.', 'duplicator-pro'); ?>
                    </span>
                <?php } else { ?>
                    <input
                        type="checkbox"
                        name="usage_tracking"
                        id="usage_tracking"
                        value="1"
                        <?php checked($global->getUsageTracking()); ?>
                    >
                    <label for="usage_tracking"><?php esc_html_e("Enable usage tracking", 'duplicator-pro'); ?> </label>
                    <i 
                            class="fas fa-question-circle fa-sm" 
                            data-tooltip-title="<?php esc_attr_e("Usage Tracking", 'duplicator-pro'); ?>" 
                            data-tooltip="<?php echo esc_attr($tplMng->render('admin_pages/settings/general/usage_tracking_tooltip', [], false)); ?>"
                            data-tooltip-width="600"
                    >
                    </i>
                <?php } ?>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label><?php esc_html_e("Hide Announcements", 'duplicator-pro'); ?></label></th>
            <td>
                <input
                    type="checkbox"
                    name="dup_am_notices"
                    id="dup_am_notices"
                    value="1"
                    <?php checked(!$global->isAmNoticesEnabled()); ?>
                >
                <label for="dup_am_notices">
                    <?php esc_html_e("Check this option to hide plugin announcements and update details.", 'duplicator-pro'); ?>
                </label>
            </td>
        </tr>
    </table><br />
    <?php TplMng::getInstance()->render('parts/settings/email_summary', []); ?>
    <!-- ===============================
DEBUG SETTINGS -->
    <h3 class="title"><?php esc_html_e('Debug', 'duplicator-pro') ?> </h3>
    <hr size="1" />

    <table class="form-table">
        <tr>
            <th scope="row"><label><?php esc_html_e("Trace Log", 'duplicator-pro'); ?></label></th>
            <td>
                <select name="_logging_mode">
                    <option value="off" <?php selected($logging_mode, 'off'); ?>>
                        <?php esc_html_e('Off', 'duplicator-pro'); ?>
                    </option>
                    <option value="on" <?php selected($logging_mode, 'on'); ?>>
                        <?php esc_html_e('On', 'duplicator-pro'); ?>
                    </option>
                    <option value="enhanced" <?php selected($logging_mode, 'enhanced'); ?>>
                        <?php esc_html_e('On (Enhanced)', 'duplicator-pro'); ?>
                    </option>
                </select>
                <p class="description">
                    <?php
                    esc_html_e("Turning on log initially clears it out. The enhanced setting writes to both trace and PHP error logs.", 'duplicator-pro');
                    echo "<br/>";
                    esc_html_e("WARNING: Only turn on this setting when asked to by support as tracing will impact performance.", 'duplicator-pro');
                    ?>
                </p><br />
                <button class="button" <?php disabled(DUP_PRO_Log::traceFileExists(), false); ?> onclick="DupPro.Pack.DownloadTraceLog(); return false">
                    <i class="fa fa-download"></i>
                    <?php echo esc_html__('Download Trace Log', 'duplicator-pro') . ' (' . esc_html(DUP_PRO_Log::getTraceStatus()) . ')'; ?>
                </button>
            </td>
        </tr>
    </table><br />

    <!-- ===============================
ADVANCED SETTINGS -->
    <h3 class="title"><?php esc_html_e('Advanced', 'duplicator-pro') ?> </h3>
    <hr size="1" />
    <table class="form-table">
        <tr>
            <th scope="row"><label><?php esc_html_e("Settings", 'duplicator-pro'); ?></label></th>
            <td>
                <button id="dup-pro-reset-all" class="button" onclick="DupPro.Pack.ConfirmResetAll(); return false">
                    <i class="fas fa-redo fa-sm"></i> <?php esc_html_e('Reset All Settings', 'duplicator-pro'); ?>
                </button>
                <p class="description">
                    <?php
                        esc_html_e("Reset all settings to their defaults.", 'duplicator-pro');
                        $tContent = __(
                            'Resets standard settings to defaults. Does not affect capabilities, license key, storage or schedules.',
                            'duplicator-pro'
                        );
                        ?>
                    <i 
                        class="fas fa-question-circle fa-sm" 
                        data-tooltip-title="<?php esc_attr_e("Reset Settings", 'duplicator-pro'); ?>" 
                        data-tooltip="<?php echo esc_attr($tContent); ?>"
                    >
                    </i>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label><?php esc_html_e("Packages", 'duplicator-pro'); ?></label></th>
            <td>
                <button class="button" onclick="DupPro.Pack.ConfirmResetPackages(); return false;">
                    <i class="fas fa-redo fa-sm"></i> <?php esc_attr_e('Reset Incomplete Packages', 'duplicator-pro'); ?>
                </button>
                <p class="description">
                    <?php esc_html_e("Reset all packages.", 'duplicator-pro'); ?>
                    <i 
                        class="fas fa-question-circle fa-sm" 
                        data-tooltip-title="<?php esc_attr_e("Reset packages", 'duplicator-pro'); ?>" 
                        data-tooltip="<?php esc_attr_e('Delete all unfinished packages. So those with error and being created.', 'duplicator-pro'); ?>"
                    >
                    </i>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label><?php esc_html_e("Foreign JavaScript", 'duplicator-pro'); ?></label></th>
            <td>
                <input
                    type="checkbox"
                    name="_unhook_third_party_js"
                    id="_unhook_third_party_js"
                    value="1"
                    <?php checked($global->unhook_third_party_js); ?>
                >
                <label for="_unhook_third_party_js"><?php esc_html_e("Disable", 'duplicator-pro'); ?></label> <br />
                <p class="description">
                    <?php
                    esc_html_e("Check this option if JavaScript from the theme or other plugins conflicts with Duplicator Pro pages.", 'duplicator-pro');
                    ?>
                    <br>
                    <?php
                    esc_html_e("Do not modify this setting unless you know the expected result or have talked to support.", 'duplicator-pro');
                    ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label><?php esc_html_e("Foreign CSS", 'duplicator-pro'); ?></label></th>
            <td>
                <input
                    type="checkbox"
                    name="_unhook_third_party_css"
                    id="unhook_third_party_css"
                    value="1"
                    <?php checked($global->unhook_third_party_css); ?>
                >
                <label for="unhook_third_party_css"><?php esc_html_e("Disable", 'duplicator-pro'); ?></label> <br />
                <p class="description">
                    <?php
                    esc_html_e("Check this option if CSS from the theme or other plugins conflicts with Duplicator Pro pages.", 'duplicator-pro');
                    ?>
                    <br>
                    <?php
                    esc_html_e("Do not modify this setting unless you know the expected result or have talked to support.", 'duplicator-pro');
                    ?>
                </p>
            </td>
        </tr>
    </table>
    <p>
        <input 
            type="submit" name="submit" id="submit" 
            class="button-primary" 
            value="<?php esc_attr_e('Save General Settings', 'duplicator-pro') ?>"
        >
    </p>
</form>

<?php
$resetSettingsDialog                 = new DUP_PRO_UI_Dialog();
$resetSettingsDialog->title          = __('Reset Settings?', 'duplicator-pro');
$resetSettingsDialog->message        = __('Are you sure you want to reset settings to defaults?', 'duplicator-pro');
$resetSettingsDialog->progressText   = __('Resetting settings, Please Wait...', 'duplicator-pro');
$resetSettingsDialog->jsCallback     = 'DupPro.Pack.ResetAll()';
$resetSettingsDialog->progressOn     = false;
$resetSettingsDialog->okText         = __('Yes', 'duplicator-pro');
$resetSettingsDialog->cancelText     = __('No', 'duplicator-pro');
$resetSettingsDialog->closeOnConfirm = true;
$resetSettingsDialog->initConfirm();

$resetPackagesDialog                 = new DUP_PRO_UI_Dialog();
$resetPackagesDialog->title          = __('Reset Packages ?', 'duplicator-pro');
$resetPackagesDialog->message        = __('This will clear and reset all of the current temporary packages.  Would you like to continue?', 'duplicator-pro');
$resetPackagesDialog->progressText   = __('Resetting settings, Please Wait...', 'duplicator-pro');
$resetPackagesDialog->jsCallback     = 'DupPro.Pack.ResetPackages()';
$resetPackagesDialog->progressOn     = false;
$resetPackagesDialog->okText         = __('Yes', 'duplicator-pro');
$resetPackagesDialog->cancelText     = __('No', 'duplicator-pro');
$resetPackagesDialog->closeOnConfirm = true;
$resetPackagesDialog->initConfirm();

$msg_ajax_error                 = new DUP_PRO_UI_Messages(
    __('AJAX ERROR!', 'duplicator-pro') . '<br>' . __('Ajax request error', 'duplicator-pro'),
    DUP_PRO_UI_Messages::ERROR
);
$msg_ajax_error->hide_on_init   = true;
$msg_ajax_error->is_dismissible = true;
$msg_ajax_error->initMessage();

$msg_response_error                 = new DUP_PRO_UI_Messages(__('RESPONSE ERROR!', 'duplicator-pro'), DUP_PRO_UI_Messages::ERROR);
$msg_response_error->hide_on_init   = true;
$msg_response_error->is_dismissible = true;
$msg_response_error->initMessage();

$msg_response_success                 = new DUP_PRO_UI_Messages('', DUP_PRO_UI_Messages::NOTICE);
$msg_response_success->hide_on_init   = true;
$msg_response_success->is_dismissible = true;
$msg_response_success->initMessage();
?>

<script>
    jQuery(document).ready(function($) {
        // which: 0=installer, 1=archive, 2=sql file, 3=log
        DupPro.Pack.DownloadTraceLog = function() {
            var actionLocation = ajaxurl + '?action=duplicator_pro_get_trace_log&nonce=' 
            + '<?php echo esc_js(wp_create_nonce('duplicator_pro_get_trace_log')); ?>';
            location.href = actionLocation;
        };

        DupPro.Pack.ConfirmResetAll = function() {
            <?php $resetSettingsDialog->showConfirm(); ?>
        };

        DupPro.Pack.ConfirmResetPackages = function() {
            <?php $resetPackagesDialog->showConfirm(); ?>
        };

        DupPro.Pack.ResetAll = function() {
            let resetUrl = <?php echo SnapJson::jsonEncode($resetAction->getUrl()); ?>;
            location.href = resetUrl;
        };

        DupPro.Pack.ResetPackages = function() {
            $.ajax({
                type: "POST",
                url: ajaxurl,
                dataType: "json",
                data: {
                    action: 'duplicator_pro_reset_packages',
                    nonce: '<?php echo esc_js(wp_create_nonce('duplicator_pro_reset_packages')); ?>'
                },
                success: function(result) {
                    if (result.success) {
                        var message = '<?php esc_html_e('Packages successfully reset', 'duplicator-pro'); ?>';
                        <?php
                        $msg_response_success->updateMessage('message');
                        $msg_response_success->showMessage();
                        ?>
                    } else {
                        var message = '<?php esc_html_e('RESPONSE ERROR!', 'duplicator-pro'); ?>' + '<br><br>' + result.data.message;
                        <?php
                        $msg_response_error->updateMessage('message');
                        $msg_response_error->showMessage();
                        ?>
                    }
                },
                error: function(result) {
                    <?php $msg_ajax_error->showMessage(); ?>
                }
            });
        };

        //Init
        $("#_trace_log_enabled").click(function() {
            $('#_send_trace_to_error_log').attr('disabled', !$(this).is(':checked'));
        });

    });
</script>

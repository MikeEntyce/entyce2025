<?php
/* @var $global DUP_PRO_Global_Entity */
defined("ABSPATH") or die("");

use Duplicator\Core\Controllers\ControllersManager;

$nonce_action    = 'duppro-settings-schedule-edit';
$action_updated  = null;
$action_response = __("Schedule Settings Saved", 'duplicator-pro');

$global = DUP_PRO_Global_Entity::getInstance();

//SAVE RESULTS
if (!empty($_POST['action']) && $_POST['action'] == 'save') {
    DUP_PRO_U::verifyNonce($_POST['_wpnonce'], $nonce_action);
    $global->send_email_on_build_mode   = (int)$_REQUEST['send_email_on_build_mode'];
    $global->notification_email_address = stripslashes($_REQUEST['notification_email_address']);
    $action_updated                     = $global->save();
}
?>

<style>    
    table.form-table tr td { padding-top: 25px; }
</style>

<form id="dup-settings-form" action="<?php echo ControllersManager::getCurrentLink(); ?>" method="post" data-parsley-validate>
<?php wp_nonce_field($nonce_action); ?>
<input type="hidden" name="action" value="save">

<?php if ($action_updated) : ?>
    <div class="notice notice-success is-dismissible dpro-wpnotice-box"><p><?php echo $action_response; ?></p></div>
<?php endif; ?> 

<!-- ===============================
SCHEDULE SETTINGS -->
<h3 class="title"><?php esc_html_e("Notifications", 'duplicator-pro') ?> </h3>
<hr size="1" />
<table class="form-table">  
    <tr>
        <th scope="row"><label><?php esc_html_e("Send Build Email", 'duplicator-pro'); ?></label></th>
        <td>
            <input 
                type="radio" 
                name="send_email_on_build_mode" 
                id="send_email_on_build_mode_never" 
                value="<?php echo DUP_PRO_Email_Build_Mode::No_Emails; ?>" 
                <?php checked($global->send_email_on_build_mode, DUP_PRO_Email_Build_Mode::No_Emails); ?> 
            >
            <label for="send_email_on_build_mode_never"><?php esc_attr_e("Never", 'duplicator-pro'); ?></label> &nbsp;
            <input 
                type="radio" 
                name="send_email_on_build_mode" 
                id="send_email_on_build_mode_failure" 
                value="<?php echo DUP_PRO_Email_Build_Mode::Email_On_Failure; ?>" 
                <?php checked($global->send_email_on_build_mode, DUP_PRO_Email_Build_Mode::Email_On_Failure); ?> 
            >
            <label for="send_email_on_build_mode_failure"><?php esc_attr_e("On Failure", 'duplicator-pro'); ?></label> &nbsp;
            <input
                type="radio" 
                name="send_email_on_build_mode" 
                id="send_email_on_build_mode_always" 
                value="<?php echo DUP_PRO_Email_Build_Mode::Email_On_All_Builds; ?>" 
                <?php checked($global->send_email_on_build_mode, DUP_PRO_Email_Build_Mode::Email_On_All_Builds); ?> 
            >
            <label for="send_email_on_build_mode_always"><?php esc_attr_e("Always", 'duplicator-pro'); ?></label> &nbsp;
            <p class="description">
                <?php
                esc_html_e("When to send emails after a scheduled build.", 'duplicator-pro');
                ?>
            </p>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><label><?php esc_html_e("Email Address", 'duplicator-pro'); ?></label></th>
        <td>
            <input 
                style="display:block;margin-right:6px; width:25em;" 
                data-parsley-errors-container="#notification_email_address_error_container" 
                data-parsley-type="email" 
                type="email" 
                name="notification_email_address" 
                id="notification_email_address" value="<?php echo esc_attr($global->notification_email_address); ?>" 
            >
            <p class="description">  <?php esc_html_e('Admin email will be used if empty.', 'duplicator-pro'); ?>  </p>
            <div id="notification_email_address_error_container" class="duplicator-error-container"></div>

        </td>
    </tr>
</table>

<p class="submit dpro-save-submit">
    <input type="submit" name="submit" id="submit" class="button-primary" value="<?php esc_attr_e('Save Schedule Settings', 'duplicator-pro') ?>" style="display: inline-block;" />
</p>
</form>

<script>
jQuery(document).ready(function ($) {
    //Data
});
</script>

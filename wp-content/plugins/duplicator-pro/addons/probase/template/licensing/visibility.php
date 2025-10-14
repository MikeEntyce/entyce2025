<?php

/**
 * @package Duplicator
 */

use Duplicator\Addons\ProBase\License\License;
use Duplicator\Addons\ProBase\LicensingController;
use Duplicator\Core\Controllers\ControllersManager;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 * @var array<string, mixed> $tplData
 */

$global = DUP_PRO_Global_Entity::getInstance();
?>
<h3 class="title"><?php esc_html_e("Key Visibility", 'duplicator-pro') ?> </h3>
<small>
    <?php
    esc_html_e(
        "This is an optional setting that prevents the 'License Key' from being copied. 
        Select the desired visibility mode, enter a password and hit the 'Change Visibility' button.",
        'duplicator-pro'
    );
    echo '<br/>';
    esc_html_e("Note: the password can be anything, it does not have to be the same as the WordPress user password.", 'duplicator-pro');
    ?>
</small>
<hr size="1" />
<form
    id="dup-license-visibility-form"
    action="<?php echo esc_url(ControllersManager::getCurrentLink()); ?>"
    method="post"
    data-parsley-validate
>
    <?php $tplData['actions'][LicensingController::ACTION_CHANGE_VISIBILITY]->getActionNonceFileds(); ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><label><?php esc_html_e("Visibility", 'duplicator-pro'); ?></label></th>
            <td>
                <label class="margin-right-1">
                    <input
                        type="radio"
                        name="license_key_visible"
                        value="<?php echo (int) License::VISIBILITY_ALL;?>"
                        onclick="DupPro.Licensing.VisibilityTemporary(<?php echo (int) License::VISIBILITY_ALL;?>);"
                        <?php checked($global->license_key_visible, License::VISIBILITY_ALL); ?>
                    >
                    <?php esc_html_e("License Visible", 'duplicator-pro'); ?>
                </label>
                <label class="margin-right-1">
                    <input
                        type="radio"
                        name="license_key_visible"
                        value="<?php echo (int) License::VISIBILITY_INFO;?>"
                        onclick="DupPro.Licensing.VisibilityTemporary(<?php echo (int) License::VISIBILITY_INFO;?>);"
                        <?php checked($global->license_key_visible, License::VISIBILITY_INFO); ?>
                    >
                    <?php esc_html_e("Info Only", 'duplicator-pro'); ?>
                </label>
                <label>
                    <input
                        type="radio"
                        name="license_key_visible"
                        value="<?php echo (int) License::VISIBILITY_NONE;?>"
                        onclick="DupPro.Licensing.VisibilityTemporary(<?php echo (int) License::VISIBILITY_NONE;?>);"
                        <?php checked($global->license_key_visible, License::VISIBILITY_NONE); ?>
                    >
                    <?php esc_html_e("License Invisible", 'duplicator-pro'); ?>
                </label>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label><?php esc_html_e("Password", 'duplicator-pro'); ?></label></th>
            <td>
                <input type="password" class="dup-wide-input" name="_key_password" id="_key_password" size="50" />
            </td>
        </tr>
        <?php if ($global->license_key_visible == License::VISIBILITY_ALL) { ?>
            <tr valign="top">
                <th scope="row"><label><?php esc_html_e("Retype Password", 'duplicator-pro'); ?></label></th>
                <td>
                    <input
                        type="password"
                        class="dup-wide-input"
                        name="_key_password_confirmation"
                        id="_key_password_confirmation"
                        data-parsley-equalto="#_key_password"
                        size="50"
                    >
                </td>
            </tr>
        <?php } ?>
        <tr valign="top">
            <th scope="row"></th>
            <td>
                <button
                    class="button"
                    id="show_hide"
                    onclick="DupPro.Licensing.ChangeKeyVisibility(); return false;"
                >
                    <?php  esc_html_e('Change Visibility', 'duplicator-pro'); ?>
                </button>
            </td>
        </tr>
    </table>
</form>

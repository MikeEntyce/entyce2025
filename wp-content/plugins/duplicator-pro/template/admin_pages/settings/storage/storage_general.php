<?php

/**
 * @package Duplicator
 */

defined("ABSPATH") or die("");

use Duplicator\Controllers\SettingsPageController;
use Duplicator\Libs\Snap\SnapIO;

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 * @var array<string, mixed> $tplData
 */

$global = DUP_PRO_Global_Entity::getInstance();
?>
<form 
    id="dup-settings-form" 
    action="<?php echo $ctrlMng->getCurrentLink(); ?>" 
    method="post"
    data-parsley-validate
>
    <?php $tplData['actions'][SettingsPageController::ACTION_SAVE_STORAGE_GENERAL]->getActionNonceFileds(); ?>

    <table class="form-table">            
        <tr valign="top">
            <th scope="row"><label><?php esc_html_e("Storage", 'duplicator-pro'); ?></label></th>
            <td>
                <?php esc_html_e("Full Path", 'duplicator-pro'); ?>:
                <?php echo SnapIO::safePath(DUPLICATOR_PRO_SSDIR_PATH); ?><br/><br/>
                <input 
                    type="checkbox" 
                    name="_storage_htaccess_off" 
                    id="_storage_htaccess_off" 
                    value="1"
                    <?php checked($global->storage_htaccess_off); ?> 
                >
                <label for="_storage_htaccess_off">
                    <?php esc_html_e("Disable .htaccess File In Storage Directory", 'duplicator-pro') ?> 
                </label>
                <p class="description">
                    <?php esc_html_e("Disable if issues occur when downloading installer/archive files.", 'duplicator-pro'); ?>
                </p>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label><?php esc_html_e("Max Retries", 'duplicator-pro'); ?></label></th>
            <td>
                <input 
                    class="dup-narrow-input" 
                    type="text" 
                    name="max_storage_retries" 
                    id="max_storage_retries" 
                    data-parsley-required data-parsley-min="0" 
                    data-parsley-type="number" 
                    data-parsley-errors-container="#max_storage_retries_error_container" 
                    value="<?php echo $global->max_storage_retries; ?>" 
                >
                <div id="max_storage_retries_error_container" class="duplicator-error-container"></div>
                <p class="description">
                    <?php esc_html_e('Max upload/copy retries to attempt after failure encountered.', 'duplicator-pro'); ?>
                </p>
            </td>
        </tr>
    </table>
    <p class="submit dpro-save-submit">
        <input 
            type="submit" 
            name="submit" 
            id="submit" 
            class="button-primary" 
            value="<?php esc_attr_e('Save Storage Settings', 'duplicator-pro') ?>" 
            style="display: inline-block;" 
        >
    </p>
</form>
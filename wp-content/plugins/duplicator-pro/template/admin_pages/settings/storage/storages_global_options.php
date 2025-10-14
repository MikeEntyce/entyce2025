<?php

/**
 * @package Duplicator
 */

defined("ABSPATH") or die("");

use Duplicator\Controllers\SettingsPageController;
use Duplicator\Models\Storages\StoragesUtil;

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 * @var array<string, mixed> $tplData
 */

?>
<form 
    id="dup-settings-form" 
    action="<?php echo $ctrlMng::getCurrentLink(); ?>" 
    method="post" data-parsley-validate
>
    <?php
    $tplData['actions'][SettingsPageController::ACTION_SAVE_STORAGE_OPTIONS]->getActionNonceFileds();
    StoragesUtil::renderGlobalOptions();
    ?>

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
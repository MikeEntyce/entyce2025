<?php

/**
 * Duplicator package row in table packages list
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

use Duplicator\Controllers\PackagesPageController;
use Duplicator\Controllers\RecoveryController;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Views\TplMng;
use Duplicator\Package\Recovery\RecoveryPackage;
use Duplicator\Views\ViewHelper;

/**
 * Variables
 *
 * @var ControllersManager $ctrlMng
 * @var TplMng $tplMng
 * @var array<string, mixed> $tplData
 * @var DUP_PRO_Package $package
 */
$package        = $tplData['package'];
$isRecoverPoint = (RecoveryPackage::getRecoverPackageId() === $package->ID);

$colorClass = ($isRecoverPoint ? 'green' : '');
?>
<h3 class="dup-title margin-top-0">
    <?php ViewHelper::disasterIcon(true, $colorClass); ?>&nbsp;
    <?php
    if ($isRecoverPoint) {
        esc_html_e('Disaster Recovery - Is Set on this Backup', 'duplicator-pro');
    } else {
        esc_html_e('Disaster Recovery - Is Available for this Backup', 'duplicator-pro');
    }
    ?>
</h3>

<?php $tplMng->render('parts/recovery/package_info_mini'); ?>
<hr class="margin-top-1 margin-bottom-1" >

<?php if ($isRecoverPoint) {
    RecoveryController::renderRecoveryWidged([
        'details'    => false,
        'selector'   => false,
        'subtitle'   => '',
        'copyLink'   => false,
        'copyButton' => true,
        'launch'     => false,
        'download'   => true,
        'info'       => true,
    ]);
} else {
    $setRecoveryLink = PackagesPageController::getInstance()->getActionByKey(PackagesPageController::ACTION_SET_RECOVERY_POINT)->getUrl(
        ['recovery_package' => $package->ID]
    );
    ?>
    <div class="dup-pro-recovery-widget-wrapper" >
        <div class="dup-pro-recovery-point-actions" >
            <div class="dup-pro-recovery-buttons">
                <a 
                    href="<?php echo esc_url($setRecoveryLink); ?>" 
                    class="button button-primary dpro-btn-set-recovery" 
                    data-package-id="<?php echo (int) $package->ID; ?>"
                >
                    <span><?php ViewHelper::disasterIcon(); ?>&nbsp;
                    <?php esc_html_e("Set Disaster Recovery", 'duplicator-pro'); ?></span>&nbsp;
                    <i 
                        class="fas fa-question-circle fa-sm dup-base-color white"
                        data-tooltip-title="<?php esc_attr_e("Activate Recovery", 'duplicator-pro'); ?>"
                        data-tooltip="<?php esc_attr_e("This action will set this package as the active Disaster Recovery Backup.", 'duplicator-pro'); ?>"
                        aria-expanded="false"
                    >
                    </i>
                </a>
            </div>
        </div>
    </div>
<?php }

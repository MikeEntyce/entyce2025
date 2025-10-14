<?php

use Duplicator\Controllers\PackagesPageController;
use Duplicator\Core\CapMng;
use Duplicator\Package\Recovery\RecoveryPackage;

defined('ABSPATH') || defined('DUPXABSPATH') || exit;

/**
 * Variables
 *
 * @var RecoveryPackage $recoverPackage
 * @var int $recoverPackageId
 * @var array<int, array{id: int, created: string, nameHash: string, name: string}> $recoveablePackages
 * @var bool $selector
 * @var string $subtitle
 * @var bool $displayCopyLink
 * @var bool $displayCopyButton
 * @var bool $displayLaunch
 * @var bool $displayDownload
 * @var bool $displayInfo
 * @var string $viewMode
 * @var string $importFailMessage
 */

if (!$selector) {
    return;
}

$packagesURL = PackagesPageController::getInstance()->getPageUrl();

?>
<div class="dup-pro-recovery-point-selector">
    <?php if (empty($recoveablePackages)) { ?>
        <div class="dup-pro-notice-details">
            <div class="margin-bottom-1" >
                <b><?php _e('Would you like to create a Recovery Point before running this import?', 'duplicator-pro'); ?></b>
            </div>
            <b><?php _e('How to create:', 'duplicator-pro'); ?></b>
            <ol class="dup-pro-simple-style-list" >
                <li>
                    <?php _e('Open the ', 'duplicator-pro'); ?>
                    <a href="<?php echo esc_url($packagesURL); ?>" target="_blank">
                        <?php _e('packages screen', 'duplicator-pro'); ?>
                    </a>
                    <i class="fas fa-external-link-alt fa-small" ></i>
                    <?php _e('and create a valid recovery package.', 'duplicator-pro'); ?>
                </li>
                <li>
                    <?php _e('On the packages screen click the package\'s Hamburger menu and select "Set Recovery Point".', 'duplicator-pro'); ?>
                </li>
                <li>
                    <span class="dup-pro-recovery-windget-refresh link-style"><?php _e('Refresh', 'duplicator-pro'); ?></span>
                    <?php _e('this page to show and choose the recovery point', 'duplicator-pro'); ?>.
                </li>
            </ol>
        </div>
    <?php } else {
        $tooltipContent = __(
            'A Recovery Point allows one to quickly restore the site to a prior state. 
            To use this, mark a package as the Recovery Point, then copy and save off the associated URL. 
            Then, if a problem occurs, browse to the URL to launch a streamlined installer to quickly restore the site.',
            'duplicator-pro'
        );
        ?>
        <div class="dup-pro-recovery-point-selector-area-wrapper" >
            <?php if (CapMng::can(CapMng::CAP_CREATE, false)) { ?>
                <span class="dup-pro-opening-packages-windows" >
                    <a href="<?php echo esc_url($packagesURL); ?>" >[<?php _e('Create New', 'duplicator-pro'); ?>]</a>
                </span> 
            <?php } ?>
            <label>
                <i class="fas fa-question-circle fa-sm"
                    data-tooltip-title="<?php esc_attr_e("Choose Recovery Point Archive", 'duplicator-pro'); ?>"
                    data-tooltip="<?php echo esc_attr($tooltipContent); ?>">
                </i>
                <b><?php _e('Step 1 ', 'duplicator-pro'); ?>:</b> <i><?php _e('Choose Recovery Point Archive', 'duplicator-pro'); ?></i>
            </label>
            <div class="dup-pro-recovery-point-selector-area">
                <select class="recovery-select" name="recovery_package" >
                    <option value=""> -- <?php _e('Not selected', 'duplicator-pro'); ?> -- </option>
                    <?php
                    $currentDay = null;
                    foreach ($recoveablePackages as $package) {
                        $packageDay = date("Y/m/d", strtotime($package['created']));
                        if ($packageDay != $currentDay) {
                            if (!is_null($currentDay)) {
                                ?>
                                </optgroup>
                            <?php } ?>
                            <optgroup label="<?php echo esc_attr($packageDay); ?>">
                                <?php
                                $currentDay = $packageDay;
                        }
                        ?>
                            <option value="<?php echo $package['id']; ?>" <?php selected($recoverPackageId, $package['id']) ?>>
                                <?php echo '[' . $package['created'] . '] ' . $package['name']; ?>
                            </option>
                    <?php } ?>
                    </optgroup>
                </select>             
                <button type="button" class="button recovery-reset" ><?php echo _e('Reset', 'duplicator-pro'); ?></button> 
                <button type="button" class="button button-primary recovery-set" ><?php echo _e('Set', 'duplicator-pro'); ?></button>
            </div>
        </div>
    <?php } ?>
</div>

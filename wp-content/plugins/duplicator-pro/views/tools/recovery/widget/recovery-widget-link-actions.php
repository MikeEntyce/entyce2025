<?php

use Duplicator\Package\Recovery\RecoveryPackage;
use Duplicator\Views\ViewHelper;

defined('ABSPATH') || defined('DUPXABSPATH') || exit;

/**
 * passed values
 *
 * @var ?RecoveryPackage $recoverPackage
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


if (empty($recoveablePackages)) {
    return;
}

$installerLink = ($recoverPackage instanceof RecoveryPackage) ? $recoverPackage->getInstallLink() : '';
$disabledClass = empty($installerLink) ? 'disabled' : '';

if ($displayCopyLink) {
    $toolTipContent  = __(
        'The recovery point URL is the link to the recovery point package installer. 
        The link will run the installer wizard used to re-install and recover the site. 
        Copy this link and keep it in a safe location to easily restore this site.',
        'duplicator-pro'
    );
    $toolTipContent .= '<br><br><b>';
    $toolTipContent .= __('This URL is valid until another recovery point is set.', 'duplicator-pro');
    $toolTipContent .= '</b>';
    ?>
    <label>
        <i class="fas fa-question-circle fa-sm"
            data-tooltip-title="<?php esc_attr_e("Recovery Point URL", 'duplicator-pro'); ?>"
            data-tooltip="<?php echo esc_attr($toolTipContent); ?>"
        >
        </i> 
        <b><?php _e('Step 2 ', 'duplicator-pro'); ?>:</b> <i><?php _e('Copy Recovery URL &amp; Store in Safe Place', 'duplicator-pro'); ?></i>
    </label>
    <div class="copy-link <?php echo $disabledClass; ?>"
         data-dup-copy-value="<?php echo esc_url($installerLink); ?>"
         data-dup-copy-title="<?php _e("Copy Recovery URL to clipboard", 'duplicator-pro'); ?>"
         data-dup-copied-title="<?php _e("Recovery URL copied to clipboard", 'duplicator-pro'); ?>" >
        <div class="content" >
            <?php echo empty($installerLink) ? __('Please set the Recovery Point to generate the Recovery URL', 'duplicator-pro') : $installerLink; ?>
        </div>
        <i class="far fa-copy copy-icon"></i>
    </div>
<?php } ?>
<div class="dup-pro-recovery-buttons" >
    <?php
    if ($displayLaunch) { ?>
        <a href="<?php echo esc_url($installerLink); ?>"
           class="button button-primary dup-pro-launch <?php echo $disabledClass; ?>" target="_blank"
           title="<?php _e('Initiates system recovery using the Recovery Point URL.', 'duplicator-pro'); ?>" 
        >
            <?php ViewHelper::restoreIcon(); ?>&nbsp;<?php _e('Restore Backup', 'duplicator-pro'); ?>
        </a>
        <?php
    }
    if ($displayDownload) {
        $title = __(
            'This button downloads a recovery launcher that allows you to perform the recovery with a simple click of the downloaded file.',
            'duplicator-pro'
        );
        ?>
        <button 
            type="button" 
            class="button button-primary dup-pro-recovery-download-launcher <?php echo $disabledClass; ?>" 
            title="<?php echo esc_attr($title); ?>"
        >
            <i class="fa fa-rocket" ></i>&nbsp;<?php _e('Download Launcher', 'duplicator-pro'); ?>
        </button>
        <?php
    }
    if ($displayCopyButton) {
        ?>
        <button type="button" class="button button-primary dup-pro-recovery-copy-url <?php echo $disabledClass; ?>" 
                data-dup-copy-value="<?php echo $installerLink; ?>"
                data-dup-copy-title="<?php _e("Copy Recovery URL to clipboard", 'duplicator-pro'); ?>"
                data-dup-copied-title="<?php _e("Recovery URL copied to clipboard", 'duplicator-pro'); ?>" >
            <i class="far fa-copy copy-icon"></i>&nbsp;<?php _e('Copy LINK', 'duplicator-pro'); ?>
        </button>
        <?php
    }
    ?>
</div>

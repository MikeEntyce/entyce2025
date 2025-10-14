<?php

/**
 * @package Duplicator
 */

use Duplicator\Addons\ProBase\License\License;
use Duplicator\Addons\ProBase\LicensingController;
use Duplicator\Addons\ProBase\Models\LicenseData;
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

$license_status          = LicenseData::getInstance()->getStatus();
$license_type            = License::getType();
$license_text_disabled   = false;
$activate_button_text    = __('Activate', 'duplicator-pro');
$license_status_text_alt = false;


switch ($license_status) {
    case LicenseData::STATUS_VALID:
        $license_status_style  = 'color:#509B18';
        $activate_button_text  = __('Deactivate', 'duplicator-pro');
        $license_text_disabled = true;

        $license_key = License::getLicenseKey();

        $license_status_text  = '<b>' . __('Status: ', 'duplicator-pro') . '</b>' . __('Active', 'duplicator-pro');
        $license_status_text .= '<br/>';
        $license_status_text .= '<b>' . __('Expiration: ', 'duplicator-pro') . '</b>';
        $license_status_text .= LicenseData::getInstance()->getExpirationDate(get_option('date_format'));
        $expDays              = LicenseData::getInstance()->getExpirationDays();

        if ($expDays === false) {
            $expDays = __('no data', 'duplicator-pro');
        } elseif ($expDays <= 0) {
            $expDays = __('expired', 'duplicator-pro');
        } elseif ($expDays == PHP_INT_MAX) {
            $expDays = __('no expiration', 'duplicator-pro');
        } else {
            $expDays = sprintf(__('%d days left', 'duplicator-pro'), $expDays);
        }

        $license_status_text .= ' (<b>' . $expDays . '</b>)';
        break;
    case LicenseData::STATUS_INACTIVE:
        $license_status_style = 'color:#dd3d36;';
        $license_status_text  = __('Status: Inactive', 'duplicator-pro');
        break;
    case LicenseData::STATUS_SITE_INACTIVE:
        $license_status_style = 'color:#dd3d36;';
        $global               = DUP_PRO_Global_Entity::getInstance();

        if (LicenseData::getInstance()->haveNoActivationsLeft()) {
            $license_status_text = __('Status: Inactive (out of site licenses).', 'duplicator-pro') . '<br>' . License::getNoActivationLeftMessage();
        } else {
            $license_status_text = __('Status: Inactive', 'duplicator-pro');
        }
        break;
    case LicenseData::STATUS_EXPIRED:
        $renewal_url          = DUPLICATOR_PRO_BLOG_URL . 'checkout?edd_license_key=' . License::getLicenseKey();
        $license_status_style = 'color:#dd3d36;';
        $license_status_text  = sprintf(
            _x(
                'Your Duplicator Pro license key has expired so you aren\'t getting important updates! %1$sRenew your license now%2$s',
                '1: <a> tag, 2: </a> tag',
                'duplicator-pro'
            ),
            '<a target="_blank" href="' . $renewal_url . '">',
            '</a>'
        );
        break;
    default:
        // https://duplicator.com/knowledge-base/how-to-resolve-license-activation-issues/
        $license_status_style    = 'color:#dd3d36;';
        $license_status_text     = '<b>' .  __('Status: ', 'duplicator-pro') . '</b>' .
            LicenseData::getInstance()->getLicenseStatusString() . '<br/>';
        $license_status_text_alt = true;
        break;
}
?>


<form
    id="dup-license-activation-form"
    action="<?php echo esc_url(ControllersManager::getCurrentLink()); ?>"
    method="post"
    data-parsley-validate
>
    <h3 class="title"><?php esc_html_e('Activation', 'duplicator-pro') ?> </h3>
    <hr size="1" />
    <table class="form-table">
        <?php
        if ($global->license_key_visible !== License::VISIBILITY_NONE) : ?>
            <tr valign="top" id="dup-tr-license-dashboard">
                <th scope="row"><?php esc_html_e('Dashboard', 'duplicator-pro') ?></th>
                <td>
                    <i class="fa fa-th-large fa-sm"></i>
                    <a target="_blank" href="<?php echo esc_url(DUPLICATOR_PRO_BLOG_URL . 'my-account'); ?>">
                        <?php
                        esc_html_e('Manage Account Online', 'duplicator-pro')
                        ?>
                    </a>
                </td>
            </tr>
            <tr valign="top" id="dup-tr-license-type">
                <th scope="row"><?php esc_html_e('License Type', 'duplicator-pro') ?></th>
                <td class="dup-license-type">
                    <?php LicensingController::displayLicenseInfo(); ?>
                </td>
            </tr>
        <?php endif; ?>
        <?php if ($global->license_key_visible === License::VISIBILITY_ALL) : ?>
            <tr valign="top" id="dup-tr-license-key-and-description">
                <th scope="row">
                    <label><?php esc_html_e('License Key', 'duplicator-pro'); ?></label>
                </th>
                <td class="dup-license-key-area">
                    <input
                        type="text"
                        class="dup-license-key-input"
                        name="_license_key"
                        id="_license_key"
                        value="<?php echo esc_attr(License::getLicenseKey()); ?>">
                    <br>
                    <p class="description">
                    <span style="<?php echo esc_attr($license_status_style); ?>" >
                        <?php
                        echo wp_kses(
                            $license_status_text,
                            [
                                'a'  => [
                                    'href'   => [],
                                    'target' => [],
                                ],
                                'b'  => [],
                                'br' => [],
                            ]
                        );
                        ?>
                    </span>
                    <?php
                    if ($license_status_text_alt) {
                        esc_html_e('If license activation fails please wait ~ one hour and retry.', 'duplicator-pro');
                        ?>
                    <div class="dup-license-status-notes ">
                        <?php
                        printf(
                            esc_html_x(
                                '- Failure to activate after several attempts please review %1$sfaq activation steps%2$s.',
                                '1 and 2 represent opening and closing anchor tags (<a> and </a>)',
                                'duplicator-pro'
                            ),
                            '<a target="_blank" href="' . esc_url(DUPLICATOR_PRO_DUPLICATOR_DOCS_URL . 'how-to-resolve-license-activation-issues/') . '">',
                            '</a>'
                        );
                        ?>
                        <br/>
                        <?php
                            printf(
                                esc_html_x(
                                    '- To upgrade or renew your license visit %1$sduplicator.com%2$s.',
                                    '1 and 2 represent opening and closing anchor tags (<a> and </a>)',
                                    'duplicator-pro'
                                ),
                                '<a target="_blank" href="' . esc_url(DUPLICATOR_PRO_BLOG_URL) . '">',
                                '</a>'
                            );
                        ?>
                        <br/>
                        <?php esc_html_e('- A valid key is needed for plugin updates but not for functionality.', 'duplicator-pro'); ?>
                    </div>
                    <?php } ?>
                    </p>
                </td>
            </tr>
        <?php endif;?>
        <tr>
            <th scope="row" class="dup-license-key-btns">
                <label><?php esc_html_e('License Action', 'duplicator-pro'); ?></label>
            </th>
            <td class="dup-license-key-btns">
                <?php $echostring = (($license_status != LicenseData::STATUS_VALID) ? 'true' : 'false'); ?>
                <div class="dup-license-key-btns">
                    <?php if ($global->license_key_visible === License::VISIBILITY_ALL) : ?>
                    <button
                        id="dup-license-activation-btn"
                        class="button"
                        onclick="DupPro.Licensing.ChangeActivationStatus(<?php echo esc_js($echostring); ?>);return false;">
                        <?php echo esc_html($activate_button_text); ?>
                    </button>
                    <?php endif;?>
                    <button 
                        id="dup-license-clear-btn"
                        class="button" 
                        onclick="DupPro.Licensing.ClearActivationStatus();return false;"
                    >
                        <?php esc_html_e('Clear Key', 'duplicator-pro') ?>
                    </button>
                </div>
            </td>
        </tr>
    </table>
</form>

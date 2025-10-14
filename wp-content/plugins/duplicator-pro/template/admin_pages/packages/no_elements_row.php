<?php

/**
 * Duplicator package row in table packages list
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 * @var array<string, mixed> $tplData
 */

?>
<tr class="dpro-nopackages">
    <td colspan="11" class="dup-list-nopackages">
        <br />
        <i class="fa fa-archive fa-sm"></i>
        <?php esc_html_e("No Packages Found", 'duplicator-pro'); ?><br />
        <i><?php esc_html_e("Click 'Create New' to Archive Site", 'duplicator-pro'); ?></i>
        <div class="dup-quick-start">
            <b><?php esc_html_e("New to Duplicator?", 'duplicator-pro'); ?></b><br />
            <span class="dup-open-details link-style" onclick="DupPro.Pack.openLinkDetails()">
                <?php esc_html_e("Learn Duplicator in a few minutes!", 'duplicator-pro'); ?>
            </span><br/>
            <a
                class="dup-quick-start-link"
                href="<?php echo esc_url(DUPLICATOR_PRO_BLOG_URL . 'knowledge-base-article-categories/quick-start/'); ?>"
                target="_blank">
                <?php esc_html_e("Visit the 'Quick Start' guide!", 'duplicator-pro'); ?>
            </a>
        </div>
        <div style="height:75px">&nbsp;</div>
    </td>
</tr>

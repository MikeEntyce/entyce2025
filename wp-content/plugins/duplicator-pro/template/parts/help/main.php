<?php

/**
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

use Duplicator\Utils\Help\Help;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 * @var array<string, mixed> $tplData
 */
?>
<style>
    /* Dynamic Help Style */
    #duplicator-pro-help-wrapper {
        display: block;
        width: 100%;
        height: 100%;
        position: absolute;
        top: 0;
        left: 0;
        background-color: #fff;
        overflow-y: auto;
        box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.1);
        -webkit-font-smoothing: antialiased !important;
        -moz-osx-font-smoothing: grayscale !important;
    }

    #duplicator-pro-help-header {
        position: fixed;
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        width: 90%;
        top: 30px;
        left: 5%;
    }

    #duplicator-pro-help-content {
        width: 700px;
        margin: 150px auto 50px;
    }

    @media only screen and (max-width: 700px) {
        #duplicator-pro-help-content {
            width: 90%;
        }
    }

    #duplicator-pro-help-search {
        margin-bottom: 30px;
    }

    #duplicator-pro-help-search-results-empty {
        display: none;
    }

    #duplicator-pro-help-search-results,
    #duplicator-pro-help-search-results-empty {
        margin-top: 50px;
    }

    #duplicator-pro-help-search input {
        border: 1px solid #999999;
        border-radius: 25px;
        color: #444444;
        font-size: 20px;
        letter-spacing: 0;
        line-height: 20px;
        min-height: 48px;
        padding: 10px 10px 10px 25px;
        text-align: left;
        width: 100%;
    }

    #duplicator-pro-help-categories > ul > li ul {
        display: none;
        margin-left: 30px;
    }

    #duplicator-pro-help-categories > ul > li .duplicator-pro-help-category:last-of-type {
        border-bottom: 0;
    }

    .duplicator-pro-help-category {
        border-top: 1px solid #dddddd;
        margin: 0;
        width: 100%;
    }

    .duplicator-pro-help-category:last-of-type {
        border-bottom: 1px solid #dddddd;
    }

    .duplicator-pro-help-category header {
        display: flex;
        flex-direction: row;
        justify-content: flex-start;
        align-items: center;
        cursor: pointer;
        padding: 20px 0;
    }

    .duplicator-pro-help-category header span {
        color: #444444;
        font-size: 16px;
        font-weight: 600;
    }

    .duplicator-pro-help-category header .fa-folder-open {
        color: #999999;
        font-size: 21px;
        margin-right: 10px;
    }

    .duplicator-pro-help-category header .fa-angle-right {
        color: #cccccc;
        font-size: 24px;
        margin-left: auto;
        transition-property: transform;
        transition-duration: 0.25s;
        transition-timing-function: ease-out;
    }

    .duplicator-pro-help-article-list {
        display: none;
        margin-top: 20px;
    }

    .duplicator-pro-help-article {
        margin: 0;
        padding: 0 0 14px 4px;
    }

    .duplicator-pro-help-article .fa-file-alt {
        color: #b6b6b6;
        font-size: 16px;
        margin: 0 14px 0 0;
    }

    .duplicator-pro-help-article a {
        border-bottom: 1px solid transparent;
        color: #666;
        font-size: 15px;
        text-decoration: none;
    }

    #duplicator-pro-help-footer {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        margin-top: 50px;
    }

    .duplicator-pro-help-footer-block {
        border: 1px solid #ddd;
        border-radius: 6px;
        box-sizing: border-box;
        max-width: 340px;
        padding: 25px;
        text-align: center;
    }

    .duplicator-pro-help-footer-block i {
        color: #999;
        font-size: 48px;
        margin: 0 0 20px 0;
    }

    .duplicator-pro-help-footer-block h3 {
        color: #444;
        font-size: 16px;
        margin: 0 0 10px 0;
        font-weight: bold;
    }

    .duplicator-pro-help-footer-block p {
        color: #777777;
        font-size: 14px;
        margin: 0 0 20px 0;
    }

    .duplicator-pro-help-footer-block .button {
        border: 1px solid #af9ca6 !important;
        color: #4f394d !important;
        background: transparent !important;
        font-size: 15px !important;
    }
</style>
<div id="duplicator-pro-help-wrapper">
    <div id="duplicator-pro-help-header">
        <img src="<?php echo esc_url(DUPLICATOR_PRO_PLUGIN_URL . 'assets/img/duplicator-header-logo.svg'); ?>" />
    </div>
    <div id="duplicator-pro-help-content">
        <div id="duplicator-pro-help-search">
            <input type="text" placeholder="<?php esc_attr_e("Search", "duplicator-pro"); ?>" />
            <ul id="duplicator-pro-help-search-results"></ul>
            <div id="duplicator-pro-help-search-results-empty"><?php esc_html_e("No results found", "duplicator-pro"); ?></div>
        </div>
        <div id="duplicator-pro-context-articles">
            <?php if (count(Help::getInstance()->getArticlesByTag($tplData['tag'])) > 0) : ?>
                <h2><?php esc_html_e("Related Articles", "duplicator-pro"); ?></h2>
                <?php $tplMng->render('parts/help/article-list', ['articles' => Help::getInstance()->getArticlesByTag($tplData['tag'])]); ?>
            <?php endif; ?>
        </div>
        <div id="duplicator-pro-help-categories">
            <?php $tplMng->render('parts/help/category-list', ['categories' => Help::getInstance()->getTopLevelCategories()]); ?>
        </div>
        <div id="duplicator-pro-help-footer">
            <div class="duplicator-pro-help-footer-block">
                <i aria-hidden="true" class="fa fa-file-alt"></i>
                <h3><?php esc_html_e("View Documentation", "duplicator-pro"); ?></h3>
                <p>
                    <?php esc_html_e("Browse documentation, reference material, and tutorials for Duplicator.", "duplicator-pro"); ?>
                </p>
                <a 
                    href="<?php echo esc_url(DUPLICATOR_PRO_BLOG_URL . 'docs'); ?>" 
                    rel="noopener noreferrer" 
                    target="_blank" 
                    class="button">
                  <?php esc_html_e("View All Documentation", "duplicator-pro"); ?>
                </a>
            </div>
            <div class="duplicator-pro-help-footer-block">
                <i aria-hidden="true" class="fa fa-life-ring"></i>
                <h3><?php esc_html_e("Get Support", "duplicator-pro"); ?></h3>
                <p><?php esc_html_e("You can access our world-class support below.", "duplicator-pro"); ?></p>
                <a 
                    href="<?php echo esc_url(DUPLICATOR_PRO_BLOG_URL . 'my-account/support/'); ?>" 
                    rel="noopener noreferrer" 
                    target="_blank" 
                    class="button">
                    <?php esc_html_e("Get Support", "duplicator-pro"); ?>
                </a>
            </div>
        </div>
    </div>
</div>

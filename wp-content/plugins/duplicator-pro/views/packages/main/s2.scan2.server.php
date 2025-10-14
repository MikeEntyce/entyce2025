<?php

/**
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

defined('ABSPATH') || defined('DUPXABSPATH') || exit;

use Duplicator\Addons\DropboxAddon\Models\DropboxStorage;
use Duplicator\Addons\ProBase\License\License;
use Duplicator\Controllers\ToolsPageController;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Models\DynamicGlobalEntity;

/**
 * Variables
 *
 * @var ?DUP_PRO_Package $Package
 * @var bool $archive_export_onlydb
 */

$global = DUP_PRO_Global_Entity::getInstance();

global $wp_version;

$diagnosticUrl = ToolsPageController::getInstance()->getMenuLink(ToolsPageController::L2_SLUG_DISAGNOSTIC);
?>
<!-- ================================================================
SETUP
================================================================ -->
<div class="details-title">
    <i class="fas fa-tasks fa-sm fa-fw"></i> <?php esc_html_e("Setup", 'duplicator-pro'); ?>
    <div class="dup-more-details">
        <a href="<?php echo esc_url($diagnosticUrl); ?>" target="_blank" title="<?php esc_attr_e('Show Diagnostics', 'duplicator-pro'); ?>">
            <i class="fa fa-microchip"></i>
        </a>&nbsp;
        <a href="site-health.php" target="_blank" title="<?php esc_attr_e('Site Health', 'duplicator-pro'); ?>"><i class="fas fa-file-medical-alt"></i></a>
    </div>
</div>

<!-- ==========================
SYSTEM SETTINGS -->
<div class="scan-item scan-item-first">
    <div class='title' onclick="DupPro.Pack.toggleScanItem(this);">
        <div class="text"><i class="fa fa-caret-right"></i> <?php esc_html_e('System', 'duplicator-pro'); ?></div>
        <div id="data-srv-php-all"></div>
    </div>
    <div class="info">
        <?php
        //DIVIDER
        echo "<div class='scan-system-divider'><i class='fa fa-list'></i>&nbsp;" . __('General Checks', 'duplicator-pro') . "</div>";

        if (License::can(License::CAPABILITY_BRAND)) :
            ?>
            <span id="data-srv-brand-check"></span>&nbsp;<b><?php esc_html_e('Brand', 'duplicator-pro'); ?>: </b>&nbsp;<span id="data-srv-brand-name"><?php esc_html_e('Default', 'duplicator-pro'); ?></span><br />
            <div class="scan-system-subnote" id="data-srv-brand-note"><?php esc_html_e('The default content used when a brand is not defined.', 'duplicator-pro'); ?></div>
            <hr size="1" />
            <?php
        endif;
        //WEB SERVER
        $web_servers = implode(', ', $GLOBALS['DUPLICATOR_PRO_SERVER_LIST']);
        echo '<span id="data-srv-php-websrv"></span>&nbsp;<b>' . __('Web Server', 'duplicator-pro') . ":</b>&nbsp; '{$_SERVER['SERVER_SOFTWARE']}' <br/>";
        echo '<div class="scan-system-subnote">';
        esc_html_e("Supported Web Servers:", 'duplicator-pro');
        echo "&nbsp;{$web_servers}";
        echo '</div>';

         //MYSQLI
        echo '<hr size="1" /><span id="data-srv-php-mysqli"></span>&nbsp;<b>' . __('MySQLi', 'duplicator-pro') . "</b> <br/>";
        echo '<div class="scan-system-subnote">';
        esc_html_e('Creating the package does not require the mysqli module.  However the installer file requires that the PHP module mysqli be installed on the server it is deployed on.', 'duplicator-pro');
        echo "&nbsp;<i><a href='http://php.net/manual/en/mysqli.installation.php' target='_blank'>[" . __('details', 'duplicator-pro') . "]</a></i>";
        echo '</div>';

        //DROPBOX ONLY
        if ($Package->contains_storage_type(DropboxStorage::getSType())) {
            $transferMode = DynamicGlobalEntity::getInstance()->getVal('dropbox_transfer_mode');
            //OPENSSL
            echo '<hr size="1" /><span id="data-srv-php-openssl"></span>&nbsp;<b>' . __('Open SSL - Dropbox', 'duplicator-pro') . '</b>';
            echo '<div class="scan-system-subnote">';
            esc_html_e('Dropbox storage requires an HTTPS connection. On windows systems enable "extension=php_openssl.dll" in the php.ini configuration file.  ', 'duplicator-pro');
            esc_html_e('On Linux based systems check for the --with-openssl[=DIR] flag.', 'duplicator-pro');
            echo "&nbsp;<i><a href='http://php.net/manual/en/openssl.installation.php' target='_blank'>[" . __('details', 'duplicator-pro') . "]</a></i>";
            echo '</div>';

            if ($transferMode == DUP_PRO_Dropbox_Transfer_Mode::FOpen_URL) {
                //FOpen
                $test = DUP_PRO_Server::isURLFopenEnabled();
                echo '<hr size="1" /><span id="data-srv-php-allowurlfopen"></span>&nbsp;<b>' . __('Allow URL Fopen', 'duplicator-pro') . ":</b>&nbsp; '{$test}'<br/>";
                echo '<div class="scan-system-subnote">';
                esc_html_e('Dropbox communications requires that [allow_url_fopen] be set to 1 in the php.ini file.', 'duplicator-pro');
                echo "&nbsp;<i><a href='http://php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen' target='_blank'>[" . __('details', 'duplicator-pro') . "]</a></i><br/>";
                echo '</div>';
            } elseif ($transferMode == DUP_PRO_Dropbox_Transfer_Mode::cURL) {
                //FOpen
                $test = SnapUtil::isCurlEnabled() ? __('True', 'duplicator-pro') : __('False', 'duplicator-pro');
                echo '<hr size="1" /><span id="data-srv-php-curlavailable"></span>&nbsp;<b>' . __('cURL - Dropbox', 'duplicator-pro') . ":</b>&nbsp; '{$test}'<br/>";
                echo '<div class="scan-system-subnote">';
                esc_html_e('Dropbox communications requires that extension=php_curl.dll be present in the php.ini file.', 'duplicator-pro');
                echo "&nbsp;<i><a href='http://php.net/manual/en/curl.installation.php' target='_blank'>[" . __('details', 'duplicator-pro') . "]</a></i><br/>";
                echo '</div>';
            }
        }

        //DIVIDER
        echo "<div class='scan-system-divider margin-top-1'><i class='fa fa-list'></i>&nbsp;" . __('PHP Checks', 'duplicator-pro') . "</div>";

        //PHP VERSION
        echo '<span id="data-srv-php-version"></span>&nbsp;<b>' . __('PHP Version: ', 'duplicator-pro') . "</b>" . PHP_VERSION . " <br/>";
        echo '<div class="scan-system-subnote">';
        printf(
            esc_html__(
                'The minimum PHP version supported by Duplicator is %1$s, however it is highly recommended to use PHP %2$s or higher for improved stability.',
                'duplicator-pro'
            ),
            DUPLICATOR_PRO_PHP_MINIMUM_VERSION,
            DUPLICATOR_PRO_PHP_SUGGESTED_VERSION
        );
        echo "&nbsp;<i><a href='http://php.net/ChangeLog-5.php' target='_blank'>[" . __('details', 'duplicator-pro') . "]</a></i>";
        echo '</div>';

        //OPEN_BASEDIR
        $openBaseDir = ini_get("open_basedir");
        $test        = empty($openBaseDir) ? 'off' : 'on';
        echo '<hr size="1" /><span id="data-srv-php-openbase"></span>&nbsp;<b>' . __('PHP Open Base Dir', 'duplicator-pro') . ":</b>&nbsp; '{$test}' <br/>";
        echo '<div class="scan-system-subnote">';
        esc_html_e('Issues might occur when [open_basedir] is enabled. Work with your server admin or hosting provider to disable this value in the php.ini file if you’re having issues building a package.', 'duplicator-pro');
        echo "&nbsp;<i><a href='http://php.net/manual/en/ini.core.php#ini.open-basedir' target='_blank'>[" . __('details', 'duplicator-pro') . "]</a></i><br/>";
        echo '</div>';

        //MAX_EXECUTION_TIME
        $test = (set_time_limit(0)) ? 0 : ini_get("max_execution_time");
        echo '<hr size="1" /><span id="data-srv-php-maxtime"></span>&nbsp;<b>' . __('PHP Max Execution Time', 'duplicator-pro') . ":</b>&nbsp; '{$test}' <br/>";
        echo '<div class="scan-system-subnote">';
        printf(
            __(
                'Issues might occur for larger packages when the [max_execution_time] value in the php.ini is too low. 
                The minimum recommended timeout is "%1$s" seconds or higher. 
                An attempt is made to override this value if the server allows it. A value of 0 (recommended) indicates that PHP has no time limits.',
                'duplicator-pro'
            ),
            DUPLICATOR_PRO_SCAN_TIMEOUT
        );
        echo "&nbsp;<i><a href='http://www.php.net/manual/en/info.configuration.php#ini.max-execution-time' target='_blank'>[" . __('details', 'duplicator-pro') . "]</a></i>";
        echo '</div>';

        //MEMORY_LIMIT
        $test = @ini_get("memory_limit");
        echo '<hr size="1" /><span id="data-srv-php-minmemory"></span>&nbsp;<b>' . __('PHP Memory Limit', 'duplicator-pro') . ":</b>&nbsp; '{$test}' <br/>";
        echo '<div class="scan-system-subnote">';
        printf(
            __(
                'Issues might occur for larger packages when the [memory_limit] value in the php.ini is too low.  
                The minimum recommended memory limit is "%1$s" or higher. An attempt is made to override this value if the server allows it. 
                To manually increase the memory limit have a look at this %2$s[FAQ item]%3$s',
                'duplicator-pro'
            ),
            DUPLICATOR_PRO_MIN_MEMORY_LIMIT,
            "<i><a href='" . DUPLICATOR_PRO_DUPLICATOR_DOCS_URL . "how-to-manage-server-resources-cpu-memory-disk' target='_blank'>",
            "</a></i>"
        );
        echo '</div>';

        //PHP 32-bit
        $test = SnapUtil::getArchitectureString();
        echo '<hr size="1" /><span id="data-srv-php-arch64bit"></span>&nbsp;<b>' . __('PHP 64 Bit Architecture', 'duplicator-pro') . ":</b>&nbsp; '{$test}' <br/>";
        echo '<div class="scan-system-subnote">';
        printf(
            __(
                'Servers that run a PHP 32-bit architecture are not capable of creating packages larger than 2GB.   
                If you need to create a package that is larger than 2GB in size talk with your host or server admin to change your version of PHP to 64-bit. %1$s[FAQ item]%2$s',
                'duplicator-pro'
            ),
            "<i><a href='" . DUPLICATOR_PRO_DUPLICATOR_DOCS_URL . "how-to-resolve-file-io-related-build-issues' target='_blank'>",
            "</a></i>"
        );
        echo '</div>';

        ?><br/>
    </div>
</div>


<!-- ======================
WP SETTINGS -->
<div class="scan-item">
    <?php
    if (!$archive_export_onlydb && isset($_POST['filter-on'])) {
        $file_filter_data        = array(
            'filter-dir'   => DUP_PRO_Archive::parsePathFilter(SnapUtil::sanitizeNSChars($_POST['filter-paths'])),
            'filter-files' => DUP_PRO_Archive::parsePathFilter(SnapUtil::sanitizeNSChars($_POST['filter-paths'])),
        );
        $_SESSION['filter_data'] = $file_filter_data;
    } else {
        if (isset($_SESSION['filter_data'])) {
            unset($_SESSION['filter_data']);
        }
    }
    //TODO Login Need to go here

    $core_dir_included   = array();
    $core_files_included = array();
    //by default fault
    $core_dir_notice  = false;
    $core_file_notice = false;

    if (!$archive_export_onlydb && isset($_POST['filter-on']) && isset($_POST['filter-paths'])) {
        //findout matched core directories
        $filter_dirs =  DUP_PRO_Archive::parsePathFilter(SnapUtil::sanitizeNSChars($_POST['filter-paths']), true);

        // clean possible blank spaces before and after the paths
        for ($i = 0; $i < count($filter_dirs); $i++) {
            $filter_dirs[$i] = trim($filter_dirs[$i]);
            $filter_dirs[$i] = (substr($filter_dirs[$i], -1) == "/") ? substr($filter_dirs[$i], 0, strlen($filter_dirs[$i]) - 1) : $filter_dirs[$i];
        }
        $core_dir_included = array_intersect($filter_dirs, DUP_PRO_U::getWPCoreDirs());
        $core_dir_notice   = !empty($core_dir_included);


        //find out core files
        $filter_files = DUP_PRO_Archive::parsePathFilter(SnapUtil::sanitizeNSChars($_POST['filter-paths']), true);

        // clean possible blank spaces before and after the paths
        for ($i = 0; $i < count($filter_files); $i++) {
            $filter_files[$i] = trim($filter_files[$i]);
        }
        $core_files_included = array_intersect($filter_files, DUP_PRO_U::getWPCoreFiles());
        $core_file_notice    = !empty($core_files_included);
    }
    ?>
    <div class='title' onclick="DupPro.Pack.toggleScanItem(this);">
        <div class="text"><i class="fa fa-caret-right"></i> <?php esc_html_e('WordPress', 'duplicator-pro'); ?></div>
        <div id="data-srv-wp-all"></div>
    </div>
    <div class="info">
    <?php
    //VERSION CHECK
    echo '<span id="data-srv-wp-version"></span>&nbsp;<b>' . __('WordPress Version', 'duplicator-pro') . ":</b>&nbsp; '{$wp_version}' <br/>";
    echo '<div class="scan-system-subnote">';
    printf(
        __(
            'It is recommended to have a version of WordPress that is greater than %1$s. 
            Older version of WordPress can lead to migration issues and are a security risk.  
            If possible please update your WordPress site to the latest version.',
            'duplicator-pro'
        ),
        DUPLICATOR_PRO_SCAN_MIN_WP
    );
    echo '</div>';

    //CORE FILES
    echo '<hr size="1" /><span id="data-srv-wp-core"></span>&nbsp;<b>' . __('Core Files', 'duplicator-pro') . "</b> <br/>";

    $filter_text = "";
    if ($core_dir_notice) {
        echo '<div id="data-srv-wp-core-missing-dirs">';
        echo wp_kses(__("The core WordPress paths below will <u>not</u> be included in the archive. These paths are required for WordPress to function!", 'duplicator-pro'), array('u' => array()));
        echo "<br/>";
        foreach ($core_dir_included as $core_dir) {
            echo '&nbsp; &nbsp; <b><i class="fa fa-exclamation-circle scan-warn"></i>&nbsp;' . $core_dir . '</b><br/>';
        }
        echo '</small><br/>';
        echo '</div>';
        $filter_text = "directories";
    }

    if ($core_file_notice) {
        echo '<div id="data-srv-wp-core-missing-dirs">';
        echo wp_kses(__("The core WordPress file below will <u>not</u> be included in the archive. This file is required for WordPress to function!", 'duplicator-pro'), array('u' => array()));
        echo "<br/>";
        foreach ($core_files_included as $core_file) {
            echo '&nbsp; &nbsp; <b><i class="fa fa-exclamation-circle scan-warn"></i>&nbsp;' . $core_file . '</b><br/>';
        }
        echo '</div><br/>';
        $filter_text .= (strlen($filter_text) > 0) ? " and file" : "files";
    }

    if (strlen($filter_text) > 0) {
        echo '<div class="scan-system-subnote">';
        printf(
            __(
                'Note: Please change the %1$s filters if you wish to include the WordPress core files 
                otherwise the data will have to be manually copied to the new location for the site to function properly.',
                'duplicator-pro'
            ),
            $filter_text
        );
        echo '</div>';
    }


    if (!$core_dir_notice && !$core_file_notice) {
        echo '<div class="scan-system-subnote">';
        esc_html_e(
            "If the scanner is unable to locate the wp-config.php file in the root directory, then you will need to manually copy it to its new location. 
            This check will also look for core WordPress paths that should be included in the archive for WordPress to work correctly.",
            'duplicator-pro'
        );
        echo '</div>';
    }

    if (!is_multisite()) {
        //Normal Site
        echo '<hr size="1" /><span><div class="dup-scan-good"><i class="fa fa-check"></i></div></span>&nbsp;<b>' . __('Multisite: N/A', 'duplicator-pro') . "</b> <br/>";
        echo '<div class="scan-system-subnote">';
        esc_html_e('Multisite was not detected on this site. It is currently configured as a standard WordPress site.', 'duplicator-pro');
        echo "&nbsp;<i><a href='https://codex.wordpress.org/Create_A_Network' target='_blank'>[" . __('details', 'duplicator-pro') . "]</a></i>";
        echo '</div>';
    } elseif (License::can(License::CAPABILITY_MULTISITE_PLUS)) {
        //MU Gold
        echo '<hr size="1" /><span><div class="dup-scan-good"><i class="fa fa-check"></i></div></span>&nbsp;<b>' . __('Multisite: Detected', 'duplicator-pro') . "</b> <br/>";
        echo '<div class="scan-system-subnote">';
        esc_html_e('This license level has full access to all Multisite Plus+ features.', 'duplicator-pro');
        echo '</div>';
    } else {
        //MU Personal, Freelancer
        echo '<hr size="1" /><span><div class="dup-scan-warn"><i class="fa fa-exclamation-triangle fa-sm"></i></div></span>&nbsp;';
        echo '<b>' . __('Multisite: Detected', 'duplicator-pro') . "</b> <br/>";
        echo '<div class="scan-system-subnote">';
        printf(
            esc_html__(
                'Duplicator Pro is at the %1$s license level which allows for backups and migrations of an entire Multisite network.&nbsp;',
                'duplicator-pro'
            ),
            License::getLicenseToString()
        );
        echo '<br>';
        _e("To unlock all <b>Multisite Plus</b> features please upgrade the license before building a package.", 'duplicator-pro');
        echo '<br/>';
        echo "<a href='" . esc_url(License::getUpsellURL()) . "' target='_blank'>" . __('Upgrade Here', 'duplicator-pro') . "</a>&nbsp;|&nbsp;";
        echo "&nbsp;<a href='" . DUPLICATOR_PRO_DUPLICATOR_DOCS_URL . "how-does-duplicator-handle-multisite-support' target='_blank'>"
               . __('Multisite Plus Feature Overview', 'duplicator-pro') . "</a>";
        echo '</div>';
    }
    ?>
    </div>
</div>

<!-- ======================
Restore only package -->
<div id="migration-status-scan-item" class="scan-item">
    <div class='title' onclick="DupPro.Pack.toggleScanItem(this);">
        <div class="text"><i class="fa fa-caret-right"></i> <?php esc_html_e('Import Status', 'duplicator-pro');?></div>
        <div id="data-arc-status-migratepackage"></div>
    </div>
    <div class="info">
        <script id="hb-migrate-package-result" type="text/x-handlebars-template">
            <div class="container">
                <div class="data">
                    {{#if ARC.Status.PackageIsNotImportable}}
                        <hr>
                        <p>
                            <span class="maroon">
                            <?php esc_html_e("This package is not be compatible with", 'duplicator-pro'); ?>
                                <i data-tooltip-title="<?php esc_attr_e("Drag and Drop Import", 'duplicator-pro'); ?>"
                                   data-tooltip="<?php esc_html_e('The Drag and Drop import method is a new way to migrate packages you can find under Duplicator Pro > Tools > Import.', 'duplicator-pro'); ?>">
                                   <u><?php esc_html_e("Drag and Drop import", 'duplicator-pro'); ?></u>.&nbsp;
                                </i>
                                <?php esc_html_e("However it can still be used it to perform a database migration.", 'duplicator-pro'); ?>
                            </span>

                            {{#if ARC.Status.IsDBOnly}}
                                <?php
                                esc_attr_e(
                                    "Database only packages can only be installed via the installer.php file. 
                                    The Drag and Drop interface only processes packages that have all WordPress core directories and all database tables.",
                                    'duplicator-pro'
                                );
                                ?>
                            {{else}}
                                <?php esc_attr_e("To make the package compatible with Drag and Drop import don't filter any tables or core directories.", 'duplicator-pro'); ?>
                            {{/if}}
                        </p>
                        {{#if ARC.Status.HasFilteredCoreFolders}}
                        <p>
                            <b><?php esc_attr_e("FILTERED CORE DIRS:", 'duplicator-pro'); ?></b>
                        </p>
                        <ol>
                            {{#each ARC.FilteredCoreDirs as |dir|}}
                            <li>{{dir}} </li>
                            {{/each}}
                        </ol>
                        {{/if}}
                        {{#if ARC.Status.HasFilteredSiteTables}}
                            <b><?php esc_attr_e("FILTERED SITE TABLES:", 'duplicator-pro'); ?></b>
                            <div class="dup-scan-files-migrae-status">
                                <ol>
                                    {{#each DB.FilteredTables as |table|}}
                                    <li>{{table}} </li>
                                    {{/each}}
                                </ol>
                            </div>
                        {{/if}}
                    {{else}}
                        <?php esc_html_e("The package you are about to create is compatible with Drag and Drop import.", 'duplicator-pro'); ?>
                    {{/if}}
                </div>
            </div>
        </script>
        <div id="migrate-package-result"></div>
    </div>
</div>

<script>
(function ($)
{
    //Ints the various server data responses from the scan results
    DupPro.Pack.intServerData = function(data)
    {
        $('#data-srv-php-websrv').html(DupPro.Pack.setScanStatus(data.SRV.PHP.websrv));
        $('#data-srv-php-openbase').html(DupPro.Pack.setScanStatus(data.SRV.PHP.openbase));
        $('#data-srv-php-maxtime').html(DupPro.Pack.setScanStatus(data.SRV.PHP.maxtime));
        $('#data-srv-php-minmemory').html(DupPro.Pack.setScanStatus(data.SRV.PHP.minMemory));
        $('#data-srv-php-arch64bit').html(DupPro.Pack.setScanStatus(data.SRV.PHP.arch64bit));
        $('#data-srv-php-mysqli').html(DupPro.Pack.setScanStatus(data.SRV.PHP.mysqli));
        $('#data-srv-php-openssl').html(DupPro.Pack.setScanStatus(data.SRV.PHP.openssl));
        $('#data-srv-php-allowurlfopen').html(DupPro.Pack.setScanStatus(data.SRV.PHP.allowurlfopen));
        $('#data-srv-php-curlavailable').html(DupPro.Pack.setScanStatus(data.SRV.PHP.curlavailable));
        $('#data-srv-php-version').html(DupPro.Pack.setScanStatus(data.SRV.PHP.version));
        $('#data-srv-php-all').html(DupPro.Pack.setScanStatus(data.SRV.PHP.ALL));
        //Wordpress
        $('#data-srv-wp-version').html(DupPro.Pack.setScanStatus(data.SRV.WP.version));
        $('#data-srv-wp-core').html(DupPro.Pack.setScanStatus(data.SRV.WP.core));
        $('#data-srv-wp-all').html(DupPro.Pack.setScanStatus(data.SRV.WP.ALL));
    }
})(jQuery);
</script>

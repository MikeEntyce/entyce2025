<?php

/**
 * @package Duplicator
 */

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 * @var array<string, mixed> $tplData
 */

use Duplicator\Controllers\SettingsPageController;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Shell\Shell;
use Duplicator\Libs\Snap\SnapIO;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Utils\ZipArchiveExtended;

$global                = DUP_PRO_Global_Entity::getInstance();
$action_updated        = null;
$action_response       = __("Package Settings Saved", 'duplicator-pro');
$isZipArchiveAvailable = ZipArchiveExtended::isPhpZipAvailable();
$isShellZipAvailable   = (DUP_PRO_Zip_U::getShellExecZipPath() != null);
$is_shellexec_on       = Shell::test();

if (isset($_REQUEST['_package_mysqldump_path'])) {
    $mysqldump_exe_file                  = SnapUtil::sanitizeNSCharsNewlineTrim($_REQUEST['_package_mysqldump_path']);
    $mysqldump_exe_file                  = preg_match('/^([A-Za-z]\:)?[\/\\\\]/', $mysqldump_exe_file) ? $mysqldump_exe_file : '';
    $mysqldump_exe_file                  = preg_replace('/[\'";]/m', '', $mysqldump_exe_file);
    $_REQUEST['_package_mysqldump_path'] = SnapIO::safePathUntrailingslashit($mysqldump_exe_file);
}

$mysqlDumpPath     = DUP_PRO_DB::getMySqlDumpPath();
$mysqlDumpFound    = ($mysqlDumpPath) ? true : false;
$installerNameMode = $global->installer_name_mode;

?>

<form 
    id="dup-settings-form" class="dup-settings-pack-basic" 
    action="<?php echo esc_url(ControllersManager::getCurrentLink()); ?>" 
    method="post" data-parsley-validate
>
    <?php $tplData['actions'][SettingsPageController::ACTION_PACKAGE_BASIC_SAVE]->getActionNonceFileds(); ?>

    <!-- ===============================
    DATABASE -->
    <h3 class="title"><?php esc_html_e("Database", 'duplicator-pro') ?> </h3>
    <hr size="1" />
    <table class="form-table">
        <tr>
            <th scope="row"><label><?php esc_html_e("SQL Mode", 'duplicator-pro'); ?></label></th>
            <td>

                <div class="engine-radio <?php echo ($is_shellexec_on) ? '' : 'engine-radio-disabled'; ?>">
                    <input 
                        type="radio" name="_package_dbmode" value="mysql" id="package_mysqldump" 
                        <?php checked($global->package_mysqldump); ?>  onclick="DupPro.UI.SetDBEngineMode();" 
                    >
                    <label for="package_mysqldump"><?php esc_html_e("Mysqldump", 'duplicator-pro'); ?> </label> &nbsp; &nbsp; &nbsp;
                </div>

                <div class="engine-radio">
                    <input 
                        type="radio" name="_package_dbmode" id="package_phpdump" value="php" 
                        <?php checked(!$global->package_mysqldump); ?>  onclick="DupPro.UI.SetDBEngineMode();"  
                    >
                    <label for="package_phpdump"><?php esc_html_e("PHP Code", 'duplicator-pro'); ?></label>
                </div>

                <br style="clear:both"/><br/>

                <!-- SHELL EXEC  -->
                <div class="engine-sub-opts" id="dbengine-details-1" style="display:none">
                    <!-- MYSQLDUMP IN-ACTIVE -->
                    <?php if (!$is_shellexec_on) :
                        ?>
                        <div class="dup-feature-notfound">
                            <?php
                            esc_html_e(
                                'In order to use Mysqldump, one of the PHP functions has to be enabled: popen/pclose, exec or shell_exec.',
                                'duplicator-pro'
                            );
                            echo ' ';
                            esc_html_e('Please contact your host or server admin to enable one or more these functions.', 'duplicator-pro');
                            echo ' ';
                            printf(
                                esc_html_x(
                                    'For a list of approved providers that support these functions, %1$sclick here%2$s.',
                                    '%1$s and %2$s are the opening and closing tags of a link.',
                                    'duplicator-pro'
                                ),
                                '<a href="' . esc_url(DUPLICATOR_PRO_DUPLICATOR_DOCS_URL . 'what-host-providers-are-recommended-for-duplicator/')
                                . '" target="_blank">',
                                '</a>'
                            );
                            echo ' ';
                            esc_html_e('The "PHP Code" setting will be used until this issue is resolved by your hosting provider.', 'duplicator-pro');
                            ?>
                            <p>
                                <?php
                                esc_html_e('Below is a list of possible functions to activate to solve the problem.', 'duplicator-pro');
                                echo ' ';
                                esc_html_e('If the problem persists, look at the log for a more thorough analysis.', 'duplicator-pro');
                                ?>
                            </p>
                            <br/>
                            <b><?php esc_html_e('Disabled Functions:', 'duplicator-pro'); ?></b>
                            <code class="display-block margin-bottom-1">
                                <?php
                                foreach (['escapeshellarg', 'escapeshellcmd', 'extension_loaded', 'exec', 'popen', 'pclose', 'shell_exec'] as $func) {
                                    if (Shell::hasDisabledFunctions($func)) {
                                        echo esc_html($func);
                                        echo '<br>';
                                    }
                                }
                                ?>
                            </code>
                            <?php
                            printf(
                                esc_html_x(
                                    'FAQ: %1$sHow to enable disabled PHP functions.%2$s',
                                    '%1$s and %2$s are the opening and closing tags of a link.',
                                    'duplicator-pro'
                                ),
                                '<a href="' . esc_url(DUPLICATOR_PRO_DUPLICATOR_DOCS_URL . 'how-to-resolve-dependency-checks') . '" target="_blank">',
                                '</a>'
                            );
                            ?>
                        </div>
                        <!-- MYSQLDUMP ACTIVE -->
                        <?php
                    else :
                        $tipContent =  esc_attr__(
                            'Add a custom path if the path to mysqldump is not properly detected.   
                            For all paths use a forward slash as the path seperator.  
                            On Linux systems use mysqldump for Windows systems use mysqldump.exe.
                            If the path tried does not work please contact your hosting provider for details on the correct path.',
                            'duplicator-pro'
                        );
                        ?>
                        <label><?php esc_html_e("Current Path:", 'duplicator-pro'); ?></label>
                        <?php
                        SettingsPageController::getMySQLDumpMessage(
                            $mysqlDumpFound,
                            (!empty($mysqlDumpPath) ? $mysqlDumpPath : $global->package_mysqldump_path)
                        ); ?><br>
                        <label><?php esc_html_e("Custom Path:", 'duplicator-pro'); ?></label>
                        <input 
                            class="dup-wide-input" 
                            type="text" 
                            name="_package_mysqldump_path" 
                            id="_package_mysqldump_path" 
                            value="<?php echo esc_attr($global->package_mysqldump_path); ?>" 
                            placeholder="<?php esc_attr_e("/usr/bin/mypath/mysqldump", 'duplicator-pro'); ?>" 
                        >&nbsp;
                        <i class="fas fa-question-circle fa-sm"
                           data-tooltip-title="<?php esc_attr_e("mysqldump", 'duplicator-pro'); ?>"
                           data-tooltip="<?php echo esc_attr($tipContent); ?>">
                        </i><br>

                        <label><?php esc_html_e("Switch-Options:", 'duplicator-pro'); ?></label>
                        <div class="dup-group-option-wrapper">
                            <?php
                                $mysqldumpOptions = $global->getMysqldumpOptions();
                            foreach ($mysqldumpOptions as $key => $option) {
                                ?>
                                <div 
                                    class="dup-group-option-item"><input type="checkbox" name="<?php echo esc_attr($option->getInputName()); ?>" 
                                    id="<?php echo esc_attr($option->getInputName()); ?>"
                                    <?php checked($option->getEnabled()); ?>
                                >
                            --<?php echo esc_html($option->getOptionName()); ?></div>
                            <?php } ?>
                        </div>
                        <?php
                    endif; ?>
                </div>

                <!-- PHP OPTION -->
                <div class="engine-sub-opts" id="dbengine-details-2" style="display:none; line-height: 35px; margin-top:-5px">
                    <label><?php esc_html_e("Process Mode", 'duplicator-pro'); ?></label>
                    <select name="_phpdump_mode">
                        <option 
                            <?php selected($global->package_phpdump_mode, DUP_PRO_DB::PHPDUMP_MODE_MULTI); ?>
                            value="<?php echo (int) DUP_PRO_DB::PHPDUMP_MODE_MULTI; ?>"
                        >
                            <?php esc_html_e("Multi-Threaded", 'duplicator-pro'); ?>
                        </option>
                        <option 
                            <?php selected($global->package_phpdump_mode, DUP_PRO_DB::PHPDUMP_MODE_SINGLE); ?>
                            value="<?php echo (int) DUP_PRO_DB::PHPDUMP_MODE_SINGLE; ?>"
                        >
                            <?php esc_html_e("Single-Threaded", 'duplicator-pro'); ?>
                        </option>
                    </select>&nbsp;

                    <i style="margin-right:7px;" class="fas fa-question-circle fa-sm"
                       data-tooltip-title="<?php esc_attr_e("PHP Code Mode", 'duplicator-pro'); ?>"
                       data-tooltip="<?php
                        esc_attr_e(
                            'Single-Threaded mode attempts to create the entire database script in one request. 
                            Multi-Threaded mode allows the database script to be chunked over multiple requests.
                            Multi-Threaded mode is typically slower but much more reliable especially for larger databases.',
                            'duplicator-pro'
                        );
                        ?>"></i>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="_package_mysqldump_qrylimit"><?php esc_html_e("Query Size", 'duplicator-pro'); ?></label></th>
            <td>
                <select name="_package_mysqldump_qrylimit" id="_package_mysqldump_qrylimit" style="width:70px">
                    <?php
                    foreach (DUP_PRO_Constants::getMysqlDumpChunkSizes() as $value => $label) {
                        echo '<option ' . selected($global->package_mysqldump_qrylimit, $value, false) . ' value="' . (int) $value . '">'
                        . esc_html($label) . '</option>';
                    }
                    ?>
                </select>&nbsp;
                <?php $tipContent = __(
                    'A higher limit size will speed up the database build time, however it will use more memory.
                    If your host has memory caps start off low.',
                    'duplicator-pro'
                ); ?>
                <i style="margin-right:7px" class="fas fa-question-circle fa-sm"
                   data-tooltip-title="<?php esc_attr_e("MYSQL Query Limit Size", 'duplicator-pro'); ?>"
                   data-tooltip="<?php echo esc_attr($tipContent); ?>">
                </i>
            </td>
        </tr>                        
    </table>

    <!-- ===========================
    ARCHIVE -->
    <h3 class="title"><?php esc_html_e("Archive", 'duplicator-pro') ?> </h3>
    <hr size="1" />

    <!-- ===========================
    ARCHIVE ENGINE -->
    <table class="form-table" id="archive-build-manual">
        <tr>
            <th scope="row">
                <label><?php esc_html_e("Compression", 'duplicator-pro'); ?></label>
            </th>
            <td>
                <input type="radio" name="archive_compression" id="archive_compression_off" value="0" 
                    <?php checked($global->archive_compression, false); ?> />
                <label for="archive_compression_off"><?php esc_html_e("Off", 'duplicator-pro'); ?></label> &nbsp;
                <input type="radio" name="archive_compression"  id="archive_compression_on" value="1" 
                    <?php checked($global->archive_compression); ?>  />
                <label for="archive_compression_on"><?php esc_html_e("On", 'duplicator-pro'); ?></label>
                <?php $tipContent = __(
                    'This setting controls archive compression. The setting apply to all Archive Engine formats.
                    For ZipArchive this setting only works on PHP 7.0 or higher.',
                    'duplicator-pro'
                ); ?>&nbsp;
                <i style="margin-right:7px;" class="fas fa-question-circle fa-sm"
                   data-tooltip-title="<?php esc_attr_e("Archive Compression", 'duplicator-pro'); ?>"
                   data-tooltip="<?php echo esc_attr($tipContent); ?>">
                </i>
            </td>
        </tr>
        <tr>
            <th scope="row"><label><?php esc_html_e("Archive Engine", 'duplicator-pro'); ?></label></th>
            <td>
                <div class="engine-radio">
                    <input 
                        onclick="DupPro.UI.SetArchiveOptionStates();" 
                        type="radio" 
                        name="archive_build_mode" id="archive_build_mode3"  
                        value="<?php echo (int) DUP_PRO_Archive_Build_Mode::DupArchive; ?>" 
                        <?php checked($global->getBuildMode() == DUP_PRO_Archive_Build_Mode::DupArchive); ?> 
                        <?php disabled(!$global->isBuildModeAvailable(DUP_PRO_Archive_Build_Mode::DupArchive)) ?>
                    >
                    <label for="archive_build_mode3"><?php esc_html_e("DupArchive", 'duplicator-pro'); ?></label> &nbsp; &nbsp;
                </div>
                <div class="engine-radio <?php echo ($isShellZipAvailable) ? '' : 'engine-radio-disabled'; ?>">
                    <input 
                        onclick="DupPro.UI.SetArchiveOptionStates();" 
                        type="radio" 
                        name="archive_build_mode" 
                        id="archive_build_mode1"
                        value="<?php echo (int) DUP_PRO_Archive_Build_Mode::Shell_Exec; ?>"
                        <?php checked($global->getBuildMode() == DUP_PRO_Archive_Build_Mode::Shell_Exec); ?> 
                        <?php disabled(!$global->isBuildModeAvailable(DUP_PRO_Archive_Build_Mode::Shell_Exec)) ?>
                    >
                    <label for="archive_build_mode1"><?php esc_html_e("Shell Zip", 'duplicator-pro'); ?></label>
                </div>
                <div class="engine-radio">
                    <input 
                        onclick="DupPro.UI.SetArchiveOptionStates();" 
                        type="radio" 
                        name="archive_build_mode" 
                        id="archive_build_mode2"  
                        value="<?php echo (int) DUP_PRO_Archive_Build_Mode::ZipArchive; ?>" 
                        <?php checked($global->getBuildMode() == DUP_PRO_Archive_Build_Mode::ZipArchive); ?> 
                        <?php disabled(!$global->isBuildModeAvailable(DUP_PRO_Archive_Build_Mode::ZipArchive)) ?>
                    >
                    <label for="archive_build_mode2"><?php esc_html_e("ZipArchive", 'duplicator-pro'); ?></label>
                </div>

                <br style="clear:both"/>

                <!-- DUPARCHIVE -->
                <div class="engine-sub-opts" id="engine-details-3" style="display:none">
                    <?php
                    esc_html_e('This option creates a custom Duplicator Archive Format (.daf) archive file.', 'duplicator-pro');
                    echo '<br/>  ';
                    esc_html_e('This option is fully multi-threaded and recommended for large sites or throttled servers.', 'duplicator-pro');
                    echo '<br/>  ';
                    printf(
                        '%s <a href="' . esc_url(DUPLICATOR_PRO_DUPLICATOR_DOCS_URL . 'how-to-work-with-daf-files-and-the-duparchive-extraction-tool')
                        . '" target="_blank">%s</a> ',
                        esc_html__('For details on how to use and manually extract the DAF format please see the ', 'duplicator-pro'),
                        esc_html__('online documentation.', 'duplicator-pro')
                    );
                    ?>
                </div>

                <!-- SHELL EXEC  -->
                <div class="engine-sub-opts" id="engine-details-1" style="display:none">
                    <?php
                    TplMng::getInstance()->render(
                        'parts/settings/shellZipMessage',
                        ['hasShellZip' => $isShellZipAvailable]
                    );
                    ?>
                </div>

                <!-- ZIP ARCHIVE -->
                <div class="engine-sub-opts" id="engine-details-2" style="display:none;">
                    <label><?php esc_html_e("Process Mode", 'duplicator-pro'); ?></label>
                    <select  name="ziparchive_mode" id="ziparchive_mode"  onchange="DupPro.UI.setZipArchiveMode();">
                        <option <?php selected($global->ziparchive_mode, DUP_PRO_ZipArchive_Mode::Multithreaded); ?> 
                            value="<?php echo (int) DUP_PRO_ZipArchive_Mode::Multithreaded ?>">
                            <?php esc_html_e("Multi-Threaded", 'duplicator-pro'); ?>
                        </option>
                        <option <?php selected($global->ziparchive_mode == DUP_PRO_ZipArchive_Mode::SingleThread); ?> 
                            value="<?php echo (int) DUP_PRO_ZipArchive_Mode::SingleThread ?>">
                            <?php esc_html_e("Single-Threaded", 'duplicator-pro'); ?>
                        </option>
                    </select>&nbsp;
                    <i style="margin-right:7px;" class="fas fa-question-circle fa-sm"
                       data-tooltip-title="<?php esc_attr_e("PHP ZipArchive Mode", 'duplicator-pro'); ?>"
                       data-tooltip="<?php
                        esc_attr_e(
                            'Single-Threaded mode attempts to create the entire archive in one request.  
                            Multi-Threaded mode allows the archive to be chunked over multiple requests. 
                            Multi-Threaded mode is typically slower but much more reliable especially for larger sites.',
                            'duplicator-pro'
                        );
                        ?>"></i>

                    <div id="dpro-ziparchive-mode-st">
                        <input type="checkbox" id="ziparchive_validation" name="ziparchive_validation" 
                            <?php checked($global->ziparchive_validation); ?>
                        >
                        <label for="ziparchive_validation">Enable file validation</label>
                    </div>

                    <div id="dpro-ziparchive-mode-mt">
                        <label><?php esc_html_e("Buffer Size", 'duplicator-pro'); ?></label>
                        <input style="width:84px;" maxlength="4"
                               data-parsley-required data-parsley-errors-container="#ziparchive_chunk_size_error_container" 
                               data-parsley-min="5" data-parsley-type="number"
                               type="text" name="ziparchive_chunk_size_in_mb" id="ziparchive_chunk_size_in_mb" 
                               value="<?php echo (int) $global->ziparchive_chunk_size_in_mb; ?>" 
                        >
                               <?php esc_html_e('MB', 'duplicator-pro'); ?>
                        <?php
                        $toolTipContent = __(
                            'Buffer size only applies to multi-threaded requests and indicates how large an archive will get before a close is registered. 
                            Higher values are faster but can be more unstable based on the hosts max_execution time.',
                            'duplicator-pro'
                        );
                        ?>
                        <i style="margin-right:7px" class="fas fa-question-circle fa-sm"
                           data-tooltip-title="<?php esc_attr_e("PHP ZipArchive Buffer", 'duplicator-pro'); ?>"
                           data-tooltip="<?php echo esc_attr($toolTipContent); ?>"
                        >
                        </i>
                        <div id="ziparchive_chunk_size_error_container" class="duplicator-error-container"></div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- ===============================
    PROCESSING -->
    <h3 class="title"><?php esc_html_e("Processing", 'duplicator-pro') ?> </h3>
    <hr size="1" />
    <table class="form-table">
        <tr>
            <th scope="row"><label><?php esc_html_e("Server Throttle", 'duplicator-pro'); ?></label></th>
            <td>
                <input 
                    type="radio" name="server_load_reduction" id="server_load_reduction_off" 
                    value="<?php echo (int) DUP_PRO_Email_Build_Mode::No_Emails; ?>" 
                    <?php checked($global->server_load_reduction, DUP_PRO_Server_Load_Reduction::None); ?> 
                >
                <label for="server_load_reduction_off"><?php esc_html_e("Off", 'duplicator-pro'); ?></label> &nbsp;
                <input 
                    type="radio" name="server_load_reduction" id="server_load_reduction_low" 
                    value="<?php echo (int) DUP_PRO_Server_Load_Reduction::A_Bit; ?>" 
                    <?php checked($global->server_load_reduction, DUP_PRO_Server_Load_Reduction::A_Bit); ?> >
                <label for="server_load_reduction_low"><?php esc_html_e("Low", 'duplicator-pro'); ?></label> &nbsp;
                <input 
                    type="radio" name="server_load_reduction" id="server_load_reduction_medium" 
                    value="<?php echo (int) DUP_PRO_Server_Load_Reduction::More; ?>" 
                    <?php checked($global->server_load_reduction, DUP_PRO_Server_Load_Reduction::More); ?> 
                >
                <label for="server_load_reduction_medium"><?php esc_html_e("Medium", 'duplicator-pro'); ?></label> &nbsp;
                <input 
                    type="radio" name="server_load_reduction" id="server_load_reduction_high" 
                    value="<?php echo (int) DUP_PRO_Server_Load_Reduction::A_Lot ?>" 
                    <?php checked($global->server_load_reduction, DUP_PRO_Server_Load_Reduction::A_Lot); ?> 
                >
                <label for="server_load_reduction_high"><?php esc_html_e("High", 'duplicator-pro'); ?></label> &nbsp;
                <p class="description">
                    <?php esc_html_e(
                        "Throttle to prevent resource complaints on budget hosts. The higher the value the slower the backup.",
                        'duplicator-pro'
                    ); ?>
                </p>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label><?php esc_html_e("Max Build Time", 'duplicator-pro'); ?></label></th>
            <td>
                <input 
                    style="float:left;display:block;margin-right:6px;" 
                    data-parsley-required data-parsley-errors-container="#max_package_runtime_in_min_error_container" 
                    data-parsley-min="0" 
                    data-parsley-type="number" 
                    class="dup-narrow-input" 
                    type="text" 
                    name="max_package_runtime_in_min" 
                    id="max_package_runtime_in_min" 
                    value="<?php echo (int) $global->max_package_runtime_in_min; ?>" 
                >
                <p style="margin-left:4px;"><?php esc_html_e('Minutes', 'duplicator-pro'); ?></p>
                <div id="max_package_runtime_in_min_error_container" class="duplicator-error-container"></div>
                <p class="description">  
                    <?php esc_html_e('Max build and storage time until package is auto-cancelled. Set to 0 for no limit.', 'duplicator-pro'); ?>  
                </p>
            </td>
        </tr>
    </table>

    <!-- ===============================
    INSTALLER SETTINGS -->
    <h3 id="duplicator-pro-installer-settings" class="title"><?php esc_html_e("Installer Settings", 'duplicator-pro'); ?></h3>
    <hr size="1" />
    <table class="form-table">
        <tr>
            <th scope="row"><label><?php esc_html_e("Name", 'duplicator-pro'); ?></label></th>
            <td id="installer-name-mode-option" >
                <b><?php esc_html_e("Default 'Save as' name:", 'duplicator-pro'); ?></b> <br/>
                <label>
                    <i class='fas fa-lock lock-info fa-fw'></i>
                    <input type="radio" name="installer_name_mode"
                           value="<?php echo esc_attr(DUP_PRO_Global_Entity::INSTALLER_NAME_MODE_WITH_HASH); ?>"
                           <?php checked($installerNameMode === DUP_PRO_Global_Entity::INSTALLER_NAME_MODE_WITH_HASH); ?> />
                    [name]_[hash]_[date]_installer.php <i>(<?php esc_html_e("recommended", 'duplicator-pro'); ?>)</i>
                </label><br>
                <label>
                    <i class='fas fa-lock-open lock-info fa-fw'></i>
                    <input type="radio" name="installer_name_mode"
                           value="<?php echo esc_attr(DUP_PRO_Global_Entity::INSTALLER_NAME_MODE_SIMPLE); ?>"
                           <?php checked($installerNameMode === DUP_PRO_Global_Entity::INSTALLER_NAME_MODE_SIMPLE); ?> />
                           <?php echo esc_html(DUP_PRO_Installer::DEFAULT_INSTALLER_FILE_NAME_WITHOUT_HASH); ?>
                </label>
                <p class="description">
                    <?php esc_html_e("To understand the importance and usage of the installer name, please", 'duplicator-pro') ?>
                    <a href="javascript:void(0)" onclick="jQuery('#dup-pro-inst-mode-details').toggle()">
                        <?php esc_html_e("read this section", 'duplicator-pro') ?> 
                    </a>.
                </p>
                <div id="dup-pro-inst-mode-details">
                    <p>
                        <i>
                            <?php esc_html_e(
                                'Using the full hashed format provides a higher level of security by helping to prevent the discovery of the installer file.',
                                'duplicator-pro'
                            ); ?>
                        </i> <br/>
                        <b><?php esc_html_e('Hashed example', 'duplicator-pro'); ?>:</b>  my-name_64fc6df76c17f2023225_19990101010101_installer.php
                    </p>
                    <p>
                        <?php
                        esc_html_e(
                            'The Installer \'Name\' setting specifies the name of the installer used at download-time.
                            It\'s recommended you choose the hashed name to better protect the installer file.
                            Independent of the value of this setting, you can always change the name in the \'Save as\' file dialog at download-time. 
                            If you choose to use a custom name, use a filename that is known only to you. Installer filenames	must end in \'.php\'.',
                            'duplicator-pro'
                        );
                        ?>
                    </p>
                    <p>
                        <?php
                        esc_html_e(
                            'It\'s important not to leave the installer files on the destination server longer than necessary. 
                            After installing the migrated or restored site, just logon as a WordPress administrator and 
                            follow the prompts to have the plugin remove the files. 
                            Alternatively, you can remove them manually.',
                            'duplicator-pro'
                        );
                        ?>
                    </p>
                    <p>
                        <i class="fas fa-info-circle"></i>
                        <?php
                        esc_html_e(
                            'Tip: Each row on the packages screen includes a copy button that copies the installer name to the clipboard. 
                            After clicking this button, paste the installer name into the URL you\'re using to install the destination site. 
                            This feature is handy when using the hashed installer name.',
                            'duplicator-pro'
                        );
                        ?>
                    </p>
                </div>
            </td>
        </tr>
    </table>



    <!-- ===============================
    Installer Cleanup -->
    <h3 class="title"><?php esc_html_e("Installer Cleanup", 'duplicator-pro') ?> </h3>
    <hr size="1" />
    <table class="form-table">
        <tr>
            <th scope="row"><label><?php esc_html_e("Mode", 'duplicator-pro'); ?></label></th>
            <td>
                <input 
                    type="radio" 
                    name="cleanup_mode" 
                    id="cleanup_mode_Cleanup_Off" 
                    value="<?php echo (int) DUP_PRO_Global_Entity::CLEANUP_MODE_OFF; ?>" 
                    <?php checked($global->cleanup_mode, DUP_PRO_Global_Entity::CLEANUP_MODE_OFF); ?> 
                >
                <label for="cleanup_mode_Cleanup_Off"><?php esc_html_e("Off", 'duplicator-pro'); ?></label> &nbsp;
                <input 
                    type="radio" 
                    name="cleanup_mode" 
                    id="cleanup_mode_Email_Notice" 
                    value="<?php echo (int) DUP_PRO_Global_Entity::CLEANUP_MODE_MAIL; ?>" 
                    <?php checked($global->cleanup_mode, DUP_PRO_Global_Entity::CLEANUP_MODE_MAIL); ?> 
                >
                <label for="cleanup_mode_Email_Notice"><?php esc_html_e("Email Notice", 'duplicator-pro'); ?></label> &nbsp;
                <input 
                    type="radio" 
                    name="cleanup_mode" 
                    id="cleanup_mode_Auto_Cleanup" 
                    value="<?php echo (int) DUP_PRO_Global_Entity::CLEANUP_MODE_AUTO; ?>" 
                    <?php checked($global->cleanup_mode, DUP_PRO_Global_Entity::CLEANUP_MODE_AUTO); ?>
                >
                <label for="cleanup_mode_Auto_Cleanup"><?php esc_html_e("Auto Cleanup", 'duplicator-pro'); ?></label> &nbsp;
                <p class="description">
                    <?php esc_html_e("Email Notice: An email will be sent daily until the installer files are removed.", 'duplicator-pro'); ?>
                </p>
                <p class="description">
                    <?php esc_html_e("Auto Cleanup: Installer files will be cleaned up automatically based on setting below.", 'duplicator-pro'); ?>
                </p>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label><?php esc_html_e("Auto Cleanup", 'duplicator-pro'); ?></label></th>
            <td>
                <input 
                    data-parsley-required
                    data-parsley-errors-container="#auto_cleanup_hours_error_container"
                    data-parsley-min="1"
                    data-parsley-type="number"
                    class="dup-narrow-input"
                    type="text" 
                    name="auto_cleanup_hours" id="auto_cleanup_hours"
                    value="<?php echo (int) $global->auto_cleanup_hours; ?>"
                    size="7"/>
                <?php esc_html_e('Hours', 'duplicator-pro'); ?>
                <div id="auto_cleanup_hours_error_container" class="duplicator-error-container"></div>
                <p class="description">  <?php esc_html_e('Auto cleanup will run every N hours based on value above.', 'duplicator-pro'); ?>  </p>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label><?php esc_html_e("Email Address", 'duplicator-pro'); ?></label></th>
            <td>
                <input
                    data-parsley-errors-container="#cleanup_email_error_container"
                    data-parsley-type="email"
                    type="email"
                    name="cleanup_email" 
                    id="cleanup_email"
                    value="<?php echo esc_attr($global->cleanup_email); ?>"
                    size="75" />
                <p class="description"><?php esc_html_e('WordPress administration email address will be used if empty.', 'duplicator-pro'); ?></p>
                <div id="cleanup_email_error_container" class="duplicator-error-container"></div>
            </td>
        </tr>
    </table>

    <p class="submit dpro-save-submit">
        <input 
            type="submit" name="submit" id="submit" class="button-primary" 
            value="<?php esc_attr_e('Save Basic Package Settings', 'duplicator-pro') ?>" style="display: inline-block;" 
        >
    </p>
</form>

<script>
    jQuery(document).ready(function ($)
    {

        DupPro.UI.SetDBEngineMode = function ()
        {
            var isMysqlDump = $('#package_mysqldump').is(':checked');
            var isPHPMode = $('#package_phpdump').is(':checked');
            var isPHPChunkMode = $('#package_phpchunkingdump').is(':checked');

            $('#dbengine-details-1, #dbengine-details-2').hide();
            switch (true) {
                case isMysqlDump :
                    $('#dbengine-details-1').show();
                    break;
                case isPHPMode  :
                case isPHPChunkMode :
                    $('#dbengine-details-2').show();
                    break;
            }
        }

        DupPro.UI.setZipArchiveMode = function ()
        {
            $('#dpro-ziparchive-mode-st, #dpro-ziparchive-mode-mt').hide();
            if ($('#ziparchive_mode').val() == 0) {
                $('#dpro-ziparchive-mode-mt').show();
            } else {
                $('#dpro-ziparchive-mode-st').show();
            }
        }

        DupPro.UI.SetArchiveOptionStates = function ()
        {
            var php70 = <?php echo (version_compare(PHP_VERSION, '7', '>=') ? 'true' : 'false'); ?>;
            var isShellZipSelected = $('#archive_build_mode1').is(':checked');
            var isZipArchiveSelected = $('#archive_build_mode2').is(':checked');
            var isDupArchiveSelected = $('#archive_build_mode3').is(':checked');

            if (isShellZipSelected || isDupArchiveSelected) {
                $("[name='archive_compression']").prop('disabled', false);
                $("[name='ziparchive_mode']").prop('disabled', true);
            } else {
                $("[name='ziparchive_mode']").prop('disabled', false);
                if (php70) {
                    $("[name='archive_compression']").prop('disabled', false);
                } else {
                    $('#archive_compression_on').prop('checked', true);
                    $("[name='archive_compression']").prop('disabled', true);
                }
            }

            $('#engine-details-1, #engine-details-2, #engine-details-3').hide();
            switch (true) {
                case isShellZipSelected       :
                    $('#engine-details-1').show();
                    break;
                case isZipArchiveSelected   :
                    $('#engine-details-2').show();
                    break;
                case isDupArchiveSelected   :
                    $('#engine-details-3').show();
                    break;
            }
            DupPro.UI.setZipArchiveMode();
        }

        //INIT
        DupPro.UI.SetArchiveOptionStates();
        DupPro.UI.SetDBEngineMode();

        DupPro.UI.cleanupModeRadioSwitched = function() {
            if ($('#cleanup_mode_Cleanup_Off').is(":checked")){
                $('#auto_cleanup_hours').attr('readonly','readonly');
                $('#cleanup_email').attr('readonly','readonly');
            } else if ($('#cleanup_mode_Email_Notice').is(":checked")) {
                $('#auto_cleanup_hours').attr('readonly','readonly');
                $("#cleanup_email").removeAttr('readonly');
            } else if ($('#cleanup_mode_Auto_Cleanup').is(":checked")) {
                $("#auto_cleanup_hours").removeAttr('readonly');
                $("#cleanup_email").removeAttr('readonly');
            }
        }
        
        $('input[type=radio][name=cleanup_mode]').change(function () {
            DupPro.UI.cleanupModeRadioSwitched();
        });
        // We must call this also once in the beginning, after UI is loaded
        DupPro.UI.cleanupModeRadioSwitched();

    });
</script>

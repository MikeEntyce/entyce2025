<?php

/**
 * @package Duplicator
 */

use Duplicator\Controllers\StoragePageController;
use Duplicator\Core\CapMng;
use Duplicator\Libs\Snap\SnapJson;
use Duplicator\Libs\Snap\SnapWP;
use Duplicator\Models\Storages\AbstractStorageEntity;
use Duplicator\Models\Storages\StoragesUtil;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 * @var array<string, mixed> $tplData
 * @var bool $blur
 */

$blur = $tplData['blur'];
/** @var DUP_PRO_Package */
$package      = $tplData['package'];
$storage_list = AbstractStorageEntity::getAll(0, 0, [StoragesUtil::class, 'sortByPriority']);

$newStorageEditUrl = StoragePageController::getEditUrl();

$transfer_occurring = (($package->Status >= DUP_PRO_PackageStatus::STORAGE_PROCESSING) && ($package->Status < DUP_PRO_PackageStatus::COMPLETE));

$view_state          = DUP_PRO_UI_ViewState::getArray();
$ui_css_transfer_log = (isset($view_state['dup-transfer-transfer-log']) && $view_state['dup-transfer-transfer-log']) ? 'display:block' : 'display:none';

$installer_name = $package->Installer->getInstallerName();
$archive_name   = (isset($package->Archive->File)) ? $package->Archive->File : __('Unable to locate file', 'duplicator-pro');

$tplMng->render('admin_pages/packages/details/details_header');
?>
<div class="transfer-panel <?php echo ($blur ? 'dup-mock-blur' : ''); ?>">
    <div class="transfer-hdr">
        <h2 class="title">
            <i class="fas fa-exchange-alt"></i> <?php esc_html_e('Manual Transfer', 'duplicator-pro'); ?>
            <button id="dup-trans-ovr" type="button" class="dup-btn-borderless"
                    title="<?php esc_html_e('Show file details', 'duplicator-pro'); ?>"
                    onclick="DupPro.Pack.Transfer.toggleOverview()">
                <i class="fas fa-chevron-left fa-fw fa-sm"></i> Details
            </button>
        </h2>
        <hr/>
    </div>

    <!-- ===================
    OVERVIEW -->
    <div id="step1-ovr">
        <h3><?php esc_html_e('File Overview', 'duplicator-pro'); ?></h3>
        <small>
            <?php esc_html_e('These files will be transferred to the selected storage locations. Links are sensitive. Keep them safe!', 'duplicator-pro'); ?>
        </small>
        <label>
            <i class="far fa-file-archive fa-fw"></i>
            <b><?php esc_html_e('Archive File', 'duplicator-pro'); ?></b>
            <?php echo '&nbsp;(' . esc_html(DUP_PRO_U::byteSize($package->Archive->Size)) . ')'; ?><br/>
            <input type="text" value="<?php echo esc_attr($archive_name) ?>" readonly="readonly" />
            <span onclick="jQuery(this).parent().find('input').select();">
                <span class="copy-button" data-dup-copy-value="<?php echo esc_attr($archive_name); ?>">
                    <i class='far fa-copy dup-cursor-pointer'></i> <?php esc_html_e('Copy Name', 'duplicator-pro'); ?>
                </span>
            </span>
        </label>

        <label>
            <i class="fa fa-bolt fa-fw"></i>
            <b><?php esc_html_e('Archive Installer', 'duplicator-pro'); ?></b>
            <?php echo '&nbsp;(' . esc_html(DUP_PRO_U::byteSize($package->Installer->Size)) . ')'; ?><br/>
            <input type="text" value="<?php echo esc_attr($installer_name) ?>" readonly="readonly" />
            <span onclick="jQuery(this).parent().find('input').select();">
                <span class="copy-button" data-dup-copy-value="<?php echo esc_attr($installer_name); ?>">
                    <i class='far fa-copy dup-cursor-pointer'></i> <?php esc_html_e('Copy Name', 'duplicator-pro'); ?>
                </span>
            </span>
        </label>
    </div>

    <!-- ===================
    STEP 1 -->
    <div id="step2-section">
        <div style="margin:0px 0 0px 0">
            <h3><?php esc_html_e('Step 1: Choose Location', 'duplicator-pro') ?></h3>
            <input style="display:none" type="radio" name="location" id="location-storage" checked="checked" onclick="DupPro.Pack.Transfer.ToggleLocation()" />
            <label style="display:none" for="location-storage"><?php esc_html_e('Storage', 'duplicator-pro'); ?></label>
            <input style="display:none" type="radio" name="location" id="location-quick" onclick="DupPro.Pack.Transfer.ToggleLocation()" />
            <label style="display:none" for="location-quick"><?php esc_html_e('Quick FTP Connect', 'duplicator-pro'); ?></label>
        </div>

        <!-- STEP 1: STORAGE -->
        <table id="location-storage-opts" class="widefat">
            <thead>
                <tr>
                    <th style='white-space: nowrap; width:10px;'></th>
                    <th style='width:175px'><?php esc_html_e('Type', 'duplicator-pro') ?></th>
                    <th style='width:275px'><?php esc_html_e('Name', 'duplicator-pro') ?></th>
                    <th style="white-space: nowrap"><?php esc_html_e('Location', 'duplicator-pro') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i               = 0;
                $localStorageIds = array_map(function ($storage) {
                    return $storage->getId();
                }, $package->getLocalStorages());
                foreach ($storage_list as $storage) :
                    if ($storage->isDefault() || in_array($storage->getId(), $localStorageIds)) {
                        // Skip for default storage
                        continue;
                    }

                    $i++;

                    $is_valid       = $storage->isValid();
                    $mincheck       = ($i == 1) ? 'data-parsley-mincheck="1" data-parsley-required="true"' : '';
                    $row_style      = ($i % 2) ? 'alternate' : '';
                    $row_style     .= ($is_valid) ? '' : ' storage-missing';
                    $row_chkid      = "dup-chkbox-{$storage->getId()}";
                    $storageEditUrl = StoragePageController::getEditUrl($storage);

                    ?>
                        <tr class="package-row <?php echo esc_attr($row_style) ?>">
                            <td>
                                <input name="edit_id" type="hidden" value="<?php echo (int) $i ?>" />
                                <input class="duppro-storage-input"
                                    <?php disabled($is_valid == false); ?>
                                    id="<?php echo esc_attr($row_chkid); ?>"
                                    name="_storage_ids[]"
                                    data-parsley-errors-container="#storage_error_container" <?php echo esc_attr($mincheck); ?>
                                    type="checkbox"
                                    value="<?php echo (int) $storage->getId(); ?>" 
                                    <?php disabled($is_valid, false); ?>
                                >
                            </td>
                            <td>
                                <label for="<?php echo esc_attr($row_chkid); ?>" class="dup-store-lbl">
                                    <?php
                                    echo wp_kses(
                                        $storage::getStypeIcon(),
                                        [
                                            'i'   => [
                                                'class' => [],
                                            ],
                                            'img' => [
                                                'src'   => [],
                                                'class' => [],
                                                'alt'   => [],
                                            ],
                                        ]
                                    ),
                                    '&nbsp;',
                                    esc_html($storage->getStypeName());
                                    ?>
                                </label>
                            </td>
                            <td>
                                <a href="<?php echo esc_url($storageEditUrl); ?>" target="_blank">
                                <?php
                                echo ($is_valid == false) ? '<i class="fa fa-exclamation-triangle fa-sm"></i>' : '';
                                echo "&nbsp;" . esc_html($storage->getName());
                                ?>
                                </a>
                            </td>
                            <td>
                                <?php
                                echo wp_kses(
                                    $storage->getHtmlLocationLink(),
                                    [
                                        'a' => [
                                            'href'   => [],
                                            'target' => [],
                                        ],
                                    ]
                                );
                                ?>
                            </td>
                        </tr>
                    <?php
                endforeach; ?>

                <?php if ($i == 0) : ?>
                    <tr class="package-row">
                        <td colspan="4" style="text-align: center">- <?php esc_html_e('No Storage Items Found', 'duplicator-pro') ?> -</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tr class="dup-choose-loc-new-pack">
                <td colspan="4">
                    <?php if (CapMng::can(CapMng::CAP_STORAGE, false)) { ?>
                        <a href="<?php echo esc_url($newStorageEditUrl); ?>" target="_blank">
                            [<?php esc_html_e('Create New Storage', 'duplicator-pro') ?>]
                        </a>
                    <?php } else { ?>
                        &nbsp;
                    <?php } ?>
                </td>
            </tr>
        </table>

    </div>

    <!-- ===================
    STEP 2 -->
    <div id="step3-section">
        <h3>
            <?php esc_html_e('Step 2: Transfer Files', 'duplicator-pro') ?>
            <button style="<?php echo ($transfer_occurring ? 'none' : 'default'); ?>"
                      id="dup-pro-transfer-btn" type="button"
                      class="button button-large button-primary"
                      onclick="DupPro.Pack.Transfer.StartTransfer();">
                <?php esc_attr_e('Start Transfer', 'duplicator-pro') ?> &nbsp; <i class="fas fa-upload"></i>

            </button>
        </h3>

        <div style="width:700px; text-align: center; margin-left: auto; margin-right: auto" class="dpro-active-status-area">
            <div style="display:none; font-size:20px; font-weight:bold" id="dpro-progress-bar-percent"></div>
            <div style="font-size:14px" id="dpro-progress-bar-text"><?php esc_html_e('Processing', 'duplicator-pro') ?></div>
            <div id="dpro-progress-bar-percent-help">
                <small><?php esc_html_e('Full package percentage shown on packages screen', 'duplicator-pro'); ?></small>
            </div>
        </div>

        <div class="dpro-progress-bar-container">
            <div id="dpro-progress-bar-area" class="dpro-active-status-area">
                <div class="dup-pro-meter-wrapper">
                    <div class="dup-pro-meter blue dup-pro-fullsize">
                        <span></span>
                    </div>
                    <span class="text"></span>
                </div>
                <button disabled id="dup-pro-stop-transfer-btn" type="button" class="button button-large button-primarybutton dpro-btn-stop" value=""
                        onclick="DupPro.Pack.Transfer.StopBuild();">
                    <i class="fa fa-times fa-sm"></i> &nbsp; <?php esc_html_e('Stop Transfer', 'duplicator-pro'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- ===============================
    TRANSFER LOG -->
    <div class="dup-box">
        <div class="dup-box-title">
            <i class="fas fa-file-contract fa-fw fa-sm"></i>
            <?php esc_html_e('Transfer Log', 'duplicator-pro') ?>
            <button class="dup-box-arrow">
                <span class="screen-reader-text">
                    <?php esc_html_e('Toggle panel:', 'duplicator-pro') ?> <?php esc_html_e('Transfer Log', 'duplicator-pro') ?>
                </span>
            </button>
        </div>
        <div class="dup-box-panel" id="dup-transfer-transfer-log" style="<?php echo esc_attr($ui_css_transfer_log) ?>">
            <table class="widefat package-tbl">
                <thead>
                    <tr>
                        <th style='width:150px'><?php esc_html_e('Started', 'duplicator-pro') ?></th>
                        <th style='width:150px'><?php esc_html_e('Stopped', 'duplicator-pro') ?></th>
                        <th style="white-space: nowrap"><?php esc_html_e('Status', 'duplicator-pro') ?></th>
                        <th style="white-space: nowrap"><?php esc_html_e('Type', 'duplicator-pro') ?></th>
                        <th style="width: 60%; white-space: nowrap"><?php esc_html_e('Description', 'duplicator-pro') ?></th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" id="dup-pack-details-trans-log-count"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

<?php
$alert1          = new DUP_PRO_UI_Dialog();
$alert1->title   = __('Storage Warning!', 'duplicator-pro');
$alert1->message = __('At least one storage location must be checked.', 'duplicator-pro');
$alert1->initAlert();

$alert2          = new DUP_PRO_UI_Dialog();
$alert2->title   = __('Transfer Failure!', 'duplicator-pro');
$alert2->message = __('Transfer failure when calling duplicator_pro_manual_transfer_storage.', 'duplicator-pro');
$alert2->initAlert();

$alert3          = new DUP_PRO_UI_Dialog();
$alert3->title   = __('Build Error', 'duplicator-pro');
$alert3->message = __('Failed to stop build', 'duplicator-pro');
$alert3->initAlert();

$alert4          = new DUP_PRO_UI_Dialog();
$alert4->title   = $alert3->title;
$alert4->message = __('Failed to stop build due to ajax error.', 'duplicator-pro');
$alert4->initAlert();

$alert5          = new DUP_PRO_UI_Dialog();
$alert5->title   = 'INFO!';
$alert5->message = '';  // javascript inserted message
$alert5->initAlert();

$alert6          = new DUP_PRO_UI_Dialog();
$alert6->title   = 'INFO!';
$alert6->message = '';  // javascript inserted message
$alert6->initAlert();
?>
<script>
    DupPro.Pack.Transfer = {};
    jQuery(document).ready(function ($) {

        var transferRequestedTimestamp = 0;
        var activePackageId = -1;

        DupPro.Pack.Transfer.toggleOverview = function () {
            $('div#step1-ovr').toggle();
            var $i = $('#dup-trans-ovr i');

            if ($($i).hasClass('fa-chevron-left')) {
                $($i).removeClass('fa-chevron-left').addClass('fa-chevron-down');
            } else {
                $($i).removeClass('fa-chevron-down').addClass('fa-chevron-left');
            }
        }

        DupPro.Pack.Transfer.GetTimeStamp = function () {
            return Math.floor(Date.now() / 1000);
        }

        /*  METHOD: Starts the data transfer */
        DupPro.Pack.Transfer.StartTransfer = function () {

            if (jQuery('#location-storage-opts input[type=checkbox]:checked').length == 0) {
                <?php $alert1->showAlert(); ?>
            } else {
                $(".dpro-active-status-area").show(500);
                var selected_storage_ids = $.map($(':checkbox[name=_storage_ids\\[\\]]:checked'), function (n, i) {
                    return n.value;
                });
                var data = {
                    action: 'duplicator_pro_manual_transfer_storage',
                    package_id: <?php echo (int) $package->ID; ?>,
                    storage_ids: selected_storage_ids,
                    nonce: '<?php echo esc_js(wp_create_nonce('duplicator_pro_manual_transfer_storage')); ?>'
                }

                console.log("sending to selected storages " + selected_storage_ids);

                transferRequestedTimestamp = DupPro.Pack.Transfer.GetTimeStamp();

                $("#dpro-progress-bar-text").text("<?php echo esc_html__('Initiating transfer. Please wait.', 'duplicator-pro') ?>");
                $("#dpro-progress-bar-percent").text('');
                DupPro.Pack.Transfer.SetUIState(true);

                $.ajax({
                    type: "POST",
                    url: ajaxurl,
                    cache: false,
                    timeout: 10000000,
                    data: data,
                    success: function (respData) {
                        try {
                            var parsedData = DupPro.parseJSON(respData);
                        } catch (err) {
                            console.error(err);
                            console.error('JSON parse failed for response data: ' + respData);
                            <?php $alert2->showAlert(); ?>
                            transferRequestedTimestamp = 0;
                            DupPro.Pack.Transfer.SetUIState(false);
                            console.log(respData);
                            return false;
                        }
                        if (!parsedData.success)
                        {
                            if (parsedData.message != '') {
                                <?php $alert5->showAlert(); ?>
                                $("#<?php echo esc_js($alert5->getID()); ?>_message").html(parsedData.message);
                            }
                            transferRequestedTimestamp = 0;
                            DupPro.Pack.Transfer.SetUIState(false);
                            DupPro.Pack.Transfer.GetPackageState();
                        }
                    },
                    error: function (respData) {
                        <?php $alert2->showAlert(); ?>
                        transferRequestedTimestamp = 0;
                        DupPro.Pack.Transfer.SetUIState(false);
                        console.log(respData);
                    }
                });
            }
        };

        /*  METHOD: Starts the data transfer */
        DupPro.Pack.Transfer.StopBuild = function () {
            
            var data = {
                action: 'duplicator_pro_package_stop_build',
                package_id: activePackageId,
                nonce: '<?php echo esc_js(wp_create_nonce('duplicator_pro_package_stop_build')); ?>'
            }
            $("#dup-pro-stop-transfer-btn").prop("disabled", true);
            
            $.ajax({
                type: "POST",
                url: ajaxurl,
                timeout: 10000000,
                data: data,
                success: function (respData) {
                    try {
                        var parsedData = DupPro.parseJSON(respData);
                    } catch(err) {
                        console.error(err);
                        console.error('JSON parse failed for response data: ' + respData);
                        <?php $alert4->showAlert(); ?>
                        $("#dup-pro-stop-transfer-btn").prop("disabled", false);
                        return false;
                    }
                    if (!parsedData.success) {
                        <?php $alert3->showAlert(); ?>
                        $("#dup-pro-stop-transfer-btn").prop("disabled", false);
                    }
                    console.log(parsedData.message);
                },
                error: function (respData) {
                    <?php $alert4->showAlert(); ?>
                    $("#dup-pro-stop-transfer-btn").prop("disabled", false);
                }
            });
        };

        /*  METHOD: Progress bar display state*/
        DupPro.Pack.Transfer.SetUIState = function (activeProcessing) {
            if (activeProcessing)
            {
                $(".dpro-active-status-area").show(500);
                $("#dup-pro-transfer-btn").hide();
                $("#location-storage input").prop("disabled", true);
                $("#location-storage-opts input").prop("disabled", true);
            } else {
                $("#dup-pro-stop-transfer-btn").prop("disabled", true);
                // Only allow to revert after enough time has past since the last transfer request
                currentTimestamp = DupPro.Pack.Transfer.GetTimeStamp();
                if ((currentTimestamp - transferRequestedTimestamp) > 10)
                {
                    $("#location-storage input").prop("disabled", false);
                    $("#location-storage-opts input").prop("disabled", false);
                    $("#dup-pro-transfer-btn").show();
                    $(".dpro-active-status-area").hide();
                }
            }
        }

        /*  METHOD: Retreive package state */
        DupPro.Pack.Transfer.GetPackageState = function () {

            var package_id = <?php echo (int) $package->ID; ?>;
            var data = {
                action: 'duplicator_pro_packages_details_transfer_get_package_vm',
                package_id: package_id,
                nonce: '<?php echo esc_js(wp_create_nonce('duplicator_pro_packages_details_transfer_get_package_vm')); ?>'
            };

            $.ajax({
                type: "POST",
                url: ajaxurl,
                timeout: 10000000,
                data: data,
                success: function (respData) {
                    try {
                        var parsedData = DupPro.parseJSON(respData);
                    } catch (err) {
                        console.error(err);
                        console.error('JSON parse failed for response data: ' + respData);
                        console.log("Transfer failure.");
                        DupPro.Pack.Transfer.SetUIState(false);
                        console.log(respData);
                        return false;
                    }

                    console.log(parsedData);
                    if (parsedData.success)
                    {
                        var vm = parsedData.vm;

                        // vm - view model for this screen
                        // vm.active_package_id: Active package id (-1 for none)
                        // vm.percent_text: Percent through the current transfer
                        // vm.text: Text to display
                        // vm.transfer_logs: array of transfer request vms (start, stop, status, message)

                        if (activePackageId != vm.active_package_id)
                        {
                            // Once we have an active package ID allow the stop button to be clicked
                            $("#dup-pro-stop-transfer-btn").prop("disabled", false);
                        }

                        activePackageId = vm.active_package_id;
                        if (vm.active_package_id == -1)
                        {
                            // No packages are running
                            DupPro.Pack.Transfer.SetUIState(false);

                        } else if (vm.active_package_id == package_id) {

                            // This package is running
                            if (vm.percent_text != '')
                            {
                                $("#dpro-progress-bar-percent").text(vm.percent_text);
                            } else
                            {
                                $("#dpro-progress-bar-percent").text('');
                            }

                            $("#dpro-progress-bar-text").html(vm.text);
                            DupPro.Pack.Transfer.SetUIState(true);
                        } else {

                            // A package other than this one is running
                            $("#dpro-progress-bar-text").html(vm.text);
                            DupPro.Pack.Transfer.SetUIState(true);
                        }
                        DupPro.Pack.Transfer.UpdateTransferLog(vm);
                    } else {
                        if (parsedData.message != '') {
                            <?php $alert6->showAlert(); ?>
                            $("#<?php echo esc_js($alert6->getID()); ?>_message").html(parsedData.message);
                        }
                        DupPro.Pack.Transfer.SetUIState(false);
                        console.log(data);
                    }
                },
                error: function (data) {
                    console.log("Transfer failure.");
                    DupPro.Pack.Transfer.SetUIState(false);
                    console.log(data);
                }
            });
        };

        /*  METHOD: Updates the transfer log with the information from the view model */
        DupPro.Pack.Transfer.UpdateTransferLog = function (vm) {
            $("#dup-transfer-transfer-log table tbody").empty();
            var row_style, row_html;
            for (var i = 0; i < vm.transfer_logs.length; i++) {

                var transfer_log = vm.transfer_logs[i];
                console.log(transfer_log);
                
                row_style = (i % 2) ? ' alternate' : '';
                switch(transfer_log.status_text) {
                    case 'Pending':     row_style += ' status-pending';   break;
                    case 'Running':     row_style += ' status-running';   break;
                    case 'Failed':      row_style += ' status-failed';    break;
                    default:            row_style += ' status-normal';    break;
                }
                
                row_html =
                `<tr class="package-row ${row_style}">
                    <td>${transfer_log.started}</td>
                    <td>${transfer_log.stopped}</td>
                    <td>${transfer_log.status_text}</td>
                    <td>${transfer_log.storage_type_text}</td>
                    <td>${transfer_log.message}</td>
                </tr>`;

                $("#dup-transfer-transfer-log table tbody").append(row_html);
                $('#dup-pack-details-trans-log-count').html('<?php esc_html_e('Log Items:', 'duplicator-pro') ?> ' + (i + 1) );
            }

            if (i == 0)
            {
                var row_html = '<tr><td colspan="5" style="text-align:center">' + 
                    '<?php esc_html_e('- No transactions found for this package -', 'duplicator-pro'); ?></td></tr>';
                $("#dup-transfer-transfer-log table tbody").append(row_html);
            }
        };

        //INIT
        DupPro.Pack.Transfer.GetPackageState();
        setInterval(DupPro.Pack.Transfer.GetPackageState, 8000);
    });
</script>

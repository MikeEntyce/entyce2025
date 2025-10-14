<?php

/**
 * Duplicator package row in table packages list
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

defined("ABSPATH") or die("");

use Duplicator\Controllers\PackagesPageController;
use Duplicator\Controllers\StoragePageController;
use Duplicator\Controllers\ToolsPageController;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Libs\Snap\SnapJson;

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 * @var array<string, mixed> $tplData
 */

$perPage     = $tplData['perPage'];
$offset      = $tplData['offset'];
$currentPage = $tplData['currentPage'];

require_once DUPLICATOR____PATH . '/views/tools/recovery/widget/recovery-widget-scripts.php';

$transferBaseUrl   = PackagesPageController::getInstance()->getPackageTransferUrl();
$reloadPackagesURL = $ctrlMng->getCurrentLink(
    ['paged' => $currentPage]
);
?>

<!-- ==========================================
THICK-BOX DIALOGS: -->
<?php
/* ------------------------------------------
 * ALERT:  Remote > Storage items          */
$remoteDlg           = new DUP_PRO_UI_Dialog();
$remoteDlg->width    = 750;
$remoteDlg->height   = 475;
$remoteDlg->title    = __('Storage Locations', 'duplicator-pro');
$remoteDlg->message  = __('Loading Please Wait...', 'duplicator-pro');
$remoteDlg->boxClass = 'dup-packs-remote-store-dlg';
$remoteDlg->initAlert();

/* ------------------------------------------
 * ALERT:  Bulk action > no selection      */
$alert1           = new DUP_PRO_UI_Dialog();
$alert1->title    = __('Bulk Action Required', 'duplicator-pro');
$alert1->message  = '<i class="fa fa-exclamation-triangle fa-sm"></i>&nbsp;';
$alert1->message .= __('No selections made! Please select an action from the "Bulk Actions" drop down menu!', 'duplicator-pro');
$alert1->initAlert();

/* ------------------------------------------
 * ALERT:  Bulk action > no package selected  */
$alert2                      = new DUP_PRO_UI_Dialog();
$alert2->title               = __('Selection Required', 'duplicator-pro');
$alert2->wrapperClassButtons = 'dpro-dlg-nopackage-sel-bulk-action-btns';
$alert2->message             = '<i class="fa fa-exclamation-triangle fa-sm"></i>&nbsp;';
$alert2->message            .= __('No selections made! Please select at least one package to delete!', 'duplicator-pro');
$alert2->initAlert();

/* ------------------------------------------
 * ALERT: Process > Error undefined        */
$alert4          = new DUP_PRO_UI_Dialog();
$alert4->title   = __('ERROR!', 'duplicator-pro');
$alert4->message = __('Got an error or a warning: undefined', 'duplicator-pro');
$alert4->initAlert();

/* ------------------------------------------
 * ALERT: Process > Error no details       */
$alert5          = new DUP_PRO_UI_Dialog();
$alert5->title   = $alert4->title;
$alert5->message = __('Failed to get details.', 'duplicator-pro');
$alert5->initAlert();

/* ------------------------------------------
 * ALERT: Download > No storage items        */
$alert6          = new DUP_PRO_UI_Dialog();
$alert6->height  = 350;
$alert6->width   = 600;
$alert6->title   = __('Download Status', 'duplicator-pro');
$alert6->message = sprintf(
    '%s <br/><br/> <i class="fas fa-server fa-xs"></i>&nbsp;<b>%s:</b> %s <br/><br/> '
             . '<i class="far fa-hdd"></i>&nbsp;<b>%s:</b> %s <br/><br/> <small><i>%s</i></small><br/>',
    __('No package files found at the \'Default\' storage location on this server.', 'duplicator-pro'),
    __('Remote', 'duplicator-pro'),
    __('For packages stored remotely check the remote storage button next to the download button.', 'duplicator-pro'),
    __('Local', 'duplicator-pro'),
    __(
        'To enable the direct download button be sure the local \'Default\' or a non-default, but \'Local\' storage type is enabled when creating a package.',
        'duplicator-pro'
    ),
    __(
        "Note: If the Storage &#10095; Default &#10095; 'Max Packages' is set then packages will be removed but the entry will still be visible on the packages screen.", // phpcs:ignore Generic.Files.LineLength
        'duplicator-pro'
    )
);
$alert6->initAlert();

/* ------------------------------------------
 * CONFIRM: Delete packages?               */
$confirm1                      = new DUP_PRO_UI_Dialog();
$confirm1->height              = 280;
$confirm1->title               = __('Delete Packages?', 'duplicator-pro');
$confirm1->wrapperClassButtons = 'dpro-dlg-detete-packages-btns';
$confirm1->message             = __('Are you sure you want to delete the selected package(s)?', 'duplicator-pro');
$confirm1->message            .= '<br/><br/>';
$confirm1->message            .= '<small><i>' . __(
    'Note: This action removes only packages located on this server. If a remote package was created then it will not be removed or affected.',
    'duplicator-pro'
) . '</i></small>';
$confirm1->progressText        = __('Removing Packages, Please Wait...', 'duplicator-pro');
$confirm1->jsCallback          = 'DupPro.Pack.Delete()';
$confirm1->initConfirm();

/* ------------------------------------------
 * ALERT: Recovery > toolbar button        */
$toolBarRecoveryButtonInfo              = new DUP_PRO_UI_Dialog();
$toolBarRecoveryButtonInfo->showButtons = false;
$toolBarRecoveryButtonInfo->height      = 600;
$toolBarRecoveryButtonInfo->width       = 600;
$toolBarRecoveryButtonInfo->title       = __('Disaster Recovery', 'duplicator-pro');
$toolBarRecoveryButtonInfo->message     = $tplMng->render('admin_pages/packages/recovery_info/info', array(), false);
$toolBarRecoveryButtonInfo->initAlert();

/* ------------------------------------------
 * ALERT: Recovery                         */
$availableRecoveryBox              = new DUP_PRO_UI_Dialog();
$availableRecoveryBox->title       = __('Disaster Recovery Available', 'duplicator-pro');
$availableRecoveryBox->boxClass    = 'dup-recovery-box-info';
$availableRecoveryBox->showButtons = false;
$availableRecoveryBox->width       = 600;
$availableRecoveryBox->height      = 400;
$availableRecoveryBox->message     = '';
$availableRecoveryBox->initAlert();

$unavailableRecoveryBox              = new DUP_PRO_UI_Dialog();
$unavailableRecoveryBox->title       = __('Disaster Recovery Unavailable', 'duplicator-pro');
$unavailableRecoveryBox->boxClass    = 'dup-recovery-box-info';
$unavailableRecoveryBox->showButtons = false;
$unavailableRecoveryBox->width       = 600;
$unavailableRecoveryBox->height      = 700;
$unavailableRecoveryBox->message     = '';
$unavailableRecoveryBox->initAlert();

/* ------------------------------------------
 * ALERT: Package overeview > Help   */
$linkInfoDlg          = new DUP_PRO_UI_Dialog();
$linkInfoDlg->width   = 700;
$linkInfoDlg->height  = 550;
$linkInfoDlg->title   = __('Duplicator Pro Tutorial', 'duplicator-pro');
$linkInfoDlg->message = $tplMng->render('admin_pages/packages/packages_overview_help', array(), false);
$linkInfoDlg->initAlert();

$baseStorageEditURL = StoragePageController::getInstance()->getMenuLink(
    null,
    null,
    [
        ControllersManager::QUERY_STRING_INNER_PAGE => StoragePageController::INNER_PAGE_EDIT,
    ]
);
?>
<script>
jQuery(document).ready(function($) {
    
    DupPro.Pack.RestorePackageId = null;
    DupPro.PackagesTable = $('.dup-packtbl');
     
    /**
     * Click event to expands each row and show package details
     *
     * @returns void
     */
    $('th#dup-header-chkall').on('click', function() {
        var $this = $(this);
        var $icon = $this.find('i');
        if ($icon.hasClass('fa-chevron-left')) {
            $icon.attr('class', 'fas fa-chevron-down');
            $("tr.dup-row-complete").each(function() {
                $icon = $(this).find('td.dup-cell-toggle-btn i');
                $icon.attr('class', 'fas fa-chevron-down');
                $(this).next('tr').show();
            });
        } else {
            $icon.attr('class', 'fas fa-chevron-left');
            $("tr.dup-row-complete").each(function() {
                $icon = $(this).find('td.dup-cell-toggle-btn i');
                $icon.attr('class', 'fas fa-chevron-left');
            });
            $('tr.dup-row-details').hide();
        }
    });

    /**
     * Click event to expands each row and show package details
     *
     * @returns void
     */
    $('td.dup-cell-toggle-btn').on('click', function(e) {
        var $this = $(this);
        var $icon  = $this.find('i');
        if ($icon.hasClass('fa-chevron-left')) {
            $icon.attr('class', 'fas fa-chevron-down');
            $(this).parent().next('tr').show();
        } else {
            $icon.attr('class', 'fas fa-chevron-left');
            $(this).parent().next('tr').hide();
        }
    });

    $('.dup-pro-quick-fix-notice').on('click', '.dup-pro-quick-fix', function() {
        var $this = $(this),
            params = JSON.parse($this.attr('data-param')),
            toggle = $this.attr('data-toggle'),
            id = $this.attr('data-id'),
            fix = $(toggle),
            button = {
                loading: function() {
                    $this.prop('disabled', true)
                        .addClass('disabled')
                        .html('<i class="fas fa-circle-notch fa-spin fa-fw"></i> <?php esc_html_e('Please Wait...', 'duplicator-pro') ?>');
                },
                reset: function() {
                    $this.prop('disabled', false)
                        .removeClass('disabled')
                        .html("<i class='fa fa-wrench' aria-hidden='true'></i>&nbsp; <?php esc_html_e('Resolve This', 'duplicator-pro') ?>");
                }
            },
            error = {
                message: function(text) {
                    fix.append(
                        "&nbsp; <span style='color:#cc0000' id='" + 
                        toggle.replace('#', '') + 
                        "-error'><i class='fa fa-exclamation-triangle'></i>&nbsp; " + text + "</span>"
                    );
                },
                remove: function() {
                    if ($(toggle + "-error"))
                        $(toggle + "-error").remove();
                }
            };

        error.remove();
        button.loading();

        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                action: 'duplicator_pro_quick_fix',
                setup: params,
                id: id,
                nonce: '<?php echo esc_js(wp_create_nonce('duplicator_pro_quick_fix')); ?>'
            }
        }).done(function(respData, x) {
            try {
                var parsedData = DupPro.parseJSON(respData);
            } catch (err) {
                console.error(err);
                console.error('JSON parse failed for response data: ' + respData);

                button.reset();
                error.message('<?php esc_html_e('Unexpected Error!', 'duplicator-pro') ?>');
                console.log(respData);
                console.log(x);
                return false;
            }

            console.log(parsedData);
            if (parsedData.success) {
                fix.remove();

                // If there is no fixes and notifications - remove container
                if (typeof parsedData.recommended_fixes != 'undefined') {
                    if (parsedData.recommended_fixes == 0) {
                        $('.dup-pro-quick-fix-notice').remove();
                    }
                }
            } else {
                button.reset();
                error.message(parsedData.message);
            }
        }).fail(function(data, x) {
            button.reset();
            error.message('<?php esc_html_e('Unexpected Error!', 'duplicator-pro') ?>');
            console.log(data);
            console.log(x);
        });
    });

    DupPro.Pack.DownloadNotice = function() {
        <?php $alert6->showAlert(); ?>
        return false;
    };

    $('.dpro-toolbar-recovery-info').click(function () {
        if ($(this).hasClass('dup-recovery-unset')) {
            <?php $toolBarRecoveryButtonInfo->showAlert(); ?>
        } else {
            let openUrl = <?php echo json_encode($ctrlMng->getMenuLink($ctrlMng::TOOLS_SUBMENU_SLUG, ToolsPageController::L2_SLUG_RECOVERY)); ?>;
            window.open(openUrl,"_self");
        }
    });

    //DOWNLOAD MENU
    $('button.dup-dnload-btn').click(function(e) {
        var $menu = $(this).parent().find('nav.dup-dnload-menu-items');

        if ($menu.is(':visible')) {
            $menu.hide();
        } else {
            $('nav.dup-dnload-menu-items').hide();
            $menu.show(200);
        }
        return false;
    });

    $(document).click(function(e) {
        var className = e.target.className;
        if (className != 'dpro-menu-x') {
            $('nav.dup-dnload-menu-items').hide();
        }
    });

    $("nav.dup-dnload-menu-items button").each(function() {
        $(this).addClass('dpro-menu-x');
    });
    $("nav.dup-dnload-menu-items button span").each(function() {
        $(this).addClass('dpro-menu-x');
    });

    /*  Creats a comma seperate list of all selected package ids  */
    DupPro.Pack.GetDeleteList = function() {
        var arr = [];
        $("input[name=delete_confirm]:checked").each(function() {
            arr.push(this.id);
        });
        return arr;
    }

    DupPro.Pack.openLinkDetails = function() {
          <?php $linkInfoDlg->showAlert(); ?>
    }

    DupPro.Pack.BackupRestore = function() {
        Duplicator.Util.ajaxWrapper({
                action: 'duplicator_pro_restore_backup_prepare',
                packageId: DupPro.Pack.RestorePackageId,
                nonce: '<?php echo esc_js(wp_create_nonce('duplicator_pro_restore_backup_prepare')); ?>'
            },
            function(result, data, funcData, textStatus, jqXHR) {
                window.location.href = data.funcData;
            },
            function(result, data, funcData, textStatus, jqXHR) {
                alert('FAIL');
            }
        );
    };

    /*  Provides the correct confirmation items when deleting packages */
    DupPro.Pack.ConfirmDelete = function() {
        $('#dpro-dlg-confirm-delete-btns input').removeAttr('disabled');
        if ($("#dup-pack-bulk-actions").val() != "delete") {
            <?php $alert1->showAlert(); ?>
            return;
        }

        var list = DupPro.Pack.GetDeleteList();
        if (list.length == 0) {
            <?php $alert2->showAlert(); ?>
            return;
        }
        <?php $confirm1->showConfirm(); ?>
    }

    /*  Removes all selected package sets with ajax call  */
    DupPro.Pack.Delete = function() {
        var packageIds = DupPro.Pack.GetDeleteList();
        var pageCount = $('#current-page-selector').val();
        var pageItems = $('input[name="delete_confirm"]');
        var data = {
            action: 'duplicator_pro_package_delete',
            package_ids: packageIds,
            nonce: '<?php echo esc_js(wp_create_nonce('duplicator_pro_package_delete')); ?>'
        };
        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: data,
            success: function(respData) {
                try {
                    var parsedData = DupPro.parseJSON(respData);
                } catch (err) {
                    console.error(err);
                    console.error('JSON parse failed for response data: ' + respData);
                    alert('Failed to delete package with AJAX resp: ' + respData);
                    return false;
                }

                if (parsedData.error.length > 0) {
                    alert("Ajax error: " + parsedData.error);
                    return false;
                }

                //Increment back a page-set if no items are left
                if ($('#form-duplicator-nav').length) {
                    if (pageItems.length == packageIds.length)
                        $('#current-page-selector').val(pageCount - 1);
                    $('#form-duplicator-nav').submit();
                } else {
                    $('#form-duplicator').submit();
                }
            }
        });
    }

    /* Toogles the Bulk Action Check boxes */
    DupPro.Pack.SetDeleteAll = function() {
        var state = $('input#dup-chk-all').is(':checked') ? 1 : 0;
        $("input[name=delete_confirm]").each(function() {
            this.checked = (state) ? true : false;
        });
    }

    /* Stops the build from running */
    DupPro.Pack.StopBuild = function(packageID) {
        $('#action').val('stop-build');
        $('#action-parameter').val(packageID);
        $('#form-duplicator').submit();
        
        $('.dup-build-stop-btn').html('<?php esc_html_e("Cancelling...", 'duplicator-pro'); ?>');
        $('.dup-build-stop-btn').prop('disabled', true);
    }

    /*  Redirects to the packages detail screen using the package id */
    DupPro.Pack.OpenPackTransfer = function(id) {
        window.location.href = '<?php echo esc_url_raw(SnapJson::jsonEncode($transferBaseUrl)) ?>' + '&id=' + id;
    }

    /* Shows remote storage location dialogs */
    DupPro.Pack.ShowRemote = function(package_id, name) {
        <?php $remoteDlg->showAlert(); ?>
        
        Duplicator.Util.ajaxWrapper(
            {
                action: 'duplicator_pro_get_storage_details',
                package_id: package_id,
                nonce: '<?php echo esc_js(wp_create_nonce('duplicator_pro_get_storage_details')); ?>'
            },
            function (result, data, funcData, textStatus, jqXHR) {
                if (!funcData.success) {
                    var text = "<?php esc_html_e('Got an error or a warning', 'duplicator-pro'); ?>: " + funcData.message;
                    $('#TB_window .dpro-dlg-alert-txt').html(text);
                    return false;
                }

                var info = '<div class="dup-dlg-store-remote">';
                for (storage_provider_key in funcData.storage_providers) {
                    var store = funcData.storage_providers[storage_provider_key];
                    info += store.infoHTML;
                }
                info += '</div>';
                info += "<a href='" + funcData.logURL + "' class='dup-dlg-store-log-link' target='_blank'>" + 
                    '<?php echo esc_html__('[Package Build Log]', 'duplicator-pro'); ?>' + "</a>";
                $('#TB_window .dpro-dlg-alert-txt').html(info);
            },
            function(data) {
                <?php $alert5->showAlert(); ?>
                console.log(data);
                return '';
            }
        );
        
        return false;
    };

    $('.dup-restore-backup').click(function(event) {
        event.preventDefault();

        let packageId = $(this).data('package-id');
        Duplicator.Util.ajaxWrapper(
            {
                action: 'duplicator_pro_backup_redirect',
                packageId: packageId,
                nonce: '<?php echo esc_js(wp_create_nonce('duplicator_pro_backup_redirect')); ?>'
            },
            function (result, data, funcData, textStatus, jqXHR) {
                if (funcData.success) {
                    let box = new DuplicatorModalBox({
                        url: data.funcData.redirect_url, 
                        openCallback: function (iframe, modalObj) {
                            let body = $(iframe.contentWindow.document.body);
                            // For old packages
                            body.find("#content").css('background-color', 'white');

                            body.on( "click", "#s1-deploy-btn", function() {
                                modalObj.disableClose();
                            });
                        }
                    });
                    box.open();
                    //window.location.href = data.funcData.redirect_url;
                } else {
                    DupPro.addAdminMessage(funcData.message, 'error');
                }
                return '';        
            },
            function(data) {
                <?php $alert5->showAlert(); ?>
                console.log(data);
                return '';
            }
        );
        
        return false;
    });


    /*  Virtual states that UI uses for easier tracking of the three general states a package can be in*/
    DupPro.Pack.ProcessingStats = {
        PendingCancellation: -3,
        Pending: 0,
        Building: 1,
        Storing: 2,
        Finished: 3,
    }

    DupPro.Pack.setIntervalID = -1;

    DupPro.Pack.SetUpdateInterval = function(period) {
        if (DupPro.Pack.setIntervalID != -1) {
            clearInterval(DupPro.Pack.setIntervalID);
            DupPro.Pack.setIntervalID = -1
        }
        DupPro.Pack.setIntervalID = setInterval(DupPro.Pack.UpdateUnfinishedPackages, period * 1000);
    }

    DupPro.Pack.UpdateUnfinishedPackages = function() {
        let packagesTables = $('.dup-packtbl');

        var data = {
            action: 'duplicator_pro_get_package_statii',
            nonce: '<?php echo esc_js(wp_create_nonce('duplicator_pro_get_package_statii')); ?>',
            offset: <?php echo (int) $offset; ?>,
            limit: <?php echo (int) $perPage; ?>,
        }

        $.ajax({
            type: "POST",
            url: ajaxurl,
            dataType: "text",
            timeout: 10000000,
            data: data,
            complete: function() {},
            success: function(respData) {
                try {
                    var data = DupPro.parseJSON(respData);
                } catch (err) {
                    // console.error(err);
                    console.error('JSON parse failed for response data: ' + respData);
                    DupPro.Pack.SetUpdateInterval(60);
                    console.log(respData);
                    return false;
                }

                let currentFirstPackageId = -1;
                let statiiFistPackageId = -1;
                if (packagesTables.find('.dup-row').length) {
                    currentFirstPackageId = packagesTables.find('.dup-row').first().data('package-id');
                }
                if (data.length) {
                    statiiFistPackageId = data[0].ID;
                }
                if (currentFirstPackageId != statiiFistPackageId) {
                    window.location = <?php echo SnapJson::jsonEncode($reloadPackagesURL); ?>;
                }


                var activePackagePresent = false;

                for (package_info_key in data) {
                    var package_info = data[package_info_key];
                    var statusSelector = '#status-' + package_info.ID;
                    var packageRowSelector = '#dup-row-pack-id-' + package_info.ID;
                    var packageSizeSelector = packageRowSelector + ' .dup-size-column';
                    var current_value_string = $(statusSelector).text();
                    var current_value = parseInt(current_value_string);
                    var currentProcessingState;

                    if (current_value == -3) {
                        currentProcessingState = DupPro.Pack.ProcessingStats.PendingCancellation;
                    } else if (current_value == 0) {
                        currentProcessingState = DupPro.Pack.ProcessingStats.Pending;
                    } else if ((current_value >= 0) && (current_value < 75)) {
                        currentProcessingState = DupPro.Pack.ProcessingStats.Building;
                    } else if ((current_value >= 75) && (current_value < 100)) {
                        currentProcessingState = DupPro.Pack.ProcessingStats.Storing;
                    } else {
                        // Has to be negative(error) or 100 - both mean complete
                        currentProcessingState = DupPro.Pack.ProcessingStats.Finished;
                    }
                    if (currentProcessingState == DupPro.Pack.ProcessingStats.Pending) {
                        if (package_info.status != 0) {
                            window.location = window.location.href;
                        }
                    } else if (currentProcessingState == DupPro.Pack.ProcessingStats.Building) {
                        if ((package_info.status >= 75) || (package_info.status < 0)) {
                            // Transitioned to storing so refresh
                            window.location = window.location.href;
                            break;
                        } else {

                            activePackagePresent = true;
                            $(statusSelector).text(package_info.status);
                            $(packageSizeSelector).hide().fadeIn(1000).text(package_info.size);
                        }
                    } else if (currentProcessingState == DupPro.Pack.ProcessingStats.Storing) {
                        if ((package_info.status == 100) || (package_info.status < 0)) {
                            // Transitioned to storing so refresh
                            window.location = window.location.href;
                            break;
                        } else {
                            activePackagePresent = true;
                            $('#dpro-progress-status-message-transfer-msg').html(package_info.status_progress_text);
                            var statusProgressSelector = '#status-progress-' + package_info.ID;
                            $(statusProgressSelector).text(package_info.status_progress);
                            console.log("status progress: " + package_info.status_progress);
                        }
                    } else if (currentProcessingState == DupPro.Pack.ProcessingStats.PendingCancellation) {
                        if ((package_info.status == -2) || (package_info.status == -4)) {
                            // refresh when its gone to cancelled
                            window.location = window.location.href;
                        } else {
                            activePackagePresent = true;
                        }
                    } else if (currentProcessingState == DupPro.Pack.ProcessingStats.Finished) {
                        // IF something caused the package to come out of finished refresh everything (has to be out of finished or error state)
                        if ((package_info.status != 100) && (package_info.status > 0)) {
                            // wait one miutes to prevent a realod loop
                            setTimeout(function() {
                                window.location = window.location.href;
                            }, 60000);
                        }
                    }
                }

                if (activePackagePresent) {
                    $('#dup-pro-create-new').addClass('disabled');
                    DupPro.Pack.SetUpdateInterval(10);
                } else {
                    $('#dup-pro-create-new').removeClass('disabled');
                    // Kick refresh down to 60 seconds if nothing is being actively worked on
                    DupPro.Pack.SetUpdateInterval(60);
                }
            },
            error: function(data) {
                DupPro.Pack.SetUpdateInterval(60);
                console.log(data);
            }
        });
    };

    //Init
    DupPro.UI.Clock(DupPro._WordPressInitTime);
    DupPro.Pack.UpdateUnfinishedPackages();
    
    $('.dpro-btn-open-recovery-box').click(function(event) {
        event.preventDefault();

        let packageId = $(this).data('package-id');
        
        Duplicator.Util.ajaxWrapper(
            {
                action: 'duplicator_pro_get_recovery_box_content',
                packageId: packageId,
                nonce: '<?php echo esc_js(wp_create_nonce('duplicator_pro_get_recovery_box_content')); ?>'
            },
            function (result, data, funcData, textStatus, jqXHR) {
                if (funcData.success) {
                    let boxContent = funcData.content;
                    if (funcData.isRecoveable) {
                        <?php
                            $availableRecoveryBox->updateMessage('boxContent');
                            $availableRecoveryBox->showAlert();
                        ?>
                        $('.dup-pro-recovery-download-launcher').off().click(function () {
                            DupPro.Pack.downloadLauncher();
                        });
                    } else {
                        <?php
                            $unavailableRecoveryBox->updateMessage('boxContent');
                            $unavailableRecoveryBox->showAlert();
                        ?>
                    }
                } else {
                    DupPro.addAdminMessage(funcData.message, 'error');
                }
                return '';        
            }
        );

        return false;
    });
});
</script>

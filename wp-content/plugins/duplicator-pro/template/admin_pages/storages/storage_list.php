<?php

/**
 * @package Duplicator
 */

defined("ABSPATH") or die("");

use Duplicator\Ajax\ServicesStorage;
use Duplicator\Controllers\SettingsPageController;
use Duplicator\Controllers\StoragePageController;
use Duplicator\Core\CapMng;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Models\Storages\AbstractStorageEntity;
use Duplicator\Models\Storages\StoragesUtil;

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 * @var array<string, mixed> $tplData
 * @var DUP_PRO_Schedule_Entity $schedule
 * @var bool $blur
 */

$blur = $tplData['blur'];


if (($storages = AbstractStorageEntity::getAll(0, 0, [StoragesUtil::class, 'sortDefaultFirst'])) === false) {
    $storages = [];
}

$storage_count    = count($storages);
$edit_storage_url = ControllersManager::getMenuLink(
    ControllersManager::STORAGE_SUBMENU_SLUG,
    null,
    null,
    array(
        ControllersManager::QUERY_STRING_INNER_PAGE => StoragePageController::INNER_PAGE_EDIT,
    )
);
$storage_tab_url  = ControllersManager::getMenuLink(
    ControllersManager::STORAGE_SUBMENU_SLUG,
    SettingsPageController::L2_SLUG_STORAGE
);

$settingsStorageUrl = ControllersManager::getMenuLink(
    ControllersManager::SETTINGS_SUBMENU_SLUG,
    SettingsPageController::L2_SLUG_STORAGE
);

$baseCopyUrl = ControllersManager::getMenuLink(
    ControllersManager::STORAGE_SUBMENU_SLUG,
    SettingsPageController::L2_SLUG_STORAGE,
    null,
    array(
        ControllersManager::QUERY_STRING_INNER_PAGE => 'edit',
        'action'                                    => $tplData['actions']['copy-storage']->getKey(),
        '_wpnonce'                                  => $tplData['actions']['copy-storage']->getNonce(),
    )
);

?>

<!-- ====================
TOOL-BAR -->
<table class="dpro-edit-toolbar <?php echo ($blur ? 'dup-mock-blur' : ''); ?>">
    <tr>
        <td>
            <select id="bulk_action">
                <option value="-1" ><?php esc_html_e('Bulk Actions', 'duplicator-pro'); ?></option>
                <option value="<?php echo (int) ServicesStorage::STORAGE_BULK_DELETE; ?>" title="Delete selected storage endpoint(s)">
                    <?php esc_html_e('Delete', 'duplicator-pro'); ?>
                </option>
            </select>
            <input type="button" class="button action" value="<?php esc_attr_e("Apply", 'duplicator-pro') ?>" onclick="DupPro.Storage.BulkAction()">
            <?php if (CapMng::can(CapMng::CAP_SETTINGS, false)) { ?>
                <span class="btn-separator"></span>
                <a href="<?php echo esc_attr($settingsStorageUrl); ?>" class="button grey-icon" title="<?php esc_attr_e("Settings", 'duplicator-pro') ?>">
                    <i class="fas fa-sliders-h fa-fw"></i>
                </a>
            <?php } ?>
        </td>
        <td>
            <div class="btnnav">
                <a href="<?php echo esc_url($edit_storage_url); ?>" id="duplicator-pro-add-new-storage" class="button">
                    <?php esc_html_e('Add New', 'duplicator-pro'); ?>
                </a>
            </div>
        </td>
    </tr>
</table>

<form 
    id="dup-storage-form" 
    action="<?php echo esc_url($storage_tab_url); ?>" 
    method="post"
    class="<?php echo ($blur ? 'dup-mock-blur' : ''); ?>"
>
    <input type="hidden" id="dup-selected-storage" name="storage_id" value="null"/>

    <!-- ====================
    LIST ALL STORAGE -->
    <table class="widefat storage-tbl">
        <thead>
            <tr>
                <th style='width:10px;'>
                    <input type="checkbox" id="dpro-chk-all" title="Select all storage endpoints" onclick="DupPro.Storage.SetAll(this)">
                </th>
                <th style='width:275px;'>Name</th>
                <th><?php esc_html_e('Type', 'duplicator-pro'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $i = 0;
            foreach ($storages as $storage) :
                $i++;
                $type_name = $storage->getStypeName();
                $type_id   = $storage->getSType();
                ?>
                <tr id="main-view-<?php echo (int) $storage->getId() ?>"
                    class="storage-row <?php echo ($i % 2) ? 'alternate' : ''; ?>"
                    data-delete-view="<?php echo esc_attr($storage->getDeleteView(false)); ?>"
                >
                    <td>
                        <?php if ($storage->isDefault()) : ?>
                            <input type="checkbox" disabled="disabled" />
                        <?php else : ?>
                            <input name="selected_id[]" type="checkbox" value="<?php echo (int) $storage->getId(); ?>" class="item-chk" />
                        <?php endif; ?>
                    </td>
                    <td>                                             
                        <a href="javascript:void(0);" onclick="DupPro.Storage.Edit('<?php echo (int) $storage->getId(); ?>')">
                            <b><?php echo esc_html($storage->getName()); ?></b>
                        </a>
                        <div class="sub-menu">
                            <a href="javascript:void(0);" onclick="DupPro.Storage.Edit('<?php echo (int) $storage->getId(); ?>')">
                                <?php esc_html_e('Edit', 'duplicator-pro'); ?>
                            </a> 
                            |
                            <a href="javascript:void(0);" onclick="DupPro.Storage.View('<?php echo (int) $storage->getId(); ?>');">
                                <?php esc_html_e('Quick View', 'duplicator-pro'); ?>
                            </a> 
                            <?php if (!$storage->isDefault()) : ?>    
                                |
                                <a href="javascript:void(0);" onclick="DupPro.Storage.CopyEdit('<?php echo (int) $storage->getId(); ?>');">
                                    <?php esc_html_e('Copy', 'duplicator-pro'); ?>
                                </a>
                                |
                                <a href="javascript:void(0);" onclick="DupPro.Storage.deleteSingle('<?php echo (int) $storage->getId(); ?>');">
                                    <?php esc_html_e('Delete', 'duplicator-pro'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <?php echo $storage->getStypeIcon() . '&nbsp;' . esc_html($storage->getStypeName()); ?>
                    </td>
                </tr>
                <?php
                ob_start();
                try { ?>
                    <tr id='quick-view-<?php echo (int) $storage->getId(); ?>' class='<?php echo ($i % 2) ? 'alternate' : ''; ?> storage-detail'>
                        <td colspan="3">
                            <b><?php esc_html_e('QUICK VIEW', 'duplicator-pro') ?></b> <br/>
                            <div>
                                <label><?php esc_html_e('Name', 'duplicator-pro') ?>:</label>
                                <?php echo esc_html($storage->getName()); ?>
                            </div>
                            <div>
                                <label><?php esc_html_e('Notes', 'duplicator-pro') ?>:</label>
                                <?php echo (strlen($storage->getNotes())) ? esc_html($storage->getNotes()) : esc_html__('(no notes)', 'duplicator-pro'); ?>
                            </div>
                            <div>
                                <label><?php esc_html_e('Type', 'duplicator-pro') ?>:</label>
                                <?php echo esc_html($storage->getStypeName()); ?>
                            </div>
                            <?php $storage->getListQuickView(); ?>
                            <button type="button" class="button" onclick="DupPro.Storage.View('<?php echo (int) $storage->getId(); ?>');">
                                <?php esc_html_e('Close', 'duplicator-pro') ?>
                            </button>
                        </td>
                    </tr>
                    <?php
                } catch (Exception $e) {
                    ob_clean(); ?>
                    <tr id='quick-view-<?php echo intval($storage->getId()); ?>' class='<?php echo ($i % 2) ? 'alternate' : ''; ?>'>
                        <td colspan="3">
                           <?php
                            StoragePageController::getErrorMsg($e, true);
                            ?>
                            <br><br>
                           <button type="button" class="button" onclick="DupPro.Storage.View('<?php echo intval($storage->getId()); ?>');">
                           <?php esc_html_e('Close', 'duplicator-pro') ?>
                           </button>
                        </td>
                    </tr>
                    <?php
                }
                $rowStr = ob_get_clean();
                echo $rowStr;
            endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="8" style="text-align:right; font-size:12px">                       
                    <?php echo esc_html__('Total', 'duplicator-pro') . ': ' . (int) $storage_count; ?>
                </th>
            </tr>
        </tfoot>
    </table>

</form>
<?php
    //Select Action Alert
    $alert1          = new DUP_PRO_UI_Dialog();
    $alert1->title   = __('Bulk Action Required', 'duplicator-pro');
    $alert1->message = __('Please select an action from the "Bulk Actions" drop down menu!', 'duplicator-pro');
    $alert1->initAlert();

    //Select Storage Alert
    $alert2          = new DUP_PRO_UI_Dialog();
    $alert2->title   = __('Selection Required', 'duplicator-pro');
    $alert2->message = __('Please select at least one storage to delete!', 'duplicator-pro');
    $alert2->initAlert();

    //Delete Dialog
    $dlgDelete               = new DUP_PRO_UI_Dialog();
    $dlgDelete->height       = 525;
    $dlgDelete->title        = __('Delete Storage(s)?', 'duplicator-pro');
    $dlgDelete->progressText = __('Removing Storages, Please Wait...', 'duplicator-pro');
    $dlgDelete->jsCallback   = 'DupPro.Storage.deleteAjax()';
    $dlgDelete->initConfirm();
    $storage_bulk_action_nonce = wp_create_nonce("duplicator_pro_storage_bulk_actions");
?>
<script>
jQuery(document).ready(function($) {
    //Shows detail view
    DupPro.Storage.Edit = function (id) {
        document.location.href = '<?php echo "$edit_storage_url&storage_id="; ?>' + id;
    };

    //Copy and edit
    DupPro.Storage.CopyEdit = function (id) {
        document.location.href = <?php echo json_encode($baseCopyUrl); ?> + '&duppro-source-storage-id=' + id;
    };

    //Shows detail view
    DupPro.Storage.View = function (id) {
        $('#quick-view-' + id).toggle();
        $('#main-view-' + id).toggle();
    };

    //Select all checked items
    DupPro.Storage.SelectedList = function () {
        var arr = [];
        $("input[name^='selected_id[]']").each(function () {
            if ($(this).is(':checked')) {
                arr.push($(this).val());
            }
        });
        return arr;
    };

    //Sets all for deletion
    DupPro.Storage.SetAll = function (chkbox) {
        $('.item-chk').each(function () {
            this.checked = chkbox.checked;
        });
    };

    // Bulk action
    DupPro.Storage.BulkAction = function () {
        var list = DupPro.Storage.SelectedList();
        var action = $('#bulk_action').val();

        if (list.length === 0) {
            <?php $alert2->showAlert(); ?>
            return;
        }

        switch (action) {
            case '<?php echo (int) ServicesStorage::STORAGE_BULK_DELETE; ?>':
                  DupPro.Storage.deleteConfirm(list);
                break;
            default:
            <?php $alert1->showAlert(); ?>
                break;
        }
    };

    //Delete via the delete link
    DupPro.Storage.deleteSingle = function(id) {
       $('#dup-selected-storage').val(id);
       DupPro.Storage.deleteConfirm([id]);
    };

    //Load the delete confirm dialog
    DupPro.Storage.deleteConfirm = function(idList) {
        var $rowData;
        var name, id, typeName, html;

        var storeCount  = idList.length;
        var isSingle    = (storeCount == 1) ? true : false;
        var dlgID       = "<?php echo esc_js($dlgDelete->getID()); ?>";
        var $content    = $(`#${dlgID}_message`);

        html =  (isSingle)
                ? "<i><?php esc_html_e('Are you sure you want to delete this storage item?', 'duplicator-pro')?></i>"
                : `<i><?php esc_html_e('Are you sure you want to delete these ${storeCount} storage items?', 'duplicator-pro')?></i>`;

        // Build storage item html
        html += '<div class="store-items">';
        idList.forEach(v => {
            html += $('#main-view-' + v).data('delete-view');
        });
        html     +=  '</div>';

        $content.html(html);
        <?php $dlgDelete->showConfirm(); ?>

        html  = `<div class="schedule-area">
                    <b><?php esc_html_e('Linked Schedules', 'duplicator-pro')?>:</b><br/>
                    <small><?php esc_html_e("Schedules linked to the storage items above", 'duplicator-pro');  ?>:</small>
                    <div class="schedule-progress" id="${dlgID}-schedule-progress">
                        <i class="fas fa-circle-notch fa-spin"></i>
                        <?php esc_html_e('Finding Schedule Links...  Please wait', 'duplicator-pro')?>
                    </div>
                    <small>
                        <?php
                            esc_html_e("To remove storage items and unlink schedules click OK. ", 'duplicator-pro');
                            printf(
                                esc_html_x(
                                    'Schedules with asterisk%1$s will be deactivated if storage is removed.',
                                    '%1$s is an asterisk symbol',
                                    'duplicator-pro'
                                ),
                                '<span class="maroon">*</span>'
                            );
                            ?>
                    </small>
                 </div>`;
        $content.append(html);

        function loadSchedules(idList, dlgID){
            let result = DupPro.Storage.getScheduleData(idList);
            (result != null)
                ? $(`#${dlgID}-schedule-progress`).html(result)
                : $(`#${dlgID}-schedule-progress`).html("<?php esc_html_e('- No linked schedules found -', 'duplicator-pro')?>");
        }
        setTimeout(loadSchedules, 100, idList, dlgID);
    };

    //Get the linked schedule data
    DupPro.Storage.getScheduleData = function(storageIDs) {

        var result  = null;
        var html;

        $.ajax({
            type: "POST",
            url: ajaxurl,
             async: false,
            dataType: "json",
            data: {
                action: 'duplicator_pro_storage_bulk_actions',
                perform: <?php echo (int) ServicesStorage::STORAGE_GET_SCHEDULES; ?>,
                storage_ids: storageIDs,
                nonce: '<?php echo esc_js($storage_bulk_action_nonce); ?>'
            }
        })
        .done(function (data) {
            //__sleepFor(1000); //Test delays
           if (data.schedules !== undefined && data.schedules.length > 0) {
               html = '';
               data.schedules.forEach(function (schedule) {
                   let name     = $("<div/>").text(schedule.name).html();
                   let asterisk = schedule.hasOneStorage ? "*" : "";
                   html += `<div class="schedule-item">
                               <i class="far fa-clock"></i> <a href="${schedule.editURL}">${name}</a> <span class="maroon">${asterisk}</span>
                            </div>`;
               });
               result = html;
           }
        })
        .fail(function() {
            result =  '<i class="fas fa-exclamation-triangle"></i> <?php esc_html_e('Unable to get schedule data.', 'duplicator-pro')?>';
        });
        return result;
    };


    //Perform the delete via ajax
    DupPro.Storage.deleteAjax = function ()  {

        var dlgID   = "<?php echo esc_js($dlgDelete->getID()); ?>";
        var list    = DupPro.Storage.SelectedList();

        //Delete from the quick link
        if (list.length == 0) {
           var singleID = $('#dup-selected-storage').val();
           list = (singleID > 0) ? [singleID] : null;
        }

        $(`#${dlgID}_message`).hide();

        $.ajax({
            type: "POST",
            url: ajaxurl,
            dataType: "json",
            data: {
                action: 'duplicator_pro_storage_bulk_actions',
                perform: <?php echo (int) ServicesStorage::STORAGE_BULK_DELETE; ?>,
                storage_ids: list,
                nonce: '<?php echo esc_js($storage_bulk_action_nonce); ?>'
            }
        })
        .done(function()   {$('#dup-storage-form').submit()})
        .always(function() {$('#dup-selected-storage').val(null)});
    };


    //--------------------------
    //INIT
    //Name hover show menu
    $("tr.storage-row").hover(
        function () {
            $(this).find(".sub-menu").show();
        },
        function () {
            $(this).find(".sub-menu").hide();
        }
    );
});

//Used to test ajax delays
function __sleepFor(sleepDuration){
    var now = new Date().getTime();
    while(new Date().getTime() < now + sleepDuration){ /* Do nothing */ }
}
</script>

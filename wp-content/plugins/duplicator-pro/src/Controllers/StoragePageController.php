<?php

/**
 * Storage page controller
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

namespace Duplicator\Controllers;

use DUP_PRO_Log;
use DUP_PRO_U;
use Duplicator\Addons\ProBase\License\License;
use Duplicator\Core\CapMng;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Controllers\AbstractMenuPageController;
use Duplicator\Core\Controllers\PageAction;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Models\Storages\AbstractStorageEntity;
use Duplicator\Models\Storages\StoragesUtil;
use Duplicator\Models\Storages\UnknownStorage;
use Exception;

class StoragePageController extends AbstractMenuPageController
{
    const INNER_PAGE_LIST = 'storage';
    const INNER_PAGE_EDIT = 'edit';

    /**
     * Class constructor
     */
    protected function __construct()
    {
        $this->parentSlug   = ControllersManager::MAIN_MENU_SLUG;
        $this->pageSlug     = ControllersManager::STORAGE_SUBMENU_SLUG;
        $this->pageTitle    = __('Storage', 'duplicator-pro');
        $this->menuLabel    = __('Storage', 'duplicator-pro');
        $this->capatibility = CapMng::CAP_STORAGE;
        $this->menuPos      = 40;

        add_filter('duplicator_page_actions_' . $this->pageSlug, array($this, 'pageActions'));
        add_action('duplicator_after_run_actions_' . $this->pageSlug, array($this, 'pageAfterActions'));
        add_action('duplicator_render_page_content_' . $this->pageSlug, array($this, 'renderContent'), 10, 2);
    }

    /**
     * Return actions for current page
     *
     * @param PageAction[] $actions actions lists
     *
     * @return PageAction[]
     */
    public function pageActions($actions)
    {
        $actions[] = new PageAction(
            'save',
            [
                $this,
                'actionEditSave',
            ],
            [$this->pageSlug],
            'edit'
        );
        $actions[] = new PageAction(
            'copy-storage',
            [
                $this,
                'actionEditCopyStorage',
            ],
            [$this->pageSlug],
            'edit'
        );
        return $actions;
    }

    /**
     * Return storage edit url
     *
     * @param AbstractStorageEntity $storage storage entity, if is null get new storage URL
     *
     * @return string
     */
    public static function getEditUrl(AbstractStorageEntity $storage = null)
    {
        $data = [ControllersManager::QUERY_STRING_INNER_PAGE => 'edit'];
        if ($storage !== null) {
            $data['storage_id'] = $storage->getId();
        }
        return ControllersManager::getMenuLink(ControllersManager::STORAGE_SUBMENU_SLUG, null, null, $data);
    }

    /**
     * Return storage defualt edit URL
     *
     * @return string
     */
    public static function getEditDefaultUrl()
    {
        return ControllersManager::getMenuLink(
            ControllersManager::STORAGE_SUBMENU_SLUG,
            null,
            null,
            array(
                ControllersManager::QUERY_STRING_INNER_PAGE => 'edit',
                'storage_id'                                => StoragesUtil::getDefaultStorageId(),
            )
        );
    }

    /**
     * Page after actions hook
     *
     * @param bool $isActionCalled true if one actions is called,false if no actions
     *
     * @return void
     */
    public function pageAfterActions($isActionCalled)
    {
        $tplMng = TplMng::getInstance();
        if ($this->getCurrentInnerPage() == 'edit' && $tplMng->hasGlobalValue('storage_id') == false) {
            $storageId = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'storage_id', -1);
            $storage   = ($storageId == -1 ? StoragesUtil::getDefaultNewStorage() : AbstractStorageEntity::getById($storageId));
            if ($storage === false) {
                $storageId = -1;
                $storage   = StoragesUtil::getDefaultNewStorage();
            }

            $tplMng->setGlobalValue('storage_id', $storageId);
            $tplMng->setGlobalValue('storage', $storage);
            $tplMng->setGlobalValue('error_message', null);
            $tplMng->setGlobalValue('success_message', null);
        }
    }

    /**
     * Render page content
     *
     * @param string[] $currentLevelSlugs current menu slugs
     * @param string   $innerPage         current inner page, empty if not set
     *
     * @return void
     */
    public function renderContent($currentLevelSlugs, $innerPage)
    {
        try {
            switch ($this->getCurrentInnerPage()) {
                case self::INNER_PAGE_EDIT:
                    TplMng::getInstance()->render(
                        'admin_pages/storages/storage_edit',
                        [
                            'blur' => !License::can(License::CAPABILITY_STORAGE),
                        ]
                    );
                    break;
                case self::INNER_PAGE_LIST:
                default:
                    // I left the global try catch for security but the exceptions should be managed inside the list.
                    TplMng::getInstance()->render(
                        'admin_pages/storages/storage_list',
                        [
                            'blur' => !License::can(License::CAPABILITY_STORAGE),
                        ]
                    );
                    break;
            }
        } catch (Exception $e) {
            self::getErrorMsg($e, true);
        }
    }

    /**
     * Get exception error message
     *
     * @param Exception $e    exception error
     * @param bool      $echo displays if true, returns otherwise
     *
     * @return ?string
     */
    public static function getErrorMsg(Exception $e, $echo = false)
    {
        $settings_url = ControllersManager::getMenuLink(ControllersManager::SETTINGS_SUBMENU_SLUG);
        ob_start();
        ?>
        <div class="error-txt" style="margin:10px 0 20px 0; max-width:750px">
            <?php esc_html_e('An error has occurred while trying to read a storage item!  ', 'duplicator-pro'); ?>
            <?php esc_html_e('To resolve this issue delete the storage item and re-enter its information.  ', 'duplicator-pro'); ?>
            <br/><br/>
            <?php esc_html_e(
                'This problem can be due to a security plugin changing keys in wp-config.php, causing the storage information to become unreadable.',
                'duplicator-pro'
            ); ?>
            <?php
            printf(
                esc_html__(
                    'If such a plugin is doing this then either disable the key changing functionality 
                    in the security plugin or go to %1$sDuplicator Pro > Settings%2$s and disable settings encryption.',
                    'duplicator-pro'
                ),
                '<a href="' . esc_url($settings_url) . '">',
                '</a>'
            );
            ?>
            <br/><br/>
            <?php esc_html_e('If the problem persists after doing these things then please contact the support team.', 'duplicator-pro'); ?>
        </div>
        <a href="javascript:void(0)" onclick="jQuery('#dup-store-err-details').toggle();">
            <?php esc_html_e('Show Details', 'duplicator-pro'); ?>
        </a>
        <div id="dup-store-err-details" ><?php echo esc_html($e->getMessage()); ?>
            <br/><br/>
            <pre>
                <?php echo esc_html($e->getTraceAsString()); ?>
            </pre>
        </div>
        <?php
        if ($echo) {
            ob_end_flush();
            return '';
        }
        return (string) ob_get_clean();
    }

    /**
     * Save storage
     *
     * @return array{storage_id:int,storage:AbstractStorageEntity,error_message:?string,success_message:?string}
     */
    public function actionEditSave()
    {
        $error_message   = null;
        $success_message = null;

        $storageId   = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'storage_id', -1);
        $storageType = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'storage_type', UnknownStorage::getSType());
        $storage     = ($storageId == -1 ? AbstractStorageEntity::getNewStorageByType($storageType) : AbstractStorageEntity::getById($storageId));
        if ($storage === false) {
            $error_message = __('Unable to load storage item', 'duplicator-pro');
        }
        $message = '';

        if ($storage->updateFromHttpRequest($message) === false) {
            $error_message = $message;
            DUP_PRO_Log::trace('Storage update failed ID:' . $storage->getId() . ' Type:' . $storage->getStypeName() . ' Message:' . $message);
        } elseif ($storage->save() === false) {
            $error_message   = __('Unable to save storage item', 'duplicator-pro');
            $success_message = '';
            DUP_PRO_Log::trace('Storage save failed ID:' . $storage->getId() . ' Type:' . $storage->getStypeName());
        } else {
            DUP_PRO_Log::trace('Storage updated successfully ID:' . $storage->getId() . ' Type:' . $storage->getStypeName());
            $success_message = $message;
        }

        return [
            "storage_id"      => $storageId,
            "storage"         => $storage,
            "error_message"   => $error_message,
            "success_message" => $success_message,
        ];
    }

    /**
     * Save storage
     *
     * @return array{storage_id:int,storage:AbstractStorageEntity,error_message:?string,success_message:?string}
     */
    public function actionEditCopyStorage()
    {
        $error_message   = null;
        $success_message = null;
        $sourceId        = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'duppro-source-storage-id', -1);
        $targetId        = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'storage_id', -1);

        if (($storage = AbstractStorageEntity::getCopyStorage($sourceId, $targetId)) === false) {
            $error_message = __('Unable to copy storage item', 'duplicator-pro');
            $storage       = AbstractStorageEntity::getById($targetId);
        } elseif ($storage->save() === false) {
            $error_message   = __('Unable to copy storage item', 'duplicator-pro');
            $success_message = '';
            DUP_PRO_Log::trace('Storage save failed ID:' . $storage->getId() . ' Type:' . $storage->getStypeName());
        } else {
            $success_message = __('Storage Copied Successfully.', 'duplicator-pro');
        }

        return [
            "storage_id"      => $targetId,
            "storage"         => $storage,
            "error_message"   => $error_message,
            "success_message" => $success_message,
        ];
    }
}

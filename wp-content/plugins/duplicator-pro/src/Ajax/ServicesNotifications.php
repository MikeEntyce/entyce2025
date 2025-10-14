<?php

/**
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

namespace Duplicator\Ajax;

use Duplicator\Addons\ProBase\License\License;
use Duplicator\Ajax\AjaxWrapper;
use Duplicator\Core\CapMng;
use Duplicator\Core\Views\Notifications;
use Duplicator\Models\SystemGlobalEntity;
use Duplicator\Views\AdminNotices;
use Exception;

class ServicesNotifications extends AbstractAjaxService
{
    /**
     * Init ajax calls
     *
     * @return void
     */
    public function init()
    {
        if (!License::can(License::CAPABILITY_PRO_BASE)) {
            return;
        }
        $this->addAjaxCall('wp_ajax_duplicator_notification_dismiss', 'setDissmisedNotifications');
        $this->addAjaxCall('wp_ajax_duplicator_pro_admin_notice_to_dismiss', 'admin_notice_to_dismiss');
    }

    /**
     * Dismiss notification
     *
     * @return bool
     */
    public static function dismissNotifications()
    {
        $id = sanitize_key($_POST['id']);
        return Notifications::dismiss($id);
    }

    /**
     * Set dismiss notification action
     *
     * @return void
     */
    public function setDissmisedNotifications()
    {
        AjaxWrapper::json(
            array(
                __CLASS__,
                'dismissNotifications',
            ),
            Notifications::DUPLICATOR_NOTIFICATION_NONCE_KEY,
            $_POST['nonce'],
            'manage_options'
        );
    }

    /**
     * AJjax callback for admin_notice_to_dismiss
     *
     * @return boolean
     */
    public static function adminNoticeToDismissCallback()
    {

        $noticeToDismiss = filter_input(INPUT_POST, 'notice', FILTER_SANITIZE_SPECIAL_CHARS);
        $systemGlobal    = SystemGlobalEntity::getInstance();
        switch ($noticeToDismiss) {
            case AdminNotices::OPTION_KEY_ACTIVATE_PLUGINS_AFTER_INSTALL:
            case AdminNotices::OPTION_KEY_MIGRATION_SUCCESS_NOTICE:
                $ret = delete_option($noticeToDismiss);
                break;
            case AdminNotices::OPTION_KEY_S3_CONTENTS_FETCH_FAIL_NOTICE:
                $ret = update_option(AdminNotices::OPTION_KEY_S3_CONTENTS_FETCH_FAIL_NOTICE, false);
                break;
            case AdminNotices::QUICK_FIX_NOTICE:
                $systemGlobal->clearFixes();
                $ret = $systemGlobal->save();
                break;
            case AdminNotices::FAILED_SCHEDULE_NOTICE:
                $systemGlobal->schedule_failed = false;
                $ret                           = $systemGlobal->save();
                break;
            default:
                throw new Exception('Notice invalid');
        }
        return $ret;
    }

    /**
     * Hook ajax wp_ajax_duplicator_pro_admin_notice_to_dismiss
     *
     * @return never
     */
    public static function adminNoticeToDismiss()
    {
        AjaxWrapper::json(
            array(
                __CLASS__,
                'adminNoticeToDismissCallback',
            ),
            'duplicator_pro_admin_notice_to_dismiss',
            $_POST['nonce'],
            CapMng::CAP_BASIC
        );
    }
}

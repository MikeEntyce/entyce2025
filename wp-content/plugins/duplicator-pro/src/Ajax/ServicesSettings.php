<?php

/**
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

namespace Duplicator\Ajax;

use DUP_PRO_Constants;
use DUP_PRO_Handler;
use DUP_PRO_Log;
use DUP_PRO_U;
use DUP_PRO_Global_Entity;
use DUP_PRO_Secure_Global_Entity;
use Duplicator\Addons\ProBase\License\License;
use Duplicator\Core\CapMng;
use Duplicator\Core\MigrationMng;
use Duplicator\Libs\Snap\SnapIO;
use Duplicator\Libs\Snap\SnapJson;
use Duplicator\Libs\Snap\SnapURL;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Models\SystemGlobalEntity;
use Duplicator\Utils\Settings\MigrateSettings;
use Duplicator\Utils\ZipArchiveExtended;
use Exception;

class ServicesSettings extends AbstractAjaxService
{
    const USERS_PAGE_SIZE =  10;
    /**
     * Init ajax calls
     *
     * @return void
     */
    public function init()
    {
        $this->addAjaxCall("wp_ajax_duplicator_settings_cap_users_list", "capUsersList");
        $this->addAjaxCall('wp_ajax_duplicator_pro_get_trace_log', 'getTraceLog');
        $this->addAjaxCall('wp_ajax_duplicator_pro_delete_trace_log', 'deleteTraceLog');
        $this->addAjaxCall('wp_ajax_duplicator_pro_export_settings', 'exportSettings');
        $this->addAjaxCall('wp_ajax_duplicator_pro_quick_fix', 'quickFix');
    }

    /**
     * Return user list for capabilites select
     *
     * @return mixed[]
     */
    public static function capUsersListCallback()
    {
        $searchStr = SnapUtil::sanitizeNSChars($_POST['search']);
        $page      = SnapUtil::sanitizeIntInput(INPUT_POST, 'page', 1);

        $result = [
            'results'    => [],
            'pagination' => ['more' => false],
        ];

        if ($page == 1) {
            foreach (CapMng::getSelectableRoles() as $role => $roleName) {
                if (stripos($role, $searchStr) !== false) {
                    $result['results'][] = [
                        'id'   => $role,
                        'text' => $roleName,
                    ];
                }
            }
        }

        if (License::can(License::CAPABILITY_CAPABILITIES_MNG_PLUS)) {
            $args = array(
                'search'         => '*' . SnapUtil::sanitizeNSChars($_POST['search']) . '*',
                'search_columns' => array(
                    'user_login',
                    'user_email',
                ),
                'number'         => self::USERS_PAGE_SIZE,
                'paged'          => $page,
            );

            $users = get_users($args);
            foreach ($users as $user) {
                $result['results'][] = [
                    'id'   => $user->ID,
                    'text' => $user->user_email,
                ];
            }
            $args  = array(
                'search'         => '*' . SnapUtil::sanitizeNSChars($_POST['search']) . '*',
                'search_columns' => array(
                    'user_login',
                    'user_email',
                ),
                'number'         => self::USERS_PAGE_SIZE,
                'paged'          => $page + 1,
            );
            $users = get_users($args);

            $result['pagination']['more'] = count($users) > 0;
        }

        return $result;
    }

    /**
     * Import upload action
     *
     * @return void
     */
    public function capUsersList()
    {
        AjaxWrapper::json(
            array(
                __CLASS__,
                'capUsersListCallback',
            ),
            'duplicator_settings_cap_users_list',
            $_POST['nonce'],
            CapMng::CAP_SETTINGS
        );
    }

    /**
     * Hook ajax wp_ajax_duplicator_pro_get_trace_log
     *
     * @return never
     */
    public function getTraceLog()
    {
        /**
         * don't init DUP_PRO_Handler::init_error_handler() in get trace
         */
        check_ajax_referer('duplicator_pro_get_trace_log', 'nonce');
        DUP_PRO_Log::trace("enter");

        $file_path   = DUP_PRO_Log::getTraceFilepath();
        $backup_path = DUP_PRO_Log::getBackupTraceFilepath();
        $zip_path    = DUPLICATOR_PRO_SSDIR_PATH . "/" . DUP_PRO_Constants::ZIPPED_LOG_FILENAME;

        try {
            CapMng::can(CapMng::CAP_CREATE);

            if (file_exists($zip_path)) {
                SnapIO::unlink($zip_path);
            }
            $zipArchive = new ZipArchiveExtended($zip_path);

            if ($zipArchive->open() == false) {
                throw new Exception('Can\'t open ZIP archive');
            }

            if ($zipArchive->addFile($file_path, basename($file_path)) == false) {
                throw new Exception('Can\'t add ZIP file ');
            }

            if (file_exists($backup_path) && $zipArchive->addFile($backup_path, basename($backup_path)) == false) {
                throw new Exception('Can\'t add ZIP file ');
            }

            $zipArchive->close();

            if (($fp = fopen($zip_path, 'rb')) === false) {
                throw new Exception('Can\'t open ZIP archive');
            }

            $zip_filename = basename($zip_path);

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: private", false);
            header("Content-Transfer-Encoding: binary");
            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename=\"$zip_filename\";");

            // required or large files wont work
            if (ob_get_length()) {
                ob_end_clean();
            }

            DUP_PRO_Log::trace("streaming $zip_path");
            fpassthru($fp);
            fclose($fp);
            @unlink($zip_path);
        } catch (Exception $e) {
            header("Content-Type: text/plain");
            header("Content-Disposition: attachment; filename=\"error.txt\";");
            $message = 'Create Log Zip error message: ' . $e->getMessage();
            DUP_PRO_Log::trace($message);
            echo esc_html($message);
        }
        die();
    }

    /**
     * Hook ajax wp_ajax_duplicator_pro_delete_trace_log
     *
     * @return never
     */
    public function deleteTraceLog()
    {
        /**
         * don't init DUP_PRO_Handler::init_error_handler() in get trace
         */
        check_ajax_referer('duplicator_pro_delete_trace_log', 'nonce');
        CapMng::can(CapMng::CAP_CREATE);

        $res = DUP_PRO_Log::deleteTraceLog();
        if ($res) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }

    /**
     * Hook ajax wp_ajax_duplicator_pro_export_settings
     *
     * @return never
     */
    public function exportSettings()
    {
        DUP_PRO_Handler::init_error_handler();
        check_ajax_referer('duplicator_pro_import_export_settings', 'nonce');

        try {
            DUP_PRO_Log::trace("Export settings start");
            CapMng::can(CapMng::CAP_SETTINGS);

            $message = '';

            if (($filePath = MigrateSettings::export($message)) === false) {
                throw new Exception($message);
            }

            DUP_PRO_U::getDownloadAttachment($filePath, 'application/octet-stream');
        } catch (Exception $ex) {
            // RSR TODO: set the error message to this $this->message = 'Error processing with export:' .  $e->getMessage();
            header("Content-Type: text/plain");
            header("Content-Disposition: attachment; filename=\"error.txt\";");
            $message = $ex->getMessage();
            DUP_PRO_Log::trace($message);
            echo esc_html($message);
        }
        die();
    }

    /**
     * DUPLICATOR_PRO_QUICK_FIX
     * Set default quick fix values automaticaly to help user
     *
     * @return never
     */
    public function quickFix()
    {
        DUP_PRO_Handler::init_error_handler();
        check_ajax_referer('duplicator_pro_quick_fix', 'nonce');

        $json      = array(
            'success' => false,
            'message' => '',
        );
        $isValid   = true;
        $inputData = filter_input_array(INPUT_POST, array(
            'id'    => array(
                'filter'  => FILTER_SANITIZE_SPECIAL_CHARS,
                'flags'   => FILTER_REQUIRE_SCALAR,
                'options' => array('default' => false),
            ),
            'setup' => array(
                'filter'  => FILTER_SANITIZE_SPECIAL_CHARS,
                'flags'   => FILTER_REQUIRE_ARRAY,
                'options' => array('default' => false),
            ),
        ));
        $setup     = $inputData['setup'];
        $id        = $inputData['id'];

        if (!$id || empty($setup)) {
            $isValid = false;
        }
        //END OF VALIDATION

        try {
            CapMng::can(CapMng::CAP_BASIC);
            if (!$isValid) {
                throw new Exception(__("Invalid request.", 'duplicator-pro'));
            }

            $data      = array();
            $isSpecial = isset($setup['special']) && is_array($setup['special']) && count($setup['special']) > 0;

            /* ****************
             *  GENERAL SETUP
             * **************** */
            if (isset($setup['global']) && is_array($setup['global'])) {
                $global = DUP_PRO_Global_Entity::getInstance();

                foreach ($setup['global'] as $object => $value) {
                    $value = DUP_PRO_U::valType($value);
                    if (isset($global->$object)) {
                        // Get current setup
                        $current = $global->$object;

                        // If setup is not the same - fix this
                        if ($current !== $value) {
                            // Set new value
                            $global->$object = $value;
                            // Check value
                            $data[$object] = $global->$object;
                        }
                    }
                }
                $global->save();
            }

            /* ****************
             *  SPECIAL SETUP
             * **************** */
            if ($isSpecial) {
                $special              = $setup['special'];
                $stuck5percent        = isset($special['stuck_5percent_pending_fix']) && $special['stuck_5percent_pending_fix'] == 1;
                $basicAuth            = isset($special['set_basic_auth']) && $special['set_basic_auth'] == 1;
                $removeInstallerFiles = isset($special['remove_installer_files']) && $special['remove_installer_files'] == 1;
                /**
                 * SPECIAL FIX: Package build stuck at 5% or Pending?
                 * */
                if ($stuck5percent) {
                    $data = array_merge($data, $this->quickFixStuck5Percent());
                }

                /**
                 * SPECIAL FIX: Set basic auth username & password
                 * */
                if ($basicAuth) {
                    $data = array_merge($data, $this->quickFixBasicAuth());
                }

                /**
                 * SPECIAL FIX: Remove installer files
                 * */
                if ($removeInstallerFiles) {
                    $data = array_merge($data, $this->quickFixRemoveInstallerFiles());
                }
            }

            // Save new property
            $find = count($data);
            if ($find > 0) {
                $system_global = SystemGlobalEntity::getInstance();
                if (strlen($id) > 0) {
                    $system_global->removeFixById($id);
                    $json['id'] = $id;
                }

                $json['success']           = true;
                $json['setup']             = $data;
                $json['fixed']             = $find;
                $json['recommended_fixes'] = count($system_global->recommended_fixes);
            }
        } catch (Exception $ex) {
            $json['message'] = $ex->getMessage();
            DUP_PRO_Log::trace("Error while implementing quick fix: " . $ex->getMessage());
        }

        die(SnapJson::jsonEncode($json));
    }

    /**
     * Quick fix for removing installer files
     *
     * @return array{removed_installer_files:bool} $data
     */
    private function quickFixRemoveInstallerFiles()
    {
        $data        = array();
        $fileRemoved = MigrationMng::cleanMigrationFiles();
        $removeError = false;
        if (count($fileRemoved) > 0) {
            $data['removed_installer_files'] = true;
        } else {
            throw new Exception(esc_html__("Unable to remove installer files.", 'duplicator-pro'));
        }
        return $data;
    }

    /**
     * Quick fix for stuck at 5% or pending
     *
     * @return array<string, mixed> $data
     */
    private function quickFixStuck5Percent()
    {
        $global = DUP_PRO_Global_Entity::getInstance();

        $data    = array();
        $kickoff = true;
        $custom  = false;

        if ($global->ajax_protocol === 'custom') {
            $custom = true;
        }

        // Do things if SSL is active
        if (SnapURL::isCurrentUrlSSL()) {
            if ($custom) {
                // Set default admin ajax
                $custom_ajax_url = admin_url('admin-ajax.php', 'https');
                if ($global->custom_ajax_url != $custom_ajax_url) {
                    $global->custom_ajax_url = $custom_ajax_url;
                    $data['custom_ajax_url'] = $global->custom_ajax_url;
                    $kickoff                 = false;
                }
            } else {
                // Set HTTPS protocol
                if ($global->ajax_protocol === 'http') {
                    $global->ajax_protocol = 'https';
                    $data['ajax_protocol'] = $global->ajax_protocol;
                    $kickoff               = false;
                }
            }
        } else {
            // SSL is OFF and we must handle that
            if ($custom) {
                // Set default admin ajax
                $custom_ajax_url = admin_url('admin-ajax.php', 'http');
                if ($global->custom_ajax_url != $custom_ajax_url) {
                    $global->custom_ajax_url = $custom_ajax_url;
                    $data['custom_ajax_url'] = $global->custom_ajax_url;
                    $kickoff                 = false;
                }
            } else {
                // Set HTTP protocol
                if ($global->ajax_protocol === 'https') {
                    $global->ajax_protocol = 'http';
                    $data['ajax_protocol'] = $global->ajax_protocol;
                    $kickoff               = false;
                }
            }
        }

        // Set KickOff true if all setups are gone
        if ($kickoff) {
            if ($global->clientside_kickoff !== true) {
                $global->clientside_kickoff = true;
                $data['clientside_kickoff'] = $global->clientside_kickoff;
            }
        }

        $global->save();
        return $data;
    }

    /**
     * Quick fix for basic auth
     *
     * @return array{basic_auth_enabled:bool,basic_auth_user:string,basic_auth_password:string}
     */
    private function quickFixBasicAuth()
    {
        $global   = DUP_PRO_Global_Entity::getInstance();
        $sglobal  = DUP_PRO_Secure_Global_Entity::getInstance();
        $username = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : false;
        $password = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : false;
        if ($username === false || $password === false) {
            throw new Exception(esc_html__("Username or password were not set.", 'duplicator-pro'));
        }

        $data                       = array();
        $global->basic_auth_enabled = true;
        $data['basic_auth_enabled'] = true;

        $global->basic_auth_user = $username;
        $data['basic_auth_user'] = $username;

        $sglobal->basic_auth_password = $password;
        $data['basic_auth_password']  = "**Secure Info**";

        $global->save();
        $sglobal->save();

        return $data;
    }
}

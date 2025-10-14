<?php

namespace Duplicator\Utils\Email;

use DUP_PRO_Global_Entity;
use DUP_PRO_Log;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapWP;
use Duplicator\Utils\CronUtils;
use Exception;

/**
 * Email summary bootstrap
 */
class EmailSummaryBootstrap
{
    const CRON_HOOK = 'duplicator_pro_email_summary_cron';

    /**
     * Init
     *
     * @return void
     */
    public static function init()
    {
        //Init Preview page
        \Duplicator\Controllers\EmailSummaryPreviewPageController::getInstance();

        //Storage hooks
        add_action('duplicator_pro_after_storage_create', function ($storageId) {
            EmailSummary::getInstance()->addStorage($storageId);
        });
        add_action('duplicator_pro_after_storage_delete', function ($storageId) {
            EmailSummary::getInstance()->removeStorage($storageId);
        });

        //Schedule hooks
        add_action('duplicator_pro_after_schedule_create', function ($schedule) {
            EmailSummary::getInstance()->addSchedule($schedule);
        });
        add_action('duplicator_pro_after_schedule_delete', function ($scheduleId) {
            EmailSummary::getInstance()->removeSchedule($scheduleId);
        });

        //Package hooks
        add_action('duplicator_pro_build_completed', function ($package) {
            EmailSummary::getInstance()->addPackage($package);
        });
        add_action('duplicator_pro_build_fail', function ($package) {
            EmailSummary::getInstance()->addFailed($package);
        });

        add_action('duplicator_pro_after_activation', [__CLASS__, 'activationAction']);
        add_action('duplicator_pro_after_deactivation', [__CLASS__, 'deactivationAction']);

        //Set cron action
        add_action(self::CRON_HOOK, [__CLASS__, 'send']);
    }

    /**
     * Init cron on activation
     *
     * @return void
     */
    public static function activationAction()
    {
        $frequency = DUP_PRO_Global_Entity::getInstance()->getEmailSummaryFrequency();
        if ($frequency === EmailSummary::SEND_FREQ_NEVER) {
            return;
        }

        if (self::updateCron($frequency) == false) {
            DUP_PRO_Log::trace("FAILED TO INIT EMAIL SUMMARY CRON. Frequency: {$frequency}");
        }
    }

    /**
     * Removes cron on deactivation
     *
     * @return void
     */
    public static function deactivationAction()
    {
        if (self::updateCron(EmailSummary::SEND_FREQ_NEVER) == false) {
            DUP_PRO_Log::trace("FAILED TO REMOVE EMAIL SUMMARY CRON.");
        }
    }

    /**
     * Updates the WP Cron job base on frequency or settings
     *
     * @param string $frequency The frequency
     *
     * @return bool True if the cron was updated or false on error
     */
    private static function updateCron($frequency = '')
    {
        if (strlen($frequency) === 0) {
            $frequency = DUP_PRO_Global_Entity::getInstance()->getEmailSummaryFrequency();
        }

        if ($frequency === EmailSummary::SEND_FREQ_NEVER) {
            if (wp_next_scheduled(self::CRON_HOOK)) {
                //have to check return like this because
                //wp_clear_scheduled_hook returns void in WP < 5.1
                return !self::isFalseOrWpError(wp_clear_scheduled_hook(self::CRON_HOOK));
            } else {
                return true;
            }
        } else {
            if (wp_next_scheduled(self::CRON_HOOK) && self::isFalseOrWpError(wp_clear_scheduled_hook(self::CRON_HOOK))) {
                return false;
            }

            return !self::isFalseOrWpError(
                wp_schedule_event(
                    self::getFirstRunTime($frequency),
                    self::getCronSchedule($frequency),
                    self::CRON_HOOK
                )
            );
        }
    }

    /**
     * Update next send time on frequency setting change
     *
     * @param string $oldFrequency The old frequency
     * @param string $newFrequency The new frequency
     *
     * @return bool True if the cron was updated or false on error
     */
    public static function updateFrequency($oldFrequency, $newFrequency)
    {
        if ($oldFrequency === $newFrequency) {
            return true;
        }

        return self::updateCron($newFrequency);
    }

    /**
     * Get the cron schedule
     *
     * @param string $frequency The frequency
     *
     * @return string
     */
    private static function getCronSchedule($frequency)
    {
        switch ($frequency) {
            case EmailSummary::SEND_FREQ_DAILY:
                return CronUtils::INTERVAL_DAILTY;
            case EmailSummary::SEND_FREQ_WEEKLY:
                return CronUtils::INTERVAL_WEEKLY;
            case EmailSummary::SEND_FREQ_MONTHLY:
                return CronUtils::INTERVAL_MONTHLY;
            default:
                throw new Exception("Unknown frequency: " . $frequency);
        }
    }

    /**
     * Set next send time based on frequency
     *
     * @param string $frequency Frequency
     *
     * @return int
     */
    private static function getFirstRunTime($frequency)
    {
        switch ($frequency) {
            case EmailSummary::SEND_FREQ_DAILY:
                $firstRunTime = strtotime('tomorrow 14:00');
                break;
            case EmailSummary::SEND_FREQ_WEEKLY:
                $firstRunTime = strtotime('next monday 14:00');
                break;
            case EmailSummary::SEND_FREQ_MONTHLY:
                $firstRunTime = strtotime('first day of next month 14:00');
                break;
            case EmailSummary::SEND_FREQ_NEVER:
                return 0;
            default:
                throw new Exception("Unknown frequency: " . $frequency);
        }

        return $firstRunTime - SnapWP::getGMTOffset();
    }

    /**
     * Send email
     *
     * @return void
     */
    public static function send()
    {
        $recipients = DUP_PRO_Global_Entity::getInstance()->getEmailSummaryRecipients();
        $frequency  = DUP_PRO_Global_Entity::getInstance()->getEmailSummaryFrequency();
        if (count($recipients) === 0 || $frequency === EmailSummary::SEND_FREQ_NEVER) {
            return;
        }

        $parsedHomeUrl = wp_parse_url(home_url());
        $siteDomain    = (isset($parsedHomeUrl['host']) ? $parsedHomeUrl['host'] : '');

        if (is_multisite() && isset($parsedHomeUrl['path'])) {
            $siteDomain .= $parsedHomeUrl['path'];
        }

        $subject = sprintf(
            esc_html_x(
                'Your Weekly Duplicator Summary for %s',
                '%s is the site domain',
                'duplicator-pro'
            ),
            $siteDomain
        );

        $content = TplMng::getInstance()->render('mail/email_summary', EmailSummary::getInstance()->getData(), false);

        add_filter('wp_mail_content_type', [__CLASS__, 'getMailContentType']);
        if (!wp_mail($recipients, $subject, $content)) {
            DUP_PRO_Log::trace("FAILED TO SEND EMAIL SUMMARY.");
            DUP_PRO_Log::traceObject("RECIPIENTS: ", $recipients);
            return;
        }

        EmailSummary::getInstance()->reset();
    }

    /**
     * Get mail content type
     *
     * @return string
     */
    public static function getMailContentType()
    {
        return 'text/html';
    }

    /**
     * Returns true if is false or wp_error
     *
     * @param mixed $value The value
     *
     * @return bool
     */
    private static function isFalseOrWpError($value)
    {
        return $value === false || is_wp_error($value);
    }
}

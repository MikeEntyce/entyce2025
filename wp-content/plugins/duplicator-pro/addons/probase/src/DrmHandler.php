<?php

/**
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

namespace Duplicator\Addons\ProBase;

use Duplicator\Addons\ProBase\License\License;
use Duplicator\Addons\ProBase\Models\LicenseData;

class DrmHandler
{
    const SCHEDULE_DRM_DELAY_DAYS = 14;

    /**
     * Return DRM activation days
     *
     * @return int -1 if has already expired, days left otherwise
     */
    public static function getDaysTillDRM()
    {
        $status = LicenseData::getInstance()->getStatus();
        if ($status !== LicenseData::STATUS_VALID && $status !== LicenseData::STATUS_EXPIRED) {
            return -1;
        }
        if (($expiresDays = LicenseData::getInstance()->getExpirationDays()) === false) {
            return -1;
        }
        return (self::SCHEDULE_DRM_DELAY_DAYS + $expiresDays);
    }
}

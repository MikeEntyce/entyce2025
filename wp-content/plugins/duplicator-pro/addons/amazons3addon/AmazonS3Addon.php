<?php

/**
 * FTP/SFTP ADDON
 *
 * Name: Duplicator PRO base
 * Version: 1
 * Author: Duplicator
 * Author URI: https://duplicator.com/
 *
 * PHP version 5.3
 *
 * @category  Duplicator
 * @package   Plugin
 * @author    Duplicator
 * @copyright 2011-2021  Snapcreek LLC
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @version   GIT: $Id$
 * @link      https://duplicator.com/
 */

namespace Duplicator\Addons\AmazonS3Addon;

use Duplicator\Addons\AmazonS3Addon\Models\AmazonS3Storage;
use Duplicator\Addons\AmazonS3Addon\Models\AmazonS3CompatibleStorage;
use Duplicator\Addons\AmazonS3Addon\Models\BackblazeStorage;
use Duplicator\Addons\AmazonS3Addon\Models\CloudflareStorage;
use Duplicator\Addons\AmazonS3Addon\Models\DigitalOceanStorage;
use Duplicator\Addons\AmazonS3Addon\Models\DreamStorage;
use Duplicator\Addons\AmazonS3Addon\Models\GoogleCloudStorage;
use Duplicator\Addons\AmazonS3Addon\Models\VultrStorage;
use Duplicator\Addons\AmazonS3Addon\Models\WasabiStorage;
use Duplicator\Addons\AmazonS3Addon\Utils\Autoloader;
use Duplicator\Core\Addons\AbstractAddonCore;
use Duplicator\Models\Storages\AbstractStorageEntity;

/**
 * Storage ftp/sftp addon class
 */
class AmazonS3Addon extends AbstractAddonCore
{
    const ADDON_PATH = __DIR__;

    /**
     * @return void
     */
    public function init()
    {
        Autoloader::register();

        add_action('duplicator_pro_daily_actions', [__CLASS__, 'purgeOldS3MultipartUploads']);
        add_action('duplicator_pro_register_storage_types', [$this, 'registerStorages']);
        add_filter('duplicator_template_file', [__CLASS__, 'getTemplateFile'], 10, 2);
        add_filter('duplicator_usage_stats_storages_infos', [__CLASS__, 'getStorageUsageStats'], 10, 1);
    }

    /**
     * Register storages
     *
     * @return void
     */
    public function registerStorages()
    {
        AmazonS3Storage::registerType();
        AmazonS3CompatibleStorage::registerType();
        GoogleCloudStorage::registerType();
        BackblazeStorage::registerType();
        DreamStorage::registerType();
        DigitalOceanStorage::registerType();
        VultrStorage::registerType();
        CloudflareStorage::registerType();
        WasabiStorage::registerType();
    }

    /**
     * Return template file path
     *
     * @param string $path    path to the template file
     * @param string $slugTpl slug of the template
     *
     * @return string
     */
    public static function getTemplateFile($path, $slugTpl)
    {
        if (strpos($slugTpl, 'amazons3addon/') === 0) {
            return self::getAddonPath() . '/template/' . $slugTpl . '.php';
        }
        return $path;
    }

    /**
     * Get storage usage stats
     *
     * @param array<string,int> $storageNums Storages num
     *
     * @return array<string,int>
     */
    public static function getStorageUsageStats($storageNums)
    {
        if (($storages = AbstractStorageEntity::getAll()) === false) {
            $storages = [];
        }

        $storageNums['storages_s3_count']            = 0;
        $storageNums['storages_s3_compatible_count'] = 0;

        foreach ($storages as $index => $storage) {
            switch ($storage->getStype()) {
                case AmazonS3Storage::getSType():
                    $storageNums['storages_s3_count']++;
                    break;
                case AmazonS3CompatibleStorage::getSType():
                case GoogleCloudStorage::getSType():
                case BackblazeStorage::getSType():
                case DreamStorage::getSType():
                case DigitalOceanStorage::getSType():
                case VultrStorage::getSType():
                case CloudflareStorage::getSType():
                case WasabiStorage::getSType():
                    $storageNums['storages_s3_compatible_count']++;
                    break;
            }
        }

        return $storageNums;
    }


    /**
     * Purge old S3 multipart uploads
     *
     * @return void
     */
    public static function purgeOldS3MultipartUploads()
    {
        if (($storages = AbstractStorageEntity::getAll()) == false) {
            return;
        }

        foreach ($storages as $storage) {
            if (!$storage instanceof \Duplicator\Addons\AmazonS3Addon\Models\AmazonS3Storage) {
                continue;
            }

            $storage->purgeMultipartUpload();
        }
    }

    /**
     *
     * @return string
     */
    public static function getAddonPath()
    {
        return __DIR__;
    }

    /**
     *
     * @return string
     */
    public static function getAddonFile()
    {
        return __FILE__;
    }
}

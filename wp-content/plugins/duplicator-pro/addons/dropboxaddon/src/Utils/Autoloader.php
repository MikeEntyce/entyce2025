<?php

namespace Duplicator\Addons\DropboxAddon\Utils;

use Duplicator\Addons\DropboxAddon\DropboxAddon;
use Duplicator\Utils\AbstractAutoloader;

class Autoloader extends AbstractAutoloader
{
    const ROOT_VENDOR = 'VendorDuplicator\\Dropbox\\';

    const VENDOR_PATH = DropboxAddon::ADDON_PATH . '/vendor-prefixed/';
    /**
     * Register autoloader function
     *
     * @return void
     */
    public static function register()
    {
        spl_autoload_register([__CLASS__, 'load']);

        require_once DropboxAddon::ADDON_PATH . '/vendor-prefixed/guzzlehttp/guzzle/src/functions_include.php';
        require_once DropboxAddon::ADDON_PATH . '/vendor-prefixed/guzzlehttp/promises/src/functions_include.php';
        require_once DropboxAddon::ADDON_PATH . '/vendor-prefixed/guzzlehttp/psr7/src/functions_include.php';
        require_once DropboxAddon::ADDON_PATH . '/vendor-prefixed/ralouphie/getallheaders/src/getallheaders.php';
    }

    /**
     * Load class
     *
     * @param string $className class name
     *
     * @return void
     */
    public static function load($className)
    {
        if (strpos($className, self::ROOT_VENDOR) === 0) {
            foreach (self::getNamespacesVendorMapping() as $namespace => $mappedPath) {
                if (strpos($className, $namespace) !== 0) {
                    continue;
                }

                $filepath = self::getFilenameFromClass($className, $namespace, $mappedPath);
                if (file_exists($filepath)) {
                    include $filepath;
                    return;
                }
            }
        }
    }

    /**
     * Return namespace mapping
     *
     * @return string[]
     */
    protected static function getNamespacesVendorMapping()
    {
        return [
            self::ROOT_VENDOR . 'Google\\Service'     => self::VENDOR_PATH . 'google/apiclient-services/src',
            self::ROOT_VENDOR . 'Google\\Auth'        => self::VENDOR_PATH . 'google/auth/src',
            self::ROOT_VENDOR . 'Google'              => self::VENDOR_PATH . 'google/apiclient/src',
            self::ROOT_VENDOR . 'GuzzleHttp\\Promise' => self::VENDOR_PATH . 'guzzlehttp/promises/src',
            self::ROOT_VENDOR . 'GuzzleHttp\\Psr7'    => self::VENDOR_PATH . 'guzzlehttp/psr7/src',
            self::ROOT_VENDOR . 'GuzzleHttp'          => self::VENDOR_PATH . 'guzzlehttp/guzzle/src',
            self::ROOT_VENDOR . 'Psr\\Http\\Message'  => self::VENDOR_PATH . 'psr/http-message/src',
            self::ROOT_VENDOR . 'Psr\\Log'            => self::VENDOR_PATH . 'psr/log/Psr/Log',
            self::ROOT_VENDOR . 'Psr\\Cache'          => self::VENDOR_PATH . 'psr/cache/src',
            self::ROOT_VENDOR . 'Spatie\\Dropbox'     => self::VENDOR_PATH . 'spatie/dropbox-api/src',
        ];
    }
}

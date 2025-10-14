<?php

/**
 * Auloader calsses
 *
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

 namespace Duplicator\Addons\AmazonS3Addon\Utils;

use Duplicator\Addons\AmazonS3Addon\AmazonS3Addon;
use Duplicator\Utils\AbstractAutoloader;

/**
 * Autoloader calss, dont user Duplicator library here
 */
final class Autoloader extends AbstractAutoloader
{
    const VENDOR_PATH = AmazonS3Addon::ADDON_PATH . '/vendor-prefixed/';

    /**
     * Register autoloader function
     *
     * @return void
     */
    public static function register()
    {
        spl_autoload_register([__CLASS__, 'load']);

        self::loadFiles();
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

    /**
     * Load necessary files
     *
     * @return void
     */
    private static function loadFiles()
    {
        $files = [
            '/paragonie/random_compat/lib/random.php',
            '/ralouphie/getallheaders/src/getallheaders.php',
            '/guzzlehttp/promises/src/functions_include.php',
            '/guzzlehttp/psr7/src/functions_include.php',
            '/symfony/polyfill-intl-normalizer/bootstrap.php',
            '/symfony/polyfill-php70/bootstrap.php',
            '/symfony/polyfill-php72/bootstrap.php',
            '/symfony/polyfill-intl-idn/bootstrap.php',
            '/symfony/polyfill-mbstring/bootstrap.php',
            '/guzzlehttp/guzzle/src/functions_include.php',
            '/mtdowling/jmespath.php/src/JmesPath.php',
            '/aws/aws-sdk-php/src/functions.php',
        ];

        foreach ($files as $file) {
            require_once self::VENDOR_PATH . $file;
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
            self::ROOT_VENDOR . 'Symfony\\Polyfill\\Intl\\Idn'        => AmazonS3Addon::getAddonPath() . '/vendor-prefixed/symfony/polyfill-intl-idn',
            self::ROOT_VENDOR . 'Symfony\\Polyfill\\Intl\\Normalizer' => AmazonS3Addon::getAddonPath() . '/vendor-prefixed/symfony/polyfill-intl-normalizer',
            self::ROOT_VENDOR . 'Symfony\\Polyfill\\Mbstring'         => AmazonS3Addon::getAddonPath() . '/vendor-prefixed/symfony/polyfill-mbstring',
            self::ROOT_VENDOR . 'Symfony\\Polyfill\\Php70'            => AmazonS3Addon::getAddonPath() . '/vendor-prefixed/symfony/polyfill-php70',
            self::ROOT_VENDOR . 'Symfony\\Polyfill\\Php72'            => AmazonS3Addon::getAddonPath() . '/vendor-prefixed/symfony/polyfill-php72',
            self::ROOT_VENDOR . 'JmesPath'                            => AmazonS3Addon::getAddonPath() . '/vendor-prefixed/mtdowling/jmespath.php/src',
            self::ROOT_VENDOR . 'Psr\\Http\\Message'                  => AmazonS3Addon::getAddonPath() . '/vendor-prefixed/psr/http-message/src',
            self::ROOT_VENDOR . 'GuzzleHttp\\Promise'                 => AmazonS3Addon::getAddonPath() . '/vendor-prefixed/guzzlehttp/promises/src',
            self::ROOT_VENDOR . 'GuzzleHttp\\Psr7'                    => AmazonS3Addon::getAddonPath() . '/vendor-prefixed/guzzlehttp/psr7/src',
            self::ROOT_VENDOR . 'GuzzleHttp'                          => AmazonS3Addon::getAddonPath() . '/vendor-prefixed/guzzlehttp/guzzle/src',
            self::ROOT_VENDOR . 'Aws'                                 => AmazonS3Addon::getAddonPath() . '/vendor-prefixed/aws/aws-sdk-php/src',
        ];
    }
}

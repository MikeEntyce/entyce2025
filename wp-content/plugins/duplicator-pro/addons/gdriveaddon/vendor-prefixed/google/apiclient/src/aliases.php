<?php

namespace VendorDuplicator;

if (\class_exists('VendorDuplicator\\Google_Client', \false)) {
    // Prevent error with preloading in PHP 7.4
    // @see https://github.com/googleapis/google-api-php-client/issues/1976
    return;
}
$classMap = ['VendorDuplicator\\Google\\Client' => 'VendorDuplicator\\Google_Client', 'VendorDuplicator\\Google\\Service' => 'VendorDuplicator\\Google_Service', 'VendorDuplicator\\Google\\AccessToken\\Revoke' => 'VendorDuplicator\\Google_AccessToken_Revoke', 'VendorDuplicator\\Google\\AccessToken\\Verify' => 'VendorDuplicator\\Google_AccessToken_Verify', 'VendorDuplicator\\Google\\Model' => 'VendorDuplicator\\Google_Model', 'VendorDuplicator\\Google\\Utils\\UriTemplate' => 'VendorDuplicator\\Google_Utils_UriTemplate', 'VendorDuplicator\\Google\\AuthHandler\\Guzzle6AuthHandler' => 'VendorDuplicator\\Google_AuthHandler_Guzzle6AuthHandler', 'VendorDuplicator\\Google\\AuthHandler\\Guzzle7AuthHandler' => 'VendorDuplicator\\Google_AuthHandler_Guzzle7AuthHandler', 'VendorDuplicator\\Google\\AuthHandler\\Guzzle5AuthHandler' => 'VendorDuplicator\\Google_AuthHandler_Guzzle5AuthHandler', 'VendorDuplicator\\Google\\AuthHandler\\AuthHandlerFactory' => 'VendorDuplicator\\Google_AuthHandler_AuthHandlerFactory', 'VendorDuplicator\\Google\\Http\\Batch' => 'VendorDuplicator\\Google_Http_Batch', 'VendorDuplicator\\Google\\Http\\MediaFileUpload' => 'VendorDuplicator\\Google_Http_MediaFileUpload', 'VendorDuplicator\\Google\\Http\\REST' => 'VendorDuplicator\\Google_Http_REST', 'VendorDuplicator\\Google\\Task\\Retryable' => 'VendorDuplicator\\Google_Task_Retryable', 'VendorDuplicator\\Google\\Task\\Exception' => 'VendorDuplicator\\Google_Task_Exception', 'VendorDuplicator\\Google\\Task\\Runner' => 'VendorDuplicator\\Google_Task_Runner', 'VendorDuplicator\\Google\\Collection' => 'VendorDuplicator\\Google_Collection', 'VendorDuplicator\\Google\\Service\\Exception' => 'VendorDuplicator\\Google_Service_Exception', 'VendorDuplicator\\Google\\Service\\Resource' => 'VendorDuplicator\\Google_Service_Resource', 'VendorDuplicator\\Google\\Exception' => 'VendorDuplicator\\Google_Exception'];
foreach ($classMap as $class => $alias) {
    \class_alias($class, $alias);
}
/**
 * This class needs to be defined explicitly as scripts must be recognized by
 * the autoloader.
 */
class Google_Task_Composer extends \VendorDuplicator\Google\Task\Composer
{
}
/**
 * This class needs to be defined explicitly as scripts must be recognized by
 * the autoloader.
 */
\class_alias('VendorDuplicator\\Google_Task_Composer', 'VendorDuplicator\\Google_Task_Composer', \false);
/** @phpstan-ignore-next-line */
if (\false) {
    class Google_AccessToken_Revoke extends \VendorDuplicator\Google\AccessToken\Revoke
    {
    }
    class Google_AccessToken_Verify extends \VendorDuplicator\Google\AccessToken\Verify
    {
    }
    class Google_AuthHandler_AuthHandlerFactory extends \VendorDuplicator\Google\AuthHandler\AuthHandlerFactory
    {
    }
    class Google_AuthHandler_Guzzle5AuthHandler extends \VendorDuplicator\Google\AuthHandler\Guzzle5AuthHandler
    {
    }
    class Google_AuthHandler_Guzzle6AuthHandler extends \VendorDuplicator\Google\AuthHandler\Guzzle6AuthHandler
    {
    }
    class Google_AuthHandler_Guzzle7AuthHandler extends \VendorDuplicator\Google\AuthHandler\Guzzle7AuthHandler
    {
    }
    class Google_Client extends \VendorDuplicator\Google\Client
    {
    }
    class Google_Collection extends \VendorDuplicator\Google\Collection
    {
    }
    class Google_Exception extends \VendorDuplicator\Google\Exception
    {
    }
    class Google_Http_Batch extends \VendorDuplicator\Google\Http\Batch
    {
    }
    class Google_Http_MediaFileUpload extends \VendorDuplicator\Google\Http\MediaFileUpload
    {
    }
    class Google_Http_REST extends \VendorDuplicator\Google\Http\REST
    {
    }
    class Google_Model extends \VendorDuplicator\Google\Model
    {
    }
    class Google_Service extends \VendorDuplicator\Google\Service
    {
    }
    class Google_Service_Exception extends \VendorDuplicator\Google\Service\Exception
    {
    }
    class Google_Service_Resource extends \VendorDuplicator\Google\Service\Resource
    {
    }
    class Google_Task_Exception extends \VendorDuplicator\Google\Task\Exception
    {
    }
    interface Google_Task_Retryable extends \VendorDuplicator\Google\Task\Retryable
    {
    }
    class Google_Task_Runner extends \VendorDuplicator\Google\Task\Runner
    {
    }
    class Google_Utils_UriTemplate extends \VendorDuplicator\Google\Utils\UriTemplate
    {
    }
}

<?php

namespace Duplicator\Addons\OneDriveAddon;

use DUP_PRO_Log;
use Duplicator\Models\Storages\AbstractStorageAdapter;
use Duplicator\Models\Storages\StoragePathInfo;
use Duplicator\Utils\OAuth\TokenEntity;
use Exception;

/**
 * @method OneDriveStoragePathInfo getPathInfo($path)
 */
class OnedriveAdapter extends AbstractStorageAdapter
{
    /** @var TokenEntity The token object to use for authentication */
    protected $token = null;
    /** @var HttpClient */
    protected $http = null;
    /** @var string The ID of the folder to use for storage */
    protected $storageFolderId = '';
    /** @var string The name of the folder to use for storage */
    protected $storageFolderName = '';
    /** @var ?OneDriveStoragePathInfo The app folder object */
    protected $appFolder = null;
    /** @var string Base URL for API requests */
    protected $baseUrl = 'https://graph.microsoft.com/v1.0';
    /** @var bool */
    protected $sslVerify = true;
    /** @var string If empty use server cert else use custom cert path */
    protected $sslCert = '';
    /** @var int The microsecond at which the current operation started */
    protected $startTime = 0;

    /**
     * Class constructor
     *
     * @param TokenEntity $token           The token object to use for authentication
     * @param string      $storageFolder   The folder to use for storage
     * @param string      $storageFolderId The ID of the folder to use for storage
     * @param bool        $sslVerify       If true, use SSL
     * @param string      $sslCert         If empty use server cert
     */
    public function __construct(
        TokenEntity $token,
        $storageFolder,
        $storageFolderId = '',
        $sslVerify = true,
        $sslCert = ''
    ) {
        $this->token             = $token;
        $this->storageFolderName = trim($storageFolder, '/');
        $this->storageFolderId   = $storageFolderId;
        $this->sslVerify         = $sslVerify;
        $this->sslCert           = $sslCert;
        $this->http              = new HttpClient($this->baseUrl, $token->getAccessToken(), $this->sslVerify, $this->sslCert);
    }

    /**
     * Initialize the storage adapter
     *
     * @param string $errorMsg The error message to modify if initialization fails
     *
     * @return bool
     */
    public function initialize(&$errorMsg = '')
    {
        if (! $this->token->isValid()) {
            $errorMsg = __('Invalid token supplied for OneDrive', 'duplicator-pro');
            return false;
        }
        if (! $this->exists('/') && ! $this->createDir('/')) {
            $errorMsg = __('Unable to create root directory for OneDrive', 'duplicator-pro');
            return false;
        }
        if (empty($this->storageFolderId)) {
            $root = $this->getPathInfo('/');
            if (! $root || ! $root->exists) {
                $errorMsg = 'OneDrive root folder does not exist.';
                return false;
            }
            $this->storageFolderId = $root->id;
        }
        return true;
    }

    /**
     * Destroy the storage adapter
     *
     * @return bool
     */
    public function destroy()
    {
        $storageFolder = $this->getPathInfo('/');
        if (! $storageFolder || ! $storageFolder->exists) {
            return true; // nothing to delete
        }

        return $this->delete('/', true);
    }

    /**
     * Check if the storage adapter is valid
     *
     * @param string $errorMsg The error message to modify if validation fails
     *
     * @return bool
     */
    public function isValid(&$errorMsg = '')
    {
        if (!$this->token->isValid() && !$this->token->refresh()) {
            $errorMsg = 'Invalid token supplied or token refresh failed.';
            return false;
        }
        $root = $this->getPathInfo('/');
        if (! $root || ! $root->exists) {
            $errorMsg = 'OneDrive root folder does not exist.';
            return false;
        }
        return true;
    }

    /**
     * Create a directory in the storage
     * If the given path is a nested path, it will create all the parent directories
     *
     * @param string $path The path to create
     *
     * @return bool
     */
    protected function realCreateDir($path)
    {
        if (empty($this->storageFolderId)) {
            $parentFolder = $this->getAppFolder()->id;
            $path         = trim($this->storageFolderName . '/' . trim($path, '/'), '/');
        } else {
            $parentFolder = $this->storageFolderId;
            $path         = trim($path, '/');
        }

        $folders = explode('/', $this->formatPath($path));

        $completed = '';
        foreach ($folders as $folder) {
            $item = $this->getItemDetailsByPath($completed . '/' . $folder);
            if (!isset($item['id'])) {
                try {
                    $item = $this->createDriveDirectory($parentFolder, $folder);
                    if (!isset($item['id'])) {
                        return false;
                    }
                } catch (Exception $e) {
                    return false;
                }
            }
            $completed   .= '/' . $folder;
            $parentFolder = $item['id'];
        }

        return true;
    }

    /**
     * Create a file which is less that 4MB in the storage
     *
     * @see https://github.com/OneDrive/onedrive-api-docs/blob/live/docs/rest-api/api/driveitem_put_content.md
     *
     * @param string $path    The path in which the file will be created
     * @param string $content The content of the file
     *
     * @return false|int
     */
    protected function realCreateFile($path, $content)
    {
        // maximum content length is 4MB
        if (strlen($content) > 4 * 1024 * 1024) {
            return false;
        }

        $file = ltrim($this->formatPath($path), '/');

        try {
            $response = $this->http->put("/me/drive/items/{$this->storageFolderId}:/{$file}:/content", $content, ['Content-Type' => 'text/plain']);
        } catch (Exception $e) {
            // Request failed from curl
            DUP_PRO_Log::infoTrace("Failed to create file in OneDrive: {$e->getMessage()}");
            return false;
        }
        $response = json_decode($response['body'], true);

        if (!isset($response['id'])) {
            return false;
        }

        return (int) $response['size'];
    }

    /**
     * Delete relative path from storage root.
     *
     * @see https://github.com/OneDrive/onedrive-api-docs/blob/live/docs/rest-api/api/driveitem_delete.md
     *
     * @param string $path      The path or drive item id to delete
     * @param bool   $recursive Whether to delete recursively
     *
     * @return bool
     */
    protected function realDelete($path, $recursive = false)
    {
        if (! $this->exists($path)) {
            return true;
        }

        $info = $this->getItemDetailsByPath($path);

        if (! $recursive && isset($info['folder']) && $info['folder']['childCount'] > 0) {
            return false;
        }

        try {
            $response = $this->http->delete("/me/drive/items/{$info['id']}");
        } catch (Exception $e) {
            // Request failed from curl
            DUP_PRO_Log::infoTrace("Failed to delete file in OneDrive: {$e->getMessage()}");
            return false;
        }

        return $response['code'] === 204;
    }

    /**
     * Get the contents of a file
     *
     * @param string $path The path to the file
     *
     * @return false|string
     */
    public function getFileContent($path)
    {
        $item = $this->getItemDetailsByPath($path);
        if (!isset($item['@microsoft.graph.downloadUrl'])) {
            return false;
        }

        return file_get_contents($item['@microsoft.graph.downloadUrl']);
    }

    /**
     * Get path info and cache it, is path not exists return path info with exists property set to false.
     *
     * @param string $path Relative storage path, if empty, return root path info.
     *
     * @return OneDriveStoragePathInfo|false The path info or false on error.
     */
    protected function getRealPathInfo($path)
    {
        $path = '/' . ltrim($path, '/');

        $item = $this->getItemDetailsByPath($path);

        return $this->buildStoragePathInfo($item);
    }

    /**
     * Move a file or directory. The destination path must not exist.
     *
     * @see https://github.com/OneDrive/onedrive-api-docs/blob/live/docs/rest-api/api/driveitem_move.md
     *
     * @param string $oldPath The path to the file or directory to move
     * @param string $newPath The destination path
     *
     * @return bool
     */
    protected function realMove($oldPath, $newPath)
    {
        $oldItem          = $this->getPathInfo($oldPath);
        $newDirectoryItem = $this->getPathInfo(dirname($newPath));
        if (!$oldItem || !$newDirectoryItem) {
            return false;
        }
        try {
            $response = $this->http->request('PATCH', "/me/drive/items/{$oldItem->id}", [
                'parentReference' => [
                    'id' => $newDirectoryItem->id,
                ],
                'name'            => basename($newPath),
            ]);
        } catch (Exception $e) {
            // Request failed error from curl
            DUP_PRO_Log::infoTrace("Failed to move file in OneDrive: {$e->getMessage()}");
            return false;
        }
        $response = json_decode($response['body'], true);

        return isset($response['id']);
    }

    /**
     * @param string $path    The path to the directory to scan
     * @param bool   $files   Whether to include files
     * @param bool   $folders Whether to include folders
     *
     * @return string[]
     */
    public function scanDir($path, $files = true, $folders = true)
    {
        $path = '/' . ltrim($path, '/');
        if ($path !== '/') {
            // Paths under the storage folder must be prefixed with a colon, no need to do this for the storage folder itself
            $path = ":{$path}:";
        }
        try {
            $response = $this->http->get("/me/drive/items/{$this->storageFolderId}{$path}/children");
        } catch (Exception $e) {
            DUP_PRO_Log::infoTrace("Failed to scan dir in OneDrive: {$e->getMessage()}");
            return [];
        }
        $items = json_decode($response['body'], true);

        if (!isset($items['value'])) {
            return [];
        } else {
            $items = $items['value'];
        }

        foreach ($items as $index => $item) {
            $item          = $this->buildStoragePathInfo($item);
            $items[$index] = $item->name;
            if (!$folders && $item->isDir) {
                unset($items[$index]);
            }
            if (!$files && !$item->isDir) {
                unset($items[$index]);
            }
        }

        return $items;
    }

    /**
     * Check if a directory is empty
     *
     * @param string   $path    The path to the directory
     * @param string[] $filters An array of filters to apply
     *
     * @return bool
     */
    public function isDirEmpty($path, $filters = [])
    {
        $item = $this->getItemDetailsByPath($path);
        if (!isset($item['folder'])) {
            return false;
        }
        if ($item['folder']['childCount'] === 0) {
            return true;
        } elseif (empty($filters)) {
            // we have no filters, and the folder is not empty, so it must contain something
            return false;
        }
        $regexFilters = $normalFilters = [];

        foreach ($filters as $filter) {
            if ($filter[0] === '/' && substr($filter, -1) === '/') {
                $regexFilters[] = $filter; // It's a regex filter as it starts and ends with a slash
            } else {
                $normalFilters[] = $filter;
            }
        }

        $contents = $this->scanDir($path);
        foreach ($contents as $item) {
            if (in_array($item, $normalFilters)) {
                continue;
            }

            foreach ($regexFilters as $regexFilter) {
                if (preg_match($regexFilter, $item) === 1) {
                    continue 2;
                }
            }

            return false;
        }

        return true;
    }

    /**
     * Start tracking the time for the current operation
     *
     * @return void
     */
    protected function startTrackingTime()
    {
        $this->startTime = (int) (microtime(true) * SECONDS_IN_MICROSECONDS);
    }

    /**
     * Get the elapsed time since the start of the current operation
     *
     * @return float
     */
    protected function getElapsedTime()
    {
        return (int) (microtime(true) * SECONDS_IN_MICROSECONDS) - $this->startTime;
    }

    /**
     * Check if the operation has reached the timeout
     *
     * @param int $timeout The timeout in microseconds
     *
     * @return bool
     */
    protected function hasReachedTimeout($timeout)
    {
        return $timeout > 0 && $this->getElapsedTime() > $timeout;
    }

    /**
     * Copy local file to storage, partial copy is supported.
     * If destination file exists, it will be overwritten.
     * If offset is less than the destination file size, the file will be truncated.
     *
     * @param string              $sourceFile  The source file full path
     * @param string              $storageFile Storage destination path
     * @param int<0,max>          $offset      The offset where the data starts.
     * @param int                 $length      The maximum number of bytes read. Default to -1 (read all the remaining buffer).
     * @param int                 $timeout     The timeout for the copy operation in microseconds. Default to 0 (no timeout).
     * @param array<string,mixed> $extraData   Extra data to pass to copy function and updated during copy.
     *                                         Used for storages that need to maintain persistent data during copy intra-session.
     *
     * @return false|int The number of bytes that were written to the file, or false on failure.
     */
    protected function realCopyToStorage($sourceFile, $storageFile, $offset = 0, $length = -1, $timeout = 0, &$extraData = [])
    {
        $this->startTrackingTime();

        if (! isset($extraData['uploadSession'])) {
            $extraData['uploadSession'] = $this->createUploadSession($storageFile);
        }
        $uploadSession = $extraData['uploadSession'];
        if (! $uploadSession) {
            DUP_PRO_Log::infoTrace("Failed to create upload session for {$storageFile}, try uploading again.");
            return false;
        }
        $expiration       = strtotime($uploadSession['expirationDateTime']);
        $fileSize         = filesize($sourceFile);
        $defaultChunkSize = MB_IN_BYTES; // 1MB
        $chunkSize        = $length > 0 ? $length : $defaultChunkSize;
        $stream           = fopen($sourceFile, 'rb');
        $bytesRemaining   = $fileSize - $offset;
        $bytesUploaded    = $offset;
        fseek($stream, $bytesUploaded);

        // We stop uploading if we have reached the timeout or if we have uploaded the entire file.
        while ($bytesRemaining > 0 && ! $this->hasReachedTimeout($timeout)) {
            if (time() > $expiration) {
                DUP_PRO_Log::infoTrace("OneDrive Upload session expired for {$storageFile}, try uploading again.");
                unset($extraData['uploadSession']);
                return false;
            }
            $chunkSize = min($chunkSize, $bytesRemaining);
            $chunk     = fread($stream, $chunkSize);
            try {
                //$this->http->setTimeout($timeout / SECONDS_IN_MICROSECONDS);
                $response = $this->http->put($uploadSession['uploadUrl'], $chunk, [
                    'Content-Length' => $chunkSize,
                    'Content-Range'  => sprintf('bytes %d-%d/%d', $bytesUploaded, $bytesUploaded + $chunkSize - 1, $fileSize),
                ]);
            } catch (Exception $e) {
                DUP_PRO_Log::infoTrace("Failed to copy file to OneDrive: {$e->getMessage()}");
                return false;
            }

            $bytesUploaded  += $chunkSize;
            $bytesRemaining -= $chunkSize;

            if (in_array($response['code'], [200, 201])) {
                // We have finished uploading the file
                unset($extraData['uploadSession']);
                return $length > 0 ? $length : $fileSize; // We return the original requested chunksize for historical reasons.
            }
            // At this point only 202 is expected, which means we have to continue uploading
            if ($response['code'] !== 202) {
                // 4XX means we cannot resume uploading. 5XX means we can retry later.
                DUP_PRO_Log::infoTrace("OneDrive Upload error for {$storageFile}, try uploading again.");
                DUP_PRO_Log::infoTrace("OneDrive responded with code {$response['code']} & sent us: " . $response['body']);
                unset($extraData['uploadSession']);
                return false;
            }
            $responseData = json_decode($response['body'], true);
            if (isset($responseData['expirationDateTime'])) {
                $extraData['uploadSession']['expirationDateTime'] = $responseData['expirationDateTime'];
            }

            // A specific length was requested, we have uploaded the requested length.
            if ($length > 0) {
                break;
            }
        }
        // We return the original requested chunksize for historical reasons.
        // @todo: This SHOULD be the number of bytes that were written to the file.
        return $length > 0 ? $length : $fileSize;
    }

    /**
     * Get the app folder object
     *
     * @see https://github.com/OneDrive/onedrive-api-docs/blob/live/docs/rest-api/api/drive_get_specialfolder.md
     *
     * @return OneDriveStoragePathInfo|false
     */
    protected function getAppFolder()
    {
        if ($this->appFolder !== null) {
            return $this->appFolder;
        }
        try {
            $response = $this->http->get('/me/drive/special/approot');
        } catch (Exception $e) {
            DUP_PRO_Log::infoTrace("Failed to get app folder in OneDrive: {$e->getMessage()}");
            return false;
        }
        $item = json_decode($response['body'], true);

        return $this->appFolder = $this->buildStoragePathInfo($item);
    }

    /**
     * Get the details of an item by path
     *
     * @see https://github.com/OneDrive/onedrive-api-docs/blob/live/docs/rest-api/api/driveitem_get.md#http-request
     *
     * @param string $path The path to the item
     *
     * @return array<string, mixed>|false
     */
    public function getItemDetailsByPath($path)
    {
        $path = '/' . ltrim($this->formatPath($path), '/');
        if (empty($this->storageFolderId)) {
            $parent = $this->getAppFolder()->id;
            $path   = '/' . trim($this->storageFolderName . $path, '/');
        } else {
            $parent = $this->storageFolderId;
        }
        try {
            $response = $this->http->get("me/drive/items/{$parent}:{$path}");
        } catch (Exception $e) {
            DUP_PRO_Log::infoTrace("Failed to get item details in OneDrive: {$e->getMessage()}");
            return false;
        }

        return json_decode($response['body'], true);
    }

    /**
     * Create a directory in the storage
     *
     * @see https://github.com/OneDrive/onedrive-api-docs/blob/live/docs/rest-api/api/driveitem_post_children.md
     *
     * @param string $parent    The ID of the parent directory
     * @param string $directory The name of the directory to create
     *
     * @return array<string, mixed>
     */
    protected function createDriveDirectory($parent, $directory)
    {
        try {
            $response = $this->http->post('me/drive/items/' . $parent . '/children', [
                'name'                              => $this->formatPath($directory),
                'folder'                            => new \stdClass(),
                '@microsoft.graph.conflictBehavior' => 'fail',
            ]);
        } catch (Exception $e) {
            DUP_PRO_Log::infoTrace("Failed to create directory in OneDrive: {$e->getMessage()}");
            return [];
        }

        return json_decode($response['body'], true);
    }

    /**
     * Create a new upload session
     *
     * @see https://github.com/OneDrive/onedrive-api-docs/blob/live/docs/rest-api/api/driveitem_createuploadsession.md
     *
     * @param string $targetFile The path to the destination file
     *
     * @return array{uploadUrl: string, expirationDateTime: string}|false
     */
    protected function createUploadSession($targetFile)
    {
        $targetFile = $this->formatPath($targetFile);
        $parent     = $this->storageFolderId;
        $file       = basename($targetFile);
        if ($file !== $targetFile) {
            $directory = dirname($targetFile);
            $this->createDir($directory);
            $targetDir = $this->getPathInfo($directory);
            $parent    = $targetDir->id;
        }

        try {
            $response = $this->http->post("me/drive/items/{$parent}:/{$file}:/createUploadSession", [
                'item' => ["name" => $file],
            ]);
        } catch (Exception $e) {
            // Request failed from curl
            DUP_PRO_Log::infoTrace("Failed to create upload session in OneDrive: {$e->getMessage()}");
            return false;
        }

        if ($response['code'] !== 200) {
            // Failed to create the upload session, error exists in the response body
            return false;
        }
        return json_decode($response['body'], true);
    }

    /**
     * Build a StoragePathInfo object from the given array
     *
     * @param array<string, mixed> $item The array to build the object from
     *
     * @return OneDriveStoragePathInfo|false
     */
    protected function buildStoragePathInfo($item)
    {
        $info = new OneDriveStoragePathInfo();

        if (!isset($item['id'])) {
            return $info;
        }
        $info->exists   = true;
        $info->id       = $item['id'];
        $info->name     = $item['name'];
        $info->isDir    = isset($item['folder']);
        $info->created  = isset($item['createdDateTime']) ? strtotime($item['createdDateTime']) : 0;
        $info->modified = isset($item['lastModifiedDateTime']) ? strtotime($item['lastModifiedDateTime']) : 0;
        $info->size     = isset($item['size']) ? $item['size'] : 0;
        $info->webUrl   = isset($item['webUrl']) ? $item['webUrl'] : '';
        if (isset($item['file'])) {
            $info->file = $item['file'];
        }
        if (isset($item['createdBy']['user'])) {
            $info->user = $item['createdBy']['user'];
        }
        if (isset($item['parentReference']['path'])) {
            // path can be different from the name, e.g. when the file is in a subdirectory
            $fullPath              = $item['parentReference']['path'] . '/' . $info->name;
            $storagePosition       = strpos($fullPath, $this->storageFolderName); // calculate the position of the storage folder name
            $filePathStartPosition = $storagePosition + strlen($this->storageFolderName) + 1; // file path starts after the storage folder name & the slash
            $info->path            = substr($fullPath, $filePathStartPosition);
        }

        return $info;
    }

    /**
     * Generate info on create dir
     *
     * @param string $path Dir path
     *
     * @return StoragePathInfo
     */
    protected function generateCreateDirInfo($path)
    {
        return $this->getRealPathInfo($path);
    }

    /**
     * Generate info on delete item
     *
     * @param string $path Item path
     *
     * @return StoragePathInfo
     */
    protected function generateDeleteInfo($path)
    {
        $info           = new OneDriveStoragePathInfo();
        $info->path     = $path;
        $info->exists   = false;
        $info->isDir    = false;
        $info->size     = 0;
        $info->created  = 0;
        $info->modified = 0;
        return $info;
    }

    /**
     * Format a path for OneDrive. This handles special characters in the path
     *
     * @param string $path The path to format
     *
     * @return string
     */
    protected function formatPath($path)
    {
        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }

    /**
     * Copy storage file to local file, partial copy is supported.
     * If destination file exists, it will be overwritten.
     * If offset is less than the destination file size, the file will be truncated.
     *
     * @param string              $storageFile The storage file path
     * @param string              $destFile    The destination local file full path
     * @param int<0,max>          $offset      The offset where the data starts.
     * @param int                 $length      The maximum number of bytes read. Default to -1 (read all the remaining buffer).
     * @param int                 $timeout     The timeout for the copy operation in microseconds. Default to 0 (no timeout).
     * @param array<string,mixed> $extraData   Extra data to pass to copy function and updated during copy.
     *                                         Used for storages that need to maintain persistent data during copy intra-session.
     *
     * @return false|int The number of bytes that were written to the file, or false on failure.
     */
    public function copyFromStorage($storageFile, $destFile, $offset = 0, $length = -1, $timeout = 0, &$extraData = [])
    {
        $this->startTrackingTime();
        //$this->http->setTimeout($timeout / SECONDS_IN_MICROSECONDS);
        if ($length < 0) {
            // We can use the download URL to download the file in one go
            $content = $this->getFileContent($storageFile);
            if ($content === false) {
                return false;
            }
            $content = substr($content, $offset);
            if (file_put_contents($destFile, $content) === false) {
                return false;
            }
            return strlen($content);
        }

        if (! isset($extraData['downloadUrl'])) {
            $item = $this->getItemDetailsByPath($storageFile);
            if (!isset($item['@microsoft.graph.downloadUrl'])) {
                return false;
            }
            $extraData['downloadUrl'] = $item['@microsoft.graph.downloadUrl'];
            if (file_put_contents($destFile, '') === false) {
                DUP_PRO_Log::infoTrace("[OnedriveAddon] Failed to open file for writing: {$destFile}");
                return false;
            }
        }

        $downloadUrl = $extraData['downloadUrl'];

        $range = "bytes={$offset}-" . ($offset + $length - 1);
        try {
            $response = $this->http->get($downloadUrl, [], ['Range' => $range]);
        } catch (Exception $e) {
            DUP_PRO_Log::infoTrace("[OnedriveAddon] Failed to download file: {$storageFile}. Error: {$e->getMessage()}");
            return false;
        }
        if ($response['code'] !== 206 && $response['code'] !== 200) {
            DUP_PRO_Log::infoTrace("[OnedriveAddon] Failed to download file: {$storageFile}. Response " . $response['body']);
            return false;
        }
        file_put_contents($destFile, $response['body'], FILE_APPEND);

        return $length;
    }
}

<?php

namespace Duplicator\Addons\DropboxAddon\Models;

use Duplicator\Addons\DropboxAddon\Utils\DropboxClient;
use Duplicator\Models\Storages\AbstractStorageAdapter;
use Duplicator\Models\Storages\StoragePathInfo;
use VendorDuplicator\Dropbox\Spatie\Dropbox\UploadSessionCursor;

class DropboxAdapter extends AbstractStorageAdapter
{
    /** @var string */
    protected $accessToken = '';
    /** @var DropboxClient */
    protected $client = null;
    /** @var string */
    protected $storageFolder = '';
    /** @var bool */
    protected $sslVerify = true;
    /** @var string If empty use server cert else use custom cert path */
    protected $sslCert = '';
    /** @var bool */
    protected $ipv4Only = false;

    /**
     * @param string $accessToken   Dropbox access token.
     * @param string $storageFolder Dropbox storage folder.
     * @param bool   $sslVerify     If true, use SSL
     * @param string $sslCert       If empty use server cert
     * @param bool   $ipv4Only      If true, use IPv4 only
     */
    public function __construct(
        $accessToken,
        $storageFolder = '',
        $sslVerify = true,
        $sslCert = '',
        $ipv4Only = false
    ) {
        $this->accessToken   = $accessToken;
        $this->storageFolder = '/' . trim($storageFolder, '/') . '/';
        $this->sslVerify     = $sslVerify;
        $this->sslCert       = $sslCert;
        $this->ipv4Only      = $ipv4Only;
        $this->client        = new DropboxClient($accessToken, null, DropboxClient::MAX_CHUNK_SIZE, 0, $sslVerify, $sslCert, $ipv4Only);
    }

    /**
     * Get the Dropbox client.
     *
     * @return DropboxClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Initialize the storage on creation.
     *
     * @param string $errorMsg The error message if storage is invalid.
     *
     * @return bool true on success or false on failure.
     */
    public function initialize(&$errorMsg = '')
    {
        if (! $this->exists('/')) {
            try {
                $this->createDir('/');
            } catch (\Exception $e) {
                \DUP_PRO_Log::trace($e->getMessage());
                $errorMsg = $e->getMessage();
                return false;
            }
        }
        return true;
    }

    /**
     * Destroy the storage on deletion.
     *
     * @return bool true on success or false on failure.
     */
    public function destroy()
    {
        $this->delete('/', true);

        return true;
    }

    /**
     * Check if storage is valid and ready to use.
     *
     * @param string $errorMsg The error message if storage is invalid.
     *
     * @return bool
     */
    public function isValid(&$errorMsg = '')
    {
        try {
            $this->client->getMetadata($this->storageFolder);
        } catch (\Exception $e) {
            \DUP_PRO_Log::trace("Dropbox storage is invalid: " . $e->getMessage());
            $errorMsg = $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * Create the directory specified by pathname, recursively if necessary.
     *
     * @param string $path The directory path.
     *
     * @return bool true on success or false on failure.
     */
    protected function realCreateDir($path)
    {
        $path = $this->formatPath($path);

        try {
            $this->client->createFolder($path);
        } catch (\Exception $e) {
            \DUP_PRO_Log::trace($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Create file with content.
     *
     * @param string $path    The path to file.
     * @param string $content The content of file.
     *
     * @return false|int The number of bytes that were written to the file, or false on failure.
     */
    protected function realCreateFile($path, $content)
    {
        $path = $this->formatPath($path);

        try {
            $response = $this->client->upload($path, $content, 'overwrite');
        } catch (\Exception $e) {
            \DUP_PRO_Log::trace($e->getMessage());
            return false;
        }

        return $response['size'];
    }

    /**
     * Delete relative path from storage root.
     *
     * @param string $path      The path to delete. (Accepts directories and files)
     * @param bool   $recursive Allows the deletion of nested directories specified in the pathname. Default to false.
     *
     * @return bool true on success or false on failure.
     */
    protected function realDelete($path, $recursive = false)
    {
        $path = $this->formatPath($path);
        if (! $recursive) {
            try {
                $response = $this->client->listFolder($path);
                if (count($response['entries']) > 0) {
                    return false;
                }
            } catch (\Exception $e) {
                // Path is not a directory, so we can delete it.
            }
        }
        try {
            $this->client->delete($path);
        } catch (\Exception $e) {
            \DUP_PRO_Log::trace($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Get file content.
     *
     * @param string $path The path to file.
     *
     * @return string|false The content of file or false on failure.
     */
    public function getFileContent($path)
    {
        $content = '';

        try {
            $stream = $this->client->download($this->formatPath($path));
            while ($chunk = fgets($stream)) {
                $content .= $chunk;
            }
        } catch (\Exception $e) {
            \DUP_PRO_Log::trace($e->getMessage());
            return false;
        }

        return $content;
    }

    /**
     * Move and/or rename a file or directory.
     *
     * @param string $oldPath Relative storage path
     * @param string $newPath Relative storage path
     *
     * @return bool true on success or false on failure.
     */
    protected function realMove($oldPath, $newPath)
    {
        $oldPath = $this->formatPath($oldPath);
        $newPath = $this->formatPath($newPath);

        try {
            $this->client->move($oldPath, $newPath);
        } catch (\Exception $e) {
            \DUP_PRO_Log::trace($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Get path info.
     *
     * @param string $path Relative storage path, if empty, return root path info.
     *
     * @return StoragePathInfo The path info or false if path is invalid.
     */
    protected function getRealPathInfo($path)
    {
        try {
            $response = $this->client->getMetadata($this->formatPath($path));
        } catch (\Exception $e) {
            $response = [];
        }

        return $this->buildPathInfo($response);
    }

    /**
     * Get the list of files and directories inside the specified path.
     *
     * @param string $path    Relative storage path, if empty, scan root path.
     * @param bool   $files   If true, add files to the list. Default to true.
     * @param bool   $folders If true, add folders to the list. Default to true.
     *
     * @return string[] The list of files and directories, empty array if path is invalid.
     */
    public function scanDir($path, $files = true, $folders = true)
    {
        $path = rtrim($this->formatPath($path), '/') . '/';

        $filterFunc = function ($entry) use ($files, $folders) {
            if ($entry['.tag'] === 'file' && $files) {
                return true;
            }

            if ($entry['.tag'] === 'folder' && $folders) {
                return true;
            }

            return false;
        };
        try {
            $response = $this->client->listFolder($path);
        } catch (\Exception $e) {
            \DUP_PRO_Log::trace('[DropboxAddon] ' . $e->getMessage());
            return [];
        }

        // We filter out the entries as needed, then only keep the path.
        // We do this early to keep the memory usage as low as possible.
        $entries = array_map(function ($entry) use ($path) {
            return substr($entry['path_display'], strlen($path));
        }, array_filter($response['entries'], $filterFunc));

        while ($response['has_more']) {
            $response = $this->client->listFolderContinue($response['cursor']);
            $entries  = array_merge($entries, array_map(function ($entry) use ($path) {
                return substr($entry['path_display'], strlen($path));
            }, array_filter($response['entries'], $filterFunc)));
        }

        return $entries;
    }

    /**
     * Check if directory is empty.
     *
     * @param string   $path    The folder path
     * @param string[] $filters Filters to exclude files and folders from the check, if start and end with /, use regex.
     *
     * @return bool True is ok, false otherwise
     */
    public function isDirEmpty($path, $filters = [])
    {
        $path = $this->formatPath($path);
        try {
            $response = $this->client->listFolder($path);
        } catch (\Exception $e) {
            \DUP_PRO_Log::trace($e->getMessage());
            return false;
        }
        if (count($response['entries']) === 0) {
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
        $storageFile = $this->formatPath($storageFile);

        if (!$handle = fopen($sourceFile, 'rb')) {
            \DUP_PRO_Log::trace("[DropboxAddon] Could not open source file: {$sourceFile}");
            return false;
        }
        fseek($handle, $offset);
        $chunkSize = $length > 0 ? $length : MB_IN_BYTES;

        if (!empty($extraData['sessionId'])) {
            $sessionId = $extraData['sessionId'];
            $cursor    = new UploadSessionCursor($sessionId, $offset);
        } else {
            // We need to start a new session.
            try {
                $contents               = fread($handle, $chunkSize);
                $cursor                 = $this->client->uploadSessionStart($contents);
                $extraData['sessionId'] = $cursor->session_id;
            } catch (\Exception $e) {
                \DUP_PRO_Log::trace($e->getMessage());
                return false;
            }
        }

        \DUP_PRO_Log::info("[Dropbox] Setting timeout for upload request for: {$storageFile} to " . ($timeout / SECONDS_IN_MICROSECONDS) . " seconds");
        $this->client->setTimeout($timeout / SECONDS_IN_MICROSECONDS);
        while (($contents = fread($handle, $chunkSize)) && ($length < 0 || $offset > 0)) {
            try {
                $this->client->uploadSessionAppend($contents, $cursor);
            } catch (\Exception $e) {
                unset($extraData['sessionId']);
                $this->client->setTimeout(0);
                \DUP_PRO_Log::trace('[DropboxAddon] ' . $e->getMessage());
                return false;
            }
            if ($length > 0) {
                // A specific length was requested, so we can break out of the loop.
                break;
            }
        }

        $fileSize = filesize($sourceFile);
        $this->client->setTimeout(0);
        \DUP_PRO_Log::info("[Dropbox] uploaded {$storageFile} from {$offset} to {$cursor->offset}, progress: " . ceil($cursor->offset / $fileSize * 100) . "%");
        // If we have finished uploading, we need to finish the session & clear the cache
        if ($cursor->offset >= $fileSize) {
            try {
                \DUP_PRO_Log::info("[DropboxAddon] Finishing upload request for: {$storageFile}");
                $this->client->uploadSessionFinish('', $cursor, $storageFile, 'overwrite'); // this will return the file metadata
            } catch (\Exception $e) {
                \DUP_PRO_Log::trace('[DropboxAddon] ' . $e->getMessage());
                return false;
            }
            unset($extraData['sessionId']);
        }

        return $length > 0 ? $length : $fileSize;
    }

    /**
     * Normalize path, add storage root path if needed.
     *
     * @param string $path Relative storage path.
     *
     * @return string
     */
    protected function formatPath($path)
    {
        return $this->storageFolder . ltrim($path, '/');
    }

    /**
     * Build StoragePathInfo object from Dropbox API response.
     *
     * @param array<string,mixed> $response Dropbox API response.
     *
     * @return StoragePathInfo
     */
    protected function buildPathInfo($response)
    {
        $info         = new StoragePathInfo();
        $info->exists = isset($response['.tag']);

        if (!$info->exists) {
            return $info;
        }

        $info->path     = $this->getRelativeStoragePath($response['path_display']);
        $info->isDir    = $response['.tag'] === 'folder';
        $info->size     = isset($response['size']) ? $response['size'] : 0;
        $info->created  = isset($response['client_modified']) ? strtotime($response['client_modified']) : time();
        $info->modified = isset($response['server_modified']) ? strtotime($response['server_modified']) : time();

        return $info;
    }

    /**
     * Get relative storage path from Dropbox path display.
     *
     * @param string $path_display Dropbox path display.
     * @param string $subPath      Sub path to remove from the path display.
     *
     * @return string
     */
    protected function getRelativeStoragePath($path_display, $subPath = '')
    {
        $rootPath = $this->storageFolder;
        if (!empty($subPath)) {
            $rootPath .= trim($subPath) . '/';
        }
        return substr($path_display, strlen($rootPath));
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
        if (! $this->exists($storageFile)) {
            \DUP_PRO_Log::trace("[DropboxAddon] Storage file {$storageFile} does not exist");
            return false;
        }

        if (! isset($extraData['resuming']) && file_put_contents($destFile, '') === false) {
            \DUP_PRO_Log::trace("[DropboxAddon] Could not open destination file for writing. File: {$destFile}");
            return false;
        }
        $extraData['resuming'] = true;

        $this->client->setTimeout($timeout / SECONDS_IN_MICROSECONDS);
        $resource = $this->client->download($this->formatPath($storageFile));
        if (! $resource) {
            $this->client->setTimeout(0);
            \DUP_PRO_Log::trace("[DropboxAddon] Could not open storage file for downloading. File: {$storageFile}");
            return false;
        }
        fseek($resource, $offset);

        $content = '';
        while (!feof($resource)) {
            $content .= fread($resource, $length > 0 ? $length : MB_IN_BYTES);
            if ($length > 0 && strlen($content) > $length) {
                $content = substr($content, 0, $length);
                break;
            }
        }

        $this->client->setTimeout(0);
        if (file_put_contents($destFile, $content, FILE_APPEND) !== false) {
            return $length < 0 ? strlen($content) : $length;
        }
        return false;
    }
}

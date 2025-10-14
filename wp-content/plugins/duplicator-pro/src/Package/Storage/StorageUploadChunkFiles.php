<?php

/**
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

namespace Duplicator\Package\Storage;

use DUP_PRO_Log;
use DUP_PRO_Package;
use DUP_PRO_Package_Upload_Info;
use Duplicator\Libs\Chunking\ChunkingManager;
use Duplicator\Libs\Chunking\Iterators\GenericSeekableIteratorInterface;
use Duplicator\Libs\Chunking\Iterators\TimeoutFileCopyIterator;
use Duplicator\Models\Storages\AbstractStorageAdapter;
use Exception;

/**
 * Chunk manager for storage uploads
 */
class StorageUploadChunkFiles extends ChunkingManager
{
    /** @var int<0,max> */
    protected $chunkSize = 0;
    /** @var int timeout in microseconds */
    protected $chunkTimeout = 0;
    /** @var AbstractStorageAdapter */
    protected $adapter = null;
    /** @var DUP_PRO_Package_Upload_Info */
    protected $uploadInfo = null;
    /** @var DUP_PRO_Package */
    protected $package = null;

    /**
     * Class contructor
     *
     * @param mixed $extraData    extra data for manager used on extended classes
     * @param int   $maxIteration max number of iterations, 0 for no limit
     * @param int   $timeOut      timeout in microseconds, 0 for no timeout
     * @param int   $throttling   throttling microseconds, 0 for no throttling
     */
    public function __construct($extraData = null, $maxIteration = 0, $timeOut = 0, $throttling = 0)
    {
        $this->chunkSize = $extraData['chunkSize'];

        if (!$extraData['adapter'] instanceof AbstractStorageAdapter) {
            throw new Exception('Adapter must be an instance of AbstractStorageAdapter');
        }
        $this->adapter = $extraData['adapter'];

        if (!$extraData['upload_info'] instanceof DUP_PRO_Package_Upload_Info) {
            throw new Exception('Upload info must be an instance of DUP_PRO_Package_Upload_Info');
        }
        $this->uploadInfo = $extraData['upload_info'];

        if (!$extraData['package'] instanceof DUP_PRO_Package) {
            throw new Exception('Package must be an instance of DUP_PRO_Package');
        }
        $this->package = $extraData['package'];

        $this->chunkTimeout = (int) (!empty($extraData['chunkTimeout']) ? $extraData['chunkTimeout'] : 0);

        parent::__construct($extraData, $maxIteration, $timeOut, $throttling);
    }

        /**
         * Execute chunk action
         *
         * @param string                    $key     the current key
         * @param array<string, string|int> $current the current element
         *
         * @return bool
         */
    protected function action($key, $current)
    {
        $current = $this->it->current();
        if (strlen($current['from']) == 0) {
            return true;
        }

        if (is_file($current['from'])) {
            DUP_PRO_Log::trace('Copying file: ' . $current['from']);
            DUP_PRO_Log::trace('Offset: ' . $current['offset'] . ' of: ' . filesize($current['from']));

            $result = $this->adapter->copyToStorage(
                $current['from'],
                $current['to'],
                $current['offset'],
                $this->chunkSize,
                $this->chunkTimeout,
                $this->uploadInfo->copyToExtraData
            );
            if ($result === false) {
                return false;
            } else {
                /** @var TimeoutFileCopyIterator */
                $it = $this->it;
                $it->updateCurrentFileOffset($result);
                return true;
            }
        } elseif (is_dir($current['from'])) {
            return $this->adapter->createDir($current['to']);
        } else {
            return false;
        }
    }

        /**
         * Return iterator
         *
         * @param array<string, mixed> $extraData extra data for manager used on extended classes
         *
         * @return GenericSeekableIteratorInterface
         */
    protected function getIterator($extraData = null)
    {
        $it = new TimeoutFileCopyIterator($extraData['replacements']);
        $it->setTotalSize();
        return $it;
    }

    /**
     * Return persistance adapter
     *
     * @param mixed $extraData extra data for manager used on extended classes
     *
     * @return UploadPackageFilePersistanceAdapter
     */
    protected function getPersistance($extraData = null)
    {
        return new UploadPackageFilePersistanceAdapter($this->uploadInfo, $this->package);
    }
}

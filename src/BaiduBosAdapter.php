<?php

namespace Sulao\Flysystem\BaiduBos;

use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToListContents;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use Sulao\BaiduBos\Client;
use Sulao\BaiduBos\Exception;
use Throwable;

class BaiduBosAdapter implements FilesystemAdapter
{
    use UtilTrait;

    /**
     * @var Client
     */
    protected Client $client;

    /**
     * BaiduBosAdapter constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config
     *
     * @return void
     * @throws UnableToWriteFile
     */
    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $this->client
                ->putObject($path, $contents, $this->extractOptions($config));
        } catch (Throwable $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * Write a new file using a stream.
     *
     * @param string   $path
     * @param resource $contents
     * @param Config $config
     *
     * @return void
     * @throws UnableToWriteFile
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->write($path, stream_get_contents($contents), $config);
    }

    /**
     * Rename/Move a file.
     *
     * @param string $source
     * @param string $destination
     * @param Config $config
     *
     * @return void
     * @throws UnableToMoveFile
     */
    public function move(
        string $source,
        string $destination,
        Config $config
    ): void {
        try {
            $this->client->copyObject($source, $destination);
            $this->client->deleteObject($source);
        } catch (Throwable $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }


    /**
     * Copy a file.
     *
     * @param string $source
     * @param string $destination
     * @param Config $config
     *
     * @return void
     * @throws UnableToCopyFile
     */
    public function copy(
        string $source,
        string $destination,
        Config $config
    ): void {
        try {
            $this->client->copyObject($source, $destination);
        } catch (Throwable $e) {
            throw UnableToCopyFile::fromLocationTo(
                $source,
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return void
     * @throws UnableToDeleteFile
     */
    public function delete(string $path): void
    {
        try {
            $this->client->deleteObject($path);
        } catch (Throwable $e) {
            throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return bool
     */
    public function fileExists(string $path): bool
    {
        try {
            $this->client->getObjectMeta($path);
        } catch (Throwable $e) {
            return false;
        }

        return true;
    }

    /**
     * Check whether a dir exists.
     *
     * @param string $path
     *
     * @return bool
     */
    public function directoryExists(string $path): bool
    {
        try {
            $lists = $this->listContents($path, false);
            return !empty($lists);
        } catch (Throwable $e) {
            return false;
        }
    }


    /**
     * Create a directory.
     *
     * @param string $path
     * @param Config $config
     *
     * @return void
     * @throws UnableToCreateDirectory
     */
    public function createDirectory(string $path, Config $config): void
    {
        try {
            $this->client->putObject(
                rtrim($path, '/') . '/',
                '',
                $this->extractOptions($config)
            );
        } catch (Throwable $e) {
            throw UnableToCreateDirectory::atLocation(
                $path,
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Get the mime-type of a file.
     *
     * @param string $path
     *
     * @return FileAttributes
     * @throws UnableToRetrieveMetadata
     */
    public function mimeType(string $path): FileAttributes
    {
        try {
            $meta = $this->getMetadata($path);
            $mimeType = $meta['mimeType'];
        } catch (Throwable $e) {
            throw UnableToRetrieveMetadata::mimeType(
                $path,
                $e->getMessage(),
                $e
            );
        }

        return new FileAttributes($path, null, null, null, $mimeType);
    }


    /**
     * Get the lastModified of a file.
     *
     * @param string $path
     *
     * @return FileAttributes
     * @throws UnableToRetrieveMetadata
     */
    public function lastModified(string $path): FileAttributes
    {
        try {
            $meta = $this->getMetadata($path);
            $lastModified = $meta['lastModified'];
        } catch (Throwable $e) {
            throw UnableToRetrieveMetadata::lastModified(
                $path,
                $e->getMessage(),
                $e
            );
        }

        return new FileAttributes($path, null, null, $lastModified);
    }

    /**
     * Get the size of a file.
     *
     * @param string $path
     *
     * @return FileAttributes
     * @throws UnableToRetrieveMetadata
     */
    public function fileSize(string $path): FileAttributes
    {
        try {
            $meta = $this->getMetadata($path);
            $fileSize = $meta['fileSize'];
        } catch (Throwable $e) {
            throw UnableToRetrieveMetadata::fileSize(
                $path,
                $e->getMessage(),
                $e
            );
        }

        return new FileAttributes($path, $fileSize);
    }


    /**
     * Delete a directory.
     *
     * @param string $path
     *
     * @return void
     * @throws UnableToDeleteDirectory
     */
    public function deleteDirectory(string $path): void
    {
        try {
            $this->client->deleteObject(rtrim($path, '/') . '/');
        } catch (Throwable $e) {
            throw UnableToDeleteDirectory::atLocation(
                $path,
                $e->getMessage(),
                $e
            );
        }
    }


    /**
     * Read a file.
     *
     * @param string $path
     *
     * @return string
     * @throws UnableToReadFile
     */
    public function read(string $path): string
    {
        try {
            $contents = $this->client->getObject($path);
        } catch (Throwable $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }

        return $contents;
    }

    /**
     * Read a file as a stream.
     *
     * @param string $path
     *
     * @return resource
     * @throws UnableToReadFile
     */
    public function readStream(string $path)
    {
        $contents = $this->read($path);
        $stream = fopen('php://temp', 'w+b');
        fputs($stream, $contents);
        rewind($stream);

        return $stream;
    }

    /**
     * List contents of a directory.
     *
     * @param string $path
     * @param bool   $deep
     *
     * @return iterable
     * @throws UnableToListContents
     */
    public function listContents(string $path, bool $deep): iterable
    {
        $options = $this->buildListDirOptions($path, $deep);
        try {
            $result = $this->client->listObjects($options);
        } catch (Throwable $e) {
            throw UnableToListContents::atLocation($path, $e->getMessage(), $e);
        }

        $prefixes = isset($result['commonPrefixes'])
            ? array_map(function ($item) {
                return ['key' => $item['prefix']];
            }, $result['commonPrefixes'])
            : [];

        return array_map(function ($content) {
            return $this->normalizeContent($content);
        }, array_merge($result['contents'], $prefixes));
    }

    /**
     * Get the visibility of a file.
     *
     * @param string $path
     *
     * @return FileAttributes
     */

    public function visibility(string $path): FileAttributes
    {
        try {
            $acl = $this->getObjectAcl($path);
        } catch (Throwable $e) {
            throw UnableToRetrieveMetadata::visibility(
                $path,
                $e->getMessage(),
                $e
            );
        }

        $permissions = $this->extractPermissions($acl);

        if (in_array('READ', $permissions)) {
            $visibility = 'public';
        } else {
            $visibility = 'private';
        }

        return new FileAttributes($path, null, $visibility);
    }


    /**
     * Set the visibility for a file.
     *
     * @param string $path
     * @param string $visibility
     *
     * @return void
     */
    public function setVisibility(string $path, string $visibility): void
    {
        if ($visibility === 'public') {
            $visibility = 'public-read';
        }

        try {
            $this->client->putObjectAcl($path, $visibility);
        } catch (Throwable $e) {
            throw UnableToSetVisibility::atLocation(
                $path,
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array
     * @throws Exception
     */
    protected function getMetadata(string $path): array
    {
        $meta = $this->client->getObjectMeta($path);
        return $this->normalizeMeta($meta, $path);
    }

    /**
     * Get object acl, if not set, return bucket acl
     *
     * @param string $path
     *
     * @return array
     * @throws Throwable
     * @throws Exception
     */
    protected function getObjectAcl(string $path): array
    {
        try {
            $result = $this->client->getObjectAcl($path);
            return $result['accessControlList'];
        } catch (Throwable $exception) {
            if ($exception->getCode() == 404) {
                return $this->getBucketAcl();
            }

            throw $exception;
        }
    }

    /**
     * Get bucket acl
     *
     * @return array
     * @throws Exception
     */
    protected function getBucketAcl(): array
    {
        $result = $this->client->getBucketAcl();
        return $result['accessControlList'];
    }
}

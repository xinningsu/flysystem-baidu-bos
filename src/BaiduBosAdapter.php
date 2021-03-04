<?php

namespace Sulao\Flysystem\BaiduBos;

use Exception;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Config;
use Sulao\BaiduBos\Client;

class BaiduBosAdapter extends AbstractAdapter
{
    use UtilTrait;

    /**
     * @var Client
     */
    protected $client;

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
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $config)
    {
        try {
            $this->client
                ->putObject($path, $contents, $this->extractOptions($config));
        } catch (Exception $exception) {
            return  false;
        }

        return $this->client->getObjectMeta($path);
    }

    /**
     * Write a new file using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->write($path, stream_get_contents($resource), $config);
    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * Update a file using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newPath
     *
     * @return bool
     */
    public function rename($path, $newPath)
    {
        try {
            $this->client->copyObject($path, $newPath);
            $this->client->deleteObject($path);
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newPath
     *
     * @return bool
     */
    public function copy($path, $newPath)
    {
        try {
            $this->client->copyObject($path, $newPath);
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function delete($path)
    {
        try {
            $this->client->deleteObject($path);
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * Delete a directory.
     *
     * @param string $dirname
     *
     * @return bool
     */
    public function deleteDir($dirname)
    {
        try {
            $this->client->deleteObject(rtrim($dirname, '/') . '/');
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * Create a directory.
     *
     * @param string $dirname directory name
     * @param Config $config
     *
     * @return bool
     */
    public function createDir($dirname, Config $config)
    {
        try {
            $this->client->putObject(
                rtrim($dirname, '/') . '/',
                '',
                $this->extractOptions($config)
            );
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return bool
     */
    public function has($path)
    {
        try {
            $this->client->getObjectMeta($path);
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * Read a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function read($path)
    {
        try {
            $contents = $this->client->getObject($path);
        } catch (Exception $exception) {
            return false;
        }

        return compact('path', 'contents');
    }

    /**
     * Read a file as a stream.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function readStream($path)
    {
        $result = $this->read($path);
        if ($result === false) {
            return false;
        }

        $stream = fopen('php://temp', 'w+b');
        fputs($stream, $result['contents']);
        rewind($stream);

        return compact('path', 'stream');
    }

    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array
     */
    public function listContents($directory = '', $recursive = false)
    {
        $options = $this->buildListDirOptions($directory, $recursive);
        $result = $this->client->listObjects($options);

        $contents = [];
        foreach ($result['contents'] as $row) {
            if ($row['key'] === $directory) {
                continue;
            }

            $contents[] = $this->normalizeContent($row);
        }

        return $contents;
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMetadata($path)
    {
        try {
            $meta = $this->client->getObjectMeta($path);
        } catch (Exception $exception) {
            return false;
        }

        return $this->normalizeMeta($meta, $path);
    }

    /**
     * Get the size of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the mime-type of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMimeType($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the timestamp of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the visibility of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getVisibility($path)
    {
        $acl = $this->getObjectAcl($path);
        if ($acl === false) {
            return false;
        }

        $permissions = $this->extractPermissions($acl);

        if (in_array('READ', $permissions)) {
            $visibility = 'public-read';
        } else {
            $visibility = 'private';
        }

        return compact('path', 'visibility');
    }

    /**
     * Set the visibility for a file.
     *
     * @param string $path
     * @param string $visibility
     *
     * @return array|false file meta data
     */
    public function setVisibility($path, $visibility)
    {
        try {
            $this->client->putObjectAcl($path, $visibility);
        } catch (Exception $exception) {
            return false;
        }

        return compact('path', 'visibility');
    }

    /**
     * Get object acl, if not set, return bucket acl
     *
     * @param $path
     *
     * @return array|false
     */
    protected function getObjectAcl($path)
    {
        try {
            $result = $this->client->getObjectAcl($path);
            return $result['accessControlList'];
        } catch (Exception $exception) {
            if ($exception->getCode() == 404) {
                return $this->getBucketAcl();
            }
        }

        return false;
    }

    /**
     * Get bucket acl
     *
     * @return array|false
     */
    protected function getBucketAcl()
    {
        try {
            $result = $this->client->getBucketAcl();
        } catch (Exception $exception) {
            return false;
        }

        return $result['accessControlList'];
    }
}

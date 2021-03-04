<?php

namespace Sulao\Flysystem\BaiduBos;

use League\Flysystem\Config;
use League\Flysystem\Util;

trait UtilTrait
{
    /**
     * @param string $directory
     * @param false  $recursive
     *
     * @return array
     */
    public function buildListDirOptions($directory = '', $recursive = false)
    {
        $options = [];

        if (!$recursive) {
            $options['query']['delimiter'] = '/';
        }

        $directory = trim($directory, '/');
        if ($directory !== '') {
            $directory .= '/';
            $options['query']['prefix'] = $directory;
        }

        return $options;
    }
    /**
     * Extract options from config
     *
     * @param Config $config
     *
     * @return array
     */
    protected function extractOptions(Config $config)
    {
        $options = [];

        foreach (['headers', 'query', 'body', 'request', 'authorize'] as $key) {
            if ($config->has($key)) {
                $options[$key] = $config->get($key);
            }
        }

        return $options;
    }

    /**
     * Normalize the object meta array.
     *
     * @param array $meta
     * @param string $path
     *
     * @return array
     */
    protected function normalizeMeta(array $meta, $path)
    {
        $result =  Util::pathinfo($path);

        if (isset($meta['Last-Modified'])) {
            $result['timestamp'] = strtotime($meta['Last-Modified']);
        }

        return array_merge($result, Util::map($meta, [
            'Content-Length' => 'size',
            'Content-Type'   => 'mimetype',
        ]), ['type' => 'file']);
    }

    /**
     * Normalize the content from list contents of dir.
     *
     * @param array $content
     *
     * @return array
     */
    protected function normalizeContent(array $content)
    {
        $return = [];

        if (substr($content['key'], -1) === '/') {
            $return['type'] = 'dir';
            $return['path'] = rtrim($content['key'], '/');
        } else {
            $return['type'] = 'file';
            $return['path'] = $content['key'];
            $return['size'] = $content['size'];
        }

        $return['timestamp'] = strtotime($content['lastModified']);

        return $return + Util::pathinfo($content['key']);
    }

    /**
     * Extract permissions from acl
     *
     * @param array $acl
     *
     * @return array
     */
    protected function extractPermissions(array $acl)
    {
        $permissions = [];
        foreach ($acl as $row) {
            $ids = array_column($row['grantee'], 'id');
            if (in_array('*', $ids)) {
                $permissions = $row['permission'];
                break;
            }
        }

        return $permissions;
    }
}

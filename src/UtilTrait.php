<?php

namespace Sulao\Flysystem\BaiduBos;

use League\Flysystem\Config;

trait UtilTrait
{
    /**
     * @param string $directory
     * @param false  $recursive
     *
     * @return array
     */
    public function buildListDirOptions(
        string $directory = '',
        bool $recursive = false
    ): array {
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
    protected function extractOptions(Config $config): array
    {
        $options = [];

        foreach (['headers', 'query', 'body', 'request', 'authorize'] as $key) {
            if ($config->get($key)) {
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
    protected function normalizeMeta(array $meta, string $path): array
    {
        $result = pathinfo($path);

        if (isset($meta['Last-Modified'])) {
            $result['lastModified'] = strtotime($meta['Last-Modified']);
        }

        if (isset($meta['Content-Length'])) {
            $result['fileSize'] = $meta['Content-Length'];
        }

        if (isset($meta['Content-Type'])) {
            $result['mimeType'] = $meta['Content-Type'];
        }

        return array_merge($result, ['type' => 'file']);
    }

    /**
     * Normalize the content from list contents of dir.
     *
     * @param array $content
     *
     * @return array
     */
    protected function normalizeContent(array $content): array
    {
        $return = [];

        if (str_ends_with($content['key'], '/')) {
            $return['type'] = 'dir';
            $return['path'] = $content['key'];
        } else {
            $return['type'] = 'file';
            $return['path'] = $content['key'];
            $return['size'] = $content['size'];
        }

        if (isset($content['lastModified'])) {
            $return['timestamp'] = strtotime($content['lastModified']);
        }

        return $return + pathinfo($content['key']);
    }

    /**
     * Extract permissions from acl
     *
     * @param array $acl
     *
     * @return array
     */
    protected function extractPermissions(array $acl): array
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

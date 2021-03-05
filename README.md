# Flysystem Baidu BOS
Flysystem adapter for BOS(Baidu Object Storage), 百度对象存储 Flysystem 适配器。

[![MIT licensed](https://img.shields.io/badge/license-MIT-blue.svg)](./LICENSE)
[![Build Status](https://api.travis-ci.org/xinningsu/flysystem-baidu-bos.svg?branch=master)](https://travis-ci.org/xinningsu/flysystem-baidu-bos)
[![Coverage Status](https://coveralls.io/repos/github/xinningsu/flysystem-baidu-bos/badge.svg?branch=master)](https://coveralls.io/github/xinningsu/flysystem-baidu-bos)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/xinningsu/flysystem-baidu-bos/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/xinningsu/flysystem-baidu-bos)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/xinningsu/flysystem-baidu-bos/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/g/xinningsu/flysystem-baidu-bos)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=xinningsu_flysystem-baidu-bos&metric=alert_status)](https://sonarcloud.io/dashboard?id=xinningsu_flysystem-baidu-bos)
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=xinningsu_flysystem-baidu-bos&metric=reliability_rating)](https://sonarcloud.io/dashboard?id=xinningsu_flysystem-baidu-bos)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=xinningsu_flysystem-baidu-bos&metric=security_rating)](https://sonarcloud.io/dashboard?id=xinningsu_flysystem-baidu-bos)
[![Maintainability](https://api.codeclimate.com/v1/badges/b0634900a667b22fb5cb/maintainability)](https://codeclimate.com/github/xinningsu/flysystem-baidu-bos/maintainability)

# Installation

```
composer require xinningsu/flysystem-baidu-bos

```

# Examples

```php
require 'vendor/autoload.php';

// Instantiate
$client = new \Sulao\BaiduBos\Client([
    'access_key' => 'access key',
    'secret_key' => 'secret key',
    'bucket' => 'bucket',
    'region' => 'region',
    'options' => ['connect_timeout' => 10] // Optional, guzzle request options
]);
$adapter = new \Sulao\Flysystem\BaiduBos\BaiduBosAdapter($client);
$filesystem = new \League\Flysystem\Filesystem($adapter, ['disable_asserts' => true]);

// Write a new file.
$filesystem->write('file.txt', 'contents');

// Write a new file using a stream.
$filesystem->writeStream('file.txt', fopen('/resource.txt', 'r'));

// Create a file or update if exists.
$filesystem->put('file.txt', 'contents');

// Create a file or update if exists using a stream.
$filesystem->putStream('file.txt', fopen('/resource.txt', 'r'));

// Update an existing file.
$filesystem->update('file.txt', 'contents');

// Update an existing file using a stream.
$filesystem->updateStream('file.txt', fopen('/resource.txt', 'r'));

// Read a file.
$content = $filesystem->read('file.txt');

// Retrieves a read-stream for a path.
$stream = $filesystem->readStream('file.txt');

// Check whether a file exists.
$has = $filesystem->has('file.txt');

// Copy a file.
$filesystem->copy('file.txt', 'file2.txt');

// Rename a file.
$filesystem->rename('file.txt', 'file2.txt');

// Delete a file.
$filesystem->delete('file.txt');

// Get a file's metadata.
$meta = $filesystem->getMetadata('file.txt');

// Get a file's size.
$size = $filesystem->getSize('file.txt');

// Get a file's mime-type.
$mimeType = $filesystem->getMimetype('file.txt');

// Get a file's timestamp.
$ts = $filesystem->getTimestamp('file.txt');

// Set the visibility for a file.
$filesystem->setVisibility('file.txt', 'public');

// Get a file's visibility.
$visibility = $filesystem->getVisibility('file.txt');

// Delete a directory.
$filesystem->deleteDir('test/');

// Create a directory.
$filesystem->createDir('test/');

// List contents of a directory.
$lists = $filesystem->listContents('test/', true);
```

# Integration

- [xinningsu/laravel-filesystem-baidu-bos](https://packagist.org/packages/xinningsu/laravel-filesystem-baidu-bos)

# Reference

- [https://github.com/thephpleague/flysystem](https://github.com/thephpleague/flysystem)
- [https://github.com/xinningsu/baidu-bos](https://github.com/xinningsu/baidu-bos)
- [https://cloud.baidu.com/doc/BOS/index.html](https://cloud.baidu.com/doc/BOS/index.html)

# License

[MIT](./LICENSE)

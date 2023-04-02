<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use PHPUnit\Framework\TestCase;
use Sulao\Flysystem\BaiduBos\BaiduBosAdapter;
use Sulao\BaiduBos\Client;

class AdapterTest extends TestCase
{
    public function testDir()
    {
        $this->filesystem()->createDirectory('adapter_dir/');
        $this->assertTrue($this->filesystem()->directoryExists('adapter_dir/'));
        $this->filesystem()->deleteDirectory('adapter_dir/');
    }

    public function testException()
    {
        $filesystem = $this->filesystem2();

        $exception = null;
        try {
            $filesystem->write('test.txt', 'test');
        } catch (Throwable $exception) {
        }
        $this->assertInstanceOf(FilesystemException::class, $exception);

        $exception = null;
        try {
            $stream = fopen('php://temp', 'w+b');
            fputs($stream, 'adapter test');
            rewind($stream);
            $filesystem->writeStream('test.txt', $stream);
        } catch (Throwable $exception) {
        }
        $this->assertInstanceOf(FilesystemException::class, $exception);

        $exception = null;
        try {
            $filesystem->move('test.txt', 'test2.txt');
        } catch (Throwable $exception) {
        }
        $this->assertInstanceOf(FilesystemException::class, $exception);

        $exception = null;
        try {
            $filesystem->copy('test.txt', 'test2.txt');
        } catch (Throwable $exception) {
        }
        $this->assertInstanceOf(FilesystemException::class, $exception);

        $exception = null;
        try {
            $filesystem->delete('test.txt');
        } catch (Throwable $exception) {
        }
        $this->assertInstanceOf(FilesystemException::class, $exception);

        $exception = null;
        try {
            $filesystem->deleteDirectory('testttt/');
        } catch (Throwable $exception) {
        }
        $this->assertInstanceOf(FilesystemException::class, $exception);

        $exception = null;
        try {
            $filesystem->createDirectory('testttt/');
        } catch (Throwable $exception) {
        }
        $this->assertInstanceOf(FilesystemException::class, $exception);

        $this->assertFalse($filesystem->directoryExists('testttt/'));

        $exception = null;
        try {
            $filesystem->listContents('testttt/');
        } catch (Throwable $exception) {
        }
        $this->assertInstanceOf(FilesystemException::class, $exception);

        $exception = null;
        try {
            $filesystem->read('test.txt');
        } catch (Throwable $exception) {
        }
        $this->assertInstanceOf(FilesystemException::class, $exception);

        $exception = null;
        try {
            $filesystem->readStream('test.txt');
        } catch (Throwable $exception) {
        }
        $this->assertInstanceOf(FilesystemException::class, $exception);

        $exception = null;
        try {
            $filesystem->mimeType('test.txt');
        } catch (Throwable $exception) {
        }
        $this->assertInstanceOf(FilesystemException::class, $exception);

        $exception = null;
        try {
            $filesystem->lastModified('test.txt');
        } catch (Throwable $exception) {
        }
        $this->assertInstanceOf(FilesystemException::class, $exception);

        $exception = null;
        try {
            $filesystem->fileSize('test.txt');
        } catch (Throwable $exception) {
        }
        $this->assertInstanceOf(FilesystemException::class, $exception);

        $exception = null;
        try {
            $filesystem->visibility('test.txt');
        } catch (Throwable $exception) {
        }
        $this->assertInstanceOf(FilesystemException::class, $exception);

        $exception = null;
        try {
            $filesystem->setVisibility('test.txt', 'private');
        } catch (Throwable $exception) {
        }
        $this->assertInstanceOf(FilesystemException::class, $exception);
    }

    public function testAdapter()
    {
        $this->addFile();
        $this->getFile();
        $this->copyFile();
        $this->renameFile();
        $this->getMeta();
        $this->visibility();
        $this->listContents();
        $this->deleteFile();
    }

    protected function addFile()
    {
        $this->filesystem()->write(
            'adapter_test.txt',
            'adapter test',
            ['request' => ['connect_timeout' => 10]]
        );

        $stream = fopen('php://temp', 'w+b');
        fputs($stream, 'adapter test2');
        rewind($stream);
        $this->filesystem()->writeStream('adapter_test2.txt', $stream);

        $this->filesystem()->write('adapter_test3.txt', 'adapter test3');

        $stream = fopen('php://temp', 'w+b');
        fputs($stream, 'adapter test4');
        rewind($stream);
        $this->filesystem()->writeStream(
            'adapter_test/adapter_test4.txt',
            $stream
        );
    }

    protected function getFile()
    {
        $this->assertEquals(
            'adapter test',
            $this->filesystem()->read('adapter_test.txt')
        );

        $stream = $this->filesystem()->readStream('adapter_test2.txt');
        $this->assertEquals(
            'adapter test2',
            stream_get_contents($stream)
        );
    }

    protected function copyFile()
    {
        $this->filesystem()->copy(
            'adapter_test.txt',
            'adapter_test5.txt'
        );

        $this->assertTrue(
            $this->filesystem()->fileExists('adapter_test5.txt')
        );
    }

    protected function renameFile()
    {
        $this->filesystem()->move(
            'adapter_test5.txt',
            'adapter_test6.txt'
        );

        $this->assertTrue(
            $this->filesystem()->fileExists('adapter_test6.txt')
        );
        $this->assertFalse(
            $this->filesystem()->fileExists('adapter_test5.txt')
        );
    }

    protected function getMeta()
    {
        $this->assertTrue(
            is_int($this->filesystem()->lastModified('adapter_test.txt'))
        );
        $this->assertTrue(
            is_int($this->filesystem()->fileSize('adapter_test.txt'))
        );
        $this->assertTrue(
            is_string($this->filesystem()->mimeType('adapter_test.txt'))
        );
    }

    protected function visibility()
    {
        $this->filesystem()->setVisibility(
            'adapter_test.txt',
            'private'
        );
        $this->assertEquals(
            'private',
            $this->filesystem()->visibility('adapter_test.txt')
        );

        $this->filesystem()->setVisibility(
            'adapter_test.txt',
            'public'
        );
        $this->assertEquals(
            'public',
            $this->filesystem()->visibility('adapter_test2.txt')
        );
    }

    protected function listContents()
    {
        $result = $this->filesystem()->listContents('', false)->toArray();
        $this->assertTrue(is_array($result));
        $contents = array_column($result, 'path');
        $this->assertTrue(in_array('adapter_test.txt', $contents));
        $this->assertFalse(in_array(
            'adapter_test/adapter_test4.txt',
            $contents
        ));

        $result = $this->filesystem()->listContents('', true)->toArray();
        $contents = array_column($result, 'path');
        $this->assertTrue(in_array(
            'adapter_test/adapter_test4.txt',
            $contents
        ));

        $result = $this->filesystem()->listContents('adapter_test/', false)->toArray();
        $contents = array_column($result, 'path');
        $this->assertFalse(in_array('adapter_test.txt', $contents));
        $this->assertTrue(in_array(
            'adapter_test/adapter_test4.txt',
            $contents
        ));
    }

    protected function deleteFile()
    {
        $this->filesystem()->delete('adapter_test.txt');
        $this->filesystem()->delete('adapter_test2.txt');
        $this->filesystem()->delete('adapter_test3.txt');
        $this->filesystem()->delete('adapter_test6.txt');
        $this->filesystem()->delete(
            'adapter_test/adapter_test4.txt'
        );

        $this->assertFalse($this->filesystem()->fileExists('adapter_test.txt'));
    }

    protected function filesystem(): Filesystem
    {
        static $filesystem;

        if (!$filesystem) {
            $client = new Client([
                'access_key' => getenv('BOS_KEY'),
                'secret_key' => getenv('BOS_SECRET'),
                'bucket' => 'xinningsu',
                'region' => 'gz'
            ]);
            $adapter = new BaiduBosAdapter($client);
            $filesystem = new Filesystem($adapter, ['disable_asserts' => true]);
        }

        return $filesystem;
    }

    protected function filesystem2(): Filesystem
    {
        static $filesystem;

        if (!$filesystem) {
            $client = new Client([
                'access_key' => 'key_test',
                'secret_key' => 'secret_test',
                'bucket' => 'xinningsu_test',
                'region' => 'gz_test'
            ]);
            $adapter = new BaiduBosAdapter($client);
            $filesystem = new Filesystem($adapter, ['disable_asserts' => true]);
        }

        return $filesystem;
    }

    protected function filesystem3(): Filesystem
    {
        static $filesystem;

        if (!$filesystem) {
            $client = new ClientMock([
                'access_key' => 'key_test',
                'secret_key' => 'secret_test',
                'bucket' => 'xinningsu_test',
                'region' => 'gz_test'
            ]);
            $adapter = new BaiduBosAdapter($client);
            $filesystem = new Filesystem($adapter, ['disable_asserts' => true]);
        }

        return $filesystem;
    }
}

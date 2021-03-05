<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Sulao\Flysystem\BaiduBos\BaiduBosAdapter;
use Sulao\BaiduBos\Client;

class AdapterTest extends TestCase
{
    public function testGetClient()
    {
        $this->assertInstanceOf(
            Client::class,
            $this->filesystem()->getAdapter()->getClient()
        );
    }

    public function testDir()
    {
        $this->assertTrue($this->filesystem()->createDir('adapter_dir/'));
        $this->assertEmpty($this->filesystem()->listContents('adapter_dir/'));
        $this->assertTrue($this->filesystem()->deleteDir('adapter_dir/'));
    }

    public function testException()
    {
        $filesystem = $this->filesystem2();

        $this->assertFalse($filesystem->write('test.txt', 'test'));

        $stream = fopen('php://temp', 'w+b');
        fputs($stream, 'adapter test');
        rewind($stream);
        $this->assertFalse($filesystem->writeStream('test.txt', $stream));

        $this->assertFalse($filesystem->rename('test.txt', 'test2.txt'));
        $this->assertFalse($filesystem->copy('test.txt', 'test2.txt'));
        $this->assertFalse($filesystem->delete('test.txt'));
        $this->assertFalse($filesystem->deleteDir('testttt/'));
        $this->assertFalse($filesystem->createDir('testttt/'));
        $this->assertFalse($filesystem->read('test.txt'));
        $this->assertFalse($filesystem->readStream('test.txt'));
        $this->assertFalse($filesystem->getMetadata('test.txt'));
        $this->assertFalse($filesystem->getVisibility('test.txt'));
        $this->assertFalse($filesystem->setVisibility('test.txt', 'private'));

        $this->assertFalse($this->filesystem3()->getVisibility('test.txt'));
    }

    public function testAdapter()
    {
        $this->addFile();
        $this->updateFile();
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
        $this->assertTrue(
            $this->filesystem()->write(
                'adapter_test.txt',
                'adapter test',
                ['request' => ['connect_timeout' => 10]]
            )
        );

        $stream = fopen('php://temp', 'w+b');
        fputs($stream, 'adapter test2');
        rewind($stream);
        $this->assertTrue(
            $this->filesystem()->writeStream('adapter_test2.txt', $stream)
        );

        $this->assertTrue(
            $this->filesystem()->put('adapter_test3.txt', 'adapter test3')
        );

        $stream = fopen('php://temp', 'w+b');
        fputs($stream, 'adapter test4');
        rewind($stream);
        $this->assertTrue($this->filesystem()->putStream(
            'adapter_test/adapter_test4.txt',
            $stream
        ));
    }

    protected function updateFile()
    {
        $this->assertTrue($this->filesystem()->update(
            'adapter_test2.txt',
            'adapter test2.'
        ));
        $this->assertEquals(
            'adapter test2.',
            $this->filesystem()->read('adapter_test2.txt')
        );

        $stream = fopen('php://temp', 'w+b');
        fputs($stream, 'adapter test2');
        rewind($stream);
        $this->assertTrue($this->filesystem()->updateStream(
            'adapter_test2.txt',
            $stream
        ));

        $this->assertEquals(
            'adapter test2',
            $this->filesystem()->read('adapter_test2.txt')
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
        $this->assertTrue($this->filesystem()->copy(
            'adapter_test.txt',
            'adapter_test5.txt'
        ));

        $this->assertTrue(
            $this->filesystem()->has('adapter_test5.txt')
        );
    }

    protected function renameFile()
    {
        $this->assertTrue($this->filesystem()->rename(
            'adapter_test5.txt',
            'adapter_test6.txt'
        ));

        $this->assertTrue(
            $this->filesystem()->has('adapter_test6.txt')
        );
        $this->assertFalse(
            $this->filesystem()->has('adapter_test5.txt')
        );
    }

    protected function getMeta()
    {
        $meta = $this->filesystem()->getMetadata('adapter_test.txt');
        $this->assertNotFalse($meta);

        $this->assertArrayHasKey('path', $meta);
        $this->assertArrayHasKey('type', $meta);
        $this->assertArrayHasKey('size', $meta);
        $this->assertArrayHasKey('timestamp', $meta);

        $this->assertTrue(
            is_int($this->filesystem()->getTimestamp('adapter_test.txt'))
        );
        $this->assertTrue(
            is_int($this->filesystem()->getSize('adapter_test.txt'))
        );
        $this->assertTrue(
            is_string($this->filesystem()->getMimetype('adapter_test.txt'))
        );
    }

    protected function visibility()
    {
        $this->assertTrue($this->filesystem()->setVisibility(
            'adapter_test.txt',
            \League\Flysystem\AdapterInterface::VISIBILITY_PRIVATE
        ));

        $this->assertEquals(
            \League\Flysystem\AdapterInterface::VISIBILITY_PRIVATE,
            $this->filesystem()->getVisibility('adapter_test.txt')
        );

        $this->assertTrue($this->filesystem()->setVisibility(
            'adapter_test.txt',
            \League\Flysystem\AdapterInterface::VISIBILITY_PUBLIC
        ));

        $this->assertEquals(
            \League\Flysystem\AdapterInterface::VISIBILITY_PUBLIC,
            $this->filesystem()->getVisibility('adapter_test2.txt')
        );
    }

    protected function listContents()
    {
        $result = $this->filesystem()->listContents();
        $this->assertTrue(is_array($result));
        $contents = array_column($result, 'path');
        $this->assertTrue(in_array('adapter_test.txt', $contents));
        $this->assertFalse(in_array(
            'adapter_test/adapter_test4.txt',
            $contents
        ));

        $result = $this->filesystem()->listContents('', true);
        $contents = array_column($result, 'path');
        $this->assertTrue(in_array(
            'adapter_test/adapter_test4.txt',
            $contents
        ));

        $result = $this->filesystem()->listContents('adapter_test/', false);
        $contents = array_column($result, 'path');
        $this->assertFalse(in_array('adapter_test.txt', $contents));
        $this->assertTrue(in_array(
            'adapter_test/adapter_test4.txt',
            $contents
        ));
    }

    protected function deleteFile()
    {
        $this->assertTrue($this->filesystem()->delete('adapter_test.txt'));
        $this->assertTrue($this->filesystem()->delete('adapter_test2.txt'));
        $this->assertTrue($this->filesystem()->delete('adapter_test3.txt'));
        $this->assertTrue($this->filesystem()->delete('adapter_test6.txt'));
        $this->assertTrue($this->filesystem()->delete(
            'adapter_test/adapter_test4.txt'
        ));

        $this->assertFalse($this->filesystem()->has('adapter_test.txt'));
    }

    protected function filesystem()
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

    protected function filesystem2()
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

    protected function filesystem3()
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

<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Sulao\Flysystem\BaiduBos\BaiduBosAdapter;
use Sulao\BaiduBos\Client;

class AdapterTest extends TestCase
{
    protected $client;
    protected $adapter;
    protected $filesystem;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = new Client(
            getenv('BOS_KEY'),
            getenv('BOS_SECRET'),
            'xinningsu',
            'gz'
        );
        $this->adapter = new BaiduBosAdapter($this->client);
        $this->filesystem = new Filesystem(
            $this->adapter,
            ['disable_asserts' => true]
        );
    }

    public function testGetClient()
    {
        $this->assertInstanceOf(
            Client::class,
            $this->filesystem->getAdapter()->getClient()
        );
    }

    public function testDir()
    {
        $this->assertTrue($this->filesystem->createDir('adapter_dir/'));
        $this->assertEmpty($this->filesystem->listContents('adapter_dir/'));
        $this->assertTrue($this->filesystem->deleteDir('adapter_dir/'));
    }

    public function testException()
    {
        $fakeClient = new Client(
            'key_test',
            'secret_test',
            'xinningsu_test',
            'gz_test'
        );
        $adapter = new BaiduBosAdapter($fakeClient);
        $filesystem = new Filesystem(
            $adapter,
            ['disable_asserts' => true]
        );

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

        $fakeClient2 = new class (
            'key_test',
            'secret_test',
            'xinningsu_test',
            'gz_test'
        ) extends Client
        {
            public function getObjectAcl(
                string $path,
                array $options = []
            ): array {
                throw new Exception('', 404);
            }
        };
        $adapter = new BaiduBosAdapter($fakeClient2);
        $filesystem = new Filesystem(
            $adapter,
            ['disable_asserts' => true]
        );
        $this->assertFalse($filesystem->getVisibility('test.txt'));
    }

    public function testAdapter()
    {
        $this->testAddFile();
        $this->testUpdateFile();
        $this->testGetFile();
        $this->testCopyFile();
        $this->testRenameFile();
        $this->testGetMeta();
        $this->testVisibility();
        $this->testListContents();
        $this->testDeleteFile();
    }

    protected function testAddFile()
    {
        $this->assertTrue(
            $this->filesystem->write('adapter_test.txt', 'adapter test')
        );

        $stream = fopen('php://temp', 'w+b');
        fputs($stream, 'adapter test2');
        rewind($stream);
        $this->assertTrue(
            $this->filesystem->writeStream('adapter_test2.txt', $stream)
        );

        $this->assertTrue(
            $this->filesystem->put('adapter_test3.txt', 'adapter test3')
        );

        $stream = fopen('php://temp', 'w+b');
        fputs($stream, 'adapter test4');
        rewind($stream);
        $this->assertTrue($this->filesystem->putStream(
            'adapter_test/adapter_test4.txt',
            $stream
        ));
    }

    protected function testUpdateFile()
    {
        $this->assertTrue($this->filesystem->update(
            'adapter_test2.txt',
            'adapter test2.'
        ));
        $this->assertEquals(
            'adapter test2.',
            $this->filesystem->read('adapter_test2.txt')
        );

        $stream = fopen('php://temp', 'w+b');
        fputs($stream, 'adapter test2');
        rewind($stream);
        $this->assertTrue($this->filesystem->updateStream(
            'adapter_test2.txt',
            $stream
        ));

        $this->assertEquals(
            'adapter test2',
            $this->filesystem->read('adapter_test2.txt')
        );
    }

    protected function testGetFile()
    {
        $this->assertEquals(
            'adapter test',
            $this->filesystem->read('adapter_test.txt')
        );

        $stream = $this->filesystem->readStream('adapter_test2.txt');
        $this->assertEquals(
            'adapter test2',
            stream_get_contents($stream)
        );
    }

    protected function testCopyFile()
    {
        $this->assertTrue($this->filesystem->copy(
            'adapter_test.txt',
            'adapter_test5.txt'
        ));

        $this->assertTrue(
            $this->filesystem->has('adapter_test5.txt')
        );
    }

    protected function testRenameFile()
    {
        $this->assertTrue($this->filesystem->rename(
            'adapter_test5.txt',
            'adapter_test6.txt'
        ));

        $this->assertTrue(
            $this->filesystem->has('adapter_test6.txt')
        );
        $this->assertFalse(
            $this->filesystem->has('adapter_test5.txt')
        );
    }

    protected function testGetMeta()
    {
        $meta = $this->filesystem->getMetadata('adapter_test.txt');
        $this->assertNotFalse($meta);

        $this->assertArrayHasKey('path', $meta);
        $this->assertArrayHasKey('type', $meta);
        $this->assertArrayHasKey('size', $meta);
        $this->assertArrayHasKey('timestamp', $meta);

        $this->assertIsInt($this->filesystem->getTimestamp('adapter_test.txt'));
        $this->assertIsInt($this->filesystem->getSize('adapter_test.txt'));
        $this->assertIsString(
            $this->filesystem->getMimetype('adapter_test.txt')
        );
    }

    protected function testVisibility()
    {
        $this->assertTrue($this->filesystem->setVisibility(
            'adapter_test.txt',
            'private'
        ));

        $this->assertEquals(
            'private',
            $this->filesystem->getVisibility('adapter_test.txt')
        );

        $this->assertTrue($this->filesystem->setVisibility(
            'adapter_test.txt',
            'public-read'
        ));

        $this->assertEquals(
            'public-read',
            $this->filesystem->getVisibility('adapter_test2.txt')
        );
    }

    protected function testListContents()
    {
        $result = $this->filesystem->listContents();
        $this->assertIsArray($result);
        $contents = array_column($result, 'path');
        $this->assertTrue(in_array('adapter_test.txt', $contents));
        $this->assertFalse(in_array(
            'adapter_test/adapter_test4.txt',
            $contents
        ));

        $result = $this->filesystem->listContents('', true);
        $contents = array_column($result, 'path');
        $this->assertTrue(in_array(
            'adapter_test/adapter_test4.txt',
            $contents
        ));

        $result = $this->filesystem->listContents('adapter_test/', false);
        $contents = array_column($result, 'path');
        $this->assertFalse(in_array('adapter_test.txt', $contents));
        $this->assertTrue(in_array(
            'adapter_test/adapter_test4.txt',
            $contents
        ));
    }

    protected function testDeleteFile()
    {
        $this->assertTrue($this->filesystem->delete('adapter_test.txt'));
        $this->assertTrue($this->filesystem->delete('adapter_test2.txt'));
        $this->assertTrue($this->filesystem->delete('adapter_test3.txt'));
        $this->assertTrue($this->filesystem->delete('adapter_test6.txt'));
        $this->assertTrue($this->filesystem->delete(
            'adapter_test/adapter_test4.txt'
        ));

        $this->assertFalse($this->filesystem->has('adapter_test.txt'));
    }
}

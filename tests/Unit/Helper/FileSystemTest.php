<?php

declare(strict_types=1);

namespace LetsEncrypt\Tests\Unit\Helper;

use LetsEncrypt\Exception\FileIOException;
use LetsEncrypt\Helper\FileSystem;
use LetsEncrypt\Tests\TestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class FileSystemTest extends TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $root;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup('root');
    }

    protected function tearDown(): void
    {
        $this->root = null;
    }

    public function testReadFileContentWithNotReadableFile(): void
    {
        $file = vfsStream::newFile('test.file', 0330)
            ->withContent('content')
            ->at($this->root);

        $this->expectException(FileIOException::class);
        $this->expectExceptionMessage('Unable to read file: vfs://root/test.file');

        FileSystem::readFileContent($file->url());
    }

    public function testReadFileContent(): void
    {
        $file = vfsStream::newFile('test.file', 0775)
            ->withContent('content')
            ->at($this->root);

        $this->assertSame('content', FileSystem::readFileContent($file->url()));
    }

    public function testWriteContentWithNotWritableFile(): void
    {
        $file = vfsStream::newFile('test.file', 0550)
            ->withContent('content')
            ->at($this->root);

        $this->expectException(FileIOException::class);
        $this->expectExceptionMessage('Unable to write file: vfs://root/test.file');

        FileSystem::writeFileContent($file->url(), 'changed');
    }

    public function testWriteFileContent(): void
    {
        $file = vfsStream::newFile('test.file', 0775)
            ->withContent('content')
            ->at($this->root);

        FileSystem::writeFileContent($file->url(), 'changed');

        $this->assertSame('changed', $file->getContent());
    }
}

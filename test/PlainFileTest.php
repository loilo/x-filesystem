<?php declare(strict_types=1);

namespace Loilo\XFilesystem\Test;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class PlainFileTest extends BaseTest
{
    public function testFileRead()
    {
        $this->assertSame(
            'hello world',
            $this->xfs->readFile(static::FIXTURES . '/plain.txt')
        );
    }

    public function testLinkRead()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Symlinks not available on Windows');
            return;
        }

        $this->assertSame(
            'hello world',
            $this->xfs->readFile(static::FIXTURES . '/plain-link.txt')
        );
    }

    public function testDirRead()
    {
        $this->expectException(FileNotFoundException::class);
        $this->xfs->readFile(static::FIXTURES . '/subfolder');
    }

    public function testDeadLinkRead()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Symlinks not available on Windows');
            return;
        }

        $non_existing_file_path = static::FIXTURES . '/non-existing.txt';
        $dead_link_path = static::FIXTURES . '/dead-link.txt';

        touch($non_existing_file_path);
        symlink($non_existing_file_path, $dead_link_path);
        unlink($non_existing_file_path);

        $this->expectException(FileNotFoundException::class);
        $this->xfs->readFile($dead_link_path);

        unlink($dead_link_path);
    }

    public function testNonExistingRead()
    {
        $this->expectException(FileNotFoundException::class);
        $this->xfs->readFile(static::FIXTURES . '/non-existing.txt');
    }
}

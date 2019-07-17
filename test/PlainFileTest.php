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
        $this->expectException(FileNotFoundException::class);
        $this->xfs->readFile(static::FIXTURES . '/dead-link.txt');
    }

    public function testNonExistingRead()
    {
        $this->expectException(FileNotFoundException::class);
        $this->xfs->readFile(static::FIXTURES . '/non-existing.txt');
    }
}

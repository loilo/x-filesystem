<?php declare(strict_types=1);

namespace Loilo\XFilesystem\Test;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Loilo\XFilesystem\XFilesystem;

abstract class BaseTest extends TestCase
{
    const FIXTURES = __DIR__ . '/Fixtures';
    const DIST = __DIR__ . '/dist';

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var XFilesystem
     */
    protected $xfs;

    protected function setUp()
    {
        $this->fs = new Filesystem();
        $this->xfs = new XFilesystem();

        $this->fs->mkdir(static::DIST);
    }

    protected function tearDown()
    {
        $this->fs->remove(static::DIST);
    }
}

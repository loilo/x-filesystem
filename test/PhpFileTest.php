<?php declare(strict_types=1);

namespace Loilo\XFilesystem\Test;

class PhpFileTest extends BaseTest
{
    use Fixtures\FamilyTrait;

    public function testPhpRead()
    {
        $this->assertSame(
            static::$familyAssoc,
            $this->xfs->readPhpFile(static::FIXTURES . '/family.php')
        );

        // Note: Caching behaviour cannot be tested since OPCache is not enabled in the CLI
    }

    public function testPhpWrite()
    {
        $path = static::DIST . '/family.php';
        $originalPhp = file_get_contents(static::FIXTURES . '/family.php');

        $this->xfs->dumpPhpFile($path, static::$familyAssoc);

        $this->assertEquals(
            $originalPhp,
            file_get_contents($path)
        );
    }
}

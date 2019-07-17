<?php declare(strict_types=1);

namespace Loilo\XFilesystem\Test;

class GlobTest extends BaseTest
{
    public function testGlobWithExtendedWildcard()
    {
        $expectedFiles = [
            __DIR__ . '/Fixtures/server/index.php',
            __DIR__ . '/Fixtures/FamilyTrait.php',
            __DIR__ . '/Fixtures/family.php'
        ];
        $foundFiles = $this->xfs->glob(__DIR__ . '/Fixtures/**/*.php');

        sort($expectedFiles);
        sort($foundFiles);

        $this->assertEquals(
            $expectedFiles,
            $foundFiles
        );
    }
}

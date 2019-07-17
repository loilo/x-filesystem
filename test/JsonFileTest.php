<?php declare(strict_types=1);

namespace Loilo\XFilesystem\Test;

class JsonFileTest extends BaseTest
{
    use Fixtures\FamilyTrait;

    public function testJsonRead()
    {
        // Default mode
        $this->assertEquals(
            static::$familyObject,
            $this->xfs->readJsonFile(static::FIXTURES . '/family.json')
        );

        // Parse object
        $this->assertEquals(
            static::$familyObject,
            $this->xfs->readJsonFile(static::FIXTURES . '/family.json', $this->xfs::PARSE_OBJECT)
        );

        // Parse associative array
        $this->assertSame(
            static::$familyAssoc,
            $this->xfs->readJsonFile(static::FIXTURES . '/family.json', $this->xfs::PARSE_ASSOC)
        );
    }

    public function testJsonWrite()
    {
        // Dump with pretty-print
        $path = static::DIST . '/family.json';
        $this->xfs->dumpJsonFile($path, static::$familyAssoc);

        $this->assertEquals(
            file_get_contents(static::FIXTURES . '/family.json'),
            file_get_contents($path)
        );

        // Dump without pretty-print
        $path = static::DIST . '/family.min.json';
        $this->xfs->dumpJsonFile($path, static::$familyAssoc, 0);

        $this->assertEquals(
            file_get_contents(static::FIXTURES . '/family.min.json'),
            file_get_contents($path)
        );
    }
}

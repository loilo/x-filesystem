<?php declare(strict_types=1);

namespace Loilo\XFilesystem\Test;

class CsvFileTest extends BaseTest
{
    use Fixtures\FamilyTrait;

    public function testCsvRead()
    {
        // Default mode
        $this->assertEquals(
            static::$familyObject,
            $this->xfs->readCsvFile(static::FIXTURES . '/family.csv')
        );

        // Parse object
        $this->assertEquals(
            static::$familyObject,
            $this->xfs->readCsvFile(static::FIXTURES . '/family.csv', $this->xfs::PARSE_OBJECT)
        );

        // Parse associative array
        $this->assertSame(
            static::$familyAssoc,
            $this->xfs->readCsvFile(static::FIXTURES . '/family.csv', $this->xfs::PARSE_ASSOC)
        );

        // Parse associative array
        $this->assertSame(
            static::$familyArray,
            $this->xfs->readCsvFile(static::FIXTURES . '/family.csv', $this->xfs::PARSE_ARRAY)
        );
    }

    public function testCsvWrite()
    {
        $originalCsv = file_get_contents(static::FIXTURES . '/family.csv');

        // Dump rows array
        $path = static::DIST . '/family-array.csv';
        $this->xfs->dumpCsvFile($path, static::$familyArray);

        $this->assertEquals(
            $originalCsv,
            file_get_contents($path)
        );

        // Dump associative array
        $path = static::DIST . '/family-assoc.csv';
        $this->xfs->dumpCsvFile($path, static::$familyAssoc);

        $this->assertEquals(
            $originalCsv,
            file_get_contents($path)
        );

        // Dump object
        $path = static::DIST . '/family-object.csv';
        $this->xfs->dumpCsvFile($path, static::$familyObject);

        $this->assertEquals(
            $originalCsv,
            file_get_contents($path)
        );
    }
}

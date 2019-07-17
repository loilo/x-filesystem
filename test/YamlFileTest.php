<?php declare(strict_types=1);

namespace Loilo\XFilesystem\Test;

class YamlFileTest extends BaseTest
{
    use Fixtures\FamilyTrait;

    public function testYamlRead()
    {
        // Default mode
        $this->assertEquals(
            static::$familyObject,
            $this->xfs->readYamlFile(static::FIXTURES . '/family.yml')
        );

        // Parse object
        $this->assertEquals(
            static::$familyObject,
            $this->xfs->readYamlFile(static::FIXTURES . '/family.yml', $this->xfs::PARSE_OBJECT)
        );

        // Parse associative array
        $this->assertSame(
            static::$familyAssoc,
            $this->xfs->readYamlFile(static::FIXTURES . '/family.yml', $this->xfs::PARSE_ASSOC)
        );
    }

    public function testYamlWrite()
    {
        // Dump associative array
        $path = static::DIST . '/family-assoc.yml';
        $originalYaml = file_get_contents(static::FIXTURES . '/family.yml');

        $this->xfs->dumpYamlFile($path, static::$familyAssoc);

        $this->assertEquals(
            $originalYaml,
            file_get_contents($path)
        );

        // Dump object
        $path = static::DIST . '/family-object.yml';
        $this->xfs->dumpYamlFile($path, static::$familyObject);

        $this->assertEquals(
            $originalYaml,
            file_get_contents($path)
        );
    }
}

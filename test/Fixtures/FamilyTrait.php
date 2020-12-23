<?php declare(strict_types=1);

namespace Loilo\XFilesystem\Test\Fixtures;

trait FamilyTrait
{
    /**
     * @var array
     */
    protected static $familyArray;

    /**
     * @var array
     */
    protected static $familyAssoc = [
        [ 'name' => 'John', 'birthday' => '1992-02-08', 'profession' => 'Teacher' ],
        [ 'name' => 'Jane', 'birthday' => '1994-03-22', 'profession' => 'Developer' ],
        [ 'name' => 'Charly', 'birthday' => '2016-11-11', 'profession' => '' ]
    ];

    /**
     * @var array
     */
    protected static $familyObject;

    public static function setUpBeforeClass(): void
    {
        static::$familyArray = array_merge(
            [ array_keys(static::$familyAssoc[0]) ],
            array_map(function (array $row) {
                return array_values($row);
            }, static::$familyAssoc)
        );

        static::$familyObject = array_map(function (array $member) {
            return (object) $member;
        }, static::$familyAssoc);
    }
}

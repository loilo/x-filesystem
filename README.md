<div align="center">
  <br>
  <img width="200" height="250" src="x.svg" alt="eXtended Filesystem logo: a document with a big &quot;x&quot; printed on it">
  <br>
  <br>

  # eXtended Filesystem
</div>

[![Test status on Travis](https://badgen.net/travis/loilo/x-filesystem?label=tests&icon=travis)](https://travis-ci.org/loilo/x-filesystem)
[![Version on packagist.org](https://badgen.net/packagist/v/loilo/x-filesystem)](https://packagist.org/packages/loilo/x-filesystem)

An extension to Symfony's [Filesystem Component](https://symfony.com/doc/current/components/filesystem.html) with

* recursive globbing support
* methods for reading and writing some popular data exchange formats (PHP, JSON, YAML, CSV)

## Installation
```bash
composer require loilo/x-filesystem
```

## Usage
`XFilesystem` is instantiated exactly like `Filesystem`:

```php
$fs = new Loilo\XFilesystem\XFilesystem();
```

No existing behavior is modified, every method available in `Filesystem` works exactly as expected.

### Find Files through Globs
This method matches the behavior of [PHP's built-in `glob` function](https://secure.php.net/manual/function.glob.php), but adds support for the recursive wildcard `/**/`:

#### Signature
```php
/**
 * Find files by a glob. As opposed to PHP's built-in "glob" function, this method supports the ** wildcard.
 * @see https://www.php.net/manual/en/function.glob.php#refsect1-function.glob-parameters
 *
 * @param string $pattern The pattern. No tilde expansion or parameter substitution is done.
 * @param integer $flags Flags to apply
 * @return array
 */
public function glob($pattern, $flags = 0)
```

#### Example
```php
$fs->glob('src/**/*.php');
```

### Read Plain Files
Weirdly enough, `Filesystem` has no built-in way to read plain files, so here we go:

#### Signature
```php
/**
 * Read the contents of a file
 *
 * @param string $filename    The file to read from
 * @param bool   $allowRemote Whether reading from remote sources should be allowed (defaults to `true`)
 * @return string The contents from the file
 *
 * @throws FileNotFoundException When the path does not exist or is not a file
 * @throws IOException           When the file exists but is not readable
 */
function readFile($filename)
```

#### Example
```php
$fs->readFile('plain.txt');
```


### Read JSON Files
### Signature
```php
/**
 * Read the contents of a file and parse them as JSON
 *
 * @param string $filename The file to read from
 * @param int    $mode     The parse mode:
 *                         `PARSE_ASSOC` to return an associative array
 *                         `PARSE_OBJECT` to return a \stdClass object
 * @return mixed The parsed JSON data
 *
 * @throws FileNotFoundException    When the path does not exist or is not a file
 * @throws IOException              When the file exists but is not readable
 * @throws UnexpectedValueException When parsing the JSON fails
 */
function readJsonFile($filename, $mode = self::PARSE_OBJECT)
```

#### Example
**`data.json`**
```json
{ "a": 1, "b": 2, "c": 3 }
```

**`read-json.php`**
```php
$fs->readJsonFile('data.json')
```

**`>>>`**
```
stdClass Object
(
    [a] => 1
    [b] => 2
    [c] => 3
)
```

### Write JSON Files
#### Signature
```php
/**
 * Dump data into a file as JSON
 *
 * @param string $filename The file to be written to
 * @param mixed  $data     The data to write to the file
 * @param int    $flags    The json_encode() flags to utilize
 *
 * @throws IOException              When the file cannot be written to
 * @throws UnexpectedValueException When encoding the JSON fails
 */
function dumpJsonFile(
    $filename,
    $data,
    $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
)
```

#### Example
**`write-json.php`**
```php
$fs->dumpJsonFile('data.json', [
  'a' => 1,
  'b' => 2,
  'c' => 3
]);
```

**`>>>`**
```json
{
    "a": 1,
    "b": 2,
    "c": 3
}
```


### Read YAML Files
#### Signature
```php
/**
 * Read the contents of a file and parses them as YAML
 *
 * @param string $filename The file to read from
 * @param int    $mode     The parse mode: `PARSE_ASSOC` to return an associative array, `PARSE_OBJECT` to return a \stdClass object
 * @return mixed The parsed YAML data
 *
 * @throws FileNotFoundException When the path does not exist or is not a file
 * @throws IOException           When the file exists but is not readable
 * @throws ParseException        When the file could not be read or the YAML is not valid
 */
function readYamlFile($filename, $mode = self::PARSE_OBJECT)
```

#### Example
**`data.yaml`**
```yaml
a: 1
b: 2
c: 3
```

**`read-yaml.php`**
```php
$fs->readYamlFile('data.yml')
```

**`>>>`**
```
stdClass Object
(
    [a] => 1
    [b] => 2
    [c] => 3
)
```

### Write YAML Files
#### Signature
```php
/**
 * Dump content into a file as YAML
 *
 * @param string $filename The file to be written to
 * @param mixed  $data     The data to write into the file
 * @param int    $inline   The level where to switch to inline YAML
 * @param int    $indent   The amount of spaces to use for indentation of nested nodes
 *
 * @throws IOException When the file cannot be written to
 */
function dumpYamlFile($filename, $data, $inline = 2, $indent = 4)
```

#### Example
**`write-yaml.php`**
```php
$fs->dumpYamlFile('data.yml', [
  'a' => 1,
  'b' => 2,
  'c' => 3
]);
```

**`>>>`**
```yaml
a: 1
b: 2
c: 3
```


### Read CSV Files
#### Signature
```php
/**
 * Read the contents of a file and parses them as CSV
 *
 * @param string $filename   The file to read from
 * @param int    $mode       The parse mode:
 *                           `PARSE_ARRAY` returns each row as an array
 *                           `PARSE_ASSOC` takes the first row as headers and returns associative arrays
 *                           `PARSE_OBJECT` takes the first row as headers and returns \stdClass objects
 * @param string $delimiter  The field delimiter (one character only)
 * @param string $charset    The charset the CSV file is encoded in
 * @param string $enclosure  The field enclosure (one character only)
 * @param string $escapeChar The escape character (one character only)
 * @return mixed The parsed CSV data
 *
 * @throws FileNotFoundException    When the path does not exist or is not a file
 * @throws IOException              When the file exists but is not readable
 * @throws UnexpectedValueException When the column count is inconsistent
 */
function readCsvFile(
    $filename,
    $mode       = self::PARSE_OBJECT,
    $delimiter  = ',',
    $charset    = 'UTF-8',
    $enclosure  = '"',
    $escapeChar = '\\'
)
```

#### Example
**`data.csv`**
```csv
a,b,c
1,2,3
4,5,6
7,8,9
```

**`read-csv.php`**
```php
$fs->readCsvFile('data.csv')
```

**`>>>`**
```
Array
(
    [0] => stdClass Object
        (
            [a] => 1
            [b] => 2
            [c] => 3
        )

    [1] => stdClass Object
        (
            [a] => 4
            [b] => 5
            [c] => 6
        )

    [2] => stdClass Object
        (
            [a] => 7
            [b] => 8
            [c] => 9
        )

)
```

### Write CSV Files
#### Signature
```php
/**
 * Dump content into a file as CSV
 *
 * @param string $filename   The file to be written to
 * @param mixed  $data       The data to write into the file
 * @param string $delimiter  The field delimiter (one character only)
 * @param string $enclosure  The field enclosure (one character only)
 * @param string $escapeChar The escape character (one character only)
 * @param int    $dumpMode   How to interpret passed data (CSV_DUMP_PLAIN or CSV_DUMP_STRUCTURED)
 *
 * @throws IOException When the file cannot be written to
 */
function dumpCsvFile(
    $filename,
    $data,
    $delimiter  = ',',
    $enclosure  = '"',
    $escapeChar = '\\',
    $dumpMode   = self::CSV_DUMP_DETECT
)
```

#### Example
**`write-csv.php`**
```php
$fs->dumpCsvFile('data.csv', [
    [ 'a', 'b', 'c' ],
    [  1,   2,   3  ],
    [  4,   5,   6  ],
    [  7,   8,   9  ]
]);
```

**`>>>`**
```csv
a,b,c
1,2,3
4,5,6
7,8,9
```


### Read PHP Files
Read PHP files that [`return` data](https://secure.php.net/manual/en/function.include.php#example-126).

> **WARNING!** This method utilizes PHP's `include` statement and has no safety nets against possible side effects and abuse through arbitrary code execution. Only ever use this with trusted files!

#### Signature
```php
/**
 * Read and return the contents of a PHP file
 *
 * @param string $filename The PHP file to include
 * @param int    $caching  The caching behaviour:
 *                         `PHP_ALLOW_CACHED` will evaluate the file only once
 *                         `PHP_INVALIDATE_CACHE` will re-evaluate it if it changed
 *                         `PHP_FORCE_INVALIDATE_CACHE` will re-evaluate it anyway
 *                          Note that only OPCache is utilized which usually is turned off in PHP CLI
 * @return mixed The data returned from the file
 *
 * @throws FileNotFoundException When the path does not exist or is not a file
 * @throws IOException           When the file exists but is not readable
 */
function readPhpFile($filename, $caching = self::PHP_ALLOW_CACHED)
```

#### Example
**`data.php`**
```php
<?php return [
  'a' => 1,
  'b' => 2,
  'c' => 3
]
```

**`read-php.php`**
```php
$fs->readPhpFile('data.php') === [
  'a' => 1,
  'b' => 2,
  'c' => 3
];
```

### Write PHP Files
#### Signature
```php
/**
 * Dump data into a file as PHP
 *
 * @param string $filename The file to be written to
 * @param string $data     The data to write into the file
 *
 * @throws IOException When the file cannot be written to
 */
function dumpPhpFile($filename, $data)
```

#### Example
**`write-php.php`**
```php
$fs->dumpPhpFile('data.php', [
  'a' => 1,
  'b' => 2,
  'c' => 3
]);
```

**`>>>`**
```
<?php return array(
  'a' => 1,
  'b' => 2,
  'c' => 3
);
```
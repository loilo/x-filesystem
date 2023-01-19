<?php declare(strict_types=1);

namespace Loilo\XFilesystem;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Symfony Filesystem component, extended by some file reading and dumping options
 */
class XFilesystem extends Filesystem
{
    const PHP_ALLOW_CACHED = 0;
    const PHP_INVALIDATE_CACHE = 1;
    const PHP_FORCE_INVALIDATE_CACHE = 2;

    const CSV_DUMP_PLAIN = 0;
    const CSV_DUMP_STRUCTURED = 1;
    const CSV_DUMP_DETECT = 3;

    const PARSE_ARRAY = 0;
    const PARSE_ASSOC = 1;
    const PARSE_OBJECT = 2;

    /**
     * @var bool
     */
    protected $remoteAllowed = false;

    /**
     * Sets the remoteAllowed flag.
     *
     * @param bool $remoteAllowed Whether to allow reading from HTTP(S) URLs or not
     */
    public function setRemoteAllowed(bool $remoteAllowed)
    {
        $this->remoteAllowed = $remoteAllowed;
    }

    /**
     * Tell whether reading from HTTP(S) sources is allowed
     *
     * @return bool
     */
    public function isRemoteAllowed()
    {
        return $this->remoteAllowed;
    }

    /**
     * Enforce the accessibility and readability of a file
     *
     * @param string $filename The file to check
     *
     * @throws FileNotFoundException When the path does not exist or is not a file
     * @throws IOException           When the file exists but is not readable
     */
    protected function enforceFileAccessibility(string $filename)
    {
        if (!file_exists($filename)) {
            throw new FileNotFoundException(
                sprintf('File "%s" does not exist.', $filename),
                0,
                null,
                $filename
            );
        }
        if (!is_file($filename)) {
            throw new FileNotFoundException(
                sprintf('Path "%s" exists but is not a file.', $filename),
                0,
                null,
                $filename
            );
        }
        if (!is_readable($filename)) {
            throw new IOException(
                sprintf('File "%s" exists but is not readable.', $filename),
                0,
                null,
                $filename
            );
        }
    }

    /**
     * Read the contents of a file
     *
     * @param string $filename    The file to read from
     * @param bool   $allowRemote Whether reading from remote sources should be allowed
     * @return string The contents from the file
     *
     * @throws FileNotFoundException When the path does not exist or is not a file
     * @throws IOException           When the file exists but is not readable
     */
    public function readFile(string $filename): string
    {
        $isRemote = false;

        if ($this->remoteAllowed && preg_match('|^https?://|', $filename)) {
            $isRemote = true;
        }

        if (!$isRemote) {
            $this->enforceFileAccessibility($filename);
            $result = file_get_contents($filename);
        } else {
            $result = file_get_contents($filename, false, stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: PHP'
                    ]
                ]
            ]));
        }

        if ($result === false) {
            throw new IOException(
                sprintf('Could not read from file "%s".', $filename),
                0,
                null,
                $filename
            );
        } else {
            return $result;
        }
    }

    /**
     * Read and return the contents of a PHP file.
     * Since this utilizes `include` and does not have any safety nets against remote code execution, only ever use it on trustworthy sources!
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
    public function readPhpFile(
        string $filename,
        int $caching = self::PHP_ALLOW_CACHED
    ) {
        $isCached = function_exists('opcache_is_script_cached')
            ? opcache_is_script_cached($filename)
            : false;

        if ($caching !== static::PHP_ALLOW_CACHED) {
            // Invalidate OPCache
            if ($isCached) {
                opcache_invalidate(
                    $filename,
                    $caching === static::PHP_FORCE_INVALIDATE_CACHE
                );
            }

            $this->enforceFileAccessibility($filename);

            // Enforce file accessibility only if not cached
        } elseif (!$isCached) {
            $this->enforceFileAccessibility($filename);
        }

        return include $filename;
    }

    /**
     * Dump data into a file as PHP
     *
     * @param string $filename The file to be written to
     * @param string $data     The data to write into the file
     *
     * @throws IOException When the file cannot be written to
     */
    public function dumpPhpFile(string $filename, $data)
    {
        return $this->dumpFile(
            $filename,
            sprintf("<?php return %s;\n", var_export($data, true))
        );
    }

    /**
     * Read the contents of a file and parse them as JSON
     *
     * @param string $filename The file to read from
     * @param int    $mode     The parse mode:
     *                         `PARSE_ASSOC` to return an associative array,
     *                         `PARSE_OBJECT` to return a \stdClass object
     * @return mixed The parsed JSON data
     *
     * @throws FileNotFoundException    When the path does not exist or is not a file
     * @throws IOException              When the file exists but is not readable
     * @throws UnexpectedValueException When parsing the JSON fails
     */
    public function readJsonFile(
        string $filename,
        int $mode = self::PARSE_OBJECT
    ) {
        // Check $mode flag
        if ($mode !== static::PARSE_ASSOC && $mode !== static::PARSE_OBJECT) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid $mode: "%s", must be one of XFilesystem::PARSE_ASSOC, XFilesystem::PARSE_OBJECT.',
                    $mode
                )
            );
        }

        $result = @json_decode(
            $this->readFile($filename),
            $mode === static::PARSE_ASSOC
        );

        // Throw on parse error
        if (json_last_error() !== 0) {
            throw new UnexpectedValueException(
                json_last_error_msg(),
                json_last_error()
            );
        }

        return $result;
    }

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
    public function dumpJsonFile(
        string $filename,
        $data,
        int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ) {
        $encodedData = @json_encode($data, $flags);

        if (json_last_error() !== 0) {
            throw new InvalidArgumentException(
                json_last_error_msg(),
                json_last_error()
            );
        }

        return $this->dumpFile($filename, $encodedData);
    }

    /**
     * Read the contents of a file and parses them as YAML
     *
     * @param string $filename The file to read from
     * @param int    $mode     The parse mode:
     *                         `PARSE_ASSOC` to return an associative array,
     *                         `PARSE_OBJECT` to return a \stdClass object
     * @return mixed The parsed YAML data
     *
     * @throws FileNotFoundException When the path does not exist or is not a file
     * @throws IOException           When the file exists but is not readable
     * @throws ParseException        When the file could not be read or the YAML is not valid
     */
    public function readYamlFile(
        string $filename,
        int $mode = self::PARSE_OBJECT
    ) {
        // Check $mode flag
        if ($mode !== static::PARSE_ASSOC && $mode !== static::PARSE_OBJECT) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid $mode: "%s", must be one of XFilesystem::PARSE_ASSOC, XFilesystem::PARSE_OBJECT.',
                    $mode
                )
            );
        }

        return Yaml::parse(
            $this->readFile($filename),
            $mode === self::PARSE_OBJECT
                ? Yaml::PARSE_OBJECT | Yaml::PARSE_OBJECT_FOR_MAP
                : 0
        );
    }

    /**
     * Dump content into a file as YAML.
     *
     * @param string $filename The file to be written to
     * @param mixed  $data     The data to write into the file
     * @param int    $inline   The level where to switch to inline YAML
     * @param int    $indent   The amount of spaces to use for indentation of nested nodes
     *
     * @throws IOException When the file cannot be written to
     */
    public function dumpYamlFile(
        string $filename,
        $data,
        int $inline = 2,
        int $indent = 4
    ) {
        return $this->dumpFile(
            $filename,
            Yaml::dump($data, $inline, $indent, Yaml::DUMP_OBJECT_AS_MAP)
        );
    }

    /**
     * Parses a CSV line
     *
     * @param string   $row        The line to parse
     * @param int      $index      The index of the line in the CSV
     * @param string   $filename   The name of the CSV file
     * @param int|null $length     The number of columns in the header
     * @param string   $delimiter  The field delimiter (one character only)
     * @param string   $enclosure  The field enclosure (one character only)
     * @param string   $escapeChar The escape character (one character only)
     * @return array An array of parsed fields
     *
     * @throws UnexpectedValueException When the column count doesn't match
     */
    protected function parseCsvLine(
        string $row,
        int $index,
        string $filename,
        ?int &$length,
        string $delimiter,
        string $enclosure,
        string $escapeChar
    ) {
        $row = trim($row);
        $fields = str_getcsv($row, $delimiter, $enclosure, $escapeChar);

        if (is_null($length)) {
            $length = sizeof($fields);
        } else {
            if (sizeof($fields) !== $length) {
                throw new UnexpectedValueException(
                    sprintf(
                        'Column count mismatch: Header row has %s columns, %s columns found in line %s of %s',
                        $length,
                        sizeof($fields),
                        $index + 1,
                        realpath($filename)
                    )
                );
            }
        }

        return $fields;
    }

    /**
     * Read the contents of a file and parses them as CSV
     *
     * @param string $filename   The file to read from
     * @param int    $mode       The parse mode:
     *                           `PARSE_ARRAY` to return each line as an array
     *                           `PARSE_ASSOC`/`PARSE_OBJECT` to take the first rowas headers
     *                           and return an associative array/a \stdClass object
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
    public function readCsvFile(
        string $filename,
        int $mode = self::PARSE_OBJECT,
        string $delimiter = ',',
        string $charset = 'UTF-8',
        string $enclosure = '"',
        string $escapeChar = '\\'
    ) {
        // Check $mode flag
        if ($mode !== static::PARSE_ARRAY &&
            $mode !== static::PARSE_ASSOC &&
            $mode !== static::PARSE_OBJECT
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid $mode: "%s", must be one of XFilesystem::PARSE_ARRAY, XFilesystem::PARSE_ASSOC, XFilesystem::PARSE_OBJECT.',
                    $mode
                )
            );
        }

        // Read and convert CSV file
        $csvString = $this->readFile($filename);
        if ($charset !== 'UTF-8') {
            $csvString = mb_convert_encoding($csvString, 'UTF-8', $charset);
        }

        // Parse rows
        $length = null;
        $rows = explode("\n", trim($csvString));
        $data = array_map(
            function (
                $row,
                $index
            ) use (
                $filename,
                &$length,
                $delimiter,
                $enclosure,
                $escapeChar
            ) {
                return $this->parseCsvLine(
                    $row,
                    $index,
                    $filename,
                    $length,
                    $delimiter,
                    $enclosure,
                    $escapeChar
                );
            },
            $rows,
            array_keys($rows)
        );

        // Structure parsed data according to $mode
        if ($mode === static::PARSE_ARRAY || empty($data)) {
            return $data;
        } else {
            $keys = array_shift($data);
            return array_map(function ($row) use ($mode, $keys) {
                $rowData = array_combine($keys, $row);
                if ($mode === static::PARSE_OBJECT) {
                    return (object) $rowData;
                } else {
                    return $rowData;
                }
            }, $data);
        }
    }

    /**
     * Alternative implementation of fputcsv() to support delimiters/enclosures
     * longer than one character.
     *
     * @param resource $handle   The file pointer must be valid, and must point to a file successfully opened by fopen() or fsockopen() (and not yet closed by fclose()).
     * @param array $fields      An array of strings.
     * @param string $delimiter  The optional delimiter parameter sets the field delimiter (one character only).
     * @param string $enclosure  The optional enclosure parameter sets the field enclosure (one character only).
     * @param string $escape_char The optional escape_char parameter sets the escape character (at most one character). An empty string ("") disables the proprietary escape mechanism.
     * @return int|false
     */
    private function fputcsv(
        $handle,
        array $fields,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape_char = '\\'
    ) {
        $delimiterEsc = preg_quote($delimiter, '/');
        $enclosureEsc = preg_quote($enclosure, '/');

        $enclosedChars = [$enclosure];
        if (strlen($escape_char) === 1) {
            $enclosedChars[] = $escape_char;
        }

        $output = [];
        foreach ($fields as $field) {
            $output[] = preg_match(
                "/(?:${delimiterEsc}|${enclosureEsc}|\\s)/",
                $field
            )
                ? $enclosure .
                    str_replace(
                        $enclosedChars,
                        array_map(function ($enclosedChar) use ($enclosure) {
                            return $enclosure . $enclosedChar;
                        }, $enclosedChars),
                        $field
                    ) .
                    $enclosure
                : $field;
        }

        return fwrite($handle, join($delimiter, $output) . "\n");
    }

    /**
     * Writes a line of CSV data to a resource
     *
     * @param resource $handle     The resource to write to
     * @param array    $lineData   The (non-associative) array of data to encode as CSV
     * @param int      $lineNo     The line number the $lineData is associated with
     * @param int      $format     The format (plain or structured) the original data had
     * @param string   $delimiter  The field delimiter (one character only)
     * @param string   $enclosure  The field enclosure (one character only)
     * @param string   $escapeChar The escape character (one character only)
     *
     * @throws InvalidArgumentException When writing the CSV fails
     */
    protected function writeCsvLine(
        $handle,
        array $lineData,
        int $lineNo,
        string $delimiter,
        string $enclosure,
        string $escapeChar
    ) {
        if (strlen($delimiter) > 1 || strlen($enclosure) > 1) {
            $result = $this->fputcsv(
                $handle,
                $lineData,
                $delimiter,
                $enclosure,
                $escapeChar
            );
        } else {
            $result = fputcsv(
                $handle,
                $lineData,
                $delimiter,
                $enclosure,
                $escapeChar
            );
        }

        if ($result === false) {
            // Negative line number = error is in keys of the structured data
            if ($lineNo === -1) {
                throw new InvalidArgumentException('Invalid data in headline');
            }

            throw new InvalidArgumentException(
                sprintf('Invalid data in item #%s', $lineNo)
            );
        }
    }

    /**
     * Dump content into a file as CSV
     *
     * @param string $filename   The file to be written to
     * @param mixed  $data       The data to write into the file
     * @param string $delimiter  The field delimiter (one character only)
     * @param string $enclosure  The field enclosure (one character only)
     * @param string $escapeChar The escape character (one character only)
     * @param int    $dumpMode   How to interpret passed data (plain or structured), auto-detection by default
     *
     * @throws IOException When the file cannot be written to
     */
    public function dumpCsvFile(
        string $filename,
        array $data,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escapeChar = '\\',
        int $dumpMode = self::CSV_DUMP_DETECT
    ) {
        // Detect dump mode from first data item
        if ($dumpMode === static::CSV_DUMP_DETECT) {
            if (empty($data)) {
                $dumpMode = static::CSV_DUMP_PLAIN;
            } else {
                // Array of objects
                if (is_object($data[0])) {
                    $dumpMode = static::CSV_DUMP_STRUCTURED;
                } elseif (is_array($data[0])) {
                    // Array of scalar values
                    if (array_keys($data[0]) === range(0, sizeof($data[0]) - 1)) {
                        $dumpMode = static::CSV_DUMP_PLAIN;

                        // Array of associative arrays
                    } else {
                        $dumpMode = static::CSV_DUMP_STRUCTURED;
                    }

                    // Invalid
                } else {
                    throw new InvalidArgumentException(
                        'Could not detect dump mode from CSV data'
                    );
                }
            }
        }

        // Validate dump mode
        if ($dumpMode === static::CSV_DUMP_STRUCTURED) {
            if (!empty($data)) {
                // Convert \stdClass objects to arrays
                $data = array_map(function ($line) {
                    return (array) $line;
                }, $data);

                $heads = array_keys($data[0]);
                $lines = array_map('array_values', $data);
                $data = array_merge([$heads], $lines);
            }
        } elseif ($dumpMode !== static::CSV_DUMP_PLAIN) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid $dumpMode: "%s", must be one of XFilesystem::CSV_DUMP_PLAIN, XFilesystem::CSV_DUMP_STRUCTURED.',
                    $dumpMode
                )
            );
        }

        // Write CSV to memory first, to be able to atomically dump
        $csv = fopen('php://memory', 'r+');
        $length = null;
        for ($line = 0; $line < sizeof($data); $line++) {
            $row = $data[$line];

            if (is_null($length)) {
                $length = sizeof($row);
            } else {
                if (sizeof($row) !== $length) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'Column count mismatch: There are %o header columns, %s columns found in item #%s',
                            $length,
                            sizeof($row),
                            $dumpMode === static::CSV_DUMP_PLAIN
                                ? $line + 1
                                : $line,
                            realpath($filename)
                        )
                    );
                }
            }

            $this->writeCsvLine(
                $csv,
                $row,
                // CSV_DUMP_STRUCTURED data has a prepended header line, therefore -1 on the line count
                $dumpMode === static::CSV_DUMP_PLAIN ? $line + 1 : $line,
                $delimiter,
                $enclosure,
                $escapeChar
            );
        }
        rewind($csv);

        return $this->dumpFile($filename, rtrim(stream_get_contents($csv)));
    }

    /**
     * Find files by a glob. As opposed to PHP's built-in "glob" function, this method supports the ** wildcard.
     *
     * @param string $pattern The pattern. No tilde expansion or parameter substitution is done.
     * @param integer $flags Flags to apply, @see https://www.php.net/manual/en/function.glob.php#refsect1-function.glob-parameters
     * @return array
     */
    public function glob(string $pattern, int $flags = 0): array
    {
        // Keep away the hassles of the rest if we don't use the wildcard anyway
        if (mb_strpos($pattern, '/**/') === false) {
            return glob($pattern, $flags);
        }

        $patternParts = explode('/**/', $pattern, 2);

        $firstPart = $patternParts[0];
        $remainder = $patternParts[1];

        // Get sub dirs
        $dirs = glob($firstPart . '/*', GLOB_ONLYDIR | GLOB_NOSORT);

        // Get files for current dir
        $files = glob($pattern, $flags);

        if (mb_strpos($remainder, '/') === false) {
            $files = array_merge(
                $files,
                glob($firstPart . '/' . $remainder, $flags)
            );
        }

        foreach ($dirs as $dir) {
            $subDirContent = $this->glob($dir . '/**/' . $remainder, $flags);
            $files = array_merge($files, $subDirContent);
        }

        return array_unique($files);
    }
}

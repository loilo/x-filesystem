<?php declare(strict_types=1);

namespace Loilo\XFilesystem\Test;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Process\Process;

class HttpTest extends BaseTest
{
    protected static $serverUrl;
    protected static $serverProcess;

    public static function setUpBeforeClass()
    {
        // Get free port
        $sock = socket_create_listen(0);
        socket_getsockname($sock, $addr, $port);
        socket_close($sock);

        // Start built-in server
        $origin = '127.0.0.1:' . $port;
        static::$serverUrl = 'http://' . $origin;
        static::$serverProcess = new Process(
            'php -S ' . $origin,
            static::FIXTURES . '/server',
            null,
            null,
            0
        );
        static::$serverProcess->start();

        // Make multiple attempts to connect to the server
        for ($i = 0; $i < 20; $i++) {
            if ($i > 0) {
                // Wait 1/4s before next attempt
                usleep(250 * 1000);
            }

            $reporting = error_reporting();
            error_reporting(E_ERROR);
            $result = get_headers(static::$serverUrl, 1);
            error_reporting($reporting);

            if (is_array($result)) {
                return;
            }
        }

        // Not connected after multiple attempts
        // -> fail the whole test suite
        static::fail('Could not set up test server');
    }

    public static function tearDownAfterClass()
    {
        static::$serverProcess->stop(3, SIGINT);
    }

    public function testUnallowedRead()
    {
        $this->expectException(FileNotFoundException::class);
        $this->xfs->readFile($this::$serverUrl);
    }

    public function testAllowedRead()
    {
        $this->xfs->setRemoteAllowed(true);
        try {
            $this->assertSame(
                "Success\n",
                $this->xfs->readFile($this::$serverUrl)
            );
        } finally {
            $this->xfs->setRemoteAllowed(false);
        }
    }
}

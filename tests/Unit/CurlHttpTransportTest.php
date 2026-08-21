<?php

declare(strict_types=1);

namespace Openpay\Tests\Unit;

use Openpay\Data\CurlHttpTransport;
use Openpay\Data\OpenpayApiConnectionError;
use Openpay\Data\OpenpayApiConsole;
use Openpay\Data\OpenpayApiError;
use PHPUnit\Framework\TestCase;

final class CurlHttpTransportTest extends TestCase
{
    private static int $port = 0;

    /** @var resource|false */
    private static $server = false;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $script = \dirname(__DIR__).'/Fixtures/curl-echo-server.php';
        $port = self::findFreePort();
        $cmd = \sprintf(
            '%s -S 127.0.0.1:%d %s',
            escapeshellarg(\PHP_BINARY),
            $port,
            escapeshellarg($script),
        );
        self::$server = proc_open($cmd, [
            0 => ['pipe', 'r'],
            1 => ['file', sys_get_temp_dir().'/openpay-curl-server.log', 'w'],
            2 => ['file', sys_get_temp_dir().'/openpay-curl-server.err', 'w'],
        ], $pipes);
        if (!\is_resource(self::$server)) {
            self::fail('Unable to start the fixture HTTP server');
        }
        self::$port = $port;
        self::waitUntilReady($port);
    }

    public static function tearDownAfterClass(): void
    {
        if (\is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
        parent::tearDownAfterClass();
    }

    protected function tearDown(): void
    {
        OpenpayApiConsole::setLevel(OpenpayApiConsole::CONSOLE_NONE);
        OpenpayApiConsole::printToScreen(false);
        parent::tearDown();
    }

    public function testGetPostPutDeleteAgainstLocalServer(): void
    {
        $transport = new CurlHttpTransport(2, 5);
        $base = 'http://127.0.0.1:'.self::$port;
        [$body, $code] = $transport->send('get', $base.'/', ['Accept: application/json'], null, null);
        self::assertSame(200, $code);
        self::assertStringContainsString('ok', $body);

        [$echo, $created] = $transport->send('post', $base.'/echo', ['Content-Type: application/json'], '{"a":1}', 'sk_test');
        self::assertSame(201, $created);
        self::assertStringContainsString('POST', $echo);

        [$putBody, $putCode] = $transport->send('put', $base.'/echo', ['Content-Type: application/json'], '{}', null);
        self::assertSame(201, $putCode);
        self::assertStringContainsString('PUT', $putBody);

        [$delBody, $delCode] = $transport->send('delete', $base.'/', [], null, null);
        self::assertSame(200, $delCode);
        self::assertStringContainsString('ok', $delBody);
    }

    public function testNonUtf8BodyTriggersWarnPath(): void
    {
        OpenpayApiConsole::setLevel(OpenpayApiConsole::CONSOLE_ALL);
        OpenpayApiConsole::printToScreen(true);
        $transport = new CurlHttpTransport(2, 5);
        ob_start();
        [$body, $code] = $transport->send('get', 'http://127.0.0.1:'.self::$port.'/latin1', [], null, null);
        ob_end_clean();
        self::assertSame(200, $code);
        self::assertNotSame('', $body);
    }

    public function testInvalidMethod(): void
    {
        $this->expectException(OpenpayApiError::class);
        (new CurlHttpTransport())->send('patch', 'http://127.0.0.1/', [], null, null);
    }

    public function testConnectionErrorOnClosedPort(): void
    {
        $this->expectException(OpenpayApiConnectionError::class);
        (new CurlHttpTransport(1, 1))->send('get', 'http://127.0.0.1:1/', [], null, null);
    }

    public function testResolveHostError(): void
    {
        $this->expectException(OpenpayApiConnectionError::class);
        (new CurlHttpTransport(1, 2))->send('get', 'https://127.0.0.1:'.self::$port.'/', [], null, null);
    }

    private static function findFreePort(): int
    {
        $sock = stream_socket_server('tcp://127.0.0.1:0');
        if (false === $sock) {
            throw new \RuntimeException('Unable to allocate a TCP port');
        }
        $name = stream_socket_get_name($sock, false);
        fclose($sock);
        if (false === $name || !str_contains($name, ':')) {
            throw new \RuntimeException('Unable to read bound port');
        }

        return (int) substr($name, strrpos($name, ':') + 1);
    }

    private static function waitUntilReady(int $port): void
    {
        $deadline = microtime(true) + 5;
        while (microtime(true) < $deadline) {
            $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
            if (\is_resource($fp)) {
                fclose($fp);

                return;
            }
            usleep(50000);
        }
        self::fail('Fixture HTTP server did not start on port '.$port);
    }
}

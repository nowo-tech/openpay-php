<?php

declare(strict_types=1);

namespace Openpay\Tests\Unit;

use Openpay\Data\Openpay;
use Openpay\Data\OpenpayApiConnector;
use Openpay\Data\OpenpayApiRequestError;
use PHPUnit\Framework\TestCase;

final class OpenpayHttpTransportTest extends TestCase
{
    private FakeOpenpayHttpTransport $transport;

    protected function setUp(): void
    {
        parent::setUp();
        Openpay::reset();
        $this->transport = new FakeOpenpayHttpTransport();
        Openpay::setHttpTransport($this->transport);
        Openpay::configure('aaaaaaaaaaaaaaaaaaaa', 'sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'MX', '127.0.0.1');
    }

    protected function tearDown(): void
    {
        Openpay::reset();
        parent::tearDown();
    }

    public function testGetRequestUsesFakeTransportAndDecodesJson(): void
    {
        $this->transport->body = '{"id":"trxyz","status":"completed"}';
        $this->transport->status = 200;

        $response = OpenpayApiConnector::request('get', '/charges', ['limit' => 1]);

        self::assertSame(['id' => 'trxyz', 'status' => 'completed'], $response);
        self::assertCount(1, $this->transport->calls);
        self::assertSame('get', $this->transport->calls[0]['method']);
        self::assertStringContainsString('/charges?limit=1', $this->transport->calls[0]['url']);
        self::assertNull($this->transport->calls[0]['body']);
        self::assertSame('sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', $this->transport->calls[0]['auth']);
    }

    public function testPostRequestSendsJsonBody(): void
    {
        $this->transport->body = '{"id":"chxyz"}';
        $this->transport->status = 201;

        $response = OpenpayApiConnector::request('post', '/charges', ['amount' => 100, 'method' => 'card']);

        self::assertSame(['id' => 'chxyz'], $response);
        self::assertSame('post', $this->transport->calls[0]['method']);
        self::assertSame('{"amount":100,"method":"card"}', $this->transport->calls[0]['body']);
        self::assertContains('Content-Type: application/json', $this->transport->calls[0]['headers']);
    }

    public function testHttpErrorMapsToRequestError(): void
    {
        $this->transport->body = '{"error_code":1001,"description":"bad","category":"request"}';
        $this->transport->status = 400;

        $this->expectException(OpenpayApiRequestError::class);
        $this->expectExceptionMessage('bad');
        OpenpayApiConnector::request('get', '/charges');
    }

    public function testResetClearsCustomTransport(): void
    {
        $reflection = new \ReflectionClass(OpenpayApiConnector::class);
        $property = $reflection->getProperty('transport');

        Openpay::setHttpTransport($this->transport);
        self::assertSame($this->transport, $property->getValue());

        Openpay::reset();
        self::assertNull($property->getValue());
    }
}

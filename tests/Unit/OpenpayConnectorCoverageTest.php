<?php

declare(strict_types=1);

namespace Openpay\Tests\Unit;

use Openpay\Data\Openpay;
use Openpay\Data\OpenpayApiAuthError;
use Openpay\Data\OpenpayApiConnectionError;
use Openpay\Data\OpenpayApiConnector;
use Openpay\Data\OpenpayApiError;
use Openpay\Data\OpenpayApiRequestError;
use Openpay\Data\OpenpayApiTransactionError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OpenpayConnectorCoverageTest extends TestCase
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

    public function testCustomUserAgentAndPutDeleteAndNestedQuery(): void
    {
        Openpay::setUserAgent('NowoOpenpay/test');
        $this->transport->body = '{}';
        OpenpayApiConnector::request('PUT', '/webhooks/whaaaaaaaaaaaaaaaa', ['url' => 'https://example.test']);
        OpenpayApiConnector::request('delete', '/webhooks/whaaaaaaaaaaaaaaaa', ['force' => '1']);
        OpenpayApiConnector::request('get', '/charges', [
            'filter' => ['a' => '1', 'b' => null],
            'tags' => ['x', 'y'],
        ]);

        self::assertSame('put', $this->transport->calls[0]['method']);
        self::assertContains('User-Agent: NowoOpenpay/test', $this->transport->calls[0]['headers']);
        self::assertSame('delete', $this->transport->calls[1]['method']);
        self::assertStringContainsString('force=1', $this->transport->calls[1]['url']);
        self::assertStringContainsString('filter%5Ba%5D=1', $this->transport->calls[2]['url']);
        self::assertStringContainsString('tags%5B%5D=x', $this->transport->calls[2]['url']);
        self::assertStringNotContainsString('filter%5Bb%5D', $this->transport->calls[2]['url']);
    }

    public function testEmptyBodyIsDecodedAsEmptyArray(): void
    {
        $this->transport->body = '';
        $this->transport->status = 204;
        self::assertSame([], OpenpayApiConnector::request('get', '/charges'));
    }

    public function testInvalidJsonThrowsRequestError(): void
    {
        $this->transport->body = '{not-json';
        $this->expectException(OpenpayApiRequestError::class);
        OpenpayApiConnector::request('get', '/charges');
    }

    public function testInvalidMethodRejected(): void
    {
        $this->expectException(OpenpayApiError::class);
        OpenpayApiConnector::request('patch', '/charges');
    }

    #[DataProvider('errorStatusProvider')]
    public function testHttpErrorMapping(int $status, string $expected): void
    {
        $this->transport->body = '{"error_code":1001,"description":"mapped","category":"request","request_id":"r1","fraud_rules":["f"]}';
        $this->transport->status = $status;
        $this->expectException($expected);
        OpenpayApiConnector::request('get', '/charges');
    }

    /**
     * @return list<array{0: int, 1: class-string<\Throwable>}>
     */
    public static function errorStatusProvider(): array
    {
        return [
            [401, OpenpayApiAuthError::class],
            [403, OpenpayApiAuthError::class],
            [400, OpenpayApiRequestError::class],
            [404, OpenpayApiRequestError::class],
            [413, OpenpayApiRequestError::class],
            [422, OpenpayApiRequestError::class],
            [500, OpenpayApiRequestError::class],
            [503, OpenpayApiRequestError::class],
            [402, OpenpayApiTransactionError::class],
            [409, OpenpayApiTransactionError::class],
            [412, OpenpayApiTransactionError::class],
            [423, OpenpayApiTransactionError::class],
            [418, OpenpayApiError::class],
        ];
    }

    public function testErrorBodyWithoutErrorCode(): void
    {
        $this->transport->body = '{"description":"no code"}';
        $this->transport->status = 400;
        $this->expectException(OpenpayApiRequestError::class);
        $this->expectExceptionMessage('Invalid response body');
        OpenpayApiConnector::request('get', '/charges');
    }

    public function testNanJsonEncodeThrows(): void
    {
        $this->expectException(OpenpayApiError::class);
        $this->expectExceptionMessage('Failed to encode request as JSON');
        OpenpayApiConnector::request('post', '/charges', ['n' => \NAN]);
    }

    public function testMissingMerchantId(): void
    {
        Openpay::reset();
        Openpay::setHttpTransport($this->transport);
        Openpay::setApiKey('sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb');
        Openpay::setPublicIp('127.0.0.1');
        $this->expectException(OpenpayApiAuthError::class);
        $this->expectExceptionMessage('Empty or no Merchant ID');
        OpenpayApiConnector::request('get', '/');
    }

    public function testInvalidMerchantId(): void
    {
        Openpay::reset();
        Openpay::setHttpTransport($this->transport);
        Openpay::setId('short');
        Openpay::setApiKey('sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb');
        Openpay::setPublicIp('127.0.0.1');
        $this->expectException(OpenpayApiAuthError::class);
        $this->expectExceptionMessage('Invalid Merchant ID');
        OpenpayApiConnector::request('get', '/');
    }

    public function testMissingApiKey(): void
    {
        Openpay::reset();
        Openpay::setHttpTransport($this->transport);
        Openpay::setId('aaaaaaaaaaaaaaaaaaaa');
        Openpay::setPublicIp('127.0.0.1');
        $this->expectException(OpenpayApiAuthError::class);
        $this->expectExceptionMessage('Empty or no Private Key');
        OpenpayApiConnector::request('get', '/');
    }

    public function testInvalidApiKey(): void
    {
        Openpay::reset();
        Openpay::setHttpTransport($this->transport);
        Openpay::setId('aaaaaaaaaaaaaaaaaaaa');
        Openpay::setApiKey('not-a-key');
        Openpay::setPublicIp('127.0.0.1');
        $this->expectException(OpenpayApiAuthError::class);
        $this->expectExceptionMessage('Invalid Private Key');
        OpenpayApiConnector::request('get', '/');
    }

    public function testMissingPublicIp(): void
    {
        Openpay::reset();
        Openpay::setHttpTransport($this->transport);
        Openpay::configure('aaaaaaaaaaaaaaaaaaaa', 'sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'MX', '127.0.0.1');
        $ref = new \ReflectionClass(Openpay::class);
        $ref->getProperty('publicIp')->setValue(null, null);
        $this->expectException(OpenpayApiAuthError::class);
        $this->expectExceptionMessage('Empty or no public ip');
        OpenpayApiConnector::request('get', '/');
    }

    public function testInvalidPublicIp(): void
    {
        Openpay::reset();
        Openpay::setHttpTransport($this->transport);
        Openpay::configure('aaaaaaaaaaaaaaaaaaaa', 'sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'MX', 'not-an-ip');
        $this->expectException(OpenpayApiAuthError::class);
        $this->expectExceptionMessage('Invalid public ip');
        OpenpayApiConnector::request('get', '/');
    }

    public function testMissingEndpoint(): void
    {
        Openpay::reset();
        Openpay::setHttpTransport($this->transport);
        Openpay::setId('aaaaaaaaaaaaaaaaaaaa');
        Openpay::setApiKey('sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb');
        Openpay::setPublicIp('127.0.0.1');
        $this->expectException(OpenpayApiConnectionError::class);
        $this->expectExceptionMessage('No API endpoint set');
        OpenpayApiConnector::request('get', '/');
    }

    public function testDispatchRejectsUnknownMethodViaReflection(): void
    {
        $connector = (new \ReflectionClass(OpenpayApiConnector::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(OpenpayApiConnector::class, 'dispatch');
        $this->expectException(OpenpayApiError::class);
        $this->expectExceptionMessage('Invalid request method');
        $method->invoke($connector, 'patch', 'https://example.test', [], [], null);
    }

    public function testEncodeToQueryStringNonArrayPassthrough(): void
    {
        $connector = (new \ReflectionClass(OpenpayApiConnector::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(OpenpayApiConnector::class, 'encodeToQueryString');
        self::assertSame('raw', $method->invoke($connector, 'raw'));
    }
}

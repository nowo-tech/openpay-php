<?php

declare(strict_types=1);

namespace Openpay\Tests;

use Openpay\Data\Openpay;
use Openpay\Data\OpenpayApiConnector;
use Openpay\Data\OpenpaySession;
use PHPUnit\Framework\TestCase;

final class OpenpaySessionTest extends TestCase
{
    protected function tearDown(): void
    {
        Openpay::reset();
        parent::tearDown();
    }

    public function testRunResetsCredentialsEvenWhenTheCallbackThrows(): void
    {
        $transport       = new FakeOpenpayHttpTransport();
        $transport->body = '{"id":"ok"}';
        $session         = new OpenpaySession(
            'aaaaaaaaaaaaaaaaaaaa',
            'sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
            'MX',
            '127.0.0.1',
            $transport,
        );

        try {
            $session->run(function () {
                self::assertSame('sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', Openpay::getApiKey());
                throw new \RuntimeException('charge failed');
            });
            self::fail('expected RuntimeException');
        } catch (\RuntimeException $e) {
            self::assertSame('charge failed', $e->getMessage());
        }

        self::assertNull(Openpay::getApiKey());
        self::assertNull(Openpay::getId());
    }

    public function testRunReturnsCallbackResult(): void
    {
        $transport       = new FakeOpenpayHttpTransport();
        $transport->body = '{"id":"ch1"}';
        $session         = new OpenpaySession(
            'aaaaaaaaaaaaaaaaaaaa',
            'sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
            transport: $transport,
        );

        $result = $session->run(fn () => OpenpayApiConnector::request('get', '/charges'));

        self::assertSame(['id' => 'ch1'], $result);
        self::assertNull(Openpay::getApiKey());
    }
}

<?php

declare(strict_types=1);

namespace Openpay\Tests\Unit;

use Openpay\Data\Openpay;
use Openpay\Data\OpenpayApiConnector;
use PHPUnit\Framework\TestCase;

final class OpenpayCredentialsTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $previousEnv = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->captureEnv([
            'OPENPAY_MERCHANT_ID',
            'OPENPAY_API_KEY',
            'OPENPAY_COUNTRY',
            'OPENPAY_PUBLIC_IP',
            'OPENPAY_PRODUCTION_MODE',
        ]);
        $this->clearOpenpayEnv();
        Openpay::reset();
    }

    protected function tearDown(): void
    {
        Openpay::reset();
        $this->restoreEnv();
        parent::tearDown();
    }

    public function testConfigureThenResetDoesNotReadProcessEnvironment(): void
    {
        putenv('OPENPAY_API_KEY=sk_aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
        putenv('OPENPAY_MERCHANT_ID=aaaaaaaaaaaaaaaaaaaa');
        putenv('OPENPAY_PRODUCTION_MODE=true');

        Openpay::configure('bbbbbbbbbbbbbbbbbbbb', 'sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'CO', '1.1.1.1');
        self::assertSame('sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', Openpay::getApiKey());
        self::assertSame('bbbbbbbbbbbbbbbbbbbb', Openpay::getId());

        Openpay::reset();

        self::assertNull(Openpay::getApiKey());
        self::assertNull(Openpay::getId());
        self::assertTrue(Openpay::getSandboxMode());
        self::assertSame('', Openpay::getEndpointUrl());
    }

    public function testConfigureFromEnvironmentRestoresCredentialsAfterReset(): void
    {
        putenv('OPENPAY_API_KEY=sk_cccccccccccccccccccccccccccccccc');
        putenv('OPENPAY_MERCHANT_ID=cccccccccccccccccccc');
        putenv('OPENPAY_COUNTRY=PE');
        putenv('OPENPAY_PUBLIC_IP=8.8.8.8');
        putenv('OPENPAY_PRODUCTION_MODE=true');

        Openpay::reset();
        Openpay::configureFromEnvironment();

        self::assertSame('sk_cccccccccccccccccccccccccccccccc', Openpay::getApiKey());
        self::assertSame('cccccccccccccccccccc', Openpay::getId());
        self::assertSame('PE', Openpay::getCountry());
        self::assertSame('8.8.8.8', Openpay::getPublicIp());
        self::assertFalse(Openpay::getSandboxMode());
        self::assertTrue(Openpay::getProductionMode());
        self::assertSame('https://api.openpay.pe/v1', Openpay::getEndpointUrl());
    }

    public function testFreshProcessFallsBackToEnvironment(): void
    {
        putenv('OPENPAY_API_KEY=sk_dddddddddddddddddddddddddddddddd');
        putenv('OPENPAY_MERCHANT_ID=dddddddddddddddddddd');

        $this->simulateFreshProcess();

        self::assertSame('sk_dddddddddddddddddddddddddddddddd', Openpay::getApiKey());
        self::assertSame('dddddddddddddddddddd', Openpay::getId());
    }

    public function testGetInstanceWritesCredentialsAndDisablesEnvironmentFallback(): void
    {
        putenv('OPENPAY_API_KEY=sk_eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee');
        putenv('OPENPAY_MERCHANT_ID=eeeeeeeeeeeeeeeeeeee');

        Openpay::getInstance('ffffffffffffffffffff', 'sk_ffffffffffffffffffffffffffffffff', 'MX', '127.0.0.1');

        self::assertSame('sk_ffffffffffffffffffffffffffffffff', Openpay::getApiKey());
        self::assertSame('ffffffffffffffffffff', Openpay::getId());

        Openpay::reset();
        self::assertNull(Openpay::getApiKey());
        self::assertNull(Openpay::getId());
    }

    public function testSetProductionModeWinsOverEnvironment(): void
    {
        $this->simulateFreshProcess();
        putenv('OPENPAY_PRODUCTION_MODE=true');
        self::assertFalse(Openpay::getSandboxMode());

        Openpay::setProductionMode(false);
        self::assertTrue(Openpay::getSandboxMode());
        self::assertFalse(Openpay::getProductionMode());
    }

    public function testSdkVersionConstant(): void
    {
        self::assertSame('3.2.1', Openpay::VERSION);
    }

    public function testConnectorResetIsIdempotent(): void
    {
        OpenpayApiConnector::reset();
        OpenpayApiConnector::reset();
        $this->addToAssertionCount(1);
    }

    private function simulateFreshProcess(): void
    {
        Openpay::reset();
        $reflection = new \ReflectionClass(Openpay::class);
        foreach (['useEnvironmentCredentials', 'useEnvironmentProductionMode'] as $property) {
            $reflection->getProperty($property)->setValue(null, true);
        }
    }

    /**
     * @param list<string> $names
     */
    private function captureEnv(array $names): void
    {
        foreach ($names as $name) {
            $this->previousEnv[$name] = getenv($name);
        }
    }

    private function clearOpenpayEnv(): void
    {
        foreach (array_keys($this->previousEnv) as $name) {
            putenv($name);
        }
    }

    private function restoreEnv(): void
    {
        foreach ($this->previousEnv as $name => $value) {
            if (false === $value) {
                putenv($name);
            } else {
                putenv($name.'='.$value);
            }
        }
    }
}

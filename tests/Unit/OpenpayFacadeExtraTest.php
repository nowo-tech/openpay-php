<?php

declare(strict_types=1);

namespace Openpay\Tests\Unit;

use Openpay\Data\Openpay;
use Openpay\Data\OpenpayApi;
use PHPUnit\Framework\TestCase;

final class OpenpayFacadeExtraTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $previousEnv = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['OPENPAY_MERCHANT_ID', 'OPENPAY_API_KEY', 'OPENPAY_COUNTRY', 'OPENPAY_PUBLIC_IP', 'OPENPAY_PRODUCTION_MODE'] as $name) {
            $this->previousEnv[$name] = getenv($name);
            putenv($name);
        }
        Openpay::reset();
    }

    protected function tearDown(): void
    {
        Openpay::reset();
        foreach ($this->previousEnv as $name => $value) {
            if (false === $value) {
                putenv($name);
            } else {
                putenv($name.'='.$value);
            }
        }
        parent::tearDown();
    }

    public function testCountryEndpointsAndEglobalClassification(): void
    {
        Openpay::configure('aaaaaaaaaaaaaaaaaaaa', 'sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'CO', '1.1.1.1');
        self::assertSame('https://sandbox-api.openpay.co/v1', Openpay::getEndpointUrl());

        Openpay::setSandboxMode(false);
        self::assertSame('https://api.openpay.co/v1', Openpay::getEndpointUrl());

        Openpay::configure('aaaaaaaaaaaaaaaaaaaa', 'sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'PE', '1.1.1.1');
        Openpay::setProductionMode(true);
        self::assertSame('https://api.openpay.pe/v1', Openpay::getEndpointUrl());

        Openpay::setClassificationMerchant('eglobal');
        self::assertSame('eglobal', Openpay::getClassificationMerchant());
        Openpay::setCountry('MX');
        Openpay::setEndpointUrl('MX');
        Openpay::setSandboxMode(true);
        self::assertSame('https://sand-api.ecommercebbva.com/v1', Openpay::getEndpointUrl());
        Openpay::setSandboxMode(false);
        self::assertSame('https://api.ecommercebbva.com/v1', Openpay::getEndpointUrl());
    }

    public function testNoopsOnEmptySetters(): void
    {
        Openpay::configure('aaaaaaaaaaaaaaaaaaaa', 'sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb');
        Openpay::setUserAgent('');
        self::assertSame('', Openpay::getUserAgent());
        Openpay::setClassificationMerchant('');
        self::assertSame('', Openpay::getClassificationMerchant());
        Openpay::setApiKey('');
        self::assertSame('sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', Openpay::getApiKey());
        Openpay::setId('');
        self::assertSame('aaaaaaaaaaaaaaaaaaaa', Openpay::getId());
        Openpay::setCountry('');
        self::assertSame('MX', Openpay::getCountry());
        Openpay::setPublicIp();
        self::assertSame('127.0.0.1', Openpay::getPublicIp());
    }

    public function testConfigureFromEnvironmentPartialDoesNotCallConfigure(): void
    {
        putenv('OPENPAY_MERCHANT_ID=aaaaaaaaaaaaaaaaaaaa');
        putenv('OPENPAY_COUNTRY=CO');
        Openpay::configureFromEnvironment();
        self::assertSame('aaaaaaaaaaaaaaaaaaaa', Openpay::getId());
        self::assertNull(Openpay::getApiKey());
        self::assertSame('CO', Openpay::getCountry());
        self::assertSame('https://sandbox-api.openpay.co/v1', Openpay::getEndpointUrl());
    }

    public function testGetInstanceDefaultCountryAndCreateRootUrl(): void
    {
        $root = Openpay::getInstance('aaaaaaaaaaaaaaaaaaaa', 'sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb');
        self::assertInstanceOf(OpenpayApi::class, $root);
        self::assertSame('', $root->getFullURL());
        self::assertSame('MX', Openpay::getCountry());
    }

    public function testUnknownCountryLeavesEndpointBlank(): void
    {
        Openpay::configure('aaaaaaaaaaaaaaaaaaaa', 'sk_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'XX', '127.0.0.1');
        self::assertSame('', Openpay::getEndpointUrl());
    }
}

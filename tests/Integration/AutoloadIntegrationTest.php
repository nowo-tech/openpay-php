<?php

declare(strict_types=1);

namespace Openpay\Tests\Integration;

use Openpay\Data\Openpay;
use PHPUnit\Framework\TestCase;

final class AutoloadIntegrationTest extends TestCase
{
    public function testVersionConstantIsSemver(): void
    {
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', Openpay::VERSION);
    }
}

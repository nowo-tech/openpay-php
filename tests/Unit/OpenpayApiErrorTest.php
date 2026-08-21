<?php

declare(strict_types=1);

namespace Openpay\Tests\Unit;

use Openpay\Data\OpenpayApiError;
use PHPUnit\Framework\TestCase;

final class OpenpayApiErrorTest extends TestCase
{
    public function testDefaultConstructorValues(): void
    {
        $error = new OpenpayApiError();
        self::assertSame('', $error->getMessage());
        self::assertNull($error->getDescription());
        self::assertSame(0, $error->getErrorCode());
        self::assertSame('', $error->getCategory());
        self::assertSame(0, $error->getHttpCode());
        self::assertSame('', $error->getRequestId());
        self::assertSame([], $error->getFraudRules());
    }

    public function testConstructorAcceptsNullMessageAndNonIntegerCode(): void
    {
        $error = new OpenpayApiError(null, '1001', 'request', 'req_1', 400, ['rule']);

        self::assertSame('', $error->getMessage());
        self::assertSame(1001, $error->getCode());
        self::assertSame('1001', $error->getErrorCode());
        self::assertSame('request', $error->getCategory());
        self::assertSame(400, $error->getHttpCode());
        self::assertSame('req_1', $error->getRequestId());
        self::assertSame(['rule'], $error->getFraudRules());
    }
}

<?php

declare(strict_types=1);

namespace Openpay\Tests\Unit;

use Openpay\Data\OpenpayApiConsole;
use PHPUnit\Framework\TestCase;

final class OpenpayConsoleTest extends TestCase
{
    protected function tearDown(): void
    {
        OpenpayApiConsole::setLevel(OpenpayApiConsole::CONSOLE_NONE);
        OpenpayApiConsole::printToScreen(false);
        parent::tearDown();
    }

    public function testAllLevelsPrintToScreen(): void
    {
        OpenpayApiConsole::printToScreen(true);
        OpenpayApiConsole::setLevel(OpenpayApiConsole::CONSOLE_ALL);
        ob_start();
        OpenpayApiConsole::trace('t');
        OpenpayApiConsole::debug('d');
        OpenpayApiConsole::info('i');
        OpenpayApiConsole::warn('w');
        OpenpayApiConsole::error('e');
        OpenpayApiConsole::critical('c');
        $out = (string) ob_get_clean();
        self::assertStringContainsString('[TRACE]', $out);
        self::assertStringContainsString('[DEBUG]', $out);
        self::assertStringContainsString('[INFO]', $out);
        self::assertStringContainsString('[WARNING]', $out);
        self::assertStringContainsString('[ERROR]', $out);
        self::assertStringContainsString('[CRITICAL]', $out);
    }

    public function testSyslogPathWhenNotPrintingToScreen(): void
    {
        OpenpayApiConsole::printToScreen(false);
        OpenpayApiConsole::setLevel(OpenpayApiConsole::CONSOLE_ALL);
        OpenpayApiConsole::info('syslog-path');
        $this->addToAssertionCount(1);
    }

    public function testDisabledLevelIsSilent(): void
    {
        OpenpayApiConsole::printToScreen(true);
        OpenpayApiConsole::setLevel(OpenpayApiConsole::CONSOLE_NONE);
        ob_start();
        OpenpayApiConsole::info('nope');
        $out = (string) ob_get_clean();
        self::assertSame('', $out);
    }
}

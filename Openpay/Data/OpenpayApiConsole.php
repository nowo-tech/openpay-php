<?php

declare(strict_types=1);

namespace Openpay\Data;

class OpenpayApiConsole
{
    public const CONSOLE_NONE = 0;
    public const CONSOLE_TRACE = 2;
    public const CONSOLE_DEBUG = 4;
    public const CONSOLE_INFO = 8;
    public const CONSOLE_WARNING = 16;
    public const CONSOLE_ERROR = 32;
    public const CONSOLE_CRITICAL = 64;
    public const CONSOLE_ALL = 126;

    private static $instance;
    private $level;
    private $toScreen;

    private function __construct()
    {
        $this->level = self::CONSOLE_NONE;
        $this->toScreen = false;
    }

    private function record($prefix, $text): void
    {
        $output = $prefix.': '.print_r($text, true);
        if (true == $this->toScreen) {
            print_r('<pre>'.$output.'</pre>'."\n");
        } else {
            syslog(\LOG_INFO, $output);
        }
    }

    private function checkFlag($flag, $value)
    {
        return ($flag & $value) == $flag;
    }

    private static function getInstance()
    {
        if (null == self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private static function _log($type, $flag, $text): void
    {
        $logger = self::getInstance();
        if ($logger->checkFlag($flag, $logger->level)) {
            $logger->record('['.strtoupper($type).']', $text);
        }
    }

    // -----------------------------------------------
    public static function setLevel($level): void
    {
        $instance = self::getInstance();
        $instance->level = $level;
    }

    public static function printToScreen($flag): void
    {
        $instance = self::getInstance();
        $instance->toScreen = ($flag ? true : false);
    }

    public static function trace($text): void
    {
        self::_log('trace', self::CONSOLE_TRACE, $text);
    }

    public static function debug($text): void
    {
        self::_log('debug', self::CONSOLE_DEBUG, $text);
    }

    public static function info($text): void
    {
        self::_log('info', self::CONSOLE_INFO, $text);
    }

    public static function warn($text): void
    {
        self::_log('warning', self::CONSOLE_WARNING, $text);
    }

    public static function error($text): void
    {
        self::_log('error', self::CONSOLE_ERROR, $text);
    }

    public static function critical($text): void
    {
        self::_log('critical', self::CONSOLE_CRITICAL, $text);
    }
}

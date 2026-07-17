<?php

declare(strict_types=1);

namespace Openpay\Data;

/**
 * Openpay SDK credential holder (Nowo fork).
 *
 * Credentials are process-global statics (upstream SDK design). Callers must use
 * {@see configure()} / {@see getInstance()} per logical operation and {@see reset()}
 * afterwards so merchant keys never leak across HTTP requests — including under
 * php-fpm workers and FrankenPHP ZTS / worker mode.
 */
class Openpay
{
    private static $instance = null;

    private static $id = null;

    private static $apiKey = null;

    private static $userAgent = '';

    private static $country = null;

    private static string $apiEndpoint = '';

    private static string $apiSandboxEndpoint = '';

    private static bool $sandboxMode = true;

    private static $classification = '';

    private static $publicIp = null;

    /**
     * Clears all merchant credentials and endpoint state.
     * Safe to call between requests / after each API session.
     */
    public static function reset(): void
    {
        self::$instance             = null;
        self::$id                   = null;
        self::$apiKey               = null;
        self::$userAgent            = '';
        self::$country              = null;
        self::$apiEndpoint          = '';
        self::$apiSandboxEndpoint   = '';
        self::$sandboxMode          = true;
        self::$classification       = '';
        self::$publicIp             = null;
    }

    /**
     * Overwrites static credentials for the next API calls (no merge with previous merchant).
     */
    public static function configure(string $id, string $apiKey, string $country = 'MX', string $publicIp = '127.0.0.1'): void
    {
        self::$id        = $id;
        self::$apiKey    = $apiKey;
        self::$country   = $country;
        self::$publicIp  = $publicIp;
        self::setEndpointUrl($country);
    }

    public static function getInstance(string $id, string $apiKey, ?string $country = '', ?string $publicIp = null)
    {
        $country  = ($country !== null && $country !== '') ? $country : 'MX';
        $publicIp = $publicIp ?? '127.0.0.1';

        self::configure($id, $apiKey, $country, $publicIp);

        return OpenpayApi::createRoot();
    }

    public static function setUserAgent($userAgent): void
    {
        if ($userAgent !== '')
        {
            self::$userAgent = $userAgent;
        }
    }

    public static function getUserAgent()
    {
        return self::$userAgent;
    }

    public static function setClassificationMerchant($classification): void
    {
        if ($classification !== '')
        {
            self::$classification = $classification;
        }
    }

    public static function getClassificationMerchant()
    {
        return self::$classification;
    }

    public static function setApiKey($key = ''): void
    {
        if ($key !== '')
        {
            self::$apiKey = $key;
        }
    }

    public static function getApiKey()
    {
        $key = self::$apiKey;

        if (!$key)
        {
            return getenv('OPENPAY_API_KEY');
        }

        return $key;
    }

    public static function setId($id = ''): void
    {
        if ($id !== '')
        {
            self::$id = $id;
        }
    }

    public static function setCountry($country = ''): void
    {
        if ($country !== '')
        {
            self::$country = $country;
        }
    }

    public static function getCountry()
    {
        return self::$country;
    }

    public static function getId()
    {
        $id = self::$id;

        if (!$id)
        {
            return getenv('OPENPAY_MERCHANT_ID');
        }

        return $id;
    }

    public static function setPublicIp($publicIp = null): void
    {
        if (null !== $publicIp)
        {
            self::$publicIp = $publicIp;
        }
    }

    public static function getPublicIp()
    {
        return self::$publicIp;
    }

    public static function getSandboxMode()
    {
        if (getenv('OPENPAY_PRODUCTION_MODE'))
        {
            return strtoupper(getenv('OPENPAY_PRODUCTION_MODE')) === 'FALSE';
        }

        return self::$sandboxMode;
    }

    public static function setSandboxMode($mode): void
    {
        self::$sandboxMode = (bool) $mode;
    }

    public static function getProductionMode(): bool
    {
        $sandbox = self::$sandboxMode;

        if (getenv('OPENPAY_PRODUCTION_MODE'))
        {
            $sandbox = (strtoupper(getenv('OPENPAY_PRODUCTION_MODE')) === 'FALSE');
        }

        return !$sandbox;
    }

    public static function setProductionMode($mode): void
    {
        self::$sandboxMode = !(bool) $mode;
    }

    public static function setEndpointUrl($country): void
    {
        if ($country === 'MX')
        {
            if (self::getClassificationMerchant() !== 'eglobal')
            {
                self::$apiEndpoint        = 'https://api.openpay.mx/v1';
                self::$apiSandboxEndpoint = 'https://sandbox-api.openpay.mx/v1';
            }
            else
            {
                self::$apiEndpoint        = 'https://api.ecommercebbva.com/v1';
                self::$apiSandboxEndpoint = 'https://sand-api.ecommercebbva.com/v1';
            }
        }
        elseif ($country === 'CO')
        {
            self::$apiEndpoint        = 'https://api.openpay.co/v1';
            self::$apiSandboxEndpoint = 'https://sandbox-api.openpay.co/v1';
        }
        elseif ($country === 'PE')
        {
            self::$apiEndpoint        = 'https://api.openpay.pe/v1';
            self::$apiSandboxEndpoint = 'https://sandbox-api.openpay.pe/v1';
        }
    }

    public static function getEndpointUrl(): string
    {
        return self::getSandboxMode() ? self::$apiSandboxEndpoint : self::$apiEndpoint;
    }
}

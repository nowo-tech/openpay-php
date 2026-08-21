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
    public const VERSION = '3.2.1';

    private static $instance;

    private static $id;

    private static $apiKey;

    private static $userAgent = '';

    private static $country;

    private static string $apiEndpoint = '';

    private static string $apiSandboxEndpoint = '';

    private static bool $sandboxMode = true;

    private static $classification = '';

    private static $publicIp;

    /**
     * When true, empty static id/key fall back to OPENPAY_MERCHANT_ID / OPENPAY_API_KEY.
     * Starts true for single-tenant BC. {@see reset()} turns it off.
     */
    private static bool $useEnvironmentCredentials = true;

    /**
     * When true, OPENPAY_PRODUCTION_MODE overrides {@see $sandboxMode}.
     * Starts true for single-tenant BC. {@see reset()} turns it off.
     */
    private static bool $useEnvironmentProductionMode = true;

    /**
     * Clears all merchant credentials and endpoint state.
     * Safe to call between requests / after each API session.
     *
     * After reset(), OPENPAY_API_KEY / OPENPAY_MERCHANT_ID /
     * OPENPAY_PRODUCTION_MODE are not read until {@see configure()},
     * {@see getInstance()}, or {@see configureFromEnvironment()}.
     */
    public static function reset(): void
    {
        self::$instance = null;
        self::$id = null;
        self::$apiKey = null;
        self::$userAgent = '';
        self::$country = null;
        self::$apiEndpoint = '';
        self::$apiSandboxEndpoint = '';
        self::$sandboxMode = true;
        self::$classification = '';
        self::$publicIp = null;
        self::$useEnvironmentCredentials = false;
        self::$useEnvironmentProductionMode = false;
        OpenpayApiConnector::reset();
    }

    public static function setHttpTransport(?OpenpayHttpTransport $transport): void
    {
        OpenpayApiConnector::setTransport($transport);
    }

    /**
     * Overwrites static credentials for the next API calls (no merge with previous merchant).
     */
    public static function configure(string $id, string $apiKey, string $country = 'MX', string $publicIp = '127.0.0.1'): void
    {
        self::$useEnvironmentCredentials = false;
        self::$useEnvironmentProductionMode = false;
        self::$id = $id;
        self::$apiKey = $apiKey;
        self::$country = $country;
        self::$publicIp = $publicIp;
        self::setEndpointUrl($country);
    }

    /**
     * Loads merchant credentials from OPENPAY_* environment variables.
     * Call this per request after {@see reset()} when the app is env-only.
     */
    public static function configureFromEnvironment(): void
    {
        $id = self::envString('OPENPAY_MERCHANT_ID');
        $apiKey = self::envString('OPENPAY_API_KEY');
        $country = self::envString('OPENPAY_COUNTRY') ?? 'MX';
        $publicIp = self::envString('OPENPAY_PUBLIC_IP') ?? '127.0.0.1';

        if (null !== $id && null !== $apiKey) {
            self::configure($id, $apiKey, $country, $publicIp);
        } else {
            self::$useEnvironmentCredentials = false;
            self::$useEnvironmentProductionMode = false;
            self::$id = $id;
            self::$apiKey = $apiKey;
            self::$country = $country;
            self::$publicIp = $publicIp;
            self::setEndpointUrl($country);
        }

        $production = self::envString('OPENPAY_PRODUCTION_MODE');
        if (null !== $production) {
            self::$sandboxMode = 'FALSE' === strtoupper($production);
        }
    }

    private static function envString(string $name): ?string
    {
        $value = getenv($name);

        if (false === $value || '' === $value) {
            return null;
        }

        return $value;
    }

    public static function getInstance(string $id, string $apiKey, ?string $country = '', ?string $publicIp = null)
    {
        $country = (null !== $country && '' !== $country) ? $country : 'MX';
        $publicIp ??= '127.0.0.1';

        self::configure($id, $apiKey, $country, $publicIp);

        return OpenpayApi::createRoot();
    }

    public static function setUserAgent($userAgent): void
    {
        if ('' !== $userAgent) {
            self::$userAgent = $userAgent;
        }
    }

    public static function getUserAgent()
    {
        return self::$userAgent;
    }

    public static function setClassificationMerchant($classification): void
    {
        if ('' !== $classification) {
            self::$classification = $classification;
        }
    }

    public static function getClassificationMerchant()
    {
        return self::$classification;
    }

    public static function setApiKey($key = ''): void
    {
        if ('' !== $key) {
            self::$useEnvironmentCredentials = false;
            self::$apiKey = $key;
        }
    }

    public static function getApiKey()
    {
        if (self::$apiKey) {
            return self::$apiKey;
        }

        return self::$useEnvironmentCredentials ? self::envString('OPENPAY_API_KEY') : null;
    }

    public static function setId($id = ''): void
    {
        if ('' !== $id) {
            self::$useEnvironmentCredentials = false;
            self::$id = $id;
        }
    }

    public static function setCountry($country = ''): void
    {
        if ('' !== $country) {
            self::$country = $country;
        }
    }

    public static function getCountry()
    {
        return self::$country;
    }

    public static function getId()
    {
        if (self::$id) {
            return self::$id;
        }

        return self::$useEnvironmentCredentials ? self::envString('OPENPAY_MERCHANT_ID') : null;
    }

    public static function setPublicIp($publicIp = null): void
    {
        if (null !== $publicIp) {
            self::$publicIp = $publicIp;
        }
    }

    public static function getPublicIp()
    {
        return self::$publicIp;
    }

    public static function getSandboxMode()
    {
        if (self::$useEnvironmentProductionMode) {
            $production = self::envString('OPENPAY_PRODUCTION_MODE');
            if (null !== $production) {
                return 'FALSE' === strtoupper($production);
            }
        }

        return self::$sandboxMode;
    }

    public static function setSandboxMode($mode): void
    {
        self::$useEnvironmentProductionMode = false;
        self::$sandboxMode = (bool) $mode;
    }

    public static function getProductionMode(): bool
    {
        return !self::getSandboxMode();
    }

    public static function setProductionMode($mode): void
    {
        self::$useEnvironmentProductionMode = false;
        self::$sandboxMode = !(bool) $mode;
    }

    public static function setEndpointUrl($country): void
    {
        if ('MX' === $country) {
            if ('eglobal' !== self::getClassificationMerchant()) {
                self::$apiEndpoint = 'https://api.openpay.mx/v1';
                self::$apiSandboxEndpoint = 'https://sandbox-api.openpay.mx/v1';
            } else {
                self::$apiEndpoint = 'https://api.ecommercebbva.com/v1';
                self::$apiSandboxEndpoint = 'https://sand-api.ecommercebbva.com/v1';
            }
        } elseif ('CO' === $country) {
            self::$apiEndpoint = 'https://api.openpay.co/v1';
            self::$apiSandboxEndpoint = 'https://sandbox-api.openpay.co/v1';
        } elseif ('PE' === $country) {
            self::$apiEndpoint = 'https://api.openpay.pe/v1';
            self::$apiSandboxEndpoint = 'https://sandbox-api.openpay.pe/v1';
        }
    }

    public static function getEndpointUrl(): string
    {
        return self::getSandboxMode() ? self::$apiSandboxEndpoint : self::$apiEndpoint;
    }
}

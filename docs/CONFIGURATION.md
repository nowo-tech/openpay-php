# Configuration

Credentials are **process statics**. Always isolate them per request on php-fpm
and FrankenPHP workers.

## Merchant credentials

```php
use Openpay\Data\Openpay;

Openpay::configure($merchantId, $privateKey, 'MX', $publicIp);
$openpay = Openpay::getInstance();
```

Or in one call:

```php
$openpay = Openpay::getInstance($merchantId, $privateKey, 'MX', $publicIp);
```

## Environment variables

`OPENPAY_MERCHANT_ID`, `OPENPAY_API_KEY`, `OPENPAY_PUBLIC_IP`, `OPENPAY_SANDBOX`
(and country) are read by `getInstance()` **only when** the statics are empty.

After `Openpay::reset()`, env vars are **not** re-read until
`Openpay::configureFromEnvironment()` or a new `configure()` / `getInstance()`
with arguments.

## HTTP transport

Default is cURL (`CurlHttpTransport`). Timeouts are constructor arguments
(connect / total) so FrankenPHP workers are not left hung (REQ-RUNTIME-001).

```php
use Openpay\Data\CurlHttpTransport;
use Openpay\Data\Openpay;

Openpay::setHttpTransport(new CurlHttpTransport(connectTimeout: 5, timeout: 20));
```

`reset()` restores the cURL default.

## Request-scoped session

```php
use Openpay\Data\OpenpaySession;

$session = new OpenpaySession($merchantId, $privateKey, 'MX', $publicIp);
$charge  = $session->run(fn ($openpay) => $openpay->charges->add($payload));
```

`reset()` always runs when the callback returns or throws.

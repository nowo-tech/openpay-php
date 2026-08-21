# Configuration

Credentials are **process statics**. Always isolate them per request on php-fpm
and FrankenPHP workers.

## Merchant credentials

```php
use Openpay\Data\Openpay;
use Openpay\Data\OpenpayApi;

Openpay::configure($merchantId, $privateKey, 'MX', $publicIp);
$openpay = OpenpayApi::createRoot();
```

Or credentials and root in one call (`getInstance` requires id and key):

```php
$openpay = Openpay::getInstance($merchantId, $privateKey, 'MX', $publicIp);
```

## Environment variables

On a **fresh process** that has never called `reset()` / `configure()` /
`getInstance()`, empty statics still fall back to:

- `OPENPAY_MERCHANT_ID`
- `OPENPAY_API_KEY`
- `OPENPAY_COUNTRY` (default `MX`)
- `OPENPAY_PUBLIC_IP` (default `127.0.0.1`)
- `OPENPAY_PRODUCTION_MODE` (`FALSE` → sandbox; any other value → production)

After `Openpay::reset()`, those variables are **not** re-read until
`Openpay::configureFromEnvironment()` or a new `configure()` / `getInstance($id, $apiKey, …)`.

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

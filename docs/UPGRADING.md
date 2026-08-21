# Upgrading

## 3.1.1.1 → 3.2.0

This is the first Nowo fork release after the Packagist rename. It stays a
drop-in for `openpay/sdk` **3.1.1** (`Openpay\\` namespaces, `replace` in
`composer.json`). Read this before bumping if you use env vars, a manual
install, or PHP older than 8.3.

### PHP 8.3 is required

`composer.json` now has `"php": ">=8.3"`.

`3.1.1.1` declared `>=8.1` but already used `#[Override]` (PHP 8.3), so 8.1/8.2
were already broken. If you are on 8.3+, only the Composer constraint changes.

### `reset()` no longer reads `OPENPAY_*`

After `Openpay::reset()`, `getApiKey()`, `getId()`, and `getSandboxMode()`
return the cleared statics. They do **not** fall back to
`OPENPAY_API_KEY` / `OPENPAY_MERCHANT_ID` / `OPENPAY_PRODUCTION_MODE`.

That stops a later merchant from inheriting the worker environment (php-fpm /
FrankenPHP).

**Before (leaked env after reset):**

```php
Openpay::reset();
// getApiKey() could still return getenv('OPENPAY_API_KEY')
```

**After — pick one per request:**

```php
Openpay::configure($id, $apiKey, $country, $publicIp);
// or
Openpay::getInstance($id, $apiKey, $country, $publicIp);
// or, env-only apps:
Openpay::reset();
Openpay::configureFromEnvironment();
```

**Recommended** on workers:

```php
use Openpay\Data\OpenpaySession;

$session = new OpenpaySession($id, $apiKey, 'MX', $publicIp);
$charge  = $session->run(fn ($openpay) => $openpay->charges->add($payload));
```

`OpenpaySession` always calls `reset()` in `finally`.

On a **fresh process** that never called `reset()`, empty statics still fall
back to `OPENPAY_*` (same as upstream). The change is only after `reset()` /
`configure()` / `getInstance()`.

### Manual install bootstrap

Root `Openpay.php` no longer `require()`s every class (that fatals if mixed
with Composer). It registers a PSR-4 autoload.

```php
require __DIR__ . '/openpay-php/Openpay.php';

use Openpay\Data\Openpay;
```

The old path `Openpay/Openpay.php` never existed; the bootstrap file lives at
the repository root.

Composer users should keep using `vendor/autoload.php` only. Do not also
`require` `Openpay.php`.

### TLS / `cacert.pem`

`Openpay/Data/cacert.pem` is gone. Failed CA verification no longer retries
with that bundle. PHP/cURL must use the OS trust store (Debian, FrankenPHP
images, etc. already do).

### Logging

`OpenpayApiConsole` debug no longer prints HTTP bodies or `__set` values.
Enable console logging only for method + URL + status.

### HTTP client

cURL is still the default. To mock tests or wrap Guzzle / Symfony HttpClient:

```php
use Openpay\Data\Openpay;
use Openpay\Data\CurlHttpTransport;
use Openpay\Data\OpenpayHttpTransport;

Openpay::setHttpTransport(new CurlHttpTransport(connectTimeout: 5, timeout: 20));
// or any OpenpayHttpTransport implementation
```

`reset()` restores the cURL default. There is no `psr/http-client` Composer
requirement.

### Exceptions

`OpenpayApiError` still exposes `getErrorCode()` as the API value (string or
int). `Exception::getCode()` is always `int`. Invalid JSON from the API now
throws `OpenpayApiRequestError` instead of returning `null`.

### User-Agent

Default User-Agent is `OpenpayPhp/3.2.0` (was `OpenpayPhp/v2`). Override with
`Openpay::setUserAgent()`.

### Composer

```sh
composer require nowo-tech/openpay-php:^3.2
```

`replace` of `openpay/sdk` 3.1.1 is unchanged. Apps that required
`nowo-tech/openpay-php:3.1.1.1` should move to `^3.2`.

# Changelog

All notable changes to this Nowo fork are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

The Composer package is `nowo-tech/openpay-php`. It still `replace`s upstream
`openpay/sdk` **3.1.1** (namespaces stay `Openpay\\`).

## [3.2.0] - 2026-08-21

Fork release on top of `3.1.1.1`. PHP 8.3+, request-isolated credentials, injectable HTTP.

### Added

- `Openpay::configureFromEnvironment()` to reload `OPENPAY_*` after `reset()`.
- `Openpay::setHttpTransport()` / `OpenpayHttpTransport` / `CurlHttpTransport`
  (cURL remains the default; no new production dependencies).
- `OpenpaySession` — configure, run a callback, always `reset()` (including on throw).
- `Openpay::VERSION` (`3.2.0`); User-Agent is `OpenpayPhp/3.2.0`.
- PHPUnit 11 suite and GitHub Actions on PHP 8.3–8.6.
- `ext-json` and `ext-mbstring` declared in `composer.json`.

### Changed

- PHP constraint is `>=8.3` (honest: `#[Override]` already required 8.3).
- After `reset()`, `getApiKey()` / `getId()` / `getSandboxMode()` no longer
  fall back to `OPENPAY_*` process environment.
- Root `Openpay.php` is a PSR-4 autoload for manual installs (no eager `require()`).
- JSON encode/decode uses `JSON_THROW_ON_ERROR`.
- `declare(strict_types=1)` on every PHP file; closing `?>` removed.
- `OpenpayApiResourceBase::__set` no longer treats `0` / `false` as empty.

### Removed

- Bundled `Openpay/Data/cacert.pem` (Mozilla CA data from August 2023). TLS uses the OS trust store.
- `curl_close()` and `utf8_encode()` (deprecated / removed on current PHP).
- Eclipse `.project` and `NOTES.txt` (SDK 1.2.0 / PHP 5.2).
- Fossil `composer.lock` pinning PHPUnit 4.8.

### Security

- Debug logs no longer dump HTTP bodies or `__set` property values.
- `reset()` no longer lets a later tenant inherit `OPENPAY_API_KEY` from the worker process.

### Fixed

- `refreshData()` guarded with `isset()` (PHP 8 undefined-key warnings).
- `OpenpayApiError` casts message/code before `Exception::__construct()`.
- Extra argument to `encodeToQueryString()` removed.

## [3.1.1.1] - 2026-08-21

First Packagist release of the Nowo fork (`nowo-tech/openpay-php`).

- Drop-in `replace` of `openpay/sdk` 3.1.1.
- `Openpay::configure()`, `Openpay::reset()`, `OpenpayApi::createRoot()` for
  php-fpm / FrankenPHP workers.
- Composer name changed from `openpay/sdk` because that vendor is claimed on Packagist.

[3.2.0]: https://github.com/nowo-tech/openpay-php/compare/3.1.1.1...3.2.0
[3.1.1.1]: https://github.com/nowo-tech/openpay-php/releases/tag/3.1.1.1

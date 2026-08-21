# Feature Specification: Openpay PHP SDK baseline

**Feature Branch**: `001-baseline`  
**Created**: 2026-08-21  
**Status**: Active  
**Input**: Product spec for the Nowo Openpay PHP SDK (`nowo-tech/openpay-php`), covering 100% of production units under `Openpay/` plus root `Openpay.php`.

**Related docs**: [`docs/SPEC-DRIVEN-DEVELOPMENT.md`](../../docs/SPEC-DRIVEN-DEVELOPMENT.md), [`docs/CONFIGURATION.md`](../../docs/CONFIGURATION.md), [`docs/USAGE.md`](../../docs/USAGE.md), [`docs/COVERAGE.md`](../../docs/COVERAGE.md)  
**Code inventory (traceability)**: [`code-inventory.md`](code-inventory.md)

---

## Summary

This library is a Composer drop-in for `openpay/sdk` **3.1.1** (`replace`). Integrators call `Openpay::configure()` / `getInstance()` / `OpenpaySession`, talk to Openpay over HTTPS, and **must** `reset()` between php-fpm / FrankenPHP worker requests so merchant keys never leak. HTTP is injectable (`OpenpayHttpTransport`; default `CurlHttpTransport`).

## User Scenarios & Testing

### US-001 — Drop-in Composer replacement (Priority: P1)

As a merchant PHP app, I require `nowo-tech/openpay-php` instead of `openpay/sdk`, so charges/customers keep working with `Openpay\\` classes.

**Acceptance Scenarios**:

1. **Given** Composer resolves `nowo-tech/openpay-php`, **When** I instantiate `Openpay\Data\Openpay`, **Then** the API surface of `openpay/sdk` 3.1.1 is available.
2. **Given** `openpay/sdk` is also requested, **When** Composer installs, **Then** this package `replace`s `openpay/sdk` 3.1.1.

### US-002 — Request-safe credentials (Priority: P1)

As a FrankenPHP / php-fpm worker, I call `Openpay::reset()` or `OpenpaySession`, so the next request does not inherit the previous merchant key.

**Acceptance Scenarios**:

1. **Given** `configure()` then `reset()`, **When** `getApiKey()` is called, **Then** it does not fall back to `OPENPAY_*` env.
2. **Given** `OpenpaySession::run()`, **When** the callback throws, **Then** `reset()` still runs.

### US-003 — Injectable HTTP (Priority: P2)

As a tester, I inject `OpenpayHttpTransport` so PHPUnit never hits the live Openpay API.

**Acceptance Scenarios**:

1. **Given** a fake transport, **When** `OpenpayApiConnector::request()` runs, **Then** no cURL call is made and JSON is decoded from the fake body.

## Functional requirements

### Autoload / facade

- **FR-SDK-001**: PSR-4 autoload `Openpay\\` → `Openpay/`; root `Openpay.php` bootstraps for manual installs.
- **FR-SDK-002**: `Openpay::configure()` / `reset()` / `configureFromEnvironment()` / `getInstance()` MUST isolate process statics.
- **FR-SDK-003**: `OpenpaySession` MUST always `reset()` after the callback (success or throw).
- **FR-SDK-004**: `OpenpayApi` is the merchant root with derived lists (customers, charges, …).

### HTTP

- **FR-HTTP-001**: Default transport is cURL with explicit connect/total timeouts.
- **FR-HTTP-002**: `Openpay::setHttpTransport()` MUST be honored until `reset()`.
- **FR-HTTP-003**: Connector validates merchant id, private key, public IP, and endpoint before sending.

### Errors

- **FR-ERR-001**: API errors MUST throw typed `OpenpayApi*` exceptions, not generic `\Exception` for HTTP failures.
- **FR-ERR-002**: Connection failures from cURL MUST throw `OpenpayApiConnectionError`.

### Resources

- **FR-RES-001**: Resource CRUD goes through `OpenpayApiResourceBase` (`_create` / `_retrieve` / `_find` / `_update` / `_delete`).
- **FR-RES-002**: Nested lists use `OpenpayApiDerivedResource` (`add` / `get` / `getList`). Cache keys MUST be cast to string before `strtolower()` (PHP 8 rejects `strtolower(int)`).
- **FR-RES-003**: Domain resources (Customer, Charge, Card, Plan, Subscription, Token, Webhook, Fee, Payout, Transfer, BankAccount, Capture, Refund, Pse, Bine) expose the upstream Openpay paths.

## Success criteria

- **SC-001**: PHPUnit unit + integration suites pass on PHP 8.3+.
- **SC-002**: Clover statements on `Openpay/` are **≥ 99%** (REQ-TEST-003). Mapped production units in the inventory equal **40** (`find Openpay -name '*.php'` = 39 + root `Openpay.php`).
- **SC-003**: PHPStan (scoped Nowo files) + PHP-CS-Fixer run in CI.
- **SC-004**: Default-branch `ci.yml` includes `git-hygiene` with `fetch-depth: 0`.

## Non-goals

- Not a Symfony bundle (no Flex recipe, no Twig, no admin UI).
- Not a live Openpay sandbox integration in CI (fake HTTP transport only).
- PHPStan is not required to analyse the entire legacy `Openpay/Resources` tree in this baseline.
- Rector (`make rector-dry`) is scoped to the same Nowo HTTP/session files plus `tests/` — not the upstream resource engine.

## Validation commands

```bash
make setup-hooks
make test-coverage
make check-no-cursor-coauthor
composer cs-check
composer phpstan
```

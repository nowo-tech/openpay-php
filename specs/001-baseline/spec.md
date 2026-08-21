# Feature Specification: Openpay PHP SDK baseline

**Feature Branch**: `001-baseline`
**Created**: 2026-08-21
**Status**: Active
**Input**: Backfill GitHub Spec Kit baseline documenting 100% of production code in `Openpay/` + root `Openpay.php`.

**Related docs**: [`docs/SPEC-DRIVEN-DEVELOPMENT.md`](../../docs/SPEC-DRIVEN-DEVELOPMENT.md), [`docs/CONFIGURATION.md`](../../docs/CONFIGURATION.md), [`docs/USAGE.md`](../../docs/USAGE.md)
**Code inventory (traceability)**: [`code-inventory.md`](code-inventory.md)

---

## User Scenarios & Testing

### User Story 1 — Drop-in Composer replacement (Priority: P1)

As a merchant PHP app, I require `nowo-tech/openpay-php` instead of `openpay/sdk`, so charges/customers keep working with `Openpay\\` classes.

**Acceptance Scenarios**:

1. **Given** Composer resolves `nowo-tech/openpay-php`, **When** I instantiate `Openpay\Data\Openpay`, **Then** the API surface of `openpay/sdk` 3.1.1 is available.
2. **Given** `openpay/sdk` is also requested, **When** Composer installs, **Then** this package `replace`s `openpay/sdk` 3.1.1.

### User Story 2 — Request-safe credentials (Priority: P1)

As a FrankenPHP / php-fpm worker, I call `Openpay::reset()` or `OpenpaySession`, so the next request does not inherit the previous merchant key.

**Acceptance Scenarios**:

1. **Given** `configure()` then `reset()`, **When** `getApiKey()` is called, **Then** it does not fall back to `OPENPAY_*` env.
2. **Given** `OpenpaySession::run()`, **When** the callback throws, **Then** `reset()` still runs.

### User Story 3 — Injectable HTTP (Priority: P2)

As a tester, I inject `OpenpayHttpTransport` so PHPUnit never hits the live Openpay API.

---

## Requirements

- **FR-SDK-001**: PSR-4 autoload `Openpay\\` → `Openpay/`; root `Openpay.php` bootstraps Composer autoload for manual installs.
- **FR-SDK-002**: `Openpay::configure()` / `reset()` / `configureFromEnvironment()` / `getInstance()` MUST isolate process statics.
- **FR-SDK-003**: `OpenpaySession` MUST always `reset()` after the callback (success or throw).
- **FR-HTTP-001**: Default transport is cURL with explicit connect/total timeouts.
- **FR-HTTP-002**: `Openpay::setHttpTransport()` MUST be honored until `reset()`.
- **FR-ERR-001**: API errors MUST throw typed `OpenpayApi*` exceptions, not generic `\Exception` for HTTP failures.

## Success Criteria

- PHPUnit unit + integration suites pass on PHP 8.3+.
- PHPStan + PHP-CS-Fixer run in CI.

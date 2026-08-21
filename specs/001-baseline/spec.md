# Feature Specification: Openpay PHP SDK baseline

**Feature Branch**: `001-baseline`  
**Created**: 2026-08-21  
**Updated**: 2026-08-21  
**Status**: Shipped (`3.2.1`, tag `v3.2.1`)  
**Input**: Product spec for the Nowo Openpay PHP SDK (`nowo-tech/openpay-php`), covering 100% of production units under `Openpay/` plus root `Openpay.php`.

**Related docs**: [`docs/SPEC-DRIVEN-DEVELOPMENT.md`](../../docs/SPEC-DRIVEN-DEVELOPMENT.md), [`docs/CONFIGURATION.md`](../../docs/CONFIGURATION.md), [`docs/USAGE.md`](../../docs/USAGE.md), [`docs/COVERAGE.md`](../../docs/COVERAGE.md), [`docs/UPGRADING.md`](../../docs/UPGRADING.md), [`docs/CHANGELOG.md`](../../docs/CHANGELOG.md)  
**Code inventory (traceability)**: [`code-inventory.md`](code-inventory.md)

---

## Summary

This library is a Composer drop-in for `openpay/sdk` **3.1.1** (`replace`). Integrators call `Openpay::configure()` / `getInstance($id, $apiKey, …)` / `OpenpaySession`, talk to Openpay over HTTPS, and **must** `reset()` between php-fpm / FrankenPHP worker requests so merchant keys never leak. HTTP is injectable (`OpenpayHttpTransport`; default `CurlHttpTransport`). Current release: **3.2.1**. GitHub repo is `nowo-tech/OpenpayPhp`; Packagist name stays `nowo-tech/openpay-php`.

## User Scenarios & Testing

### US-001 — Drop-in Composer replacement (Priority: P1)

As a merchant PHP app, I require `nowo-tech/openpay-php` instead of `openpay/sdk`, so charges/customers keep working with `Openpay\\` classes.

**Why this priority**: Without drop-in namespaces and `replace`, apps cannot adopt the fork.

**Independent Test**: Install the package and call `Openpay::getInstance($id, $apiKey, 'MX', $ip)` then `$openpay->charges` / `$openpay->customers`.

**Acceptance Scenarios**:

1. **Given** Composer resolves `nowo-tech/openpay-php`, **When** I use `Openpay\Data\Openpay`, **Then** the API surface of `openpay/sdk` 3.1.1 is available.
2. **Given** `openpay/sdk` is also requested, **When** Composer installs, **Then** this package `replace`s `openpay/sdk` 3.1.1.
3. **Given** a tagged release, **When** I read `Openpay::VERSION`, **Then** it matches the release (currently `3.2.1`) and the default User-Agent is `OpenpayPhp/{VERSION}`.

### US-002 — Request-safe credentials (Priority: P1)

As a FrankenPHP / php-fpm worker, I call `Openpay::reset()` or `OpenpaySession`, so the next request does not inherit the previous merchant key.

**Why this priority**: Process statics plus env fallback leaked keys across tenants on shared workers.

**Independent Test**: PHPUnit `OpenpayCredentialsTest` / `OpenpaySessionTest`.

**Acceptance Scenarios**:

1. **Given** `configure()` then `reset()`, **When** `getApiKey()` / `getId()` / `getSandboxMode()` are called, **Then** they do not fall back to `OPENPAY_*` env.
2. **Given** `OpenpaySession::run()`, **When** the callback throws, **Then** `reset()` still runs and the custom transport is cleared.
3. **Given** a fresh process that never called `reset()`, **When** statics are empty, **Then** `getApiKey()` / `getId()` still fall back to `OPENPAY_*` (upstream BC).
4. **Given** env-only apps after `reset()`, **When** they call `configureFromEnvironment()`, **Then** `OPENPAY_MERCHANT_ID`, `OPENPAY_API_KEY`, `OPENPAY_COUNTRY`, `OPENPAY_PUBLIC_IP`, and `OPENPAY_PRODUCTION_MODE` are loaded again.
5. **Given** `getInstance($id, $apiKey, …)`, **When** it returns, **Then** credentials are overwritten (no merge with a previous merchant) and `OpenpayApi::createRoot()` is the merchant root.

### US-003 — Injectable HTTP (Priority: P2)

As a tester, I inject `OpenpayHttpTransport` so PHPUnit never hits the live Openpay API.

**Why this priority**: CI must not use live merchant keys or the public Openpay network.

**Independent Test**: PHPUnit with `FakeOpenpayHttpTransport`.

**Acceptance Scenarios**:

1. **Given** a fake transport, **When** `OpenpayApiConnector::request()` runs, **Then** no cURL call is made and JSON is decoded from the fake body.
2. **Given** `reset()`, **When** the next request is sent without `setHttpTransport()`, **Then** the default is `CurlHttpTransport` again.

### US-004 — Safe HTTPS and logs (Priority: P1)

As a merchant, I send card and key material to Openpay without the SDK dumping PAN or private keys into debug logs, and without skipping TLS.

**Why this priority**: PCI residual; upstream debug logged HTTP bodies.

**Independent Test**: Connector/console unit tests; inspect `CurlHttpTransport` options.

**Acceptance Scenarios**:

1. **Given** console debug is enabled, **When** a request is sent, **Then** logs include method, URL, and status — not the JSON body.
2. **Given** the default transport, **When** cURL runs, **Then** `CURLOPT_SSL_VERIFYPEER` is true and TLS uses the OS trust store (no bundled `cacert.pem`).
3. **Given** connect/total timeouts, **When** the host is unreachable, **Then** `OpenpayApiConnectionError` is thrown (defaults 30s / 80s).

### US-005 — Release quality gate (Priority: P2)

As a maintainer, I run `make release-check` and GitHub Actions so a tag is not cut with red CI or Cursor co-author trailers.

**Why this priority**: REQ-CI-003 / REQ-GIT-001 / REQ-TEST-003 for the Nowo catalog.

**Independent Test**: `make release-check`; default-branch `ci.yml`.

**Acceptance Scenarios**:

1. **Given** `master` / `main`, **When** CI runs, **Then** PHPUnit runs on PHP 8.3, 8.4, and 8.5; PHPStan and CS run; `git-hygiene` uses `fetch-depth: 0`.
2. **Given** `make test-coverage`, **When** Lines coverage of `Openpay/` is below 99%, **Then** the coverage script fails.
3. **Given** a `v*` tag, **When** `release.yml` runs, **Then** GitHub Release notes come from `docs/CHANGELOG.md`.

### Edge Cases

- Empty merchant id / private key / public IP → `OpenpayApiAuthError` before HTTP.
- Merchant id not matching `/^[a-z0-9]{20}$/i` or key not matching `/^sk_[a-z0-9]{32}$/i` → `OpenpayApiAuthError`.
- Public IP failing `FILTER_VALIDATE_IP` (IPv4 or IPv6) → `OpenpayApiAuthError`.
- Country not MX / CO / PE (and not eglobal MX) → empty endpoint → `OpenpayApiConnectionError`.
- `getInstance()` without id and key is invalid (no zero-arg overload).
- After `reset()`, sandbox defaults to true and endpoint is empty until reconfigure.
- Nested resource cache ids that are integers must stringify before `strtolower()`.
- Invalid JSON from the API → `OpenpayApiRequestError`, not a silent `null`.
- `OpenpaySession` always `reset()` in `finally`, including when the callback throws.

## Requirements

### Autoload / facade

- **FR-SDK-001**: PSR-4 autoload `Openpay\\` → `Openpay/`; root `Openpay.php` bootstraps for manual installs (must not eager-require every class).
- **FR-SDK-002**: `Openpay::configure()` / `reset()` / `configureFromEnvironment()` / `getInstance($id, $apiKey, …)` MUST isolate process statics. `getInstance` MUST overwrite credentials (no merge with the previous merchant).
- **FR-SDK-003**: `OpenpaySession` MUST always `reset()` after the callback (success or throw) and MUST restore the default HTTP transport.
- **FR-SDK-004**: `OpenpayApi` is the merchant root with derived lists (customers, charges, cards, plans, tokens, webhooks, fees, payouts, transfers, PSE, bine).
- **FR-SDK-005**: After `reset()`, `OPENPAY_*` MUST NOT be read until `configure()`, `getInstance($id, $apiKey, …)`, or `configureFromEnvironment()`. A never-reset process MAY still fall back to env (upstream BC).
- **FR-SDK-006**: Env names are `OPENPAY_MERCHANT_ID`, `OPENPAY_API_KEY`, `OPENPAY_COUNTRY`, `OPENPAY_PUBLIC_IP`, `OPENPAY_PRODUCTION_MODE` (`FALSE` → sandbox).
- **FR-SDK-007**: `Openpay::VERSION` MUST match the released package; default User-Agent is `OpenpayPhp/{VERSION}`.
- **FR-SDK-008**: Endpoints: MX (`api.openpay.mx` / sandbox), CO, PE; MX + classification `eglobal` → BBVA ecommerce hosts.

### HTTP

- **FR-HTTP-001**: Default transport is cURL with explicit connect/total timeouts (defaults 30s / 80s).
- **FR-HTTP-002**: `Openpay::setHttpTransport()` MUST be honored until `reset()`.
- **FR-HTTP-003**: Connector validates merchant id, private key, public IP, and endpoint before sending. Auth is HTTP Basic with the private key. `X-Forwarded-For` carries the public IP.
- **FR-HTTP-004**: Default cURL MUST set `CURLOPT_SSL_VERIFYPEER` true and MUST use the OS CA store (no bundled `cacert.pem`, no CA-retry with an SDK PEM).
- **FR-HTTP-005**: Console debug MUST NOT print HTTP bodies or `__set` property values (method + URL + status only).
- **FR-HTTP-006**: Methods are GET / POST / PUT / DELETE; POST/PUT encode JSON with `JSON_THROW_ON_ERROR`.

### Errors

- **FR-ERR-001**: API errors MUST throw typed `OpenpayApi*` exceptions, not generic `\Exception` for HTTP failures (401/403 auth, 400/404/… request, 402/409/… transaction).
- **FR-ERR-002**: Connection failures from cURL MUST throw `OpenpayApiConnectionError`.

### Resources

- **FR-RES-001**: Resource CRUD goes through `OpenpayApiResourceBase` (`_create` / `_retrieve` / `_find` / `_update` / `_delete`).
- **FR-RES-002**: Nested lists use `OpenpayApiDerivedResource` (`add` / `get` / `getList`). Cache keys MUST be cast to string before `strtolower()` (PHP 8 rejects `strtolower(int)`).
- **FR-RES-003**: Domain resources (Customer, Charge, Card, Plan, Subscription, Token, Webhook, Fee, Payout, Transfer, BankAccount, Capture, Refund, Pse, Bine) expose the upstream Openpay paths.

### Quality / release

- **FR-QA-001**: PHPStan analyses only Nowo HTTP/session files (`OpenpaySession`, `OpenpayHttpTransport`, `CurlHttpTransport`). Rector dry-run uses the same surface plus `tests/`.
- **FR-QA-002**: PHPUnit covers unit + integration; Clover **Lines** on `Openpay/` MUST be ≥ 99%.
- **FR-QA-003**: CI PHP matrix is 8.3–8.5 (8.6 out until phpstan-frankenphp supports it). PHP constraint is `>=8.3`.
- **FR-REL-001**: GitHub slug is PascalCase `OpenpayPhp`; Packagist remains `nowo-tech/openpay-php`.
- **FR-REL-002**: New git tags are `vX.Y.Z` so `release.yml` publishes GitHub Release notes from `docs/CHANGELOG.md`. `replace` of `openpay/sdk` stays **3.1.1** unless the drop-in contract changes.

### Key Entities

- **Merchant session**: id, private key, country, public IP, sandbox/production, endpoint URL (process statics).
- **HTTP transport**: request method, URL, headers, body, Basic auth; response body + status.
- **API resource**: Openpay object (customer, charge, …) with nested lists and serialized fields.
- **API error**: HTTP status, Openpay `error_code`, category, request id, optional fraud rules.

## Success Criteria

- **SC-001**: PHPUnit unit + integration suites pass on PHP 8.3+.
- **SC-002**: Clover **Lines** on `Openpay/` are **≥ 99%** (REQ-TEST-003). Mapped production units in the inventory equal **40** (`find Openpay -name '*.php'` = 39 + root `Openpay.php`).
- **SC-003**: PHPStan (scoped Nowo files) + PHP-CS-Fixer + Rector dry-run (scoped) run in `make release-check` and CI.
- **SC-004**: Default-branch `ci.yml` includes `git-hygiene` with `fetch-depth: 0`.
- **SC-005**: After `reset()`, a second tenant cannot read the previous key from statics or from leftover `OPENPAY_*` fallback.
- **SC-006**: Default transport verifies TLS peers; debug logs do not contain request/response JSON bodies.

## Assumptions

- Hosts that need env-only credentials call `configureFromEnvironment()` after every `reset()`.
- Production images include OS CA certificates (Debian / FrankenPHP images already do).
- Live Openpay sandbox/production is out of CI; fake HTTP proves request shape.
- Integrators pass real `$id` and `$apiKey` to `getInstance` on every logical operation (no empty-string reuse of a previous merchant).
- `replace` of `openpay/sdk` 3.1.1 remains until Nowo chooses a new upstream pin.

## Non-goals

- Not a Symfony bundle (no Flex recipe, no Twig, no admin UI).
- Not a live Openpay sandbox integration in CI (fake HTTP transport only).
- PHPStan is not required to analyse the entire legacy `Openpay/Resources` tree in this baseline.
- Rector (`make rector-dry`) is scoped to the same Nowo HTTP/session files plus `tests/` — not the upstream resource engine.
- Not required to run a Beacon **server** or any other Nowo product domain.

## Validation commands

```bash
make setup-hooks
make release-check
make check-no-cursor-coauthor
```

Equivalent pieces: `composer cs-check`, `composer phpstan`, `composer rector-dry`, `composer test-coverage`.

# Spec-driven development

This repository follows Nowo **REQ-*** bundle standards (see the local
`BUNDLES_FULL_SPECS_DETAILS.md` in `developer.local.server/repositories/bundles`)
plus a GitHub Spec Kit baseline under `specs/001-baseline/`.

## Product behaviour

- Drop-in Composer replacement for `openpay/sdk` 3.1.1 (`Openpay\\` namespaces).
- Request-safe merchant credentials on php-fpm and FrankenPHP workers.
- Injectable HTTP transport with explicit timeouts.
- PHP 8.3+, PHPUnit, PHP-CS-Fixer, Rector, PHPStan (FrankenPHP rulesets).

## User stories

- **As** a merchant app **I want** `composer require nowo-tech/openpay-php` **so that**
  I keep the Openpay API without the official SDK leaking credentials across requests.
- **As** a FrankenPHP worker **I want** `OpenpaySession` / `reset()` **so that**
  the next tenant does not inherit the previous API key.
- **As** a contributor **I want** `make release-check` **so that** style, analysis,
  and tests run the same way as other Nowo packages.

## REQ-* traceability

| Area | Where |
| ---- | ----- |
| Docker | `Dockerfile`, `docker-compose.yml` (`name: openpay-php`) |
| Makefile | `ensure-up`, `release-check`, `setup-hooks`, `update-deps` |
| QA | `composer cs-check` / `phpstan` / `test` / `test-coverage` |
| Docs | `docs/*` linked from README (REQ-DOCS-002) |
| CI | `.github/workflows/ci.yml` |
| Git | `.githooks/commit-msg`, `.scripts/check-no-cursor-coauthor.sh` |

## Layers

1. **Constitution** — `.specify/memory/constitution.md`
2. **Baseline spec** — `specs/001-baseline/`
3. **Implementation** — `Openpay/`, `Openpay.php`, `tests/`

Validation: PHPUnit, PHPStan, PHP-CS-Fixer, Rector dry-run.

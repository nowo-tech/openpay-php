# Coverage

PHPUnit Clover is generated at the repo root as `coverage.xml` (`composer test-coverage` / `make test-coverage`).

## Include

`phpunit.xml.dist` `<source>` includes `Openpay/` (the Composer PSR-4 tree). Root `Openpay.php` is a manual-install autoload bootstrap and is not part of the Clover surface.

## Gate

`.scripts/php-coverage-percent.sh` fails the build when PHPUnit **Lines** coverage is below **99%** (REQ-TEST-003).

## Justified ignores

`OpenpayApiConnector`:

- missing-class guard (`class_exists('Openpay\\Data\\Openpay')` is always true in this package)
- `return []` after `handleRequestError()` which always throws

Marked with `@codeCoverageIgnore`. No silent `@codeCoverageIgnore` on resource classes.

# Release checklist

Use this checklist when cutting a new version.

> Current release: **3.2.0** (tag `3.2.0`, no `v` prefix — matches upstream).
> New tags should be `vX.Y.Z` so `.github/workflows/release.yml` runs.

## Before releasing

- [ ] `make release-check` (style, Rector dry-run, PHPStan, tests, coverage)
- [ ] Update [docs/CHANGELOG.md](CHANGELOG.md): move `[Unreleased]` to `[X.Y.Z] - YYYY-MM-DD`
- [ ] Update [docs/UPGRADING.md](UPGRADING.md) if the public API changed
- [ ] Bump `Openpay::VERSION` in `Openpay/Data/Openpay.php`

## Releasing

```bash
git tag -a v3.3.0 -m "Release v3.3.0"
git push origin master
git push origin v3.3.0
```

## After pushing

- [ ] Confirm GitHub Release from `release.yml`
- [ ] Packagist picks up the tag (`nowo-tech/openpay-php`)
- [ ] Keep `replace` of `openpay/sdk` at **3.1.1** unless the drop-in contract changed

## Security checklist (REQ-SEC-002)

- [ ] No secrets, merchant keys, or PAN/CVV in git, docs, or tests
- [ ] HTTP bodies with card data are not logged
- [ ] `composer audit` reviewed

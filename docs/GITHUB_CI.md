# GitHub CI

Workflows:

| File | Purpose |
| ---- | ------- |
| `.github/workflows/ci.yml` | PHPUnit (PHP 8.3–8.6), code style, PHPStan, coverage |
| `.github/workflows/release.yml` | GitHub Release on `v*` tags |
| `.github/workflows/sync-releases.yml` | Backfill missing `v*` releases |

Default branch is **`master`**.

## REQ-GIT-001

`.scripts/check-no-cursor-coauthor.sh` is the same checker as other Nowo packages.
Install hooks with `make setup-hooks`. `make release-check` runs the checker locally.

This fork still has historical Cursor co-author trailers from the 3.2.0
modernization PRs. Do **not** force-push `master` until a dedicated
`make strip-cursor-coauthor-from-history` pass is approved. The CI workflow
does not fail the default branch on that history until the rewrite lands.

# Spec Kit

GitHub Spec Kit layout for this library:

- `.specify/` — constitution and templates
- `specs/001-baseline/` — current product spec and `Openpay/` inventory

Workflow:

1. Change behaviour → update `specs/001-baseline/spec.md` (or a new `specs/NNN-*`).
2. Implement in `Openpay/` / tests.
3. Run `make release-check`.
4. Document in `docs/CHANGELOG.md` and README if the public API changed.

See [SPEC-DRIVEN-DEVELOPMENT.md](SPEC-DRIVEN-DEVELOPMENT.md).

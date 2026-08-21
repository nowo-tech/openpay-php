# Specification Quality Checklist: Openpay PHP SDK baseline

**Purpose**: Validate `specs/001-baseline/spec.md` against the shipped 3.2.1 library.  
**Created**: 2026-08-21  
**Feature**: [spec.md](../spec.md)

## Content Quality

- [x] Product behaviour is described (drop-in SDK, request isolation, HTTP, release gate)
- [x] Mandatory sections completed (scenarios, FRs, success criteria, assumptions, non-goals)
- [x] Written for maintainers of this library (not a Symfony app)

## Requirement Completeness

- [x] No [NEEDS CLARIFICATION] markers remain
- [x] Requirements are testable (PHPUnit, `make release-check`, CI)
- [x] Success criteria are measurable (coverage %, PHP matrix, TLS/logs)
- [x] Acceptance scenarios cover P1 flows
- [x] Edge cases identified (auth validation, empty endpoint, integer cache ids, JSON errors)
- [x] Scope bounded (non-goals: not a bundle, no live Openpay CI)
- [x] Assumptions identified (OS CA store, env reload after reset)

## Feature Readiness (shipped 3.2.1)

- [x] US-001…005 match implemented code
- [x] Inventory lists 40 production units
- [x] CI `git-hygiene` + PHP 8.3–8.5 documented
- [x] Rector/PHPStan scope documented

## Notes

Baseline is **shipped**, not a draft. New behaviour belongs in `specs/002-*` (or an update to this file in the same PR).

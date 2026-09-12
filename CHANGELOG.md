# Changelog

All notable changes to `workflow-engine-laravel` will be documented in this file.

## v1.0.0 - 2026-09-12

First stable release.

- Laravel 13 support (`illuminate/*` now `^10|^11|^12|^13`).
- Depends on `solution-forest/workflow-engine-core: ^1.0` instead of `dev-main || ^0.0.3-alpha`. A stable release cannot require an alpha, and `dev-main` in a release means consumers get whatever `main` happens to be that day.
- `minimum-stability` is now `stable`.

**The test suite had never run against core 0.0.4+**, and three things had drifted:

- Auto-generated step ids became 1-based in core, so assertions on `email_0` / `http_0` / `delay_0` were off by one. Two tests also asserted the same key twice while claiming to check three different steps, which hid it.
- `WorkflowContext` is immutable now and `setData()` is gone; a fixture still mutated the context it was handed.
- One test made a real HTTP request to `api.example.com` and a real five-minute delay — it spent 300 seconds sleeping, then failed on DNS, and never reached the payment assertions that were also broken.

**Suite time: 304s → 4.6s**, 74 tests passing.

> Note: `workflow-engine-core` v1.0.0 is not yet on Packagist — the repository has no Packagist webhook configured. Until it is published, consumers need a VCS repository entry for the core package.

## v0.0.6-alpha - 2026-02-19

**Full Changelog**: https://github.com/solutionforest/workflow-engine-laravel/compare/v0.0.5-alpha...v0.0.6-alpha

## v0.0.5-alpha - 2026-02-19

**Full Changelog**: https://github.com/solutionforest/workflow-engine-laravel/compare/v0.0.4-alpha...v0.0.5-alpha

## v0.0.4-alpha - 2026-02-19

**Full Changelog**: https://github.com/solutionforest/workflow-engine-laravel/compare/v0.0.3-alpha...v0.0.4-alpha

## v0.0.3-alpha - 2026-02-19

### What's Changed

* Bump stefanzweifel/git-auto-commit-action from 5 to 6 by @dependabot[bot] in https://github.com/solutionforest/workflow-engine-laravel/pull/4
* Bump aglipanci/laravel-pint-action from 2.5 to 2.6 by @dependabot[bot] in https://github.com/solutionforest/workflow-engine-laravel/pull/5
* Bump actions/checkout from 4 to 5 by @dependabot[bot] in https://github.com/solutionforest/workflow-engine-laravel/pull/6
* Bump actions/checkout from 5 to 6 by @dependabot[bot] in https://github.com/solutionforest/workflow-engine-laravel/pull/8
* Bump stefanzweifel/git-auto-commit-action from 6 to 7 by @dependabot[bot] in https://github.com/solutionforest/workflow-engine-laravel/pull/7
* Bump dependabot/fetch-metadata from 2.4.0 to 2.5.0 by @dependabot[bot] in https://github.com/solutionforest/workflow-engine-laravel/pull/9
* Update workflow-engine-core dependency to dev-main by @lam0819 in https://github.com/solutionforest/workflow-engine-laravel/pull/10

**Full Changelog**: https://github.com/solutionforest/workflow-engine-laravel/compare/v0.0.2-alpha...v0.0.3-alpha

## v0.0.2-alpha - 2025-05-29

**Full Changelog**: https://github.com/solutionforest/workflow-engine-laravel/compare/v0.0.1-alpha...v0.0.2-alpha

## v0.0.1-alpha - 2025-05-29

### What's Changed

* Bump dependabot/fetch-metadata from 2.3.0 to 2.4.0 by @dependabot in https://github.com/solutionforest/workflow-engine-laravel/pull/1
* Feature/core by @lam0819 in https://github.com/solutionforest/workflow-engine-laravel/pull/3

### New Contributors

* @dependabot made their first contribution in https://github.com/solutionforest/workflow-engine-laravel/pull/1
* @lam0819 made their first contribution in https://github.com/solutionforest/workflow-engine-laravel/pull/3

**Full Changelog**: https://github.com/solutionforest/workflow-engine-laravel/commits/v0.0.1-alpha

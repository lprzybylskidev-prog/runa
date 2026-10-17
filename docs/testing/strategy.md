# Testing Strategy

## Principles

Tests protect meaningful behavior and regressions. Runa does not pursue arbitrary coverage percentages.

Before writing tests, identify the scenarios that matter:
- normal success,
- validation,
- authorization/authentication when relevant,
- edge cases,
- conflict/concurrency behavior,
- errors and dependency failures,
- regression cases for real bugs.

Coverage is informational, not a pass/fail target by itself.

## Backend test framework

Prefer Pest if the installed Laravel/PHP/toolchain supports it cleanly at RM-2026-001 implementation time.

Fallback: PHPUnit.

Feature tests are the default for application behavior that crosses Laravel boundaries.

Unit tests are for isolated logic where a smaller test is clearer and valuable.

Do not over-mock Laravel/application behavior merely to make a test "unit."

## Test database and isolation

Initial test database:

`runa_test`

Tests may use the same PostgreSQL container as development but must use:
- a separate test database,
- testing environment configuration,
- isolated storage,
- isolated cache/queue namespaces.

Before destructive test setup, hard guards verify:
- `APP_ENV=testing`,
- the selected database is clearly a test database such as `_test`,
- expected test connection/host values.

Fail hard if the guard is not satisfied.

Never run destructive tests against the development or production database.

## Parallel execution

Tests should be parallel-safe from the start:
- no test order dependency,
- no shared dirty global state,
- deterministic setup/cleanup.

Canonical command:

`./bin/test --parallel`

If parallel execution reveals a concurrency/isolation defect:
1. create a minimal reproduction,
2. fix the isolation/concurrency problem,
3. rerun focused tests,
4. rerun the full required suite only after the defect is understood.

Do not repeatedly run the entire suite while guessing.

A separate complete test stack is not required initially unless evidence demonstrates that isolation in the shared PostgreSQL service is insufficient.

## Time and randomness

Use controllable time when logic depends on the current time.

Use controllable randomness where random values affect behavior.

Development seed randomness is defined separately in `docs/data/seeding.md`.

## Frontend unit/component tests

Use:
- Vitest,
- Vue Test Utils.

Add Testing Library only if it adds concrete value and receives dependency approval under project policy.

Tests should focus on behavior, state transitions, emitted contracts, rendering of meaningful conditions, and regressions.

Do not assert internal implementation details merely to increase test count.

## Playwright

Install Playwright from day one as part of the accepted frontend/testing toolchain.

Browsers:
- Chromium: primary automation target,
- Firefox: installed and used where useful,
- WebKit: add later only if a real compatibility need appears.

### Durable E2E tests

Keep durable E2E tests focused on:
- critical user flows,
- important regressions,
- application-wide infrastructure behavior.

Do not convert every UI interaction into a permanent browser test.

### Deterministic data

Playwright uses deterministic factories/seeders/fixtures.

Never depend on manually prepared developer database state.

Canonical demo data may use stable known public IDs/accounts where useful.

### Frontend change diagnostics

Every meaningful frontend change should be visually inspected, with proportionate Playwright/manual support.

Representative sizes:
- desktop: approximately 1440x900,
- tablet: approximately 1024x768,
- mobile: approximately 390x844.

Check where relevant:
- light and dark,
- Polish and English smoke behavior,
- console errors/warnings,
- failed network requests,
- 404/500 responses,
- rendering/layout,
- forms/validation.

The final gate normally includes:
- Chromium critical desktop flows,
- selected mobile flows,
- light/dark where relevant,
- PL/EN smoke for user UI,
- Firefox where useful.

## Scratch browser work

Temporary Playwright exploration belongs in visible project scratch locations rather than hidden local junk.

Generated browser artifacts such as:
- screenshots,
- videos,
- traces,

belong under a gitignored `artifacts/playwright/`.

Scratch test/code files must be removed before roadmap closure or promoted to durable tests/tools.

## Security regressions

When fixing a reproducible security defect such as XSS, access-control leakage, IDOR, unsafe validation, or related behavior, add a regression test when the scenario can be meaningfully automated.

## Test failures

Do not weaken tests merely to make a gate green.

Investigate whether:
- behavior changed intentionally,
- test expectation is stale,
- code is defective,
- test isolation is defective.

Behavior changes require agreed specification/roadmap updates.

## Worker and browser isolation

Parallel workers isolate database state (including per-worker test databases where required), storage paths, cache keys, queue state, files and ports. Guards must accept only the deliberately provisioned test targets, including worker-specific names, rather than trusting a substring alone.

Browser tests use a separately configured testing application endpoint through Nginx/PHP-FPM with testing env/state. They must never reset the developer application database or use `artisan serve`. Avoid concurrent suites mutating the same test endpoint/state.

Use `.playwright/scratch/` for temporary browser scripts and `tools/scratch/` for other diagnostic scripts. Neither source directory is gitignored. Keep scripts until no longer needed within the current work, then remove them or promote them to durable tests before closure; generated artifacts remain ignored.

During ordinary roadmap points, run only proportionate focused checks or visual diagnostics needed for the change. Full suites, static analysis and scanner pipelines belong to final verification. Test code and safe parallel design are still implemented with the feature.

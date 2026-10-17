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

Pest 5.3.2 with pest-plugin-laravel 5.0.1 is installed on PHP 8.4 / Laravel 13. PHPUnit 13.4.2 and ParaTest 7.27.2 are resolved testing dependencies in `composer.lock`.

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

## Implemented PostgreSQL safety foundation

`TestDatabaseGuard` requires environment `testing`, default connection/driver `pgsql`, host `postgres`, role `runa_test` and database `runa_test`, `runa_test_<positive integer>` or Laravel's `runa_test_test_<positive integer>`. It also queries the actual database name and session role before allowing a destructive operation. The supported host is declared in project config rather than inferred from the same connection being checked.

Guarded subclasses of Laravel's `db:wipe`, `migrate:fresh`, `migrate:refresh`, `migrate:reset` and `migrate:rollback` validate before execution, including an explicit `--database` option. Console-event listeners are insufficient: the installed framework intentionally skips forwarding those events during testing. The command subclasses cover both direct CLI use and nested framework calls. Normal safe `migrate` remains available in development; these destructive commands are intentionally restricted to isolated test databases. Production rollback does not automatically reverse database migrations.

Run inside the devcontainer:

```sh
python3 tools/testing/database-guards.py
APP_ENV=testing php artisan runa:test-db:guard
```

The regression script deliberately resets only the guarded test database after verifying unsafe environment/host/role/name/connection rejection. It supplements the Pest/parallel suite. A separate test-role-owned parallel database was provisioned, migrated and removed successfully during initial verification.

## Local commands

Run these from the host or development container:

| Command | Purpose |
|---|---|
| `./bin/format` / `./bin/format --check` | Pint for project PHP; Prettier for frontend, frontend tests and root tool configs |
| `./bin/lint` | ESLint recommended JS/TS/Vue correctness rules; zero warnings |
| `./bin/typecheck` | Strict Vue/TypeScript application and test checking |
| `./bin/analyse` | Larastan/PHPStan `max`, no baseline, one analysis process with a 1 GiB PHP limit |
| `./bin/test` | Backend feature tests, serial |
| `./bin/test --parallel` | Backend feature tests, `TEST_WORKERS` from the resource profile |
| `./bin/test frontend` | Vitest + Vue Test Utils in headless Chromium |
| `./bin/test e2e --critical` | Current critical Playwright suite in Chromium, Firefox and mobile Chromium |

Runner options can follow the test selector, for example `./bin/test --filter=Health` or `./bin/test e2e --project=firefox`. `--critical` currently selects the entire small critical suite. Each wrapper provides English `--help`. Host wrappers execute the same tools in `app`; they do not use host PHP/Node. `bin/check` is their shared dispatcher. Run `./bin/dev prepare` and start the development environment first.

Pint excludes the vendored `lang/` snapshot and generated/runtime trees. Prettier also excludes localization data and lockfiles. Formatting establishes consistent source layout without changing the catalog's integrity hashes. ESLint owns correctness, Prettier owns layout; the core assignment rule is disabled only for Vue SFCs because template reads are invisible to that core rule. Other recommended rules remain active. PHPStan uses one analysis process to bound memory and avoid the native parallel-worker crash observed during toolchain verification.

The Vitest Playwright provider is a pinned integration adapter of the approved test stack. Components run against a real DOM; no DOM emulator is installed. Browser binaries and OS libraries for exactly Playwright 1.64.1 Chromium/Firefox are provisioned in the development Docker stage. Production dependency/frontend stages skip that browser layer, and production runtime images contain no testing tools.

## Backend execution details

`Tests\TestCase` bootstraps `.env.testing` and verifies the base database configuration and live identity before any worker database DDL. Pest/ParaTest supplies a `TEST_TOKEN`; only canonical integers from 0 to 99999 are accepted, and positive tokens become `runa_test_test_N`. Missing worker databases are created by the dedicated test role, then the selected live identity is guarded again before safe migrations. Worker databases persist for reuse; tests using database state must use transactions or an explicitly guarded reset. The queue-table smoke test demonstrates transaction rollback using existing technical tables, without adding business fixtures.

Each worker receives `.runtime/tests/worker-N/` storage, an array cache with a worker prefix, synchronous queues and an array mailer. Test applications rebuild their configuration between cases. HTTP tests cover health, JSON errors and request IDs; database tests cover schema placement and unsafe configuration/live-identity rejection; localization tests cover native Polish validation. Cached application configuration is rejected by the test wrapper so development config cannot silently override `.env.testing`.

The test wrapper takes a nonblocking checkout-wide lock across backend, frontend and E2E runs. A competing suite fails clearly instead of reusing mutable worker state. Use the wrapper for normal runs; direct runner commands are diagnostic bypasses of that concurrency protection. No extra HTTP ports are needed for backend tests.

## Isolated browser endpoint

The optional Compose `testing` profile starts `app-test` and `nginx-test`, using `.env.testing`, the dedicated PostgreSQL test role/database, native `LARAVEL_STORAGE_PATH=.runtime/tests/e2e`, synchronous queue and array mail. The PHP-FPM startup guard verifies its actual test database before serving. Nginx is reachable only on the internal Compose network at `http://nginx-test:8080`; no additional host port is published. Development remains at its own endpoint and database.

From the host, `./bin/test e2e --critical` starts the testing profile and waits for readiness. From the devcontainer, provision those siblings once from a host terminal before running the identical wrapper:

```sh
docker compose --env-file .env.dev.local -f compose.yaml -f .devcontainer/compose.resources.yaml --profile testing up -d --wait nginx-test
```

This explicit host lifecycle keeps Docker sockets out of the devcontainer. E2E uses one worker because all browser projects share one testing endpoint. Each test gets a fresh browser context. Critical coverage checks health, responsive overflow, PL/EN preferences, dark-theme persistence, API failure/retry and unexpected console/page errors. Failure traces live in ignored `artifacts/playwright/results/`. `tools/testing/frontend.mjs` remains the broader visual diagnostic and also targets the isolated testing endpoint.

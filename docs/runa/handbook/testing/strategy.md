# Application testing

Tests protect behavior and meaningful regressions. Use feature tests for Laravel/application boundaries and unit tests for isolated logic. Cover success, known validation, authorization, conflicts, cancellation and relevant failure paths; avoid arbitrary coverage goals and tests that mirror implementation.

## Central test layout

All authored suites and their private fixtures live below repository-root `tests`
(`/workspace/tests` in the development container):

```text
tests/
  backend/{app,runa}/       # PHP Feature, Unit and modules/<code>
  frontend/{app,runa}/      # unit, e2e and private acceptance fixtures
  tools/{app,runa}/         # Python and tooling acceptance
```

Application developers own the `app` directories. Runa's private `runa` directories
are excluded from customer acquisition. Reusable development infrastructure stays
in `src/runa/Testing` and `tools/application/testing`; it is not a private suite.
The public `Tests\TestCase` and `@runa/testing/{vue,vitest,playwright}` entrypoints
retain their identities. Internal PHP runner adapters live in `Testing/Backend`,
the test-database guard in `Testing/Database`, and the fixed worker-cleanup child
in `src/runa/testing/backend`. These implementation paths are not application
extension APIs. Private Runa browser configurations live under owner tooling
and resolve the same installed src packages; customers own src/vitest.config.mjs
and src/playwright.config.mjs.
Runtime images omit the entire test tree and development dependencies. Laravel
and frontend runner configuration stays beside its source/dependency manifests in
`src`; PHPUnit's private owner configuration/bootstrap lives with its backend suite.
Commands retain the `tooling` scope name; `tools` is the directory name.

Existing customer test files are preserved during updates. Reconcile their paths,
autoload and runner configuration explicitly; updates do not move authored tests
or replace customer test configuration silently. A default scope that detects old
cases instead of central cases reports blocked reconciliation, rather than
claiming a successful empty suite. Legacy explicitly configured
Python roots under `src/app` remain readable; new suites use the central roots.

## Default and focused commands

`php artisan runa:test` (host `./bin/artisan runa:test`, recovery `./bin/test`)
runs application backend, frontend and tooling tests in that order. The default
includes Python tooling tests; it replaces the former backend-only default.
Browser/e2e remains an explicit selection. Select repeated `--scope=backend`,
`--scope=frontend`, `--scope=tooling` or `--scope=e2e` to run a subset. The focused
commands `runa:test:backend`, `runa:test:frontend`, `runa:test:python` and
`runa:test:e2e` use the same lifecycle. Existing recovery positional spellings
`./bin/test backend|frontend|tooling|e2e` remain supported.

`--filter=TEXT` becomes a Pest filter, Vitest name pattern, pytest `-k` expression
or Playwright grep in each selected scope. Native runner options/paths require
one explicit scope: for example `runa:test --scope=backend --parallel` or
`runa:test --scope=frontend path/to/example.test.ts`. A filter with no collected
matches is a failure; use a focused scope when names belong to one language.
All independently runnable scopes run after another scope fails. Missing tools,
collection errors and interrupted/unstarted work remain visible failures. A
customer scope containing no authored tests is explicitly `empty`, never evidence
of platform verification. A missing owner suite is a failure.

The shared service holds `.runtime/tests/suite.lock` once for the selected run,
reaps child processes before backend cleanup and releases the lock after reporting.
Native `php artisan test` retains its own PHP lifecycle. The two commands share
the lock, without invoking each other's locked wrapper. Latest native scope/cleanup
codes and statuses are written to `.runtime/tests/application-results.json`;
focused commands return the native failure, while a multi-scope run returns 1 on
any failure and 130 on interruption. No scope is silently retried. Python JUnit
output is `.runtime/python/reports/application-pytest.xml`; frontend/browser
reports retain their established paths. Setup/reopen never executes these suites.

## PHP

Native `php artisan test` and `php artisan runa:test:backend` select src/phpunit.xml and tests/backend/app/Feature and Unit. Pest and PHPUnit are available. Standard make:test writes tests/backend/app/Feature or Unit; Tests\TestCase in src/runa/Testing/TestCase.php supplies reusable guarded application isolation. The Pest contribution lives in tests/backend/app/Pest.php. Native Artisan uses the development-only Runa adapter to select that root, and the application wrapper passes the same explicit root/configuration. Direct Pest execution from src uses `vendor/bin/pest --test-directory=../tests/backend/app`; bare Pest retains its upstream tests-directory default. The obsolete src/tests directory is absent. The common testing namespace is dev-only Composer autoload, excluded from production images. Customer test setup never invokes owner migration tools or discovers platform tests; fresh parallel worker databases receive forward-only technical preparation from the delivered primitive before application migrations.

Development installation and `runa:dev:initialize` explicitly prepare platform data in both the development database and the isolated base `runa_test` database through the forward-only platform preparation primitive. Application test execution then applies only application-owned migrations. After platform updates, refresh the development clone through initialization before running native application tests.

Run `php artisan runa:test --scope=backend --parallel`; TEST_WORKERS defaults to the selected resource profile. Each worker validates APP_ENV=testing, PostgreSQL test host/role/database and actual connection identity before any destructive DDL; only explicit isolated test database names are accepted. Worker database/storage/cache namespaces are isolated. The guarded worker database is selected before provider boot, so registry/maintenance and other singleton services retain the same connection as test transactions; never switch/purge it after booted services have captured it. Application migrations run only for application-owned objects and preserve Runa data/history. Use transactions or guarded resets for database mutation and deterministic fixtures. Never reset development/production data for tests. The test base uses synchronous queues and array mail.

## Frontend

`php artisan runa:test:frontend` uses src/vitest.config.mjs and tests/frontend/app/unit. Vitest/Vue Test Utils run in real headless Chromium. Test rendered states, events, validation, focus/accessibility and asynchronous failure/race behavior. The example welcome test consumes the public UI facade. Keep TypeScript strict; no known-payload any.

## Browser endpoint and startup

Build with `pnpm --dir src run build` inside the development container. From the native Docker host, `./bin/artisan runa:test:e2e` starts the testing siblings for that run and removes only containers it creates, including on failure. Use this host entrypoint when automatic endpoint ownership is wanted.

For a deliberately persistent manual endpoint, start it on the host:

```sh
docker compose --env-file .env.dev.local -f compose.yaml -f .devcontainer/compose.resources.yaml --profile testing up -d --wait nginx-test
```

Then run `php artisan runa:test:e2e` inside the development container. Existing manually provisioned endpoints are preserved by automatic host runs. Their operator owns their eventual scoped teardown. src/playwright.config.mjs selects only tests/frontend/app/e2e and targets real Nginx/PHP-FPM at http://nginx-test:8080, never artisan serve or the owner fixture harness. RUNA_E2E_BASE_URL may select another provisioned HTTP(S) application test endpoint. No data reset is performed by the browser runner.

Chromium, Firefox and mobile Chromium use one worker and fresh contexts. The retained --critical spelling runs the application suite; use normal Playwright filters for specific cases. HTML report, screenshots and failure traces live under .runtime/artifacts/playwright/application. Browser binaries are supplied by the development image; production contains no browser/test tooling.

## Test resource lifecycle

The base `runa_test` database is persistent and retains prepared platform data. Parallel worker databases `runa_test_test_N` (canonical tokens 1–99999) are disposable. Supported backend runners clean inactive workers before execution and after success, failure or a handled interruption, while holding `.runtime/tests/suite.lock`. The cleanup child loads the testing environment and validates the configured and actual PostgreSQL host, role and base database through TestDatabaseGuard. It inspects only canonical worker names, prints the exact scope, refuses the whole cleanup if any candidate has another owner or active connections, and uses ordinary DROP DATABASE without force or session termination. Development/production/base databases, foreign owners and unexpected names are preserved. Cleanup failure makes an otherwise successful run fail; it never converts a test failure into success.

The shared TestCase selects a worker before provider boot and disables Laravel's additional database suffixing. Do not add a second naming/DDL owner or rely on Laravel's drop-databases option for these custom workers. Native application tests and owner backend tests use the same cleanup contract. A hard kill, machine restart or lost Docker client can prevent immediate teardown; the next supported backend run recovers inactive orphan workers. Active orphan processes must finish or be investigated before cleanup can proceed. Reopen/setup never performs this cleanup.

Automatic host browser startup serializes endpoint ownership with `.runtime/tests/endpoint.lock`. Before startup it records a unique `runa.testing.run` label in `.runtime/tests/endpoint.json` and a local Compose override, applying the label only to missing app-test/nginx-test services. Existing containers are not recreated or adopted. Teardown and next-run recovery inspect exact container IDs, project, checkout directory, service and run label before stopping/removing only owned containers. Failed/interrupted cleanup retains its journal; a still-running suite prevents recovery. Shared development networks, every volume, model/Qdrant caches, backups and browser evidence remain untouched. Manually provisioned endpoints are outside automatic ownership.

## Concurrency and acceptance

The runa:test* wrappers and native php artisan test share a nonblocking checkout suite lock and forward child failure codes. Native sequential/PHPUnit testing composes Collision and restores its scoped raw-argv
adaptation on every exit. Native Pest parallel testing invokes the same installed
Pest engine with the application test root, preserving Laravel parallel flags,
terminal behavior and native exits through the shared lock/cleanup lifecycle.
Use `php artisan test --parallel --processes=2` to select two PHP workers.
Cleanup children discard keys inherited from the development environment before
loading `.env.testing`; environment parser diagnostics never expose file values.
Read-only native discovery (list-tests, list-tests-xml, list-suites and list-groups) does not acquire the lock or clean databases. Direct Pest/PHPUnit runners bypass lifecycle protection; use supported runners for execution. Cached application config must be cleared before backend test execution so .env.testing remains effective.

Visually inspect significant frontend changes at desktop/tablet/mobile sizes, light/dark and PL/EN as relevant. Check keyboard/focus semantics, meaningful labels, console/network errors, long content and responsive overflow. Keep durable browser tests focused on critical application flows and reproducible regressions. Temporary exploration lives under .playwright/scratch or tools/scratch; promote useful coverage or remove scratch before closure, preserving ignored artifacts.

Do not weaken tests to obtain PASS. Investigate behavior versus test/isolation defects. Implementation points produce code, tests and documentation without routine check runs; only small, fast, focused checks for a concrete uncertainty belong there. Full suites, broad integration/installation acceptance, static analysis, image builds/scanner pipelines and full quality/security/AI and production checks run after all implementation in final verification, followed by fixes and repeated final verification. Mandatory commit hooks remain in force. If any supplied roadmap requires broad verification earlier, stop and notify the user regardless of its author; follow the [roadmap workflow](../workflows/roadmaps.md#roadmap-points) before proceeding. See [quality](../quality/README.md) and [security](../security/baseline.md).

## Python tooling and public test support

Place application Python tests in `tests/tools/app/test_*.py`; put reusable
application tooling in `src/app/tooling` or an application module. Import it with
qualified workspace package names. For additional module/test roots, author
`src/app/testing.json`, for example:

```json
{
  "schema_version": 2,
  "python_roots": [".", "modules/example"]
}
```

Schema 2 roots are explicit paths relative to `tests/tools/app`; escapes, duplicates
and symlink redirection outside application ownership are refused. Schema 1 retains
its existing `src/app`-relative meaning for configured customer projects. Absent configuration retains
the default root. Configuration registers source paths, never executable commands.
`runa:test:python --path=tests/tools/app/test_example.py::test_case` selects a
file/node within those roots; `--collect-only` inspects tooling discovery.
Shared pytest configuration enforces importlib discovery and strict registered
markers. Docker/network/knowledge/database/packaging scenarios require explicit
marker selection and provisioned prerequisites; no unit helper starts a stack.

Delivered `tools.application.testing.support.process.RecordingRunner` records
literal `ProcessSpec` inputs and consumes scripted results/errors.
`support.workspaces` provides opt-in temporary workspace/Git factories;
`support.fakes` supplies a manual clock, recording HTTP boundary and Compose/image
JSON responses. Compose these with application services using explicit contexts.
They import only stdlib/core support and never import private test cases. Actual
Git repositories are created only when the repository factory is called.

PHP tests extend the delivered `Tests\TestCase`; module authors can use the
public ModuleContractSuite from the module development guide. Browser tests may
import `recordBrowserProblems` from `src/runa/Testing/browserProblems.ts` (for an
application e2e test: `../../../runa/Testing/browserProblems`). It records browser
errors and optionally the documented anonymous-session probe exception, without
starting a browser. These helpers are development-only. Application tests,
fixtures, discovery configuration and tooling remain customer-owned and are
reconciled through reviewed updates; protected Runa helpers are consumed directly.

Frontend tests import the existing public Vue facade as before. Import mount and
Vue Test Utils helpers from `@runa/testing/vue`, and Vitest functions from `@runa/testing/vitest` so their dependencies resolve from
the shared development source context. E2E tests can
import `test`, `expect` and Playwright types from `@runa/testing/playwright`; the
shared tsconfig/configuration resolves the installed container dependencies in
`src/node_modules`. Vitest configurations use those same Vue/Vitest/test-utils
instances for central files, without a second dependency install or import-path
injection.

Use the same shared facades for dynamic `import()` calls. Resolve relative lazy
imports from the central test file's actual directory, including imports inside
unselected tests: Vitest's dependency scanner visits every configured suite entry
before applying file/name selections. An unresolved import can abort initial
optimization and reload the browser with mismatched Vitest runtime state. A warm
cache can hide this failure; dependency-resolution diagnostics must also cover a
cold optimizer cache without deleting retained reports or other tooling state.

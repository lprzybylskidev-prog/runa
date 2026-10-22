# Application testing

Tests protect behavior and meaningful regressions. Use feature tests for Laravel/application boundaries and unit tests for isolated logic. Cover success, known validation, authorization, conflicts, cancellation and relevant failure paths; avoid arbitrary coverage goals and tests that mirror implementation.

## PHP

Native `php artisan test` and `php artisan runa:test` select src/phpunit.xml and src/app/tests/Feature and Unit. Pest and PHPUnit are available. Standard make:test writes application tests; Tests\TestCase in src/tests/TestCase.php supplies reusable guarded application isolation. The Pest contribution lives in src/app/tests/Pest.php. Customer test setup never invokes platform migration tools or discovers platform tests.

Development installation and `runa:dev:initialize` explicitly prepare platform data in both the development database and the isolated base `runa_test` database through the forward-only platform preparation primitive. Application test execution then applies only application-owned migrations. After platform updates, refresh the development clone through initialization before running native application tests.

Run `php artisan runa:test --parallel`; TEST_WORKERS defaults to the selected resource profile. Each worker validates APP_ENV=testing, PostgreSQL test host/role/database and actual connection identity before any destructive DDL; only explicit isolated test database names are accepted. Worker database/storage/cache namespaces are isolated. Application migrations run only for application-owned objects and preserve Runa data/history. Use transactions or guarded resets for database mutation and deterministic fixtures. Never reset development/production data for tests. The test base uses synchronous queues and array mail.

## Frontend

`php artisan runa:test:frontend` uses src/vitest.config.mjs and src/app/tests/frontend. Vitest/Vue Test Utils run in real headless Chromium. Test rendered states, events, validation, focus/accessibility and asynchronous failure/race behavior. The example welcome test consumes the public UI facade. Keep TypeScript strict; no known-payload any.

## Browser endpoint and startup

Build with `pnpm --dir src run build` inside the development container. Start the isolated testing siblings once from the native Docker host:

```sh
docker compose --env-file .env.dev.local -f compose.yaml -f .devcontainer/compose.resources.yaml --profile testing up -d --wait nginx-test
```

Then run `php artisan runa:test:e2e` inside the development container. The initial/recovery host `./bin/artisan runa:test:e2e` also starts that profile. src/playwright.config.mjs selects only src/app/tests/e2e and targets real Nginx/PHP-FPM at http://nginx-test:8080, never artisan serve or the owner fixture harness. RUNA_E2E_BASE_URL may select another provisioned HTTP(S) application test endpoint. No data reset is performed by the browser runner.

Chromium, Firefox and mobile Chromium use one worker and fresh contexts. The retained --critical spelling runs the application suite; use normal Playwright filters for specific cases. HTML report, screenshots and failure traces live under artifacts/playwright/application. Browser binaries are supplied by the development image; production contains no browser/test tooling.

## Concurrency and acceptance

The runa:test* wrappers share a nonblocking checkout suite lock and forward child failure codes. Native php artisan test and direct runners retain framework behavior and bypass that wrapper lock; avoid competing diagnostic suites against shared state. Cached application config must be cleared before backend wrapper runs so .env.testing remains effective.

Visually inspect significant frontend changes at desktop/tablet/mobile sizes, light/dark and PL/EN as relevant. Check keyboard/focus semantics, meaningful labels, console/network errors, long content and responsive overflow. Keep durable browser tests focused on critical application flows and reproducible regressions. Temporary exploration lives under .playwright/scratch or tools/scratch; promote useful coverage or remove scratch before closure, preserving ignored artifacts.

Do not weaken tests to obtain PASS. Investigate behavior versus test/isolation defects. Full quality/security/AI and production checks belong to final verification, with proportionate point checks during implementation. See [quality](../quality/README.md) and [security](../security/baseline.md).

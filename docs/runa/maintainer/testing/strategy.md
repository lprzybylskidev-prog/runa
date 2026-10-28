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

Standalone GitHub acquisition has offline source/security contracts in
`python3 tools/maintainer/test-python --scope=contracts --path=contracts/test_bootstrap.py` (`bootstrap-source-contracts` local gate).
They cover public/private credential transport, redirect denial, access failures,
tag/commit mismatch, malicious paths/links, real Git blob/mode verification,
atomic publication races, interruption, selection and installer failure/resume.
Temporary source fixtures never start Docker services or use real credentials.
Live public acquisition and authorized private acceptance are recorded separately;
synthetic private failures do not prove live private access.

First-install orchestration has offline preservation/failure/configuration
contracts in `tests/tools/runa/contracts/test_installation.py`, native command validation/mail
redaction in `InstallationTest`, and host acceptance in
`tests.tools.runa.acceptance.installation_host`. The latter uses real fresh isolated dev/prod
containers and databases, certificate-verified HTTPS/SMTP and a deliberate SMTP
rejection/resume. It checks unchanged active development identities, customer
version/files, stable credentials, repeat behavior and absence of real production
markers, then prints/removes only task-created disposable resources. Synthetic
mail/TLS acceptance does not prove a real domain/provider or production deployment.

## Disposable test resources

Owner `test-backend` shares the [public worker lifecycle](../../handbook/testing/strategy.md#test-resource-lifecycle): suite-locked guarded cleanup before and after execution, preserving the base database and refusing active workers. Native application tests participate in the same lock. `test-e2e` host startup uses the delivered journaled endpoint lifecycle and removes only its newly created, labeled testing containers; manually provisioned endpoints remain externally owned. Do not bypass these entrypoints to run suites. `TestResourceContracts` in testing/management.py covers ownership, manual preservation, startup failure and interrupted recovery; WorkerCleanupTest covers active-worker and non-testing refusal. Broad live browser/installation acceptance remains in final verification.

## Backend test framework

Pest 5.3.2 with pest-plugin-laravel 5.0.1 is installed on PHP 8.4 / Laravel 13. PHPUnit 13.4.2 and ParaTest 7.27.2 are resolved testing dependencies in `src/composer.lock`.

Feature tests are the default for application behavior that crosses Laravel boundaries.

Unit tests are for isolated logic where a smaller test is clearer and valuable.

Do not over-mock Laravel/application behavior merely to make a test "unit."

## Application and platform suite ownership

Customer defaults are application-only: `php artisan test` and `php artisan runa:test` use `src/phpunit.xml` with `tests/backend/app/Feature` and `Unit`; `runa:test:frontend` uses `src/vitest.config.mjs` with `tests/frontend/app/unit`; `runa:test:e2e` uses `src/playwright.config.mjs` with `tests/frontend/app/e2e`. The welcome scaffold includes one PHP, component and browser smoke test. Native `make:test` still writes application tests, including PHPUnit tests extending the reusable `Tests\TestCase` in `src/runa/Testing/TestCase.php`. The native test adapter selects `tests/backend/app` directly; there is no forwarding bootstrap in `src/tests`.

Owner commands `python3 tools/maintainer/test-backend`, `test-frontend` and `test-e2e` select private configurations under `tests/backend/runa`. The owner backend uses `Tests\PlatformTestCase`, a private fixture autoloader and explicit platform migration preparation. The shared customer base performs application migrations and bootstraps a fresh worker's missing technical platform history only through the delivered forward-only primitive, preserving guarded worker storage/database/cache isolation. Customer test setup never invokes owner distribution tools. Platform suites and `src/runa/tools` harnesses are excluded from customer acquisition; production images exclude both owners' tests.

The three `runa:test*` wrappers, native `php artisan test` and owner runners share a nonblocking checkout suite lock and preserve child failure codes. Native read-only test discovery bypasses lifecycle mutation. Direct Pest/Node runners bypass the wrapper lock and cleanup; use supported runners for execution. Runner options filter the selected suite; configurations and test roots are explicit rather than inferred from which private directories happen to be present.

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

`php artisan runa:test --parallel`

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

Development seed randomness is defined separately in `docs/runa/handbook/data/seeding.md`.

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

Keep temporary Playwright exploration in project scratch locations and generated browser evidence in the workspace-root runtime tree.

Generated browser artifacts such as:

- screenshots,
- videos,
- traces,

belong under a gitignored `.runtime/artifacts/playwright/`.

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

During implementation points, write code, tests and documentation without routinely running checks. Run only small, fast, focused checks or visual diagnostics needed to resolve a concrete uncertainty. Full suites, broad integration/installation acceptance, static analysis, image builds/scanner pipelines and full quality gates belong to final verification after all implementation, followed by fixes and repeated final verification. Mandatory commit hooks remain in force. During roadmap creation/review or execution, stop and notify the owner if any supplied plan requires broad verification earlier, regardless of its author; follow the [roadmap workflow](../workflows/roadmaps.md#roadmap-points) before proceeding. Test code and safe parallel design are still implemented with the feature.

## Implemented PostgreSQL safety foundation

`TestDatabaseGuard` requires environment `testing`, default connection/driver `pgsql`, host `postgres`, role `runa_test` and database `runa_test`, `runa_test_<positive integer>` or Laravel's `runa_test_test_<positive integer>`. It also queries the actual database name and session role before allowing a destructive operation. The supported host is declared in project config rather than inferred from the same connection being checked.

Guarded subclasses of Laravel's `db:wipe`, `migrate:fresh`, `migrate:refresh`, `migrate:reset` and `migrate:rollback` validate before execution, including an explicit `--database` option. Console-event listeners are insufficient: the installed framework intentionally skips forwarding those events during testing. The command subclasses cover both direct CLI use and nested framework calls. Normal safe `migrate` remains available in development; these destructive commands are intentionally restricted to isolated test databases. Production rollback does not automatically reverse database migrations.

Run inside the devcontainer:

```sh
python3 tools/maintainer/test-backend --parallel --processes=1 --filter=DatabaseTest
APP_ENV=testing php artisan runa:test-db:guard
```

The focused backend runner executes `DatabaseTest` in one disposable worker after verifying unsafe environment/host/role/name/connection rejection. It preserves the persistent base `runa_test` and uses the shared worker cleanup and suite lock. `check-database-migrations` selects this same runner; no private filename adapter or nested pytest process is involved.

## Local commands

Run these inside the configured development container. The host recovery launcher delegates to that container:

| Command                                                       | Purpose                                                                           |
| ------------------------------------------------------------- | --------------------------------------------------------------------------------- |
| `php artisan runa:format` / `php artisan runa:format --check` | Pint for project PHP; Prettier for frontend, frontend tests and root tool configs |
| `php artisan runa:lint`                                       | ESLint recommended JS/TS/Vue correctness rules; zero warnings                     |
| `php artisan runa:typecheck`                                  | Strict Vue/TypeScript application and test checking                               |
| `php artisan runa:analyse`                                    | Larastan/PHPStan `max`, no baseline, one analysis process with a 1 GiB PHP limit  |
| `php artisan runa:test`                                       | Application backend tests, serial                                                 |
| `php artisan runa:test --parallel`                            | Application backend tests, `TEST_WORKERS` from the resource profile               |
| `php artisan runa:test:frontend`                              | Application Vitest + Vue Test Utils in headless Chromium                          |
| `php artisan runa:test:e2e --critical`                        | Application Playwright in Chromium, Firefox and mobile Chromium                   |

Runner options can follow the test selector, for example `php artisan runa:test --filter=Welcome` or `php artisan runa:test:e2e --project=firefox`. `--critical` currently selects the entire small critical suite. Each wrapper provides English `--help`. Host wrappers execute the same tools in `app`; they do not use host PHP/Node. Normal configured-environment commands use `php artisan`; `./bin/artisan` is the initial/recovery host transport. Run `php artisan runa:dev:prepare` and start the development environment first.

Pint excludes the vendored `src/runa/lang/` snapshot and generated/runtime trees. Prettier also excludes localization data and lockfiles. Formatting establishes consistent source layout without changing the catalog's integrity hashes. ESLint owns correctness, Prettier owns layout; the core assignment rule is disabled only for Vue SFCs because template reads are invisible to that core rule. Other recommended rules remain active. PHPStan uses one analysis process to bound memory and avoid the native parallel-worker crash observed during toolchain verification.

The Vitest Playwright provider is a pinned integration adapter of the approved test stack. Components run against a real DOM; no DOM emulator is installed. Browser binaries and OS libraries for exactly Playwright 1.64.1 Chromium/Firefox are provisioned in the development Docker stage. Production dependency/frontend stages skip that browser layer, and production runtime images contain no testing tools.

## Backend execution details

`Tests\TestCase` bootstraps `.env.testing`, selects the guarded worker database before provider boot captures singleton connections, and verifies the base database configuration and live identity before any worker database DDL. Pest/ParaTest supplies a `TEST_TOKEN`; only canonical integers from 0 to 99999 are accepted, and positive tokens become `runa_test_test_N`. Missing worker databases are created by the dedicated test role, then the selected live identity is guarded again before migrations. The shared base runs the application migrator. A fresh customer worker lacking shared.migrations first invokes the delivered forward-only runa/database/prepare.php with the selected test database/storage and exact checkout HEAD; it never calls owner tools. Native sequential tests continue to use the base runa_test prepared by initialization. The shared test application disables Laravel's second per-case database selection to avoid double suffixes; other parallel callbacks and process lifecycle options remain available. Child workers receive the actual current database and storage explicitly. The owner PlatformTestCase additionally runs the private platform migrator first, with separate histories; this is test preparation, never normal application startup. Worker databases persist for reuse; tests using database state must use transactions or an explicitly guarded reset. The queue-table smoke test demonstrates transaction rollback using existing technical tables, without adding permanent business tables. Ownership acceptance uses disposable application tables to verify reciprocal fresh/refresh/reset/rollback/wipe and seeding isolation, guarded option rejection, dependency failure, unknown-history refusal, platform table inventory, and atomic required-seed retry.

Each worker receives `.runtime/tests/worker-N/` storage, an array cache with a worker prefix, synchronous queues and an array mailer. Test applications rebuild their configuration between cases. HTTP tests cover health, JSON errors and request IDs; database tests cover schema placement and unsafe configuration/live-identity rejection; localization tests cover native Polish validation. Cached application configuration is rejected by the test wrapper so development config cannot silently override `.env.testing`.

The test wrapper takes a nonblocking checkout-wide lock across backend, frontend and E2E runs. A competing suite fails clearly instead of reusing mutable worker state. Use the wrapper for normal runs; direct runner commands are diagnostic bypasses of that concurrency protection. No extra HTTP ports are needed for backend tests.

## Isolated browser endpoint

The optional Compose `testing` profile starts `app-test` and `nginx-test`, using `.env.testing`, the dedicated PostgreSQL test role/database, native `LARAVEL_STORAGE_PATH=.runtime/tests/e2e`, synchronous queue and array mail. The PHP-FPM startup guard verifies its actual test database before serving. Nginx is reachable only on the internal Compose network at `http://nginx-test:8080`; no additional host port is published. Development remains at its own endpoint and database.

From the host, `./bin/artisan runa:test:e2e --critical` starts the testing profile, waits for readiness and tears down only its newly created labeled endpoint containers, including on failure. Existing manual endpoints remain externally owned. From the devcontainer, provision those siblings once from a host terminal before running the identical wrapper:

```sh
docker compose --env-file .env.dev.local -f compose.yaml -f .devcontainer/compose.resources.yaml --profile testing up -d --wait nginx-test
```

The owner `python3 tools/maintainer/test-e2e` entrypoint starts, waits for and tears down its own labeled testing containers when invoked on the host; the owner browser quality gate explicitly uses the host coordinator. Direct execution inside the container still requires the host startup above. This lifecycle keeps Docker sockets out of the devcontainer. Application Playwright targets `http://nginx-test:8080` directly; `RUNA_E2E_BASE_URL` can select another already provisioned HTTP(S) application test endpoint. The runner does not start a PHP server, create synthetic routes or reset data. It finalizes emergency assets before execution and uses one worker because projects share the test endpoint. Each test gets a fresh browser context. The application welcome smoke runs in Chromium, Firefox and mobile Chromium; failures retain screenshots/traces and an HTML report under `.runtime/artifacts/playwright/application/`. Build first with `pnpm --dir src run build`. Owner harness acceptance is separate below.

## UI foundations and isolated browser tests

Public UI imports are exercised by `tests/frontend/runa/unit/public-ui.test.ts` and compile-checked consumers. `tests/frontend/runa/acceptance/ui-boundaries.mjs` bundles the real production entry and a public Table/ConfirmDialog consumer in memory: production must contain no retired review or test modules; components must not implicitly initialize layouts, routing or preferences. Docker context excludes tests/tools/.runtime and legacy public/ui-review output. `RetiredReviewTest` verifies that application routes have no previews/fixtures in local/testing/staging/production and former URLs return real 404s. No environment flag restores them.

Before browser checks, build the application/native error renderer:

```sh
./bin/pnpm run build
python3 tools/maintainer/test-e2e --critical
```

The owner E2E runner first finalizes static emergency documents with `php src/runa/build/errors/build.php` inside the application container, after the build and before Playwright starts. Missing renderer output fails preparation explicitly. The Node-only build does not need PHP; its regenerated output must never rely on stale static documents from an earlier setup. The owner test command uses isolated testing Nginx/PHP-FPM. Its private Playwright configuration owns `tests/frontend/runa/acceptance/browser-harness.mjs`, bound only to 127.0.0.1:4178 with reuse disabled, and stops it after the suite. It builds component/layout scenarios under tests/frontend/runa/unit/fixtures/browser into ignored `.runtime/browser-harness`, plus a separate independent consumer build. Before serving, the private harness verifies the isolated testing database and the actual checkout commit, then applies pending Core source metadata and no-data upgrades forward through ModuleUpdates. Existing application data remains intact; customer browser commands never execute this private preparation. There is no catalog selector, demo navigation, public output or application registration. Root/API/assets/real missing pages are proxied to nginx-test:8080. A test-only PHP HTTP-kernel adapter registers synthetic error/mail routes only in that child process, preserving actual Laravel response status, JSON negotiation, Retry-After, native preference forms and real array-transport MIME rendering. It does not start another PHP application server or deliver external messages. RUNA_E2E_UPSTREAM may select an existing development Nginx for focused local checks; it is test-tool input, not application runtime configuration. Missing generated emergency documents fail harness startup.

The 1440×900, tablet and 390×844 PL/EN light/dark matrices retain root, independent identity/version/footer, minimal optional illustration/long-content, application navigation/optional regions, forms, tables and component motion coverage. Real root preference interactions also run in Firefox and mobile Chromium. Screenshots and timing/trace attachments remain under ignored .runtime/artifacts/playwright/runa; they are evidence, not owner acceptance. Full CDP accessibility-tree inspection and Playwright-generated A4 PDF evidence run only on Chromium; Firefox retains the shared DOM/reflow and print-document geometry checks. Native browser-menu zoom and manual NVDA/VoiceOver certification are not claimed. `tests/frontend/runa/acceptance/frontend.mjs` remains an additional real-Nginx root diagnostic, checking MinimalLayout preference readiness and both footer versions against their independent application/platform manifests.

### Layouts, forms and interactions

Frontend tests cover public primitive/identity contracts, independent reactive scopes, safe URLs/logo fallback, footer options, versioned preference migration/denial/quota/isolation/reset, exact-path resolution and optional workspace states. Browser application tests exercise direct URL/history/refresh, sidebar scroll/collapse/groups, tooltip positioning, sticky header and scrolling toolbar, mobile focus trapping/Escape/outside dismissal, top-navigation overflow, user popovers and denied storage. Synthetic modules remain test fixtures, not application routes.

Form/unit scenarios cover controlled submission/validation, field/error associations, exact decimals, temporal/DST/date-only boundaries, file limits, masks/cursor/IME, dependent-field cancellation, dirty guards, Wizard validation/draft retention, layered dialogs and toast/tooltip ownership. Providers and features retain server authorization/integrity responsibility.

### Mail and native errors

MailLayoutTest sends real messages through Laravel’s array transport, checking standard Markdown/notification defaults, multipart/alternative, related CID branding, PL/EN, explicit/automatic text, independent versions, escaping, HTTPS/missing/unsafe image inputs, namespaced overrides and footer visibility. Test-only mail/browser templates live under tests/Fixtures; RunaServiceProvider registers only the production runa-mail namespace. Browser email checks cover no-JavaScript responsive HTML, long/blocked images, real text/MIME attachments and a public action URL. Browser engines do not certify Outlook/Gmail/Apple Mail rendering.

HttpErrorsTest registers synthetic failures inside the test process and covers six statuses in PL/EN, unknown paths, API/JSON negotiation, Retry-After/request IDs, identity escaping and database/build-asset failure without sensitive exception detail. Browser errors cover actual kernel statuses, no-JavaScript 404/503 responsive/theme matrices, shared MinimalLayout artwork, GET preference controls and generated standalone emergency output. These tests do not prove production Nginx fallback: retain the separate host `tests.tools.runa.acceptance.error_fallback_host` gate and production isolation checks. Favicon shape/dimensions and native asset contracts remain permanent.

### Tables and exports

Table/client/server/personalization tests cover counts, paging/filter/sort/search, stale/cancelled replies, encoded provider transport, stable selection, query snapshots, widths/order/visibility and saved/default views. Browser gestures use real mouse/native drag, waiting for transient width-error feedback to leave before measuring geometry; keyboard reorder remains covered. Export tests verify actual CSV/XLSX/PDF bytes, formulas/types, Unicode/pagination/orientation, explicit/all-matching/exclusions, bounded providers, cancellation/retry/partial progress/authorization feedback and isolated uniform print rules. Server jobs/permissions remain application-owned; no domain API or external service is added.

### Integrated semantics and component motion

The independent StandaloneConsumer fixture composes forms, selections, tabs, confirmation, tables and exports without a layout/router/preference bootstrap; browser flows run with storage blocked. Its build is independent of scenario shell chunks. In-memory boundary checks and production Docker exclusions remain required.

Integrated checks cover all optional shell combinations, ten component scenarios (layouts have their own matrix), normal/200%-equivalent reflow, duplicate IDs, labels/errors/accessible names, minimum control targets, 62 theme-token contrast pairs, keyboard/calendar/boolean/modal behavior, print and console/network cleanliness. 200% reflow uses a separate 720×450 CSS-pixel context at DPR 2, equivalent to a 1440×900 desktop layout/text scale, not native browser-menu zoom.

Component motion tests cover Select exits/interrupted reopening, immediate inert/aria-hidden and focus return, natural-height disclosures at three viewport widths under normal/reduced motion, stable file/tag removal focus and Wizard draft retention with live reduced-motion cancellation. Timing is checked through rendered interactions/DOM, not screenshots alone. The accepted complete component audit and maintenance rules are in `.agents/skills/frontend/references/motion.md`.

## Management transport and cold recovery

`python3 tools/maintainer/test-python --scope=contracts --path=contracts/test_management.py` checks host-plan rejection (including duplicate/unknown fields and arbitrary execution payloads), schema/allowlist agreement, output/time limits, shared production-lock contention, private receipt permissions/removal, unresolved-operation fencing, symlink rejection, plan-only behavior and scoped container cleanup after interruption. `ManagementTest` verifies the shared normal Artisan command and its absence from the normal production registry.

On the native Docker host, `python3 -m tests.tools.runa.acceptance.management_host` builds/reuses the isolated management image, copies only explicit credential-free transport/build inputs into a disposable checkout, proves discovery and plan-only with no application kernel/vendor/key/DB, then runs the real development command lifecycle against small isolated PHP process services. It stops and recovers that fixture app, rejects bad inputs and verifies active development app identity/start time did not change. This is transport/Compose recovery acceptance, not full Laravel installation or production acceptance. No database, model cache or durable volume is provisioned. Before scoped cleanup it lists fixture container/network identities; it preserves development/test data, caches, backups and evidence. The report is `.runtime/ownership/RM-2026-005-point-2-host.json`.

Both tests are selected by tooling quality gates, with the real host acceptance explicitly marked host-only. They do not require Docker privileges inside the devcontainer. Management PHP is included in the existing Pint and PHPStan scopes.

## Backend composition acceptance

`CompositionTest` verifies src base/config/database/namespace paths, protected database command registration, route ownership, customer config merging and identical-name file/cache isolation between application and Runa. Existing DB/seeding/queue/health/mail/error/localization tests use the same technical schemas and isolated test identity after namespace relocation. Test worker storage remains outside the development storage tree; E2E still honors LARAVEL_STORAGE_PATH. Platform tests live under tests/backend/runa; tests/backend/app has the customer Pest contribution and separate ApplicationTests autoload namespace. Run wrappers from the repository root; they select src. Native Artisan and the application wrapper select ../tests/backend/app directly; direct Pest uses --test-directory=../tests/backend/app. There is no src/tests directory or forwarding bootstrap. Owner bootstrap and fixture discovery are selected exclusively by the maintainer backend runner.

### Native Pest parallel transport

`php artisan test --parallel --processes=2` keeps the application PHP scope and
shared TestRun lock/worker cleanup. Its adapter passes the central test root to
Pest before Pest delegates to ParaTest; Collision's separate early ParaTest option
parser does not support Pest's test-directory flag. Sequential/PHPUnit commands
continue composing Collision. Both paths retain literal arguments, native exits,
TTY selection and guarded testing environment; Laravel parallel/cache flags remain
explicit child inputs. This is a fixed transport to the installed engine, not a
second scheduler or a reason to bypass the supported command.

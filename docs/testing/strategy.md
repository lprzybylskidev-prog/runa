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

| Command                                 | Purpose                                                                           |
| --------------------------------------- | --------------------------------------------------------------------------------- |
| `./bin/format` / `./bin/format --check` | Pint for project PHP; Prettier for frontend, frontend tests and root tool configs |
| `./bin/lint`                            | ESLint recommended JS/TS/Vue correctness rules; zero warnings                     |
| `./bin/typecheck`                       | Strict Vue/TypeScript application and test checking                               |
| `./bin/analyse`                         | Larastan/PHPStan `max`, no baseline, one analysis process with a 1 GiB PHP limit  |
| `./bin/test`                            | Backend feature tests, serial                                                     |
| `./bin/test --parallel`                 | Backend feature tests, `TEST_WORKERS` from the resource profile                   |
| `./bin/test frontend`                   | Vitest + Vue Test Utils in headless Chromium                                      |
| `./bin/test e2e --critical`             | Current critical Playwright suite in Chromium, Firefox and mobile Chromium        |

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

This explicit host lifecycle keeps Docker sockets out of the devcontainer. E2E uses one worker because all browser projects share one testing endpoint. Each test gets a fresh browser context. Critical coverage checks independent API health, responsive overflow, compact PL/EN and system/light/dark controls, preference persistence, opaque loading-cover sequencing, focus restoration, tooltip dismissal and unexpected console/page errors. HTTP-client failure behavior remains covered by frontend unit contracts; the root intentionally has no connection/retry panel. Failure traces live in ignored `artifacts/playwright/results/`. `tools/testing/frontend.mjs` remains the broader visual diagnostic and also targets the isolated testing endpoint.

## UI foundations and isolated browser tests

Public UI imports are exercised by `tests/frontend/public-ui.test.ts` and compile-checked consumers. `tools/testing/ui-boundaries.mjs` bundles the real production entry and a public Table/ConfirmDialog consumer in memory: production must contain no retired review or test modules; components must not implicitly initialize layouts, routing or preferences. Docker context excludes tests/tools/.runtime and legacy public/ui-review output. `RetiredReviewTest` verifies that application routes have no previews/fixtures in local/testing/staging/production and former URLs return real 404s. No environment flag restores them.

Before browser checks, build the application/native error renderer:

```sh
./bin/pnpm run build
./bin/test e2e --critical
```

The normal E2E wrapper first finalizes static emergency documents with `php resources/errors/build.php` inside the application container, after the build and before Playwright starts. Missing renderer output fails preparation explicitly. The Node-only build does not need PHP; its regenerated output must never rely on stale static documents from an earlier setup. The normal test command uses isolated testing Nginx/PHP-FPM. Playwright owns `tools/testing/browser-harness.mjs`, bound only to 127.0.0.1:4178 with reuse disabled, and stops it after the suite. It builds component/layout scenarios under tests/frontend/fixtures/browser into ignored `.runtime/browser-harness`, plus a separate independent consumer build. There is no catalog selector, demo navigation, public output or application registration. Root/API/assets/real missing pages are proxied to nginx-test:8080. A test-only PHP HTTP-kernel adapter registers synthetic error/mail routes only in that child process, preserving actual Laravel response status, JSON negotiation, Retry-After, native preference forms and real array-transport MIME rendering. It does not start another PHP application server or deliver external messages. RUNA_E2E_UPSTREAM may select an existing development Nginx for focused local checks; it is test-tool input, not application runtime configuration. Missing generated emergency documents fail harness startup.

The 1440×900, tablet and 390×844 PL/EN light/dark matrices retain root, independent identity/version/footer, minimal optional illustration/long-content, application navigation/optional regions, forms, tables and component motion coverage. Real root preference interactions also run in Firefox and mobile Chromium. Screenshots and timing/trace attachments remain under ignored artifacts/playwright; they are evidence, not owner acceptance. Full CDP accessibility-tree inspection and Playwright-generated A4 PDF evidence run only on Chromium; Firefox retains the shared DOM/reflow and print-document geometry checks. Native browser-menu zoom and manual NVDA/VoiceOver certification are not claimed. `tools/testing/frontend.mjs` remains an additional real-Nginx root diagnostic, checking MinimalLayout preference readiness and both footer versions against canonical VERSION.

### Layouts, forms and interactions

Frontend tests cover public primitive/identity contracts, independent reactive scopes, safe URLs/logo fallback, footer options, versioned preference migration/denial/quota/isolation/reset, exact-path resolution and optional workspace states. Browser application tests exercise direct URL/history/refresh, sidebar scroll/collapse/groups, tooltip positioning, sticky header and scrolling toolbar, mobile focus trapping/Escape/outside dismissal, top-navigation overflow, user popovers and denied storage. Synthetic modules remain test fixtures, not application routes.

Form/unit scenarios cover controlled submission/validation, field/error associations, exact decimals, temporal/DST/date-only boundaries, file limits, masks/cursor/IME, dependent-field cancellation, dirty guards, Wizard validation/draft retention, layered dialogs and toast/tooltip ownership. Providers and features retain server authorization/integrity responsibility.

### Mail and native errors

MailLayoutTest sends real messages through Laravel’s array transport, checking standard Markdown/notification defaults, multipart/alternative, related CID branding, PL/EN, explicit/automatic text, independent versions, escaping, HTTPS/missing/unsafe image inputs, namespaced overrides and footer visibility. Test-only mail/browser templates live under tests/Fixtures; AppServiceProvider registers only the production runa-mail namespace. Browser email checks cover no-JavaScript responsive HTML, long/blocked images, real text/MIME attachments and a public action URL. Browser engines do not certify Outlook/Gmail/Apple Mail rendering.

HttpErrorsTest registers synthetic failures inside the test process and covers six statuses in PL/EN, unknown paths, API/JSON negotiation, Retry-After/request IDs, identity escaping and database/build-asset failure without sensitive exception detail. Browser errors cover actual kernel statuses, no-JavaScript 404/503 responsive/theme matrices, shared MinimalLayout artwork, GET preference controls and generated standalone emergency output. These tests do not prove production Nginx fallback: retain the separate host `tools/testing/error-fallback-host.py` gate and production isolation checks. Favicon shape/dimensions and native asset contracts remain permanent.

### Tables and exports

Table/client/server/personalization tests cover counts, paging/filter/sort/search, stale/cancelled replies, encoded provider transport, stable selection, query snapshots, widths/order/visibility and saved/default views. Browser gestures use real mouse/native drag, waiting for transient width-error feedback to leave before measuring geometry; keyboard reorder remains covered. Export tests verify actual CSV/XLSX/PDF bytes, formulas/types, Unicode/pagination/orientation, explicit/all-matching/exclusions, bounded providers, cancellation/retry/partial progress/authorization feedback and isolated uniform print rules. Server jobs/permissions remain application-owned; no domain API or external service is added.

### Integrated semantics and component motion

The independent StandaloneConsumer fixture composes forms, selections, tabs, confirmation, tables and exports without a layout/router/preference bootstrap; browser flows run with storage blocked. Its build is independent of scenario shell chunks. In-memory boundary checks and production Docker exclusions remain required.

Integrated checks cover all optional shell combinations, ten component scenarios (layouts have their own matrix), normal/200%-equivalent reflow, duplicate IDs, labels/errors/accessible names, minimum control targets, 62 theme-token contrast pairs, keyboard/calendar/boolean/modal behavior, print and console/network cleanliness. 200% reflow uses a separate 720×450 CSS-pixel context at DPR 2, equivalent to a 1440×900 desktop layout/text scale, not native browser-menu zoom.

Component motion tests cover Select exits/interrupted reopening, immediate inert/aria-hidden and focus return, natural-height disclosures at three viewport widths under normal/reduced motion, stable file/tag removal focus and Wizard draft retention with live reduced-motion cancellation. Timing is checked through rendered interactions/DOM, not screenshots alone. The accepted complete component audit and maintenance rules are in `.agents/skills/frontend/references/motion.md`.

## Management transport and cold recovery

`python3 tools/testing/management.py` checks host-plan rejection (including duplicate/unknown fields and arbitrary execution payloads), schema/allowlist agreement, output/time limits, shared production-lock contention, private receipt permissions/removal, unresolved-operation fencing, symlink rejection, plan-only behavior and scoped container cleanup after interruption. `ManagementTest` verifies the shared normal Artisan command and its absence from the normal production registry.

On the native Docker host, `python3 tools/testing/management-host.py` builds/reuses the isolated management image, copies only explicit credential-free transport/build inputs into a disposable checkout, proves discovery and plan-only with no application kernel/vendor/key/DB, then runs the real `bin/dev` lifecycle against small isolated PHP process services. It stops and recovers that fixture app, rejects bad inputs and verifies active development app identity/start time did not change. This is transport/Compose recovery acceptance, not full Laravel installation or production acceptance (RM-005 point 8 onward). No database, model cache or durable volume is provisioned. Before scoped cleanup it lists fixture container/network identities; it preserves development/test data, caches, backups and evidence. The report is `.runtime/ownership/RM-2026-005-point-2-host.json`.

Both tests are selected by tooling quality gates, with the real host acceptance explicitly marked host-only. They do not require Docker privileges inside the devcontainer. Management PHP is included in the existing Pint and PHPStan scopes.

## Backend composition acceptance

`CompositionTest` verifies src base/config/database/namespace paths, protected database command registration, route ownership, customer config merging and identical-name file/cache isolation between application and Runa. Existing DB/seeding/queue/health/mail/error/localization tests use the same technical schemas and isolated test identity after namespace relocation. Test worker storage remains outside the development storage tree; E2E still honors LARAVEL_STORAGE_PATH. Platform tests stay at repository tests/ through a src/tests bridge until point 4. Run wrappers from the repository root during this stage.

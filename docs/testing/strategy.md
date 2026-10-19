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

## UI foundation contracts and baseline

RM-2026-004 point 1 adds `tests/frontend/public-ui.test.ts` for independent public primitive imports and a compile-checked provider consumer. `./bin/pnpm exec node tools/testing/ui-boundaries.mjs` bundles the real production entry and an isolated public consumer in memory: review modules must stay absent from production and controls must not initialize a layout/router/preferences. Laravel `UiReviewTest` uses isolated temporary public fixtures to verify environment registration, stale-route rejection, a missing review build and real 404/API behavior; it needs no generated review assets.

Build both entries before browser checks: `./bin/pnpm run build` and `./bin/pnpm exec vite build --config vite.review.config.mjs`. `./bin/test e2e --critical` includes the exact Chromium matrix at 1440×900, 1024×768 and 390×844 in PL/EN and light/dark. Screenshots are retained under ignored `artifacts/playwright/ui-foundations/`; timing JSON attachments in the Playwright results record opacity at locale change, inert content, the 500ms hold, normal/reduced-motion behavior and focus restoration. Existing preference interactions also run in Firefox and mobile Chromium. These captures establish the point-1 baseline for later visual gates; screenshots alone do not constitute user acceptance. `tools/testing/frontend.mjs` is an additional existing diagnostic, not a replacement for this baseline.

Point 2 adds `tests/frontend/minimal-layout.test.ts`: named/default slots, independent reactive identity scopes, URL validation, logo failure/replacement and individual/whole footer options. `tests/e2e/minimal-layout.spec.ts` captures the same PL/EN/theme/viewport matrix for the built-in illustration, explicit illustration hiding and long-content compositions. It checks short-page footer placement, long-page flow, optional visibility and console/network cleanliness. Root regression now verifies the dual-version footer. Public-consumer bundling checks modules with emitted code (`renderedLength > 0`), since Rolldown also reports fully tree-shaken parsed modules, plus explicit absence of layout/preference initialization in emitted consumer code. Review environment/production exclusion remains covered by UiReviewTest and the bundle check. Point 2 was explicitly accepted on 2026-10-18; point 9 corrects the intermittent preference-tooltip dismissal defect and tests the shared controls in both layouts.

The point-2 root/minimal matrices also assert that the narrow-screen illustration is 240px high and appears above content, the artwork retains its fixed RUNA signature, and footer platform attribution follows PL/EN.

RM-2026-004 point 3 adds preference schema/migration/denial/quota/isolation/reset tests and exact-path resolver/optional-workspace/availability tests. Application E2E covers direct URL/history/refresh, persisted sidebar groups/collapse, collapsed tooltip position, actual sidebar scroll isolation, sticky-header/scrolling-toolbar behavior, matching brand/header height, full-row sidebar disclosures and header collapse-control placement, focus-trapped mobile drawer, Escape/outside dismissal/focus return, navigation focus, avatar disclosure, independently hidden regions, fluid width, denied storage and narrow top-navigation overflow. The 12-case Chromium PL/EN/theme/viewport matrix captures application layout and mobile drawers, checks console/network cleanliness and print visibility. Root/minimal regression and preference timing continue across browser projects. UiReviewTest verifies all five named application review entries, unknown paths and production denial. Fixtures remain synthetic and separate from the production entry; retire temporary review dependencies at point 22 after motion acceptance.

### Shared control and feedback verification

`tests/frontend/controls.test.ts` covers native busy activation, accessible groups and decorative icons through isolated public imports. `tests/frontend/feedback.test.ts` mounts isolated fixtures for alert live semantics, tooltip hover/content/keyboard focus/click/Escape, nested popover dismissal and focus/Tab return, toast remaining-time pause/resume and persistent errors/focused dismissal. Timer fixtures isolate synthetic pointer events from the browser's physical pointer position; real pointer/preference behavior also receives targeted rendered inspection.

The temporary feedback category covers PL/EN/light/dark at desktop/tablet/mobile, including narrow popup bounds, top-right toast placement, explicit close and retained inline errors. Keep interaction screenshots at viewport size after transitions settle; full-page captures taken while scrolled can misleadingly show fixed offscreen content. Existing root/application E2E remains the regression check for preference timing, drawer behavior and the unique profile trigger using shared Popover. Permanent tests do not import temporary catalog components.

### Native email layout verification

`tests/Feature/MailLayoutTest.php` sends real messages through Laravel's array transport (no Mail fake/SMTP), checks standard Markdown and notification defaults, multipart/alternative and related CID parts, PL/EN, explicit/automatic text, independent versions, escaping, public HTTPS/missing/unsafe image inputs and namespaced overrides/footer visibility. `UiReviewTest` checks email route registration and stale-route rejection outside local/testing. Fixtures under `tests/Fixtures/mail` are permanent test-only templates, independent of temporary preview content.

`tests/e2e/email.spec.ts` checks the native email preview in Chromium/Firefox/mobile Chromium; Chromium captures PL/EN desktop/tablet/mobile light-only HTML without JavaScript and long/blocked-image variants. Screenshots and sample EML/TXT are ignored evidence under `artifacts/playwright/ui-foundations`; MIME/text are also test attachments. Inspect the real rendered output. These checks exercise browser engines, not Outlook/Gmail/Apple Mail; actual client rendering must be reported separately when available. Temporary preview checks retire with the review infrastructure in RM-2026-004 point 22; permanent MIME/template tests remain.

### HTTP errors and emergency response

`HttpErrorsTest` covers six native statuses in PL/EN, real unknown routes, API/JSON negotiation, Retry-After/request IDs, identity escaping and database/build-asset failure without production detail disclosure. It also checks that the standalone-document preview has a complete 503 response body. `UiReviewTest` verifies production/staging absence and stale-controller rejection. `tests/e2e/errors.spec.ts` exercises actual native status codes and no-JavaScript 404/503 desktop/tablet/mobile PL/EN light/dark matrices, actual MinimalLayout artwork/header/footer composition, native preference forms and the static-document preview. Temporary browser review checks retire with point 22; native error tests/templates remain.

Offline production/operations contracts verify the narrow static-routing and read-only maintenance mount, keeping Nginx running while writers stop, resume/health failure behavior, backups and rollback compatibility. They do not claim real Nginx execution. Run `python3 tools/testing/error-fallback-host.py` on the native host for the production Nginx image/config, missing/recovered/stopped FastCGI peer, explicit maintenance, internal-page access, JSON/status/body/retry/CSP/static-asset contracts and scoped disposable-resource cleanup. It uses the existing development image only for a synthetic Python FastCGI peer; no development service or database is changed. Keep its ignored JSON report alongside visual evidence. Full release verification still runs the applicable production deployment/isolation gates.

Dialog interaction coverage (`tests/frontend/dialogs.test.ts`) checks title/description/modal semantics, initial focus, Tab wrapping, return focus, dynamic background inert state, nested dialog/Select dismissal, busy/error/retry, complete backdrop gestures and shared scroll-lock cleanup. Browser review additionally verifies viewport bounds, long-content scrolling and the existing mobile drawer’s focus/scroll restoration. Preview fixtures are development-only and are not a dependency of these permanent isolated component tests.

### Integrated UI review and independent consumer

RM-2026-004 point 20 adds tests/e2e/integrated-ui.spec.ts and tests/frontend/layout-matrix.test.ts. The integrated browser suite checks all 32 sidebar/topbar/statistics/toolbar/supplementary combinations in contained/fluid widths across desktop/tablet/mobile, PL/EN and light/dark: 768 rendered states. The additional frontend matrix covers 16 optional actions/breadcrumbs/footer/heading combinations while preserving mandatory content; existing MinimalLayout/Footer tests cover individual footer elements, illustrations, overrides and long content.

All eleven catalog categories run in PL/EN and both themes at normal and 200%-equivalent desktop reflow (88 category states). The scaled case uses a distinct 720×450 CSS-pixel browser context at DPR 2, preserving 1440×900 physical dimensions without CSS transforms or screenshot-time CDP metric resets. Checks include document overflow, duplicate IDs, label/error references, Chromium accessibility-tree control names, and at least 24×24 visible button/boolean-control targets (actual larger wrapping labels count as the target; inline links and disabled controls are not treated as standalone targets). Table regions retain intentional internal horizontal scrolling. Sixty-two theme-token text/control/focus contrast pairs meet the relevant 4.5:1/3:1 thresholds; this is not a pixel-wide gradient/color audit.

The layout-free fixture is tests/frontend/fixtures/StandaloneConsumer.vue with a thin standalone-main.ts/CSS entry. The temporary review build compiles it independently after the catalog, preventing a shared catalog/layout chunk, and links it from the catalog footer. In-memory public-consumer bundling verifies no emitted layout/router/preference initialization while allowing ordinary field/feedback/layer/table composables. Browser tests deny Storage methods and still complete mobile PL/EN/theme form validation, field-error association/focus, keyboard selection/tabs, confirmation focus loop/Escape/return, sort/selection and real CSV download. Calendar grid/day navigation and mixed checkbox/switch keyboard states are verified in both languages.

Reduced-motion and print cases preserve functionality and hide shell/navigation/footer/toolbars in print. Existing root/minimal/application matrices, timing, sticky navigation, drawer focus, persistence and provider/export checks remain applicable. Console warnings/errors, unexpected HTTP failures and failed requests are collected; intentional navigation/request aborts are excluded. Render captures are under artifacts/playwright/integrated. Accessibility evidence uses browser semantics and real keyboard interaction; no manual NVDA/VoiceOver session or complete WCAG certification is claimed. Final owner visual acceptance remains separate from automated success.

The complete catalog and temporary independent HTML remain until explicit retirement approval in point 20. Point 21 removes preview HTML/routes/build registrations and catalog-only tests, while preserving the reusable consumer fixture and production boundary checks.

## Component motion verification

RM-2026-004 point 21 adds `tests/e2e/component-motion.spec.ts`: real Select entrance/exit and interrupted reopening, Escape/focus return, disclosure collapse/inert semantics at three viewport widths with normal/reduced motion, stable tag/file removal focus and Wizard draft retention with live reduced-motion cancellation. Existing integrated catalog, table and export checks continue to exercise PL/EN, both themes, reflow, keyboard and print. Intermediate visual timing is checked through rendered CSS/DOM behavior; screenshots alone are not claimed as motion proof. The complete component audit and reusable rules are in `.agents/skills/frontend/references/motion.md`. Temporary URL-based checks retire with the catalog; permanent component harness tests remain.

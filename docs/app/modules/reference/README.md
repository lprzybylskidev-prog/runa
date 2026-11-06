# Development reference module

`app.reference` is an evolvable, application-owned working example. It is Optional and initially disabled. Production source, build registry and images exclude it completely. The framework rejects accidentally copied reference source in production. Keep this identity development-only; create a separately named application module for production behavior.

The first-development installer offers the example interactively or with `--reference-module=include`. Use `--reference-module=exclude` for clean source (also the unattended default). The acquisition receipt records the selection; sparse checkout retains it on reopen. The external Runa updater replaces protected platform source only, preserving application source and central documentation, including local edits. It never installs a previously declined example. Ordinary reopen performs no example copy or migration. Customer repository clones retain the committed example selection; production staging removes its source even when the customer committed it.

## Source map

| Contract                                        | Working source under `src/app/modules/reference/`                                                                                                         |
| ----------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Identity, compatibility, declarations           | `module.json`                                                                                                                                             |
| Health, lifecycle and work decisions            | `backend/Definitions/ReferenceHealthPolicy.php`, `ReferenceWorkPolicy.php`, `ReferenceJobPolicy.php`                                                      |
| Typed public API, guarded proxy, implementation | `backend/Contracts/Ping.php`, `backend/PingProxy.php`, `backend/PingHandler.php`                                                                          |
| Named guarded HTTP/resource                     | `backend/Definitions/ReferenceHttp.php`, `backend/Http/`                                                                                                  |
| Owned schema and UUIDv7 model                   | `database/migrations/`, `backend/Models/Sample.php`                                                                                                       |
| Transactional installation                      | `backend/Definitions/ReferenceUpgrades.php`, `backend/InitializeReference.php`                                                                            |
| Typed settings and reversible reinitialization  | `backend/Definitions/ReferenceSettings.php`                                                                                                               |
| Classified CLI, hourly scheduling and jobs      | `backend/Definitions/ReferenceCommands.php`, `ReferenceSchedules.php`, `ReferenceJobs.php`                                                                |
| Independent versioned facts/listeners           | `backend/Definitions/ReferenceEvents.php`, `ReferenceListener.php`, `ReferenceEventHandler.php`                                                           |
| Navigation, page and named slot contributions   | `backend/Definitions/ReferenceFrontend.php`, `frontend/components/panel.vue`                                                                              |
| Guarded Admin page, read adapter and widget     | `backend/Definitions/ReferenceManagement.php`, `backend/Management/ReferenceReader.php`, `frontend/components/inspect_section.vue`, `response_widget.vue` |
| Reusable source assertion                       | `tests/backend/app/modules/reference/ModuleContractTest.php` (repository-relative)                                                                        |

The candidate Admin example is read-only: it shows the same current nonsensitive label as
Ping through an owned page and dashboard widget. The adapter uses the guarded API;
it never reads another module's models or exposes encrypted settings. No write,
synthetic entity identity, health timestamp or automatic permission assignment is
introduced. Admin is optional to the business API; the module needs no Core
manifest dependency just to declare framework-owned management contracts.

## Install and observe

From development, first inspect `./bin/artisan runa:modules:check app`. Registration is explicit: `./bin/artisan runa:modules:sync app`. Synchronization alone neither migrates nor enables. A trusted installation/deployment integration explicitly calls `ModuleUpdates::plan('app')`, reviews the plan, then calls `apply('app', $exactSourceCommit, $journalProgressCallback)` under its existing maintenance/writer-stop/operation journal. This is an application-owner operation; the Runa updater never runs it implicitly. The reference migration creates only `app_reference.samples`; the transactional initializer inserts one `welcome` row. Repeat installation retains that row and its public identity. No downgrade/destructive rollback is supplied.

Run `./bin/artisan runa:modules:health app.reference` before enabling. In a trusted application console integration, obtain `ModuleTransitions::preview('enable', 'app.reference')`, review blockers/changes and apply that exact object. Inspect `runa:modules:management app.reference`. Disable through the same preview/apply protocol; stored rows, settings and files remain. Re-enabling never restores unrelated cascade-disabled dependents.

The named `app.reference.reference.read` HTTP route is `GET /api/app/reference/ping`; it returns the nonsensitive `label` setting. Resolve the typed PHP API using `ModuleApis::resolve('app.reference', Ping::class, '1.0.0')`. Cached proxies recheck availability on every call. Do not instantiate PingHandler from a consumer. These development read endpoints contain no protected business data; new production operations need real server authorization.

`app:reference:inspect` remains diagnostic while disabled; `app:reference:work` dispatches guarded database-queue work. The hourly schedule uses that same operational entrypoint. Work checks cancellation before its repeat-safe private `last-observation.txt` write and publishes `observed` v1; synchronous and asynchronous subscribers run independently. Checkpoints cannot undo completed effects, and failed/retried work may repeat fact publication. Consumers needing exactly-once business effects must add domain idempotency. The framework does not invent compensation for a module.

Read settings with `runa:modules:settings app.reference`; pass a JSON `values` patch through stdin with `--write --revision=<read revision> --generation=<read generation>` to change `label`. The optional `credential` setting demonstrates encrypted storage and reinitialization; it has no external service effect and is never returned in Ping/logs. Read projection exposes only whether it is set. Revision/generation conflicts require a fresh read and deliberate retry. Infrastructure keys remain outside module settings.

The opt-in frontend registry exposes `app.reference.panel`. Compose the existing ModulePage/navigation adapters in your chosen shell; the installer does not replace App.vue. The panel uses shared Card/Button and HTTP transport, Polish default/English locale prop, Button-owned loading, shared FormNotifications error/retry feedback, retained previous response during refresh and abort on unmount. It remains static except inherited control feedback; no page entrance motion is needed. The `app.reference.panel` host accepts `reference.panel` v1 contributions; place ModuleSlot at the host location in a consumer. Do not place the page into its own host recursively.

## Checks and troubleshooting

Run `php artisan runa:test --scope=backend ../tests/backend/app/modules/reference`
from the configured development PHP terminal for the delivered source assertion.
Native backend paths resolve from `src`; use `./bin/artisan` from the host only
when setup/recovery transport is needed. Runtime metadata can be checked with `runa:modules:check app --runtime` after synchronization. This source assertion does not exercise PostgreSQL installation, settings changes,
work/events, retained-data disable or production exclusion. Add application-owned
behavior tests for the examples you adopt; private platform suites are not
delivered customer tests or a customer verification prerequisite.

- Missing source: inspect the installation selection and sparse projection. Do not materialize declined examples on ordinary reopen/update.
- Critical unknown health: explicitly diagnose before enable; scheduled recovery only handles due enabled modules. Health failure never changes enabled flags.
- Installed version mismatch: review and run the explicit application module update; toggles cannot migrate.
- API 409/503 or hidden navigation: inspect management/effective availability and manifest/build hashes, synchronize reviewed source and refresh the frontend projection.
- Waiting disable: inspect active work; run/reconcile the module's own bounded policy. Do not edit flags or discard leases.
- Production refusal: remove development reference source from the delivery projection; disabling it is insufficient.

Use the [public module development guide](../../../runa/handbook/development/modules.md) for creation, dependency direction, source boundaries, tests and independent version policy. Current reference version is 0.3.0, independently of platform VERSION.

The health declaration implements HealthPolicy schema 2. Its hybrid mode and
check timing are defined in ReferenceHealthPolicy source only; no Admin tab or
database override changes that policy.

The reference SettingsProvider demonstrates the native source/CLI settings
contract; it has no generic Admin settings tab. Administrator-facing configuration
requires a dedicated owned page with change history and its own validated workflow,
as described in the Admin authoring guide. Existing values remain preserved.

The example panel binds its own request state to public Button busy, retaining a
stable action label and result/error output; no loading sentence or separate
spinner implementation is needed.

## Admin integration status

The unreleased 0.3.0 source includes exact-call health planning (Framework API
^1.1.0). Focused native regressions cover the guarded inspect page/widget and access
revocation, including withdrawal between selection and health preparation. Health
checks run before the locked business read; the hybrid policy is unchanged. Final
development runtime alignment and rendered/manual acceptance remain roadmap work,
not a claim from these focused tests. No development data update/reset follows
from source tests alone.

## Admin customer journey and replacement contracts

After the explicit app update and enable steps above, use the application's normal
Permissions administration to grant `runa.admin.enter` and
`app.reference.reference.inspect` to an authorized development account. Entry uses
the shared session/password confirmation. Registration does not grant access.
Open `/admin/app.reference/inspect`: the label comes from the existing guarded
`Ping::read(): PingResult` API 1.0.0. Refresh rereads that API. The Admin dashboard
also displays its response; it makes no claim about technical health or observation
freshness. Grant withdrawal hides the page/widget and refuses subsequent reads;
disabling the module withdraws the owning API too. Public nonsensitive Ping remains
usable without Admin entry. No business mutation or Audit event is emitted by a read.

Source declarations remain application-owned in `backend/Definitions`; the private
`Management/ReferenceReader` adapter implements the delivered
`ManagementResourceReader::{read,page}` and `ManagementWidgetReader::widget` DTO
signatures. It refuses foreign public IDs, cursors, filters and sorts. It is not a
new supported business API. The response revision hashes the current message and
is only a read projection identity. The `admin_showcase` 0.2.0 → 0.3.0 forward edge
changes no schema, sample row, credential, settings or prior upgrade identities.

`inspect_section.vue` consumes public `AdminSectionPresentationProps` and composes
`AdminSection` inside shared Card/Button chrome. Admin owns authorization,
readiness, read cancellation, errors and disposal. Customers can edit this example
or replace the section through the documented Admin presentation provider, retaining
one form controller. `response_widget.vue` accepts public `{widget: AdminWidgetData}`;
it renders only available response values and uses the shared Admin locale.

The standalone `panel.vue` keeps `{locale?: 'pl'|'en'}` (Polish default) and exposes
a default slot `{busy:boolean, message:string, load:()=>Promise<void>}` for response
markup. It owns the HTTP lane, duplicate-read guard, retained response, notification
lifetime and unmount cancellation. Values are nonsensitive business text and are
never translated. Existing page/slot keys remain unchanged; no customer copies
security logic. Mount a ModulePage for `app.reference.panel` or a ModuleSlot for
its declared host in an opted-in application composition. Do not recursively
mount a host inside its own panel contribution.

`ReferenceManagement::readPlan` returns one ManagementReadCall for Ping::read
for each read/page/widget invocation. It uses the existing binding targets and
contains no arguments, results or proof. Customers extending the reader follow the
[public read-health contract](../../../runa/handbook/modules/admin/declarations.md#read-health-plans),
including separate occurrences for repeated/nested public calls. A critical owner
health failure withdraws its effective grants and hides its widget; an isolated
reader failure while still available shows an empty unavailable diagnostic.

## Capability reproduction and deliberate exclusions

| Capability                                | Runnable source / reproduction                                                                                                                                                                                                                                                                           |
| ----------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Identity and retained optional lifecycle  | Inspect `module.json`, `runa:modules:check app --runtime`, then the explicit lifecycle preview/apply procedure above. Disable retains the sample and settings.                                                                                                                                           |
| Owned PostgreSQL data                     | Explicit app update runs the one-table migration and repeat-safe initializer for the `welcome` Sample. The model and migration show UUIDv7/ownership; the example intentionally supplies no unrelated record editor.                                                                                     |
| Guarded PHP and HTTP                      | Resolve Ping 1.0.0 above, or request `GET /api/app/reference/ping` while effectively available.                                                                                                                                                                                                          |
| Settings/encryption/reinitialization      | `runa:modules:settings app.reference`; write only a reviewed JSON `values` patch with the exact revision/generation. The optional credential is redacted, not a working external integration.                                                                                                            |
| CLI/work/scheduler/storage                | `app:reference:inspect`; while enabled, `app:reference:work`. Observe the returned work ID through the public `ModuleWork::status($workId)` adapter; the owning hourly schedule invokes the same operational entry. `ReferenceJobHandler` shows cancellation checkpoints and repeat-safe scoped storage. |
| Versioned facts and listeners             | Work publishes `observed` v1. `ReferenceEvents`, `ReferenceListener` and `ReferenceEventHandler` show independent synchronous/asynchronous consumers; queue completion is distinct from initial dispatch. Publication may repeat on retry.                                                               |
| Frontend page/navigation/visual slot      | `ReferenceFrontend`, panel and an opted-in ModulePage/ModuleSlot consumer as above. Production excludes their source/build entries.                                                                                                                                                                      |
| Admin composition                         | The inspect page, declared binding, native grant, replaceable section and dashboard widget above are executable.                                                                                                                                                                                         |
| Health and work policy                    | `runa:modules:health app.reference` and the source-owned policy definitions. No generic Admin health/integration editor.                                                                                                                                                                                 |
| Cross-module editing and aggregate writes | Intentionally absent: no foreign-subject editing use case exists in this read-only scenario. Use the [Admin examples](../../../runa/handbook/modules/admin/examples.md) and their opt-in generator/Core examples when adding one.                                                                        |

Application frontend regressions live in
`tests/frontend/app/unit/modules/reference/presentation.test.ts`. Run the scoped
`php artisan runa:test:frontend ../tests/frontend/app/unit/modules/reference/presentation.test.ts`
from the development PHP terminal. They cover retained reads, duplicate requests,
dismissed localized failures, replacement response markup, cancellation and
unavailable widget content and public Admin section refresh retention/busy feedback. Private owner suites additionally exercise native Admin
grants and adapter availability; they are not customer dependencies.

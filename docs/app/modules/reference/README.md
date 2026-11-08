# Development reference module

`app.reference` is an evolvable, application-owned working example. It is Optional and initially disabled. Production source, build registry and images exclude it completely. The framework rejects accidentally copied reference source in production. Keep this identity development-only; create a separately named application module for production behavior.

The first-development installer offers the example interactively or with `--reference-module=include`. Use `--reference-module=exclude` for clean source (also the unattended default). The acquisition receipt records the selection; sparse checkout retains it on reopen. The external Runa updater replaces protected platform source only, preserving application source and central documentation, including local edits. It never installs a previously declined example. Ordinary reopen performs no example copy or migration. Customer repository clones retain the committed example selection; production staging removes its source even when the customer committed it.

## Source map

| Contract                                        | Working source under `src/app/modules/reference/`                                                                                                                                            |
| ----------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Identity, compatibility, declarations           | `module.json`                                                                                                                                                                                |
| Health, lifecycle and work decisions            | `backend/Definitions/ReferenceHealthPolicy.php`, `ReferenceWorkPolicy.php`, `ReferenceJobPolicy.php`                                                                                         |
| Typed public API, guarded proxy, implementation | `backend/Contracts/Ping.php`, `backend/PingProxy.php`, `backend/PingHandler.php`                                                                                                             |
| ReferenceTools and shared protected workspace   | `backend/Contracts/ReferenceTools.php`, `backend/Actions/`, `frontend/contracts.ts`, `frontend/useReferenceWorkspace.ts`, `frontend/components/workspace.vue`, `tools_page.vue`              |
| Named guarded HTTP/resource                     | `backend/Definitions/ReferenceHttp.php`, `backend/Http/`                                                                                                                                     |
| Owned schema and UUIDv7 model                   | `database/migrations/`, `backend/Models/Sample.php`                                                                                                                                          |
| Transactional installation                      | `backend/Definitions/ReferenceUpgrades.php`, `backend/InitializeReference.php`                                                                                                               |
| Typed settings and reversible reinitialization  | `backend/Definitions/ReferenceSettings.php`                                                                                                                                                  |
| Classified CLI, hourly scheduling and jobs      | `backend/Definitions/ReferenceCommands.php`, `ReferenceSchedules.php`, `ReferenceJobs.php`                                                                                                   |
| Independent versioned facts/listeners           | `backend/Definitions/ReferenceEvents.php`, `ReferenceListener.php`, `ReferenceEventHandler.php`                                                                                              |
| Navigation, page and named slot contributions   | `backend/Definitions/ReferenceFrontend.php`, `frontend/components/panel.vue`                                                                                                                 |
| Guarded Admin pages, read adapters and widget   | `backend/Definitions/ReferenceManagement.php`, `backend/Management/ReferenceReader.php`, `frontend/components/inspect_section.vue`, `response_widget.vue`, `tools_page.vue`, `workspace.vue` |
| Reusable source assertion                       | `tests/backend/app/modules/reference/ModuleContractTest.php` (repository-relative)                                                                                                           |

The Admin inspect page/widget retain Ping; the four additional Admin pages and
replaceable standalone workspace expose ReferenceTools 1.0.0 through the guarded
backend workflows below. Runtime alignment and rendered acceptance remain
separate from source delivery. The example declares optional Admin/Audit/Auth/Permissions integration
constraints, so Ping and its CLI/work example remain independent of Core storage.
Protected examples require those integrations installed and effectively available.
No registration, synchronization or source update automatically grants user access.

## Install and observe

From development, first inspect `./bin/artisan runa:modules:check app`. Registration is explicit: `./bin/artisan runa:modules:sync app`. Synchronization alone neither migrates nor enables. A trusted installation/deployment integration explicitly calls `ModuleUpdates::plan('app')`, reviews the plan, then calls `apply('app', $exactSourceCommit, $journalProgressCallback)` under its existing maintenance/writer-stop/operation journal. This is an application-owner operation; the Runa updater never runs it implicitly. The reference migration creates only `app_reference.samples`; the transactional initializer inserts one `welcome` row. Repeat installation retains that row and its public identity. No downgrade/destructive rollback is supplied.

Run `./bin/artisan runa:modules:health app.reference` before enabling. In a trusted application console integration, obtain `ModuleTransitions::preview('enable', 'app.reference')`, review blockers/changes and apply that exact object. Inspect `runa:modules:management app.reference`. Disable through the same preview/apply protocol; stored rows, settings and files remain. Re-enabling never restores unrelated cascade-disabled dependents.

The named `app.reference.reference.read` HTTP route is `GET /api/app/reference/ping`; it returns the nonsensitive `label` setting. Resolve the typed PHP API using `ModuleApis::resolve('app.reference', Ping::class, '1.0.0')`. Cached proxies recheck availability on every call. Do not instantiate PingHandler from a consumer. These development read endpoints contain no protected business data; new production operations need real server authorization.

`app:reference:inspect` remains diagnostic while disabled; `app:reference:work` dispatches guarded database-queue work. The hourly schedule uses that same operational entrypoint. Work checks cancellation before its repeat-safe private `last-observation.txt` write and publishes `observed` v1; synchronous and asynchronous subscribers run independently. Checkpoints cannot undo completed effects, and failed/retried work may repeat fact publication. Consumers needing exactly-once business effects must add domain idempotency. The framework does not invent compensation for a module.

Read settings with `runa:modules:settings app.reference`; pass a JSON `values` patch through stdin with `--write --revision=<read revision> --review=<read review_id>` to change `label`. The optional `credential` setting demonstrates encrypted storage and reinitialization; it has no external service effect and is never returned in Ping/logs. Read projection exposes only whether it is set. Revision/review conflicts require a fresh read and deliberate retry. Infrastructure keys remain outside module settings.

The opt-in frontend registry exposes `app.reference.panel`. Compose the existing ModulePage/navigation adapters in your chosen shell; the installer does not replace App.vue. The panel uses shared Card/Button and HTTP transport, Polish default/English locale prop, Button-owned loading, shared FormNotifications error/retry feedback, retained previous response during refresh and abort on unmount. It remains static except inherited control feedback; no page entrance motion is needed. The `app.reference.panel` host accepts `reference.panel` v1 contributions; place ModuleSlot at the host location in a consumer. Do not place the page into its own host recursively.

## Checks and troubleshooting

Run `php artisan runa:test --scope=backend ../tests/backend/app/modules/reference`
from the configured development PHP terminal for the delivered application cases.
Native backend paths resolve from `src`; use `./bin/artisan` from the host only
when setup/recovery transport is needed. Runtime metadata can be checked with `runa:modules:check app --runtime` after synchronization. The source assertion alone does not exercise PostgreSQL installation, settings changes,
work/events, retained-data disable or production exclusion. CoreExamplesTest adds
application-owned HTTP success/refusal/validation/rollback cases for the backend workflows below. Add application-owned
behavior tests for the examples you adopt; private platform suites are not
delivered customer tests or a customer verification prerequisite.

- Missing source: inspect the installation selection and sparse projection. Do not materialize declined examples on ordinary reopen/update.
- Critical unknown health: explicitly diagnose before enable; scheduled recovery only handles due enabled modules. Health failure never changes enabled flags.
- Installed version mismatch: review and run the explicit application module update; toggles cannot migrate.
- API 409/503 or hidden navigation: inspect management/effective availability and manifest/build hashes, synchronize reviewed source and refresh the frontend projection.
- Waiting disable: inspect active work; run/reconcile the module's own bounded policy. Do not edit flags or discard leases.
- Production refusal: remove development reference source from the delivery projection; disabling it is insufficient.

Use the [public module development guide](../../../runa/handbook/development/modules.md) for creation, dependency direction, source boundaries, tests and independent version policy. Current reference version is 0.4.1, independently of platform VERSION.

The health declaration implements HealthPolicy schema 2. Its hybrid mode and
check timing are defined in ReferenceHealthPolicy source only; no Admin tab or
database override changes that policy.

The reference SettingsProvider demonstrates native settings access, field grants,
action-specific proof and publication. Its owned HTTP workflow and named snapshot
adapter and the dedicated Admin workspace are implemented below.
Existing values remain preserved.

The example panel binds its own request state to public Button busy, retaining a
stable action label and result/error output; no loading sentence or separate
spinner implementation is needed.

## Admin integration status

The 0.4.0 source retains exact-call health planning and now requires Framework API
^2.1.0. Focused native regressions cover the guarded inspect page/widget and access
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

Use HMR or the explicit [development asset build](../../../runa/handbook/development/environment.md#firstopenrebuild-behavior)
when viewing the example through Nginx. A default production-mode build omits its
components even in a local Laravel environment; a successful API response alone
does not establish that the example's presentation is available.

Source declarations remain application-owned in `backend/Definitions`; the private
`Management/ReferenceReader` adapter implements the delivered
`ManagementResourceReader::{read,page}` and `ManagementWidgetReader::widget` DTO
signatures. It refuses foreign public IDs, cursors, filters and sorts. It is not a
new supported business API. The response revision hashes the current message and
is only a read projection identity. Current installation runs the single
`initialize` null → 0.4.1 definition after its owned CREATE migration.

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
for each inspect read/page/widget invocation; samples/settings/history/work each
declare their actual ReferenceTools read method. It uses the existing binding targets and
contains no arguments, results or proof. Customers extending the reader follow the
[public read-health contract](../../../runa/handbook/modules/admin/declarations.md#read-health-plans),
including separate occurrences for repeated/nested public calls. A critical owner
health failure withdraws its effective grants and hides its widget; an isolated
reader failure while still available shows an empty unavailable diagnostic.

## Capability reproduction and deliberate exclusions

The [Core capability matrix](core-capabilities.md) maps all four supplied Core
modules to public API versions, executable source, routes, expected results and
application success/refusal tests. It distinguishes reference examples from native
Core workflows and private platform verification.

| Capability                                | Runnable source / reproduction                                                                                                                                                                                                                                                                                                             |
| ----------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Identity and retained optional lifecycle  | Inspect `module.json`, `runa:modules:check app --runtime`, then the explicit lifecycle preview/apply procedure above. Disable retains the sample and settings.                                                                                                                                                                             |
| Owned PostgreSQL data                     | Explicit app update runs the one-table migration and repeat-safe initializer for the `welcome` Sample. The model and migration show UUIDv7/ownership; ReferenceTools supplies revision-checked editing with an independent code-field grant.                                                                                               |
| Guarded PHP and HTTP                      | Resolve Ping 1.0.0 above, or request `GET /api/app/reference/ping` while effectively available.                                                                                                                                                                                                                                            |
| Settings/encryption/reinitialization      | `runa:modules:settings app.reference`; write only a reviewed JSON `values` patch with the exact revision/review identity. The optional credential is redacted, not a working external integration.                                                                                                                                         |
| CLI/work/scheduler/storage                | `app:reference:inspect`; while enabled, `app:reference:work`. Observe the returned work ID through the public `ModuleWork::status($workId)` adapter; the owning hourly schedule invokes the same operational entry. `ReferenceJobHandler` shows cancellation checkpoints and repeat-safe scoped storage.                                   |
| Versioned facts and listeners             | Work publishes `observed` v1. `ReferenceEvents`, `ReferenceListener` and `ReferenceEventHandler` show independent synchronous/asynchronous consumers; queue completion is distinct from initial dispatch. Publication may repeat on retry.                                                                                                 |
| Frontend page/navigation/visual slot      | `ReferenceFrontend`, panel and an opted-in ModulePage/ModuleSlot consumer as above. Production excludes their source/build entries.                                                                                                                                                                                                        |
| Admin composition                         | Inspect/section/widget retain Ping; samples/settings/history/work pages use ReferenceTools and the shared workspace. See the Core matrix for independent grants, proof and refusal cases.                                                                                                                                                  |
| Health and work policy                    | `runa:modules:health app.reference` and the source-owned policy definitions. No generic Admin health/integration editor.                                                                                                                                                                                                                   |
| Cross-module editing and aggregate writes | Foreign-subject editing and multi-module aggregate business writes remain excluded: the example edits its own shared Sample. Native settings/protected work integrate the required Core participants. Use the [Admin examples](../../../runa/handbook/modules/admin/examples.md) and their opt-in generator/Core examples when adding one. |

Application frontend regressions live in
`tests/frontend/app/modules/reference/presentation.test.ts`. Run the scoped
`php artisan runa:test:frontend ../tests/frontend/app/modules/reference/presentation.test.ts`
from the development PHP terminal. They cover retained reads, duplicate requests,
dismissed localized failures, replacement response markup, cancellation and
unavailable widget content and public Admin section refresh retention/busy feedback. Private owner suites additionally exercise native Admin
grants and adapter availability; they are not customer dependencies.

## Guarded Core backend examples

Owner: application reference module. Audience: application/module developers.
Implementation lives in `backend/Contracts/ReferenceTools.php`, its source-owned
`Definitions/ReferenceToolsApi.php`, `ReferenceToolsProxy.php`, `Actions`, `Queries`,
`Policies`, `Http` and `Management/ReferenceSnapshots.php`. Resolve the guarded API
through `ModuleApis::resolve('app.reference', ReferenceTools::class, '1.0.0')`.
These are normal backend runtime entrypoints in the optional development example;
production excludes the entire example. Never construct the handler to bypass ingress.

Complete signatures (the contract's array PHPDoc documents scalar collections):

```php
public function samples(): array;
public function editSample(string $publicId, string $revision, string $code): array;
public function settings(): array;
public function reviewSettings(array $patch, int $revision, string $reviewId): array;
public function publishSettings(array $patch, int $revision, string $reviewId, string $review): array;
public function snapshots(?string $publicId = null): array;
public function captureSnapshot(string $name, int $revision, string $reviewId): array;
public function deleteSnapshot(string $publicId, int $revision, string $reviewId): array;
public function history(?string $sample = null, ?string $before = null): array;
public function dispatch(): array;
public function work(): array;
```

All named authenticated routes below use `/api/app/reference/` and return
`{data: ...}` with no-store responses. Route names use `app.reference.` plus the
operation key in the table. The existing Ping route remains public/nonsensitive.

| Operation         | Method/path           | Grant suffix and observable result                                                                                                               |
| ----------------- | --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| samples.read      | GET samples           | samples.read; bounded items with public_id/revision, code only with samples.code.read                                                            |
| samples.edit      | PATCH samples         | samples.write plus samples.read and samples.code.write; public_id/revision/code input returns the new monotonic revision and safe projection     |
| settings.read     | GET settings          | settings.read; revision, review_id and localized settings; credential set-state requires settings.credential.read                                |
| settings.review   | POST settings/review  | settings.write plus settings.read; values/revision/review_id input returns redacted changes and encrypted review                                 |
| settings.publish  | POST settings/publish | same grants, exact original input plus review; one-use password action app.reference.settings.publish; operation/status result                   |
| snapshots.read    | GET snapshots         | settings.read; optional public_id loads committed label only; snapshots is a native list; values is null until selected, then a native field map |
| snapshots.capture | POST snapshots        | settings.write plus settings.read; name/revision/review_id captures committed label with Audit                                                   |
| snapshots.delete  | DELETE snapshots      | same grants; public_id/revision/review_id and own password action app.reference.settings.snapshot_delete                                         |
| history.read      | GET history           | history.read plus independent runa.audit.read; optional sample/before UUIDv7 filters; safe event identity/action/time/outcome and next cursor    |
| work.dispatch     | POST work             | work.dispatch; password action app.reference.work.dispatch commits durable work intent and explicit Audit; work_id/status admitted               |
| work.read         | GET work              | work.read; bounded owning work identities, statuses, progress, attempts and cancellation state                                                   |

Grant suffixes above use `app.reference.`. Credential replacement/clear additionally
requires settings.credential.write, and the backend independently enforces it.
An operation password is entered only in Auth's shared full-page confirmation
view; Admin entry proof cannot authorize these separate actions. The samples are
shared development records with an explicit owning Policy; deleted records refuse.
`EditSample` uses the native transaction phases and opt-in Auditable code field.
`DispatchWork` prepares queue admission before locks and records its explicit event
in the same transaction; admission does not claim worker completion.

Settings use the [native publication contract](../../../runa/handbook/architecture/module-settings.md).
Store the original revision/review_id, review an explicit label/credential patch,
confirm the exact action, then deliberately publish that same patch and review.
Credential null clears, a string sets/replaces, and omission retains it. Neither
review, history nor snapshots expose credential values or ciphertext. Health renewal
alone preserves review identity; real source/availability/revision changes refuse.
Loading a named snapshot only returns label values for a draft; it never publishes.
Current rules, grants and a fresh own proof still apply at publication. A conflict
requires the user to reconsider their draft; never fetch a new identity and replay.

The original samples CREATE defines positive bigint revision with default 1.
Existing installations carrying the earlier CREATE/ALTER fingerprints require
explicit identified development/test storage and module-ledger reconciliation;
ordinary update refuses changed migration history. A native application wipe alone
does not reset module completion or installed-version provenance. See the
[database reset policy](../../../runa/handbook/architecture/database.md#migration-policy). Core source compatibility includes Admin ^1.1.0 for its frontend lifecycle seam, Auth ^6.0.0,
Audit ^1.1.0 and Permissions ^2.1.0; those modules require their current explicit
forward updates before protected workflows can run. The reference workspace is implemented in source; aligned runtime/assets and
owner acceptance remain separate. No UI acceptance is claimed by the backend cases.

Run the application-owned `CoreExamplesTest.php` through the supported backend
runner against prepared isolated test Core. It covers field omission/read/write
refusal, audited edit/stale/deleted records, settings secret safety/review/proof,
named snapshots, dispatch proof/replay/expiry/revocation, Audit-read independence
and transaction rollback. Owner tests additionally cover native semantic identity,
prepared-read nesting and policy snapshot integration. Full suites and current
development runtime preparation remain later roadmap points.

## Interactive reference presentation

The application-owned `frontend/components/workspace.vue` is the shared default
presentation for samples, settings/snapshots, history and managed work. Its typed
`ReferenceWorkspaceProps` are `{ access: ReferenceAccess; view: ReferenceView;
locale?: 'pl' | 'en' }`. `ReferenceView` is `samples | settings | history | work`.
`ReferenceAccess` contains an optional admitted `identity` string, `read`, `write`,
`codeRead`, `codeWrite`, `credentialRead` and `credentialWrite` booleans. The caller
must derive these hints from current server authority and withdraw identity/access
on logout, restriction, module disable or access loss. Hints never authorize HTTP.
Keep one workspace per admitted identity/view; the controller aborts and clears
protected state when its input authority changes or its Vue scope is disposed.

`useReferenceWorkspace(access, view, locale)` (three getter functions) lives in
`frontend/useReferenceWorkspace.ts`, returns `ReferenceWorkspaceController`, and
owns guarded HTTP reads, draft reconciliation, review/publication, snapshots and
action-specific Auth proof. The workspace default slot receives `{ controller }`
for a complete application presentation replacement; preserve controller lifetime
and the shell's Auth password-confirmation host. Neither component nor controller
starts a session. Standalone consumers pass their existing admitted session key
and capability projection, compose `PageNavigation` in their shell for the four
views, and mount this component directly. The ordinary registry panel and dashboard
widget retain public Ping without protected requests. Do not create a second
session or copy Auth security logic to mount the workspace.

Admin registers `/admin/app.reference/samples`, `/settings`, `/history` and `/work`
(with the same `/admin/app.reference/` prefix). The shared
`frontend/components/tools_page.vue` consumes public `AdminPageProps`; current
page/section/field projections provide access hints. The native Admin shell owns
subview navigation, session invalidation and full-page password confirmation.
The custom view reads the named authenticated ReferenceTools HTTP endpoints;
source-owned readers `ReferenceSamplesReader`, `ReferenceSettingsReader`, `ReferenceHistoryReader` and `ReferenceWorkReader` provide genuine guarded read fallbacks for
the declared native bindings. No private Runa frontend import is used.

The module owns these application examples; their audience is application authors,
the entrypoints are the components/composable and Admin pages above, and delivery
is optional development frontend/backend source, excluded from production. Backend
ReferenceTools signatures remain 1.0.0; this presentation completes the existing
unreleased reference 0.4.1 candidate without changing its storage or API.

### Workspace behavior and reproduction

Grant `runa.admin.enter` and the desired `app.reference.<area>.read` grant; add the
independent write grants from the HTTP table only when needed. The native Admin
top bar advertises readable pages. Field grants omit the code or credential
projection independently; a read grant alone renders readonly values. The history
page also requires AuditReads authority; example grants never replace it.

1. Open `/admin/app.reference/samples`, select the code action, edit and save. A
   competing edit retains the local draft and offers the shared conflict choices;
   a removed record closes its editor. Open `/admin/app.reference/history` with
   independent history/Audit grants to inspect the owning action/outcome summaries.
2. Open `/admin/app.reference/settings`, change the label and review it. Publish
   requests the existing Auth full-page password confirmation for
   `app.reference.settings.publish`. Admin entry proof does not authorize it.
   Source-provided setting names/descriptions follow the active PL/EN locale.
   Credential controls expose set state and deliberate keep/replace/clear intent;
   neither the previous value nor the new value appears in review/history/snapshots.
3. Name and capture the committed label, even while another label is in the draft.
   Loading a snapshot changes only the draft and invalidates its review. Dirty
   replacement requires consent. Deletion uses a shared destructive dialog followed
   by its distinct `app.reference.settings.snapshot_delete` password action.
4. Open `/admin/app.reference/work` and dispatch. Confirm
   `app.reference.work.dispatch`; cancellation sends no domain write. Admission is
   reported separately from execution. Explicit refresh reads actual managed work;
   no polling or simulated completion is supplied.

The controller owns one read/write lane per view and preserves content during
refresh. Settings and snapshot operations share that lane and the last confirmed
semantic review/revision. Actual stale-state refusals refresh only this owning
view, retain drafts and never replay a write or silently substitute a fresh review.
Unrelated business conflicts retain their own message. Secrets/drafts stay in
local memory and are erased on identity/access withdrawal/disposal. Standalone
routers must call the workspace's exposed `beforeLeave(): Promise<boolean>`;
`blocksNavigation: boolean` reports dirty/pending state; `initialSettled: boolean`
reports settlement of the initial read for a composing shell. Native Admin installs this
through `useAdminPageContext`, without a second session or private frontend import.

Application regressions live in `tests/frontend/app/modules/reference/workspace.test.ts`
plus the retained presentation cases. Their supported application frontend runner
owns disposal/reporting; complete suites, browser states, current runtime/assets
and owner acceptance are separate roadmap stages.

Snapshot reads demonstrate native structured Management values through the guarded
framework snapshot owner. The standalone/replacement workspace reads these values
directly, loads only label into the local draft and preserves the original settings
revision/review until publication. It does not parse an encoded fallback or expose
credentials. Admin composition requires Admin ^2.0.0; source requires Framework
API ^2.1.0. Use the working settings/snapshot page with its existing read/write
grants to capture a committed label, load it without publishing, cancel back to
the prior draft, then deliberately review/publish through the native proof flow.

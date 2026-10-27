# Development reference module

`app.reference` is an evolvable, application-owned working example. It is Optional and initially disabled. Production source, build registry and images exclude it completely. The framework rejects accidentally copied reference source in production. Keep this identity development-only; create a separately named application module for production behavior.

The first-development installer offers the example interactively or with `--reference-module=include`. Use `--reference-module=exclude` for clean source (also the unattended default). The acquisition receipt records the selection; sparse checkout retains it on reopen. The external Runa updater replaces protected platform source only, preserving application source and central documentation, including local edits. It never installs a previously declined example. Ordinary reopen performs no example copy or migration. Customer repository clones retain the committed example selection; production staging removes its source even when the customer committed it.

## Source map

| Contract                                        | Working source under `src/app/modules/reference/`                                                    |
| ----------------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| Identity, compatibility, declarations           | `module.json`                                                                                        |
| Health, lifecycle and work decisions            | `backend/Definitions/ReferenceHealthPolicy.php`, `ReferenceWorkPolicy.php`, `ReferenceJobPolicy.php` |
| Typed public API, guarded proxy, implementation | `backend/Contracts/Ping.php`, `backend/PingProxy.php`, `backend/PingHandler.php`                     |
| Named guarded HTTP/resource                     | `backend/Definitions/ReferenceHttp.php`, `backend/Http/`                                             |
| Owned schema and UUIDv7 model                   | `database/migrations/`, `backend/Models/Sample.php`                                                  |
| Transactional installation                      | `backend/Definitions/ReferenceUpgrades.php`, `backend/InitializeReference.php`                       |
| Typed settings and reversible reinitialization  | `backend/Definitions/ReferenceSettings.php`                                                          |
| Classified CLI, hourly scheduling and jobs      | `backend/Definitions/ReferenceCommands.php`, `ReferenceSchedules.php`, `ReferenceJobs.php`           |
| Independent versioned facts/listeners           | `backend/Definitions/ReferenceEvents.php`, `ReferenceListener.php`, `ReferenceEventHandler.php`      |
| Navigation, page and named slot contributions   | `backend/Definitions/ReferenceFrontend.php`, `frontend/components/panel.vue`                         |
| Future management metadata                      | `backend/Definitions/ReferenceManagement.php`                                                        |
| Reusable source assertion                       | `tests/ModuleContractTest.php`                                                                       |

## Install and observe

From development, first inspect `./bin/artisan runa:modules:check app`. Registration is explicit: `./bin/artisan runa:modules:sync app`. Synchronization alone neither migrates nor enables. A trusted installation/deployment integration explicitly calls `ModuleUpdates::plan('app')`, reviews the plan, then calls `apply('app', $exactSourceCommit, $journalProgressCallback)` under its existing maintenance/writer-stop/operation journal. This is an application-owner operation; the Runa updater never runs it implicitly. The reference migration creates only `app_reference.samples`; the transactional initializer inserts one `welcome` row. Repeat installation retains that row and its public identity. No downgrade/destructive rollback is supplied.

Run `./bin/artisan runa:modules:health app.reference` before enabling. In a trusted application console integration, obtain `ModuleTransitions::preview('enable', 'app.reference')`, review blockers/changes and apply that exact object. Inspect `runa:modules:management app.reference`. Disable through the same preview/apply protocol; stored rows, settings and files remain. Re-enabling never restores unrelated cascade-disabled dependents.

The named `app.reference.reference.read` HTTP route is `GET /api/app/reference/ping`; it returns the nonsensitive `label` setting. Resolve the typed PHP API using `ModuleApis::resolve('app.reference', Ping::class, '1.0.0')`. Cached proxies recheck availability on every call. Do not instantiate PingHandler from a consumer. These development read endpoints contain no protected business data; new production operations need real server authorization.

`app:reference:inspect` remains diagnostic while disabled; `app:reference:work` dispatches guarded database-queue work. The hourly schedule uses that same operational entrypoint. Work checks cancellation before its repeat-safe private `last-observation.txt` write and publishes `observed` v1; synchronous and asynchronous subscribers run independently. Checkpoints cannot undo completed effects, and failed/retried work may repeat fact publication. Consumers needing exactly-once business effects must add domain idempotency. The framework does not invent compensation for a module.

Read settings with `runa:modules:settings app.reference`; pass a JSON `values` patch through stdin with `--write --revision=<read revision> --generation=<read generation>` to change `label`. The optional `credential` setting demonstrates encrypted storage and reinitialization; it has no external service effect and is never returned in Ping/logs. Read projection exposes only whether it is set. Revision/generation conflicts require a fresh read and deliberate retry. Infrastructure keys remain outside module settings.

The opt-in frontend registry exposes `app.reference.panel`. Compose the existing ModulePage/navigation adapters in your chosen shell; the installer does not replace App.vue. The panel uses shared Card/Button and HTTP transport, Polish default/English locale prop, explicit loading/error/retry feedback and abort on unmount. It remains static except inherited control feedback; no page entrance motion is needed. The `app.reference.panel` host accepts `reference.panel` v1 contributions; place ModuleSlot at the host location in a consumer. Do not place the page into its own host recursively.

## Checks and troubleshooting

Run `./bin/artisan runa:test app/modules/reference/tests` for the delivered source assertion. Runtime metadata can be checked with `runa:modules:check app --runtime` after synchronization. Owner acceptance in `tests/backend/runa/Feature/ModuleReferenceTest.php` additionally exercises real PostgreSQL install/retry/settings/API/work/events/retained-data disable and production refusal. Existing framework regressions cover dependency/Core/failure/concurrency scenarios with isolated fixtures, without pretending Auth/Admin exist.

- Missing source: inspect the installation selection and sparse projection. Do not materialize declined examples on ordinary reopen/update.
- Critical unknown health: explicitly diagnose before enable; scheduled recovery only handles due enabled modules. Health failure never changes enabled flags.
- Installed version mismatch: review and run the explicit application module update; toggles cannot migrate.
- API 409/503 or hidden navigation: inspect management/effective availability and manifest/build hashes, synchronize reviewed source and refresh the frontend projection.
- Waiting disable: inspect active work; run/reconcile the module's own bounded policy. Do not edit flags or discard leases.
- Production refusal: remove development reference source from the delivery projection; disabling it is insufficient.

Use the [public module development guide](../../../runa/handbook/development/modules.md) for creation, dependency direction, source boundaries, tests and independent version policy. Current reference version is 0.1.0, independently of platform VERSION.

# Queues, Scheduler, Console, and Testing

## Jobs

1. Jobs are for asynchronous/heavy/long work.
2. A job should delegate business logic to an Action/Service rather than embedding the whole use case.
3. Define queue/retry/backoff behavior explicitly per job.
4. Make duplicate-risk jobs idempotent when possible.
5. Dispatch after commit when the job depends on committed database state.
6. Failed jobs use standard Laravel failure mechanisms plus safe actionable context.

Module jobs use the [managed work protocol](../../../../docs/runa/handbook/architecture/module-framework.md#delivered-queue-and-active-work-protocol): source-owned JobProvider/JobDefinition, WorkHandler and WorkPolicy, dispatched through ModuleWork rather than raw Bus/Queue. Work row/envelope share the registry's database transaction; keep queue retry_after above the lease and atomic units below the fixed lease-minus-five timeout. Test real worker start/deserialization, releases, generation races and independent process death; fakes cannot prove fences or crash recovery. Policy code owns retry/cancel/compensation; failed/expired work requires explicit reconciliation and must never replay automatically. Ordinary application jobs retain Laravel's normal behavior.

## Scheduler

7. Declare scheduled behavior using the current supported Laravel scheduler mechanism.
8. Do not require host cron as the application architecture when the production scheduler container can run the supported mechanism.
9. Add overlap/concurrency protection when a scheduled task would be unsafe to run concurrently.
10. Do not apply overlap locks mechanically to tasks that are naturally safe.

Technical queue heartbeat comes from the real daemon's WorkerStarting, maintenance polling and idle/completion paths. Maintenance pauses job consumption without suppressing daemon liveness; separate health probes never refresh their own heartbeat. WorkerStopping clears the in-process daemon context.

Module schedules use source-owned ScheduleProvider/ScheduleDefinition targeting classified CommandProvider entries. Operational entries dispatch managed ModuleWork jobs; diagnostics use owned DiagnosticHandler. Preserve callback-time and worker-time checks, UTC cron, optional callback overlap expiry and maintenance-exempt technical pulse/health recovery. A schedule mutex ends after enqueue; work-level idempotency/coordination belongs to the module. Test cached schedules across lifecycle/source changes and real worker start.

## Console commands

Guard development execution before local Actions and independently before host locks/executors: a production acquisition receipt cannot be overridden by a caller's development APP_ENV. Preserve production artifact exclusion and inert discovery/previews. Production/deploy/host controls use their operational scope; do not add a public bypass. See docs/runa/handbook/tooling/commands.md for mode boundaries.

Custom commands expose one fixed operation per discoverable name (for example `runa:dev:up`, `runa:knowledge:search`, `runa:test:frontend`). Do not add a public action selector or a flag that starts an unrelated task. Keep native framework combinations and explicitly named install/update/deploy/quality workflows. Use the shared versioned management definitions for normal/cold/host registration; owner `tools/maintainer/check-*`, `quality-*` and `test-*` entrypoints have no customer aliases. See docs/runa/handbook/tooling/commands.md.

Runtime-independent host operations share the explicit management command registry with normal local/testing Artisan. Normal php artisan host commands execute through the authenticated private workspace executor; only explicit --plan is inert. Use `./bin/artisan --management list` for cold discovery and --recover-host/--operator for setup/recovery; application providers, dotenv, DB and keys are outside that bootstrap. Local development tooling runs PHP Actions/fixed local processes. Operational and optional development registries remain separate. Production runtime has no management commands, executor socket or credentials; only the separate operator environment receives transport access. Follow docs/runa/handbook/tooling/host-executor.md and the current command reference, including operation receipts after self-restart/disconnection and no blind mutation retry.

Owner-delivered first installation uses internal dev/prod installation and existing dev/deploy mechanics,
private settings and explicit initial-install resume. Preserve the scaffold and
generated secrets; repeat is not an update/reset. Native SMTP verification is an
application command and must redact provider failures. Follow
docs/runa/handbook/production/deployment.md for the implemented lifecycle and acceptance scope.

11. Console commands return meaningful exit codes.
12. Output/help is concise, actionable, and English-only.
13. Destructive commands require clear confirmation interactively.
14. If a destructive command needs automation, provide an explicit safe non-interactive mode instead of bypassing safety implicitly.
15. Do not print secrets/sensitive payloads.

## Testing

16. Prefer Pest when the installed version stack supports it cleanly; otherwise use PHPUnit.
17. Feature tests are primary for application behavior crossing Laravel boundaries.
18. Unit tests focus on isolated logic where that scale is genuinely clearer.
19. Use framework fakes selectively and preserve meaningful integration behavior.
20. Security/behavior regressions receive focused tests when reproducible.

Test ownership is explicit: native `php artisan test` and customer `runa:test`, `runa:test:frontend`, `runa:test:e2e` use application configurations and `src/app/tests`. Select the guarded worker database before provider boot so singleton services and test transactions share one connection; do not switch/purge it after booted services capture it. Shared `src/runa/Testing/TestCase.php` preserves the `Tests\TestCase` contract, guarded worker selection and application migrations; it never requires distribution or maintainer code. A fresh customer worker without platform history first uses the delivered forward-only data preparation primitive. Only this test application opts out of Laravel's additional per-case database suffixing; other parallel callbacks and process lifecycle options remain available. Owner `tools/maintainer/test-*` select private configurations under `src/runa/tests`, where platform migration preparation and fixture autoloading remain. Only owner E2E uses the synthetic platform harness; application E2E uses the real testing Nginx endpoint and keeps failure artifacts/reporting under `.runtime/artifacts/playwright/application`. Preserve the shared suite lock and native child exit status. Customer delivery excludes all platform tests/harnesses.

## Additional framework checks

Use single-server scheduling only when the deployment topology and task semantics require it; do not add it mechanically. Do not queue listeners solely because they can implement `ShouldQueue`.

For future HTTP integrations, framework tests fake outgoing HTTP and prevent accidental real network calls. The integration module still owns adapter/provider design; this is test safety, not a premature module architecture.

Use Laravel cache and filesystem abstractions where infrastructure changes should remain transparent to application behavior. Detailed cache/storage lifecycle stays with its owning feature/module.

## Project seeding

Use the shared `ManagedSeeder` / `SeedGraph` foundation and `docs/runa/handbook/data/seeding.md` for reference/demo/manual scenarios. Validate dependencies before execution and retain the direct development-environment guard. Keep scenario options in Laravel config and use each `SeedContext`'s independent generator; never seed global randomness or invent demo entities merely to populate the framework.

Database commands use `DatabaseScope`: native Artisan operations and `DatabaseSeeder` are app-only; owner `tools/maintainer` scripts use a private external bootstrap and no public scope selector. Use explicit application `seeding.register`, never folder discovery or delegation to Runa seeds. Ordinary startup performs no migrations/seeding. Explicit runa:dev:initialize prepares customer clones and pending local forward platform/application data after pulls through the narrow platform preparation primitive, without exposing owner reset/rollback/selectable-seeder commands. Development installation/initialization also prepares platform data in the base `runa_test` database before native application tests; native application migrations remain app-only; a fresh parallel worker may first receive technical platform preparation through the delivered primitive, never owner tools. Protect the separate `shared.migrations` / `shared.application_migrations` histories, table inventory, and isolated-test reset guards; see docs/runa/handbook/architecture/database.md.

## Module public event subscribers

Use ModuleEvents with source-owned EventProvider declarations rather than raw module Laravel listeners. Commit-dependent facts use the default connection's afterCommit callback; test the real outer transaction, savepoint rollback and subscriber/enqueue failure without origin failure. Async subscriptions reserve a declared JobDefinition, retain encrypted managed work, listener identity and a unique delivery key; worker admission rechecks the binding, toggle and both-side availability. Test real queue execution and subscriber-only reconciliation retries with stable idempotency keys. Callback publication is not a durable outbox and subscriber deduplication is not exactly-once external effects. Optional integration configuration uses ModuleIntegrations under registry generation/lock control; no management HTTP endpoint or fictional Audit implementation is supplied.

Supplied logging scopes propagate only safe correlation metadata to native queues; managed module work/event subscribers retain their persisted ownership, correlation and event/work IDs. Never serialize request payloads into diagnostic queue metadata. Console scopes include command names only. Test actual HTTP/controller construction, Artisan events, sync/database workers and failed-scope cleanup when changing this boundary; Laravel suppresses Symfony command event rerouting during unit tests, so explicitly enable it for the isolated integration scenario. See the [structured logging contract](../../../../docs/runa/handbook/architecture/module-framework.md#delivered-structured-logging-and-future-logs-read-boundary).

Use the [Auth preference/delivery contract](../../../../docs/runa/handbook/modules/auth/preferences.md) for source-declared account preferences, optimistic revisions, explicit safe guest adoption and email-only routing. Never store credentials/drafts in preferences, infer legacy ownership, bypass mandatory primary recipients or cache recipient decisions across jobs. Security mail uses reference-only managed work and revalidates state before its persisted single SMTP attempt; recovery retains direct mail.

Follow the [Auth policy/operator contract](../../../../docs/runa/handbook/modules/auth/policies-and-operators.md)
for atomic settings/history/Audit/proof consumption, bounded account exceptions,
preview-bound bulk revocation and trusted CLI initialization/recovery. Keep private
one-time material out of command arguments/output and logs; operator access is the
customer's infrastructure boundary. Do not grant a temporary administrator flag or
call guarded business gateways behind a settings/update fence.

Use the [Core operator/selection contract](../../../../docs/runa/handbook/modules/auth/policies-and-operators.md)
for actual first-administrator membership, resumable invitation admission, private
no-mail setup and Auth bulk selection. Use guarded PermissionsSelections at ordinary
ingress; the trusted operator adapter is local infrastructure access, not a public
authorization bypass. Preserve source/data/update fences and atomic Audit.

Use the [module development corpus contract](../../../../docs/runa/handbook/data/seeding.md#explicit-module-development-contributors)
for explicit owner-checked contributors and module-ID dependencies. Keep native
application production seeding separate, reject production even through direct
fixture calls, exchange public references and preserve credentials/provenance with
atomic Audit. Initial account passwords use hidden local prompts; never argv or
distributed defaults.

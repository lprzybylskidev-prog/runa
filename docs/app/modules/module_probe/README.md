# Temporary module probe

Development-only `app.module_probe` observes incremental framework delivery. It is independent of the retained reference module planned later. Production Docker contexts exclude this source; production discovery rejects accidental presence. Remove this module before final roadmap verification.

It supplies a strict identity manifest and an executable schema-version-1 `LifecyclePolicy` with explicit stateless/no-work preparation and compensation. It accepts null installed data because it owns no migrations or business data. It now declares a typed public Ping API and a safe operational GET endpoint; no business work or domain data is registered.

After forward platform migrations, run `php artisan runa:modules:sync app`. Inspect `ModuleRegistry::snapshot()` from a trusted development consumer: the probe is registered, source-present, disabled, source version `0.8.1`, installed version `null`. Repeat sync to observe an unchanged generation. Registry tests exercise missing/restored sources and preserve administrative/data versions.

The graph planner can now observe this probe without changing its disabled flag:

```php
$graph = new \Runa\Support\Modules\ModuleGraph($registry->snapshot(), $platformVersion);
$preview = $graph->preview('enable', 'app.module_probe');
```

The affected list is `['app.module_probe']`, proposed changes are `['app.module_probe' => true]`, and graph blockers are empty on a compatible platform. The probe remains disabled with no installed data; graph readiness is not operational readiness. Dependency/cycle/Core cascades use isolated test fixtures rather than adding fake production module requirements.

A trusted development consumer can now exercise the administrative lifecycle:

```php
$transitions = app(\Runa\Support\Modules\ModuleTransitions::class);
app(\Runa\Support\Modules\ModuleHealth::class)->diagnose('app.module_probe');
$enabled = $transitions->apply($transitions->preview('enable', 'app.module_probe'));
$disabled = $transitions->apply($transitions->preview('disable', 'app.module_probe'));
```

Both return `succeeded`; enable/disable preserve the null installed version and final disabled state. Snapshot generation advances for reservation/fence publication and atomic completion. Diagnose the probe before enabling it, as shown below. The probe now demonstrates administrative transitions and module-owned health; the guarded synchronous PHP/HTTP gateway is available. Failure, waiting, process-exit recovery, concurrency and Core rejection use isolated test fixtures. Do not unregister this probe during ordinary acceptance, as a tombstone requires explicit re-registration.

The source-owned `ProbeHealthPolicy` uses a critical `writable_runtime` check in hybrid mode (1-second timeout, 60-second interval, 120-second expiry). It returns only whether the runtime framework directory is writable. Its only permitted DB override is a supported health mode. Observe health through a trusted development consumer:

```php
$health = app(\Runa\Support\Modules\ModuleHealth::class);
$health->diagnose('app.module_probe'); // Also valid while disabled, before enable preview.
$availability = app(\Runa\Support\Modules\ModuleAvailability::class);
$status = $availability->statuses('app.module_probe', 'probe.read')['app.module_probe'];
```

While disabled, status preserves `enabled=false` and `available=false`. After explicit enable, the check is healthy and availability true on a writable runtime. Repeat public-operation evaluation to exercise hybrid per-use checks even with fresh observations, then explicitly disable again. `php artisan runa:modules:health app.module_probe` provides explicit CLI diagnosis; the no-argument command polls enabled scheduled/hybrid checks. The operational Ping API below exposes only a fixed message; no business data, Core registration or public health diagnostics are added. Mode overrides use `ModuleHealth::configure` with a current snapshot generation and never change enabled flags.

The probe can also observe the delivered storage/data boundaries from a trusted development consumer, without installing domain data:

```php
$identity = new \Runa\Support\Modules\ModuleIdentity('app.module_probe');
$disk = app(\Runa\Support\Modules\Storage\ModuleStorage::class)->disk($identity->id);
$path = 'diagnostics/'.bin2hex(random_bytes(8)).'.txt';
try {
    $disk->put($path, 'probe');
    $ok = $disk->get($path) === 'probe';
} finally {
    $disk->delete($path);
}
```

The file belongs under the application private disk's `modules/app/module_probe/` prefix. Delete only the diagnostic's unique file, preserving other content. Reject `../other/file`, absolute/stream paths and symlinks. The low-level disk remains accessible to trusted diagnostics while the probe is disabled; it is not an operational availability gate. ModuleDataPaths returns empty migration/seed lists for this stateless probe. Identity derives `app_module_probe`; this stateless probe creates no development schema. Before explicit installation its installed version is null. Real DDL, collision/FK/UUID constraints, storage escape and disable/unregister retention are exercised by isolated module fixtures. The explicit installation probe below now records installed version 0.9.0 without domain data; earlier null-version checks describe the pre-installation acceptance.

The probe's `0.2.0` source adds the exact `1.0.0` Ping contract, a readonly response, an owned typed proxy and implementation, and schema-version-1 PHP/HTTP descriptors. No migration is needed. A trusted ordinary application consumer can retain the proxy:

```php
$api = app(\Runa\Support\Modules\Api\ModuleApis::class)
    ->resolve('app.module_probe', \App\Modules\ModuleProbe\Contracts\Ping::class, '1.0.0');
```

While disabled, `$api->read()` throws `OperationRejected`. After diagnosis and successful explicit enable, it returns `PingResult(message: 'module_probe')`; GET `/api/app/module_probe/ping`, named `app.module_probe.probe.read`, returns `{"message":"module_probe"}` through an unwrapped JsonResource. The HTTP controller deliberately consumes its own typed gateway, demonstrating a nested public operation with separate health evaluation. It reveals no health, registry or management information. After explicit disable, the same retained proxy and direct URL reject with 503/`module_unavailable`. Always finish acceptance by disabling the probe. The API's null permission deliberately allows this fixed public message only when available; protected operation denial and injected authorization, critical expiry, transition fences, source drift and serialized routes are covered by isolated test fixtures.

The probe's `0.3.0` source adds `ProbeJobs`, an owned handler and module-owned work policy for `probe.work`. It has no business effect or domain data. `ModuleWork::dispatch('app.module_probe', 'probe.work', [])` rejects while disabled; after diagnosis/enable, the normal database worker executes one safe checkpoint and records success. Limits are three attempts, a five-second minimum retry delay and a thirty-second lease. The policy explicitly retries unavailable pending work and cancels requested work; its cancellation limitation is `safe_checkpoints_only`. Failed/crashed stateless work can be explicitly reconciled as cancelled.

The lifecycle policy now waits while active work remains. Dispatch a second job onto a uniquely named diagnostic queue, then request disable before running that worker: the transition stays pending with unchanged enabled=true and a fence/cancellation signal. The worker cancels through the probe policy without constructing the handler; resume the same transition to publish enabled=false. Observe safe status through `ModuleWork::status($id)` and preview active-work counts. Never run the acceptance worker against an unrelated queue. Finish with disabled state, the retained installed version and no fence; remove only the exact task-created terminal work rows after retaining verification evidence. No schema/storage data or Core module is installed.

The probe now declares classified CLI commands and an hourly UTC schedule, discovered without per-module bootstrap edits. After `php artisan runa:modules:sync app`, `php artisan app:module_probe:inspect` returns a fixed diagnostic while disabled; `php artisan app:module_probe:work` refuses while disabled and returns a managed work ID only when explicitly enabled/healthy. Inspect that ID through ModuleWork status; CLI exit 0 reports enqueue, not completed work. `php artisan schedule:list` includes `module:app.module_probe:work`; its two-minute overlap expiry covers enqueue only. Worker execution still rechecks state and uses the existing probe work policy. Leave the probe disabled after manual acceptance. Production exclusion and final probe removal remain unchanged.

The probe's `0.5.0` source: executing `probe.work` now publishes the effect-free `observed` v1 fact after its safe checkpoint. ProbeEvents declares independent owned sync and async subscribers; the latter runs reserved managed job `probe.event` and records the safe checkpoint `probe_fact_observed`. Consume its public envelope with PublicEvent and use the WorkContext delivery key for an idempotent business subscriber. Direct generic dispatch of `probe.event` is refused; it requires its event binding. Normal application jobs retain their native behavior.

To accept in development, synchronize source, explicitly enable the healthy probe, dispatch `probe.work` on a dedicated temporary queue and consume both work envelopes. Inspect both terminal work IDs/statuses, then disable the probe and remove only those identified terminal test rows after checking no dedicated queue envelopes remain. No domain data or external effects are created. Optional integration metadata/toggle/both-side availability and Admin-shaped required Audit behavior use isolated fixtures, without adding another probe or registering a real Audit module. Leave development services, other work/data and verification evidence intact.

The probe's `0.6.0` source adds `ProbeSettings`: a localized immediate `label` (default `probe`) and nullable sensitive `credential` using reinitialization. Its handler explicitly has no work/effects and safely accepts/compensates. `php artisan runa:modules:settings app.module_probe` exposes defaults, revisions and credential set-state only. Pipe a JSON values envelope with the read revision/generation into the same command's `--write` mode; never pass a secret as an argument. Observe the updated label/set-state, then restore label `probe` and clear credential with null. Both writes preserve the disabled flag and null installed version; no domain data/schema is created. Isolated tests exercise real process interruption, safe change hooks, stale revisions, key failure and failed restoration/recovery fences. Production exclusion is unchanged.

Version `0.7.0` added `ProbeManagement` with localized `probe.inspect` permission and an `inspect` action descriptor. Run `php artisan runa:modules:sync app` and `php artisan runa:modules:management app.module_probe` while disabled: output combines safe state/root causes, settings revision and credential set-state, integration/work projections and future management descriptors. The command executes no action and requires no Admin/Auth mock. The probe declares no custom Vue component until an actual build mapping exists. Source synchronization preserves disabled state, settings and the retained installed version. Tests cover owned component keys, unsupported renderer versions/types, duplicate descriptors, permission references, bounded active work and concurrent generation changes.

## Frontend projection probe

Source 0.8.0 adds ProbeFrontend page/slot declarations and an effect-free local `frontend/components/panel.vue`. The public metadata endpoint returns `/modules/app/module_probe/panel`, named `app.module_probe.pages.panel`; disabled navigation is hidden and no slot contribution is active. After explicit health diagnosis/enable, the page and its single contribution become available; disabling hides/unmounts them again. The default welcome entry does not opt into module rendering. The isolated owner browser tests exercise the virtual registry and adapters; a customer entry that wants pages must explicitly compose ModulePage/ModuleSlot. Production build mapping excludes this probe, and production packaging still omits its source. Do not add a preview route or alter the application's welcome page to demonstrate it.

## Structured logging probe

Source 0.8.1 adds static diagnostic records to the existing Ping API, inspect command, managed work and independent sync/queued event subscribers. No contract or payload changes. After source synchronization, run `php artisan app:module_probe:inspect` while disabled to observe `extra.origin=app.module_probe`, command/operation/correlation identity and `code=probe_diagnostic` in both JSON stderr and the fixed private rotating log family. Existing explicit diagnosis/enable/HTTP/work/event acceptance observes request/work/event IDs without logging input/settings. Finish with the probe disabled; this patch creates no domain data, new files outside the technical log family, or additional module registrations. Production exclusion and final probe removal remain mandatory.

## Explicit installation probe

Source 0.9.0 declares pure ProbeUpgrades metadata and a stateless InitializeProbe handler. The null-to-0.9.0 initialization and 0.8.1-to-0.9.0 update edges write no domain data; the framework alone records transactional completion and installed version. Run ModuleUpdates::plan('app') from a trusted development consumer to see the pending path without registration changes. Explicitly apply('app', $exactSourceCommit, $journalProgress) under the deployment's writer/maintenance protocol, then repeat: the completed step must not execute again. This invocation is application-owned; forward Runa preparation never installs the probe. Current accepted state is disabled, installed version 0.9.0, no module-domain schema and no active fence. Prior null-installed-version examples describe acceptance before this explicit installation. The probe and its completion provenance remain disposable and are removed under the final probe-cleanup point with the normal ownership safeguards.

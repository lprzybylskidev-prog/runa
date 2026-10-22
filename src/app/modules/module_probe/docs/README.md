# Temporary module probe

Development-only `app.module_probe` observes incremental framework delivery. It is independent of the retained reference module planned later. Production Docker contexts exclude this source; production discovery rejects accidental presence. Remove this module before final roadmap verification.

It supplies a strict identity manifest and an executable schema-version-1 `LifecyclePolicy` with explicit stateless/no-work preparation and compensation. It accepts null installed data because it owns no migrations or business data. It now declares a typed public Ping API and a safe operational GET endpoint; no business work or domain data is registered.

After forward platform migrations, run `php artisan runa:modules:sync app`. Inspect `ModuleRegistry::snapshot()` from a trusted development consumer: the probe is registered, source-present, disabled, source version `0.2.0`, installed version `null`. Repeat sync to observe an unchanged generation. Registry tests exercise missing/restored sources and preserve administrative/data versions.

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

The file belongs under the application private disk's `modules/app/module_probe/` prefix. Delete only the diagnostic's unique file, preserving other content. Reject `../other/file`, absolute/stream paths and symlinks. The low-level disk remains accessible to trusted diagnostics while the probe is disabled; it is not an operational availability gate. ModuleDataPaths returns empty migration/seed lists for this stateless probe. Identity derives `app_module_probe`, but no development schema or installed version is created. Real DDL, collision/FK/UUID constraints, storage escape and disable/unregister retention are exercised by isolated module fixtures. Module-aware install/update orchestration remains planned.

The probe's `0.2.0` source adds the exact `1.0.0` Ping contract, a readonly response, an owned typed proxy and implementation, and schema-version-1 PHP/HTTP descriptors. No migration is needed. A trusted ordinary application consumer can retain the proxy:

```php
$api = app(\Runa\Support\Modules\Api\ModuleApis::class)
    ->resolve('app.module_probe', \App\Modules\ModuleProbe\Contracts\Ping::class, '1.0.0');
```

While disabled, `$api->read()` throws `OperationRejected`. After diagnosis and successful explicit enable, it returns `PingResult(message: 'module_probe')`; GET `/api/app/module_probe/ping`, named `app.module_probe.probe.read`, returns `{"message":"module_probe"}` through an unwrapped JsonResource. The HTTP controller deliberately consumes its own typed gateway, demonstrating a nested public operation with separate health evaluation. It reveals no health, registry or management information. After explicit disable, the same retained proxy and direct URL reject with 503/`module_unavailable`. Always finish acceptance by disabling the probe. The API's null permission deliberately allows this fixed public message only when available; protected operation denial and injected authorization, critical expiry, transition fences, source drift and serialized routes are covered by isolated test fixtures.

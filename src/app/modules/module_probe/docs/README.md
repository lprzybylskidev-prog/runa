# Temporary module probe

Development-only `app.module_probe` observes incremental framework delivery. It is independent of the retained reference module planned later. Production Docker contexts exclude this source; production discovery rejects accidental presence. Remove this module before final roadmap verification.

It supplies a strict identity manifest and an executable schema-version-1 `LifecyclePolicy` with explicit stateless/no-work preparation and compensation. It accepts null installed data because it owns no migrations or business data. No provider, operational entrypoint or business work is registered.

After forward platform migrations, run `php artisan runa:modules:sync app`. Inspect `ModuleRegistry::snapshot()` from a trusted development consumer: the probe is registered, source-present, disabled, source version `0.1.0`, installed version `null`. Repeat sync to observe an unchanged generation. Registry tests exercise missing/restored sources and preserve administrative/data versions.

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

Both return `succeeded`; enable/disable preserve the null installed version and final disabled state. Snapshot generation advances for reservation/fence publication and atomic completion. Diagnose the probe before enabling it, as shown below. The probe now demonstrates administrative transitions and module-owned health; operational gateways remain planned. Failure, waiting, process-exit recovery, concurrency and Core rejection use isolated test fixtures. Do not unregister this probe during ordinary acceptance, as a tombstone requires explicit re-registration.

The source-owned `ProbeHealthPolicy` uses a critical `writable_runtime` check in hybrid mode (1-second timeout, 60-second interval, 120-second expiry). It returns only whether the runtime framework directory is writable. Its only permitted DB override is a supported health mode. Observe health through a trusted development consumer:

```php
$health = app(\Runa\Support\Modules\ModuleHealth::class);
$health->diagnose('app.module_probe'); // Also valid while disabled, before enable preview.
$availability = app(\Runa\Support\Modules\ModuleAvailability::class);
$status = $availability->statuses('app.module_probe', 'probe.read')['app.module_probe'];
```

While disabled, status preserves `enabled=false` and `available=false`. After explicit enable, the check is healthy and availability true on a writable runtime. Repeat public-operation evaluation to exercise hybrid per-use checks even with fresh observations, then explicitly disable again. `php artisan runa:modules:health app.module_probe` provides explicit CLI diagnosis; the no-argument command polls enabled scheduled/hybrid checks. No operational API, business storage, Core registration or public diagnostic endpoint is added. Mode overrides use `ModuleHealth::configure` with a current snapshot generation and never change enabled flags.

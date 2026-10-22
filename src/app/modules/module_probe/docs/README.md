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
$enabled = $transitions->apply($transitions->preview('enable', 'app.module_probe'));
$disabled = $transitions->apply($transitions->preview('disable', 'app.module_probe'));
```

Both return `succeeded`; enable/disable preserve the null installed version and final disabled state. Snapshot generation advances for reservation/fence publication and atomic completion. This demonstrates administrative transitions only; health checks and operational gateways remain planned. Failure, waiting, process-exit recovery, concurrency and Core rejection use isolated test fixtures. Do not unregister this probe during ordinary acceptance, as a tombstone requires explicit re-registration.

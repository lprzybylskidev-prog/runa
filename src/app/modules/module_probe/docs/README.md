# Temporary module probe

Development-only `app.module_probe` observes incremental framework delivery. It is independent of the retained reference module planned later. Production Docker contexts exclude this source; production discovery rejects accidental presence. Remove this module before final roadmap verification.

Currently it supplies a strict identity manifest and an inert lifecycle descriptor source. The executable lifecycle interface arrives in its own roadmap point; no provider, operational entrypoint, migration or business work is registered yet.

After forward platform migrations, run `php artisan runa:modules:sync app`. Inspect `ModuleRegistry::snapshot()` from a trusted development consumer: the probe is registered, source-present, disabled, source version `0.1.0`, installed version `null`. Repeat sync to observe an unchanged generation. Registry tests exercise missing/restored sources and preserve administrative/data versions.

The graph planner can now observe this probe without changing its disabled flag:

```php
$graph = new \Runa\Support\Modules\ModuleGraph($registry->snapshot(), $platformVersion);
$preview = $graph->preview('enable', 'app.module_probe');
```

The affected list is `['app.module_probe']`, proposed changes are `['app.module_probe' => true]`, and graph blockers are empty on a compatible platform. The probe remains disabled with no installed data; graph readiness is not operational readiness. Dependency/cycle/Core cascades use isolated test fixtures rather than adding fake production module requirements.

# Lifecycle, Health, Logging, Resources, and Devcontainer

## Entrypoint/lifecycle

1. Keep entrypoints small and predictable.
2. Do not hide hundreds of lines of installer/migration/build/chmod behavior in runtime entrypoint.
3. Container start should not perform heavy/destructive bootstrap automatically.
4. Use `exec` for the final foreground process in shell entrypoints so signals reach PID 1 correctly.
5. Services must handle SIGTERM/graceful shutdown correctly.
6. Choose graceful-stop behavior according to process type; queue workers may need job completion semantics.
7. Do not use meaningless infinite shell loops only to keep a container alive.
8. Health checks diagnose; they do not repair, migrate, restart, or clear cache.

## Health/diagnostics

9. Health checks should test a real ability to serve/function, not merely that a PID exists.
10. Health checks remain lightweight; do not run full doctor/migrations/heavy queries/external-provider fan-out.
11. Distinguish liveness vs readiness semantics even though Compose is simpler than Kubernetes.
12. An app health check must not create a cascade of external provider requests.
13. Diagnose Docker in a stable order:
    - resolved Compose config,
    - container state,
    - logs,
    - health,
    - network/DNS,
    - mounts/permissions,
    - image/build,
    - host resources.
14. Inspect `docker compose config` before guessing how env/overrides resolved.
15. Do not bypass architecture to "fix" a service (for example, do not replace broken Nginx/PHP-FPM with `artisan serve`).

## Logging

16. Container processes primarily log to stdout/stderr.
17. Do not store huge logs in ephemeral container filesystems.
18. Configure practical log growth/rotation for production Docker logging.
19. Aggregated project logs should make service/process origin obvious.

## Resources

20. Do not hardcode magical production CPU/memory values; production values are environment/workload specific.
21. OOM is a diagnostic signal, not an instruction to double the limit without investigation.
22. Worker concurrency must make sense relative to CPU/memory allocation.
23. Heavy development tooling such as embeddings/Qdrant/tests should not starve the app/database unnecessarily.

## Devcontainer

24. Devcontainer builds must be deterministic/reproducible within pinned dependencies.
25. Rebuilding the devcontainer must not destroy persistent data the developer expects to keep.
26. Developer-only tools belong in the dev image, not production.
27. Development may have more diagnostic tools than production, but application runtime requirements maintain parity.
28. Post-create/setup scripts must be idempotent.
29. Distinguish first bootstrap from normal reopen; do not repeat expensive setup needlessly.
30. Never run `migrate:fresh` automatically on each rebuild/reopen.
31. Initialize data only through explicit acquisition/clone preparation/deploy. Ordinary reopen/setup never migrates or seeds; runa:dev:initialize prepares local forward platform/application data, preserves credentials/storage and requires a customer checkout.
32. Do not require private absolute host paths; use ignored local overrides.
33. GPU support for embeddings is optional and CPU-fallback-safe.
34. Do not build multi-arch images before a real platform need.
35. If architecture/platform becomes relevant, declare it explicitly.
36. Do not introduce Docker Bake before build complexity justifies it.
37. Use one canonical build path so developer/deploy/test do not use three divergent undocumented flag sets.

## Host transitions and selective work

Use native Linux Docker Engine/Compose and preserve `.:/workspace`; never infer a private host checkout from `/workspace`. Keep the application toolchain in project containers. Small host tasks need ordered commands, effects/expected output and reviewed user results. Before substantial host work, persist the active roadmap's `Execution handoff` under `docs/runa/handbook/workflows/roadmaps.md`; follow `docs/runa/handbook/development/editor-and-agents.md` for transition, startup and return checks. Do not automate VS Code switching or claim unobserved GUI acceptance.

Inspect actual project/container state after editor transitions; current shutdown behavior is recorded in the editor guide. `shutdownAction: none` keeps services running until explicit host `./bin/artisan runa:dev:down`. Prefer selective restart for process changes; restart does not apply a new image/configuration. Use `./bin/artisan runa:dev:build <service>` and explicit `./bin/artisan runa:dev:recreate <service>` for selected image/configuration work; recreation uses `--no-deps` and requires prerequisite/health review. Review dependencies for selective recreation/build and use the wrapper's explicit Compose project/files. Capture app ID/start time before and after sibling-only work. Whole-project down/build and attached-app recreation require deliberate scope/session handling. Do not reset persistent data to repair ordinary reopen.

Runa application readiness excludes optional AI services. Keep Qdrant/CPU/CUDA startup and restartable first-index initialization independent of setup/post-create; do not add health/completion edges back into application dependencies. Host `./bin/artisan runa:dev:up` waits for application services before starting optional AI without a health wait. See `docs/runa/handbook/development/knowledge.md` for retries, explicit stale-index repair and strict AI acceptance.

Environment creators/readers, template names and safe credential reconciliation are documented in docs/runa/handbook/development/environment-files.md. Preserve private backup configuration; no implicit credential rotation or volume reset. Development preparation checks both application and test credentials against initialization inputs.

Python operational/source and development-tool boundaries follow
[Python tooling](../../../../docs/runa/handbook/development/python-tooling.md).
Keep the host stdlib-only and generated development state under `.runtime/python`;
package preparation must not add Python tooling to production images.

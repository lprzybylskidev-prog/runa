# Docker Build and Compose

## Image build

1. Use multi-stage production builds when they keep build dependencies out of runtime.
2. Production runtime images contain only runtime needs, not compilers/test tooling/Playwright/pnpm cache/Git.
3. Keep build context minimal with deliberate `.dockerignore`.
4. Do not `COPY .` before dependency layers when that destroys useful cache behavior.
5. Use BuildKit cache mounts for expensive Composer/pnpm/apt caches when they materially help.
6. Prefer `COPY` over `ADD` unless a specific `ADD` feature is intentionally needed.
7. Do not download random runtime artifacts at container startup; build a complete reproducible image.
8. Builds must not depend on accidental host state.
9. Do not pass persistent build secrets via normal `ARG`/`ENV` where image history/layers can expose them.
10. Use BuildKit secret/SSH mechanisms for genuine private build access and ensure secrets do not survive into final stage.
11. Production runs non-root where practical.
12. UID/GID handling in development must avoid root-owned bind-mounted files.
13. Never solve permissions with `chmod 777`.
14. Declare/prepare only required writable runtime paths.
15. Consider read-only production filesystem outside required mounts/tmp when it fits, but do not break Laravel/runtime behavior merely to satisfy a hardening slogan.
16. Choose minimal base images pragmatically; do not force Alpine/distroless when compatibility/debuggability suffers.
17. Do not optimize image MB at the expense of maintainability.
18. A production image must build correctly from a clean state; cache accelerates but is not required for correctness.

Module builds copy the Vite discovery plugin and both owners' module roots into the frontend stage, and module PHP source into the dependency/runtime stages. Keep empty source roots tracked; development reference/probe and test exclusions apply before COPY. Verify actual isolated customer builds rather than relying on a workspace frontend build.

## Compose

19. `depends_on` is not readiness. Use health-based conditions or job-completion semantics where dependency readiness matters.
20. Container-to-container traffic uses service DNS name and container port, not host published port or hardcoded IP.
21. Publish host ports only when the host/user genuinely needs them.
22. Keep internal services internal; do not expose every service to localhost.
23. Never hardcode container IP addresses.
24. Do not create multiple Docker networks without a real isolation/architecture need.
25. `internal: true` networks may be used deliberately for truly internal tiers.
26. Use named volumes for persistent service data and bind mounts for source/config in development.
27. Mount immutable config/input read-only when the service should not modify it.
28. Treat `docker compose down -v` as destructive.
29. Project scripts must never delete the database volume without a clearly destructive command and confirmation.
30. Keep service/volume/network naming stable enough for project scripts/operations.
31. Control Compose project name when needed to prevent checkout/environment collisions.
32. Avoid `container_name` unless there is a concrete need; service DNS is normally enough.
33. Choose restart policy by process type. Long-running services and one-shot jobs have different semantics.
34. One-shot init/migration jobs exit 0/non-zero clearly.
35. Do not allow multiple app replicas/processes to race the same migration step; deploy has one controlled migration point.

Customer container/devcontainer definitions are editable scaffold. External development updates review affected names separately from src/runa integrity, apply only exact approved eligible file replacements/removals and preserve customer instruction extensions for manual reconciliation. Use docs/runa/handbook/release/platform-reconciliation.md for target manifest/lock, env, image/recreation and team/deploy ordering. Generated build output stays outside src/runa; private error shells use .runtime/frontend/errors/templates, copied into production images outside persistent storage mounts.

Use the single workspace-root .runtime tree for private generated build/tooling
state; see [artifact placement](../../../../docs/runa/handbook/architecture/project-principles.md#generated-tooling-and-build-state). Resolve paths consistently
from src and build stages. Keep only required generated runtime assets in explicit
production copies; preserve unrelated caches/reports/backups/operation state.

When reviewing security updates for compiled helper binaries, inspect their
embedded compiler/standard-library and module versions as well as the helper
release number. Build replacements from pinned, integrity-verified sources with
patched toolchains when explicitly authorized; keep compilers out of runtime.
Follow [PostgreSQL runtime security](../../../../docs/runa/handbook/security/postgres-gosu-policy.md)
for the supplied image, preserving database data and the documented entrypoint.

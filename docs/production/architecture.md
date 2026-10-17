# Production Architecture

## Goal

Production baseline is complete but deliberately simple. Do not pre-build enterprise infrastructure that the application has not earned.

No Kubernetes, blue/green platform, ELK stack, Redis/Horizon, or similar infrastructure is part of the baseline unless a future roadmap introduces a real need.

## Runtime services

Baseline production services:

- application/PHP-FPM,
- Nginx,
- PostgreSQL,
- queue worker,
- scheduler.

Outbound mail uses a configurable SMTP/provider path.

Application persistent storage is mounted appropriately.

Nginx serves static/public assets and forwards PHP execution to PHP-FPM with path semantics matching development.

## AI/RAG isolation

Production must not contain or run:
- Qdrant,
- embedding services,
- knowledge/RAG runtime dependencies,
- development AI tools,
- project knowledge CLI.

Enforce isolation at multiple layers:

1. production Compose has no AI services,
2. production environment hard-disables knowledge CLI,
3. production image/build excludes AI/dev files where not needed,
4. production checkout uses sparse checkout to avoid `.agents`, `.devcontainer`, AI/RAG docs/services and other development-only material,
5. production installer/self-test verifies no AI services/ports/volumes/knowledge CLI/AI directories are present.

Production scripts explicitly use production Compose/project context and never accidentally use development Compose.

## Runtime parity

Capabilities required by application code must exist compatibly in both development and production.

Dev-only tools such as Xdebug, local RAG, Qdrant, embedding service, Mailpit, and development diagnostics are intentionally absent.

## Queue

A separate worker runs from day one.

Initial driver: database.

Redis/Horizon is not introduced until a real use case exists.

Jobs should be idempotent where possible, with retry/backoff behavior defined per job/use case.

## Scheduler

A dedicated scheduler process/container runs the current supported Laravel scheduler mechanism.

Do not depend on host cron for normal application scheduling.

## Cache

Use standard Laravel cache behavior initially.

Do not add Redis merely for caching before a concrete need.

## Logging

Application logs use standard Laravel logging and stdout/stderr integration appropriate for containers.

Do not log sensitive payloads/secrets.

Provide `./bin/logs` as the canonical operator-facing aggregation helper.

Host/container logging must have practical rotation/growth control.

## Health

Production exposes a minimal safe health endpoint.

Distinguish:
- process/liveness concerns,
- application readiness,
- deep operator diagnostics.

The public/basic health endpoint must not leak configuration, secrets, stack traces, database details, or sensitive dependency information.

Deep diagnostics belong to operator tools/doctor, not an internet-facing endpoint.

## Maintenance mode

Use Laravel maintenance mode only when a deployment/operation genuinely requires it. Do not make it the automatic answer to every deploy.

## Security posture

Production containers follow Docker/security rules:
- non-root where practical,
- minimal required privileges,
- no Docker socket,
- no unnecessary ports,
- no development tooling,
- controlled writable paths,
- secret/config handling outside images.

See `.agents/skills/docker/` and `.agents/skills/security/`.

## Storage

Durable application files live in explicit persistent volumes, outside the image layer, separately from temporary/cache data. Maintain compatible path semantics in dev and production. Public storage links/static serving are prepared idempotently when needed; private file-module policy is deferred. Database and durable storage are backed up separately with coordinated restore metadata.

## Background and mail runtime foundation

Point 6 pairs development worker/scheduler definitions with production services using the same built PHP runtime image, separate processes, validated configuration, graceful shutdown and instance-aware health signals. All three PHP services share application storage; SMTP settings come from the production env file. There is no Mailpit service in production. Production SMTP must use TLS and an actual configured relay/provider host; no provider package is required for standard SMTP.

The production deployment point owns migrations before worker/scheduler startup, process replacement after code updates, and the complete operational self-test. Production containers do not run the development setup job. Compose definitions at this stage are a runtime foundation, not a finished installer/deployment procedure.

Production retains root `composer.json` as application namespace metadata required by Laravel command discovery, without installing the Composer binary. Build inputs exclude local storage, runtime heartbeats and generated bootstrap caches. The image creates empty non-root-owned writable storage directories; application data enters only through the dedicated persistent volume. This avoids baking development captures/uploads or stale development package manifests into a release.

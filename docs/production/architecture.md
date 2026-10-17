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

## Production container contract

`compose.production.yaml` defines the five production services under the separate `runa-prod` project. PHP-FPM, queue and scheduler share the same exact-tag image and application-storage volume. Their root filesystems are read-only; only `/tmp`, per-container bootstrap caches and persistent storage are writable. All application services run as `runa` (UID/GID 1000), drop capabilities and forbid privilege escalation. Nginx runs as `nginx`, mounts only the `app/public` volume subdirectory read-only (with supplemental GID 1000 for public-file read access), and exposes it through `/storage/`. PostgreSQL retains its official entrypoint privileges for initial volume ownership and switching to its database user; it has no published port.

Every service has bounded JSON-file log rotation (10 MB × 3), a memory/CPU limit, restart policy and graceful-stop period. Laravel logs go to stderr. Production Nginx explicitly rewrites the root URI to its FastCGI location because `index.php` exists only in the PHP image, not the static image. Production Nginx logs omit query strings, uses a same-origin CSP, denies dotfiles and arbitrary PHP execution, and caches hashed build assets. No HSTS is emitted on this internal HTTP listener: HTTPS termination and HSTS belong to the host's configured TLS reverse proxy. Only loopback port 8081 is published by default, separate from development port 8080. External TLS/domain setup is an operator prerequisite, not an included proxy platform.

The PHP production dependency stage uses the dedicated production runtime and Composer alone; it does not depend on the development toolchain/Codex stage. Runtime copies are allowlisted, `.dockerignore` excludes secret/local/generated/development inputs, and both production images consume the same locked frontend build. Runtime does not contain Composer, Node, pnpm, Python, Git, Xdebug, Boost, test runners or knowledge tooling. Development build targets retain their existing tooling; no runtime/toolchain versions changed.

The production entrypoint initializes missing storage directories without deleting data, validates runtime configuration, creates a per-container configuration cache, and execs the selected process. No migration or seeding runs implicitly at startup. Migrations and production seeding must finish before queue/scheduler startup; deployment automation is described in docs/production/deployment.md. Storage/cache contents are never supplied from the developer checkout or embedded in images. Recreate containers after environment changes; a simple restart does not reload Compose environment definitions.

Health layers:

- PHP-FPM's FastCGI ping checks process liveness.
- Named `GET /api/health` checks application boot and returns only `status: ok`, independently of the database.
- Named `GET /api/ready` verifies a read against the required `shared.jobs` table, returning only `status: ok` or HTTP 503 with `status: unavailable` and no database details. It disables response caching. Nginx's production healthcheck uses this path, so a listening process before migrations is not a ready application.
- Queue/scheduler use instance-aware heartbeat plus database checks. Deep diagnostics remain operator commands; public readiness does not run seeding, SMTP delivery, scanners or repair operations.

`python3 tools/testing/production.py` checks topology and production command safety without Docker access. Actual build/image/stack checks require the host's Docker daemon; source tests do not prove container startup. Point 14 supplies deployment/self-test/isolation automation and point 15 verifies the complete fresh-host lifecycle.

Production requires Docker Engine 26 or newer for volume subpath mounts, plus Docker Compose 2.35 or newer. The Nginx container cannot access private application storage through its public-file mount. Volume subpaths must already exist; application health is the startup dependency after the PHP entrypoint initializes storage.

## Approved PHP Alpine preparation; acceptance pending

The production-only PHP runtime now uses official `php:8.4.26-fpm-alpine3.24` at index digest `sha256:78cd8de9970a9cd6ff4d98860a94eb5bf37dd2d5776b4785562dbfad01c29d5a`. Development retains the existing Debian/browser toolchain. The production dependency stage installs Composer/unzip on Alpine; neither enters the final image. Required PDO PostgreSQL, mbstring, intl/full ICU data, bcmath, OPcache, pcntl, ZIP, cURL and XML capabilities remain mandatory. UID/GID, FPM configuration, read-only mounts, startup validation, storage layout and health/worker/scheduler behavior keep their established contracts.

The upstream Alpine PHP image uses GNU iconv rather than musl iconv. Final-image probes explicitly check Polish iconv/Laravel transliteration, multibyte text and ICU ordering; decimal/XML/ZIP/HTTPS/process support; required extensions, exact PHP version, UTC/non-root identity, assets and absence of development material. They also check FPM configuration and the actual tar/find options used for storage backup/restore. These probes are executed by the existing production build/image/self-test path; their source fixture remains operator tooling and is never copied into runtime images.

The upstream base reported zero HIGH/CRITICAL findings, but this is not acceptance of the built Runa image. Actual Docker build, final-image probes, HTTP/background runtime, backup/restore, application/browser checks and final-image Trivy results remain required. No Docker-host evidence for the newly built Alpine PHP image has yet been supplied. PostgreSQL remains on its previous runtime configuration pending the separate [gosu](../security/gosu-assessment.md) and [locale](postgresql-alpine-assessment.md) decision.

Host verification of the Alpine candidate found GNU libiconv 1.18 transliterates `Zażółć gęślą jaźń` as `Zaz'ol'c ge'sla ja'z'n` under all four tested locales; glibc's iconv produces `Zazolc gesla jazn` under the development UTF-8 locale. Laravel `Str::ascii(..., 'pl')` consistently returns `Zazolc gesla jazn`. Application source currently has no direct iconv/transliteration calls. The operator probe now asserts the exact reviewed implementation-specific iconv output plus a lossless UTF-8/UTF-16LE round trip, and keeps the exact application-facing Laravel assertion. Unknown implementations still fail. Do not use native iconv transliteration output as a cross-runtime identifier/slug contract; establish explicit application behavior when such a feature is introduced. This is a documented native-library difference, not an application behavior regression or final image acceptance.

Host acceptance (2026-10-17) for release commit `2edfa91f12f5fb21ed0449d8b91b4855f0175b31` passed on final PHP image `sha256:595408a424cc00bbe1c08201393d365ea7852f83c55e59f6849bbacd1f15cfa4`: build, all capability probes (ICU 78.1), FPM configuration, actual application/background health, root/readiness/isolation, coordinated backup, isolated database restore verification and database/storage restore followed by healthy services. Trivy 0.76.0 final-image vulnerability/secret scan reported zero HIGH/CRITICAL findings for PHP and Nginx; repository and dependency audits passed. The unmodified PostgreSQL Bookworm image still fails the combined security gate. This is PHP candidate acceptance evidence, not point-14 completion or a full final-gate PASS; no PostgreSQL exception was applied. Reports/logs are local artifacts under `.runtime/security/` and `.runtime/php-alpine-host.log`.

# Production Architecture

## Goal

Production baseline is complete but deliberately simple. Do not pre-build enterprise infrastructure that the application has not earned.

No Kubernetes, blue/green platform, ELK stack, Redis/Horizon, or similar infrastructure is part of the baseline unless a future roadmap introduces a real need.

## Responsibility boundary

Runa supplies a minimal working production baseline with application security and
supported installation/deployment operations. The customer operates and protects
the host, infrastructure and access to operator commands, including SSH/Docker/CLI
permissions, network exposure, TLS termination and externally managed backups.
Additional infrastructure hardening is customer-owned unless explicitly agreed.

Authorized access to execute a dedicated operator command is a trusted boundary.
An Auth emergency-recovery command does not need an additional recovery container,
separate database credential or infrastructure authority service solely to prove
that the operator may execute it. Application authorization, input validation,
secret-safe handling and Audit remain required. This boundary does not remove the
supplied container/runtime safeguards described below. The recovery command's
behavior and acceptance remain defined by its owning Auth specification; this
responsibility rule does not claim the command is already delivered.

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
4. the production host checkout contains the same complete customer revision as development, with common owner-only exclusions; versioned development configuration/knowledge source is not started or copied into runtime images,
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

Application logs use Laravel/Monolog structured JSON to stderr and the fixed private `storage/logs/runa-YYYY-MM-DD.log` family (14 rotating files, `0640`, append locking). See the [logging and future Logs read contract](../architecture/module-framework.md#delivered-structured-logging-and-future-logs-read-boundary) for origin/correlation, redaction, retention and backup policy. Daily file bytes need operator capacity monitoring/quota; the file-count limit does not impose a byte quota.

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

Durable application files live in explicit persistent volumes, outside the image layer, separately from temporary/cache data. Maintain compatible path semantics in dev and production. Public storage links/static serving are prepared idempotently when needed; private file-module policy is deferred. Database and durable storage backup/recovery are externally managed by the customer; no bundled backup subsystem is required.

## Background and mail runtime foundation

The runtime pairs development worker/scheduler definitions with production services using the same built PHP runtime image, separate processes, validated configuration, graceful shutdown and instance-aware health signals. All three PHP services share application storage; SMTP settings come from the production env file. There is no Mailpit service in production. Production SMTP must use TLS and an actual configured relay/provider host; no provider package is required for standard SMTP.

Production retains `src/composer.json` as application namespace metadata required by Laravel command discovery, without installing the Composer binary. Build inputs exclude local storage, runtime heartbeats and generated bootstrap caches. The image creates empty non-root-owned writable storage directories; application data enters only through the dedicated persistent volume. This avoids baking development captures/uploads or stale development package manifests into a release.

## Production container contract

PHP development and production/management use official PHP 8.4.27 images pinned
by digest. Production installs corrected curl/libcurl and Brotli packages.
PostgreSQL 18.6 uses the supplied derivative with corrected libraries and gosu
1.20 rebuilt with Go 1.27.3; see [runtime security](../security/postgres-gosu-policy.md).
There are no active scanner exceptions for the retired PHP/gosu/Brotli findings.
Rebuild images and recreate services to adopt changes; restarting an existing
container does not update its runtime. Development uses
`./bin/artisan runa:dev:build app postgres` followed by selective recreation of
app/queue/scheduler and PostgreSQL, preserving data volumes. Recreate active testing
profile endpoints through their explicit Compose context as well. Run idempotent
setup inside the replaced app (`python3 -m tools.application.development setup
--if-needed`) to restore container-local Boost MCP registration. Production uses
normal tested customer deployment and exact-SHA PHP/Nginx/PostgreSQL images;
preserve database/storage volumes and follow [platform reconciliation](../release/platform-reconciliation.md).
A successful test run does not establish the cause of earlier native crashes.

`compose.production.yaml` defines the five production services under the separate `runa-prod` project. PHP-FPM, queue and scheduler share the same exact-tag image and application-storage volume. Their root filesystems are read-only; only `/tmp`, per-container bootstrap caches and persistent storage are writable. All application services run as `runa` (UID/GID 1000), drop capabilities and forbid privilege escalation. Nginx runs as `nginx`, mounts only the `app/public` volume subdirectory read-only (with supplemental GID 1000 for public-file read access), and exposes it through `/storage/`. PostgreSQL keeps the official entrypoint and runs as postgres (UID/GID 70) from startup; its managed named volume initializes without root. Custom bind mounts require operator-prepared ownership. It has no published port.

Every service has bounded JSON-file log rotation (10 MB × 3), a memory/CPU limit, restart policy and graceful-stop period. Laravel JSON logs go to stderr and the bounded private rotating file family on application storage. Production Nginx explicitly rewrites the root URI to its FastCGI location because `index.php` exists only in the PHP image, not the static image. Production Nginx logs omit query strings, uses a same-origin CSP, denies dotfiles and arbitrary PHP execution, and caches hashed build assets. No HSTS is emitted on this internal HTTP listener: HTTPS termination and HSTS belong to the host's configured TLS reverse proxy. Only loopback port 8081 is published by default, separate from development port 8080. External TLS/domain setup is an operator prerequisite, not an included proxy platform.

The PHP production dependency stage uses the dedicated production runtime and Composer alone; it does not depend on the development toolchain/Codex stage. Runtime copies are allowlisted, `.dockerignore` excludes secret/local/generated/development inputs, and both production images consume the same locked frontend build. Runtime does not contain Composer, Node, pnpm, Python, Git, Xdebug, Boost, test runners or knowledge tooling. Development build targets retain their existing development tools; the PHP runtime version is checked consistently in development and production.

Health layers:

- PHP-FPM's FastCGI ping checks process liveness.
- Named `GET /api/health` checks application boot and returns only `status: ok`, independently of the database.
- Named `GET /api/ready` verifies a read against the required `shared.jobs` table, returning only `status: ok` or HTTP 503 with `status: unavailable` and no database details. It disables response caching. Nginx's production healthcheck uses this path, so a listening process before migrations is not a ready application.
- Queue/scheduler use instance-aware heartbeat plus database checks. Deep diagnostics remain operator commands; public readiness does not run seeding, SMTP delivery, scanners or repair operations.

Production requires Docker Engine 26 or newer for volume subpath mounts, plus Docker Compose 2.35 or newer. The Nginx container cannot access private application storage through its public-file mount. Volume subpaths must already exist; application health is the startup dependency after the PHP entrypoint initializes storage.

## Nginx emergency response

The production Nginx image includes generated `src/public/runa-error` HTML/CSS/fonts/logo and PL/EN 503/English JSON documents. It serves them independently of PHP, Laravel, the database, Vite and AI tooling. `src/runa/build/errors/render.mjs` pre-renders the actual MinimalLayout Vue component and emits its CSS/assets during the Node build; `src/runa/build/errors/build.php` then finalizes static documents through the thin PHP wrapper. Only HTML/CSS/assets ship, with no Vue/Node/SSR runtime. No runtime secret or environment file is embedded. Only the dedicated `framework/maintenance` volume subpath is added read-only for maintenance signaling.

`docker/nginx/production.conf` maps Nginx-generated upstream 502/504 and its explicit maintenance 503 to an internal static document with final status 503, Retry-After 120 and no-store. API paths or JSON Accept headers receive a safe English JSON response; web requests receive Polish by default or English from `lang=en`/an English-leading Accept-Language. The static documents use system CSS or whitelisted `theme=light|dark` variants, with native MinimalLayout preference forms. Their internal paths cannot be requested directly, while their local assets remain publicly readable. Normal root/API/PHP responses keep their existing behavior and Laravel status/retry/body contracts; `fastcgi_intercept_errors off` avoids replacing application-originated 503/502/404/403 responses. Security headers are shared through `docker/nginx/security-headers.conf` and retained by the internal location, with same-origin styles/fonts/images and no inline-script/style exception. Docker's embedded DNS resolver is used at request time, so Nginx can start without PHP and discover recreated app containers.

## Private generated artifacts

Private native-error templates live at
/workspace/.runtime/frontend/errors/templates, generated freshly during image
build and copied explicitly into PHP. The shared workspace-root artifact placement
is defined in [project principles](../architecture/project-principles.md#generated-tooling-and-build-state).
No host runtime directory, renderer bundle, report, backup or development cache is
copied wholesale. Public generated error assets remain under src/public/runa-error.

## Runtime source paths

The Laravel base path is /workspace/src. Explicit image copies include the customer source/config/views and required platform backend/config/data/routes, mail/error templates, localization, generated UI/error assets and production-only vendor. Management, tests, source frontend, Node/build tooling, Boost and AI are absent. No source/dependency bridges remain. Root artisan transports to src/artisan; per-container config-cache tmpfs mounts at /workspace/src/bootstrap/cache. The existing application-storage volume now mounts at /workspace/src/storage without changing its identity. Nginx mounts application/public and runa/public read-only at /storage/ and /runa-storage/. Startup creates missing directories without deleting data; no implicit legacy-storage migration occurs.

## First administrator

Use the [trusted Auth operator procedure](../modules/auth/policies-and-operators.md)
after Core data preparation. The production installer can admit an initial emailed
invitation using explicit administrator settings; no-mail setup uses the private
one-time handoff command in the configured application PHP environment. Both grant
the actual Permissions system role under the same singleton/transaction protocol.
Reopen, ordinary deployment and customer-clone initialization never grant new
administrators. Host/CLI access protection remains the customer's responsibility.

## Runtime source projection

The PHP dependency stage prunes developer commands/adapters, supplied module
fixture implementations/contracts and raw module frontend trees before producing
its optimized autoloader. The final image copies that pruned Console, Support and
module source, retaining migrations, manifests, all historical upgrade handlers,
ModuleJob and source/runtime operator diagnostics. Frontend build inputs remain
available in their separate build stage. Generators, developer analysis, shared
PHP/browser test support and private suites/configuration are absent at runtime.
Development seeding refuses before resolving omitted fixture support. Required
SMTP verification and module contract diagnostics remain delivered operator tools;
see [the API/delivery boundary](../architecture/public-api.md#local-enforcement-and-delivery).

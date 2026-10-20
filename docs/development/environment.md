# Development Environment

## Supported host and execution boundary

Development supports native Linux with Docker Engine, the Docker Compose plugin, Python 3 and Git, without a distribution lock-in. Windows, macOS and Docker Desktop accommodations are outside the supported workflow. Keep the workspace bind mount `.:/workspace`; private host paths belong only in ignored local configuration.

The host owns Docker lifecycle operations. PHP, Composer, Node/pnpm, PostgreSQL and application tests run in project containers through existing wrappers; no host application toolchain is required. Follow [editor-and-agents.md](editor-and-agents.md#moving-work-between-container-and-host) for small host tasks and durable roadmap handoffs. Current editor shutdown behavior is documented there. Resource profiles and configured limits are described in [resources.md](resources.md).

## Goal

A future developer should be able to:

1. clone the repository,
2. open it in VS Code,
3. choose Reopen/Rebuild in Container,
4. obtain a working development stack except for explicitly required local secrets/settings.

There is no separate developer bootstrap script whose existence compensates for a broken devcontainer. The devcontainer/Compose setup is the bootstrap path.

## Container topology

Host Docker Compose manages sibling containers. VS Code attaches to the application/devcontainer.

Do not use Docker-in-Docker.

Baseline development services:

- app/devcontainer,
- Nginx,
- PostgreSQL,
- Qdrant,
- local embedding service,
- Mailpit,
- queue worker capability,
- scheduler capability.

Future Redis or Meilisearch is added only by a roadmap with a real use case.

All meaningful services have real health checks.

## HTTP runtime

Do not use `php artisan serve`.

Development request path:

Nginx -> PHP-FPM -> Laravel

Nginx is a separate container with access to the application public/static assets as required.

## PHP

Preferred PHP line: 8.4 if compatible with the selected supported Laravel/toolchain versions at implementation time.

Pin an exact supported version after current compatibility research.

Required baseline PHP extensions:

- `pdo_pgsql`
- `mbstring`
- `intl`
- `bcmath`
- `opcache`
- `pcntl`
- `zip`
- `curl`
- `xml`

Add GD/Imagick only when an application roadmap creates a concrete image-processing need.

## Composer

Pin a controlled Composer major/minor line and use the project lockfile.

Do not install random global PHP packages.

Production uses locked `composer install --no-dev` behavior.

Dependency upgrades happen only consciously in a roadmap.

## Node and pnpm

Use the current supported Node LTS available at RM-2026-001 implementation time, pinned to an exact version.

pnpm is the standard package manager.

Use Corepack where appropriate and pin `packageManager` in project metadata.

Do not rely on random host-global npm/pnpm tooling.

Commit `src/pnpm-lock.yaml`.

Use frozen installs in controlled/production builds.

## Frontend toolchain

Baseline:

- Vue 3,
- TypeScript,
- Tailwind CSS,
- Vite,
- ESLint,
- Prettier,
- `vue-tsc`.

TypeScript strict mode is required.

Use type-aware strict `typescript-eslint` configuration where supported and strict/recommended Vue linting appropriate to the installed versions.

Quality gate target: zero unexpected lint warnings.

ESLint is a quality/lint tool, not the primary formatter.

Formatting:

- Pint for PHP,
- Prettier for Vue/TS/JS/JSON/Markdown/CSS.

Canonical commands:

- `./bin/artisan runa:format`
- `./bin/artisan runa:lint`
- `./bin/artisan runa:typecheck`
- `./bin/artisan runa:analyse`

## Larastan/PHPStan

Use Larastan over plain PHPStan for Laravel-aware analysis.

Use the highest analysis level supported by the installed PHPStan/Larastan stack. Do not lower it to accommodate application errors; fix code/types first.

Do not use blanket `ignoreErrors`.

Every suppression requires a concrete reason.

Baselines are exceptional tools for legacy/import scenarios. New code must not accumulate baseline debt merely to obtain green output.

Fix code/types before suppressing analysis.

## Xdebug

Development-only.

Off by default.

Canonical command:

- `./bin/artisan runa:xdebug:enable`
- `./bin/artisan runa:xdebug:disable`
- `./bin/artisan runa:xdebug:status`

VS Code debugging should be preconfigured.

Xdebug must not be present/enabled in production runtime.

## VS Code extensions

Devcontainer recommendations/configuration should include:

- Intelephense
- PHP Namespace Resolver
- Vue Official
- Tailwind CSS IntelliSense
- ESLint
- Prettier
- EditorConfig
- Docker
- GitLens
- `cweijan.vscode-database-client2`

Do not automatically install the Codex/OpenAI VS Code extension. The user installs it manually after the container is open because automatic extension installation has previously caused failures.

Where safely possible, preconfigure Database Client for the development PostgreSQL service without embedding secrets. If plugin constraints prevent that, document exact onboarding steps.

## Laravel Boost and Codex CLI

Laravel Boost is a required development dependency.

Purposes:

- Laravel MCP tools,
- project inspection,
- version-aware Laravel documentation search.

Runa does not use Boost-generated skills as its durable AI policy.

Follow the single installation-safety procedure in `docs/ai/laravel-boost.md`; remove generated skills and preserve Runa guidance while retaining MCP functionality.

Do not let future Boost installation/update commands silently overwrite Runa skills.

Codex CLI must exist inside the devcontainer.

Acceptance requires a real Boost MCP call, such as version-aware Search Docs/project inspection, with output consistent with installed versions.

See `docs/ai/laravel-boost.md`.

## Developer resource profiles

Profile usage and tuning guidance lives in `docs/development/resources.md`.

Support:

- `low`
- `standard`
- `high`
- `custom`

Profile values are local/ignored configuration, not YAML edits.

Default: `standard`.

Initial user high profile:

```dotenv
DEV_CPU_LIMIT=20
DEV_MEMORY_LIMIT=16G
TEST_WORKERS=12
PLAYWRIGHT_WORKERS=4
```

Tune the whole development stack coherently rather than only one container.

Use an ignored local file such as `.env.dev.local`.

Canonical command:

`./bin/artisan runa:dev:profile`

It should show/change the effective profile.

Development doctor reports effective limits/workers.

## GPU embeddings

The local embedding service should support NVIDIA CUDA where available.

The development environment must fall back cleanly to CPU when NVIDIA container support/GPU is unavailable.

A missing GPU must not block normal development.

Model execution backend must not change the semantic chunking/indexing contract.

Persistent local model cache should survive devcontainer rebuilds and must not be committed or included in production.

## First/open/rebuild behavior

Devcontainer setup scripts are idempotent.

Rebuild/reopen must not:

- destroy the development database,
- run `migrate:fresh`,
- overwrite local secrets/config,
- redownload large model data unnecessarily,
- reinstall unstable global tools needlessly.

If a database is empty, development setup may initialize it safely.

If it already exists, use normal migrations/health checks.

Distinguish first bootstrap work from normal reopen behavior.

## Host independence

The repository must not depend on one developer's private host paths.

Host-specific overrides go through ignored local config.

GPU support is an optional host capability.

## Runtime parity

If the application requires a PHP extension/runtime capability in development, production receives an equivalent compatible capability.

Dev-only tooling such as:

- Xdebug,
- Qdrant/RAG,
- embedding service,
- development diagnostics,

is exempt from runtime parity.

## Mail

Development uses Mailpit.

Production uses configurable outbound SMTP/provider settings.

Inbound mail handling is not part of the baseline.

## Queue and scheduler

Provide:

- a separate queue worker process/container capability,
- a separate scheduler process/container capability.

Initial queue driver: database.

Do not add Redis/Horizon until a real use case requires them.

Use the current supported Laravel scheduler mechanism; do not require host cron for application scheduling.

## No CI

Do not create GitHub Actions or CI files as part of environment setup.

Local hooks and quality tooling are the accepted enforcement mechanism.

## Editor behavior and command context

Enable format-on-save and select the formatter explicitly per language. Save-time work is lightweight formatting, not tests or full static analysis. Do not use blanket ESLint disables; keep strict type-aware/Vue rules and fail on lint warnings.

VS Code extensions listed above are installed automatically through devcontainer customizations, except the manually installed Codex/OpenAI extension. Verify the actual editor behavior during initial acceptance; CLI availability alone does not prove extension integration.

Host Compose owns container lifecycle/build/production simulation. Host wrappers dispatch application commands into the running container; inside the devcontainer, application and knowledge commands run directly against sibling services. Do not mount the Docker socket or introduce Docker-in-Docker to make host-only commands work from the workspace container. Document which terminal each command requires.

Qdrant and the local embeddings service start automatically with the devcontainer. Independent `knowledge-init` initializes a missing index and retries service failures without delaying application setup or post-create. Existing stale indexes require explicit incremental indexing. Application doctor reports bounded AI degradation separately; dedicated knowledge doctor/evals remain strict.

Select `high` locally for the initial project owner's environment; the portable repository default remains `standard`. Document profile selection, effective whole-stack limits and tuning for other hardware.

## Base container commands

The initial container uses `docker/php/Dockerfile` with a shared `runtime` stage and a `development` stage. Runtime PHP extensions are compiled once in the shared stage; Composer, Node, Corepack/pnpm, Git and Python are added only to development. The non-root `runa` user uses the host UID/GID recorded during `./bin/artisan runa:dev:prepare`. PHP-FPM serves port 9000 on the internal Compose network, with a FastCGI ping health check and no published host port.

Host Python 3 and Docker Compose are required for the host wrapper and VS Code initialize command. Run:

```sh
./bin/artisan runa:dev:prepare
./bin/artisan runa:dev:build
./bin/artisan runa:dev:up
./bin/artisan runa:dev:shell
```

`./bin/artisan runa:dev:config` prints resolved Compose configuration; `./bin/artisan runa:dev:down` stops/removes this project's containers while preserving named volumes. There is no destructive volume-reset shortcut. The delivered stack includes app/PHP-FPM, PostgreSQL, Nginx, Mailpit, queue, scheduler and independent optional knowledge services.

The devcontainer initializes the resource override before Compose starts and attaches as `runa`. Application setup configures the implemented versioned Git hooks. Opening or rebuilding does not delete application data. See [toolchain selections](toolchain.md) and [resource profiles](resources.md).

## Laravel foundation

The pinned Laravel skeleton was generated in an isolated container directory and only the required application files were adopted. Runtime depends on `laravel/framework`; starter auth/models/migrations, Blade welcome views, Tinker, Pail, PAO and demo frontend content are absent. `src/composer.lock` records the actual dependency graph. Install through `composer install`, not an unplanned update.

The devcontainer's post-create command is `./bin/artisan runa:dev:setup --if-needed`, executed inside the container. With a matching setup fingerprint and required dependency/build outputs (including the generated `src/public/runa-error/pages/503-pl-system.html`), it skips dependency installation and frontend rebuilding while retaining configuration/migration and hook/MCP checks. Missing output or changed inputs run complete setup; the retired `503-pl.html` filename is not readiness evidence. It creates a missing `.env` from `.env.dev.example` with a random development database password, installs locked PHP dependencies, generates a missing application key, validates configuration/templates and sets the hooks path. It preserves existing environment values and keys on repeated runs. Setup also prepares the guarded testing environment, applies ordinary migrations and installs/builds the locked frontend as described below.

Nginx publishes `http://localhost:8080`; `GET /api/health` returns only `{"status":"ok"}`. This is a lightweight application liveness check, not proof of database/queue readiness. PHP-FPM and Nginx run without root. API failures render JSON. Incoming `X-Request-ID` values must contain 1–64 ASCII letters, digits, underscores or hyphens; otherwise a fresh UUIDv7 is generated. The identifier is returned and placed in logging context. No request payload is logged by this middleware.

The initial production build target copies an allowlist of runtime files and locked production dependencies into the shared PHP runtime. Its Nginx image uses a separate production configuration with restrictive browser headers and readiness checks. `compose.production.yaml` defines all five production services; deployment, isolation verification and production operations are implemented. Follow docs/production/deployment.md for installer and deployment procedures.

## PostgreSQL initialization

`./bin/artisan runa:dev:prepare` creates missing local `.env`, `.env.testing` and `.env.dev.database` files without printing credentials. `.env.dev.database.example` documents the infrastructure-only secrets; PostgreSQL consumes that file, while Laravel reads its own connection credentials from `.env`. Normal preparation refuses conflicting application/initialization passwords rather than silently changing an existing role. Post-create setup copies a missing test key from the generated local application key and runs ordinary migrations, never `migrate:fresh`.

PostgreSQL 18.6 persists data under the named volume mounted at `/var/lib/postgresql`. Its TCP health check authenticates as the application role and executes `SELECT 1`, avoiding premature readiness while the entrypoint's temporary Unix-socket-only initialization server runs. The application depends on this health check. PostgreSQL has no published host port; VS Code's database client uses service DNS within the devcontainer; see editor-and-agents.md for setup and manual verification.

Initialization scripts run only for an empty volume. Changing local passwords does not rewrite an existing database. Reconcile credentials/roles deliberately; do not delete a volume to repair ordinary configuration. Rebuild/reopen preserves databases. Production uses its own Compose project, volume and `.env.prod.database`; production operations are documented in docs/production/deployment.md.

## Frontend foundation

When setup inputs or generated outputs require it, post-create `./bin/artisan runa:dev:setup --if-needed` installs `src/pnpm-lock.yaml` with `--frozen-lockfile` and builds the Vue SPA. `http://localhost:8080/` serves the Vite-built HTML through the named Laravel `spa` route without Blade or a session/auth module. Hashed assets live in ignored `src/public/build`; API traffic remains under `/api`. Changes to frontend source require a new `pnpm run build` when using this built-output URL.

For HMR, run `pnpm run dev` inside the devcontainer and open the VS Code-forwarded port 5173 at `/build/`. Vite proxies `/api` to the sibling Nginx, which still calls PHP-FPM/Laravel. The HMR server is frontend tooling, not the Laravel runtime. Light/dark/system and PL/EN preference state is owned by the shell; no UI framework or global state dependency is installed.

The production Dockerfile has explicit `production` and `production-nginx` targets that consume the same locked frontend build stage. PHP gets native translation data/license and the SPA HTML/assets; Nginx gets only static frontend assets/configuration. No host-built frontend output is copied as a build input. Production container behavior and host build checks are documented in `docs/production/architecture.md` and `docs/tooling/commands.md`; operational self-tests and their baseline host simulation evidence are recorded in closed RM-2026-001.

Browser verification uses `src/runa/tools/testing/frontend.mjs` with project Playwright, Chromium and Firefox. Its screenshots are ignored under `artifacts/playwright/foundation`. Run `PLAYWRIGHT_BROWSERS_PATH=/opt/playwright node src/runa/tools/testing/frontend.mjs` inside the container after provisioning those browsers. The development image provisions the pinned browsers and system dependencies; baseline acceptance is recorded in closed RM-2026-001.

## Complete runtime services and startup order

The stack includes Mailpit, queue and scheduler alongside app, PostgreSQL, Nginx and development-only Qdrant/embeddings. `./bin/artisan runa:dev:build` builds the app toolchain, non-root Mailpit, Qdrant and configured CPU/CUDA embedding images. Pass service names for selective builds; build alone never recreates app. Compose owns every service on the host; there is no Docker socket, Docker-in-Docker or privileged container.

Startup graph: PostgreSQL authenticates successfully -> app/PHP-FPM becomes available -> the one-shot `setup` service prepares the application -> Nginx, worker and scheduler start. All PHP services carry the same build definition and UID/GID overrides, so cold startup does not rely on the attached VS Code container retaining the base image tag. Setup uses the same `./bin/artisan runa:dev:setup --if-needed` path as the devcontainer post-create hook, serialized by a shared checkout file lock so concurrent invocations do not race dependency installation or migrations. Its ignored `.runtime/dev/setup.sha256` covers lockfiles, local app environment, setup script, configuration, migrations and frontend sources. If those inputs and generated dependency/frontend outputs are unchanged, it validates configuration, applies normal migrations, configures hooks and registers Boost, with the scanner prerequisite checked before the fast path; dependency installation/build are skipped. A failed setup blocks dependent services. Never mark a failed/partial setup complete, and never run destructive migrations on reopen.

Qdrant, CPU/CUDA embeddings and `knowledge-init` are independent automatic development services. Neither application setup nor devcontainer post-create waits for AI readiness. `./bin/artisan runa:dev:up` waits only for PostgreSQL, app, setup, Nginx, queue, scheduler and Mailpit, then starts the AI siblings without a health wait and reports bounded optional availability/freshness. The independent initializer retries failed readiness/indexing and initializes only a missing index; existing stale sources require explicit indexing. See [knowledge.md](knowledge.md) for recovery and strict checks.

The setup container exits successfully and has no runtime healthcheck. It is expected to show `Exited (0)`, while long-running services become healthy. Ordinary `./bin/artisan runa:dev:up`/devcontainer reopening can rerun the lightweight setup check. Explicit `./bin/artisan runa:dev:setup` forces the full idempotent path. Missing application key/env/dependencies on a fresh checkout are prepared before HTTP/background services start. A separate empty checkout with its own project/volumes was tested through this graph.

Workers run `queue:work database --sleep=3 --tries=3 --timeout=60 --memory=192 --max-time=3600`; Docker restarts them after normal recycling or failure. The 60-second worker timeout is below the 90-second database retry interval; the 75-second stop grace permits current work to finish. Scheduler runs Laravel's `schedule:work` directly, without host cron. Both validate configuration before starting. They use the shared app source/storage and non-root identity. After changing already-loaded worker/scheduler PHP code, restart those services deliberately; bind mounts do not reload a long-running PHP process.

### Runtime health

Worker health combines a heartbeat from Laravel's idle/successful-job events with a real read of `shared.jobs`. Scheduler health requires successful execution of the scheduled `runa:runtime:pulse` command each minute, also with a database readiness check. Probes never refresh their own heartbeat or repair state. Records are replaced atomically under ignored `storage/framework/health`, contain only instance identity/time, and reject stale, future or foreign-container timestamps. Queue freshness is 120 seconds to accommodate a 60-second job; scheduler freshness is 150 seconds. Scheduler's startup grace allows the first minute boundary. These are liveness/readiness signals, not evidence that every business job succeeded; inspect failed jobs separately.

```sh
php artisan runa:runtime:health queue
php artisan runa:runtime:health scheduler
php artisan schedule:list
php tools/maintainer/testing/runtime.php
python3 tools/maintainer/testing/services.py
```

Run health commands in the corresponding container because instance IDs differ. The development-only services smoke enqueues a real Laravel email, waits for worker delivery to Mailpit and leaves an identifiable test message. It refuses production, alternate SMTP hosts and synchronous queue configuration.

### Mail and persistence

Mailpit uses `docker/mailpit/Dockerfile.dockerignore` to include only its data-directory placeholder in the build context; the root production exclusions remain independent.

Mailpit UI is published only at `http://localhost:8025`. SMTP stays internal at `mailpit:1025`; app traffic stays at `http://localhost:8080`. PostgreSQL, workers and scheduler publish no ports. Mailpit accepts only localhost/loopback/service Host headers, stores up to 500 messages in its named volume, runs with the host-matched non-root UID/GID and uses its upstream `readyz` healthcheck. It has no outbound relay configuration.

Laravel's SMTP configuration lives in `config/mail.php` and all app env examples. Development uses Mailpit; testing uses the array mailer. Credentials are optional as a pair, but invalid types/ports/schemes/sender values fail validation. Production requires actual outbound SMTP, TLS and the database queue; it rejects Mailpit/localhost/template-host settings and the array mailer. Never log credentials or mail payloads.

PostgreSQL data, Mailpit captures and application storage survive container recreation/rebuild. Named-volume owners are prepared by the images; no root ownership repair or destructive bootstrap is required on an ordinary reopen. Container logs have local rotation (10 MB, 3 files). Profile-specific service budgets are documented in `resources.md`.

## Knowledge services

The development stack now also starts Qdrant and CPU embeddings automatically; the generated host override adds CUDA embeddings when selected. Setup waits for vector-store/CPU readiness, initializes a missing index, and reports staleness without automatically re-embedding existing changed documents. Model cache and vector data persist in named volumes. All knowledge services remain development-only and expose no host ports. Hardware selection, resource limits, pinned images/model and actual acceptance evidence are documented in [knowledge.md](knowledge.md); retrieval, doctor and evaluation commands are implemented and documented there.

## Image identity and rebuilds

Local working images use `runa-development:dev`, `runa-mailpit:dev`, `runa-qdrant:dev`, `runa-embeddings-cpu:dev` and optional `runa-embeddings-cuda:dev`. These are mutable development tags. Production `runa-production`/`runa-nginx` keep exact release or full-commit identities.

Shared development/test and production-build Nginx runtime inputs use tag plus registry-verified digest: `nginx:1.31.7-alpine-slim@sha256:ebd058041a732a2b2dc0b606e34572dd8e0034de6a4aa4d50d8c13a240ac6005` (verified 2026-10-18). The official slim variant retains the same Nginx version and the core/FastCGI behavior used by Runa, omitting optional dynamic modules that the project does not load. It replaces the full development/test variant after its blocking Expat vulnerability; the production target no longer needs its prior explicit libexpat upgrade/install workaround. No suppression is added. Existing PostgreSQL, production PHP, Qdrant/Ubuntu and embedding digests remain unchanged. Development PHP, Node, Composer and Mailpit retain reviewed version tags and their accepted rebuild variability. Digest updates require deliberate maintenance, actual-image scans and runtime checks; pins do not automatically receive security fixes. This is targeted runtime reproducibility, not a guarantee that all builds are identical.

## Standalone HTTP error assets

Native HTTP errors and emergency 503 are build-time renders of the actual MinimalLayout Vue component. `./bin/artisan runa:dev:setup` builds these as part of `pnpm run build` and finalizes static 503 documents with `php src/runa/resources/errors/build.php`. For a manual refresh use `./bin/pnpm run build:errors` then `php src/runa/resources/errors/build.php` inside the application container. Rebuild after shared layout/component/style/copy/branding changes. Private generated views and public standalone assets are ignored and recreated independently in production; runtime rendering does not need Vue/Node/JavaScript, Vite's main build files, a database or optional AI tooling. Browser checks use isolated test fixtures; there are no application preview routes or catalog.

The Compose setup job invokes its fixed private Python executor before Composer is available; normal configured use is runa:dev:setup. Compose knowledge-init invokes tools/application/knowledge/startup.py directly, independently of PHP/vendor/application readiness. Normal operator commands use the Artisan vocabulary documented in Tooling Commands.

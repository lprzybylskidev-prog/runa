# Development Environment

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

Commit `pnpm-lock.yaml`.

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
- `./bin/format`
- `./bin/lint`
- `./bin/typecheck`
- `./bin/analyse`

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

- `./bin/xdebug on`
- `./bin/xdebug off`
- `./bin/xdebug status`

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

`./bin/dev profile`

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

Qdrant and the local embeddings service start automatically with the devcontainer. On first setup, build a missing knowledge index; on subsequent opens check health/freshness and use incremental indexing when needed. Required services perform lightweight readiness checks and doctor supplies deeper self-tests.

Select `high` locally for the initial project owner's environment; the portable repository default remains `standard`. Document profile selection, effective whole-stack limits and tuning for other hardware.

## Base container commands

The initial container uses `docker/php/Dockerfile` with a shared `runtime` stage and a `development` stage. Runtime PHP extensions are compiled once in the shared stage; Composer, Node, Corepack/pnpm, Git and Python are added only to development. The non-root `runa` user uses the host UID/GID recorded during `./bin/dev prepare`. PHP-FPM serves port 9000 on the internal Compose network, with a FastCGI ping health check and no published host port.

Host Python 3 and Docker Compose are required for the host wrapper and VS Code initialize command. Run:

```sh
./bin/dev prepare
./bin/dev build
./bin/dev up
./bin/dev shell
```

`./bin/dev config` prints resolved Compose configuration; `./bin/dev down` stops/removes this project's containers while preserving named volumes. There is no destructive volume-reset shortcut. At the base-toolchain stage, only app/FPM exists; application, Nginx and sibling services are completed by the following roadmap points.

The devcontainer initializes the resource override before Compose starts and attaches as `runa`. It sets the versioned hooks path after creation; hook scripts become available in their implementation point. Opening or rebuilding does not delete application data. See [toolchain selections](toolchain.md) and [resource profiles](resources.md).

## Laravel foundation

The pinned Laravel skeleton was generated in an isolated container directory and only the required application files were adopted. Runtime depends on `laravel/framework`; starter auth/models/migrations, Blade welcome views, Tinker, Pail, PAO and demo frontend content are absent. `composer.lock` records the actual dependency graph. Install through `composer install`, not an unplanned update.

The devcontainer's post-create command is `./bin/dev setup`, executed inside the container. It creates a missing `.env` from `.env.dev.example` with a random development database password, installs locked PHP dependencies, generates a missing application key, validates configuration/templates and sets the hooks path. It preserves existing environment values and keys on repeated runs. Database setup and frontend installation are completed by their roadmap points.

Nginx publishes `http://localhost:8080`; `GET /api/health` returns only `{"status":"ok"}`. This is a lightweight application liveness check, not proof of database/queue readiness. PHP-FPM and Nginx run without root. API failures render JSON. Incoming `X-Request-ID` values must contain 1–64 ASCII letters, digits, underscores or hyphens; otherwise a fresh UUIDv7 is generated. The identifier is returned and placed in logging context. No request payload is logged by this middleware.

The initial production build target copies an allowlist of runtime files and locked production dependencies into the shared PHP runtime. Its Nginx image uses the same HTTP configuration. `compose.production.yaml` is an incomplete paired runtime foundation; points 13–14 complete the stack, isolation verification and production operations. It must not be treated as a deployment procedure yet.

## PostgreSQL initialization

`./bin/dev prepare` creates missing local `.env`, `.env.testing` and `.env.dev.database` files without printing credentials. `.env.dev.database.example` documents the infrastructure-only secrets; PostgreSQL consumes that file, while Laravel reads its own connection credentials from `.env`. Normal preparation refuses conflicting application/initialization passwords rather than silently changing an existing role. Post-create setup copies a missing test key from the generated local application key and runs ordinary migrations, never `migrate:fresh`.

PostgreSQL 18.6 persists data under the named volume mounted at `/var/lib/postgresql`. Its TCP health check authenticates as the application role and executes `SELECT 1`, avoiding premature readiness while the entrypoint's temporary Unix-socket-only initialization server runs. The application depends on this health check. PostgreSQL has no published host port; VS Code's future database client uses service DNS within the devcontainer.

Initialization scripts run only for an empty volume. Changing local passwords does not rewrite an existing database. Reconcile credentials/roles deliberately; do not delete a volume to repair ordinary configuration. Rebuild/reopen preserves databases. Production uses its own Compose project, volume and `.env.prod.database`; production operations remain pending points 13–14.

# Canonical Tooling Commands

Every durable `./bin/*` command must:

- use English help/output,
- support `--help`,
- fail with meaningful exit codes,
- avoid secrets in output,
- be documented here in the same roadmap point that introduces/changes it.

This file documents the implemented command surface: development lifecycle/profile/setup, passthrough wrappers, seeders, doctor, hooks, knowledge, local quality, security, production lifecycle/deploy tools and frontend/backend checks. RM-2026-001 records their baseline acceptance evidence. Commands still require their documented environment and operational prerequisites; simulation acceptance does not prove real production deployment.

## Runtime-independent Artisan transport

Before a checkout exists, the separately supplied `distribution/bootstrap.py` exception acquires
an exact GitHub release, with public access by default and optional private
credentials. It supports interactive selection, explicit noninteractive inputs,
atomic publication and install-now/postponement. See [bootstrap](bootstrap.md)
for host requirements, trust, credential handling and resume instructions.

Artisan is the public domain-command registry. Normal configured-environment use is
`php artisan runa:<family> ...` from the development/operator PHP terminal. Local
commands run PHP Actions; host lifecycle/deploy/quality/security operations
execute through the private workspace host executor and propagate output/exit
status. Explicit --plan is nonexecuting. See [host executor](host-executor.md) for
trust, lifecycle, cancellation and disconnected-operation recovery.

`./bin/artisan` is initial setup/unavailable-environment recovery transport, not
the normal daily interface. Host-only --recover-host starts/restarts the executor;
--operator enters the isolated prepared PHP operator terminal. No host PHP/Node
or container Docker socket is required. Cold --management discovery/plans still
use the pinned management artifact independently of app/DB/keys. Production runtime
images omit management, development tooling and private executor state; the separate
operator environment receives the transport explicitly. The source registry has
operational commands including runa:host, and optional development commands.

Pre-autoload setup and independent knowledge-init retain their existing narrow
bootstrap exceptions. Compatibility aliases remain transports, not another command
registry. Mature Docker/Git/scanner/quality mechanics stay fixed internal
helpers; application source-tool/test/Xdebug orchestration stays in PHP Actions.

### Host-plan v1 execution boundary

`src/runa/management/host-plan.v1.schema.json` defines schema_version, request_id, operation and parameters. Parameters are empty for argument-free development actions, otherwise contain only a bounded list of literal arguments (at most 128, at most 4096 bytes each). Unknown/duplicate fields, executable/shell/env/path/mount fields, unsupported operations and mismatched request IDs are rejected again by the executor. Only fixed allowlisted helper/tool families execute; argument arrays never become shell text. A null request ID is an explicit nonexecuting preview; normal host work is request-bound by the executor. The transport supplies a fresh internal ID and consumes only the immediately returned JSON; there is no arbitrary plan-file executor. Operator-supplied --host-request is rejected. --plan never executes the operation.

Development lifecycle plans retain the shared production operation lock and unresolved-operation fencing. Production/deploy helpers retain their existing locks/recovery rules. Quality owns its stable quality lock and each gate owns its resource locks; the coordinator does not hold the production lock across its child gates. Test Actions retain the suite lock. Private mode-600 request receipts are removed after use; state/lock symlinks are rejected. Meaningful child exit codes propagate unchanged.

Management discovery runs the inspected immutable image ID with no network, mounts, forwarded env or capabilities, read-only root and bounded CPU/memory/PIDs/tmp. Combined output is capped at 64 KiB and 30 seconds; timeout/interruption removes only its named disposable container. Host operations and quality retain their own existing durations, not discovery timeouts. Background quality semantics and all hard gates remain unchanged: after launching a background roadmap/final gate, stop work and do not poll.

### Command migration map (RM-005 point 6)

| Previous family                    | Canonical form                                                                                                           | Execution                                                             |
| ---------------------------------- | ------------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------- |
| dev                                | `runa:dev action [arguments]`                                                                                            | PHP validation; proven host/setup process mechanics                   |
| doctor                             | `runa:doctor [area]`                                                                                                     | PHP application checks; deterministic source/host diagnostics         |
| quality                            | `runa:quality show/roadmap/final/status [--background]`                                                                  | Existing dependency graph, locks and interruption evidence            |
| knowledge                          | `runa:knowledge action [query] [filters]`                                                                                | Development-only internal retrieval/index engine                      |
| format/lint/typecheck/analyse/test | `runa:format`, `runa:lint`, `runa:typecheck`, `runa:analyse`, `runa:test [frontend/e2e] [runner options]`                | PHP Actions; runner options may follow --                             |
| xdebug                             | `runa:xdebug on/off/status`                                                                                              | PHP development configuration Action                                  |
| security                           | `runa:security dependencies/secrets/trivy/review-changed-surface`                                                        | Existing scanner/evidence mechanics; Trivy on host                    |
| production/prod                    | `runa:production action [service]`                                                                                       | Fixed host Compose/image helper                                       |
| deploy                             | `runa:deploy target [--simulation]`                                                                                      | Exact-source, lock and recovery mechanics                             |
| install-prod                       | `runa:install dev/prod target [--settings-file=path] [--profile=standard] [--resume] [--non-interactive] [--simulation]` | Owner distribution only; absent from customer registration            |
| host                               | `runa:host status [operation-id]` / `stop`                                                                               | Private executor lifecycle and durable disconnected-operation results |
| git-hook/quality-check             | `runa:hook pre-commit/commit-msg`, `runa:check debug-residue`                                                            | Hook is owner maintenance only; source check remains available        |
| seed                               | `runa:seed ...`                                                                                                          | Existing application command                                          |
| logs                               | `runa:dev logs [service]`                                                                                                | Fixed host log action                                                 |
| artisan/composer/pnpm/boost-mcp    | Existing transports                                                                                                      | Framework/tool/stdio entrypoints; no second domain registry           |

Compatibility retained on 2026-10-20: `./bin/dev`, `./bin/doctor`, `./bin/quality`, `./bin/knowledge`, `./bin/security`, `./bin/xdebug`, `./bin/format`, `./bin/lint`, `./bin/typecheck`, `./bin/analyse`, `./bin/test`, `./bin/deploy`, `./bin/production`, `./bin/prod`, `./bin/logs`, `./bin/seed`, `./bin/quality-check`. They are thin aliases through `./bin/command` or passthrough; `./bin/check` and `./bin/operations` are internal shared names and show discovery help. `bin/project`, `bin/install-prod` and `bin/git-hook` are removed. Owner hook transport lives in `distribution/maintenance/bin/git-hook` and is excluded from customer source. Governance-required quality/doctor entrypoints stay available. See docs/maintenance/deprecations.yml.

Internal Python process engines live under `tools/project/executors/` with no public bin registry. Their argument parsing is defensive process validation, not a separately documented CLI. Preserve mature Compose/scanner/Git/quality/RAG mechanics; application-facing source-check orchestration and Xdebug behavior live in PHP Actions. Do not mechanically translate process engines to PHP or introduce Docker-in-Docker.

## Development

### `php artisan runa:dev profile`

Show or change development resource profile.

Profiles:

- low
- standard
- high
- custom

Doctor output includes effective resource values.

### `php artisan runa:dev up`

Host-only application startup waits for PostgreSQL, app, successful setup, Nginx, queue, scheduler and Mailpit. It then starts Qdrant, configured embeddings and `knowledge-init` automatically without waiting for AI health/indexing. Application failures return nonzero; optional AI failures report degradation. Repair optional services and repeat startup, or run `php artisan runa:knowledge index` for an existing stale index.

### `php artisan runa:dev knowledge-init` / `php artisan runa:dev knowledge-status`

Inside the development container: `knowledge-init` is the independent Compose initialization job, retrying through its restart policy; `knowledge-status` reports bounded optional service/freshness availability without failing application diagnostics. Neither is part of application setup/post-create.

### `php artisan runa:dev build` / `php artisan runa:dev recreate`

Executed on the host through the private executor: `build [service ...]` builds selected local service images; no arguments builds all configured development images. Building does not recreate running containers. `recreate service [service ...]` requires explicit known services and runs selective `up -d --no-deps --force-recreate`; review prerequisite readiness and check health afterward. It starts no sibling dependencies and does not wait for health. App recreation can disconnect an attached editor; plan the host transition first. `restart [service]` retains image/configuration, with no service restarting the whole stack. `down` is explicitly whole-project and preserves volumes.

### `php artisan runa:dev doctor`

Alias for `php artisan runa:doctor development`. From the host, first validates Docker service health/setup exit status, then delegates application diagnostics into the app container. Inside the devcontainer, checks configuration/migrations, fresh shared queue/scheduler heartbeats, Nginx/Mailpit HTTP readiness and effective resource values. Optional AI service/freshness diagnostics have bounded deadlines and report degradation separately; strict model/retrieval acceptance remains `php artisan runa:knowledge doctor` and `php artisan runa:knowledge eval`. Shared heartbeat freshness does not replace the instance-bound Docker healthchecks. This path requires no Docker socket inside the devcontainer.

### Frontend and localization checks currently available

Inside the devcontainer: `pnpm run typecheck`, `pnpm run build`, `pnpm run dev` (HMR port 5173 at `/build/`), `python3 tools/localization/snapshot.py check`, `php tools/testing/localization.php`. `php artisan runa:dev setup` now includes locked frontend installation/build. Snapshot review/update commands are documented in `docs/development/localization.md` and never overwrite local catalogs.

### Runtime services currently available

Inside the corresponding worker/scheduler container, `php artisan runa:runtime:health queue|scheduler` verifies its own heartbeat and database access. `php artisan schedule:list` shows the technical pulse. `php tools/testing/runtime.php` checks heartbeat/SMTP rejection; development-only `python3 tools/testing/services.py` exercises database queue -> SMTP -> Mailpit. Mailpit UI is at `http://localhost:8025`. See `docs/development/environment.md` for startup order and persistence.

## Formatting/lint/type/static analysis

- `php artisan runa:format`
- `php artisan runa:format --check`
- `php artisan runa:lint`
- `php artisan runa:typecheck`
- `php artisan runa:analyse`

## Tests

### `php artisan runa:test --parallel`

Run backend tests with parallel-safe isolation.

### `php artisan runa:test frontend`

Run Vitest/Vue component tests.

### `php artisan runa:test e2e --critical`

Run Playwright through an isolated loopback browser harness. Build assets first with `./bin/pnpm run build`. The E2E wrapper finalizes static emergency documents with PHP inside the application container before starting Playwright, including after a build replaces the generated output. Playwright owns harness startup/shutdown; root/API/native assets use isolated testing Nginx/PHP-FPM, while synthetic routes exist only in the test process. See `docs/testing/strategy.md`.

## Xdebug

- `php artisan runa:xdebug on`
- `php artisan runa:xdebug off`
- `php artisan runa:xdebug status`

Xdebug is development-only and off by default.

## Seeding

- `php artisan runa:seed default`
- `php artisan runa:seed production`
- `php artisan runa:seed development`
- `php artisan runa:seed occasional <Seeder>`
- `php artisan runa:seed graph`

Implemented now; the initial graph is empty because no business/reference/demo entities exist. Categories and dependency validation run before execution; production refuses development dependencies, including explicit development requests. `--force` deliberately bypasses the production confirmation only for automated seed execution, not destructive database commands. See `docs/data/seeding.md`.

## Knowledge/RAG

Knowledge commands include automatic first-index initialization and explicit maintenance/retrieval operations. Search supports `--top-k 1..50`, `--explain`, `--document-type`, `--scope`, `--roadmap-id`, `--status` and `--source-path`; quote multiword queries. `--device cpu` forces CPU. Search/eval require a fresh index; doctor reports failures and rebuild explicitly resets the development collection. `python3 tools/testing/knowledge.py` runs offline ingestion contracts, `python3 tools/testing/knowledge-live.py` checks CPU/CUDA ingestion, and `python3 tools/testing/knowledge-retrieval.py` checks real hybrid retrieval in an isolated collection. `php artisan runa:dev embeddings auto|cpu|cuda` selects host GPU policy; apply it with build/up. See `docs/development/knowledge.md`.

- `php artisan runa:knowledge search <query>`
- `php artisan runa:knowledge search <query> --explain`
- `php artisan runa:knowledge index`
- `php artisan runa:knowledge initialize` — initializes only missing/empty collections under the shared lock; existing stale indexes require explicit indexing.
- `php artisan runa:knowledge status`
- `php artisan runa:knowledge rebuild`
- `php artisan runa:knowledge stale`
- `php artisan runa:knowledge doctor`
- `php artisan runa:knowledge eval`

Production must reject/disable this command surface.

## Quality

- `php artisan runa:quality show`
- `php artisan runa:quality roadmap`
- `php artisan runa:quality final`
- `php artisan runa:quality status`

Latest-run-only local state lives under gitignored `.runtime/quality/`.

## Generic doctor

Project provides:

`php artisan runa:doctor` and `php artisan runa:doctor <area>`

Implemented areas: `repository`, `config`, `maintenance`, `hooks`, `frontend`, `database-migrations`, `database`, `seeding`, `boost`, `tooling`, `docs`, `cleanliness`, `development`, `production`, `release`. `docs` checks public command documentation and the bounded README state contract in docs/workflows/releases.md; it does not interpret arbitrary prose. The read-only `release` check includes the same docs validation and computes the expected version from main and the active roadmap, permits only the final verification item to remain open, and rejects premature version/tag drift. After closure metadata edits it verifies VERSION/changelog/closed-roadmap/checklist/report agreement, release-type arithmetic and any existing annotated tag identity (with the documented main-only draft-planning exception); remote publication is verified separately. `frontend` uses the real browser diagnostic script and requires the testing Nginx service. `database-migrations` resets only the guarded isolated test database; `database` and `seeding` run the relevant backend contracts. `cleanliness` rejects unreviewed scratch and untracked/unstaged files; staged reviewed changes are allowed.

Do not turn doctor into one giant opaque script. Subcommands should have clear responsibilities.

## Security

Implemented wrapper surface:

- `php artisan runa:security dependencies`
- `php artisan runa:security secrets`
- `php artisan runa:security trivy`
- `php artisan runa:security review-changed-surface`

Underlying tools include:

- Composer audit,
- pnpm audit,
- Gitleaks,
- Trivy.

## Production

Implemented helper surface:

- `php artisan runa:production build`
- `php artisan runa:production validate`
- `php artisan runa:production self-test`
- `php artisan runa:production verify-ai-isolation`

## Deployment

- `php artisan runa:deploy`
- `php artisan runa:deploy rollback`

## Logs

`php artisan runa:dev logs`

Provide a concise operator view across relevant production/development services without exposing secrets.

## Destructive commands

Any destructive reset/prune/restore command must:

- state what will be destroyed,
- scope itself to Runa resources,
- require explicit confirmation interactively,
- provide an intentional safe non-interactive form only when real automation needs it.

Do not expose a "fix Docker" command that runs global host prune operations.

## Container lifecycle and passthrough commands

Host-side development lifecycle:

- `php artisan runa:dev up`
- `php artisan runa:dev down`
- `php artisan runa:dev restart [service]`
- `php artisan runa:dev status`
- `php artisan runa:dev logs [service]`

Production lifecycle uses `php artisan runa:production up|down|restart|status|logs` with an explicit production Compose context. `php artisan runa:production` owns build/validate/self-test/isolation operations; `prod` may delegate to that shared implementation but must not contain a second independent Compose configuration path.

Host application wrappers:

- `./bin/artisan <arguments>`
- `./bin/composer <arguments>`
- `./bin/pnpm <arguments>`

These forward arguments safely into the intended running application container. Within the devcontainer they invoke the installed tool directly. Do not invent an npm wrapper when pnpm is the accepted manager.

`php artisan runa:doctor` without an area is the required quick whole-development-stack diagnostic. It checks repository/maintenance/docs/tooling and development runtime readiness. Area subcommands provide focused checks, not an optional substitute for the global entrypoint. Area names used by the gate definition are part of the command contract.

First installation is owner-controlled standalone delivery; see
[installation](installation.md). No default customer `runa:install`, `runa:project`
or `runa:hook` command is registered. Required internal installation/recovery
primitives stay in the platform. Runtime command
`runa:install:verify-mail <recipient>` submits the explicitly requested technical
SMTP check without printing provider errors or secrets.

Quality supports `php artisan runa:quality final --background` and `php artisan runa:quality roadmap --background`. `php artisan runa:check debug-residue` performs the focused source check used by the gate definition. The runner and referenced subcommands are implemented; each roadmap must run its selected checks before closure.

Host-only Docker lifecycle/build commands must say which terminal to use. App/knowledge commands work from host and devcontainer without granting the workspace Docker-socket privileges.

Gate-specific doctor areas include `repository`, `config`, `maintenance`, `hooks`, `frontend`, `database-migrations`, `database`, `seeding`, `boost`, `tooling`, `docs`, `release` and `cleanliness`. `./bin/pnpm run build` is the real frontend production build check. A production image scan waits for the relevant selected build; do not scan an unrelated stale image.

## Versioned Git hooks and internal launchers

`./distribution/maintenance/bin/git-hook pre-commit|commit-msg <message-file>` is the entrypoint used by `.githooks/`; normal Git commits run it automatically. Owner maintenance setup explicitly configures `git config --local core.hooksPath .githooks`; customer development startup does not alter Git hook configuration. `pre-commit` scans a temporary snapshot of the staged blobs with pinned Gitleaks defaults and redacted output, blocks environment/private-key/large files, formats staged PHP/frontend/config/docs, runs quick ESLint/Python/shell syntax checks, and checks knowledge freshness. It never runs the full test/static/security suite or auto-indexes knowledge.

If formatting would alter a partially staged file, the hook rejects it without changing that file or its index entry. Format explicitly and stage the intended hunks. Fully staged files may be formatted and restaged after checks pass. Formatting knowledge sources can then block for explicit `php artisan runa:knowledge index`; review the formatted diff before committing again. The hook uses actual staged content even when worktree content differs. `commit-msg` enforces Conventional Commits and the active roadmap scope.

`php artisan runa:doctor hooks` invokes installed scripts against a temporary Git repository with safe synthetic fixtures; it never creates fixture commits. Maintenance validator regressions: `python3 tools/testing/maintenance.py`. Host/devcontainer argument forwarding contracts: `python3 tools/testing/wrappers.py`; these simulate the host context without granting Docker access. Baseline host lifecycle acceptance and its manual editor verification boundary are recorded in closed RM-2026-001.

`./bin/boost-mcp` starts the development-only Boost stdio MCP server; `--help` exits without starting a server. `bin/command` and `bin/passthrough` are thin compatibility transports; canonical definitions are Artisan commands. All public commands provide English `--help`; failures return nonzero. `php artisan runa:dev logs [service]` is host-only and prints the last 100 development service log lines. Production logs use `php artisan runa:production logs [service]`; logs must never contain secrets.

Pinned Gitleaks 8.30.1 is stored under ignored `.runtime/tools/`, installed by development setup. The installer supports the selected Linux x86_64 environment and verifies the release archive against its published checksum and reviewed SHA256 pin. It fetches from the official GitHub release, has no application dependency and never enters the production runtime image. Missing/incompatible scanner tooling fails the hook rather than skipping it. Supply-chain sources: https://github.com/gitleaks/gitleaks/releases/tag/v8.30.1 and its `config/gitleaks.toml`. Scanning uses the built-in defaults, ignores inline allow comments and does not load staged scanner configuration. No scanner finding is suppressed.

## Production image and lifecycle implementation

`php artisan runa:production validate|build` and `php artisan runa:production up|down|restart|status|logs [service]` run from the authorized operator PHP terminal through its host executor, with host Docker Engine 26 or newer and Docker Compose 2.35 or newer (raw env-file and volume-subpath support). They always use `compose.production.yaml`, the explicit `runa-prod` (real) or `runa-prod-simulation` project and ignored `.env.prod.compose` interpolation settings. The explicit project flag prevents ambient `COMPOSE_PROJECT_NAME` from redirecting operations; ambient image-tag/port variables are removed so validated local settings control interpolation. Copy `.env.prod.compose.example` and select an exact `vX.Y.Z` release or full lowercase Git commit hash as `RUNA_IMAGE_TAG`; `latest`, branch names, abbreviated hashes and arbitrary option forwarding are rejected. `RUNA_HTTP_PORT` defaults to 8081 on loopback. The two runtime env files are `.env.prod` and `.env.prod.database`, based on their examples. Raw env-file loading preserves literal dollar signs in secrets; use unquoted values. Do not commit or print expanded environment files/configuration.

`validate` executes quiet Compose validation without printing expanded secrets. `build` requires a clean checkout matching the exact selected tag and builds PHP/Nginx from locked dependencies and then inspects those exact selected images: non-root users, required PHP extensions, no development dependencies/tools, SPA assets and Nginx config syntax. Image checks override the normal application entrypoint and require no application secrets or database. Compose still needs the runtime env files to exist; copied example files suffice for build-only checks. No production service is started by `build`.

Lifecycle `up` starts existing images and waits for health; it is not an installer and requires prior migrations/seeding and valid runtime secrets/SMTP configuration. `down` preserves all volumes. `restart`/`logs` accept at most one of the five production services; logs show the last 100 lines. Self-test/isolation, actual-image Trivy scanning, deployment and rollback have baseline host simulation evidence in closed RM-2026-001. Revalidate changed operational surfaces under the applicable roadmap gates. Image build alone cannot establish full production acceptance.

## Production operations and security

- Owner distribution only: `php artisan runa:install prod <vX.Y.Z|full-SHA> [--settings-file=path] [--non-interactive] [--simulation] [--resume]`: first host install; private TLS/SMTP settings, preserved secrets/scaffold and explicit recovery. `production` remains an alias; development uses the same command with mode dev and optional profile.
- `php artisan runa:deploy <vX.Y.Z|full-SHA> [--simulation]`: exact-version preflight/build/migrate/seed/recreate/health flow.
- `php artisan runa:deploy rollback`: previous immutable application images only, with migration/seeder compatibility checks; never automatic database reversal.
- `php artisan runa:production self-test|verify-ai-isolation`: host checks against the managed deployment, including services, images, env, volumes and sparse checkout.
- `php artisan runa:doctor production`: delegates to the same deep host self-test.
- `php artisan runa:security dependencies|secrets|trivy|review-changed-surface`: lock audits, redacted history/current-source scanning, repository plus exact production image scanning, and content-bound actual agent review evidence.

Production env files must be private (mode 600). Simulation mode must match the deploy flag and uses separate Compose resources. All production operations share an exclusive lock. Follow docs/production/deployment.md for prerequisites, failure recovery and local acceptance. Backups are customer-managed.

Trivy uses pinned 0.76.0, through an explicitly installed `.runtime/tools/trivy` or the approved `aquasec/trivy:0.76.0` host container. Images are exported to a temporary archive rather than sharing the Docker socket. HIGH/CRITICAL findings fail, with no suppression added. Detailed JSON reports are written under private ignored `.runtime/security/`, rather than echoing secret findings into terminal logs. No Trivy execution/image-scan PASS is claimed until actual host evidence is available. Agent review evidence under `.runtime/security-review.json` records a real review report and current changed-source hashes; missing/stale evidence fails rather than manufacturing a review.

Run quality selections containing host gates from the Docker host; the coordinator delegates application checks to the development container and executes production/scanner gates locally. `show`/`status` work in both contexts. Local operational contracts: `python3 tools/testing/operations.py`; production topology/context contracts: `python3 tools/testing/production.py`. Neither substitutes for actual host deployment and image-isolation verification.

Trivy collects the repository and every distinct selected runtime-image report even when an earlier scan fails; it returns a combined nonzero result after collecting the available reports. Repository dependency scanning explicitly includes development dependencies. Scan completion never downgrades a finding to success.

`python3 tools/testing/postgres-alpine.py` is a host-only disposable official PostgreSQL candidate probe. It uses no existing volume, published port or custom entrypoint/gosu; it tests the proposed ICU Polish baseline and removes its uniquely named tmpfs container. It does not adopt that candidate into runtime Compose. See `docs/production/postgresql-alpine-assessment.md`.

`python3 tools/testing/switch-postgres-icu.py` is the retained one-time host helper for the already delivered selective ICU switch. It records identities/hashes, validates locale/test guards and refuses reused targets/partial state, including the current existing ICU volume. Do not rerun it on the current cluster. The old rollback volume was separately retired by RM-2026-003; the helper performs no cleanup. See `docs/production/postgresql-alpine-assessment.md` for current disposition and dated evidence.

Approved PostgreSQL ICU production simulations now use `runa-prod-icu-simulation`, distinct from prior Bookworm simulations. `python3 tools/testing/docker-test-inventory.py` emits a read-only resource inventory without environment values for final cleanup review. No cleanup is performed. The security runner retains raw Trivy reports and applies only `docs/security/postgres-gosu-not-affected.json` after exact artifact/binary/CVE/evidence/expiry validation; disposition evidence is separate and any drift remains blocking.

An isolated installer checkout can run `php artisan runa:security trivy --dependencies-root /absolute/development/checkout` on the host. Image/repository scans target the installer checkout/deployment; dependency audits delegate read-only to the existing development app after byte-for-byte Composer/pnpm lockfile checks. No development container is started or restarted.

## Source ownership and dependency inputs

The Laravel root is `src/`; repository-root `php artisan` forwards there and framework subprocesses execute from that root. Host wrappers remain repository-relative. `./bin/composer` and `./bin/pnpm` select src automatically; direct container Composer/pnpm/vendor-bin commands run from src. Shared UI/error/translation resources and owner test suites are documented in [Application Architecture](../architecture/application-architecture.md#delivered-source-and-runtime-composition). No repository-root app/config/bootstrap/database/routes/storage links remain.

Owner dependency declarations are `src/{runa,app}/{composer,package}.json`; `python3 tools/project/dependencies.py` composes the reviewed src manifests and `--check` validates consistency without writing. It never changes locks or resolves packages. Unsupported customer fields or conflicting production/development constraints block composition. Dependency approval, Composer lock validation and frozen pnpm installation remain mandatory. Development setup and production build preparation verify composition before installation/build.

### Application manifest check

`php artisan runa:application:check` validates both owner manifests, platform manifest synchronization with VERSION, and application compatibility. Add `--platform-version=0.3.0` to check a stable candidate before operations. It reads source only, exits nonzero for invalid/unsupported/incompatible input and never bumps application version. The supported grammar and manifest contract are in [Application Architecture](../architecture/application-architecture.md#application-identity-and-compatibility). Release validation checks the calculated target with the same evaluator, preserving existing platform release arithmetic. Full updater invocation follows later RM-005 points.

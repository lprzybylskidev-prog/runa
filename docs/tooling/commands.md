# Canonical Tooling Commands

Every durable `./bin/*` command must:

- use English help/output,
- support `--help`,
- fail with meaningful exit codes,
- avoid secrets in output,
- be documented here in the same roadmap point that introduces/changes it.

This file defines the implemented and planned command surface. RM-2026-001 provides development lifecycle/profile/setup, passthrough wrappers, seeders, doctor, hooks, knowledge, local quality, security, production lifecycle/deploy/backup/state tools and frontend/backend checks. Production operational tooling requires host acceptance before point 14 is complete. Release metadata validation remains the final roadmap point; an available command is not evidence that its host/runtime gates passed.

## Development

### `./bin/dev profile`

Show or change development resource profile.

Profiles:

- low
- standard
- high
- custom

Doctor output includes effective resource values.

### `./bin/dev doctor`

Alias for `./bin/doctor development`. From the host, first validates Docker service health/setup exit status, then delegates application diagnostics into the app container. Inside the devcontainer, checks configuration/migrations, fresh shared queue/scheduler heartbeats, Nginx/Mailpit HTTP readiness, knowledge model/retrieval health and effective resource values. Shared heartbeat freshness does not replace the instance-bound Docker healthchecks. This path requires no Docker socket inside the devcontainer.

### Frontend and localization checks currently available

Inside the devcontainer: `pnpm run typecheck`, `pnpm run build`, `pnpm run dev` (HMR port 5173 at `/build/`), `python3 tools/localization/snapshot.py check`, `php tools/testing/localization.php`. `./bin/dev setup` now includes locked frontend installation/build. Snapshot review/update commands are documented in `docs/development/localization.md` and never overwrite local catalogs.

### Runtime services currently available

Inside the corresponding worker/scheduler container, `php artisan runa:runtime:health queue|scheduler` verifies its own heartbeat and database access. `php artisan schedule:list` shows the technical pulse. `php tools/testing/runtime.php` checks heartbeat/SMTP rejection; development-only `python3 tools/testing/services.py` exercises database queue -> SMTP -> Mailpit. Mailpit UI is at `http://localhost:8025`. See `docs/development/environment.md` for startup order and persistence.

## Formatting/lint/type/static analysis

- `./bin/format`
- `./bin/format --check`
- `./bin/lint`
- `./bin/typecheck`
- `./bin/analyse`

## Tests

### `./bin/test --parallel`

Run backend tests with parallel-safe isolation.

### `./bin/test frontend`

Run Vitest/Vue component tests.

### `./bin/test e2e --critical`

Run the critical Playwright browser set.

## Xdebug

- `./bin/xdebug on`
- `./bin/xdebug off`
- `./bin/xdebug status`

Xdebug is development-only and off by default.

## Seeding

- `./bin/seed default`
- `./bin/seed production`
- `./bin/seed development`
- `./bin/seed occasional <Seeder>`
- `./bin/seed graph`

Implemented now; the initial graph is empty because no business/reference/demo entities exist. Categories and dependency validation run before execution; production refuses development dependencies, including explicit development requests. `--force` deliberately bypasses the production confirmation only for automated seed execution, not destructive database commands. See `docs/data/seeding.md`.

## Knowledge/RAG

All seven knowledge commands are implemented. Search supports `--top-k 1..50`, `--explain`, `--document-type`, `--scope`, `--roadmap-id`, `--status` and `--source-path`; quote multiword queries. `--device cpu` forces CPU. Search/eval require a fresh index; doctor reports failures and rebuild explicitly resets the development collection. `python3 tools/testing/knowledge.py` runs offline ingestion contracts, `python3 tools/testing/knowledge-live.py` checks CPU/CUDA ingestion, and `python3 tools/testing/knowledge-retrieval.py` checks real hybrid retrieval in an isolated collection. `./bin/dev embeddings auto|cpu|cuda` selects host GPU policy; apply it with build/up. See `docs/development/knowledge.md`.

- `./bin/knowledge search <query>`
- `./bin/knowledge search <query> --explain`
- `./bin/knowledge index`
- `./bin/knowledge status`
- `./bin/knowledge rebuild`
- `./bin/knowledge stale`
- `./bin/knowledge doctor`
- `./bin/knowledge eval`

Production must reject/disable this command surface.

## Quality

- `./bin/quality show`
- `./bin/quality roadmap`
- `./bin/quality final`
- `./bin/quality status`

Latest-run-only local state lives under gitignored `.runtime/quality/`.

## Generic doctor

Project provides:

`./bin/doctor` and `./bin/doctor <area>`

Implemented areas: `repository`, `config`, `maintenance`, `hooks`, `frontend`, `database-migrations`, `database`, `seeding`, `boost`, `tooling`, `docs`, `cleanliness`, `development`, `production`. The `release` area currently rejects execution with a message about pending release tooling; it cannot report PASS before that implementation. `frontend` uses the real browser diagnostic script and requires the testing Nginx service. `database-migrations` resets only the guarded isolated test database; `database` and `seeding` run the relevant backend contracts. `cleanliness` rejects unreviewed scratch and untracked/unstaged files; staged reviewed changes are allowed.

Do not turn doctor into one giant opaque script. Subcommands should have clear responsibilities.

## Security

Expected wrapper surface:

- `./bin/security dependencies`
- `./bin/security secrets`
- `./bin/security trivy`
- `./bin/security review-changed-surface`

Underlying tools include:

- Composer audit,
- pnpm audit,
- Gitleaks,
- Trivy.

## Production

Expected helper surface:

- `./bin/production build`
- `./bin/production validate`
- `./bin/production self-test`
- `./bin/production verify-ai-isolation`

## Deployment

- `./bin/deploy`
- `./bin/deploy rollback`

## Backup

- `./bin/backup create`
- `./bin/backup verify`
- `./bin/backup restore`

## Logs

`./bin/logs`

Provide a concise operator view across relevant production/development services without exposing secrets.

## Project state

`./bin/project mark-production-deployed`

Use only after the first real successful production deployment to update versioned state according to `docs/production/deployment.md`.

## Destructive commands

Any destructive reset/prune/restore command must:

- state what will be destroyed,
- scope itself to Runa resources,
- require explicit confirmation interactively,
- provide an intentional safe non-interactive form only when real automation needs it.

Do not expose a "fix Docker" command that runs global host prune operations.

## Container lifecycle and passthrough commands

Host-side development lifecycle:

- `./bin/dev up`
- `./bin/dev down`
- `./bin/dev restart [service]`
- `./bin/dev status`
- `./bin/dev logs [service]`

Production lifecycle uses `./bin/prod up|down|restart|status|logs` with an explicit production Compose context. `./bin/production` owns build/validate/self-test/isolation operations; `prod` may delegate to that shared implementation but must not contain a second independent Compose configuration path.

Host application wrappers:

- `./bin/artisan <arguments>`
- `./bin/composer <arguments>`
- `./bin/pnpm <arguments>`

These forward arguments safely into the intended running application container. Within the devcontainer they invoke the installed tool directly. Do not invent an npm wrapper when pnpm is the accepted manager.

`./bin/doctor` without an area is the required quick whole-development-stack diagnostic. It checks repository/maintenance/docs/tooling and development runtime readiness. Area subcommands provide focused checks, not an optional substitute for the global entrypoint. Area names used by the gate definition are part of the command contract.

`./bin/install-prod` is the guided production installer described in `docs/production/deployment.md`; run it from the production host. No separate developer installer is required.

Quality supports `./bin/quality final --background` and `./bin/quality roadmap --background`. `./bin/quality-check debug-residue` performs the focused source check used by the gate definition. The runner and all referenced subcommands must be implemented before final verification; intended commands in this planning package are not claims that executable files already exist.

Host-only Docker lifecycle/build commands must say which terminal to use. App/knowledge commands work from host and devcontainer without granting the workspace Docker-socket privileges.

Gate-specific doctor areas include `repository`, `config`, `maintenance`, `hooks`, `frontend`, `database-migrations`, `database`, `seeding`, `boost`, `tooling`, `docs`, `release` and `cleanliness`. `./bin/pnpm run build` is the real frontend production build check. A production image scan waits for the relevant selected build; do not scan an unrelated stale image.

## Versioned Git hooks and internal launchers

`./bin/git-hook pre-commit|commit-msg <message-file>` is the entrypoint used by `.githooks/`; normal Git commits run it automatically. Setup configures `core.hooksPath .githooks`. `pre-commit` scans a temporary snapshot of the staged blobs with pinned Gitleaks defaults and redacted output, blocks environment/private-key/large files, formats staged PHP/frontend/config/docs, runs quick ESLint/Python/shell syntax checks, and checks knowledge freshness. It never runs the full test/static/security suite or auto-indexes knowledge.

If formatting would alter a partially staged file, the hook rejects it without changing that file or its index entry. Format explicitly and stage the intended hunks. Fully staged files may be formatted and restaged after checks pass. Formatting knowledge sources can then block for explicit `./bin/knowledge index`; review the formatted diff before committing again. The hook uses actual staged content even when worktree content differs. `commit-msg` enforces Conventional Commits and the active roadmap scope.

`./bin/doctor hooks` invokes installed scripts against a temporary Git repository with safe synthetic fixtures; it never creates fixture commits. Maintenance validator regressions: `python3 tools/testing/maintenance.py`. Host/devcontainer argument forwarding contracts: `python3 tools/testing/wrappers.py`; these simulate the host context without granting Docker access. Actual host lifecycle acceptance remains point 15.

`./bin/boost-mcp` starts the development-only Boost stdio MCP server; `--help` exits without starting a server. `bin/check`, `bin/passthrough` and `./bin/operations` are internal shared launchers for the public symlink commands. All public commands provide English `--help`; failures return nonzero. `./bin/logs [service]` is host-only and prints the last 100 development service log lines. Production logs use `./bin/prod logs [service]`; logs must never contain secrets.

Pinned Gitleaks 8.30.1 is stored under ignored `.runtime/tools/`, installed by development setup. The installer supports the selected Linux x86_64 environment and verifies the release archive against its published checksum and reviewed SHA256 pin. It fetches from the official GitHub release, has no application dependency and never enters the production runtime image. Missing/incompatible scanner tooling fails the hook rather than skipping it. Supply-chain sources: https://github.com/gitleaks/gitleaks/releases/tag/v8.30.1 and its `config/gitleaks.toml`. Scanning uses the built-in defaults, ignores inline allow comments and does not load staged scanner configuration. No scanner finding is suppressed.

## Production image and lifecycle implementation

`./bin/production validate|build` and `./bin/prod up|down|restart|status|logs [service]` run only in a host terminal with Docker Engine 26 or newer and Docker Compose 2.35 or newer (raw env-file and volume-subpath support). They always use `compose.production.yaml`, the explicit `runa-prod` (real) or `runa-prod-simulation` project and ignored `.env.prod.compose` interpolation settings. The explicit project flag prevents ambient `COMPOSE_PROJECT_NAME` from redirecting operations; ambient image-tag/port variables are removed so validated local settings control interpolation. Copy `.env.prod.compose.example` and select an exact `vX.Y.Z` release or full lowercase Git commit hash as `RUNA_IMAGE_TAG`; `latest`, branch names, abbreviated hashes and arbitrary option forwarding are rejected. `RUNA_HTTP_PORT` defaults to 8081 on loopback. The two runtime env files are `.env.prod` and `.env.prod.database`, based on their examples. Raw env-file loading preserves literal dollar signs in secrets; use unquoted values. Do not commit or print expanded environment files/configuration.

`validate` executes quiet Compose validation without printing expanded secrets. `build` requires a clean checkout matching the exact selected tag and builds PHP/Nginx from locked dependencies and then inspects those exact selected images: non-root users, required PHP extensions, no development dependencies/tools, SPA assets and Nginx config syntax. Image checks override the normal application entrypoint and require no application secrets or database. Compose still needs the runtime env files to exist; copied example files suffice for build-only checks. No production service is started by `build`.

Lifecycle `up` starts existing images and waits for health; it is not an installer and requires prior migrations/seeding and valid runtime secrets/SMTP configuration. `down` preserves all volumes. `restart`/`logs` accept at most one of the five production services; logs show the last 100 lines. Self-test/isolation, actual-image Trivy scanning, deployment, rollback and backup are implemented in point 14 and still require host execution evidence. Image build alone cannot establish full production acceptance.

## Production operations and security (point 14)

- `./bin/install-prod <vX.Y.Z|full-SHA> [--simulation]`: guided host install; refuses existing configuration, generates private secrets and prepares sparse checkouts.
- `./bin/deploy <vX.Y.Z|full-SHA> [--simulation]`: exact-version preflight/build/backup/migrate/seed/recreate/health flow.
- `./bin/deploy rollback`: previous immutable application images only, with migration/seeder compatibility checks; never automatic database reversal.
- `./bin/backup create|verify <backup-id>|restore <backup-id> [--confirm-runa-prod-restore]`: coordinated database/durable-file archives, actual isolated restore validation and deliberate destructive restore.
- `./bin/production self-test|verify-ai-isolation`: host checks against the managed deployment, including services, images, env, volumes and sparse checkout.
- `./bin/doctor production`: delegates to the same deep host self-test.
- `./bin/project mark-production-deployed --evidence <first-deploy.json>`: development-only one-way versioned-state recording after a real successful deployment; requires a clean active-roadmap branch and commits/pushes.
- `./bin/security dependencies|secrets|trivy|review-changed-surface`: lock audits, redacted history/current-source scanning, repository plus exact production image scanning, and content-bound actual agent review evidence.

Production env files must be private (mode 600). `.env.prod.backup` configures a private absolute backup directory and positive retention count. Simulation mode must match the deploy flag and uses separate Compose resources; it never records the irreversible production-ever-deployed transition. All production operations share an exclusive lock. Follow `docs/production/deployment.md` and `docs/production/backups.md` for prerequisites, failure recovery and local acceptance commands.

Trivy uses pinned 0.76.0, through an explicitly installed `.runtime/tools/trivy` or the approved `aquasec/trivy:0.76.0` host container. Images are exported to a temporary archive rather than sharing the Docker socket. HIGH/CRITICAL findings fail, with no suppression added. Detailed JSON reports are written under private ignored `.runtime/security/`, rather than echoing secret findings into terminal logs. No Trivy execution/image-scan PASS is claimed until actual host evidence is available. Agent review evidence under `.runtime/security-review.json` records a real review report and current changed-source hashes; missing/stale evidence fails rather than manufacturing a review.

Run quality selections containing host gates from the Docker host; the coordinator delegates application checks to the development container and executes production/scanner gates locally. `show`/`status` work in both contexts. Local operational contracts: `python3 tools/testing/operations.py`; production topology/context contracts: `python3 tools/testing/production.py`. Neither substitutes for actual host deployment/backup/restore verification.

Trivy collects the repository and every distinct selected runtime-image report even when an earlier scan fails; it returns a combined nonzero result after collecting the available reports. Repository dependency scanning explicitly includes development dependencies. Scan completion never downgrades a finding to success.

`python3 tools/testing/postgres-alpine.py` is a host-only disposable official PostgreSQL candidate probe. It uses no existing volume, published port or custom entrypoint/gosu; it tests the proposed ICU Polish baseline and removes its uniquely named tmpfs container. It does not adopt that candidate into runtime Compose. See `docs/production/postgresql-alpine-assessment.md`.

`python3 tools/testing/switch-postgres-icu.py` performs the explicitly authorized host-only selective development database switch onto a new named volume; it preserves the running app/devcontainer and old rollback volume, records identities/hashes, validates locale/test guards and restores background workers. It refuses reused targets/partial state. See `docs/production/postgresql-alpine-assessment.md`; no cleanup is performed.

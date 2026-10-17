# Canonical Tooling Commands

Every durable `./bin/*` command must:

- use English help/output,
- support `--help`,
- fail with meaningful exit codes,
- avoid secrets in output,
- be documented here in the same roadmap point that introduces/changes it.

This file defines the intended command surface. RM-2026-001 implements the initial commands. Currently implemented: `dev` profile/prepare/setup/build/up/down/config/shell/status/restart/logs/doctor and host-only embeddings selection; application passthrough wrappers; seeders; focused/global doctor; versioned hooks; knowledge; formatting/lint/type/static-analysis/testing and debugging helpers. `setup --if-needed` skips unchanged dependency/frontend work and installs the pinned Gitleaks scanner if missing. Quality/security/production/deploy/backup/project-state commands below are planned for their respective remaining roadmap points, not claims of current availability. `prod --help` exists but lifecycle operations fail explicitly without action until production Compose is implemented.

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

Implemented areas: `repository`, `config`, `maintenance`, `hooks`, `frontend`, `database-migrations`, `database`, `seeding`, `boost`, `tooling`, `docs`, `cleanliness`, `development`. The `release` area currently rejects execution with a message about pending release tooling; it cannot report PASS before that implementation. `frontend` uses the real browser diagnostic script and requires the testing Nginx service. `database-migrations` resets only the guarded isolated test database; `database` and `seeding` run the relevant backend contracts. `cleanliness` rejects unreviewed scratch and untracked/unstaged files; staged reviewed changes are allowed.

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

`./bin/boost-mcp` starts the development-only Boost stdio MCP server; `--help` exits without starting a server. `bin/check` and `bin/passthrough` are internal shared launchers for the public symlink commands. All public commands provide English `--help`; failures return nonzero. `./bin/logs [service]` is host-only and prints the last 100 development service log lines. Production logs await the production context; logs must never contain secrets.

Pinned Gitleaks 8.30.1 is stored under ignored `.runtime/tools/`, installed by development setup. The installer supports the selected Linux x86_64 environment and verifies the release archive against its published checksum and reviewed SHA256 pin. It fetches from the official GitHub release, has no application dependency and never enters the production runtime image. Missing/incompatible scanner tooling fails the hook rather than skipping it. Supply-chain sources: https://github.com/gitleaks/gitleaks/releases/tag/v8.30.1 and its `config/gitleaks.toml`. Scanning uses the built-in defaults, ignores inline allow comments and does not load staged scanner configuration. No scanner finding is suppressed.

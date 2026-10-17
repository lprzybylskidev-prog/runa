# Canonical Tooling Commands

Every durable `./bin/*` command must:
- use English help/output,
- support `--help`,
- fail with meaningful exit codes,
- avoid secrets in output,
- be documented here in the same roadmap point that introduces/changes it.

This file defines the intended command surface. RM-2026-001 implements the initial commands.

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

Validate development stack, services, health checks, runtime/tooling, and local profile.

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

See `docs/data/seeding.md`.

## Knowledge/RAG

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

Expected areas include repository/config/docs/tooling/release and other focused deterministic checks.

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

`./bin/doctor` without an area is the required quick whole-development-stack diagnostic. Area subcommands provide focused checks, not an optional substitute for the global entrypoint. Area names used by the gate definition are part of the command contract.

`./bin/install-prod` is the guided production installer described in `docs/production/deployment.md`; run it from the production host. No separate developer installer is required.

Quality supports `./bin/quality final --background` and `./bin/quality roadmap --background`. `./bin/quality-check debug-residue` performs the focused source check used by the gate definition. The runner and all referenced subcommands must be implemented before final verification; intended commands in this planning package are not claims that executable files already exist.

Host-only Docker lifecycle/build commands must say which terminal to use. App/knowledge commands work from host and devcontainer without granting the workspace Docker-socket privileges.

Gate-specific doctor areas include `repository`, `config`, `maintenance`, `hooks`, `frontend`, `database-migrations`, `database`, `seeding`, `boost`, `tooling`, `docs`, `release` and `cleanliness`. `./bin/pnpm run build` is the real frontend production build check. A production image scan waits for the relevant selected build; do not scan an unrelated stale image.

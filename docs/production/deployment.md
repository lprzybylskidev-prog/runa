# Production Deployment and Rollback

## Production installer

A production installer is required.

It should guide/check:

- host requirements,
- repository access,
- production secrets/settings,
- sparse checkout,
- exact application version,
- image/build preparation,
- service start,
- migrations,
- production seeders,
- health checks,
- AI/RAG isolation self-test.

The installer must not silently invent insecure defaults.

## Sparse checkout

Production checkout excludes development-only content when not needed at runtime, including the conceptual categories:

- `.agents/`
- `.devcontainer/`
- development-only AI/RAG service definitions
- Qdrant/embedding implementation/config
- documentation that production runtime does not require
- local development artifacts/tooling

The exact sparse checkout must still include all runtime/deploy scripts/config required for deployment and operations.

## Canonical deploy command

`./bin/deploy`

Deployment is version-oriented, not "whatever happens to be in the working tree."

Typical flow:

1. validate production environment/config,
2. resolve/check out the exact target version/commit,
3. build/pull exact runtime images as designed,
4. install Composer production dependencies from lockfile:
   - no dev dependencies,
   - prefer distribution packages,
   - optimized/autoloader behavior appropriate to Laravel,
5. build frontend assets from the locked pnpm graph,
6. run migrations,
7. run production seeders,
8. ensure required persistent-storage/public-link setup such as `storage:link` is correct and idempotent,
9. build Laravel production caches/optimization,
10. restart/update required runtime services,
11. run health checks,
12. run production self-test including AI/RAG isolation,
13. record deployment identity/state locally.

Do not run development Compose commands from production scripts.

## Migration safety

Deployment failure during migrations or production seeders is a deployment failure.

Do not pretend code rollback automatically reverses schema/data changes.

When a roadmap introduces a migration that constrains rollback:

- document it before deploy,
- design compatible rollout/expand-contract when needed,
- state whether the previous application version remains schema-compatible.

## Rollback

Canonical command:

`./bin/deploy rollback`

Deployment records the previous version/image/commit identity needed for rollback.

Rollback covers application code/container version.

Database migration reversal happens only when it is explicitly safe. Do not execute destructive `down()` automatically merely because rollback was requested.

If schema/data changes prevent safe rollback:

- stop,
- report the constraint,
- use the roadmap's documented recovery strategy.

## Deployment identity

A deployment should make it possible to identify:

- semantic project version,
- Git SHA,
- relevant image/build identity,
- deployment timestamp.

## First production deployment marker

Versioned repository state lives in:

`docs/project/state.yml`

Initial:

```yaml
production:
  ever_deployed: false
  first_deployed_at: null
```

After the first successful real production deployment:

- the state becomes true once,
- it must never return to false.

The production installer writes a local production-side marker/stamp after success. It must not push to Git from the server.

Development operator command:

`./bin/project mark-production-deployed`

This command:

- verifies the evidence/intent,
- updates versioned state,
- sets the first deployment date,
- performs sanity checks,
- creates the appropriate project commit/push according to roadmap/workflow context.

If production marker says deployed while repository state says false, stop migration editing and reconcile immediately.

## Production seeders

Deploy order is migrations then production seeders.

Production seeders are idempotent.

Production seeder failure blocks successful deployment.

## Config

Every production-critical config is validated before destructive/irreversible deployment work where possible.

Detect development-only values in production, including:

- debug modes,
- local origins,
- dev mail,
- AI/RAG enablement,
- unsafe CORS,
- invalid secret/config state.

## Pre-migration and simulation checks

Validate the resolved production configuration, dependency installation and image/build readiness before running migrations. If an upgrade or post-update health check fails, stop and attempt only the safe rollback supported by the recorded version/schema compatibility; never report success after a failed step. Report the previous and resulting version.

Local production acceptance uses an isolated Compose project, ports, secrets and persistent volumes. It cannot target a real deployment or set the irreversible production-ever-deployed flag. Host-side operations do not require the workspace container to receive Docker-socket access.

Keep `docs/project/state.yml` in the production deployment checkout even when other docs are excluded. The local first-deploy marker and the versioned state are deployment inputs, not application-image content. A first successful real deploy is reported as awaiting state reconciliation until the development workflow records the one-way transition before further migration editing. The state helper follows the active-roadmap rule; it cannot commit directly to main as an application/config fix or push from production.

## Implemented operator workflow

Run host operations on Linux with Python 3.11+, Git, Docker Engine 26+ and Compose 2.35+. No host PHP/Node installation or Docker socket in the devcontainer is required. Run commands from the operator checkout, not from `.runtime/production/releases/`. Use a dedicated production clone. `install-prod <vX.Y.Z|full-SHA>` checks host versions, collects HTTPS/SMTP/backup settings, generates separate random database credentials and a Laravel key, and writes private raw env files. It refuses existing settings. Values are unquoted single lines; literal dollar signs remain literal. TLS termination/domain configuration is an operator prerequisite.

The real installer applies a non-cone sparse allowlist to the operator checkout and each immutable release checkout. Only application/build inputs, production Docker files, operation launchers/modules and `docs/project/state.yml` remain. Development AI files, knowledge CLI, agents, devcontainer and AI documentation are excluded. Simulation preserves the development operator checkout and checks the same sparse release allowlist. Runtime images exclude even the operational Python/Git scripts.

For an existing installation, use `./bin/deploy <vX.Y.Z|full-SHA>`. Branch names, abbreviated hashes and arbitrary command forwarding are rejected. Fetch the intended trusted release into the operator repository before deployment; deploy never follows a mutable branch or fetches an unreviewed update automatically. A clean detached sparse clone is prepared under `.runtime/production/releases/<SHA>`. Build dependencies/frontend assets remain locked in the existing Docker stages. Existing complete exact-SHA image pairs are reused; an incomplete pair requires explicit operator inspection. Do not overwrite immutable image tags with diagnostic builds.

Preflight validates Compose, images, private env permissions, separate admin/runtime credentials and Laravel configuration before stopping services or running migrations. Upgrades first create a coordinated backup, then stop HTTP/application/workers/scheduler, run migrations and production seeders, recreate services and check health/root/readiness/isolation. Laravel config optimization remains per-container in the production entrypoint; static storage uses the existing Nginx public-volume subpath and needs no public symlink. Deployment has a brief maintenance outage; this baseline does not promise zero downtime.

State lives in ignored, private `.runtime/production/`. `deployment.json` records current/previous SHA, semantic version, image IDs, UTC time, context and a fingerprint of migration/seeder sources. `in-progress.json` records operations that may have changed data. An interrupted or incompatible failed operation blocks further deploy/restore/rollback until an operator inspects database/application state and follows the appropriate recovery procedure. Never delete that marker to turn a failed operation into a reported success. After recovery, preserve its evidence separately before deliberately clearing it. Preflight failure restores the original selected tag. Failure after migrations attempts application recovery only if previous migration/seeder sources are identical; the deploy still exits nonzero. Recovery failure leaves the marker in place.

`./bin/deploy rollback` verifies recorded previous image IDs and unchanged migration/seeder fingerprints, validates settings, recreates the previous application and checks health. It never calls migration rollback/down. Changed migration/seeder sources block this automatic path, even when a human might establish compatibility. Such changes require a documented recovery strategy and deliberate operator execution.

Production operations share one nonblocking lock, including lifecycle and backup commands. Logs never print expanded environment/Compose configuration. Keep the entire operator `.runtime/production` directory and env files private; they contain operational identities and configuration. Preserve secrets separately with appropriate access controls; application backup archives deliberately exclude env files.

## Isolated local production acceptance

Set `RUNA_PRODUCTION_MODE=simulation` in `.env.prod.compose` and pass `--simulation` to install/deploy. This selects `runa-prod-icu-simulation`, its own volumes/network, and the configured loopback port. Real mode selects `runa-prod`; a mode mismatch is rejected. A first managed deployment refuses existing unmanaged containers/volumes rather than adopting unknown data. Keep the prior point-13 diagnostic stack separate; stop its listener if it occupies the simulation port without deleting its volumes.

When no production env files exist:

```bash
./bin/install-prod "$(git rev-parse HEAD)" --simulation
```

With existing synthetic operator settings: add the simulation mode to `.env.prod.compose`, make `.env.prod`, `.env.prod.database` and `.env.prod.compose` mode 600, configure `.env.prod.backup` from its example with a private absolute directory, then run:

```bash
./bin/deploy "$(git rev-parse HEAD)" --simulation
./bin/production self-test
./bin/production verify-ai-isolation
./bin/backup create
# Substitute the backup ID printed by create:
./bin/backup verify <backup-id>
./bin/backup restore <backup-id>
./bin/security trivy
```

Restore asks for the exact displayed target; automation can pass `--confirm-runa-prod-restore`. Validate an upgrade/rollback using two committed, reviewed versions with identical database/seeder fingerprints; a single initial deployment has no previous release to roll back to. Do not manufacture a release tag or alter migrations merely to make that demonstration pass. Host execution evidence remains required; offline contract tests cannot prove Docker/restore/image scanning success.

## First real deployment evidence

Only a successful real deployment writes `.runtime/production/first-deploy.json`; simulation never does. Copy that private evidence to the development environment and run `./bin/project mark-production-deployed --evidence <path>`. The helper requires a clean active-roadmap branch, checks the real-deployment flag, exact known commit/version and image evidence, records the one-way first date, indexes knowledge, commits and pushes the state. It never pushes from production or turns true back to false. Do not invoke it for local acceptance.

## Gate execution context

A quality selection containing production/image gates runs from the Docker host: `./bin/quality roadmap` or `./bin/quality final`. The host coordinator runs marked host steps locally and delegates application steps to the development app container. `show` and `status` remain usable in either context, with shared latest-run evidence. Starting a host-dependent selection inside the devcontainer fails with instructions before scheduling work. Actual production self-test/isolation requires a managed sparse deployment, not just a source inspection or image build. No CI or Docker socket mount is introduced.

Reference command semantics: [Compose one-off operations](https://docs.docker.com/reference/cli/docker/compose/run/) and [PostgreSQL restore](https://www.postgresql.org/docs/current/app-pgrestore.html).

Noninteractive production Compose calls close inherited stdin by default so a pasted shell/heredoc cannot be consumed by a container client. Explicit database/storage dump and restore streams remain connected intentionally. For multi-command host acceptance, execute a saved script with `bash script.sh </dev/null` and an explicit noninteractive restore flag.

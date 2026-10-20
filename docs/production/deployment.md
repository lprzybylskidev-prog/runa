# Production Deployment and Rollback

Production operation guard: every `runa:production:*` execution, `runa:deploy` and `runa:deploy:rollback` requires the existing installer-selected acquisition mode `prod`. A development, missing or unknown installation mode is rejected before production locks/state, Docker/builds or deployment work. `APP_ENV=production`, production configuration files and `--simulation` do not bypass this restriction. Production simulations use separate production-mode fixture installations. Help, discovery and explicit nonexecuting `--plan` remain available from development. This uses acquisition context, not a first-production history flag or licensing mechanism.

## Production installer

For a new operator checkout, use the [standalone GitHub bootstrap](../tooling/bootstrap.md)
to acquire and verify an exact published release without overwriting existing
files. Public access requires no token; private access uses hidden input or a
private credential file. Acquisition can be postponed before the existing
containerized installer. Existing installed repositories still follow the
deployment/update procedure below; bootstrap never replaces a customer checkout.

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

`php artisan runa:deploy`

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

`php artisan runa:deploy:rollback`

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

Deployment journals record the successful source/image identity and pending failures; they do not publish a first-production flag or commit/push project state.

## Implemented operator workflow

Run host operations on Linux with Python 3.11+, Git 2.39+, Docker Engine 26+ and
Compose 2.35+. No host PHP/Node or Docker socket in the devcontainer is required.
Use a dedicated clean production checkout and run commands from its root, not
`.runtime/production/releases/`. The [unified installer](../tooling/installation.md)
is invoked by the separately supplied bootstrap, not a customer command. It accepts hidden interactive settings or a private
JSON file, generates separate database/application credentials once, provisions
standard Nginx TLS from supplied certificates (or verifies an explicitly selected
existing terminator), checks HTTPS and submits an SMTP verification message.
Existing settings without its own recovery state are refused. Use --resume for
an interrupted initial installation; completed repeats preserve files/secrets/data.
Raw env values stay unquoted single lines, including literal dollar signs.

The real installer applies a non-cone sparse allowlist to the operator checkout and each immutable release checkout. Only application/build inputs, production Docker files, operation launchers/modules remain. Development AI files, knowledge CLI, agents, devcontainer and AI documentation are excluded. Simulation preserves the development operator checkout and checks the same sparse release allowlist. Runtime images exclude even the operational Python/Git scripts.

For an existing acquired installation, use `php artisan runa:deploy <vX.Y.Z|full-SHA>`. Branch names, abbreviated hashes and arbitrary command forwarding are rejected. Fetch the intended trusted release into the operator repository before deployment; deploy never follows a mutable branch or fetches an unreviewed update automatically. A clean detached sparse clone is prepared under `.runtime/production/releases/<SHA>`. Build dependencies/frontend assets remain locked in the existing Docker stages. Existing complete exact-SHA image pairs are reused; an incomplete pair requires explicit operator inspection. Do not overwrite immutable image tags with diagnostic builds.

Preflight validates Compose, images, private env permissions, separate admin/runtime credentials and Laravel configuration before stopping services or running migrations. Operations enable a dedicated shared HTTP-maintenance marker, keep Nginx serving static 503 while stopping application/workers/scheduler, run migrations and production seeders, recreate the writers, reopen HTTP and check health/root/readiness/isolation. Laravel config optimization remains per-container in the production entrypoint; static storage uses the existing Nginx public-volume subpath and needs no public symlink. Maintenance returns branded HTML or English JSON 503 rather than requiring PHP to serve the notice. Nginx recreation during version switching/remounts can still cause a brief transport interruption; this baseline does not promise zero downtime.

State lives in ignored, private `.runtime/production/`. `deployment.json` records current/previous SHA, semantic version, image IDs, UTC time, context and a fingerprint of migration/seeder sources. `in-progress.json` records operations that may have changed data. An interrupted or incompatible failed operation blocks further deploy/rollback until an operator inspects database/application state and follows the appropriate recovery procedure. Never delete that marker to turn a failed operation into a reported success. After recovery, preserve its evidence separately before deliberately clearing it. Preflight failure restores the original selected tag. Failure after migrations attempts application recovery only if previous migration/seeder sources are identical; the deploy still exits nonzero. Recovery failure leaves the marker in place.

`php artisan runa:deploy:rollback` verifies recorded previous image IDs and unchanged migration/seeder fingerprints, validates settings, recreates the previous application and checks health. It never calls migration rollback/down. Changed migration/seeder sources block this automatic path, even when a human might establish compatibility. Such changes require a documented recovery strategy and deliberate operator execution.

Production operations share one nonblocking lock, including lifecycle and deployment commands. Logs never print expanded environment/Compose configuration. Keep the entire operator `.runtime/production` directory and env files private; they contain operational identities and configuration. Preserve secrets separately with appropriate access controls; backup provisioning and recovery are externally managed by the customer.

## Isolated local production acceptance

Set `RUNA_PRODUCTION_MODE=simulation` in `.env.prod.compose` and pass `--simulation`
to install/deploy. The default project remains `runa-prod-icu-simulation`; explicit
installer setting `simulation_project` may select a fresh
`runa-prod-simulation-*` project for isolated acceptance. Built-in simulation TLS
binds to loopback. Real mode selects `runa-prod` and refuses custom simulation
projects; a mode mismatch is rejected. A first managed deployment refuses existing
unmanaged containers/volumes rather than adopting unknown data. Keep prior
diagnostic stacks separate; stop a conflicting listener without deleting its data.

When no production env files exist:

```bash
# Use the exact postponed internal installer command printed by owner bootstrap,
# with --simulation --settings-file=/private/simulation.json --non-interactive.
```

With existing synthetic operator settings: add the simulation mode to `.env.prod.compose`, make `.env.prod`, `.env.prod.database` and `.env.prod.compose` mode 600, then run:

```bash
php artisan runa:deploy <full-SHA> --simulation
php artisan runa:production:self-test
php artisan runa:production:verify-ai-isolation
```

Validate an upgrade/rollback using two committed, reviewed versions with identical database/seeder fingerprints; a single initial deployment has no previous release to roll back to. Do not manufacture a release tag or alter migrations merely to make that demonstration pass. Host execution evidence remains required; offline contract tests cannot prove Docker/image scanning success.

## Gate execution context

Run normal operational commands from the separately prepared PHP operator context;
initial setup/recovery uses `./bin/artisan`, and `./bin/artisan --operator` enters
that context without host PHP or a Docker socket. In the owner development
workspace, `php artisan runa:quality:roadmap|final` forwards host work to the private
executor, preserving the dependency graph and background/no-poll contract. The
host coordinator executes host steps and delegates application checks to the
configured development container. Scanners/quality/RAG are absent from production
management/runtime artifacts. Production self-test/isolation requires a managed
sparse deployment. No CI or Docker socket mount is introduced.

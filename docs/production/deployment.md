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

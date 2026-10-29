# Canonical Tooling Commands

Every durable `./bin/*` command must:

- use English help/output,
- support `--help`,
- fail with meaningful exit codes,
- avoid secrets in output,
- be documented here in the same roadmap point that introduces/changes it.

## Runtime-independent Artisan transport

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
operational commands including runa:host:status and runa:host:stop, and optional development commands.

Pre-autoload setup and independent knowledge-init retain their existing narrow
bootstrap exceptions. Compatibility aliases remain transports, not another command
registry. Mature Docker/Git/scanner/quality mechanics stay fixed internal
helpers; application source-tool/test/Xdebug orchestration stays in PHP Actions.

### Host-plan v1 execution boundary

The isolated Artisan registry, PHP adapters and source-owned catalogues live in
`src/runa/Tooling/Management`, separate from module business management. Both
normal and cold entrypoints consume that one registry. Its retained
`Runa\Management` namespace is loaded explicitly, outside application boot.

`src/runa/Tooling/Management/host-plan.v1.schema.json` defines schema_version, request_id, operation and parameters. Parameters are empty for argument-free development actions, otherwise contain only a bounded list of literal arguments (at most 128, at most 4096 bytes each). Unknown/duplicate fields, executable/shell/env/path/mount fields, unsupported operations and mismatched request IDs are rejected again by the executor. Only fixed allowlisted helper/tool families execute; argument arrays never become shell text. A null request ID is an explicit nonexecuting preview; normal host work is request-bound by the executor. The transport supplies a fresh internal ID and consumes only the immediately returned JSON; there is no arbitrary plan-file executor. Operator-supplied --host-request is rejected. --plan never executes the operation.

Development lifecycle plans retain the shared production operation lock and unresolved-operation fencing. Production/deploy helpers retain their existing locks/recovery rules. Quality owns its stable quality lock and each gate owns its resource locks; the coordinator does not hold the production lock across its child gates. Test Actions retain the suite lock. Private mode-600 request receipts are removed after use; state/lock symlinks are rejected. Meaningful child exit codes propagate unchanged.

Management discovery runs the inspected immutable image ID with no network, mounts, forwarded env or capabilities, read-only root and bounded CPU/memory/PIDs/tmp. Combined output is capped at 64 KiB and 30 seconds; timeout/interruption removes only its named disposable container. Host operations and quality retain their own existing durations, not discovery timeouts. Background quality semantics and all hard gates remain unchanged: after launching a background roadmap/final gate, stop work and do not poll.

### Explicit operation commands

Retained recovery aliases: `./bin/format`, `./bin/lint`, `./bin/typecheck`, `./bin/analyse`, `./bin/test` (backend/frontend/tooling aggregate), `./bin/deploy`, `./bin/logs`; Composer/pnpm/Boost remain tool transports. Removed multi-action aliases are listed in the migration map. Git hooks use dedicated `tools/application/bin/pre-commit` and `commit-msg` transports. `./bin/command`, `./bin/check` and `./bin/operations` only show discovery.

Internal Python services live under `tools/application`; fixed package command modules do not form a public bin registry. Development setup starts `python3 -m tools.application.development` from the selected tool checkout. Their argument parsing is defensive process validation, not a separately documented CLI. Preserve mature Compose/scanner/Git/quality/RAG mechanics; application-facing source-check orchestration and Xdebug behavior live in PHP Actions. Do not mechanically translate process engines to PHP or introduce Docker-in-Docker.

## Development

`php artisan runa:dev:initialize` (cold host transport: `./bin/artisan runa:dev:initialize`) explicitly prepares a complete customer clone, local credentials/dependencies, forward platform data and then application migrations/DatabaseSeeder. It also prepares pending local data after pulling a customer update. Ordinary prepare/setup/up and editor reopen do not run data steps. See [development initialization](../development/environment.md#customer-repository-and-local-initialization).

Development operation guard: the entire optional development command catalog (dev lifecycle/profiles, tests, formatting/lint/type/static analysis, doctor/quality, hooks, security checks, Xdebug and knowledge/RAG) is blocked in a prod-mode installation, even if development source was copied there or the caller supplies APP_ENV=local. Existing invalid/unknown acquisition modes also fail closed. PHP checks before local Actions/host dispatch and the independent executor checks before locks, operation-state writes or Docker/tool execution. Production APP_ENV and the configured .env production value also block development tools. Owner development and cold bootstrap remain available before acquisition/environment initialization. Help/discovery and explicit nonexecuting --plan remain available. Production/deploy/host control stay operational; production runtime artifacts still omit development tooling. Native Laravel commands retain their framework safeguards; this guard concerns Runa's optional development catalog.

### `php artisan runa:dev:profile`

Show the development resource profile. Change it with `php artisan runa:dev:profile:set <profile> [settings]`.

Profiles:

- low
- standard
- high
- custom

Doctor output includes effective resource values.

### `php artisan runa:dev:up`

Host-only application startup waits for PostgreSQL, app, successful setup, Nginx, queue, scheduler and Mailpit. It then starts Qdrant, configured embeddings and `knowledge-init` automatically without waiting for AI health/indexing. Application failures return nonzero; optional AI failures report degradation. Repair optional services and repeat startup, or run `php artisan runa:knowledge:index` for an existing stale index.

### `php artisan runa:dev:knowledge-init` / `php artisan runa:dev:knowledge-status`

Inside the development container: `knowledge-init` is the independent Compose initialization job, retrying through its restart policy; `knowledge-status` reports bounded optional service/freshness availability without failing application diagnostics. Neither is part of application setup/post-create.

### `php artisan runa:dev:build` / `php artisan runa:dev:recreate`

Executed on the host through the private executor: `build [service ...]` builds selected local service images; no arguments builds all configured development images. Building does not recreate running containers. `recreate service [service ...]` requires explicit known services and runs selective `up -d --no-deps --force-recreate`; review prerequisite readiness and check health afterward. It starts no sibling dependencies and does not wait for health. App recreation can disconnect an attached editor; plan the host transition first. `restart [service]` retains image/configuration, with no service restarting the whole stack. `down` is explicitly whole-project and preserves volumes.

### `php artisan runa:doctor:development`

Alias for `php artisan runa:doctor:development`. From the host, first validates Docker service health/setup exit status, then delegates application diagnostics into the app container. Inside the devcontainer, checks configuration/migrations, fresh shared queue/scheduler heartbeats, Nginx/Mailpit HTTP readiness and effective resource values. Optional AI service/freshness diagnostics have bounded deadlines and report degradation separately; strict model/retrieval acceptance remains `php artisan runa:knowledge:doctor` and `php artisan runa:knowledge:eval`. Shared heartbeat freshness does not replace the instance-bound Docker healthchecks. This path requires no Docker socket inside the devcontainer.

## Module discovery

### `php artisan runa:modules:sync runa|app`

Trusted installation/deployment operation that discovers exactly one source owner and atomically synchronizes module registration metadata. It preserves administrative flags and installed versions, defaults new Optional modules to disabled and records missing source without deleting data. It does not install module data or load operational providers. Normal requests never invoke it. See [module discovery and registry](../architecture/module-framework.md#delivered-discovery-and-registry-operations) for migration prerequisites, snapshot semantics and current delivery limits.

### Module-owned commands and schedules

Source-discovered module commands use `<owner>:<code>:<key>` and accept bounded JSON through `--payload`. Diagnostic handlers remain available while disabled/unhealthy. Operational commands enqueue a managed owned job and return its work ID; a zero exit reports admission, not completion. Module schedules target the same classified entries, run in UTC and recheck at dispatch and worker execution. Use `php artisan schedule:list` to inspect them. See [module Artisan and schedules](../architecture/module-framework.md#delivered-module-artisan-and-schedules) for trusted recovery, overlap and retry boundaries. No module-specific global registration edits or cold host-management aliases are provided.

### `php artisan runa:permissions:declarations --payload='{"module":"app.documents"}'`

Trusted read-only module diagnostic for declaration-update impact: old/new dictionary/role memberships, distinct impacted users and source/state fingerprint. It changes no grants; actual synchronization belongs to explicit transactional module install/update. See [Permissions declarations](../modules/permissions/README.md).

### Trusted Auth operator setup

Native `runa:auth:initialize`, `runa:auth:recover` and `runa:auth:restore-policy`
run in the configured application PHP environment. Initialization supports either
`--invite --confirm` with email/name (working mail), or `--output=/private/setup.json --confirm` for a new private one-time no-mail handoff. Follow the exact options,
private path, recovery disablement and token handling rules in the
[operator guide](../modules/auth/policies-and-operators.md). These are local trusted
operator commands, with no public issuance endpoint or development-only dependency.

## Formatting/lint/type/static analysis

- `php artisan runa:format`
- `php artisan runa:format --check`
- `php artisan runa:lint`
- `php artisan runa:typecheck`
- `php artisan runa:analyse`

### `php artisan runa:test`

Run application backend, frontend and tooling tests by default. Repeated
`--scope=backend|frontend|tooling|e2e` selects scopes; e2e is opt-in.
`--filter=TEXT` selects native test names in each scope. Use one explicit scope
for native options, for example `runa:test --scope=backend --parallel`.
`runa:test:backend` remains PHP-only; `runa:test:python` discovers application Python
tests and accepts `--path=tests/tools/app/test_example.py`, `--marker=...`
and `--collect-only`. Native `php artisan test` remains PHP-only. See
[application testing](../testing/strategy.md) for authored roots, public support,
empty/failure semantics, reports and guarded cleanup.

### `php artisan runa:test:frontend`

Run only application Vitest/Vue component tests from `tests/frontend/app/unit`, using `src/vitest.config.mjs`.

### `php artisan runa:test:e2e --critical`

Run application Playwright tests from `tests/frontend/app/e2e` against the real isolated Nginx/PHP-FPM endpoint (`http://nginx-test:8080`), using `src/playwright.config.mjs`. Build assets first with `pnpm --dir src run build`. Start the Compose testing profile on the host before running inside the development container; the host entrypoint starts it automatically and removes only run-owned endpoint containers on exit. Existing manual endpoints are preserved; see [test resource lifecycle](../testing/strategy.md#test-resource-lifecycle). `RUNA_E2E_BASE_URL` can select another already provisioned application test endpoint. The runner never resets the application database or starts the platform fixture harness. Failure traces/screenshots and the HTML report go to `.runtime/artifacts/playwright/application/`. The retained `--critical` spelling runs the application suite; select individual tests using normal Playwright filters.

## Xdebug

- `php artisan runa:xdebug:enable`
- `php artisan runa:xdebug:disable`
- `php artisan runa:xdebug:status`

Xdebug is development-only and off by default.

## Seeding

- `php artisan runa:seed`
- `php artisan runa:seed:production`
- `php artisan runa:seed:development`
- `php artisan runa:seed:occasional <scenario>`
- `php artisan runa:seed:plan`

The default application graph is empty until application seeders are registered. Explicit module development contributors supply optional local Auth/Permissions scenarios; they do not enter native/default/production application seeding. Categories and dependency validation run before execution; production refuses development dependencies, including explicit development requests. `--force` deliberately bypasses the production confirmation only for automated seed execution, not destructive database commands. See `docs/runa/handbook/data/seeding.md`.

## Knowledge/RAG

- `php artisan runa:knowledge:search <query>`
- `php artisan runa:knowledge:search <query> --explain`
- `php artisan runa:knowledge:index`
- `php artisan runa:knowledge:initialize` — initializes only missing/empty collections under the shared lock; existing stale indexes require explicit indexing.
- `php artisan runa:knowledge:status`
- `php artisan runa:knowledge:rebuild`
- `php artisan runa:knowledge:stale`
- `php artisan runa:knowledge:doctor`
- `php artisan runa:knowledge:eval`

Production must reject/disable this command surface.

## Quality

- `php artisan runa:quality:plan`
- `php artisan runa:quality:roadmap`
- `php artisan runa:quality:final`
- `php artisan runa:quality:status`

Customer state lives under gitignored `.runtime/application-quality/`; owner release checks use `.runtime/quality/`.

## Security

Implemented wrapper surface:

- `php artisan runa:security:dependencies`
- `php artisan runa:security:secrets`
- `php artisan runa:security:trivy`
- `php artisan runa:security:review-changed-surface`

Underlying tools include:

- Composer audit,
- pnpm audit,
- Gitleaks,
- Trivy.

## Production

Production operation guard: every `runa:production:*` execution, `runa:deploy` and `runa:deploy:rollback` requires the locally prepared installation role `prod`. A development, missing or unknown installation mode is rejected before production locks/state, Docker/builds or deployment work. `APP_ENV=production`, production configuration files and `--simulation` do not bypass this restriction. Production simulations use separate production-mode fixture installations. Help, discovery and explicit nonexecuting `--plan` remain available from development. This uses local installation context, not a first-production history flag or licensing mechanism.

Implemented helper surface:

- `php artisan runa:production:build`
- `php artisan runa:production:validate`
- `php artisan runa:production:self-test`
- `php artisan runa:production:verify-ai-isolation`

## Deployment

- `php artisan runa:deploy`
- `php artisan runa:deploy:rollback`

## Logs

`php artisan runa:dev:logs`

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

- `php artisan runa:dev:up`
- `php artisan runa:dev:down`
- `php artisan runa:dev:restart [service]`
- `php artisan runa:dev:status`
- `php artisan runa:dev:logs [service]`

Production lifecycle uses `php artisan runa:production:up|down|restart|status|logs` with an explicit production Compose context. Separate production build/validate/self-test/isolation commands share fixed internal mechanics.

Host application wrappers:

- `./bin/artisan <arguments>`
- `./bin/composer <arguments>`
- `./bin/pnpm <arguments>`

These forward arguments safely into the intended running application container. Within the devcontainer they invoke the installed tool directly. Do not invent an npm wrapper when pnpm is the accepted manager.

Quality supports `php artisan runa:quality:final --background` and `php artisan runa:quality:roadmap --background`. `php artisan runa:check:debug-residue` performs the focused source check used by the gate definition. The runner and referenced subcommands are implemented; each roadmap must run its selected checks before closure.

Host-only Docker lifecycle/build commands must say which terminal to use. App/knowledge commands work from host and devcontainer without granting the workspace Docker-socket privileges.

Gate-specific doctor areas include `repository`, `config`, `maintenance`, `hooks`, `frontend`, `database-migrations`, `database`, `seeding`, `boost`, `tooling`, `docs`, `release` and `cleanliness`. `./bin/pnpm run build` is the real frontend production build check. A production image scan waits for the relevant selected build; do not scan an unrelated stale image.

## Versioned Git hooks and internal launchers

`./tools/application/bin/pre-commit` and `./tools/application/bin/commit-msg <message-file>` is the entrypoint used by `.githooks/`; normal Git commits run it automatically. Owner maintenance setup explicitly configures `git config --local core.hooksPath .githooks`; customer development setup configures missing hooks and preserves an existing local selection. `pre-commit` scans a temporary snapshot of the staged blobs with pinned Gitleaks defaults and redacted output, blocks environment/private-key/large files, formats staged PHP/frontend/config/docs, runs quick ESLint, staged Ruff format/lint and Python/shell syntax checks, and checks knowledge freshness. It never runs the full test/static/security suite or auto-indexes knowledge.

If formatting would alter a partially staged file, the hook rejects it without changing that file or its index entry. Format explicitly and stage the intended hunks. Fully staged files may be formatted and restaged after checks pass. Formatting knowledge sources can then block for explicit `php artisan runa:knowledge:index`; review the formatted diff before committing again. The hook uses actual staged content even when worktree content differs. `commit-msg` enforces Conventional Commits and the active roadmap scope.

`./bin/boost-mcp` starts the development-only Boost stdio MCP server; `--help` exits without starting a server. `bin/command` and `bin/passthrough` are thin compatibility transports; canonical definitions are Artisan commands. All public commands provide English `--help`; failures return nonzero. `php artisan runa:dev:logs [service]` is host-only and prints the last 100 development service log lines. Production logs use `php artisan runa:production:logs [service]`; logs must never contain secrets.

Pinned Gitleaks 8.30.1 is stored under ignored `.runtime/tools/`, installed by development setup. The installer supports the selected Linux x86_64 environment and verifies the release archive against its published checksum and reviewed SHA256 pin. It fetches from the official GitHub release, has no application dependency and never enters the production runtime image. Missing/incompatible scanner tooling fails the hook rather than skipping it. Supply-chain sources: https://github.com/gitleaks/gitleaks/releases/tag/v8.30.1 and its `config/gitleaks.toml`. Scanning uses the built-in defaults, ignores inline allow comments and does not load staged scanner configuration. No scanner finding is suppressed.

## Production image and lifecycle implementation

`php artisan runa:production:validate|build` and `php artisan runa:production:up|down|restart|status|logs [service]` run from the authorized operator PHP terminal through its host executor, with host Docker Engine 26 or newer and Docker Compose 2.35 or newer (raw env-file and volume-subpath support). They always use `compose.production.yaml`, the explicit `runa-prod` (real) or `runa-prod-simulation` project and ignored `.env.prod.compose` interpolation settings. The explicit project flag prevents ambient `COMPOSE_PROJECT_NAME` from redirecting operations; ambient image-tag/port variables are removed so validated local settings control interpolation. Copy `.env.prod.compose.example` and select an exact `vX.Y.Z` release or full lowercase Git commit hash as `RUNA_IMAGE_TAG`; `latest`, branch names, abbreviated hashes and arbitrary option forwarding are rejected. `RUNA_HTTP_PORT` defaults to 8081 on loopback. The two runtime env files are `.env.prod` and `.env.prod.database`, based on their examples. Raw env-file loading preserves literal dollar signs in secrets; use unquoted values. Do not commit or print expanded environment files/configuration.

`validate` executes quiet Compose validation without printing expanded secrets. `build` requires a clean checkout matching the exact selected tag and builds PHP/Nginx from locked dependencies and then inspects those exact selected images: non-root users, required PHP extensions, no development dependencies/tools, SPA assets and Nginx config syntax. Image checks override the normal application entrypoint and require no application secrets or database. Compose still needs the runtime env files to exist; copied example files suffice for build-only checks. No production service is started by `build`.

## Production operations and security

- `php artisan runa:deploy [target] [--simulation]`: fetch the configured customer repository (default `main`, or branch/tag/full SHA), build the complete selected project, run forward Runa migrations/required seeds/module upgrades then application migrate/DatabaseSeeder, recreate selected services and verify health/isolation. See [deployment](../production/deployment.md) for remote selection and recovery.
- `php artisan runa:deploy:rollback`: previous immutable application images only, requiring proven schema/data compatibility; current data-changing deployments block automatic rollback. Never reverses database changes.
- `php artisan runa:production:self-test|verify-ai-isolation`: host checks against the managed deployment, including services, images, env, volumes and common customer-source exclusions.
- `php artisan runa:production:self-test`: delegates to the same deep host self-test.
- `php artisan runa:security:dependencies|secrets|trivy|review-changed-surface`: lock audits, redacted history/current-source scanning, repository plus exact production image scanning, and content-bound actual agent review evidence.

Trivy uses pinned 0.76.0, through an explicitly installed `.runtime/tools/trivy` or the approved `aquasec/trivy:0.76.0` host container. Images are exported to a temporary archive rather than sharing the Docker socket. HIGH/CRITICAL findings fail, with no suppression added. Detailed JSON reports are written under private ignored `.runtime/security/`, rather than echoing secret findings into terminal logs. Runtime-image scanning requires the locally prepared production checkout and uses its selected deployment images. The selected deployment lockfiles must match the scanned/audited source; use the same customer revision rather than auditing different dependency inputs. Agent review evidence under `.runtime/security-review.json` records a real review report and current changed-source hashes; missing/stale evidence fails rather than manufacturing a review.

Trivy collects the repository and every distinct selected runtime-image report even when an earlier scan fails; it returns a combined nonzero result after collecting the available reports. Repository dependency scanning explicitly includes development dependencies. Scan completion never downgrades a finding to success.

An isolated installer checkout can run `php artisan runa:security:trivy --dependencies-root /absolute/development/checkout` on the host. Image/repository scans target the installer checkout/deployment; dependency audits delegate read-only to the existing development app after byte-for-byte Composer/pnpm lockfile checks. No development container is started or restarted.

## Source ownership and dependency inputs

Owner dependency declarations are `src/{runa,app}/{composer,package}.json`; `python3 -m tools.application.dependencies` composes the reviewed src manifests and `--check` validates consistency without writing. It never changes locks or resolves packages. Unsupported customer fields or conflicting production/development constraints block composition. Dependency approval, Composer lock validation and frozen pnpm installation remain mandatory. Development setup and production build preparation verify composition before installation/build.

## Owner tooling and delivery layout

- bin: thin customer tool/setup/recovery transports.
- tools/application: customer development, operations, security, hooks and knowledge implementations.
- tools/maintainer: owner-only platform test harnesses, release metadata, diagnostics and quality entrypoints; excluded from customer projection.
- distribution: external acquisition/initialization, installation gateway adapters and development updater; excluded from customer projection.

Standard make:* commands generate application artifacts. make:controller, make:model, make:request and make:provider use the App namespace; migrations/seeders/factories use src/app/database; make:config uses src/app/config and make:test uses tests/backend/app. Provider registration is customer-owned src/app/providers.php. Platform assets use explicit paths so native generators never need to write into src/runa.

### `php artisan runa:modules:settings [module]`

Trusted redacted settings read. `--write --revision=N --generation=N` accepts a bounded JSON envelope from piped standard input, never secret arguments. `--recover=UUID` without a module restores/compensates an interrupted settings change. Failure/ambiguous recovery exits 1 with safe diagnostics. See the [declarative settings contract](../architecture/module-framework.md#delivered-declarative-settings) for payload, revision, handler and external-key semantics. No management HTTP endpoint or Admin UI is supplied.

### `php artisan runa:modules:management <module>`

Trusted versioned combined management metadata: localized identity, status/root causes/dependencies, redacted settings, integrations, bounded active work and declared permissions/actions/component keys. Exit 1 means missing/incomplete metadata or a concurrent generation change; disabled/unhealthy state remains inspectable. No action is executed and no management HTTP endpoint is exposed. See the [management descriptor contract](../architecture/module-framework.md#delivered-management-descriptors).

### `php artisan runa:make:module <owner.code> [type] [name]`

Development-only owned source generator. Defaults to a complete nonmutating JSON Lines preview. `--write` applies; repeated `--overwrite=repository/path=SHA256` explicitly authorizes each existing target, including changed manifest declarations. Types: module, controller, model, migration, service, action, api, component and test. Central owner docs/test scaffold and safe refusing stubs follow the [module development guide](../development/modules.md). No registry/migration/activation side effect; Runa generation additionally requires a receipt-free owner checkout.

### `php artisan runa:modules:check <runa|app>`

Development source ownership/manifest/required graph checks without executing module source. `--runtime` adds synchronized trusted descriptor validation through existing resolvers without running operations, health checks or lifecycle/settings/update hooks. Failures exit 1. Reusable test suite, literal-analysis limitations and independent `python3 tools/application/module_versions.py --owner app --base main` version/changelog checks are documented in the [module development guide](../development/modules.md).

## Quality selection options

`runa:quality:plan`, `runa:quality:roadmap` and `runa:quality:final` accept repeated
`--group=NAME` or standalone `--all`. Background remains limited to roadmap/final;
status takes no flags. See [quality groups and examples](../quality/README.md).
The native Artisan registry and host transport preserve and validate these options.

### Auth invitation reminders

Installed Auth declares `php artisan runa:auth:entry.remind` as an operational
ModuleWork command and a minute schedule with overlap protection. Reminders are
disabled by default and bounded by encrypted module settings; see the
[Auth transport procedure](../modules/auth/transport.md). Administrative invitation
issue/resend/invalidation uses the protected typed contract, not credentials or raw
tokens in CLI arguments. Delivered [Auth entry screens](../modules/auth/entry-ui.md) and [account screens](../modules/auth/account-ui.md) consume these guarded endpoints. Integrated operator commands are described below.

### Auth session cleanup

`php artisan runa:auth:sessions.cleanup` admits operational ModuleWork cleanup;
its zero exit indicates admission, not completion. The five-minute schedule removes
bounded expired/revoked/stale technical session/trust/login-window rows and preserves
Audit or password history. It also removes bounded expired step-up proofs, profile mail/requests and disposable unreferenced private avatar files; current/disabled avatars and address identities remain preserved. See the [profile contract](../modules/auth/profile.md). See the
[session/abuse contract](../modules/auth/sessions.md) and [credential contract](../modules/auth/security.md).

### Auth security reminders

`php artisan runa:auth:security.remind` admits bounded operational reminder work;
zero means admission, not completion. Its minute schedule rotates through 100
accounts, revalidating exact deadlines and current recipients before delivery.
See [preferences and security delivery](../modules/auth/preferences.md). Normal
recovery mail remains direct and independent of a Notifications module.

### Auth policy and operator commands

Native local `php artisan runa:auth:initialize`, `runa:auth:recover` and
`runa:auth:restore-policy` perform the fixed trusted operator operations. Each
requires `--confirm`; initialize accepts `--email`, `--name`, optional `--login`
and either a new absolute private `--output` file or `--invite` for configured mail; recover takes `--user` UUIDv7 and
`--output`; policy restoration takes `--revision`. Account commands use the integrated Permissions OperatorAuthority adapter and require current exact Core source/data, no update fence and mandatory Audit. There is no
temporary administrator flag or general web bypass. The external
`AUTH_OPERATOR_RECOVERY_ENABLED=false` switch disables issuance/redemption and
operator policy restoration. Secrets are written only to the private file, never
stdout/stderr or command arguments. See the complete
[policy/operator procedure](../modules/auth/policies-and-operators.md) for
filesystem paths, 15-minute handoff, repeated initialization and recovery behavior.

`php artisan runa:auth:lifecycle.apply` admits bounded current-predicate scheduled
activation/deactivation work; zero means admission, not completion. Its minute
schedule, authority integration and Audit semantics follow the same guide.

### Module development fixtures and private initial setup

`runa:seed:plan --modules` previews the explicit application plus module development
graph; `runa:seed:development --modules` executes it only in local/testing console
context. `runa:seed:accounts` lists safe local fixture identifiers and unset-password
status. `runa:seed:accounts <scenario> --setup` uses hidden interactive confirmation
to initialize only an absent password; it accepts no password argument and never
replaces existing credentials. Production receipt/environment checks apply even
with local APP_ENV or --force. Native db:seed/default/production seeding remain
application-owned. See [seeding](../data/seeding.md#explicit-module-development-contributors)
for prerequisites, independent tuning, contribution contracts and manual navigation.

## Python development commands

`./bin/artisan runa:python:setup` prepares the shared frozen Python tools in the
app container. `runa:python:format [--check]`, `runa:python:lint` and
`runa:python:typecheck` cover delivered tooling and application source. These three
commands accept `--config=src/app/<file>` for application-owned composition;
see [Python tooling](../development/python-tooling.md). Inside the container use
`php artisan`. No host pip/package installation is needed. Private owner tests
continue through the maintainer test transport, outside the customer registry.

`runa:analyse` checks application PHP at the configured maximum level and includes
public PHP import/signature boundary checks before PHPStan;
`runa:lint` includes application TS/Vue import boundaries. Their existing quality
nodes retain ownership. See [API enforcement](../architecture/public-api.md#local-enforcement-and-delivery)
for diagnostics, supported consumers and static-analysis limits. Module source/runtime
checks remain trusted operator diagnostics in production; developer analysis,
generation and test commands remain development-only.

### Knowledge evidence

`php artisan runa:knowledge:read <chunk_id> [--neighbors=0..5] [--section]
[--max-chars=1000..60000] [--json]` returns source-verified indexed content and
bounded context as JSON. Search/status accept `--json`; existing search
`--explain` and JSON status output remain compatible. See
[evidence reading](../development/knowledge.md#evidence-reading-and-structured-output)
for budgets, citation nulls, freshness refusals and the development-only boundary.

Knowledge search accepts explicit `--intent=legacy|current|history|audit` and
`--per-source=1..10`, retaining existing filters/top-k/explain. See
[retrieval intent](../development/knowledge.md#retrieval-intent-and-evidence-limits)
for ranking factors, single-source expansion and interpretation limits.

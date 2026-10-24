# Application Architecture

## Backend shape

Laravel is the backend framework. The application frontend is a Vue SPA consuming `/api/...` endpoints. Blade is not the application rendering layer.

Controllers remain transport-oriented:

- accept validated input,
- call application behavior,
- return resources/responses.

Substantial use cases belong in Actions. Shared/cross-cutting or integration behavior may use Services. Complex read/list/report behavior may use Query classes. Simple Eloquent behavior may stay direct when it is clearer.

Do not create generic `SomethingService`, `Helpers`, `Utils`, or repository layers as dumping grounds.

## Application behavior

### Actions

Use an Action when a use case has meaningful orchestration, state transitions, multiple collaborators, transaction boundaries, or reusable application behavior.

### Services

Use a Service for genuinely shared capability, cross-cutting technical behavior, or integration boundaries. A Service must have a clear responsibility.

### Query classes

Use Query classes for complex reads:

- non-trivial filtering,
- reports,
- aggregates,
- multi-relation listings,
- reusable query construction.

Queries are read-only by design.

### DTOs

Use DTOs when input is complex, crosses multiple layers, belongs to an integration contract, or loose arrays would obscure meaning. Do not create a DTO for every endpoint mechanically.

### Events

Events represent facts that already happened. Prefer past-tense names. Use them for real asynchronous/cross-cutting reactions, not as disguised commands. A critical immediate cross-module operation should usually be an explicit call.

### Jobs

Use jobs for asynchronous, heavy, or long-running work. Jobs delegate business behavior to Actions/Services rather than becoming the business layer.

Where duplicate execution can happen:

- make jobs idempotent when possible,
- define retry/backoff deliberately,
- classify dependency failures as retryable or non-retryable.

### Lifecycle/state transitions

Business status transitions go through explicit application behavior, normally an Action. Do not scatter raw status updates. Validate allowed transitions.

Do not install a state-machine dependency until repeated, real behavior justifies it.

## Transactions and side effects

Use short database transactions for atomic multi-record changes.

Do not keep external HTTP calls, long file operations, or other slow external effects inside database transactions.

Dispatch work/events after commit when they depend on committed data.

Nested transactions/savepoints are conscious choices, not accidental framework behavior.

## Dependency direction

Dependencies should be readable. Introduce an interface when there is a real boundary, swap requirement, or integration seam. Do not create interfaces for every class.

Use explicit dependency injection. Avoid service-locator patterns such as scattered `app()` calls in business code.

Facades may be used pragmatically at framework boundaries when they remain clear and testable.

## Hidden behavior

Avoid critical business behavior in:

- Eloquent observers,
- model hooks,
- hidden global scopes,
- accessors/casts that issue database queries,
- implicit static/global mutable state.

Global scopes are reserved for true global invariants, not hidden business filters.

## Time and randomness

When logic depends on "now", use a controllable/testable time source.

When randomness affects behavior, make it controllable for tests and deterministic workflows.

## Refactoring

A refactor should preserve behavior unless behavior change was discussed. Protect risky refactors with tests before changing structure.

## Cache contract

Cache only an identified expensive read/computation. Document the canonical source, namespaced key, purpose, TTL and invalidation path. Cache never becomes the source of truth. Use Laravel cache APIs rather than coupling business logic to a specific driver. Redis remains deferred.

Each request has a safe correlation ID for diagnostics, propagated to related work where useful; validate externally supplied identifiers before logging them.

## Platform and customer ownership

### Delivered composition

One Laravel application lives under `src`, with one autoloader, container and composed dependency graph. Customer business code can remain ordinary PHP/Vue; module roots are reserved for RM-2026-006 and have no registry/runtime behavior here.

```text
repository/
  src/
    app/                         # customer PHP, config, routes, lang, resources
      providers.php              # application provider registrations
      application.json           # independent identity/version/compatibility
      database/                  # application migrations/explicit seeders/factories
      tests/                     # application PHP/frontend/browser tests
    runa/                        # protected platform namespace directories and resources
      platform.json              # platform runtime/build identity
      database/                  # isolated platform migrations/required seed root
      management/                # cold commands; excluded from runtime images
    tests/{TestCase.php,Pest.php} # guarded native application test support/discovery
    bootstrap/                   # Laravel composition and generated cache
    public/                      # front controller and published generated assets
    storage/
      app/{application,runa}/{private,public}/
      framework/                 # shared sessions/cache/views/health/maintenance
      logs/
    artisan
    composer.json, composer.lock # composed dependency inputs and reviewed lock
    package.json, pnpm-lock.yaml
  docs/app/                      # application documentation and roadmaps
  docs/runa/handbook/             # public Runa procedures/contracts
  .agents/skills/                # supplied customer agent guidance
  bin/, tools/application/, docker/, .devcontainer/
```

Both owner roots place namespace directories/config/database/lang/resources/routes directly under src/app or src/runa; there is no extra nested src directory. Only delivered src/runa is the external updater's integrity/replacement boundary. Compose, hooks, bootstrap/build/test configuration, shared tools and root VERSION are customer-controlled scaffold. Customer repositories version the same complete project for development and production; private settings, data, dependencies and runtime artifacts differ.

The owner checkout additionally contains distribution, tools/maintainer, platform tests/browser harnesses and docs/runa/maintainer. The fixed shared exclusion map in tools/application/repository_boundary.py and standalone distribution/bootstrap.py keeps them out of customer delivery. Never infer a production host-source allowlist from the narrower runtime image contents. Platform test namespaces/autoloading are owner-only; native customer tests select src/app/tests. See [public tests](../../handbook/testing/strategy.md) and [owner testing](../testing/strategy.md).

### Public extension points and runtime

RunaServiceProvider owns configuration/database guards, mail/identity integration and worker heartbeats. Application providers use src/app/providers.php; overrides, translations, routes and generators target application roots. The welcome application uses SpaController and named route spa at / and /build/index.html. Public health/readiness/identity APIs retain their named /api routes. Vue consumes the public @/runa facade with explicit Vite/Vitest/TypeScript aliases preceding application @; platform internals resolve inside Runa. See [public architecture](../../handbook/architecture/application-architecture.md) and [frontend contracts](../../handbook/architecture/frontend.md).

The workspace bind mount is repository .:/workspace, while Laravel's base path is /workspace/src. Root private env files are loaded explicitly. Nginx serves src/public; development uses PHP-FPM, never artisan serve. Production Dockerfiles copy explicit runtime directories, excluding management, tests, scanners, Boost, AI and build/development tools. Host production retains shared customer operational/development source, guarded by its local production role; runtime containers receive no operator executor mount or Docker access.

One src/storage tree separates durable application/platform disks and file cache namespaces. Framework state/logs remain technical shared state. Nginx mounts only public application/platform subtrees read-only at /storage/ and /runa-storage/. Operations preserve both durable trees, database history/data and private settings/journals. Backups/recovery are externally managed by the customer. Native application destructive/data commands preserve platform objects; the narrow fixed forward platform primitive coordinates explicit initialization, updates and deployment. See [database](../../handbook/architecture/database.md) and [seeding](../../handbook/data/seeding.md).

### Dependency and build composition

Platform src/runa/{composer.json,package.json} and optional application src/app/{composer.json,package.json} compose into src/{composer.json,package.json}. Application inputs accept dependency sections only; protected autoload/scripts/build metadata stays platform-composed. Duplicate declarations must match exactly in the same production/development section. Conflicts/unsupported fields fail. tools/application/dependencies.py never generates/replaces locks or upgrades dependencies; validate the Composer lock and frozen pnpm installation. New dependencies require explicit approval.

Runtime-independent management explicitly loads command classes and stays outside Composer classmap scanning. The Compose setup helper prepares dependencies before Composer exists; knowledge-init is an independent optional Python path without PHP/vendor/application-kernel prerequisites. The PHP Artisan entrypoint and named commands remain the public interface; Python executors implement host/quality/scanner/Git/RAG mechanics without another domain-command registry. See [commands](../../handbook/tooling/commands.md) and [host transport](../../handbook/tooling/host-executor.md).

Vite builds src/app/resources/index.html into src/public/build. Platform error-renderer inputs live in src/runa; generated private shells live in .runtime/frontend/errors/templates, public emergency assets in src/public/runa-error. Production images contain generated documents/assets and required PHP views, outside persistent storage mounts. Browser reports and owner test harness output never enter production images.

### Identity and compatibility

Customer src/app/application.json defines independent identity/version and supported platform range. Protected src/runa/platform.json defines platform identity for PHP, browser, error/mail and production builds. Root VERSION controls owner release arithmetic only; release checks synchronize the platform manifest without changing application version. The bundled Runa-named application is a placeholder at 0.0.0. The authoritative manifest/range/public DTO and branding contracts are in [configuration](../../handbook/architecture/configuration.md).

ApplicationManifest/VersionCompatibility validate before configuration and external update execution. runa:application:check checks installed platform identity or an explicit candidate version. Public /api/identity exposes only the identity DTO with no-store; Vue uses its bounded runtime projection and build-time fallback. Private paths, supported ranges, credentials and schema internals stay outside responses. Customer branding configuration cannot override canonical manifest identity.

### Acquisition, update and deployment

External distribution/bootstrap.py obtains a verified Runa release for first development; distribution/initialize.py selects a complete customer branch/tag/SHA for teammates/production. Atomic publication refuses existing destinations and preserves customer remotes/history. Clones prepare their own local dev/prod role; another workstation's acquisition receipt/journal is unnecessary. Local role metadata is not a trusted source inventory or signature. See [acquisition](../tooling/bootstrap.md) and [installation](../tooling/installation.md).

The development-only external updater independently retrieves original/target releases, compares delivered src/runa, checks dependencies/compatibility and reviews customized infrastructure. Read-only preflight/plan is nonmutating. Apply/resume uses the existing operation lock and private pending journal, fences HTTP/writers, replaces only platform source, builds and executes platform forward migrations/required seeds. Resume revalidates source/tag/database identities and completed effects. It preserves customer scaffold/locks/infrastructure/application data; changed customization requires a new bound continue/abort decision. No automatic code/data rollback, customer commit/push or infrastructure engine exists. See [updates](../tooling/updates.md).

Customer developers manually reconcile release-required scaffold/container/env changes, verify and commit/push the complete project. Teammates pull and explicitly initialize pending local data; ordinary reopen/setup/up remains nondestructive. Production deploy fetches only the tested customer revision and runs forward platform migrations/required seeds before application migrations/explicit DatabaseSeeder. Additional declared services/volumes are supported, with actual config/image/runtime/AI isolation hard gates. Production never accesses owner releases or runs the owner updater. See [reconciliation](../../handbook/release/platform-reconciliation.md) and [deployment acceptance](../production/deployment.md).

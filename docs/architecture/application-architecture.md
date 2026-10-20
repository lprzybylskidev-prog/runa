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

### Agreed target, not yet relocated

RM-2026-005 point 1 establishes the following composition contract. At this point the working Laravel application still runs from the repository root; the relocation is delivered in subsequent points. The owner approved the `src/`, `app/`, `runa/` and storage boundaries on 2026-10-20. Do not interpret this target tree as a currently available startup procedure.

```text
repository/
  src/                           # one Laravel application base path
    app/                         # customer source, scaffolded once
      Http/Controllers/          # initially SpaController for named route spa
      Providers/                 # thin application bindings/contributions
      routes/                    # initially GET /
      resources/                 # welcome App.vue, main.ts, index.html, custom assets
      config/                    # application-owned overrides
      database/                  # customer migrations/seeders/factories
      lang/                      # application translations/overrides
      tests/                     # customer tests
      docs/                      # customer documentation
      application.json           # independent identity/version/compatibility
    runa/                        # protected platform source
      src/                       # Runa namespace; providers/HTTP/console/support
      config/                    # platform defaults
      routes/                    # health/readiness and future platform routes
      database/                  # technical migrations/seeding
      resources/                 # UI library, styles, images, fonts, views, errors, lang
      tests/                     # platform PHP/frontend/browser tests and fixtures
    bootstrap/                   # protected composition and generated cache
    public/                      # protected front controller and published build/assets
    storage/
      app/
        application/{private,public}/
        runa/{private,public}/
      framework/                 # shared Laravel sessions/views/cache/health/maintenance
      logs/                      # shared technical logs
    artisan
    composer.json                # composed dependency inputs
    composer.lock
    package.json                 # composed dependency/build inputs
    pnpm-lock.yaml
    vendor/                      # generated, not release source
    node_modules/                # generated development/build dependencies
  bin/, tools/, docker/, docs/    # Runa-owned repository infrastructure and guidance
```

Module roots `src/runa/modules/` and `src/app/modules/` are reserved for RM-2026-006, not registered or implemented by RM-005. Customer business code need not be modular. Protected source ownership is independent of directory nesting: Compose, Git hooks, development configuration, quality definitions, deployment metadata and root VERSION remain Runa-owned outside `src/runa/`.

### Source classification and initial scaffold

Almost all current application source is platform behavior. Current `app/Console`, `app/Database`, `app/Support`, middleware, health/readiness controllers and provider behavior move into `src/runa/src/` under `Runa\`. The existing base Controller is platform-owned. Split `AppServiceProvider`: its existing guards, migration repository, mail namespace/branding, configuration validation and heartbeats become platform behavior; customer bindings retain a thin `App\Providers\AppServiceProvider`.

Only `routes/spa.php`, `app/Http/Controllers/SpaController.php`, `resources/js/App.vue`, `resources/js/main.ts` and `resources/index.html` form the initial welcome-page scaffold. Their targets are `src/app/routes/web.php`, `src/app/Http/Controllers/SpaController.php`, `src/app/resources/js/{App.vue,main.ts}` and `src/app/resources/index.html`. Its controller can depend on a public Runa base controller contract. The named route remains `spa`, serving `/` and `/build/index.html`; no catch-all route is introduced. The welcome page consumes the platform MinimalLayout. Keep its page copy/application identity configurable without moving shared layouts or translation infrastructure into customer ownership.

All other existing resources, technical configuration, queue migrations, managed seeding, Polish validation snapshot, mail/error templates, supplied favicon and tests belong to Runa. Preserve attribution/license files with relocated assets. `DatabaseSeeder`'s orchestration is platform behavior; scaffold an empty customer seed contribution separately. Existing platform config is not silently reclassified as an editable customer config file.

Generate application directories, manifests and thin registration inputs once; do not regenerate over an existing customer tree. Platform updates never delete, merge or replace customer files. Protected templates/default assets remain separately owned; application branding is supplied through configuration/public asset publication rather than editing protected originals. Publish customer assets through declared destinations that cannot collide with protected generated errors, platform assets or the front controller. Tests of either owner run only through the development/test graph.

### Dependency and build composition

Keep one Laravel container, one autoloader and one composed dependency graph. Map `Runa\` to `src/runa/src/` and `App\` to `src/app/`; give application and platform factories/seeders/tests distinct namespaces during relocation. Paths here are repository-relative; Composer paths in `src/composer.json` are relative to `src/`.

Platform dependency declarations/build defaults belong in `src/runa/{composer.json,package.json}`. Optional application declarations live in `src/app/{composer.json,package.json}` and are preserved. Produce reviewed `src/composer.json`, `src/package.json` and their locks from both owners' declared inputs. Initial composition preserves all current pinned dependencies and scripts' semantics. Do not run a dependency upgrade as a side effect of relocation. Platform source retrieval supplies its declarations, not a replacement for customer/composed locks. Conflicting declarations or an unusable frozen lock block preparation before maintenance. Resolve deliberate dependency changes in the owning roadmap with the required approvals. Generated composition metadata is explicitly enumerated and validated against its inputs, not a blanket integrity exclusion.

Runa owns the Vite/error-renderer, TypeScript, lint, format, test and static-analysis configuration. Move app-relative build/test configuration to `src/` as protected composition inputs; leave host orchestration at repository root. Vite builds the customer entry in `src/app/resources/index.html`, consuming shared styles/assets from `src/runa/resources/`; output remains `src/public/build/`. Register `@/runa/ui` and its existing subpaths explicitly in Vite, Vitest and TypeScript before the application `@/` alias, so customer code cannot shadow platform facade imports. Platform internal imports must resolve inside Runa rather than accidentally through the application alias. Preserve the facade exports and all layout/provider/types/contracts; no router or layout becomes a prerequisite for standalone controls.

The Node error-renderer and PHP finalizer consume platform-owned resources and emit generated `src/runa/resources/views/errors/generated` plus `src/public/runa-error` and ignored renderer output. They remain build-time operations; production contains only generated documents/assets and required PHP views. Test harness output stays outside public/production graphs. Update test discovery for both owners without loading test fixtures in HTTP routes.

### Runtime and infrastructure composition

The bind mount remains repository `.:/workspace`, with editor workspace `/workspace`. Laravel's base path becomes `/workspace/src`; application commands and PHP-FPM/Nginx paths must use that base explicitly. Host wrappers still start from the verified repository checkout. Development shell/tool launchers must not confuse repository-relative `bin/` with application-relative `artisan`, autoloaders and lockfiles. Existing `.env` inputs stay private at repository root; wire Laravel's environment loading and test configuration explicitly before switching the base path. Preserve Compose project identities, volume identities and credentials.

Update Docker build context allowlists/COPY instructions, Nginx roots, PHP entrypoints/health probes, Compose workdirs/mount subpaths, devcontainer post-create, wrappers, Boost launcher/config, knowledge allowlists, hooks, quality/source runners and deployment sparse checkout together with their affected moves. Sparse source must include protected platform and preserved customer inputs, while runtime images still exclude tests, tooling, scanners, Boost and AI. Do not broadly copy `src/` into production as a shortcut. Root VERSION remains platform release authority; consumers in src must read it via an explicit build/config input.

`src/storage/` is the single writable storage root, separate from both source owners. Use explicit application/platform filesystem disks rooted at `app/application/{private,public}` and `app/runa/{private,public}`; cache keys likewise include owner and application identity. Framework cache/sessions/views/health/maintenance and logs remain shared technical runtime state. Test workers retain their isolated storage and cache namespaces. Preserve the current default private-disk behavior until explicit application/platform disk configuration replaces it.

Backup/restore covers both durable trees under storage/app plus PostgreSQL, using the existing coordinated lock/fencing/archive contract. Runtime framework state/logs remain excluded. Nginx mounts only each public subtree read-only, with separate explicit URL mappings; it cannot traverse either private subtree. Startup creates missing directories without deleting data. Updates preserve storage and private operation journals; a source-tree switch is never a storage replacement.

### Relocation and preservation acceptance

Before the move, inventory every tracked path with its source digest, symlink identity, owner, target and generated/runtime classification. RM-005 point 1 records its dated snapshot under `.runtime/ownership/`; this evidence describes the audited commit, not a permanent integrity baseline for later releases. At point 9 generate the trusted installed-release inventory from actual delivered protected inputs.

Relocate by ownership: point 2 proves runtime-independent command transport against the existing layout; point 3 moves backend/framework/config/data and establishes the src base-path composition; point 4 completes frontend/tests/build/public paths. If that intermediate graph cannot remain runnable, extend the coherent relocation in point 3 only as far as needed for an operational bridge and finish/remove the bridge in point 4. No point may be committed complete with broken startup. Complete command caller migration in point 6 before retiring compatibility launchers.

Existing mixed-ownership checkouts require an explicit old-to-new inventory comparison and a clean/collision-free destination. Preserve unrecognized customer additions; stop on ambiguous ownership, symlink escape, modified protected source or conflicting destination instead of overwriting. Existing `storage/app/private` and `storage/app/public` may contain customer data: determine provenance and migrate to the matching owner only after the exact scope is established. Ordinary reopen performs no relocation/reset.

Preproduction metadata currently reports `production.ever_deployed: false`; check local real-deployment evidence before migration/reset work as required by [Database Architecture](database.md#migration-policy). Preserve `shared.migrations`, technical-table identity semantics and already-applied migration identity when only paths move. Relocation alone requires no schema reset or data deletion. Use the established scoped preproduction reset policy only if an actual need is demonstrated; never turn a source reorganization into an implicit volume rebuild.

The durable customer preservation fixture is `tests/Fixtures/ownership/customer-sentinels.json` until test relocation. It supplies distinct source/config/route/frontend/dependency/identity/private/public markers for both storage owners. Materialize it only in isolated acceptance installations, hash before each operation and require identical bytes afterward (except an explicitly requested customer edit). Include additional unknown customer files and collision cases when the update implementation is tested; this audit does not claim those future operations already pass.

### Runtime-independent command composition (RM-005 point 2)

Before source relocation, `management/commands.php` supplies the same explicit command classes to the normal local/testing Artisan registry and to the minimal `management/bootstrap.php` selected internally by the single root `artisan` entrypoint. The bootstrap instantiates Laravel's Artisan console over its base container and event dispatcher only; it does not load the HTTP/application kernel, dotenv, package discovery or operational providers. This is a console bootstrap for the same application/tooling, not a second Laravel skeleton or command registry. Point 3 relocates its Runa-owned source alongside the agreed boundaries.

The separately targeted management image reuses `production-runtime` and production-only locked vendor dependencies. It packages no customer application source and needs no checkout vendor/ or credentials at runtime. It is not included in the production Compose services or production application image. The host launcher validates a bounded versioned plan and delegates only fixed operations through the existing deployment lock. The detailed current surface and migration table live in [Tooling Commands](../tooling/commands.md#runtime-independent-artisan-transport).

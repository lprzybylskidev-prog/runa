# Application Architecture

## Backend shape

Laravel is the backend framework. The application frontend is a Vue SPA consuming `/api/...` endpoints. Blade is not the application rendering layer.

Controllers remain transport-oriented:

- accept validated input,
- call application behavior,
- return resources/responses.

Substantial use cases belong in Actions. Shared/cross-cutting or integration behavior may use Services. Complex read/list/report behavior may use Query classes. Simple Eloquent behavior may stay direct when it is clearer.

Do not create generic `SomethingService`, `Helpers`, `Utils`, or repository layers as dumping grounds.

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

## Read-path performance

When an application-wide review is requested, inventory all delivered runtime
surfaces: ordinary App code and modules, HTTP boot/middleware, public and protected
views, settings/assets, CLI/scheduler/queue work and frontend request owners.
Trace collection callbacks and serializers as well as visible query loops. Record
which areas were source-reviewed, measured or regression-checked; checking selected
Core pages does not establish application-wide coverage or prove absence of all
performance defects. Keep development tooling/release verification separate from
runtime application review.

Review query growth when adding or changing lists, resources, bootstrap metadata,
permission intersections, settings/preference snapshots or module discovery. N+1
includes repeated authorization, registry, declaration and accessor work, even
when no Eloquent relation is involved. Small development datasets do not excuse
hundreds of repeated infrastructure reads. Repair demonstrated amplification in
the authorized area and inspect analogous consumers in the same point; report
protected-platform findings to its owner from a customer checkout. Escalate a
changed architectural/security premise through the applicable roadmap workflow.

1. Measure the complete relevant read and its stages, recording elapsed time,
   query count, repeated query families and lock waits. Match the real principal,
   transaction nesting and payload size; separate cold loading from repeated
   reads. A fast SQL plan alone does not prove a fast endpoint, and an internal
   projection timing does not measure HTTP/session/proof overhead. Keep bindings,
   private values and credentials out of evidence. Use isolated test data or
   nonmutating diagnostics; do not assume a POST read or rollback permits writes.
2. Batch bounded collections and reuse validated declarations within the current
   computation. Deduplicate identical permission checks only within one
   synchronous metadata intersection with the same owner, principal and
   generation. Discard those decisions before invoking owning handlers or starting
   another operation; owning grants, record Policies and expiry checks remain
   authoritative. Do not add a request/worker/global authorization cache.
3. For snapshot reuse, identify which lock guarantees stability and how writes,
   nested commit/rollback, replacement transactions, generation reuse and database
   reconnection invalidate it. Database/model events may be suppressed; an
   event-only invalidator cannot guarantee freshness. Keep time-dependent health
   and eligibility evaluation separate from immutable registry records. Preserve
   registry/authority/account lock order rather than removing locks for speed.
4. Compare the same stages after repair and add focused regression scenarios for
   query growth and freshness across operations. Prefer bounded query counts as
   assertions; absolute millisecond thresholds are environment-dependent. Include
   rollback/reused-worker cases for retained snapshots and denial/current-grant
   cases for authorization-related changes. Follow the normal verification phase
   rules; this review does not authorize early broad suites or new infrastructure.

The framework's trusted PermissionAuthorizer groups a single decision in a short
default-connection transaction and anchors the registry lock before nested Auth
reads. It still rereads current grants and availability for every decision. Internal
management discovery similarly groups source declaration collection without running
resource, widget or write handlers. Both retain their existing signatures and private
runtime ownership; neither is an application extension API or a request-wide cache.
Auth's [bounded policy reads](../modules/auth/sessions.md#bounded-policy-reads)
follow the same lock-lifetime rule. No additional request is introduced.

Include frontend request counts and server-to-application payload size in that
review. Coalesce overlapping read triggers while retaining a trailing invalidation
for later focus/publication changes. Keep independent prerequisites explicit;
never remove current authorization, account fencing or dirty-draft reconciliation
to reduce request counts. Select required metadata/asset columns rather than
transferring every stored image or recovery payload for a small projection.

## Application and protected platform

One Laravel application lives under src. Customer code belongs to src/app; protected Runa PHP/resources/config/routes live in src/runa. Both use the same dependency graph and container. Customer source can use ordinary PHP/Vue without becoming a module.

Use src/app/providers.php for application providers; config overrides go in src/app/config, translations in src/app/lang, routes in src/app/routes, migrations and explicit seeders in src/app/database. Standard Laravel make:* generators target application roots. Vue imports the public @/runa facade and uses @ for application resources. API requests use /api/... through the shared HTTP client. Do not modify platform source to customize behavior.

Application dependency declarations in src/app/composer.json and package.json are composed with the supplied Runa inputs. Every new dependency requires explicit approval and deliberate compatibility/security/license/tree review; preserve committed locks. Conflicts block preparation. No repository layer, UI framework or global state library is introduced by default.

There is one shared src/storage. Durable application and platform files are separated under app/application and app/runa, each with public/private areas; framework state/logs are shared technical runtime state. Use application disks/cache namespaces, keep writable data outside source and preserve both durable trees during operations. Framework modules use ModuleStorage over the correct owner disk with a fixed modules/owner/code prefix; see [module storage boundaries](module-framework.md#driver-independent-storage-and-public-references).

The application manifest src/app/application.json defines independent identity/version and platform compatibility. Runtime platform identity comes from protected src/runa/platform.json; root VERSION is customer scaffold and remains owner release metadata only in a maintenance checkout. Reusable guarded test support lives in src/runa/Testing with the stable Tests\TestCase development contract; application Pest contributions live in tests/backend/app. Native migrations/seeds and customer test commands operate on the application; the external Runa owner alone operates platform migrations, tests and updates. See [database](database.md), [seeding](../data/seeding.md) and [tests](../testing/strategy.md).

## Module framework design

The [module framework contracts](module-framework.md) describe delivered strict manifest validation, owner-scoped discovery, persistent registry snapshots, module autoloading, graph-only lifecycle previews and durable atomic administrative transitions, module-owned health policies, effective availability, independent maintenance reasons and PostgreSQL/storage boundaries, and availability-aware typed PHP/HTTP adapters, managed queue/work leases and cooperative cancellation, classified Artisan/schedule adapters, independent after-commit public events and optional integration toggles, typed encrypted settings, management/frontend descriptors, structured logging and owner-scoped installation/upgrade orchestration. Application-module installation remains an explicit customer deployment invocation. The delivered [module generator and optional development reference](../development/modules.md#working-reference-and-application-integration) provide reviewed source scaffolding and working examples; generation does not register, install or enable a module. Managed synchronous module operations use the declared gateways; class loading and low-level storage/data helpers do not authorize operations. Ordinary application code remains supported.

# Audit

`runa.audit` is a platform-owned Core module, version `0.5.0`, with no
Auth or Permissions dependency. It supplies append-only manual events, opt-in
automatic model capture, local suppression and permission-protected reads. No Audit viewer or administration interface is supplied.

## Installation and ownership

Use the explicit owner module installation/update workflow described in
[module development](../../development/modules.md). Registration and migrations
never happen during ordinary request boot. `runa_audit.events` stores immutable
history; `runa_audit.entities` stores same-schema RESTRICT references to each event.
Foreign entities and actors use UUIDv7 references without foreign SQL, models or
FKs. Both tables use bigint identities; events additionally expose unique UUIDv7
public IDs and UTC occurrence times. There are no mutable Eloquent models,
update/delete APIs, soft deletion, retention setting or cleanup job. Unrestricted
PostgreSQL operators can still modify history; this is an application boundary.

The explicit transactional `null -> 0.1.0` initialization records `audit.store_ready`
after schema creation. No pre-store history is invented. Completed installation
steps and matching versions are no-ops. Normal ingress remains fenced during
preparation. Source-owned framework adapters record subsequent reserved forward
steps and actual settings publication/restoration on their exact connection;
participant failure rolls back the step/publication. Existing recovery fences and
independent SettingsObserver semantics remain intact. These adapters are never
application bypasses. Missing/stale Audit source after installation fails closed.
The explicit `0.1.0 -> 0.2.0` forward edge adds read indexes without transforming
history. Fresh installation traverses both edges; existing installations retain
their events. Both public APIs remain version `1.0.0`.

## Manual public API

Resolve `Runa\Modules\Audit\Contracts\AuditRecords` version `1.0.0` through
`ModuleApis` for `runa.audit`. `record(AuditEvent): AuditReference` records within
the caller's active **default PostgreSQL Connection** transaction; it never commits
the business operation. `attempt(AuditEvent): AuditReference` is a distinct root
entrypoint which requires no ambient transaction and a non-success outcome.
The guarded API accepts immutable owned DTOs, bounded arrays and scalars only.
It never accepts a model, callback, password, OTP, actor input or connection object.
There is no public HTTP write endpoint. Manual recording has no human permission
requirement: it is a trusted PHP operation behind normal availability admission,
not an unauthenticated request-to-event bridge. Never expose arbitrary event input
from a request. Reads use the separate permission-protected AuditReads contract below.

```php
use Illuminate\Support\Facades\DB;
use Runa\Modules\Audit\Contracts\AuditEntity;
use Runa\Modules\Audit\Contracts\AuditEvent;
use Runa\Modules\Audit\Contracts\AuditRecords;
use Runa\Support\Modules\Api\ModuleApis;

$audit = app(ModuleApis::class)->resolve('runa.audit', AuditRecords::class, '1.0.0');
DB::transaction(function () use ($audit, $orderPublicId): void {
    // Perform the owning module's business mutation here, on DB::connection().
    $audit->record(new AuditEvent('app.sales', 'order.approve', entities: [
        new AuditEntity('app.sales', 'order', $orderPublicId),
    ], changes: ['status' => ['old' => 'draft', 'new' => 'approved']]));
});
```

Actions own the outer transaction. Do not catch Audit persistence errors inside
that transaction and then commit the business write. Success entries roll back
with root rollback or their savepoint; events and all related references insert
atomically. A local `AuditWriter::record(Connection, AuditEvent): AuditReference`
contract describes trusted transaction participation. Its owned implementation
rejects a different Connection object, non-PostgreSQL connections and writes
outside transactions. It is used by source-owned bootstrap/settings adapters;
ordinary consumers use the guarded API. No callbacks cross that gateway.

For an independent refusal, let the business transaction roll back fully first,
then synchronously call `attempt` with outcome `refused`, `attempt` or `failure`.
A nested Action propagates its safe refusal to the root; even after a savepoint
rollback an ambient outer transaction is refused. The independent record commits
before returning. It never relies on a discarded afterCommit callback, queue or
second connection. If Audit is unavailable, `AuditUnavailable` means persistence
was not confirmed: fail/report unavailable history accurately, never claim an
audited refusal. Availability fences also still apply to the ordinary attempt API.
Do not persist raw exception messages as failure context.

## Safe event values and trusted context

`AuditEvent` contains module, stable dotted action code, outcome, up to 64 typed
`AuditEntity(module, type, publicId)` references, approved changes and context.
Duplicate references are coalesced. `AuditReference` contains only publicId and UTC
occurredAt. Append history has indexes for time/id, actor/id, module/action/id,
action/id, outcome/id, correlation/id and entity/event access.

Only pass explicitly selected safe business fields; arbitrary aliased credential
values cannot be inferred reliably. Sensitive names (including normalized nested
password/hash, OTP/TOTP, token, recovery, invitation, session, cookie, authorization,
remember/trusted-device and key names) are redacted before insertion. Recognizable
credential strings under aliases are also redacted. Declare application-specific credential aliases through the optional
`sensitiveFields` list on AuditEvent (names match at every nesting level), or pass
already redacted values. Redaction always removes the complete sensitive subtree. Never pass
requests, models, headers, cookies, form payloads or entire exception objects.

Each changes/context tree is limited to 64 KiB JSON, 1,024 visited nodes, nesting
8, key length 128 and UTF-8 scalar text 8 KiB. Unsupported objects/resources,
nonfinite numbers, invalid encoding and excessive payloads are rejected with safe
errors. Do not split a secret across approved fields. There is no blind serializer.

Actor identity is resolved on the server through the current trusted PrincipalScope;
the optional AuditActorResolver bridge uses Auth public identity participation for approved historical display labels when available, without adding an Audit dependency. Human and
machine identities require UUIDv7. Unresolved HTTP actors remain anonymous; system,
CLI, scheduler and queue execution retain safe kind/labels. Requests cannot supply
actor identity or occurrence time; arbitrary event context cannot overwrite them.
Labels must be explicitly approved historical display data, never credentials.

LogContext and OperationScope supply request/correlation/operation/work identities.
QueueLogContext supplies bounded job class/id/queue identities, unwound on success
and failure; a queue does not inherit a human request actor. HTTP captures a route
path template (no parameter values/query), validated IP, bounded method and a known
User-Agent family/version only. CLI records command identity without arguments.
No execution service stores raw requests or secret-bearing job payloads. The
technical logging subsystem remains separate from business Audit.

## Automatic single-model capture

Use `Runa\Modules\Audit\Contracts\Auditable` on a model with a durable UUIDv7
`public_id`, and declare a source-owned `AuditModelPolicy`. Module models retain
ModuleModel ownership/schema/relationship guards. Ordinary App Eloquent models may
also opt in; their existing public-ID generation and write validation still apply.
A consuming module declares its required Audit dependency (`^0.2.0` for these
capabilities), uses only public Contracts and keeps its own table ownership.

```php
use Runa\Modules\Audit\Contracts\Auditable;
use Runa\Modules\Audit\Contracts\AuditModelPolicy;
use Runa\Support\Modules\Data\ModuleModel;

class Order extends ModuleModel
{
    use Auditable;

    protected $table = 'app_sales.orders';
    protected $guarded = ['id', 'public_id'];

    public static function moduleId(): string { return 'app.sales'; }

    public function auditPolicy(): AuditModelPolicy
    {
        return new AuditModelPolicy('app.sales', 'order', ['status', 'total', 'deleted_at']);
    }
}

DB::transaction(function () use ($order): void {
    $order->status = 'approved';
    $order->save();
});
```

Default events are `created`, `updated`, `deleted`, `restored`, `force_deleted`;
`events: [...]` replaces that list, including `[]` to disable automatic entries.
Soft deletion emits deleted; restoring emits restored without a second updated
entry; physical deletion of a SoftDeletes model emits force_deleted. A model
without soft deletion emits deleted for its physical delete. Actions should still
follow the business soft-delete policy. No Audit history is ever deleted.

Default diff captures approved changed fields as old/new pairs, comparing locked
persisted state with the actual saved state. Snapshot mode (`snapshot: true`)
records complete approved before/after maps; creation has a null before and physical
deletion a null after. Soft deletion retains an after state and restoration retains
both. A no-op or change only outside approved fields produces no updated entry.
Policies explicitly include timestamps/deleted_at when those values matter.
Raw structured JSON attributes are decoded before nested redaction; application
credential aliases use `sensitiveFields`. Redacted-only values do not expose changes
to secret contents. Never include a credential merely because its alias looks safe.
No model accessor, relation or arbitrary object is serialized.

An existing default PostgreSQL transaction is mandatory **before** save/delete;
unsafe autocommit or alternate connections fail before writing, including when
capture is disabled/suppressed. Availability is checked first. Each mutation and
its Audit entry share a nested savepoint, so a caught Audit failure cannot leave
that mutation committed; outer rollback still removes successful entries. Discard
or reload model instances after an outer transaction rollback. On an immediate
capture failure the pending attributes/existence state are restored for the caller.
Public identity cannot be replaced. Avoid replacing the trait's save/delete methods
or creating recursive mutation listeners; this is trusted PHP, not a sandbox.

Quiet single-model methods still use this capture boundary. `saveOrIgnore` and
model atomic counters are refused: use an explicit Action/manual event. Query
builder updates/deletes/inserts/upserts, relationship sync/attach/detach and raw SQL
do not pass through model save/delete. Model events are not universal SQL hooks.
Every meaningful bulk, relationship or aggregate mutation needs a manual event.

## Scoped suppression and manual aggregates

`app(AuditSuppression::class)->run(Closure)` is a trusted local helper, not a public
ModuleApis callback. Nested scopes unwind in finally on success or failure; the
container scopes its state to the execution lifecycle. Never retain it in a static
or singleton consumer. Explicit record/attempt calls remain active. Use suppression
only when an explicit aggregate event covers the automatically captured writes.

```php
use Runa\Modules\Audit\Contracts\AuditSuppression;

DB::transaction(function () use ($audit, $order, $itemPublicId): void {
    app(AuditSuppression::class)->run(function () use ($audit, $order, $itemPublicId): void {
        $order->status = 'approved';
        $order->save();
        // An owning Action may also perform its own validated bulk SQL or pivot sync.
        $audit->record(new AuditEvent('app.sales', 'order.approve', entities: [
            new AuditEntity('app.sales', 'order', $order->public_id),
            new AuditEntity('app.sales', 'item', $itemPublicId),
        ], changes: ['status' => ['old' => 'draft', 'new' => 'approved']]));
    });
});
```

For relationship sync, capture approved added/removed public references and emit a
stable action such as `order.items_change` after the owned pivot mutation. For bulk
SQL capture a safe operation summary and bounded entity references; paginate/chunk
large operations deliberately instead of exceeding the event's 64-entity/payload
limits. Keep all business writes and required manual records in the same transaction.
Never infer bulk capture from use of the trait elsewhere on the model.

## Authorized reads

Resolve `Contracts\AuditReads` for `runa.audit`, version `1.0.0`, through ModuleApis.
All page/detail/metadata operations require trusted `runa.audit.read` authority and
normal module availability. Installed Permissions supplies current native grants; the fallback authorizer denies protected reads when the integration is unavailable. There is no unauthenticated read, edit/delete operation,
management endpoint or Audit UI. Consumers must not call the private Query directly.

`page(AuditQuery): AuditPage` supports module, stable action, actor publicId/kind,
exact AuditEntity reference, outcome, correlationId and inclusive UTC ISO-8601
from/until. Limit is 1–100 (default 50). Append identity descending defines stable
pagination even when occurrence times tie; `nextBefore` is an opaque UUIDv7 event
reference, never an internal bigint. Pass it as `before` with the same filters on
the next call; concurrent appends do not move existing page boundaries. This is
append order, not a transaction-wide historical snapshot across concurrent commits.
Unknown cursors are rejected. No total count or unbounded offset/search is supplied.

Page summaries contain immutable event/reference/actor/entity metadata and omit
changes/context/execution trees. At most 100 summaries and 512 KiB encoded page
values are returned; a byte-limited page can contain fewer records and still carry
a next cursor. Related entities are loaded in one bounded batch, without N+1 reads.
`detail(publicId): ?AuditEntry` returns one event including bounded persisted safe
payloads, or null for an unknown valid reference. Both DTOs are readonly; neither
exposes internal IDs or an Eloquent model. `metadata()` describes filter/order/page
bounds for trusted consumers, without providing a generic renderer or management UI.

## Own-account history summaries

Audit 0.3.0 adds an additive no-data-transform `0.2.0 -> 0.3.0` forward edge and
public `OwnHistory` 1.0.0 through ModuleApis. `page(module, entityType, actions,
before)` requires a trusted human principal, binds the entity UUIDv7 to that
principal, validates a bounded 1–64 action allowlist and returns at most 50 rows.
Only public_id, occurred_at, action and outcome are projected; next is an opaque
append cursor. Cursors must belong to that same filtered own-entity history.
No caller-supplied subject, actor identity, entity graph, payload or correlation
access is available through summaries. General AuditReads still requires runa.audit.read.
Module availability remains mandatory; no Auth/Permissions dependency is added.
Auth independently controls its own login/account switches and action allowlists;
see the [private profile contract](../auth/profile.md). Other modules must choose
their own source-controlled action list and confirm an identity-bound entity is
appropriate; this contract is not record ACL or arbitrary subject authorization.

## Core management and optional actor labels

Version 0.5.0 adds source-only 0.4.0 -> 0.5.0 preparation and read-only `event`
management metadata: safe summary fields/filters, no write operations, payloads
only through guarded detail reads. Audit keeps no Auth/Permissions dependency.
The framework optionally enriches trusted human actors through Auth's designated
non-recursive AuthorizationIdentity contract, storing a safe display-name snapshot
of at most 160 UTF-8 bytes. Missing/unavailable Auth preserves UUID actor identity
without a label; it never blocks independent Audit persistence. Historical actor
snapshots are immutable. Authorized current identity lookup uses AuthIdentities
(including deleted subjects); it is separate from history and never rewrites it.

## Bounded own-history details

Audit 0.6.0 adds `OwnHistoryDetails` 1.0.0 without changing `OwnHistory` 1.0.0.
`detail(module, entityType, publicId, fieldsByAction)` binds every lookup to the
trusted current human entity and module. A trusted source supplies an action map
(1–64 actions, at most 32 approved field names per action); never pass a browser
projection. Secret field names are rejected. Foreign, unknown and excluded events
return null without revealing whether another account owns the event.

Only public_id/occurred_at/action/outcome and approved old/new pairs from persisted
changes are projected. Audit 0.7.0 extends values to finite JSON scalars and nested arrays/maps for
source-approved structured preference fields. Existing scalar projections remain
compatible. Persisted SafePayload structural/byte/key limits and 8 KiB text limit
apply; redaction is applied again before projection. Malformed pairs are omitted. Context, actors, entity graphs, internal IDs
and raw Audit payloads are never returned. Empty fields permit a safe event-only
detail for credential operations. This capability requires module availability and
a trusted human, not general Audit read permission. The caller still enforces its
own visibility policy and chooses domain-safe fields; static checking cannot prove
that a deliberately trusted source has not mislabeled sensitive data.

The no-transform source upgrade retains old immutable events. Missing captured
pairs remain missing. See the [Auth history contract](../auth/history.md) for its
fixed action/field map, authorization, capture and presentation rules.

# Audit

`runa.audit` is a platform-owned Core module, initially version `0.1.0`, with no
Auth or Permissions dependency. Its append-only store and manual events are
delivered. Automatic model capture, suppression and authorized reads are pending
RM-2026-011 point 3. No Audit viewer or administration interface is supplied.

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
from a request. Read authorization is delivered with the read API separately.

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
occurredAt. `AuditQuery` starts the bounded read value contract; it exposes no query
execution in this delivery. Append history has indexes for time/id, actor/id,
module/action/id, correlation/id and entity/event access; point 3 adds actual reads.

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
future Auth may bind AuditActorResolver for approved historical labels. Human and
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

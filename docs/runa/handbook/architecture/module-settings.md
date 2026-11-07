# Module settings publication

The module framework owns typed validation, encrypted values, revisions, the
native publication fence and recovery. Modules own definitions, business rules,
access requirements and domain effects. Admin composes these contracts; it never
owns another settings store or publication runner.

## Source extension and entrypoints

The single source-owned `SettingsProvider` may additionally implement these
`Runa\Contracts\Modules` interfaces:

```php
interface SettingsAccessProvider
{
    public function access(): SettingsAccess;
}
SettingsAccess::__construct(ApiOperation $read, ApiOperation $write,
    array $readFields = [], array $writeFields = [], ?MutationProof $proof = null)
interface SettingsAuthorization
{
    public function authorizing(Connection $connection, SettingsContext $context): void;
}
interface SettingsParticipant
{
    public function publishing(Connection $connection, SettingsPublication $publication): void;
}
```

Owner: module framework. Audience: trusted module authors. Implement interfaces
on the provider declared under `declarations.settings`, in owned backend source.
The normal declaration resolver verifies physical ownership, exact class/interface
and unchanged manifest. There is no callback/class input to an HTTP operation.
`access()` is pure metadata, including during runtime descriptor checks. Read/write
operations require explicit permissions. Field maps contain at most 128 declared
setting keys and additional permission strings; omitted keys inherit the operation
permission. The optional password `MutationProof` uses the existing Auth contract.

Resolve container-owned `Runa\Support\Modules\Settings\ModuleSettings`.
`readAuthorized(string $module): array` returns the existing redacted read shape,
omitting fields denied by current read grants. `update(string $module, array $patch,
int $expectedRevision, int $expectedGeneration, ?SettingsActor $actor = null):
TransitionResult` retains its native independent-runner protocol. With access
metadata, the runner checks the write operation and every patched field, consumes
the declared proof once and derives human actor context from the trusted principal.
An actor argument is history metadata only and cannot select authority. A mismatched
actor or a system actor during authenticated publication is refused.

`read`, `value`, `integer`, `boolean` and `text` remain trusted owning/backend or
operator reads, never authorization for direct HTTP projection. HTTP adapters must
establish the current identity through Auth and use the authorized projection.
Providers without access metadata retain trusted native/CLI publication; they do
not gain an HTTP publication contract. These source extensions do not change the
signatures or security requirements of existing Core guarded APIs.

## Admission, locks and atomicity

1. Resolve pure declarations and capture access/provider/handler/schema identity.
   Before the publication transaction, admit the owning write operation, patched
   field grants and applicable Auth proof/Audit operations. Reuse the native
   `HealthAdmissions` scope; after sealing it, no external health checks start
   under publication locks.
2. Retain the native independent runner advisory lock and its exact PostgreSQL
   connection. Open one publication transaction with one attempt, lock the registry
   row exclusively, acquire the common authority advisory lock, then lock the
   authenticated actor through the reserved Auth integration. Recheck principal,
   generation, source, declarations, grants and admission before mutation.
3. Validate expected revision/generation, declared patch and complete candidate.
   Invoke the provider's optional `authorizing` hook before transition/fence writes.
   It may enforce additional owning record/business rules through public APIs;
   no external effects or new unprepared health dependencies belong here.
4. Consume the source-declared proof in this same default transaction, then write
   the transition, encrypted candidate/revision and synchronous publication history.
   Invoke provider `publishing` and mandatory framework Audit participation. Hook,
   proof, publication or Audit failure rolls back this entire transaction. Verify
   source, principal and connection again before the generation bump and commit.
   Final grant/admission checks happen immediately before installing the fence;
   normal operational admission must refuse once that fence exists. Never automatically retry a write.
5. Run the existing `SettingsHandler::apply` outside the transaction. Later failure
   uses native restore/compensate/recovery, not all-stage rollback. Provider
   `publishing` also participates in transactional `restore` and reserved `upgrade`
   phases; authorization/proof consumption occurs only for the initial publish.
   Recovery must remain possible after the initiating proof has been consumed.

`SettingsContext` and `SettingsPublication` retain their complete signatures and
redacted changes: secret replacement/clear is only `replaced: true`. A provider
must not copy private runtime values into history. Existing encrypted operation
snapshots support recovery; terminal operations discard old/candidate credentials.
Use existing Audit settings history and Admin named snapshots for generic history
and saved configuration; only domain-specific consequences/history belong in a
module. No new history table, snapshot store or runner is introduced.

## Security integration and operator boundary

The private framework settings security coordinator is an additional named caller
of reserved `TransactionSecurity::lockSubjects(array): void` and
`proof(MutationProof, bool): void`. The existing fixed Core/Auth bridge implements
identity locks and guarded AuthStepUp calls; the framework never imports Auth
models/actions or reads foreign tables. Current grants use OperationGuard and the
existing Permissions integration. Audit retains its synchronous owning bridge.
Neither reserved contract is an ordinary customer identity/proof bypass.

A principal-free trusted console operation remains operator execution and does not
borrow browser proof. HTTP never becomes operator execution through actor metadata;
access-enabled publication without a trusted human principal is refused outside
the console. Jobs must not inherit a browser principal. Source/grant/connection
changes refuse publication; protected errors never include candidate secrets.

See [declarative settings](module-framework.md#delivered-declarative-settings) for
types, encrypted storage, CLI, application and recovery details, and
[owning transactions](module-transactions.md) for the shared security protocol.

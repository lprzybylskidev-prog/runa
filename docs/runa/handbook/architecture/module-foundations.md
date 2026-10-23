# Module transaction and identity foundations

These additive framework seams prepare Audit, Auth and Permissions. Audit now supplies its append-only store, automatic capture and authorized reads; see the [Audit guide](../modules/audit/README.md). Authentication/session transport and administration screens remain undelivered.
Use the existing [module framework](module-framework.md) and
[development guide](../development/modules.md) for source ownership and adapters.

## Transactional settings and installation

`SettingsParticipant::publishing(Connection, SettingsPublication): void` is for
required synchronous persistence inside the exact settings write transaction.
Publication contains safe context, actual revision, publish/restore phase and
changes in the direction actually written; sensitive fields expose replaced=true.
Exceptions roll back the publication. Failed restoration retains the recovery
fence until successfully retried. SettingsObserver keeps its independent best-effort
semantics and cannot guarantee Audit/history persistence. Do not send external
mail/network effects from transactional participants.

`PreparationParticipant::preparing(PreparationContext): void` is a trusted
framework update adapter for required Audit in a reserved transactional forward
step. The executing ModuleUpdates runner checks the owner/source reservation and
manifest, binds the exact connection/step fingerprint and retains ordinary ingress
fencing. It is not a request option or general availability bypass. Completed
steps are not repeated. Nontransactional steps do not receive this capability.
Do not retain/serialize preparation contexts or invoke private foreign writers
from application code. The framework Audit adapter resolves exact owned participation after installation and refuses missing/stale source; before installation it cannot claim an Audit record exists.

## HTTP identity boundary

HttpEndpoint has an optional HttpAccess declaration: Public (default for existing
consumers), Authenticated or Restricted. The shared guard evaluates current module
availability, trusted HttpAccountPolicy and permissions before constructing the
controller or resolving its FormRequest. Cached descriptor access is rechecked.
Restricted requires the Auth flow allowlist; it does not bypass authorization.
Non-public endpoints require a trusted principal. Future Auth supplies actual
session/eligibility/flow enforcement; the default provider rejects non-public access.

Ordinary API routes use the same account provider through EnforceAccountPolicy;
health/ready stay independent. Declare routes through supported named adapters.
PrincipalScope exposes an immutable UUIDv7/kind TrustedPrincipal within a trusted
server root and unwinds on exceptions. Private identity and permission resolution
must not recursively enter protected APIs. Never construct identity from request
fields or copy session/step-up secrets into metadata, events or queue payloads.
Availability, identity, permission, step-up and domain invariants remain separate.

## Database and authority coordination

Owning Actions and Audit success persistence share the exact default PostgreSQL
Connection and transaction. Independent refusal history belongs after root rollback,
never in the rejected transaction/savepoint or an afterCommit listener. The [Audit module](../modules/audit/README.md) owns manual event/redaction APIs, opt-in transactional model capture and permission-protected bounded reads.

AuthorityGuard is the neutral lifecycle integration for future Permissions. Its
default denies changes requiring administrative authority. AuthorityLock requires
an active PostgreSQL transaction: registry shared row lock first (or an already
held stronger lock), then transaction advisory key `(1381322305, 11)`, then Auth
rows in UUID order, then Permissions rows in stable order. All authority-affecting
writers use this same order and owner contracts; cross-module SQL/FKs/models remain
forbidden. Check and mutation plus required Audit must commit together.

Durable dictionaries use `code`, `name_pl`, `name_en`, `description_pl`,
`description_en`. DictionaryEntry::fromLocalized maps existing pl/en source arrays;
`toRow()` projects those five database columns. It does not create another editable
source, change manifests or introduce translation catalogs for dictionary values.

## Future management declarations

ManagementEntityProvider optionally extends an existing ManagementProvider with
version 1 ManagementEntity metadata. Bounded fields describe localized labels,
types, sensitivity/editability, filters/sorts and UUID public reference lookups.
ManagementOperation describes a permission-bound operation, declared writable input
names, step-up, revision, preview and bulk requirements. ModuleManagement projects
entities alongside existing declarations, checking permissions/duplicates/bounds.
Invalid declarations produce a safe diagnostic. No renderer or execution endpoint
is supplied. Actual Actions/FormRequests enforce validation and security, regardless
of presentation metadata; secrets never belong in the projection.

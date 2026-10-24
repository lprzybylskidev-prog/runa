# Audit, Auth and Permissions foundations

RM-2026-011 point 1 establishes the framework adapters below. Points 2–3 deliver the Audit store, transactional participation, automatic capture and authorized reads. Point 4 adds Auth identity storage, current-time eligibility and audited lifecycle operations. Point 5 adds Fortify cookie/session/CSRF entry transport, registration/invitations and verification. Point 6 adds tracked sessions, deadlines, revocation, real-activity heartbeat and account/source abuse windows. Remaining Auth flows, Permissions and user interfaces remain assigned to later roadmap points.
See the [Auth transport contract](../../handbook/modules/auth/transport.md).
The public usage contract is [module foundations](../../handbook/architecture/module-foundations.md).

## Module versions, graph and initial data

Initial independent manifest versions are `0.1.0` for `runa.audit`, `runa.auth`
and `runa.permissions`, each Core. Audit advances compatibly to `0.3.0` for capture/reads and private own-history summaries; Auth advances to 0.9.0 for credentials, private profile/address/avatar, preferences, policy/operator contracts and entry/restricted/application/account UI; Permissions retains its initial projection. Framework API remains `1.0.0`: new optional
interfaces/default constructor arguments preserve existing consumers. Platform
compatibility starts at `>=0.4.2 <0.6.0`, allowing development against the actual
predecessor while the roadmap carries the new source. Shipping requires platform
0.5.0, not a claim that the predecessor contains these capabilities.

Auth requires Audit `^0.3.0`; Permissions must require the current compatible Auth baseline (now `^0.9.0`) and Audit `^0.3.0`.
There is no reverse Auth dependency. Each manifest uses the normal source-owned
lifecycle, upgrade and public adapter declarations. First installation declares an
explicit transactional `null -> 0.1.0` initialization step. A matching installed
version is a no-op; interrupted steps resume only with unchanged source evidence.
Future versions declare explicit forward edges and central CHANGELOG entries.
Do not create placeholder manifests before the owning module implementation.

Schema ownership is `runa_audit`, `runa_auth`, `runa_permissions`. Migrations claim
schemas through ModuleSchema on the default pgsql Connection; all internal IDs
are bigint identity and external entity IDs unique non-null UUIDv7. Foreign
module IDs are UUIDv7 references, without foreign SQL/models/relations/FKs.
Same-module FKs use RESTRICT/RESTRICT. Dictionary columns are exactly `code`,
`name_pl`, `name_en`, `description_pl`, `description_en`; source localization
projects through DictionaryEntry, while each owner persists its dictionary rows.
No registry/manifest locale format changes and no second editable source.

## Transaction ownership and Audit contracts

Actions own the outer transaction on the same default Connection object as Audit.
Alternate-connection success auditing is refused. Audit writes append-only event
rows with an internal identity, public_id, UTC occurrence, actor reference/safe
historical label, module/action/outcome, request/correlation/operation identities
and bounded redacted JSON changes/context. Related entity references are separate
Audit-owned rows with same-schema event FK and foreign UUIDv7 references. Audit
rows do not inherit ModuleModel soft-delete/update semantics. Index actual read
paths (time/id pagination, actor, entity, module/action and correlation).

The Audit module now provides immutable owned AuditEvent/AuditReference
DTOs and `AuditWriter::record(Connection $connection, AuditEvent $event):
AuditReference`. It verifies an active transaction and exact connection before
writing. The local Auditable helper requires that transaction before the model's
first write. Diff is default; approved full snapshots are opt-in. Manual aggregate
operations suppress automatic capture within a finally-unwound local scope;
explicit records remain active. No callback/model/secret crosses ModuleApis.

Independent refusals use a distinct AuditAttempts entrypoint that refuses ambient
transactions. The root first rolls back the business transaction, then records the
safe refusal synchronously. A nested Action returns/throws a safe refusal to its
root rather than committing an independent entry inside a savepoint. If Audit is
unavailable, report unavailable persistence accurately and fail the operation;
never label an unpersisted refusal as audited. No queue/afterCommit success history.

## Settings publication

`SettingsParticipant::publishing(Connection, SettingsPublication): void` runs after
the stored write, before committing each actual publication/restoration. It sees
safe SettingsContext, actual new revision, phase `publish`/`restore`/`upgrade` and directional
changes. Sensitive fields expose only replaced=true. The observer remains
independent and best effort. Auth's participant composes policy history with Audit
on that exact Connection, without ModuleApis ingress behind the reservation fence.

Candidate participation failure rolls back values/revision/transition/generation
and never applies the handler. Restore participation failure rolls back restoration
and retains recovery_required; retry writes the same restoration once. An already
restored revision does not emit a second publication during handler recovery.
Audit/history exceptions propagate through this seam; no mail/network side effects.
Baseline participation is empty only before Audit is installed. An installed Audit
without a required binding causes refusal, even if discovery/provider wiring broke.

## Authentication and non-recursive authorization

HttpEndpoint adds HttpAccess public/authenticated/restricted with public as the
backward-compatible descriptor default. ModuleHttpGuard verifies cached access
metadata, checks maintenance/availability, resolves HttpAccountPolicy, then checks
OperationAuthorizer before construction, binding or validation. Public means no
required login; installed Auth still evaluates a supplied session's current
restrictions. Restricted operations must belong to Auth's explicit flow allowlist;
choosing the enum is not a bypass. Non-public descriptors cannot accept a null
principal. Module APIs keep their existing availability/permission checks.

HttpAccountPolicy resolves eligibility through private Auth lookup, never a
protected Auth API. PrincipalScope holds an immutable UUIDv7/kind TrustedPrincipal
for the synchronous request/job root and unwinds in finally; permission/identity
resolution cannot recursively enter protected authorization. No proof, cookie,
password or OTP is part of that DTO. Laravel scoped binding also resets worker
lifecycles; trusted future queue/CLI adapters establish their own explicit scope,
never inherit a human request identity from serialized input. Step-up and Audit
suppression remain owned finally-unwound domain scopes in their respective points.

EnforceAccountPolicy applies the same provider to ordinary API routes. Module
routes use their dedicated adapter; health/ready remain independent. Public
application identity/frontend discovery remains reachable before Auth exists.
Point 5 deliberately wires session/CSRF middleware and secure cookies through the
Auth transport provider and lazy account-policy bridge. The generic policy interface
alone does not supply authentication. Anonymous independent module ingress does not
require Auth readiness; supplied authenticated sessions do.
DenyProtectedOperations and DenyAuthorityChanges remain the default bindings.

## Reserved preparation and bootstrap

ModuleUpdates invokes `PreparationParticipant::preparing(PreparationContext): void`
only inside transactional forward steps, after recording the step as running and
checking the live owner/exact source operation reservation and module manifest hash
under the registry lock. Context binds that Connection, owner, operation, module,
step fingerprint and manifest hash. This trusted local capability is not a module
API DTO and has no public HTTP/CLI bypass flag. The participant and handler share
the same transaction/completion record; failure retains update fencing and prevents
installed-version publication. No call occurs for completed/no-op steps.

The participant may invoke only Audit's owned preparation writer directly, with the
bound connection/context; this does not open normal ModuleApis/HTTP/work ingress.
Nontransactional handlers receive no capability and cannot perform required audited
declaration synchronization. Do not route runtime business calls through this seam.
The capability describes trusted executing PHP, not a sandbox against malicious
source. It must not be serialized, retained or reused after the callback returns.

Bootstrap order is Audit schema/store and its initial transactional step, then Auth
schema/dictionaries/policy state, then Permissions schema/dictionaries/declarations.
Audit's initial writer records the store-ready boundary when storage exists;
there is no invented pre-store history. Point 2 replaces baseline provider bindings with the source-checked Audit participation adapter; failure/missing source is closed. Permissions installs the protected
system role, then point 16 binds idempotent first-admin invitation/role assignment
to actual installer/CLI ownership. Incomplete administrative web operations stay
denied; no is_admin fallback, temporary general bypass or extra services.

## Shared last-superadmin protocol

AuthorityGuard::assertMayRemove(Connection, list<UUIDv7>): void is the neutral
Auth lifecycle extension; before Permissions binds it, authority-affecting changes
are denied. Permissions implements the invariant using public Auth eligibility
reads; Auth never acquires a Permissions dependency or queries its schema.

Every mutation changing eligible superadmin membership (including creation,
activation, restore, role deletion, assignment, scheduled/bulk/self deactivation)
uses the same protocol, even when its preview appears harmless:

1. Begin the default pgsql transaction. Acquire registry shared row lock, or retain
   the stronger registry lock already held by settings/update orchestration.
2. Acquire AuthorityLock: transaction advisory key `(1381322305, 11)`.
3. Lock required Auth rows in public_id order, then Permissions rows in stable
   role/public_id order, through owner contracts. Do not call a gateway that takes
   a registry lock after acquiring domain locks.
4. Re-read eligibility/membership under the shared lock, validate the prospective
   state, perform owner mutations, persist Audit on the same transaction and commit.

Cross-module participants preserve this order; acquiring the advisory lock again
in the same transaction is allowed. No code may take these locks in reverse order.
Preview locks are not execution authorization. Acquire the lock before the domain
write, never in an after-save observer. Refusals roll back first and use the separate
Audit attempt path. Time expiry/security locks/external outages can temporarily
remove all administrators; scheduled/expiry previews explain that consequence,
and the guarded infrastructure recovery flow addresses it without bypassing checks.

## Failure table

| Boundary             | Failure                                                          | Required result                                                 |
| -------------------- | ---------------------------------------------------------------- | --------------------------------------------------------------- |
| Public ingress       | Missing/stale source, maintenance, unavailable dependency        | 503 safe module error before account/controller work            |
| Account ingress      | Missing required session                                         | 401, no-store; no controller/FormRequest                        |
| Restricted session   | Action outside current permitted flow                            | Safe restricted error; no ordinary operation                    |
| Authorization        | No Permissions binding or no grant                               | 403; availability/eligibility still independent                 |
| Principal resolution | Recursive lookup or unexpected provider failure                  | Refuse; scope unwinds, no stale principal                       |
| Settings             | Required history/Audit write fails                               | Roll back publication, or keep recovery fence on failed restore |
| Preparation          | Required Audit, owner/source/reservation mismatch                | Roll back step, retain fence, no version publication            |
| Authority            | Missing guard, wrong connection/transaction, last eligible admin | Refuse before mutation; root records independent refusal        |
| Management           | Unsupported/duplicate/badly referenced metadata                  | Empty affected declarations plus safe diagnostic; no execution  |

## Management and instruction products

ManagementEntityProvider is an optional extension of ManagementProvider; version 1
entities contain bounded fields and operations, read permission, localized metadata,
public reference identities, sensitive/read-only flags, filter/sort choices, named
writable inputs, revision/preview/bulk and step-up requirements. No executable
validation strings, database class paths or renderer. Form Requests remain the
validation authority. ModuleManagement adds entities without changing existing
schemaVersion 1 consumers. Concrete modules extend only for actual operations.

Both backend/Laravel/security/PostgreSQL instruction products link the public
contract. Root owner/customer AGENTS require meaningful business/security history
and explicit manual events for bulk SQL, relationships and aggregate operations.
Procedures remain in the public Audit guide. Audit is now 0.3.0 (compatible capture/read and safe own-history behavior); planned Auth/Permissions consume ^0.3.0 rather than the
initial ^0.1.0 Audit store constraint. Their own initial versions remain 0.1.0.
Frontend skills/components are unaffected because this point adds no screens.

## Auth identity delivery

Point 4 delivers the [Auth identity contract](../../handbook/modules/auth/README.md):
owned Authenticatable ModuleModel, exclusive naming, normalized email/login claims,
independent current-time eligibility, optimistic audited lifecycle operations and
monotonic revocation generations. Guarded identity/lifecycle APIs are 1.0.0; initial
The initial identity edge remains 0.1.0; Auth now targets 0.9.0, requiring Audit ^0.3.0. Default administrative authority remains
denied. No Permissions dependency, is_admin flag, foreign data access or user seed.
Identifier-reuse settings govern subsequent deletions without reclaiming ownership;
operational session/trust revocation is delivered in point 6; point 7 delivers
[passwords, second factors, trust issuance and step-up](../../handbook/modules/auth/security.md). Root/customer
AGENTS already require guarded module access, UUIDv7 and transactional history;
both domain reference products link this guide rather than duplicate procedures.

## Private account capability

Auth 0.5.0 supplies versioned private naming/login edits, recipient-bound primary
and secondary address verification, independently forced reverification, captured
session policy, permissioned administrative replacement/bypass, private avatar
ownership with durable retryable cleanup candidates and optional step-up-protected
self lifecycle. All business writes and consumed proofs are synchronous with Audit.
Audit 0.3.0 supplies trusted-self summary queries without importing Auth or granting
general Audit reads. Procedures and transport are owned by the public
[profile guide](../../handbook/modules/auth/profile.md). Preferences/delivery category contracts and policy history/operator contracts are delivered; entry/account screens are delivered and Permissions remains subsequent work.

## Policy publication and operator delivery

Point 10 supplies the [policy/operator procedure](../../handbook/modules/auth/policies-and-operators.md).
SettingsAuthorization is an optional compatible participant extension: authorize and
consume old-policy step-up before reservation creation, then persist actual revision
history and Audit after the stored write in the same transaction. A history failure
also rolls back proof consumption. Compensation creates new history without reversing
prior security effects. Global revisions invalidate old bounded lifetime exceptions.

Native operator commands trust access to execute CLI under the owner-confirmed
production responsibility boundary. Private handoff files keep setup/recovery tokens
out of stdout and recorded host transport; no extra container/service/DB credential
is introduced. OperatorAuthority remains a neutral unbound contract until Permissions
integration, so first-role grant/account recovery fail closed now. Initialization
singleton ownership and exact same-subject pre-setup rotation prevent repeated normal
initialization granting additional superadmins. An outstanding recovery blocks old
password login; redemption issues no session and preserves required factor enrollment.

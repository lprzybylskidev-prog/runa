# Owning module transactions

RM-2026-016 implements this source-owned protocol under the owner's 2026-10-30
architecture decision. The framework owns admission/root coordination; modules
own their locks, validation, writes, result projection and Audit. Admin consumes
the same protocol as standalone owner operations; it is not a separate business
engine. No request supplies a participant class, callback, permission or lock plan.

## Public source extension and consumption

`Runa\Contracts\Modules` owns these trusted PHP contracts and readonly values:

```php
interface TransactionalMutation
{
    public function definition(): MutationDefinition;
    public function preflight(): void;
    public function lock(): void;
    public function validate(): void;
    public function apply(): void;
    public function result(): array;
    public function refused(string $reason): void;
}
MutationDefinition::__construct(string $owner, string $key, array $access,
    array $subjects, array $resources, array $proofs = [])
MutationAccess::__construct(string $module, ApiOperation $operation)
MutationProof::__construct(string $action, string $requirement,
    int $ttlSeconds = 300, bool $everyAction = true, bool $idle = false)
```

`access` is a nonempty list of at most 32 MutationAccess values naming the owner's
actual guarded entry and nested API calls. `subjects` is a unique sorted list of
at most 100 Auth UUIDv7 values including copy sources; the root adds the actor.
Subject locks cover retained identities, including soft-deleted targets for an
explicit restore. Locks grant no eligibility: every owner validates its supported
current/deleted target state, independently of actor authentication and grants.
`resources` is a nonempty unique list of at most 32 owner-namespaced resource keys
(up to 256 bytes), naming actual independently written revisions. `proofs` contains
at most 8 MutationProof values; requirement is password, TTL
1–300. `idle: true` requires `everyAction: false` and asserts Auth protected-section
idle access without renewal in both native proof phases. Fixed-age and single-use
operation proofs remain the defaults. See [Auth section access](../modules/auth/security.md#protected-section-idle-access).
Source-owned metadata never authorizes bypassing the native owner guard.
Owner is a retained module ID or `app`; key follows ApiOperation grammar. Proof
actions are existing owning actions. Duplicate single-use proof demands and
multiple writers of one resource are refused; there is no implicit proof reuse.

Resolve the container-owned public adapter
`Runa\Support\Modules\Data\ModuleTransactions` and call
`run(array $mutations): array`. Input is a nonempty list of at most 16 fresh
TransactionalMutation instances. Results are an owned-ID-keyed map of each
mutation's bounded JSON result after all applies, inside the root transaction.
Module implementations live in owned backend/Actions; ordinary App implementations
live in src/app. The runner verifies physical source ownership before invocation.
These are trusted source participants, never ModuleApis DTOs or HTTP callbacks.
Their result is not permission to expose unfiltered data over HTTP.

## Phases and constraints

1. Capture immutable source definitions, reject duplicate identities/resources/proof
   consumption and sort by owner/key. Admit every declared operation and required
   native proof/Audit call outside a transaction. Admission executes each applicable
   on-use check for that exact module/operation within this logical root. Source-owned
   read-only preflight collects dynamic read dependencies; locked validation repeats
   the actual revision/review checks before any writes.
2. Open one default PostgreSQL transaction, one attempt. Lock registry state
   exclusively, acquire the shared authority lock, then lock the complete actor/
   subject union through the Auth-owned reserved TransactionSecurity integration.
   Recheck generation, maintenance, grants and admission validity under those locks.
3. Invoke owner lock then validate phases in canonical owner/key order. All owners
   compare expected revisions and exact previously reviewed previews against the
   same pre-write state. No participant writes business data in these phases.
4. Verify/consume required Auth proofs in that same transaction after all validation.
   Each single-use demand consumes once; failure rolls back proof and Audit.
5. Recheck current grants at each apply, then invoke owning writes/Audit in canonical
   owner/key order. Do not rerun another owner's old preview after earlier writes.
   Project results only after all applies; commit once. Authority removal that
   invalidates a later operation refuses and rolls back the root.
6. After root rollback and scope disposal, call owning refused hooks with a safe
   reason. Permissions keeps its independent safe access.refused Audit. Refusal
   Audit failure is blocking; it never converts failed work into success.

Same-owner validation and apply share one implementation for standalone and joined
execution. Profile naming validation and assignment preview comparison happen
before either write; there is no special assignment-before-profile rule or generic
before/after dependency graph. Overlapping writers must be one explicit owning
mutation, rather than silently translating expected revisions.

No ambient transaction, automatic write/deadlock retry, pending participant,
independent settings/lifecycle runner, direct external I/O, file operation or mail
transmission participates. Durable same-connection intent requires explicit owner
support. Stronger reviewed authority/bulk workflows stay independent until an
owning transactional implementation is deliberately supplied. This protocol does
not weaken record Policies, last-administrator protection or ARCH-003 proofs.

## Admission and internal boundaries

HealthAdmissions is a private synchronous scope shared by ModuleHealth and the
root runner. Before locking, it records exact-operation evidence bound to source
manifest, health-policy fingerprint and each check's actual expiry. Repeated
nested checks within that logical admission reuse only that scope's evidence.
After sealing, a missing/expired/changed admission is refused; no external health
check starts inside the business transaction. Current cached failure/expiry,
maintenance, source/generation changes and current authorization still refuse.
The scope always unwinds on exception and is never a client token or global cache.

Reserved `Runa\Contracts\Modules\TransactionSecurity` has
`lockSubjects(array $publicIds): void` and
`proof(MutationProof $proof, bool $consume): void`. Only the fixed framework root,
the [native settings coordinator](module-settings.md) and named Core/Auth integration
may consume it. Auth owns actual identity locking
and existing session/security-bound StepUp verification/consumption; no user model
or connection crosses this boundary. Other modules never import Auth persistence.

The first supplied mutations are Auth profile naming/login edit and ordinary
Permissions assignments. Other operations retain their owning protocols until
explicitly migrated. Complete concurrency, rollback, native/runtime and rendered
acceptance belongs to the roadmap's final verification, not per-point execution.

## Current native API versions and intentional break

Auth 6.0.0 and Permissions 2.1.1 retain the intentional replacement
of arbitrary ambient transaction nesting on AuthProfiles.edit and
PermissionsAccess.applyAssignments. Their guarded API versions are AuthProfiles 2.1.0 and PermissionsAccess 2.2.0; other
Core guarded APIs remain at their declared versions. Call standalone writes with
no ambient transaction. Source-owned composition uses the documented phases;
there is no v1 alias or nested whole-write compatibility path. Current native
consumers and rollback fixtures follow that contract. Supplied modules require
platform >=0.7.0 <0.8.0, matching the delivered 0.7.0 platform.
Module versions and guarded API versions are independent.

Authorization/introspection and effective-permission previews read current cached
availability; they do not run external on-use checks for unrelated permission
owners. Actual guarded operations still perform required exact-operation admission.
The runner captures generation after preflight, then refuses any subsequent change
under locks. Admin aggregate revisions describe semantic participant/source/build/policy/record
state and exclude publication of unchanged successful health observations; the
current source-owned integration is documented in [Admin composition](../modules/admin/composition.md). Transport-only reads/other native writers
retain their owning protocols until deliberately migrated; no blanket composition
of all module methods is claimed.

Every owning refusal hook is attempted even when an earlier hook fails, so native
prepared results are invalidated after rollback. Any failed required refusal
returns transaction_refusal_unavailable (503); only the original bounded safe
reason code is retained for internal correlation, without hook payloads or raw
exception text. No successful mutation/proof/Audit state survives that failure.

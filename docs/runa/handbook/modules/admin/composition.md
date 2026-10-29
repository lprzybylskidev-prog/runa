<!-- runa-knowledge {"purpose":"proposal","effective_date":null,"applicable_version":"0.7.0","decisions":[]} -->

# Admin atomic participation

Status: RM-2026-016 point 1 specification; no implementation or concurrency PASS.
This realizes approved ARCH-001 and ARCH-003 without changing existing guarded
Auth/Permissions signatures. A visual slot is not an editable participant.

## Trusted extension signatures

The following interfaces and final readonly values belong to
`Runa\Contracts\Modules`. They are trusted server participation, not guarded
module APIs or request-selected callbacks. Modules implement them in owned
`backend/Management`; ordinary App implementations stay under `src/app/Management`.

```php
interface ManagementParticipant
{
    public function plan(ManagementWrite $input): ManagementPlan;
    public function lock(ManagementPlan $plan): void;
    public function validate(ManagementPlan $plan): void;
    public function apply(ManagementPlan $plan): ManagementOutcome;
    public function refused(ManagementPlan $plan, string $reason): void;
}
ManagementPlan::__construct(string $id, string $owner, string $operation,
    ManagementWrite $input, array $subjects, array $revisions,
    array $before, array $after, array $proofs,
    array $requiresBefore = [], array $invalidates = [])
ManagementProof::__construct(string $action, string $requirement,
    int $ttlSeconds, bool $everyAction)
```

`subjects` is a unique sorted `list<string>` of affected Auth UUIDv7 identities,
including the actor and copy-source subjects when applicable, max 100.
`revisions` is `array<string,string>` of all read dependencies, max 128.
`before`, `after`, `requiresBefore`, `invalidates` are unique `list<string>` of
source-declared operation/dependency keys, max 32 each. `proofs` is
`list<ManagementProof>`, max 8; action is an existing owning action, requirement
uses existing Auth values, TTL 1–300 seconds and never longer than the owner TTL.
Plans retain the bounded submitted command and exact reviewed preview; they carry
no database connections, models, closures or proof secrets. `before/after` express
write-order edges; `requiresBefore/invalidates` describe read dependency identities
whose reviewed state must survive until this operation applies. The root rejects
duplicate writers of one owning revision unless that owner explicitly supplies
one combined participant. No inferred cross-owner version translation.

Plan is rebuilt from trusted declarations and current state on every Save. Client
submission of a plan, participant class, lock order or compatibility declaration
is rejected. `plan` is read-only and cannot consume proof, reserve work or mutate.
`lock` acquires only the participant owner's domain locks, in documented stable
public-identity/key order, after shared/identity locks. `validate` rechecks the
locked revisions, submitted preview, policy, grants, availability and all values,
without consuming proof. `apply` uses owning guarded operations, preserving each
owner's proof consumption and success Audit; it must return committed, never
pending. `refused` runs only after root rollback and records the owner's existing
safe required refusal via AuditRecords.attempt. No business mutation or success
event is permitted there. Each planned participant is called at most once per
phase; refusal only for owners whose refusal protocol requires it.

The root has one narrowly reserved Auth identity-lock bridge:

```php
interface ManagementIdentityLocks
{
    /** @param list<string> $publicIds */
    public function lockSubjects(array $publicIds): void;
}
```

This framework contract is implemented only by Auth; the named
`Runa\Integration\Core\AdminIdentityLocks` bridge may invoke it during an active
root PostgreSQL transaction after the registry/settings/authority locks. It
locks existing owning identity rows in UUID order and reports missing identities
through the owning safe refusal. It returns no user/model/security values and
does not authorize mutation. It is not registered through ModuleApis, not an App
extension/replacement and not a general public identity resolver. Auth internally
reuses its current lock and invariant behavior. Other participants request subject
locks through plans, never through direct calls to this reserved bridge.

## Root protocol and actual lock order

Admin's private aggregate Action owns the root transaction, using the exact
default PostgreSQL connection; no connection switching or ambient caller
transaction is accepted at the Admin Save ingress. No automatic write replay,
deadlock retry, external I/O or independent runner runs inside this root.

1. Admit current Admin entry and locate exact source-bound form/participant set.
   Parse bounded inputs; build read-only plans and the directed write graph.
   Refuse unsupported targets, missing required participants or cyclic dependencies.
2. Begin one default transaction. Acquire `shared.module_registry_state` row
   **exclusive**, matching EntryTransaction/AccessTransaction and ModuleWork
   admission, before other locks. Lock existing framework settings state needed by
   the plans in module-ID order, then acquire existing AuthorityLock. Internal
   framework bridges own those locks; Admin does not import private foreign models.
3. Recheck source generation/hashes, participant membership, effective maintenance,
   availability and current actor eligibility/grants. Invoke the Auth bridge for
   the complete sorted union of actor/subject UUIDs. Invoke participants' owning
   domain `lock` in owner-ID, participant-ID order. Owners keep their private row
   order stable and include additional referenced entities in their plans.
4. Validate all plans against the locked snapshot and the aggregate revision;
   compare exact owner previews/fingerprints without refreshing the user's review.
   Check proof compatibility without consuming proof. Any missing/expired proof
   follows Auth's existing confirmation flow before a new explicit submission.
5. Apply in topological write order, breaking independent ties by owned participant
   ID. Recheck the owning authority at each guarded call. Existing nested owner
   transactions remain savepoints. No participant failure is caught as success.
6. Recheck required participants/source state and commit once. Return authorized
   refreshed records and the new aggregate revision. Publication is a single commit;
   enqueue is allowed only through existing same-connection durable intent admission
   prepared before the root and confirmed compatible by the owner.
7. On any failure, roll back the whole root before independently recording required
   refusal history. Rollback includes owning writes, queued intent rows, consumed
   proof and success Audit. Return safe namespaced errors; preserve readable drafts.
   If refusal history itself fails, surface Audit unavailability (503), preserve
   the original safe reason for internal correlation, and never claim success.

Shared locks serialize competing existing owner writers, but do not grant authority.
Cross-subject source/target previews include every subject and referenced role/
definition version. Membership changes and A→B→A changes remain conflicts because
monotonic owner revisions and source generation participate in the fingerprint.

## Compatibility matrix

| Operation or combination                                                       | Atomic form eligibility                     | Reason / required protocol                                                                                                        |
| ------------------------------------------------------------------------------ | ------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| Auth profile name/login edit                                                   | Yes, single profile participant             | Owning expectedVersion, record grant, default transaction and manual Audit preserved                                              |
| Ordinary Permissions assignment (add/replace/copy/preset)                      | Yes, one assignment participant per subject | Exact AssignmentPreview and fresh both-factor every-action proof                                                                  |
| Profile + assignment for the same user                                         | Yes, assignment **before** profile          | Assignment preview includes identity version; reverse order is rejected, never silently regenerated                               |
| App-owned transactional fields + Core form                                     | Yes, explicit compatible declaration        | Same default connection; own SQL only; subject/lock/revision dependencies declared and owner Audit mandatory                      |
| Multiple assignments requiring the same every-action proof                     | No                                          | Existing proof consumption cannot be reused; use one owner-supported combined operation or separate explicit saves                |
| Grant removal affecting a later participant's authority                        | Fail closed                                 | Recheck current grants at each apply; root rollback restores all prior writes/proof/Audit                                         |
| Two operations writing one owner revision                                      | No by default                               | Only one owner-provided combined participant may define its semantics                                                             |
| Protected authority / force role deletion                                      | Independent reviewed action                 | Preserve AuthorityPreview/RoleDeletionPreview, confirmation and last-eligible-superadministrator rules                            |
| Auth lifecycle, email/security changes and preference reset                    | Independent initially                       | Their owning transaction/proof/intent semantics remain intact; no blanket composition claim                                       |
| Auth policies / ModuleSettings / lifecycle transitions                         | Independent runner                          | Explicitly reject ambient transactions; preserve reservation/publication/recovery                                                 |
| Health diagnosis                                                               | Independent                                 | Deadline-bound external health work cannot be inside root                                                                         |
| Participant or required dependency with applicable on_use/hybrid health checks | Independent initially                       | Existing guarded ingress can run checks outside transactions only; prechecking does not authorize skipping that guard inside Save |
| Avatar/logo upload, remote APIs, direct mail/filesystem effects                | Excluded                                    | Not rolled back with PostgreSQL; no generic upload engine added                                                                   |
| Optional integrations / health policy / work cancellation                      | Independent commands                        | Owning Audit/state boundary; do not join aggregate Save                                                                           |

The first supported pair does not make arbitrary operations composable. In
particular the root never replaces owning proof checks with its entry proof, never
merges identical single-use proof demands and never calls private change Actions.
An owner may implement a new additive compatible combined operation later; that
requires contract/test/version review before declaring it available.

## Required behavioral fixtures

Implementation points 6–8 must realize these scenarios against actual engines:
profile/assignment success; each owner failing after the other wrote; Audit
failure; missing/refused refusal history; proof rollback/reuse refusal; stale
preview and ABA; actor grant removal; last-superadministrator; copy-source change;
missing optional contributor after form open; required participant failure;
multi-subject lock order; app contribution rollback; independent-runner refusal;
and a repeated explicit submission after the user resolves a conflict. Baseline
source fixtures in point 1 protect existing API signatures and ownership only.

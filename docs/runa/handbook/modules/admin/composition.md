<!-- runa-knowledge {"purpose":"current_guidance","effective_date":"2026-10-30","applicable_version":"0.7.0","decisions":[]} -->

# Admin atomic participation

The owner approved rebuilding native transaction/admission APIs on 2026-10-30.
The framework now owns the [native phased protocol](../../architecture/module-transactions.md),
including exact-operation health admission, canonical locks, all-owner pre-write
validation, proof consumption, apply, projection and post-rollback refusal. Auth
profile and Permissions assignment standalone writes use that same protocol.
Admin HTTP aggregate dispatch and declaration registration remain point 6 work;
no aggregate UI or concurrency acceptance is claimed here.

## Declared Admin integration

The remaining Admin-specific source adapter is specified as:

```php
namespace Runa\Contracts\Modules;

interface ManagementParticipant
{
    public function prepare(ManagementWrite $input): TransactionalMutation;
    public function project(array $result): ManagementOutcome;
}
```

The owning participant maps bounded Admin input and exact reviewed previews into
its native mutation and projects its authorized native result. It does not return
connections, models, closures, proof secrets or client-selected classes. Module
implementations stay in owned backend/Management; App implementations stay in
src/app/Management. Admin locates only source-bound declared participant classes,
checks current entry/field/section/command authority, required participant set and
aggregate revision, then calls ModuleTransactions once. It cannot call private
foreign Actions or reconstruct foreign locks/writes. Native owner adapters reuse
the owning mutation factory, not an entire old guarded write inside another root.

A visual SlotHost contribution remains presentation-only. Editable contributions
must declare a compatible native mutation and remain present/current at Save.
Aggregate revisions cover exact participants, source/build identities, subject IDs,
owner revisions and related authorized reads. Lost required participation or stale
review is a conflict; no partial commit or automatic refresh/replay occurs.

## Supported initial composition

Profile name/login edits and ordinary reviewed assignments validate before either
writes. Canonical owner ordering applies Auth first; that does not invalidate the
already validated assignment review. There is no assignment-before-profile shim,
version translation, requiresBefore/invalidates graph or review regeneration.
Duplicate writers of one resource require a single owning combined mutation.
Duplicate single-use proof demands are refused; entry proof never substitutes for
an owning stronger action proof. Current authority is rechecked at every apply.

The root uses exactly the default PostgreSQL connection. Native owner Audit and
consumed proof roll back with all writes; required refusal Audit runs only after
rollback. Pending/recovery_required outcomes cannot masquerade as committed atomic
results. External health work occurs before root locking under native admission,
with expiry/source/current availability checks under the transaction; on-use health
is not by itself a reason to exclude a transactional participant.

Module settings/lifecycle publication runners, diagnosis, file/asset work, network
I/O and mail transmission stay independent. Protected authority, force deletion,
Auth policy and bulk-security workflows keep their actual reviewed requirements
and stronger proof; no blanket composition is claimed. Additional owners join only
through the same bounded source-owned contract and real rollback/Audit tests.

## Acceptance ownership

The focused native checks recorded in the active roadmap establish their exact
source scenarios only. Full rollback/ABA, actor grant loss, source/participant
loss, proof expiry/duplication, Audit/refusal failure, multi-subject lock ordering
and app contribution concurrency are final roadmap verification. Developer examples
and generator consumers must follow this current protocol, rather than preserving
the superseded unchanged-API composition proposal.

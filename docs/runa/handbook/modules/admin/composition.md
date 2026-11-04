<!-- runa-knowledge {"purpose":"current_guidance","effective_date":"2026-10-30","applicable_version":"0.7.0","decisions":[]} -->

# Admin atomic participation

Admin supplies aggregate preview and Save through AdminAccess 1.0.0 and the named
HTTP routes in [declarations](declarations.md). The framework owns the
[native phased protocol](../../architecture/module-transactions.md): exact-operation
health admission, canonical locks, all-owner validation, proof consumption, apply,
projection and post-rollback refusal. Source implementation and focused tests do
not establish final rendered, concurrency or release acceptance.

## Source contract and ownership

The public source extension is delivered in Runa\Contracts\Modules:

```php
interface ManagementParticipant
{
    public function prepare(ManagementWrite $input): TransactionalMutation;
    public function project(array $result): ManagementOutcome;
}
```

prepare returns a fresh owning native mutation. project receives that mutation's
bounded native result after all applies, inside the root transaction. It must
return committed, no operation ID and a non-null record with the submitted public
UUID. Both methods are trusted source code, never HTTP callbacks or guarded API
values. Preparation and preview are read-only; project must not perform writes or
external I/O. The native definition must have the declaration's owner and include
the exact declared owning operation/permission. Non-none review requirements must
match the declared action and a native proof demand, including everyAction.
This initial adapter supports zero demands for none or exactly one declared native
action demand. Additional/undeclared demands are incompatible; the UI cannot
represent them as a weaker action.

Module implementations live in owned backend/Management; App implementations live
in src/app/Management. Bind both ManagementBinding.participant and
ManagementCommandDefinition.participant to the same owned class. Discovery verifies
its interface, source identity and instantiability before construction. App classes
also require explicit container binding. The adapter may implement the reader,
writer and participant interfaces together. A source-native mutation cannot be
borrowed from another owner or wrap an old whole write. Module adapters reuse
private factories only within their own module; App adapters implement their own
phases or use documented public source contracts, never foreign private Actions.

Visual SlotHost composition remains presentation-only. Editable contributions use
ManagementContribution and a compatible atomic command with intent=save. Their
projected identity is `<owner>.<contribution>.<section>`. Inputs, review entries,
returned records and validation paths retain that identity; host fields are scoped
to the owning page and section. Independent commands, tables/bulk, settings and
lifecycle publication runners, diagnosis, files/assets, network I/O and mail
transmission do not participate. Protected authority, force deletion, Auth policy
and bulk-security workflows retain their independent reviewed protocols.

## Admission, revisions and phases

The client submits exactly every currently writable atomic Save section, including
editable contributions. Read-only sections are excluded. Missing, additional or
lost participants conflict; duplicates/unknown input keys are invalid. At most 15
business participants fit beside the Admin guard in the native 16-mutation bound.
Total aggregate input is at most 256 KiB, output at most 1 MiB.

The coordinator first verifies entrance proof and ends entry admission. Owner
preview/prepare/read health work runs outside that transaction. Each input carries
its own public UUID and owner revision; a mismatch conflicts. Preview returns a
fingerprint covering the page's current authorized source/build/policy declarations,
canonical participant identities, actor UUID, subject UUIDs, owner revisions,
requested values, native definitions and owning review fingerprints. Declaration
revision excludes unchanged successful health observation publication; native
admission still fences exact-operation evidence, expiry and transaction generation.

Preview uses the current page descriptor revision. Save requiring review uses the
returned aggregate revision and each returned owning review. Save without required
review accepts the current descriptor revision or exact aggregate fingerprint.
Changing values after review needs a new explicit preview. A fingerprint supplies
no authority. Owners must include related state and monotonic versions in their
review/revision contracts so changing values away and back cannot conceal ABA.

One ModuleTransactions.run executes the Admin guard and all native owners. Guard
preflight admits its read dependencies; locked validation rebuilds the complete
current selection/reviews before any business apply. Canonical owner/key order
and the complete sorted Auth subject union apply regardless of input or visual
order. All owners validate before either writes. The native proof plan includes
reusable entrance password proof and the owning action demands; entry proof never
substitutes for stronger action proof. Duplicate writers/single-use proof demands
are refused. Current native authority is checked at each apply.

After all applies, participant projections and current page/field authorization run
inside that same root, before commit. Invalid/pending results or lost result authority
roll back the complete save, including Audit and proof consumption. In particular,
removing one's own read grants cannot commit and return a stale authorized record.
Save returns current descriptor revision and every authorized committed record.
Native refusal hooks execute only after rollback; refusal Audit failure remains a
blocking transaction_refusal_unavailable. No automatic replay, review regeneration,
revision translation or assignment-before-profile ordering shim is used.

## Supplied composition and example

Auth owns ProfilePage/ProfileEditor and Permissions owns
AssignmentContribution/AssignmentEditor. These are private declaration adapters,
not direct customer factory APIs. The initial `runa.auth/users` record page defaults
to the current actor; explicit section reads accept public_id for an authorized
target. Directory/lifecycle coverage and full Permissions administration retain
their owning roadmap points. Profile Save replaces name, first_name, surname and
login together. Ordinary access Save replaces roles and permissions and preserves
protected system assignments under the owning protocol. Roles/permissions use
bounded field-specific guarded lookups. No new storage or API version is introduced.

For a selected user, read the page descriptor and both section records:

```text
GET /api/admin/runa.auth/users
GET /api/admin/runa.auth/users/sections/profile?public_id=<uuid>
GET /api/admin/runa.auth/users/sections/runa.permissions.user_access.assignments?public_id=<uuid>
```

Build participants using each record's public_id/revision. The profile input has
command=save and all four naming/login values. The Permissions input has command=save,
roles/permissions UUID lists and its own revision. POST that aggregate with the
page revision to `/api/admin/runa.auth/users/preview`. Show the safe differences,
confirm `runa.permissions.access.manage` with native both-factor proof, copy each
returned review to its participant input and the aggregate revision to the root,
then POST explicitly to `/api/admin/runa.auth/users/save`. A conflict requires
reconciliation and another explicit review; never replay the old write.

App providers can contribute a section to `runa.auth.users.profile` using the same
contract. Register the App provider through AdminRegistry during bootstrap and bind
its editor/participant class explicitly. Its native definition owner is app;
resources use the app namespace, Auth subject UUIDs are declared up front, and
access includes the declared operation on runa.admin with a current existing grant.
Its own PostgreSQL writes and explicit Audit event belong in apply. The test fixture
under tests/backend/runa/Fixtures/Management/Atomic*.php.stub supplies a working
App-owned note contribution without importing private platform persistence.

## Acceptance ownership

Focused HTTP checks cover native profile/access commit, both owning Audit failure
directions and transactional App projection. The authored cases also cover stale
reviews/ABA, own-grant removal, lost/duplicate participation, expired action proof,
proof reuse, safe namespaced errors and App rollback. AggregateCompetition opens
an independent PostgreSQL backend with a bounded lock timeout; the phase probe
checks registry and complete sorted subject locks before any apply. Full scenario,
concurrency and rendered acceptance run in final roadmap verification, not during
ordinary implementation. Excluded cases are not reported as PASS.

## Standalone participant forms

Admin 0.2.0 also admits an atomic command through its section `/preview` and
`/commands` routes when the source-owned binding implements `ManagementWriter`.
It runs that native standalone writer after entry admission ends, retaining the
same projected inputs, grants, revisions, review, destructive confirmation and
proof demands. `ManagementParticipant` alone does not supply standalone execution.
This does not change aggregate `/save`: that route still requires every current
writable participant. Auth user details uses standalone profile/access forms so
saving one form never submits another form's dirty draft.

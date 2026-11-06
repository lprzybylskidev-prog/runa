# Ordinary access management

Permissions supplies guarded `PermissionsAccess` API 2.2.0. Resolve it through
ModuleApis; never resolve its implementation or write assignment tables directly.
The current module requires Auth ^2.0.0 || ^3.0.0 and Audit ^1.0.0; framework API remains 1.0.0.
The explicit 0.1.0 → 0.2.0 edge validates new storage; the transactional updater
runs the forward migration for fresh installs and upgrades, without assigning users.

## Authorization and proof

`runa.permissions.access.manage` permits ordinary assignment previews/mutations,
custom-role and preset management. An authorized manager may grant ordinary rights
they do not hold. Read/list methods require `runa.permissions.introspection.read`.
Account-based reads/previews additionally consume AuthIdentities' guarded eligibility
lookup and require `runa.auth.eligibility.read`. These grants never replace current
actor eligibility, source/module availability, record policies or session checks.

Every mutation, including deletion and a no-op application, requires a fresh,
session/action-bound password AuthStepUp proof for `runa.permissions.access.manage`.
It is consumed with `everyAction: true` inside the default PostgreSQL business
transaction. Confirm through Auth's existing step-up endpoint; credentials and
codes never enter Permissions APIs, Audit, metadata or presets. A trusted server
adapter establishes PrincipalScope; payload fields cannot select the actor.

The transaction acquires the registry row exclusively, shared authority advisory
lock, Auth proof/session/user locks through its public contract, then owned data.
It rechecks authorization after locking. This is the same registry-first protocol
used by Auth; it avoids upgrading a registry shared lock after taking authority.
No permission/assignment cache survives the operation.

## Assignments, copy and templates

```php
use Runa\Modules\Permissions\Contracts\AssignmentSet;
use Runa\Modules\Permissions\Contracts\PermissionsAccess;
use Runa\Support\Modules\Api\ModuleApis;

$access = app(ModuleApis::class)->resolve('runa.permissions', PermissionsAccess::class, '2.2.0');
$review = $access->previewAssignments($userPublicId, new AssignmentSet(
    roles: [$rolePublicId], permissions: [$permissionPublicId],
));
// Present the review and obtain the required Auth step-up proof before this call.
$result = $access->applyAssignments($review);
```

`assignments(user)` projects current ordinary role/direct UUIDv7 references,
assignment revision, protected system membership and safe current eligibility/
identity/security versions. Definition projections contain public references only.
Each AssignmentSet accepts at most 100 role references and 100 direct references,
sorts and deduplicates them; add unions must fit those same bounds. References must
exist, be nondeleted and ordinary. Disabled-module references remain assignable
and retained; availability blocks their runtime operations.

`previewCopy(sourceUser, targetUser, mode)` snapshots assigned ordinary roles and
direct permissions, preserving their distinction. It excludes the source's system
role. `previewPreset(preset, targetUser, mode)` snapshots the template. All three
previews default to `add`: union missing assignments and preserve existing ones.
Explicit `replace` applies exactly the requested ordinary roles/direct grants.
Both modes preserve the recipient's existing protected system role. Preset/copy
never turns effective role-derived grants into direct grants.

AssignmentPreview returns current/resulting assignments, added/removed roles and
direct references, and practical effective-permission additions/removals. Effective
differences account for current target eligibility, module availability and existing
system membership; they do not predict business record policies or future session
security checks. The effect projection is bounded to 1000 candidate permissions;
larger selections refuse explicitly instead of silently truncating the review.

Execution rebuilds the preview under the lock and compares its fingerprint, ignoring
client-supplied result/diff fields. The binding includes request/mode/target,
source/preset versions and assignments, target monotonic assignment revision,
current role/permission versions/memberships, eligibility and availability effects.
A stale preview returns `access_preview_stale` (409). Review anew; never replay the
write automatically. Assignment revisions prevent an empty → changed → empty cycle
from making an earlier preview valid. Repeating a newly reviewed idempotent add
consumes its proof but creates no duplicate assignment event or revision.

## Custom roles and presets

`definitions(kind, after, filters = [])` lists at most 100 safe dictionary summaries with an
exclusive UUIDv7 cursor and optional bounded [search/selected-ID filters](README.md#searchable-assignment-catalogue); `definition(kind, publicId)` returns the owned dictionary,
version and assignments. Read/list kind is `role`, `preset` or `permission`; permissions are read-only
source dictionaries. Use their safe UUIDv7 summaries to select direct grants.
Mutation kind is exactly `role` or `preset`.

Construct DefinitionInput with kind, DictionaryEntry and AssignmentSet. New custom
codes use `custom.<single_snake_case_key>` (key maximum 64 characters). Names are
required PL/EN text up to 160 characters; descriptions are required nonempty PL/EN
text up to 1000 characters. Codes remain immutable and unique even after deletion.
Updates require the existing publicId and positive expected version together.
Roles accept permissions only; presets accept ordinary roles and direct permissions.

```php
use Runa\Contracts\Modules\DictionaryEntry;
use Runa\Modules\Permissions\Contracts\DefinitionInput;

$draft = new DefinitionInput('preset', new DictionaryEntry(
    'custom.support', 'Wsparcie', 'Support', 'Dostęp wsparcia.', 'Support access.',
), new AssignmentSet(), sourceUser: $sourceUserPublicId);
$review = $access->previewDefinition($draft);
// After review and a fresh password proof:
$preset = $access->saveDefinition($review);
```

`sourceUser` creates a new preset from the user's assigned ordinary roles/directs.
It cannot be combined with explicit members or an existing preset. Execution
rechecks the source snapshot. DefinitionPreview binds current dictionary/version,
requested content, references and membership revisions; role edits also bind the
set of assigned users. It reports affected-user count and role/template permission
coverage changes. This coverage is structural, with current module availability;
individual users' other roles/directs may preserve a permission being removed.
These are review metadata, not user administration pages.

Presets are assignment snapshots with no permanent recipient link. Applying one
writes ordinary assignments only. Editing/deleting it never changes recipients;
editing a referenced role still changes that role's ordinary runtime grants.
`deleteDefinition(kind, publicId, version)` soft-deletes custom definitions after
proof/version checks. A role referenced by a user or preset refuses with
`access_role_referenced` (409). A preset removes only its own membership pivots.
Unreferenced custom roles remove their own permission pivots. Foreign keys remain
RESTRICT/RESTRICT. Source-owned and system definitions cannot be edited or deleted.
Explicit force-delete and protected superadmin writers use the separate
[protected authority contract](authority.md), dedicated capabilities and proofs.

## Audit, recovery and management metadata

Each meaningful operation records one aggregate `assignments.changed`, `role.saved`,
`role.deleted`, `preset.saved` or `preset.deleted` event with complete old/new owned
assignments and public entity references. Source-user/template provenance and
revision/affected-user counts are bounded context. SQL pivot writes emit no model
events, so there is no automatic capture to suppress; never manufacture duplicate
per-pivot history. Auth's separate proof-consumption history remains meaningful.
Required aggregate Audit failure rolls back dictionaries, pivots, revisions and
proof consumption, even when an outer transaction catches the error.

Root refusals record `access.refused` independently after rollback, including
protected references, stale/conflicting definitions and missing proofs. Nested
consumers must roll back their root before recording independent refusal history
through AuditRecords::attempt; never escape an open transaction on another connection.

The existing management provider declares bounded custom-role/preset/assignment
entities, dictionary/reference fields and revision/preview/password requirements.
Metadata uses its provider-local permission keys and never grants permission or
executes a write. Unknown management schema versions refuse through the shared
adapter. The source-owned [Admin pages](administration.md) consume these same public backend contracts; the metadata itself executes no operation.

Tests prepare real-session/step-up, stale preview and ABA, role/preset independence,
copy semantics, protected-role exclusion, ordinary grants beyond the actor's own,
reference deletion refusal and aggregate-Audit/proof rollback. Broad integration,
concurrency, source/runtime/version and final security checks run in the roadmap's
final verification point.

Administrative profile/assignment standalone writes now use the framework's
[native phased transaction protocol](../../architecture/module-transactions.md).
Call AuthProfiles 2.1.0 / PermissionsAccess 2.2.0 without an ambient transaction;
aggregate callers use source-owned participants. This intentionally replaces
nested whole-write composition, preserving current grants, reviewed differences,
stronger action proof and atomic owning Audit. Source/global settings and external
operations retain their separate owning workflows.

## Admin native assignment composition

The private source-declared AssignmentContribution/AssignmentEditor joins the
Auth profile record with ordinary reviewed roles/permissions replacement and
bounded guarded field lookups. Native password proof, monotonic revisions,
protected-role exclusion and same-transaction Audit remain authoritative.
See [Admin composition](../admin/composition.md) for supported inputs, exact review
and the root protocol. Protected authority and bulk workflows stay independent.

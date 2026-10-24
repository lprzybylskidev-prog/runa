# Protected authority and role deletion

Permissions 0.3.0 adds guarded `PermissionsAuthority` API 1.0.0 and the trusted
Auth lifecycle participant. It requires Auth ^0.10.1 and Audit ^0.4.0. The explicit
0.2.0 → 0.3.0 source upgrade validates the protected role and synchronizes new
declarations during reserved preparation. It grants no user access automatically.

## Dedicated authority operations

Resolve `PermissionsAuthority` through ModuleApis, using `runa.permissions` and
API version `1.0.0`. `runa.permissions.authority.manage` protects both
`previewAuthority(userPublicId, grant)` and `applyAuthority(reviewed)`.
Ordinary access management, copy, presets and role editing cannot write protected
membership. A nonmember cannot grant this role to themselves, even with the
dedicated capability. Authorized actors may grant it to another retained,
nondeleted account; inactive accounts retain membership without gaining access.

The preview returns current membership, target eligibility and assignment revision.
Its fingerprint binds the target, requested decision, current identity/security
state, protected role and exact membership/revision. Execution reconstructs the
preview and rejects stale state with `access_preview_stale` (409). Caller-provided
projection fields are never execution authority. Newly reviewed no-ops emit no
duplicate membership history or revision.

Each mutation consumes a fresh session/action-bound `both` AuthStepUp proof for
`runa.permissions.authority.manage` with `everyAction: true`. Password/OTP inputs
stay in Auth. A superadministrator still passes account/session eligibility,
required factors, step-up, record/domain policies and module availability. The
system role supplies ordinary action grants, never a general security bypass.

Changes record `authority.granted` or `authority.removed`, old/new membership and
revision, target UUIDv7 and system-role UUIDv7. Membership, monotonic revision,
proof consumption and required Audit commit in one default PostgreSQL transaction.
Audit failure rolls them all back, including when an outer caller catches it.
Root refusals record independent `access.refused` after rollback; nested consumers
must roll their own root back before recording a refused attempt.

## Last eligible administrator

The framework lazily resolves Permissions' trusted AuthorityGuard only when its
source, installed version and dependencies are available. Missing/stale integration
fails closed. It accepts only the exact default connection in an active PostgreSQL
transaction. Auth has no reverse dependency or Permissions persistence access.

Explicit deactivation, soft deletion and protected-role removal exclude the target
identities from the candidate set. At least one other current superadministrator
must satisfy Auth eligibility (`mayOperate`), including approval, activation,
expiry, security/attempt locks, verification and password restrictions. A bulk
caller passes the complete removal set; checking each target independently before
any mutation is unsafe. The same guard covers self-service and scheduled removal.

All relevant writers serialize under registry row lock → authority transaction
advisory lock `(1381322305, 11)` → owned Auth rows → owned Permissions rows. Actions
needing Auth proof/outbox take the registry row exclusively before authority; they
never upgrade it after domain locks. The invariant reads current membership and
Auth's designated trusted public AuthorizationIdentity contract while the shared
advisory lock is held. Application consumers must still use guarded public APIs;
the invariant bridge does not expose a general identity/grant lookup bypass.
Two concurrent removals cannot both approve the same remaining administrator.

Empty exclusion lists serialize non-removing creation/activation/restore/profile
work and check integration readiness without rejecting recovery or harmless edits.
Administrative factor reset and forced reverification conservatively exclude their
target because they can remove usable authority. Ordinary profile/avatar/invitation
edits do not imply role removal.

Time passing can expire every account or reach scheduled deactivation dates before
the scheduler materializes them. Current ingress still refuses those accounts.
Security locks, abuse locks and required security policy can also temporarily leave
no usable administrator; they remain authoritative. Scheduling/expiry consumers
must explain this future lockout risk rather than promise continuous availability.
The scheduled job refuses last-admin materialization, preserves the schedule,
records independent `authority.refused` and continues unrelated due accounts.
Repeated due refusals are meaningful attempts, not successful state changes.
Use the [trusted operator recovery procedure](../auth/policies-and-operators.md)
for lockout; actual OperatorAuthority/installer wiring belongs to the separate
cross-module bootstrap integration. No temporary role flag or browser bypass exists.

## Explicit force deletion of custom roles

Ordinary `PermissionsAccess::deleteDefinition` still refuses user/preset references.
`runa.permissions.roles.force_delete` separately authorizes
`previewRoleDeletion(publicId, version)` and `forceDeleteRole(reviewed, confirmed)`.
Every execution requires explicit `confirmed: true` plus a fresh `both` proof for
that exact capability. Module-owned, predefined and system roles always refuse.

The bounded preview returns all impacted user UUIDv7s, assigned before/after sets,
revisions and practical effective-permission losses, plus referenced preset UUIDv7s
and versions. Counts are the lengths of those complete lists, each limited to 100;
larger deletions refuse with `access_deletion_limit`, never a truncated confirmation.
Effective losses account for other roles/direct grants, current eligibility and
module availability; record policies and future state remain independent.
Deleted accounts' retained pivots are included. Deleted/missing identities cannot
produce effective rights. Assignment/reference/effect bounds follow ordinary access.

Execution recomputes the preview, including recipient revisions/assignments,
role/permission versions, exact pivot identities, practical effects and preset
versions. It explicitly removes only same-schema user-role, preset-role and owned
role-permission pivots, advances affected assignment/preset revisions, then
soft-deletes the role. RESTRICT/RESTRICT FKs remain intact. No foreign Auth SQL,
account deletion, system-role removal or implicit permission deletion occurs.

Required aggregate `assignments.changed`, `preset.saved` and `role.force_deleted`
events include old/new sets and provenance. Proofs, all histories, revisions and
unlinking roll back together. No automatic per-pivot events are invented. Existing
metadata declares these separate capabilities, preview/proof/revision requirements
and explicit confirmation without introducing an Admin renderer or UI.

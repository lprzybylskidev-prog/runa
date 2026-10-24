# Permissions module changelog

## 0.2.0

Add compatible PermissionsAccess 1.0.0 for versioned custom-role/preset CRUD,
from-user templates, reviewed add/replace assignments and direct access copy.
Preserve ordinary role/direct meaning and protected system membership; bind
execution to current revisions/memberships/eligibility and source snapshots.
Require current native grants and fresh both-factor Auth step-up; commit complete
aggregate Audit, pivots, dictionaries and monotonic assignment revisions atomically.
Add relational preset storage, forward 0.1.0 -> 0.2.0 edge and safe management entity
metadata. Refuse referenced-role deletion and all generic system/source edits.
No new dependency, Admin UI, dedicated superadmin writer or first-admin grant.

## 0.1.0

Initial Core dictionaries, source-owned permission/predefined-role declarations,
protected unassigned system role and explicit transactional install/update sync.
Add source/state-bound impact previews, per-definition Audit and rollback/no-op
semantics, direct/role union, current eligibility and module availability checks,
uncached cross-worker reads, Laravel namespaced Gate/OperationAuthorizer binding
and guarded bounded Permissions 1.0.0 introspection. No assignment writer, presets,
last-superadmin participant, automatic user grants or administration UI.

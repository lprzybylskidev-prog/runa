# Permissions module changelog

## 0.5.7

Align the required Auth range to ^0.19.0 for factor dialogs and the additive
trusted-device controller contract. Existing grants/authority remain unchanged.
Source-only forward edge; no data change.

## 0.5.6

Align Auth dependency with compatible 0.18.0 secondary-address orchestration.
Source-only forward edge; no Permissions behavior or stored changes.

## 0.5.5

Align Auth dependency with compatible 0.17.0 recovery-code modal presentation.
Source-only forward edge; no Permissions behavior or stored changes.

## 0.5.4

Align Auth dependency with compatible 0.16.0 TOTP QR presentation additions.
Source-only forward edge; no Permissions API, behavior or stored changes.

## 0.5.3

Align Auth dependency with compatible 0.15.0 shell profile presentation contracts.
Source-only forward edge; no Permissions behavior, API or stored changes.

## 0.5.2

Align Auth dependency with compatible 0.14.0 account-card/cropping contracts.
No Permissions API, data, settings or behavior change; source-only forward edge.

## 0.5.1

Align the existing Auth dependency with compatible Auth 0.13.0 public presentation
contracts. No Permissions API, behavior, settings or storage change; source-only
0.5.0 -> 0.5.1 forward edge.

## 0.5.0

Add explicit local DevelopmentAccess/contributor role/direct/preset scenarios with module-ID dependencies, transactional provenance/revisions/Audit and preserved developer changes. Keep protected authority in the existing first-administrator path and normal management proof requirements unchanged. Add forward 0.4.0 -> 0.5.0 preparation; require Auth ^0.12.0.

## 0.4.0

Add guarded PermissionsSelections 1.0.0 with explicit one/selected/all user intent and its native capability. Supply trusted actual-role first-administrator/recovery participation, safe management input controls and source-only 0.3.0 -> 0.4.0 preparation. Require Auth ^0.11.0 and Audit ^0.5.0; retain independent account/security checks, atomic Audit and existing ordinary-assignment protections.

## 0.3.0

Add compatible PermissionsAuthority 1.0.0 for dedicated reviewed superadministrator
grant/removal and confirmed custom-role force deletion. Require dedicated current
grants and fresh both-factor proofs, prevent self-escalation, preserve at least one
eligible superadministrator under the shared lifecycle transaction lock, and record
atomic membership/reference/revision/proof/Audit histories. Bind force deletion to
complete bounded user/preset effects and reject stale previews. Add source-only
0.2.0 -> 0.3.0 edge and require corrected Auth ^0.10.1; framework API remains 1.0.0.
First-administrator operator/installer integration remains separate. No new storage,
dependency package, Admin UI, foreign persistence access or general policy bypass.

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

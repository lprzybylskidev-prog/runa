# Permissions module changelog

## 2.1.0 — Unreleased

- Use fresh password confirmation for all ordinary access, assignment, definition and protected-authority mutations. Consume AuthStepUp 2.0.0 with the existing grants, reviews, revisions, last-administrator checks and atomic Audit. The unreleased 2.1.0 candidate retains its public Permissions signatures.

- Extend PermissionsAccess to 2.2.0 with allowlisted definition ordering and literal code/name filters before cursor paging. Show both localized names and full catalogue table controls; enable local search/filters in the bounded effective-permissions table.

- Complete access-editor interaction states: pointer/hover/pressed/focus on custom buttons, inherited checkbox cursors and truthful disabled row/page affordances.

- Polish assignment-editor visuals with the shared small search field, bounded desktop width, natural category widths and quiet aligned filters, counts and page controls.

- Replace the expanded assignment lists with a dedicated compact catalogue editor, integrated search/assigned scope, retained category queries/pages, dense rows and bounded pagination. Preserve readonly browsing, unavailable-assignment removal and native proof/review.

- Omit generic access instructions and show account-selection, assignment-submit and retry progress through shared controls.

- Add public searchable PermissionsAssignments presentation and bounded server definition search/selected-ID hydration through PermissionsAccess 2.1.0; preserve native grants, proof, revision and transactional Audit.

- Reuse the Permissions field catalogue for Auth account assignments, preserving native UUID lookups/grants while providing Polish Role/Uprawnienia labels.

- Accept Auth 3.0.0 after account-expiry retirement; Permissions behavior and guarded API contracts remain unchanged.

- Reuse shared Admin long-value and JSON review previews without changing definition locks or confirmation/proof protocols.

- Return exact non-deleted definition totals before cursor/limit and forward them to Admin without changing read authority.

- Batch role-deletion recipient assignment/revision reads and identical reference
  snapshots within one preview. Bulk-write assignment/definition/declaration
  pivots while preserving current eligibility, per-recipient limits, fingerprints,
  protected authority and mandatory aggregate Audit.

- Keep the shared reset control beside filter fields; existing definition/effective
  permission tables contain no timestamp columns requiring date formatting.

- Declare the shared `runa.permissions.tables.export` permission for authorized
  exports/printing across all Runa tables; retain owning reads and record Policies.
  Forward `table_exports` preparation synchronizes declarations transactionally.
- Open definition details in guarded dialogs; share compact filters, refresh,
  reset, column preferences and bounded current-page exports.

## 2.0.0 — 2026-10-30

- Use shared FormNotifications for effective-permission read errors.

- Localize administrative labels/actions/effective-permission columns through the shared Admin PL/EN presentation contract without changing grants, definition values or native action semantics.

- Deliver source-owned Admin roles/presets/permission dictionaries, reviewed add/replace/copy/preset access and effective reads, dedicated protected authority and confirmed force-delete sections with native proof/Audit protocols.

- Supply private AssignmentContribution/AssignmentEditor for ordinary reviewed user access in the Auth profile aggregate, guarded field lookups, exact owner revision and native both-factor action proof. Protected authority and bulk workflows remain independent.

- Use native owner lock/validate/apply phases for standalone and shared transactions; all owner reviews validate before any business write.
- Intentionally replace ambient nesting of PermissionsAccess.applyAssignments with source-owned ModuleTransactions composition; the guarded write API version is 2.0.0 and standalone calls require no ambient transaction. Other guarded API versions remain unchanged.
- Require platform >=0.7.0 <0.8.0 for the new framework protocol. Preserve strong action proof, current native authority and same-transaction Audit; add a retained-source native_transactions forward edge with no schema change.

## 1.0.5 — 2026-10-30

- Widen platform compatibility to <0.8.0, preserving the >=0.4.2 lower bound and existing API contracts.
- Append the source-only admin_platform forward edge; no schema or data transformation.

## 1.0.4 — 2026-10-28

- Complete typed declaration and assignment contracts; serialize every role permission group as a JSON list.
- Add the source-only verification_contracts forward edge without schema or data changes.

## 1.0.3 — 2026-10-28

- Support platform 0.6.x explicitly while retaining public APIs and historical upgrade identities.
- Record the source-only platform_delivery edge; production excludes developer implementations and raw module frontend sources. No schema or data transformation.

## 1.0.2 — 2026-10-28

- Reconcile internal platform collaborators with the module framework and Core integration homes; public contracts and runtime behavior remain compatible.
- Add a source-only forward edge with no schema or data transformation.

## 1.0.1 — 2026-10-27

- Relocate module test metadata and cases to the central owner test tree; runtime APIs and data remain unchanged.

## 1.0.0

Prepare the first stable Permissions release for verified closure of RM-2026-011.
Preserve the delivered public contracts and add the source-only 0.5.11 -> 1.0.0
update edge; no schema or data transformation. Align required Core module dependencies to ^1.0.0.

## 0.5.11

Align Auth ^0.23.0 and Audit ^0.7.0 dependency ranges for complete account history capture.
Source-only forward edge; no schema migration.

## 0.5.10

Accept compatible Auth ^0.22.0 and Audit ^0.6.0 dependencies for own-history detail
contracts. Existing grants, authorization and assignment behavior are unchanged.
Source-only forward edge; no storage migration.

## 0.5.9

Align the required Auth constraint with compatible account refresh/controller
additions in Auth 0.21.0. Source-only forward edge; no permission policy or schema
change.

## 0.5.8

Align the required Auth range to ^0.20.0 for the additive full-preferences reset
capability. Existing permission/authority contracts remain unchanged.
Source-only forward edge; no schema change.

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

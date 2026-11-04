# Permissions administration

RM-2026-016 point 8 supplies Permissions-owned Admin declarations and adapters at
module 2.1.0 / PermissionsAccess 2.1.0, PermissionsAuthority and Permissions 1.0.0.
All pages require current Admin entry/password proof and module availability;
Permissions owns native validation, record rules, revisions, proof and Audit.
There is no reverse backend dependency on Admin or foreign persistence access.

## Pages and operations

| Page / section                                            | Owning API                                                          | Grants and execution                                                                                                                                          |
| --------------------------------------------------------- | ------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `roles`, `presets`, `permissions` / `catalogue`, `editor` | PermissionsAccess.definitions / definition                          | `introspection.read`; UUID cursor, max 100, exact authorized non-deleted total before cursor/limit. Dictionary owner/system status and members stay explicit. |
| `roles`, `presets` / `editor.store`                       | previewDefinition / saveDefinition                                  | `access.manage`; exact native definition review, immutable code, expected version; new presets optionally snapshot a source account.                          |
| `roles`, `presets` / `editor.delete`                      | definition / deleteDefinition                                       | `access.manage`; reviewed current definition/version and explicit confirmation. Referenced roles refuse.                                                      |
| `roles` / `force.force_delete`                            | PermissionsAuthority.previewRoleDeletion / forceDeleteRole          | Separate `roles.force_delete`, not ordinary write authority; complete impacted accounts/presets/effective-loss review and confirmation.                       |
| `access` / `access.assign`, `copy`, `preset`              | previewAssignments / previewCopy / previewPreset / applyAssignments | `access.manage`; add (default) or replace; direct and role grants stay distinct and protected membership is preserved.                                        |
| `access` / `effective`                                    | Permissions.explain                                                 | `introspection.read`; owning code cursor, bounded rows, eligibility/availability-aware effects; no invented UUID for permission codes.                        |
| `access` / `authority.change`                             | PermissionsAuthority.previewAuthority / applyAuthority              | Dedicated `authority.manage`; membership/revision/eligibility review, explicit confirmation, self-escalation and last-eligible-administrator protection.      |

Grant names in the table are qualified with `runa.permissions.`. Account assignment
reads/previews also require native `runa.auth.eligibility.read`. Each write consumes
one fresh session/action-bound both-factor proof for its exact native capability.
Independent workflows never join atomic profile/access Save. The existing Auth
profile contribution remains an ordinary replacement-only aggregate participant.

Dictionary fields are `code`, `name_pl`, `name_en`, `description_pl`, `description_en`,
`permissions` and, for presets, `roles`, optional `source_user`. Native constructors
retain required localized text, custom-code grammar, 100-member bounds, source-user
snapshot restrictions and expected public identity/version. Metadata exposes safe
owner/system state and `review_summary` for complete owning review effects. Source
and system rows are read-only in the default UI and refuse through owning APIs.
Force-delete has its own section so a dedicated manager needs no ordinary write
grant. No ordinary operation can include the system role in roles or presets.

Account commands send only their declared fields: assign uses roles/permissions/mode,
copy uses source_user/mode and preset uses preset/mode. The subject comes from the
selected record public UUID; the actor always comes from the session. Selection
starts with the current account and changing accounts respects dirty-draft guards.
Effective rows retain the selected subject across cursor reads. Read-only/source
values, inaccessible fields, count inventions and arbitrary operation names are
never write authority. A stale native fingerprint/version returns conflict; review
again explicitly, with no automatic write replay. Owning transactions retain current
grants, proof consumption, required aggregate Audit and post-rollback refusals.

## Presentation and replacement

Public `@/runa/permissions` exports `PermissionsAdminDefinitions` and
`PermissionsAdminAccess` with `AdminPageProps`, and `PermissionsAdminDefinition` /
`PermissionsAdminEffective` with `AdminSectionPresentationProps`. The underlying
source lives in `src/runa/modules/permissions/frontend/components`. Customers can
compose these defaults, replace a page/section through the public Admin presentation
provider, or use `useAdminForm`, `useAdminResource` and controlled public fields from
`@/runa/admin`. Keep shared session/proof/reconciliation/dirty guards in Runa.

Definitions use the reusable `AdminRecordBrowser` table/detail composition; its
public signature is documented in [Admin frontend](../admin/frontend.md#record-browser).
No foreign private adapters or copied security logic are supported extension seams.
Focused source/interaction diagnostics are recorded in the active roadmap; complete
backend/browser/concurrency/release acceptance remains its final point.

`PermissionsAccess.definitions(string $kind, ?string $after = null, array $filters = [])` returns
`{items: list<{public_id: string, code: string, name_pl: string, name_en: string,
version: int}>, next: ?string, total: int}`. Permissions owns this guarded runtime
contract in `backend/Contracts/PermissionsAccess.php`; its private owning Action
counts in SQL and Admin forwards the value without foreign reads or estimation.
Optional bounded search/selected-ID filters follow the [catalogue contract](README.md#searchable-assignment-catalogue).
The total includes zero, describes the filtered set independently of the page cursor and confers no write
authority or snapshot guarantee. Customers resolve the public guarded API.

Default definition fields and reviewed differences reuse Admin's public
ValuePreview presentation: long values have explicit Show all/Collapse, and JSON
reviews are indented without changing payload tokens. Expansion changes only the
local display and never confirms, submits or refreshes a reviewed operation.

Account selection uses local action progress through the existing Admin form
controller. The default access page omits generic explanations of ordinary versus
protected authority; native reviews, stronger proofs, protected membership and
separate authority actions remain enforced by their existing owners. Assignment
Save inherits Form progress and record-read retries reveal the shared skeleton.

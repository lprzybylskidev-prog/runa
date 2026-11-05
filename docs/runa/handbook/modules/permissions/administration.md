# Permissions administration

RM-2026-016 point 8 supplies Permissions-owned Admin declarations and adapters at
module 2.1.0 / PermissionsAccess 2.2.0, PermissionsAuthority and Permissions 1.0.0.
All pages require current Admin entry/password proof and module availability;
Permissions owns native validation, record rules, revisions, proof and Audit.
There is no reverse backend dependency on Admin or foreign persistence access.

## Pages and operations

Permissions supplies exactly three navigation pages, in this order:

| Page          | Behavior                                                                            |
| ------------- | ----------------------------------------------------------------------------------- |
| `permissions` | Informational catalogue and readonly details; no creation, update or deletion.      |
| `roles`       | Catalogue, creation and one combined details/editor dialog, plus reviewed deletion. |
| `presets`     | Catalogue, creation and one combined details/editor dialog, plus reviewed deletion. |

Definitions use the same public table, Card/Dialog, controlled fields, compact
membership catalogue and local form feedback as Auth. The default page is the
permission catalogue. The details dialog edits localized names/descriptions and
members in place; there are no separate view/edit screens. Code is immutable after
creation. Roles contain permissions; presets contain ordinary roles and direct
permissions. Definitions supplied by module source and system definitions remain
readonly and refuse mutation through native APIs. Readonly fields and source facts
convey this without a repeated explanatory banner. New presets are composed from
explicit members in this UI; the guarded backend's source-account snapshot API
remains supported independently.

Reads use `PermissionsAccess.definitions/definition` with
`runa.permissions.introspection.read`. Saves use `previewDefinition/saveDefinition`
and deletes use `definition/deleteDefinition`, with `runa.permissions.access.manage`,
expected native versions, exact reviewed fingerprints, fresh password confirmation
and atomic Audit. Referenced role deletion refuses ordinarily. The same role details
dialog offers the distinct reviewed `PermissionsAuthority.previewRoleDeletion/
forceDeleteRole` workflow only with `runa.permissions.roles.force_delete`; its
complete impact review, explicit confirmation and transactional safeguards remain.
A dedicated force-delete manager does not need ordinary definition-write authority.

## Selected-user access in Auth

There is no Permissions account selector, assignment page, effective-permissions
page or standalone authority editor. Account access belongs to Auth user details.
The existing `runa.permissions.user_access.assignments` contribution retains its
ordinary replacement-only atomic `save(roles, permissions)` command and adds two
independent reviewed commands:

- `copy(source_user, mode)` calls `previewCopy` and native assignment mutation.
- `preset(preset, mode)` calls `previewPreset` and native assignment mutation.

Both support `add` (default) or `replace`, retain the target's protected membership
and never copy protected authority. Source account/preset revisions and assignments
are fingerprinted in the native preview and revalidated before apply. Changed
sources, recipients or definitions refuse as stale, without automatic replay.
Copy/preset tools open separate shared dialogs with a source picker and add/replace
mode. Cancel closes the dialog and clears its source/mode draft; Apply closes it
before opening the existing Auth review. They require saving or reverting an existing membership draft first;
selection alone never mutates access. The current Auth details review and shared
full-page password confirmation own final submission and cancellation.

The source picker searches the guarded AuthAdministration 2.1.0 `users` catalogue,
requiring `runa.auth.users.read`; no foreign Auth queries/models are used. Preset
choices use PermissionsAccess 2.2.0 `definitions('preset')`. Both are bounded,
localized, searchable and paginated through the existing Admin options lane.
Absent source-read authority omits the copy tool. Writes retain
`runa.permissions.access.manage` and native Auth eligibility reads.

The independent `runa.permissions.user_authority.authority` contribution places
protected superadministrator assignment in the same Auth user details. It requires
`runa.permissions.authority.manage`, native eligibility/revision review, explicit
confirmation and password proof. Self-escalation and last-eligible-administrator
protection remain authoritative. It never joins ordinary profile/access atomic Save.

## Presentation and replacement

Permissions owns public runtime presentation `PermissionsAdminDefinitions`
(`AdminPageProps`) and `PermissionsAdminDefinition`
(`AdminSectionPresentationProps`) through `@/runa/permissions`, implemented in
`src/runa/modules/permissions/frontend/components`. Both have no slots/events.
`PermissionsAssignments` retains `AdminSectionPresentationProps`, no slots and
`submit(section: string, command: string, fields?: string[])`; copy/preset commands
use the same event as ordinary Save. Its internal import/member components are
private implementation, not customer entrypoints. The removed
`PermissionsAdminAccess` and `PermissionsAdminEffective` exports have no adapters.

Customers compose these defaults or replace pages/sections through the public Admin
presentation provider and `useAdminForm/useAdminResource/AdminField` contracts.
Keep session/proof/reconciliation/dirty guards in Runa; neither presentation nor
selection confers write authority. A successful create selects the saved record so
subsequent editing uses its real identity/version; deletion returns to the catalogue.

`PermissionsAccess.definitions(string $kind, ?string $after = null, array $filters = [],
?string $sort = null, string $direction = 'asc')` returns
`{items: list<{public_id: string, code: string, name_pl: string, name_en: string,
version: int}>, next: ?string, total: int}`. Permissions owns this guarded runtime
contract in `backend/Contracts/PermissionsAccess.php`; its private owning Action
counts in SQL and Admin forwards the value without foreign reads or estimation.
Optional bounded search/selected-ID filters follow the [catalogue contract](README.md#searchable-assignment-catalogue).
Optional literal `code`, `name_pl`, `name_en` substring filters accept at most
160 characters each. Ordering accepts those same three keys and `asc|desc`,
with UUID ascending ties; absent sort retains UUID ascending order. SQL filters,
counts and orders before limiting. Continue a UUID cursor with the same query;
restart after changing filters/order or when its sorted subject disappears.
Admin catalogues expose both localized names, column filters and server sorting.
The total includes zero, describes the filtered set independently of the page cursor and confers no write
authority or snapshot guarantee. Customers resolve the public guarded API.

Definition confirmation reuses public ConfirmDialog and ValuePreview for native
review effects; expansion is local presentation only. Complete module/backend/
browser/release verification remains the active roadmap's final point.

# RM-2026-017 point 10o — Native structured management reads

Date: 2026-11-08. Status: owner accepted the correction on 2026-11-08; implementation complete.
This review separates the original design-stop evidence from current source and
focused diagnostics. Runtime/assets preparation and acceptance remain pending.

## Original verified source and incorrect premise — bb20930b

P08 correctly identifies redundant read serialization, but its claim that the
existing Management/Admin boundary already supports these projections is incomplete.
The generic payload containers support nesting; field declarations and their
actual transport consumers do not:

- `src/runa/Contracts/Modules/ManagementRecord.php` calls
  `ModuleFramework/Management/DeclarationRules::json`. Nested arrays are accepted,
  with depth 8 and an aggregate 65536-byte limit. Objects, including stdClass, are
  explicitly rejected. Returning the existing unloaded snapshot's stdClass
  directly would therefore fail as well.
- `src/runa/Contracts/Modules/ManagementField.php` permits only text, boolean,
  integer, datetime, uuid and dictionary. UUID multiple selection is not a generic
  collection type.
- `src/runa/modules/admin/backend/Queries/Projection.php::record` validates each
  projected value against that field type. The affected read fields currently
  declare text, which accepts strings only. Changing just their readers to arrays
  produces `admin_projection_invalid`.
- `src/runa/modules/admin/frontend/decode.ts::decodeRead` independently enforces
  the same scalar/UUID rules. `AdminJson` and `AdminRecord.values` allowing nested
  values does not make a declared text field accept them.
- `src/runa/modules/admin/frontend/fieldValidation.ts` and
  `AdminFieldControl.vue` have no structured field validation/control contract.
  Preference delivery is writable text today, not merely readonly metadata.

At the design stop, the snapshot handbook documented JSON text, consistently with that code.
This is a planning-premise correction, not proof that existing code is stale.
Implementation stopped under AGENTS's significant-unknown rule before changing the
public field contract. The owner subsequently explicitly accepted the correction
after its explanation; the accepted outcome is implemented below.

## Traced source and consumers

| Producer                                                 | Encoded values                                                                  | Consumers                                                                                                                                             |
| -------------------------------------------------------- | ------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Support/Modules/Settings/SettingsSnapshotSection::read` | snapshots catalog; selected snapshot values                                     | Admin `useAdminSettingsSnapshots`; Auth policy snapshots; reference guarded HTTP/Admin adapters and `frontend/contracts.ts` / `useReferenceWorkspace` |
| Auth `Management/CreationSection::read`                  | password_policy                                                                 | `AuthAdminCreation.vue`                                                                                                                               |
| Auth `Management/SubjectSection::read`                   | addresses; sessions; devices; active_exceptions; exception_versions; exceptions | `AuthAdminEmails.vue`; `AuthAdminUserSection.vue`; `components/admin_section.vue`; `policyExceptions.ts`                                              |
| Auth `Management/PreferenceSection::read`                | delivery; categories; recipients                                                | `AuthAdminUserSection.vue`; owning preference command                                                                                                 |

SubjectSection additionally decodes its own exception_versions string to derive
exception_version. Remove that round trip with the owning projection correction.
Session/device exports use independent guarded resource reads and must migrate too.

Delivery is currently written by JSON.stringify in the specialized user section
and accepted by PreferenceSection::execute only as text, then decoded. Its control
is an interactive category/recipient editor, not an intentional raw JSON textarea.
A coherent native-array contract must update this write and its validation as well
as the read; preserving an encoded write would retain unnecessary plumbing.

Tests to update include Admin backend EntryTest (email, session, trust, exceptions,
preferences, snapshots and read-plan cases), application CoreExamplesTest, frontend
Admin forms/Auth administration and application reference workspace scenarios.
Retain secret omission, independent grants, expiry/revision conflicts, snapshot
load/cancel draft retention, no publication on load, proof refusal and atomic Audit.

## Owning correction — owner accepted 2026-11-08

Extend the existing public `ManagementField` type vocabulary with `json` for
bounded structured arrays/maps, without adding another envelope, resolver or engine.
Keep its constructor signature and the existing record/read/write interfaces.
Owner: module framework for declarations and Admin for transport/presentation;
audience: existing module/App Management providers and supported Admin replacements;
entrypoints: the existing Management declarations, guarded readers/commands and
public Admin controllers. Implementation remains in their current source homes,
with the same development/production delivery modes.

Accepted semantics:

1. `json` accepts native list/map values, plus nullable field state; encoded strings
   are invalid. Preserve the existing recursive payload/size limits and secret
   projection rules. Do not relax text validation to accept arrays globally.
2. Structured fields are not filterable, sortable, UUID references, choices or
   maxLength text controls. Owning command validation still defines business input.
3. Supplied readonly presentation uses the existing safe value presentation or
   specialized collection views. Editable delivery uses its existing specialized
   category/recipient controls and native array values. Define explicit refusal
   for unsupported generic structured editing rather than silently treating JSON
   as text or introducing a new generic JSON editor.
4. Update declaration validation, backend projection, frontend descriptor/value
   decoding and field validation together. Migrate Auth and snapshot declarations,
   all actual readers/controllers, reference consumers, fixtures and documentation.
   Accept only the new representation, without compatibility decoders.
5. Return null for unloaded snapshot values; a selected snapshot returns its native
   field map. This avoids introducing PHP object support solely for an empty map.
   Catalogs/collections remain lists. Document PHP empty-map/list behavior explicitly
   where needed rather than claiming an unsupported object representation.
6. Keep semantic review/original write revisions, field grants, guarded native API
   entrypoints, authority/health locks, one-use password proof, secret omission and
   atomic Audit unchanged. Storage encryption, hashes, URL filters and Audit display
   chunks remain intentional encodings outside P08.

The owner explicitly accepted this contract extension after discussion. Its complete
signatures/constraints are recorded in the owning public declarations guide. The
implementation/version/instruction assessment follows below. No new dependency is
introduced.

## Original investigation evidence

The local knowledge index reported current (234 documents, 13341 chunks), and a
bounded current-intent query located the original snapshot/contract guidance.
Findings above were checked in actual source. No tests were run: the concrete
conflict is established by the declared allowlists and both projection validators.
No services, storage, ledgers, images, assets or temporary Docker resources changed.

The owner resolved the field semantics on 2026-11-08. Combined readiness remains
10r, explicit owner
manual acceptance 11 and broad final verification/release 12. Platform VERSION
0.7.0 and approved feature/0.8.0 projection are unchanged.

## Implemented source and contracts

ManagementField accepts json with explicit refusal of sensitive/filterable/sortable,
reference, maxLength, choices and multiple controls. ManagementRecord retains its
existing recursive depth/size validation. Admin backend projection and frontend
read/descriptor decoding recognize native lists/maps and null while refusing encoded
strings/scalars; text fields remain strict. Readonly default fields use escaped
ValuePreview; generic writable JSON has no text input and reports unavailable.
Frontend validation accepts structured values for specialized presentations.

Snapshot records return native catalogs/maps and null for unselected values. Auth
creation/subject/preference readers return their existing safe collections directly;
the exception version no longer needs a local decode. Declaration types and every
identified supplied consumer, including independent export reads and the reference
workspace, use these native values. Preference delivery writes accept only native
lists through the existing owning DeliveryInput validation. No dual reader remains.

Guards, native settings orchestration, lock phases, original review/revision tokens,
proof consumption, encrypted persistence, source fingerprints and atomic Audit were
not changed. URL filter serialization, hashes, deep-copy operations and escaped Audit
text/display formatting remain intentional encodings outside this correction.

The reference's actual interactive settings/snapshot workspace uses the guarded
common owner, reads native catalog/value data and loads label into its draft before
an explicit review/publication. Updated working fixtures and CoreExamplesTest retain
capture/load-without-publication, secret omission, independent proof and deletion
scenarios. No copied snapshot/security implementation is introduced.

## Honest versions and operational follow-up

- Framework API 2.1.0: additive declaration vocabulary. Auth/Admin/reference require
  ^2.1.0; unrelated module constraints remain compatible without source changes.
- Admin 2.0.0: useAdminSettingsSnapshots already shipped in main/Admin 1.0.0 with
  encoded values; requiring native values breaks that real consumer contract.
  The standing pre-1.0 authority permits this MAJOR. Existing initialization and
  actual current/published update paths now target 2.0.0. AdminAccess stays 1.0.0.
- Auth remains the unreleased 6.0.1 candidate, including this additional break in
  the already planned Auth 6 MAJOR relative to published source. Guarded Auth API
  method signatures/versions do not change; management projections are corrected.
- Reference remains 0.4.1: ReferenceTools 1.0.0 is a new unpublished API in this
  roadmap (absent from main), corrected before first release. Its optional Admin
  constraint advances to ^2.0.0. Current examples/consumers are updated together.
- Platform VERSION remains 0.7.0; approved feature/0.8.0 projection is unchanged.

No installed module/database/ledger state or assets were updated. Point 10r must
align real development/test storage/history, module candidates and assets from the
combined coherent source, and confirm working pages/APIs before owner point 11.

## Focused diagnostics and limits

These bounded checks answer concrete contract/draft/security questions; they are
not complete suites, final quality evidence or owner manual acceptance:

| Supported command                                                                                                                                                                                                                                                                           | Result and scope                                                                                                                                                                             |
| ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `python3 tools/maintainer/test-frontend admin-fields.test.ts --filter=structured`                                                                                                                                                                                                           | PASS: 2 cases; native/scalar distinction, text strictness, escaped readonly display and unavailable generic editor. Exit 0, cleanup 0.                                                       |
| `python3 tools/maintainer/test-backend --parallel --processes=1 --filter='structured management fields\|edits target preferences atomically\|named settings snapshots load'`                                                                                                                | PASS: 3 cases, 62 assertions; declaration/payload bounds, refusal of encoded writes, native preference revision/Audit, snapshot native maps and proof-protected deletion. Exit 0, cleanup 0. |
| `python3 tools/maintainer/test-frontend admin-forms.test.ts auth-administration.test.ts auth-creation.test.ts --filter='settings snapshot preview\|hides admin recipient selectors\|sorts filters exports and refreshes\|switches naming credentials\|active.*exception\|exception.*expir'` | PASS: 3 selected cases in 3 files; dirty snapshot cancel, configuration omission and creation credentials. Exit 0, cleanup 0. Unmatched/skipped cases are not PASS.                          |
| `python3 tools/maintainer/test-frontend auth-administration.test.ts --filter='security table .* supports sorting\|named account rules open focused editors'`                                                                                                                                | PASS: 3 cases; native session/trust table export and authoritative exception state/version. Exit 0, cleanup 0.                                                                               |
| `./bin/artisan runa:test --scope=frontend workspace.test.ts --filter='loads a snapshot only into the draft'`                                                                                                                                                                                | PASS: 1 application case; native snapshot into a draft and review invalidation. Exit 0, cleanup 0.                                                                                           |

The first nonparallel backend invocation of the same filter passed the structural
case (12 assertions) but the two Admin cases refused fixture setup: the base test
Core schemas already exist, while EntryTest requires absent disposable Core schemas.
Native result was exit 2, cleanup 0; no Admin behavior PASS was inferred. Repeating
through supported parallel mode with one disposable worker supplied the correct
fixture ownership and produced the PASS above. No base reset or guard change occurred.
One frontend invocation attempted while the backend held the suite lock refused
with exit 1 (`Another test suite is running in this checkout`); it ran only after
the backend completed. The lock was respected, not bypassed.

The existing Vite dynamic-import analysis warning in generated Vitest runtime
appears in frontend diagnostics; native executions passed. No production-build
warning or new runtime exception is inferred. Static analysis, complete suites,
application native backend CoreExamplesTest and browser/runtime acceptance remain
in final verification/preparation; they were not claimed by these diagnostics.

The backend runner identified and removed disposable `runa_test_test_1` after its
parallel run. The scoped cleanup list before that run was empty. No task-created
Docker containers/networks/images/volumes require removal. Development/base-test
data, services, volumes, Qdrant/model caches and reports remain preserved.

## Current documentation and instruction products

Synchronized the owning public declaration/type contract, snapshot composition,
Auth administration/current module guides, module/central changelogs, reference
README/capability matrix and pending owner scenarios. The original P08 audit and
previous dated readiness records retain their historical evidence.

Both instruction products were assessed. Root AGENTS and backend/frontend skills
already route public declarations and replacement components to their owning guides.
Owner frontend component references and customer frontend/application references
carry no duplicate JSON wire signatures; they keep using the corrected public
handbook. No new reusable workflow or copied type catalogue is needed, so no root
or customer skill edits are required. Customer protected-source boundaries remain.

Point 10o is complete with its logical implementation commit/checklist update.
Point 10p is next; combined readiness 10r, sole owner manual acceptance 11 and final
verification/release 12 remain open. No owner acceptance or final PASS is claimed.

# RM-2026-017 point 10o — Structured read contract design stop

Date: 2026-11-08. Status: source investigation complete; proposal awaits owner decision.
Point 10o remains open. No runtime implementation, test execution, runtime/assets
preparation or acceptance is claimed.

## Verified source and incorrect premise

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

The snapshot handbook currently documents JSON text, consistently with this code.
This is a planning-premise correction, not proof that existing code is stale.
Under AGENTS's significant-unknown rule, implementation stops before changing the
public field contract. The accepted outcome remains native structured projections;
its required owning contract change must be discussed explicitly.

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

## Proposed owning correction — not approved

Extend the existing public `ManagementField` type vocabulary with `json` for
bounded structured arrays/maps, without adding another envelope, resolver or engine.
Keep its constructor signature and the existing record/read/write interfaces.
Owner: module framework for declarations and Admin for transport/presentation;
audience: existing module/App Management providers and supported Admin replacements;
entrypoints: the existing Management declarations, guarded readers/commands and
public Admin controllers. Implementation remains in their current source homes,
with the same development/production delivery modes.

Proposed semantics:

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

Before implementation, obtain owner agreement to this contract extension and then
record its complete signatures/constraints in the owning public guide. Assess the
framework API and Auth/Admin/reference candidate versions from the actual change;
no version bump or dependency change is made by this proposal. Assess both instruction
products in the implementation point; current AGENTS/skills already cover public
contract ownership and require no new reusable rule for this investigation.

## Evidence limits and next step

The local knowledge index reported current (234 documents, 13341 chunks), and a
bounded current-intent query located the original snapshot/contract guidance.
Findings above were checked in actual source. No tests were run: the concrete
conflict is established by the declared allowlists and both projection validators.
No services, storage, ledgers, images, assets or temporary Docker resources changed.

Resolve the proposed field semantics with the owner, then amend the 10o premise
and implement the owning correction. Combined readiness remains 10r, explicit owner
manual acceptance 11 and broad final verification/release 12. Platform VERSION
0.7.0 and approved feature/0.8.0 projection are unchanged.

# Admin Panel architecture review and proposal

Review date: 2026-10-29. Planning baseline: platform `0.6.2`, clean `main` after
RM-2026-015. This review specifies proposed contracts, not a delivered public API.
The owner approved the design directions below and explicitly requested activation
on 2026-10-29. Work belongs to the active
[RM-2026-016 roadmap](../roadmaps/closed/RM-2026-016-admin-panel.md); point 1
finalizes complete public signatures before runtime implementation.

## Authority and evidence

The requested functional scope is accepted as planning input: a mandatory English
administration framework, automatic module integration, application-owned
customization, unified forms, complete supported Core administration and safe
operational diagnostics. Owner-approved design directions and the remaining implementation proposals are distinguished below. Existing
AGENTS, roadmap, public API, transaction, security and delivery rules still apply.
No source contract, runtime configuration, dependency or migration changes are
authorized by this review.

The local knowledge index reported current: 191 documents, 9,310 chunks, no missing
or orphan chunks. A bounded current-intent query located module foundations,
Audit reads, Auth and management command guidance. Significant findings below were
checked in original documents and code. The inventory is source-verified;
composition design and compatibility predictions are deductions, not runtime PASS.
No tests, native acceptance, builds or diagnostic health evaluations were run.

Primary evidence (repository-root-relative paths):

- `docs/runa/handbook/architecture/{public-api,module-framework,module-foundations,configuration,database}.md`.
- `src/runa/Contracts/Modules/Management{Provider,EntityProvider,Entity,Field,Operation,Component}.php`.
- `src/runa/ModuleFramework/Management/ModuleManagement.php` and
  `src/runa/Contracts/Modules/ModuleManagement.php`.
- `src/runa/Support/Modules/Api/{ModuleApis,GuardedApi}.php`,
  `src/runa/ModuleFramework/Api/OperationGuard.php`.
- `src/runa/ModuleFramework/{ModuleHealth,ModuleAvailability,ModuleTransitions}.php`,
  `src/runa/Support/Modules/{Settings/ModuleSettings,Work/ModuleWork}.php`,
  `src/runa/ModuleFramework/Events/ModuleIntegrations.php`.
- `src/runa/Runtime/MaintenanceState.php`,
  `src/runa/Http/Middleware/EnforceEffectiveMaintenance.php`.
- `src/runa/modules/{auth,permissions,audit}/module.json` and their `backend/Contracts`
  and `backend/Definitions` public API declarations.
- `src/runa/modules/auth/backend/{Actions/ManageProfiles,Support/EntryTransaction}.php`,
  `src/runa/modules/permissions/backend/{Actions/ChangeAssignments,Support/AccessTransaction}.php`,
  `src/runa/ModuleFramework/Data/AuthorityLock.php`.
- `src/runa/Support/Modules/Frontend/ModuleFrontend.php`,
  `src/runa/Contracts/Modules/{FrontendPage,SlotHost,SlotContribution}.php`.
- `src/runa/modules/auth/frontend/AuthApplication.vue`,
  `src/runa/resources/js/layouts/ApplicationLayout.vue`,
  `docs/runa/handbook/development/frontend/{customization,forms,tables}.md`.
- `src/runa/Identity/{ApplicationManifest,ApplicationIdentity}.php`,
  `src/runa/Providers/RunaServiceProvider.php`, `src/app/application.json`,
  `src/runa/config/{app,branding}.php`.
- `src/runa/Development/Modules/ModuleGenerator.php`,
  `docs/runa/handbook/development/modules.md`,
  `docs/runa/maintainer/{quality/README,testing/strategy}.md`.

## A. Existing capability inventory

| Request sections                                               | Current support                                                                                                                                    | Required Admin work                                                                                                                                                                                   |
| -------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 3–4: Core identity, ownership, lifecycle, independent versions | Implemented framework; Auth 1.1.4, Audit/Permissions 1.0.4. Admin does not exist.                                                                  | New `src/runa/modules/admin`, Core manifest, explicit installation/forward edges, owned API/permissions/tests/docs; 0.x development and 1.0.0 at verified creation closure.                           |
| 4, 9–10: metadata and automatic interfaces                     | ManagementProvider/EntityProvider and bounded entities, fields, operation safety metadata exist. ManagementRenderers describes compatibility only. | Add page/composition/read/write bindings and actual renderers. Current descriptors neither execute operations nor provide CRUD APIs.                                                                  |
| 5–6: shell, navigation and entry                               | ApplicationLayout has module/page navigation and user-menu slot. AuthApplication renders `/account` and sign-out inside that menu.                 | `/admin` dashboard, module-left/page-top navigation, deterministic developer order/defaults and account-menu-only entry. No mandatory Overview or empty sections.                                     |
| 7: identity, native grants, step-up                            | ModuleApis, OperationGuard, AuthStepUp and current Permissions exist. Non-superadministrator native grants work.                                   | Admin entry permission/proof, server-filtered projection and endpoint enforcement. No generic superadministrator bypass.                                                                              |
| 7: field access                                                | ManagementField has writable/sensitive flags; ManagementEntity has read permission; operations have permissions.                                   | Add explicit field/section read/write authorization declarations and server projections. A writable flag is not a field grant; sensitive is not permission metadata.                                  |
| 8, 13, 26: standard UI                                         | Shared forms, dirty guards, reconciliation, feedback, themes, accessible controls and responsive shell exist.                                      | Compose PL/EN defaults, generated forms, controllers, validation mapping and rendered acceptance.                                                                                                     |
| 11, 15: contributions/widgets                                  | SlotHost/SlotContribution already enforce owned build keys, availability, permissions and deterministic composition. Props are flat scalars.       | Publish named Admin hosts, widget/data adapters and editable form participation. A visual slot alone cannot contribute validated writes or coordinate Save.                                           |
| 12: atomic Save                                                | Exact default PostgreSQL transaction, nested Auth/Permissions savepoints and transactional Audit already exist.                                    | Bounded supported composition protocol; ARCH-001. Settings/lifecycle runner operations cannot join it.                                                                                                |
| 14, 17–18: diagnostics/work                                    | Persisted ModuleHealth observations, availability root causes, scheduler recovery, active work and cancellation exist.                             | Authorized sanitized HTTP adapters, timestamps/expiry/dependencies, enabled-default filtering and explicit re-evaluation. Preserve bounded progress semantics; no invented percentage if none exists. |
| 16: management                                                 | ModuleTransitions previews/apply/resume/cancel, ModuleSettings revisions/recovery, ModuleIntegrations exist. Core cannot be disabled.              | Permissioned domain adapters and missing Audit records for newly exposed meaningful framework mutations. No install/update/upload/migration APIs.                                                     |
| 16, 20: complete Core administration                           | Public Auth/Permissions writes and Audit reads exist; management metadata covers only a subset.                                                    | Use the full API matrix below, add bounded owner reads where missing, and keep self/operator capabilities in their proper audience.                                                                   |
| 19: maintenance                                                | Ordinary HTTP and guarded module APIs fail during effective maintenance. Only health/ready HTTP is exempt.                                         | ARCH-002 resolved: retain existing guards; maintenance diagnostics/recovery remain CLI-only, without an Admin exception.                                                                              |
| 20: Audit                                                      | Manual/automatic append-only history, guarded cursor page/detail and atomic records exist.                                                         | Read-only viewer and adapters; reuse owning events without duplicates. No generic undo or arbitrary Audit writer endpoint.                                                                            |
| 21: global settings                                            | DB-backed module settings exist; business settings belong in DB. Global identity/name/logo currently come from source manifests.                   | App-owned dynamic setting declarations/storage and safe runtime presentation projection; ARCH-004 for identity/branding ownership.                                                                    |
| 22–23: independent pages/replacement                           | Ordinary app Laravel/Vue and Auth presentation/headless seams exist. Module frontend pages are module-owned and fixed-path.                        | Public application bootstrap page/contribution registration, Admin facade/controllers/providers, nested replacements. No artificial module requirement.                                               |
| 24: optional infrastructure                                    | Auth owns durable work/delivery and targeted throttling fallback contracts.                                                                        | Reuse exact module behaviors; no hard dependency on nonexistent queue/notification/throttle modules or parallel delivery.                                                                             |
| 25: tables/bulk/export                                         | Table/useTable/provider/selection/views/export foundation exists; no generic backend query engine.                                                 | Endpoint-specific providers, safe selections and exports within each API's bounds. Audit cursor order and unknown totals remain honest.                                                               |
| 27: generator                                                  | Module/api/component/test generation, previews and exact-hash overwrite exist. Components have no page/slot wiring.                                | Opt-in Admin examples/scaffolds, no mandatory Admin dependency for business modules.                                                                                                                  |
| 28–30: docs, tests, simplicity                                 | Central suites, public test support, quality groups and guidance ownership exist.                                                                  | Extend existing infrastructure with security/composition/replacement scenarios; final-only complete verification. No new runners or dependencies proposed.                                            |

Sections 1–2 and 31–34 define the review process, reporting and execution limits;
this review and linked draft satisfy the planning artifacts. Architectural decisions
and release metadata have owner dispositions recorded below; activation is recorded
in the roadmap.

### Complete Core API integration matrix

Resolve guarded contracts at their declared API versions (currently 1.0.0), never
their private implementation. Management operation keys do not automatically name
PHP methods. Each binding must be explicit and tested against its real definition.

| Owner / contract                    | Required administration coverage and boundary                                                                                                                                                                                                                                                                                                     |
| ----------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AuthIdentities                      | Authorized identity and eligibility lookup, including deleted subjects where supported. There is no public pageable user directory; add an additive Auth-owned bounded list/selection read contract instead of querying foreign users from Admin.                                                                                                 |
| AuthLifecycle                       | `create(NewIdentity)` and `change(publicId, expectedVersion, LifecycleChange)`; expose every supported lifecycle intent, deadlines and safety refusal. Preserve last-eligible-superadministrator checks and identifier release/reuse rules.                                                                                                       |
| AuthInvitations                     | Invite/resend by token rotation and invalidate. Never return invitation secrets from Admin.                                                                                                                                                                                                                                                       |
| AuthProfiles                        | Profile name/firstName/surname/login editing; dedicated primary-email change, verified replacement, require verification, secondary-address add/change/remove. `notificationEmails` is a separately permissioned recipient lookup, not permission to expose every address. Add safe admin read projections for editable secondary data if absent. |
| AuthSessions / AuthBulkSessions     | Per-user session list/revoke/revoke-all and selected/all bounded preview/revoke with optional trust. Preserve native selection, both-factor proof and fingerprint.                                                                                                                                                                                |
| AuthSecurity                        | Request password reset, reset factors, trusted-device list/revoke. No password/hash/OTP/TOTP/recovery secret viewer.                                                                                                                                                                                                                              |
| AuthPolicies / ModuleSettings       | Current typed/redacted policy, immutable history, consequences preview, publish, supported policy restore and per-account exceptions/revocation/expiry. Policy restore is a specific existing Auth operation, not generic configuration undo.                                                                                                     |
| AuthPreferences                     | `resetAccount(publicId, revision)` administrative capability. read/write/reset target the current account; keep them in self-service rather than inventing foreign preference editing.                                                                                                                                                            |
| AuthDelivery                        | Permissioned delivery/category/recipient capabilities only where useful to an actual administrative task; preserve private routing. No second mail dispatch UI/engine.                                                                                                                                                                            |
| AuthStepUp                          | Shared proof for Admin entry and owner-defined action requirements. No new proof store, password implementation or policy.                                                                                                                                                                                                                        |
| Permissions / PermissionsSelections | Current grants/introspection and native subject selection. No frontend authorization engine or private identity resolver.                                                                                                                                                                                                                         |
| PermissionsAccess                   | Read/list/detail roles, permissions, presets, assignments; preview/apply assignments, copy and preset; preview/save custom definitions, delete definitions. Preserve add/replace distinction and immutable source/system definitions.                                                                                                             |
| PermissionsAuthority                | Separate preview/apply system authority and preview/confirmed force-delete custom roles. No system-role editing through ordinary assignments.                                                                                                                                                                                                     |
| AuditReads                          | Cursor page/filter/metadata/detail, authorized entity links and optional authorized current identity lookup. No edit/delete/retention/rollback controls.                                                                                                                                                                                          |
| OwnHistory / OwnHistoryDetails      | Existing identity-bound self-history remains self-service. It is not a substitute for general administrative read authority.                                                                                                                                                                                                                      |
| Reserved integration contracts      | AuthorizationIdentity, OperatorAuthority, BulkUserSelection, AuditWriter, DevelopmentAccess and private engines remain reserved. No operator/bootstrap/development-account web controls.                                                                                                                                                          |

This is a capability inventory, not permission to wrap every public method in an
HTTP endpoint. Each operation keeps its supported audience. Module management must
also expose supported integration toggles/configuration, health policy controls,
diagnosis, transition status/resume/cancel, settings recovery and work actions with
their own permissions, safe audit event and actual engine semantics.

## B. Proposed architecture

### Ownership, discovery and public contracts

Admin requires Audit, Auth and Permissions. They do not require Admin. Optional
business modules remain operational without Admin. Keep reusable administration
declarations in additive framework contracts extending the existing management
declaration path; keep rendering, Admin access, projection and form orchestration
in `runa.admin`. This avoids the cycle that would result from making Auth or
Permissions depend on an Admin-owned declaration interface while Admin needs them.
Core-to-application dependencies remain forbidden. An application contribution to
an Auth host is declared by the application, never discovered through a reverse
Core dependency.

Use the existing registry, owned declaration resolver, manifest hashes, renderer
compatibility catalogue and build component registry. Do not add package discovery
or executable database configuration. Add an optional provider extension rather
than mandatory methods to existing ManagementProvider implementations. Preserve
schema-version-1 consumers; version genuinely incompatible DTO changes explicitly.

Before implementation, record complete PHP/TS signatures, bounds, exceptions,
props/slots/controller return shapes and audience in the owning public guide.
Point 1 records these complete signatures and bounds in the
[owning Admin specification](../../handbook/modules/admin/README.md).
Responsibilities below remain design descriptions, not implemented symbols:

| Capability                                                 | Owner / supported consumer / entrypoint                                                                      | Home and delivery                                                                                           |
| ---------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------- |
| Page, section, field, widget and contribution descriptors  | Framework-owned public extension, implemented by trusted modules through management declarations             | `src/runa/Contracts/Modules`; runtime metadata, never executable client input                               |
| App independent pages and Core-page additions              | Admin-owned public bootstrap registration implemented/called from `src/app`; no foreign module writes        | `src/runa/modules/admin/backend/Contracts`; runtime, guarded reads/writes via explicit bindings             |
| Resource reads and commands                                | Owning module public consumption through ModuleApis and named HTTP adapters                                  | Owning `backend/Contracts`, private Actions/Queries/HTTP; production runtime                                |
| Admin projection, entry, dashboard and composite Save      | Admin-owned guarded consumption; named authenticated HTTP adapters                                           | Admin module backend; runtime                                                                               |
| Framework diagnostics/lifecycle/settings/work adapters     | Named source-owned trusted integration, exposed only through authorized Admin use cases                      | Framework engines retain ownership; no customer import of private engines                                   |
| Admin defaults/headless controllers/presentation providers | Public consumption/composition through proposed `@/runa/admin` facade, implemented in app-owned replacements | Admin frontend and facade; built assets with module manifest identity                                       |
| Composite transaction participation                        | Bounded trusted protocol; direction approved in ARCH-001                                                     | Neutral framework participation plus module-owned implementations; no arbitrary callback through ModuleApis |
| Generator examples and extension test bridges              | Developer-only public support                                                                                | Existing generator/testing homes; excluded from production                                                  |

### Routing, shell and entry security

Use `/admin` as a named server dashboard entry and named Admin page routes. Backend
operations remain under `/api/...`. Existing `/modules/<owner>/<code>/<key>` module
routes retain their meaning; Admin page routing is additive. Do not repurpose
FrontendPage.area or silently redirect all existing pages into Admin. Add an
additive default-dispatcher seam for Admin while keeping app-owned entrypoints and
independent routers supported.

Reuse ApplicationLayout: module/independent-page links left, selected-module pages
top, developer-defined order/group/default. Hide empty sections. The sole link into
Admin is next to User panel in AuthApplication's account dropdown (and equivalent
public replacement contracts). ApplicationLayout already offers a user-menu slot;
AuthApplication needs an additive supported menu contribution seam and navigation
inputs so customers do not copy its session/security logic. No Admin link in the
ordinary application sidebar or top navigation. In Admin, reuse the shell while
enforcing English labels/messages, including shared controls, proof prompts and
validation feedback without permanently changing the user's application locale.

Propose an ordinary `runa.admin.enter` permission plus password proof for a stable
server action, using AuthStepUp's current TTL/method/session rules. Check the proof
before protected bootstrap/data and every Admin endpoint; a shell HTML response
alone must expose no protected state. Proof expiry uses the shared confirmation
flow. Module-specific actions still require their own grants, policy, stronger
proof or every-action consumption. Session pause/account change unmounts protected
content using existing Auth controllers. Do not require a system role for entry.

### Declarative rendering and customization

Bind resource reads, lookups and operations explicitly to supported public
contracts. Standard entities compose existing Table, Form, FormSection, field
controls, ConfirmDialog, feedback and dirty guards. Existing ManagementField types
do not express a complete form language; extend only for actual Core/app examples.
No generic model-to-CRUD reflection, request-selected class/method or validation
code in metadata. Own Queries provide bounded filtering/order/selection/counts.

Read and write grants are separate for pages, sections and fields. A denied read
removes values, descriptors, options, derived counts, validation echoes and export
columns; read-only fields reject submitted writes. Backend whitelists inputs and
rechecks current availability, grants, policies and revisions. Field restrictions
do not weaken the owning API's operation permission. Return safe errors without
revealing forbidden values. Existing sensitive settings remain write-only with
set-state, never a readonly secret value.

Use stable named hosts for widgets, resource sections, controls and actions.
Reuse existing scalar SlotHost props for identity/version/context and manifest-bound
custom Vue components. Add a separate typed editable participation contract where
the visual slot is insufficient. Sort contributions by priority then stable owned
ID. Uninstalled/disabled/unavailable/unauthorized contributions neither read nor
save; loss during an open dirty form produces a clear unavailable/conflict state,
preserving safe drafts and refusing the whole submitted aggregate. An invalid
contribution fails its section safely without breaking independent pages; failed
required form participation blocks Save rather than silently omitting writes.

Publish usable defaults plus nested field/section/form/page/widget/layout
replacement and independent-page registration. A customer may replace all
presentation through public controllers; session, proof and backend enforcement
remain platform-owned. App-owned handlers can implement approved custom operations
or contribute to Core pages but cannot replace foreign persistence or security
bindings. Do not allow a presentation replacement to change a Core operation's
authorization or validation rules.

### Composite Save proposal (ARCH-001)

The existing APIs do not prohibit nested transactions. ManageProfiles uses
EntryTransaction and PermissionsAccess uses AccessTransaction; both join the exact
default PostgreSQL connection through savepoints. ModuleApis accepts bounded owned
DTOs/scalars/arrays, not closures, models or connection objects.

For the profile-plus-assignment example, a naive profile-first loop changes the
identity version included in ChangeAssignments' preview fingerprint. It then fails
as stale. Applying the reviewed permission change first, then the profile change,
can preserve that preview for this specific pair, but is not a general protocol
for future contributors, repeated proof consumption or multi-subject lock order.
Grant removal may also invalidate a later call, correctly causing rollback.

Propose a bounded declared aggregate with trusted participant registration and a
single root Action. The server validates the exact participant set, subject UUIDs,
revisions, input namespaces and reviewed changes; opens one default transaction;
acquires registry/authority and actor/subject locks in the supported order; rechecks
availability and authorization; applies compatible owning public operations in an
explicit declared order; records each owning Audit event; commits once. Extend the
neutral trusted lock/preparation participation only as needed to make this order
supported for future modules. Admin never locks or queries foreign business tables.

Keep per-owner expected revisions plus an aggregate revision covering participant
membership and related reads. Do not silently regenerate a reviewed preview after
an earlier write or silently replay a rejected save. Explicitly declare/prove
compatibility per operation, including whether multiple writes consume the same
single-use proof. Incompatible combinations are unavailable as an atomic form;
give a clear supported workflow rather than partial success.

Root rollback removes all business writes, durable same-connection queue inserts,
proof consumption and success Audit. After rollback, synchronously persist required
safe refusal history with AuditRecords.attempt; nested owners cannot independently
commit it. Propagate unavailable history accurately. Do not suppress or duplicate
existing owner success events. Never catch a participant failure and commit the
remaining writes. Return namespaced validation/conflict errors and keep dirty data.

Explicit exclusions from composite transactions: ModuleSettings and
ModuleTransitions require independent runners outside an ambient transaction;
health checks run outside transactions; avatar upload requires its own root/file
lifecycle; remote I/O, mail transmission and unrelated resources cannot roll back
with PostgreSQL. Keep these as explicit owner operations, with durable intent or
after-commit delivery only where the existing owner contract supplies it. A single
Save applies to the declared compatible form, not to every button on a page.

### Diagnostics, management and failure boundaries

Dashboard reads reuse cached/persisted health observations, not health checks on
every render. Show safe checked-at/expiry/freshness, dependencies and registry
states, defaulting to enabled modules. Registry-retained not-installed/disabled
states may be filtered; do not claim discovery of arbitrary absent packages.
Use existing identity versions and a permission-filtered safe runtime allowlist;
never send raw config, paths, environment values, exceptions or upstream bodies.

Explicit authorized Diagnose delegates to ModuleHealth.diagnose with its deadline,
locking and safe errors; retain the existing scheduler. Health policy writes,
Optional transitions, integration configuration and work cancellation get narrow
permissions and meaningful Audit events in their real transaction/publication
boundary. Keep lifecycle preview/generation/preparation/resume/cancel and settings
revision/recovery behavior; pending/recovery_required is not success. Audit review
must identify framework operations already covered by SettingsParticipant and
those needing a new participant, without duplicate events. A wrapper event after
the engine commits is insufficient for mandatory mutation Audit.

Work reads preserve the 100-item projection/truncation signal and declared progress;
offer bounded further reads. Cancellation remains cooperative and policy-dependent.
No arbitrary job execution, Artisan command, shell, generic repair or maintenance
marker clearing endpoint. Independent unavailable module sections remain isolated.

Reuse Auth's current permission/session refresh and form visibility/navigation
reconciliation. No unconditional polling, WebSockets or new grant cache. Existing
owner-specific delivery/outbox and throttling remain authoritative. Optional
integrations require declared compatibility/toggles and explicit safe fallback;
never switch queue transport, reset security counters or duplicate a notification
because an advanced provider becomes unavailable.

### Global settings classification

| Existing source                                                                                             | Classification                                                     | Proposal                                                                                                                                                                                                                            |
| ----------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `src/app/application.json`: id, version, supported Runa range                                               | Developer-owned application identity/compatibility                 | Remain in source, read-only in Admin.                                                                                                                                                                                               |
| Same manifest: name and branding.logo                                                                       | Currently developer-owned canonical presentation identity          | ARCH-004 approved: preserve canonical source defaults; customer presentation/logo overrides persist separately in DB.                                                                                                               |
| `src/runa/platform.json`, root VERSION                                                                      | Platform release identity                                          | Read-only; never runtime settings.                                                                                                                                                                                                  |
| `src/runa/config/branding.php`, app-owned override, CSP/mail settings                                       | Developer-managed presentation/technical policy                    | Keep technical layout/assets/CSP/mail transport in source/config; classify an individual safe presentation option before exposing it.                                                                                               |
| ModuleSettings including Auth policy                                                                        | Module-owned dynamic configuration, encrypted, revisioned, audited | Reuse owner declarations and publication/recovery. Keep module settings in module sections.                                                                                                                                         |
| `.env`, database/queue/mail/cache drivers and credentials, APP_KEY/URL/debug, knowledge/embedding endpoints | Environment/infrastructure                                         | Remain outside Admin; no restart/container/image controls or secret export.                                                                                                                                                         |
| New app-declared safe business/presentation options                                                         | Application-owned dynamic values                                   | Separate global Application settings page and app-owned definitions/defaults, DB persistence, current-request reads/revisions and atomic Audit. No fake app module or storage under Admin's schema for customer business ownership. |

Propose a small public application-settings adapter over app-owned persistence in
`shared`, with source-declared keys/validation/grants, no request-supplied config
paths, and clear precedence between manifest defaults and authorized runtime
presentation. Use existing types/encryption/redaction primitives where suitable;
do not force module lifecycle application handlers onto ordinary app presentation
values. No new asset library or generic upload system; safe same-origin logo asset
selection is enough initially unless a concrete upload requirement is agreed.
Missing DB during emergency/static rendering uses validated build defaults.

## C. Architectural decisions and owner dispositions

The owner resolved all four decisions on 2026-10-29 as recorded individually below.
Ordinary new renderer/adapters, additive
declarations, field permissions, bounded Auth directory reads and public
presentation seams are implementation work, not separate constitutional conflicts.
Their exact public signatures are specified in the
[Admin contract](../../handbook/modules/admin/README.md) by point 1 before runtime
implementation. They remain unavailable until the owning implementation point.

### ARCH-001 — Supported cross-module atomic form coordination

**Current:** Module APIs preserve foreign ownership and bounded value boundaries.
Auth/Permissions support nested same-connection writes, but have owner lock, proof,
preview and refusal protocols. No general editable contribution coordination
contract exists. Settings/lifecycle runners reject ambient transactions. Evidence:
ModuleApis, EntryTransaction, AccessTransaction, ChangeAssignments,
ModuleSettings.exclusive and ModuleTransitions.exclusive in the source paths above.

**Proposed:** Adopt the bounded trusted aggregate participation protocol described
above, exposing only explicitly compatible operations through one Save.

**Reason:** Future Auth + Permissions + app.hr forms require a supported order and
atomic Audit/proof/revision behavior, beyond a visual slot or sequential HTTP calls.

**Impact:** Framework trusted participation/audience rules; Admin orchestration;
Auth/Permissions compatibility declarations or additive preparation APIs where
needed; aggregate revision/input contracts; rollback/concurrency/Audit tests;
module version/changelog and both instruction products. No cross-module SQL/FKs,
public callback gateway or network atomicity is proposed. Exact changed signatures
must be recorded before coding; incompatible existing API changes require separate
module MAJOR approval and are not authorized by accepting this direction.

**Alternatives:** Handwritten source-owned composite Actions for a finite set of
known forms (simpler but insufficient automatic future contribution); independent
module saves with explicit partial results (violates requested all-or-nothing).

**Recommendation:** Approve bounded compatible participation, retaining current
APIs and exclusions; do not introduce a universal distributed transaction engine.

**Approval required:** Yes; obtained on 2026-10-29. The owner approves a coordination
layer in Admin for mixed forms while modules remain independent. The bounded
atomicity, ownership and nontransactional exclusions above remain applicable.

### ARCH-002 — Admin access during effective maintenance

**Current:** EnforceEffectiveMaintenance exempts only health/ready. OperationGuard
rejects effective maintenance and assertCurrent checks manual/update maintenance.
Module availability also includes maintenance. Auth/Permissions/Audit guarded calls
therefore cannot become safe merely by exempting `/admin` in HTTP middleware.
Infrastructure/update fencing may prevent PHP traffic entirely.

**Proposed:** Retain the current HTTP/module maintenance block. Use supported
trusted CLI diagnostics/recovery during maintenance; no Admin exception, propagated
admission context or maintenance-marker controls are introduced.

**Reason:** The owner explicitly chooses CLI-only maintenance operation instead of
extending Admin availability. This resolves the original maintenance UI requirement
by an agreed scope reduction and preserves existing guards.

**Impact:** No maintenance admission API or guard changes. Admin negative tests
must prove maintenance remains blocked, and operator documentation points to
existing CLI diagnostics/recovery. Existing health/ready and scheduler recovery
exceptions retain their established scope.

**Alternatives:** A narrowly admitted manual-maintenance Admin subset would require
a separately agreed guard/integration design. It is excluded from this roadmap.

**Recommendation:** Follow the owner-selected CLI-only boundary.

**Approval required:** Yes; resolved on 2026-10-29. The owner chooses CLI only and
rejects introducing maintenance access through Admin in this roadmap.

### ARCH-003 — Existing reviewed security workflows versus ordinary Save UX

**Current:** PermissionsAccess requires reviewed AssignmentPreview/DefinitionPreview
and every-action `both` proof, even for a no-op write. PermissionsAuthority, Auth
policy publication and bulk session revocation also have previews/strong proof.
See `permissions/access.md`, `permissions/authority.md`, AuthPermissions and
PermissionManagement. The request rejects extra review for normal edits.

**Proposed:** Preserve these owning security workflows as explicit exceptions to
ordinary Save. A profile-only change saves directly; an access-bearing composite
form presents the required effective-access/consequence review and obtains each
owner action's proof, then performs one atomic persistence operation. Review may
be inline but must actually present the approved differences before confirmation.

**Reason:** Hiding or regenerating previews and replacing action-bound proofs with
Admin entry confirmation would weaken existing security and concurrency semantics.

**Impact:** Admin form/action UX and composition semantics, preview DTO transport,
stale-review and proof tests, public examples. Keeping the current owner semantics
does not require breaking module APIs. Removing them would require a separate
security-policy discussion and owner module compatibility assessment.

**Alternatives:** Keep access editing a separate clearly reviewed module workflow
on the same page (does not meet a combined one-Save form); explicitly redesign
Permissions/security policy (larger risk and outside this recommendation).

**Recommendation:** Confirm the security-workflow exception; preserve ordinary
direct Save for edits without an owning review requirement.

**Approval required:** Yes; obtained on 2026-10-29. The owner accepts preserving
the existing reviewed security workflows and stronger action-specific proofs.

### ARCH-004 — Dynamic application branding versus manifest identity

**Current:** ApplicationManifest owns name/logo; ApplicationIdentity projects them.
RunaServiceProvider expressly rejects overrides of canonical branding identity
keys. Browser, native errors, mail and static builds share those validated defaults.
ModuleSettings is module-scoped; no non-module global dynamic store is supplied.

**Proposed:** Keep immutable application ID/version/compatibility in the manifest;
introduce app-owned dynamic display-name/logo/presentation values with explicit
runtime projection and fallback precedence. Preserve `/api/identity` canonical
identity and supply a separate presentation projection. The Runa default logo
stays in code; a customer-selected logo/presentation override is stored in the
application database and takes precedence for application presentation, with the
code-owned default as fallback. Do not mutate protected Runa assets or manifests.

**Reason:** Administrator changes without redeploy cannot be implemented by writing
source manifests/config cache. A separate uncontrolled override would conflict with
the current single-source identity contract.

**Impact:** Public configuration ownership guide, app-owned storage/migration and
declarations, Admin form, identity/presentation consumers, mail/error/static fallback
and CSP validation tests, delivery/customization docs. Existing technical config,
environment secrets and platform identity remain source/operator-owned.

**Alternatives:** Expose only newly declared non-identity business options while
keeping name/logo developer-managed (explicitly reduces requested dynamic branding);
make canonical runtime identity DB-backed (larger cross-consumer compatibility change).

**Recommendation:** Approve an additive app-owned runtime presentation layer with
manifest fallback and a separate projection, preserving canonical identity.

**Approval required:** Yes; obtained on 2026-10-29. The owner accepts the proposed
separation and specifies that Runa's logo remains code-owned while the customer's
customization is database-backed.

## D. Quality, compatibility and documentation plan

Create tests centrally under `tests/backend/runa/modules/admin`,
`tests/frontend/runa` and `tests/tools/runa`, with app-owned consumer fixtures.
Do not place authored suites inside modules. Reuse supplied test support and
guarded disposable workers/endpoints. Test code is delivered during implementation;
complete suites, concurrency acceptance, browser rendering, static analysis,
builds, scanners and native install/update acceptance execute only in the final
roadmap point. No percentage target, new test runner, CI or dependency is proposed.

Acceptance covers unauthorized/non-system-role access; password proof and expiry;
record/page/section/field read/write/export/lookup leakage; current grant changes;
manifest/hash/schema/renderer failure; disabled/unavailable contributions; malformed
input and stale revisions/preview/ABA; rollback of every participant, proof, queue
intent and success Audit; refusal history after root rollback; self-demotion and
last-superadministrator constraints; compatible operation order and multi-subject
locks; nontransactional operation refusal; diagnostic freshness/redaction;
maintenance denial with CLI-only recovery; lifecycle Core/Optional restrictions and recovery;
settings redaction/publication; optional fallback; account-switch/abort races;
dirty draft reconciliation and no replay; independent pages, partial and complete
customer replacements; generator output without Admin; light/dark, keyboard,
mobile/tablet/desktop and Polish/English Admin including shared feedback (owner amendment 2026-10-31).

Initial quality proposal: `docs,backend,frontend,e2e,database,delivery,installation,updates,security-source`.
Backend/frontend/e2e cover changed contracts, actual rendered surfaces and types;
database covers new module/global-setting persistence and constraints; delivery
covers protected/public projection and customer replacement/generator ownership;
native installation and updates cover the new mandatory Core and its real forward
history/customer preservation. Security-source covers the substantial new
authenticated read/write and trusted coordination surface, locked dependency
audits and content review. Native installation/update acceptance is justified by
the changed Core installation contract, not merely by docs or knowledge changes.

No explicit tooling group unless Python/coordinator behavior changes; generator
PHP and source/delivery contracts do not alone change the quality coordinator.
No containers/security-image/production/development/transport/knowledge-runtime
group solely from Admin scope: no image/service/startup/host-executor/knowledge
algorithm change is proposed. Mandatory baseline/hooks, freshness and real selected
prerequisites still apply; a selected prerequisite may broaden execution. Reassess
production if implementation changes deployment/update fencing or runtime packaging,
and knowledge if runtime/source registration behavior changes. The selected CLI-only
maintenance boundary itself introduces no production admission change.
Excluded groups are not PASS. Preview the actual graph before executing final
verification. Proposed 0.7.0 is not a scheduled five-minor all-group checkpoint;
refresh cadence if release order changes.

Review and update affected handbook procedures in the owning point, including all
18 developer topics from the request: architecture/declarations/generation,
navigation/composition/tables/forms/components/backend operations/widgets,
cross-module participation/transactions/authorization/availability,
Core customization/independent pages/full replacement/generator/testing/limitations.
Examples must execute against delivered signatures. The public Admin contract
specification records the point 1 implementation target with explicit proposal
metadata and unavailable status; it does not claim that its APIs are implemented.

Assess root AGENTS/.agents/skills and separately authored customer-agent guidance
for every reusable change. During implementation, update affected backend/security,
frontend form/table/layout references and customer public consumption guidance
without granting protected edits or copying canonical procedures. This planning
task originally changed neither product. Point 1 subsequently adds delivery-status
references to owner/customer backend/frontend skills; the canonical public guide
owns the specification, without authorizing customer protected edits.

## E. Readiness and approval ledger

- RM-2026-016 is the next unused number after closed RM-2026-015. RM-2026-014 was
  cancelled/removed by the owner; do not reuse its historical identity.
- 2026-10-29 — Owner-confirmed platform metadata: `release_type: feature`,
  `proposed_version: 0.7.0` after 0.6.2; MINOR for a substantial compatible
  capability. This resolves release metadata only. It is not platform 1.0.0,
  activation approval or automatic approval of a module MAJOR.
- Existing application and Core manifests cap platform support below 0.7.0.
  Explicitly review/adapt their compatibility declarations in implementation;
  never have the updater silently rewrite a customer's compatibility choice.
- 2026-10-29 — All four architecture decisions are resolved: ARCH-001 bounded
  cross-module coordination approved; ARCH-002 selects CLI-only maintenance;
  ARCH-003 existing security reviews/proofs approved; ARCH-004 app-owned dynamic
  presentation approved, with code-owned Runa logo and DB-backed customer override.
- 2026-10-29 — The owner explicitly requests activation after resolving the four
  architecture decisions and confirming release metadata. Baseline/order remain
  current. Complete public signatures are agent-owned point 1 specification work,
  required before runtime implementation rather than before activation. No runtime
  implementation is delivered by this review or activation.

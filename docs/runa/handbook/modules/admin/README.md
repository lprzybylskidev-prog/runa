<!-- runa-knowledge {"purpose":"current_guidance","effective_date":"2026-10-30","applicable_version":"0.7.0","decisions":[]} -->

# Admin contract specification

Admin 2.0.0 adds native structured management fields, updates the snapshot
consumer contract and includes custom-page lifecycle composition and
editor-lifetime corrections. The 1.0.0 creation release shipped with platform
0.7.0; 2.0.0 is the current unreleased source candidate. It supplies the shared PL/EN
session shell, guarded page/section/resource reads, generated record forms,
independent commands/previews, reconciliation, dirty navigation and native atomic
preview/Save. Core pages cover [Auth administration](../auth/administration.md),
[Permissions administration](../permissions/administration.md), the
[Audit viewer](../audit/administration.md), framework operations, retained settings
recovery and runtime application settings/presentation. Opt-in generators and
[consumer examples](examples.md) support independent App pages and partial/full
presentation composition. The sections below describe the delivered contracts;
future changes require their own verification under the active roadmap.

The framework owns declarative administration and trusted transaction participation.
Admin owns discovery projection, entry, default rendering and aggregate coordination.
Auth, Permissions, Audit and application code retain ownership of their operations,
validation, persistence and Audit. Admin requires those three Core modules; none
requires Admin. An application can declare pages without creating a module.

Read the contracts in dependency order:

1. [Declarations, handlers and wire values](declarations.md).
2. [Atomic participation and compatibility](composition.md).
3. [Frontend controllers and replacement](frontend.md).
4. [Core coverage, framework operations and settings](operations.md).
5. [Generator, consumer examples and authoring map](examples.md).

## Ownership and supported entrypoints

Page declarations, handler DTOs, App registration and guarded Admin reads below
are delivered. Capability-specific reads, field-bound
options, typed widget freshness and source-bound component/build projection are
implemented, including compiled registry resolution and shell rendering. Atomic
participation, generated record forms and independent command dispatch are delivered;
application global settings use platform-owned public adapters.
Resource reads are delivered through `useAdminResource`; `useAdminTable` and
`AdminResourceTable` compose bounded collection reads with the existing Table.
`AdminField` and `validateAdminField` supply controlled field composition; default
record sections compose the one useAdminForm controller and native authorized
reads/independent writes and native atomic preview/Save. Private engines retain their current homes and are never
ordinary application imports.

| Capability                                              | Owner and audience                                                      | Supported entrypoint                                                                 | Implementation home and delivery                                                      |
| ------------------------------------------------------- | ----------------------------------------------------------------------- | ------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------- |
| Pages, fields, sections, widgets, command/read bindings | Framework public extension, trusted module authors                      | Optional `ManagementPageProvider` under existing manifest `declarations.management`  | `src/runa/Contracts/Modules`; runtime declarations                                    |
| Owned read/write adaptation                             | Owning module or application, trusted source handler                    | `ManagementHandler` named by `ManagementBinding`                                     | Owning `backend/Management` or `src/app/Management`; runtime                          |
| Atomic participation                                    | Framework trusted extension, explicitly compatible owning handlers      | `ManagementParticipant::{prepare,project}` and native mutations                      | Contracts under framework; owner implementations; runtime, never ModuleApis callbacks |
| App pages/contributions                                 | Admin public bootstrap extension, ordinary App providers                | `AdminRegistry::register(ManagementPageProvider $provider): void`                    | Contract under Admin `backend/Contracts`; private registry under Admin; runtime       |
| Protected Admin reads/writes                            | Admin public guarded consumption                                        | `AdminAccess` resolved through ModuleApis, API `1.0.0`; named HTTP endpoints         | Admin `backend/Contracts`, private Actions/Queries/Http; runtime                      |
| Core reads/writes                                       | Owning Core public guarded consumption                                  | Existing contracts/API `1.0.0`; additive Auth reads below                            | Owning module `backend/Contracts`, private implementations; runtime                   |
| Diagnostics/lifecycle/work and settings recovery        | Named framework-to-Admin trusted integration                            | Fixed Admin operation adapters, never a customer engine binding                      | `src/runa/Integration/Core/Admin*` bridges and Admin Actions; runtime                 |
| App settings/presentation                               | Platform public extension/consumption, App-owned definitions and values | `ApplicationSettingProvider`, `ApplicationSettings`, `ApplicationPresentation` below | Contracts/adapters below; source declarations in App; runtime                         |
| Default/custom/headless frontend                        | Admin public consumption                                                | `@/runa/admin`; additive `@/runa/auth` menu seam                                     | Admin frontend, facade under `src/runa/resources/js/runa/admin`; built assets         |
| Generator and consumer test support                     | Development consumption                                                 | Existing `runa:make:module`, public test bridges                                     | Existing development/testing homes; absent from production                            |

Framework extensions do not depend on `Runa\Modules\Admin`. App bootstrap registration
does not authorize replacing an existing module's handler or security binding.
Metadata and binding identities are source-owned; requests cannot supply PHP
classes, methods, SQL, component imports, file paths or authorization definitions.

## Delivered module and API versions

Admin 1.0.0 was released with platform 0.7.0 and includes native transaction
composition. Module and guarded API versions are listed separately below.

| Owner         | Source version                             | Contract disposition                                                                                                                                                                                                               |
| ------------- | ------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Platform      | 0.7.0, feature                             | Platform versioning remains independent of the Admin module version.                                                                                                                                                               |
| Admin         | 2.0.0, structured snapshot contract change | Requires Audit ^1.0.0, Auth ^6.0.0 and Permissions ^2.0.0; explicit creation-release history follows development 0.2.0.                                                                                                            |
| Auth          | 6.0.1                                      | AuthProfiles API 2.1.0; AuthAdministration API 2.1.0; AuthPolicies API 4.0.0; AuthStepUp API 2.0.0; other current guarded interfaces retain their declared versions. Administrative reads and owning Admin adapters are delivered. |
| Permissions   | 2.1.2                                      | PermissionsAccess API 2.2.0; source phases replace ambient assignment-write nesting. Other current APIs retain their declared versions.                                                                                            |
| Audit         | 1.1.2                                      | AuditReads API 1.1.0; AuditRecords stays at 1.0.0; the guarded read-only viewer is delivered.                                                                                                                                      |
| Framework API | 2.1.0                                      | Adds bounded json field declarations; native settings use semantic review identity; exact-call read-health planning and native transactions remain available. All current consumers use the corrected signatures.                  |
| App scaffold  | 0.0.0                                      | Its supplied Runa range is >=0.2.1 <0.9.0; customer ranges remain application-owned.                                                                                                                                               |

Auth/Permissions and Admin require platform `>=0.7.0 <0.9.0`. Audit now requires the same target platform range for its supplied Admin defaults. Do not rewrite customer manifests automatically. Retain
actually published migration/upgrade identities and append required forward
history. Pre-1.0 source refactoring does not require preserving obsolete API
signatures or asking for another module-MAJOR approval; record the break and update
current consumers. New behavioral/security/architecture premises still follow
roadmap discussion. Neither platform promotion to 1.0.0 nor release closure is
implied by these independent module versions.

Read handlers invoking on-use/hybrid APIs use the optional
[exact-call source plan](declarations.md#read-health-plans).
Admin prepares each health check outside its existing locked read transaction and
retains current per-call authorization/proof/Policies. Source and narrow diagnostics
are not final runtime/browser acceptance.

## Delivery status and acceptance ownership

Admin 2.0.0 is Core, requires Framework API ^2.1.0, and requires Audit `^1.0.0`, Auth `^6.0.0` and Permissions `^2.0.0`. Its platform
range is `>=0.7.0 <0.9.0`; installation requires a compatible platform runtime. Explicit platform installation/update at the target version
registers it and records `upgrade:initialize:install:2.0.0` after its prerequisites.
Updates from current-schema 1.1.0 or published 1.0.0 each run one explicit
transactional preparation step without replaying initialization.
There are no Admin tables or request-time migrations. Existing preparation
participation synchronizes its declared grants and transactional Audit; installation
assigns no grants or users. Request boot never installs or registers Admin.

Resolve `Runa\Modules\Admin\Contracts\AdminAccess` through ModuleApis with version
`1.0.0`. Protected methods require current Core availability, `runa.admin.enter`, a
current operational Auth session and password proof for `runa.admin.enter` with
TTL 300 seconds and `everyAction=false`. Ordinary direct/role grants suffice;
the protected system role does not bypass proof or session checks. Bootstrap, page and section read are
implemented, as are independent command/previewCommand. Aggregate preview/save execute compatible native participants through one root;
see [atomic participation](composition.md) for limits and current coverage.

Delivered routes are GET `/admin` (`runa.admin.dashboard`),
`/admin/{owner}/{page}` (`runa.admin.page`), `/api/admin`
(`runa.admin.bootstrap`), `/api/admin/entry` (`runa.admin.entry`) and
`/api/admin/{owner}/{page}` (`runa.admin.page.read`). HTML contains no protected
values. The fixed lazy dispatcher loads owned FormRequest/controller/JsonResource
only after admission. API responses are no-store; protected errors use fixed
English messages/reasons. The proof-free authenticated advertisement returns only
`{available:boolean}`; false hides entry without diagnostic details. Ordinary
platform maintenance, including missing mandatory Core installation, retains 503.
Guarded PHP still applies normal gateway availability before calling entry.

The default dispatcher selects Admin without changing App source. Shared
ApplicationSessionShell places the advertised entry beside User panel in the
account dropdown, retaining one session controller. Admin supplies localized PL/EN
ApplicationLayout, owner-left/page-top navigation, password confirmation and
nested replacement providers. Bootstrap returns authorized owners/pages/widgets
and current DB-backed application presentation with source defaults. Auth declares Users and Authentication policy pages; selected-user session revocation belongs to User details; the catalogue includes only currently authorized pages. App providers can supply
independent pages and matching compiled/static components. Section reads,
independent command/preview and bounded aggregate HTTP transports are delivered;
Auth/Permissions administration, the read-only Audit viewer and native profile/ordinary assignment composition are delivered. Framework operations, settings operation recovery, application global settings
and DB-backed presentation are delivered. Source/component tests alone do not
establish rendered or release acceptance for later changes.
The welcome catalogue remains independent localized manifest metadata.

Named trusted `Runa\Integration\Core\AdminEntry`, `AdminHttpGuard`,
`AdminBootstrapController` and `AdminDiagnostics` are private integration, not
customer extension points. `AdminEntry::read(Closure(OperationContext): T): T`
checks current entry inside the default PostgreSQL transaction with registry →
authority → Auth user/proof lock order, without consuming reusable entry proof.
The HTTP admission transaction ends before controller dispatch; each protected
read rechecks proof/current grants in its own transaction. Independent commands
execute after that entry transaction ends and retain native owning protocols.

`AdminDiagnostics::snapshot(string $filter = 'enabled'): array` accepts
`enabled|registered|all`; `module(string $module): array` reports fixed declaration
diagnostics. Both require entry plus `runa.admin.diagnostics.read`, use existing
registry/cached availability/management reads, and reject generation changes.
Projection includes only module identity/kind, source/installed versions, registry
flags, dependencies, availability and observation timestamps/pass state. It excludes
paths, source hashes, settings, secret values, work payloads and native error text.
Point 9 exposes these bounded diagnostics and allowed native operations through
[AdminOperations](operations.md#operational-http-and-presentation-contract).
Engine-owned transactional Audit covers meaningful operational writes; maintenance
admission and CLI recovery remain unchanged. No arbitrary engine dispatcher exists.

Point 3 implements the declaration and projection subset described in
[declarations](declarations.md#delivered-discovery-and-read-projection).

Point 1 records contracts and current-API compatibility fixtures. Points 2–11
implement these contracts and author behavioral tests. Point 12 alone runs complete
affected-area suites, concurrency/browser/native acceptance and release checks.
There is no new dependency, CI pipeline, test runner or runtime service.

The private compatibility ledger under
`docs/runa/maintainer/architecture/admin-contract-baseline.json` records current
Core method signatures and operation declarations for every applicable method,
including audience exclusions. It is owner evidence, excluded from customer
delivery. The public [coverage map](operations.md#core-operation-coverage) owns
the administrative behavior and boundaries.

## Revised native transaction direction

The owner approved direct native Auth/Permissions/framework refactoring on
2026-10-30. The [phased transaction contract](../../architecture/module-transactions.md)
supersedes the earlier unchanged-API composition proposal. AuthProfiles 2.1.0 and
PermissionsAccess 2.2.0 ship with independent Auth 6.0.1 and Permissions 2.1.2; standalone
writes have a root boundary, while source composition joins native phases.
Auth/Permissions require platform >=0.7.0 <0.9.0. Audit ships as independent 1.1.2 with optional guarded read ordering; record signatures remain unchanged.
The native path and Admin aggregate dispatch are implemented. The first stable
Admin creation release records its explicit final upgrade boundary to 1.0.0.

Admin entry uses the Auth [protected-section idle contract](../auth/security.md#protected-section-idle-access),
not fixed-age operation proof. Its document/controller includes navigation and
teleported dialogs; automatic reads do not renew. GET/POST `/api/admin/activity`
(`runa.admin.activity.show`/`runa.admin.activity.renew`) retain entry grants,
availability and session checks. Bootstrap `activity` exposes numeric
`remaining_seconds` and `heartbeat_seconds`. Native aggregate entry uses an idle
MutationProof; each owning sensitive operation keeps its independent proof policy.

Named module settings snapshots and the public AdminSettingsPage/controller are
documented in [settings snapshots](settings-snapshots.md).

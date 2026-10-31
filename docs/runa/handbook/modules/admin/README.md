<!-- runa-knowledge {"purpose":"current_guidance","effective_date":"2026-10-30","applicable_version":"0.7.0","decisions":[]} -->

# Admin contract specification

This guide records the implementation status and remaining specification for
RM-2026-016, targeting platform 0.7.0. Points 2–4 supply Admin 0.1.0 source,
explicit installation history, entry security, declaration discovery and authorized
bootstrap/page/section reads, the English session shell, page-read HTTP and public
frontend replacement seams. Point 5 adds named section-read HTTP and the public
resource read controller, explicit cursor-table composition and automatic declared
collection sections and controlled fields, then completes generated record forms,
independent commands/previews, reconciliation, dirty navigation and owning
collection adapter composition. Point 6 supplies native atomic preview/Save,
source-owned participants and initial Auth profile/Permissions assignment composition.
Point 7 delivers complete [Auth administration](../auth/administration.md). Point 8 supplies [Permissions administration](../permissions/administration.md) and the [Audit viewer](../audit/administration.md); point 9 supplies guarded framework operations and module settings. Point 10 delivers guarded application global settings and no-restart presentation.
Existing Auth, Permissions, Audit and framework APIs retain their current contracts.
Source implementation and focused checks do not establish rendered or release acceptance.

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

## Ownership and supported entrypoints

Page declarations, handler DTOs, App registration and guarded Admin reads below
are implemented through point 3. Capability-specific reads, field-bound
options, typed widget freshness and source-bound component/build projection are
implemented, including compiled registry resolution and shell rendering. Atomic
participation, generated record forms and independent command dispatch are delivered;
application global settings are delivered in point 10 through platform-owned public adapters.
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
| Diagnostics/lifecycle/settings/work                     | Named framework-to-Admin trusted integration                            | Fixed Admin operation adapters, never a customer engine binding                      | `src/runa/Integration/Core/Admin*` bridges and Admin Actions; runtime                 |
| App settings/presentation                               | Platform public extension/consumption, App-owned definitions and values | `ApplicationSettingProvider`, `ApplicationSettings`, `ApplicationPresentation` below | Contracts/adapters below; source declarations in App; runtime                         |
| Default/custom/headless frontend                        | Admin public consumption                                                | `@/runa/admin`; additive `@/runa/auth` menu seam                                     | Admin frontend, facade under `src/runa/resources/js/runa/admin`; built assets         |
| Generator and consumer test support                     | Development consumption                                                 | Existing `runa:make:module`, public test bridges                                     | Existing development/testing homes; absent from production                            |

Framework extensions do not depend on `Runa\Modules\Admin`. App bootstrap registration
does not authorize replacing an existing module's handler or security binding.
Metadata and binding identities are source-owned; requests cannot supply PHP
classes, methods, SQL, component imports, file paths or authorization definitions.

## Current candidate versions and release plan

Released platform baseline is 0.6.2. The 0.7.0 candidate includes deliberate
pre-1.0 native transaction changes approved by the owner on 2026-10-30. Current
source versions below are not a claim of completed release acceptance.

| Owner         | Current source / target                   | Contract disposition                                                                                                                                              |
| ------------- | ----------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Platform      | Target 0.7.0, feature                     | Canonical VERSION stays at the released baseline until finalization.                                                                                              |
| Admin         | Development 0.1.0; creation release 1.0.0 | Requires Audit ^1.0.0 and Auth/Permissions ^2.0.0; no unpublished 0.2.0 compatibility edge.                                                                       |
| Auth          | 2.0.0                                     | AuthProfiles API 2.0.0; other current guarded interfaces retain their declared versions. Administrative reads and owning Admin adapters are delivered in point 7. |
| Permissions   | 2.0.0                                     | PermissionsAccess API 2.0.0; source phases replace ambient assignment-write nesting. Other current APIs retain their declared versions.                           |
| Audit         | Current 1.1.0 candidate                   | Owning read/record contracts stay at 1.0.0; point 8 supplies the guarded read-only viewer.                                                                        |
| Framework API | 1.0.0                                     | New trusted transaction extension; constructor changes remain internal.                                                                                           |
| App scaffold  | Keep 0.0.0                                | Widen its Runa range to >=0.2.1 <0.8.0 in the owning integration point.                                                                                           |

Auth/Permissions and Admin require platform `>=0.7.0 <0.8.0`. Audit now requires the same target platform range for its supplied Admin defaults. Do not rewrite customer manifests automatically. Retain
actually published migration/upgrade identities and append required forward
history. Pre-1.0 source refactoring does not require preserving obsolete API
signatures or asking for another module-MAJOR approval; record the break and update
current consumers. New behavioral/security/architecture premises still follow
roadmap discussion. Neither platform promotion to 1.0.0 nor release closure is
implied by these candidate module versions.

## Delivery status and acceptance ownership

Admin 0.1.0 is Core and requires Audit `^1.0.0`, Auth/Permissions `^2.0.0`. Its platform
range is `>=0.7.0 <0.8.0`: source checkout at released 0.6.2 does not authorize
installing it there. Explicit platform installation/update at the target version
registers it and records `upgrade:initialize:install:0.1.0` after its prerequisites.
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
account dropdown, retaining one session controller. Admin supplies English-scoped
ApplicationLayout, owner-left/page-top navigation, password confirmation and
nested replacement providers. Bootstrap returns authorized owners/pages/widgets
and current source-default presentation (revision 0). Auth declares Users, Authentication policy and Bulk session revocation pages; the catalogue includes only currently authorized pages. App providers can supply
independent pages and matching compiled/static components. Section reads,
independent command/preview and bounded aggregate HTTP transports are delivered;
Auth/Permissions administration, the read-only Audit viewer and native profile/ordinary assignment composition are delivered. Framework operations and module settings are delivered in point 9; point 10 delivers application global settings and DB-backed presentation. Source/component
tests are not final rendered/release acceptance.
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
[AdminOperations](operations.md#operational-http-and-presentation-contract-point-9).
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
supersedes the earlier unchanged-API composition proposal. AuthProfiles and
PermissionsAccess use guarded API 2.0.0 and independent module 2.0.0; standalone
writes have a root boundary, while source composition joins native phases.
Auth/Permissions require platform >=0.7.0 <0.8.0. Audit develops as independent 1.1.0 with unchanged guarded read/record signatures.
The native path and Admin aggregate dispatch are implemented; complete owning
read/operation coverage retains its roadmap points. No
intermediate unpublished Admin 0.2.0 update path is retained.

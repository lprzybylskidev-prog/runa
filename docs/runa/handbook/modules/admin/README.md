<!-- runa-knowledge {"purpose":"proposal","effective_date":null,"applicable_version":"0.7.0","decisions":[]} -->

# Admin contract specification

This is the implementation specification for RM-2026-016, point 1, targeting
platform 0.7.0. Admin is not yet delivered. None of the new PHP symbols, frontend
exports, routes or persistence below is available until its implementation point
is complete. Existing Auth, Permissions, Audit and framework APIs remain available
under their current guides. Specification fixtures protect those existing APIs;
they do not prove Admin runtime behavior or rendered acceptance.

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

All new symbols in this table are specified, not delivered. Private engines retain
their current homes and are never ordinary application imports.

| Capability                                              | Owner and audience                                                      | Supported entrypoint                                                                 | Implementation home and delivery                                                      |
| ------------------------------------------------------- | ----------------------------------------------------------------------- | ------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------- |
| Pages, fields, sections, widgets, command/read bindings | Framework public extension, trusted module authors                      | Optional `ManagementPageProvider` under existing manifest `declarations.management`  | `src/runa/Contracts/Modules`; runtime declarations                                    |
| Owned read/write adaptation                             | Owning module or application, trusted source handler                    | `ManagementHandler` named by `ManagementBinding`                                     | Owning `backend/Management` or `src/app/Management`; runtime                          |
| Atomic participation                                    | Framework trusted extension, explicitly compatible owning handlers      | `ManagementParticipant` and immutable plans                                          | Contracts under framework; owner implementations; runtime, never ModuleApis callbacks |
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

## Compatibility and independent version plan

Baseline: platform 0.6.2; Auth 1.1.4, Permissions/Audit 1.0.4; each current manifest
and the app scaffold excludes platform 0.7.0. This is a plan, not a manifest update.

| Owner         | Planned release                  | Reason and prerequisites                                                                                                       |
| ------------- | -------------------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| Platform      | 0.7.0                            | Compatible feature release; no existing API removal                                                                            |
| Admin         | Develop at 0.1.0; close at 1.0.0 | New Core creation, explicit installation and forward history; required Audit/Auth/Permissions `^1.0.0`                         |
| Auth          | 1.2.0                            | Additive directory/detail read API, composition implementation, menu/presentation inputs; keep current APIs at exactly `1.0.0` |
| Permissions   | 1.1.0                            | Additive management/composition declarations and owned participant; preserve reviewed operation signatures and proofs          |
| Audit         | 1.1.0                            | Additive viewer declarations/handler; keep read/record contracts unchanged                                                     |
| Framework API | Keep `^1.0.0`                    | New optional interfaces, unchanged v1 provider methods and DTOs                                                                |
| App scaffold  | Keep app version 0.0.0           | Explicitly widen Runa range to `>=0.2.1 <0.8.0` only when 0.7.0 support is delivered                                           |

For supplied Core manifests preserve the existing lower bound and widen platform
support to `>=0.4.2 <0.8.0`; new Admin targets `>=0.7.0 <0.8.0`. Do not widen customer
manifests automatically. Review source/runtime/version checks and dependent module
requirements at the owning implementation point, update the central module
changelog and append forward edges without modifying historical identities.
No incompatible module change or MAJOR is authorized. If later implementation
cannot preserve this plan, discuss the changed premise before coding it.

## Delivery status and acceptance ownership

Point 1 records contracts and current-API compatibility fixtures. Points 2–11
implement these contracts and their behavioral tests. Point 12 alone runs complete
affected-area suites, concurrency/browser/native acceptance and release checks.
There is no new dependency, CI pipeline, test runner or runtime service.

The private compatibility ledger under
`docs/runa/maintainer/architecture/admin-contract-baseline.json` records current
Core method signatures and operation declarations for every applicable method,
including audience exclusions. It is owner evidence, excluded from customer
delivery. The public [coverage map](operations.md#core-operation-coverage) owns
the administrative behavior and boundaries.

# RM-2026-012 API and placement map

This record preserves the point-12 baseline and the compatible layout delivered
by points 13–19. RM-012 subsequently closed and released as 0.6.0; point numbers
and baseline inventories below are historical engineering evidence. Use the public
API guide and current source for supported consumption. Point 13 delivers the platform PHP relocations and provider split.
Point 14 delivers the built-in module execution layout while retaining contract
and historical upgrade identities. Point 15 publishes named frontend consumer
contracts through the retained facades and normalizes reactive presentation maps.
Point 16 delivers the internal account collaborators and Auth-owned preference/
welcome composition while retaining the public controller and facade identities.
Point 17 delivers isolated management tooling, grouped developer support, canonical
generator anatomy, private browser configurations/branding tools and delivered
static-error builders. Point 18 delivers symbol/import enforcement and runtime
projection. Point 19 reconciles the public placement guide and both instruction
products with those sources. The public authority for
customer use remains [the API boundary](../../../handbook/architecture/public-api.md)
and each capability's linked public guide. Ordinary Laravel application code and
optional application-owned modules remain equally supported.

## Evidence and interpretation

[source-map.json](source-map.json) enumerates all **859 tracked `src/runa` files**
at `baseline_commit`, including the hidden module placeholder. Each row records
owner, audience, execution mode, customer source projection, production disposition,
exact current/target file and PHP symbol where applicable. An unchanged target is
intentional, not an unreviewed remainder. `point` owns a relocation; null means no
relocation. The separate additions list records 29 new collaborators without assigning them fictitious baseline source objects. Baseline targets plus those additions cover all 884 current tracked src/runa files; four owner configurations/renderers moved outside that tree. This is source inventory, not runtime acceptance.
Hashes describe the baseline, not a freshness requirement after implementation.

[signatures.json](signatures.json) records PHP declarations, frontend facade and
component evidence, source objects, named reserved callers and customer/generated/
test/handbook references. The exact `commit:path` source object is the signature
baseline, including PHPDoc generics, inherited methods, constructor defaults,
exceptions, callback arguments/results and inferred TypeScript controller members.
Excerpts and literal reference matches aid navigation; they are not a parser,
semantic compatibility proof or an import allowlist. Existing invalid example
imports are findings to repair, not new supported APIs.

The map is owner engineering data, excluded from customer acquisition and production.
Do not install it as another runtime registry. Point 18 implements boundary checks
through existing source/quality ownership. Final acceptance is recorded in the
closed RM-012 roadmap.

## Frozen dependency direction

Application code consumes public adapters, module contracts and frontend facades.
Public adapters delegate to their owning engines. Engines depend on platform value/
extension contracts, Laravel and injected effect boundaries. Core integration may
know Auth/Audit/Permissions contracts and designated participants; it belongs in
`Runa\Integration\Core`, outside the generic module engine. A module owns its
contracts and implementation. Foreign business use goes through guarded ModuleApis;
reserved integration permits only named callers and existing transaction procedures.

`Runa\ModuleFramework` is the internal module-engine home. It is deliberately
outside `Runa\Modules\<Code>` so it cannot collide with registry-aware module
loading. Internal engines never import application implementations or command
adapters. Providers compose engines and integration, commands invoke them, and
public signatures never require customers to import engines to implement a handler.
Container-only constructor collaborators are composition details, not customer
construction/extension seams. Renaming such collaborators must preserve container
resolution and all documented constructors actually used by customer code.

Keep the guarded module autoloader, manifest hash/source checks and namespace
ownership. Composer handles ordinary platform PSR-4 roots; it must not acquire an
unconditional module mapping or a fallback classmap that bypasses registry checks.
The isolated management loader remains separate from application bootstrap.

## Placement by responsibility

| Baseline area                                          | Frozen target / retained purpose                                                                                                                                                                                                                                                             | Implementation owner |
| ------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------- |
| `Contracts/Modules`                                    | Retain exact file/FQCN identities; classify individual contracts below. No physical directory name grants authority.                                                                                                                                                                         | 13–14                |
| Public `Support/Modules` adapters and values           | Retain exact paths/FQCNs listed in the JSON and public guide. Delegate private work rather than adding aliases or copied engines.                                                                                                                                                            | 13                   |
| Other `Support/Modules`                                | `ModuleFramework/<same responsibility and filename>`; Core bridges go to `Integration/Core`. Generator is the development exception below.                                                                                                                                                   | 13                   |
| Public seeding and `Logging/LogContext`                | Retain their documented names, including `SeedGraph` used by the application seeder.                                                                                                                                                                                                         | 13                   |
| Other `Support`                                        | `Identity`, `Configuration`, `Logging`, `Mail`, `Runtime` by responsibility; test guard goes to `Testing/Database`. Keep public `VersionCompatibility`.                                                                                                                                      | 13 / 17              |
| `Database` / `database`                                | Retain PHP infrastructure versus migrations, seeders and forward preparation. Preserve migration identities and bytes.                                                                                                                                                                       | 13                   |
| `Foundation`, `Http`, `Console`, `Providers`           | Retain entrypoints and transport identities; split composition below. Commands are supported CLI behavior, not extensible PHP bases. Retain the welcome controller used by application routes.                                                                                               | 13                   |
| Built-in module `backend/Contracts`                    | Retain exact module-owned public and reserved FQCNs. Distinguish public APIs, declarations, local helpers and trusted contracts per row.                                                                                                                                                     | 14                   |
| Module `Definitions`                                   | Pure descriptors stay; executable Auth handlers/policies move to `backend/Work`, with exact targets in the map.                                                                                                                                                                              | 14                   |
| Module HTTP                                            | Controllers/resources gain `Http/Controllers` and `Http/Resources`; request subtrees remain. Private proxies, Actions, models, queries and storage retain cohesive existing homes.                                                                                                           | 14                   |
| Module initialization and `Upgrade*.php`               | Retain class names, paths, edges, step IDs and fingerprints. These persisted identities take precedence over grouping old empty handlers into new folders. Do not consolidate retained handlers in this roadmap.                                                                             | 14                   |
| Module frontend                                        | Auth's facade-targeted feature presentation remains under its module's `frontend`; discovered opt-in module components use `frontend/components/<snake_case>.vue`. Auth entry components are not implicitly registered plugin components.                                                    | 15–17                |
| `resources/js/runa`                                    | Retain all supported `@/runa/{ui,auth,http,modules}` imports/subpaths. Private declarations such as pdfmake/virtual ambient types do not become public exports.                                                                                                                              | 15                   |
| Private UI implementations                             | Retain components/layouts/composables with their feature owner; controller decomposition adds collaborators below. Dedicated shell menus remain internal.                                                                                                                                    | 16                   |
| `management`                                           | `Tooling/Management`, retaining `Runa\Management` through its isolated loader and all command/schema/host protocol identities. It is delivered operator tooling, not module business management.                                                                                             | 17                   |
| `Testing`                                              | Keep `Tests\TestCase` identity/path; PHP runner adapters stay development-only, internal PHP adapters move to `Testing/Backend`, worker script to `testing/backend`. Retain public TS bridges/browser support and frontend-runtime config entry at their exact paths as development facades. | 17                   |
| `frontend`                                             | Retain the public module plugin import path used by application Vite configuration; private Vitest/Playwright configs move to `tools/maintainer/frontend`.                                                                                                                                   | 17                   |
| `stubs/modules` and ModuleGenerator                    | One capability in `Development/Modules`, templates in its `stubs`. Generated consumers use public APIs, central test/doc roots and canonical component anatomy.                                                                                                                              | 17                   |
| Executable error builders                              | `build/errors/{build.php,render.mjs}`. Runtime error views/messages/templates stay resources. Public static error adoption remains supported.                                                                                                                                                | 17                   |
| Owner branding renderers                               | `tools/maintainer/assets`. Existing logos/fonts/licenses remain resources.                                                                                                                                                                                                                   | 17                   |
| `config`, `routes`, `lang`, manifests, other resources | Retain owned locations and loading semantics. Config overrides stay application-owned; imported language/license snapshots retain audited bytes. Composer/pnpm source manifests continue feeding composition.                                                                                | 18–19                |

## Public PHP operation closure

The source-object signatures are retained for all supported extension interfaces,
values, base classes and module contracts. Public declarations may be implemented
by customer modules without becoming a platform integration participant.

| Public surface                                                                  | Supported operation and signature closure                                                                                                                                                                                                                                                                                   |
| ------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Contracts\Modules` declaration providers and handlers                          | Implement API/HTTP/command/job/event/integration/settings/frontend/slot/schedule/management/role/upgrade/health/lifecycle descriptors and handlers. Preserve their required DTOs/enums, schema versions, callbacks and defaults. Runtime invocation remains owned by the matching guarded engine.                           |
| `Support\Modules\Api\ModuleApis`, `GuardedApi`                                  | Resolve exact versioned owned interfaces; implement typed proxies through protected `invoke`. Direct engine admission/scope/boundary validation is private. `OperationRejected` and `GraphBlocker` remain catchable public values with safe HTTP rendering.                                                                 |
| `Support\Modules\Data` public helpers, `ModuleIdentity`                         | Extend ModuleModel; use ModuleSchema from owned migrations, PublicReferenceRule with PublicReferenceLookup, and the identity's schema/table/storage derivation. Preserve Laravel base methods, Blueprint and failure callbacks and public UUID routing.                                                                     |
| `Support\Modules\Work\WorkContext`, `WorkStopped`, `ModuleWork`                 | WorkHandler receives WorkContext; read workId/deliveryKey and checkpoint progress, handling WorkStatus/WorkStopped. Customers do not construct contexts, provide lease tokens or invoke queue execution internals. Dispatch and documented status/cancellation/recovery calls keep existing ingress/ownership restrictions. |
| `Support\Modules\Events\ModuleEvents`, `Storage\ModuleStorage`, `ModuleDisk`    | Keep dispatch/transactional preparation callback, PublicEvent, disk stream/resource and path contracts. Direct listener construction and storage escape checks remain internal.                                                                                                                                             |
| `Support\Modules\Settings\ModuleSettings`                                       | Retain owning-module `value`, upgrade-default context and documented redacted read/publication procedures. This does not authorize reading another owner's secrets or publishing outside the trusted management transaction.                                                                                                |
| `Support\Modules\Frontend\ModuleFrontend`                                       | Retain ordinary application `applicationHost(SlotHost)` and documented projection behavior. Ordering writes remain trusted management operations; a public class does not make every method a customer security bypass.                                                                                                     |
| `Support\Modules\Development\ModuleContractSuite`                               | Retain source and runtime descriptor diagnostics. ModuleRecord/RegistrySnapshot remain stable signature values; not authority to construct persisted registration state. Runtime diagnostics are required in production.                                                                                                    |
| `Support\Seeding`, `Support\Logging\LogContext`, `Support\VersionCompatibility` | Preserve managed seeder inheritance/category/dependencies, SeedContext/native generator, explicit development contributors/fixtures, application SeedGraph composition, synchronous scoped logging callbacks and bounded compatibility grammar. Production rejects development seeding.                                     |
| Auth module public contracts                                                    | Guarded Auth APIs and their readonly values, preference/delivery declarations and supported policy surfaces stay module-owned. Reserved contracts below are excluded from ordinary consumption.                                                                                                                             |
| Audit module public contracts                                                   | Guarded records/reads/own-history APIs and values, Auditable/AuditModelPolicy extension and local AuditSuppression.run retain default-transaction/redaction/rollback rules. `active()` is capture plumbing, not permission to suppress explicit history.                                                                    |
| Permissions module public contracts                                             | Guarded permissions/access/authority/selection APIs and values retain current grants, proof, eligibility, record-policy, revision and audit requirements. DevelopmentAccess remains restricted development integration.                                                                                                     |

Constructing a DTO is not establishing identity, granting permissions or acquiring
a transaction reservation. Internal validators may reference public values without
making their injected engines public. All inherited Laravel/PHP/Vue standard types
retain their upstream contracts; no new dependency or framework API is introduced.

## Reserved integration

`reserved_integrations` contains exact baseline caller file lists, including
implementations and provider composition. Translate those file identities through
source-map targets during migration; new composition delegates below inherit only
the bindings they receive. Literal matches include validation-only DTO users;
those are not new trusted business entrypoints.

| Contract group                                                            | Allowed purpose / named integration boundary                                                                                                                                                                                                     |
| ------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| AuthorityGuard; Auth OperatorAuthority                                    | Auth mutation Actions enforce protected membership; ProtectedAuthority/OperatorPermissions bridge to Permissions PreserveAuthority/OperatorAuthority, retaining registry → authority → user lock order.                                          |
| Auth AuthorizationIdentity                                                | AuthAuthorizationIdentity, AuthAuditActor and the listed Permissions read/authority collaborators only; non-recursive current identity resolution.                                                                                               |
| Auth BulkUserSelection                                                    | Auth BulkSessions through PermissionUserSelection and guarded PermissionsSelections; authorization is rechecked on preview and execution.                                                                                                        |
| SettingsParticipant/SettingsAuthorization; PreparationParticipant/Context | ModuleSettings/ModuleUpdates through AuditParticipation to Audit Participation, Auth PolicyHistory and Permissions Participation inside the default reserved transaction. No browser/ordinary ModuleApis payload accepts the capability context. |
| AuditWriter; AuditActorResolver                                           | Audit storage plus Permissions initialization/participation/declaration synchronization; optional AuthAuditActor enrichment. Public manual history uses guarded AuditRecords instead.                                                            |
| OperationAuthorizer; HttpAccountPolicy; TrustedPrincipal                  | Framework operation/HTTP guards, Auth account bridge and PermissionAuthorizer/Gate. DTO validation does not bind a principal; application code uses guarded permissions or Laravel policies.                                                     |
| AuthRequestThrottle                                                       | ThrottleAuthEntry and its no-integration default; future trusted bridge follows the public throttling procedure. No new integration is supplied by this map.                                                                                     |
| SecurityMailDelivery                                                      | Auth SecurityNotices only; retain direct critical-mail fallback. No generic customer mail binding is authorized.                                                                                                                                 |
| DevelopmentAccounts/DevelopmentAccess                                     | Existing explicit development CLI/contributors and cross-module fixture integration; never production initialization or ordinary guarded business operations.                                                                                    |

Required production operator diagnostics include application/config manifest checks,
module source/runtime contract checks, runtime/readiness, module sync/update/lifecycle/
health recovery, redacted settings/management and Auth operator recovery. Their
framework prerequisites remain delivered; a directory called Development does not
alone exclude ModuleContractSuite. Generator/test/Xdebug/knowledge/branding support
and third-party development test packages are excluded from production runtime.
`operator-bundle` in JSON means available to the documented isolated operator
transport, not loaded into every PHP-FPM request or exposed as an HTTP endpoint.

## Frontend signature and alias closure

The supported aliases stay `@/runa/ui`, `@/runa/auth`, `@/runa/http`,
`@/runa/modules`, their documented public type/style subpaths and the explicit
`@runa/testing/{vue,vitest,playwright}` development aliases. Broad `@runa/*`
implementation imports are private. `virtual:runa-module-components` stays an
opt-in build entry. No directory facade, second dependency installation or router
is introduced. Retain `Testing/browserProblems.ts` and `Testing/frontend-runtime.mjs` because
existing documented relative imports and application configuration consume them.
Language grouping does not justify breaking these supported development entrypoints.

All facade exports, underlying props/defaults/emits/slots and function result types
are frozen at the recorded source objects. Point 15 publishes explicit consumer
names without narrowing or silently dropping existing inferred members. Auth entry,
account and welcome controllers plus useApplicationSession stay exported. In
particular account history/conflict/security/delivery/trusted-device methods,
entry CSRF/proof/enrollment methods and session refresh/logout/activity members are
part of that baseline, not incidental private locals to discard during extraction.

AuthPresentation retains optional minimalLayout/applicationLayout/entry/account/
accountLoading/historyDetail/welcome replacements and fallback merging. Preserve
accountId/page props, reactive inputs and keyed lifetimes. AuthApplication keeps
its account prop, loading/default session slots and 401 cleanup. UI components,
MinimalLayout/ApplicationLayout slots, public preferences, forms/tables/exports,
ModulePage/ModuleSlot and HttpErrorLayoutProps retain their complete baseline
contracts. UI implementation imports recorded as evidence never become customer
entrypoints.

The point-15 explicit types live in `modules/auth/frontend/contracts/{presentation,controllers}.ts`
and are reexported through the existing Auth facade. Existing exported types keep
their names. The UI facade additionally owns macro-compatible layout props/slots and static-error
content types; the modules facade owns page/slot props and fallback slots. Existing
wrapper components consume those declarations directly. Controller aliases include
all returned members; type-only imports add no runtime dependency.

Point 16 delivers private account collaborators in `modules/auth/frontend/account/`:
`profile.ts` owns confirmed-profile/draft reconciliation and avatar conflicts;
`lists.ts` owns paginated session/history/trust reads and detail cancellation;
`validation.ts` owns task/proof field requirements and validation; `delivery.ts`
owns delivery reads, recipient reconciliation and preference mutations. `context.ts`
declares the shared lifetime and mutation contracts supplied by the parent; `types.ts`
contains shared internal declarations, with existing public types reexported from
the retained controller. There is no second account store or security controller.

`useAuthAccount` retains the coordinated refresh and sequential section/task save
pipeline, proof/secret cleanup and safe draft lifecycle. Splitting that pipeline
into separate feature writers would obscure shared revisions and partial-success
semantics. Entry and default presentation already compose feature components;
no additional split is needed solely for their size. Public return members remain
unchanged, including inferred values consumed by the named point-15 aliases.

Generic `resources/js/lib/preferenceStore.ts` now contains storage/contracts only.
Auth transport lives in `modules/auth/frontend/preferences/accountPreferences.ts`;
`preferences/session.ts` composes one default adapter and session binding. Public
facade exports stay stable; generic infrastructure imports no Auth code. Supplied
account-aware UI consumers explicitly use that composition. Welcome catalogue
presentation now lives in `modules/auth/frontend/welcomeModules.ts`, still reading
all Runa manifests at build time. Both old private implementation files are removed.
Shared revision, queue, cancellation and account epoch remain single-owned; no
parallel caches or replay.

Consumer evidence includes the minimal application shell/seeder/routes, Reference
module, generated stubs, frontend replacement fixtures and public guides. Repair
Reference's private HTTP import through the existing public facade in point 15.
Point 17 makes generated components follow opt-in discovery; generation never
implies automatic registration. Customer-authored existing files remain protected
by reviewed update decisions, never silently moved or overwritten.

## New composition and migration order

The owner approved one target correction on 2026-10-28: retain the exact
`Support/Modules/Work/ModuleJob.php` file and FQCN as the durable queue envelope.
Laravel serializes its class identity; internal audience does not make a persisted
identity disposable. The target inventory reflects this retained transport, with
no alias or parallel implementation. Other work engines move as planned.

Point 13 keeps RunaServiceProvider as the configured entrypoint and delegates to
`Providers/ModuleFrameworkServiceProvider`, `Providers/CoreIntegrationServiceProvider`
and `Providers/DevelopmentServiceProvider`. Module framework registration owns
engines/neutral defaults; Core integration owns explicit module bridges;
development owns test/generator adapters behind environment/dependency guards.
AuthTransportServiceProvider retains HTTP/Fortify responsibility. Preserve binding
precedence, config/route cache behavior, queue logging and heartbeat boot ordering.
There is no new general provider registry or DI framework.

Point 14 changes private module references/descriptors together, preserving public
APIs, installed upgrade identities and migration bytes; assess independent module
PATCH versions and dependency bounds before any edit. Points 15–16 establish typed
public frontend contracts before splitting orchestration. Point 17 reconciles
all executable callers, Composer/dev autoload, aliases, generator templates and
build paths in the same moves. Point 18 applies boundary/import/signature checks
and actual acquisition/image/upgrade projections. Point 19 reconciles the complete
current guidance, source map and both instruction products before final acceptance.

The source-map's production values are target responsibilities, not an assertion
that today's Docker COPY inputs already implement them. `compiled` means browser
source becomes build output; raw TS/Vue is not required runtime source. `exclude`
means no production runtime dependency. Preserve operator recovery inputs and
necessary source diagnostics when projecting module trees. Private suites remain
in central `tests/*/runa`, owner configurations under tools/maintainer, and all
customer test/support paths retain their distinct delivery ownership.

No new constitutional rule, public namespace break, module MAJOR, dependency,
schema migration or production operation is approved here. Root AGENTS and the
owner/customer backend/Laravel/frontend skills already route to the public boundary;
this point adds owner migration evidence only. Their existing rules remain valid.
Exact delivered-layout guidance changes with the owning implementation points,
then receives the final point-19 reconciliation.

## Point 18 enforcement and projection

The delivered `ModuleFramework/Development/api-boundary.json` owns static symbol
classification and named reserved/internal callers; it is not a runtime registry.
It materializes the public symbol list from this map and the reserved integration
list from signatures.json, correcting provider callers for point 13. Ten internal
platform collaborators have exact existing Core-module caller paths (principal/
operation scopes, authority lock, registry/declarations/availability, settings
schema, immutable boundary values and queue log context). New callers require
source review; customer code never gains an exception by copying their names.

Development ApiSourceCheck uses the already installed PHP analysis parser; the
runtime module source diagnostic needs no developer dependency. ESLint owns the
TS/Vue import rule and TypeScript retains signature/consumer verification. Existing
analyse/lint commands and quality nodes own these checks. PHPStan paths now include
all relocated engine/integration/runtime areas and built-in module source.

The production dependency stage prunes development commands, contributor source
and raw frontend trees; the final image reuses that projection. Diagnostic code,
queue envelopes, module manifests/upgrade history and SMTP verification stay.
TestMail was incorrectly marked development/exclude in the point-12 map despite
being required by installation SMTP verification. Its row now records operator/
source, matching existing code and the roadmap's recovery boundary. No command
removal, receipt/schema change, migration-history rewrite or public alias break
is introduced. Auth 1.1.4, Audit/Permissions 1.0.4 explicitly support platform
0.6.x and retain source-only platform_delivery edges. Their verification_contracts
patch edges complete typed boundaries without data transformations. Dependent ^1.0.0 constraints
remain compatible. At this baseline their manifests supported platform 0.5.0; current manifests and
the released changelog establish current compatibility.

Projected-customer acceptance now authors public PHP/Vue/Python cases and invokes
the delivered checker without owner source; updater fixtures preserve application
code/config/docs and all three test roots. Production acceptance checks the
pruned material and retained runtime/operator identities, then invokes the actual
module runtime diagnostic. Full analysis/projection/image/upgrade evidence belongs
to final point 22; focused unit/source probes are not that acceptance.

## Final source coverage and retained identities

The `files` baseline rows keep their original hashes/source identities and exact
current targets. The separate `additions` rows classify all 29 new files by owner,
audience, execution mode and projection. Together their src/runa targets cover all
884 current tracked source files. Four baseline owner configs/renderers now live
under tools/maintainer. The final source-map contract checks coverage and absence
of obsolete relocated files; it is scheduled with Python contracts in point 22.

Deliberate retained paths include public Support helpers, WorkContext, persisted
ModuleJob, all historical module-root upgrades/migrations, Database infrastructure
versus database resources, Tests\TestCase, browserProblems/frontend-runtime,
frontend/module-components.mjs and public frontend facades/subpaths. Management's
isolated loader retains its namespace. These are supported/persisted boundaries,
not temporary compatibility shims. Removed only the nine empty old source homes
under Support/Modules/{Console,Management,Updates}, management/Actions and management,
stubs/modules and stubs, tools/assets and tools. Runtime caches, reports and data
are outside this source cleanup.

## Public consumer and final quality matrix

| Consumer/contract                                                      | Authored evidence                                                                                                       | Final owner                                                            |
| ---------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------- |
| Ordinary Laravel PHP and public signature closure                      | test_api_boundary.py native/PHPDoc/ordinary-source cases; application scaffold route/seeder; generated public consumers | php-static-analysis, python-tests                                      |
| Optional application modules and generation                            | ModuleDevelopmentTest.php; test_api_boundary.py generated shapes; central app Reference cases                           | backend-tests, module-development-contracts, python-tests              |
| Public aliases, relative imports and TS/Vue types                      | test_api_boundary.py ESLint AST cases; customization/contracts.ts and all Customer*.vue fixtures                        | lint, frontend-typecheck, frontend-tests, python-tests                 |
| Entry/default/custom layouts, CSRF/refusal and keyed pages             | auth-entry.test.ts and its CustomerEntry/MinimalLayout/NestedEntry fixtures                                             | frontend-tests, frontend-browser                                       |
| Custom account, full shell, 401 cleanup and account/draft lifetime     | auth-account.test.ts CustomerAccount/Application/Host fixtures and orchestration regressions                            | frontend-tests, frontend-browser                                       |
| Module pages/slots and static errors                                   | modules.test.ts CustomerModules fixture; error-customization.test.ts CustomerErrorLayout fixture                        | frontend-tests, frontend-build, production-acceptance                  |
| Delivered PHP/Vue/Python support without private files                 | installation_host.py authors public PHP/Vue/Python cases and invokes the delivered checker                              | installation-host-acceptance                                           |
| Interrupted upgrades preserve customer code/config/docs/tests          | test_update_services.py 0.5.0→0.6.0 case and native update/team acceptance                                              | python-tests, update-execution-contracts, installation-host-acceptance |
| Runtime projection, required diagnostics and upgrade identities        | production-runtime.php absence/retained-source checks and installation_host.py actual module runtime diagnostic         | production-acceptance, repository-container-scan                       |
| All current source classifications and public policy/caller identities | test_api_boundary.py current map/declared policy contracts                                                              | python-tests                                                           |

Paths above refer to central tests/tools/runa/contracts, tests/backend/runa/Feature,
tests/frontend/runa/unit (and its fixtures/customization), or native
tests/tools/runa/acceptance as appropriate. Existing quality definitions own these
commands; no new coordinator/registry/CI or automatic group selector is introduced.
All groups remain selected. Final preview, actual candidate-bound acceptance and
current-state release review still belong to point 22. Historical PASS/failure
reports are preserved and do not establish acceptance for the new candidate.

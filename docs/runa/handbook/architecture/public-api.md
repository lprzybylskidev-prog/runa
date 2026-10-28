# Public APIs and platform internals

## Customer extension boundary

Customers write application code in `src/app`. All Runa source stays protected,
including public API definitions. Customers consume, implement or extend a
supported contract from application-owned code rather than editing the contract
or copying its implementation.

Supported use and physical delivery are separate decisions. Internal registries,
locks and resolvers may be delivered because Runa needs them to run; that does not
make them customer APIs. Shared development/test support may be public without
belonging in a production image.

## Classifying a capability

| Kind                    | Supported use                                                                                     | Boundary                                                                                              |
| ----------------------- | ------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------- |
| Public extension        | Implement a documented interface, declare supported metadata or extend a documented base class.   | An interface does not authorize replacing platform security bindings.                                 |
| Public consumption      | Call a supported adapter, consume public values or compose public components.                     | Preserve the adapter's availability, authorization, transaction and version checks.                   |
| Trusted integration     | Follow an explicitly documented, authorized server-side participation procedure.                  | Reserved identity/grant resolution and preparation/private writers are not ordinary application APIs. |
| Internal implementation | Depend on it only from its owning platform/module implementation or a named integration boundary. | Public language visibility or presence in the checkout does not imply customer support.               |

A directory named `Contracts`, a PHP public method or a TypeScript export alone
cannot establish a supported API. Classify the intended consumer and operation:
implementing an extension, consuming a guarded service and replacing an internal
binding are different permissions. Public signatures include required values,
exceptions, base classes, callbacks, props, slots and controller return members.

Documented public helper paths remain supported after internal reorganization.
Internal grouping preserves their file/FQCN identities and frontend/test/build
entrypoints, including helpers used by the supplied scaffold and generators.
Neighboring implementation files remain private. The placement table below records
the delivered layout; physical presence is independent of consumer permission.

## Backend and modules

Customer applications may use ordinary Laravel code or optional application-owned
modules. Runa does not require full DDD, separate domain models, repositories or
mappers. Choose such patterns locally only when they solve a concrete problem;
public adapters remain usable from either application style.

Platform contracts describe module participation; module-owned contracts describe
that module's capabilities and values. Keep ownership with the module rather than
moving every shared contract into the platform namespace.

Follow the [module framework](module-framework.md) and
[module development guide](../development/modules.md) for supported declarations,
guarded API resolution and exact helper preconditions. Existing documented
customer helpers include ModuleApis, GuardedApi, ModuleModel, ModuleSchema,
PublicReferenceRule, ModuleStorage, ModuleEvents and WorkContext, even where their
current namespaces contain Support. This is not permission to use neighboring
internal classes or invoke every public method outside its documented lifecycle.

Use specific Auth, Audit and Permissions guides for their public APIs. Ordinary
module operations use guarded adapters. Local helpers such as Auditable and scoped
Audit suppression have their own transaction and field rules. Private resolvers,
foreign models/queries and reserved installation writers are not shortcuts.

## Built-in module contracts and execution

All supported module contract identities remain in their owning
`Runa\Modules\{Auth,Audit,Permissions}\Contracts` namespace. Classify the symbol
and operation rather than granting access to that whole directory:

| Owner       | Customer consumption or extension                                                                                                                                                                                                       | Reserved integration                                                                                              |
| ----------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------- |
| Auth        | Guarded `Auth*` APIs and their immutable values, exceptions and selections; `PreferenceProvider`/`DeliveryProvider` declarations.                                                                                                       | `AuthorizationIdentity`, `BulkUserSelection`, `OperatorAuthority`, `SecurityMailDelivery`, `DevelopmentAccounts`. |
| Audit       | Guarded `AuditRecords`, `AuditReads`, `OwnHistory`, `OwnHistoryDetails` and their values/exceptions; `Auditable` and `AuditModelPolicy` for opt-in capture; scoped `AuditSuppression::run` under the Audit transaction/redaction rules. | `AuditWriter`, `AuditActorResolver`; suppression's `active()` is capture plumbing.                                |
| Permissions | Guarded `Permissions`, `PermissionsAccess`, `PermissionsAuthority`, `PermissionsSelections` and their preview/selection/declaration values.                                                                                             | `DevelopmentAccess`, only for explicit development contributors.                                                  |

Reserved contracts support the existing named Core integration, owning Actions,
transaction participants and operator/development procedures in each module's
guide. They do not authorize application security-binding replacement, direct
foreign persistence or ordinary unguarded service calls. Availability, current
grants, fresh proof, record policies and atomic Audit remain independent checks.

Private `Actions`, `Queries`, `Models`, `Proxies`, `Support` and definition
providers remain owned by the module. Auth executable work handlers and its work
policy live in `backend/Work`; `Definitions/EntryWork` declares stable operation
keys and delegates execution there. Auth HTTP adapters use `Http/Controllers`,
`Http/Requests` and `Http/Resources`. Public consumers use guarded contracts and
named HTTP endpoints, without importing these implementation classes. Combined
settings declaration/application handlers retain their existing source identity.

Initialization and historical `Upgrade*.php` files deliberately retain their
module-root paths and classes. Their bytes, edge keys and fingerprints participate
in installed-history and interrupted-update validation. Do not consolidate empty
handlers or regroup these persisted identities as directory cleanup. A compatible
source change adds its own forward edge without rewriting prior steps.

## Platform implementation homes

The module engine lives in `Runa\ModuleFramework`, with registry-aware module
loading, admission, lifecycle, persistence and update orchestration. Reserved
Auth/Audit/Permissions bridges live in `Runa\Integration\Core`. Identity,
configuration, logging, mail and runtime infrastructure use their respective
platform namespaces. These are internal implementation homes, not customer APIs.

Documented public adapters and values retain their existing `Runa\Support`
identities, including WorkContext, ModuleApis, ModuleWork, ModuleSettings,
ModuleFrontend, ModuleModel, ModuleSchema, ModuleStorage and LogContext. A public
adapter can receive internal container dependencies without making those engines
customer construction or extension seams. Use the documented adapter or CLI.

`Runa\Support\Modules\Work\ModuleJob` also retains its exact identity because
Laravel serializes it into durable queue envelopes. It remains internal; customers
dispatch through ModuleWork. Do not rename persisted classes as directory cleanup.

RunaServiceProvider remains the configured entrypoint. It registers the module
framework, Core integration and development providers explicitly. Auth transport
keeps its separate Fortify/HTTP provider. Platform boot retains logging, queue
admission and heartbeat ordering. Composer excludes module source from classmap
scanning; the guarded module loader remains responsible for module classes.

## Frontend composition and replacement

Use public `@/runa/ui`, `@/runa/auth`, `@/runa/http`, `@/runa/modules` and their
documented public subpaths/types. A broad alias resolving an implementation file
does not make that file a supported customer import.

Customers may retain supplied defaults, compose application-owned screens from
public components/layouts, or replace presentation through supported headless
contracts. Follow [frontend customization](../development/frontend/customization.md)
for presentation providers, props/slots, Auth controllers, keyed account/page
lifetimes and static error layouts. Named controller/props/slots/events are
exported through those same facades; the adoption guide records their complete
consumer surface. Preserve session/CSRF orchestration,
cancellation, dirty drafts and account isolation; do not copy the private logic.

The minimal supplied dispatcher is not a general application router. Customers
own their application entrypoint, screens and navigation; server authorization
remains authoritative. Module pages/slots retain manifest/build identity checks.

## Source placement and supported entrypoints

| Location                                                                                              | Responsibility and consumer                                                                                                                                                                         |
| ----------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `src/app`, `tests/*/app`, `docs/app`                                                                  | Customer application code/configuration/presentation, authored cases and domain guidance. Ordinary Laravel and optional application modules are supported.                                          |
| `src/runa/Contracts/Modules`                                                                          | Platform declarations, values and reserved integration contracts. Use the individually documented symbols in [module contracts](module-framework.md); namespace membership grants no audience.      |
| `src/runa/Support/Modules`                                                                            | Retained public adapters/values listed below. ModuleJob is the deliberately retained internal persisted envelope.                                                                                   |
| `src/runa/ModuleFramework`, `src/runa/Integration/Core`                                               | Internal engines and named Core bridges. Runtime module diagnostics remain available without developer dependencies.                                                                                |
| `src/runa/modules/<code>/backend`                                                                     | Module-owned public Contracts, private Actions/Queries/Models/Proxies, HTTP Controllers/Requests/Resources, pure Definitions and executable Work. Retain module-root historical upgrade identities. |
| `src/runa/Providers`, `Configuration`, `Identity`, `Logging`, `Mail`, `Runtime`, `Foundation`, `Http` | Platform composition and internal infrastructure, except explicitly documented public adapters/controllers.                                                                                         |
| `src/runa/Database`, `src/runa/database`                                                              | PHP database infrastructure versus migration/seed/deploy resources. These are distinct responsibilities; preserve persisted migration identities.                                                   |
| `src/runa/resources/js/runa`                                                                          | Public frontend facade/type/style entrypoints; compose their exports from application-owned code.                                                                                                   |
| `src/runa/resources/js`, `src/runa/modules/auth/frontend`                                             | Private supplied presentation and session/account orchestration. Module-owned public types are reexported through facades.                                                                          |
| `src/runa/Tooling/Management`                                                                         | Delivered isolated Artisan tooling/registry/schema. Retains its dedicated loader and Runa\Management identity; separate from module business management.                                            |
| `src/runa/Development/Modules`                                                                        | Development generator and its stubs. Generated code follows the same public APIs and application/test/doc ownership as handwritten code.                                                            |
| `src/runa/Development`                                                                                | Developer-only PHP analysis support and fixed source-check adapter, used through runa:analyse.                                                                                                      |
| `src/runa/Testing`, `src/runa/testing/backend`                                                        | Delivered reusable development test support, internal runner/database adapters and the worker transport; not private authored cases.                                                                |
| `src/runa/frontend`, `src/runa/build/errors`                                                          | Delivered Vite module discovery/import policy and error build executables. They are build/development inputs, excluded from production PHP runtime.                                                 |
| `src/runa/resources`, `config`, `routes`, `lang`, manifests                                           | Owned runtime/build resources and source declarations; application overrides use documented application-owned locations. Preserve mail bridges and imported provenance/licenses.                    |
| `tools/maintainer/frontend`, `tools/maintainer/assets`, `tests/*/runa`                                | Private owner configurations, branding renderers and authored platform suites/fixtures. Excluded from customer delivery.                                                                            |

The supported PHP helpers keep their exact `Runa\Support` identities:

- `Modules\Api\{ModuleApis,GuardedApi,OperationRejected}` for guarded API use.
- `Modules\Data\{ModuleModel,ModuleSchema,PublicReferenceRule}` and
  `Modules\{ModuleIdentity,ModuleRecord,RegistrySnapshot}` for documented data/value roles.
- `Modules\Storage\{ModuleStorage,ModuleDisk}`, `Modules\Events\ModuleEvents`,
  `Modules\Frontend\ModuleFrontend` and `Modules\Settings\ModuleSettings` for their
  documented scoped operations. Not every public method is an ordinary application action.
- `Modules\Work\{ModuleWork,WorkContext,WorkStopped}` and
  `Modules\Development\ModuleContractSuite` for classified work and diagnostics.
- `Seeding\{ManagedSeeder,SeedCategory,SeedContext,SeedGraph,DevelopmentContributor,DevelopmentFixtures}`,
  `Logging\LogContext` and `VersionCompatibility` under their linked public procedures.

Module-owned API/value/exception identities stay in their owning Contracts paths;
use the [Auth](../modules/auth/README.md), [Audit](../modules/audit/README.md) and
[Permissions](../modules/permissions/README.md) guides for exact signatures and
preconditions. `Runa\Http\Controllers\WelcomePageController` remains the supplied
application-route entry. `Tests\TestCase` and the documented browser bridges are
development support, independently of the platform runtime namespace.

| Frontend entrypoint                                                         | Supported consumer                                                                                                                                           |
| --------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `@/runa/ui` and documented UI type/style subpaths                           | Components, layouts, forms, navigation, preferences, tables/exports and typed props/slots. See [component contracts](../development/frontend/components.md). |
| `@/runa/auth`                                                               | Supplied Auth views, presentation provider, named controller/props/slots/events types and headless session/entry/account/welcome orchestration.              |
| `@/runa/http`                                                               | The shared HTTP client and its public errors/types.                                                                                                          |
| `@/runa/modules`, `@/runa/modules/presentation`                             | Module page/slot/registry consumers and public presentation types.                                                                                           |
| `@runa/testing/vue`, `@runa/testing/vitest`, `@runa/testing/playwright`     | Delivered developer test bridges, resolved from one installed toolchain.                                                                                     |
| `src/runa/Testing/browserProblems.ts`, `frontend-runtime.mjs`               | Retained documented browser observation and frontend configuration imports.                                                                                  |
| `src/runa/frontend/module-components.mjs`, `virtual:runa-module-components` | Build plugin and opt-in manifest-bound component discovery.                                                                                                  |

An ordinary Laravel service can consume a guarded Core API without becoming a module:

```php
use Runa\Modules\Auth\Contracts\AuthIdentities;
use Runa\Support\Modules\Api\ModuleApis;

$identities = app(ModuleApis::class)->resolve('runa.auth', AuthIdentities::class, '1.0.0');
$identity = $identities->identity($publicId);
```

Handle availability/operation refusal and enforce the use case's authorization and
record policies. Use an application-owned Form Request/Action/JsonResource for a
substantial write; resolving an API never grants permission for private persistence.

A customer screen composes public controls and retains shared HTTP behavior:

```vue
<script setup lang="ts">
import { Button } from '@/runa/ui';
import { request } from '@/runa/http';

async function refresh() {
  await request('/api/customer/documents');
}
</script>

<template>
  <Button @click="refresh">Odśwież</Button>
</template>
```

This illustrates import ownership; the application owns the endpoint and loading/
error states. See [frontend adoption](../development/frontend/customization.md) for
complete headless/replacement examples, cancellation and session lifecycle.

## Authoring and maintaining the boundary

Before adding or relocating a capability, record its owner, intended consumers,
supported entrypoint, complete public signature, implementation home and runtime/
development mode. Keep public APIs distinct from internal engines and trusted
integration seams. Avoid generic Support/Utils areas as a substitute for deciding
responsibility.

Generators, templates and recommended examples must use the same supported
customer APIs as handwritten application code. Reusable test helpers remain
separate from private suites; follow the [testing guide](../testing/strategy.md).

Synchronize the owning public guide, application examples and relevant owner/
customer instruction products with implementation. Boundary checks should cover
PHP and TS/Vue imports, public signatures and generated consumers, with explicit
named callers for trusted integration. They prevent accidental coupling; they do
not sandbox trusted PHP or replace runtime authorization.

Preserve supported namespace/import identities and observable behavior during
internal reorganization. A breaking public/module change requires the project's
explicit versioning decision; a directory move does not approve a MAJOR release.

## Local enforcement and delivery

`runa:analyse` first inspects application PHP (including ordinary Laravel code and
application tests), platform references and public native/PHPDoc signatures without
booting customer classes. It resolves PHP namespaces, aliases, grouped imports,
FQCNs and native union/intersection types through the installed analysis parser.
Module source diagnostics use a dependency-free lexical companion and the same
source-owned symbol policy. Runa module Contracts are classified individually;
reserved contracts and internal Core collaborators admit only named source callers.
Application-owned module Contracts retain their own declaration convention; this
policy does not classify customer APIs or require ordinary applications to adopt modules.

`runa:lint` checks application TS/Vue static imports, reexports, literal dynamic
imports and type imports against the documented frontend/test entrypoints. Relative
paths and broad aliases resolve to the same boundary. The existing TypeScript check
validates exported consumer types and application-owned replacements. Dynamic class
names, computed imports, runtime object members and semantic compatibility still
need review and behavioral tests. Checks prevent accidental coupling, not trusted
code execution; a diagnostic path never confers runtime authority.

Keep public value/extension signatures free of internal types. The five adapters
ModuleApis, ModuleFrontend, ModuleEvents, ModuleSettings and ModuleWork retain container-owned
constructor collaborators; this does not make those engines customer extension APIs.
Their supported operation signatures remain checked. PHPDoc alias/FQCN checks cover
callback/generic/exception references; they are not a complete PHPDoc type theorem.

Customer development receives these checks, generators and public test support.
The production PHP image uses a pruned runtime projection: no generator/test
adapters, development contributor implementations/contracts or raw module frontend
sources. Compiled assets, module manifests, historical upgrades, runtime diagnostics
and persisted ModuleJob identity remain. `runa:modules:check <owner> [--runtime]`
also works in production for trusted operator diagnostics, using production source
rules without development packages. SMTP verification remains an operator command.
Acquisition receipts and customer source/configuration/test ownership are unchanged;
updates do not move or overwrite customer-authored extensions implicitly.

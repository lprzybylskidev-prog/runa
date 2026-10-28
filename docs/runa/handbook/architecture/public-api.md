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

The current source has mixed locations. Existing documented public helper paths
remain valid until an explicit compatible migration is delivered. Do not infer
that all `Support` classes are internal or that all module `Contracts` are public.
Internal reorganization preserves these public file/FQCN identities and supported
frontend/test/build entrypoints. This includes public helpers used by the supplied
application scaffold, generated consumers and application test/build configuration.
A cleaner directory grouping alone does not authorize changing their import paths.
New internal homes become current guidance only when their owning implementation
is delivered.

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

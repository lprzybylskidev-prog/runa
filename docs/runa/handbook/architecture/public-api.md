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

## Frontend composition and replacement

Use public `@/runa/ui`, `@/runa/auth`, `@/runa/http`, `@/runa/modules` and their
documented public subpaths/types. A broad alias resolving an implementation file
does not make that file a supported customer import.

Customers may retain supplied defaults, compose application-owned screens from
public components/layouts, or replace presentation through supported headless
contracts. Follow [frontend customization](../development/frontend/customization.md)
for presentation providers, props/slots, Auth controllers, keyed account/page
lifetimes and static error layouts. Preserve session/CSRF orchestration,
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

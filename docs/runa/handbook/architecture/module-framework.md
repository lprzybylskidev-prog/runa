# Module framework contracts

## Delivery status and scope

This is the schema-version-1 design baseline for the module framework. Registry persistence, discovery, lifecycle adapters and generators are not yet delivered. Do not use the examples as working commands or register production modules until those capabilities are implemented. Ordinary application PHP/Vue and independent frontends remain supported.

Modules share the existing Laravel application and container. They are trusted local code, not sandboxed plugins or independently downloaded Composer packages. The framework enforces declared boundaries; static checks and PostgreSQL schema conventions cannot sandbox deliberately malicious code.

## Identity and source layout

The public module ID is `<owner>.<code>`, where owner is exactly `runa` or `app`. Code matches `[a-z][a-z0-9]*(?:_[a-z][a-z0-9]*)*` and is at most 58 ASCII bytes, leaving room for PostgreSQL's 63-byte identifier limit. Codes are unique within an owner; `runa.filesystem` and `app.filesystem` may coexist. The ID is a contract key; management records use bigint identity and UUIDv7 public identifiers where externally exposed.

| Item                      | Runa ownership               | Application ownership       |
| ------------------------- | ---------------------------- | --------------------------- |
| Module root               | `src/runa/modules/<code>/`   | `src/app/modules/<code>/`   |
| Domain schema             | `runa_<code>`                | `app_<code>`                |
| Private/public disk owner | `runa`                       | `application`               |
| Disk-relative prefix      | `modules/runa/<code>/`       | `modules/app/<code>/`       |
| PHP prefix                | `Runa\Modules\<PascalCode>\` | `App\Modules\<PascalCode>\` |

Each root contains `module.json`, `backend/`, `resources/`, `routes/`, `database/`, `config/`, `lang/`, `tests/` and `docs/` as needed. Public PHP interfaces/DTOs/events live in `backend/Contracts/`; Actions, models, jobs, providers and other implementation classes live under `backend/`. Frontend code lives in `resources/js/`; migrations and seeders in `database/`. Writable files never live in module source.

Derive PascalCode by capitalizing and joining code segments. Reject case-insensitive PHP prefix collisions, including collisions with ordinary application/platform classes, rather than silently renaming a prefix. A generated local autoload map must explicitly map these prefixes to each `backend/` directory: the current broad `App\`/`Runa\` mappings do not handle the lowercase module roots. No per-module dependency or Composer autoload edit is required from application developers. Loading this map is a later discovery implementation responsibility.

Source roots, manifest files and referenced files must remain inside the owning real directory without symlink traversal. Reject reserved codes `platform`, `application`, `shared`, `public`, `system`, `reference` and `module_probe`; the last two are reserved for controlled development fixtures. A module's owner, code and kind cannot be changed in place to reinterpret retained data.

## Manifest schema version 1

`module.json` is a bounded UTF-8 JSON object, at most 64 KiB and depth 16. Reject duplicate JSON keys, unknown fields, unknown schema versions and invalid values before operational providers load. Empty collections are explicit. There are no database-controlled PHP class names, file paths or executable expressions.

| Required field   | Type and meaning                                                                                        |
| ---------------- | ------------------------------------------------------------------------------------------------------- |
| `schema_version` | Integer literal `1`                                                                                     |
| `id`             | Validated owner/code ID matching the source root                                                        |
| `kind`           | `core` or `optional`; source-owned, never editable in DB                                                |
| `version`        | Bounded stable `X.Y.Z` module version                                                                   |
| `platform`       | Supported platform version constraint                                                                   |
| `framework_api`  | Supported module-framework public contract version constraint                                           |
| `name`           | Object containing exactly nonempty `pl` and `en` strings                                                |
| `description`    | Object containing exactly nonempty `pl` and `en` strings                                                |
| `dependencies`   | Object containing exactly `required` and `optional` maps from full module IDs to version constraints    |
| `providers`      | List of unique owner-local provider class names                                                         |
| `declarations`   | Map of declaration categories to lists of unique owner-local descriptor class names, as specified below |
| `docs`           | List of unique module-relative Markdown paths within `docs/`                                            |
| `tests`          | List of unique module-relative directories within `tests/`                                              |

Localized names are at most 160 characters, descriptions at most 2000. Collections are bounded to 256 items per category; class names and relative paths to 256 characters. Required/optional dependency maps must be disjoint, cannot reference self, and Runa modules cannot depend on application modules through either map. A required cycle is invalid; optional integration references never become enablement edges.

Version components are decimal integers without leading zeros, at most nine digits each. Version constraints reuse `Runa\Support\VersionCompatibility`: exact or `=`, `>`, `>=`, `<`, `<=`, `^`, `~` against complete stable triples; whitespace is AND and `||` is OR, maximum 256 characters. Wildcards, partial versions, prereleases and build metadata are unsupported in v1 compatibility evaluation and fail explicitly. Do not use the dev-only transitive Composer SemVer package at runtime. Module versions and framework API versions are independent of platform VERSION; the initial framework API contract is `1.0.0` when delivered.

Every category below is required in `declarations`; an unused category is `[]`, except that `lifecycle` always has exactly one descriptor. Its descriptor classes return typed immutable source definitions, not free-form executable manifest payloads. Source descriptors use category-specific interfaces; unsupported descriptor revisions fail validation. The declaration envelope and boundaries are frozen here; each feature point supplies its detailed PHP types and executable validation before it can be used.

| Category       | Source declaration contract                                                                                                |
| -------------- | -------------------------------------------------------------------------------------------------------------------------- |
| `apis`         | Public interface, contract version, owned implementation binding and operation IDs; consumers receive guarded gateways     |
| `health`       | Module-owned check definitions, severity, default mode, timing, operation applicability and allowed operational overrides  |
| `lifecycle`    | Exactly one module-owned transition/work policy descriptor, including explicit stateless/no-work behavior where applicable |
| `settings`     | Typed/localized definitions, defaults, backend validation, sensitivity and application handler                             |
| `integrations` | Target module/public contract, own toggle/default/config and required operations                                           |
| `http`         | Owned route source, stable operation IDs and diagnostic/operational classification                                         |
| `commands`     | Command class, stable operation ID and diagnostic/operational classification                                               |
| `jobs`         | Job class, stable operation ID and module work-policy reference                                                            |
| `schedules`    | Stable task ID, owned target operation and schedule definition                                                             |
| `events`       | Versioned public event definitions and owned listener/subscriber definitions                                               |
| `frontend`     | Build-time page/component/navigation keys and named route bindings                                                         |
| `slots`        | Host/contribution IDs, props contract version, default priority and local build-time component keys                        |
| `management`   | Declarative actions, permission identifiers and optional safe custom-component keys                                        |
| `upgrades`     | Stable upgrade step IDs, from/to versions, prerequisites, transactional/idempotency and recovery declarations              |

Descriptor identity is `(module_id, category, key)` with nonempty English snake_case keys. Each descriptor exposes integer `schema_version: 1`. Contract versions and required version ranges are separate from descriptor schema revisions. References to other descriptors use these stable keys, never DB-supplied class strings. Class reflection validates namespace, actual source ownership and expected interface before instantiation. Descriptor construction/provider registration must not mutate data, start work or perform external I/O. Providers bind implementation collaborators; adapters register declared entrypoints. Unclassified module entrypoints are contract violations.

Example identity manifest for a future application module, not a shipped module:

```json
{
  "schema_version": 1,
  "id": "app.documents",
  "kind": "optional",
  "version": "0.1.0",
  "platform": "^0.4.0",
  "framework_api": "^1.0.0",
  "name": { "pl": "Dokumenty", "en": "Documents" },
  "description": { "pl": "Obsługa dokumentów", "en": "Document processing" },
  "dependencies": { "required": {}, "optional": {} },
  "providers": ["App\\Modules\\Documents\\Providers\\DocumentsServiceProvider"],
  "declarations": {
    "apis": [],
    "health": [],
    "lifecycle": ["App\\Modules\\Documents\\Definitions\\DocumentWorkPolicy"],
    "settings": [],
    "integrations": [],
    "http": [],
    "commands": [],
    "jobs": [],
    "schedules": [],
    "events": [],
    "frontend": [],
    "slots": [],
    "management": [],
    "upgrades": []
  },
  "docs": ["docs/README.md"],
  "tests": ["tests"]
}
```

## State and dependency contracts

The registry snapshot separates `registered`, `source_present`, administrative `enabled`, `source_version`, `installed_version`, own health and derived availability. Snapshot `generation` changes invalidate cached effective state; persisted settings/health-policy revisions additionally identify their own changes. A module without successfully installed compatible data cannot operate. Discovery never sets installed version merely because source exists and never runs during ordinary request boot.

Own health is `healthy`, `degraded`, `unhealthy` or `unknown`. Effective availability is `available` or `blocked` with independent structured reasons: disabled, missing source, incompatible contract/data, transition/recovery fence, own critical check, required dependency or platform maintenance. Warning observations may degrade an available module. Root causes contain safe codes, module/check IDs, timestamps and dependency paths; no raw exception, secret or business payload.

New Optional registrations are disabled; Core registrations are mandatory. No future Core is registered or required merely because its name is reserved in platform planning. Empty production registry is valid. Missing previously registered Core remains a maintenance reason. Recovery never changes enabled flags; enabling a dependency never restores cascade-disabled dependents.

Required dependencies form the activation/availability graph. Optional dependencies only constrain an explicitly enabled integration's usable contract; absence or failure cannot disable the independent module. Required and optional references must still respect ownership direction and version grammar. A future Admin-shaped fixture requires Audit, while unrelated fixtures may configure optional Audit integration. No real Auth, Permissions, Audit or Admin implementation is included.

## Module-owned health policy

Each module supplies its check handlers, critical/warning classification, `on_use`/`scheduled`/`hybrid` defaults, intervals, timeouts, expiry and applicability to public operations. Its definition specifies which operational values administrators may change in DB and their validation bounds. The base has no global business health cadence or inferred policy. Modules without special checks are healthy when source, installed data and required conditions are satisfied.

The framework implements shared meanings: expired critical observations become unknown/blocking; warning failures/staleness remain diagnostic. `on_use` runs applicable checks on each public operation; `hybrid` also schedules them. Fresh cached results cannot replace required per-use checks. Single-flight deduplication can join genuinely overlapping checks but cannot silently skip a later operation's check. A guarded nested call is another public operation under the callee's policy. Pure internal helper calls do not create extra public-operation boundaries. Use controllable clocks and bounded execution, without claiming a generic timeout can safely terminate arbitrary PHP/external effects.

Scheduled diagnostics and explicit recovery checks can run while business work is blocked. Required dependency failures propagate effective blocking and safe root-cause paths. Recovery recalculates availability, not administrative state. Infrastructure protection may reject invalid/unbounded configuration; it must not substitute a global policy for module-owned rules.

## Module-owned transition and work policy

The module supplies the decision mechanism for preparation, pending work, running work, checkpoints, cancellation and compensating preparation. The base never guesses a workflow from job names or selects a business policy from a fixed global catalogue. Even a stateless module declares its no-work behavior explicitly.

The shared protocol transports outcomes rather than business decisions:

| Contract                 | Inputs                                                                                                | Output/guarantees                                                                            |
| ------------------------ | ----------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------- |
| Transition preview       | Intent, affected graph, registry generation, module policy/settings revisions and active-work summary | Safe reasons, blockers, expected work behavior and policy revision; no side effects          |
| Prepare                  | Operation ID, same validated context, module-owned plan and transition fence                          | `ready`, `waiting` or `refused`, safe reason codes and opaque module-owned preparation token |
| Preparation status       | Operation/token and refreshed work context                                                            | Current readiness/progress; no claim that flags changed                                      |
| Compensate preparation   | Same operation/token                                                                                  | Confirmed compensation or explicit recovery-required outcome                                 |
| Work decision/checkpoint | Operation/work IDs, generation, cancellation intent and module-owned context                          | Module-owned handling decision and safe progress, enforced through the work adapter          |

`ready`, `waiting` and `refused` describe readiness only. They do not enumerate how a module drains, pauses, cancels, completes or reconciles its work. Module-specific plans/tokens stay opaque to the base and are versioned/validated by the module; diagnostics expose only sanitized summaries. A refused module can block the whole requested cascade. A waiting transition reports pending status separately from success and preserves all prior enabled flags.

The framework validates preview revisions, serializes competing transitions, establishes an affected-module ingress fence, invokes each module policy and atomically publishes all enabled flags plus generation only after every required preparation is ready. It uses short DB transactions around durable reservation/publication, never a transaction held across slow module hooks. A restart/retry binds to the same operation and revalidates preparation evidence. Stale previews require a fresh preview.

Before publication, refusal/failure compensates prepared participants and leaves every enabled flag unchanged. Uncertain compensation leaves an explicit recovery fence. After publication, a new runtime failure changes health/availability rather than pretending the administrative transaction rolled back. Hooks must not introduce irreversible business effects. Required data migrations run during installation/update, never as enable/disable hooks.

Ingress fences and generation checks prevent new work from passing a stale cached gateway or starting from a stale queue dispatch. The module decides what already-running work may complete and where it can stop safely. The base records leases/progress and carries cancellation intent; it never kills work midtransaction or interprets a missing lease as proof that external work was reversed. Pending-work treatment and retry bounds are module-provided. Missing/refused/invalid policy never authorizes new work. Diagnostic recovery remains possible. Core cannot be disabled, including through a forged DB flag or cascade.

## Public boundary and management envelopes

Public PHP contracts use immutable DTOs/interfaces under the owning `Contracts` namespace. A declared interface is resolved through a per-operation availability-aware gateway; consumers never receive an unchecked implementation to retain. Operation identity is `(module_id, operation_key)`, shared by HTTP/CLI/job/listener adapters so policy checks do not depend on transport. Ordinary application code may consume this gateway without becoming a module.

| Envelope                 | Required information                                                                                                                                                 |
| ------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Module status            | Schema version, module ID, public management ID, generation, source/installed versions, registration/enabled/source state, own health, availability and safe reasons |
| Lifecycle preview        | Schema version, intent, affected IDs in deterministic order, expected generation/policy revisions, module decisions, active work and blockers                        |
| Transition result        | Operation ID, `pending`/`succeeded`/`refused`/`failed`/`recovery_required`, publication generation if successful, safe per-module status                             |
| Public operation context | Origin/target module, stable operation/correlation ID, generation and optional actor/system context; no invented Auth user                                           |
| Availability rejection   | Stable reason code, target module/operation, safe root causes and retry information where known                                                                      |
| Active work              | Public work ID, owning module/operation, lease observation, module-provided progress/cancellation capability and safe limitations                                    |

Detailed transport DTOs are implemented with their corresponding adapters. They must preserve these meanings and never expose internal relational IDs, raw tokens or sensitive policy context. Availability is not authorization. Management writes stay trusted CLI-only until real Auth/Permissions can authorize them; HTTP diagnostics are restricted, sanitized and read-only. Module HTTP uses `/api/<owner>/<code>/...` and route names `<owner>.<code>.<operation>`; business resource paths follow normal plural/kebab-case conventions.

Public events carry contract version, module ID, stable event/correlation IDs and safe payload; committed-fact delivery happens after commit. Independent subscriber failure never retries the originating action. Critical coordinated operations use explicit calls and documented consistency guarantees. Foreign-module UUID validation through an API does not prevent a later deletion race; owner-side commands/version tokens/reservations are required when the domain needs stronger guarantees. No cross-module FKs, models/relations, SQL or storage traversal.

## Existing extension mechanisms and implementation boundaries

| Existing mechanism                                                        | Framework integration                                                                                                            |
| ------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| `RunaServiceProvider`, `AppServiceProvider`, application provider list    | Register validated definitions and guarded bindings in the same Laravel container; no request-time discovery mutation            |
| `DatabaseScope`, `IdentityMigrationRepository`                            | Extend explicit owner-scoped migration inventories and paths; preserve isolated test database and runtime/migration roles        |
| Application/platform private/public disks                                 | Add driver-independent scoped adapters under the correct durable owner; preserve data on disable/unregister                      |
| `VersionCompatibility` and external updater plan/journal                  | Reuse the stable constraint grammar, operation lock, maintenance ownership and existing journal; add module-aware planning/steps |
| `NavigationEntry`, `RouteContext`, `PageDescriptor`, `createPageResolver` | Supply module metadata and current availability without changing the public UI facade or requiring the bundled shell             |
| Layout slots                                                              | Keep ordinary Vue layout slots; implement namespaced cross-module host/contribution discovery as a new adapter                   |
| Scheduler pulse, queue heartbeats, request correlation                    | Preserve technical process diagnostics; module health/work tracking is a separate concept                                        |

The delivered updater currently has fixed migration/seeder phases, not a generic module contribution registry. Extend those phases and their existing journal in the update point; do not assume a callable hook API already exists or create a second operation state store. Migrate all Runa-supplied modules including disabled ones. App-module discovery/upgrade contracts are reusable but explicitly invoked by the customer installation/deployment workflow, never silently installed by the platform updater.

Database schema prefixes and prohibition of cross-module FKs here are target module rules. The existing database guide still describes delivered ordinary application behavior; synchronize its schema/ownership sections when module schema enforcement is implemented. Retained data and upgrade provenance survive missing source and unregister; incompatible restoration blocks instead of resetting data.

## Development fixtures and guide outline

Use three distinct forms of development evidence:

1. A temporary `app.module_probe` during framework construction, with explicit development-only discovery/build inclusion, for observing each implemented boundary. Start when discovery exists; grow it incrementally. Remove its source, build/discovery entries and identified disposable data/resources before final verification. Never deliver it to customer production. Preserve useful behavior assertions in isolated tests.
2. Isolated test graph fixtures for Core/Optional, version/ownership failures, required/optional Audit shapes, policy refusal/waiting/compensation, health expiry and worker races. They are not production Core registrations and may remain in test sources.
3. A separate evolvable Optional reference module delivered later as a development aid. Keep it after roadmap completion, excluded from production source/images/discovery. Removing the temporary probe must not remove this reference.

Complete the developer guide incrementally in this document or linked focused guides: anatomy/manifest; registration and retained data; graph/lifecycle; own health; module work policy; PostgreSQL/storage; public PHP/HTTP APIs; jobs/commands/schedules; events/integrations; settings; Admin descriptors; frontend/slots; versioned upgrades; generators/contracts; troubleshooting. Examples should consume actual delivered contracts and must not imply real Auth/Permissions/Audit/Admin are present.

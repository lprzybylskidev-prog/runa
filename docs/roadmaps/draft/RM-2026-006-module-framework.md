---
id: RM-2026-006
title: Module framework, lifecycle and extension contracts
status: draft
branch: roadmap/RM-2026-006-module-framework
created_at: 2026-10-18
activated_at: null
closed_at: null
release_type: feature
impact_areas:
  - backend
  - frontend
  - database
  - development
  - production
  - tooling
  - security
  - documentation
---

# RM-2026-006 — Module framework, lifecycle and extension contracts

## Overview

### Goal, order and release

Deliver the modular infrastructure and developer contracts through which future Runa and business modules can register, communicate, operate safely and update. Keep ordinary application code and independent customer frontends fully supported.

Planning-only draft. Requires [RM-2026-005](RM-2026-005-platform-installation-updates.md) closed, merged and released; that roadmap itself requires [RM-2026-004](RM-2026-004-ui-foundations.md). Do not activate concurrently or begin a consumer before its predecessor. Update links after predecessor lifecycle moves.

Planned release **0.4.0**, following planned 0.3.0. `release_type: feature` reflects new framework capability and follows the existing pre-1.0 minor-release rule. Independent module versions do not determine platform VERSION. No major bump or readiness claim is authorized. Recompute the projected version if actual main has intervening releases.

### Scope and exclusions

Include manifests/registration/registry, dependency graph, administrative lifecycle, technical health, effective maintenance, centralized guards, PostgreSQL/storage isolation, public APIs/events, settings, declarative Admin contracts, frontend navigation/slots, commands/schedules, structured logs, module-aware updates, generators, contract checks and a development-only reference module.

Do not implement or mock production Core modules. Future order is `runa.auth` → `runa.permissions` → `runa.audit` → `runa.admin`, each with its own future roadmap. Do not ship placeholder Core registrations requiring nonexistent modules. Optional Logs/Filesystem/Integrations modules, actual Auth/Permissions/Audit/Admin behavior, business screens, license infrastructure, destructive uninstall and arbitrary application installers are excluded. Test fixtures may simulate Core behavior in isolation only.

### Audited sources and dependencies

Audit baseline: `main` `725f75b`, VERSION 0.1.2, no active roadmap, closed RM-001/002/003 and production-ever-deployed false. Knowledge was current and retrieved significant rules were opened in source. The current source has no module registry/framework; existing technical support is not a set of modules. This roadmap consumes the future released results of RM-004/005, so refresh actual paths and contracts at activation rather than presuming those features already exist.

Read root/applicable nested AGENTS, roadmap/Git/release workflows, relevant backend/Laravel/PostgreSQL/security/Docker/frontend/Vue/Tailwind skills, `docs/architecture/{application-architecture,database,api,configuration,frontend,deferred-module-concerns,language-and-naming}.md`, `docs/data/seeding.md`, `docs/security/baseline.md`, production deployment/backup/architecture docs, quality gates, testing docs and RM-004/005 delivered contracts.

| Existing source/mechanism                                                         | Reuse and extension                                                                                                                                          |
| --------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `app/Providers/AppServiceProvider.php`, bootstrap routing/commands/schedule       | RM-005 relocates these; extend provider-driven Laravel registration, not a second container/framework. Preserve technical scheduler pulse.                   |
| `app/Support/Seeding/SeedGraph.php`, category/context/managed seeders             | Existing deterministic graph/category behavior can inform/reuse bounded ordering; dependency enablement is a separate graph, not a repurposed seeding state. |
| `IdentityMigrationRepository`, guarded database commands, queue migrations        | Preserve shared technical schema, bigint identity and isolated test DB guarantees. Add module schemas/migration sources explicitly.                          |
| `RuntimeHeartbeat`, health/readiness controllers                                  | Heartbeats describe process health, not per-module health. Reuse diagnostics/time abstractions but keep concepts distinct.                                   |
| `config/logging.php`, `AssignRequestId`                                           | Current logging is stderr with request correlation; add bounded structured origin channels/files, not an imagined existing logs module.                      |
| `config/filesystems.php`, Laravel Storage                                         | Add scoped module storage adapters supporting multiple drivers; no direct cross-module path traversal.                                                       |
| RM-004 UI facade, route/navigation descriptors, preference adapter and slots      | Supply module metadata to existing interfaces. Framework cannot require customers to render the bundled shell.                                               |
| RM-005 ownership map, manifest compatibility evaluator, update plan/journal hooks | Extend same installer/updater/Artisan surface; do not build a module package installer or a second update state store.                                       |
| `docs/architecture/deferred-module-concerns.md`                                   | Keep Auth/Audit/Files/Permissions business policies deferred; remove only entries actually resolved by framework implementation.                             |

## Specification

### Decisions

- 2026-10-18 — Ownership IDs are `runa.<code>` and `app.<code>`, with stable validated English snake_case short codes, unique namespaced IDs and collision-safe schema/storage derivation. Runa-owned modules cannot depend on app-owned modules. Application modules may depend on either. Customers may also use ordinary application code.
- 2026-10-18 — Module source is self-contained: backend, public contracts, frontend, routes, models, migrations, seeders, translations, config, tests, docs, commands/jobs/schedules. Runa modules live under RM-005's protected `platform/modules/<code>/`; application modules use `modules/<code>/` in the customer-owned application root. Runa-supplied code ships with platform releases, not separately installed packages.
- 2026-10-18 — Registration, administrative enabled state, source presence, own health, derived effective availability and installed data version are distinct fields/concepts. Technical recovery never changes enabled flags.
- 2026-10-18 — Persist operational metadata in `shared` technical tables; each module's domain data owns its own schema. No cross-module foreign keys, direct models/relations/SQL or file traversal. Same-module FKs preserve `ON DELETE RESTRICT ON UPDATE RESTRICT`.
- 2026-10-18 — Centralize enforcement in framework adapters, with fail-closed critical health. Do not claim PHP/SQL static analysis is a sandbox preventing deliberately malicious trusted code. Review contracts and runtime/static checks jointly.
- 2026-10-18 — Management is CLI-first pending real Auth/Permissions. Do not expose unauthenticated module-management HTTP endpoints. Define DTO/action contracts now; any diagnostic HTTP output is sanitized and read-only. Future Admin/Auth can bind authorization at the trusted boundary without fake authorization implementations.
- 2026-10-18 — Reuse framework cryptography and Laravel storage/queue/event/mail mechanisms; verify exact APIs with Boost version-aware docs before implementation. No custom cryptography or new dependency is implicitly approved.

### Manifest, discovery and registry

Manifest schema includes schema version, module ID/code, Core/Optional kind, SemVer version, supported platform/API contract ranges, name and PL/EN descriptions, required/optional versioned dependencies, providers/public contracts, settings, healthchecks, integrations, route/CLI/schedule/event/frontend contributions and docs/test locations. Class/component references resolve only to local owned code via allowlisted registries, never database-supplied arbitrary class names/paths. Validate duplicate IDs/codes, owner/path mismatch, reserved names and unknown declaration versions before loading operational providers.

Discover trusted source definitions deterministically during standard installation/update; synchronize registrations under a lock/transaction. Preserve existing settings/enabled state; new Optional defaults disabled, Core is mandatory. Registering does not activate Optional. Track source version separately from successfully migrated installed version. Normal request boot consumes a registry snapshot and validated source metadata; do not mutate registration or migrate per request. Cache invalidation uses a generation/version so workers and requests observe lifecycle changes consistently.

Suggested technical records: module registry (ID/kind/source presence/enabled/source and installed versions), validated settings and revisions, integration toggles, cached health observations, slot-order overrides and update step records linked to the existing operation journal. Define bigint identity and necessary constraints; public durable management identifiers use UUIDv7. Stable module string IDs are public contract keys, not replacement internal relational PKs. Avoid extra tables until access patterns justify them. No registry FK to a module domain schema.

### Dependencies and administrative transitions

Resolve required dependencies recursively with SemVer compatibility; optional declarations neither auto-enable nor block ordinary module availability. Report full cycle paths and reject invalid affected graphs without disabling independent healthy modules. Optional integration cycles do not become activation recursion; validate actual required graph and integration references explicitly.

Enable preview shows all required dependencies, version/health checks, changes and blockers. Enable executes the entire administrative cascade atomically or changes nothing. Disable preview shows enabled required dependents recursively; execute flags atomically, preserving data/config. Refuse Core disable and any cascade that would disable Core. Re-enabling a dependency does not restore previously cascade-disabled dependents; maintain no historical auto-reactivation list. Validate concurrent graph/registry revisions at apply time; stale previews cannot authorize different cascades.

External reinitialization/quiescing cannot be rolled back by a database transaction. Preflight before flags change; publish a transition fence to reject new work, commit atomic administrative flags with a new generation, then finalize idempotent effects. On effect failure keep affected work blocked with explicit transition/error state and recovery, never half-active chains. Document this distinction and test concurrent commands/requests/workers.

### Health and effective maintenance

Represent own health as healthy/degraded/unhealthy/unknown, with separate disabled/missing source state and effective availability. Each declared check has critical/warning severity, timeout, interval and expiry. Modes are `on_use`, `scheduled`, `hybrid`, configurable per module. Cache/persist timestamps/results/root cause with bounded content, deadlines, single-flight checks and no sensitive diagnostic payloads. No special checks means healthy when source/dependencies/mandatory conditions are satisfied.

A failed/expired critical observation blocks operations; expired critical means unknown. Warning failure degrades and stale warning remains diagnostic without blocking. Required dependencies propagate effective blocking and root-cause paths; optional absent/unhealthy modules do not block unrelated work. Avoid repeated expensive recursion, cycles and stale snapshots; invalidate dependent effective caches when graph/check generation changes. `on_use` can recheck within deadline; scheduled/hybrid checks recover unavailable modules even without business traffic. Diagnostic health jobs are exempt from normal business gates so recovery is possible.

When required conditions recover, availability recovers automatically; flags stay unchanged. Missing/unhealthy/critically unknown Core causes effective platform maintenance. Compose maintenance reasons from manual Laravel maintenance, update maintenance, module/Core and runtime requirements; clearing one reason never clears another. Continue liveness, restricted sanitized diagnostics and health recovery while ordinary HTTP/jobs/schedules/operational commands fail closed. Keep manual maintenance artifacts untouched. Prepare future Auth/Permissions critical contracts without insecure fail-open fallback or dummy users.

Administratively disabled Optional UI is hidden; enabled but unavailable Optional UI can show disabled diagnostic entries, with no actionable operations. Direct APIs remain guarded regardless of visibility.

### Missing source and unregister

Discovery marks disappeared registered code missing, retaining config/data/files/versions. Required dependents block; missing Core triggers maintenance. Restoring compatible source rechecks schema/installed version/dependencies and recovers according to previous enabled state. Separate explicit unregister previews dependency effects, refuses mandatory Core/removal with unresolved required dependents, and removes appropriate registry references without deleting schema/tables/business files. Preserve installed upgrade provenance needed to recognize retained data. Restored code after unregister is a new registration with preserved-data compatibility checks; incompatible data blocks, never resets. No destructive uninstall command.

### Data, files and APIs

Schemas derive from owner/code (`runa_<code>`, `app_<code>`) with length/collision validation, PostgreSQL identifier limits and one stable mapping. Use schema-qualified tables, restricted runtime role and migration-role discipline; no search_path switching between modules in long-lived workers. Durable domain entities use bigint identity internally and non-null unique UUIDv7 public_id externally, soft deletes/timestamps/UTC and query-driven indexes. Shared technical records stay shared.

References to other modules store public UUIDs without cross-schema FKs. Validate via owning public API; if necessary validation is unavailable, block that operation, not unrelated work. Cross-module concurrency guarantees must be explicit: authoritative owner-side commands/version tokens/reservations where needed; API validation alone is not proof against a race. Do not invent a distributed transaction coordinator or silently promise cross-module atomicity. Critical consistency is a documented coordinated contract; asynchronous independent facts use events.

Storage is exposed as a module-scoped Laravel disk/prefix facade, logically `modules/<owner>/<code>/`, driver independent. Reject traversal/absolute paths/encoded escapes and isolate disks/visibility. Other modules access files through public APIs, never scoped adapter internals. Disable/unregister preserves files; backups include owned namespaces. This is infrastructure, not the future Filesystem business module.

Public in-process interfaces/DTOs/events are versioned, documented and resolved through availability-aware proxies/factories; do not return unchecked raw service objects that continue operating after disable. Internal models/services/tables are private. HTTP module APIs use explicit namespaced URLs and dot-named routes under `/api/...`; typed resources/Form Requests/Actions follow project conventions. Frontend guards are UX only. Business code can consume contracts without becoming a module.

### Central execution enforcement and active work

| Entry point      | Mandatory framework enforcement                                                                                          |
| ---------------- | ------------------------------------------------------------------------------------------------------------------------ |
| HTTP             | Automatically attached module availability middleware and future authorization seam, including direct URLs               |
| In-process API   | Availability-aware contract gateway rechecks effective state at each operation                                           |
| Queued jobs      | Module-owned dispatch metadata plus universal worker middleware/before-execution guard; disabled jobs cannot start       |
| Running work     | Active-work registration/lease, state generation and cooperative cancellation checkpoints at safe consistency boundaries |
| Schedules        | Discovered task registrations plus dispatch-time and execution-time gates                                                |
| Artisan          | Registered diagnostic versus operational classification; operational commands cannot bypass state                        |
| Events/listeners | Listener ownership wrapper and recheck at actual sync/async invocation                                                   |
| Frontend         | Descriptor availability/slot filtering plus server-authoritative API protection                                          |

Disabling rejects new dispatches, prevents pending jobs from starting and signals existing jobs to reach declared safe checkpoints; do not kill a process midtransaction. A bounded policy decides pending work release/defer/cancel without infinite hot retry or losing diagnostic identity. Already-running atomic units may complete safely; report active work and cancellation limitations in preview/management contracts. Expire crashed-worker leases with evidence; prevent disabled work resurrecting on queue retry. Contract/generator checks reject unclassified entrypoints and documented unmanaged bypasses.

### Events, optional integrations and settings

Public events describe committed facts with contract version, originating module, stable event/correlation ID and safe payload. Dispatch after commit when consumers require committed state. Subscriber failures are isolated per independent listener; failed async delivery retries that subscriber with idempotency keys, not the originating business action. Sync independent listeners log/report failure without invalidating committed origin. Explicit critical coordinated operations use a distinct fail-closed contract with consistency/retry rules, not accidental event rollback semantics.

Optional integrations have own typed toggle/default/config metadata. They require both sides effectively available, do not enable either side and do not change either module's enabled flag when toggled. Future Audit is an optional integration hook, never an implicit hard dependency or mock audit implementation.

Settings declare stable key, type/default, backend validation, PL/EN display name/description, sensitive marker and immediate/reinitialize behavior. Include string/integer/decimal/boolean/enum/array and extensible type registry. Operational values persist in DB, infrastructure secrets/config remain environment inputs. Sensitive values use Laravel encryption with externally managed key; reads return redacted/set-state only, writes can replace without reveal. Do not log plaintext, return ciphertext as a pseudo-secret API, or include values in audit hooks.

Updates use revision/optimistic concurrency. Validate candidate settings, snapshot prior working values, apply immediate or fenced reinitialize handler; restore previous values when effects are safely compensatable. If restoration/application is ambiguous keep module blocked with explicit diagnostics, do not falsely claim rollback. Expose future Audit hooks with safe metadata only.

### Declarative Admin and frontend contracts

Management descriptors expose settings/types/localization/validation, enabled/health/root causes, dependencies, integration toggles, active work, permissions declarations and module actions. Admin will implement generic renderers later. Custom administrative Vue components are referenced by safe build-time keys for cases not expressible declaratively, not dynamically evaluated DB code. Define permission/action identifiers and authorization injection seams; do not enforce fictional current permissions or expose write HTTP endpoints before real authorization exists. Future Admin remains English-only despite PL/EN module metadata for user-facing contexts.

Module frontend contributions supply routes/pages/component/navigation metadata through RM-004 interfaces and RM-005 ownership/build mapping. Use deterministic build-time inclusion of local sources and runtime availability projection; code presence never grants permission. Lazy loading may follow measured cost. Direct refresh resolves the route context; unavailable routes do not start data requests. No mandatory bundled frontend for API-only customer applications.

Applications/modules declare named UI slots with owner/namespaced ID and supported props contract. Contributions have stable IDs, default integer priority and deterministic ID tie-break. Optional DB order overrides reference contribution IDs and survive compatible updates; remove stale references without renumbering unrelated choices. Hosts need no edits for contributors. Filter contributions by host/contributor health/enabled and later permission seam, and validate incompatible slot contracts. No hardcoded business screens.

### Commands, scheduling and logging

Discover module Artisan commands/schedules from manifests/providers without global per-module schedule edits. Diagnostic commands can inspect disabled/unhealthy modules under trusted CLI safeguards; operational commands always gate execution. Preserve existing technical scheduler pulse and no production AI commands. Test task registration, overlap/retry behavior and runtime state changes after schedule creation.

Establish structured bounded logging using existing Laravel/Monolog capabilities: UTC timestamp, severity, origin (`platform`, application ID, module ID), request/operation/event correlation, safe context and centralized redaction. Keep container stderr compatibility and a bounded rotating file channel set under storage for future Logs reads, not one file per arbitrary module/user/input. Origin fields distinguish modules within logical channels. Specify retention/rotation/permissions/backup implications and prevent path/log injection. Logs module reads/search UI and Audit records remain excluded.

### Versions and module-aware updates

Module SemVer is independent of platform/application versions. Manifest dependency constraints are enforced before activation/update; incompatibility blocks required dependents and Core may cause effective maintenance. Reuse RM-005 compatibility semantics; include module-version-change guidance/checks in AGENTS, relevant skills and local gates. Public contract changes require corresponding module version/changelog updates; record limits of static detection instead of claiming to infer all semantic breaks.

Extend RM-005's single installation/update plan and journal. Discover/register code, preflight versions/dependencies/infrastructure/module checks, and plan all Runa-supplied module migrations/upgrades **including administratively disabled modules**. Enabled toggles never run migrations. Distinguish schema migrations from explicit data/settings/resource upgrade steps with stable module/step/from/to IDs, ordered prerequisites, completion records and retry/idempotency policy. Apply safe chains across skipped versions; block missing paths/downgrades unless explicitly supported. Discovery does not claim installed data version until required steps succeed.

Stop entire update on first failed module step; preserve completed records and maintenance, do not process unrelated later modules. Revalidate and resume only supported safe steps after remediation. Transaction rollback covers transactional work, not arbitrary files/external effects; document irreversible steps and backup recovery. Dry-run extends the existing nonmutating plan with versions, graph/order, expected migrations/upgrades, preflight results, risks and missing paths; no mutation to discover behavior. Re-enabling/disable never drops data/settings/files.

Platform update manages Runa and supplied modules only. Framework may expose generic migration/upgrade planners for app modules, but application developers opt into and own their own deployment workflows. Do not silently execute arbitrary app-module installers through the platform updater. Validate required interoperability without taking ownership of their deployment.

### Generators and reference module

Artisan generators create complete skeletons and controllers/models/migrations/services/Actions/public API contracts/Vue components/tests/docs inside an existing module. Derive owner namespace/schema/storage/test paths, named routes and entrypoint classification. Validate identifiers and prevent path traversal, collisions and unintended overwrites; explicit overwrite is scoped and reviewed. Runa-owned generation requires platform-development context, never a customer production shortcut. Skeletons have no unnecessary business implementation.

One evolvable reference Optional module is development/testing only, demonstrates actual registry/manifest/schema/storage/public API/settings/health/diagnostic+operational CLI/frontend/UI slots/events/update contracts and reusable tests. Keep it out of production discovery, sparse source and images, not merely default-disabled. Test Core/dependency/failure scenarios with isolated fixture modules instead of producing fake mandatory services. Reference docs can later gain examples consuming real Core modules as those are delivered.

## Implementation plan

All points refresh actual predecessor contracts, source/knowledge/skills, synchronize affected docs/references and commit tested work. Dependencies are sequential; no inherited UI visual approval gates.

- [ ] 1. Refresh architecture and freeze manifest/public contract schema.
  - Requires released RM-005. Inspect its ownership/provider/update/compatibility interfaces and RM-004 navigation/slots/UI. Define IDs, manifest schema, entrypoint declarations, kind/version/dependency semantics and test fixture topology. Record no real Core requirements. Add developer docs outline in existing architecture documentation.
- [ ] 2. Registry persistence and deterministic discovery.
  - Requires 1. Add shared technical tables/constraints, source/installed-version separation and locked sync. Register new Optional disabled, preserve existing values, validate duplicates/path ownership, missing source and cached generation. Test repeated install/update discovery and no request-time writes. Apply DB identity/test guards.
- [ ] 3. Required/optional graph resolution and lifecycle previews.
  - Requires 2. Version ranges, cycle paths, dependency direction, affected subgraph errors and deterministic cascade DTOs. Test incompatible/missing/transitive/optional graphs, unrelated operation preservation, Core rejection and stale preview revisions.
- [ ] 4. Atomic administrative transitions and missing/unregister policy.
  - Requires 3. Implement enable/disable transaction/fence/generation, recursive flags and no historical reactivation. Add missing/restoration/unregister behavior with retained data provenance. Test concurrent transitions/failures, preserved settings/schema/files and restored incompatible data. No destructive uninstall.
- [ ] 5. Own/effective health and independent maintenance reasons.
  - Requires 4. Severity/mode/expiry checks, caching/persisted observations, bounded evaluation/root causes and scheduled recovery. Distinguish manual/update/Core reasons and liveness diagnostics. Test clock-controlled stale critical/warning, required propagation, automatic recovery, no flag mutation and missing Core using fixtures.
- [ ] 6. PostgreSQL and driver-independent storage boundaries.
  - Requires 5. Schema derivation/ownership, module migration/seed paths, scoped disks, public UUID references and API validation contracts. Test schema collisions/identifier length, identity/FK DDL, absence of cross-module FKs/relations, traversal/symlink/driver escape and preservation on disable/unregister. Document concurrency limits of cross-module reference validation.
- [ ] 7. Availability-aware public APIs and HTTP adapters.
  - Requires 6. Versioned interfaces/DTOs, per-operation proxy checks, named namespaced HTTP registration, resource/error contracts and future authorization boundary. Tests prove cached API references and direct routes cannot bypass disable/health; API-only ordinary app consumer works. Management writes remain trusted CLI-only.
- [ ] 8. Queue fencing, active work and safe cancellation.
  - Requires 7. Module dispatch/job metadata, central worker guards, lease/status/cancellation interface and bounded pending-job policy. Test disable between dispatch/start, retries, running checkpoints, crash lease expiry and state-generation races with real worker acceptance where needed. Publish active-work DTOs for future Admin.
- [ ] 9. Operational/diagnostic Artisan and schedule registration.
  - Requires 8. Manifest discovery and centralized execution classification/gates; no global per-module schedule edits. Test disabled/unhealthy operational rejection, available diagnostics/health recovery, schedule dispatch/execution race and preserved technical runtime pulse.
- [ ] 10. Public events and optional integrations.
  - Requires 9. Versioned facts, after-commit dispatch, listener ownership guards, independent subscriber failure/retry isolation and idempotency; separate critical coordinated call contracts. Implement integration metadata/toggles with both-side availability. Test failed subscriber never repeats origin, disabled queued listener cannot execute, optional absence/toggle never disables module. No Audit implementation.
- [ ] 11. Declarative settings and controlled reinitialization.
  - Requires 10. Typed/default/validated/localized schema, DB operational values, revisions, encryption/redacted reads, immediate/reinitialize handlers and safe compensation. Test sensitive values absent from API/logs, key failure, concurrent changes, invalid defaults and failed application/rollback fencing. Infrastructure configuration remains separate.
- [ ] 12. Future Admin management descriptors and extension contracts.
  - Requires 11. Publish combined metadata/actions/permissions/integrations/active-work/diagnostics DTOs and safe custom-component declarations. Version schemas and renderer extension types; test complete metadata and unsupported types. Provide trusted CLI output now, no Admin UI/Auth mocks/unauthenticated management endpoints.
- [ ] 13. Module frontend discovery, navigation and UI slots.
  - Requires 12. Integrate build metadata/runtime availability with existing layout route contracts, named slot hosts/contributions/priority ordering and optional persisted overrides. Test deep links/refresh, disabled hidden versus unhealthy diagnostic navigation, stable equal priorities, incompatible slot props, stale override handling and custom customer frontend opt-out. Update frontend component/page references.
- [ ] 14. Structured bounded logging and origin propagation.
  - Requires 13. Reuse Monolog, define safe origin/context/correlation/redaction, file channels/rotation/retention and stderr. Test HTTP/CLI/job/event attribution, sensitive payload exclusion and bounded file count/path safety. Document future Logs read contract without search UI or Audit behavior.
- [ ] 15. Module-aware install/update and nonmutating plans.
  - Requires 14. Extend RM-005 plan/journal/lock/preflight hooks; register sources and migrate all Runa modules regardless enabled state, then tracked versioned upgrade chains. Test skipped releases, missing path, incompatible dependency, disabled migrations, step retry/idempotency, first failure stops later modules, maintenance retention and safe resume. Prove toggles run no migrations and application installers are not silently invoked.
- [ ] 16. Generators, module version policy and reusable contract suite.
  - Requires 15. Implement skeleton/common-element generators with owner/context/overwrite protections, docs/tests scaffold and independent module SemVer/changelog checks. Add static/runtime contracts for manifest/registration/graphs/lifecycle/storage/API/settings/events/frontend/versioning; detect forbidden imports/SQL/schema references pragmatically and document limitations. Update AGENTS/skills/quality graph without CI or new universal process gates.
- [ ] 17. Development-only reference module and developer guide.
  - Requires 16. Build executable representative module, use it to validate generated output and all public extension seams. Complete module anatomy/registration/lifecycle/dependencies/health/APIs/data/storage/events/settings/frontend/CLI/upgrade/troubleshooting docs and application developer examples. Ensure each generated module owns docs; preserve reference as evolving development aid. Test complete exclusion from production source/image/discovery and no phantom Core entries.
- [ ] 18. Integrated lifecycle/update/concurrency acceptance.
  - Requires 17. Exercise isolated module graphs with real PostgreSQL, queues, scheduler, HTTP, frontend and updater. Cover unavailable Core maintenance plus manual maintenance, optional isolation, all entrypoint bypass attempts, active-work disable races, interrupted upgrade/resume, missing/restored/unregistered data, app consumer and module dependencies. Scan actual changed images and inspect production AI/reference exclusion. Resolve fixes in coherent points before final verification.
- [ ] 19. Full verification, release finalization, merge and final report.
  - Requires 1–18 complete. Review README, architecture/config/API/frontend/database/deferred concerns, deployment/update/tooling/testing/security/AI docs and affected skill references against actual code; record sources/findings/disposition. Run full applicable local gates/security/production isolation and reusable module contracts. Insert any fixes immediately before this point and rerun full verification. Follow roadmap/release workflows for version/changelog/module metadata, knowledge refresh/docs/release checks, closure/merge/main sanity, immutable tag/GitHub Release and branch deletion. Background gates end the turn without polling. Finalize projected 0.4.0 from actual VERSION.

## Validation, risks and completion criteria

Reuse backend Pest, frontend Vitest/Vue Test Utils/Playwright, static analysis, existing doctor/quality/hooks/security and isolated host production checks; extend them with contract suites rather than create CI. Check actual DDL, real queue start boundaries and disabled HTTP/in-process/API behavior. Static namespace/schema checks are heuristics, supplemented by executable isolation/availability tests and review. Do not pretend malicious trusted module code is sandboxed by a schema convention.

Risks: false Core requirements could make a module-free foundation permanently unavailable; mandatory Core is derived only from actual registered declared Core expectations, preserved missing-Core records and release requirements, not a hardcoded future list. Health cache expiry can cause unsafe stale access or recovery deadlocks; use controllable clocks and independent diagnostics. Disable races span DB flags and external work; fences/generations/checkpoints require failure/concurrency tests and honest cancellation limits. Cross-module data validation can race deletion; document explicit coordinated owner contracts. Settings reinitialization and data upgrades can be irreversible; block/resume and backups take precedence over false rollback promises. Module public APIs/settings/slots become compatibility surfaces; version/document them deliberately.

Acceptance: self-contained ownership-separated modules, valid registry/source/version state, deterministic atomic dependency transitions, independent health/automatic recovery, Core maintenance/manual reason isolation, centralized enforcement on every declared entrypoint, preserved missing/unregistered data, PostgreSQL/storage boundaries, public versioned APIs/events, encrypted settings/declarative Admin metadata, availability-aware optional frontend/slots, automatic commands/schedules, bounded structured logs, disabled-module migrations and resumable fail-stop updates, safe generators and reusable contracts. Development reference is excluded from production; no real business/Core modules or mock authorization shipped. Current docs/gates and independent module version rules are synchronized, full local verification/release complete.

During execution inspect and display exact task resource identities before cleanup; delete only proven task-created disposable containers/networks/tags/volumes after final use, preserving development/test data, rollback resources, backups, knowledge/model caches and verification reports. This planning task itself does not alter runtime resources.

## Cross-roadmap consistency review (2026-10-18)

| Required handoff                                                   | Producer → consumer                                                     | Consistency result                                                                                                                      |
| ------------------------------------------------------------------ | ----------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------- |
| Optional reusable UI, identity provider and navigation descriptors | RM-004 → RM-005/006                                                     | UI works with local defaults first; ownership relocation preserves facade; module descriptors adapt later.                              |
| Platform/application identity                                      | RM-004 interface → RM-005 manifest → RM-006 independent module versions | Platform VERSION remains canonical; application/module versions never change implicitly.                                                |
| Install/update hooks                                               | RM-005 → RM-006                                                         | Generic ordered contribution seam first; registry/health/module migrations only in RM-006. One journal/lock/recovery model.             |
| Isolation/extensibility                                            | RM-005 ownership → RM-006 module boundaries                             | Customers edit app-owned code, never protected Runa/module source. Ordinary code and custom frontends remain valid.                     |
| Auth/Permissions/Admin/Audit integration                           | RM-004 adapter seams + RM-006 metadata/contracts → future roadmaps      | No premature production functionality; trusted CLI management avoids unauthenticated write APIs.                                        |
| Quality and release governance                                     | Existing workflows → all three                                          | Local hooks/quality, no CI, user-controlled activation/major versions, closed history untouched. Visual approvals apply only to RM-004. |
| Temporary examples                                                 | RM-004 catalog removal; RM-006 reference exclusion                      | UI demos removed after acceptance, reusable tests/docs preserved; module reference remains dev-only and absent from production.         |

No requirement is left architecturally unplanned. Execution-time dependency choices still require evidence/approval if new packages are needed. Actual email-client coverage, private GitHub access and production TLS/SMTP validation depend on available execution environments; their tests and failure criteria are specified rather than asserted as already passed. Missing REF-003 image does not prevent planning from its approved textual specification. All three documents remain drafts; no application implementation, activation, version bump, release publication or modification of closed roadmaps is authorized by creating them.

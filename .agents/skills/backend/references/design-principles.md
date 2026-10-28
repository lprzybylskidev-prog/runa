# Backend Design Principles

1. Fail fast at system/input boundaries when critical assumptions are invalid.
2. Keep business logic independent from transport/framework Request/Response objects when reasonable.
3. Define an explicit source of truth for important data/state.
4. Keep dependency direction readable. Introduce interfaces only for real boundaries, swaps, or integrations.
5. Translate errors at boundaries rather than leaking infrastructure exceptions into API/user contracts.
6. Design failure paths together with the happy path.
7. Never swallow errors silently.
8. Keep command/write behavior and query/read behavior semantically clear without implementing ceremonial full CQRS.
9. Make side effects explicit.
10. Do not create `Helpers`, `Utils`, `CommonService`, or similar junk drawers.
11. Use a Rule-of-Three mindset for abstractions. Limited duplication is preferable to speculative generalization.
12. Comments explain why, constraints, or non-obvious choices; they do not narrate obvious code.
13. Do not leave AI-generated change-history/narrative comments in source.
14. Prefer meaningful names over vague `data`, `result`, `tmp`, or unexplained abbreviations.
15. Use one stable term per concept across the codebase.
16. Do not generalize for hypothetical future use.
17. Prefer explicit behavior over magic for important flows.
18. Global scopes are for truly global invariants, not hidden business filtering.
19. Observers/model hooks must not own critical business workflows.
20. Public methods should expose a small meaningful surface.
21. Prefer explicit dependency injection over service locators.
22. Limit mutable static/global state.
23. Project conventions always outrank generic skill preferences.

## Runa application-layer mapping

Use the existing architecture before inventing another layer:

- Controller — HTTP/transport boundary.
- Form Request — non-trivial input validation.
- Action — substantial application use case/state transition/orchestration.
- Service — shared/cross-cutting/integration capability with a clear responsibility.
- Query — complex reusable read/list/report behavior.
- JsonResource — non-trivial response contract.
- DTO — complex multi-layer/integration input/value contract where it improves meaning.

Do not add a repository layer over Eloquent by default.

Do not introduce a new abstraction because one method is long. First identify whether the responsibility itself is wrong.

## Module framework design boundary

Platform module engines (`Runa\ModuleFramework`) and Core bridges
(`Runa\Integration\Core`) are internal. Existing documented Support adapters
and values retain their public identities. Follow the public API boundary for
container dependencies, persisted queue identities and supported entrypoints;
implementation visibility is not permission for customer imports.

During a lifecycle fence, ordinary availability reads use cached own health and stop required-graph traversal. Rejected ingress must not publish on-use observations that invalidate the waiting reservation. Keep regression coverage across HTTP, retained PHP proxies, CLI, scheduler, frontend projection and real queue draining; explicit diagnostic/scheduled recovery remains independent.

For module framework work, consult the [public module contracts](../../../../docs/runa/handbook/architecture/module-framework.md) and active roadmap. Use explicit owner-scoped registry synchronization during installation/deployment; request boot and snapshot reads never discover or mutate registrations. Source version is not installed data version. Modules supply health and transition/work decision policies; the base enforces the shared protocol, fences and state revisions without guessing business behavior. Use `ModuleGraph` for pure dependency/cascade previews over one registry snapshot. A transition publisher must reacquire the snapshot under its publication lock and revalidate the complete preview; graph validity alone is not health, installed-data or preparation readiness. Use `ModuleTransitions` for durable reservation/preparation/publication and recovery; do not hold a transaction over module hooks. Consume snapshot fences at operational boundaries, keep preparation idempotent/reversible by operation ID and retain recovery fences after uncertain compensation. Tombstone unregister preserves data provenance, and explicit re-registration never implies installed-data compatibility. The contract document distinguishes delivered administrative lifecycle from planned operational adapters.

Module health definitions and permitted DB overrides belong to the source-owned `HealthPolicy`; use `ModuleHealth` for revision-fenced writes/diagnostics and `ModuleAvailability` for required-graph evaluation. Preserve per-use check execution even with fresh observations, bound checks and safe root paths, and keep check code outside transactions. Health publication participates in registry generation. Never clear manual/update maintenance to recover Core health: compose independent reasons through `MaintenanceState`. See the public module contract for delivered PHP/HTTP/queue work adapters and classified command/schedule adapters and independent public event/integration adapters.

Use `ModuleIdentity`, `Data\ModuleSchema` and `Data\ModuleModel` for module-domain ownership/identity/relations, `Data\ModuleDataPaths` for validated migration/seed inputs and `Storage\ModuleStorage` for scoped owner disks. Never use foreign-module FKs/models/SQL or raw disk paths. Validate external UUIDv7 references through `PublicReferenceLookup`/`PublicReferenceRule` and the target public API; existence validation alone does not prevent concurrent deletion. Schema markers and retained registry identity jointly determine wipe ownership. Low-level helpers are not availability/authorization gates or a sandbox; see the public contract for exact concurrency and driver limits.

Use `ModuleApis` and owned typed `GuardedApi` proxies for cross-module public calls, including retained lookup validators. Declare exact API versions and immutable DTO boundaries. Use `HttpProvider` business endpoint descriptors and the framework dispatcher; never expose raw implementations or register an unmanaged route to skip module state. HTTP controller middleware and implicit Eloquent binding are unsupported: use the injected operation authorizer and explicit Form Request/owner queries. Protected operations default to denial; declaring a public operation is an explicit exposure choice. Route-cache regeneration follows source synchronization. Guards reject stale ingress, but do not revoke already admitted business effects; managed queue work adds leases and cooperative cancellation through ModuleWork. Keep current availability, authorization and transaction consistency distinct.

For module work, declare JobProvider/JobDefinition and owned WorkHandler/WorkPolicy classes. Dispatch only through ModuleWork, with bounded JSON and atomic database enqueue. Pending/start admission and checkpoints are distinct: modules own retry/cancel/reconciliation decisions, while the framework enforces availability, generations, leases and retry bounds. Do not infer rollback from expired leases or automatically replay ambiguous effects; keep failed/crashed work visible until explicit module reconciliation. Checkpoints belong outside atomic units. Cancellation signals survive transition compensation and reenable. Synchronize the [public work protocol](../../../../docs/runa/handbook/architecture/module-framework.md#delivered-queue-and-active-work-protocol) and relevant tests when changing this boundary.

Declare module CommandProvider/ScheduleProvider descriptors instead of registering raw Artisan commands or global per-module schedules. Diagnostics are trusted recovery handlers; operational commands enqueue owned managed jobs and report admission, not completion. Schedule callbacks revalidate source and dispatch, and workers recheck availability. Optional scheduler overlap locks cover enqueue callbacks only; module work owns longer-lived concurrency/idempotency. See the public module Artisan/schedule contract.

Declare owned EventProvider facts/subscriptions and IntegrationProvider optional metadata. Publish module facts through ModuleEvents after the owner transaction commits; use the prepared closure when admission must happen before that transaction. Independent listener failures never invalidate the committed origin. Async listeners target declared reserved managed jobs; WorkContext delivery keys and module-owned idempotent reconciliation permit subscriber-only retries. Keep the after-commit callback delivery gap explicit; it is not a durable relay. Required coordinated behavior uses typed ModuleApis and owner-side consistency contracts. Integration writes use expected registry generation and preserve enabled flags; event invocation rechecks both sides and the toggle. Synchronize the public module event/integration guide and queue/console pattern reference when changing these boundaries.

Declare module settings through one owned SettingsProvider with validated typed/default/localized SettingDefinition values and a reversible SettingsHandler. Use ModuleSettings for generation/revision-fenced writes and encrypted DB persistence; infrastructure configuration stays external. Read secrets only through the explicit private backend value method, never management projections, handler contexts, observer payloads or logs. Immediate/reinitialize effects run behind the existing transition fence; compensate by stable operation ID after restoring prior values. Ambiguous restoration retains recovery_required, never a claimed rollback. SettingsObserver is a best-effort independent integration seam, not an Audit store/relay. Synchronize the [settings contract](../../../../docs/runa/handbook/architecture/module-framework.md#delivered-declarative-settings) and failure/concurrency tests when changing this boundary.

Module source keeps implementation; tests live in central tests/backend, tests/frontend and tests/tools under the app/runa owner and modules/<code>; module guides live centrally in docs/app/modules/<code> or docs/runa/handbook/modules/<code> by owner, never in a module-local docs directory. Manifest docs references are repository-root metadata and must not become runtime file dependencies. Generators preserve this placement.

Use source-owned ManagementProvider DTOs for future administrative permissions/actions/custom-component metadata. Read combined status/settings/integration/work projections through Management\ModuleManagement; never serialize private runtime settings, work payloads or provider exceptions. Renderer compatibility is an explicit versioned bootstrap catalogue; custom backend setting types need separate renderer contracts. Component keys are owner-prefixed build identities, never dynamic imports/URLs/DB code. Descriptor permissions do not grant access: future execution must bind a trusted backend use case through OperationGuard/OperationAuthorizer and recheck generation/revision. Keep unavailable modules diagnosable and synchronize the [management contract](../../../../docs/runa/handbook/architecture/module-framework.md#delivered-management-descriptors) when this boundary changes.

For frontend module metadata, use the sanitized Frontend\ModuleFrontend projection and source-owned FrontendProvider/SlotProvider declarations. Public null permissions are explicit; protected presentation consults OperationAuthorizer, while business requests still require OperationGuard. Keep page/slot build identities local and manifest-bound; never execute component paths from DB settings. Slot ordering uses the existing registry lock/generation and retains inactive IDs across source loss or disable. See the [frontend module contract](../../../../docs/runa/handbook/architecture/module-framework.md#delivered-frontend-pages-and-named-slots) and frontend component/page references.

Use the supplied Laravel structured logging channels with static English messages and allowlisted technical reason/identity context. Module HTTP/PHP/CLI/work/event adapters establish origin/correlation and unwind it; ordinary application callbacks may inject LogContext for explicit scopes. Do not log private payloads or interpolate exceptions/user values. Changing diagnostic fields, scope propagation or retention also updates the [public logging contract](../../../../docs/runa/handbook/architecture/module-framework.md#delivered-structured-logging-and-future-logs-read-boundary) and its attribution/redaction regressions. Logs read/search remains deferred; business Audit follows the separate public Audit module contract.

### Module generation and independent versions

Use the delivered ModuleGenerator and ModuleContractSuite rather than native generators writing outside a module. Follow docs/runa/handbook/development/modules.md: preview full candidates, exact per-file hash authorization, central docs, ModuleModel/ModuleSchema, guarded API proxies and classified HTTP descriptors. Replace stateless/refusing skeleton policy/handlers before adding effects. Generation never installs or activates modules.

Assess module-local PATCH/MINOR/MAJOR and update its manifest plus central CHANGELOG.md for implementation changes; review dependents/optional integrations and run module source/runtime/version checks. The scoped backend quality graph checks bumps/changelog entries, not semantic correctness. Review literal-check blind spots and do not treat PHP/SQL checks as a sandbox.

Use SettingsParticipant for mandatory transactional history; SettingsObserver stays best effort. Shared account policy, trusted principal scopes, reserved preparation participation and bounded management entities are defined in the [foundation contract](../../../../docs/runa/handbook/architecture/module-foundations.md). These adapters alone do not implement Audit/Auth/Permissions. Never turn trusted preparation into a public availability bypass.

Use guarded Auth identity/lifecycle contracts rather than foreign account models or tables. Keep independent eligibility, permission, session and step-up decisions separate; resolve current eligibility rather than caching login success. Follow the [Auth identity/lifecycle guide](../../../../docs/runa/handbook/modules/auth/README.md) for the authoritative procedure.

Use the [Auth entry transport contract](../../../../docs/runa/handbook/modules/auth/transport.md)
for native session/CSRF bootstrapping, registration/invitation/verification and
private single-use delivery boundaries. Do not add duplicate Fortify routes or put
credentials/tokens in module APIs, events, metadata or queue payloads. Password/2FA flows use the credential procedure below.

Use the [Auth session/abuse contract](../../../../docs/runa/handbook/modules/auth/sessions.md)
for tracked proof/deadline validation, own/admin revocation and standalone abuse
windows. Ordinary polling is not user activity; a heartbeat never moves the captured
absolute deadline. Remembered cookies cannot recreate revoked sessions. Technical
cleanup preserves Audit history. Do not add a native recaller or permanent account
lock from attacker-controlled counters.

Use the [Auth credential security contract](../../../../docs/runa/handbook/modules/auth/security.md)
for password policy/recovery, mandatory factors, trusted devices and AuthStepUp.
Consume server-chosen session/action-bound step-up inside the business transaction;
trust or recent login never substitutes for required fresh proof. Never expose
factor secrets, password history or OTPs through module APIs, logs or Audit.

Use the [private Auth profile/address contract](../../../../docs/runa/handbook/modules/auth/profile.md)
for versioned own/admin changes, recipient-bound verification, notification-only
secondary emails, avatar ownership/cleanup and safe own-history summaries. Other
modules consume guarded public contracts; never expand security delivery to
secondary recipients or expose general Audit detail for self-service history.

Follow the [Permissions contract](../../../../docs/runa/handbook/modules/permissions/README.md) for source-owned declarations, current direct/role action grants and guarded introspection. Preserve independent account/security/availability and record-policy decisions; never call private authorization resolvers or use generic superadmin policy bypasses.

Use the [Core operator/selection contract](../../../../docs/runa/handbook/modules/auth/policies-and-operators.md)
for actual first-administrator membership, resumable invitation admission, private
no-mail setup and Auth bulk selection. Use guarded PermissionsSelections at ordinary
ingress; the trusted operator adapter is local infrastructure access, not a public
authorization bypass. Preserve source/data/update fences and atomic Audit.

Use the [module development corpus contract](../../../../docs/runa/handbook/data/seeding.md#explicit-module-development-contributors)
for explicit owner-checked contributors and module-ID dependencies. Keep native
application production seeding separate, reject production even through direct
fixture calls, exchange public references and preserve credentials/provenance with
atomic Audit. Initial account passwords use hidden local prompts; never argv or
distributed defaults.

For Python tooling changes, follow the [public Python tooling guide](../../../../docs/runa/handbook/development/python-tooling.md) for shared
frozen tools, qualified packages, injected execution contexts and application-owned
configuration. Keep private owner suites/release rules outside delivered services;
verify staged Python through Ruff without running full suites in hooks. Internal integrations call explicit service APIs; process adapters execute fixed
package modules with `python3 -m` from the selected tool checkout. Do not recreate
removed executor paths or compatibility facades.

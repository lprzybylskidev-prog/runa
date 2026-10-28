# Application backend guidance

Read docs/runa/handbook/architecture/application-architecture.md and relevant existing App code. Use thin controllers, Form Requests for non-trivial writes, Actions for substantial use cases, Services for shared integrations and Query classes for complex reads. Use JsonResources for non-trivial responses; avoid repositories over Eloquent and hidden observer workflows. Keep transactions short, validate state at writes, publish commit-dependent events after commit and require idempotency for unsafe retries. Measure queries before introducing caches/queues. Preserve safe English error contracts and static redacted logging.

Use docs/runa/handbook/architecture/public-api.md for exact supported entrypoints.
`Runa\ModuleFramework` engines and `Runa\Integration\Core` bridges are internal;
consume documented Support adapters and values instead of their container
collaborators. The retained ModuleJob identity is a persisted platform envelope,
not a customer dispatch API; use ModuleWork.

Ordinary App code may consume module public Contracts through ModuleApis without becoming a module. For optional app modules load docs/runa/handbook/development/modules.md and the exact public adapter sections in architecture/module-framework.md. Use guarded API proxies, classified HTTP/work/event/console descriptors and explicit installed-version/lifecycle operations. Never call foreign implementations/models/SQL/storage directly. Module source version and installed data version differ. Assess independent module versions, central changelogs and dependent contracts. No phantom Auth/Audit/Permissions implementations.

Use approved public Audit operations for mandatory application history in the owning transaction; SettingsObserver remains best effort. SettingsParticipant is reserved to named Core integration and is not a customer extension or binding replacement. Shared identity/transaction/preparation/management adapters are documented in docs/runa/handbook/architecture/module-foundations.md; Audit and Auth identity/lifecycle behavior come from their owned Core modules; Permissions declarations/current action grants are delivered; ordinary assignment/preset/copy operations are delivered; protected authority and trusted operator/bootstrap integration are delivered. Use public guarded contracts and never turn trusted installation participation into a public bypass.

Meaningful business/security mutations require Audit history. Use public Auditable Contracts with approved fields inside the default PostgreSQL transaction. Bulk SQL, relationship/pivot changes and aggregate operations require explicit manual Audit events in that same transaction; scoped automatic suppression never disables them. Follow docs/runa/handbook/modules/audit/README.md for guarded ModuleApis, redaction, rollback/refusal boundaries and permission-protected bounded reads. Never invoke the protected bootstrap/settings writer or assume arbitrary Eloquent writes are captured.

Use guarded Auth identity/lifecycle contracts rather than foreign account models or tables. Keep current eligibility, permissions, sessions and step-up separate; never cache login success as authorization. Follow the public Auth identity/lifecycle contract in `docs/runa/handbook/modules/auth/README.md`; cookie/session/CSRF entry transport, registration and invitation/verification are delivered under `docs/runa/handbook/modules/auth/transport.md`; password/2FA use the credential guide below. Do not add duplicate Fortify routes or send credentials/tokens through module APIs, events, metadata or queue payloads.

Use `docs/runa/handbook/modules/auth/sessions.md` for tracked session/deadline checks,
own/admin revocation, real-activity heartbeat and bounded account/source login locks.
Polling never renews activity; remember persistence cannot restore a revoked session
or move its captured absolute deadline. Technical cleanup preserves Audit history.
Do not add a recaller or attacker-triggered permanent account lock. Consume delivered
public contracts; protected platform source remains read-only.

Use `docs/runa/handbook/modules/auth/security.md` for shared password policy/recovery,
mandatory factors, trusted devices and AuthStepUp. Keep server-chosen step-up and
its every-action consumption inside the business transaction. Trust/recent login
never substitutes for required fresh proof; keep credentials/OTP/history private.

Use `docs/runa/handbook/modules/auth/profile.md` for versioned private profile and
address workflows, notification-only verified secondary emails, authenticated avatar
ownership/cleanup and safe own-history summaries. Consume guarded public contracts;
never route core security mail to secondaries or grant general Audit read access
for an own-account history feature. Protected platform implementation remains read-only.

Use docs/runa/handbook/modules/auth/preferences.md for the delivered AuthPreferences/
AuthDelivery contracts. Declare exact source-owned preference schemas and notification
categories, use optimistic revisions, and keep account caches/requests isolated on
logout or switch. Legacy guest adoption requires explicit ownership confirmation;
server values win and reset never resurrects legacy state. Do not put credentials,
drafts or permission state in preferences, disable mandatory primary security mail,
cache recipients across jobs, or import Auth private SQL/models. Reference-only
managed security mail rechecks state and uses a persisted single SMTP attempt;
critical recovery retains direct mail. Application code consumes these contracts
without requiring conversion into an app module; protected Runa remains read-only.

Use the public Auth policy/operator guide at
`docs/runa/handbook/modules/auth/policies-and-operators.md` for versioned policy
publication, bounded account exceptions, preview-bound session revocation and
trusted CLI recovery. Consume public contracts; do not write Auth/Audit tables or
implement alternate role flags. Operator handoff secrets never belong in arguments,
output, logs or retrieval. Customer operators protect infrastructure/CLI access.

Follow the [Permissions contract](../../../../docs/runa/handbook/modules/permissions/README.md) for source-owned declarations, current direct/role action grants, guarded introspection and reviewed ordinary assignment/custom-role/preset/copy operations. Preserve independent account/security/availability and record-policy decisions; never call private authorization resolvers or use generic superadmin policy bypasses.

Use the [protected authority contract](../../../../docs/runa/handbook/modules/permissions/authority.md)
for dedicated superadministrator changes and confirmed custom-role force deletion.
Preserve the shared last-eligible-administrator transaction invariant, dedicated
capabilities, fresh both-factor proof and atomic aggregate Audit. Never emulate
protected membership through ordinary assignment/copy/preset writers.

Use `docs/runa/handbook/modules/auth/policies-and-operators.md` for delivered
first-administrator/operator setup and Permissions-selected Auth bulk sessions.
Consume guarded PermissionsSelections and current Auth bulk proof/preview contracts.
Trusted local operator participation never authorizes a public bypass or protected
platform edits; customer CLI/infrastructure access remains customer-owned.

Use `docs/runa/handbook/data/seeding.md#explicit-module-development-contributors`
for supplied local manual-test accounts, hidden initial password setup and explicit
module-owned contributors. Keep application `seeding.register` separate from the
module contributor map; declare module-ID dependencies, preserve fixture provenance
and existing credentials/grants with atomic Audit, and reject production at every
fixture entry. Customer workflows use delivered commands, never owner test tools.

Module email presentation uses the shared native Runa Markdown layout described in
`docs/runa/handbook/architecture/frontend.md` (Native email layout), including text
alternatives and configured branding. Reusing that layout does not authorize
changing a module's delivery, recipient, secret-handling or retry contract. Auth
private mail stays at its guarded synchronous delivery boundary; do not queue its
Mailable or move recovery into general notification routing.

For Python tooling changes, follow the [public Python tooling guide](../../../../docs/runa/handbook/development/python-tooling.md) for shared
frozen tools, qualified packages, injected execution contexts and application-owned
configuration. Keep private owner suites/release rules outside delivered services;
verify staged Python through Ruff without running full suites in hooks. Internal integrations call explicit service APIs; process adapters execute fixed
package modules with `python3 -m` from the selected tool checkout. Do not recreate
removed executor paths or compatibility facades.

Reusable test support and application tooling discovery are documented in `docs/runa/handbook/testing/strategy.md`; compose the delivered helpers rather than importing private platform cases or copying lifecycle logic.

For source-boundary diagnostics, use the [API enforcement guide](../../../../docs/runa/handbook/architecture/public-api.md#local-enforcement-and-delivery).
Keep application consumers on public entrypoints and review new platform integration
callers explicitly; passing static checks never replaces runtime authorization.

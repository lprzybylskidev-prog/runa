---
id: RM-2026-011
title: Audit, Auth and Permissions Core modules
status: active
branch: roadmap/RM-2026-011-audit-auth-permissions-core
created_at: 2026-10-23
activated_at: 2026-10-23
closed_at: null
release_type: feature
proposed_version: 0.5.0
impact_areas:
  - backend
  - frontend
  - database
  - security
  - development
  - tooling
  - documentation
---

# RM-2026-011 — Audit, Auth and Permissions Core modules

## Overview

### What and why

Deliver three platform-owned Core modules in one roadmap and one release, in the
mandatory order **Audit → Auth → Permissions**, following shared foundations and
ending with cross-module integration, expandable developer fixtures and final
verification. Audit supplies meaningful immutable business/security history; Auth
supplies installation-local human identity, secure sessions and private account
self-service; Permissions supplies native action-level grants and reusable access
templates. Reuse the shipped module framework and existing Laravel/Vue platform.

Activated by the owner on 2026-10-23 after
[RM-2026-010](../closed/RM-2026-010-manual-quality-group-selection.md) was released,
closed and merged as maintenance 0.4.2 (baseline `0616673`, tag `v0.4.2`).
The owner confirmed **feature 0.5.0**: increment MINOR and reset PATCH.
VERSION remains 0.4.2 until release finalization.

### Scope and non-goals

Audit and Permissions deliver backend, schemas, contracts, authorized APIs and
future-management declarations only. Auth additionally delivers the necessary
public authentication and own-account views. Profile access belongs in the
existing ApplicationLayout user dropdown; authentication/recovery uses the
existing MinimalLayout where appropriate. No parallel application shell.

No Admin Panel, generic Admin renderer, placeholder administration pages, Audit
viewer UI, role/preset/assignment management UI or user administration pages.
Declare narrow reusable management metadata for later generation of lists,
columns, filters, forms, validations, relationships and actions; do not implement
that generator. Future custom management components remain optional extensions.

No Teams, record ACL engine, deny grants, Spatie Permission, starter kit, Sanctum,
JWT/localStorage credentials, central identity service, LDAP/OIDC/SSO, WebAuthn,
general Notifications/Throttling/Import module, CSV/XLSX user import, hard deletion,
anonymization, audit retention/deletion jobs, SQL interception/triggers, replacement
technical logger, translation-file refactor, speculative framework or CI pipeline.

## Specification

### Decisions and requirement authority

- 2026-10-23 — Owner explicitly requested activation and execution of point 1.
  Baseline main and origin/main match at 0616673; RM-010 is closed, v0.4.2 exists,
  and no other roadmap is active. Confirmed feature 0.5.0 remains appropriate.

- 2026-10-23 — Owner supplied the comprehensive Audit/Auth/Permissions planning
  specification as approved functional requirements and placed this work after
  RM-2026-010. Its explicit request is ONE comprehensive roadmap, not three releases.
- 2026-10-23 — Owner confirmed release_type feature and proposed_version 0.5.0.
  That planning decision did not itself authorize activation.
- Audit precedes Auth; Auth precedes Permissions. Audit has no Auth/Permissions
  dependency. Final Auth security cannot rely on a temporary is_admin column.
- Keep dictionary values in PostgreSQL with code/name_pl/name_en/description_pl/
  description_en. Do not reopen the rejected JSON/translation-catalog migration.
- Audit is synchronous and indefinite. Bulk SQL/relationship changes in meaningful
  audit scope require explicit manual events. Deliver that rule to both agent
  instruction products in the same points that introduce the capability.
- Only user-facing Auth/self-service screens are authorized. Administrative access
  requirements authorize secure backend operations, not management pages.
- Full suites, broad acceptance, static analysis, image builds and scanners run
  only in the final point after implementation. Necessary small focused checks
  resolve concrete uncertainties and finish before their implementation commit.

### Inspected baseline and reuse map

Research used the current local knowledge index and original sources below,
plus Laravel Boost's version-aware Laravel 13 authentication/Fortify documentation.
This is a source review, not a claim that these future capabilities already exist.
Refresh at activation after RM-010; do not freeze internal filenames prematurely.

| Existing source                                                                                                                                                                | Reuse and concrete constraint                                                                                                                                                                                                                                                        |
| ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| AGENTS.md; docs/runa/maintainer/agent-contract.md; maintainer workflows/roadmaps.md and workflows/releases.md                                                                  | Owner lifecycle, one release, explicit activation, point commits, final background handoff and closure synchronization.                                                                                                                                                              |
| docs/runa/handbook/architecture/module-framework.md; src/runa/Support/Modules/ModuleManifest.php; ModuleRegistry.php; ModuleAvailability.php                                   | Core manifests, owner discovery, mandatory registration, retained identity/generation and ingress availability. Currently no shipped Runa Core module; app.reference is a development example only.                                                                                  |
| src/runa/Contracts/Modules; Support/Modules/Api, Data, Work, Events, Settings, Management, Frontend and Updates                                                                | Guarded APIs, schema/storage ownership, queue/schedule/event declarations, settings and versioned update orchestration. Extend these, not a parallel registry.                                                                                                                       |
| src/runa/Support/Modules/Data/ModuleSchema.php and ModuleModel.php; handbook architecture/database.md                                                                          | ModuleSchema uses the supplied Connection and explicit schema claims. ModuleModel supplies UUIDv7, schema qualification and soft deletion; immutable Audit rows must not inherit mutable/deletable behavior blindly.                                                                 |
| src/runa/Support/Modules/Api/OperationGuard.php; Contracts/Modules/OperationAuthorizer.php; Providers/RunaServiceProvider.php                                                  | Default DenyProtectedOperations; trusted identity/permission binding still missing. Availability runs before authorization/controller/validation; permission checks must not recursively authorize their own identity lookup.                                                        |
| src/runa/Support/Modules/Settings/ModuleSettings.php; Contracts/Modules/SettingsObserver.php                                                                                   | Encrypted current values, revisions, generation fence and recovery exist. Observer is explicitly independent and cannot reverse publication; it cannot satisfy atomic mandatory Audit/history persistence.                                                                           |
| src/runa/Contracts/Modules/ManagementAction.php, ManagementPermission.php, ModuleManagement.php; Support/Modules/Management/ManagementRenderers.php                            | Safe localized actions/permissions/settings projection exists, not entity CRUD metadata or an Admin UI. Add only missing declarations needed by these three modules.                                                                                                                 |
| Current src/runa and src/app PHP dictionary search                                                                                                                             | No current name_pl/description_pl dictionary implementation was found; existing module metadata uses localized source arrays. Establish the approved five-field database standard for the new dictionaries without pretending there is an existing dictionary store to migrate.      |
| src/runa/Support/Logging; src/runa/Http/Middleware/AssignRequestId.php                                                                                                         | Existing LogContext, request/correlation/operation scopes, queue/CLI cleanup, safe technical logging. Audit is a separate database history using these identities.                                                                                                                   |
| src/bootstrap/app.php; src/runa/config/database.php; src/runa/config/queue.php; src/runa/config/mail.php                                                                       | One default pgsql connection, database queue with after_commit, existing mail and scheduler. No explicit shipped auth/session config or Auth user implementation; session/CSRF wiring must be added deliberately. No Redis dependency is required.                                   |
| src/runa/resources/js/layouts/ApplicationLayout.vue and MinimalLayout.vue; lib/http.ts                                                                                         | Existing dropdown/user-menu slot and responsive themed layouts; one same-origin HTTP wrapper already sends CSRF headers when a cookie exists. Issuing and validating that cookie/session is still required.                                                                          |
| src/runa/resources/js/lib/preferenceStore.ts, lib/preferences.ts; composables/useLocale.ts, useDisplayPreferences.ts, usePreference.ts, useTableViews.ts, useStatisticsMode.ts | Scoped browser preferences plus legacy runa.locale/runa.theme compatibility writes. Existing keys lack account identity; never import another user's cache on account switch.                                                                                                        |
| src/runa/Support/Seeding; src/runa/config/seeding.php; Database/RequiredSeeds.php; handbook data/seeding.md                                                                    | Category/dependency/deterministic-count primitives and production guard exist. SeedGraph.registered selects application-owned classes only; platform modules need an explicit owner-scoped contributor adapter, not registration as app seeders.                                     |
| distribution/installation.py; distribution/bootstrap.py; tools/application/executors/operations.py; Support/Modules/Updates/ModuleUpdates.php                                  | Existing acquisition/provisioning/journal/forward-data/deployment contracts. Extend actual installation settings and supported command transport for first administrator; preserve recovery and ownership.                                                                           |
| docs/runa/maintainer/customer-agent; distribution/bootstrap.py; distribution/update_files.py; tools/application/knowledge/corpus.py                                            | Separate owner/customer guidance; verified delivery, receipt hashes, reviewed updates and exclusion of authoring templates from retrieval. Audit rules must survive both fresh acquisition and reviewed updates with local extensions.                                               |
| docs/runa/maintainer/architecture/deferred-module-concerns.md; handbook architecture/api.md; development/modules.md                                                            | Existing deferred preferences/Auth/authorization guidance and independent module SemVer/contracts. Deferred deletion wording must be corrected when the new policy is delivered, without rewriting closed roadmaps.                                                                  |
| src/runa/composer.json and src/composer.json                                                                                                                                   | PHP ^8.4 and Laravel 13.37.0; Fortify is absent. The owner specification explicitly mandates Fortify, authorizing that package only. Review exact compatible version/license/security/transitive footprint and lock it during implementation; further dependencies require approval. |

### Layout, ownership and public contracts

Use src/runa/modules/audit, auth and permissions, each with module.json, backend
and its existing framework-owned subdirectories. Public types belong in the
module's backend/Contracts. Use Runa\Modules\Audit, Auth and Permissions prefixes;
domain schemas are runa_audit, runa_auth and runa_permissions. Central customer
module guides belong in docs/runa/handbook/modules/<code>; owner design/release
records stay in maintainer. No module-local docs directory.

Use existing generator previews and independent module manifests/version checks.
Choose initial module versions and explicit install/update edges in point 1;
record every later semantic change in the central module changelog. Platform
0.5.0 does not automatically become each module's version. Keep the existing
framework API version unless a reviewed contract change actually requires a bump.

Required graph: Auth requires Audit; Permissions requires Auth and Audit. Auth
must not acquire a reverse required dependency on Permissions. Provide a neutral
trusted policy/lifecycle guard extension for Permissions to contribute when
installed; before that, administrative HTTP remains denied. Use public identity,
eligibility, step-up and permission DTOs/contracts, no cross-module SQL, Eloquent
relations, foreign keys or private model imports. Bigint identities/FKs stay within
one module; foreign identities are UUIDv7 references with public lookup contracts.

All use the same default PostgreSQL connection for atomic data-plus-audit writes.
Audit's own repository writes its schema via its public contract on that exact
transaction connection; consumers do not write its tables. Actions own the outer
transaction. Document rejection of unsupported alternate-connection auditing.
Cross-module guard calls must preserve this connection and deterministic lock order.

Guarded typed APIs accept bounded scalars/arrays and owned immutable DTOs, not
callbacks, models or secrets. Scoped auto-audit suppression is a trusted local
PHP developer helper, not a callback through ModuleApis. Password/OTP input stays
in Auth-owned HTTP/FormRequest/Action processing and never becomes general module
metadata, event payload or cross-module identity DTO. Necessary enrollment/recovery
material is returned only by the authorized no-store self-service flow.

HTTP declarations use the current /api/runa/<code>/... prefix and named routes;
module controllers use the supported invokable dispatcher signature, validated
UUIDv7 parameters, thin Actions/Queries and JsonResources. No hidden controller
middleware. Introduce trusted pre-controller authentication/session/restricted
flow policy at the shared adapter where needed. Ordinary app APIs get the same
server-enforced account/session predicates. Keep health/ready, diagnostics and
explicit bootstrap/recovery entrypoints independently reachable as appropriate.

Laravel session guard and Fortify supply authentication primitives; customize
route registration/responses so duplicate unguarded Fortify endpoints do not
bypass module availability or the /api contract. No package UI/starter kit.
Explicit session and CSRF middleware on cookie endpoints must issue a same-origin
CSRF token, regenerate session IDs on authentication and invalidate them on logout.
Secure/HttpOnly/SameSite cookies, TLS, CSRF and no-store sensitive responses are
server requirements; frontend route hiding is only presentation.

Dictionary tables and comparable durable options use the exact five columns
code, name_pl, name_en, description_pl, description_en, with stable ownership and
localized display. Adapt existing source descriptors' pl/en arrays at a deliberate
projection boundary; do not replace the manifest localization shape, create a
second editable authority or move dictionary values into locale catalogs. Static
frontend interface copy continues using existing PL/EN mechanisms. Audit technical
outcome codes do not require ceremonial dictionary tables without a display need.

### Required framework reconciliation before module implementation

These are identified implementation constraints, not permission to weaken guards:

1. SettingsObserver currently runs independently of publication and swallows
   consumer failure by design. Add a source-owned transactional participation seam
   for mandatory Audit and Auth policy-version persistence at each actual settings
   publication/compensation transaction. Preserve the observer's independent
   semantics for other consumers. Do not attach fail-closed auditing only to that
   observer or an afterCommit listener. Retain settings fences and recovery.
2. Public event subscribers are independent; they cannot guarantee synchronous
   atomic business auditing either. Domain Actions call Audit directly. Queued mail
   may follow commit; database audit persistence never uses a queue.
3. Auth startup/security checks cannot depend recursively on an authorizer that
   calls the same protected Auth API. Bind a request/job-scoped trusted principal
   and non-recursive identity/eligibility lookup, then Permissions evaluation.
   Reused workers must discard principal, step-up and suppression state.
4. Installation/update reservations intentionally fence ordinary module gateways.
   Define a narrow trusted preparation capability for required audited install/
   declaration changes once Audit is installed, bound to the existing exact
   owner/source/reservation and connection. Never add a caller-supplied bypass flag
   or open a web endpoint. Audit installs first; record the bootstrap boundary when
   its store becomes available, without inventing pre-store history. Ordinary
   ingress stays fenced until preparation succeeds.
5. Shared management metadata lacks entity field/filter/reference declarations.
   Add only bounded, source-owned, versioned metadata for actual module operations,
   with permission/step-up/input/sensitivity/concurrency/preview semantics. No
   database-supplied executable paths or generic renderer is authorized.
6. Auth needs Laravel Authenticatable/verification interfaces while ModuleModel
   enforces module schema/relations. Use an owned compatible model/traits, not
   foreign models or a second default users table. Audit is append-only and needs
   a deliberately separate immutable model/repository policy.
7. The deferred registry's old assertion that Audit will own physical deletion/
   anonymization conflicts with this new approved specification. Replace that
   current-state claim when implemented: a future data-management module owns it.
   Preserve indefinite Audit retention and closed historical records.

Document concrete seam signatures, lock ordering, compatibility, source ownership
and failure behavior in point 1 before consumers rely on them. If implementation
reveals that the approved behavior cannot be met without a new architectural or
constitutional decision, stop that implementation and discuss it; routine adapter
choices do not require reopening functional requirements.

### Audit requirements

- Provide a public record/query contract and an opt-in Auditable trait. Default
  create/update/delete/restore capture is enabled per model; selected events can be
  disabled. Correctly distinguish soft delete, restore and physical-delete events
  without assuming every Eloquent query emits model events.
- Default captures actual changed fields with old/new values; optional per-model
  snapshots capture complete approved before/after state, appropriate creation/
  deletion states and restoration changes. Never serialize model objects blindly.
  Use safe declared field policies, sensitive-name safeguards, bounded nesting/
  byte sizes and explicit redaction. Exclude passwords/hashes, OTPs/TOTP secrets,
  sessions, remember/trust/reset/invitation tokens, recovery codes, cookies and
  authorization headers, including nested/aliased inputs.
- Manual events support arbitrary stable module action codes, safe context,
  outcomes, before/after changes and multiple related entity references in one
  business operation. Scoped nested automatic suppression always unwinds on
  exceptions; explicit record calls remain active. No state leaks across HTTP,
  sequential jobs, CLI or scheduled work. Avoid double recording.
- Changes committed to business data and their Audit row share one PostgreSQL
  transaction. Audit failure rejects the mutation; outer rollback/savepoints also
  roll back its success entry. Ensure automatic hooks run inside the transaction
  containing the write; a post-save exception after autocommit is insufficient.
  Define the supported transactional write helper/precondition for Auditable models
  and make unsafe standalone mutation fail before the write, rather than silently
  claiming atomicity. Cover nested transactions and mass assignment behavior.
- Expose a distinct independent attempt/refusal path. Root orchestration rolls
  back the failed business transaction, then synchronously persists its safe
  failure event before completing the response/command. Explicitly handle nested
  transaction boundaries; never insert a refusal into the transaction being rolled
  back or rely on an afterCommit callback that will be discarded. Document failure
  reporting when Audit itself is unavailable; never silently claim persistence.
- Actors include human, unresolved/anonymous, system, scheduler, queue job, CLI and
  future machine identity. No Auth dependency; public actor references and safe
  bounded historical labels preserve meaning after account deactivation/deletion.
  Auth later supplies a trusted human actor resolver, never request-supplied identity.
- Reuse LogContext request/correlation/operation IDs and managed work identities.
  HTTP context: bounded IP/User-Agent/method/path/request ID, excluding query secrets,
  bodies/headers/cookies. CLI: command/process identity, no arguments/secrets.
  Jobs/scheduler: safe class/task/job/queue IDs. No new technical log subsystem.
- Append-only rows expose no ordinary edit/delete endpoint, even to superadmin.
  No retention settings or cleanup jobs. This does not protect against unrestricted
  PostgreSQL operators. Use bigint identity, UUIDv7 public ID, UTC occurrence time,
  meaningful JSON change payloads and indexes for actual query access paths.
- Authorized bounded query/filter contracts cover actor, module, action, entity
  type/public ID, time range, outcome and correlation. Stable pagination/order,
  payload limits and realistic large-record tests are required. No Audit admin UI.

### Auth requirements

#### Identity, eligibility, registration and invitation

- Human users have immutable unique UUIDv7 identities, soft deletion, one mandatory
  primary email and either name OR first_name/surname, never both representations.
  Enforce complete valid naming alternatives; expose one consistent display name.
  Optional unique normalized login; configured email-only, login-only or either
  lookup. No service/machine credentials or arbitrary HR/domain profile fields.
- Primary email is unique under the configured soft-deleted identifier-reuse policy.
  Default retains reserved email/login; optional reuse creates a new identity and
  transfers no business/Audit history. Restore conflicts require explicit resolution,
  never theft of the newer owner's identifier. Use race-safe DB constraints/claims
  for normalization/reuse transitions, not validation-only uniqueness.
- Keep activation, soft deletion, approval, scheduled activation/deactivation,
  account expiry, email verification, administrative security lock, temporary
  attempt lock and password-change restriction distinct. Eligibility is evaluated
  on every relevant ingress, not only scheduler cadence or initial login.
- Deactivation/deletion revokes all sessions/trusted devices. Reactivation never
  restores them; restoring a deleted user does not activate them. Only authorized
  administrators reactivate/restore, including after self-initiated changes.
  Scheduling supports activation/deactivation only. Expiry is a separate predicate;
  changing/removing it restores eligibility immediately if other gates allow it.
- Before activation date issue no authenticated session/invitation even after correct
  credentials; unauthenticated responses must not reveal lifecycle status.
- Public registration is configurable, with automatic or administrator approval;
  approval, verification and activation dates are independent. When disabled,
  authorized administrators create/invite through backend contracts. All creation
  methods, including future imports, enforce the same invariants.
- Invitations: expiring single-use hashed tokens, invalidation, queued mail,
  manual resend and optional scheduled reminder interval/count. Idempotent delivery
  must recheck current eligibility; never send actionable mail for deleted/inactive/
  otherwise ineligible accounts. Initial password setup does not immediately force
  another change merely because the account is new.

#### Sessions, passwords, 2FA and step-up

- Server-side sessions have unlimited concurrency by default; configurable limit
  atomically revokes the oldest when reached. Self list/revoke and authorized admin
  revocation, inactivity timeout, absolute lifetime and optional remember-me duration.
  Remember-me cannot reset/bypass the absolute authentication lifetime. Track only
  operational metadata; Audit owns historical login events. Clean expired/stale
  sessions and trusted-device technical rows, never Audit history.
- Distinguish real user activity from background traffic. A throttled heartbeat for
  typing/click/scroll/touch may maintain inactivity within the absolute deadline;
  polling is not activity and mouse movement cannot flood the server. Configurable
  warnings/extension UX, enforced server-side. Unexpected expiry/revocation returns
  established 401; restricted flows use stable safe codes without exposing secrets.
- Reauthentication may preserve safe unsaved drafts in account-bound memory where
  feasible. Clear on logout/account switch; no indiscriminate sensitive localStorage
  drafts and no automatic replay of non-idempotent writes.
- Configurable complexity/history/expiry/forced-change policy using framework
  hashing and throttling. Own password change requires current password, optionally
  2FA. Admin resets are email-only; no admin-set/visible temporary password.
  Reset links are expiring and single-use; admin-driven reset revokes all sessions
  and trusted devices. Recovery never bypasses mandatory 2FA.
- Own password changes support keep-all/revoke-others/revoke-all sessions, default
  revoke-others; always revoke trusted devices. Policy changes choose future-only
  (default) or force affected users to change; hashes cannot prove old complexity.
- Forced/expired-password users complete credentials and required 2FA, then get
  restricted sessions permitting only password-change/supporting operations.
- Enable/disable TOTP, TOTP recovery codes and email OTP independently; optional/
  mandatory 2FA, single required method or several registered permitted choices.
  Mandatory TOTP cannot be satisfied by email/password or email OTP. Enforce short
  OTP lifetime, attempt limits, single-use recovery codes and secure storage.
- Trusted devices: optional enablement, lifetime, own/admin list/revocation; bypass
  ordinary 2FA only where permitted, never mandatory step-up. Password change,
  account blocking and 2FA reset invalidate trust appropriately. Privileged admin
  recovery resets/revokes sessions/trust and requires new enrollment under policy;
  it must not create a mandatory-TOTP bypass. No WebAuthn implementation.
- Shared public step-up contract supports password, 2FA or both, recent verification
  TTL or every-action reconfirmation. Bind proof to current identity/session/action
  where required; enforce server-side. Permissions consumes it, not a second engine.
- Standalone abuse defenses cover account/source counting windows, limits, temporary
  locks and optional administrative escalation. Avoid trivial permanent lockout
  attacks and distinguish locks from deactivation. Use existing DB/cache primitives;
  no future Throttling module or new Redis service is required.

#### Email, private profile and avatar

- Verification can apply to new users only or all existing users when enabled.
  Support verification-only restricted sessions. Admin force-reverification
  immediately removes normal access from existing sessions, with no grace period;
  successful verification restores access only if every other predicate passes.
  Audit administrative action and completion.
- Own primary-email change requires current password, new-address verification,
  optional old-address confirmation, old-address notification and expiring single-use
  links. Keep old primary until completion. Session policy keep-all/revoke-others/
  revoke-all defaults to revoke-others.
- Admin change normally verifies new address; separately configured privileged
  bypass may mark verified only with specific permission, step-up, Audit and old-address
  notification. Admin change always revokes all sessions/trusted devices.
- Optional multiple secondary emails are independently verified notification-only
  addresses: never login/reset/invitation/2FA/core-security recipients. Configure
  user add/change/remove; authorized admin management. No within-account duplicates;
  cross-user sharing defaults prohibited, may be enabled with independent per-user
  verification, while primary uniqueness remains enforced. Removal stops routing;
  owning business module controls disclosure of sensitive notifications.
- Private self-service only: own details/password/2FA/sessions/trust/secondary emails/
  preferences, independently configurable own login and account-change history
  queried through Audit. Self-editability of each name representation/login/primary
  email is configurable, default allowed. Admin ordinary editing is independently
  permissioned. Audit relevant changes; no public profile or user-management UI.
- Avatar can be enabled/disabled with editing owner user/admin/both/neither. Disable
  hides controls and uses initials without deleting existing storage. Validate real
  content/type/size, isolate storage through ModuleStorage, authorize reads/writes
  and ownership, reject executable/SVG-like unsafe inputs, and handle replacement
  cleanup after successful commit. Both naming representations support initials.
  Do not build the future Filesystem module or invent image cryptography/codecs.
- Self-deactivation/self-soft-delete independently configurable, both disabled by
  default, require step-up, revoke sessions/trust and audit. Only administrators
  can undo them; last-superadmin protection also applies.

#### Preferences, delivery and policy configuration

- Auth owns durable database-backed per-user preferences and a shared public module
  declaration/read/write/reset API: module namespace, declared key, type/default,
  validation, version/migration and localized metadata where useful. Separate from
  global module configuration; no secrets or arbitrary undeclared JSON dumping.
- Inventory all existing language/theme, table views/columns/filters, statistics and
  shell/view preferences plus scoped and legacy keys. Server values/defaults are
  authoritative once signed in; writes persist immediately and report conflicts/
  failures. Other devices see changes on reload/new login, no polling/WebSockets.
- Optimistic revisions prevent silent conflicting writes. Reset key/module/all to
  declared defaults; admin resets require permission and Audit. Define application/
  module/view/key scopes in addition to account. Import only valid allowlisted
  unclaimed legacy guest settings on initial adoption with explicit safe ownership;
  existing server values win. Never automatically import the last signed-in user's
  cache into another account. Clear account cache/pending requests on logout/switch;
  guest fallback stays separate. Reset must not resurrect legacy values.
- Auth owns addresses and per-user category/channel/recipient preferences. Origin
  modules own notification categories, content, mandatory status and sensitivity.
  Other modules query authorized safe recipient/routing contracts, not Auth tables.
  Initially Auth sends security mail through existing Laravel mail/queue facilities.
  A future Notifications adapter may deliver email/in-app later; no operational
  in-app controls now, and critical recovery has a direct-mail fallback.
- Configurable notices for new browsers, failed logins, locks, password changes,
  account/password expiry with frequency controls. Expiry reminders have start-before,
  interval, maximum count and optional admin copy for account expiry. Jobs are
  idempotent, revalidate recipients/state on execution, and skip obsolete reminders.
  Critical security delivery cannot be user-disabled. No geolocation/fraud engine.
- Global security settings use the existing versioned validated ModuleSettings
  surface plus Auth-owned immutable policy versions/history, comparison, consequence
  previews, optimistic revision, permission, step-up and synchronous Audit.
  Restore creates a NEW version and never reverses already executed security effects.
  Avoid two independently writable current configurations. Explicit allowlisted
  per-account exceptions record reason, expiry, authorized actor and Audit; revalidate
  on policy change, never override non-negotiable invariants. No settings admin UI.

#### Administrative operations and bootstrap

- Authorized bulk session revoke targets one user, selected users or all, using
  Permissions' public selection contracts. Preview exact impact, step-up, optional
  trust revocation, Audit and explicit atomic/bounded bulk error semantics. No UI.
- First administrator through installation email and secure initial-password flow
  or dedicated supported Artisan command. CLI supports mail-not-yet-configured
  initialization with safely handed-off one-time setup material, not a plaintext
  password. Persist a concurrency-safe installation initialization state; repeated
  normal initialization cannot grant unlimited superadmins. Final grant is an
  actual Permissions system-role assignment, never a users.is_admin flag.
- Emergency CLI recovery supports lost 2FA, inactive admin and broken policy. No
  public emergency endpoint/browser bypass. Require explicit operator confirmation,
  trusted infrastructure capability unavailable to ordinary app/worker processes,
  short-lived single-use recovery link/token, no logging of credentials and a complete
  disable switch. Environment labels, an ordinary app UID or a caller flag alone do
  not establish infrastructure authority. Integrate with supported host transport
  and audit CLI origin. Recovery redemption remains narrow and policy-bound, with
  required enrollment/credential setup before ordinary access; do not bypass mandatory
  TOTP by turning email/password into a full session.

### Permissions requirements

- Native roles↔permissions and users↔roles plus direct users↔permissions; effective
  grants are the union, no denies. Stable permission codes integrate Laravel Gate/
  Policies and the existing OperationAuthorizer. Auth owns identity/eligibility;
  Permissions stores only foreign UUIDv7 identity references, no copied Auth model.
- Permission, role and preset rows carry code/name_pl/name_en/description_pl/
  description_en in PostgreSQL. Uniqueness/namespace and module ownership prevent
  one module replacing another's definitions.
- Modules declare permissions and predefined roles in trusted code. Explicit install/
  update synchronizes idempotently, no request-boot writes. Definitions have stable
  codes and controlled localized updates. No automatic user grants, silent removal
  of assignments or deletion of removed declarations; obsolete definitions require
  an intentional migration/lifecycle procedure.
- Module-owned predefined roles are read-only to admins, not automatically assigned;
  changes to role membership on update are previewed and audited because existing
  users may be affected. Custom roles are distinct editable entities.
- Protected nondeletable system super_admin grants existing/future ordinary actions
  centrally, not auth/2FA/step-up/account/domain-rule or unavailable-module bypasses.
  Dedicated assignment/removal capability, step-up and Audit; generic role editing,
  preset/copy or grant-management must not escalate to superadmin accidentally.
- Ordinary actions cannot remove/deactivate/delete the last active eligible superadmin,
  including self-service, bulk and scheduled operations. Lock a shared invariant
  before all relevant Auth/Permissions changes, query each module only through its
  public contract and commit check/change/audit atomically. Define fixed lock order
  for concurrent role/account operations; two concurrent last-admin removals must
  not both pass. Also preview scheduled/expiry consequences; do not promise that
  security locks or external failures can never make every admin temporarily
  unavailable. Emergency CLI recovery addresses such lockout without weakening locks.
- Presets are reusable assignment templates, not roles/runtime layers/permanent user
  links. CRUD, creation from user's ASSIGNED roles/direct grants, application, and
  direct user-to-user copy are authorized and audited. Later preset edits/deletion
  never change prior recipients.
- Add is default: union missing assignments, preserve existing, deduplicate. Replace
  removes prior ordinary role/direct assignments and applies exact requested content,
  while independently enforcing superadmin constraints. Before either mode expose
  current/add/remove and practical effective-permission diff, bind execution to
  reviewed revisions, apply atomically and audit complete old/new assignments.
  Copy assigned roles/direct permissions, never flatten effective grants into directs.
- All assignment/role/membership/preset/copy/module-update changes take effect on
  the next backend request, with correct cross-worker cache invalidation. No logout,
  WebSockets or constant permission polling; frontend refresh may update controls.
- Disabled modules retain definitions, assignments, roles and preset references,
  while unavailable operations stay blocked. Re-enable restores effective grants
  under current definitions; Core modules themselves cannot be normally disabled.
- Default role deletion refuses assigned users/preset references. Explicit authorized
  force-delete requires impacted-user counts, preset references, practical permission
  effects, extra confirmation, step-up, atomic same-module reference/assignment
  removal and Audit. Module/system roles and module permissions are protected from
  ordinary admin deletion; all database FKs remain RESTRICT, no cascade shortcut.
- Authorized administrators MAY grant ordinary permissions/roles they do not own;
  no generic grant-only-your-own rule. Dedicated capabilities, self-escalation checks
  for protected authority and meaningful refused-attempt audit remain mandatory.
- Authorized introspection explains effective/direct/role-derived grants and module
  availability, bounded and privacy-preserving. General action grants compose with
  record-level Laravel Policies owned by business modules; no generic record ACL
  or team-scoped roles/tables/inheritance.

### Cross-module behavior and management metadata

Auth Audit coverage: successful/failed login, create, verify/reverify, reset/change
password, 2FA enroll/remove/reset, trusted-device changes, session revoke, account
activate/deactivate/delete/restore, administrative identity changes, policy publish/
restore, emergency CLI and meaningful restricted/denied attempts. Permissions Audit
coverage: role CRUD, meaningful declaration sync, role/direct assignment/revocation,
preset CRUD/apply, access copy, superadmin changes and denied critical privilege
operations. Use one meaningful aggregate event with scoped automatic suppression
where a mutation otherwise creates misleading duplicates; never include credentials.

Auth supplies trusted actors, historical identity lookup, current eligibility,
step-up and recipient/preferences contracts. Permissions supplies authorization,
selection and last-superadmin participation. Audit works without either; no runtime
circular authorization and no query of another module's internals.

Future Admin declarations expose owned entity IDs, field types/localized labels/
descriptions, safe columns/filters/sorts, validation/input controls, relationships/
public reference lookup, permitted operations, permission and step-up requirements,
configuration and bulk preview/concurrency semantics. Redact secrets, distinguish
read-only module/system values from editable custom data and reject unknown schema
versions. Reuse existing management/render compatibility metadata; no renderer,
management navigation or administration frontend acceptance is created.

### Developer fixtures and testing contract

Extend the existing ManagedSeeder/SeedContext categories, deterministic dependency
ordering, per-seeder counts/seed/random options and production guards with explicit
owner-scoped platform/module contributors. Keep customer seeding independent of
owner-only harnesses and never register protected module classes as application
seeders. Future modules contribute named fixtures through a documented allowlisted
extension point, not arbitrary folder scanning or dynamic classes from requests.

Initial corpus includes an administrator with real role/grants and representative
Auth lifecycle/verification/2FA/session and Permissions role/direct/preset cases.
Use module public creation/assignment contracts. Idempotent stable scenario keys
prevent duplicate accounts/roles/grants and never overwrite a developer's changed
private credentials on rerun. Discover local account details safely through a
supported development command/config; no source-coded distributed password, no
production template knobs/accounts and no test/demo data in normal production
initialization. Required production dictionary/system-role setup is separate from
developer fixtures. Keep deterministic counts/seeds independently configurable.

Prepare meaningful tests while implementing, but execute broad suites/acceptance
only in the final point. Required coverage includes every requirement above and:

| Area                 | Required failure, concurrency and isolation evidence                                                                                                                                                                                                                                                                                                                                                                                                                   |
| -------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Audit                | Eloquent create/update/delete/restore; event toggles; diff/snapshot; redaction including nested secrets; manual multi-entity and relationships; nested suppression/exception cleanup and worker reuse; real same-connection outer rollback/savepoints; audit failure prevents mutation; refusal survives rollback; no unauthenticated event spoofing; immutable read-only APIs; bounded filtering/pagination/large payloads.                                           |
| Auth identity/mail   | Both naming representations; all identifier modes; normalized race-safe uniqueness/reuse/restore conflicts; independent lifecycle predicates; scheduled activation/deactivation and expiry; registration approval; single-use invitation/resend/reminder/retry; verification/reverification immediately restricts existing sessions; primary change and secondary routing/sharing; no account enumeration.                                                             |
| Auth security        | Concurrent session limits/revocation/absolute and idle deadlines, remember-me; real activity versus polling; reset/password history and forced restricted flow; TOTP/email OTP/recovery code replay; mandatory TOTP cannot be bypassed; trusted-device and step-up freshness; abuse locks; concurrent credential/link redemption; emergency capability isolation/disablement; no secret logging.                                                                       |
| Auth state           | Policy history/restore/new revision/critical preview, stale changes/exceptions; profile/avatars/own histories; preference adoption/defaults/resets, races and late responses across account switches; idempotent notifications suppress obsolete/mandatory-routing violations; no operational in-app delivery claims.                                                                                                                                                  |
| Permissions          | Union/direct/role derivation, no denies; source ownership/idempotent declarations and removed definition preservation; read-only module/system roles; add/replace preset and direct copy, preview staleness and later independence; next-request cross-worker invalidation; disabled/re-enabled Optional module preservation; force-delete RESTRICT; dedicated superadmin grants and simultaneous last-admin account/assignment/scheduler mutations.                   |
| Integration/delivery | No Auth↔Permissions required cycle, default deny before integration, availability before controller/validation, all transport routes protected, bootstrapping once under concurrency, interrupted first install/update resume and exact source, synchronous policy Audit failure fences publication, no duplicate aggregate history, public reference-only boundaries, customer fresh acquisition/update/clone receives Audit guidance, dev corpus rejects production. |
| Auth UI only         | Login/register/invite/verify/reset/2FA/restricted flows; own profile/sessions/trust/preferences; 401 and safe draft handling without write replay; desktop/tablet/mobile, light/dark, PL/EN, keyboard/focus/errors/reduced motion and avatar initials. Test rendered views using existing browser tools; no administrative pages.                                                                                                                                      |

Use real PostgreSQL transactions/concurrent connections for atomicity and lock
invariants, controlled time for deadlines, existing mail/queue fakes for policy
cases plus representative real delivery in final acceptance, and existing module
contract/source/runtime/version checks. Do not substitute mocked Gate outcomes for
real auth/CSRF/step-up/availability enforcement. Reuse the public frontend component
catalogue, layouts and test patterns; no new test framework/dependency by default.

## Implementation plan

Each point includes implementation, prepared regressions and affected current-state
handbook/owner/customer guidance in the same commit. Listed tests are preparation
and acceptance criteria for final verification unless a necessary focused check
resolves a concrete uncertainty. Do not run broad checks in points 1–18.

- [x] 1. Establish shared contracts, transaction and bootstrap foundations.
  - Requires explicit activation after RM-010 release/closure/merge. Refresh source,
    knowledge, dependency versions, public API constraints and the actual RM-010
    selection interface. Record the owner-confirmed feature version against baseline.
  - Define manifest versions, required graph, connection ownership, Audit modes,
    authenticated/restricted pre-controller policy, trusted principal scope and
    non-recursive authorization. Specify transactional settings participation,
    preparation-time Audit capability and cross-module last-admin lock protocol.
    Implement the required additive framework seams with safe default bindings;
    subsequent module points supply their actual domain implementations.
  - Prepare minimum management entity/declaration extensions and map existing
    localized descriptors to the database dictionary standard. Evaluate Fortify
    compatibility/dependency footprint; approve no unmandated extra package.
  - Acceptance: explicit contract/signature/schema/lock-order and upgrade decisions,
    fail-closed failure table and bootstrap sequence recorded in durable design;
    regression fixtures cover seams without dummy production Core registrations.
    Public framework docs and both affected instruction references are synchronized.
- [x] 2. Deliver Audit storage, manual API and transactional/independent semantics.
  - Requires 1. Add Core manifest, owned runa_audit migrations, append-only repository,
    immutable public record/query DTOs and manual multi-entity event Actions.
  - Implement trusted actor/execution context and redaction/bounds, same-connection
    success writes and explicit post-rollback independent refusal writes. Integrate
    necessary preparation capability without opening ordinary ingress.
  - Acceptance/tests: audit-write failure rolls back business change; outer/nested
    rollback does not retain success; independent refusal survives; no raw secrets/
    unsafe payloads or Auth dependency. Document public contracts, transaction
    preconditions and unavailable-Audit handling in the Audit handbook guide.
- [ ] 3. Deliver automatic capture, suppression and authorized Audit reads.
  - Requires 2. Implement Auditable event configuration, changed-fields/snapshot
    capture and safe transactional mutation preconditions; local scoped suppression
    helper preserves explicit events and unwinds across errors/reused workers.
  - Add bounded authorized filters/pagination/read metadata, immutability and
    meaningful query indexes. No update/delete operation, retention jobs or UI.
  - Acceptance/tests: all event/mode/rollback/redaction/suppression combinations,
    manual relationship/bulk examples and stable bounded reads. Add mandatory Audit
    awareness now to root AGENTS and owner/customer backend/Laravel/PostgreSQL
    guidance, with public examples and delivery regression preparation.
- [ ] 4. Deliver Auth identities and independent eligibility/lifecycle state.
  - Requires 3. Add Core manifest requiring Audit, runa_auth schema, owned compatible
    Authenticatable model, naming/identifier/primary-email constraints and public
    UUIDv7 identity/display/eligibility lookup contracts.
  - Implement registration approval/activation/soft-delete/restore/expiry/lock
    predicates, identifier reservation/reuse and lifecycle Actions with Audit;
    expose neutral last-admin guard integration, denied until trusted integration
    where administrative authority is unavailable. No is_admin field.
  - Acceptance/tests: naming alternatives, lookup normalization, uniqueness races,
    eligibility combinations, reuse preserves identity/history, restore conflicts
    and revoke-not-restore sessions/trust. Synchronize identity/database guidance.
- [ ] 5. Integrate Fortify, session/CSRF transport and registration/invitation flows.
  - Requires 4. Deliberately add reviewed mandated Fortify through platform composed
    manifests/locks. Wire native guard, cookie/session middleware, named module HTTP
    routes/FormRequests/Actions/resources and central safe response envelopes.
  - Implement configurable registration/approval, password-first-setup invitations,
    eligibility-aware resend/reminders and verification/reverification restrictions.
    Reuse declared jobs/schedules, post-commit mail and existing delivery adapters.
  - Acceptance/tests: no duplicate bypass routes, CSRF/session fixation, anonymous
    enumeration, before-activation refusal, token replay/races and idempotent stale
    delivery checks. Auth security/command/config documentation and package review
    record explain environment defaults and supported extension points.
- [ ] 6. Deliver session limits, deadlines, revocation and abuse defenses.
  - Requires 5. Persist operational sessions/trust metadata under Auth; implement
    unlimited/default and bounded concurrency with oldest-session eviction, absolute/
    idle/remember-me rules, immediate account revocation and restricted ingress.
  - Add own/admin list/revoke APIs, operational cleanup and throttled real-activity
    heartbeat contract. Add account/source abuse windows/locks without new services.
  - Acceptance/tests: concurrent login caps, stale cookie/remember replay, next-request
    lifecycle lockout, cleanup preserves Audit, polling cannot prolong idle and
    absolute deadline survives extension. Publish endpoint/policy/failure contracts.
- [ ] 7. Deliver passwords, 2FA, trusted devices and reusable step-up.
  - Requires 6. Implement password complexity/history/expiry/forced-change and reset,
    session-revocation policies, restricted password flow, TOTP/recovery/email OTP,
    required-method enforcement, enrollment and trust lifecycle.
  - Public step-up confirmation supports password/2FA/both and TTL/every-action;
    privileged 2FA recovery revokes sessions/trust and requires correct re-enrollment.
  - Acceptance/tests: one-time/expiry races, old-password/optional second-factor
    requirements, mandatory TOTP not bypassed, trust never satisfies forced step-up,
    hash/history confidentiality and non-reusable proof. Synchronize secure usage,
    mail settings and owner/customer backend/security guidance.
- [ ] 8. Deliver email changes, private profile, avatar and own-history contracts.
  - Requires 7. Implement protected own/admin primary-email changes, verified
    secondary addresses and notification-only routing; own editable-field policy,
    optional self-deactivate/delete, authenticated avatar storage/validation and
    independently enabled own login/account history through Audit queries.
  - Acceptance/tests: primary uniqueness/old-email preservation, old-address notice,
    configured sharing per-account verification, admin bypass permission/step-up/
    revoke-all, self-only records, avatar ownership/content/disable preservation and
    naming fallbacks. No domain profile expansion or administration pages.
  - Update public Auth contracts and frontend component/pattern references as needed.
- [ ] 9. Deliver account preferences and security delivery contracts.
  - Requires 8. Add typed/versioned preference declarations, Auth-owned rows and
    immediate revision-checked APIs; adapt all inspected local/legacy preference
    consumers with safe first adoption, guest fallback, reset and account switching.
  - Add public address/category/channel/routing preferences, declared Auth email
    jobs/reminders with frequency/eligibility/idempotence checks and future delivery
    adapter boundary. Critical security email stays available without Notifications.
  - Acceptance/tests: reload/new-login device convergence, stale-write conflicts,
    rejected save feedback, account/late-response isolation, reset defaults and
    obsolete/mandatory notice handling. No polling/WebSockets or fake in-app channel.
    Document module ownership/declaration/import and email routing conventions.
- [ ] 10. Deliver policy history, privileged backend and operator initialization.
  - Requires 9. Use ModuleSettings plus transactional history/Audit participation for
    security config preview/publish/restore, per-user allowlisted exceptions and
    revision checking; scheduled activation/deactivation uses current predicates.
  - Implement backend bulk revocation previews and safe limits, first-admin setup
    command/installer contract and restricted infrastructure emergency recovery.
    Prepare Permissions role bootstrap integration, keeping incomplete web admin
    operations denied; never substitute a temporary admin boolean or general bypass.
  - Acceptance/tests: history/Audit failure prevents publication; restore is a new
    version; exception expiry/revalidation; guarded CLI confirmation/disablement and
    application-process refusal; idempotent bootstrap ownership and no secret logs.
    Document operational recovery and policy consequences without an admin UI.
- [ ] 11. Deliver Auth entry and restricted-flow Vue screens.
  - Requires 10. Use MinimalLayout/current components for login, enabled registration,
    invitation/initial password, verification/reverification, recovery/reset,
    2FA/enrollment/recovery and forced-change flows. Use shared HTTP wrapper and
    server-declared capability/restriction states, with no token persistence.
  - Acceptance/tests prepared: all flow outcomes/error/loading/conflict states,
    safe redirect, CSRF, keyboard/focus/announcements, PL/EN, responsive light/dark
    and reduced motion. Update public Auth UI contracts and affected shared pattern
    references in this point; no management shell or placeholder admin route.
- [ ] 12. Deliver own-account UI and session-expiry integration.
  - Requires 11. Add profile link inside ApplicationLayout user dropdown; own details,
    password/2FA, secondary email, avatar, sessions/trusted devices, preferences and
    optional own histories reuse existing layouts/table/form components.
  - Handle 401/restricted responses, configurable idle warning/activity extension,
    safe account-bound in-memory drafts and deliberate resubmission after login.
  - Acceptance/tests prepared: real activity versus polling, no write replay,
    account switch/reset isolation, hide disabled features, private history only,
    responsive/theme/localization/accessibility and existing shell compatibility.
    Synchronize component catalogue and user-facing guides; no admin views.
- [ ] 13. Deliver Permissions dictionaries, declaration sync and authorization.
  - Requires 12. Add Core manifest requiring Auth/Audit, owned runa_permissions
    schema/models with five-field dictionaries, roles/permissions/direct grants and
    UUIDv7 Auth references. Add module-owned role/permission declarations, initial
    system role and controlled idempotent install/update synchronization.
  - Bind native permission union/Laravel Gate/OperationAuthorizer/introspection with
    request-current eligibility and cross-worker invalidation, preserving availability.
    Predefined definitions are read-only; source removal preserves grants/references.
  - Acceptance/tests: direct/role derivation, namespaces, sync/update impact previews/
    Audit, no auto grants, default denial before readiness, disabled Optional-module
    preservation and next-request changes. Publish declarations and public contracts.
- [ ] 14. Deliver assignment, preset and access-copy backend operations.
  - Requires 13. Add authorized versioned assignment CRUD, custom roles, preset CRUD/
    from-user and add/replace application/copy with bound preview and atomic changes.
    Use complete assignment Audit events and scoped automatic suppression.
  - Acceptance/tests: preset snapshot independence, copies retain direct-vs-role
    meaning, idempotent add, exact ordinary replace, stale preview conflicts and no
    implicit superadmin escalation; authorized admins may grant ordinary rights
    beyond their own. Provide safe future-management metadata, no pages.
  - Synchronize public API/security examples and declaration review guidance.
- [ ] 15. Integrate protected authority and last-superadmin invariants.
  - Requires 14. Implement dedicated superadmin grant/remove, fixed shared lock
    protocol and Auth lifecycle participation; cover self/bulk/scheduled deactivation,
    soft deletion and equivalent assignment changes. Enforce eligibility/2FA/step-up/
    domain and availability independently of superadmin ordinary permission shortcut.
  - Implement default role-delete refusal and explicit force-delete preview/confirmation/
    same-schema cleanup with RESTRICT FKs; protect module/system definitions.
  - Acceptance/tests: real parallel last-admin operations cannot both succeed,
    refusals persist independently in Audit, ordinary copy/preset cannot escalate,
    backend introspection respects access and lock ordering avoids deadlocks.
    Document expiry/security-lock boundaries and emergency recovery requirements.
- [ ] 16. Complete cross-module bootstrap, lifecycle and management integration.
  - Requires 15. Wire first administrator in real installer/local supported CLI to
    actual Permissions system role and secure invitation/no-mail setup, once under
    concurrent/repeated/resumed initialization. Complete privileged Auth bulk-user
    selection and step-up integration, scheduled last-admin protection, actor/history
    lookup and policy/declaration Audit during fenced forward updates.
  - Finalize minimal entity/field/reference/action/bulk metadata for future Admin;
    no renderer or management UI. Review explicit initial/update/no-op version edges,
    source compatibility and recovery for all three Core modules.
  - Acceptance scenarios prepared: fresh install/existing customer upgrade/clone,
    exact source and repeat resume, all security events without duplicate histories,
    broken Auth/Permissions emergency recovery with capability isolation, unavailable
    dependency handling and no foreign SQL/models/FKs. Synchronize delivery/operator docs.
- [ ] 17. Deliver extensible development-only manual-testing corpus.
  - Requires 16. Extend owner-scoped ManagedSeeder/SeedGraph adapters/contributors;
    modules explicitly contribute deterministic idempotent scenarios and dependencies.
    Add representative administrator/roles/direct/preset/Auth states, guarded local
    account discovery and independent config-based counts/random/seed tuning.
  - Acceptance/tests prepared: repeat no duplicates/credential overwrite, missing/
    cyclic contributor refusal before mutation, real production refusal even through
    explicit CLI, no demo knobs in prod templates and no owner harness dependency in
    customer workflows. Document future module contributions and both data owners.
- [ ] 18. Complete current-state guidance and integrated acceptance preparation.
  - Requires 17. Review AGENTS, owner/customer backend/Laravel/PostgreSQL/security/
    frontend skills and references, public API/module/database/seeding/mail/Auth/
    deployment guides, root README and command help against actual implementation.
    Remove obsolete deferred claims, retain future data-management/Notifications/
    Throttling/Teams/Import/external-provider/WebAuthn scopes, preserve closed records.
  - Ensure mandatory manual Audit review rule, database dictionaries, preferences,
    boundaries and developer corpus reach both instruction products through verified
    bootstrap/update/clone, preserving customer extensions. Prepare integrated
    tests and rendered Auth acceptance from the matrix above; do not execute broad
    suites/builds/scanners here. Review independent module versions/changelog.
  - Acceptance: every approved requirement maps to implementation and meaningful
    prepared coverage; both guidance products and current-state claims agree; no
    admin UI, new undeclared dependency or unsupported delivery claim is introduced.
- [ ] 19. Full integrated verification, feature release, merge and final report.
  - Requires 1–18 complete and committed. Record current-state documentation review
    sources/findings/disposition and actual changed-surface security review. Resolve
    a proportionate explicit final selection through the supported RM-010 interface;
    include real module/backend/auth/CSRF/transaction/concurrency, Auth frontend/E2E,
    database, installer/update/host transport, customer delivery/dev seeder, dependency/
    secret/security and production-isolation acceptance affected by this work. These
    installation integrations genuinely require their acceptance. No invented flags.
  - Run the complete agreed final workflow once implementation is finished, using
    the established background handoff and built-in status command. No earlier small
    diagnostic substitutes for final acceptance. On failure insert fix points before
    this point, complete/commit them and rerun the complete agreed final selection
    from its beginning under the policy actually delivered by RM-010.
  - Inspect and remove only identified task-created disposable resources, preserving
    development/test data, Qdrant/model caches, backups, reports and rollback material.
    Reconcile actual predecessor/version, finalize feature 0.5.0 and independent
    module versions/changelog, synchronize closure docs, refresh knowledge and run
    focused release-metadata checks. Close, merge with merge commit, sanity-check
    main, create immutable tag/push/publish release and delete the roadmap branch
    under the established workflow. Report migrations/recovery and residual limits.

## Execution status

Active on the roadmap branch. Points 1–2 deliver additive framework foundations and manual Audit storage/transaction participation. Point 3 is next: automatic capture, suppression and authorized reads. Auth/Permissions and Fortify remain assigned to subsequent points.
Release metadata is owner-confirmed. Any newly discovered incompatible
architectural premise requires discussion before dependent work.

## Point 1 delivery

- Architecture: [core foundations](../../architecture/core-module-foundations.md)
  records independent 0.1.0 manifests, framework API 1.0.0 compatibility,
  dependency/connection/schema ownership, Audit modes/refusal boundaries, explicit
  transactional initialization/no-op/resume edges, bootstrap order and shared
  registry/advisory/Auth/Permissions lock protocol. No production Core skeletons.
- Additive framework code: transactional settings publication/restoration participant,
  reserved transactional update participant, pre-controller account policy/access
  declarations, scoped trusted principals with recursion refusal, ordinary API
  account middleware, default-denied authority guard and shared transaction lock.
  Missing integration after Audit installation refuses publication/preparation.
- Management: optional version-1 bounded entity/field/operation metadata and safe
  projection; explicit five-column DictionaryEntry projection preserves source
  localization. No renderer, administrative routes or screens.
- [Fortify evaluation](../../architecture/fortify-dependency-review.md) reviews
  compatibility, current releases, advisory history and five-package candidate
  footprint. Candidate exact 1.36.2 avoids the newer mandatory passkeys tree;
  point 5 must refresh the review and lock the actual solver result. No package,
  lockfile, migration, image or runtime service change in this point.
- Knowledge status was current at activation (154 sources/2147 chunks); relevant
  local retrieval and original sources were reviewed, including Laravel 13 Boost
  documentation. RM-010 selection is repeated --group=NAME or --all; preview via
  quality-plan and final background execution/status remain assigned to point 19.
  No final quality scope/evidence is claimed here.
- Focused diagnostics resolved transaction rollback/restoration/retry, reserved
  preparation atomicity/resume, principal cleanup/recursion, pre-controller policy,
  authenticated-null/cache-access refusal and bounded dictionary/entity validation.
  Eight distinct focused regressions passed; the first settings rollback assertion
  incorrectly compared DTO object identity and was corrected to structural equality.
  Additional entity projection and two-connection authority-lock regressions are
  prepared for final acceptance. Broad suites/static analysis/builds/scanners remain
  deferred to point 19; commit hooks remain mandatory.
- Public module/API guidance and owner/customer backend, Laravel, PostgreSQL and
  security references are synchronized. The deferred deletion claim now assigns
  physical deletion/anonymization to future data management. Root/customer AGENTS
  need no duplicate foundation procedure; their existing rules apply. Mandatory
  manual Audit guidance follows actual capture in point 3. Frontend references
  remain unaffected because no frontend capability changed.
- No task-created Docker resources to remove. Existing development/test data,
  services, Qdrant/model caches, backups and verification reports are preserved.
  Tests use the supported guarded maintainer runner and disposable source/schema
  fixtures with its existing scoped teardown.

## Point 2 delivery

- Delivered Core `runa.audit` at independent initial version 0.1.0; public manual API
  version 1.0.0 and framework API 1.0.0 remain compatible. No current required
  dependents exist; planned Auth/Permissions constraints remain ^0.1.0. Central
  Audit changelog and public handbook document actual delivered scope.
- Owned ModuleSchema migrations create append-only events and normalized related
  references with bigint identities, UUIDv7, UTC occurrence, same-schema RESTRICT
  FK, query-driven time/actor/module/action/correlation/entity indexes and bounded
  meaningful JSON. No mutable model, update/delete API, retention or Auth dependency.
- Immutable event/entity/reference/query values, trusted actor resolution, guarded
  manual record/attempt Actions, bounded nested redaction and explicit sensitive
  alias names. Server request/operation/queue/CLI identities remain separate from
  caller context. HTTP retains safe route templates, validated IP and bounded
  known User-Agent family; no raw request/job/command arguments or credentials.
- Success requires the exact active default PostgreSQL Connection. Independent
  refusal requires complete root rollback and a non-success outcome; nested
  ambient transactions and alternate connections are refused. Persistence failures
  propagate as AuditUnavailable without claiming confirmed history. No afterCommit
  or asynchronous Audit persistence.
- Real provider bindings now use the source-checked Audit participation adapter
  after installation. It composes atomic settings publication/restoration and
  reserved preparation without opening ordinary ingress. Transactional initial
  store-ready history starts only after migrations; matching versions/completed
  steps do not duplicate it. Upgrade handlers retain their isolated-construction
  contract and resolve the owned writer only during execution.
- Six distinct focused regressions passed: real root/savepoint rollback and write
  failure; independent refusal/unavailable store; safe payload/actor identity;
  settings participation and invalid reservation; exact initializer/no-op/fenced
  gateway; sequential queue cleanup without inherited human/request identity.
  Initial fixtures missed the manifest-declared test directory and supplied an
  incomplete reservation tuple; both fixture errors were corrected and their
  focused checks passed. Module source check and independent version check passed;
  runtime declarations were checked inside the isolated initializer regression.
  Full suites/static analysis/builds/scanners remain deferred to point 19.
- Current module/framework/deferred guidance and owner/customer backend, Laravel,
  PostgreSQL and security references are synchronized. Root AGENTS and customer
  AGENTS already require guarded module access, safe values and transactional
  writes; no duplicate procedure is needed here. Mandatory automatic/manual Audit
  awareness remains assigned to point 3 when capture is delivered. Frontend
  instruction products remain unaffected because no UI changed.
- No Docker resources were created or removed. Exact disposable Audit fixture
  tables/schema and registry/update-step rows were removed by scoped teardown in
  the guarded test database. Existing development/test data outside that fixture,
  running services, Qdrant/model caches, backups and verification reports remain.

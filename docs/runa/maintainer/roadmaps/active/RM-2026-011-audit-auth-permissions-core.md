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
public authentication, an authenticated application entry view and own-account
views. Profile access and the sign-out button belong together in the existing
ApplicationLayout user dropdown; authentication/recovery uses the existing
MinimalLayout where appropriate. The optional public welcome page exposes
session-aware entry links; disabling it sends `/` to sign-in. No parallel
application shell.

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

- 2026-10-24 — Owner accepts header placement but requires quiet ordinary text
  links instead of prominent buttons for welcome authentication/application entry.
  Point 19d applies this visual correction with accessible hover/focus/touch states
  and republishes current assets. Full point 20 remains gated by visual acceptance.

- 2026-10-24 — Owner visual review requires welcome login/conditional registration
  beside language/theme controls in the page header, including authenticated entry
  in that same location. Point 19c repairs presentation through a reusable public
  MinimalLayout header-actions slot and republishes local assets for continued
  manual review. Focused layout rendering/build is required; point 20 remains gated.

- 2026-10-24 — Owner requests a working local manual-test environment and private
  login details for the development-seeded superadministrator before visual
  acceptance. Point 19b owns non-destructive live development forward preparation,
  explicit Core corpus/password setup, current frontend asset publication and a
  focused real guest/sign-in/account/sign-out check. Building served frontend/error
  assets is explicitly needed for this operational preparation; no full quality,
  static-analysis, image/scanner or broad acceptance gate starts before point 20.
  Never hardcode/distribute passwords, reset the database or bypass Auth security.

- 2026-10-24 — Owner requests one workspace-root .runtime location for generated
  tooling/build state. Add point 19a before final verification to consolidate the
  src/.runtime error renderer/templates under .runtime/frontend/errors, synchronize
  PHP/build/image consumers and durable owner/customer guidance, and preserve
  existing reports, backups, caches and operation state. This does not accept the
  frontend appearance or authorize entry into point 20.

- 2026-10-24 — Owner reaffirmed during point 19 that frontend cleanup is a general
  requirement for every current/future Runa module view, not Auth-only work. Ship
  usable defaults plus public presentation replacement/composition, keep src/app
  minimal and synchronize owner/customer root guidance, domain skills and the
  module development guide so later modules retain the same ownership boundary.

- 2026-10-24 — Owner requires a mandatory stop before the final roadmap point.
  After points 1–19 are complete and committed, explicitly notify the owner that
  the roadmap is ready to enter its final point and wait for the owner's manual
  frontend inspection and explicit acceptance of its appearance. Do not start
  point 20, full verification or release finalization before that acceptance.
  This roadmap-specific gate does not move broad verification into earlier points.

- 2026-10-24 — Owner confirmed the frontend ownership/customization principle:
  Runa supplies immediately usable default views, layouts and components; customers
  may use them unchanged, compose application views from them, or replace their
  entire frontend while retaining supported backend/security contracts. Customer
  overrides belong in src/app and must survive platform updates without protected
  src/runa edits or copying authentication/security orchestration. Keep the shipped
  src/app as small as possible: only essential application/bootstrap/configuration/
  extension entrypoints and the reference module, with no additional sample modules,
  product screens, dashboards or platform presentation/business logic. Thin wiring
  is permitted where needed to boot/customize an application; review each retained
  scaffold file rather than mechanically deleting required entrypoints. New point
  19 reviews and repairs the delivered customization boundary, prepares regressions
  and synchronizes owner/customer skills and public guidance. Final verification
  moves from point 19 to point 20; no broad checks move earlier. Existing feature
  0.5.0 metadata and the exclusion of Admin UI remain unchanged.

- 2026-10-24 — Owner requested an Auth setting to enable/disable the public welcome
  page. Enabled by default, it preserves the welcome view at `/`; disabled, entering
  `/` redirects directly to `/auth/login`. The login flow still resolves an existing
  session into the application or required security steps, without redirect loops.
  Point 12 owns this setting and navigation behavior; sign-out returns through `/`
  and therefore reaches welcome or sign-in according to the current setting.

- 2026-10-24 — Owner confirmed completion of the public-to-authenticated navigation
  path within this roadmap. Point 12 adds session-aware welcome links, the actual
  application entry view using ApplicationLayout, post-login navigation after all
  required security steps, and a sign-out button in the same user dropdown as the
  profile link. Point 17 supplies discoverable development accounts and prepares
  the complete manual sign-in/application/profile/sign-out scenario. This clarifies
  the existing Auth UI scope; feature 0.5.0 and the final-verification order remain
  unchanged. No Admin UI or separate shell is authorized.

- 2026-10-23 — Owner corrected the production responsibility boundary and emergency
  recovery requirements before point 10 implementation. Runa supplies a minimal
  working production baseline with application security; the customer owns host,
  infrastructure and operator-access protection. Authorized access to run the
  dedicated Artisan recovery command is the operator trust boundary. No additional
  recovery container, infrastructure capability or separate database credential is
  required. Retain explicit confirmation, disablement, single-use expiring recovery
  material, no secret logging, session/trust revocation, Audit and required
  credential/2FA enrollment before ordinary access. This supersedes the earlier
  application-process isolation requirement, including its acceptance criteria.
  Synchronized owner/customer agent contracts and the public production architecture;
  existing domain skills already route to those sources and need no duplicate rule.

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
- Emergency CLI recovery supports lost 2FA, inactive admin and broken policy through
  a dedicated supported Artisan command. Access to execute that command is trusted
  operator access; the customer protects infrastructure and CLI access. No additional
  recovery container, infrastructure capability or separate database credential is
  required. Require explicit operator confirmation, a short-lived single-use recovery
  link/token, no logging of credentials and a complete disable switch. Revoke existing
  sessions/trusted devices and audit CLI origin. Preserve supported command transport
  without persisting recovery secrets in transport logs. Recovery redemption remains
  narrow and policy-bound, with required enrollment/credential setup before ordinary
  access; do not bypass mandatory TOTP by turning email/password into a full session.
  No public emergency-issuance endpoint or general browser bypass is supplied.

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
| Auth security        | Concurrent session limits/revocation/absolute and idle deadlines, remember-me; real activity versus polling; reset/password history and forced restricted flow; TOTP/email OTP/recovery code replay; mandatory TOTP cannot be bypassed; trusted-device and step-up freshness; abuse locks; concurrent credential/link redemption; trusted CLI recovery confirmation/disablement; no secret logging.                                                                    |
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
resolves a concrete uncertainty. Do not run broad checks in points 1–19.

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
- [x] 3. Deliver automatic capture, suppression and authorized Audit reads.
  - Requires 2. Implement Auditable event configuration, changed-fields/snapshot
    capture and safe transactional mutation preconditions; local scoped suppression
    helper preserves explicit events and unwinds across errors/reused workers.
  - Add bounded authorized filters/pagination/read metadata, immutability and
    meaningful query indexes. No update/delete operation, retention jobs or UI.
  - Acceptance/tests: all event/mode/rollback/redaction/suppression combinations,
    manual relationship/bulk examples and stable bounded reads. Add mandatory Audit
    awareness now to root AGENTS and owner/customer backend/Laravel/PostgreSQL
    guidance, with public examples and delivery regression preparation.
- [x] 4. Deliver Auth identities and independent eligibility/lifecycle state.
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
- [x] 5. Integrate Fortify, session/CSRF transport and registration/invitation flows.
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
- [x] 6. Deliver session limits, deadlines, revocation and abuse defenses.
  - Requires 5. Persist operational sessions/trust metadata under Auth; implement
    unlimited/default and bounded concurrency with oldest-session eviction, absolute/
    idle/remember-me rules, immediate account revocation and restricted ingress.
  - Add own/admin list/revoke APIs, operational cleanup and throttled real-activity
    heartbeat contract. Add account/source abuse windows/locks without new services.
  - Acceptance/tests: concurrent login caps, stale cookie/remember replay, next-request
    lifecycle lockout, cleanup preserves Audit, polling cannot prolong idle and
    absolute deadline survives extension. Publish endpoint/policy/failure contracts.
- [x] 7. Deliver passwords, 2FA, trusted devices and reusable step-up.
  - Requires 6. Implement password complexity/history/expiry/forced-change and reset,
    session-revocation policies, restricted password flow, TOTP/recovery/email OTP,
    required-method enforcement, enrollment and trust lifecycle.
  - Public step-up confirmation supports password/2FA/both and TTL/every-action;
    privileged 2FA recovery revokes sessions/trust and requires correct re-enrollment.
  - Acceptance/tests: one-time/expiry races, old-password/optional second-factor
    requirements, mandatory TOTP not bypassed, trust never satisfies forced step-up,
    hash/history confidentiality and non-reusable proof. Synchronize secure usage,
    mail settings and owner/customer backend/security guidance.
- [x] 8. Deliver email changes, private profile, avatar and own-history contracts.
  - Requires 7. Implement protected own/admin primary-email changes, verified
    secondary addresses and notification-only routing; own editable-field policy,
    optional self-deactivate/delete, authenticated avatar storage/validation and
    independently enabled own login/account history through Audit queries.
  - Acceptance/tests: primary uniqueness/old-email preservation, old-address notice,
    configured sharing per-account verification, admin bypass permission/step-up/
    revoke-all, self-only records, avatar ownership/content/disable preservation and
    naming fallbacks. No domain profile expansion or administration pages.
  - Update public Auth contracts and frontend component/pattern references as needed.
- [x] 9. Deliver account preferences and security delivery contracts.
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
- [x] 10. Deliver policy history, privileged backend and operator initialization.
  - Requires 9. Use ModuleSettings plus transactional history/Audit participation for
    security config preview/publish/restore, per-user allowlisted exceptions and
    revision checking; scheduled activation/deactivation uses current predicates.
  - Implement backend bulk revocation previews and safe limits, first-admin setup
    command/installer contract and trusted-operator CLI emergency recovery.
    Prepare Permissions role bootstrap integration, keeping incomplete web admin
    operations denied; never substitute a temporary admin boolean or general bypass.
  - Acceptance/tests: history/Audit failure prevents publication; restore is a new
    version; exception expiry/revalidation; trusted CLI confirmation/disablement and
    public emergency-issuance refusal; idempotent bootstrap ownership and no secret logs.
    Document operational recovery and policy consequences without an admin UI.
- [x] 11. Deliver Auth entry and restricted-flow Vue screens.
  - Requires 10. Use MinimalLayout/current components for login, enabled registration,
    invitation/initial password, verification/reverification, recovery/reset,
    2FA/enrollment/recovery and forced-change flows. Use shared HTTP wrapper and
    server-declared capability/restriction states, with no token persistence.
  - Acceptance/tests prepared: all flow outcomes/error/loading/conflict states,
    safe redirect, CSRF, keyboard/focus/announcements, PL/EN, responsive light/dark
    and reduced motion. Update public Auth UI contracts and affected shared pattern
    references in this point; no management shell or placeholder admin route.
- [x] 12. Deliver application entry, own-account UI and session-expiry integration.
  - Requires 11. Add a persisted boolean Auth setting for public welcome availability,
    default enabled. When enabled, `/` keeps the welcome page with sign-in and,
    only when enabled, registration for guests, and application access for
    authenticated users. When disabled, entering `/` redirects directly to
    `/auth/login`, including direct entry and refresh; an existing session continues
    through the normal login-state resolution into the application or required
    security steps, without loops. This presentation setting does not bypass Auth
    checks or revoke sessions. Use the existing settings/public-projection contracts
    and document install/upgrade defaults and how the operator changes the setting.
  - Complete the public-to-authenticated navigation path. Add the actual authenticated application entry view
    using the existing ApplicationLayout, current identity and available navigation.
    After sign-in and all required verification/2FA/password steps, navigate to a
    validated internal return path or this application entry by default, rather
    than returning to the public welcome page. Preserve direct-entry/refresh behavior
    and server-enforced session/eligibility boundaries; no speculative business
    dashboard, Admin page or replacement shell.
  - Put the profile link and sign-out button together inside ApplicationLayout's
    existing user dropdown. Sign-out uses the guarded CSRF-protected POST, handles
    pending/failure states, and on success clears account-bound frontend state/drafts
    and returns to `/`, showing welcome or redirecting to sign-in according to the
    current welcome setting. Navigation/back/refresh cannot restore
    authenticated access after revocation.
  - Implement own-account views for details, password/2FA, secondary email, avatar,
    sessions/trusted devices, preferences and optional own histories, reusing
    existing layouts/table/form components.
  - Handle 401/restricted responses, configurable idle warning/activity extension,
    safe account-bound in-memory drafts and deliberate resubmission after login.
  - Acceptance/tests prepared: real activity versus polling, no write replay,
    account switch/reset isolation, hide disabled features, private history only,
    responsive/theme/localization/accessibility and existing shell compatibility.
    Include welcome-setting defaults and changes, enabled/disabled `/` entry and
    refresh, existing-session resolution without loops, both sign-out destinations,
    guest/authenticated welcome links, disabled-registration visibility,
    post-login/restricted-flow destinations, application direct entry/refresh,
    keyboard/focus access to profile/sign-out in the same dropdown, and actual
    session invalidation plus state cleanup on sign-out.
    Synchronize component catalogue and user-facing guides; no admin views.
- [x] 13. Deliver Permissions dictionaries, declaration sync and authorization.
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
- [x] 14. Deliver assignment, preset and access-copy backend operations.
  - Requires 13. Add authorized versioned assignment CRUD, custom roles, preset CRUD/
    from-user and add/replace application/copy with bound preview and atomic changes.
    Use complete assignment Audit events and scoped automatic suppression.
  - Acceptance/tests: preset snapshot independence, copies retain direct-vs-role
    meaning, idempotent add, exact ordinary replace, stale preview conflicts and no
    implicit superadmin escalation; authorized admins may grant ordinary rights
    beyond their own. Provide safe future-management metadata, no pages.
  - Synchronize public API/security examples and declaration review guidance.
- [x] 15. Integrate protected authority and last-superadmin invariants.
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
- [x] 16. Complete cross-module bootstrap, lifecycle and management integration.
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
    broken Auth/Permissions trusted CLI emergency recovery, unavailable
    dependency handling and no foreign SQL/models/FKs. Synchronize delivery/operator docs.
- [x] 17. Deliver extensible development-only manual-testing corpus.
  - Requires 16. Extend owner-scoped ManagedSeeder/SeedGraph adapters/contributors;
    modules explicitly contribute deterministic idempotent scenarios and dependencies.
    Add representative administrator/roles/direct/preset/Auth states, guarded local
    account discovery and independent config-based counts/random/seed tuning.
    Provide a discoverable usable development account and supported private initial
    credential/setup procedure so the owner can exercise the delivered UI locally.
    Document and prepare the manual path from `/` with welcome enabled and disabled,
    through sign-in and required
    security steps to ApplicationLayout, profile access and dropdown sign-out back
    to `/`, including refusal of authenticated access after sign-out. This uses real
    supplied Auth/Permissions state and no distributed source-coded password.
  - Acceptance/tests prepared: repeat no duplicates/credential overwrite, missing/
    cyclic contributor refusal before mutation, real production refusal even through
    explicit CLI, no demo knobs in prod templates and no owner harness dependency in
    customer workflows. Document future module contributions and both data owners.
- [x] 18. Complete current-state guidance and integrated acceptance preparation.
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
- [x] 19. Reconcile frontend customization contracts and the minimal application scaffold.
  - Requires 18. Inventory delivered welcome/Auth/account/application views,
    layouts, navigation, branding/theme, native HTTP errors and emergency 503
    generation. Distinguish public extension contracts from protected internals;
    preserve supplied defaults without requiring customer configuration.
  - Support three deliberate adoption levels: unchanged Runa screens, application
    screens composed from Runa layouts/components, and an entirely customer-owned
    frontend using supported backend contracts. Repair missing presentation seams
    where needed; customers must not copy security/session/restriction orchestration
    or import private platform internals merely to replace appearance. Keep server
    authentication, CSRF, authorization, eligibility and recovery authoritative.
  - Keep default screens/templates and reusable orchestration in src/runa. Minimize
    shipped src/app to essential manifest/dependency/bootstrap/configuration/route/
    test/extension entrypoints and the reference module. Remove or relocate redundant
    scaffolding and platform behavior; retain only necessary thin application wiring.
    No new application example module, business screen, dashboard or Admin UI.
    Customer-authored screens remain application-owned when customers add them.
  - Document stable override/composition entrypoints, ownership and update behavior;
    preserve customer files/customizations through acquisition, reviewed updates and
    teammate clones. Cover both native errors and generated emergency responses;
    do not introduce a second independent error renderer or bypass protections.
  - Update public component/layout/Auth/error guidance and owner/customer agent
    contracts, frontend/Vue/Tailwind/security skills/references where affected.
    Establish the reusable requirement that future Runa views ship usable defaults
    plus documented presentation replacement without protected source edits.
    Assess independent module versions/contracts and synchronize changelogs.
  - Prepare meaningful regressions/isolated examples for unchanged defaults, custom
    application composition and full frontend replacement, security-flow preservation,
    minimal scaffold and update/clone preservation. Keep examples in owner fixtures,
    not shipped src/app beyond reference. Run only necessary focused diagnostics
    here; broad rendering/integration/installation/security acceptance belongs to 20.
- [x] 19a. Consolidate generated runtime artifacts at workspace root.
  - Requires 19. Move error renderer/templates from src/.runtime to the root
    .runtime/frontend/errors subtree; synchronize frontend scripts, native PHP
    lookup, standalone finalization and explicit production image copies.
  - Preserve generated native views during local relocation and all unrelated
    reports/caches/backups/operation state. Keep public assets and Laravel storage
    in their existing roles. Record one-root placement in owner/customer guidance.
  - Prepare path/build/runtime regressions; run only necessary focused diagnostics.
    Broad builds/images/acceptance remain in 20 after explicit visual acceptance.
- [x] 19b. Prepare the live development environment for manual frontend review.
  - Requires 19a. Inspect local database/registry/build state, apply supported forward
    platform/Core preparation and explicitly run the module development corpus.
  - Privately initialize the fixture administrator password through the existing
    supported prompt and provide the owner its development login details. Preserve
    existing credentials/data and all production guards; no default shared password.
  - Publish current development frontend/error assets and verify real welcome
    guest links, policy-dependent registration, sign-in/security/account/sign-out.
    Repair concrete readiness defects and synchronize the manual preparation docs.
  - Commit the point; full point 20 remains gated by explicit owner visual acceptance.
- [x] 19c. Place welcome authentication links beside header preferences.
  - Requires 19b. Add a public header-actions composition slot, move welcome
    session/guest links into it and preserve registration policy, responsive wrapping,
    default header/identity/preferences and custom-layout forwarding contracts.
  - Synchronize component/page/customization and owner/customer guidance, module
    version/changelog and selected header regression. Publish and inspect the
    actual desktop/mobile themed welcome. No broad verification before point 20.
- [x] 19d. Reduce welcome header entry visual emphasis to text links.
  - Requires 19c. Retain header placement, policy/session navigation and shared
    preferences. Use quiet native anchors with accessible focus, touch and motion
    behavior; publish and inspect desktop/mobile light/dark for continued review.
  - Synchronize current guidance, independent module version and commit; no broad
    verification or appearance acceptance inferred before point 20.
- [x] 19e. Correct the visual outline of programmatic Auth heading focus.
  - Owner review identifies automatic heading outline on Auth entry. Preserve
    programmatic reading focus while suppressing only entry/account heading outlines;
    retain visible interactive focus. Publish and inspect actual entry/tab behavior,
    synchronize module version and guidance; point 20 remains appearance-gated.
- [x] 19f. Use outlined form actions and consistent quiet text navigation.
  - Owner review requests outline submits across forms and Auth navigation matching
    welcome text links. Reuse shared public controls, preserve interaction/security,
    publish local assets and inspect actual form/link states before further review.
  - Update component/form guidance and both frontend products, module version and
    commit; final point 20 remains gated by explicit owner appearance acceptance.
- [ ] 20. Full integrated verification, feature release, merge and final report.
  - Entry gate: stop and notify the owner before starting this final point. The
    owner manually checks the frontend; proceed only after explicit acceptance
    of its appearance. Frontend acceptance is pending and must not be inferred
    from implementation completion, prior approvals or silence.
  - Requires 1–19 complete and committed. Record current-state documentation review
    sources/findings/disposition and actual changed-surface security review. Resolve
    a proportionate explicit final selection through the supported RM-010 interface;
    include real module/backend/auth/CSRF/transaction/concurrency, Auth frontend/E2E,
    database, frontend customization/replacement/native-error and minimal-scaffold
    preservation, installer/update/host transport, customer delivery/dev seeder, dependency/
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

Active on the roadmap branch. Points 1–10 deliver framework foundations, Audit, Auth identities/lifecycle, Fortify entry transport, tracked sessions/abuse defenses, password policy/recovery, second factors, trusted devices, reusable step-up, private profile/address/avatar/own-history contracts and durable account preferences/security delivery. Point 10 adds policy history, bounded privileged backend operations and trusted operator initialization/recovery contracts. Point 11 adds Auth entry and restricted-flow Vue screens. Point 12 adds configurable public welcome, authenticated application entry, own-account UI, dropdown sign-out and session-expiry integration. Point 13 delivers Permissions dictionaries, source-owned declaration synchronization, native current action grants and bounded authorized introspection. Point 14 delivers reviewed ordinary assignment/custom-role/preset/access-copy backend operations with version binding, fresh both-factor proof and aggregate Audit. Point 15 delivers dedicated protected authority, shared last-superadministrator lifecycle protection and confirmed custom-role force deletion. Point 16 delivers actual first-administrator/operator authority, resumable installer invitations, Permissions-selected Auth bulk operations, optional safe actor labels and Core management metadata. Point 17 delivers explicit extensible development-only contributors, real local account/role/direct/preset/Auth scenarios, private initial credential setup and the documented manual navigation path. Point 18 synchronizes current-state guidance and maps requirements to prepared backend, browser and customer-delivery acceptance. Point 19 delivers public frontend customization/replacement contracts, shared static-error customization and the minimal application scaffold. Points 1–19 are complete; point 19a consolidates runtime artifact placement and is complete before point 20, which remains gated by explicit owner appearance acceptance. Point 19b prepares the real development environment and seeded administrator for review. Before the final point, stop for the owner's manual frontend inspection and explicit appearance acceptance; acceptance is pending.
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
  quality-plan and final background execution/status remain assigned to point 20.
  No final quality scope/evidence is claimed here.
- Focused diagnostics resolved transaction rollback/restoration/retry, reserved
  preparation atomicity/resume, principal cleanup/recursion, pre-controller policy,
  authenticated-null/cache-access refusal and bounded dictionary/entity validation.
  Eight distinct focused regressions passed; the first settings rollback assertion
  incorrectly compared DTO object identity and was corrected to structural equality.
  Additional entity projection and two-connection authority-lock regressions are
  prepared for final acceptance. Broad suites/static analysis/builds/scanners remain
  deferred to point 20; commit hooks remain mandatory.
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
  Full suites/static analysis/builds/scanners remain deferred to point 20.
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

## Point 3 delivery

- Core Audit advances independently to 0.2.0 for compatible capture/read behavior.
  Manual API 1.0.0 and framework API 1.0.0 remain unchanged; new AuditReads is
  version 1.0.0. No current required dependents exist. Planned Auth/Permissions
  now require Audit ^0.2.0; their initial module versions remain 0.1.0. The explicit
  0.1.0 -> 0.2.0 forward edge adds action/outcome append-order indexes without
  rewriting history; fresh installation traverses initialization and this edge.
- Public opt-in Auditable and source-owned AuditModelPolicy implement explicit
  field/event allowlists, diff/snapshot, nested JSON redaction, create/update/soft
  delete/restore/physical delete distinctions and quiet single-model capture.
  An existing exact default PostgreSQL transaction and availability are required
  before mutation, even when suppressed/disabled. Each model mutation plus Audit
  uses one savepoint; a caught Audit failure cannot retain the business write.
  Persisted locked before state avoids stale-original history. No-op changes are
  omitted; insert-or-ignore and model atomic counters require manual orchestration.
- Scoped local AuditSuppression unwinds nested callbacks on errors and never
  suppresses explicit records. It is not a callback-bearing gateway API. Public
  examples require manual aggregate/relationship/bulk events in the same owning
  transaction; no SQL interception or claim of universal Eloquent event capture.
- Separate AuditReads operations all require trusted runa.audit.read authority,
  denied by the supplied default authorizer until Permissions integration. Immutable
  summaries/detail and metadata support bounded filters, inclusive UTC ranges,
  stable append-order UUIDv7 cursors, batched related entities, at most 100 summaries
  and 512 KiB per page. Large payloads appear only in single-record detail. No
  edit/delete/retention operation, HTTP management endpoint or Audit UI was added.
- Local knowledge was current (159 sources/2182 chunks); relevant retrieval,
  original foundation sources and Laravel 13 Boost Eloquent/transaction guidance
  were reviewed. Six distinct focused regressions passed, resolving unsafe/caught
  failure/savepoint rollback, lifecycle/snapshot behavior, suppression cleanup and
  explicit records, default denial/filtered bounded reads/large detail, persisted
  before-state/unavailable suppression and fresh initializer/runtime declarations. A final remember/absolute-boundary review added a focused
  regression proving a longer remember policy cannot bypass the ordinary absolute
  lifetime at admission or after policy tightening.
  The first test fixture loaded its module trait before registry initialization;
  moving it to the lazy owner fixture autoloader fixed discovery. The authorization
  fixture also needed fresh singleton gateways after replacing the test authorizer.
  Source ownership and independent module version checks passed. Full lifecycle
  mode/event combinations and delivery/update/clone regressions are prepared for
  final acceptance; broad suites/static analysis/builds/scanners remain point 20.
- Public Audit/module/foundation guidance and deferred capability claims are
  synchronized. Root owner/customer AGENTS and both backend/Laravel/PostgreSQL/
  security products require meaningful Audit history and manual bulk/relationship
  events, referencing one public procedure. Acquisition/teammate-clone and reviewed
  update fixtures assert Audit guidance survives while local extensions are kept.
  CLAUDE already routes to AGENTS; frontend products need no change because no UI
  or frontend contract changed. Historical closed roadmaps/changelog evidence stays
  unchanged. Mandatory hooks and incremental knowledge freshness apply at commit.
- No Docker resources were created or removed. Guarded tests removed only their
  fixture-created app_audit_fixture.records/schema, runa_audit tables/schema and
  matching test registry/update rows through scoped teardown. Existing development/
  test data outside those disposable fixtures, services, Qdrant/model caches, backups
  and verification reports remain preserved. No production migration was executed.

## Point 4 delivery

- Delivered Core `runa.auth` at independent initial version 0.1.0, requiring Audit
  ^0.2.0 and framework API ^1.0.0. New AuthIdentities/AuthLifecycle guarded APIs
  are 1.0.0. Audit and the framework retain their versions; no current Auth
  dependents exist. Planned Permissions ^0.1.0 remains compatible. The explicit
  transactional null -> 0.1.0 initialization creates no user/credential/authority;
  matching versions are no-ops. No Fortify/package/configuration change in this point.
- Owned ModuleSchema migration supplies runa_auth.users with bigint identity,
  immutable external UUIDv7, UTC timestamps, soft deletion, complete exclusive naming
  alternatives and normalized email/login claim constraints. The owned ModuleModel
  implements native Authenticatable and uses public_id for authentication identity.
  Default mass assignment is closed, model serialization reveals only public_id,
  and direct model deletion/restore/force-delete are refused in favor of owning Actions.
- Unique email/login claims reserve identifiers through deletion by default; explicit
  configured reuse releases them on subsequent deletions. Policy changes do not
  retrospectively reassign/reclaim ownership. A restored account reacquires both
  claims atomically and stays inactive; conflicts retain the old deleted identity
  and never transfer history. Identifier modes email/login/either use one normalized
  lookup with unambiguous namespaces. Initial settings use existing audited publication;
  complete policy history/impact preview remains point 10.
- Current-time eligibility keeps approval, activation/scheduling, deletion, expiry,
  administrative/attempt locks and verification/password restrictions independent.
  Exact UTC deadlines apply without scheduler cadence or login-time caching.
  Versioned lifecycle Actions follow registry/settings/advisory/Auth/participant lock
  order, synchronous manual Audit and safe root-rollback refusal persistence. No-op
  changes do not emit history. Missing trusted authority integration denies changes;
  no is_admin, Permissions dependency, foreign model/SQL/FK or administrative HTTP/UI.
- Deactivate/delete/restore/activate and administrative security locks advance the
  persistent revocation generation and clear remember tokens. Rescheduling an already
  effective deactivation or an inactive activation schedule cannot revive old tokens.
  Actual operational session/trust rows, deadline enforcement and registration/auth
  transport remain their owning subsequent points; no such runtime capability is
  claimed merely from the generation contract.
- Local knowledge was current (159 sources/2193 chunks); targeted retrieval and
  original foundation sources were reviewed, together with Laravel 13 Boost native
  authentication/soft-delete/transaction documentation. Six distinct focused
  regressions passed for naming/normalization/DB constraints, exact-time independent
  predicates, reservation/reuse/restore conflicts, caught Audit rollback/revocation,
  default denial/authorized immutable projections and real reserved initialization/
  no-op/runtime declarations. A final remember/absolute-boundary review added a focused
  regression proving a longer remember policy cannot bypass the ordinary absolute
  lifetime at admission or after policy tightening. A fixture expected `completed` instead of the existing
  `succeeded` settings result; the expectation was corrected. Source ownership and
  independent module version checks passed. Competing-connection claim arbitration
  and extended customer acquisition/update/clone scenarios are prepared for final
  acceptance; broad suites/static analysis/builds/scanners remain point 20.
- Public Auth/API/database/module guidance and obsolete deferred claims are
  synchronized. Both backend/Laravel/PostgreSQL/security reference products link
  the identity/lifecycle procedure. Delivery fixtures retain these rules through
  acquisition/clone and preserve customer extensions during reviewed updates.
  Root/customer AGENTS already require UUIDv7, guarded public APIs, default-denied
  authorization and transactional Audit; no additional constitution/procedure copy
  is needed. CLAUDE remains an AGENTS pointer. Frontend products are unaffected:
  no UI or browser capability changed. Platform release metadata stays projected
  feature 0.5.0; historical closed roadmaps and released changelog entries are untouched.
- No Docker resources were created or removed and no production migration ran.
  Guarded focused tests removed only their previously absent runa_auth.users/schema,
  runa_audit.entities/events/schema and matching fixture registry/update rows, with
  exact RESTRICT teardown. Audit fixtures now prepare real Auth storage rather than
  fabricate Core readiness. Development/test data outside those fixtures, existing
  services, Qdrant/model caches, backups and verification reports are preserved.

## Point 5 delivery

- Added mandated exact Fortify 1.36.2 through the platform/composed Composer manifests
  and lock. The solver added only the five reviewed packages, with no unrelated
  updates/removals. Fresh Packagist advisory lookup matched none of their resolved
  versions. Native/custom authentication, CreatesNewUsers and regenerated sessions
  are integrated; package routes/views remain disabled from provider registration.
  Final Composer audits, image/extension/scanner acceptance remain point 20.
- Auth advances independently 0.1.0 -> 0.2.0 for additive entry capabilities;
  existing identity/lifecycle API signatures remain compatible. New protected
  AuthInvitations 1.0.0 exposes bounded resend/invalidation without secrets. Audit
  and framework API versions remain unchanged; Permissions is not implemented and
  its future manifest must select the then-current compatible Auth baseline.
- Added native web guard, encrypted JSON server-side file sessions and explicit
  same-origin CSRF/cookie middleware. Production validates host-only Secure,
  HttpOnly, SameSite=Lax defaults. Login/logout rotate IDs and CSRF; fresh eligibility
  and revocation generation protect ordinary and module ingress, including explicit
  verification-only restrictions. Health/ready and anonymous independent modules do
  not acquire a reverse Auth availability dependency. Remember input is prohibited;
  operational session controls/deadlines/trust stay in their owning points.
- Public registration defaults disabled. Enabled creation preserves exclusive names,
  normalized unique claims, independent automatic/admin approval and verification,
  native hashes and transactional Audit. Duplicate valid submissions have a generic
  accepted response and create no session. Credential refusals expose no existence
  or lifecycle status; login refusal records no identifier/secret. Initial password
  setup never immediately forces another password change or authenticates the user.
- Owned additive entry_tokens migration supplies hashed expiring single-use proofs,
  same-module RESTRICT references and private encrypted pending mail. Token mutation,
  queue work/envelope and Audit commit together. Consumption and Audit failure roll
  back together. Source jobs/schedules recheck current eligibility/recipient/security
  generation, suppress stale mail, rotate manual resends and bound optional reminder
  chains. Delivery claims/purges secrets before one SMTP attempt and does not replay
  ambiguous outcomes; explicit resend is the recovery. Mail fragments avoid server
  query strings; UI consumption is assigned to point 11. No actual email was sent
  during checks (the guarded harness used the array transport).
- Added reserved ModuleSettings::upgradeDefaults for immediate additive defaults:
  preserve stored values, validate complete source keys, advance once and persist
  settings.upgrade through mandatory transaction participation. Ordinary reads never
  fill missing values silently. No changed-value/reinitialize migration authority
  or public preparation bypass is introduced. Registry (stronger row lock), authority,
  user, token order preserves shared lock coordination; SMTP handoff uses the same
  narrow bounded locks after a separately committed at-most-once claim.
- Local knowledge was current (161 sources/2208 chunks); focused retrieval/original
  sources and Boost Laravel 13 Fortify/session/CSRF documentation informed the work.
  Six distinct focused regressions passed for actual named route suppression/session
  rotation, real CSRF, atomic token consumption/Audit rollback, encrypted settings
  upgrades, registration/approval/enumeration and idempotent/stale delivery/reminders.
  CSRF initially returned Laravel's transformed HTTP exception envelope; its safe
  419 renderer was corrected. The upgrade fixture initially lacked real migration
  history; it now establishes it through the actual updater before modeling 0.1.0.
  A focused follow-up caught Laravel 13's additional markEmailAsUnverified interface
  method; both direct verification mutators now refuse the unaudited shortcut.
  Its interrupted empty fixture was inspected and removed through an isolated guarded
  recovery diagnostic, then the login/rotation check passed again. Competing-token
  connection arbitration and broader cookie/concurrency/delivery acceptance are
  prepared for final verification. Module source/version checks passed.
- Current public Auth/API/database/module/command guidance, dependency review and
  owner architecture are synchronized. Owner and customer backend/Laravel/security/
  PostgreSQL references share the new transport procedure; acquisition/clone/update
  fixtures assert it while preserving local extensions. Root/customer AGENTS already
  require protected APIs, transactional Audit and server authority; no constitution
  change or procedure duplication is needed. CLAUDE remains a pointer. Frontend
  instruction products need no update: no browser UI component was delivered.
- The host module source-check wrapper unexpectedly built its missing disposable
  management helper image before running the source check. An attempted interruption
  arrived after the build completed. This was not planned final image acceptance and
  is not credited as a quality gate. Scoped cleanup removed only its newly created
  runa-management:2a709b7923c92afbb4bbef20 tag/image after checking container references.
  No service/network/volume was created, no production migration ran, and active
  development/test data, Qdrant/model caches, backups, reports and shared builder
  caches remain preserved. Guarded fixtures removed only their absent-before-use
  Auth/Audit schemas and matching registry/update/work/queue records.

## Point 6 delivery

- Auth advances independently 0.2.0 -> 0.3.0 for additive session capabilities;
  existing identity/lifecycle/invitation API signatures remain compatible. Add
  protected AuthSessions 1.0.0 and explicit reserved settings-default upgrade.
  Permissions must select the then-current compatible Auth baseline. Audit and
  framework API versions remain unchanged; no new dependency/service is added.
- Owned forward migration creates operational sessions, revocable trust metadata
  and HMAC-keyed account/source login windows. Session/trust have same-module
  RESTRICT user references; internal counters have no public identity. No application
  or production database migration ran outside isolated guarded fixtures.
- Root admission serializes registry/authority/user locks, finishes native/Fortify
  session regeneration before storing the ID hash, atomically evicts oldest live
  sessions when a positive cap applies and records Audit. Unlimited remains default.
  Every authenticated ingress checks fresh eligibility/generation and bound tracked
  session proof, current idle and captured absolute deadline. Lifecycle revocation
  marks session/trust rows atomically; reactivation never restores old access.
- Optional remember persists the same native encrypted session cookie, without a
  recaller or remember-token reconstruction. Idle still applies and the captured
  absolute deadline never resets. Remember duration is capped by the general absolute
  policy. Stricter lifetime policy may expire sooner;
  relaxation never extends the captured deadline. Native storage/cookie ceiling is
  30 days; ordinary cookies still expire on close. Untracked older sessions must
  log in again after upgrade. Trust issuance/2FA remains point 7.
- Own cursor-bounded session list/revoke and explicit real-activity heartbeat are
  named restricted-flow endpoints. Polling never updates activity; early heartbeats
  are coalesced, mouse movement is rejected, extension/warning policy is exposed for
  later UI. Protected administrative list/revoke/all contracts remain denied by
  default until Permissions. No administration UI or automatic write replay exists.
- Standalone transactional DB windows count account failures and all source logins,
  use fixed bounded temporary locks and never slide locks on blocked requests.
  Generic 422 refusals hide account/source/lifecycle reasons. Optional escalation
  emits Audit for review without attacker-triggered permanent security locks.
  Non-login unsafe entry retains its separate source cap. Scheduled operational
  cleanup deletes bounded expired/idle/revoked/stale-generation technical rows only,
  preserving Audit; native files retain Laravel GC.
- Local knowledge was current (162 sources/2232 chunks); focused retrieval, original
  sources and Boost Laravel 13 session/rate-limit documentation were consulted.
  Nine distinct focused regressions passed for deadlines/heartbeat, cap eviction/
  foreign proof/revocation rollback, remembered persistence/cleanup/lifecycle,
  temporary-lock expiry/escalation, login-admission Audit rollback, additive settings
  upgrades, cross-connection registry arbitration and real updater initialization/
  runtime declarations. A final remember/absolute-boundary review added a focused
  regression proving a longer remember policy cannot bypass the ordinary absolute
  lifetime at admission or after policy tightening. An initial test did not send
  real cookies across JSON requests; its browser helper now does. A duplicated import
  was removed; its interrupted fixture left route-registration residue which the
  guarded teardown removed, and the isolated upgrade check then passed. Module source
  and independent version/changelog checks passed. Broader parallel/browser/security/
  installation acceptance remains point 20; mandatory hooks remain enabled.
- Public Auth/session/API/database/security/command guidance and owner architecture
  are synchronized. Owner and customer backend/Laravel/PostgreSQL/security references
  link the shared procedure; acquisition/clone/update regressions assert its delivery
  while preserving local extensions. Root/customer AGENTS already require current
  server authority and transactional Audit, so no constitution/procedure duplication
  is needed. CLAUDE remains a pointer; frontend instruction products are unaffected
  because no browser component is delivered.
- Knowledge status rebuilt the absent cached management-helper image unexpectedly;
  it is not credited as an image quality gate. Scoped cleanup removed only newly
  created runa-management:2a709b7923c92afbb4bbef20 after checking there are no container
  references. No task service/network/volume was created. Guarded fixtures remove
  only their absent-before-use Auth/Audit schemas and matching registry/work/queue
  evidence. Existing development/test databases, Qdrant/model caches, backups,
  verification reports and shared builder caches remain preserved.

## Point 7 delivery

- Auth advances independently 0.3.0 -> 0.4.0 for additive credential capabilities;
  existing identity/lifecycle/invitation/session contracts stay compatible. Add
  AuthSecurity and AuthStepUp APIs 1.0.0, a reserved settings-default upgrade and
  owned forward credential migration. Audit/framework API versions remain unchanged.
  Permissions must select the then-current compatible Auth baseline. No dependency,
  service, UI or production installation change is introduced.
- Shared new-password validation covers registration, invitation setup, change and
  reset: configurable complexity, native hashing, bounded private previous hashes,
  expiry and forced-change restriction. Own change verifies the current password
  and optional fresh factor, supports keep-all/revoke-others/default/revoke-all and
  always invalidates trust, step-up and pending tokens. Reset is expiring/single-use,
  email-only, generic for anonymous callers and revokes all sessions/trust without
  authenticating, verifying an email or removing mandatory/enrolled 2FA. Existing
  password age starts at upgrade; hashes do not claim retrospective complexity.
- TOTP uses Fortify's already installed engine and encrypted expiring enrollment;
  exact accepted counters persist under the user lock, with atomic rollback/replay
  protection. One-time recovery codes persist only as hashes and are shown once.
  Optional email OTP uses encrypted pending delivery and token-bound keyed equality,
  short expiry, current recipient/generation and exact native-session binding.
  Independent enablement/required choices preserve mandatory TOTP against email,
  password and recovery-flow bypass. Account-wide bounded security attempt windows
  supplement the source cap; failures never create permanent account locks.
- Trust issuance requires a fresh factor and uses a private encrypted host-only
  HttpOnly cookie with only hashed/revocable bounded technical storage. Current
  enrollment/method policy, captured/current lifetime and generation gate its use.
  It may satisfy ordinary login factors, never fresh step-up or session deadlines.
  Factor changes/recovery invalidate affected sessions/trust and old proofs.
- Step-up binds identity, session hash, action, requirement, factor method and TTL;
  assertion rechecks current account/session/method policy inside the business
  transaction. Every-action consumption and Audit commit or roll back with the
  protected mutation. Privileged reset requires dedicated permission, neutral
  authority and every-action both-factor proof; before Permissions these protected
  APIs remain denied. No callbacks, models or secrets cross public module APIs.
- Local knowledge was current (163 documents/2246 chunks); focused retrieval,
  original sources and Boost Laravel 13 Fortify/password/hash guidance informed the
  work. Seventeen distinct focused regressions passed across named native transport,
  mandatory enrollment/replay/recovery, factor/password Audit rollback, history/
  session policy, reset preserving TOTP, scoped step-up consumption/TTL/principal/
  method-policy changes, private trust-cookie admission, email OTP binding/expiry/
  attempts, password age/current policy, worker lock arbitration, privileged recovery,
  real updater/default upgrade and private single-attempt reset/OTP delivery.
  The installed engine's first-verification boolean required a non-null initial
  replay counter; ingress now refreshes the owned user before projections so a
  reused guard cannot return stale password restrictions. Module source and independent
  version/changelog checks passed. Broad concurrent/browser/installation/security
  acceptance remains point 20; mandatory hooks remain enabled.
- Public Auth/API/database/cleanup guidance, module changelog and owner architecture
  are synchronized. Owner/customer backend/Laravel/PostgreSQL/security references
  route to the shared credential guide; acquisition/clone/reviewed-update fixtures
  assert its delivery while preserving customer extensions. Root/customer AGENTS
  already require guarded APIs, current server authority and transactional Audit;
  no new constitution rule or duplicated procedure is needed. CLAUDE stays a pointer.
  Frontend instruction products are unaffected because this point adds no screens.
- Knowledge status built the absent cached management-helper image; this is not a
  final image/scanner gate. Scoped cleanup targets only this task-created
  runa-management:2a709b7923c92afbb4bbef20 (no container references). No task service,
  network or volume was created. Guarded fixtures removed only absent-before-use
  Auth/Audit schemas and their registry/work/queue evidence. Development/test data,
  Qdrant/model caches, backups, verification reports and shared builder caches are
  preserved. No actual mail or production database migration was performed.

## Point 8 delivery

- Auth advances independently 0.4.0 -> 0.5.0 for private profile/address/avatar
  capabilities and AuthProfiles 1.0.0. Audit advances compatibly 0.2.0 -> 0.3.0
  for OwnHistory 1.0.0; its forward edge transforms no data and adds no Auth
  dependency. Auth now requires Audit ^0.3.0; future Permissions must select Auth
  ^0.5.0 and Audit ^0.3.0. Existing API signatures/framework API stay compatible.
- Private named HTTP/FormRequest/Action/resource operations enforce current tracked
  unrestricted sessions again under the account lock, optimistic versions and
  configurable own naming/login/email editability. Names retain exclusive complete
  representations and consistent display/initials; no domain profile fields or
  screens are introduced. Explicit self-deactivate/delete default disabled and
  consume password step-up with lifecycle authority, revocation and Audit atomically.
- Primary changes retain the old identity claim until new-address and optional
  captured old-address consent complete. Hash-only equality, encrypted pending mail,
  exact expiry, single-use/reissue cancellation, shared registry/authority/user
  arbitration and native database uniqueness protect completion. Captured own
  session policy defaults revoke-others. Administrative change requires dedicated
  permission, authority and both-factor every-action step-up; normal flow verifies
  the new address, configured separately permissioned bypass can mark it verified.
  Administrative replacement always revokes all sessions/trust. Mandatory former-
  primary notice survives the reported address/generation and later lifecycle
  changes; actionable mail rechecks current eligibility/ownership before SMTP.
- Secondary UUIDv7 identities are independently verified, versioned, optionally
  shared under current policy and notification-only. Add/change/remove switches
  and bounded counts are independent; removal immediately stops routing. Primary
  creation/registration/restore participate in address arbitration. Core credential
  recipients remain primary-only. Recipient/category/channel delivery preferences
  remain point 9. Private profile mail uses reference-only queued work and persisted
  single-attempt SMTP claims; password replacement cancels pending address links.
- Verification policy supports all/new/disabled with creation-time requirement and
  an independent explicit forced flag. Administrative force-reverification removes
  normal access from existing sessions on next ingress, and successful verification
  clears only that restriction. Current eligibility/password/factor/session gates
  remain authoritative. Step-up follows these current verification predicates.
- Avatars use private ModuleStorage, native content/MIME/dimension/size validation
  for JPEG/PNG/WebP and rejection of executable/SVG-like payloads. User/admin/both/
  neither editing and feature disable preserve current blobs/initials. Upload first
  commits a bounded unreferenced cleanup candidate; publication rechecks ownership,
  version/policy and commits with Audit. Old blobs become disposable after commit;
  failed publication preserves the old avatar. Existing technical cleanup retries
  exact unreferenced candidates and expires profile mail/requests, never current or
  disabled avatars, address identities or Audit history.
- Independently enabled own login/account history uses guarded public Audit queries
  bound to the trusted human entity and fixed Auth action lists. Same-scope cursors
  and 50-row pages return only event identity/time/action/outcome, without arbitrary
  subject, actor/entity/payload disclosure or general Audit read permission.
- Local knowledge was current (164 documents/2264 chunks); focused retrieval and
  original sources plus Boost Laravel 13 file/image/storage guidance informed the
  work. Sixteen distinct new focused regressions passed, including session-policy
  dataset variants, private naming/history isolation, address consent/replay/expiry/
  conflict/Audit rollback, secondary ownership/sharing/routing, admin bypass proof,
  pre-existing-session reverification, avatar disable/rollback/cleanup, lifecycle
  proof rollback, recipient-aware single-attempt delivery, password cancellation,
  current verification policy, worker-row arbitration and late restriction changes.
  Three existing focused transport/updater regressions passed. Module source and
  independent version/changelog checks passed; real fenced updater fixtures checked
  current runtime declarations and additive settings/no-op behavior. An unfenced
  runtime CLI scan correctly reported not_registered on the ordinary development
  database; no registry synchronization was forced to satisfy that diagnostic.
  Broad suites/browser/installation/security acceptance remains point 20.
- Public Auth/Audit/API/database/command guides and owner architecture are current.
  Both owner/customer backend/Laravel/PostgreSQL/security references point to the
  public profile procedure; acquisition/clone/reviewed-update assertions prepare
  delivery coverage. Root/customer AGENTS already require guarded APIs, transaction
  Audit and server authority; no constitutional change or duplicate procedure is
  needed. CLAUDE remains a pointer. No frontend component/pattern contract changed:
  actual screens and their frontend instruction updates remain points 11–12.
- No new dependency/service or production migration/mail is introduced. Guarded
  fixtures removed only their absent-before-use Auth/Audit schemas, work/queue and
  registry evidence; avatar fixtures removed their exact temporary storage root.
  Knowledge status recreated the absent cached helper image. After the final hook/
  knowledge operations, scoped cleanup removes only the unreferenced task-created
  runa-management:2a709b7923c92afbb4bbef20 image tag. No task network/container/volume
  was created. Development/test data, Qdrant/model caches, backups, reports and
  shared builder caches are preserved.

## Point 9 delivery

- Auth advances independently 0.5.0 -> 0.6.0, with an explicit reserved forward
  storage/defaults edge. AuthPreferences/AuthDelivery 1.0.0 add compatible public
  contracts; Audit ^0.3.0 and framework API 1.0.0 stay unchanged. Future Permissions
  must select Auth ^0.6.0 and Audit ^0.3.0. VERSION stays 0.4.2 until finalization.
- Source-owned SettingsProvider extensions declare exact typed/versioned preference
  schemas and email categories without another manifest registry. Auth owns bounded
  PostgreSQL rows and account revisions, default/migration validation, current
  principal reads/writes, own key/module/all reset and permissioned audited admin
  reset. Source-configured application and shared display scopes prevent arbitrary
  namespace growth. Named private no-store cookie/CSRF endpoints recheck current
  unrestricted tracked sessions under registry/authority/user locks. Declaration
  source/availability and optimistic conflicts remain authoritative.
- Inventory covers legacy locale/theme, scoped display/navigation, statistics and
  table columns/query/current/named/default views. Shared consumers subscribe to
  account hydration without writing it back, queue immediate revision-ordered saves,
  retain separate guest compatibility storage and show localized failure/reload
  feedback through one Alert/Button-based PreferenceFeedback in both layouts.
  Reset tombstones prevent legacy resurrection. Account caches stay memory-only;
  logout/switch aborts/fences pending and late work. New login/reload refreshes the
  server snapshot without polling. Legacy adoption is explicitly ownership-confirmed,
  allowlists four display/navigation keys, preserves server values and closes after
  adoption/reset. No automatic browser ownership inference is made. Exact domain
  table/statistics schemas remain the originating module's declaration responsibility;
  existing concrete tables are isolated developer fixtures, not shipped app domains.
- Source-owned categories expose email-only routing with verified owned secondary
  UUIDv7 choices and current eligibility; mandatory/sensitive mail is primary-only
  and cannot be user-disabled. Delivery preference changes are audited atomically.
  No Notifications runtime or operational in-app controls are introduced. Existing
  recovery/password/OTP/address-link delivery stays on its direct security path.
- Reference-only managed security notices cover optional new-browser/failed-login,
  configured locks/password changes and account/password expiry. HMAC browser
  inventory conveys no trust. Global availability/frequency, exact-deadline lead/
  interval/count and an optional verified eligible admin-account copy bound delivery.
  A durable rotating 100-user reminder scan, event uniqueness and persisted single
  SMTP claims handle repeat/obsolete work. Execution revalidates policy, account,
  addresses and current deadlines. A future trusted adapter can decline before an
  attempt and retain direct Mail fallback; ambiguous sends never auto-retry.
  Bounded technical outbox cleanup preserves Audit and current preference/address data.
- Added prepared coverage for PostgreSQL persistence/relogin/account/application
  isolation, validation/adoption/reset/revision conflict, guarded DTO API/default
  admin denial, mandatory/invalid recipients, reminder deduplication/obsolescence,
  single SMTP attempt and Audit-failure rollback. Browser tests cover late account
  responses, queued logout fencing, guest separation, conflict feedback and rendered
  shared PL/EN alert/reload controls. Existing fixture migration/version/teardown and
  reserved updater expectations now include the new storage; broad execution stays
  in point 20.
- Necessary focused diagnostics: supported backend runner selecting the three new
  preference/delivery scenarios PASS (55 assertions); supported frontend runner
  selecting account-preferences.test.ts PASS (4 browser tests). These answer concrete
  revision/transaction/late-response/rendering questions, not full acceptance. No
  broad suites, static analysis, builds, security scans or final quality gate run.
  Mandatory commit formatting/lint/secrets/freshness hooks still apply.
- Synchronized the public Auth preference/delivery procedure, independent changelog,
  frontend architecture/component/table/layout references, database/command guidance
  and obsolete deferred-preference claim. Updated owner backend/Laravel/PostgreSQL/
  security/frontend/Vue routes and separately authored customer application references.
  Root owner/customer AGENTS need no new constitution rule: existing public-contract,
  Audit, server authority and account-isolation invariants already cover this capability.
  No new dependency, admin screen or platform release is introduced.
- Fixture teardown removes only its exact disposable Auth/Audit tables and managed
  work rows through the existing guarded owner harness. No task-specific Docker
  containers/networks/volumes were created for these diagnostics. Existing development/
  test data, management tooling image, Qdrant/model caches and verification/browser
  reports remain preserved. The small source diagnostics do not authorize a dev
  database reset or replace final installation/concurrency/browser acceptance.

## Point 10 delivery

- Auth advances independently 0.6.0 -> 0.7.0 with a forward storage/defaults edge;
  AuthPolicies/AuthBulkSessions 1.0.0 are additive. Audit ^0.3.0, framework API
  ^1.0.0 and platform VERSION 0.4.2 stay unchanged. Future Permissions must consume
  Auth ^0.7.0 and Audit ^0.3.0. No dependency, recovery container or service added.
- ModuleSettings remains the single writable current policy. Mandatory source-checked
  participation stores immutable policy revision snapshots/changes/actor/consequences
  atomically with Audit. Optional SettingsAuthorization consumes old-policy both-factor
  step-up before the reservation fence in that same transaction; failure restores the
  proof. The post-write participant never recursively uses an ordinary gateway behind
  its own fence. Historical restore creates a new revision and preserves executed
  security effects. Preview validates revision/generation and compares consequences;
  future-only complexity is default, explicit force-change affects all password-bearing
  non-deleted accounts because stored hashes cannot prove old complexity.
- Account exceptions deliberately allow only password_expiry_days (1–3650) and
  session_idle_minutes (1–43200), with reason, actor, UTC expiry within 30 days,
  monotonic per-key mutation version, current policy revision, every-action both-factor
  proof and synchronous Audit. Null revokes; expiry applies immediately; any global
  policy publication revokes old exceptions for explicit reconsideration. Session
  validation/projection/cleanup and expiry notices use effective account lifetimes.
  Exceptions cannot disable expiry with zero or relax eligibility, absolute deadlines,
  2FA, step-up or permission invariants.
- Bulk session/trust preview captures exact targets and a version/state fingerprint.
  Execution consumes fresh both-factor proof and refuses stale/missing/oversized
  selections atomically: maximum 100 accounts and 10000 session/trust rows, including
  explicit all-account mode. Audit and proof consumption roll back with revocations.
  Final Permissions selection/permission wiring remains in point 16. Scheduled
  activation/deactivation now has bounded managed work, current-predicate/authority
  rechecks, revocation and Audit; activation preserves a future deactivation date
  and repeating consumed dates creates no duplicate history.
- Native fixed Artisan initialize/recover/restore-policy commands use trusted CLI
  access under the owner's corrected production boundary. Explicit confirmation,
  the external AUTH_OPERATOR_RECOVERY_ENABLED switch, private new mode-600 handoff
  files and 15-minute hashed single-use tokens keep secrets out of arguments/output
  and transport logs. A singleton serializes first-admin ownership and same-subject
  pre-setup token rotation. OperatorAuthority is intentionally unbound until real
  Permissions system-role integration in point 16; account commands fail closed now
  and never invent an admin flag. Installer-callable local actions are prepared.
  Policy restoration uses existing trusted settings publication. Account recovery
  revokes old sessions/trust/factors, blocks old-password login while pending and
  creates no session at redemption; ordinary ingress still requires email verification
  and mandatory factor enrollment/challenge. Deleted identities are not restored.
- Prepared regressions cover required-history/Audit rollback, proof rollback at
  publication, restore/new revision/no effect reversal, exception versions/expiry/
  revalidation, stale bulk previews, one-time ownership/token rotation/replay/expiry/
  disablement/private CLI handoff, mandatory TOTP and scheduled idempotence. Existing
  upgrade fixtures include new storage/version/teardown. Necessary focused diagnostic:
  supported maintainer backend runner selecting the seven policy/operator scenarios
  PASS (125 assertions); initial diagnostics exposed a test helper's unavailable
  Mockery dependency (replaced with native Artisan calls) and step-up Audit behind
  the publication fence (fixed by pre-publication transactional authorization).
  No broad suites, installation/concurrency acceptance, static analysis, builds,
  scanners or final gates run; those remain point 20. Mandatory hooks still apply.
- Synchronized public policy/operator/security/database/configuration/command and
  module-foundation guidance, central Auth changelog, owner foundations and both
  backend/Laravel/PostgreSQL/security instruction reference products. Root owner and
  customer AGENTS already carry the explicit CLI trust/production boundary and Audit
  rules; no additional constitution text is needed. Frontend references are unchanged
  because this point supplies no screens; UI belongs to points 11–12.
- Restored the existing development stack nondestructively through runa:dev:up
  after observing a stopped app; no development data migration/reset was run. Tests
  use the existing isolated owner database and remove only exact fixture-created
  tables/work and private handoff files. No task-specific disposable Docker resources
  were created; development services/data, Qdrant/model caches and reports are
  preserved. The forward policy migration is prepared, not applied to development
  or production by this point.

## Point 11 delivery

- Auth advances independently 0.7.0 -> 0.8.0 for additive entry/restricted UI,
  safe public form constraints and own-security flags. An explicit source-only
  forward edge changes no data/settings. Audit ^0.3.0, framework API ^1.0.0 and
  platform VERSION 0.4.2 are unchanged; future Permissions requires Auth ^0.8.0.
  No dependency, migration, authority shortcut or Admin UI is added.
- Public lazy AuthEntry/entryPage composition uses MinimalLayout and existing form,
  selection, feedback and preference controls. Exact named /auth routes cover
  login, enabled registration, forgot/reset password, invitation setup, primary
  verification, profile-address confirmation and current restricted-session support.
  The app-owned App.vue explicitly selects these paths while retaining its welcome.
  Document-only GETs remain independent of module readiness; data endpoints retain
  availability/session/CSRF/eligibility guards. Unknown paths are still 404.
- Public entry-options projects a narrow allowlist of form constraints, never the
  settings document or account state. Compatible security flags guide recovery-code
  availability, password-change proof and verified-email enrollment. Current session
  state orders verification, permitted factor enrollment/challenge and password
  change. Mandatory TOTP offers no email substitute; email enrollment verifies the
  primary first, even when general verification is disabled. Change-specific 2FA
  with no usable factor leads to enrollment. Unknown restrictions fail closed.
- PL/EN forms implement immediate controlled validation, naming alternatives,
  password complexity/UTF-8 bounds, password-manager autocomplete and fixed safe
  backend error mapping. Fragment tokens are removed before requests and submitted
  only by an explicit POST. Token/password/OTP/TOTP/recovery material stays in memory;
  page changes/unmount cancel ownership and discard secrets. Recovery codes require
  an explicit saved acknowledgement. Safe local returns, 401/409/419/429/failure
  states, current-account refresh, explicit CSRF renewal and no automatic write
  replay preserve server authority. Shared controls own motion; step transitions
  are immediate with heading/error focus and live announcements.
- Necessary focused diagnostics resolved the new state-machine/token/focus and
  projection/route uncertainties: supported frontend runner selecting auth-entry
  PASS (12 cases), backend runner selecting the two entry-options/named-route cases
  PASS (25 assertions), and one Chromium rendered login/keyboard diagnostic PASS.
  Inspected dark desktop and light mobile screenshots under
  artifacts/playwright/runa/auth-entry. Additional locale/reduced-motion/flow browser
  scenarios are prepared for final verification; no full suite, broad integration,
  type/static analysis, application/image build, scanners or quality gate ran.
  The isolated browser fixture build reports its existing large scenario chunks;
  it is not production bundle acceptance. Mandatory commit hooks remain required.
- Render startup exposed missing file-session storage in the dedicated test FPM
  runtime (health 500). Compose now creates framework/sessions alongside existing
  test directories; the focused request diagnostic and rendered test confirm the
  repair. No database reset or development/production migration was run. Removed
  the task-recreated runa-dev-app-test-1 and stopped the previously existing
  runa-dev-nginx-test-1 back to its prior state. Preserved development services,
  database data, existing network/images, Qdrant/model caches, reports and screenshots;
  no disposable image/network/volume was created.
- Synchronized Auth UI/transport/security/version guides, public frontend patterns,
  architecture/design current-state claims, owner foundations and owner/customer
  frontend instruction references. Both root AGENTS contracts and remaining domain
  skills already require server authority, controlled forms and secret-safe handling;
  no constitution/backend rule changed. Customer reference describes consuming the
  public component and preserving app-owned integration, never protected edits.
  Account dropdown/self-service, global expiry/activity/drafts and optional own
  security management remain point 12; Permissions and real operator integration
  remain their later points. Full acceptance remains point 20.

## Point 12 delivery

- Auth advances independently 0.8.0 -> 0.9.0 for welcome/application/account UI and
  the persisted public_welcome_enabled boolean (default true). The explicit
  account_ui upgrade adds settings defaults through existing transactional
  history/Audit participation. Fresh installs use the same default. Audit ^0.3.0,
  framework API ^1.0.0 and platform VERSION 0.4.2 remain unchanged. No migration,
  dependency, infrastructure change, authority shortcut or Admin UI is added.
- Public AuthWelcome and AuthApplication composition connects the app-owned App.vue
  with MinimalLayout and the existing ApplicationLayout. Exact named /app and
  /account document routes retain guarded no-store account data. Default public
  root delegates its welcome policy to a platform controller; disabled welcome
  redirects to named sign-in, with frontend projection handling loaded documents.
  Guests see enabled sign-in/registration; authenticated visitors enter the app or
  required security steps. Login/restricted completion selects validated internal
  return paths or /app, avoiding a welcome/sign-in loop.
- Profile link and pending/error-aware CSRF POST sign-out share the existing user
  dropdown. Native LinkButton extracts shared styled navigation from welcome/app
  consumers, inheriting control variants/focus/motion without replacing shell menus.
  Own-account sections cover versioned identity and primary email, secondary
  address verification/reissue/removal, password/factors/recovery, private avatar,
  own sessions/trust, immediate account preferences/reset/email recipients and
  independently enabled private history. Backend flags hide unavailable features;
  required proofs, permissions and neutral authority refusal remain server-side.
- The application boundary handles current 401/restricted responses with ordinary
  reauthentication, fences late replies, refreshes on visible/history restoration,
  warns from server idle/absolute timestamps and sends throttled heartbeats only
  after actual typing/click/scroll/touch. Polling/mouse movement never extend idle.
  Safe name/login drafts remain account-bound in memory and require explicit
  restoration/review/resubmission. Account changes and logout clear them, including
  late unmount ownership. Credentials/codes/files clear after attempts and unmount;
  no automatic write replay or browser-storage drafts. Failed sign-out restores
  account preference hydration instead of leaving the active account in guest mode.
- Necessary focused diagnostics resolved new navigation/projection, request-epoch,
  draft, logout and activity uncertainties: frontend auth-entry/auth-account PASS
  (20 cases), revised account-boundary subset PASS (9 cases; three additional
  optional-feature regressions prepared for final selection), backend projection/
  named-page/welcome-policy subset PASS (3 cases, 44 assertions), and one Chromium
  account/dropdown render diagnostic PASS for dark desktop and light mobile.
  Inspected screenshots under artifacts/playwright/runa/results. Additional real
  navigation with both welcome settings, optional-feature and final cross-browser
  scenarios are prepared, not reported as passed. No full suite, static analysis,
  production/application build, scanner or quality gate ran. The isolated browser
  harness retains its existing large-chunk warning; production bundle acceptance
  remains final verification. Mandatory commit hooks remain required.
- Updated public Auth entry/account/profile/security/session/version and frontend
  component/form/layout/motion guides, security baseline and owner foundations.
  Owner/customer frontend references route to the shared account contract. Both
  root AGENTS products and backend/Laravel/PostgreSQL/security skills already mandate
  server authority, Audit, safe secrets and guarded contracts; this point changes
  no reusable policy in those products. Customer guidance preserves its app-owned
  composition and never permits protected platform edits.
- No Docker resource was created by these container-local diagnostics; test
  coordinator/browser processes close through their supported runner. Preserved
  development/test database data, services, images/networks/volumes, Qdrant/model
  caches, backups, reports and screenshots. No cleanup deletion was required.
  Real discoverable development accounts/manual end-to-end preparation remains
  point 17; Permissions/integration and full acceptance remain points 13–20.

## Point 13 delivery

- Added Core runa.permissions 0.1.0, requiring Auth ^0.10.0 and Audit ^0.4.0.
  Owned runa_permissions dictionaries have the five approved localized fields,
  immutable UUIDv7 public identities, version/timestamp/soft-delete columns, unique
  codes and protected source/system ownership. Assignment pivots have bigint
  identities, unique pairs, same-schema RESTRICT/RESTRICT keys and foreign Auth
  UUIDv7 references only. Explicit null -> 0.1.0 installation creates the protected
  unassigned system role; no administrator, credential or user grant is seeded.
- Reused ManagementProvider permission metadata and the existing management manifest
  category. Additive optional RoleProvider/RoleDefinition declarations describe
  same-owner predefined roles; namespace/type/duplicate/size/member validation
  refuses unsafe declarations. Framework API remains 1.0.0. Source removal retains
  definitions, memberships and user references; source-owned roles have no editable
  administration surface. No Admin UI or general management executor was added.
- Added source/state-bound declaration previews, old/new memberships and distinct
  impacted-user counts. The existing trusted module diagnostic exposes read-only
  previews; reserved install/update preparation applies them under the default
  transaction, registry/shared authority lock and stale-state recheck. Initial
  installation catches up registered source declarations. Per-definition Audit
  events and nested savepoints roll back all changes on required history failure;
  unchanged/repeated updates create no duplicate declaration events. Added explicit
  source-only Audit 0.3.0 -> 0.4.0 and Auth 0.9.0 -> 0.10.0 edges and central module
  changelogs, with dependent manifest constraints reconciled. Public APIs retain
  their existing versions; Auth adds its trusted non-recursive eligibility contract.
- Native authorization now resolves current direct/role unions and the protected
  system-role ordinary-action shortcut. It denies unknown permissions and preserves
  module/dependency readiness, maintenance, current Auth eligibility, independent
  HTTP sessions/factor requirements, step-up and domain predicates. The framework
  alone bridges Auth's public eligibility interface to its owning query; Permissions
  imports no foreign private model/query/SQL and never reenters a protected gateway
  while authorizing. Laravel namespaced Gate checks retain record-level policy
  decisions. No grant/eligibility cache survives a check, so cross-worker mutations
  take effect on the next request without logout or polling. Guarded Permissions
  1.0.0 introspection is permission-protected, code-cursor paginated and bounded,
  with batched direct/role/module reads and public IDs only.
- Prepared regressions for real fenced installation/no-op, direct/role derivation,
  cross-connection changes, native Gate/OperationGuard non-recursion, policy denial,
  system-role limits, default denial/readiness, protected introspection, source
  namespaces/duplicates, predefined membership impact/retirement, stale previews,
  Audit rollback and disabled Optional-module grant preservation/re-enable. Updated
  existing Auth/Audit fixture versions and exact Permissions cleanup for real Core
  updater tests. Fixtures do not add a production module or distributed credential.
- Necessary focused diagnostics used the supported owner test-backend entrypoint:
  `--filter='initializes current Permissions dictionaries'` passed (1 test,
  9 assertions), resolving first-install schema/upgrade construction and recursive
  authorization uncertainty. `--filter='unions direct and role grants|previews affected users'`
  passed (2 tests, 15 assertions), resolving batched introspection/cross-connection
  reads and source-owned membership preview/synchronization behavior. Full suites,
  broad acceptance, static analysis and scanners remain in final point 20.
- Synchronized public Permissions/module/database/command and affected Auth/Audit
  guidance, owner design/deferred-state descriptions, root/customer AGENTS and both
  backend/Laravel/PostgreSQL/security reference products. No frontend/Vue/Tailwind
  skill change is needed: this point supplies backend contracts without a new view,
  layout, style or frontend pattern. Existing owner/customer skill entrypoints
  already direct readers to the updated references; no duplicate procedure or new
  skill/dependency is introduced. Updated durable sources are staged/indexed before
  the mandatory commit hooks. Platform VERSION remains 0.4.2; feature 0.5.0 is still
  finalized only in point 20.
- No development data reset, application runtime configuration change or temporary
  Docker resource was needed. New schema execution occurred only in guarded
  disposable test databases, whose fixture-created module tables/schemas were
  removed; development/test service data, knowledge/model caches and reports remain
  preserved. Assignment/preset/copy mutations, last-superadmin participation and
  first-admin integration remain points 14–16; their fail-closed seams are unchanged.

## Point 14 delivery

- Permissions advances compatibly 0.1.0 -> 0.2.0 with additive guarded
  PermissionsAccess 1.0.0 and an explicit forward access edge. New owned preset
  dictionaries, preset-role/permission RESTRICT pivots and per-user monotonic
  assignment revisions use the existing transactional ModuleSchema updater.
  Auth ^0.10.0, Audit ^0.4.0, framework API 1.0.0 and platform VERSION 0.4.2 remain
  unchanged. No dependency or application runtime configuration change is added.
- Ordinary access management covers bounded public-reference reads/list cursors,
  versioned custom-role/preset creation/update/soft deletion, from-user presets,
  direct assignment add/replace and reviewed preset application/user-to-user copy.
  Roles/directs retain their assigned meaning; source system membership is excluded,
  recipient system membership survives ordinary replace and no effective grant is
  flattened into a direct assignment. Preset edits/deletion preserve recipients.
  Referenced custom roles refuse ordinary deletion; force deletion and dedicated
  superadmin assignment/removal remain point 15.
- Previews bind request, source/preset/target, versions, role memberships, target
  monotonic revision, current eligibility and practical availability-aware grant
  differences. Execution recomputes the complete preview under registry -> shared
  authority -> Auth proof/user -> owned data locks, rejects stale revisions and
  rechecks native authorization. Authorized managers may grant ordinary rights they
  do not hold. Public Auth eligibility lookup remains guarded; management requires
  its explicit read capability. Reference/member/effect bounds refuse explicitly.
- Every mutation uses real session/action-bound both-factor AuthStepUp proof consumed
  inside the same default PostgreSQL transaction. Aggregate old/new assignment/
  dictionary history and source provenance commit with pivots/revisions/proof;
  savepoints prevent partial writes even if an outer caller catches Audit failure.
  Query-builder pivots emit no automatic events, so there is no duplicate automatic
  history to suppress. Root refusals record independent access.refused Audit after
  rollback; nested consumers retain the public root-rollback/refusal obligation.
- Extended existing management metadata with custom-role/preset/assignment entity,
  dictionary/public-reference fields and revision/preview/both-factor declarations.
  Provider-local permission keys are validated through the existing management
  projection. No renderer, HTTP CRUD surface, administration page or frontend
  component is introduced. Existing native authorization keeps reading current
  committed grants on the next request without a cache/logout/polling requirement.
- Prepared regressions cover real proof/session authorization, grants beyond the
  actor's own, idempotent add, exact replace/protected membership, assigned-role
  versus direct copy, source/preset independence, membership-version staleness,
  assignment ABA, role/preset reference deletion, dictionary protections/bounds,
  independent refused history and aggregate Audit/proof rollback. Updated exact
  disposable storage cleanup and post-install test routing without weakening any
  production adapter. Additional prepared cases remain for final verification.
- Necessary focused diagnostics through the supported owner test-backend runner:
  --filter='applies reviewed ordinary access snapshots|refuses stale access previews'
  PASS (2 cases, 46 assertions). These resolve the public DTO/session/proof/Audit
  transaction and management-projection uncertainties. Initial fixture diagnostics
  exposed route registration after installation and an expected metadata ordering
  mismatch; fixtures now use isolated current guarded routes and sorted metadata.
  The final rollback diagnostic rejects only assignments.changed, allowing proof
  consumption and pivot writes before required aggregate Audit fails. No broad
  suites, concurrency/installation acceptance, static analysis, builds, scanners
  or final quality gates ran; those remain point 20. Mandatory commit hooks apply.
- Synchronized public Permissions access/API/security/database/module-foundation
  guidance, central changelog and owner current-state/deferred descriptions, plus
  both backend/Laravel/PostgreSQL/security instruction reference products. Root
  owner/customer AGENTS already require guarded public APIs, server authority and
  explicit aggregate Audit; their reusable rules remain sufficient without
  duplicating this API procedure. Frontend/Vue/Tailwind products need no change
  because this point adds no view, component, styling or frontend pattern.
- The migration was exercised only in guarded disposable owner-test storage; no
  development/production migration, reset or volume operation ran. Fixture-created
  module tables/schemas and Auth work were removed by the supported test lifecycle.
  No temporary Docker resource was created; development/test service data, existing
  images/networks/volumes, Qdrant/model caches, backups and reports are preserved.

## Point 15 delivery

- Permissions advances compatibly 0.2.0 -> 0.3.0 for additive guarded
  PermissionsAuthority 1.0.0, dedicated authority.manage / roles.force_delete
  declarations and source-only forward preparation. Auth advances PATCH
  0.10.0 -> 0.10.1 to correct neutral authority participation and root-refusal
  persistence; its public API signatures/data/policies are unchanged. Permissions
  now requires Auth ^0.10.1; Audit ^0.4.0, framework API 1.0.0 and platform VERSION
  0.4.2 remain unchanged. No dependency package, migration or runtime setting added.
- Dedicated reviewed superadministrator grant/removal binds target current identity,
  security/eligibility, protected role, exact membership and monotonic assignment
  revision. Prevents self-escalation by a nonmember, requires its separate current
  grant and fresh session/action-bound both-factor proof, and preserves ordinary
  copy/preset/replace exclusions. Complete authority membership/revision/proof/Audit
  changes commit together; no-op reviews create no duplicate membership history.
- The framework lazy ProtectedAuthority bridge validates exact default transaction
  connection and available source/data before resolving the trusted Permissions
  AuthorityGuard. Registry -> shared authority advisory -> owned Auth -> owned
  Permissions lock ordering serializes prospective removal and writes. Auth never
  imports Permissions models/SQL; the trusted participant consumes Auth's designated
  public non-recursive AuthorizationIdentity contract. Application callers retain
  guarded ingress. The invariant accepts a complete bulk exclusion set and requires
  another current eligible system-role member, with bounded-page survivor lookup.
- Auth deactivation/soft deletion, self-service, scheduled deactivation and protected
  assignment removal participate in the same invariant. Empty exclusion sets keep
  non-removing creation/activation/restore/profile/avatar/invitation work available;
  forced reverification/factor reset conservatively protect usable authority.
  Security/abuse locks, policy restrictions and time expiry remain independent and
  can temporarily remove every usable administrator. Documented schedule/expiry
  lockout consequences and trusted emergency recovery; actual operator authority,
  first-administrator installer and privileged bulk selection wiring remain point 16.
- Scheduled last-admin refusal rolls back only that account, persists independent
  authority.refused at the root and continues unrelated due accounts. Self-service
  root refusal persists security.refused after its proof/business rollback; direct
  lifecycle conflicts retain their independent identity refusal. Nested callers
  retain the root-rollback obligation; no savepoint claims independent persistence.
- Default referenced-role deletion remains refused. Dedicated force deletion requires
  a current version/review, extra explicit confirmation and fresh dedicated both-factor
  proof. Complete bounded affected-user assignment/revision/effective-permission
  effects and preset-reference/version lists bind execution; changed references,
  grants, eligibility or effective losses invalidate it. Explicit same-schema pivot
  cleanup advances affected assignment/preset revisions and soft-deletes only a
  custom role. Module/system definitions and RESTRICT FKs remain protected. Required
  assignment/preset/role Audit and proofs roll back with every owned write.
- Extended existing management metadata with the separate authority entity and
  force-delete confirmation/capabilities/preview/proof requirements. No new Admin UI,
  routes, renderer or generic authorization bypass. Existing current-grant resolver
  still enforces availability/eligibility and leaves record/domain/factor checks intact.
- Prepared meaningful regressions for dedicated proof/grants, self-escalation,
  last-member removal, multi-target exclusion, unavailable survivors, activation,
  independent refusals, full force-delete rollback and stale recipient changes,
  alternate effective grant preservation, scheduled progress, real self-service
  proof rollback, authority-history failure and two real concurrent lifecycle workers.
  The concurrency fixture uses guarded owner-test storage and the real shared lock;
  its parallel acceptance and remaining scenarios are reserved for final verification.
- Necessary focused diagnostics through supported test-backend: dedicated real
  authority proof plus confirmed force-role deletion/Audit failure PASS (2 cases,
  38 assertions); lifecycle/activation/trusted bridge and changed-module runtime
  descriptors PASS (1 case, 13 assertions); scheduled root-refusal persistence and
  continuation PASS (1 case, 3 assertions). Repeated the lifecycle diagnostic after
  bounded survivor lookup replaced full materialization; it passed. These answer
  concrete cross-module ingress/proof/transaction/runtime/lockout uncertainties.
  No broad suites, concurrency acceptance, static analysis, builds, scanners or
  final quality workflow ran; those remain point 20 after frontend acceptance.
- Supported source checks for runa modules and module_versions.py --owner runa
  --base HEAD passed. Read-only development --runtime initially reported all three
  modules not registered; no application registration/bootstrap was performed to
  manufacture a pass. The isolated lifecycle diagnostic instead validates actual
  registered Auth/Permissions runtime descriptors after supported fixture install.
- Synchronized public Permissions authority/access/API/security/module-foundation
  and Auth lifecycle/scheduled/recovery guidance, independent central changelogs,
  owner current-state/deferred descriptions and both backend/Laravel/PostgreSQL/
  security instruction reference products. Root owner/customer AGENTS already
  require guarded APIs, server authority and atomic meaningful Audit; no duplicate
  root procedure is needed. Frontend/Vue/Tailwind products are unaffected because
  there is no view/component/style or frontend extension change in this point.
- Only guarded disposable owner-test schemas/work were installed and removed through
  the supported fixture lifecycle. No development/production migration/reset or
  manual Docker resource operation ran. No temporary container/network/image/volume
  was created; active development/test data, existing services/images/volumes,
  Qdrant/model caches, backups, reports and rollback material remain preserved.

## Point 16 delivery

- Completed trusted first-administrator integration with the actual protected
  Permissions system role. Framework OperatorPermissions validates enabled exact
  Core source/data, default transaction and absence of update/settings fences,
  then invokes only Permissions-owned OperatorAuthority. Auth owns identity/token/
  singleton writes; Permissions owns membership and monotonic access revision.
  Registry -> authority -> Auth -> Permissions lock order and atomic mandatory
  Audit preserve one initial recipient under concurrent/repeated initialization.
  Ordinary role/copy/preset writers cannot grant this role.
- Native initialize supports existing private no-mail handoff and additive --invite
  with explicit confirmation. Invitation admission uses encrypted reference-only
  Auth work and existing POST redemption, invalidates unused private material and
  never emits tokens to stdout/argv/installer journals. Same-recipient live admission
  or completed password setup is idempotent; different recipients refuse. Expired
  or failed-attempt invitations can be explicitly reissued without another grant.
- Real production installer accepts optional administrator email/name/login, admits
  the initial invitation after application/TLS/SMTP acceptance in the actual release
  and records only safe admission in the existing journal. Failures remain resumable;
  crash/repeat after successful admission does not create duplicate role/history.
  Omission selects the documented trusted CLI setup path. Development/customer clones
  use the same local CLI; ordinary reopen, update and startup never grant authority.
  Extended native installation acceptance preparation with the actual invitation.
- Trusted recovery checks retained real system membership including inactive/locked
  administrators without ordinary grants, account eligibility or lifecycle guard.
  Existing disable switch, private single-use token, revocations, security-generation
  binding, required credential/factor enrollment and Audit stay authoritative.
  Missing/stale Core source/data or unresolved fencing still refuses; this does not
  repair source or bypass module update recovery. Scheduled last-admin refusal and
  continuation reuse the point-15 shared participant unchanged.
- Added guarded PermissionsSelections 1.0.0 / UserSelection and users.select native
  capability. One/selected unique UUIDv7 IDs (at most 100) or explicit all intent
  canonicalize without querying Auth storage. Neutral BulkUserSelection bridges
  Auth preview AND execution through that public API; independent Auth bulk grant,
  exact target/policy/security fingerprint, both-factor every-action proof and
  100-account/10000-row atomic limits still apply. Removed grants invalidate the
  next operation; a DTO is never durable authorization. No reverse required Auth
  dependency, foreign SQL/model/FK or copied identity store was introduced.
- Optional framework Audit actor enrichment snapshots trusted human display names
  through the designated non-recursive public Auth identity contract, bounded to
  160 UTF-8 bytes. Lazy resolution/failure preserves UUID-only attribution when Auth
  is unavailable. Audit retains no Auth/Permissions dependency; historical snapshots
  remain immutable. Guarded AuthIdentities includes deleted subjects for separately
  authorized historical lookup, never changes Audit records or exposes credentials.
- Completed safe version-1 management entities for immutable Audit summaries and
  Auth identities/lifecycle, bulk sessions and policy; refined Permissions custom
  dictionary controls. Additive ManagementField required/maxLength/choices/multiple
  metadata stays bounded/static, rejects invalid shapes and unknown schema versions,
  and leaves validation/security in backend contracts. References use public UUID
  lookups; policy configuration reuses ModuleSettings. No Admin page, renderer,
  management navigation, executable metadata or credential fields were added.
- Independent additive MINOR versions: Audit 0.4.0 -> 0.5.0, Auth 0.10.1 -> 0.11.0,
  Permissions 0.3.0 -> 0.4.0, each with an explicit source-only forward edge and
  central changelog. Auth requires Audit ^0.5.0; Permissions requires Auth ^0.11.0
  and Audit ^0.5.0. Initial null edges still own storage; source-only upgrades retain
  data, preparation synchronizes declarations/history only on meaningful changes,
  and exact installed-source repeat remains no-op. Reviewed existing reserved
  preparation/Audit adapters: fenced policy/declaration writes already use exact
  reservation/source/default connection, so no parallel bypass or new migration is
  needed. Framework API 1.0.0 and platform VERSION 0.4.2 remain unchanged.
- Prepared regressions for actual-role/Audit failure rollback, repeated/different
  initialization recipients, invitation resume after password setup, disabled and
  inactive/locked recovery without ordinary grants, selection grant removal and
  bulk proof/history rollback, safe descriptors/runtime contracts, deleted identity
  lookup/immutable history, missing Auth actor enrichment, and two real competing
  initialization workers. Existing fenced forward-update, scheduled authority and
  external upgrade/clone fixtures remain part of final acceptance. Refreshed isolated
  source-only fixture versions rather than leaving obsolete installed-version claims.
- Necessary focused diagnostics: actual-role initialization/recovery and native
  selection/bulk proof/history PASS (2 cases, 28 assertions after fixture repair);
  safe Core management/historical identity and all three installed runtime
  descriptors PASS (1 case, 16 assertions); optional unavailable Auth enrichment
  still permitting independent Audit PASS (1 case, 3 assertions). Two individually
  selected Python installer settings/admission-failure/resume contracts PASS.
  The first diagnostic exposed only fixture queue configuration and an invalid
  active+deleted setup; repaired those fixtures and received passing results.
  Supported runa:modules:check runa and module_versions.py --owner runa --base HEAD
  PASS. No full suites, real concurrency/installation acceptance, analysis, builds,
  scanners or final quality ran; they remain point 20 after appearance acceptance.
- Synchronized public operator, API/module foundations, management, security and
  production guidance, owner installer/current-delivery records and module changelogs.
  Updated owner and customer backend/Laravel/security reference products to route to
  delivered operator/selection contracts. Root AGENTS products already require guarded
  APIs, trusted CLI responsibility, atomic Audit and customer source protection and
  need no duplicate rule. Existing PostgreSQL references already cover the unchanged
  lock/transaction protocol; frontend/Vue/Tailwind products have no view/style/extension
  change in this point. Updated allowlisted knowledge incrementally before commit.
- Only guarded disposable owner-test tables/work were installed and removed through
  fixture lifecycle; no development/production migration/reset or Docker operation
  ran. No temporary container/network/image/volume was created. Existing development/
  test data, services, images/volumes, Qdrant/model caches, backups, reports and rollback
  resources remain preserved. Final frontend acceptance remains pending; point 17
  is next and point 20 must not start before the owner's explicit visual acceptance.

## Point 17 delivery

- Added explicit module-owned DevelopmentContributor registration, module-ID
  dependencies and owner-checked development ManagedSeeders on the existing
  SeedGraph/SeedContext foundation. `runa:seed:plan --modules` previews both graphs;
  `runa:seed:development --modules` explicitly opts into module data. Missing/cyclic
  contributors (including empty contributors), seed dependencies, category and
  owner violations refuse before seeder mutation. Native db:seed/default/production
  application ownership remains unchanged; loose seed files are not discovered.
- Added local Auth scenarios for real administrator, role/direct recipients,
  approval/verification, forced password change, email factor, tracked sessions,
  inactive/locked/expired/scheduled accounts and independently tuned extra users.
  Permissions adds reader role, direct grant, role/direct preset and independent
  extra roles through fixed public development contracts. Module boundaries use
  public UUID references, never foreign models/SQL/FKs or owner harness imports.
  No new UI, package dependency, global PRNG mutation or production demo input.
- Stable module-owned development_fixtures provenance binds scenarios and UUIDv7
  subjects atomically with owned writes, monotonic assignment revisions and explicit
  aggregate Audit. Registry -> authority -> Auth -> Permissions ordering serializes
  local preparation. Repeat skips completed scenarios and preserves changed private
  credentials, names/factors/grants and deleted-record history. Required deleted
  definitions refuse rather than revive. Mandatory Audit failure rolls back the
  complete owning contributor, including provenance and initial credential writes.
- `runa:seed:accounts` lists only local scenario identifiers/setup status;
  `<scenario> --setup` reads confirmed initial password through hidden interactive
  input with visible fallback disabled. No password option, source default, token,
  hash or factor secret is output. Existing credentials are never overwritten.
  Administrator creation/setup uses the existing real operator singleton/system
  membership/redemption contract and refuses unrelated prior initialization.
  Current password/verification/mandatory-factor rules and normal login remain
  authoritative; no session or durable proof is issued by setup.
- Each entry, including direct module methods and development ManagedSeeder runs,
  refuses non-console/non-local environments and production/unknown acquisition
  roles through the existing DevelopmentContext guard. A local APP_ENV or --force
  cannot override production provenance or configured production environment.
  Installed enabled exact module data/source, no fences and mandatory Audit remain
  prerequisites. Production forward upgrades create empty provenance storage only.
- Auth advances compatible MINOR 0.11.0 -> 0.12.0; Permissions advances compatible
  MINOR 0.4.0 -> 0.5.0 and requires Auth ^0.12.0. Each adds an explicit forward edge
  and owned ledger migration, with central changelog. Audit remains 0.5.0, framework
  API remains 1.0.0 and platform VERSION remains 0.4.2 until point 20.
- Prepared regressions cover private setup/listing, real authority, idempotence and
  preserved credentials/history, bounded incremental counts, foreign/missing/cyclic
  contributors, unrelated-account refusal, direct production receipt rejection,
  hidden CLI confirmation and aggregate/provenance/password Audit rollback.
  Refreshed exact disposable-schema inventories and isolated Auth fixture setup.
  Necessary focused diagnostics passed: real corpus/credential preservation plus
  count/adoption behavior (2 cases, 31 assertions); injected Audit failure after
  owned writes (1 case, 6 assertions); dependency/ownership and production receipt
  refusal; current Auth fenced initialization/runtime fixture (1 case, 4 assertions).
  Initial diagnostics caught a migration filename convention and the fixture's
  Throwable expectation; corrected both and received passing results. Source module
  checks and module_versions.py --owner runa --base HEAD passed. The hidden-console
  credential diagnostic also passed (1 case, 4 assertions), using installed Symfony
  CommandTester without an added mocking dependency; expanded empty-contributor
  cycle refusal passed within the focused dependency diagnostic. Contributor graph
  ordering retains transitive edges through empty intermediate contributors.
  No broad suite, integration/concurrency/install acceptance, analysis, build,
  scanner or final quality workflow ran. Mandatory commit hooks apply.
- Synchronized the authoritative public seeding/contributor/private-setup/manual
  welcome/sign-in/application/profile/dropdown-sign-out procedure, independent dev/
  test inputs, commands, module/database/framework/operator guidance, root README
  and owner foundations. Both backend/Laravel/PostgreSQL/security reference products
  route to this contract. Root owner/customer AGENTS already enforce source owners,
  supported commands, server security and atomic Audit; no duplicate root rule is
  needed. Frontend/Vue/Tailwind products are unchanged because this point adds no
  view/component/style/customization seam; point 19 owns those contracts.
- Only guarded disposable owner-test schemas/work were installed and removed by
  fixture lifecycle. No live developer/production database initialization, migration,
  reset or volume operation ran; live accounts are created through the documented
  explicit local command after normal forward initialization. No temporary Docker
  container/network/image/volume was created. Development/test data, existing
  services/images/volumes, Qdrant/model caches, backups and reports are preserved.
  Point 18 is next; point 20 still requires explicit owner appearance acceptance.

## Point 18 delivery

- Reviewed requirements against actual public contracts and prepared meaningful
  coverage in the [Core acceptance map](../../quality/core-module-acceptance.md).
  It distinguishes real PostgreSQL/HTTP/worker evidence from intercepted rendered
  fixtures and maps Audit, Auth identity/security/state, Permissions, integration,
  customer delivery and development corpus scenarios to their owning tests.
  Point 19 still owns replacement/customization/native-error/minimal-scaffold
  coverage; point 20 owns complete execution after explicit appearance acceptance.
- Corrected stale missing-Auth-UI, unbound-operator/bootstrap and deferred-protected-
  authority claims in public commands, module/framework/Auth/Audit/frontend mail
  guidance and owner foundations. Replaced obsolete deferred registry entries with
  the actual remaining Admin/data-management/Filesystem/Logs/Notifications/
  Throttling/Teams/Import/external-provider/WebAuthn scopes. Root README now links
  the supplied Core capabilities. Default application seeds remain empty while
  explicit module fixtures are available. Historical records remain unchanged.
- Reviewed root owner/customer AGENTS and backend/Laravel/PostgreSQL/security/
  frontend products and relevant references. Both roots already require manual
  bulk/pivot/aggregate Audit, current native grants, supported commands and public
  module boundaries; no duplicated root rule is needed. PostgreSQL products already
  carry the five-field dictionary rule; backend/security/frontend already route
  preferences to its authoritative contract. Updated public page acceptance and
  customer frontend routing guidance; owner frontend references already link to
  the public page guide. No Vue/Tailwind component, syntax or style contract changes
  occur here; their customization work remains point 19.
- Extended authored customer acquisition/sparse-reapply/teammate-clone and reviewed
  update scenarios to cover preferences, operator/authority, seeding and frontend
  guidance while preserving customer root/domain extensions. Prepared all configured
  browser projects for login/profile keyboard and responsive desktop/tablet/phone
  in both themes, with project-isolated screenshot paths; existing entry scenarios
  cover PL/EN, reduced motion and private fragments. Removed the old Chromium-only
  render diagnostic skip. Broad browser/delivery acceptance was not executed.
- Added real installed-Core CSRF/login/profile/logout coverage with actual authority
  binding, no assigned role and no authentication/permission mock. A necessary
  focused diagnostic resolved route setup after explicit fixture initialization:
  rebuild source-bound module routes once while retaining application routes.
  Initial setup attempts caught duplicate route registration and missing Auth routes;
  corrected fixture wiring, then the case passed: 1 test, 17 assertions. Native help
  for initialize/accounts confirms invitation versus private-file options and hidden
  initial setup. No broad suite, build, scanner or final workflow ran.
- Reviewed manifests, compatible requirements, changelogs and forward/no-op edges:
  Audit 0.5.0, Auth 0.12.0, Permissions 0.5.0, framework API 1.0.0. Guidance and
  owner-test changes alter no shipped runtime/API/schema or dependency, so no module
  version/changelog increment is required. Platform VERSION remains 0.4.2.
- Only guarded disposable owner-test Core schemas were created and removed by the
  existing fixture lifecycle. No live development/production initialization/reset,
  migration, Docker resource or volume change occurred. Existing development/test
  data, services/images/volumes, Qdrant/model caches, backups and reports are preserved.
  Mandatory hooks and staged incremental knowledge freshness apply to this commit.

## Point 19 delivery

- Established the general current/future Runa view rule, reaffirmed by the owner:
  ship usable defaults plus documented public presentation replacement/composition.
  Support unchanged screens, application composition and complete customer frontend
  ownership. Keep platform defaults/controllers in src/runa and customer files in
  src/app. No universal renderer/registry, new example module, business dashboard,
  Admin UI or dependency was introduced.
- Added public AuthPresentationProvider with typed subtree overrides for entry,
  account, welcome and minimal/application layouts, inherited defaults and no
  global registration. Public entry/account/welcome/session controllers retain the
  existing CSRF/restrictions/proof, fragment/secret cleanup, current capabilities,
  account fencing and explicit retries. Default Auth wrappers key page/account
  lifetimes. AuthApplication supplies a current-session content slot, remounts its
  content on account change and retains dropdown/profile/sign-out, expiry warnings,
  failed-session handling and reauthentication around custom content. Backend
  security, availability, authorization, recovery and Audit remain authoritative.
- Moved default path composition into public DefaultApplication and public identity
  hydration into createApplication; shared CSS and request/ApiError transport now
  have supported facade entrypoints. App.vue/main.ts are thin integration wiring.
  Removed the redundant App SpaController and wired the existing named welcome
  route directly to the public platform controller. Retained manifest/dependencies,
  HTML, providers/configuration/routes, database/test/translation extension markers
  and selected reference module after inventory review. Existing welcome/account
  visuals and essential application extension entrypoints remain supplied.
- Native HTTP errors and pre-generated emergency 503 select one optional customer
  errorLayout.ts SSR component, falling back to the actual shared MinimalLayout.
  Added typed static error props and public default layout/content, common safe
  status/copy/identity/actions/request placeholders and CSS emission. Standalone
  generation merges non-secret app branding without Laravel/env/database access.
  Executable HTML rejection now covers case-insensitive script/style/event
  attributes. Existing status/JSON/headers/CSP and escaped runtime substitutions
  remain shared; no parallel PHP/Blade application UI or independent renderer.
- Auth advances additive MINOR 0.12.0 -> 0.13.0 with an explicit source-only forward
  edge. Permissions advances PATCH 0.5.0 -> 0.5.1 solely to align its required Auth
  constraint to ^0.13.0 (pre-1.0 caret ^0.12.0 excludes the new minor). Reviewed
  their existing public backend contracts/dependents; no storage/settings/data
  change. Audit stays 0.5.0, framework API stays 1.0.0 and platform VERSION stays
  0.4.2 until release finalization. Both central module changelogs are synchronized.
- Prepared owner-only custom layout/entry/account/full-shell/static-error consumers,
  default/composed/replacement session and capability regressions, responsive/theme/
  reduced-motion browser scenarios, native/generated customer error installation
  acceptance and exact minimal-scaffold/acquisition/reapply/update/clone coverage.
  Customer application files are preserved without updater content inspection;
  reviewed exact file actions concern eligible delivered files/instruction products.
- Necessary focused diagnostics PASS: six selected browser unit cases covering
  public custom composition, CSRF refusal/explicit retry/required verification,
  session-401 content removal, account capabilities and static errors; isolated
  server rendering of the public customer error wrapper without browser globals
  or executable HTML; two individually selected Python scaffold/update-ownership
  contracts; named platform/application composition (1 case, 10 assertions); all
  three source module checks and module_versions.py --owner runa --base HEAD.
  Initial diagnostics exposed fixture-only async-root handling, an invalid
  restriction identifier, an awaited reauthentication render and omitted essential
  dotfile markers; corrected fixtures and received passing results. The module
  graph diagnostic identified the required Permissions constraint alignment above.
  The SSR diagnostic disabled irrelevant whole-workspace dependency discovery.
  No broad suite, build/typecheck/analysis, image/scanner, installation/update
  acceptance or final quality workflow ran. Mandatory commit hooks still apply.
- Synchronized the public adoption/controller/error/update contract, frontend
  architecture, component/page/module/Auth/API guidance, README, owner foundations
  and acceptance map. Updated both owner/customer AGENTS and frontend/Vue/Tailwind
  skills/references plus security references for all future module views, not Auth
  alone. Backend/Laravel/PostgreSQL skills already route to public module contracts
  and unchanged backend/storage authority; no duplicate rule is needed there.
  Customer authoring files remain excluded from owner retrieval; staged allowlisted
  guidance is indexed incrementally before commit.
- No live database initialization/migration/reset, Docker operation or temporary
  container/network/image/volume was created. Existing development/test data,
  services/images/volumes, Qdrant/model/build caches, backups, rollback resources
  and verification reports (including diagnostic failure screenshots) are preserved.
  No task-created Docker cleanup is needed.
- STOP before point 20: points 1–19 are ready and committed together with their
  point records. Owner manual frontend inspection and explicit appearance
  acceptance are still pending. Do not begin full verification/release finalization
  until that acceptance; implementation completion and silence do not grant it.

## Point 19a delivery

- Owner requested one workspace-root .runtime location after noticing both root
  and src variants. Inspection found general tool/cache/report/operation state in
  root .runtime (about 2.3 GB) and only generated error renderer/templates in
  src/.runtime (about 1.4 MB). The latter came from package commands running in src.
- Consolidated private error build artifacts under .runtime/frontend/errors:
  renderer and templates have separate owned subdirectories. Vite resolves its
  output from the config location, both composed/platform package scripts use the
  root output, and SSR generation/native PHP/standalone finalization agree on the
  workspace location. Explicit Node -> PHP build -> production PHP copies follow
  the new path; only required HTML enters the runtime image. Public CSS/assets,
  Laravel storage and supported verification-evidence locations remain unchanged.
- Moved exactly five existing renderer files (465764 bytes) and 72 existing
  templates (814980 bytes) to absent destinations; all 77 per-file SHA256 hashes
  matched after relocation. Removed only the now-empty src/.runtime directory.
  Preserved every unrelated root runtime subtree, including older artifacts,
  caches, operation/recovery state, reports and backups. No blanket cleanup or
  overwrite was performed. Removed obsolete ignore entries for src/.runtime;
  the canonical root remains Git/Docker-ignored and builds generate fresh output.
- Necessary focused diagnostics PASS: imported Vite config and both package
  entrypoints resolve the canonical root; the supported owner native-error case
  (12 status/locale variants, 104 assertions) reads relocated templates and retains
  responses/headers; the documented standalone PHP finalizer emits all six locale/
  theme 503 documents without recreating src/.runtime. Retained isolated finalizer
  evidence under .runtime/tests/error-artifact-relocation. Existing native-error,
  browser, clean image and production-fallback regressions remain in final point
  20; no broad suite, frontend/image build, scanner or final quality was run.
- Updated authoritative artifact placement, frontend/design/production and owner
  architecture/update guidance, both owner/customer AGENTS, Docker and frontend
  reference products. This is a reusable placement rule for all future tooling,
  not an Auth-only convention. Other skills already inherit the root placement
  contract and need no duplicate rule. No module-owned implementation/API/storage
  contract changed, so Audit 0.5.0/Auth 0.13.0/Permissions 0.5.1 and platform VERSION
  0.4.2 remain unchanged. No dependency or migration was introduced.
- Mandatory commit hooks and incremental staged knowledge freshness apply. No
  Docker container/network/image/volume was created or removed; development/test
  data/services, Qdrant/model/build caches, backups, rollback resources and reports
  are preserved. Point 20 remains open and cannot start before explicit owner
  appearance acceptance; this cleanup does not grant that acceptance.

## Point 19b delivery

- Diagnosed actual local development environment (APP_ENV local, PostgreSQL runa
  on service postgres): Core source had never been synchronized/installed and the
  served frontend was stale. Auth entry-options returned a real 404; this was not
  optional Core enablement or a missing welcome implementation. No reset needed.
- Executed the documented forward platform primitive at exact source HEAD,
  retaining existing tables/data and installing Audit 0.5.0, Auth 0.13.0 and
  Permissions 0.5.1 with their existing transactional migrations/upgrade handlers.
  All three are now registered, enabled and installed at source version. Ran the
  explicit module development seed graph (Auth then Permissions); created canonical
  local scenarios/extra records, fixture provenance and actual protected authority.
- Initialized only the administrator's unset password through supported hidden
  interactive runa:seed:accounts administrator --setup, with generated private
  material and confirmation, no argv/source/log/default credential or echo.
  Private local material is mode 0600 in the ignored manual-auth operational
  directory; no password is recorded here. Confirmed protected role through the
  existing authority adapter inside its required transaction. Other accounts keep
  unset credentials; repeats preserve the administrator password.
- Enabled public_registration only in this local development database through the
  supported revision/generation-checked audited settings command; source/production
  defaults remain false. Public welcome remains enabled. Guest now has both login
  and conditional registration entry links; no UI/security bypass was introduced.
- Published actual frontend assets with Vite and rebuilt/finalized shared error
  assets for this explicit operational readiness task. Error rebuild exposed a
  relocation issue: the SSR entry outside src/node_modules could not resolve Vue.
  Set SSR noExternal so the private build renderer is self-contained; rebuilding
  and standalone PHP finalization then passed. Renderer bundles still never enter
  production runtime images. This changes no module-owned contract/version.
- Focused real Chromium/Nginx/PHP/PostgreSQL manual-path diagnostic passed without
  API interception: visible guest login/registration, seeded administrator login,
  unrestricted session 200, application entry, dropdown profile, account form,
  dropdown logout, guest welcome and session 401; zero page errors. Initial probes
  were repaired for async navigation and exact dropdown scope, and the registration
  probe reran after the audited local setting change. Readiness/safe screenshots
  remain under .runtime/dev/manual-auth. This is not broad E2E/installation/security
  acceptance or owner visual acceptance. No full quality, typecheck/static-analysis,
  image build or scanner ran; mandatory commit hooks apply.
- Synchronized public seeding/manual handover, renderer-placement guidance and both
  frontend instruction reference products. Existing owner/customer AGENTS and
  backend/Laravel/PostgreSQL/security rules already require supported operations,
  private credentials, current guards and preserved data; no duplicate rule needed.
  Platform VERSION and all module versions remain unchanged.
- No temporary Docker resources were created or removed. Live development data is
  forward-prepared and seeded as requested; all prior data, services, test database,
  caches, backups, reports and rollback resources were preserved. Point 20 remains
  open and awaits the owner's explicit acceptance of frontend appearance.

## Point 19c delivery

- Applied owner's visual correction: welcome sign-in/conditional registration and
  authenticated application entry now occupy the default header beside locale and
  theme controls. Added public MinimalLayout header-actions slot with translator t;
  default branding/preferences remain shared and action groups wrap on narrow widths.
  Loading/failure/retry follows the same header location; content keeps its heading
  and description. Custom layout fixture forwards the new slot.
- Rendered inspection exposed LinkButton borrowing a class whose Button styles
  were scoped to another component. Extracted one button.css source and used scoped
  imports in Button/LinkButton so links receive existing variants, theme/focus/
  hover/reduced-motion behavior without mounting another button. No duplicate CSS
  source, independent control implementation or new motion pattern was introduced.
- Auth compatible PATCH 0.13.0 -> 0.13.1, source-only welcome_header edge and central
  changelog. Existing Permissions ^0.13.0 remains compatible; Permissions 0.5.1,
  Audit 0.5.0, framework API 1.0.0 and platform VERSION 0.4.2 remain unchanged.
  Applied the explicit forward module edge locally, preserving credentials/data.
- Selected welcome registration/session header regression passed (1 case). Published
  current client and static error assets for continued manual review. Actual served
  Chromium welcome passed at 1440/390 widths in light/dark: header links, no content
  duplicates, 44px styled link controls and no horizontal overflow; initial probes
  also confirmed no page errors. Inspected desktop/mobile screenshots retained in
  .runtime/dev/manual-auth/header. The style diagnostic accounts for normal flex
  item display blockification. Module-version check passed. No broad suites, full
  typecheck/static-analysis, image/scanner or final quality acceptance ran.
- Synchronized frontend architecture, component/page/customization contract and both
  owner/customer frontend references. General root public-presentation rules and
  existing Vue/Tailwind style/lifecycle guidance already apply; no duplicate root
  rule is needed. The shared MinimalLayout default still serves Auth/errors.
- No Docker resources created/removed, database reset or credential change.
  Existing data, caches, reports, services, backups and rollback material preserved.
  Point 20 remains gated by explicit owner frontend appearance acceptance.

## Point 19d delivery

- Owner retained header location but rejected prominent button presentation. Replaced
  welcome entry LinkButton instances with local native text anchors: muted text,
  no surface/border, 16px spacing, hover underline/primary text, visible focus and
  44px touch area. Signed-in application entry follows the same presentation;
  registration policy and shared locale/theme controls remain unchanged.
- Small color transition uses the accepted 160ms control rhythm and disables under
  reduced motion. No shared Button/LinkButton behavior/style contract changed.
  Updated page/customization and owner/customer frontend references with the owner's
  reusable welcome-link visual direction; existing Vue/Tailwind/root guidance applies.
- Auth PATCH 0.13.1 -> 0.13.2 with source-only edge and central changelog; existing
  Permissions ^0.13.0 remains compatible. Applied local forward edge without data/
  credential changes and published current Vite assets for continued manual review.
- Actual served Chromium desktop 1440 and phone 390 in both themes passed: native
  header guest links, transparent background/zero border, 44px touch target, visible
  focused outline and no overflow. Inspected rendered desktop and retained images
  under .runtime/dev/manual-auth/header-text. Module version check passed. No broad
  suites/static analysis/image build/scanner/final quality ran; hooks still apply.
- No temporary Docker resource, reset or credential change. Data, services,
  caches, backups, rollback material and reports preserved. Point 20 still awaits
  explicit owner frontend appearance acceptance.

## Point 19e delivery

- Owner reported a selection-like rectangle on every Auth heading. The global
  :focus-visible rule overrode the heading utility while the flow intentionally
  focused its tabindex=-1 reading target. Added a narrowly scoped auth-heading:focus
  exception in supplied entry/account views. Programmatic focus/announcements and
  control/error-feedback indicators remain intact; no global focus rule disabled.
- Auth PATCH 0.13.2 -> 0.13.3, source-only heading_focus edge and central changelog.
  Existing Permissions ^0.13.0 remains compatible. Published current client assets
  and applied the local forward edge without schema/settings/credential changes.
- Focused real browser inspection of login/register/forgot-password confirms the
  heading remains active without outline and keyboard-mode text fields retain
  their visible indicators. The probe explicitly selects a text field on the
  registration page (its first Tab target is a radio input with wrapper feedback),
  recognizes shared input inset-shadow indicators and waits for their transition.
  No broad suite/static-analysis/image/scanner/final quality ran; hooks apply.
- Updated entry/account guidance and both frontend instruction reference products:
  reading-heading focus differs from interactive controls, whose visible keyboard
  indicators remain required. Existing root/Vue/Tailwind rules already apply.
- No Docker resources, resets or credential changes. Existing data/services/caches/
  reports/backups/rollback resources preserved. Point 20 remains appearance-gated.

## Point 19f delivery

- Owner requests outlined form submits and navigation matching quiet welcome links.
  Entry login/register/recovery/security submits and account/profile/security/
  delivery-preference saves now explicitly use outline, preserving busy/disabled
  states and danger tone. Related entry confirmation actions also use outline;
  active account tabs retain their independent selected state. Existing neutral
  static GET preference/error controls are unchanged.
- Extracted the second use of quiet navigation into public TextLink/AppTextLink;
  welcome and all entry form navigation now share native href semantics, muted
  12px text, no permanent underline/surface/border, hover underline/primary text,
  visible focus, 44px touch target and reduced-motion behavior. Removed both
  page-local link styles. Shared Button/LinkButton contracts remain unchanged.
- Auth PATCH 0.13.3 -> 0.13.4, source-only form_presentation edge and changelog;
  existing Permissions ^0.13.0 remains compatible. Published current Vite assets
  and applied local forward preparation without schema/settings/password changes.
  Initial compilation caught multiline anchor closing tags during component
  extraction; corrected tags and received a passing build before review.
- Focused real served browser diagnostics passed login/register/forgot at 1440/390
  widths in light/dark: outline submits, no permanent navigation underlines, hover
  underline and no overflow. Inspected rendered login screenshot; retained safe
  screenshots in .runtime/dev/manual-auth/form-style. Selected welcome conditional
  registration/session header regression passed (1 case). Module versions passed.
  No broad suite/static-analysis/image/scanner/final quality ran; hooks apply.
- Synchronized public component/form guidance and both frontend reference products
  with outlined submits and shared TextLink use for future forms. Root/Vue/Tailwind
  guidance already requires public component reuse and current patterns; no duplicate
  rule needed. No Docker resources/reset/credential changes; preserved all existing
  data, services, caches, reports, backups and rollback material. Point 20 remains
  gated by explicit owner appearance acceptance.

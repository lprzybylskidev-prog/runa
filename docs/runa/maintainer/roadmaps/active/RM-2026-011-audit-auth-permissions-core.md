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

- 2026-10-25 — Owner authorizes targeted Auth throttling instead of a blanket
  unsafe-method cap, removal of redundant preference reads, and a narrow optional
  Throttling handoff. Point 19zw retains intrinsic Auth proof/credential safeguards;
  an active future adapter owns request throttling exclusively, and unavailable
  integration restores the targeted local fallback. The general module remains
  deferred; this is a correction within confirmed feature 0.5.0.

- 2026-10-25 — Owner removes the redundant account dropdown caption and requests
  warning deactivation and error/danger deletion colors. Point 19zv preserves the
  existing outlined action design and behavior in feature 0.5.0.

- 2026-10-25 — Owner requires disabled welcome to remove Home from breadcrumbs
  and send brand links to /app, plus visibly pulsing avatar loading matching content
  skeletons. Point 19zu corrects both within the confirmed feature 0.5.0 release.

- 2026-10-25 — Owner requests consistent lowercase `runa.` ApplicationLayout
  branding, avatar loading feedback through profile/image discovery in header and
  dropdown, and breadcrumbs on /app and /account. Point 19zt keeps the existing
  feature 0.5.0 scope and shared public component/presentation contracts.

- 2026-10-25 — Owner requests a complete welcome introduction: `.runa`, concise
  localized Laravel/modular-monolith copy, brand-style module icons and localized
  name/description tooltips. Point 19zs also establishes the new-module welcome
  maintenance requirement. Included in the existing confirmed feature 0.5.0 scope.

- 2026-10-25 — Owner authorizes automatic scoped test-resource lifecycle and
  cleanup of the audited existing database/container residue. Point 19zr preserves
  the base test database, development data, caches, reports and other projects;
  worker databases are disposable. Supported runners clean workers on success or
  failure and recover inactive orphans under the suite lock. Browser startup owns
  only its newly created testing containers; manually provisioned endpoints remain
  externally owned. Extend owner/customer guidance in the same point. This is a
  tooling correction within the confirmed feature 0.5.0 release.

- 2026-10-25 — Owner refines illustration motion: animate only one randomly
  chosen point at a time. Point 19zq preserves the existing complete lap/pulse
  timing and excludes immediately repeating the preceding point.

- 2026-10-25 — Owner accepts sparse background glow and requests removal of the
  all-point preview. Point 19zp restores randomness and adds occasional full laps
  with gentle pulsation for the logo illustration points, returning to their
  starting positions. Both effects remain exclusive to interactive MinimalLayout.

- 2026-10-25 — Owner requests a temporary visibility preview: every constellation
  point pulses together every three seconds, exclusively in MinimalLayout. Point
  19zo retains the sparse baseline behind the internal preview switch.

- 2026-10-25 — Owner requests rare randomized subtle constellation-point glows
  exclusively in MinimalLayout. Point 19zn opts that layout into the shared
  backdrop effect; ApplicationLayout retains the same static backdrop.

- 2026-10-25 — Owner requests shared loading feedback for table query changes:
  pagination/page size, sorting, search and filters. Point 19zm adds a 140ms
  presentation interval using the existing motion rhythm, without delaying local
  processing or server requests. External/provider reads may keep it visible longer.

- 2026-10-25 — Owner reports a second table-loading cycle after data appears
  and requests a centered spinner without visible loading text. Point 19zl removes
  the duplicated tab-entry read and adjusts shared Table presentation.

- 2026-10-25 — Owner requests a spinner while table data loads; empty results
  must appear only after reads finish. Point 19zk wires external client reads into
  the existing shared Table loading presentation.

- 2026-10-25 — Owner requests the destination shell immediately and structural
  skeletons in place of initial layout/spinner flashes. Point 19zj supplies the
  account Cards and notification rows using the existing Skeleton. A universal
  future-view rule awaits explicit owner approval of the rendered result.

- 2026-10-25 — Owner requests inline secondary-email status badges beside the
  address rather than below it. Point 19zh preserves wrapping on narrow screens.

- 2026-10-25 — Owner requests removing the trusted-device subsection title from
  the two-factor Card. Point 19zg retains actions and dialog titles unchanged.

- 2026-10-25 — Owner requests removing separators above form submits in the user
  panel and Preferences. Point 19zf fixes the shared Form actions presentation;
  retain spacing and separators between independent sections. No behavior change.

- 2026-10-25 — Owner requests concise account/password copy, centered avatar and
  trusted-device management only while an active trusted device exists. Point 19zd
  removes the named helper/status text in PL/EN and reuses the guarded active-device
  list for visibility. Owner confirms expired/missing devices need no management
  button; no historical marker, schema or security-policy change is needed.

- 2026-10-25 — Owner approves automatic read refresh across current account views,
  preservation of dirty drafts, shared revisions for own preference saves and
  form-local conflict decisions only for overlapping changes. Point 19zc supplies
  shared serialization, lifecycle-fenced refresh on visible return/tab changes and
  conflict recovery without replaying writes or resetting the page; synchronize
  owner/customer skills and public contracts. No polling or security-policy change.

- 2026-10-24 — Owner requests stacked Preferences Cards, removal of theme/save
  helper copy and the Always enabled summary, and actual multi-address recipient
  selection. Point 19zb repairs only this presentation; mandatory security delivery
  stays server-owned and unchanged. Final point 20 remains appearance-gated.

- 2026-10-24 — Owner approves creation-roadmap module stabilization: develop as
  0.x.y, then release Audit, Auth and Permissions as 1.0.0 at verified closure of
  this roadmap. Later compatible features use MINOR and corrections PATCH;
  incompatible MAJOR changes require explicit owner approval before implementation,
  independent of internal rewrite size. Platform feature 0.5.0 is unchanged.
  Point 19za records the rule; point 20 prepares stable versions, dependency
  constraints and install/update edges without bypassing its appearance gate.

- 2026-10-24 — Owner requests a Preferences header reset action, dedicated explicit
  language/theme choices in this view and a more direct email editor. Point 19z
  replaces cycling controls only in Preferences, retaining shared cover/persistence;
  optional delivery uses independent inline drafts, switches and recipient selection
  with explicit per-category Save/Revert. Keep shell controls and security/account
  views unchanged. Clarify whether reset should additionally include delivery;
  do not silently expand the existing presentation-only reset boundary.
  Owner subsequently confirms email delivery must reset too: add a distinct atomic
  reset_all operation with one account revision and manual Audit; preserve legacy
  reset APIs and introduce no schema/dependency change.

- 2026-10-24 — Owner limits the next visual revision to Preferences, places its
  navigation beside Account and security and requests a complete presentation
  rebuild using the accepted independent Card/shared-dialog patterns. Point 19y
  groups display/language, email delivery and default reset; preserve existing
  persistence, mandatory primary-only security delivery, explicit revisions and
  confirmation. Publish assets for owner review, without inferring final acceptance.

- 2026-10-24 — Owner requests moving trusted-device management into Two-factor
  authentication and allowing new trust only after a usable factor is enrolled.
  Point 19x moves all factor and trust actions into shared dialogs, preserves
  fresh server factor proof and existing authorization/Audit, clears private modal
  drafts on cancel, and republishes the local client for continued visual review.
  Keep legacy custom-presentation controller seams, add independent trusted-device
  state/actions and review module/dependent versions. Final point 20 remains gated.

- 2026-10-24 — Owner requests the concise End session label and global column
  sorting. Point 19w makes data columns sortable by default with explicit false
  opt-out, preserves server capability allowlists and the existing three-state
  header cycle, adds chronological account-date ordering and republishes local
  assets for manual review. Focused regressions/render inspection answer these
  uncertainties; final verification/appearance acceptance remain gated at point 20.

- 2026-10-24 — Owner requests relocating root browser artifacts, pnpm store and
  Python bytecode into the workspace-root .runtime tree. Point 19v updates all
  live browser consumers, workspace pnpm store configuration and development
  Python cache environment; preserve existing evidence/cache bytes and installed
  dependency links. Existing processes need environment activation; do not disrupt
  the manual-review environment or start final point 20. Feature 0.5.0 unchanged.

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
0.5.0 does not automatically become each module's version. The owner-approved
creation-roadmap policy delivers Audit, Auth and Permissions as 1.0.0 at verified
closure; reconcile manifests, central changelogs, required constraints and explicit
upgrade/install edges in point 20. Until then their development versions remain
0.x.y. Keep the existing
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
- [x] 19g. Empty the default application and refine the user-panel dropdown.
  - Owner requests empty /app without heading/welcome content or account sidebar
    entry, self-service named Panel użytkownika/User panel and a polished dropdown.
  - Preserve session/security feedback, public custom content slot and profile/
    sign-out behavior; publish and inspect actual application/menu/panel states.
    Synchronize module version, coverage and guidance; final appearance gate stays.
- [x] 19h. Prevent preference writes during initial view hydration.
  - Owner reported transient preference-error feedback on each authenticated page.
    Apply initial locale/theme to the document without persistence; retain writes
    for real changes, hydration fencing and explicit error feedback.
  - Prepare focused delayed-hydration/remount/change regressions, publish assets
    and verify real authenticated navigation does not flash errors or write on read.
    Synchronize public/owner/customer guidance and commit; point 20 stays gated.
- [x] 19i. Cancel departing-document requests without false error feedback.
  - Owner reports remaining flashes on the old document. Firefox reproduction
    confirms beforeunload cancels preference/profile/security/activity fetches before
    pagehide, producing TypeError and false errors. Add explicit document-request
    cancellation classification, preserve real errors and no write replay, verify
    canceled navigation/resumed document behavior and publish assets for review.
  - Synchronize public HTTP/Auth lifecycle guidance, versions and meaningful focused
    cancellation regressions; no final point 20 or appearance approval inferred.
- [x] 19j. Consolidate the user panel account editor.
  - Owner requests one visible editor for identity, avatar, password and two-factor
    verification, without task-switch buttons; use the existing fluid shell width.
    Keep sessions and preferences unchanged for their separate manual review.
  - Retain capability/proof/version boundaries, explicit enrollment/recovery steps
    and honest partial-save feedback; synchronize public/customer guidance and
    prepare focused editor diagnostics plus rendered desktop/mobile themed review.
- [x] 19k. Separate account card submissions and prepare cropped avatars.
  - Owner requests four existing Card compositions with independent submits for
    identity, avatar, password and verification, plus shared success feedback.
  - Diagnose real avatar upload, accept bounded larger local originals and provide
    accessible square cropping before upload without new dependencies. Keep server
    image/storage/security bounds and sessions/preferences behavior intact.
  - Synchronize public and owner/customer patterns, module metadata and focused
    checks; publish and inspect real desktop/mobile themed states before commit.
- [x] 19l. Replace inline avatar sliders with modal direct manipulation.
  - Owner requests shared Dialog on file selection, a dimmed surrounding image
    and circular avatar preview, pointer dragging and wheel zoom without sliders.
  - Preserve keyboard/touch access, cancel/confirm without implicit upload, public
    crop export and bounded-file cleanup; inspect rendered modal and focused tests.
- [x] 19m. Unify operation feedback in top-right notifications.
  - Owner requests successes and form-wide failures in one top-right location,
    without duplicate banners above forms. Keep associated field corrections.
  - Reuse public notification presentation with persistent actionable failures,
    shared account/entry/avatar/preference consumers, modal accessibility and docs.
- [x] 19n. Display current account avatar throughout the application shell.
  - Owner reports saved avatar absent from top menu and dropdown. Wire guarded
    current profile presentation into both, including immediate save/removal updates.
  - Reuse shared avatar with initials/error fallback, preserve identity fencing,
    public customization and no polling; publish and inspect focused cases.
- [x] 19o. Center notification text.
  - Owner requests centered text in shared top-right notifications. Keep icon,
    dismissal and recovery actions usable; publish and inspect the rendered result.
- [x] 19p. Remove the shell avatar frame.
  - Owner requests no pixelated-looking border around the top-bar avatar.
    Preserve its circular image, touch target and visible keyboard focus.
- [x] 19q. Polish account card flow, confirm avatar removal, expose clear two-factor method controls and enable local full-feature manual review.
- [x] 19r. Add private local TOTP QR enrollment alongside manual keys in account and required enrollment views.
- [x] 19s. Align alert icons, move avatar confirmation and recovery-code acknowledgement into modal bodies, then reset the identified local development database for a fresh manual review.
- [x] 19t. Integrate secondary notification addresses into Account details with clear status/actions and isolated versioned submissions; remove the separate default navigation tab.
- [x] 19u. Apply the existing shared native Markdown email layout to all direct Auth mail, preserving private delivery, text alternatives and single-attempt guards.
- [x] 19v. Consolidate browser evidence, pnpm store and Python bytecode under workspace-root .runtime; preserve existing artifacts and configure future writes.
- [x] 19w. Enable global default column sorting and repair active-session actions/date ordering.
- [x] 19x. Move trusted devices into two-factor settings and use guarded dialogs for all factor/trust actions.
- [x] 19y. Rebuild Preferences with account Card/dialog patterns and adjacent navigation.
- [x] 19z. Refine Preferences header reset, explicit display choices and inline delivery editing.
- [x] 19za. Record owner-approved module stabilization and later MINOR/PATCH/MAJOR policy in owner/customer guidance and closure requirements.
- [x] 19zb. Simplify and stack Preferences Cards; repair multi-address selection.
- [x] 19zc. Refresh current account views automatically, preserve drafts and reconcile conflicts locally; record reusable guidance.
- [x] 19zd. Remove redundant account copy, center avatar and show management only for active trusted devices.
- [x] 19ze. Remove duplicate spacing below the final two-factor method row.
- [x] 19zf. Keep shared form actions with their fields without a separator.
- [x] 19zg. Remove the redundant trusted-device subsection heading.
- [x] 19zh. Place secondary email status badges beside their addresses.
- [x] 19zi. Repair Auth frontend TypeScript errors blocking development setup.
- [x] 19zj. Preserve the application shell during initial session loading and show structural account/notification skeletons; publish for owner review.
- [x] 19zk. Show table loading spinners during account reads instead of premature empty results.
- [x] 19zl. Load account table tabs once and center the spinner without visible text.
- [x] 19zm. Show shared loading feedback on every table query transition.
- [x] 19zn. Add sparse randomized constellation-point glow only to MinimalLayout.
- [x] 19zo. Temporarily preview all MinimalLayout constellation points every three seconds.
- [x] 19zp. Restore sparse backdrop glow and animate full illustration-point orbits in MinimalLayout.
- [x] 19zq. Animate only one randomly selected illustration point per cycle.
- [x] 19zr. Enforce guarded worker database and test-container cleanup; retire audited obsolete local resources and synchronize owner/customer guidance.
- [x] 19zs. Complete welcome branding, platform description and accessible module catalogue; synchronize module authoring guidance.
- [x] 19zt. Normalize application branding, cover profile/image avatar loading and add application/account breadcrumbs.
- [x] 19zu. Honor welcome policy in branding/breadcrumbs and make the avatar skeleton pulse visibly.
- [x] 19zv. Remove redundant dropdown caption and apply semantic account-action colors.
- [x] 19zw. Target Auth request throttling, expose optional takeover and remove duplicate preference reads.
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
    Audit/Auth/Permissions 1.0.0 manifests/changelogs, dependent constraints and
    explicit install/update edges, synchronize closure docs, refresh knowledge and run
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

## Point 19g delivery

- Owner requests empty default application and self-service named Panel użytkownika/
  User panel. Removed account sidebar descriptor and welcome card/content/account
  link; default /app has no heading. Retained application navigation and session
  warnings/failures/reauthentication. Public custom-content slot remains unchanged.
  Shared ApplicationLayout omits the heading region when title/heading/actions are
  absent, avoiding empty headings without copying the shell into Auth.
- Refined dedicated dropdown identity with initials badge, compact wrapping name,
  muted caption and divider. Left-aligned native link/sign-out rows have 18px icons,
  consistent padding, 44px targets, hover/focus and reduced-motion treatment.
  Added logout to the existing public icon catalogue. Native sign-out preserves
  pending/disabled behavior and existing session/Audit handling. No universal menu
  abstraction or generic UI-framework component introduced.
- Auth PATCH 0.13.4 -> 0.13.5, source-only user_panel edge, central changelog and local
  forward application; existing Permissions ^0.13.0 remains compatible. Published
  current Vite assets without schema/settings/credential changes.
- Selected empty-workspace/sidebar/dropdown frontend regression passed (1 case),
  module version checks passed. Real served signed-in Chromium checks passed at
  1440/390 in light/dark: empty workspace, no sidebar account link, renamed dropdown
  entry and no overflow; actual dropdown -> renamed panel and sign-out passed.
  Inspected final dark menu screenshot after popup entrance; retained safe evidence
  under .runtime/dev/manual-auth/user-panel. No broad suites/static analysis/image/
  scanner/final quality ran; mandatory hooks apply.
- Updated prepared browser acceptance names and public account/customization/seeding/
  component guidance, plus both frontend page/reference instruction products. Root
  ownership and Vue/Tailwind interaction/style rules already apply without duplicate
  changes. Default application is blank rather than a business/example screen.
- No Docker resource creation/removal, reset or credential change. Existing data,
  services, caches, reports, backups and rollback material preserved. Point 20
  remains gated by explicit owner frontend appearance acceptance.

## Point 19h delivery

- Reproduced the owner's transient preference error on real authenticated /app and
  /account without HTTP failures. Locale/theme immediate watchers attempted writes
  when a new consumer mounted before account snapshot readiness, generating local
  save_failed, then successful hydration cleared it. Kept existing error feedback;
  removed the invalid initialization write rather than hiding errors.
- useLocale/useTheme now apply initial document lang/theme directly and watch only
  subsequent value changes for persistence. Existing hydration syncing, reactive
  subscriptions, guest storage, account fences and media listener cleanup remain.
  No module-owned code/API/schema changed; all module/platform versions unchanged.
- Added a real shared-store display consumer regression: pending delayed snapshot,
  mounted/remounted consumers, hydration and document theme cause no writes/errors;
  explicit locale/theme changes each write once and synchronize another consumer.
  Selected test passed (1 case); repaired fixture Content-Type before passing.
- Published current client assets for continued manual review. Actual authenticated
  Chromium navigation /account -> /app -> /account emitted snapshot operations only,
  zero initialization writes and zero transient preference alerts. This reuses the
  mutation observer that captured the original flash. No broad suites, typecheck/
  analysis, image/scanner or final quality ran; mandatory hooks apply.
- Synchronized authoritative preferences guide and owner/customer Vue references
  with read-only mount/hydration and deliberate persistence. Existing frontend/
  security/root contracts already require no echo writes and preserved error/fence
  behavior; no duplicate rule needed. Backend/database behavior unchanged.
- No Docker resources/reset/credential changes; preserved development/test data,
  services, caches, reports, backups and rollback material. Point 20 still awaits
  explicit owner frontend appearance acceptance.

## Point 19i delivery

- Owner clarified the remaining flash occurs on the departing document. Real Firefox
  link navigation reproduced old-page preference, profile/security and activity
  errors: beforeunload occurred first, fetch rejected with TypeError 2–5ms later,
  pagehide followed tens of ms later. Chromium did not consistently expose this.
  This is separate from fixed initial-hydration writes; no API failure was involved.
- Shared HTTP now owns an explicit document AbortController covering both fetch and
  response-body reads, combined with caller signals. In-flight requests attach a
  beforeunload guard that waits for synchronous dirty-form prevention; pagehide
  also cancels. Idle documents detach beforeunload for bfcache eligibility. Pageshow
  and new explicit requests renew the controller if a document remains/resumes.
  Cancellation is a public RequestCancelledError, never a server success.
- Preference store/session discovery and Auth entry/account/welcome/application
  consumers recognize explicit document cancellation without publishing danger
  alerts. Genuine network/API/proof/conflict errors still report normally. Canceled
  write completion may be unknown: no success claim or automatic replay; subsequent
  work reconciles through authoritative reads. Existing secret cleanup, signals,
  account epoch fencing, pending cleanup and server guards remain.
- Auth PATCH 0.13.5 -> 0.13.6, source-only navigation_cancellation edge/changelog;
  Permissions ^0.13.0 remains compatible. Local forward preparation and current
  client publication preserve data/settings/credentials; framework API/platform
  versions unchanged.
- Focused HTTP regressions pass: document read/write cancellation rejects typed
  errors without replay, new explicit requests work, prevented unload keeps requests
  alive and real TypeError remains an error (2 cases). Selected preference regression
  preserves null feedback on cancellation and reports real load failure (1 case).
  Real Firefox and Chromium repeated link navigation passed with an observer spanning
  old/new documents and no danger-alert flashes. Module versions pass. No broad
  suite/static-analysis/image/scanner/final quality ran; mandatory hooks apply.
- Updated authoritative preferences/public HTTP consumer guidance and owner/customer
  Vue async reference products. Existing root/frontend/security contracts already
  require supported transport, safe cancellation and no replay; no duplicate rule.
- No Docker resources/reset/credential change. Existing data, services, caches,
  reports, backups and rollback material preserved. Point 20 remains gated by
  explicit owner frontend appearance acceptance.

## Point 19j delivery

- Owner requests combined identity/avatar/password/verification editing and full
  available width, leaving sessions/preferences for later review. AuthApplication
  now passes the existing width=fluid shell contract. Default account navigation
  groups details/security/avatar into Account and security; existing public tab/
  task methods remain compatible for custom presentations.
- AuthAccountEditor composes shared controls in one Form, two desktop columns and
  one narrow column, with directly visible profile/password fields, inline avatar
  selection/removal and one outlined Save changes action. A labelled verification
  Select replaces task-switch buttons. Corrected fieldset titles to the shared
  FormSection title prop. Destructive account actions remain explicitly confirmed;
  session/preference operations retain their behavior and separate navigation.
- Public useAuthAccount owns saveOverview and typed overviewAction/removeAvatar/
  overviewNeedsProof state. Validate all selected edits before writes, then call
  existing guarded APIs with refreshed profile versions; stop on failure/cancellation/
  reauthentication without replay and explicitly report partial completion. Reset
  completed email/avatar intents. Password/factor edits require separate saves to
  avoid reusing a one-time proof. Preserve TOTP begin/confirm, fresh proofs, explicit
  recovery-code acknowledgement, account fencing, safe drafts and secret cleanup.
  No backend permission/proof/transaction/schema/settings contract was changed.
- Auth compatible PATCH 0.13.6 -> 0.13.7, source-only account_editor forward edge and
  changelog. Permissions ^0.13.0 remains compatible. Applied the local forward step
  and published current client assets for manual review; no credentials/reset/data
  fixture changes. Module source/version diagnostics passed for all three Core modules.
- Six selected frontend cases passed: stale version refusal, deliberate draft review,
  TOTP confirmation/recovery acknowledgement, password validation/CSRF no replay,
  prevalidated sequential writes/partial failure and avatar version/removal reset.
  Real served authenticated Chromium checks passed at 1920/390 in both themes:
  one form, no overflow or JS errors, no writes on an unchanged save, and workspace
  width 1656px/max-width none at 1920. Inspected desktop/mobile screenshots retained
  under .runtime/dev/manual-auth/account-editor. An initial browser attempt during
  the source/installed-version mismatch was retried after forward preparation.
- Synchronized account/customization/form guidance and both owner/customer frontend
  references with the reusable visible-editor pattern. Root AGENTS, Vue/Tailwind
  and security products already require shared public composition, responsive forms,
  guarded proof/version handling and secret cleanup; no duplicate rule needed.
  Added no page motion; shared controls retain their existing reduced-motion/focus
  behavior and secret material remains hidden from print.
- No broad suite/static analysis/image/scanner/final quality ran; mandatory hooks
  apply. No Docker resources were created or removed. Preserved existing development
  services/data, caches, reports, backups and rollback material. Point 20 remains
  gated by explicit owner frontend appearance acceptance.

## Point 19k delivery

- Owner replaces the previous single-save presentation with four existing Cards:
  identity, avatar, password and verification. Each has its own Form and outlined
  submit; ordinary fields remain directly visible. Retained fluid width, responsive
  two/one-column layout and separate unchanged session/preference operations.
  Success uses shared Alert/ToastViewport, specific operation descriptions and one
  live announcement; actionable errors stay inline. Shared feedback owns motion.
- Public saveSection/AccountSection and sectionFields/sectionErrors scope credentials,
  errors, focus and cleanup to the submitting card. Saving one card preserves other
  card drafts. Existing choose/submit/saveOverview APIs remain compatible. Details
  still validates before sequential name/email operations with refreshed revisions,
  explicit partial completion and no replay. Password/factor proofs remain separate;
  TOTP/recovery acknowledgement and lifecycle confirmations remain explicit.
- Reproduced avatar rejection with a real 902684-byte PNG: compressed pixels contained
  27 literal <? sequences, triggering the previous whole-file code regex and 422.
  A small image saved successfully, proving storage was writable. AvatarImageContent
  now inspects PNG/JPEG/WebP container boundaries and nonpixel metadata, accepting
  encoded pixel bytes while refusing active metadata and appended content. Retained
  native MIME/size/dimension validation, private scoped storage, policy/version/Audit,
  guarded reads and secure image headers. No new decoder/sanitizer claim/dependency.
- Added public AuthAvatarCropper for customer composition without private copies.
  Local originals are bounded to 20 MiB, 40M pixels and 12000px per side; accessible
  square preview, panning and shared AppPickerSlider zoom/position export a 512px PNG.
  Only the crop is uploaded under existing server limits. File/decode/export failures
  block upload, URLs/decoded references are released and stale exports are fenced.
  Reused the color picker's internal slider with compatible optional min=0 and
  disabled-fieldset/inert guards. No crop animation; existing feedback honors reduced
  motion. Enrollment/recovery secrets remain hidden from print.
- Auth MINOR 0.13.7 -> 0.14.0 for additive card/cropping contracts; Permissions PATCH
  0.5.1 -> 0.5.2 aligns Auth ^0.14.0. Explicit source-only forward edges/changelogs,
  local preparation and current client build passed; all Core source/version checks
  passed. No schema/settings/credential reset or external dependency added.
- Nine selected frontend cases passed, covering independent drafts/submits, partial
  failure/version/no replay, password validation, TOTP/recovery, actual crop output,
  invalid/oversized files and existing color-slider behavior. Focused backend avatar
  cases and the settings-upgrade fixture case passed. Added browser-generated small
  JPEG/WebP fixtures containing code-like pixel bytes; real uploads/reads for all
  three formats pass, malicious metadata/trailers refuse, and Audit rollback/private
  cleanup remain covered. Initial backend runs exposed stale manual Core fixture
  initialization/routes; replaced it with ModuleUpdates and source-route rebuild,
  removed redundant cold reconstruction from its upgrade case and received passes.
- Actual authenticated served browser saved a 6584980-byte 5000x1500 original through
  crop/upload/read as a 512x512 avatar with HTTP 200 and shared success feedback.
  Restored the prior avatar state through guarded API (HTTP 200). Inspected desktop,
  mobile and success/crop screenshots in .runtime/dev/manual-auth/account-sections;
  1440/390 light/dark checks show exactly four forms/submits, no overflow or JS errors.
- Replaced obsolete one-form/one-save current guidance in account/form/customization
  docs and both frontend instruction products. Added format-aware binary-validation
  guidance to the public security baseline and both security reference products.
  Root AGENTS, primary skills and Vue/Tailwind products already require shared public
  components and proper cleanup; no duplicate constitutional rule needed.
- No broad suites/static analysis/image/scanner/final quality ran; mandatory hooks
  apply. No Docker resources created/removed. Preserved development services/data,
  caches, reports, backups and rollback material. Point 20 still requires explicit
  owner frontend appearance acceptance.

## Point 19l delivery

- Owner requests modal cropping with a dimmed surrounding image, circular avatar
  frame, mouse dragging and wheel zoom instead of sliders. The account card now
  opens public Dialog on file selection, with local Apply crop/Cancel and Adjust
  crop for an accepted selection. Apply prepares a circular card preview; only the
  existing card Save uploads. Cancel/Escape never upload and canceled readjustment
  preserves the accepted crop. Object URLs are released on replacement/unmount.
- Public AuthAvatarCropper retains File/locale/maxBytes, ready and crop() contracts.
  Its canvas shows the source and a dimmed outside area around the bright circle.
  Dragging maps pointer distance to actual image coordinates and clamps boundaries;
  wheel zoom preserves the center. Ctrl+wheel retains native browser zoom. Touch
  supports single-finger pan and pinch zoom; keyboard arrows, +/- and Home provide
  alternatives without sliders. Export uses a separate canvas so masking never
  enters the 512px PNG. Existing file limits, stale export fencing and cleanup stay.
- Reused Dialog lifecycle, focus containment/return, busy state and reduced-motion
  transitions; direct manipulation remains immediate. Shared color sliders remain
  unchanged. Auth PATCH 0.14.0 -> 0.14.1 has a source-only forward edge/changelog,
  compatible Permissions ^0.14.0, local forward preparation and published assets.
  All Core source/version diagnostics passed; no data/settings/schema changes.
- Four focused frontend cases cover apply-before-save, independent drafts, modal
  cancel/readjustment, wheel/pinch/keyboard output, dimmed-preview isolation and
  invalid/oversized originals. Adapted teleported-modal selectors and real Touch
  event fixtures before receiving passing results. Actual served authenticated
  Chromium checks exercised drag/wheel and Apply/Escape in 1440/390 light/dark,
  zero sliders, no overflow/JS errors and zero upload requests on Apply/Cancel.
  Inspected retained screenshots under .runtime/dev/manual-auth/avatar-modal.
- Replaced obsolete slider/inline preview claims in account/customization/component
  docs, updated both frontend instruction products. Root AGENTS, primary frontend/
  Vue/Tailwind/security guidance already requires public primitives, accessibility
  and cleanup; no duplicate rule needed. Backend upload/storage contracts unchanged.
- No broad suites/static analysis/image/scanner/final quality ran; mandatory hooks
  apply. No Docker resources or live avatar/data changes; existing services/data,
  caches, reports, backups and rollback material preserved. Point 20 remains gated
  by explicit owner appearance acceptance.

## Point 19m delivery

- Owner requests all successful-save/form-wide error outcomes only at top right.
  Added public FormNotifications over the existing local toast queue/viewport and
  migrated Auth account/entry, crop/decode/export and shared account-preference
  feedback. Removed duplicate outcome banners and moved CSRF/reload/retry actions
  into persistent error notifications. Linked field corrections, static security
  instructions, recovery-code acknowledgement and session warnings retain context.
- FormNotifications owns one current outcome, error precedence, partial-completion
  description, persistent failures, timed successes, PL/EN and optional actions.
  attempt increments allow an identical dismissed validation error to be reported
  on deliberate resubmission. Current outcomes update in place; title/description
  updates reset success reading time. No HTTP interception/global error toast or
  automatic retry was introduced; each feature retains its safe controller state.
- ToastViewport exposes scoped actions and follows the active modal portal, waiting
  until its target exists so it is never moved with stale inert state. Modal retry/
  close controls stay within focus containment. One polite live region announces
  additions/text changes. Existing dismissal, hover/focus pausing, focus restoration
  and reduced-motion transitions remain; top-right stack sits above content.
- Nine focused frontend cases passed: outcome replacement/repeated attempt, persistent
  action recovery, modal keyboard access, account partial/conflict/CSRF, entry CSRF/
  safe errors/token handling, crop invalid-file feedback and existing toast timers/
  focus return. Updated prepared selectors for teleported notifications. Initial
  modal test exposed an initially body-teleported inert node; deferred portal mount
  fixed the issue and the received regression passed.
- Actual served authenticated browser showed one success with zero inline success
  banners; invalid password retained three associated field corrections and zero
  inline failure banners. Intercepted conflict diagnostics at 1440/390 light/dark
  showed one persistent top-right error, zero inline duplicate, no overflow/JS
  errors and working explicit Reload current values. Inspected screenshots retained
  in .runtime/dev/manual-auth/form-notifications. No real profile/avatar writes.
- Auth PATCH 0.14.1 -> 0.14.2, source-only form_notifications edge and changelog;
  existing Permissions ^0.14.0 remains compatible. Applied local forward preparation,
  published current client assets and passed all Core source/version diagnostics.
  No schema/settings/backend authority/credential changes or dependencies.
- Replaced obsolete inline operation-error/success claims in visual direction,
  frontend architecture/component/form guides and Auth account/entry/preferences
  docs. Updated owner/customer frontend references. Root AGENTS and primary Vue/
  Tailwind/security skills already require shared controls, accessible errors and
  safe explicit recovery; no duplicate constitutional rule required.
- No broad suites/static analysis/image/scanner/final quality ran; mandatory hooks
  apply. No Docker resources created/removed. Preserved services/development data,
  caches, reports, backups and rollback material. Point 20 remains gated by explicit
  owner frontend appearance acceptance.

## Point 19n delivery

- Owner reports saved avatar missing in top menu/dropdown. Both previously rendered
  initials only. ApplicationLayout now accepts optional userAvatar and AuthApplication
  supplies the guarded current profile's image to both trigger and dropdown, using
  the same revision/account-qualified private URL. Profile display name/initials also
  update after edits; no backend session projection or authority contract changed.
- Added public shared Avatar (src/initials/size) for the shell trigger, dedicated
  dropdown identity and account/crop preview. It renders a circular object-fit image,
  falls back to initials on missing/failed images and retries on source changes.
  Decorative images use empty alt; the owning trigger retains its accessible name,
  focus/disclosure behavior and 44px target. No additional avatar motion is needed.
- Public useApplicationSession exposes optional current profile presentation. Its
  nonblocking guarded read runs on initialization/explicit session refresh, without
  polling. Existing shared HTTP observation accepts same-account validated nonstale
  profile reads after saves/removals, refreshing both shell locations immediately.
  Foreign/stale responses are ignored; pause/logout/switch/dispose clear presentation.
  Epoch/identity fences remain authoritative. A failed optional read leaves initials
  and cannot block an otherwise valid shell; no credential/data is persisted.
- Auth MINOR 0.14.2 -> 0.15.0 for additive public session presentation; Permissions
  PATCH 0.5.2 -> 0.5.3 aligns Auth ^0.15.0. Source-only forward edges/changelogs and
  local preparation passed. Published current client assets; all Core source/version
  diagnostics passed. No schema/settings/backend API/dependency changes.
- Seven focused frontend cases passed across received runs: shell/dropdown updates
  on replacement/removal, stale/foreign profile rejection, image failure/retry,
  suspension cleanup, existing dropdown/session boundaries, migrated account-avatar
  save/draft handling and delayed optional-read disposal without blocking the shell.
  Real served authenticated Chromium loaded the existing 512px image in both trigger
  and dropdown; 1440/390 light/dark checks passed with no overflow/JS errors. Inspected
  screenshots retained under .runtime/dev/manual-auth/shell-avatar. No real account/
  avatar writes were made during this diagnostic.
- Updated account/customization/component/page-layout guidance and both owner/customer
  frontend reference products. Replaced obsolete no-avatar catalogue wording now
  that a real requirement exists. Root AGENTS, Vue/Tailwind/security guidance already
  requires public reuse, account fencing and safe private reads; no duplicate rule.
- No broad suites/static analysis/image/scanner/final quality ran; mandatory hooks
  apply. No Docker resources created/removed. Existing services/data, caches, reports,
  backups and rollback material preserved. Point 20 still awaits explicit owner
  frontend appearance acceptance.

## Point 19o delivery

- Owner requests centered text in the notification. Shared Toast now opts into
  Alert alignment=center: center content horizontally, align icon/text/close
  vertically and center recovery actions. Ordinary Alert alignment defaults start.
  Empty descriptions no longer reserve a blank line or offset the title.
- Published current client assets and inspected the actual authenticated no-change
  success notification. Computed textAlign/alignItems are center, no empty content
  line, and title/icon vertical centers differ by less than 0.01px. Screenshot
  retained under .runtime/dev/manual-auth/notification-alignment. No new tests or
  broad checks for this small presentation correction; mandatory hooks apply.
- Updated the public component catalogue. Existing owner/customer frontend references
  already point to that contract and require shared components; no new instruction
  rule or duplicate guidance needed. Root/Vue/Tailwind/security products unchanged.
  No module-owned source changed, so module versions remain unchanged.
- No data/settings/avatar/credential changes or Docker resources created/removed.
  Preserved services, caches, reports, backups and rollback material. Point 20
  remains gated by explicit owner frontend appearance acceptance.

## Point 19p delivery

- Owner requests removing the pixelated-looking top avatar frame. Dedicated shell
  CSS now removes its ordinary/hover/pressed border and background, preserving the
  circular image, 44px disclosure target and existing visible keyboard outline.
- Published current client assets. Real served authenticated browser confirmed 0px
  border, transparent background and 44x44px target in ordinary/hover/focus states;
  keyboard focus retains its 3px outline. No tests/broad checks for this small CSS
  correction; mandatory hooks apply. Updated the public component catalogue.
- No module source/API changed; versions unchanged. Owner/customer skills already
  reference the shared catalogue and preserve keyboard focus, so no new rule needed.
  No data/settings/resource changes; services, caches, reports and backups preserved.
  Point 20 remains gated by explicit owner frontend appearance acceptance.

## Point 19q delivery

- Owner requests compact details/password stacking, confirmed avatar deletion,
  explicit two-factor controls and all available self-service frontend features
  enabled for local manual review. Default account cards now use independent
  details/password and avatar/security column stacks, collapsing in reading order
  on narrow screens. Each operation retains its own form and credential draft.
- Avatar removal uses the shared ConfirmDialog with cancellation and busy guards.
  It sends only the existing versioned DELETE after confirmation; failed requests
  keep the modal open for explicit recovery/retry. Only the active feedback owner
  renders top-right notifications, inside the modal focus boundary when open.
  Existing crops must first be saved/discarded; cancellation preserves the image.
- The two-factor card lists authenticator/email methods with status, descriptions
  and explicit setup/disable buttons. Selected operations reveal scoped proof and
  named submit/cancel. Cancellation clears local credentials/enrollment secrets;
  enrollment confirmation, recovery-code acknowledgement and server authority
  remain intact. Password saves no longer reject an unrelated selected security
  operation in a different card.
- Auth advances compatibly from 0.15.0 to 0.15.1 through the source-only
  account_methods edge, applied locally. Audit 0.5.0 and Permissions 0.5.3 remain
  compatible; Permissions' ^0.15.0 Auth dependency needs no change. No schema,
  backend API, credentials, stored avatar or dependency changes.
- Through the supported revision/generation-checked audited settings command,
  local Auth settings revision 2 enables secondary addresses and their existing
  add/change/remove permissions, own login/account histories, trusted devices,
  email OTP, lifecycle self-service and all six mail-notice categories. Existing
  TOTP/recovery/edit/avatar capabilities remain enabled; remembered sign-in is
  enabled for 10080 minutes. Required 2FA stays optional. Audit/Permissions have no
  settings declarations for these views and their Core runtime checks pass.
  Production defaults and last-superadministrator/security invariants are unchanged.
- Public account/forms/seeding guides describe current behavior and the explicit
  full-feature review scenario, including verified secondary recipients and
  primary-only sensitive mail. Owner/customer frontend references point to the
  shared form pattern. Root contracts need no change. The upstream skill-creator
  validator could not start because its Python environment lacks PyYAML; no
  dependency was added. Only existing reference text changed; entrypoint/frontmatter
  remain unchanged and references were reviewed directly.
- Seven focused Chromium regressions pass: TOTP enrollment/recovery acknowledgement,
  independent cropped-avatar save, independent password save with another security
  draft, removal cancellation/confirmation, one modal error with explicit retry,
  direct email enrollment/cancel cleanup and correct chosen-method disable/proof.
  Initial assertions were corrected for the additional Dialog, obsolete checkbox,
  asynchronous reload and nested danger-tone close button. Core source/runtime and
  version diagnostics pass; current Vite client assets published for manual review.
- Real served browser confirmed all user-panel tabs, both factor methods, secondary
  address form, delivery categories and trust view. Desktop password card follows
  details with the intended 24px gap regardless of avatar height; 390px mobile has
  no horizontal overflow. Light/dark screenshots inspected; both avatars decode
  at 512px, confirmation/cancel works and no browser page errors were reported.
  Screenshots remain private under .runtime/dev/manual-auth/account-methods.
  No actual avatar deletion, factor enrollment or lifecycle operation was performed
  on the owner's account. No Docker resources created/removed; active services,
  databases, caches, reports and backups preserved. Point 20 remains gated by
  explicit owner frontend appearance acceptance; no full quality gate was run.

## Point 19r delivery

- Owner requests a scannable QR alongside the manual authenticator setup key.
  The existing guarded no-store TOTP begin response now adds qr_code, an SVG data
  URI generated locally from exactly the existing otpauth URI using BaconQrCode
  3.1.1, already installed by Fortify 1.36.2. No dependency, remote QR service,
  separate image endpoint, credential-bearing request URL or stored QR file added.
  Existing password/current-factor proofs, pending-secret TTL, activation, replay
  counters, Audit and recovery-code acknowledgement remain unchanged.
- Shared AuthTotpSetup is public through @/runa/auth and reused by account and
  required-entry enrollment. It shows a black/white 256px QR with four-module quiet
  margin, manual key and localized instructions/alternative text. The presentation
  is static, responsive and print-hidden. Public controllers expose private
  qrCode/enrollmentQr state, accept only bounded SVG base64 image data, preserve
  manual fallback for older begin payloads and clear the image with its secret on
  completion/cancel/method switch/logout/disposal, including after effects stop.
- Auth advances additively to 0.16.0; Permissions advances to 0.5.4 to align its Auth
  dependency with ^0.16.0. Both source-only forward edges are applied locally;
  no schema, settings or owner credential/avatar changes. Audit remains 0.5.0;
  framework API/platform release versions remain unchanged until point 20.
- Focused backend QR regression passes with 17 assertions: unauthenticated and
  incorrect-password refusal, no-store response, parseable local SVG with no
  external/script content, matching URI/key and encrypted pending-only enrollment.
  Three focused Chromium regressions pass for account/required enrollment QR,
  manual fallback rendering, recovery transition, bounded/local image inputs,
  cancellation and disposal cleanup. Core source/runtime and module-version
  diagnostics pass. Current client assets published for manual review.
- Real served browser with a locally generated, explicitly fake enrollment payload
  confirms a decoded 256x256 image, 390px layout without horizontal overflow,
  cancellation cleanup, print exclusion and no page errors. Inspected dark desktop,
  dark mobile and light-theme screenshots; private evidence stays under
  .runtime/dev/manual-auth/totp-qr. Preview intercepts only TOTP begin and never
  starts/enrolls a real factor on the owner's account. Smartphone scan acceptance
  remains part of the owner's manual review; no independent QR decoder was added.
- Synchronized security, account/entry UI, public presentation and module changelog
  docs. Owner component catalogue and customer frontend guidance reference the
  shared private setup contract. Existing root/security guidance already forbids
  secret persistence/disclosure; no constitutional change needed. No Docker test
  resources created/removed; active services, databases, caches, backups and reports
  preserved. Point 20 remains gated by explicit owner appearance acceptance;
  no full quality gate was run.

## Point 19s reset scope

- Owner explicitly requests a clean manual-review start after these frontend fixes.
  Inspected the actual local environment: APP_ENV local, PostgreSQL host postgres
  at 172.27.0.7, database/owner runa, 43 tables confined to shared/runa_audit/
  runa_auth/runa_permissions, 15 development fixture accounts, zero non-fixture
  accounts and one session. The postgres maintenance connection reaches the same
  server. Database locale is ICU pl-PL, LC_COLLATE C, LC_CTYPE C.UTF-8.
- Native migrate:fresh is application-owned and preserves Core; its isolated-test
  guard must not be disabled or spoofed for development. No supported whole-dev
  reset command exists. Use a one-off trusted operator reset of ONLY database runa
  from the postgres maintenance connection, preserving role/locale/privileges,
  then the supported forward platform initializer, application migrate/seed,
  module development corpus and hidden interactive initial-password setup.
  This implements the explicitly requested preproduction database recreation;
  it does not change native guards or add a bypass command.
- Scope discards this database's fixture accounts, sessions/factors/preferences,
  Audit, grants, queue/cache and module installation/settings state, all rebuildable
  from current source/seeders. Preserve postgres and every runa_test* database,
  database roles, Docker resources/volumes, private storage files, .env files,
  administrator-password, Qdrant/model caches, backups and verification evidence.
  Restore the previously requested full-feature local Auth review switches after
  initialization. Reuse the existing private password without exposing it.

## Point 19s delivery

- Shared Alert symbols now align vertically to the center independently of message
  text alignment. Avatar deletion keeps its title in the header and the confirmation
  question in the modal body. No change to the reported operation's error handling.
- Public AuthRecoveryCodesDialog is shared by optional/required enrollment and
  replacement. The modal body holds the storage instruction and one-time codes;
  its footer requires explicit saved acknowledgement. Escape/outside/X do not
  discard codes. The dialog focuses its acknowledgement after the generating
  request finishes; background inline recovery banners/lists are removed.
  Codes remain in memory and print-hidden, with existing controller cleanup.
- Auth advances to 0.17.0 for the additive shared presentation export; Permissions
  0.5.5 aligns ^0.17.0. Source-only edges applied; no schema/API-authority changes.
  Synchronized module changelogs, account/entry and public presentation guidance,
  owner catalogue and customer frontend references. Root policies need no change.
- Four focused Chromium regressions pass: optional TOTP codes in a modal, required
  enrollment acknowledgement, avatar deletion confirmation and replacement with
  proof/explicit acknowledgement, dismissal guards and focus. Assertions now use
  teleported dialog content rather than the old inline page list. Core runtime/
  source/version diagnostics pass and current client assets were published.
- The scoped database recreation above completed: only local database runa was
  dropped/recreated with its original owner/ICU locale/privileges. Ran the supported
  forward platform initializer, application migrate --seed and both Core development
  contributors. Native migrate:fresh guards remain untouched. Maintenance protected
  the destructive/forward phase and was disabled before guarded module seeding;
  all guarded seeding completed successfully after that precondition was satisfied.
- Reinitialized the fixture superadministrator through the supported hidden
  interactive setup command, supplying the existing mode-0600 password file only
  after terminal echo was disabled. No credential entered argv/logs. Restored all
  full-feature local Auth review switches, including registration, secondary
  addresses, histories, trust, email OTP, lifecycle actions, notices and remembered
  sign-in. Fifteen fixture accounts are restored; administrator has no avatar or
  pending/enrolled TOTP. Login with the preserved password and normal logout pass.
- Real served browser preview used fake enrollment/replacement payloads only;
  no real factor was enrolled. Confirmed body-only avatar question, eight codes in
  both initial and replacement modals, explicit acknowledgement, Escape refusal,
  action focus and 390px without overflow. Inspected dark/light desktop/mobile
  screenshots under .runtime/dev/manual-auth/recovery-modal. Shared alert icon's
  center matches the alert center exactly (0px delta). Preview sessions were revoked
  through own-session controls and signed out; no active administrator preview
  sessions remain. The separate seeded session scenario is retained.
- Preserved every other database (postgres/runa_test*), database roles, private
  storage files, password/.env files, Docker volumes/services, Qdrant/model caches,
  backups and verification evidence. No Docker resources created or removed.
  Point 20 remains gated by explicit owner appearance acceptance; no broad/final
  quality gate was run.

## Point 19t delivery

- Owner requests removing the separate secondary-address tab and integrating it
  into Account details with a clearer presentation. The default navigation now
  omits addresses; the details Card contains the collection below its identity
  Form. Status badges, current count/limit, quiet per-row edit/reissue/remove
  actions and compact independent add/edit fields use existing public UI controls.
  Unverified addresses alone offer explicit reissue; unchanged edits cannot submit.
  Removal uses a shared confirmation modal with body text and one modal-bound
  failure/recovery notification. Editing focuses its field through its owned ID.
- New public saveSecondary/secondaryErrors orchestration uses the existing guarded
  versioned notification-address endpoint and authoritative profile reload. It
  validates known email format, feature/operation flags, limits, current address
  identity and explicit removal confirmation. It never rebinds the parent identity
  fields, clears unrelated credential/avatar/security drafts or silently retries
  conflicts. Legacy addresses/choose/submit controller seams remain available for
  customer presentations. No backend policy, verification/routing or API change.
- Auth advances additively to 0.18.0 through secondary_details; Permissions 0.5.6
  aligns ^0.18.0 through auth_secondary_details. Both source-only forward edges
  applied locally; Audit remains 0.5.0 and framework/platform release versions
  remain unchanged. No dependencies, schema/settings/credentials reset.
- Six focused secondary-address Chromium regressions pass: edit/delete identity
  and fresh revision with confirmation/cancel, known email rejection and draft
  preservation throughout a deliberately pending request, feature permissions/
  limit/verified state, reissue preserving an unsent address, conflict preservation
  without replay, and one modal failure preserving its address. The existing
  account-card validation/partial-failure regression also passes after extracting
  shared email validation; its assertions now await completion of the actual
  sequential request chain rather than assuming flushPromises completes network work. The TextInput has no focus() exposure; owned-ID focus
  replaced that initial assumption. Core source/runtime/version diagnostics pass;
  current Vite assets published for manual review.
- Rendered the actual served client against explicit test-only Auth/profile/
  preference/activity fixtures; the owner's administrator now has TOTP, so preview
  did not create a real session, retrieve a factor secret or change owner data.
  Verified default navigation, integrated mixed verified/pending list, add/edit,
  confirmation/cancel, focus and 390px with no horizontal overflow. No page errors
  or error notifications in the corrected preview. Screenshots retained privately
  in .runtime/dev/manual-auth/secondary-details; dark/light desktop and mobile
  presentation inspected.
- Updated account/public-controller/module changelog and manual-review seeding
  guidance to remove the obsolete separate-tab claim. Owner/customer form guidance
  records independent collection drafts/submissions through public orchestration.
  Root/security rules need no change; server authority and verified-recipient rules
  already cover this capability. No Docker resources created/removed; databases,
  active owner sessions/2FA, settings, files, caches, backups and reports preserved.
  No database reset was requested or performed in this point. Point 20 remains
  gated by explicit owner appearance acceptance; no broad/final gate was run.

### Point 19u — shared Auth mail presentation

- Owner requested using the already delivered email template for Auth messages.
  Entry invitations/reset/verification/email OTP, profile/address confirmation and
  former-primary notices, and direct security-notice fallback now use one native
  AuthMessage Markdown body with the existing Runa layout/theme/branding/footer.
  Link messages include an action and complete fragment URL fallback; OTP codes
  preserve leading zeroes without a link. Notice mail contains no token. Entry/
  profile retain Polish copy; security notices retain PL/EN preference and layout
  locale. The optional SecurityMailDelivery adapter contract remains unchanged.
- Preserved private reference-only jobs, recipient/state rechecks, origin checks,
  persisted single-attempt claims, locked synchronous handoff and static redacted
  delivery failures. No new dependency, notification engine, queue payload or
  schema/settings/credential change. Auth advances PATCH 0.18.0 -> 0.18.1 through
  source-only mail_layout, applied locally. Permissions ^0.18.0 remains compatible
  at 0.5.6; Audit stays 0.5.0. Source/runtime/version diagnostics pass. Cleared
  compiled views and signalled queue worker restart for current PHP/templates.
- Nine focused backend cases pass (157 assertions): actual entry/profile/security
  delivery with duplicate/expired/obsolete suppression and current recipients,
  native multipart branding and text, complete action URL, zero-prefixed OTP,
  notice content, matching PL/EN and locale restoration. These cases use the owning
  Auth fixture so they also run independently of other test files. Synthetic array-
  transport previews inspected at 900px/390px for reset, OTP and notice, without
  horizontal overflow; retained under .runtime/dev/manual-auth/auth-mail. Browser
  previews do not certify external mail clients. No real email was sent by checks.
- Synchronized native email architecture, public layout/Auth delivery guidance and
  module changelog, correcting the stale Auth README version. Owner/customer
  backend guidance now requires shared presentation while preserving each module's
  delivery/security contracts. Existing frontend references link to the updated
  public layout guide; root and security rules already cover this boundary and
  need no changes. No Docker resources created/removed; development data, owner
  accounts/sessions/2FA, files, caches, backups and reports preserved. No database
  reset or broad/final verification; point 20 still awaits owner acceptance.

## Point 19v delivery

- Relocated artifacts to .runtime/artifacts, pnpm store to .runtime/pnpm-store,
  and nine first-party Python cache directories to .runtime/python/pycache using
  Python's absolute-source prefix layout. Verified file sizes and SHA-256 before
  and after every move: 1,211 browser files, 6,726 package-store files and 117
  bytecode files preserved. Evidence: .runtime/artifacts/relocation-RM-2026-011.json.
- Updated application/owner Playwright and Vitest outputs, explicit E2E screenshots/
  exports, browser diagnostic output, installation evidence collection and error
  fallback report paths. Old roadmap delivery paths remain historical evidence;
  their contents now reside under the corresponding .runtime/artifacts paths.
- pnpm-workspace.yaml owns the relative storeDir, including isolated build workspaces.
  Updated only the generated installed-modules store path after moving the same
  store; dependencies, lockfile and existing hardlinks were preserved. No install
  or package update was needed.
- Development image, Compose environments and devcontainer remote environment
  select PYTHONPYCACHEPREFIX. Current processes retain their inherited environment;
  export the prefix in existing terminals or reload the devcontainer environment.
  No live application restart/rebuild is performed during manual frontend review.
- Synchronized public artifact placement/testing/command docs, owner testing skill
  reference and both owner/customer root instruction products. Existing Docker/
  frontend skills and customer references already link to the updated placement
  source; they need no duplicate procedure. No module or public API/version change.
- Focused validation checks configuration path resolution, pnpm's selected store,
  Python prefix resolution, changed JavaScript/Python syntax and byte integrity.
  Full browser suites/builds/scanners remain deferred to gated point 20.
- No Docker resources were created or removed. Preserved all development/test data,
  sessions, Qdrant/model caches, backups, reports and unrelated runtime state.

## Point 19w delivery

- Table columns sort by default with explicit sortable=false opt-out, consistently
  across headers, client processing, controlled providers and named-view snapshots.
  The existing ascending/descending/original-order cycle resets pagination and
  retains semantic aria-sort. Server headers still require provider sort capability;
  API allowlists/authorization remain independently enforced. Numeric values use
  numeric ordering; explicit compare adapters retain precedence. No new dependency.
- Account session actions now read End for current/other sessions; trusted-device
  Revoke is unchanged. Created/expiry/history columns compare UTC instants while
  keeping localized presentation. Cursor history sorting covers loaded rows only.
  No revocation/session security, backend API, schema or settings change.
- Auth PATCH 0.18.1 -> 0.18.2 records the account presentation repair and adds the
  source-only session_sorting forward edge, applied to local development through
  ModuleUpdates. Audit 0.5.0 and Permissions 0.5.6/^0.18.0 remain compatible.
  Source/runtime module diagnostics and version/changelog checks against the
  preceding commit pass. Platform VERSION/framework API remain unchanged.
- Four selected browser-component regressions pass: default/opt-out numeric header
  cycle, server capabilities/query forwarding, saved-view sort normalization and
  account date ordering across month/timezone boundaries with concise actions.
  The account fixture initially wrapped the native page envelope twice; fixed the
  fixture and received the passing result. No backend behavior change/test required.
- Published the current client with a focused Vite build for manual review. Inspected
  the served account table at 1440px/390px in light/dark with synthetic read-only API
  data, ascending dates and two End actions; no document overflow or page errors.
  Evidence: .runtime/dev/manual-auth/session-sorting. The narrow preview creates no
  real session and performs no real account mutation; it does not claim full E2E.
- Updated the public table/component/account contracts, Auth version/changelog,
  owner table reference and customer frontend reference. Existing owner/customer
  root rules and Vue/Tailwind skills already require shared controls/public guidance
  and need no duplicate instruction. Sorting stays immediate without row motion;
  existing Button feedback and reduced-motion behavior remain shared. No shell
  table duplicates were found; account/default and test consumer tables use Table.
- No Docker resources created/removed; development/test data, existing sessions,
  credentials/2FA, Qdrant/model caches, backups and reports preserved. No broad
  tests/static analysis/scanners or final point 20; owner visual acceptance pending.

## Point 19x delivery

- Moved trusted-device presentation into the default Two-factor authentication
  Card and removed its separate default navigation tab. Policy-disabled features
  stay hidden; new browser trust remains disabled until a usable enrolled factor
  exists. Management stays available for inspecting/revoking existing trust.
- Extracted module-owned AuthAccountSecurity from the editor. Setup, confirmation,
  email enablement, method removal, recovery replacement and trust issuance now
  run in shared Dialogs; trusted-device lists use a Dialog and row revocation uses
  ConfirmDialog. Existing password/factor requirements remain unchanged: factor
  changes require the existing current password/proof; trust issuance needs the
  existing fresh factor, without an invented password requirement.
- Cancel/Escape/X clears modal password/code/enrollment-key/QR and validation.
  Pending work cannot dismiss/replay. Begin/confirm remains in one dialog, then
  hands one-time recovery codes to the existing acknowledgement-only dialog.
  Shared modal motion/focus/feedback and reduced-motion/print contracts remain;
  retained exit DOM completes the established transition before detaching.
- Added public independent trustedRows/trustedCursor, canTrustDevice,
  loadTrustedDevices and revokeTrustedDevice controller seams. Bounded explicit
  older-page loading and successful-row removal preserve unrelated session/history
  state and account/password/avatar drafts. Retain legacy trust tab/choose/submit
  seams for customer replacements. Fresh browser issuance is additionally gated
  in the controller; backend CredentialFactors already independently requires an
  actual enrolled, permitted factor/code inside SecurityContext and Audit.
- Auth MINOR 0.18.2 -> 0.19.0 records additive presentation/controller capabilities;
  Permissions PATCH 0.5.6 -> 0.5.7 aligns its required Auth range to ^0.19.0.
  Reviewed dependency compatibility, added source-only forward edges and applied
  them locally through ModuleUpdates. Audit stays 0.5.0, platform VERSION 0.4.2 and
  framework API 1.0.0; source/runtime/version diagnostics pass. No schema/settings,
  policy, credential, public backend API or dependency change.
- Ten selected browser-component regressions pass: TOTP lifecycle/QR cleanup,
  recovery acknowledgement, chosen-method removal, email modal/cancel, independent
  password submission, no-factor trust gating, fresh trust proof, isolated bounded
  list/revoke confirmation and CSRF renewal without replay. Fixture selectors were
  migrated to teleported forms/toasts; the optional proof component preserves its
  existing default current-password field. One focused backend case passes with
  17 assertions, including refusal before factor enrollment and preservation of
  trust/step-up/password-change revocation rules.
- Published current client assets with focused Vite builds for continued review.
  Inspected synthetic read-only API previews of the served card/setup/trust/list/
  revoke dialogs at 1440px/390px in both themes: no document overflow or page errors.
  Evidence: .runtime/dev/manual-auth/factor-dialogs. Synthetic setup QR pixels are
  a placeholder; existing private real QR generation is unchanged. No real email,
  account mutation, session issuance or destructive reset was performed by previews.
- Synchronized public account/security/presentation contracts, module READMEs and
  changelogs, owner component guidance and customer frontend reference. Corrected
  the stale Permissions README version/dependency claim. Existing owner/customer
  root rules and Vue/security/backend skills already route to public contracts and
  require server-side authority, so need no duplicate rule. No shell duplicate
  factor controls require migration; the account editor uses the extracted Card.
- No Docker resources created/removed; development/test data, current owner sessions,
  credentials/2FA, Qdrant/model caches, backups and reports preserved. Full suites,
  static analysis/scanners and final point 20 remain deferred; owner visual acceptance
  is still pending.

## Point 19y delivery

- Limited changes to Preferences presentation/controller outcome, with Preferences
  directly after Account and security in the default navigation. Extracted
  AuthAccountPreferences into module-owned source; account/security/session UI
  retains its prior behavior. No application scaffold logic added.
- Reused the accepted independent Card stacks: appearance/language and presentation
  reset on the left, email summaries on the right, stacking on narrow screens.
  Display keeps the existing two compact controls, current values, automatic
  account persistence, shared loading cover and preference feedback. No selects,
  parallel preference store, new dependency or custom animation was introduced.
- Optional email categories have status/recipient summaries and independent copied
  modal drafts. Reused Switch/Checkbox/Form/Dialog with local recipient validation,
  primary/verified-secondary choices and explicit outlined Save/Cancel. Required/
  sensitive categories remain read-only and primary-only. Successful saves refresh
  authoritative categories/account preferences and show a named viewport outcome.
  Conflict reload explicitly discards/reloads the current draft without replay.
- Presentation reset uses ConfirmDialog body copy explaining default appearance/
  navigation and removal of saved table layouts/filters/views. Cancel does not set
  confirmation or write. Reset leaves delivery subscriptions unchanged; failures
  stay in the modal focus layer with explicit CSRF recovery or full-page snapshot
  reload for conflicts. Shared dialog motion/focus/reduced-motion remains unchanged.
- Auth PATCH 0.19.0 -> 0.19.1 records this source-only presentation repair, with a
  preferences_presentation forward edge applied locally. Permissions 0.5.7/^0.19.0
  remains compatible; Audit remains 0.5.0. Source/runtime module checks and scoped
  version/changelog checks against the preceding commit pass. Platform VERSION
  and framework API remain unchanged. No schema, policy, backend API or security
  mutation changed; existing controller persistence/revision guards are retained.
- Five selected browser-component regressions pass: adjacent navigation/Card/read-only
  required summaries, canceled independent drafts/verified recipients/local validation/
  exact revisioned writes, explicit conflict reload without replay, confirmed reset
  versus cancel, and failed category-read recovery without writes. No broad tests
  or backend suite run for this presentation-only change.
- Built/published current client assets and inspected served synthetic API previews
  at 1440px/768px/390px, light/dark and English mobile: page, long-address delivery
  modal, reset dialog and cancellation. No page errors, document/dialog overflow
  or unintended summary mutation. Evidence: .runtime/dev/manual-auth/preferences.
  Previews perform no real account writes/reset/email; actual private data remains.
- Synchronized public account/preferences/presentation docs, Auth README/changelog,
  owner page-pattern reference and customer frontend reference. Existing root
  owner/customer and Vue/Tailwind guidance already mandates shared components,
  isolated drafts, server authority and public replacement contracts; no duplicate
  instructions needed. Module-owned default composition stays replaceable through
  existing Auth presentation/controller contracts.
- No Docker resources created/removed; development/test data, sessions, credentials,
  Qdrant/model caches, backups and reports preserved. Broad final verification and
  point 20 remain gated by explicit owner frontend appearance acceptance.

## Point 19z delivery

- Moved confirmed reset to the right of the Preferences page heading and removed
  its separate Card. Owner explicitly confirmed reset must include email delivery.
  The dialog now names appearance/navigation/table views and email subscriptions/
  recipients. Successful full reset discards unsaved delivery drafts and reloads
  defaults; cancel neither writes nor discards those drafts.
- Preferences now has explicit language and system/light/dark RadioGroups, using
  the existing shared display controller/cover/persistence. Added exact-value
  selection with validation/current-value no-op and origin focus restoration.
  Shell/entry compact controls still cycle through the same helper. Recorded the
  owner's scoped exception in visual-direction; no second preference store.
- Replaced optional notification summaries/edit dialogs with independent inline
  Form rows: Switch, multiple recipient Select and dirty-only Save/Revert actions.
  Off categories keep their recipient selection disabled/preserved. Local drafts
  never mutate read state; writes/revert/reload affect the selected row and preserve
  other dirty rows, including across an authoritative array refresh. Required
  messages have a read-only Always enabled/primary-address explanation.
- Added own HTTP preferences reset_all with required revision and no module/key
  scope. Existing SecurityContext/EntryTransaction and registry/authority/user locks
  delete both user-owned preference tables, advance one revision/set adoption
  fence and explicitly record preferences.reset_all Audit in one transaction.
  Stale revisions/scoped full resets refuse; Audit failure rolls everything back.
  No schema, policy, credentials/session mutation or new dependency. Other accounts
  remain untouched. Existing PHP/scoped reset and no-argument controller reset
  keep presentation-only semantics; the header explicitly calls resetPreferences(true).
  The account adapter queues resetAll and reloads authoritative defaults, without
  automatic replay on conflict/network observation failure.
- Auth MINOR 0.19.1 -> 0.20.0 covers the additive full reset/controller option;
  Permissions PATCH 0.5.7 -> 0.5.8 aligns Auth ^0.20.0. Added source-only forward
  edges, reviewed dependent compatibility and applied them locally through
  ModuleUpdates. Audit stays 0.5.0; PHP API/framework API remain 1.0.0 and platform
  VERSION stays 0.4.2. Source/runtime/version diagnostics pass.
- Seven selected frontend regressions pass for header/explicit controls/inline
  structure, independent dirty rows/revert/recipient validation/revisioned save,
  explicit conflict reload, reset/cancel/legacy reset compatibility, exact display
  value/cover/cycle behavior, failed category reads and queued reset_all versus old
  reset. Two selected backend cases pass (41 assertions), covering legacy scope,
  owner isolation, stale/scoped refusal, defaults, one revision and Audit rollback.
  The final focused header case was rerun after adding the backward-compatible
  includeDelivery option; it passes. Shared the existing composable mount harness
  to satisfy mandatory test-file lint; exact display selection and the existing
  account-switch boundary case both pass with that harness. No broad suites/
  static/scanner gates ran.
- Published client assets and inspected served synthetic API previews at desktop,
  tablet and mobile, light/dark plus English mobile. Verified header placement,
  direct theme application/cover/focus return, dirty row/revert and complete reset
  confirmation, without page errors or document overflow. Evidence remains in
  .runtime/dev/manual-auth/preferences-inline. No real account reset, email or
  browser-session mutation was performed by these previews.
- Synchronized public preference/account/customization/design guidance, both module
  READMEs/changelogs, owner page-pattern reference and customer frontend reference.
  Existing owner/customer root and backend/security/Vue skills already require
  transactional Audit, server authority and replaceable presentation, so need no
  duplicate instruction. Knowledge became stale during source edits; original
  sources were used. Boost docs lookup was attempted; installed Laravel validation
  source and existing transaction patterns confirmed the narrow APIs used.
- No Docker resources created/removed. Preserved development/test data, owner
  sessions/credentials, Qdrant/model caches, backups and reports. Point 20 and full
  verification/release finalization still await explicit owner visual acceptance.

### Point 19za — Module stabilization policy

- Recorded the owner-approved creation-roadmap 0.x.y -> 1.0.0 rule, later
  compatible MINOR/PATCH changes and explicit owner approval before incompatible
  MAJOR implementation. Rewrite size alone does not determine compatibility.
- Updated the canonical public module guide, owner/application release workflows
  and both root instruction products. Existing backend skill references delegate
  version assessment to that guide and need no duplicated procedure.
- Point 20 now explicitly targets Audit/Auth/Permissions 1.0.0 and their dependent
  constraints/install/update edges. No manifest, installed version or runtime code
  changed; platform target remains feature 0.5.0 and final appearance gate remains.
- Knowledge was current and queried before editing. Documentation-only change:
  review the diff and run mandatory commit hooks; no runtime test is warranted.

### Point 19zb — Simplified Preferences and recipient selection

- Stacked both Cards at every viewport and removed the theme/save helper copy and
  Always enabled summary. Mandatory/sensitive security delivery stays server-owned,
  enabled and primary-only; no policy, API or account data change.
- Bound Select's generic multiple prop explicitly to true; the bare attribute
  was falsy at runtime. Verified actual option clicks retain two selected addresses
  and submit their array with the existing revision, validation and isolated drafts.
- Auth PATCH 0.20.0 -> 0.20.1, source-only upgrade edge and central changelog/README.
  Permissions 0.5.8 and its Auth ^0.20.0 constraint remain compatible. Source/runtime
  module diagnostics and module-version checks against the prior HEAD passed.
  Applied only the local source-only update; preserved sessions and account data.
- Two focused frontend cases passed, including actual two-recipient selection and
  revisioned save. Published Vite assets for review. Served-browser diagnostics
  with synthetic API passed at 1440/768/390px, light/dark and English mobile:
  stacked Cards, multiple selection, reset/cover/focus/revert and no errors/overflow.
  Inspected desktop screenshot; evidence remains in workspace-root .runtime.
- Synchronized public account guidance and owner/customer page-pattern references.
  Root/Vue/Tailwind/security guidance already covers these unchanged public seams
  and needs no duplicate rule. No Docker resources created or removed. Final
  verification and release remain gated by explicit owner appearance acceptance.

### Point 19zc — Automatic read reconciliation and local conflict decisions

- Added coalesced, account/view-fenced controller refresh on tab changes, visible
  return, focus/pageshow and HTTP 409. Retain trailing focus invalidations during
  pending reads/busy operations. Current profile/security/options, visible lists,
  loaded trusted devices and delivery categories refresh without remount, polling,
  focus changes or destructive form initialization. Failed reads retain drafts and
  expose Retry loading; rejected writes are never replayed.
- Three-way profile/delivery comparisons merge clean/disjoint values and retain
  dirty identity, secondary email, password/factor and avatar drafts. Overlapping
  fields use shared public FormConflict with current values and explicit local
  accept/keep decisions before a subsequent save. Removed addresses cannot be kept.
  Avatar content lacks a separate revision, so external profile changes with an
  image draft conservatively require local review and show the current server image.
- Serialized presentation and delivery mutations through the shared account
  preference queue; delivery re-reads/compares its category within the queue before
  using a newer revision. A counter update alone cannot authorize an unreconciled
  overwrite. Conflicts refresh reads and cancel older queued mutation intents.
  Same-account session refresh retains the cache until replacement; logout, account
  switch, forbidden/restricted sessions and late results retain their fences.
- Auth MINOR 0.20.1 -> 0.21.0 adds public refresh/detail/avatar resolution seams and
  optional secondary baselineEmail; Permissions PATCH 0.5.8 -> 0.5.9 aligns Auth
  ^0.21.0. Added source-only forward edges, central changelogs and current READMEs.
  Existing PHP API/framework versions and Audit remain unchanged. Scoped source,
  runtime and prior-HEAD module-version diagnostics passed; applied local source-only
  updates without resetting databases or changing owner sessions/credentials.
- Focused browser-runner diagnostics cover revision serialization, automatic conflict
  reads/no replay, canceled queued intents, disjoint/overlapping fields, preserved
  files/passwords, secondary edits, trailing focus, same-account cache and forbidden
  session clearing. Existing scoped session/401/account-switch, independent avatar/
  password, factor disable and trusted-device management regressions passed.
- Served-browser synthetic API previews passed at 1440/768/390px, light/dark and EN:
  actual display then multi-recipient delivery saves share revisions; focus refresh
  retains a dirty name, updates untouched login and presents/resolves local conflict
  without a write. No page errors/overflow. Inspected mobile local-conflict screenshot;
  preserved evidence under .runtime/dev/manual-auth/account-refresh. Published assets.
- Updated canonical public forms/components/account/preferences/customization guides,
  root owner/customer rules, owner/customer frontend skills and Vue async references.
  Backend/security skills retain their existing server-authority, transaction,
  cancellation and secret-cleanup rules; no duplicate rule needed. No dependency,
  schema, authorization policy or Docker resource change. No broad/final gate run;
  point 20 remains gated by explicit owner appearance acceptance.

### Point 19zd — Concise account copy and active trust management

- Removed byte counts from PL/EN password hints while preserving configured minimum,
  complexity rules and existing byte validation. Removed the requested confirmed-email,
  secondary-address introduction, avatar instructions, factor summary/status lines,
  trust explanation and delivery-editor introduction. Kept actionable unverified-email
  and missing-enrollment guidance. Centered the avatar preview within its Card.
- Device management appears only for a nonempty current guarded trustedRows list;
  expired/revoked/missing devices do not keep it visible. Read the existing list at
  initialization, visible refresh and after security changes; preserve independent
  pagination and busy state. No historical marker, database migration, endpoint or
  authentication/authorization change.
- Auth PATCH 0.21.0 -> 0.21.1 with a source-only forward edge and central changelog/
  README. Permissions 0.5.9 and Auth ^0.21.0 remain compatible; Audit/framework and
  platform versions unchanged. Local source-only update and scoped runtime/version
  diagnostics passed without resetting data or changing owner sessions/credentials.
- Focused frontend diagnostics passed for empty/active trust visibility, disappearance
  after refresh, guarded issuance, cursor/revocation dialogs and retained byte-limit
  validation in entry forms. The dialog pagination regression now waits for its
  refresh to finish before invoking another request against preloaded rows.
- Published Vite assets. Served-browser previews with synthetic API passed at
  1440/768/390px, light/dark and EN: requested copy absent, centered avatar, hidden
  empty manager, shared preference revisions and nondestructive refresh preserved.
  Inspected mobile avatar/security screenshots; no page errors/overflow. Evidence
  remains under .runtime/dev/manual-auth/account-copy.
- Updated public account UI guidance and owner/customer page-pattern references.
  Root/frontend/Vue/Tailwind/security/backend instructions already require concise
  copy, shared primitives and server authority; no duplicate rule needed. No external
  dependency or Docker resource changes. Final point 20 remains appearance-gated;
  no broad/final verification started.

### Point 19ze — Final factor-row spacing

- Owner requests removal of the excess gap below Email codes. Remove the final
  method row's bottom padding; keep the shared section gap and trust-section
  border/padding. Static spacing correction, no motion or behavior change.
- Auth PATCH 0.21.1 -> 0.21.2 with source-only update edge and central changelog/
  README. Required dependents remain compatible. Existing layout skills and
  owner/customer guidance need no new reusable rule for this local spacing fix.
- Publish assets and inspect served layout spacing; no new test for a reversible
  class-only correction. Final point 20 remains appearance-gated.

### Point 19zf — Form actions stay with their fields

- Removed the horizontal border and extra top padding from the shared Form actions
  slot, retaining its 24px margin and wrapping button layout. Account details,
  password, avatar and Preferences now share this default; independent section
  separators remain. Static presentation requires no motion.
- Published current Vite assets for manual review. Focused served-browser diagnostics
  with synthetic API confirmed computed zero border/padding and 24px action spacing
  for account and dirty Preferences forms at desktop/light and mobile/dark sizes,
  with no overflow or page errors. Inspected the desktop account screenshot;
  evidence remains under .runtime/dev/manual-auth/form-actions.
- Updated the public forms contract and owner/customer frontend references. Root,
  Vue and Tailwind instructions already require shared components; no duplicate
  guidance is needed. No module-owned source/API/dependency changed, so independent
  module versions remain unchanged. No schema, dependency or Docker resource change.
  No new tests for this reversible class-only correction. Final point 20 remains
  appearance-gated; no broad verification or release started.

### Point 19zg — Remove redundant trust heading

- Removed the trusted-device subsection heading from the two-factor Card in PL/EN;
  retained actions, enrollment prerequisites, independent separator and dialog titles.
- Auth PATCH 0.21.2 -> 0.21.3 uses the existing no-op presentation upgrade handler
  for its source-only forward edge. Updated central module changelog/README and
  public account presentation guidance. Permissions ^0.21.0 remains compatible.
- Published Vite assets; focused served-browser inspection with synthetic API checks
  the missing heading at desktop/light and mobile/dark sizes. No overflow/page errors.
  Source/runtime module contracts and prior-HEAD module-version diagnostics passed
  after supported registry synchronization. Applied only the pending source-only
  Auth upgrade through the existing local preparation diagnostic: no scoped module
  update CLI exists, so the fixture invokes the guarded ModuleUpdates API directly.
  The initial browser check could not enter the pending module; it passed after
  this update. No data reset or security change.
- Existing owner/customer guidance already requires concise copy and delegates to
  the updated public account guide; no new reusable instruction is necessary.
  No dependency or Docker resources changed. Final point 20 remains gated.

### Point 19zh — Inline secondary-address statuses

- Placed existing status Badges beside each secondary email using a wrapping,
  vertically centered flex row. Long addresses break within the available width;
  statuses can wrap on narrow screens. Static layout, no motion/behavior change.
- Auth PATCH 0.21.3 -> 0.21.4 with a source-only edge using the existing no-op
  presentation handler; central module changelog/README and public account UI guide
  synchronized. Permissions ^0.21.0 remains compatible. Existing owner/customer
  references already delegate to that guide; no new reusable instruction needed.
- Published Vite assets and applied only the pending source-only update through
  the existing local ModuleUpdates diagnostic (no scoped module-update CLI).
  Runtime source contracts and prior-HEAD version checks passed. Focused served
  synthetic-API browser diagnostics confirmed verified/pending badges beside emails
  at desktop/light width and long-address wrapping without overflow at mobile/dark
  width; no page errors. Inspected desktop evidence under
  .runtime/dev/manual-auth/secondary-status. No new tests for this class-only fix.
- No dependencies, schemas, security policies or Docker resources changed. Final
  point 20 remains appearance-gated; no broad verification/release started.

### Point 19zi — Restore development setup after Auth type errors

- Fixed strict TypeScript failures in Auth defaults, custom-entry fixture and
  existing Auth tests: explicit callback bindings preserve controller-owned form
  and heading refs; preference form refs use the exposed Form instance type;
  removed unused actions. Fixtures use the public Profile type, generic Select
  wrappers retain their actual props and existence assertions use find wrappers.
- Auth compatible PATCH 0.21.4 -> 0.21.5, with the existing no-op source-only
  upgrade handler and a new explicit edge. Permissions ^0.21.0 remains compatible;
  no public API, security, schema or dependency changes. Updated central Auth
  version/changelog and public customization guidance. Owner/customer Vue guidance
  already delegates to that public contract; no separate instruction change needed.
- Concrete startup diagnostics: typecheck passed; four selected Chromium tests
  passed (CSRF validation focus, inline recipients, avatar refresh and disjoint
  delivery refresh). Existing tests cover the corrected behavior; no new test
  harness needed. The supported development setup completed its previously failing
  build successfully. Source/runtime module checks and prior-HEAD module version
  checks passed. Full final verification remains pending behind point 20's owner
  appearance acceptance gate.
- Supported host development startup completed; setup exited 0, app/PostgreSQL,
  Nginx, queue and scheduler are healthy; GET /auth/login returned HTTP 200.
  Compose recreated app/setup/queue/scheduler/knowledge-init during startup;
  no disposable Docker resources were created for tests or removed. Development
  and test data, Qdrant/model caches, backups and reports were preserved.
- Synchronized module metadata and applied only the pending Auth source-only edge
  through a local guarded ModuleUpdates diagnostic (no scoped update CLI exists),
  refusing pending migrations or unrelated upgrades. No reset/migration ran.

### Point 19zj — Structural initial account loading

- Keep the destination ApplicationLayout mounted during the initial session read,
  failure/retry and account initialization. Shared AuthAccountLoading uses the
  existing Skeleton in four named Cards and navigation placeholders; preferences
  render notification-row skeletons. No credentials/identity or protected forms
  render before guarded session/account reads. Existing in-place read refresh and
  reauthentication remain unchanged. Skeleton pulse stops for reduced motion/print;
  content replacement is immediate, without entrance animation or artificial delay.
- Public AuthAccountLoading, accountLoading presentation entry and application
  loading slot preserve customer composition. Auth PATCH 0.21.5 -> 0.21.6 uses an
  existing source-only no-op upgrade; no schema/security change. Permissions
  ^0.21.0 remains compatible; Audit/Permissions contracts and versions unchanged.
- Knowledge was current (175 sources/2659 chunks); focused retrieval and original
  account/presentation/design/module/command sources reviewed. One focused browser
  unit regression passes for deferred session/profile reads, persistent shell and
  no early protected Forms. Published Vite assets for review. Served synthetic-API
  diagnostics pass at 1440/768/390 widths, light/dark, PL/EN and normal/reduced
  motion, including skeleton geometry and notification replacement; no page errors
  or overflow. Inspected desktop screenshot. Evidence is under
  .runtime/artifacts/playwright/structural-loading. Initial visual inspection found
  child Skeleton selector specificity overriding placeholder sizes; fixed and
  repeated the rendered diagnostics with explicit 44px field-height assertions.
- Source/runtime module checks and prior-HEAD independent version checks pass.
  The runtime check initially omitted its required owner argument; the supported
  command with runa succeeds. Registry synchronized; applied exactly the pending
  source-only Auth structural_loading edge using the existing local guarded
  ModuleUpdates diagnostic (no scoped module-update CLI). No data reset.
- Public account/presentation docs and both owner/customer frontend instruction
  products describe the capability. Root AGENTS needs no change: the universal
  future-view rule remains pending the owner's visual acceptance, as requested.
  No dependency or Docker resources created/removed. Development/test data, running
  services, Qdrant/model caches, backups and evidence preserved. Full verification,
  release and final appearance acceptance remain gated at point 20.

### Point 19zk — Table read loading indicators

- Shared Table now accepts an optional external loading flag, combined with its
  existing provider loading state. Loading takes precedence over empty/success
  content and uses the existing AsyncContent Spinner. Account session/history
  reads and trusted-device dialog reads expose dedicated epoch-fenced indicators
  reset in finally; mutation busy state does not masquerade as a table read.
- Auth PATCH 0.21.6 -> 0.21.7, source-only forward edge using the existing no-op
  presentation handler. Permissions ^0.21.0 remains compatible; no dependent API,
  database or security-policy change. Registry synchronized and exactly the pending
  table_loading edge applied through the existing guarded local ModuleUpdates
  diagnostic; no scoped update CLI exists. Source/runtime and prior-HEAD independent
  module-version checks pass.
- Knowledge was current and focused table retrieval/original sources reviewed.
  One deferred-session browser unit regression passes: no empty message while
  pending, then empty state after the completed empty response. Published Vite assets
  for owner review. Served synthetic-API diagnostics at 1440/768/390, both themes,
  PL/EN and normal/reduced motion confirm the spinner-before-empty sequence with
  no overflow/page errors. Evidence: .runtime/artifacts/playwright/table-loading.
  Broad verification remains gated at point 20.
- Public tables/account guidance, owner table reference and customer frontend
  product synchronized. Root AGENTS already requires correct component states;
  no constitution change or new universal skeleton policy. No dependencies or
  Docker resources created/removed; development/test data, services, Qdrant/model
  caches, backups and verification evidence preserved.

### Point 19zl — One table read and centered spinner

- Table-tab entry previously called readPage directly and then refreshAccount,
  which called readPage again after profile/security reads. Use one refresh pipeline
  under the operation boundary, keeping pageLoading true from tab selection through
  the list response. Ordinary automatic account refresh remains available.
- Shared Table overrides only AsyncContent's loading slot: centered Spinner in a
  stable loading area with no visible text, retaining its localized status label.
  Other AsyncContent consumers retain their existing presentation.
- Auth PATCH 0.21.7 -> 0.21.8, source-only no-op forward edge. Permissions ^0.21.0
  remains compatible; no foreign contracts, schema, dependency or security change.
  Registry synchronized and exactly single_table_read applied through the existing
  local guarded ModuleUpdates diagnostic (no scoped module update command).
- Knowledge was current; focused retrieval and original account/table contracts
  reviewed. Focused browser unit regression passes with a deferred response,
  request-count assertion and accessible spinner/no visible loading text. Published
  Vite assets; served synthetic-API diagnostics pass at 1440/768/390, light/dark,
  PL/EN and normal/reduced motion, asserting one list request, spinner center on
  both axes, absent visible label, completed empty result and no subsequent spinner.
  No overflow/page errors; evidence: .runtime/artifacts/playwright/single-table-read.
  Source/runtime and prior-HEAD module-version checks pass. Broad verification stays
  gated at point 20.
- Updated public tables/account docs and central Auth changelog/README. Owner and
  customer instruction products already delegate table loading behavior to these
  public guides and require correct asynchronous states; no new rule/API needed.
  No Docker resources created/removed. Existing development/test data, services,
  Qdrant/model caches, backups and verification evidence preserved.

### Point 19zm — Shared table query feedback

- Shared Table observes controlled page/page-size/sort/search/filter identity and
  displays one centered spinner for at least the existing 140ms motion interval.
  Calculation/provider requests are not delayed; external/provider reads extend
  the same loading state. Rapid query changes replace one presentation timer;
  disposal clears it. Initial reads retain their existing behavior. Selection and
  column visibility/width do not trigger query feedback. Reduced motion keeps the
  spinner static. Sorting restores header focus only if focus was not moved away.
- Knowledge was current; focused table retrieval and original public table/motion
  guidance reviewed. Five necessary focused browser unit regressions pass for
  all five query dimensions in client/server modes, one provider call per query,
  rapid changes/slow responses, semantic empty recovery and existing sort contracts.
  The existing local-sort test needed to await visible completion instead of
  asserting rows during the newly requested spinner interval; corrected and rerun.
- Published Vite assets. Served synthetic-API diagnostics at desktop/light and
  mobile/dark, normal/reduced motion confirm sort/pagination spinners, retained
  keyboard sort focus and exactly one initial API read for subsequent local
  queries, without page errors/overflow. Evidence:
  .runtime/artifacts/playwright/table-query-feedback. Final point 20 remains gated;
  no broad verification or release started.
- Public tables/motion docs and owner/customer frontend guidance synchronized;
  consumers use the shared behavior without local timers or duplicate reads.
  Root AGENTS already requires shared components and correct asynchronous states.
  No Auth/module source or API changed, so independent module versions remain
  unchanged; platform feature 0.5.0 is still projected under this active roadmap.
  No dependencies, runtime configuration, database or Docker resources changed.
  Existing services, development/test data, Qdrant/model caches, backups and evidence
  preserved. The separate universal page-skeleton rule still awaits owner approval.

### Point 19zn — MinimalLayout constellation-point glow

- MinimalLayout alone opts into the existing shared backdrop's constellationGlow
  effect; public constellationGlow=false opts out. ApplicationLayout leaves the
  backdrop default false. Static preferences/native-error output remain static.
  Artwork, lines, positions and gradients are unchanged; no page entrance effect.
- One randomly selected viewport-visible point brightens across its three existing
  circles with a gentle 3000ms opacity pulse, then waits 7–15 seconds before the
  next different point. No overlapping pulses or permanent animation loops.
  Reduced motion, print, hidden documents and opt-out cancel animations/timers;
  unmount also removes media/visibility listeners. No third-party dependency.
- Knowledge was current; focused retrieval and original layout/backdrop/design/
  motion contracts reviewed. Two focused browser unit regressions pass for static
  default, one-point pulse, quiet interval/no immediate repeat, disposal and live
  reduced-motion/print/visibility/opt-out cancellation. Focused changed-file lint
  passes. Published Vite assets and inspected the desktop/light glow screenshot.
- Served synthetic-API diagnostics with deterministic random selection pass for
  desktop/light and mobile/dark MinimalLayout, reduced-motion MinimalLayout and
  normal-motion ApplicationLayout. They observe actual point opacity animation,
  live media cancellation and static ApplicationLayout, with no page errors or
  overflow. The initial preview checked media cancellation before the asynchronous
  browser change event; awaiting that event's observable result corrected the
  diagnostic. Evidence: .runtime/artifacts/playwright/constellation-glow.
- Public component/layout/motion/design guidance and owner/customer frontend
  instruction products synchronized. Root AGENTS already covers shared-component
  isolation/accessibility; no constitution edit needed. No Auth/module source/API
  changes, independent version changes, runtime configuration or database changes.
  No Docker resources created/removed; development/test data, active services,
  Qdrant/model caches, backups and evidence preserved. Full point 20 remains gated
  by owner appearance acceptance; no broad verification/release started.

### Point 19zo — Temporary all-point glow preview

- Owner-requested temporary MinimalLayout preview enables all 21 constellation
  points together, first after three seconds and then every three seconds. Keep
  the baseline three-second opacity pulse/levels so the owner can assess strength.
  The internal backdrop preview flag defaults false; MinimalLayout alone enables
  it. Sparse randomized baseline remains available when preview is removed.
- ApplicationLayout remains static; reduced-motion/print/hidden-tab/static-output
  cancellation and teardown are retained. Focused preview regression passes for
  all circles, exact cadence and timer disposal; changed-file lint passes. Published
  Vite assets. Served synthetic-API inspection on / passes desktop/light and mobile/
  dark, all 21 points pulsing together, reduced-motion cancellation and static
  ApplicationLayout, with no overflow/page errors. Evidence:
  .runtime/artifacts/playwright/constellation-preview.
- Public layout/motion docs distinguish the temporary preview from the sparse
  baseline. Owner/customer guidance and root AGENTS need no new rule: this is a
  temporary visual comparison, not a reusable policy or additional public API.
  No module/version, dependency, runtime-configuration, database or Docker changes.
  Existing services/data, Qdrant/model caches, backups and evidence preserved.
  Final point 20 remains appearance-gated; no broad verification/release started.

### Point 19zp — Restore sparse glow and add complete point orbits

- Removed the temporary all-point preview flag/test and restored sparse randomized
  background glow after owner acceptance. Interactive MinimalLayout alone also
  opts its default illustration into six-point orbit motion, with public
  orbitMotion=false opt-out; custom illustration slots own their presentation.
- Replaced the illustration's decorative pseudo-points with matching real child
  elements. Each follows its parent's stationary ellipse through one six-second
  lap, using 73 percentage-position frames and three gentle opacity pulses, then
  returns precisely to its original position. Logo/lines/art composition do not
  rotate. Random quiet intervals are 8–14 seconds before/between laps.
- Both effects reuse the private useDecorativeMotion scheduler rather than
  duplicate lifecycle logic. Reduced motion, print, hidden documents, opt-out and
  unmount cancel work; static-preference/native errors remain static. Default
  illustration/backdrop remain static unless explicitly opted in by MinimalLayout.
- Knowledge was current; focused retrieval/original illustration/layout/motion/CSS
  contracts reviewed. Three focused browser unit regressions pass after extraction:
  sparse glow/interval/no repeat, media/visibility/opt-out/disposal, and six closed
  point paths/pulsation/rest. Changed-file lint passes. Published Vite assets.
- Served synthetic-API inspection with controlled random selection passes for /
  desktop/dark and mobile/light: sparse background, six travelling points, fixed
  line/logo transforms and exact original point positions after completion. Reduced
  motion and ApplicationLayout stay static; no page errors/overflow. Inspected
  desktop during-motion screenshot. Evidence: .runtime/artifacts/playwright/orbit-motion.
- Public component/layout/motion/design docs and owner/customer frontend references
  synchronized, removing obsolete temporary-preview/current-static-point claims.
  Root AGENTS requires no new constitution rule. No module source/API/version,
  dependency, runtime configuration or database changes. No Docker resources
  created/removed; existing services/data, Qdrant/model caches, backups and evidence
  preserved. Final point 20 remains gated by owner appearance acceptance.

### Point 19zq — Single random illustration point

- Illustration motion now randomly selects one of its six points, excluding the
  previous selection. Only that point follows the existing six-second closed lap
  and pulses; five others remain static. Existing 8–14 second quiet intervals,
  MinimalLayout-only opt-in and lifecycle/reduced-motion guards are unchanged.
- Knowledge was current and the current point/component/motion contracts reviewed.
  Updated focused browser regression passes for one animation, complete path,
  rest interval, a different second selection and cancellation. Published Vite
  assets. Served synthetic-API diagnostics at desktop/dark and mobile/light verify
  exactly one moving point, five stationary points, fixed lines/logo and return
  to the original position. Reduced-motion/ApplicationLayout remain static, without
  overflow/page errors. Evidence: .runtime/artifacts/playwright/single-orbit-motion.
- Public layout/motion/design guidance synchronized. Existing component and owner/
  customer frontend references already delegate to those contracts; no additional
  instruction rule or public API needed. No module/version, dependency, runtime
  configuration, database or Docker changes. Existing data/services, Qdrant/model
  caches, backups and evidence preserved. Full point 20 remains appearance-gated.

### Point 19zr — Disposable test resource lifecycle and scoped retirement

- Owner-approved tooling correction in the existing feature 0.5.0 scope. Knowledge
  was current; reviewed the active point, testing/command/database ownership and
  Docker safety sources. Loaded Docker, PostgreSQL, backend, Laravel and security
  guidance; checked version-aware Boost parallel-testing documentation.
- Supported owner/application backend runners and native Artisan test now share
  suite-locked worker cleanup before/after runs. Preserve base runa_test and reject
  foreign ownership or active worker connections before any removal. Canonical
  worker names only; ordinary DROP DATABASE, no forced connection termination.
  The child validates testing host/role/base and explicitly returns failure even
  after Laravel installs its exception renderer. Failure never becomes PASS.
  Read-only native discovery remains available inside an existing suite.
- Automatic host browser startup records a unique run label before Compose starts,
  labels only missing testing services and never recreates/adopts manual endpoints.
  Exact project/checkout/service/run identity checks govern teardown and journaled
  recovery after failure/interruption. Active suites block recovery. No Docker
  socket, global prune, volume reset or application/production runtime changes.
- Scoped retirement inspected actual database owners/connections and container
  labels/mounts/state: removed 18 inactive test-role worker databases (13 ordinary
  plus five historical double-suffix names), runa-dev-nginx-test-1 and the old
  runa_devcontainer nginx/php pair, then its empty default network. Preserved runa,
  runa_test, all volumes, nine healthy running development services, management
  image/build caches, Qdrant/models, backups, reports and unrelated projects.
  Evidence: .runtime/artifacts/test-resource-cleanup-2026-10-25.json.
- Focused checks: eight TestResourceContracts cover ownership, manual preservation,
  partial startup/interruption recovery, busy-suite refusal and runner failure
  propagation. Four parallel owner regressions cover worker identity, active-worker
  refusal, production refusal and native discovery/competing-run rejection. One
  native application WelcomeTest passes in parallel and its worker is removed.
  Owner test-e2e --list exercises real endpoint startup/health and exact teardown
  of both newly created labeled containers, without executing browser acceptance.
  Initial focused failures exposed Laravel's zero-exit exception renderer and a
  native option-order requirement; corrected cleanup exit handling and used the
  native without-tty option first. No broad gates or image builds were run.
- Public testing/commands and maintainer strategy synchronized; owner/customer
  AGENTS, Docker and Laravel references now delegate to the public lifecycle.
  PostgreSQL/backend/security skills already route to these authoritative contracts
  and require no duplicate procedure. No module contract/manifest/version,
  dependency or schema migration changes. Final point 20 remains pending owner
  frontend appearance acceptance; these focused results are not its final gate.

## Point 19zs delivery

- Replaced the welcome placeholder with `.runa` and a concise PL/EN description of
  the Laravel-based modular monolith. Delivered Audit/Auth/Permissions icon tiles
  reuse brand-mark styling and shared Tooltip pointer/focus/Escape behavior.
- Platform-owned build-time discovery reads only Runa public manifest metadata;
  each catalogue item projects identity/name/description, never live installation,
  availability or private permission state. Auth remains a presentation consumer.
  Generic fallback protects development; authoring completion requires a module icon.
- Auth compatible cosmetic PATCH 0.21.8 -> 0.21.9 with a source-only forward edge;
  existing Permissions ^0.21.x dependency remains compatible. No API/session/security
  behavior, schema, dependency or infrastructure changes.
- Module development, public presentation/component/asset guides and owner frontend
  reference synchronized. Customer frontend reference explains its public replacement
  seam without authorizing protected edits. Root AGENTS and other skills already
  delegate to these guides and need no duplicated procedure.
- Source module and prior-HEAD version checks pass. Built current client assets for
  owner review with the existing Vite build entry through bin/pnpm. Applied the
  source-only module edge in development and prepared the isolated base test module
  state using the forward-only preparation primitive; no data reset or new migration.
- Eight focused Chromium welcome cases pass: desktop/mobile, PL/EN, light/dark,
  manifest copy, icon count, hover, keyboard tooltip transition, Escape and overflow.
  Visually inspected actual desktop light/dark and mobile dark screenshots under
  .runtime/artifacts/playwright/ui-foundations. Source/runtime descriptor checks pass.
- Initial browser attempt could not resolve the absent optional nginx-test endpoint;
  used the documented RUNA_E2E_UPSTREAM=http://nginx:8080 for this local check.
  Updated the historical root assertion to allow only the expected signed-out session
  endpoint 401 and its matching browser resource message. Tooltip assertions now await
  the existing exit transition before matching the next item. No product error was
  suppressed; other network/console errors still fail.
- No temporary Docker resources were created or removed. Preserved development/test
  databases, active services, caches, backups and browser evidence. No broad gate run;
  final point 20 remains gated on owner appearance acceptance.

## Point 19zt delivery

- ApplicationLayout presents the default Runa identity as lowercase `runa.` while
  preserving custom application names. Both authenticated routes use the shared
  Breadcrumbs slot: Home / Application and Home / Application / User panel.
- Shared Avatar exposes additive loading metadata and uses the existing circular
  Skeleton until profile discovery and image load complete. No blank-image/initials
  intermediate state; absent/failed images retain initials. Source changes retry.
  Header and dropdown share the primitive; account previews inherit image readiness.
- The session controller exposes profileLoading without blocking optional profile
  discovery or private workspace mounting. Epoch/disposal and request-generation
  checks fence completion; existing identity/security behavior remains unchanged.
- Auth compatible PATCH 0.21.9 -> 0.21.10, source-only forward edge. Current
  Permissions dependency remains compatible; no schema, dependency or Docker change.
- Public component/motion/customization guides and customer frontend reference
  synchronized, including breadcrumbs and userLoading forwarding. Existing root
  AGENTS/owner frontend/Vue/Tailwind references already delegate to these public
  contracts; no duplicate procedure is necessary.
- Four focused browser unit cases pass: image readiness/error/retry, delayed profile
  metadata, existing dropdown integration and optional-read disposal fencing. Initial
  delayed-profile test resolved only one of the account screen's concurrent reads;
  a shared gate now releases all reads and preserves the actual optional behavior.
- Two focused Playwright cases cover /app desktop and /account mobile with separately
  delayed profile and image responses, both menu avatars, branding and breadcrumb
  semantics. Current client assets built for review. Source and prior-HEAD module
  version checks pass. The original loading-state checks did not establish visible
  pulse geometry; point 19zu adds exact bounds/opacity/reduced-motion assertions.
  Applied the exact-source forward module edge in development and isolated base
  testing; runtime descriptor checks pass. No data reset or migration.
  Inspected rendered loading/loaded menu and account screenshots under
  .runtime/artifacts/playwright/avatar-*.png.
- No temporary Docker resources created or removed. Development/test data, active
  services, caches, backups and evidence preserved. Final point 20 remains open
  behind explicit owner visual acceptance; no broad verification started.

## Point 19zu delivery

- Verified that disabled welcome previously left brand links at / and retained Home
  breadcrumbs. Auth now reads the existing public entry-options projection as an
  optional, epoch/generation-fenced presentation read. welcomeEnabled controls the
  shell homeHref and breadcrumb root without delaying session/workspace mounting.
  Unknown/failed public policy conservatively uses /app and omits Home.
- Both ApplicationLayout brand locations and MinimalLayout accept homeHref. Default
  authenticated, public Auth entry and welcome presentations pass the destination;
  generic layout defaults remain /. Server root-to-sign-in behavior is unchanged.
- Diagnosed the avatar with a focused rendered probe: its animation was running,
  but the Skeleton resolved to 44px over a steady avatar backing fill. Loading now
  removes that fill and absolutely fits the shared Skeleton to the exact avatar
  bounds; the full placeholder inherits content-skeleton pulse/reduced-motion rules.
- Auth PATCH 0.21.10 -> 0.21.11, source-only forward edge; dependent Permissions
  constraint remains compatible. No schema/dependency/authorization changes.
- Public component, customization and motion contracts plus customer frontend
  reference synchronized. Existing root/owner skills already delegate to these
  contracts and need no duplicated rules. Corrected the prior point's overly broad
  motion-evidence claim instead of treating CSS animation presence as visible proof.
- Five focused Chromium cases pass: enabled/disabled welcome on /app and /account,
  public Auth branding, both 40px menu placeholders, transparent loading backing,
  actual opacity 1 -> 0.45 at the shared pulse midpoint, reduced-motion disable,
  separate delayed profile/image completion and breadcrumb/link semantics. Four
  browser unit regressions pass after awaiting the optional navigation policy.
  Source and prior-HEAD module version checks pass; current client assets built.
  Published the exact-source forward edge locally in development and isolated base
  testing; runtime module checks pass. No resets or new migrations. No full quality
  gates started.
- No temporary Docker resources created/removed. Active development, both base
  databases, caches, backups and browser evidence preserved. Final point 20 remains
  behind owner appearance acceptance.

## Point 19zv delivery

- Removed the PL/EN account-menu caption and unused caption CSS. Avatar/name remain.
- Account deactivation uses warning, deletion uses the existing danger/error palette.
  Both entry buttons explicitly use outline because neutral variants do not display
  semantic tones. Confirmation submits preserve the matching operation tone.
- Auth cosmetic PATCH 0.21.11 -> 0.21.12 with source-only edge; dependent constraints
  remain compatible. No behavior, schema, dependency or security-policy changes.
- Public presentation guide/changelog synchronized. Existing root/owner/customer
  skills already delegate to public presentation/components; no reusable contract
  or new procedure requires duplicate instruction-product edits.
- Client assets built; source and prior-HEAD module-version checks pass. Published
  the source-only forward edge in development and isolated base testing; runtime
  module checks pass without reset or new migrations. This small color/copy change
  needs no new tests; shared Button handles theme, interaction and reduced motion.
  No temporary Docker resources created/removed; data, services, caches, backups and
  evidence preserved. Final point 20 still awaits owner appearance acceptance.

## Point 19zw delivery

- Replaced the blanket non-login unsafe-method Auth cap with a source-owned list
  of real entry/proof route operations. Normal preference reads/writes, profile,
  history, avatar, activity, logout and session revocation never consume this cap.
  Buckets are independent by operation/source, retaining 30/minute and safe 429/
  Retry-After behavior; clients cannot opt out or forge module route metadata.
- Added the neutral AuthRequestThrottle seam and no-integration binding. An active
  future module handles a classified request exclusively; false means absent/disabled
  before any enforcement. Rejections/faults propagate without weaker fallback.
  Future bridges must check lifecycle/availability each call. Login reaches the hook
  but keeps its transactional intrinsic account/source protections; proof budgets,
  expiry, one-time use, eligibility/permissions and Audit remain Auth-owned.
- Preferences now enter through one account refresh pipeline. Its own snapshot
  suppresses only its redundant delivery subscriber read; external invalidation still
  refreshes visible rows. Removed the separate tab-entry delivery read. Dirty drafts,
  per-row conflict resolution and the shared mutation revision queue remain intact.
- Auth PATCH 0.21.12 -> 0.21.13 with source-only edge; existing Permissions constraint
  remains compatible. No dependency, schema migration or runtime configuration change.
- Public throttling/transport/session/security/preferences contracts and owner/customer
  security references synchronized. Deferred module register records optional exclusive
  takeover; root and backend/Laravel/frontend skills already delegate to these guides.
- Focused checks pass: nine request-cap cases cover targeted 429s, normal operations,
  separate buckets/sources, adapter ownership/disable/failure and login distinction.
  Two real Auth transport/abuse regressions pass, including 70 preference/delivery
  reads plus persisted writes after registration exhausts its cap. Five browser unit
  cases prove one delivery read per tab/refresh, retained external invalidation and
  dirty/conflict behavior. Source/version checks pass; current client assets built.
- Supported runner removed its disposable runa_test_test_1 worker after the focused
  parallel run. No Docker resources created/removed. Base development/test databases,
  services, caches, backups and evidence preserved. Published the exact-source
  forward edge in development and isolated base testing; runtime module checks pass.
- Owner clarified that the future optional Throttling module was informational,
  not agreed planning scope. Removed the prematurely authored draft and release/
  ordering proposals. Retain only the deferred ownership constraint: modules will
  be planned incrementally, and Throttling remains distant and unscheduled.
  Final point 20 remains appearance-gated; no broad checks or release started.

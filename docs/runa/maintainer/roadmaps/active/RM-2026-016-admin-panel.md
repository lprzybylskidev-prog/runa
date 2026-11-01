---
id: RM-2026-016
title: Admin Panel Core administration framework
status: active
branch: roadmap/RM-2026-016-admin-panel
created_at: 2026-10-29
activated_at: 2026-10-29
closed_at: null
release_type: feature
proposed_version: 0.7.0
prepared_version: 0.7.0
prepared_version_approved_at: 2026-10-31
prepared_version_reason: Owner-authorized runnable frontend acceptance; Core targets 0.7.0 while released 0.6.2 runtime and stale local module installation caused HTTP 503.
quality_groups: docs,backend,frontend,e2e,database,delivery,installation,updates,security-source,tooling
quality_rationale: Provisional affected-area scope for the new mandatory Core, public declarations and adapters, transactional storage, rendered UI and protected customer extension/generator contracts. Native installation/updates verify actual Core forward history and preservation. Security-source covers new authenticated operations and composition. Tooling covers prepared-candidate release validation added for owner manual acceptance. Mandatory baseline/hooks and real prerequisites remain required. No infrastructure/image or knowledge-runtime change is currently proposed and no all-group milestone is due at 0.7.0.
impact_areas:
  - backend
  - frontend
  - database
  - security
  - installation
  - updates
  - documentation
---

# RM-2026-016 — Admin Panel Core administration framework

## Overview

### What and why

Create `runa.admin`, the final planned Core module, as a Polish/English administration
framework using the existing Application shell. Module authors declare reusable
pages, entities, settings, actions, widgets and cross-module contributions once;
installed, enabled, available and authorized contributions appear automatically.
Customers retain application-owned partial and full presentation/backend extension
through documented public entrypoints, without protected edits or copied security.

Baseline: released 0.6.2 after closed
[RM-2026-015](../closed/RM-2026-015-proportionate-quality-and-fixture-reuse.md).
The owner confirmed `feature` / `0.7.0` on 2026-10-29:
a MINOR increment for substantial compatible functionality. The owner explicitly
requested activation on 2026-10-29 after confirming ARCH-001–004. Baseline and
predecessor order remain current. Activation does not change VERSION or approve
an incompatible module MAJOR.

2026-10-31 owner amendment: the entire supplied Admin panel now supports Polish
(default) and English through the existing application preference. This supersedes
all earlier English-only presentation decisions/evidence in this roadmap. Technical
contracts, identifiers and operational output remain English. Translation changes
must preserve session/proof ownership, grants and dirty drafts.

### Scope and exclusions

The [architecture review](../../architecture/RM-2026-016-admin-review.md) contains
the source inventory, complete Core API matrix, architecture, configuration audit,
four approval decisions, acceptance cases and quality rationale. It is part of
this roadmap specification. Point 2 implements the guarded entry/bootstrap
foundation; other proposed contracts retain the delivery status in the handbook.

Scope includes `/admin` dashboard and account-menu-only entry; developer-controlled
module/page navigation; complete supported Auth/Permissions/Audit administration;
generated and customizable tables/forms; field/section/page/action authorization;
compatible atomic cross-module Save; diagnostics/work/Optional lifecycle/settings
recovery; source-only health policy/integrations; CLI-only maintenance diagnostics/recovery; application
global settings; independent pages; customer replacements; opt-in generator
scaffolding; tests and complete developer documentation.

Excluded: module install/update/code upload/migrations through HTTP, arbitrary
Artisan/shell execution, private foreign models/SQL, generic repair/undo, database
import/export, global Admin search, visual navigation/widget editors, mandatory
Overview pages, parallel Auth/Permissions/Audit/health/table/work engines, future
infrastructure dependencies, new UI framework/state library or unapproved packages.
Existing standard table exports remain supported within owning read permissions.

## Specification

### Decisions and activation

- 2026-10-29 — Owner requests architecture review and draft preparation only.
  Functional requirements are planning input; no significant architectural choice
  below is implicitly approved by that request.
- Existing framework/public API/ownership/security/quality rules remain authority.
  New declarations must preserve backend isolation and required dependency direction.
- 2026-10-29 — **ARCH-001 — APPROVED:** modules remain backend-independent while
  Admin supplies the bounded coordination layer for mixed forms and atomic Save.
  Preserve owning validation, grants, Audit, lock/proof/preview/revision protocols
  and explicit exclusions for nontransactional operations.
- 2026-10-29 — **ARCH-002 — RESOLVED: CLI ONLY:** retain existing HTTP/module
  maintenance guards. Diagnostics/recovery during maintenance use supported CLI;
  no Admin maintenance exception or new admission context is in scope.
- 2026-10-29 — **ARCH-003 — APPROVED:** existing reviewed access/policy/bulk workflows
  and owner-specific stronger proof remain exceptions to ordinary direct Save.
- 2026-10-29 — **ARCH-004 — APPROVED:** Runa's default logo remains code-owned;
  customers can set their own presentation/logo through DB-backed application
  settings. Preserve canonical identity and source defaults, with explicit runtime
  presentation precedence and fallback across consumers.
- 2026-10-29 — **Release metadata confirmed:** the owner explicitly confirms
  `release_type: feature` and `proposed_version: 0.7.0`. This resolves the release
  metadata blocker. ARCH-001–004 were subsequently resolved above.
- 2026-10-29 — **Activation approved:** the owner explicitly requests activation
  and reiterates the recorded architecture decisions. Complete PHP/TS signatures
  are agent-owned specification work in point 1, before runtime implementation in
  point 2; they are not another owner architecture approval or activation blocker.

- 2026-10-30 — **Pre-1.0 change authorization:** the owner explicitly says backward
  compatibility is not required while Runa is in development before platform
  1.0.0. Change existing modules directly when that produces a simpler coherent
  design; do not build compatibility workarounds. This supersedes earlier blanket
  signature-preservation/separate module-MAJOR approval requirements, not ARCH-002
  maintenance or ARCH-003 security decisions. See the canonical owner
  [pre-1.0 policy](../../workflows/releases.md#pre-10-compatibility).
- 2026-10-30 — **Restart architecture review:** the owner requests review from the
  beginning under that policy. Runtime implementation is held at point 3 pending
  the source-grounded [reassessment](../../architecture/RM-2026-016-admin-reassessment.md).
  Points 1–2 retain their actual historical completion; their design consequences
  and the uncommitted point-3 worktree are subject to that review. The subsequent explicit revised
  ARCH-001 approval below resolves the hold.

- 2026-10-30 — **ARCH-001 revised — APPROVED:** the owner explicitly accepts
  rebuilding native Auth/Permissions transaction APIs and framework admission so
  shared Save does not depend on a special order of unchanged old API calls.
  All owners validate the locked pre-write snapshot before any business apply;
  standalone and aggregate execution reuse those owning phases. Framework admission
  runs required health work before the root and revalidates sealed evidence inside.
  Existing grants, stronger proofs, reviewed previews, atomic Audit and maintenance
  boundaries remain required. Exact signatures are agent-owned implementation work,
  as in the point-1 decision, not another architecture approval. See
  [native transaction contract](../../../handbook/architecture/module-transactions.md).
  This resolves the reassessment hold; feature/0.7.0 is unchanged.

- 2026-10-30 — **Native version assessment:** Auth/Permissions move to independent
  2.0.0 and guarded AuthProfiles/PermissionsAccess API 2.0.0 for the deliberate
  replacement of ambient whole-write nesting with phased source composition.
  Standing owner pre-1.0 authorization covers these breaks; no platform MAJOR is
  inferred. Other guarded API versions remain unchanged. Both modules now require
  target platform >=0.7.0 <0.8.0. Current native consumers and private target-runtime
  fixtures are updated; published predecessor history is retained.

- 2026-10-31 — **Runnable manual acceptance — APPROVED:** the owner explicitly
  authorizes setting VERSION and the protected runtime manifest to the agreed
  0.7.0 before final verification and asks to retain the reason. Local source
  already requires 0.7.0, but runtime still advertised 0.6.2 and the development
  registry retained earlier module hashes/versions; effective Core maintenance
  returned HTTP 503. Prepare the candidate and forward local data without resets
  or weakened availability guards. This is not release closure, a final gate PASS,
  Admin 1.0.0 promotion, tagging or publication; published baseline remains 0.6.2.
- 2026-10-31 — **Manual frontend acceptance policy — APPROVED:** for every roadmap
  adding/changing frontend, the penultimate point belongs to the owner's manual
  tests. The agent first prepares a usable application/current assets/data and
  records readiness. Only the owner confirms acceptance. Insert repairs before
  that point and repeat affected acceptance after UI changes; final automated
  verification/release stays last. Apply the rule to owner and customer instruction
  products and this roadmap; ordinary non-frontend roadmaps need no extra point.

Record subsequent significant owner decisions individually with dates and update
the review/roadmap.
Before platform 1.0.0, necessary incompatible Runa/Core refactoring has standing
owner authorization under the 2026-10-30 decision below. Preserve security and
ownership invariants, update current consumers and assess independent versions;
do not preserve historical signatures solely for compatibility. Significant new
architecture or behavior premises still return to discussion. Admin receives its
independent 1.0.0 only at verified creation closure; platform versioning is separate.

### Contract and behavior requirements

1. Admin requires Audit/Auth/Permissions without introducing reverse dependencies.
   Use coherent framework management declarations; keep module-owned business
   contracts and implementations with their owners. No module requires Admin to run
   its business logic. Independent app pages need no module manifest.
2. Reuse manifest-bound component loading, slots, ManagementRenderers compatibility,
   native grants, Auth session/proof, exact default PostgreSQL/Audit, settings and
   lifecycle/work/health engines. Explicitly bind trusted operations; metadata is
   neither executable input nor authorization.
3. Root dashboard `/admin`; named page and API routes. Reuse ApplicationLayout and
   the account dropdown. No extra entry in ordinary application navigation. Keep
   existing module page paths and application-owned router/startup contracts.
4. Gate every Admin bootstrap/read/write with current entry authority/password proof
   and the owning operation's grants, record policy, stronger proof, availability
   and revisions. Project no forbidden values/options/export fields. Read-only
   submissions fail closed. Never use a generic superadministrator policy bypass.
5. Compatible multi-owner forms use one Save/root commit, explicit participant set,
   expected revisions and reviewed previews. Failure rolls back writes, success
   Audit and consumed proof; safe refusal history follows root rollback. Settings,
   lifecycle, uploads and external effects retain their independent protocols.
6. Polish-default/English-secondary usable defaults; light/dark, responsive and accessible. Shared form
   reconciliation preserves dirty drafts, uses aggregate mutation revisions,
   resolves overlap locally and never silently retries writes. Destructive actions
   confirm; existing owner review/proof requirements remain visible.
7. Public field/section/form/page/widget/layout replacements and headless controllers
   preserve platform security/session orchestration. Automatic module contributions
   are available only with current source/installation/availability/grants. Stable
   ordering and safe component/section isolation must not hide failed required saves.
8. Dashboard consumes persisted health with freshness/expiry and safe reasons,
   enabled by default with registry-state filters. Explicit diagnosis reuses the
   engine. Management supports only existing allowed controls; newly exposed
   meaningful mutations require Audit in their actual engine transaction.
9. Global app settings remain distinct from module settings. Classify individual
   safe values; preserve environment/technical/source ownership and no-restart
   runtime projection only for approved dynamic values. No infrastructure secrets.

## Implementation plan

Activation is complete. Point 1 specifies the public contracts before runtime
implementation in point 2. Points
deliver code, test code and synchronized current-state documentation, including
affected owner/customer instruction products. Only small, fast checks needed to
resolve a concrete uncertainty may run during implementation; do not schedule
routine suites, static analysis, browser acceptance, builds or scanners here.
Mandatory commit hooks remain in force. Each completed point is committed with
this roadmap ID and the checklist updated. Broad verification is exclusively point
12, after every implementation point. Added points 2a–2b apply the approved
reassessment before resuming point 3; completed point history remains evidence.

- [x] 1. Finalize the approved public contract specification and compatibility map.
  - Prerequisites: individual ARCH-001–004 decisions, release confirmation and activation.
  - Record complete signatures/DTOs/bounds/exceptions/props/slots/controller returns,
    owner/audience/entrypoint/home/delivery in the owning handbook. Define editable
    participation, read/write bindings, field/section authorization, independent
    app registration and presentation defaults. Preserve existing provider methods.
  - Record per-Core API coverage and exact maintenance/atomicity/settings matrices;
    refresh independent module version plans and app/Core platform ranges below 0.7.0.
  - Deliver public boundary/schema/consumer fixture tests and updated instruction
    product references. No undocumented private integration shortcuts.
- [x] 2. Establish the Core module, guarded entry and framework adapters.
  - Prerequisite: 1.
  - Add Admin manifest, lifecycle/explicit forward installation, permission declarations,
    guarded APIs/named HTTP endpoints, thin controllers/FormRequests/Resources and
    entry password proof. Build source-owned framework integration adapters for
    diagnostics/status and later management actions, never arbitrary engine access.
  - Author entry/denial/non-superadministrator/session/proof/availability tests and
    source/runtime/install history fixtures. Add module documentation/changelog and
    welcome catalogue icon/localized descriptions without claiming rendered PASS.
- [x] 2a. Rebuild native transaction/admission phases in the owning framework and Core.
  - Prerequisites: 1–2 and the explicit revised ARCH-001 decision on 2026-10-30.
  - Specify and implement source-owned transactional mutations, one root lock/
    validate/proof/apply/result/refusal protocol and exact-operation health admission.
    Move Auth profile and Permissions assignment standalone writes onto these same
    owner phases; do not call unchanged whole writes from the aggregate or encode
    assignment-before-profile ordering. Preserve current grants and strong proofs.
  - Author mixed-owner success in canonical Auth-first order, stale preview/ABA,
    rollback/proof/Audit/refusal, same-resource/proof duplication, admission expiry/
    source change and scope-unwind cases. Only necessary focused uncertainty checks
    run here; full suites/concurrency/native checks remain point 12.
  - Synchronize public/source boundaries, current consumers, module version/history
    and both instruction products. Retire obsolete whole-signature freeze gates.
- [x] 2b. Correct unreleased presentation/read contracts from the reassessment.
  - Prerequisites: 2a; update exact contracts before their implementation in 3–5.
  - Specify field-bound options, page/build identities and widget freshness results,
    per-binding availability isolation and one shared session-shell controller with
    minimal authenticated entry advertisement. Finalize platform ownership of the
    shared application-settings store and installation path before point 10.
  - Point 2a removed unpublished Admin 0.2.0/no-op history churn. Keep the current
    0.1.0 development version until the creation release unless a real need emerges.
    Update the plan/current guides and assess both instruction products.
- [x] 3. Implement declaration discovery and authorized projection.
  - Prerequisites: 1–2, 2a–2b.
  - Extend existing management declarations with pages/sections/resource and command
    bindings, widgets and contribution metadata. Add trusted app independent-page
    registration. Use registry/hash/schema/renderer checks and deterministic ordering.
  - Filter page/section/field reads and writes, values/options/derived counts, selection
    and exports server-side; isolate invalid declarations. Author negative disclosure,
    disabled/unavailable/stale generation and application-consumer cases; document API.
- [x] 4. Deliver the Admin shell and public presentation/controller seams.
  - Prerequisites: 2–3.
  - Add `/admin` and named page routing/default-dispatch integration; module-left,
    page-top navigation, no empty/mandatory Overview. Extend AuthApplication menu and
    shared session-shell composition inputs; put entry only beside User panel.
  - Provide English-scoped Application layout, session/proof orchestration, nested
    presentation provider and public `@/runa/admin` facade/controller types. Preserve
    app-owned routes and full replacement without copied security logic.
  - Author replacement/menu/location/English/route/session race component/browser
    scenarios; synchronize public customization and component/layout references.
- [x] 5. Implement standard generated resource tables, forms and actions.
  - Prerequisites: 3–4.
  - Compose existing Table/useTable/providers/selection/views/exports and Form/fields/
    confirmations/feedback. Support automatic, partial and full replacement, public
    UUID lookups, static validation hints, custom controls and explicit command bindings.
  - Preserve owner pagination/filter/sort/count/export limits and secret set-state;
    map 422/409/safe errors and dirty navigation guards. Implement shared aggregate
    revision/read reconciliation without replay or edited-form reinitialization.
  - Author standard CRUD/read-only/field-denial/export/bulk/dirty/conflict scenarios
    and synchronize forms/tables/frontend references and customer examples.
- [x] 6. Implement approved cross-module contributions and atomic Save.
  - Prerequisites: 1, 3, 5; ARCH-001 and ARCH-003 approved as recorded.
  - Add editable participation alongside existing visual slots; owner-namespaced
    input/errors/revisions, compatibility validation and canonical owner phases and lock order.
    Root Action coordinates guarded owners and atomic Audit/proof; refusal records
    follow rollback. Exclude independent runners/file/network operations explicitly.
  - Author profile-plus-role plus future app contribution fixtures, both failure
    directions, stale preview/ABA, multi-subject order, proof reuse/rollback, own-grant
    removal, Audit failure and participant-loss cases. Implement concurrency harness
    here; run complete acceptance only in final verification.
  - Document trusted/public audiences, compatibility restrictions and working examples.
- [x] 7. Complete Auth administration through owning public APIs.
  - Prerequisites: 3–6.
  - Deliver bounded Auth-owned directory/necessary missing read APIs additively;
    identity/lifecycle/deadlines/locks, invitations, profile/login/names, email and
    secondary-address workflows, sessions/trust/security resets, policy/history/
    consequences/restore/exceptions and administrative preference reset.
  - Keep self-service, recipient/delivery and reserved operator capabilities in their
    supported audience. Integrate Permissions sections through contribution contracts.
  - Cover every applicable matrix entry with API/UI tests and document exact grants,
    proofs/revisions and independent workflows; update Auth version/changelog.
- [x] 8. Complete Permissions administration and Audit viewer.
  - Prerequisites: 3–7.
  - Deliver assignments/direct grants, custom roles/presets/copy/add-replace and
    effective changes review, source/system read-only definitions, dedicated protected
    authority and confirmed force-delete flows. Preserve native selection and
    last-eligible-superadministrator invariants.
  - Deliver authorized Audit cursor filters/detail/entity links without payload leaks,
    total-count invention, edits or undo; optional current identity labels use guarded Auth.
  - Author owner policy/preview/step-up/Audit atomicity regressions and frontend
    interaction cases; update changed module versions/changelogs and public guides.
- [x] 9. Deliver dashboard, module controls, work and approved maintenance behavior.
  - Prerequisites: 2–5, 7–8; recorded ARCH-002 decision.
  - Dashboard versions/safe runtime summary, health/dependencies/freshness/filtering,
    module widgets and explicit diagnosis use existing observations/engines.
  - Wire allowed Optional transitions/preview/status/resume/cancel, retained settings
    recovery and permitted work cancellation/status. Health policy and integration
    configuration remain source-only under owner amendments 11a17 and 11a22.
    Add mandatory transactional Audit participation where engines lack it; retain
    pending/recovery_required and module-owned recovery semantics.
  - Retain current maintenance guards and update/Core failure fencing; maintenance
    diagnostics/recovery remain CLI-only. Add no Admin availability exception,
    operator/shell/install/update controls or maintenance-marker mutation.
  - Author diagnostics/redaction/permission/failure isolation/lifecycle/maintenance/
    work/optional fallback tests, plus deployment-fence scenarios if affected.
- [x] 10. Deliver approved application global settings and runtime presentation.
  - Prerequisites: 1, 3–5; ARCH-004 resolved.
  - Add source-declared app-owned safe settings/defaults, storage/migration, revisions,
    validation/grants and atomic Audit, independent of module settings. Implement
    approved identity/presentation precedence and browser/mail/error fallbacks.
    Keep the Runa default logo in code and customer-selected branding/logo in DB.
  - Keep technical/.env/source compatibility fields read-only; no infrastructure
    controls or generic asset/upload subsystem. Author no-restart persistence,
    ownership/rollback/CSP/fallback and customer extension cases; document classification.
- [x] 11. Complete generator, consumer examples and documentation integration.
  - Prerequisites: 1–10.
  - Extend runa:make:module opt-in scaffolding for pages/navigation/entities/forms/
    tables/settings/actions/widgets/contributions/custom components/permissions.
    Preserve preview/exact-hash authorization, ownership and production refusal;
    generated business behavior works without Admin and no unnecessary dependency.
  - Finish working examples and all 18 developer documentation topics from the review.
    Author delivery/update preservation and partial/full app replacement fixtures.
    Synchronize minimal app scaffold, welcome catalogue and source/API/version checks.
  - Review owner AGENTS/skills plus customer bundle; update affected public consumption
    guidance without authorizing protected edits; record unaffected-product reasons.
- [x] 11a. Prepare runnable owner frontend acceptance and synchronize workflow policy.
  - Prerequisites: 1–11 and the owner's 2026-10-31 approval.
  - Record the new manual acceptance rule in canonical workflows, template and both
    instruction products; retain the automatic final gate and repair-loop rules.
  - Prepare approved 0.7.0 source/runtime metadata and release-validator support,
    retaining published 0.6.2 history and exact target arithmetic from main.
  - Correct compilation blockers found while preparing the real local asset build,
    preserving public layout slots, typed collection adapters and owner test contracts.
  - Explicitly apply only necessary forward local platform/module data, preserve
    users/settings/Audit, prepare current assets and confirm real HTTP readiness
    plus authorized access. Record exact outcomes and any operational blockers.
- [x] 11a1. Apply owner-requested Polish Admin label correction.
  - Replace the Polish account-menu entry and module catalogue name with
    `Panel administratora`, refresh local registry metadata/current assets and
    preserve English-only Admin presentation. No grant/route/security change.
- [x] 11a2. Complete the requested panel rename in both supported languages.
  - Use `Panel administratora` / `Administrator panel` consistently in menu/catalogue,
    English dashboard/navigation and related accessibility/entry copy. Update existing
    consumer expectations and refresh local registry/assets before manual handover.
- [x] 11a3. Complete Auth operation-proof presentation and native Admin entry composition.
  - Review original Auth roadmap requirements against delivered entry/account/admin
    presentations; record confirmed gaps and bounded source coverage without editing history.
  - Deliver an Auth-owned replaceable MinimalLayout proof page for password/factor/both,
    localize shared inline/page proof controls and feedback in PL/EN, reuse one owning
    proof controller and preserve native action/session binding and secret erasure.
  - Replace Admin's duplicate proof request/form with the public Auth page/controller;
    keep pre-admission content outside ApplicationLayout with one shared session owner.
  - Author failure/retry/fencing/layout/replacement regressions, run only necessary
    focused checks, refresh real assets and preserve manual acceptance as penultimate.
- [x] 11a4. Apply owner-approved Polish/English Admin localization.
  - Supersede the English-only rule in owner/customer guidance and current specs.
  - Use the existing application language preference for all supplied Admin/Core
    administration views, controls, proof, validation and feedback; preserve drafts.
  - Supply source-owned bilingual metadata and a public presentation translation
    contract for App/module extensions, with technical identifiers unchanged.
  - Add focused language-switch/default/replacement regressions, prepare current
    assets and keep owner manual acceptance and final verification open.
- [x] 11a5. Match Auth proof heading focus and cancellation styling.
  - Reuse the existing Auth reading-heading focus rule so initial focus is announced
    without a visible heading outline; retain keyboard indicators on controls.
  - Use the existing red error/danger outline button for proof cancellation,
    synchronize current presentation docs and refresh local assets for owner review.
- [x] 11a6. Stabilize Admin menu advertisement and password admission revalidation.
  - Ignore unchanged session/activity observations for optional navigation reads;
    revalidate known same-session availability in place and hide on denial/invalidation.
  - Keep the mounted pre-admission Auth proof and transient input through same-session
    validation; recheck bootstrap in the background without weakening protected-read fences.
  - Cover delayed validation, denials, identity switches and late responses; update
    current contracts and prepare assets before returning to owner manual acceptance.
- [x] 11a7. Unify initial presentation readiness and nondestructive revalidation.
  - Owner-approved manual-acceptance repair: introduce one public, identity-scoped
    readiness contract for supplied and future views; separate first reveal,
    background reads, local mutations and immediate identity/access invalidation.
  - Prepare session profile/preferences and downloaded/decoded avatar before the
    application shell's first reveal; bound optional image waits with initials
    fallback. Retain the same decoded image across dropdown mounts and refreshes.
  - Audit supplied Auth/Admin/layout consumers; keep independent page/data-region
    readiness and existing security/request epochs, dirty reconciliation and native
    refusal behavior. Do not add another session owner or wait for every request.
  - Synchronize public composition contracts and both instruction products, cover
    delayed/error/identity-switch/background-read scenarios with focused checks,
    and build runnable assets before repeating owner acceptance.
- [x] 11a8. Keep layouts visible from startup and localize loading placeholders.
  - Owner correction to 11a7: never gate the application root or whole layout on
    branding, preferences, profile or avatar reads. Render the normal frame immediately.
  - Apply readiness only to the dependent content/control region; keep the avatar's
    own skeleton, bounded decode fallback and shared image owner. Preserve background
    refresh/draft behavior and protected-content session admission.
  - Synchronize current public/agent guidance and focused rendered regressions,
    prepare current assets and commit before returning to manual acceptance.
- [x] 11a9. Synchronize initial content/avatar completion within the visible frame.
  - Owner correction to 11a8: keep the layout immediate, but finish its initial
    content and avatar placeholders together after profile/preferences and image
    decode or bounded fallback settle. Reuse the shared readiness controller.
  - Latch per account/session so later reads keep content/focus/drafts mounted;
    preserve immediate access/identity invalidation and local loading geometry.
  - Cover delayed download/decode, image failure/timeout and background refresh,
    synchronize current contracts and prepare assets before owner acceptance.
- [x] 11a10. Move page-level navigation into the shared top bar.
  - Owner correction: sidebar selects an area/module; links between subviews of
    that area belong in the existing horizontal top navigation, not content buttons.
  - Add a typed UI-owned page-navigation composition seam for nested defaults and
    App layout replacements. Reuse the existing navigation renderer/resolver,
    preserve URL-derived active state, query/history and existing dirty guards.
  - Migrate Admin dashboard application settings and Auth account tabs; audit other
    supplied private views, preserving operation/record actions inside content.
  - Synchronize contracts/instruction products, exercise focused navigation/guard
    regressions and prepare current assets before returning to owner acceptance.
- [x] 11a11. Coordinate current navigation and Admin initial presentation.
  - Remove obsolete content-tab placeholders and publish account navigation loading
    in the header. Keep the Admin Application frame mounted during bootstrap.
  - Register explicit initial Admin page, record, option, diagnostics and editor
    dependencies with the existing readiness latch; reveal content and navigation
    together after settlement, preserving background drafts and access fences.
  - Synchronize public composition guidance, add focused delayed-read regressions,
    prepare runnable assets and commit before repeating owner acceptance.
- [x] 11a12. Refine the Admin dashboard and module operation dialogs.
  - Apply the owner's ten dashboard corrections: shared welcome/sidebar icons,
    automatic module-state filtering with visible refresh, verified identity/version
    sources, semantic module cards, corrected breadcrumbs and no empty widget copy.
  - Open selected module operations in a guarded, accessible dialog with readable
    status/details and scoped tabs; preserve capability/proof/preview, dirty-close,
    URL/history, request fencing and native lifecycle/work/recovery contracts.
  - Synchronize public defaults/composition and instruction-product assessment,
    run focused uncertainty checks and prepare current assets before acceptance.
- [x] 11a13. Repair modal operation feedback and deliver native operation history.
  - Owner authorizes the six reported modal corrections plus a history table in
    place of manual UUID entry: colored badges, retained content during diagnosis,
    operation-local progress and semantic diagnostic outcome without redundant IDs.
  - Active work opens a stable table at the bottom, including empty state; every
    deliberate click refreshes it. Native lifecycle/settings history includes
    completed/failed/in-progress changes and only currently allowed row actions.
  - Reuse the durable framework transition ledger with bounded cursor reads and
    existing per-kind management grants; never expose/decrypt private payloads or
    introduce another history store. Preserve native review/recovery/Audit guards.
  - Synchronize public contracts/instruction assessment, cover focused races,
    refusal/redaction/pagination and prepare current assets before owner acceptance.
- [x] 11a14. Align navigation hierarchy and scope the module overview.
  - Rename dashboard Operations to Modules and render breadcrumbs from the current
    area/module/page hierarchy, including query-driven subviews and linked ancestors.
  - Move diagnosis to the top of the overview. Keep its result and operation/task
    tables inside that tab; replace the Active work trigger with an automatically
    loaded Task history table and its own explicit refresh.
  - Include retained terminal work through the existing native work owner, preserve
    bounded reads, grant checks, write/dirty guards and stale-response fencing.
  - Synchronize public contracts, run focused regression checks and prepare current
    assets before repeating owner acceptance.
- [x] 11a15. Cover breadcrumb discovery with shared navigation readiness.
  - Keep the breadcrumb region mounted with the existing animated skeleton until
    shell/page navigation settles; reveal only the complete path and links.
  - Reuse the existing readiness signals without blocking the frame or introducing
    a new loading engine; preserve normal background-refresh behavior.
  - Synchronize guidance, verify delayed bootstrap/page readiness and prepare assets.
- [x] 11a16. Settle account-menu admission and display controls before initial reveal.
  - Include bounded Admin entry discovery in the existing session readiness latch;
    retain settled presentation during background revalidation and fence identities.
  - Cover language/theme controls with local shared skeletons during initial
    navigation readiness and keep the account-menu trigger disabled until ready.
  - Verify delayed menu/preferences in both completion orders, fallback, identity
    cleanup and unchanged background behavior; synchronize guidance/build assets.
- [x] 11a17. Make module health policy code-only and repair filtered-list loading.
  - Owner decision, 2026-10-31: remove the health-policy tab, JSON editor,
    management operation/grant and database override/revision mechanism. Keep
    module-owned source checks, observations, diagnosis and availability guards.
  - Change HealthPolicy.definition() to source-only; update current consumers,
    reference module, tests and canonical documentation. Remove persisted override
    state with a forward platform migration; preserve business data and history.
  - Owner addition: disabled module cards retain a gray top accent and gray hover
    border through the existing card motion.
  - Filter changes replace module cards with the shared content Skeleton while
    the latest read settles. Preserve the filter/frame and stale-response fencing;
    ordinary background refresh retains confirmed cards.
  - Prepare current assets/data, record focused checks and instruction assessment,
    and commit before repeating owner acceptance.
- [x] 11a18. Put module controls first and review lifecycle actions in a dialog.
  - Owner decision, 2026-10-31: retain enable/disable/unregister semantics;
    installation stays outside Admin. Registration and installed data remain
    distinct; do not relabel unregister as uninstall.
  - Place Optional lifecycle action buttons above module details, remove the
    intent selector, instructional Core warning and standalone Preview trigger.
    Clicking an action opens one confirmation dialog with the native preview,
    blockers, dependencies and work; apply only its exact reviewed token.
  - Place diagnosis feedback beside its trigger and keep it independent from
    lifecycle/work feedback. Fence pending previews by module/session/authority,
    preserve native admission and show failures without replaying mutations.
  - Synchronize guidance, run focused checks and prepare current assets before
    repeated owner acceptance. No installation API or database change is included.
- [x] 11a19. Consolidate module actions and show diagnosis in a dialog.
  - Remove the Optional lifecycle heading. Keep lifecycle buttons left and the
    diagnosis trigger right in one responsive action row above module details.
  - Open the shared nested Dialog for diagnosis loading, safe results and errors;
    preserve focus return, current authority/context fencing and explicit retry.
  - Synchronize guidance, cover focused modal behavior and prepare current assets
    before repeated owner acceptance. No backend or persistence change.
- [x] 11a20. Translate the lifecycle confirmation Cancel label.
  - Add the missing Polish dictionary entry and check rendered Enable/Disable/
    Unregister confirmation footers in Polish. Preserve English presentation.
  - Prepare current assets and commit before repeated owner acceptance.
- [x] 11a21. Remove the generic module settings editor in favor of owned pages.
  - Owner decision, 2026-10-31: administrator-editable module settings belong on
    the module's dedicated page with change history and its own reviewed workflow,
    as Auth already supplies; do not expose a second generic module-dialog form.
  - Remove the settings tab/editor mode, generic settings read/update endpoints
    and read grant. Retain native settings storage, Auth policy pages and retained
    operation history/status/recovery. Integrations keep their existing editor.
  - Document authoring/generator boundaries and update current consumers/tests;
    prepare assets, run focused checks and commit before repeated owner acceptance.
- [x] 11a22. Make optional integration configuration source-only.
  - Owner decision, 2026-10-31: follow the source-only health-policy removal;
    remove the Admin tab/editor, public presentation seam, read/write operations,
    grant and database override storage. Keep source-declared integration behavior,
    validation and both-side availability/version admission.
  - Replace default/override metadata with explicit source enabled/config values;
    update consumers, tests and canonical guides, using a forward migration.
  - Remove the module dialog tab bar entirely; retain its single content view,
    lifecycle/diagnosis/history and module deep links.
  - Prepare current assets/data, record focused uncertainty checks and instruction
    assessment, and commit before repeated owner acceptance.
- [x] 11a23. Deliver application display defaults, uploaded branding and settings layout.
  - Owner request, 2026-10-31: add default PL/EN language, system/light/dark theme
    and IANA display timezone; preserve explicit account/browser preferences and UTC
    storage. Source defaults are pl/system/Europe/Warsaw.
  - Allow PNG/JPEG/WebP logo uploads, automatically fit a transparent square PNG
    favicon and allow an independent custom favicon. Use browser Canvas rasterization,
    bounded backend PNG validation and transactional binary columns on the existing
    application-settings singleton, with its revision/grants/Audit. No new dependency,
    external file lifecycle or generic upload system. Custom favicon wins; reset
    restores automatic logo favicon and source assets when logo is reset.
  - Application settings/Modules use their own main headings; remove the duplicate
    small headings. Bound field widths and align localized Restore defaults buttons
    beside fields, wrapping on narrow screens. Preserve shared controls/replacements.
  - Synchronize public contracts, source consumers and tests, prepare assets/data,
    record focused checks and commit before repeated owner acceptance.
- [x] 11a24. Enable timezone search and remove redundant settings copy.
  - Owner request, 2026-10-31: enable the existing local searchable Select variant
    for timezone fields; preserve other settings controls and native validation.
  - Owner addition: remove the restart/reset introduction and the two logo/favicon
    explanatory texts, including obsolete translations.
  - Synchronize the owning frontend guide/changelog and prepare current assets.
- [x] 11a25. Unify form and operation failure notifications across delivered views.
  - Owner request, 2026-10-31: use the account panel's shared FormNotifications
    for settings outcomes; associate validation errors with their controls and
    remove duplicate generic banners. Audit remaining shared/Core views and
    migrate operation errors while preserving contextual security/availability,
    reconciliation and durable operation status displays.
  - Keep explicit recovery actions, modal focus boundaries and repeat-attempt
    feedback; synchronize contracts and record focused verification before commit.
- [x] 11a26. Make shared feedback mandatory in both frontend skill entrypoints.
  - Owner request, 2026-10-31: require FormNotifications for every new/changed
    view's form-wide and operation outcomes, prohibit local duplicate banners and
    route to the canonical field/recovery/modal contract before implementation.
  - Synchronize owner/customer skill products and provenance without copying the
    handbook procedure or changing runtime behavior.
- [x] 11a27. Unify module record browsers and system table controls.
  - Owner request, 2026-10-31: review Auth, Permissions and Audit together for
    every reported module-view defect. Compact desktop filters to six columns,
    use declared dictionary choices, filter automatically and provide icon-only
    reset. Open records in guarded shared dialogs labelled Details.
  - Deliver shared icon-only refresh with Tooltip for every Table, connect real
    owning reads and remove duplicate refresh controls. Align preferences/actions.
  - Review applicable table capabilities across supplied views; authorize exports
    separately from reads on the server, preserve bounded scopes and field grants.
    Synchronize public contracts, regression scenarios and current assets before
    returning to owner acceptance. Broad verification remains point 12.
- [x] 11a28. Keep filter reset beside filters and format module timestamps.
  - Owner corrections, 2026-10-31: place the icon/Tooltip reset inside the filter
    region, separate from table actions, and correct displayed table dates.
  - Review Auth/Permissions/Audit together; use declared datetime semantics and
    the application locale/timezone formatter, preserving raw export instants.
    Prepare current assets and synchronize owning guidance before owner review.
- [x] 11a29. Remove repeated registry/authorization read amplification across supplied flows.
  - Owner request, 2026-10-31: repair measured Admin latency, audit other small
    framework/Core read paths for the same amplification and repair demonstrated
    cases. Preserve current grants, expiry, generation, transaction and rollback
    guarantees; do not introduce persistent authorization caches.
  - Add focused query-growth and rollback/reused-worker regression scenarios,
    compare representative before/after stages and synchronize canonical guidance.
    Require performance evidence/review in both owner/customer backend skills;
    repair demonstrated defects in authorized scope, escalate changed premises.
    Broad verification remains point 12 after owner acceptance.
- [x] 11a30. Complete the application-wide repeated-work performance review and repairs.
  - Owner correction, 2026-10-31: point 11a29 covered framework/Core read paths,
    but the requested review includes the whole supplied application: ordinary
    App/reference code, HTTP startup/middleware/controllers, account/security,
    module/operational views, background work and frontend request orchestration.
  - Inventory runtime surfaces and analogous loops, measure suspected repeated
    queries/declarations/requests, repair demonstrated cases and add focused
    growth/freshness regressions. Record reviewed areas, findings and limits;
    do not present a selected Core review as whole-application coverage.
    Preserve owning authority, locks, expiry, bounded reads and dirty drafts.
    Broad verification remains point 12 after owner frontend acceptance.
- [x] 11a31. Deliver Auth-owned protected-section idle access and migrate Admin.
  - Owner approval, 2026-10-31: renew the specific protected section on deliberate
    navigation, clicks, typing and scrolling. Ignore pointer motion, hidden tabs,
    background reads and automatic refresh. Expired access requires credentials.
  - Auth owns guarded idle assertion/activity APIs and a reusable scoped frontend
    controller. Server-owned endpoints choose action, requirement and TTL; clients
    cannot select weaker policy. Keep fixed-age and single-use operation proof intact.
  - Retain current session/principal/security-version/factor/availability checks,
    transactional rollback and native aggregate enforcement. Store idle activity
    independently from credential verification and preserve live rows during cleanup.
  - Add native expiry/isolation/policy/rollback and frontend activity/lifecycle
    regressions, synchronize public contracts/examples and both instruction products.
    Prepare current runtime/assets for owner acceptance; broad gates stay point 12.
- [x] 11a32. Keep table headers and a below-header horizontal scrollbar visible during page scrolling; use typed date/time filters throughout module views.
  - Owner approval, 2026-10-31: preserve vertical page scrolling, pin headers and
    their horizontal scrollbar within table bounds, and repair Audit from/until
    with the existing timestamp picker. Review sibling module views and repair
    the common mechanism. Preserve semantic headers, sorting/selection,
    keyboard/touch access and canonical UTC instants.
  - Synchronize public contracts/changelogs, add focused scrolling/filter
    regressions and prepare current assets for repeated owner acceptance.
    Broad verification remains point 12.
- [x] 11a33. Unify application date/time presentation and input defaults; remove timezone annotations and maintain durable frontend guidance.
  - Owner request, 2026-10-31: review the whole system, use one conversion path
    driven by application timezone settings, and remove timezone notes beside/in
    date/time inputs. Dates without a time remain calendar dates; UTC storage,
    transport, operational deadlines and canonical export contracts stay intact.
  - Review shared primitives, all module/App date consumers, public settings
    hydration, calendar defaults and frontend owner/customer skills. Repair
    demonstrated inconsistencies, add focused timezone/DST regressions and prepare
    runnable assets. Manual acceptance 11b and broad final gates 12 remain open.
- [x] 11a34. Repair compact timestamp filters, pinned-header scroll latency and exact filtered totals.
  - Owner request, 2026-11-01: align datetime fields/calendar triggers with other
    compact filters, reduce header jumping during page/dialog scrolling and show
    the actual number of matching results independently of cursor navigation.
  - Owning Audit/Auth/Permissions reads count authorized filtered results before
    cursor/limit; Admin forwards exact totals while retaining previous/next paging.
    Preserve nullable custom-owner totals, current security/selection contracts,
    one semantic header and synchronized horizontal scrolling.
  - Add focused regressions, synchronize public contracts/changelogs and prepare
    current assets for repeated manual acceptance. Broad verification stays in 12.
- [x] 11a35. Vertically center header labels and scroll wide tables horizontally on header wheel input.
  - Owner request, 2026-11-01; clarified vertical alignment only. Preserve left
    alignment, balance the header's rail gutter and share wheel behavior between
    the header and rail. Consume horizontal intent at both ends, preserve native
    Ctrl+wheel zoom and normal page scrolling when the table fits.
  - Extend focused scrolling coverage, synchronize public guidance and prepare
    current assets; manual acceptance 11b and final verification 12 remain open.
- [x] 11a36. Unify localized dictionary values in module filters, tables and read-only details.
  - Owner request, 2026-11-01: translate displayed Audit dictionary values like
    filter choices and use lowercase labels in PL/EN. Repair the same mismatch
    across Auth/Permissions/shared Admin presentation without translating raw
    identifiers, payloads, wire values or canonical exports.
  - Extend focused presentation regressions, synchronize current guides/changelogs
    and prepare current assets for repeated manual acceptance; broad gates stay in 12.
- [x] 11a37. Improve module detail readability and bounded value previews.
  - Owner request, 2026-11-01: group Audit event metadata, format escaped JSON,
    collapse long values with explicit expansion, remove empty filter-only detail
    fields and single-part pagination noise. Explain and verify entity history links.
  - Inspect Auth/Permissions equivalents, share repeated value presentation,
    extend focused regressions and prepare current assets for repeated acceptance.
    Manual acceptance 11b and final verification 12 remain open.
- [x] 11a38. Link Audit identifiers and refine shared JSON previews.
  - Owner request, 2026-11-01: move correlation into Event, make correlation and
    actor UUID values direct history-filter links and remove related-entity cards.
    Highlight JSON syntax and replace the framed expansion button with an inline
    link inside the preview; apply the shared presentation across all three modules.
  - Extend focused interaction coverage, synchronize public guidance and prepare
    current assets. Owner manual acceptance 11b and final verification 12 stay open.
- [x] 11a39. Animate detail-modal reads and right-align preview expansion links.
  - Owner clarification, 2026-11-01: loading feedback belongs inside the detail
    modal, not the history table. Use shared animated primitives for initial
    detail reads and payload continuation; inspect Auth/Permissions equivalents.
  - Right-align Show all/Collapse in the shared preview, extend focused delayed-read
    regressions and prepare current assets. Manual acceptance and final gates stay open.
- [x] 11a40. Standardize content-loading presentation across the supplied frontend.
  - Owner correction, 2026-11-01: inspect every loading animation and modal;
    loading content uses pulsing content placeholders, never standalone circles.
    Table reads, action-button progress and the explicitly accepted preference
    change cover retain their dedicated feedback. Keep frames and dirty drafts.
  - Supply one public ContentLoading primitive and Dialog loading default, make
    AsyncContent default to placeholders, migrate inconsistent consumers, review
    every existing loading/modal owner and enforce allowed Spinner ownership in lint.
  - Synchronize both instruction products and canonical guides, author focused
    regressions and prepare actual assets. Full suites remain final verification.
- [x] 11a41. Put the collapsed JSON continuation marker on its own line.
  - Owner request, 2026-11-01: separate the ellipsis from JSON tokens so truncated
    content is obvious. Apply through shared ValuePreview and prepare current assets.
  - Owner reports satisfaction with Audit and completion of its view review after
    this adjustment. This records Audit-specific acceptance, not whole-roadmap 11b.
- [x] 11a42. Restore content spacing after loading-wrapper migration.
  - Owner regression, 2026-11-01: restore the gap below Module state on /admin.
    Remove the unnecessary retained-content wrapper and put layout utilities on
    their actual content owner. Inspect all ContentLoading consumers for the same
    class/direct-child mismatch and preserve refresh DOM/focus.
  - Verify normal/loading/refresh layouts with focused geometry checks and prepare
    current assets; manual acceptance and final gates remain open.
- [ ] 11b. Owner manual frontend acceptance.
  - Prerequisites: completed/committed implementation and runnable local application
    with current assets/data and usable authorized access from 11a.
  - The owner tests Admin and affected application frontend and reports defects.
    Insert repairs before this point and repeat affected manual acceptance.
  - Complete only after explicit owner acceptance; record tested source/environment.
    Agent checks and automated tests cannot supply that decision. This stays the
    penultimate point; final automated verification/release remains last.
- [ ] 12. Complete affected-area verification, release finalization and closure.
  - Prerequisites: all implementation points complete and committed, explicit owner
    manual acceptance in 11b; no unresolved
    architecture, public-signature, module-version or synchronization decisions.
  - Review current-state README, commands, configuration, architecture, Core/Admin
    guides, app compatibility, module/generator/customization docs and both instruction
    products against delivered behavior. Record sources/findings/disposition; preserve
    closed roadmaps and dated evidence. Reconcile projected version/order and cadence.
  - Finalize proportionate quality_groups/quality_rationale; preview the exact graph
    with supported quality-plan repeated --group flags. Initial proposal is in
    frontmatter; justify every heavy selection and add real prerequisites. No excluded
    area is PASS. If a five-minor checkpoint becomes due, use supported --all.
  - Run supported quality-roadmap --background with the declared selection, end the
    turn and provide quality-status. Complete full selected backend/frontend/tooling
    prerequisites, browser/render/security/database and native installation/update
    acceptance here only. Follow the owner SIGSEGV disposition without hiding reports.
  - If verification requires changes, insert a fix point before manual acceptance;
    run the complete affected-area repair suite in its failing mode before committing
    the fix, then restart final verification from the beginning. Discuss changed
    architectural premises rather than silently repairing them.
  - Finalize Admin 1.0.0 and assessed existing module versions/forward edges, platform
    version/changelog and closure metadata under releases.md. Perform focused allowed
    post-metadata validation; substantive changes invalidate the prior gate.
  - Clean only identified task-created disposable resources after showing exact scope;
    preserve active dev/base databases, durable data, Qdrant/model caches and evidence.
    Close/move roadmap, merge with merge commit, run short main sanity, tag/push/publish
    release and delete roadmap branch. Report real results, limitations and resources.

## Risks and unresolved scope

The highest risks are unsupported composite operation ordering/proof reuse,
accidental maintenance bypasses, incomplete Core coverage hidden by
metadata-only scaffolds, field/lookup/export disclosure, and runtime branding
diverging across browser/mail/error/build consumers. Exact contract signatures
are now recorded by point 1 and must be followed during implementation rather
than invented inside renderer code. No new dependency or infrastructure requirement is proposed.

## Activation validation and instruction products

Activation was documentation-only: source paths, relative links, active metadata,
open checklist and final-only verification ordering were reviewed without broad
quality/native operations. At activation, neither instruction product required
changes because no operating rule or delivered capability changed. The point 1
assessment below records subsequent contract references. No implementation point
or release was completed by activation itself.

## Point 1 — Public contract specification

Completed 2026-10-29. The [owning handbook specification](../../../handbook/modules/admin/README.md)
records PHP declarations/DTOs/bounds/errors, explicit guarded bindings, wire schema,
app registration, complete frontend controller/props/slots/events and component
registry seams, trusted aggregate phases/lock order/proof/refusal behavior,
per-Core operation coverage and maintenance/settings/presentation matrices.
All new symbols/routes remain explicitly specified, not implemented or delivered.
The original review now links to that source rather than claiming signatures
remain undefined. ARCH-001–004 and feature/0.7.0 remain unchanged.

### Source grounding and compatibility

- Knowledge status was current: 193 documents / 9,448 chunks, no missing/orphan
  chunks. Bounded current-intent retrieval located Audit reads, Auth capabilities
  and transport. Original Audit/Auth guides, public API/database/module guides,
  ManagementProvider/EntityProvider and value constructors, ModuleApis/OperationGuard,
  Core contracts/definitions/manifests, AuthApplication presentation/controller types,
  EntryTransaction, AccessTransaction/ChangeAssignments/AccessState, AuthorityLock
  and framework lifecycle/health/settings/integration/work sources were opened.
- Source-verified: assignment previews include identity version; profile-first
  would invalidate them. Supported pair order is assignment then profile. Multiple
  uses of the same every-action proof are incompatible, not implicitly reusable.
  Applicable on-use/hybrid health checks cannot execute in an ambient transaction;
  those participants use independent workflows initially, preserving current guards.
- The private [compatibility ledger](../../architecture/admin-contract-baseline.json)
  enumerates 19 existing guarded APIs / 59 methods with native signatures, imported
  types, declarations, versions, operations, permissions and audience dispositions;
  12 legacy declaration/slot contracts retain their signatures/schema.
- Planned independent releases: Admin develops at 0.1.0 and closes at 1.0.0; Auth
  1.2.0, Permissions/Audit 1.1.0 for additive surfaces. Existing API versions remain
  exactly 1.0.0. Supplied Core platform ranges will retain their lower bound and
  widen to <0.8.0 with delivered support; the app scaffold similarly widens its
  current range. Current manifests/VERSION/changelogs are unchanged because this
  point delivers specification/tests, not runtime capability. Customer ranges are
  never rewritten automatically. No incompatible module MAJOR is approved.

### Focused verification and instruction products

The focused compatibility test file exercises actual current public import checks,
legacy provider construction and schema refusal bounds, plus source signatures,
API registration/version/permission preservation. Supported command:
`python3 tools/maintainer/test-python --scope=contracts --path=contracts/test_admin_contract_specification.py`.
Final focused result after fixture review: **4 passed**, exit 0, cleanup 0. This small check resolves whether the proposed additive
extension preserves existing customer consumers; it is not an Admin runtime,
concurrency or browser PASS. Reports remain under `.runtime/python/reports/`.
Broad verification remains exclusively point 12.

Owner backend/frontend skills and separately authored customer counterparts now
route to canonical Admin contracts and require respecting delivery status. Root
AGENTS and customer AGENTS, security/database/Vue/Tailwind skill entrypoints need
no change: existing ownership, atomic Audit, guarded APIs and replacement rules
already govern the specification; no new operating policy, dependency, security
exception or implemented UI is introduced. No runtime services, database resources,
containers or volumes were created or removed.

## Point 2 — Core and guarded entry foundation

Completed 2026-10-30. Admin source develops at 0.1.0 as a Core requiring
Audit/Auth/Permissions ^1.0.0, with an explicit null → 0.1.0 initialization edge.
The existing preparation participant supplies declaration synchronization and
Audit; no Admin table, user assignment, runtime registration or migration was added.
Guarded AdminAccess 1.0.0 provides bootstrap; other declared operations fail closed
after entry checks until their owning points. Named GET `/api/admin` uses a fixed
lazy platform dispatcher and owned controller/FormRequest/JsonResource. No HTML
Admin route, menu, declaration discovery, business write or frontend facade is
claimed. The welcome catalogue uses localized manifest descriptions and an
existing adjustments icon; rendered acceptance is pending.

### Source grounding, security and compatibility

- Knowledge was current: 198 documents / 9,570 chunks with no missing/orphan chunks.
  Bounded current-intent retrieval located Auth entry/session/security and the
  architecture review. Original Admin contracts, public boundary/module guides,
  StepUp/SecurityContext, ModuleApis/OperationGuard, HTTP ingress, Permissions
  AccessTransaction/preparation participation and registry/availability/management
  sources were opened. Laravel Boost confirmed Laravel 13.37.0 and supplied
  version-aware middleware/FormRequest documentation. Backend/Laravel/security
  skills and the frontend catalogue/layout reference guided implementation.
- Entry requires current native `runa.admin.enter`, operational Auth session and
  session/security-version-bound password proof for `runa.admin.enter`, TTL 300,
  everyAction=false. Both middleware and protected read recheck authority/proof;
  no generic system-role bypass. Registry → authority → Auth user/proof locks use
  the default PostgreSQL transaction. HTTP admission ends before dispatch, leaving
  future independent commands under their owning protocols. Maintenance has no
  new exception. Fixed private diagnostics adapters require their additional grant
  and project cached safe flags/versions/dependencies/observation times, not engine
  objects, settings, paths, payloads or raw errors. Commands remain point 9.
- Source review identified the cold/unregistered-module route-loading hazard:
  direct module-controller registration attempted to load an unavailable class
  before entry guards. The fixed platform dispatcher now defers owned controller
  construction until admitted and rechecks source/current generation. This is a
  technical implementation correction, without changing the approved HTTP paths.
- Admin retains its specified >=0.7.0 <0.8.0 target. Existing Core ranges widen
  compatibly to >=0.4.2 <0.8.0 with source-only `admin_platform` edges and assessed
  PATCH versions: Auth 1.1.5, Audit/Permissions 1.0.5. Historical handlers/migrations,
  guarded signatures/API versions and required dependency directions are unchanged.
  Later Auth 1.2.0 and Audit/Permissions 1.1.0 feature plans remain applicable.
  Platform VERSION/app scaffold range remain under their later owning points.

### Focused verification and remaining acceptance

The necessary focused check resolved whether actual native session admission,
ordinary direct grants and reusable entry proof work together under nested owner
transactions. Supported command:
`python3 tools/maintainer/test-backend --parallel --processes=1 --filter='admits a direct Admin entry grant only with current session-bound password proof'`.
An initial pre-execution bootstrap failure exposed the controller loading issue
above; no test ran in that attempt. After correction: **1 passed, 20 assertions**,
native exit 0, cleanup 0; duration 5.71 seconds. It covers missing entry grant,
missing/wrong-action proof, access without a superadministrator role, reusable
proof and its 300-second expiry. Coordinator evidence is
`.runtime/tests/runa-results.json`; no broad suite/native/browser PASS is claimed.

Additional authored cases cover anonymous/revoked authority, session/security
proof binding, maintenance/missing Core, source/runtime declarations, installation
history and safe diagnostics with independent grants. The retained-history planner
fixture includes the new Admin install and appended compatible Core edges. Public
consumer fixtures admit AdminAccess and reject private Admin engines/bridges.
Complete suites, full source/static checks, native installation/update, browser
and complete target-platform acceptance remain exclusively point 12.

### Documentation, instruction products and resources

Synchronized the Admin guide's exact delivered/planned split, declarations and
operations status, independent changelogs/Core versions, public boundary and
welcome layout catalogue reference. Owner frontend reference and separately
authored customer frontend reference route to that canonical status/access
distinction. Existing owner/customer backend skills already route to Admin
delivery status; both AGENTS contracts and other domain skills need no changes:
no new authority, dependency, security exception or workflow rule was introduced.
The dated architecture review and point-1 record retain their historical baseline.

The supported runner created and removed only `runa_test_test_1` after inspecting
its exact identity; the initial/final scoped cleanup lists were [] and
[runa_test_test_1]. The base runa_test database, development data, containers,
volumes, caches, backups and reports were preserved. No Docker resources were
created or removed. The roadmap remains active; point 3 is next.

## Point 3 — Declaration discovery and authorized projection (in progress)

Initial worktree implementation and focused evidence, 2026-10-30, recorded before
points 2a–2b. The continuation below records the revised read/discovery checkpoint;
point 3 remains incomplete, with its prerequisites now satisfied.
The draft implementation adds additive ManagementPageProvider and bounded
page/section/field/command/binding/target/widget/contribution values, owning handler
interfaces and read/write/result DTOs without changing legacy provider methods,
constructors or ModuleManagement schema 1. the earlier draft 0.2.0/no-op edge was retired in point 2a; Admin remains at
unreleased development 0.1.0. Point 2a records current changed Core/API versions;
platform VERSION remains unchanged. Its creation release remains 1.0.0 at verified
closure; platform target remains feature/0.7.0.

### Implementation and source grounding

Knowledge was current at entry: 199 documents / 9,597 chunks, no missing/orphan
chunks. Bounded current-intent retrieval located the Admin declaration contract,
Audit/Auth boundary and transaction foundations. Original Admin declarations,
public API/module guides, legacy Management providers/renderers, ModuleDeclarations,
ModuleApis, ModuleFrontend, OperationGuard/Authorizer, Registry/Availability and
AdminEntry sources were opened. Backend/Laravel/security skills and Laravel Boost
13.37.0 container documentation guided implementation. No dependency was added.

Source-verified discovery checks registration/source/hash, enabled availability,
owned handlers/components, declared target dependencies and exact API methods,
schema/collection bounds and deterministic order. Invalid/conflicting owners are
isolated with safe diagnostic codes. App providers use existing grants and explicit
App handler bindings; they cannot replace a foreign owner. Public import fixtures
now admit the new declarations/AdminRegistry and reject internal discovery,
projection and validation classes.

App registration uses a source-owned Laravel afterResolving bootstrap callback,
resolved before the first protected discovery and frozen at that discovery. This
keeps cold installation/recovery startup independent of unavailable module classes,
preserving the lazy module-loading boundary established by point 2. It is a
technical bootstrap choice within the approved registration contract, not a new
request-time installation or authority mechanism.

AdminAccess 1.0.0 now projects bootstrap/page/section reads under existing entry
proof and native authority. Projection removes denied fields/values/choices and
commands, rejects forbidden filters/sorts/lookups/unknown input/read-only writes,
validates typed fields and explicit sensitive set-state, preserves unknown totals,
and refuses mixed generations. Owning handlers retain record/option/selection/
count/export Policies and guarded API use. Contributions retain owner namespaces
and host permission checks; widgets fail with empty safe diagnostics. The shared
write-input intersection executes no writes. Preview/save/command remain refused
until their owning implementation points; required unavailable participation cannot
silently produce a partial Save. Only bootstrap has HTTP transport so far.

### Focused checks and remaining acceptance

Three necessary supported focused backend runs resolved separate implementation
uncertainties, all with --parallel --processes=1:

- `--filter='projects an independent App page and removes forbidden fields values choices queries and options'`: 1 passed / 23 assertions, 0.72 seconds; verifies real discovery/projection of App source and denial before handler calls using an explicit authorization double.
- `--filter='discovers only available module declarations without constructing business handlers'`: 1 passed / 18 assertions, 0.82 seconds; verifies available source discovery, exact target/dependency checks and zero business constructor/execution calls. An earlier attempt failed on a nonexistent fixture reset helper before assertions; corrected to the fixture's actual static reset fields and reran this same focused case.
- `--filter='registers lazy App declarations before guarded discovery and freezes replacement'`: 1 passed / 12 assertions, 4.37 seconds; verifies actual native session/password-proof entry, lazy App callbacks, authorized bootstrap, freezing and repeated reads.

All successful runs returned native exit 0 / cleanup 0. Additional authored cases
cover read-only/unknown inputs, declaration conflicts/components/late registration,
secret/exception redaction, DTO bounds, generation changes, retained upgrade history
and consumer imports. Complete suites, static analysis, concurrency/browser/native
installation/update acceptance remain exclusively point 12. These focused results
are not a broad, rendered, release or all-group PASS.

### Documentation, instruction products and resources

Synchronized canonical Admin status/declaration/registration/projection guidance,
module changelog/forward history, public boundary and framework references. Owner
and separately authored customer backend skills now route App registration and
record-authority consumption to that canonical guide. Root/customer AGENTS and
other domain skills need no change: existing authority, protected source, Audit,
proof and frontend replacement rules already apply; no new policy/UI is introduced.

Each supported run inspected its exact disposable worker scope and removed only
runa_test_test_1; initial cleanup lists were empty and final lists contained that
worker. Base runa_test, development data, containers/volumes, Qdrant/model caches,
backups and reports were preserved. Source fixtures remove only their owned temporary
directories. No Docker resources or persistent migrations were created/removed.
The roadmap remains active at point 3. The architecture decision is resolved; added point 2a is current and point 4
has not started.

## Point 2a — Native owner transaction and admission protocol

Implemented 2026-10-30 under the owner's explicit revised ARCH-001 decision.
The framework supplies source-owned mutation definitions/access/proofs and
ModuleTransactions, exact-operation HealthAdmissions and one default PostgreSQL
root with registry/authority/sorted Auth subject locks. All participants validate
before proof consumption and business applies; results follow all writes. Duplicate
writers/single-use demands, ambient roots, source/generation/health expiry changes,
missing admissions and non-JSON/oversized results refuse. Scope disposal and root
rollback precede mandatory owning refusal Audit. No retries or external work run
inside the root.

Auth profile validation and writes now share the native phase implementation;
Permissions validates the exact reviewed snapshot once under the shared lock and
applies that snapshot without rerunning it after a profile write. Standalone
administrative profile/assignment writes use that same runner. Canonical Auth-first
order succeeds; no assignment-before-profile shim or generic ordering graph remains.
Actual guard grants still recheck at each apply, and stronger session/action-bound
both-factor proof is consumed once with its owning Audit in the root. A failed
root invalidates the private prepared result rather than leaving a phantom model
projection available to its owning caller.

Authorization/introspection/effective-permission projection now consumes current
cached availability; actual operation ingress owns on-use execution. The new
admission scope reuses only its exact operation/source/policy/expiry evidence,
rechecks native cached failures and refuses any new health work after sealing.
Unmigrated readers/writers retain their owning protocols and require deliberate
migration; there is no blanket declaration that all operations compose.

Auth/Permissions independent versions and their changed guarded APIs are 2.0.0,
with explicit source-only native_transactions edges retaining published predecessor
history. Their platform floor is 0.7.0. Private owner fixtures select that candidate
runtime explicitly, without changing canonical platform VERSION. Admin remains
unreleased development 0.1.0, requires Auth/Permissions ^2.0.0 and retains its actual
initial edge; the unneeded point-3 0.2.0/no-op edge was removed. Audit remains 1.0.5.
No database migration or development/production reset was needed.

### Grounding, focused evidence and remaining verification

Knowledge was current: 200 documents / 9,658 chunks at start; bounded retrieval
located Admin entry, Permissions authority/transaction guidance and the reviewed
proposal. Original native guard/scope, availability/health, permission resolution,
Auth profile/step-up/security and Permissions preview/transaction sources were
opened. Backend/Laravel/security guidance applies; Laravel Boost 13.37.0 confirmed
transaction behavior. No dependency or new infrastructure/service was introduced.

Necessary supported checks used --parallel --processes=1 with exact native names:

- Mixed Auth-first profile/assignment commit and atomic Audit/proof: 1 passed,
  15 assertions initially. Rollback-on-Audit plus exact-operation health expiry/
  source/unwind checks: 2 passed, 23 assertions. After the explicit API/module 2.0.0
  and candidate-runtime changes, these same three scenarios passed together:
  3 passed / 38 assertions, 7.29 seconds, native exit 0 / cleanup 0.
- The focused standalone API 2.0.0/stale-preview/duplicate-writer/single-use test
  passed: 1 test / 15 assertions, 4.40 seconds, native exit 0 / cleanup 0.
  Its first attempt used an uppercase
  UUID while expecting a duplicate-writer refusal; existing TrustedPrincipal's
  lowercase grammar correctly rejected the constructor. The fixture now repeats
  the actual canonical UUID; native UUID acceptance was not relaxed.

The final focused run after adding the commit fence (current maintenance, source,
required graph and health expiry without rechecking a consumed write grant) passed
all three native write scenarios: 3 passed / 45 assertions, 11.52 seconds, exit 0 /
cleanup 0. The separately received health admission scenario adds 8 assertions;
these four focused cases total 53 assertions, not a complete backend suite.
A separate focused double-failure case (business Audit and independent refusal
Audit) passed 1 test / 14 assertions in 4.84 seconds, native exit 0 / cleanup 0.
It verifies 503 refusal, rollback and invalidation of both owners' prepared results.
All refusal hooks are attempted despite another hook's failure. The five focused
scenarios total 67 assertions; this remains scoped implementation evidence.

No full backend suite, static analysis, browser/concurrency/native installer or
release PASS is claimed. Existing reviewed access/ABA/proof/Audit fixtures remain
and current source consumers/API versions are updated; complete selected checks
remain final point 12. The immutable historical compatibility ledger is inventory,
while its executable checks now validate current declaration/consumer contracts
rather than freezing every old signature.

### Documentation, instruction products and resources

The canonical native transaction guide owns signatures, audiences, phases and
admission behavior. The Admin composition guide replaces obsolete plan/proof/
identity-lock/ordering contracts with native owner participation; access/profile
consumption guides, version changelogs and public import/signature boundaries are
synchronized. Owner/customer backend skills link the source contract and preserve
ordinary App ownership/protected-source restrictions. Root/customer AGENTS and
Laravel/security/frontend skills require no new rule: the approved owner policy,
server authority, atomic Audit and public source boundaries already apply.

Each supported run inspected exact disposable worker scope and removed only
runa_test_test_1; base runa_test, development data, active containers/volumes,
Qdrant/model caches, backups and reports were preserved. No Docker resources were
created or removed. The preceding point-3 worktree is preserved separately and
remains incomplete. Point 2b revises its read/presentation contracts next.

## Point 2b — Revised read/presentation contracts

Completed 2026-10-30 as specification work before resuming point 3. The canonical
Admin declaration/frontend/operations guides now specify:

- Field-bound options input and result, exact field authorization and bounded
  subject/filter/cursor semantics; a denied sibling cannot block an allowed lookup.
- Capability-specific resource/options/widget/write interfaces and explicit widget
  observation/expiry results, including unknown/stale/unavailable behavior.
- Page component projection and owned component/build/manifest identity for every
  replacement, with fail-closed resolution and required-form blocking.
- Structural declaration validation separately from binding availability; an
  unavailable optional target cannot discard unrelated pages from the same owner.
- One shared ApplicationSessionShell and session controller, with creator-owned
  disposal and minimal authenticated AdminAccess.entry advertisement. The proof-free
  availability boolean exposes no protected data; every protected method retains
  entry proof and native authority. Point 4 delivers this contract and consumers.
- Platform ownership of shared.application_settings, protected forward migration,
  public adapters and existing installation/update order. App owns definitions,
  values/assets; no customer-authored prerequisite migration or runtime DDL.

These are corrections of unreleased specifications under the approved reassessment,
not delivered runtime claims. Point 3 must migrate its preserved unfinished worktree
and tests to the new DTO/interfaces, field isolation, component identity and widget
results. Point 4 implements shell/advertisement/build resolution; points 5/10/11
consume the revised contracts. Admin remains development 0.1.0, creation target
1.0.0; platform feature/0.7.0 and approved security/maintenance decisions are unchanged.

Grounding: current knowledge status (201 documents / 9,696 chunks), bounded current
retrieval and original declaration/frontend/operations/reassessment sources; source
inspection of AuthApplication/useApplicationSession, manifest-bound moduleFrontend
registry and distribution update stages. Backend/frontend/security guidance applies.
Only documentation/staged diff review and mandatory commit hooks belong to this
point; no runtime suite, rendered acceptance or final verification PASS is claimed.
Incremental knowledge freshness is refreshed for the source changes before commit.

Instruction products: owner and customer frontend skills route session/build
composition to its canonical guide. Root/customer AGENTS and backend/security skills
already enforce public boundaries, server authority, protected source and delivery
status, so need no duplicated contract rule. The existing point-3 backend guidance
and runtime work remain outside this specification commit. No dependencies, database
changes, Docker resources or infrastructure were created/removed; existing data,
Qdrant/model caches and reports are preserved. Point 3 is next.

## Point 3 continuation — field-bound reads and binding isolation

2026-10-30: after point 2b, migrated the preserved declaration worktree to separate
resource/options/widget/write capabilities and immutable field-bound options and
widget freshness DTOs. Discovery verifies handler capabilities without constructing
business handlers. Runtime target availability is checked per actual binding;
structurally valid App/optional bindings with missing targets no longer discard
unrelated pages. Required unavailable sections retain safe blocked descriptors.

Lookup projection validates only the requested readable reference field, rejects
unknown/denied fields before handler execution and rejects mismatched result field
identity. Widget projection preserves owning observation/expiry, marks expired data
stale and refuses future/invalid observations without exception disclosure. Existing
native entry/proof, owning guarded calls, record Policies and write refusal remain.

A necessary focused supported check resolved the new field/isolation wire behavior:
`python3 tools/maintainer/test-backend --parallel --processes=1 --filter='authorizes one lookup|isolates an unavailable binding'` passed 2 tests / 19 assertions,
0.93 seconds, native exit 0 and cleanup 0. A second focused run of the existing
App field/value/query denial scenario after adding an independently readable lookup
also passed: 1 test / 23 assertions, 0.86 seconds, native exit 0 / cleanup 0. The runner showed and removed only
`runa_test_test_1`; base/development databases, containers, Qdrant/model caches and
reports were preserved. No broad suite or rendered acceptance was run.

After making capability checks explicit at invocation, the same three focused
scenarios passed together: 3 tests / 42 assertions, 0.94 seconds, native exit 0 /
cleanup 0. This repeated only the affected focused scenarios.

Backend/Laravel/security guidance and Boost Laravel 13.x container documentation
were consulted; the current knowledge query and original sources from point 2b
remain applicable. Both instruction products already link the canonical Admin
contracts and delivery status; customer backend guidance travels with this work.
No root AGENTS/security policy or dependency change is needed.

Point 3 remains OPEN. Remaining work is complete page/section/field/widget component
and build identity projection with its source registry evidence, stale/missing-build
scenarios and final review of discovery/read consumers. Point 4 still owns compiled
registry resolution, shell/entry advertisement and frontend rendering. The read/
discovery checkpoint is not a completed point or release PASS. Admin stays 0.1.0;
no migration, reset, infrastructure or disposable Docker resource was introduced.

## Point 3 completion — owned component/build projection

Completed 2026-10-30. Discovery now captures source-owned component identities for
pages, sections, fields, widgets and contributed sections, including current module
manifest identity and matching generated build evidence. Admin projects that public
shape without source paths or handler details; discovery/page/bootstrap revisions
include component evidence. A null component still chooses the supplied default;
a non-null component with build_id=null is explicitly unavailable and cannot select
a loader or default. This nullable state completes the unreleased exact wire
signature without changing the approved presentation/security architecture.

The existing Vite plugin emits public/build/runa-components.json and retains the
same evidence in its virtual builds export. Owner identity covers sorted relative
source hashes from module frontend or ordinary App resources, including helper
changes and additions. Missing, malformed, symlinked, stale, foreign-manifest and
oversized evidence fail closed per component. Source changes during bundle
production refuse mismatched evidence. Source-bearing discovery checks canonical
Vue files and current tree hashes; production strips frontend source and verifies
compiled evidence, owned membership and module manifest identity instead. This
preserves production isolation without adding runtime Vue source or dependencies.

Compiled registry resolution, development-server evidence publication, required
form blocking and rendering remain point 4 (form orchestration in point 5). No
business adapter, write coordinator, frontend route/controller or rendered UI is
claimed by point 3. Admin stays development 0.1.0 with its real initial installation
edge; no version churn, migration or reset was needed.

Grounding: knowledge current at entry (201 documents / 9,727 chunks), bounded
current-intent retrieval and original Admin declaration/frontend/public-boundary
and framework sources. Backend/Laravel/security and relevant frontend guidance
were loaded; Boost supplied installed-version filesystem guidance. Both owner and
customer guidance already route component/build consumption to the canonical Admin
guide, so neither instruction product needs a duplicate rule. Public/source
boundaries, Admin status/changelog/declarations/frontend and framework guidance are
synchronized; historical point-3 checkpoints above retain their actual open status.

Focused evidence resolving build-wire and production-source uncertainty:

- Supported backend command --parallel --processes=1 with filter
  'projects owned component evidence|binds module component projection': final
  2 tests / 21 assertions, 0.92 seconds, native exit 0 / cleanup 0. An initial
  fixture accessed its module descriptor before the owned autoloader loaded it;
  corrected the fixture to discover first. The earlier corrected run passed
  2 tests / 20 assertions before adding stripped-source discovery coverage.
- One Node source diagnostic selected only 'component evidence binds' from the
  existing module-build.test.mjs: 1 passed, exit 0. It verifies deterministic
  identity, virtual/asset agreement, source-change fencing and symlink refusal.
  Direct Node is used because the supported frontend wrapper selects browser
  .test.ts cases and has no selector for this Node-only build-plugin case.
- One temporary Node/PHP parity diagnostic confirmed identical source maps and
  build identity for a component plus an imported helper, exit 0. Its owned
  temporary tree was removed in finally. Full builds/suites/static/browser/native
  acceptance remain exclusively point 12; no broad or release PASS is claimed.

Supported backend runners displayed their exact disposable scope and removed only
runa_test_test_1. Base runa_test, development data, containers/volumes, Qdrant/model
caches, backups and reports were preserved. No Docker resource was created or
removed. Point 3 is complete; point 4 is next.

## Point 4 completion — shared session shell and Admin presentation

Completed 2026-10-30. Added named Admin document, entry-advertisement and page-read
routes with lazy owned controllers and the existing authenticated maintenance/
entry guard. The advertisement returns only current availability, without proof or
protected bootstrap values. Ordinary maintenance, including missing mandatory
Core installation, remains 503. Guarded PHP retains normal gateway availability;
protected reads retain entry grants and the native session-bound password proof.

Extracted ApplicationSessionShell under Auth and updated AuthApplication consumers.
Auth/account/Admin share one caller-owned session controller and retain native
activity, expiry, logout, profile and reauthentication. Standalone layouts retain
preference session initialization; inside the shared shell they do not start
another session read. Authority refreshes and account/session changes fence Admin
advertisement/bootstrap/page responses. English scope never writes the account's
ordinary app language; theme and supplied responsive/accessible layout remain.

AdminApplication supplies module-left/page-top navigation, dashboard and empty/
unavailable states, useAdmin/useAdminPage, entry password confirmation, nested
presentation providers and a public @/runa/admin facade. DefaultApplication
selects Admin routes and forwards an explicit static App component registry without
expanding the app scaffold. Full customer presentation fixtures retain platform
admission. Component resolution checks owner/key/manifest/build before choosing a
loader or App entry; component failures remain isolated. Vite serve atomically
publishes current evidence and invalidates/reloads its virtual registry after
source changes. Generated business tables/forms/actions and required-form blocking
remain point 5; business handlers/writes/settings retain their later points.

Agent-owned exact contract details: AdminController.entryProof exposes transient
password/busy refs for public AdminEntryProof or headless submission; confirmation
uses the fixed native action, never duplicates submission or replays business
writes, and clears credentials on completion/invalidation/disposal.
ApplicationSessionController.authorityRevision advances on successful validation,
not every heartbeat. ApplicationLayout.locale fixes presentation language. These
complete the approved shared-session/presentation design without a new owner
architecture premise. Admin stays 0.1.0 and Auth stays the already unreleased 2.0.0;
existing initial/native_transactions edges suffice. No schema/data migration or
extra source-only version edge is introduced.

Grounding: local knowledge current at entry (201 documents / 9,737 chunks), bounded
current-intent query and original Admin/public frontend/session/framework sources;
frontend/Vue/Tailwind and backend/Laravel/security skills, installed Tailwind 4.3.3
and Boost Laravel 13.x routing/FormRequest documentation. Public Admin status,
declarations/frontend, customization, component/layout references, public boundary,
framework and Auth account/changelog guidance are synchronized. Owner and customer
frontend skills already route to these canonical contracts and require shared
session/build checks; no duplicate rule or root contract change is needed.

Necessary focused diagnostics only:

- Supported backend --parallel --processes=1 with filter 'advertises only current
  entry|routes Admin pages through guarded': final 2 tests / 29 assertions,
  6.45 seconds, native exit 0 / cleanup 0. The fixture now publishes a registry
  generation change and expects ordinary missing-Core maintenance, rather than
  bypassing the platform guard.
- Supported frontend filters for session fencing, supplied-session/English/menu,
  provider/build resolution, native entry proof and authorized locations: 5 passed,
  3.92 seconds, exit 0 / cleanup 0. A subsequent focused public customer replacement
  plus shared-session/English rerun passed 2 scenarios, 3.81 seconds. Six distinct
  scenarios were exercised. Initial diagnostics exposed and corrected optional
  Boolean wrapper defaults and duplicate layout preference session initialization;
  the test mock now uses the emitted virtual registry instead of unsupported mock
  hoisting through the shared Vitest facade.
- Direct Node selected only 'development evidence republishes' in the existing
  module-build.test.mjs: 1 passed, 37.76 ms, native exit 0. The supported frontend
  browser wrapper cannot select this Node-only plugin case. It verifies published/
  compiled agreement after a source edit and watcher disposal; its owned temporary
  tree was removed in finally.

Complete suites, type/static analysis, builds, scanners and authored responsive
light/dark E2E remain point 12. Mandatory commit hooks remain required. No release
or complete rendered acceptance is claimed. Supported runners displayed and removed
only runa_test_test_1; base/development databases, active development containers,
volumes, Qdrant/model caches, backups and reports were preserved. No task-created
Docker resources required removal. Point 5 is next.

## Point 5 checkpoint — section transport and resource read lifecycle

2026-10-30: implemented named section-read HTTP through the existing lazy guarded
dispatcher, a dedicated Form Request and JsonResource. Admission/proof precedes
validation and handler construction; owning page/section/field/read/filter/sort/
lookup Policies remain authoritative. GET filters use one bounded JSON object to
preserve scalar/null types; PHP consumers retain their native typed array. Unknown
keys, malformed input and denied reference fields fail closed. No write executes.

The public useAdminResource read lane decodes record/page/options by the requested
capability and readable descriptor, preserves unknown totals and secret set-state,
and refuses foreign subjects, denied fields, raw secrets and mismatched options.
Identical pending reads coalesce; newer reads, page/source/authority/account changes
and scope disposal fence old responses. Protected query subjects/filters are erased
on account/session or owning-page changes. The last same-context query refreshes
after a descriptor update or return to a visible document, without polling. Resource
command remains explicitly unavailable locally; it cannot send/replay a write.
Same-session page refresh retains mounted content while pending, preparing for
draft reconciliation without remounting the editor.

Grounding: local knowledge current at entry (201 documents / 9,764 chunks), bounded
current-intent retrieval and original Admin/frontend/form/table/public-boundary
sources. Backend/Laravel/security and frontend/Vue/Tailwind guidance were consulted;
Boost provided Laravel 13.x Form Request/named routing documentation. Both owner
and customer instruction products already route consumption to canonical Admin
delivery status and public forms/tables guides; no duplicated skill/root rule is
needed. Current Admin status/declarations/frontend/changelog and public API boundary
are synchronized. Admin stays development 0.1.0; this additive unreleased read
delivery needs no source-only version edge, migration or dependency.

Focused diagnostics resolved HTTP type/admission and lifecycle uncertainty:

- Supported backend --parallel --processes=1 with filter 'registers lazy App
  declarations|routes Admin pages through guarded': 2 tests / 75 assertions,
  19.34 seconds, native exit 0 / cleanup 0. Includes typed/null filter round trips,
  denied/unknown/malformed queries, options, secret refusal and pre-validation proof.
- Supported frontend filter 'Admin resource reads|Admin page refresh retains|Admin
  session diagnostic': 6 passed, 3.69 seconds, native exit 0 / cleanup 0. An earlier
  fixture asserted before native Response decoding settled; it now awaits the
  observable result. After adding query-erasure protection, the affected obsolete-
  query/descriptor/account scenario alone passed, 3.64 seconds, exit 0 / cleanup 0.
  Vite reported a dynamic-import analysis warning in its generated Vitest dependency;
  no application Vue warning or complete rendered acceptance is claimed.

Point 5 remains OPEN. Generated resource table/default controls, forms/commands,
static hints, dirty navigation and aggregate revision/read reconciliation are next;
atomic participant dispatch remains point 6. No broad suite/type/static/build/
scanner/native acceptance was run; point 12 retains final verification. Supported
backend cleanup displayed/removed only runa_test_test_1. No Docker resources were
created or removed; base/development databases, active services, durable volumes,
Qdrant/model caches, backups and reports were preserved. Mandatory hooks and staged
incremental knowledge freshness remain required for this checkpoint commit.

## Point 5 checkpoint — public cursor-table composition

2026-10-30: added public useAdminTable/AdminResourceTable and exact exported
controller/props/slots for explicit module/App page composition. The adapter owns
one existing useAdminResource lane and feeds the shared server Table/useTable
provider; it performs no second transport, local filtering/sorting/slicing or
all-page scan. It remembers only visited opaque cursors, resets paging on query/
descriptor changes, invalidates later changed cursors and refuses unvisited jumps.
Nullable owner totals are preserved; unknown navigation counts prevent exact totals
from manufacturing numbered cursor access. Backend/owner limits remain authority.

Readable columns preserve sort/filter flags and secret Set/Not set. Integer/boolean
filters retain native types; invalid conversion refuses locally instead of dropping
the filter and sending a broader query. Missing/duplicate public UUID row identities
refuse rendering. Account/authority/page changes erase protected query/cursor state.
English Table controls retain shared responsive/theme/keyboard/loading/error behavior.
Public filter/toolbar/cell/row slots and a customer composition fixture exercise
partial replacement without private imports or copied admission logic. No preference
storage, dependency, business write, backend/API signature or migration was added.
Admin remains unreleased development 0.1.0; its initial forward edge is unchanged.

Grounding: knowledge current at entry (201 documents / 9,777 chunks), bounded
current-intent retrieval and original Admin frontend/declaration, public boundary,
customization and shared tables guides. Frontend/Vue/Tailwind guidance, table/
component/forms/accessibility references, installed Tailwind 4.3.3 and existing
Table/provider/resource sources were consulted. No Laravel API was changed or
guessed. Current Admin status/frontend/changelog, public boundary and canonical
tables guide are synchronized. Owner and separately authored customer frontend
references already route to these canonical contracts; both instruction products
need no duplicate rule. Existing AGENTS/security/session/public-boundary policies
apply unchanged; no new authorization or reusable operating rule is introduced.

A necessary supported focused diagnostic resolved cursor-number bridging, typed
filter refusal, row identity and public composition uncertainty:
`python3 tools/maintainer/test-frontend --filter='Admin cursor tables'`.
Initial three scenarios passed in 3.74 seconds. After correcting normalized-query
refresh completion and adding the actual customer slot consumer, the same affected
selection passed **4 tests**, 3.71 seconds, native exit 0 / cleanup 0. The existing
Vite warning is in the generated Vitest runner's dynamic import; no application
Vue warning was reported. Reports remain in the supported runner locations.
No complete suite/type/static/build/scanner/browser-matrix/native acceptance or
rendered/release PASS is claimed; broad verification remains point 12.

Point 5 remains OPEN. Automatic section dispatch, generated forms/field controls,
commands/confirmation, owner-bound selection/views/exports/bulk, dirty navigation
and shared aggregate revision/read reconciliation remain to implement; atomic
participant execution remains point 6. This commit records a functional public
collection component, not completion of the whole generated CRUD contract.
No Docker resources or databases were created/removed by this frontend selection;
active development services/data, durable volumes, Qdrant/model caches, backups
and reports were preserved. Mandatory hooks and staged incremental knowledge
freshness apply to this checkpoint.

## Point 5 checkpoint — automatic declared collection sections

2026-10-30: ManagementSection now declares source-owned `view: 'record'|'table'`
(default record), projected through the exact section wire/TypeScript decoder.
This distinguishes presentation intent for the existing reader's record/page
capabilities without probing a handler or inferring intent from field names.
Unsupported values refuse declaration/response decoding. This is an agent-owned
presentation signature detail within point 5, not a new operation or authority.

The default page dispatches table sections to one existing useAdminTable and
AdminResourceTable scope. It retains owner cursor/query limits, secret set-state,
English shared controls, admission, account fencing and disposal. A declared
component precedes the generic provider; unavailable non-null source/build identity
never falls back to a table. Binding diagnostics refuse mounting even custom
section presentation. Existing per-section boundaries isolate rendering failures.
Replacements receive public AdminSectionPresentationProps with section and owning
page controller, allowing public resource/table composition without another
session owner or a competing default read lane. Internal dispatch components are
private; no extra facade runtime entrypoint is needed.

Grounding: current local knowledge at entry (201 documents / 9,792 chunks), bounded
current-intent retrieval and original Admin declarations/frontend/public boundary
and frontend component/table/form guidance. Frontend/Vue/Tailwind and backend/
Laravel/security skills were consulted. This change uses ordinary PHP value
validation and existing projection; no new Laravel API was needed. Installed
Tailwind remains 4.3.3; no new styling system, dependency or runtime configuration.
Canonical Admin status/declarations/frontend/changelog and public API guide are
synchronized. Owner and separately authored customer frontend/backend references
already direct consumers to those canonical guides; both instruction products need
no duplicate rule or reference change. Admin remains unreleased 0.1.0; the additive
unreleased declaration/wire refinement needs no intermediate migration/history edge.

Necessary focused diagnostics resolved automatic dispatch, replacement precedence,
unknown intent refusal and unavailable-source behavior:

- Supported frontend filter `Admin automatic tables`: four scenarios PASS,
  3.94 seconds, exit 0 / cleanup 0; the added unavailable declared-component
  fallback scenario then passed independently, 3.88 seconds, exit 0 / cleanup 0.
  The existing Vite warning concerns generated Vitest runner dynamic import only.
- Supported backend `--parallel --processes=1` filter
  `projects explicit collection intent`: one test / four assertions PASS,
  0.72 seconds, exit 0 / cleanup 0. An initial attempt during the frontend run
  was refused by the suite lock before execution; it was rerun after that run ended.
  Guarded cleanup displayed/removed only runa_test_test_1, preserving runa_test.

Point 5 remains OPEN: generated record forms/controls, commands/confirmation,
selection/views/exports/bulk, dirty navigation and aggregate revision/read
reconciliation remain. Native atomic participant execution remains point 6.
No broad suite/type/static/build/scanner/browser acceptance was run; point 12 owns
final verification. No Docker resources were created/removed. Active services,
development/base data, durable volumes, Qdrant/model caches, backups and reports
were preserved. Mandatory hooks and staged incremental knowledge freshness apply.

## Point 5 checkpoint — controlled fields and native record presentation

2026-10-30: delivered public AdminField/validateAdminField and exact exported
props/emits/lookup input for controlled module/App field composition. Defaults
reuse FormField/TextInput/NumberInput/Checkbox/Select/TagsInput, merging safe
static hints with owner errors. Integer input retains incomplete/out-of-range
drafts without rounding or stale valid fallback; UUIDv7/UTC/choices/required and
Unicode max-length hints remain subordinate to backend validation. UTC text has
no implicit browser-timezone conversion. Secret set-state never fills a password
input; read-only values show only Set/Not set. IME and English shared controls
retain their established behavior.

Field-specific owned components precede the inherited provider and supplied
default. Missing/stale declared builds never fall back; each field isolates render
failures. Read-only/descriptor/busy state blocks even custom update emissions.
Optional field-bound lookup providers use shared bounded Select cursor/cancellation
and initial/off-page labels; missing providers refuse free-text lookup input.
The public customer fixture consumes only Admin/UI facades and exact typed props.
These presentation inputs confer no grant, operation or transport authority.

Default record sections now mount one existing useAdminResource read lane for
the projected public subject or owning singleton. They render only returned
readable values, retain native account/authority/source fencing and descriptor/
visibility refresh, and offer explicit safe read retry. Raw secret responses refuse
the whole record before rendering. All default record fields remain read-only;
no editable form/Save/command or competing write controller is claimed.

Grounding: local knowledge current at entry (201 documents / 9,802 chunks), bounded
current-intent retrieval and original Admin/frontend/forms/components/public-boundary
sources. Frontend/Vue/Tailwind and secret/browser security skills and relevant
references were consulted. Installed Tailwind is 4.3.3; no Laravel API, schema,
configuration or dependency changed. Canonical Admin status/frontend/changelog,
forms/components and public API guidance are synchronized. Owner frontend guidance
and the separately authored customer frontend skill already direct consumers to
canonical Admin delivery status, forms, public facades and shared session/build
rules; both instruction products need no duplicated rule or reference change.
Admin remains unreleased development 0.1.0 with its real initial installation edge;
this frontend delivery needs no intermediate version/history or migration.

Necessary focused diagnostics resolved precision/secret/input/replacement and
native read-composition uncertainty. Supported frontend selections first passed
six field scenarios (3.86 seconds), then two record scenarios and the field-bound
lookup scenario (3.93 seconds). After adding invalid-calendar-date refusal,
projected subject identity, associated read-only text and per-field render-failure
isolation, the affected selection
`python3 tools/maintainer/test-frontend --filter='Admin fields|Admin automatic records'`
passed **10 tests**, 4.00 seconds, native exit 0 / cleanup 0. Existing Vite warning
concerns only generated Vitest runner dynamic import. Reports are preserved.
No broad suite/type/static/build/scanner/browser-matrix/native acceptance or
rendered/release PASS is claimed; point 12 retains final verification.

Point 5 remains OPEN: editable record form orchestration and automatic lookup
adaptation, commands/confirmation, selection/views/exports/bulk, dirty navigation
and shared aggregate revision/read reconciliation remain. Native atomic execution
remains point 6. No Docker resources or databases were created/removed; active
development services/data, durable volumes, Qdrant/model caches, backups and reports
were preserved. Mandatory hooks and staged incremental knowledge freshness apply.

## Point 5 completion — generated editors, independent commands and collection adapters

Completed 2026-10-30. The remaining point-5 scope now supplies one public
useAdminForm owner per page plus AdminPage/AdminForm/AdminSection/AdminAction.
Default record sections and table record/create editors share finite JSON drafts,
static validation, native subject revisions, field-bound UUID cursor lookups,
controlled errors, confirmation and public customer replacements. No key-name
heuristic selects a write: source commands explicitly declare intent and inputs;
proofAction identifies the exact native action. Delete intent requires destructive
confirmation, save/create intent is unambiguous and command binding availability
is projected separately from the reader. These agent-owned presentation/binding
signature details complete the approved design without a new behavioral premise.

Reads reconcile baseline/local/remote values without remounting edited fields.
Clean/disjoint fields merge; overlaps retain their old write token until a local
choice. Denied fields leave drafts/errors/snapshots, read-only changes revert,
secret replacements clear on invalidation and account/authority/page changes
cancel/fence protected work. Shared page mutation admission refuses competing
intents instead of queueing/rebasing/replaying them. 422 maps fixed safe field
errors; 409 refreshes reads automatically while preserving drafts. Dirty document
navigation and explicit Admin navigation share useDirtyGuard; custom routers use
the public beforeLeave contract for their own history. Confirmation cancel keeps
the editor, and busy writes refuse departure. Pending/recovery_required retain
native operation identities, block blind resubmission and never become committed
success. Required build/render/read failures block Save while healthy siblings
remain visible. The public customer editor fixture reuses the same form/controller.

Independent command/preview transports now use named lazy guarded routes, bounded
Form Requests and JsonResource. Entry/proof admission precedes validation and
writer construction. The entry root ends before a native independent writer
executes; owners retain their own transaction, proof, preview, Policies, Audit
and side-effect protocols. Result projection reauthorizes current entry/fields;
validation echoes and forbidden preview/secret values are suppressed. Empty wire
maps retain object shape. Entry-scope versus operation-scope denial keeps stronger
native proof in the editor. Auth supplies public useOperationProof and
AuthOperationProof over its existing native security/step-up/email-code endpoints,
with eligible methods, credential cleanup and late-response fencing, no second
session or business-write replay.

The atomic frontend/transport contract includes exact participant sets, current
reviews and aggregate revisions, all-result decoding before baseline mutation,
shared independent-read reconciliation and explicit Save. Actual multi-owner native
execution deliberately remains point 6 and still refuses there; point 5 does not
claim a server atomic coordinator or real Core business adapters from points 7–10.

Cursor collections now compose native selection/views, TablePreferences,
TableActions and source-owned export/bulk/print/preference adapters. Unknown totals
never invent all-matching selection or numbered cursor jumps. Exports admit only
current visible/readable/nonsecret fields and server providers, without scanning
pages or treating the current page as a complete dataset. Bulk intersects projected
owning command authority, confirms and rechecks exact context/query/selection, then
starts one native provider. Owning review/proof/idempotency/selection/size/status
protocols remain authoritative. Operations are context-fenced. View preferences
are ephemeral by default; explicit account-aware source stores/scopes retain their
own declarations. No arbitrary backend export endpoint or new persistence engine.

Grounding: knowledge current at resumption (201 documents / 9,819 chunks), bounded
intent query and original Admin/form/table/session/public-boundary sources;
frontend/Vue/Tailwind/security plus backend/Laravel guidance and Boost Laravel
13.x Form Request/named routing documentation. Installed Tailwind stays 4.3.3.
Current Admin status/declarations/frontend/changelog, public boundary, shared
forms/components/tables and Auth proof/changelog guidance are synchronized.
Owner and separately authored customer frontend/backend guidance already point
to these canonical contracts and require public facades, admission, reconciliation
and build evidence; neither instruction product needs duplicated procedures or
new authority. Admin remains unreleased 0.1.0 and Auth remains unreleased 2.0.0;
current initial/native_transactions edges suffice. No dependency, migration,
runtime configuration or version-only history edge was introduced.

Necessary focused evidence resolved command transaction boundaries and generated
form/adapter composition rather than running ordinary broad gates:

- Supported backend --parallel --processes=1, filter
  'dispatches independent Admin commands': final 1 test / 37 assertions PASS,
  17.15 seconds, native exit 0 / cleanup 0. An initial run exposed camelCase method
  identity being used as an operation key; the fixed dispatcher now uses the
  declared command.preview operation. The test covers native admission before
  validation, safe fields/errors/previews, destructive/review refusal, exact stale
  revision and execution at transaction level zero. It also confirms that aggregate
  transport currently refuses before point 6. Temporary App writer evidence does
  not stand in for owning Core business/Audit acceptance.
- Supported frontend selected generated forms/collection adapters/native proof/
  dirty navigation/automatic records/tables/fields: final 35 scenarios PASS,
  4.53 seconds, exit 0 / cleanup 0. Earlier focused runs exposed and fixed reactive
  field enumeration and updated read retry presentation. A transient Vite parser
  message during simultaneous file formatting disappeared on the subsequent
  stable-source run; the stable run reports only the existing generated Vitest
  runner dynamic-import warning. The final safe-preview/aggregate selection passed 3 scenarios in 4.13 seconds,
  exit 0 / cleanup 0, including refusal of secret/undeclared preview differences.
- Authored responsive light/dark desktop/tablet/mobile E2E covers generated fields,
  validation, 409 local resolution without replay, save feedback and dirty unload.
  Browser matrix/build/type/static/scanner/full-suite/native acceptance are not run
  here and remain point 12; no broad or release PASS is claimed.

Supported backend cleanup displayed and removed only runa_test_test_1. No Docker
resource was created or removed. Base/development databases, active services,
durable volumes, Qdrant/model caches, backups and reports were preserved. Mandatory
hooks and staged incremental knowledge synchronization apply to the completion
commit. Point 5 is complete; point 6 is next.

## Point 6 completion — native aggregate Save — 2026-10-30

Complete the approved native composition, not a transport checkpoint. AdminAccess
preview/save now dispatch one root Action. Source-owned ManagementParticipant has
prepare(ManagementWrite): TransactionalMutation and project(array): ManagementOutcome;
discovery verifies ownership, interface, exact binding identity and explicit App
container binding. It does not expose native objects through HTTP/ModuleApis or
import foreign private factories. The public audience catalogue and named internal
caller policy include the new contract and the exact trusted adapters.

The coordinator requires the complete current writable atomic Save set, current
per-owner records/revisions and exact owning reviews. Participant IDs namespace
contribution inputs/results/errors. Semantic page/source/build/policy identities,
actor/subjects, requested values, owning revisions/reviews and immutable native
definitions bind the aggregate fingerprint. Unchanged successful health observation
publication does not pretend to be a business edit. At most 15 business participants
share the existing native 16-mutation bound with the Admin guard; HTTP and guidance
record that limit, 256-KiB input and 1-MiB output.

Entry admission ends before owner reads/preparation and native health preflight.
The source-owned Admin guard revalidates selection/reviews inside the canonical
root, before any apply. ModuleTransactions retains canonical owner/key and complete
sorted subject locks, all-owner validation, native proof planning/consumption,
current grants per apply and same-transaction Audit. Admin compatibility admits
zero native action proof demands for none or exactly one matching declared demand;
additional undeclared demands conflict. The guard projects all owner
results and current field/page authority before commit: pending/recovery results,
missing/mismatched records or lost result grants roll back the whole save. Required
native refusal history follows rollback and refusal-Audit failure remains blocking.
No old whole-write nesting, assignment-first shim, revision translation or review
regeneration is used. Independent lifecycle/settings/file/network/mail/bulk and
protected-authority protocols remain excluded.

Auth supplies private ProfilePage/ProfileEditor and Permissions supplies private
AssignmentContribution/AssignmentEditor: the initial real naming/login plus
ordinary access composition. Default record reads select the actor; explicit reads
use public UUIDs. Inputs retain owning validation, guarded reads/lookups, native
both-factor assignment proof and protected-role exclusion. Complete directories,
lifecycle/security and Permissions administration remain points 7–8. Admin remains
unreleased 0.1.0; Auth/Permissions remain unreleased 2.0.0 with existing
initial/native_transactions history. No dependency, runtime migration, storage or
version-only edge was added.

Author real HTTP cases for commit/replay, both Audit failure directions and explicit
retry after rollback, App contribution commit and invalid final projection, exact
participant loss before admission and under root locks, duplicate/incompatible
participants, safe namespaced validation, stale review/ABA, expired/consumed proof,
own-grant removal and refusal-Audit failure. App fixtures implement owning native
phases and explicit Audit without foreign persistence. AggregateCompetition uses
an independent PostgreSQL backend and bounded lock timeout to probe the registry
and complete sorted actor/target/contributor subject locks before any apply. The
full scenario/concurrency matrix is authored here and runs only at point 12.

Necessary focused supported backend diagnostics (--parallel --processes=1):

- 'coordinates an Admin aggregate HTTP review': 1 test / 27 assertions PASS,
  38.51 seconds, native exit 0 / cleanup 0. Initial discovery exposed incorrectly
  ordered localization keys; corrected to the canonical pl/en order. Later consumed
  proof assertions remain authored for final acceptance.
- 'rolls back an Admin aggregate in either owning Audit failure direction':
  2 tests / 46 assertions PASS, 91.15 seconds, exit 0 / cleanup 0. Both owners'
  writes/Audit and consumed proof roll back; explicit retry succeeds.
- 'includes an App editable contribution': final 2 tests / 43 assertions PASS,
  60.47 seconds, exit 0 / cleanup 0. This resolves physical App ownership and
  before-commit projection uncertainty. An initial fixture built an invalid pending
  DTO; corrected it to a valid pending outcome, which the coordinator rejects
  with conflict while rolling back every owner and proof.

- 'rolls back own grant removal when current result authority is lost':
  1 test / 20 assertions PASS, 25.85 seconds, exit 0 / cleanup 0. Entrance authority
  is reauthorized inside the root before returned values; removing one's own
  grants rolls back the profile, assignments and consumed proof.

No broad suite, static/type/build/scanner, browser or full concurrency acceptance
was executed; these remain point 12. Source review and synchronized Admin
status/declarations/composition/frontend/operations, public boundary/native
transactions, Auth profile and Permissions access guides and central module
changelogs describe the delivered behavior and remaining scope. Owner and customer
backend/frontend skills and AGENTS already reference the canonical composition,
App-only contribution, public boundaries, reconciliation and final-only checks;
no duplicated rule or instruction authority change is needed in either product.

Local knowledge was current at entry (201 documents / 9,852 chunks); bounded
retrieval was source-verified. Backend/Laravel/security/PostgreSQL guidance was
applied. Boost documentation lookup failed DNS resolution; existing local framework
APIs and native patterns were inspected instead. Incremental knowledge freshness
and mandatory commit hooks apply to this completion commit.

Supported cleanup displayed and removed only runa_test_test_1. Task-owned App fixture
source/schema was removed by its guarded test lifecycle. No Docker resource was
created or removed; active services, base/development database data, durable
volumes, Qdrant/model caches, backups and reports were preserved. Point 6 is complete;
point 7 is next. The active roadmap and release remain open.

## Point 7 completion — Auth administration — 2026-10-30

Auth owns the additive guarded AuthAdministration 1.0.0 directory/profile/lifecycle
reads and readonly DTOs. Directory uses UUID order, bounded filter-bound opaque
cursors, literal name search and separately authorized login predicates, without
email, totals, internal keys or credential state. Deleted/all and profile primary/
secondary/preference projections retain their separate current grants; missing or
unauthorized deleted subjects fail safely. Effective eligibility remains its own
guarded read. Admin has no foreign model/query or reverse backend dependency.

The Users declaration retains the native profile host and Permissions contribution
and adds directory selection, every native lifecycle intent/deadline/lock, creation,
invitation rotation/invalidation, primary and secondary email workflows, sessions,
trust/security resets, eligibility, policy exceptions and preference reset. Source
commands bind explicit owning public APIs, typed allowlisted inputs and original
proof actions/revisions. Stronger actions retain single-use both-factor proof;
meaningful writes retain native transaction/Audit/outbox/refusal behavior. These
independent operations cannot join profile/access Save. Self-service, recipient/
delivery routing, operator/bootstrap and reserved identity/grant APIs remain in
their original audiences.

Authentication policy sections derive typed controls from owning settings under
existing declaration bounds. Each patch previews differences/consequences and uses
the exact global settings revision/generation and native policy publication runner.
Immutable history/restore has a separate reviewed/confirmed flow; native succeeded
maps to committed, pending/recovery retains operation identity. Selected/all bulk
session revocation preserves native Permissions selection, bounds, fingerprints,
proof and confirmation. There is no second policy, delivery, trust or session engine.

Auth's manifest-bound Users and section components reuse Admin's shared form,
Table, controlled fields, independent proof/confirmation and reconciliation. Selecting
a directory subject overrides initial descriptor record IDs, protects dirty drafts
and clears on account/page ownership changes. Optional independent workflow
failures stay local; required content and every atomic participant still block Save.
Read-lane clear cancels obsolete
collection responses and forgets retained queries. Public Auth defaults and Admin
section dispatch/failure-boundary composition preserve App-owned replacement with
Runa-owned security. No sample business scaffold or new dependency was introduced.

Author real API cases for directory/cursor/login/deleted/address disclosure,
every lifecycle intent and stale version, create/invite/secondary/preference
protocols, stronger-proof refusal/success/reuse, policy review/publication/restore/
history, session/trust scoping, bulk fingerprint/selection and read-only denial.
Frontend scenarios cover shared subject selection, late read cancellation,
collection presentation and public defaults; browser scenarios cover actual built
Auth components with English, responsive light/dark and dirty navigation.
Final source review also corrected cleared optional naming/login text to native
null in creation/profile adapters. Its supported single-worker backend regression
passed: 1 test / 10 assertions, 5.64 seconds, native exit 0 / cleanup 0. Complete area suites,
rendered acceptance, concurrency, static analysis, security and native
installation/update verification remain point 12.

Necessary focused diagnostics resolved declaration bounds, native publication
status/proof handling and shared subject selection:

- Readonly declaration construction: Users 13 sections, policy 4, bulk 1,
  17 administrative bindings; existing limits preserved without changing schemas.
- Supported backend --parallel --processes=1, directory/profile disclosure case:
  1 test / 38 assertions PASS, 17.70 seconds, native exit 0 / cleanup 0. After replacing synthetic nested admission with the
  current owning OperationScope context for optional field grants, the same case
  passed again: 38 assertions, 16.75 seconds, exit 0 / cleanup 0.
- Same supported backend mode, native policy publish/restore case:
  1 test / 23 assertions PASS, 29.60 seconds, native exit 0 / cleanup 0.
- Supported frontend subject-selection regression: 1 test PASS, 4.10 seconds,
  exit 0 / cleanup 0. Public Auth Users composition/selection: 1 test PASS,
  4.20 seconds, exit 0 / cleanup 0. The optional-independent-failure isolation
  regression also passed: 1 test, 4.16 seconds, exit 0 / cleanup 0. The runner emitted its generated Vite
  module-runner dynamic-import diagnostic; no application failure was reported.

Source review synchronized Auth administration/README/profile/changelog, Admin
status/declarations/operations/frontend/changelog, public API/components and test
inventories. Canonical exact administrative contracts now live in Auth's guide;
Admin operations links there rather than duplicating the read specification.
Owner frontend pattern reference and customer frontend skill route to that same
public guide. Root owner/customer AGENTS and backend skills already require owning
guarded APIs, native Audit/proof, App-only replacement and final-only broad checks;
no new always-loaded rule or change in their authority is needed.

Auth remains the unreleased 2.0.0 candidate, with additive Administration API 1.0.0
and unchanged AuthProfiles 2.0.0/other API versions. The existing native_transactions
forward edge already carries this unreleased source; no version-only history,
migration, runtime configuration, storage or new service is needed. Admin remains
unreleased 0.1.0. Platform VERSION and release metadata are unchanged.

Grounding: knowledge current at entry (201 documents / 9,879 chunks); bounded
current retrieval was verified against the owning Auth/Admin guides and source.
Backend/Laravel/security/PostgreSQL/frontend/Vue/Tailwind guidance was applied;
Boost supplied installed Laravel 13 query/validation documentation. Mandatory
hooks and staged incremental knowledge freshness apply to this point's commit.
Supported runners displayed and removed only disposable runa_test_test_1 after
each backend run. No Docker resources were created/removed; active services,
base/development data, Qdrant/model caches, backups and reports were preserved.

## Point 8 completion — Permissions administration and Audit viewer — 2026-10-30

Permissions owns source declarations and explicit guarded adapters for roles,
presets, source permission dictionaries, ordinary assignments, copy, add/replace,
from-account preset snapshots and bounded effective rights. Native review effects,
versions, subject selection and availability remain authoritative; role-derived
rights are not flattened into direct grants. Source/system definitions are readonly
in the defaults and refuse through owning APIs. Protected authority and force-delete
use dedicated independent sections/capabilities/proofs/confirmation; force-delete
managers do not require ordinary access-write authority. Self-escalation and the
last-eligible-superadministrator invariant remain in the native owner. All writes
retain native default-connection transactions, consumed proof and required Audit.
The existing profile/access aggregate contribution stays separate and unchanged.

Audit supplies its own read-only history declaration/adapter and English default
viewer over AuditReads 1.0.0. Cursor/filter summaries omit payload/entity trees and
invent no totals; explicit detail projects persisted redacted values and immutable
historical actor labels. Complete entity-reference filters and encoded links open
that entity's Audit history, without guessing a foreign record route or granting
access to its current state. Native large detail payloads retain the existing record bound through explicit
aligned JSON fragments and immutable-event continuation cursors. Missing events/current read authority fail safely;
there are no edit/delete/undo commands or foreign persistence/identity reads.
Optional current Auth labels are omitted rather than replacing historical labels.

Public AdminRecordBrowser composes the existing cursor table, section failure
boundaries and shared form subject selection/dirty guards. Public Permissions and
Audit facades expose typed page/section defaults without copying session, proof or
write protocols into App presentation. Effective permissions use their native code
cursor and retain the selected account, without fabricated public UUIDs. No new
package, runtime service, configuration or database migration is introduced.

Audit advances its independent candidate from 1.0.5 to 1.1.0 for additive viewer
functionality, with no-transform admin_history forward preparation and target
platform >=0.7.0 <0.8.0. Existing public Audit APIs remain 1.0.0. Permissions remains
the unreleased 2.0.0 candidate carried by native_transactions, Admin unreleased
0.1.0; no version-only edge is added to either. Platform VERSION, feature/0.7.0 and
published predecessor history remain unchanged.

Author API regressions for custom definitions/stale reviews, ordinary access/copy/
preset effects and protected reference exclusion, dedicated authority/self-escalation/
last administrator, force-delete confirmation and proof/Audit rollback, readonly
source dictionaries, Audit summary/detail redaction, filters/cursors and grant loss.
Author frontend regressions for source readonly rendering, dirty definition selection,
escaped immutable detail/entity links, URL filter initialization and selected-account
cursor fencing. Built-module E2E scenarios cover desktop/tablet/mobile light/dark;
complete rendered, concurrency, static/type/build/security and native installation/
update acceptance remain point 12. No broad acceptance or release PASS is claimed.

Necessary focused supported diagnostics resolved the actual adapter boundaries:

- Native declaration construction: four Permissions pages, six bindings and one
  Audit history page/binding fit the current schema/bounds without changes.
- Supported backend --parallel --processes=1 custom definition diagnostic: PASS,
  native exit 0 / cleanup 0, including required role Audit/proof rollback and
  explicit retry, version staleness and deletion. Native runner completion was
  confirmed from its supported result report; no unobserved assertion count is claimed.
- Audit summary/detail/privacy diagnostic: final 1 test / 19 assertions PASS,
  12.26 seconds, native exit 0 / cleanup 0. Revoked page authority correctly returns
  safe 404 rather than disclosing the page; the test follows that existing contract.
- Dedicated force-delete/Audit rollback diagnostic: 1 test / 39 assertions PASS,
  29.21 seconds, native exit 0 / cleanup 0. Exact force-delete authority suffices
  without ordinary access-write grants; Audit failure restores pivots and proof.
- Ordinary add/replace/copy/preset and protected-authority diagnostics: final
  2 tests / 77 assertions PASS, 63.75 seconds, native exit 0 / cleanup 0. Native
  protected-reference validation remains 422; the owner preserves last authority.
- Supported frontend selected Permissions/Audit interaction diagnostics initially
  passed 5 scenarios in 4.47 seconds, exit 0 / cleanup 0. Corrected shared Table query
  composition and a typed readonly fixture; no application Vue warnings remain.
  The generated Vite module-runner dynamic-import warning remains tooling output.
- Native large Audit detail continuation diagnostic: 1 test / 28 assertions PASS,
  18.44 seconds, native exit 0 / cleanup 0. Every part stays below 64 KiB and
  complete changes/context reconstruct the persisted trees; PostgreSQL JSONB map
  ordering is compared semantically. Invalid continuation refuses.
- Final frontend interaction run including immutable-event payload continuation:
  6 scenarios PASS, 4.24 seconds, exit 0 / cleanup 0; no application warning.

Grounding: entry knowledge was current (202 documents / 9,914 chunks); bounded
current-intent retrieval was verified against owning Permissions/Audit/Admin sources.
Backend/Laravel/security/frontend/Vue/Tailwind guidance and PostgreSQL ownership
rules were applied. Boost supplied installed Laravel 13 allowed-array validation
documentation. Current module/access/authority/viewer, Admin status/declarations/
operations/frontend, public boundary/components, changelogs and test inventories
are synchronized. Owner frontend pattern references and customer frontend skill
link the new owning guides. Root owner/customer AGENTS and backend skills already
require guarded owning APIs, native proofs/Audit and App-only presentation; neither
needs another always-loaded rule, duplicated procedure or expanded authority.

Stage and incrementally synchronize current knowledge for the completion commit;
mandatory formatting/quick-lint/secret/freshness hooks remain required. Supported
runners displayed and removed only disposable runa_test_test_1. No Docker resources
were created or removed. Active services, base/development databases, durable data,
Qdrant/model caches, backups and verification evidence were preserved. Point 8 is
complete; point 9 is next. The roadmap and release remain active/open.

## Point 9 completion — Operational dashboard and native controls — 2026-10-30

Delivered AdminOperations 1.0.0 with named lazy GET/POST operational transport,
fixed explicit views/commands, entry/password proof and exact per-operation grants.
Cached diagnostics project platform/application/PHP versions, registry filters,
source/installed versions, dependencies, safe reasons and observation timestamps;
reads execute no diagnosis. Explicit diagnosis runs the existing independent engine.
Optional enable/disable/unregister use actor-bound, ten-minute encrypted review of
the complete native preview and fresh source/generation/plan/work checks. Status,
resume/cancel/compensation and settings recovery retain native outcomes and fences.
No shell/operator/install/update/maintenance-marker endpoint or availability
exception is added; maintenance and failed Core recovery remain CLI-only.

Health/integration configuration, lifecycle reservation/durable phases/publication/
compensation and first cooperative work cancellation now participate in required
Audit at their actual default PostgreSQL write boundary. Fixed Core wiring supplies
the reserved OperationalParticipant; installed Audit/source failures fail closed.
Framework-only isolated engines retain their no-Audit default. No configuration,
secret, runner token or work payload enters this operational history. Settings use
the existing publication/restore participant. Auth policy settings remain readonly
here and use their dedicated reviewed owning workflow. Cooperative cancellation
checks current declared capability under registry/work locks, is idempotent and
never claims terminal completion. Synchronous Admin configuration re-admits the
resulting registry inside its locked entry transaction after intentional generation
publication; independent native runners execute outside that transaction.
Request-scoped native authorization is repeated within reservation/publication
participant transactions and always unwinds. Revocation during a lifecycle hook
refuses publication and retains native reconciliation state.

Default English dashboard composes persisted module health, independently isolated
module widgets, source-typed settings/secret set-state, health overrides/integration
maps, explicit lifecycle review and retained-operation/work controls. Public
AdminOperations/AdminOperationalEditor/AdminOperationResult and useAdminOperations
provide partial/full App presentation replacement without copied session/proof logic.
One write lane fences account changes/late responses, freezes confirmed input,
retains dirty drafts on failure/conflict and never replays writes. Successful
publication reconciles reads; pending/recovery outcomes remain visible. Responsive
light/dark browser scenarios are authored for final acceptance.

### Grounding and focused uncertainty checks

Knowledge was current at entry: 204 documents / 9,956 chunks, no missing/orphan
chunks. Bounded current-intent retrieval was checked against original Admin
operation contracts, framework health/lifecycle/settings/integration/work engines,
Audit participation/storage, guarded API/entry/HTTP and frontend controller sources.
Backend/Laravel/security/PostgreSQL/frontend/Vue/Tailwind guidance was applied;
Boost supplied installed Laravel 13 transaction/lock and allowed-array documentation.

Necessary supported focused diagnostics received these final native results:

- Operational diagnostics/grants/redaction/manual maintenance: 1 test,
  27 assertions PASS, 10.13 seconds, native exit 0 / cleanup 0.
- Synchronous configuration/generation and actual Audit rollback, integration and
  sensitive settings publication: 1 test, 26 assertions PASS, 9.74 seconds,
  native exit 0 / cleanup 0. Initial failure exposed omitted participant wiring
  in explicit engine factories; corrected in the owning service provider.
- Optional reviewed transition, stale generation, reservation Audit failure and
  native publication/status: 1 test, 18 assertions PASS, 8.67 seconds,
  native exit 0 / cleanup 0.
- Cooperative work cancellation/privacy/idempotency and Audit rollback:
  1 test, 17 assertions PASS, 6.20 seconds, native exit 0 / cleanup 0.
  The fixture refreshes a work singleton retained from pre-candidate test bootstrap;
  runtime engine admission remains unchanged.
- Waiting/resume/publication Audit failure/native compensation: 1 test,
  14 assertions PASS, 6.03 seconds, native exit 0 / cleanup 0.
- Auth reviewed-workflow isolation and independent read grants: 1 test,
  15 assertions PASS, 5.35 seconds, native exit 0 / cleanup 0. Controller-local
  refusal projection preserves Admin reason/scope despite Laravel pipeline rendering.
- After request-scoped native authorization wiring, the selected synchronous
  configuration and Optional lifecycle diagnostics passed together: 2 tests /
  44 assertions, 19.27 seconds, native exit 0 / cleanup 0.
- Mid-hook grant revocation, refused native publication, conservative resume and
  request-scope unwind: 1 test / 12 assertions PASS, 7.28 seconds, native exit 0 /
  cleanup 0.
- Selected operational frontend diagnostics: 4 scenarios PASS, 4.28 seconds,
  native exit 0 / cleanup 0. Corrected shared HTTP prefix/query composition and
  awaited real browser response completion. No application Vue warning remains;
  the existing Vite module-runner dynamic-import warning is tooling output.
- Public consumer/reserved-boundary fixture diagnostic: 1 test PASS, 0.05 seconds,
  native exit 0 / cleanup 0.

Additional authored actor/expiry/tampered/source/revocation cases and responsive
browser acceptance await point 12, alongside complete backend/frontend/static/
security/native installation/update/concurrency checks. No broad suite, rendered
acceptance, deployment or release PASS is claimed. One attempted concurrent runner
was refused by the checkout suite lock before execution; the selected frontend
run subsequently completed normally.

### Versions, documentation, instruction products and resources

Admin remains its unreleased creation candidate 0.1.0; Audit remains the unreleased
1.1.0 candidate with its existing admin_history source edge. These additive guarded
Admin and reserved Audit integration additions require no extra version-only edge,
public Audit API change or data migration. Auth/Permissions remain 2.0.0. Platform
VERSION, feature/0.7.0, published tags and predecessor records are unchanged.

Synchronized Admin delivery/operations/frontend contracts, public boundary/component
catalogue, framework operational Audit guidance, module changelogs, consumer and
central test inventories. Owner frontend layout reference and separately authored
customer frontend skill route to the canonical operational composition guide.
Root owner/customer AGENTS and backend/domain skill entrypoints need no added rule:
existing guarded ownership, atomic Audit, App presentation and final-only verification
requirements cover this capability; no authority/security/dependency policy changes.

Stage and incrementally refresh knowledge before the mandatory completion hooks.
Supported backend runners displayed and removed only runa_test_test_1; fixture-owned
Optional sources, work/queue and transition rows were removed. No task Docker
containers, networks, image tags or volumes were created or removed. Active services,
base/development databases, Qdrant/model caches, backups and all evidence remain.
Point 9 is complete; point 10 is next. Roadmap/release remain active/open.

## Point 10 completion — Application settings and runtime presentation — 2026-10-31

Delivered the platform-owned `shared.application_settings` migration and protected
inventory, independent of module settings/Admin installation. Explicit forward
platform installation/update remains the only initialization path; boot, reopen
and HTTP never create storage. The technical singleton uses bigint identity,
UNIQUE/CHECK singleton, positive revision, JSONB-object values and UTC timestamps.
App declares safe non-secret scalar defaults and deployed static logo assets through
frozen, source-verified ApplicationSettingProvider; the minimal scaffold adds no
business settings or upload system. New public Contracts/Settings values and
container-owned Support/Settings adapters are classified in the owning operations
contract and public-boundary catalogue. Native application wipes preserve the store.

Protected reads and writes require a trusted current principal, current native
grants, registry/authority locks and safe exact types. Writes check the locked
revision and both read/write grants per key, then record one explicit redacted
application.settings.changed Audit event in the same default PostgreSQL transaction.
The fixed platform event origin is runa.audit; only key names/revision enter history,
no selected values or artificial public UUID. Exact Audit admission precedes root
locks. Mandatory Audit failure rolls back values/revision. Removed App definitions
and asset selections remain persisted but are not disclosed as current values.

Public no-store `/api/presentation` and Admin bootstrap share only name/logo/revision.
Canonical identity, versions, compatibility, environment, technical mail/CSP policy
and Runa's code logo remain source-owned. The browser shell refreshes presentation
without restart after settings writes/session changes and focus/visibility/history,
with per-application cancellation/late-response fencing and disposal. Mail and normal
server errors consume validated runtime branding with source fallback on storage
failure; approved PNG/JPEG assets pass the existing local CID policy. Native error
shells add an escaped logo variant. Emergency/static output uses only build-time
source defaults/logo, with no database read or baked runtime override.

The default English application editor and public useAdminApplicationSettings share
the existing Admin operation/session/dirty owner and single write lane. Per-field
read/write metadata drives typed defaults and source-reset controls. Refresh retains
dirty drafts, merges disjoint reads and requires explicit overlap resolution with
the current value visible. Failed/stale writes are not replayed; account/authority
changes clear and fence drafts. App can replace the editor through the public
presentation provider, default slot or headless controller without copied security.

### Grounding, authored coverage and focused results

Entry knowledge was current: 204 documents / 9,993 chunks, no missing/orphan chunks.
Bounded current-intent retrieval was checked against the original application
settings/storage/presentation contracts, native admission/authority/Audit, identity,
mail/error builds, database ownership and current operational controllers. Applied
backend/Laravel/security/PostgreSQL/frontend/Vue/Tailwind guidance. Boost supplied
installed Laravel 13 transaction/pessimistic-lock and allowed-array documentation.

Focused supported diagnostics resolved the new transaction and source boundaries:

- Application settings persistence/grants/revisions, canonical identity, native
  mail/error projection, actual Audit failure rollback and grant loss: 1 test /
  42 assertions PASS, 15.09 seconds, native exit 0 / cleanup 0.
- Missing storage fallback/fail-closed behavior and App-owned frozen source/logo
  catalogue, hidden defaults/options and removed-source fallback: 2 tests /
  32 assertions PASS, 8.58 seconds, native exit 0 / cleanup 0.
- Selected settings reconciliation/readonly/reset/account fencing and shared-shell
  public presentation hydration/publication: final 5 scenarios PASS, 4.34 seconds,
  native exit 0 / cleanup 0. Initial two fixture failures required awaiting actual
  browser response/render completion. No application Vue warning; the existing
  Vite module-runner dynamic-import warning remains tooling output.
- Public consumer/internal-engine boundary diagnostic: 1 test PASS, 0.04 seconds.
  Container-owned public adapter signature diagnostic: 1 test PASS, 0.06 seconds.
  Both used supported test-python, native exit 0 / cleanup 0.

Authored strict source URL/scalar cases, actual database inventory/identity and
application-wipe preservation, native fresh-install/store and updater/recovery
value/revision preservation, plus responsive light/dark default editor scenarios.
Complete backend/frontend/tooling, static/type/build/CSP/rendered/native deployment
and installation/update acceptance remain point 12. No broad verification or release
PASS is claimed. Private Settings is included in the existing final static scope;
public constructor exceptions remain limited to the named container-owned adapters.

### Versions, guidance, instruction products and resources

Admin remains its unreleased 0.1.0 creation candidate. This platform-owned additive
store/adapter capability changes no existing guarded module API or module data;
Auth/Permissions stay 2.0.0 and Audit stays its unreleased 1.1.0 candidate. No
version-only module forward edge, dependency or runtime configuration change is
introduced. Platform VERSION, feature/0.7.0 and published history are unchanged.

Current Admin contracts/status/frontend, database/public boundary, browser/mail/
error customization and component references, module changelog and central test
inventory are synchronized. Owner frontend references and separately authored
customer frontend guidance link the canonical replacement/settings contracts.
Root owner/customer AGENTS and backend/domain skills need no new always-loaded
rule: existing protected-source, native-grant/transaction/Audit, minimal-scaffold
and public-composition requirements cover the delivered capability. Customer
registration examples operate only in App and authorize no protected edits.

Stage and incrementally synchronize knowledge before mandatory completion hooks.
Supported backend runners displayed and removed only disposable runa_test_test_1;
fixture source/assets were removed and singleton snapshots restored. No task Docker
containers, networks, image tags or volumes were created or removed. Development/
base databases, active services, Qdrant/model caches, backups and evidence remain.
Point 10 is complete; point 11 is next. Roadmap/release remain active/open.

Point-10 completion review additionally identified the shared operational 503
classification clearing a settings draft after a confirmed storage/Audit rollback.
Fixed only those exact operation-scoped settings reasons to retain the current
editor/draft and display local failure; native authentication/grant/Core/maintenance
refusals still close the protected shell. A focused test using the real useAdmin
session controller confirms preserved drafts, explicit retry only and Core-loss
clearing: 1 scenario PASS, 4.19 seconds, native exit 0 / cleanup 0. The canonical
frontend contract is synchronized. This is the same point's failure behavior;
no gate, authority or architecture policy changes. Completion commits use mandatory
formatting/lint/secret/freshness hooks; the first implementation commit is 860c9758.

## Point 11 completion — Generator and consumer integration — 2026-10-31

Delivered the development-only opt-in `runa:make:module <module> admin <Name>`
bundle: owning public typed calculation API/guarded proxy/handler, management
page/navigation/entity/record form/independent action/table/widget/owned contribution,
custom section component and immediate module settings. Central module guide/test
and API/management/settings/docs manifest registration join the same reviewed plan.
The runnable example normalizes text without persisting submissions; owning
business execution has no Admin dependency. Configuration uses the existing
independent module-settings runner and its grants/revisions/Audit. This example
does not invent a durable entity, a Save mutation or a health observation.

Preserved nonmutating preview, exact per-file SHA-256 consent, source ownership,
customer-to-Runa and production refusal, symlink/path checks and preflight before
writes. Refuse competing settings providers rather than append a second owner or
overwrite validation/recovery; generated key spelling is bounded. Calculations
check the native settings generation/revision, avoiding a value-hash ABA token.
Foreign contributions require an explicit optional host dependency and owning
subject policy; the generated singleton does not pretend to support UUID subjects.

The public [authoring/examples guide](../../../handbook/modules/admin/examples.md)
maps all 18 developer topics to their canonical procedures and supplies executable
independent App page/reader, lazy registration, partial section and full content/
page consumer examples. Private consumer fixtures compose only public Admin/UI
facades and one owning session/page/form controller; paused sessions hide custom
content. Update-review fixtures now explicitly preserve App Admin components,
providers, generated adapters, central module docs/tests and application customizations.
The minimal App stays unchanged except its agreed compatibility range widening
to >=0.2.1 <0.8.0; version remains 0.0.0. Existing customer ranges are not rewritten.
Admin remains development 0.1.0 until verified creation closure; no module API,
install/upgrade edge, storage migration, dependency or welcome icon change is needed
for this authoring integration. Its current catalogue/defaults remain in place.

### Grounding and focused checks

Knowledge status was current (204 documents / 10,023 chunks, no missing/orphan
chunks). Bounded current-intent retrieval located Admin frontend/native ownership;
original public/declaration/composition/source-placement/module/security guides,
generator/stubs/command, source/runtime fixture and customer update-review sources
were opened. Relevant backend/Laravel/security/frontend/Vue/Tailwind skills and
component/form/page references were loaded. Boost supplied Laravel 13.37.0 and
version-aware Artisan/validation documentation. No broad implementation-stage
suite, static analysis, build, native installation/update or scanner was run.

Small supported checks resolved actual generated class/metadata/import/consent
compatibility, executable public PHP examples, controller composition and whether
the updater classifies the added nested customer paths as preserved:

- `python3 tools/maintainer/test-backend ../tests/backend/runa/Feature/ModuleDevelopmentTest.php --filter='opt-in Admin|oversized Admin'`: **2 passed**, 46 assertions, exit/cleanup 0.
- `python3 tools/maintainer/test-backend ../tests/backend/runa/Feature/ModuleDevelopmentTest.php --filter='documented independent'`: **1 passed**, 8 assertions, exit/cleanup 0.
- `python3 tools/maintainer/test-frontend --filter='customer section with the public form owner'`: **2 passed**, exit/cleanup 0. These mounted consumers do not replace final responsive/theme/Core/browser acceptance.
- `python3 tools/maintainer/test-python --scope=contracts --path=contracts/test_update.py --filter=frontend_and_error_customizations_remain_application_owned_across_review`: **1 passed**, exit/cleanup 0; native update acceptance remains point 12.

Initial focused runs found fixture/object-shape and readonly/fragment-marker
mistakes; these were corrected and the same selected cases passed. Native reports
and browser failure evidence remain under `.runtime/python/reports` and
`.runtime/artifacts/playwright`; Vite's generated runner emitted its existing
dynamic-import analysis warning. All excluded checks remain unexecuted, not PASS.

### Documentation and instruction products

Synchronized Admin delivery status/API method list, module authoring, command
catalogue, changelog and supplied App compatibility. Owner backend skill and
frontend page reference plus separately authored customer backend/frontend skills
route to the canonical examples without duplicating procedures. Root and customer
AGENTS, Laravel/security/database/Vue/Tailwind entrypoints need no change: existing
ownership, grants, exact overwrite, session and public-facade rules already govern
this capability; no new operating authority or exception is introduced.

Mandatory commit hooks/fresh knowledge remain required. No task-created Docker
containers/networks/images/volumes need removal. Supported test cleanup removed no
worker databases and preserved the base/development databases, active services,
Qdrant/model caches and all verification evidence. Point 11 is complete; point 12
is next. The roadmap/release remain active/open; no final gate or release is claimed.

## Point 11a — Manual acceptance preparation — 2026-10-31

The owner approved a runnable 0.7.0 candidate and the penultimate manual frontend
acceptance rule. Root/customer AGENTS, owner/public roadmap workflows and the
public template now route to that rule. Repairs go before manual acceptance;
owner confirmation is required, and automated final verification/release stays last.
Asset builds needed for the manual runtime are explicitly operational preparation;
they do not waive final automated build/type/security/browser/native gates.

VERSION and the protected runtime manifest now both identify 0.7.0. The active
roadmap records the explicit approval/date/reason and retains the released 0.6.2
baseline. The maintainer README identifies the actual candidate and active roadmap;
the published changelog is unchanged. Release validation accepts only the recorded
prepared version equal to proposed/computed target with a valid approval date and
reason; it still refuses arbitrary drift, missing manual completion, existing tags
and wrong target arithmetic. Finalization must not increment the prepared target
to 0.8.0. The focused release contracts selected `owner_approved_runtime` and
`active_version_and_branch_drift`: 2 PASS, native exit/cleanup 0.

The required `pnpm --dir src run build` initially exposed strict TS errors in
Admin layout optional-prop/slot forwarding and collection injection, Audit filter
binding, recursive field fixture refs, mock discriminants and Node-only E2E file
imports. Corrected owning types/forwarding and controlled filter inputs; fixtures
use shallow typed state and explicit results, and browser acceptance reads the
actual served component manifest. No new dependency, assertion suppression or
weakened compiler setting was added. The complete supported asset build (including
vue-tsc and normal/static error rendering) then passed. Admin remains development
0.1.0 and Audit retains its current 1.1.0 candidate; these compilation corrections
belong to their existing unreleased scope, without extra version-only edges.

Read-only local diagnostics identified database `runa`, stale Core manifest
registrations/installed versions and no active/reserved business work or transitions.
Plan: forward-create only shared.application_settings; apply retained module
upgrades Audit 1.0.0 → 1.1.0, Auth/Permissions 1.0.0 → 2.0.0 and initialize Admin
0.1.0. No module-domain migration is pending. Local runtime preparation and HTTP
readiness remain pending; point 11a stays open until those results are received.
No final gate or owner manual PASS is claimed.

### Completed local runtime preparation

Implementation/policy/candidate preparation is committed at `6350cbc3`. The
supported build was repeated after hook formatting to align source-bound component
evidence with the actual committed Vue sources; it passed, including vue-tsc,
application assets and native/static error shells. Output is preserved in
`.runtime/frontend/manual-acceptance-build.log`.

The explicitly documented forward preparation primitive was invoked against the
local development database `runa` with the exact source commit:
`php src/runa/database/prepare.php 6350cbc3a5c46672716ff6d58b2c64d6392591aa`.
Native maintenance was enabled for this task; queue
workers pause under that maintenance, operational module admission remains fenced,
and database diagnostics confirmed zero reserved jobs, active business work or
active transitions before execution. No global service or data cleanup was used.
The primitive forward-created shared.application_settings, recorded the fixed
required seed completion and applied all planned retained Core upgrades plus Admin
initialization. Native exit 0; operation evidence is preserved at
`.runtime/dev/manual-acceptance/forward-6350cbc3.log` and the native database ledgers.

Only the task-owned native maintenance was then removed and a native queue restart
signal published. Effective maintenance reports no reasons. All Core modules are
registered/enabled with source matching installed data: Audit 1.1.0,
Auth/Permissions 2.0.0 and Admin 0.1.0. The original 15 user accounts remain present;
no database reset, fixture replacement, credential change, manual permission bypass
or production operation was performed. The base testing database and its data were
not changed by this local operation.

Readiness requests against the actual development Nginx/PHP-FPM environment passed:
`/`, `/admin`, `/api/presentation`, `/api/runa/auth/entry-options` and `/api/ready`
return HTTP 200. Unauthenticated `/api/admin/entry` still returns 401 as required;
the owner uses the existing account/password and native entry proof. Local manual
URL: `http://localhost:8080/`, Admin: `http://localhost:8080/admin`. Current assets
are built; no HMR process is required for this URL. These observations establish
handover readiness, not authenticated manual acceptance or final quality PASS.

Owner/customer guidance and the shared template are synchronized. Existing domain
skills already route roadmap timing to the canonical shared workflow, so they need
no duplicated manual-acceptance procedure. No Docker containers, networks, volumes
or image tags were created/removed; active services, development/test data, model/
Qdrant caches and all reports remain. Point 11a is complete. Point 11b is pending
the owner's manual frontend tests and explicit acceptance; point 12 stays last/open.

## Point 11a1 — Polish Admin label — 2026-10-31

Owner-requested manual-review correction: the Polish account-menu entry and Admin
module catalogue name now read `Panel administratora`. English Admin presentation,
routes, grants and session/entry guards are unchanged. Auth 2.0.0 and Admin 0.1.0
remain their existing unreleased candidates; no new version-only edge is needed.
Their changelogs are synchronized. No reusable instruction or public signature
changes, so owner/customer AGENTS and skills need no additional rule.

Explicit `runa:modules:sync runa` refreshed the changed manifest without changing
installed data or account assignments (registry generation 181). The supported
manual-runtime asset build passed, including its type/error-shell prerequisites;
output remains in `.runtime/frontend/manual-acceptance-label-build.log`. Actual
home/Admin document requests return HTTP 200. No extra tests were added for this
label-only correction, no broad quality gate was run and no Docker/data cleanup
was needed. Owner manual acceptance in 11b remains open.

## Point 11a2 — Complete bilingual panel naming — 2026-10-31

The owner clarified that a requested UI rename applies to both supported languages.
The earlier Polish-only change was incomplete. The menu and module catalogue now
use `Panel administratora` / `Administrator panel`; the English dashboard heading,
navigation, loading/editor accessibility labels and entry/unavailable copy use the
same panel name. Existing mounted/E2E consumer expectations, public example and
current candidate changelogs are synchronized. Technical identities/routes, native
grants/security and existing unreleased module versions are unchanged. Existing
locale/presentation guidance in both instruction products already applies; no new
operating rule or public contract is required.

Explicit module synchronization refreshed the manifest at generation 182 without
data installation or permission changes. The supported asset build with type/error
shell prerequisites passed; output is preserved in
`.runtime/frontend/manual-acceptance-bilingual-label-build.log`. The actual Admin
document returns HTTP 200. No new tests are needed for the label correction;
existing browser expectations are updated for final acceptance. No full quality
gate or Docker/data cleanup was run. Manual owner acceptance in 11b remains open.

### Point 11a3 — Auth proof presentation and Admin admission repair

Owner requested the original Auth roadmap review and immediate repair of all
confirmed presentation gaps found in this bounded review. Historical RM-2026-011
remains unchanged. Its shared public step-up requirement explicitly includes
password, factor or both and native identity/session/action binding. Its entry UI
point requires MinimalLayout; its general frontend decision requires usable,
replaceable defaults. A standalone operation-proof page was missing from the
combination of those delivered capabilities; the historical entry-screen list did
not explicitly name a new step-up URL.

| Requirement/source inspected                                                     | Current presentation coverage and finding                                                                                                                                                                                                                   |
| -------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| RM-2026-011 shared step-up contract; Auth StepUp and useOperationProof           | Native backend proof already existed. Admin bypassed the shared frontend controller with its own credential request/form. Replaced with the public Auth controller/page.                                                                                    |
| Entry/restricted flows; entry.ts, AuthEntryFlow/useAuthEntry                     | Login, registration, invitations, reset/recovery, verification/reverification, forced password change, 2FA/enrollment/recovery have source-backed screen branches. No other missing default screen identified in this group.                                |
| Account flows; AuthAccountDefault/Editor/Security/Preferences and useAuthAccount | Details/password, secondary email, avatar, factors/recovery, sessions/trusted devices, preferences and history have default sections/dialogs. Account proof already has PL/EN controls. No missing default identified in this group.                        |
| General usable defaults/replacement and MinimalLayout                            | Added AuthOperationProofPage, shared private fields and full-page/default-slot/events/layout replacement contracts. Admin admission previously rendered proof inside ApplicationLayout; now uses the shared shell admission slot before application chrome. |
| PL/EN shared proof controls/errors                                               | Existing inline operation proof was English-only. Inline and page now share PL/EN labels and safe feedback; Admin retains its scoped English locale.                                                                                                        |
| Cancellation source review                                                       | A cancelled factor-options read could restore methods for the same unchanged identity after reset. Added the aborted-signal fence and a delayed-response regression.                                                                                        |

This is source coverage for the listed public frontend flows, not a full Auth
functional/security audit or acceptance PASS. Trusted operator/CLI-only operations
are not missing web views. Existing owning backend admission/step-up policy stays
in force; no new route, session owner, proof engine, grant or business write was
introduced. The page emits successful confirmation; Admin then reads bootstrap.
Failed proof stays retryable and clears credentials, cancellation returns to /app,
and a failed initial session read retains an explicit Retry action.

Public contracts/current-state guides and component/layout/customization references
are synchronized. Owner frontend reference and customer frontend guidance both
point to the canonical Auth composition contract. Root owner/customer contracts
already require supplied defaults, supported replacements and one session owner;
no new constitution or root-rule duplication is needed. Auth 2.0.0 and Admin 0.1.0
remain their existing unreleased candidates; the public controller/presentation
changes are included there, with no manifest-only version edge or migration.

Necessary focused checks: `python3 tools/maintainer/test-frontend
--filter='Admin entry proof|Auth operation proof'`: **8 passed**, native exit 0,
cleanup 0. These cover native fixed action/single submission, MinimalLayout before
admission and wrong-password retry, PL defaults/local validation/secret clearing,
password plus eligible factor/email and combined proof, aborted-read fencing,
full-page replacement/controller-slot forwarding, and failed-session retry.
The initial assertion counted MinimalLayout's ordinary preference-session read as
a proof request; corrected it to assert the native proof endpoint and mutation
count specifically. No extra session owner was introduced in Admin composition.

Current assets are prepared with `pnpm --dir src run build` for owner frontend
acceptance (includes type compilation, Vite and native error shells). Real local
`/admin` and `/auth/login` return HTTP 200. This does not claim authenticated owner
acceptance. No full suite, final quality gate, database reset or Docker cleanup was
run. Development data/services, knowledge caches and test/build reports remain.
Manual point 11b and final point 12 remain open.

### Point 11a4 — Polish/English Admin and shared language preference

On 2026-10-31 the owner explicitly replaced the English-only Admin rule with
Polish-default/English-secondary presentation. This amendment supersedes the
historical English scope recorded by earlier completed points. Owner/customer
AGENTS, frontend skill references, localization/architecture/configuration guides,
active architecture specification and current Admin/Core presentation contracts
are synchronized; closed roadmaps and dated historical evidence remain unchanged.

Removed fixed English scopes from AdminApplication/AdminLayout/entry proof and
shared form/table/selection/notification/confirmation defaults. Admin and its
Auth/Permissions/Audit screens now consume the existing account-aware application
language preference. Reviewed source English UI copy has a Polish presentation
catalogue; explicit source PL/EN metadata stays authoritative. Technical keys,
UUIDs, requests, business values, backend diagnostics and Audit records retain
their original identities/content. No separate language/session/authorization
engine, dependency, database migration or runtime setting was introduced.

Public AdminText, AdminMessages, useAdminLocale/adminText and
AdminApplication.messages allow ordinary App/module presentation composition.
Framework page/section/command/widget titles and optional groups accept bounded
English strings or exact PL/EN maps. Field/owner projections retain both locales;
Core field names have actual owning Polish values, including Auth policy names.
English source-string titles use the shipped Polish catalogue; App-specific
strings use explicit maps or the App message extension. Locale changes update
headings/navigation/shared controls and errors without refetching protected data,
resetting query/identity owners or remounting dirty forms. Source plain strings
remain valid for intentionally untranslated labels; no obsolete adapter was added.

Focused switching checks exposed and repaired two prerequisites: guest preference
writes/resets did not notify other mounted display subscribers, and Vue inferred
Boolean before String for AdminJson props, coercing an empty text draft to true.
Guest writes/resets now notify through the existing adapter; the public JSON union
places String first to preserve empty text while retaining nullable booleans.
A constant leave-confirmation message avoids interpolating localized metadata
objects into an English string. Confirmation titles retain their source text/map
until presentation, so an open confirmation can follow a language change.

Necessary focused verification (not final acceptance):

- `python3 tools/maintainer/test-backend ../tests/backend/runa/modules/admin/ProjectionTest.php --filter='bilingual management metadata'`: **1 PASS, 12 assertions**, exit/cleanup 0. Covers source text/map retention, invalid maps and real Auth/Permissions field names plus field projection.
- `python3 tools/maintainer/test-backend ../tests/backend/runa/Feature/ManagementPagesTest.php --filter='discovers only available module declarations'`: **1 PASS, 19 assertions**, exit/cleanup 0. Confirms bilingual module names survive manifest discovery without constructing owning business handlers.
- `python3 tools/maintainer/test-frontend --filter='Admin bilingual|uses one supplied session|Admin entry proof|Admin fields render nullable booleans'`: **9 PASS**, exit/cleanup 0. Covers Polish defaults, live PL/EN navigation/metadata/validation, retained input DOM/draft/password, one shared session/no extra bootstrap/proof/business write, explicit maps/App overrides, guest write/reset notifications and unchanged nullable booleans.
- Public translation regression rerun after consolidating dynamic validation copy: **1 PASS**, exit/cleanup 0. Existing English fixtures now explicitly select English rather than depending on the removed forced locale.

Auth 2.0.0, Permissions 2.0.0, Audit 1.1.0 and Admin 0.1.0 remain the existing
unreleased candidates under this active roadmap. Their changelogs include the
localized presentation/metadata changes; no extra version-only upgrade edge is
needed before their first verified closure. Platform remains the owner-approved
prepared 0.7.0 candidate. Generator strings remain valid source declarations;
public authoring/replacement examples describe localized maps and controls.

`pnpm --dir src run build` prepares the current compiled application and native
error assets for owner inspection, including type compilation. Manual owner
acceptance in 11b and full affected-area final verification in 12 remain open.
No full suites, quality release gate, deployment, database reset or manual owner
acceptance is claimed. Supported backend runners reported successful worker
cleanup with no disposable workers to remove; no other Docker resources were
created or removed. Existing development services/data, model/knowledge caches
and verification/build evidence are preserved.

### Point 11a5 — Proof reading focus and error-tone cancellation

Owner manual inspection found the standalone Admin admission heading outlined on
initial render and requested red/error cancellation. The page already focused its
reading heading, but its Tailwind utility did not override the shared focus-visible
rule. Reused the existing AuthEntryFlow/AuthAccountDefault scoped
`.auth-heading:focus` rule. Reading focus and tabindex=-1 remain; interactive
controls keep their keyboard focus indicators. The shared Auth proof Cancel button
now uses the existing danger tone and outline variant, giving both standalone and
inline proof presentation red text/border without new CSS tokens or controls.

The current Auth entry guide/changelog is synchronized. Owner/customer guidance
already covers Auth reading-heading focus, public presentation composition and
shared controls; no new reusable rule or duplicated instruction is needed. Auth
remains the existing unreleased 2.0.0 candidate, with no public signature, native
proof policy, module manifest or version-only upgrade change. Admin remains its
existing unreleased candidate and consumes the same Auth view.

Necessary rendered regression: `python3 tools/maintainer/test-frontend
--filter='Auth operation proof uses Polish defaults'`: **1 PASS**, native exit 0,
cleanup 0. The mounted browser check confirms initial focus remains on the heading,
its computed outline is none, and the existing password-confirmation/cancellation
flow still clears transient credentials. Current assets were rebuilt with
`pnpm --dir src run build` (type compilation, Vite and native errors): PASS.
Real local `/admin` returns HTTP 200. No broad suites/final quality gate,
database changes or Docker resource cleanup were needed. Development data,
services, knowledge/model caches and reports remain. Owner manual point 11b and
final verification/release point 12 remain open.

### Point 11a6 — Stable optional link and pre-admission revalidation

Owner manual testing found the Admin link disappearing/reappearing on dropdown
activity and the password admission page remounting after returning to a tab.
The advertisement watcher returned a new tuple on each session-object update,
so even unchanged activity observations cleared/refetched the link. It now watches
a stable account/session/restriction/validation fingerprint. The known same-session
link remains visible during a fresh optional availability read; denied, malformed
or failed results hide it. Lost/restricted/changed identity hides it immediately,
and request epochs still ignore late responses. Opening the menu itself does not
read protected bootstrap or start another session/activity loop.

A successful Auth session validation advances authorityRevision even when identity
is unchanged. Admin previously used that observation revision as proof-controller
identity and unconditionally cleared the pre-admission view. Entry proof now uses
the current unrestricted account/session identity, while same-session revalidation
reads native bootstrap in the background without replacing that password page.
An unchanged native step_up_required refusal retains the form/input. A real denial,
identity invalidation or successful bootstrap admission clears the transient proof.
Explicit proof still uses the same native action and no write is replayed.
Protected bootstrap/page/read/mutation authority fences remain intact; restriction
values additionally participate in their stamp. This repair does not retain
protected Admin data across authority changes. A matching tuple-watcher issue in
useAdminPage was corrected to individual sources, avoiding descriptor reads caused
only by activity timestamps and their unnecessary downstream reconciliation.

Current Admin/Auth guides and candidate changelogs are synchronized, including
removal of residual current guidance claiming Admin still pins English. Owner and
customer instructions already require one session owner, safe in-place read
reconciliation and cleanup on identity loss; their canonical public references
remain applicable, with no new rule or copied procedure needed. Public controller
signatures and source/runtime versions remain unchanged: Auth 2.0.0 and Admin 0.1.0
are still their existing unreleased candidates; no migration/version-only edge,
dependency, backend authorization or runtime configuration change is introduced.

Necessary focused check: `python3 tools/maintainer/test-frontend
--filter='Admin revalidation|Admin session diagnostic|Admin resource reads coalesce|Admin cursor tables type filters|Admin page refresh'`:
**8 PASS**, native exit 0 / cleanup 0. Coverage includes unchanged activity/menu
reopening, delayed optional reads/denial/late responses, mounted password DOM/input
across repeated validation, real refusal, account-switch erasure, successful
admission cleanup, stable descriptors, and existing strict protected-read/cursor
fences. Native browser response/DOM settlement is awaited before refusal assertions.
`pnpm --dir src run build` passed type compilation, Vite and native error assets;
current local `/admin` returns HTTP 200. No broad suites/final release gate,
DB reset or Docker cleanup was needed. Existing development services/data,
model/knowledge caches and all verification/build evidence remain. Manual owner
point 11b and final verification/release point 12 remain open.

### Point 11a7 — Agreed loading/readiness repair

The owner approved a common readiness model after observing that Application
content appeared before the avatar. This extends the current Admin manual-acceptance
repair: its shell and Auth dependencies own the observed behavior, with the same
public contract available to future views. It is not a second active roadmap.
Platform candidate 0.7.0 and the feature release decision remain unchanged.
Initial reveal waits for declared essential presentation dependencies; optional
presentation failures settle to usable defaults, while identity/access loss clears
protected presentation immediately. Background reads preserve mounted content,
focus and drafts; writes remain explicit and are never replayed. Heavy tables and
optional widgets own separate readiness regions.

Implemented the public UI-owned useViewReadiness/ViewBoundary contract and
identity-scoped useImageReadiness with download+decode, 2.5-second fallback and
shared shell/dropdown image ownership. Public layout props expose composition
without requiring private injection or copied security logic. Optional profile
and preference snapshots settle within five seconds; initial public branding/logo
settles before root reveal. Subsequent reads keep mounted presentation and focus.

Auth entry/welcome reuse existing session/preference reads instead of adding a
layout session owner; delayed heading mount retains reading focus. The application
shell waits for initial profile/preferences/avatar, retains its loading slot and
clears protected content on account/session invalidation. Admin bootstrap/page
revalidation retains unchanged descriptors and draft presentation while strict
authority stamps continue fencing old responses/reviews. Tables retain filters
for the same identity and reset cursor/selection intent. Operational reads expose
refreshing separately from write busy, keep editors available and cannot overwrite
an edit made while an explicit reload was pending. A deliberate write can
supersede an optional read; neither is replayed. Real access/context loss clears
protected state and draft ownership.

The canonical frontend loading guide records complete public signatures, ownership,
optional-fallback bounds, supplied-view audit and future App/module requirements.
Components/layout/customization/motion, Auth account and Admin guides and both
module candidate changelogs are synchronized. Root/customer AGENTS and both
frontend pattern references link the same contract. Other domain skills/customer
backend guidance need no changes: backend APIs, grants, transactions, storage and
operators are unchanged. Auth remains the unreleased 2.0.0 candidate (including
new frontend refreshing), Admin its 0.1.0 creation candidate; Permissions/Audit
versions and native upgrade edges are unchanged. The additive UI contract remains
within the approved platform feature candidate 0.7.0; no dependency was added.

Necessary focused browser regressions cover cold/decoded/failed/timed-out images,
shared image ownership, same-identity refresh focus/drafts, initial preference
hydration without duplicate sessions, explicit retry, stale/foreign responses,
Admin admission/protected refresh, filters, operational edits/write supersession
and actual account/access loss. The final focused run passed 29 tests across five
files (329 tests excluded), native exit 0 and cleanup 0. Earlier targeted runs
also covered entry restrictions, CSRF refusal, explicit token handling and layout
replacement. The affected welcome test's obsolete three-module catalogue
expectation was updated to include the already delivered Admin module. Failed
native reports/screenshots remain preserved alongside successful evidence.

The prepared-asset build passed Vue TypeScript, Vite and native/static error-shell
rendering; log: .runtime/frontend/RM-2026-016-readiness-build.log. Nginx returned
200 for /admin and /auth/login; the served Admin document references the current
build and every entry-linked asset returned 200. This is local handover readiness,
not authenticated owner acceptance or a full quality PASS. Full suites/gates and
release closure remain point 12, after explicit owner acceptance in 11b. No Docker
resources, database changes or destructive operations were needed; development
services/data, caches and reports are preserved.

### Point 11a8 — Immediate frames, local loading regions

The owner rejected the whole-page loading placeholder produced by 11a7. This
correction supersedes that point's initial shell/root reveal decision: layouts
render immediately and only dependent content/elements display loading states.
Removed the root branding gate and the outer ApplicationSessionShell readiness
gate. MinimalLayout and ApplicationLayout now apply their public readiness prop
only to content/default (plus MinimalLayout actions), retaining the actual header,
backdrop, illustration/navigation and footer throughout loading. Welcome renders
its static content immediately and waits only in navigation. The session shell
still mounts protected content only after session validation; profile/avatar
metadata use the shared avatar's own animated placeholder/fallback. The decoded
image owner, bounded waits and nondestructive refresh/draft/security behavior
from 11a7 remain.

The public loading/layout/customization, Auth account and Admin branding contracts
and candidate changelogs are synchronized. Root/customer AGENTS explicitly require
immediate frames with local loading. Existing frontend pattern references already
link the canonical guide and need no duplicate procedure. No backend API, module
upgrade edge, dependency or release decision changed; existing unreleased platform
0.7.0, Auth 2.0.0 and Admin 0.1.0 candidates remain appropriate.

Focused browser regressions passed 12 cases (348 excluded), native exit 0 and
cleanup 0: both layout frames remain mounted while their content waits; root
mounts while branding waits; shell content is independent of the profile read;
image/entry readiness and same-session Admin proof refresh remain guarded. A test
selector was narrowed to its content input to exclude native preference controls.
The asset build passed TypeScript, Vite and native/static error rendering; log:
.runtime/frontend/RM-2026-016-local-loading-build.log.

A focused Playwright visual diagnostic against the actual development Nginx
endpoint deliberately held identity/presentation/entry-options/session responses.
Inspected four retained screenshots under
.runtime/artifacts/playwright/RM-2026-016-local-loading/: login desktop dark PL,
login mobile light EN, application desktop light EN and application mobile dark PL.
Each retained its real frame/header/footer with local animated placeholders and
no horizontal overflow. Releasing reads completed normal guest sign-in rendering.
This is loading-state evidence, not authenticated owner manual acceptance. The
browser/context resources were closed; no Docker resources or data were changed.
All screenshots/build/test reports and existing services/data/caches are preserved.
Manual point 11b and final verification/release point 12 remain open.

### Point 11a9 — Coordinated initial content/avatar completion

The owner clarified that local placeholders must also finish together: 11a8
correctly preserved the frame but released content after session validation while
the avatar still awaited profile/image loading. The shared ApplicationSessionShell
now uses the existing account/session-scoped useViewReadiness latch for its initial
content placeholder and avatar loading prop. Required initial settlement includes
session, profile, account preferences and image download/decode or bounded fallback.
This does not wrap/hide the layout. Pre-admission MinimalLayout composition remains
independent; heavy page regions retain their own loading states. Subsequent reads
keep mounted content/drafts/focus and the decoded avatar; actual identity/access
loss still invalidates protected content. No extra session owner or arbitrary
minimum loading delay was introduced.

Canonical loading/customization/Auth account contracts and the Auth candidate
changelog are synchronized. Root/customer AGENTS and frontend pattern references
already require an immediate frame with local loading and link the owning guide;
that rule remains correct, so no duplicate instruction-product text is necessary.
No public signatures, backend APIs, dependencies, module upgrade edges or release
decisions changed. Auth remains the unreleased 2.0.0 candidate within the existing
platform 0.7.0 candidate; Admin/Permissions/Audit versions are unchanged.

Focused browser regressions passed 13 cases (350 excluded), native exit 0/cleanup 0.
They cover delayed profile/download/decode, image error and the 2.5-second timeout,
simultaneous placeholder completion, retained frame and background draft/focus/image,
plus prior immediate-layout/root and Admin proof revalidation regressions. The
prepared-asset build passed TypeScript, Vite and native/static error rendering;
log: .runtime/frontend/RM-2026-016-coordinated-loading-build.log.

A focused Playwright diagnostic used controlled API presentation fixtures against
the real development Nginx-served build and held a real PNG response. Mutation
observations recorded content/avatar loading pairs true/true then false/false,
never mismatched, with the layout retained and native image naturalWidth positive.
Inspected waiting.png and ready.png under
.runtime/artifacts/playwright/RM-2026-016-coordinated-loading/. This is fixture-based
loading evidence, not authenticated owner acceptance. Browser/context resources
were closed and the temporary diagnostic helper removed; screenshots/build/test
reports and existing development services/data/caches remain. No Docker resources
or database data were created/changed. Manual point 11b and final point 12 stay open.

### Point 11a10 — Shared top navigation for page subviews

The owner confirmed the existing navigation placement rule: the sidebar selects an
area/module, while its additional subviews belong in the shared horizontal top
bar. Added UI-owned PageNavigation/usePageNavigationHost through `@/runa/ui`, with
typed entries/current/navigate, nearest-layout registration and usable standalone
fallback. ApplicationLayout combines published links with explicit topNavigation;
layout lifetime, existing dropdown/overflow/keyboard behavior and modified clicks
remain intact. Stable presentation snapshots prevent parent/slot render loops;
older publisher cleanup cannot clear a newer registration. No global state or
routing dependency was introduced. Public usePageResolver now exposes readonly
href so query changes/history update the mounted subview.

Auth account tabs now publish to the top bar and use `?tab=...`, preserving native
pending-write blocking and the account controller. Admin dashboard Operations and
Application settings publish there too; selected module Health policy, Settings
and Integrations use a capability-filtered top dropdown. Existing dirty guards
protect deliberate and history transitions. Accepted transitions abort optional
reads before the new view reads; initial capability admission precedes deep-linked
editor loading. Writes remain explicit and are never replayed. Admin owner page
links already used the top bar. Focused source review found no other supplied
application subview navigation requiring migration: MinimalLayout Auth access
links remain in their separate layout; record selection and use-case read/mutation
controls remain in content.

Synchronized public page-layout/component/customization, Auth account and Admin
frontend contracts, motion inventory and both module candidate changelogs. Root
and customer AGENTS plus frontend pattern references link the canonical rule.
No backend/API/schema, dependency or upgrade edge changed. Auth remains the
unreleased 2.0.0 candidate, Admin remains development 0.1.0, and the platform
remains the prepared 0.7.0 candidate. Other module versions are unaffected.

Focused native frontend verification passed 16 cases (354 excluded), exit 0 and
cleanup 0. Coverage includes nested header placement, publisher cleanup, standalone
fallback, URL/deep-link/history selection, dirty cancellation/acceptance, module
capability filtering, optional-read fencing and existing resolver/hidden/disabled
navigation contracts. This is implementation-focused evidence, not the final
quality graph. Log: .runtime/frontend/RM-2026-016-navigation-tests.log. The prepared
asset build passed TypeScript, Vite and native/static error rendering; log:
.runtime/frontend/RM-2026-016-navigation-build.log.

A controlled Playwright presentation fixture against the Nginx-served build
checked Admin/account in Polish/dark/1440px and English/light/390px. Top links were
localized, content had no duplicate subview controls, no document overflow or
page errors were reported. Screenshots and report are preserved under
.runtime/artifacts/playwright/runa/RM-2026-016-navigation/. This fixture-based check
does not replace authenticated owner acceptance. Browser contexts were closed and
the temporary helper removed. Existing development services, database data,
knowledge/model caches and reports remain; no task Docker resources were created.
Owner manual point 11b and final verification/release point 12 remain open.

### Point 11a11 — Current navigation and coordinated Admin initial presentation

Owner-reported loading repair after 11a10: account navigation now publishes before
its dependent content, with animated loading in the real top bar and no obsolete
content-tab skeleton. Public PageNavigation.loading and host loading support
standalone/custom layouts; ApplicationLayout.navigationLoading supports initial
sidebar/top-bar discovery. AuthApplication forwards navigationLoading/contentLoading
to its single session shell. Its frame remains immediate and Admin keeps that
Application frame during bootstrap; explicit entry proof/refusal retains its own
admission presentation.

The Admin content region reuses useViewReadiness, mounts authorized controllers in
a hidden/inert subtree and waits for explicit initial dependencies. Page projection,
record/collection reads, capabilities/diagnostics and selected settings/editor reads
settle before content/navigation/avatar placeholders finish. Async presentation
boundaries wait for renderer resolution and newly mounted read owners, retaining
the existing 15-second failure bound. Terminal read errors reveal existing recovery
controls. Interactive option searches and optional widgets retain local ownership;
no global request counter, second session owner, extra read or minimum delay was
introduced. Newly opened operational editors also use local public boundaries.
The latch preserves drafts, focus and DOM during later reads; actual identity or
access loss retains the existing protected-content/request fences.

Canonical loading/page-layout/customization and Auth/Admin frontend guidance and
both candidate changelogs are synchronized. Owner/customer AGENTS and frontend
pattern references already require the same immediate-frame/local-loading contract
and link these owning guides; neither instruction product needs duplicate rules.
Backend/security guidance is unchanged: no authorization, transaction, storage,
operator, dependency or migration change. Auth remains unreleased 2.0.0 and Admin
its 0.1.0 creation candidate within prepared platform 0.7.0; other module versions
and native upgrade edges are unchanged.

Focused browser verification passed 24 cases (350 excluded), native exit 0 and
cleanup 0. Coverage includes held bootstrap/capabilities/diagnostics, lazy renderer
plus subsequent read, bounded renderer failure, retained dirty draft/focus, automatic
record/table reads, entry proof/refusal, account/Admin URL subviews and standalone/
header navigation loading. Log: .runtime/frontend/RM-2026-016-admin-loading-tests.log.
The first diagnostic assertions used isVisible on a detached test subtree; mounting
the presentation fixture into the browser document corrected that visibility check.
Native failed reports/screenshots are preserved with successful evidence.

The manual-runtime asset build passed TypeScript, Vite and native/static error
rendering; log: .runtime/frontend/RM-2026-016-admin-loading-build.log. Nginx returned
200 for /admin and /account and every linked build asset. Browser component checks
exercise presentation; this is not authenticated owner manual acceptance or a final
quality PASS. No Docker resources or database data were created/changed. Existing
development services/data, knowledge/model caches and all reports remain. Owner
manual point 11b and final verification/release point 12 remain open.

### Point 11a12 — Dashboard status presentation and module dialogs

Implemented the owner's ten Admin dashboard corrections. UI now owns one public
source module-presentation catalogue: Welcome and Admin sidebar/cards use the same
manifest names/descriptions and platform icon map, with a usable optional-module
fallback. The module-state selector uses the requested Polish labels and refreshes
on selection without an extra button. A local Spinner/aria-busy accompanies
refresh while cards remain mounted; newer diagnostic filters cancel obsolete
optional reads and cannot receive a stale replacement snapshot.

Inspected the actual identity chain: src/runa/platform.json and VERSION identify
platform Runa 0.7.0, while src/app/application.json independently identifies the
application as Runa 0.0.0. branding config and ApplicationIdentity consume these
validated manifests; AdminOperations diagnostics already expose their correct
versions. The live public /api/identity confirms both names/versions. The defect
was presentation: the application name was replaced by a generic label and the
safe runtime.php value was rendered as a JSON object. The dashboard now separates
platform/application/runtime, uses the public identity's application name (including
its documented presentation precedence) and renders the scalar PHP version.
No identity, version or runtime configuration was changed.

Public AdminModuleCard supplies shared icons, meaningful localized status/reasons,
semantic success/warning/danger/neutral accents, source/installed versions and
readable dependency/observation details. It is available for composition and through
AdminPresentation.moduleCard. Clicking a card opens the shared accessible Dialog;
capability-filtered local Tabs contain overview/actions, health policy, settings
and integrations. Dashboard/Application settings remain in the shell top bar.
Deep links/history remain URL-derived. Closing or changing a dirty editor uses
the existing guard; rejected close retains its draft and URL, actual context loss
retains existing clearing/fences. Module changes clear old preview/work/result
presentation. Core lifecycle controls are omitted, with backend refusals unchanged.
Native lifecycle review still shows affected modules, planned changes, blockers
and active-work counts before the existing explicit apply/confirmation protocol.
Retained-operation recovery is grouped in a disclosure inside the dialog.

Corrected Admin breadcrumbs to Home / Administrator panel, including owner pages.
Removed empty dashboard-widget copy and the redundant persisted-observation note.
Updated the canonical component/module-authoring/loading/motion and Admin contracts,
Auth/Admin candidate changelogs and affected component/E2E expectations. Owner and
customer AGENTS/skills already route reuse, public replacement, navigation and
readiness to these canonical guides; both products need no duplicate rule. The
new catalogue location is reflected in the module-authoring guide. No backend API,
authorization, transaction, schema, dependency or upgrade edge changed. Auth remains
unreleased 2.0.0 and Admin creation candidate 0.1.0 under prepared platform 0.7.0;
other modules and release metadata are unchanged.

Necessary focused browser checks passed 20 cases (358 excluded), exit 0 / cleanup 0:
source-shared icons and breadcrumbs, omitted widgets, native proof/read fences,
rapid filter cancellation/progress/stale-response refusal, actual application name
with server versions/scalar PHP, guarded modal/deep-link/tab/dirty-close behavior,
custom slot ownership of module-dialog presentation, and prior initial
readiness/lazy/failure/background draft/navigation regressions.
Log: .runtime/frontend/RM-2026-016-dashboard-tests.log. Native E2E expectations are
synchronized; broad E2E and the final quality graph remain point 12.

Manual-runtime build passed TypeScript, Vite and native/static error rendering;
log: .runtime/frontend/RM-2026-016-dashboard-build.log. Nginx /admin and all 16
entry-linked build assets returned 200. A controlled Playwright presentation
diagnostic against this served build inspected dashboard, held diagnostic refresh,
module overview and settings at PL/dark/1440px and EN/light/390px. Eight final
screenshots and report.json remain under
.runtime/artifacts/playwright/runa/RM-2026-016-dashboard/. Both contexts had correct
breadcrumbs, four sidebar icons, no page errors and no document overflow. Initial
fixture screenshots remain prefixed initial-; the fixture's preference/activity
responses were corrected before the successful diagnostic. These synthetic checks
do not replace authenticated owner acceptance. Browser contexts were closed and
temporary diagnostic/edit helpers removed. No Docker resources or database data
were created/changed; existing services/data, knowledge/model caches and all reports
remain preserved. Manual owner point 11b and final verification/release 12 stay open.

### Point 11a13 — Stable modal feedback, active work and native operation history

After the owner's clarification/approval, implemented the six modal corrections
and replaced the manual retained-operation UUID/type form with history of native
lifecycle and module-settings changes. No new persistence or migration is needed:
framework-owned ModuleOperationHistory reads only safe metadata columns from the
existing durable shared.module_transitions ledger. It includes terminal and active
operations targeting/affecting the selected module, with exclusive newest-first
UUIDv7 cursors, default 25 / maximum 100 rows and no private payload selection or
decryption. Guarded AdminOperations.read('history') reuses existing entry/proof/
availability and per-kind management grants, asserts current authority after the
read and advertises only native actions appropriate to current kind/state. Native
status/resume/cancel/recover keep their existing execution/refusal/Audit semantics.
No generic undo or browser maintenance exception was introduced.

The public AdminOperationHistory default uses Table, dates/type/status and guarded
row controls, initial/explicit refresh, cursor continuation, localized state badges
and a public presentation replacement seam. It shares the owning operation lane
without another session owner. Private snapshots/tokens/settings values/internal
IDs never enter its response. Module/context changes fence old results and clear
history/work ownership. Failed history reads retain explicit recovery; successful
write/context invalidations reconcile reads without replaying writes.

The disappearing modal was caused by marking all Tabs items disabled during a
write: the shared Tabs contract intentionally has no active panel in that state.
Module tabs now retain availability/selection while Dialog's native busy fieldset
and the existing navigation guard disable changes. Diagnosis/work/history own
separate pending feedback; diagnostic refresh no longer spins Active work. Core,
registration and enabled badges use semantic colors. Diagnostic results use green
success, warning for degraded/unknown/blocked and danger for failed/unhealthy;
they show the outcome and safe explanatory reasons without repeating module ID.
Native progress renders as readable items rather than raw object serialization.

The first Active work click mounts its Table at the modal bottom even for an empty
result; subsequent clicks refresh it. Confirmed data/empty frames remain through
pending reads. Reusable Table.retainEmptyFrame is explicit/default-false and keeps
headers with an accessible empty row; both history/work defaults adopt it. Fixed
column-state widths preserve readable dates/statuses and local horizontal access
on phones, without overflowing the document. Other Table consumers retain their
existing empty-state contract. Optional diagnostic/history/work reads preserve the
frame and existing drafts/focus; true access/identity loss retains protected cleanup.

Synchronized canonical Admin operations/frontend, loading/motion/table guides and
the Admin candidate changelog. Owner/customer AGENTS and domain skills already
route to these contracts and require the same guarded public APIs, presentation
replacement and local readiness; neither product needs duplicated procedures or
new constitution rules. The framework query is a documented trusted integration
internal, not a customer API. The additive fixed history read remains within the
unreleased AdminOperations 1.0.0 / Admin creation 0.1.0 candidate. Platform remains
prepared 0.7.0; Auth/Permissions/Audit versions, existing native upgrade edges,
release metadata, dependencies and database schema are unchanged.

Necessary focused native backend checks passed 3 cases / 38 assertions using
python3 tools/maintainer/test-backend --parallel --processes=1
--filter='module operation history'. They cover exact kind grants, target/affected
scope, no payload reads (including deliberately undecryptable fixture payloads),
cursor/limit/input refusal, unknown modules and state-limited actions. Log:
.runtime/frontend/RM-2026-016-operation-history-backend-final.log. An initial
unisolated run was refused by the fixture's existing-Core-schema guard; the
supported one-worker transport preserves that base. Fixture assertions were
corrected to retain legitimate pre-existing settings history instead of assuming
an empty ledger. The runner's guarded cleanup removed runa_test_test_1, exit 0 /
cleanup 0; the base/development databases were preserved.

Focused browser regressions passed 17 cases (364 excluded), native exit 0 / cleanup 0. Coverage holds diagnosis responses while asserting the panel/card stays mounted
and unrelated buttons do not spin, verifies semantic diagnostic/badge tones and
nonduplicated identity, empty/repeated work table reads, native history paging/row
actions and existing guards/initial readiness/navigation/filter races. Log:
.runtime/frontend/RM-2026-016-modal-operations-tests-final.log. The initially
missing empty table frame exposed the need for the explicit public Table option;
its failed native screenshot/log and later successful evidence remain preserved.
These are focused implementation checks, not complete backend/frontend area PASS.

The manual-runtime build passed TypeScript, Vite and native/static error rendering;
log: .runtime/frontend/RM-2026-016-modal-operations-build.log. A controlled visual
diagnostic against the actual Nginx-served build inspected held diagnosis, empty
work and populated history/work at PL/dark/1440px and EN/light/390px. Six screenshots
and report.json remain under
.runtime/artifacts/playwright/runa/RM-2026-016-modal-operations/. Both contexts
reported no page errors/document overflow and exactly two deliberate work reads.
Browser contexts were closed and temporary diagnostic/edit helpers removed. This
does not replace authenticated owner acceptance. No Docker resources or application
DB data were created/reset; development services/data, model/knowledge caches and
verification artifacts remain. Owner manual point 11b and final quality/release
point 12 stay open.

### Point 11a14 — Hierarchical breadcrumbs and scoped module overview

Applied the owner's four manual-acceptance corrections. The dashboard top entry is
Modules / Moduły. UI's public ApplicationBreadcrumbSlotContext exposes current
sidebar/top entries, the page-published route and the existing navigation handler
through ApplicationLayout's breadcrumbs slot. The shared session shell renders
Home (when enabled) / area / owner where applicable / current page, with linked
ancestors and one current leaf. Dashboard query subviews, owner pages and account
subviews use their existing localized navigation labels; dialog tabs do not become
page hierarchy levels. Navigation uses the existing handler/dirty guards and keeps
modified-click behavior. No additional router or session owner was introduced.

Diagnosis is the first Overview action above the module card. Its result and both
history tables now belong inside the actual Overview tab panel; policy/settings/
integration panels do not include them. Operation history disposes its optional
reads on tab departure. Task history loads automatically, including deep links,
keeps confirmed table frames during refresh, and has its own Refresh tasks button
instead of the former Active work trigger. Module/tab/context changes fence late
work responses. Successful native writes reconcile task reads after the shared
write/read lane settles; no write is replayed.

The framework-owned public ModuleWork.history(module, limit=100) reuses the native
ledger and WorkStatus projection for newest-first retained active/terminal work,
bounded at 1000. Active-only lifecycle fences remain on active(). Admin's existing
work read keeps entry/proof/work.read admission, safe projection and explicit
truncation. No work payload is decrypted for history. Terminal rows offer status
reads without cancellation controls; unresolved rows retain native capability/
cooperative cancellation policy. No new engine/store, migration, permission,
dependency or data reset is needed.

Synchronized canonical page-layout, Admin frontend/operations, module-framework,
motion and Admin/Auth candidate changelogs. Owner/customer AGENTS and domain skills
already point at these canonical layout/public-adapter/readiness contracts; no
new reusable operating rule or duplicated instruction procedure is needed in either
instruction product. Admin remains the unreleased creation 0.1.0 candidate; Auth
changes remain within the unreleased 2.0.0 candidate. Public contract additions are
additive, with no persisted identity or native upgrade change. Prepared platform
0.7.0 and approved release metadata remain unchanged.

Focused supported frontend checks passed 8 cases (375 excluded), exit 0 / cleanup 0:
.runtime/frontend/RM-2026-016-overview-navigation-tests-ready.log. They cover
hierarchy/links/query navigation, automatically loaded empty/terminal work,
refresh/reconciliation, action-local diagnosis, overview-only result/tables,
selection and direct module URLs, and existing history/guard behavior. The added
deep-link case exposed initial capability cleanup overwriting the read request;
the readiness watcher now schedules the initial work read after capabilities settle.
Earlier failed evidence is retained. A first breadcrumb assertion used a nonexistent
CSS selector; it was corrected to the actual top-nav primitive.

Focused supported isolated backend checks passed 2 cases / 35 assertions using
python3 tools/maintainer/test-backend --parallel --processes=1 with the task-history
and existing read-authority filters. Log:
.runtime/frontend/RM-2026-016-task-history-backend.log. Coverage includes grant
refusal, newest-first terminal evidence, bounded/truncated reads, unchanged active
semantics, module isolation and deliberately undecryptable private task payloads.
The runner removed only runa_test_test_1; base/development databases were preserved.
An overlapping initial invocation was refused by the normal suite lock before
execution, then rerun sequentially; no runner/lock bypass was used.

The current TypeScript/Vite/native-error build passed:
.runtime/frontend/RM-2026-016-overview-navigation-build-accepted.log. Nginx /admin
and all 16 entry-linked assets returned HTTP 200. A controlled browser diagnostic
against that served build inspected PL/dark/1440px and EN/light/390px Overview,
terminal task history and policy-tab departure. Screenshots/report are retained in
.runtime/artifacts/playwright/runa/RM-2026-016-overview-navigation/. Both contexts
report no page errors/document overflow, two deliberate task reads, the expected
Modules breadcrumb and zero tables/diagnostic results on the policy tab. Synthetic
session/activity/operation responses performed no application business writes;
public identity/presentation reads used the existing endpoint. Browser contexts
were closed and the temporary diagnostic helper removed. No Docker resources were
created; active development data/services, caches and evidence remain preserved.
These focused implementation checks do not replace owner acceptance or final-area
verification. Manual point 11b and final verification/release point 12 stay open.

### Point 11a15 — Breadcrumb loading readiness

Covered breadcrumb discovery with the existing ApplicationLayout navigation
readiness. The breadcrumb region stays mounted/aria-busy with shared animated
Skeleton while shell navigationLoading or the PageNavigation publisher is loading.
Its slot mounts only when the complete navigation context is ready, removing the
visible partial area/owner/page path and intermediate links. Ready standalone
layouts still render immediately. No new timer, prerequisite registry, controller,
session or page-wide cover was introduced. The existing skeleton's reduced-motion
and print behavior applies; responsive width stays bounded. Supplied Admin's
initial latch remains unchanged, so ordinary background reads retain their path.

Extended the existing delayed Admin bootstrap/capability/diagnostic and page-host
loading regressions to assert placeholders, withheld partial links and the final
breadcrumb leaf alongside the stable frame. Focused supported frontend execution
passed 5 cases (378 excluded), exit 0 / cleanup 0:
.runtime/frontend/RM-2026-016-breadcrumb-readiness-tests.log. Existing hierarchy and
standalone layout-region regressions passed in the same selection. This is focused
implementation evidence, not full frontend acceptance. The TypeScript/Vite/native
error build passed in .runtime/frontend/RM-2026-016-breadcrumb-readiness-build.log;
Nginx /admin and all 16 entry-linked assets returned HTTP 200.

Synchronized canonical loading, motion and page-layout contracts. This shared UI
presentation repair changes no Admin/Auth module implementation or public
signature; independent module candidates and prepared platform 0.7.0 stay current.
Owner/customer AGENTS and skills already refer to those readiness/layout contracts,
so neither instruction product needs duplicated rules. No dependencies, database
changes or Docker resources were introduced; development data/services, knowledge/
model caches and verification reports remain preserved. Owner acceptance 11b and
final verification/release 12 remain open; broad gates were not run.

### Point 11a16 — Coherent account-menu and display-control readiness

The owner's remaining header loading defects came from two separate omissions:
initial content readiness did not include Admin entry advertisement settlement,
and ApplicationLayout rendered language/theme controls even while its navigation
was awaiting account preference hydration. The private Auth advertisement adapter
now exposes available/settled refs, bounds the optional read with the shared
HTTP client and a five-second signal, and settles failures/refusals to no link.
The existing session identity/readiness latch includes this settlement. Aborted
or late old-identity results remain fenced by epoch/disposal; same-identity
background revalidation retains settled presentation and the known link until a
confirmed result, denial or failure. No authority is inferred from advertisement.

ApplicationLayout uses existing local Skeletons for display controls while its
navigationLoading is true, then mounts hydrated labels/icons. Its account-menu
trigger is disabled while user/navigation presentation is loading, preventing an
incomplete initial menu. The frame stays mounted. Existing preference/session
owners, snapshot notifications, background draft retention and intentional theme/
language cover/focus behavior remain unchanged; no global request counter or extra
session/preference reads were added.

Synchronized canonical loading/motion, Auth account UI, Admin shared-shell guidance
and the Auth candidate changelog. Auth changes stay within the unreleased 2.0.0
candidate, Admin remains the unreleased 0.1.0 creation candidate and platform stays
prepared 0.7.0. No API signature, migration, manifest dependency or upgrade edge
changes. Owner/customer AGENTS and skills already reference these shared readiness/
preference contracts; neither product needs duplicated procedures or new rules.

Focused supported frontend checks passed 10 cases (377 excluded), native exit 0 /
cleanup 0. Log: .runtime/frontend/RM-2026-016-header-readiness-tests-final.log.
New held-response cases verify both advertisement/preferences completion orders,
hydrated dark-theme controls on reveal, disabled menu until ready, background
retention/removal, failure fallback and bounded abort/withdrawal. Existing initial
Admin readiness, supplied-session ownership, hierarchy and page-loading checks
passed. Earlier tests attempted menu interaction while the newly guarded trigger
was still disabled; they now wait for actual readiness rather than assuming one
promise flush means presentation completion. Failed evidence is preserved.

The TypeScript/Vite/native-error build passed in
.runtime/frontend/RM-2026-016-header-readiness-build-final.log. /admin and all 16
entry-linked assets returned HTTP 200. A controlled browser diagnostic against
the served build held advertisement/preferences separately at PL/dark/1440px and
EN/light/390px, checking pending display controls/menu, either first completion,
ready final theme and the advertised Admin link. Both contexts report no page
errors/document overflow. Screenshots and report.json remain under
.runtime/artifacts/playwright/runa/RM-2026-016-header-readiness/. Synthetic session/
preference/operation responses performed no application business writes. Browser
contexts were closed and the temporary diagnostic helper removed. No Docker or
database resources were created/reset; active services/data, model/knowledge caches
and all evidence remain preserved. These focused checks do not replace owner manual
acceptance 11b or final verification/release 12, which remain open.

### Point 11a17 — Source-only health policy and module-list presentation

The owner removes runtime health-policy editing entirely. HealthPolicy is the
platform-owned public module extension in Contracts/Modules: schemaVersion(): int
now returns 2, definition(): HealthDefinition has no overrides, and
check(string, HealthContext): bool retains the deadline/safe-result contract.
ModuleHealth, registry snapshots, availability comparisons, management discovery
and the development contract suite consume source-only definitions. Removed the
configuration method, Admin operation/grant, policy response payload and editor/tab.
Diagnosis, bounded observations, source/fingerprint expiry, required-graph blocking
and sealed transaction admission remain. No compatibility adapter is retained.

A forward platform migration removes only health_overrides/health_revision from
shared.modules, retains the bounded observations constraint, invalidates old
replaceable results and advances registry generation. Earlier committed migrations
remain unchanged. No business records, Audit history or installation versions are
removed. The reference module adopts schema 2 and independently advances 0.1.2 to
0.2.0 for this incompatible development contract, with a source_health forward edge
and platform >=0.7.0. Admin remains the unreleased creation candidate 0.1.0;
prepared platform/release metadata remain 0.7.0. Framework descriptor schema 2 is
explicit; the module manifest framework_api envelope stays version 1.

Module-state filter reads replace the card list with shared animated block
Skeletons while retaining the selector/frame. Existing cancellation/request stamps
keep the latest selection authoritative; ordinary same-filter refresh retains
confirmed cards and its local spinner. Disabled/unregistered cards use the neutral
text token for their top accent and hover border, preserving the shared 160ms
motion, reduced-motion behavior and keyboard focus ring.

Synchronized canonical module-framework/database/development guides, Admin public
operations/editor/loading/motion contracts, Admin candidate changelog and reference
module guidance/changelog. Owner/customer AGENTS and skills already delegate module
participation and readiness to these canonical guides. Neither instruction product
contains the removed override/editor procedure, so neither needs duplicated rules.
Local knowledge was current; focused retrieval was checked against original sources.

Focused supported frontend checks passed 7 cases (380 excluded), exit/cleanup 0,
in .runtime/frontend/RM-2026-016-source-health-filter-tests.log: filtered skeletons,
latest-response fencing, editor draft retention, operation read/write fencing,
deep-linked settings and overview task-history departure. Focused supported isolated
backend checks passed 5 cases / 48 assertions, exit/cleanup 0, in
.runtime/frontend/RM-2026-016-source-health-backend-ready.log: absent policy columns,
source scheduling, invalid/stale source refusal, sealed evidence expiry/change and
removed HTTP command with unchanged integration/settings Audit protections. An
initial overlapping backend invocation was refused by the existing suite lock;
it was rerun sequentially without bypass. Only runa_test_test_1 was cleaned by the
runner; base/development databases were preserved.

The supported TypeScript/Vite/native-error build passed in
.runtime/frontend/RM-2026-016-source-health-build.log. Local read-only preflight
identified environment local, database runa, 15 existing accounts, no native
maintenance and no reserved jobs/active work/transitions. Implementation is committed at ea9c1afe with mandatory hooks passing.
The post-hook build also passed:
.runtime/frontend/RM-2026-016-source-health-build-committed.log.

Prepared the actual local development database through the documented forward
primitive with source ea9c1afe, during task-owned native maintenance. It applied
only 2026_10_31_000001_make_module_health_source_only; evidence is retained in
.runtime/frontend/RM-2026-016-source-health-forward.log. Explicit app-owner sync
updated reference source metadata to 0.2.0 while preserving its disabled and
uninstalled state. Existing Core installed versions remain matched and all 15
accounts remain. Removed only the task-owned maintenance and published the native
queue restart signal. No reset, credential/grant mutation or module installation
was performed. Retained permission dictionary/grant history follows the existing
Permissions source-removal policy; no executable health.configure operation or
capability remains.

Nginx /admin, /api/ready and all 16 entry-linked assets returned HTTP 200;
unauthenticated /api/admin/entry remains 401. A focused controlled browser
inspection against that served build checked PL/dark/1440px with normal motion
and EN/light/390px with reduced motion. It verified gray top/hover computed colors,
withdrawn cards and block Skeletons during a held filtered read, motion reduction,
restored cards and exactly Overview/Settings/Integrations tabs. Neither context
had page errors or horizontal document overflow. Screenshots/report.json remain
under .runtime/artifacts/playwright/runa/RM-2026-016-source-health-filter/;
log: .runtime/frontend/RM-2026-016-source-health-browser-final.log. Synthetic
session/preferences/operation responses made no application business writes.
Two earlier diagnostic attempts used incorrect select role/translated option
selectors; the diagnostic was corrected to the actual public control and copy.
Failed logs remain preserved. Browser contexts closed and the temporary helper
was removed. No Docker resources were created or removed; development/test data,
knowledge/model caches and all verification evidence are preserved.

Point 11a17 is ready for repeated owner acceptance at http://localhost:8080/admin.
Owner acceptance 11b and final verification/release 12 remain open; these focused
checks do not claim broad quality or manual acceptance PASS.

### Point 11a18 — Direct lifecycle controls and adjacent diagnosis

The owner confirms that unregister remains registration removal, preserving
installed data; no installation/uninstallation API is added. Optional lifecycle
controls now precede the diagnostic row and module details. Enable/Disable follows
the current enabled state, alongside Unregister. Core and unregistered targets
have no lifecycle controls. Removed the Core instruction paragraph, intent selector
and standalone preview/apply sequence without weakening native server refusals.

Each action opens one shared nested Dialog with an initial Skeleton and the native
modules.preview result: affected modules, proposed flags, active work and blockers.
Loading/blocked states cannot be confirmed. Confirmation submits the exact reviewed
token once with confirmed=true; no second generic prompt is shown. Cancel/Escape
uses existing nested focus/inert/scroll ownership. A Vue render tick lets Dialog
capture its enabled opener before the shared write lane disables the outer controls;
there is no artificial timer or new modal engine. Failed apply invalidates the
review and requires explicit Review again. Module/tab/context/authority or grant
loss clears previews and fences late results. Native actor/token/expiry/generation
revalidation, Audit and admission remain unchanged.

Diagnosis feedback is owned separately from lifecycle/work outcomes and is rendered
in the same wrapping row as its trigger. Work/lifecycle results cannot overwrite it.
The diagnostic panel stays mounted and only its button indicates diagnosis progress.
Unregister precondition/dependent blockers now have explicit PL/EN messages.

Synchronized Admin frontend/loading/motion guidance and the candidate changelog.
Owner/customer AGENTS and skills already delegate to these canonical composition,
readiness, public-module and dialog contracts; neither instruction product needs
duplicate procedures. Local knowledge was current and the bounded retrieval was
checked against the relevant original contracts. Admin remains the unreleased
creation candidate 0.1.0 within prepared platform 0.7.0. No API signatures, module
manifests/upgrade edges, dependencies, runtime configuration or database changes.

Focused supported frontend checks passed 9 cases (384 excluded), native exit 0 /
cleanup 0, in .runtime/frontend/RM-2026-016-lifecycle-controls-tests-ready.log.
They cover enable/disable/unregister preview and exact-token confirmation,
cancellation/focus restoration without mutation, blocked previews, failed-apply
invalidation without replay, authority change during preview, adjacent independent
diagnosis results, task-history behavior and existing dirty tab/close guards.
Earlier failures exposed the disabled-opener focus timing; the render tick fixes
that behavior. An initial build also caught a missing test import, now corrected.
Native failed evidence remains preserved. No backend implementation changed, so
no backend suite was selected for this focused UI point.

The supported TypeScript/Vite/native-error build passed in
.runtime/frontend/RM-2026-016-lifecycle-controls-build-ready.log. A focused controlled
browser inspection against the served build passed PL/dark/1440px normal motion
and EN/light/390px reduced motion. It checked control ordering, adjacent diagnosis,
loading/ready/blocked preview, cancellation focus, Escape, one confirmed apply and
no document overflow/page errors. Screenshot/report evidence is retained under
.runtime/artifacts/playwright/runa/RM-2026-016-lifecycle-controls/; log:
.runtime/frontend/RM-2026-016-lifecycle-controls-browser.log. Synthetic account/
operation responses made no application business writes. Browser contexts closed;
temporary diagnostic helper removed. No Docker resources were created/removed;
active services/data, knowledge/model caches and all reports remain preserved.

Implementation is committed at f26229e3 with mandatory hooks passing; hooks left
the tested/built frontend source unchanged. Current Nginx /admin and /api/ready
return HTTP 200; unauthenticated /api/admin/entry remains 401, and all 16
entry-linked assets return 200. Evidence:
.runtime/frontend/RM-2026-016-lifecycle-controls-http.json. No additional build or
suite was necessary after the unchanged-source hook checks. The working application
at http://localhost:8080/admin is ready for repeated owner acceptance. Point 11a18
is complete; owner acceptance 11b and final verification/release 12 remain open.
No broad gates, database operations or infrastructure cleanup were performed.

### Point 11a19 — Unified action row and diagnostic result dialog

Removed the Optional lifecycle heading and its unused translation. Lifecycle
controls remain on the left and Diagnose now aligns right in one wrapping action
row above module details, including Core/diagnostic-only views. Diagnosis now
opens a shared nested Dialog immediately: local Skeleton, safe semantic result,
or error with explicit Retry. No inline diagnostic result remains. The modal
uses the existing focus/inert/scroll stack, captures its enabled opener before
the shared write lane disables controls and restores trigger focus on close/Escape.
Module/tab/context/authority/grant changes clear its result/open state and fence
late observations. Diagnosis remains explicit, with no automatic retry or new
request/controller/backend engine. Lifecycle confirmation behavior is unchanged.

Synchronized canonical Admin frontend/loading/motion guidance and the unreleased
Admin candidate changelog. Owner/customer AGENTS and skills already refer to these
canonical public dialog/readiness/replaceable-view contracts; neither instruction
product needs duplicate procedures. Local knowledge was current and bounded
retrieval was checked against source. No public signatures, dependencies, module
versions/manifests, upgrade edges, runtime configuration or database changes.
Admin remains the 0.1.0 creation candidate within prepared platform 0.7.0.

Focused supported frontend checks passed 10 cases (385 excluded), native exit 0 /
cleanup 0, in .runtime/frontend/RM-2026-016-diagnosis-modal-tests.log. Coverage:
separate modal results for selected/deep-linked modules, retained overview/task
history, close/focus restoration, Core diagnostic-only alignment, failure/retry
presentation without replay, authority loss during diagnosis and all existing
lifecycle confirmation/token/blocker/refusal cases. The supported TypeScript/Vite/
native-error build passed in .runtime/frontend/RM-2026-016-diagnosis-modal-build.log.
No backend suite or broad final gate was selected for this presentation-only point.

Focused controlled browser inspection passed PL/dark/1440px normal motion and
EN/light/390px reduced motion against the served assets: removed heading, right
alignment, loading/result in a separate modal, no inline result, safe healthy/
unhealthy outcomes, Escape/focus return, exactly one explicit diagnosis per context,
and no page errors/document overflow. Evidence:
.runtime/artifacts/playwright/runa/RM-2026-016-diagnosis-modal/ and
.runtime/frontend/RM-2026-016-diagnosis-modal-browser-ready.log. Static screenshots
finish finite transitions rather than capturing the entering modal translucency.
Synthetic account/operation responses performed no application business writes.
Browser contexts closed and the temporary diagnostic helper was removed.

Current /admin, /api/ready and all 16 entry-linked assets return HTTP 200;
unauthenticated /api/admin/entry remains 401. Evidence:
.runtime/frontend/RM-2026-016-diagnosis-modal-http.json. The local application is
ready for repeated owner acceptance at http://localhost:8080/admin. No Docker
resources were created or removed; development/test data, active services,
knowledge/model caches and verification evidence are preserved. Owner acceptance
11b and final verification/release 12 remain open.

### Point 11a20 — Polish lifecycle cancellation label

Added the missing Cancel -> Anuluj entry to the Admin message dictionary. The
existing shared action dialog now translates its cancellation button for enable,
disable and unregister. English retains Cancel. A focused review of literal tr()
labels in AdminOperations found no other missing dictionary entries. No lifecycle,
permission, API, dependency, database or configuration behavior changes.

Focused supported rendered frontend checks passed 6 cases (392 excluded), native
exit/cleanup 0: three Polish cancel-label cases plus the existing English lifecycle
preview/confirmation cases. Evidence:
.runtime/frontend/RM-2026-016-cancel-localization-tests-final.log. Test setup uses
the public locale controller so the stored preference cannot retain an earlier
English selection, then restores English. Earlier diagnostic failures used an
incorrect readiness phrase/raw legacy locale value and are preserved; they did
not represent a remaining runtime translation failure.

The current TypeScript/Vite/native-error build passed in
.runtime/frontend/RM-2026-016-cancel-localization-build-final.log. Nginx /admin and
all 16 entry-linked assets returned HTTP 200, with current translations available
for owner acceptance. Admin stays the unreleased 0.1.0 creation candidate within
prepared platform 0.7.0. Updated the candidate changelog; existing canonical locale
contracts and owner/customer AGENTS/skills need no new rule or duplicate procedure.
Local knowledge was current and the focused localization source was reviewed.
No Docker resources were created/removed, no application business data changed,
and existing services/data, caches and reports are preserved. Owner acceptance
11b and final verification/release 12 stay open; no broad gate was run.

### Point 11a21 — Dedicated settings pages instead of a generic module editor

The owner removes the generic module settings tab and requires module-owned
administrator-facing settings pages with history and owning publication workflows,
as Auth already provides. The native SettingsProvider/ModuleSettings storage,
validation, secret redaction, revisions, Audit and recovery contracts remain.
Source/CLI-only technical examples do not advertise an Admin editor automatically.
The reference preserves its existing values and documented native CLI example;
this point does not invent an unrequested reference administration page.

Removed the dialog settings tab, editor mode, generic settings read/update HTTP
operations, controller command/view types and settings.read declaration. Old
settings deep links resolve to Overview without making settings requests. The
fixed settings.status/recover endpoints and settings.manage authority remain only
for retained operation history/recovery, with clarified source permission copy.
No fresh settings patch is accepted through that retained recovery route. Existing
permission dictionaries/grant history retain the native source-removal policy;
no database cleanup or manual assignment mutation is performed. Dedicated Auth
policy read/review/history remains available under its owning grants.

AdminOperationalEditor now has the public props {controller,module} and supports
only non-sensitive integration configuration. Removed obsolete typed settings/
secret/custom-control code and translations rather than adding compatibility
paths. Its public facade/presentation seam remains, with the narrowed props
specified in the Admin frontend guide. Integrations retain dirty guards, initial
readiness, context/reload fencing and explicit writes. The save label is localized
as Save integration / Zapisz integrację. Admin remains its unreleased 0.1.0 creation
candidate within prepared platform 0.7.0; no manifest/upgrade or dependency change.

Synchronized Admin README/frontend/operations/authoring/candidate changelog,
module-development guidance, reference README, loading and page-navigation query
contracts. Both owner and customer instruction products already direct module
and Admin authoring to those canonical guides, so neither needs duplicated rules.
Relevant backend/frontend skills and current local knowledge were consulted;
bounded retrieval was checked against original sources.

Focused supported frontend execution passed 10 cases (392 excluded), native
exit/cleanup 0, in .runtime/frontend/RM-2026-016-dedicated-settings-frontend.log:
no generic tab/reads for Auth/Audit/Permissions/reference or old links, integration
conflict/reload draft retention, integration deep links/dirty close guards and
unchanged diagnosis/task-history departure. The supported TypeScript/Vite/native
error build passed in .runtime/frontend/RM-2026-016-dedicated-settings-build-ready.log.

Focused supported isolated backend execution passed 4 cases / 70 assertions,
parallel/processes=1 with native exit/cleanup 0, in
.runtime/frontend/RM-2026-016-dedicated-settings-backend-ready.log. Coverage includes
refused generic reads/patches for all four module IDs without generation mutation,
unchanged integration Audit rollback/publication, dedicated Auth policy read/review/
history/refusal and scoped retained settings history. The first run exposed a
stale Auth policy test assertion: the native OperationRejected render returns
error.code, not top-level reason. Source inspection confirmed that owning contract;
only that assertion was corrected, preserving HTTP 403 and step_up_required. The
same four-case supported selection then passed; failed reports remain preserved.
The runner removed only its disposable runa_test_test_1 and preserved base/dev data.

Controlled browser inspection of the served build passed PL/dark/1440px and
EN/light/390px. It checked all four module dialogs, an old Auth settings deep link,
exactly Overview/Integrations tabs, zero generic settings reads, working integration
selection/configuration and no page errors/document overflow. Screenshots and
report.json remain under .runtime/artifacts/playwright/runa/RM-2026-016-dedicated-settings/;
log: .runtime/frontend/RM-2026-016-dedicated-settings-browser.log. Synthetic account/
operation responses performed no application business writes. Browser contexts
closed and the temporary helper was removed.

Nginx /admin, /api/ready and all 16 entry-linked assets returned HTTP 200;
unauthenticated /api/admin/entry remains 401. Evidence:
.runtime/frontend/RM-2026-016-dedicated-settings-http.json. Current assets are ready
at http://localhost:8080/admin for repeated owner acceptance. No migrations,
application data changes or Docker resources were introduced/removed; active
services/data, knowledge/model caches and all reports remain preserved. Owner
acceptance 11b and final verification/release 12 remain open; no broad gate was run.

### Point 11a22 — Source-only optional integrations and a single module dialog view

The owner explicitly requests the same removal boundary as source-only health
policy (11a17). Optional integration behavior remains, with enabled/config values
owned exclusively by module source. IntegrationDefinition now has schema 2 and
explicit enabled/config parameters instead of override defaults. IntegrationProvider
retains pure source validation. ModuleIntegrations no longer writes, locks or reads
configuration from storage; version checks, both-side admission and final source
rechecking still guard use and async invocation. The manifest framework_api remains
1; this descriptor schema change belongs to the prepared platform 0.7.0 contract.
All real Core integration declarations are empty, so no module provider or upgrade
edge needs migration. Admin remains its unreleased creation candidate 0.1.0; no
independent module version change is required. Standing pre-1.0 authorization
covers removal of the Admin editor/export/presentation seam and old DTO names.

Removed the integrations read/configure HTTP operations, advertised capability and
source permission, editor component/public export and operationalEditor replacement
slot. The module dialog now renders one content view without Tabs or a local menu.
Old health/settings/integrations URLs retain module selection without editor reads.
Lifecycle reviews, diagnosis dialogs, task/operation history, guarded navigation and
identity/authority fencing remain. Application settings keeps its own editor/guards.
Retained permission dictionary/grant history follows the existing source-removal
policy; no assignment mutation or history cleanup was performed.

A new forward migration drops only shared.modules.integration_values and its
constraint, then advances registry generation. Local preflight identified the local
runa development database, no unresolved transitions and all four installed Core
versions. The exact column-removal scope was disclosed before application. Owner
forward migration passed in .runtime/frontend/RM-2026-016-source-integrations-forward.log;
postflight confirms the column is absent and module flags/installed versions are
unchanged. No reset or business-data/Audit/work deletion was performed.

Focused supported frontend checks passed 12 cases (390 excluded), native exit /
cleanup 0, in .runtime/frontend/RM-2026-016-source-integrations-frontend-final.log.
Coverage includes old module links without tabs/editor requests, direct close/focus
return, retained diagnosis/tasks and request/draft fencing. Focused isolated backend
checks passed 6 cases / 38 assertions, parallel/processes=1, native exit/cleanup 0,
in .runtime/frontend/RM-2026-016-source-integrations-backend-isolated.log: source
configuration/validation, absent override storage, async/final admission rechecks,
source synchronization and refusal of removed Admin operations without generation
mutation. The supported runner removed only its runa_test_test_1, preserving base
and development databases. Initial nonisolated execution encountered retained Core
state in the base test database; isolated execution resolved that fixture context.
A concurrent frontend invocation was refused by the suite lock. Initial UI checks
caught remaining editor references and a multiline translation value; both were
removed, then the same focused checks and build passed. Native failed evidence is
preserved; these checks do not claim broad gate PASS.

TypeScript/Vite/native-error asset preparation passed in
.runtime/frontend/RM-2026-016-source-integrations-build-final.log. Nginx /admin,
/api/ready and all 16 entry-linked assets return HTTP 200; unauthenticated
/api/admin/entry remains 401. Evidence:
.runtime/frontend/RM-2026-016-source-integrations-http.json. The local application
at http://localhost:8080/admin is ready for repeated owner acceptance.

Synchronized framework/database/module-authoring, Admin public frontend/operations,
loading/navigation/component guides and current Admin/Audit candidate changelogs.
Owner/customer AGENTS and skills already delegate these extension/presentation
rules to canonical guides and contain no removed editor procedure, so neither
instruction product needs duplicated rules. Relevant frontend/Vue/Tailwind,
backend/Laravel/security/PostgreSQL skills were loaded; local knowledge was current
and bounded retrieval was checked against original sources. No dependency,
runtime configuration or Docker resource change was made. Development/test data,
active services, model/Qdrant caches and all reports remain preserved. Owner manual
acceptance 11b and final verification/release 12 remain open.

### Point 11a23 — Application display defaults, uploaded branding and settings presentation

The owner selects default language/theme/timezone and uploaded logo with automatic
favicon plus an independent custom favicon. This explicitly extends point 10's
former deployed-assets-only scope; a generic media library remains excluded.
Source defaults are pl/system/Europe/Warsaw. ApplicationSetting adds validated IANA
timezone, and the source catalogue reserves six presentation keys, leaving 58 App
extensions. Public presentation projects only approved display defaults and selected
asset URLs. Existing account/browser language/theme overrides win; hydration does
not write preferences. UTC storage and calendar-only dates remain unchanged.

The container has no GD/Imagick. Reuse the established browser rasterization pattern
without a dependency/container change: bounded PNG/JPEG/WebP input becomes a
metadata-stripped PNG logo and transparent, aspect-preserving 64px square favicon.
Backend validates MIME/container metadata, dimensions and byte limits through the
shared platform raster helper extracted from Auth, without claiming full pixel
decoding. Public ApplicationImages and ApplicationSettings update signatures,
source placement, consumers and runtime delivery are documented before exposure.
Auth's private helper is removed; its unchanged parser now has a explicit public
platform consumption facade and explicit source-boundary inventory.

A forward migration adds three nullable bounded bytea columns to the existing
protected shared.application_settings singleton. Tiny branding assets and settings
publish under one existing aggregate revision, grants and explicit redacted Audit;
rollback cannot leave orphan filesystem objects. The paired logo/icon is written in
one SQL statement so its database invariant holds throughout. Custom favicon takes
precedence; resetting it restores the generated icon, and resetting logo restores
source branding. Current hash-addressed public PNG responses use fixed MIME,
nosniff and immutable cache headers; arbitrary paths, raw originals, remote fetching
and SVG upload are refused. Mail attaches selected PNG bytes through native CID;
normal error output receives the favicon, while static/emergency/503 assets stay source
without adding a maintenance HTTP exception.

AdminOperations now owns the Modules/Application settings main heading; duplicate
small headings are gone. Settings rows are bounded to 48rem; Restore defaults has
short PL/EN copy, a field-specific accessible name and adjacent desktop alignment,
with narrow-screen wrapping. Shared FileInput owns selection/drop/accessibility;
image preparation, previews, pending drafts and explicit Save belong to the settings
controller and remain available through its public replacement seam. Failed writes
retain images, remote image replacement conflicts even when the scalar choice is
still uploaded, and identity loss/disposal fences late conversions. Invalid files
remain dirty until corrected/reset. Native Auth/Admin instant displays and Admin
read-only/table/datetime editing use the public display timezone helper. Datetime
editing preserves UTC writes and blocks invalid/DST-gap/ambiguous drafts rather
than submitting the previous valid value; UTC exports remain raw owning data.

Focused supported backend execution passed 5 cases / 127 assertions, parallel /
processes=1, native exit/cleanup 0, in
.runtime/frontend/RM-2026-016-application-branding-backend-ready.log. Coverage:
strict IANA values, source catalogue/grants, fallback without storage creation,
unchanged canonical identity, atomic images/settings/Audit, stale writes, custom
favicon/reset precedence, fixed asset MIME/hash refusal, native CID and malformed
PNG rejection. Initial execution exposed the immediate paired-image constraint;
one-statement publication fixed it, then the same selection passed. The runner
removed only runa_test_test_1, preserving base/development data and failed reports.
A subsequent 1-case/49-assertion focused execution passed the explicit uploaded-brand
503 source fallback without a maintenance exception; evidence:
.runtime/frontend/RM-2026-016-application-branding-backend-maintenance.log.

Focused frontend execution and current TypeScript/Vite/native-error build are
recorded in .runtime/frontend/RM-2026-016-application-branding-frontend-accepted.log
and .runtime/frontend/RM-2026-016-application-branding-build-accepted.log. The focused
selection covers 10 cases: public presentation, preference precedence/timezone,
actual Canvas icon generation, conflict/file retention and image-overlap resolution,
late identity fencing, typed settings/default resets, page heading/adjacent controls
and UTC/DST-aware form editing. Initial UI assertions used a readonly fixture state
and the wrong shared Select selector; corrected to real identity loss and the public
trigger contract. These focused checks do not claim broad gate PASS.

A controlled inspection of the served build checked PL/dark/1440px and
EN/light/390px: one correct main heading, no duplicate heading, bounded input widths
(603px/300px), localized uploads/default controls, real image preparation/previews,
module navigation and no page errors/document overflow. Source/assets and isolated
synthetic account/operation responses were used, with no application business writes.
Evidence: .runtime/artifacts/playwright/runa/RM-2026-016-application-branding/ and
.runtime/frontend/RM-2026-016-application-branding-browser-final.log. Focused direct
browser orchestration was used to inspect current served assets rather than running
broad E2E acceptance before final verification. Existing E2E heading/reset assertions
were synchronized without running the broad suite.

Local read-only preflight identified environment local/database runa, no pending
transitions and absent image columns. The additive owner forward migration passed
in .runtime/frontend/RM-2026-016-application-branding-forward.log; no existing values,
images, Audit history or module state were reset. Nginx /admin, /api/ready and all
16 entry-linked assets return 200; unauthenticated Admin entry remains 401 and public
defaults are pl/system/Europe/Warsaw. Evidence:
.runtime/frontend/RM-2026-016-application-branding-http.json. Current assets/data are
ready for owner review at http://localhost:8080/admin?section=application.

Synchronized owning Admin/frontend/settings contracts, public API/database/image
and Auth preference/profile guidance, component references and candidate changelogs.
Owner/customer AGENTS and skills already defer these specific contracts to canonical
handbook guidance; neither contains an obsolete upload procedure or needs duplicate
rules. Admin 0.1.0/Auth 2.0.0 remain unreleased candidates in the same 0.7.0 creation/
transaction roadmap; no independent version-only upgrade edge is added for this
point. Release metadata is unchanged. No new Composer/pnpm dependency, runtime
configuration or Docker resource was introduced. Active services, development/base
test data, model/Qdrant caches and all evidence are preserved. Manual acceptance
11b and final affected-area verification/release 12 remain open.

### Point 11a24 — Searchable default timezone and concise settings copy

Enabled the shared Select searchable prop for timezone fields. Search filters the
existing authorized IANA options locally and retains shared PL/EN labels, keyboard
selection, focus restoration and settings draft/revision behavior. The owner also
removes the restart/reset introduction and both logo/favicon helper paragraphs;
removed their obsolete PL translations. No backend,
dependency, schema, source default or module version change. Local knowledge was
current; the source-verified component contract supplies this existing variant.
Frontend/Vue guidance applies. The canonical Admin frontend guide and candidate
changelog are synchronized; owner/customer instructions already defer to those
shared controls and need no duplicate rule. This reversible prop change adds no
implementation-mirroring test; existing shared Select search coverage remains.
Current asset preparation is recorded in
.runtime/frontend/RM-2026-016-timezone-search-build.log. No Docker resources were
created/removed and all data, caches and reports remain preserved. Manual owner
acceptance 11b and final verification/release 12 remain open.

### Point 11a25 — Shared operation and form feedback

Owner request, 2026-10-31: replace the settings editor's duplicate invalid/image
banners with the account panel's normal notification mechanism and audit remaining
views. Local knowledge was current and queried before focused source inspection;
the canonical forms/visual/component guides already require FormNotifications.

Application settings now uses shared Form and FormNotifications for read/write,
image and success outcomes, with one summary, deliberate-attempt identity and
explicit read retry. Field errors stay associated with controls; invalid submission
focuses the first invalid control without issuing a write. Logo/favicon preparation
failures remain independent, so selecting a valid favicon cannot erase a rejected
logo error. Only known branding failures become translated messages; unexpected
browser exceptions use the generic image-read failure. The redundant generic
settings warning and its translation are removed.

The focused source audit covered supplied Runa and App Vue views plus shared
feedback consumers. Migrated Admin module/diagnosis/lifecycle/history/resource
failure feedback, Auth/Audit/Permissions administrative read errors, Auth operation
proof/history-detail/welcome/session operation failures, shared Dialog.error and
TablePreferences outcomes. Preserve explicit recovery, modal portals and one
transport-error owner for operation history. Keep contextual admission/availability,
missing-adapter, session-expiry, field validation, reconciliation and durable
operation-result/progress displays; these describe state rather than duplicate
operation notifications. The App scaffold has no additional custom outcome banner.

Current Admin controller signatures/component guidance and all affected module
changelogs are synchronized. Existing prepared candidate versions remain Admin
0.1.0, Auth/Permissions 2.0.0 and Audit 1.1.0: these are corrections within the same
unreleased 0.7.0 candidate, with no version-only forward migration. Owner and
customer AGENTS/skills already prescribe the canonical feedback contract; neither
instruction product needs a new rule or duplicated implementation guidance.

Focused uncertainty checks: `python3 tools/maintainer/test-frontend
--filter='application settings|application branding|modal form notifications|caller-owned busy/error/retry|Admin operation proof'`
passes 14 tests across five files. Coverage includes invalid-submit focus/no write,
repeated dismissed image failure, independent image errors, read retry without a
parent duplicate and modal failure recovery. `pnpm --dir src run build` passes
TypeScript and prepares current browser/native-error assets; log:
`.runtime/frontend/RM-2026-016-feedback-build.log`. A one-off rendered diagnostic
against the existing Nginx endpoint used controlled API responses without data
writes: PL/dark at 1440px and EN/light at 390px each show one image notification
16px from the top/right, no horizontal overflow and no page errors. Screenshots
and report: `.runtime/artifacts/playwright/runa/RM-2026-016-feedback/`.
The temporary diagnostic script is removed; evidence is retained.

No dependencies, runtime configuration, migrations, database mutations or Docker
resources were introduced; active services, databases, caches and reports remain.
No broad verification was run. Owner acceptance 11b and final affected-area
verification/release 12 remain open.

### Point 11a26 — Mandatory feedback routing in frontend skills

Owner request, 2026-10-31: the handbook-only reference from 11a25 was insufficient
for consistent future implementation. Both owner and customer frontend SKILL.md
entrypoints now explicitly require shared public FormNotifications for every new
or changed view's form-wide and operation outcomes, with associated field errors
and no duplicate page-local banners. Both route to the existing canonical forms
contract before implementation, including repeat-attempt/recovery/modal requirements,
and distinguish contextual warnings/reconciliation/durable results. Provenance in
both SOURCE.md files identifies the local public contract; upstream pins remain.
Root/customer AGENTS already require shared patterns and frontend skills, so no
additional always-loaded rule or copied procedure is needed.

Local knowledge status/search were current and their original forms/component
sources were opened. Focused review confirms both skill entrypoints expose the
same rule and the owner-relative link resolves; the customer path is the supported
installed handbook path. Frontmatter and discovery metadata are unchanged.
The upstream skill-creator quick_validate command could not start because its
PyYAML dependency is absent; no dependency was installed. Markdown formatting,
staged repository hooks and knowledge freshness validate the documentation change.
No runtime code/assets or module versions change, so no application tests/build
are required. No Docker resources or data were created/removed. Manual acceptance
11b and final verification/release 12 remain open.

### Point 11a27 — Shared module browsers and table controls

Owner scope, 2026-10-31: inspect Auth/Permissions/Audit for each shared view defect;
compact desktop filters to six columns, use declared dictionary/boolean Selects,
filter on edits and reset through an icon/Tooltip. Shared Table now owns refresh,
reset, default in-memory column/views controls and bounded export/print composition.
Explicit preferences/export integrations retain their controllers. Actual owning
reads replace duplicate table refresh buttons across every supplied Table consumer.
Record browsers, Auth directory and generated table sections use shared Details
dialogs with owning dirty/busy close protection; creation remains accessible.

The owner explicitly selects one shared permission in this conversation:
`runa.permissions.tables.export`. It is additional to owning page/section/field
reads, native Policies and availability. Admin export reads reauthorize and remove
sensitive fields; account views use named authenticated grant preflight and repeat
it before export. Operational tables check it inside existing admission. Client
callbacks and file completion fence revoked/context-changing attempts. The default
export scope is Current loaded rows, not an all-cursor scan; CSV/XLSX/PDF and Print
reuse existing bounded generators. Broader custom server export scopes remain
provider-owned. No dependency or production configuration change is introduced.

Permissions advances independently from prepared 2.0.0 to compatible 2.1.0 for the
shared grant. New `table_exports` forward preparation synchronizes declarations
transactionally with Audit; no SQL/schema migration, prior-history rewrite or
ordinary-user assignment is added. Auth 2.0.0, Audit 1.1.0 and Admin 0.1.0 remain
unreleased candidates. Public API versions remain unchanged. Platform stays the
already approved feature/0.7.0 candidate.

Focused uncertainty evidence: six frontend cases pass in
`.runtime/frontend/RM-2026-016-tables-focused.log` (controlled filter/reset,
dictionary selection, guarded modal, exact entity filters, effective cursor and
revoked export). The backend denial/grant/revocation/sensitive-field regression
passes 1 case / 7 assertions, parallel processes=1, native exit/cleanup 0 in
`.runtime/frontend/RM-2026-016-tables-export-backend.log`. The runner removed only
`runa_test_test_1`, preserving the base and development database. An initial
concurrent invocation correctly refused the suite lock; it was rerun sequentially.
Earlier focused frontend failures exposed a recursive column/query loop and stale
English/inline-editor assumptions; the corrected selection passed. Reports remain.

The current asset build passes in
`.runtime/frontend/RM-2026-016-tables-build.log`, including TypeScript and native
error assets. The final optional-default correction explicitly includes undefined
under strict exactOptionalPropertyTypes. Two existing custom-adapter cases also
pass in `.runtime/frontend/RM-2026-016-tables-adapters.log`; authorized fixtures now
explicitly supply the projected export grant. Total focused frontend coverage is
eight cases, with no broad suite/gate claim.

Read-only local preflight verified local/database runa, no active transitions and
only Permissions `table_exports` pending, with no module migrations/other upgrades.
Implementation commit `d10bfc94` passed mandatory hooks. The supported forward
modules phase for that exact commit completed in
`.runtime/frontend/RM-2026-016-tables-forward.log`; Permissions source/installed
version is 2.1.0, availability is healthy, exactly one shared export declaration
exists and no transition remains. This preserves accounts/assignments/history.
No schema migration or data reset occurred.

Focused rendered inspection of current Nginx assets used controlled synthetic API
responses without business writes for all three module browsers at 1440px PL/dark
and Audit at 390px EN/light. Six desktop filters share one row (about 163px each);
real shared Selects, grouped trailing preferences/actions, owning refresh reads,
Details dialog/close and CSV authorization/download work. There are no page errors
or document overflow. Evidence:
`.runtime/artifacts/playwright/runa/RM-2026-016-tables/report.json` and screenshots,
`.runtime/frontend/RM-2026-016-tables-browser.log`. The diagnostic used native
close-dialog accessible names and explicit account locale/theme responses after
initial fixture corrections; earlier failure reports are not gate results.
Real module-page documents and /api/ready return 200, entry/export endpoints deny
unauthenticated requests with 401, and all entry-linked built assets return 200:
`.runtime/frontend/RM-2026-016-tables-http.json`. Current application is ready for
owner review at http://localhost:8080/admin. Owner acceptance remains pending.

Canonical tables/components, Admin, Auth profile, Permissions and module/central
changelogs are synchronized. Both owner and customer frontend references now route
sibling-view review and export authorization to canonical guidance; customer edits
remain application-only. Root/customer AGENTS need no duplicate component rule.
No task-created Docker container/network/image/volume exists; preserve active
services, development/base-test data, Qdrant/model caches and all evidence. Manual
acceptance 11b and final affected-area verification/release 12 remain open.

### Point 11a28 — Filter-local reset and formatted module dates

Owner follow-up, 2026-10-31: keep reset beside filtering inputs and correct table
calendar values. The shared labelled filter region now owns default/slot controls,
search and the single icon-only Reset filters/Tooltip. Six desktop filter columns
remain; page size/preferences/export/refresh stay in the separate table toolbar.
Reset semantics are unchanged, including controlled queries and saved views.

Source review found Audit occurred_at incorrectly declared text. It now declares
datetime, activating AdminResourceTable's existing locale/application-timezone
presentation. Audit detail values use that same formatter. Auth bounded admin
entry lists format their known timestamp keys, including session activity; account,
trusted-device and operation-history tables already use the shared formatter.
Permissions definition/effective tables have no timestamp columns. Raw UTC instants
remain in transport and table export descriptors; no date-looking free text is
heuristically converted. No dependency, schema, grant or module/API version change
is required within the current unreleased candidates.

Focused frontend coverage passes four cases for reset placement/behavior, Audit
formatted cursor/entity history and payload continuation, and Auth bounded entry
selection in `.runtime/frontend/RM-2026-016-filter-date-focused.log`. Current
TypeScript/Vite/native-error asset preparation passes in
`.runtime/frontend/RM-2026-016-filter-date-build.log`. Focused served-build
inspection uses controlled synthetic API responses for Auth/Permissions/Audit at
1440px PL/dark and Audit at 390px EN/light. It confirms the filter-local reset,
six inputs per desktop row, localized Warsaw time (18:25Z displays 19:25), usable
Details/refresh/CSV and no page errors/document overflow. Evidence:
`.runtime/artifacts/playwright/runa/RM-2026-016-filter-date/report.json`, screenshots
and `.runtime/frontend/RM-2026-016-filter-date-browser.log`.

Canonical tables/Admin guides and all affected current module changelogs are
synchronized. Both instruction products already route shared tables and sibling
module review to those guides; no duplicate skill/AGENTS rule is needed. No data
migration, business write, reset or Docker resource creation/removal occurred.
Preserve active services/databases, model/Qdrant caches and evidence. Owner manual
acceptance 11b and final automated verification/release 12 remain open.

### Point 11a29 — bounded infrastructure reads and preventive performance review

Removed repeated registry rebuilding under caller-owned transactions. The first
snapshot holds the existing singleton read lock; read-only descendants reuse it.
Connection callbacks invalidate before writes/raw statements independently of
database events; transaction levels and PDO identity fence rollback, reused
generations, replacement transactions and reconnection. Committed calls outside
transactions still check persisted generation. Health expiry and current actor
eligibility remain separate evaluations. Registry/authority/proof lock order is
unchanged; the existing exclusive Admin entry lock is intentional under that
protocol and was not weakened for speed.

Admin page metadata deduplicates permission checks per owner within one synchronous
projection, and resource field intersections do likewise locally. Decisions never
survive into owning handler execution or another operation. Native reads/writes,
record Policies, proof and current grants remain enforced.

The related-consumer review found and repaired two additional Auth cases:
presentation snapshots queried/revalidated every preference, and delivery
snapshots queried/revalidated every category. Presentation reads now validate
provider declarations once and fetch at most 1024 declared scopes in one account
query; delivery reads validate once and batch declared categories per available
module/provider. Both retain defaults, old-value migration/validation, account
isolation and recipient restrictions. Audit event/entity projection and own
history, Auth directory projection, Permissions explain/assignment loading,
settings definitions and Admin discovery/diagnostics were inspected. Their existing
bounded/batched collection reads remain; nested registry consumers benefit from
the shared fix. No speculative SQL indexes, infrastructure or permission caches
were added.

Before/after diagnostics used the same existing local principal, selected data,
default-connection transaction nesting and projection sequence. Original versions
were loaded from HEAD into an isolated diagnostic process without replacing
tracked/runtime files. Logs contain counts/timing/table families only, with zero
write statements, and both transactions rolled back. These are internal-stage
measurements, not complete browser/HTTP/session/proof timings; wall time is
environment-dependent and does not establish a latency SLA.

| Stage                         | SQL before → after | Wall time before → after |
| ----------------------------- | ------------------ | ------------------------ |
| Audit page metadata           | 3829 → 21          | 1394 → 103 ms            |
| Audit history, 25 rows        | 1760 → 31          | 563 → 94 ms              |
| Permissions roles metadata    | 3636 → 35          | 1121 → 100 ms            |
| Auth users metadata           | 15687 → 182        | 6209 → 350 ms            |
| Admin bootstrap               | 906 → 56           | 483 → 118 ms             |
| Repeated Audit metadata       | 3449 → 21          | 1361 → 72 ms             |
| Account presentation snapshot | 124 → 1            | 38 → 6 ms                |
| Account delivery snapshot     | 151 → 1            | 46 → 4 ms                |

Evidence: `.runtime/performance/RM-2026-016/{profile.php,before.jsonl,after.jsonl}`.
Focused supported backend runs resolved cache/freshness and projection uncertainties:
five registry scenarios passed (199 assertions), six projection/preferences cases
passed (77 assertions), six current-grant/proof/delivery/health scenarios passed
(88 assertions), and locked-snapshot health expiry passed (four assertions).
The presentation regression doubles entries from four to eight with one SQL read
and compares every value with the single-scope read; delivery covers stored/default,
mandatory recipient and account isolation behavior. Registry coverage includes
100 repeated reads, nested read savepoints, same-connection writes, commit/rollback
generation reuse, suppressed events and reconnect. Reports:
`.runtime/frontend/RM-2026-016-registry-cache-focused.log` and
`.runtime/performance/RM-2026-016/{reads-focused,security-freshness-focused,health-expiry-focused}.log`.

Canonical application performance, registry, Admin projection and Auth preference
guides and current changelogs are synchronized. Owner and customer backend skill
entrypoints/references route to the same preventive procedure: measure query growth
beyond relations, review siblings and repair demonstrated authorized defects;
customer repairs stay App-owned. Provenance records this as local guidance, with
no upstream/dependency refresh. Existing AGENTS already owns workflow/security and
needs no duplicated rule. This is an internal performance correction to unreleased
Admin 0.1.0/Auth 2.0.0 candidates and the platform framework; public signatures,
manifests and independent Permissions/Audit versions do not change.

No migration, development business write or Docker resource creation/removal was
needed. Supported runners cleaned only disposable `runa_test_test_1` workers;
development/base test data, services, caches and reports remain preserved. No broad
verification was run. Owner manual point 11b and final verification/release 12 stay
open.

### Point 11a30 — application-wide amplification review and repairs

Expanded the review beyond point 11a29's selected Core/framework reads. A static
inventory classified 574 runtime-eligible PHP files into 93 source areas and
located frontend read/watch/request sites across the supplied App and Runa source.
Those inventories guided source tracing of the entrypoint families below; file
counts are navigation evidence, not a claim that every file was manually read or
that every workload was benchmarked. Knowledge was current initially, then became
stale after the roadmap edit; focused original-source review continued without
bootstrapping/rebuilding optional knowledge services.

| Runtime area                       | Reviewed paths and disposition                                                                                                                                                                                                                                                                                                                                                   | Evidence level                                                                                             |
| ---------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------- |
| Ordinary App/reference             | App root/provider/routes, sample module PHP/HTTP/frontend, settings consumer and explicit click loading; no extra repeated-read defect demonstrated                                                                                                                                                                                                                              | Source                                                                                                     |
| HTTP startup/routes                | ModuleHttp and module-page descriptors repeatedly polled registry during class loading; use locked metadata scans with per-provider savepoint isolation                                                                                                                                                                                                                          | Source, cold bootstrap measurement, named/deep-route and failed-provider regressions                       |
| CLI/scheduler registration         | Commands and schedules share the bounded metadata scan; actual callbacks still re-admit current declarations and authority                                                                                                                                                                                                                                                       | Source, command/schedule source-change regressions                                                         |
| Middleware/maintenance/health      | Account policy, maintenance/readiness, request guards and health/expiry paths; keep current admission and on-use checks, benefit from shared settings fix                                                                                                                                                                                                                        | Source, native proof/source/expiry regression scenarios                                                    |
| Identity/presentation/assets       | Identity is source-only; presentation formerly transferred all encoded images, including unrelated assets; fetch hashes/presence and only the selected image                                                                                                                                                                                                                     | Source, public stage measurements, real uploaded-asset/hash/revision/Audit regression                      |
| Auth entry/security/session        | Repeated full settings loads and duplicate current-session lookup; reuse validated settings under the caller's locked snapshot, scope public projections/password rules and resolve current session once                                                                                                                                                                         | Source, isolated-stage measurements, entry/proof/session integration                                       |
| Profile/preferences/history        | Profile capabilities repeatedly loaded policy; fixed shared reader. Preferences/delivery retain point 11a29 batching; account/Audit history, email and trust/session lists are bounded native collection reads                                                                                                                                                                   | Source, stage measurements, preferences and avatar/native account regressions                              |
| Settings/application configuration | Repeated per-field grant checks in value/definition/prewrite intersections; deduplicate only the current phase. Keep current subsequent guards and reject stale writes                                                                                                                                                                                                           | Source, isolated-stage measurements, real branding grant/revision/Audit regression                         |
| Auth bulk operations               | Session/trust inventories queried per account; batch bounded inventories and revocations, retain per-account Audit/current selection/proof/fingerprint and row caps                                                                                                                                                                                                              | Source, one/four-account query-growth and stale/Audit rollback regressions                                 |
| Permissions                        | Reviewed dictionaries, assignments, effective grants, declarations and role deletion. Batch recipient assignment/revision reads, reuse identical reference sets within one preview and bulk-write pivots                                                                                                                                                                         | Source, one/two-recipient query-growth and native declaration/assignment/force-delete rollback regressions |
| Audit                              | Event/entity and own-history queries remain bounded/batched, with current grant/redaction checks; no further distinct collection-read defect found beyond shared readers                                                                                                                                                                                                         | Source and earlier point 11a29 evidence; not an independent new full Audit suite                           |
| Module operations/work             | Work lists called status per row, including no-op expiry writes and repeated job declaration reads; fetch safe columns together, validate capabilities once per module and batch selected expired leases                                                                                                                                                                         | Source, 12-item query/expiry regression and existing crash recovery scenario                               |
| Background/cleanup                 | Reminder history/count/max and account exceptions repeated per candidate; batch the locked cohort/tuples. Avatar cleanup deletes successful candidate metadata together. Session cleanup is already bounded/set-based; scheduled lifecycle/Audit and event/worker per-subject admission remain deliberate                                                                        | Source, cohort/reminder, cleanup, delivery and native history regressions                                  |
| Frontend request owners            | Traced public presentation, welcome/entry, shared session/Admin advertisement, account/profile/lists/preferences/delivery, Admin page/resource/table/form/operations and shared HTTP/table providers. Fixed overlapping public-presentation reads with a trailing invalidation; retain account trailing reconciliation, dirty drafts, bounded paging and required distinct reads | Source, browser component reproduction/regression, current asset build                                     |
| Installation/development support   | Classified source/manifest/settings/declaration preparation, configuration/seeding/storage and technical health entrypoints. Per-module transitions, subject validation, Audit and external storage effects retain their owning boundaries                                                                                                                                       | Source; no new installation/deployment/quality-suite PASS claim                                            |

The shared settings reader now keys validated declarations/values/revision by the
registry's locked snapshot identity in a WeakMap. It anchors a caller-owned read
before a child savepoint, avoiding repeated key loads while retaining database
failure rollback isolation. Same-connection writes, source changes, transaction/
savepoint rollback or replacement and reconnect prevent stale reuse; source is
still verified on every hit. No account eligibility, permission decision or
time-dependent override is retained. Runtime and public presentation signatures
are unchanged, and secret settings remain redacted in management projections.

Cold source registration uses one short registry-locked metadata transaction with
provider savepoints. A failing declaration SQL query is isolated without poisoning
healthy modules; controller/command/job execution remains outside registration.
Work listing reads neither encrypted payload nor private lease token. Expiry only
updates the selected still-expired running leases, then rereads them; renewed work
is preserved and no automatic replay is introduced.

Reminder aggregation preserves exact account/category/deadline counts and latest
timestamps, current lifetime exceptions and duplicate/frequency/expiry checks.
Per-notice guarded dispatch still rechecks its own identity/authority. Permission
and session batching retains per-recipient bounds, ordered preview fingerprints,
current Auth eligibility and per-entity mandatory Audit. Failed avatar storage
deletions retain their exact durable candidates. No security checks or business
effects were removed merely to make query counts constant.

Fresh default-connection diagnostic transactions per stage used the same existing
local principal and data; original ModuleSettings/SettingStore versions were
loaded from HEAD in an isolated baseline process. Only counts/timing were emitted,
with zero business write statements and rollback. Cold HTTP bootstrap was measured
separately, also with zero write statements. These are internal/bootstrap stages,
not full Nginx/browser request latency, realistic load tests or an SLA.

| Stage                           | SQL before → after | Approximate wall time before → after |
| ------------------------------- | ------------------ | ------------------------------------ |
| Cold HTTP route bootstrap       | 77 → 10            | 117 → 92 ms                          |
| Profile projection              | 39 → 6             | 149 → 25 ms                          |
| Public entry options            | 48 → 5             | 51 → 11 ms                           |
| Credential policy projection    | 48 → 5             | 74 → 7 ms                            |
| Application setting values      | 59 → 18            | 84 → 25 ms                           |
| Application setting definitions | 106 → 23           | 168 → 25 ms                          |

Browser reproduction observed four overlapping presentation requests before repair.
The corrected lane has one in-flight read and at most one coalesced trailing read
for overlapping focus/publication invalidations; obsolete results cannot publish.
The browser regression validates both request counts and the newest rendered name.
Asset transfer reduction is established from selected SQL columns and real byte/
hash functional assertions, without claiming a large-image performance benchmark.

Evidence lives under `.runtime/performance/RM-2026-016/`: source/read inventories,
`profile-{boot,surfaces,app}.php`, `boot-{before,after}.json`,
`surfaces-{before,after}.jsonl`, browser overlap before/after logs and focused native
reports. Final relevant targeted runs passed: settings/read/entry/bulk/reminder/work
six cases/121 assertions; assignment/declaration/role-deletion/avatar seven/118;
startup isolation/routes/commands/schedules five/36; browser presentation four.
The final settings/source-freshness and secret-projection rerun passed two cases/
76 assertions after strengthening cached source checks for provider/handler paths.
Earlier supporting runs remain preserved rather than replacing their native
failure/retry records. The current production/client and native error-shell asset
build passed. These focused checks resolved concrete changed-path uncertainties;
no broad verification graph or complete backend/frontend suite was run early.

Current framework, Auth/Permissions/Admin, application performance and frontend
loading guidance/changelogs are synchronized. Both owner/customer backend and
frontend skill entrypoints/references now route application-wide scope, query/
request growth, immediate authorized repairs and explicit coverage reporting to
canonical guides. Root AGENTS already owns authority/workflow and needs no duplicate
rule. Existing provenance remains local Runa research, without upstream refresh or
new dependencies. No public signature, migration, independent released version or
compatibility adapter was added; corrections remain within unreleased Auth 2.0.0,
Permissions 2.1.0 and Admin 0.1.0 candidates and the 0.7.0 platform.

Review limits remain explicit: no assertion of defect-free arbitrary customer
extensions, future data volumes or contention-free execution. Existing registry/
authority lock order and intentional per-account security/Audit work remain in
force. Development/base test data, services, caches and reports were preserved;
supported runners removed only their disposable `runa_test_test_1` databases and
owned temporary browser resources. No real production deployment or business data
reset occurred. Owner acceptance 11b and final verification/release 12 remain open.

### Point 11a31 — Shared protected-section idle access

Owner approval on 2026-10-31 replaces fixed-age Admin admission with reusable
Auth-owned section idle access: deliberate navigation/clicks/typing/scrolling in
that admitted section renews its own still-valid deadline. Activity elsewhere,
background requests, pointer motion and programmatic scroll never renew it.
This is an amendment to the active Admin acceptance repairs; confirmed platform
feature/0.7.0 metadata stays unchanged, with no second active roadmap.

AuthStepUp API 1.1.0 supplies assertActive and activity; source-owned endpoints
choose the action/requirement/TTL and recheck current section grants/availability.
Separate activity_at/idle_expires_at retain original credential verified_at and
expires_at; captured/current/caller TTL, principal/native session/security version,
restriction/factor and consumed-proof checks remain enforced under native locks.
Activity metadata rolls back with its owning transaction and cleanup preserves
still-live section access. Confirmation/consumption/business Audit remains native;
renewal is technical session metadata, not a per-input Audit event.

Public useProtectedActivity composes the caller's admitted identity and protected
root. Trusted click/pointer press/typing/wheel/touch input is batched at the bounded
server interval (at most 30 seconds); automatic reads/focus/visibility never renew.
Scope/identity/access loss and hidden documents cancel pending input and fence late
responses. Deadline/visibility checks read server state, allowing another tab's
legitimate activity without trusting local timestamps. Requests are bounded to ten
seconds. Bootstrap status avoids another initial read and does not reset throttling.
Admin uses the admitted /admin document including teleported dialogs/navigation;
embedded consumers choose narrower roots through the same public controller.
Native aggregate MutationProof now explicitly supports non-consuming idle assertions;
fixed-age and single-use owning operation proof remains separate in both phases.

The focused post-five-minute aggregate scenario exposed an existing missing
permissionChecks closure argument in prepareAggregate after point 11a29's projection
refactor. Pass the per-owner phase-local checker as the normal page projection does;
no authority cache, compatibility adapter or changed business premise was added.
The first extended fixture also attempted to enroll an already enrolled TOTP method;
corrected it to confirm the existing factor instead. Native failed outputs remain
in the conversation/tool evidence; successful focused reruns supersede them without
claiming a full suite or final gate.

Auth advances 2.0.0 -> 2.1.0 with a new section_activity forward edge and additive
nullable timestamps; existing migration/upgrade history is unchanged. AuthStepUp
exact-version consumers are updated to 1.1.0. Permissions stays in its already
unreleased 2.1.0 candidate and Admin in 0.1.0; platform candidate remains 0.7.0.
Public signatures/examples, native transaction/API boundaries, current Auth/Admin
guides/changelogs and central candidate changelog are synchronized. Owner/customer
security and frontend references route to the canonical Auth contract. Existing
root AGENTS already owns authorization/lifecycle and needs no duplicated rule.
No dependency, upstream refresh or new infrastructure was introduced.

Focused evidence and runnable preparation follow in this point; no full automated
gate has been run early.
Owner manual acceptance 11b and final automated verification/release 12 remain open.

Focused uncertainty checks passed: seven native cases/105 assertions cover section
renewal versus fixed-age proof, no-read renewal, action/requirement/principal
isolation, exact expiry, rollback, current policy tightening/security generation,
cleanup preservation, Admin HTTP policy ownership and real aggregate Save beyond
the original credential deadline. Eight frontend cases cover trusted input/batching,
scoped roots, synthetic input, identity/access/hidden cancellation, obsolete replies,
server expiry reads, throttle preservation during bootstrap and existing Admin/proof
integration. These are selected regressions, not complete suites. Backend evidence:
`.runtime/frontend/RM-2026-016-section-activity/backend-focused.log`;
frontend evidence is the supported native test result (8 PASS, exit/cleanup 0).
The runner removed only its disposable runa_test_test_1 database and preserved the
base/development data; no Docker resources were created by this point.

Read-only runtime inspection identified local database runa, 15 retained accounts,
Auth installed 2.0.0, no idle columns, no reserved queue jobs, active/recovery work,
module transitions or updater reservation, and no pre-existing manual maintenance.
The explicit next preparation scope is one additive module migration, its retained
2.0.0 -> 2.1.0 edge and source synchronization; no reset, credential change, fixture
replacement or production operation is needed. Preserve all data/caches/reports.

Runnable preparation completed from implementation commit
`324b13f42439e64823bdfb9334dae76bff8d91c4`. The supported asset build initially
found that root-owned tests could resolve direct vitest/browser at runtime but not
through TypeScript's src toolchain boundary. Export native page through the existing
@runa/testing/vitest facade, consume that facade in the regression and document its
browser-only use. No dependency or compiler exception was added. The repeated full
supported asset build, including vue-tsc and native/static error shells, passed;
current evidence is build-2.log under the point's artifact directory. The repeated
selected frontend run passed eight cases (frontend-focused.log). Mandatory hooks
passed staged secrets, formatting, quick lint, repository sanity and knowledge
freshness. Hook formatting/index freshness and an unused test lint suppression
were resolved before the implementation commit, without bypassing checks.

Native task-owned maintenance fenced writers; repeated inspection confirmed zero
reserved jobs, active/recovery work, transitions or updater reservations. The narrow
forward preparation modules phase applied exactly the new Auth migration and
section_activity edge. The first invocation mistakenly used operation identifier
`324b13f4f4d0c639b6a490f3c09cf00000000000` instead of the actual full commit above.
Its executed source was the committed implementation and its content fingerprints
are valid; completed native ledgers retain that original operation identifier rather
than rewriting historical evidence. A second supported preparation using the exact
real commit succeeded with no repeated migration/upgrade and synchronized current
source state. Preserve forward.log and forward-source-correction.log as evidence of
the mistake and correction; no unresolved update reservation or data repair remains.
Only this task's manual maintenance was removed and queue restart was signalled.

The resulting local database runa retains all 15 accounts; Auth is installed 2.1.0,
Permissions 2.1.0, Audit 1.1.0 and Admin 0.1.0. Both idle columns exist, all operation
reservations are clear and manual maintenance is false. No database reset,
credential/permission mutation, fixture replacement or production operation occurred.
Readiness through actual development Nginx/PHP-FPM returned 200 for /, /admin,
/api/ready, /api/presentation, /api/runa/auth/entry-options, /api/modules/frontend
and /build/runa-components.json. Anonymous /api/admin/entry and /api/admin/activity
returned 401. The served component manifest equals the current local build.
Evidence: runtime-before.json, runtime-after.json, http-readiness.json and retained
served manifests in `.runtime/frontend/RM-2026-016-section-activity/`.

Ready for repeated owner acceptance at http://localhost:8080/admin using the existing
account/password and native entry proof. Check active typing/navigation/scrolling
beyond the configured idle interval, then actual inactivity and reconfirmation;
automatic reads/open or hidden tabs must not keep the section alive. This readiness
is not authenticated owner acceptance or final quality PASS. Point 11b stays open;
final automated verification/release remains point 12. No Docker containers, images,
networks or volumes were created/removed. Supported tests removed only disposable
worker databases; development/base-test data, all services/model/Qdrant caches and
verification evidence remain preserved.

### Point 11a32 — Shared pinned table headers and typed timestamp filters

Owner approval, 2026-10-31: keep page scrolling, pin table headers together with a
horizontal scrollbar directly below them, and replace Audit from/until plain text
filters with the existing calendar/time picker. Review all sibling module views.

The shared Table retains one original semantic thead and its sorting/selection
controls. A private viewport composable bounds its vertical translation to the
table, accounts for the Application header or enclosing scrolling dialog, observes
resizes and disposes listeners/animation frames. The below-header native scrollbar
synchronizes both ways with the table region; trackpad/touch/keyboard access stays
available. Narrow tables omit the rail. No copied headers or vertical table scroll
container were introduced.

The public optional TableColumn.filterType datetime descriptor composes the
existing DateTimeInput using application timezone and PL/EN copy. Invalid drafts
remain local; reset also clears uncommitted drafts. Every Admin collection maps
declared datetime filters through that descriptor. Audit from/until now declare
datetime. Focused source review covered Auth, Permissions, Audit and Admin module
views/declarations: no other declared timestamp filter remains text, and module
record tables already compose the shared Table. Auth/Permissions currently have
no additional timestamp filters requiring declaration repair. Their generic
collection mapping is covered alongside Audit rather than adding redundant
module-specific controls. Admin 0.1.0 and Audit 1.1.0 remain the current unreleased
candidates; no new module/public backend API version or upgrade edge is required.

Eight focused native-browser frontend cases pass (exit/cleanup 0), covering zoned
UTC conversion, invalid/reset drafts, shared datetime controls in three module
owners, bounded header movement and both horizontal directions, existing semantic
sorting/selection and filter reset/revalidation. A stale existing filter regression
still expected inline error text after the earlier toast migration; its assertion
now checks the rendered notification portal. Evidence:
`.runtime/frontend/RM-2026-016-table-viewport-focused.log`. The supported asset
preparation (vue-tsc, Vite and native/static error shells) passes in
`.runtime/frontend/RM-2026-016-table-viewport-build.log`.

Focused served-build inspection uses synthetic API responses for 25 rows in
Auth/Permissions/Audit at 1440px PL/dark and 390px EN/light. All six views open the
date/time picker and Details, refresh and export, retain headers immediately below
the actual shell, synchronize horizontal movement and show no document overflow
or page errors. Native scrollbars were enabled explicitly in headless Chromium
for visual inspection. Evidence: `.runtime/frontend/RM-2026-016-table-viewport-browser.log`
and `.runtime/artifacts/playwright/runa/RM-2026-016-table-viewport/` (JSON report and
screenshots). These controlled previews are not authenticated owner acceptance.
Real Nginx readiness returns 200 for /admin, /api/ready, /api/presentation and the
component manifest; anonymous protected entry/activity return 401. Served and
local asset manifests match; see `RM-2026-016-table-viewport-readiness.json` in the
same frontend evidence directory.

Public table/Admin/Audit guides and current changelogs are synchronized. Both
instruction products already route table and sibling-module work to these public
contracts; root AGENTS and owner/customer skills need no duplicate procedure.
No dependency, database migration/reset, business write or Docker resource
creation/removal occurred. Preserve development/base-test data, services,
Qdrant/model caches and all reports. Ready for repeated owner acceptance at
http://localhost:8080/admin with existing credentials. Manual acceptance 11b and
final verification/release 12 remain open; no broad quality PASS is claimed.

### Point 11a33 — Application-wide temporal consistency and durable guidance

Owner request, 2026-10-31: review the whole system's timezone handling, use one
application-settings-driven conversion system and remove timezone annotations
from inputs. The owner additionally requested durable skills guidance.

Source review covered App and all delivered module/shared date consumers, public
presentation/default hydration, calendars, Auth history/session/device lists,
Admin widgets/health/operation history/forms/tables, Audit detail/filter views,
canonical exports, backend settings validation, Laravel/PostgreSQL timezone
configuration and operational/session deadlines. The current App scaffold has no
independent temporal presentation to migrate. Distinguish this source review from
focused runtime evidence below; this is not an all-group verification claim.

The shared instantTimestamp helper now owns epoch parsing of explicit-offset ISO,
unzoned UTC backend timestamps and PostgreSQL hour-only offsets, independently
of browser timezone. It rejects impossible/date-only text. Public signatures and
source homes are documented in the canonical forms guide before consumer use.
Auth account/security lists and session epoch parsing use that helper; the latter
never reads display settings. Existing framework/storage/cron/log clocks remain
UTC and canonical exports remain epoch/UTC based. XLSX's installed serial-number
conversion was source-reviewed and uses Date.getTime, not browser-local parts.
No new dependency, migration, mutable Audit record or server security policy was
introduced. Current unreleased Auth 2.1.0/Admin 0.1.0 and platform 0.7.0 candidate
versions remain appropriate; module API/upgrade edges are unchanged.

DateTimeInput now defaults reactively to application timezone, retaining an
optional explicit domain-zone override. Remove zoneLabel and automatic helper/
picker annotations, consumer wiring and the scenario's hardcoded Warsaw zone.
Auth lifecycle/exception input labels no longer misleadingly ask for UTC input.
Invalid local drafts survive a settings timezone refresh, including before the
first valid edit. Date-only values stay calendar dates; calendar today and footer
year follow application timezone across day/year boundaries. Admin's fallback
widget observation and known before/after Auth history dates now use the common
formatter. Ordinary text and arbitrary diagnostic/Audit JSON are not inferred as
dates. All supplied typed instant displays use the shared formatter; validation and
canonical machine datetime attributes retain their owning contracts.

Seventeen focused frontend cases pass (exit/cleanup 0), covering UTC/backend
parsing, PostgreSQL +00, invalid calendar dates, midnight/year boundaries,
New York/Kiritimati/Kathmandu presentation, settings refresh without lost drafts,
unchanged date-only models, default datetime controls across three owners,
DST gaps/overlaps, history/widget formatting, session deadline independence,
session sorting/loading and activity throttling. Evidence:
`.runtime/frontend/RM-2026-016-timezone-focused.log`. Current supported asset
preparation passes vue-tsc, Vite and native/static error shells in
`RM-2026-016-timezone-build.log` in that directory. One prior transport ended 143
after writing build completion; preserve RM-2026-016-timezone-build-interrupted.log, and use the
subsequent complete native exit-0 build as acceptance evidence.

A direct focused served-build browser diagnostic fills the existing gap for
controlled API fixtures on the running development endpoint; it does not replace
the supported full suites. Six Auth/Permissions/Audit views at 1440px PL/dark and
390px EN/light use browser zones Kiritimati/Los Angeles while public application
presentation starts in New York and changes to Kathmandu. Inputs convert 12:00
to canonical 17:00Z and re-render 22:45 after refresh; displayed 00:30Z changes
from prior-day 19:30 to 06:15. No timezone helper/picker text, page errors or
horizontal document overflow appears. Picker/Details, refresh/export and shared
header/scroll behavior remain usable. Synthetic fixture responses perform no real
setting/business writes. Evidence: `.runtime/frontend/RM-2026-016-timezone-browser.log`
and `.runtime/artifacts/playwright/runa/RM-2026-016-timezone/` (JSON/screenshot
reports). Real Nginx readiness returns 200 for Admin/readiness/presentation/assets,
401 for anonymous protected entry/activity, and the served/local manifests match;
see `.runtime/frontend/RM-2026-016-timezone-readiness.json`.

Canonical frontend architecture/forms/component and Admin guides plus current
changelogs are synchronized. Owner and customer frontend SKILL.md now direct date
work to the one public contract and shared helpers, require unchanged date-only
semantics/no timezone notes, and require sibling-consumer review. Root AGENTS and
other domain skills already retain UTC storage/security and protected-platform
boundaries; no extra always-loaded policy or duplicated procedure is needed.
The upstream quick skill validator cannot import its optional PyYAML dependency;
no package was installed. A focused stdlib check verifies unchanged frontmatter
and source-backed guide anchors, alongside normal mandatory hook validation.

No Docker resources, database reset/migration or credential/grant changes were
created by this point. Preserve development/base-test data, active services,
Qdrant/model caches and evidence, including the interrupted build log. Current
assets are ready at http://localhost:8080/admin with existing credentials. Owner
manual frontend acceptance 11b and full verification/release 12 remain open.

### Point 11a34 — Compact temporal filters, synchronous pinning and exact totals

Owner request, 2026-11-01: repair oversized date filters, header jumping during
page scrolling and missing/unconfirmed result counts. Local knowledge was current;
bounded current-intent retrieval pointed to the public table/Admin cursor contract,
which was opened alongside the owning implementation. Relevant frontend/Vue/
Tailwind/backend/Laravel/PostgreSQL skills and Laravel 13 aggregate documentation
were consulted. No new dependency, schema or authorization decision was needed.

TemporalInputProps adds optional size sm/md (default md). DateInput, TimeInput and
DateTimeInput forward the selected size to their shared text control and picker
trigger; built-in datetime table filters use sm like text/select filters. The
viewport composable now applies the same translate3d directly to the original
semantic header and rail in the passive scroll event. It eliminates the extra
animation frame and Vue render from vertical movement, caches scroll ownership
on geometry changes and measures column/overflow geometry only on resize. Bounds,
Application/dialog offsets, horizontal synchronization and listener cleanup stay
in the shared owner. Native sticky alone cannot cross the existing horizontal
scroll container while retaining the same table/header layout.

TablePage adds optional pagination numbered/cursor, independently of count
accuracy. Admin uses cursor and marks owner totals exact when non-null, including
zero; nullable custom-owner counts remain unknown. Exact counts retain the public
selection contract, current provider authorization and query-bound snapshots.
Audit counts with all scalar/entity/time filters before append cursor/limit;
Auth counts the authorized search/state dataset before its UUID cursor/limit;
Permissions counts non-deleted definitions before cursor/limit. Each reader adds
one SQL aggregate per page, without downloading records or cross-module queries.
Adapters forward the owning values through ManagementPageResult. Totals describe
the current query, not a cross-request historical snapshot. AuditPage and
UserDirectoryPage add an optional nullable total; PermissionsAccess.definitions
adds total to its result. Guarded method signatures/API resolution versions stay
unchanged. Assessed unreleased module candidates already cover these compatible
capabilities: Admin 0.1.0, Audit 1.1.0, Auth/Permissions 2.1.0; no additional forward
edge or installed-data transformation is required.

Focused native Chromium cases pass (3 cases, cleanup 0): compact temporal controls
and UTC draft/reset handling, immediate pinning before a Vue tick/next frame with
both scroll directions/table-end bounds, and exact 900-result cursor navigation
without arbitrary numbered jumps or the unconfirmed label. Evidence:
`.runtime/frontend/RM-2026-016-table-repairs-focused.log`. The initial detached
control fixture could not measure styles; attaching it to document.body corrected
the test environment. Backend Audit and Permissions focused cases pass; the Auth
count/authority case passes after correcting its fixture deletion to the owning
lifecycle Action. Tests cover filtered/empty/entity/time totals, invariant totals
across cursors and denied deleted-state access. Evidence:
`RM-2026-016-table-counts-backend.log` (two passing cases and the original fixture
failure), `RM-2026-016-table-counts-auth.log` (Auth pass), under `.runtime/frontend/`.
The initial serial attempt was refused by absent-schema guards on the preserved
base test database; the supported one-worker parallel mode provides disposable
isolation. Runner cleanup removed only runa_test_test_1 and preserved runa_test.

Supported asset preparation passes (vue-tsc, Vite and native/static error shells):
`.runtime/frontend/RM-2026-016-table-repairs-build.log`. The initial typecheck found
missing size defaults in public temporal wrappers; all three now declare md.
A focused one-off served-build browser diagnostic was used because the normal
suite does not provide synthetic Admin response/geometry probes. Six controlled
Auth/Permissions/Audit views at 1440px PL/dark and 390px EN/light show all filter
inputs/triggers at 36px, Results/Wyniki 25 without an unconfirmed label, exact
same-event header alignment below the shell across four scroll positions, working
picker and horizontal synchronization, no document overflow or page errors.
Evidence: `.runtime/frontend/RM-2026-016-table-repairs-browser.log` and
`.runtime/artifacts/playwright/runa/RM-2026-016-table-repairs/` report/screenshots.
Rendered desktop Audit evidence was inspected. Controlled responses do not replace
authenticated owner acceptance or prove real production counts/performance.

Public UI/table/forms/component, Admin/Auth/Permissions/Audit/API guides and current
central/module changelogs are synchronized. Both owner and customer skills already
route table/control/module work to these canonical guides; no new reusable workflow,
root rule or duplicated skill procedure is needed. Customer code consumes the
public optional descriptors and guarded owning results without protected edits.
No Docker resources, migration/reset, credential/grant changes or dependencies
were created. Preserve active services, development/base-test data, model/Qdrant
caches and all verification evidence. Manual acceptance 11b and final verification
12 remain open; no broad gate PASS or release closure is claimed.

### Point 11a35 — Vertically centered labels and horizontal header wheel input

Owner request and clarification, 2026-11-01: center labels vertically only and
scroll the table sideways when wheeling over its header. Current knowledge
retrieval/source review confirmed the shared table owner and existing Application
top-navigation wheel pattern. An aria-hidden top gutter balances the existing
below-label scrollbar gutter only when overflow exists; label cells retain left
alignment and sorting/selection remain in the single original semantic header.
The private viewport handler maps the dominant wheel axis to native scrollLeft,
normalizes pixel/line/page units and synchronizes the rail. Header and rail both
consume the gesture at either end of a wide table; Ctrl+wheel, fitting tables and
body-row page scrolling retain native behavior. No smoothing animation or copied
header was introduced.

The focused native Chromium viewport case passes, including vertical cell/header
centers, pixel/line/page/dominant-axis wheel input, both edges, Ctrl+wheel, body-row
and fitting-table pass-through, existing pinning and two-way horizontal sync:
`.runtime/frontend/RM-2026-016-header-wheel-focused.log` (exit/cleanup 0).
Supported asset preparation passes in `RM-2026-016-header-wheel-build.log` under
`.runtime/frontend/`. A one-off served-build diagnostic is needed for synthetic
Admin geometry and real mouse wheel input; desktop PL/dark (1440px) and mobile
EN/light (390px) both show zero vertical center mismatch, real wheel movement
without document scroll/chaining, pinned-header alignment and no page errors or
document overflow. Evidence: `.runtime/frontend/RM-2026-016-header-wheel-browser.log`
and `.runtime/artifacts/playwright/runa/RM-2026-016-header-wheel/`; the pinned
desktop screenshot was inspected. Synthetic API data is not owner acceptance.

The public table guide and current central/Admin changelogs are synchronized.
Owner/customer frontend table references already route to that canonical guide;
no root rule or duplicate skill instruction is needed. This changes shared UI
presentation only, not guarded APIs or module manifests; Admin remains the current
unreleased 0.1.0 candidate. No Docker resources or database changes were created;
active services, development/base-test data, model/Qdrant caches and evidence are
preserved. Manual acceptance 11b and full verification/release 12 remain open.

### Point 11a36 — Consistent localized dictionary presentation

Owner request, 2026-11-01: use the same localized dictionary labels in filters and
Audit rows, lowercase in both languages, and repair analogous module defects.
Current local knowledge and focused source review covered shared Admin table/field
renderers, Audit history/details, Auth directory/account history and Permissions
owned definitions/effective/access presentation. Filters already translated their
choices; table cells and read-only fields exposed raw values. The shared renderer
now localizes only declared choices (including read-only arrays) with the existing
translator. Audit's custom immutable detail renderer applies the same choice rule.
Auth's account/login-history summaries/details already translated outcomes but
used title case; these now show lowercase outcomes consistently with Audit.
Permissions definition identifiers/effective codes are free text and remain raw;
its declared dictionaries receive the shared fix. Lowercase source dictionary
keys use lowercase supplied PL labels; EN retains its lowercase source spelling.
Unknown/custom text, module/action/UUID references, JSON payloads and custom cell
slots keep their original content/ownership. No blanket lowercasing of customer
values was introduced. TableColumn.value remains canonical for exports/adapters;
only default cell rendering is localized. Query/filter/wire/storage values stay
canonical, and sensitive set-state still bypasses raw value presentation.

Ten focused Chromium cases pass across Audit/Auth/Permissions PL/EN cells/filter
options, canonical column values, read-only choices and immutable Audit detail
privacy/links. A separate focused Auth account-history case passes for lowercase
row/detail outcomes, retry, safe changes and stale-response fencing. Evidence:
`.runtime/frontend/RM-2026-016-dictionary-labels-focused.log` and
`RM-2026-016-dictionary-labels-account.log` (exit/cleanup 0). Locale tests use the
existing preference writer to avoid stale guest-store state; generic component
props use explicit known types. The pre-existing retry assertion was updated to
find the rendered FormNotifications action in its portal, instead of inside the
dialog. Supported asset preparation passes in
`.runtime/frontend/RM-2026-016-dictionary-labels-build.log`.

A one-off served-build diagnostic with controlled API responses confirms matching
lowercase actor/outcome labels in filter choices and Audit rows at 1440px PL/dark
and 390px EN/light, unchanged technical identifiers, no page errors or document
overflow. The actual Select trigger is a labelled button; its combobox belongs to
the opened search panel. Evidence:
`.runtime/frontend/RM-2026-016-dictionary-labels-browser.log` and
`.runtime/artifacts/playwright/runa/RM-2026-016-dictionary-labels/`; the desktop
screenshot was inspected. This is controlled presentation evidence, not owner
manual acceptance or full-suite verification.

Public Admin/Audit/Auth presentation guidance and central/module changelogs are
synchronized. Existing owner/customer frontend references already route to those
canonical contracts; no new root rule or duplicate skill procedure is needed.
Assessed compatible corrections stay in unreleased Admin 0.1.0, Audit 1.1.0 and
Auth 2.1.0 candidates. Permissions consumes the shared presentation without an
owned-source/API change. No migration, dependency, credential/grant change or
Docker/database resource creation/removal occurred. Active services, development/
base-test data, model/Qdrant caches and evidence remain preserved. Manual acceptance
11b and final verification/release 12 remain open.

### Point 11a37 — Readable immutable details and bounded value previews

Owner request, 2026-11-01: improve Audit detail readability, collapse long JSON
with explicit expansion and investigate entity history navigation; inspect the
same problems across Auth and Permissions. Current local knowledge status was
fresh; bounded retrieval led to the original Admin/Audit/Auth presentation guides.

Audit now groups event metadata, historical actor and technical identifiers, and
uses one compact data card for changes/context/execution. It omits filter-only
from/until/entity fields, labels empty trees and hides multipart navigation for
single-part results. Multipart cursors retain their existing native 4096-byte
fragments and explicit next/first controls. The public UI-owned ValuePreview
renders escaped text, indents complete JSON without changing numeric/string
tokens or duplicate keys, preserves incomplete fragments and collapses after eight
source lines or 480 Unicode code points. Show all/Collapse is keyboard accessible
and never fetches or submits. Replacement values reset expansion.

Shared Admin read-only fields/review previews cover Permissions definition and
Auth editors; Auth bounded collection entries also use ValuePreview for long
text/object values while known dates keep their owning formatter. Record browsers
use section/page dialog titles. Permissions effective tables have no equivalent
raw JSON detail block; their table/query/export semantics remain unchanged.

The entity link was not reproducibly broken: native clicks in the served browser
closed the detail and issued the complete module/type/public UUID Audit filters.
Related cards now explicitly explain this destination and say View entity history;
they never imply navigation to the foreign current record. No route or grant
workaround was added.

Focused native Chromium tests pass: eight Audit/value-preview cases, including
escaped markup, exact JSON token preservation, resetting expanded values and
multipart reads, plus three shared dictionary/secret-set-state cases. Evidence:
`.runtime/frontend/RM-2026-016-detail-focused.log` and
`RM-2026-016-detail-readonly.log` (native exit/cleanup 0). Supported asset preparation
`pnpm --dir src run build` passes in `RM-2026-016-detail-build.log`.
A focused one-off served-build diagnostic exercises modal geometry, native Enter
expansion and real link navigation unavailable in isolated component mocks.
All three module browsers at 1440px PL/dark and 390px EN/light have no dialog
horizontal overflow or page errors; both Audit views issue the exact entity query.
Reports/screenshots: `.runtime/artifacts/playwright/runa/RM-2026-016-detail/`;
`.runtime/frontend/RM-2026-016-detail-browser.log`. The Audit desktop/mobile rendered
screenshots were inspected. Synthetic authenticated responses are interaction
evidence, not live backend mutation acceptance or owner manual approval.

Actual local page/readiness/presentation/frontend catalogue/assets return 200;
anonymous Admin entry retains 401. Evidence:
`.runtime/frontend/RM-2026-016-detail-http.json`. Current assets are served for
owner review at http://localhost:8080/admin/runa.audit/history with existing access.

Public components/Admin/Audit/Auth/Permissions guides and current central/module
changelogs are synchronized. Owner/customer frontend component/forms references
already route consumers to these canonical public guides; no root or skill policy
change is needed in either instruction product. These are compatible presentation
corrections within the current unreleased Admin 0.1.0, Audit 1.1.0 and
Auth/Permissions 2.1.0 targets; guarded API signatures/manifests remain unchanged.
No dependency, database migration or task Docker resource was introduced; active
services, dev/base-test data, caches and verification evidence are preserved.
Manual acceptance 11b and complete final verification/release 12 remain open.

### Point 11a38 — Direct Audit history links and colored inline JSON previews

Owner clarification, 2026-11-01: correlation belongs to Event, its own value links
to matching history, and the actor UUID likewise links to that actor's history.
The earlier request also removes related-entity cards and replaces the framed
JSON expansion control with an inline text link and syntax colors. Current local
knowledge was fresh; bounded retrieval and original Audit/Admin/UI sources confirm
both scalar history selectors already exist. No backend/API extension is needed.

Audit defaults now contain Event and Actor groups, with correlation in Event.
Nonempty correlation/actor UUID values use the existing TextLink and URL-encoded
single-selector history URLs. Null/missing values remain unlinked. Actor history
selects the historical actor, independently of affected entities. The separate
technical-ID and related-entity cards and their unused labels are removed; guarded
entity projection and complete entity-filter URLs remain available for custom UI.

Shared ValuePreview now places its Show all/Collapse TextLink inside the preview.
Native Enter toggles it without changing URL/focus or fetching/submitting anything.
JSON keys, strings, numbers, literals and punctuation use existing light/dark theme
colors; forced-colors restores system text. Private tokenization preserves every
source character and renders Vue text spans, never raw HTML; lossless formatting,
fragment handling and the original bounds remain unchanged. Auth collection values
and shared Auth/Permissions fields/reviews consume this same UI primitive. No
parallel module renderer or new dependency was introduced.

Ten focused Chromium cases pass, covering correlation/actor grouping and exact
URLs in PL/EN, missing IDs, escaped syntax tokens, exact numbers/strings, expansion
and reset, and existing bounded payload pagination. Evidence:
`.runtime/frontend/RM-2026-016-detail-links-focused.log` (native exit/cleanup 0).
Supported manual-runtime asset preparation passes:
`.runtime/frontend/RM-2026-016-detail-links-build.log`. A focused one-off served-build
diagnostic verifies real link navigation, keyboard expansion and modal geometry
that isolated mocks cannot establish. Six Auth/Permissions/Audit views at 1440px
PL/dark and 390px EN/light have no dialog horizontal overflow or page errors.
Both Audit variants close details and send exactly the selected correlation or
actor filter. Evidence: `.runtime/frontend/RM-2026-016-detail-links-browser.log`
and `.runtime/artifacts/playwright/runa/RM-2026-016-detail-links/`; desktop/mobile
Audit screenshots were inspected. Synthetic authenticated responses establish
interaction behavior, not backend mutation acceptance or owner approval.

Current local page/readiness/presentation/catalogue/assets return 200; anonymous
Admin entry retains 401. Evidence:
`.runtime/frontend/RM-2026-016-detail-links-http.json`. Current assets are ready at
http://localhost:8080/admin/runa.audit/history with existing access.
Public Audit/UI guidance and central/Admin/Audit changelogs are synchronized.
Owner/customer frontend component references already route to these canonical
guides; neither instruction product requires another rule or signature change.
Compatible presentation refinements retain the current unreleased Admin 0.1.0,
Audit 1.1.0 and Auth/Permissions 2.1.0 targets. No dependency, migration, credential,
grant or Docker resource was changed; active services, dev/base-test data, caches
and evidence are preserved. Manual acceptance 11b and final verification/release
12 remain open.

### Point 11a39 — Animated modal detail reads and right-aligned expansion

Owner request/clarification, 2026-11-01: align Show all/Collapse to the right and
animate loading inside the detail modal, not the history table. Current knowledge
was fresh; bounded retrieval/original loading and component guides confirm local
loading primitives and retaining already rendered content during later reads.

Shared ValuePreview aligns its existing inline expansion link to the preview's
right edge in text and JSON modes. Audit uses the shared localized Spinner while
its initial detail activation is pending, stops on settled failure and offers
explicit Retry loading. Payload continuation shows a small Spinner in the data
card's action region while preserving current metadata/payload. No artificial
minimum delay or new HTTP read is introduced. Reduced motion disables rotation.

Sibling review found the same missing animation in shared Admin record sections,
used by Auth and Permissions definitions. Pending activation now shows Spinner;
settled failure retains the warning/retry path and retry animates while pending.
Auth bounded collections no longer announce No entries before the record arrives.
Permissions effective-table loading already belongs to Table and needs no second
spinner. Existing dirty-draft, session, availability and form feedback ownership
remain unchanged; this does not alter modal selection/subject API timing.

Seven focused Chromium cases pass, covering pending Audit reads, failure/retry,
continuation with retained content, shared record activation and existing bounded
value/pagination behavior. Evidence:
`.runtime/frontend/RM-2026-016-detail-loading-focused.log` (native exit/cleanup 0).
Supported manual-runtime asset preparation passes in
`.runtime/frontend/RM-2026-016-detail-loading-build.log`. A focused served-build
diagnostic holds the actual detail response to inspect the modal before data,
checks computed spinner animation and its reduced-motion override, then releases
it and verifies right-edge alignment and existing identifier links. All six
Auth/Permissions/Audit views at 1440px PL/dark and 390px EN/light show the loading
state, exact right-edge alignment, no horizontal overflow and no page errors.
Evidence: `.runtime/frontend/RM-2026-016-detail-loading-browser.log` and
`.runtime/artifacts/playwright/runa/RM-2026-016-detail-loading/`; Audit loading and
loaded screenshots were inspected. Controlled authenticated responses are focused
interaction evidence, not owner acceptance or full automated verification.

Actual local page/readiness/presentation/catalogue/assets return 200; anonymous
Admin entry retains 401. Evidence:
`.runtime/frontend/RM-2026-016-detail-loading-http.json`. Current assets are ready
for review at http://localhost:8080/admin/runa.audit/history with existing access.
Public UI/Admin/Audit guides and current central/Admin/Audit/Auth changelogs are
synchronized. Both owner/customer frontend references already route to these
canonical guides; no additional instruction rule or public signature is needed.
Compatible corrections retain unreleased Admin 0.1.0, Audit 1.1.0 and
Auth/Permissions 2.1.0 targets. No dependency, migration, credential/grant or Docker
resource was changed; active development services, dev/base-test data, caches and
evidence are preserved. Owner manual acceptance 11b and full verification/release
12 remain open; tentative completion of Audit view review is not whole-roadmap
manual acceptance.

### Point 11a40 — Complete content-loading review and shared defaults

Owner correction/expanded scope, 2026-11-01: review every loading animation and
all modals, replace circular content loading with pulsing geometry and prevent
future local mismatches through shared defaults and automatic enforcement. This
supersedes point 11a39's mistaken circle choice. Local knowledge was current;
bounded retrieval and original loading/components/motion/frontend sources were
reviewed before implementation.

The complete source census and per-owner decisions, including all 17 actual modal
instances, are recorded in
[content-loading review](../../reviews/RM-2026-016-content-loading.md). Existing
account/form/navigation/avatar geometry and correct readiness boundaries are
retained. Standalone circles were removed from Audit details, shared record
sections, Auth history, avatar decoding, entry forms, welcome navigation and
Admin diagnostic refresh. Remote Select options also receive content placeholders.

Public ContentLoading supplies accessible text/form/detail/image/navigation
geometry and retained-content refresh. Skeleton and refresh now share one authored
pulse in content-loading.css, including reduced-motion/print handling. A served
geometry check caught a specificity collision with block Skeleton height; field
placeholders now reliably match 36px controls and image geometry stays bounded.
AsyncContent/ViewBoundary default to content placeholders; refresh keeps existing
DOM/focus/drafts. ViewBoundary retains its original default-slot structure.
Dialog exposes loading/label/shape and a loading slot; known parent-owned reads
use it directly, while child-owned reads retain local ContentLoading. Confirm and
Alert compositions refuse unfinished confirmation/acknowledgement. No global
request counter, artificial delay, extra read or security/session owner is added.

Circular indicators remain only in shared Table, embedded Button action progress
and the previously accepted preference-change cover, not content defaults. The
new delivered ESLint rule rejects standalone view Spinner imports, aliases,
namespace members and template references outside those shared owners. It runs
through existing lint/hooks rather than introducing a CI pipeline or new gate.
A focused policy test checks owner/App refusal, alias/namespace handling, allowed
ContentLoading and the shared Table exception. Both owner/customer frontend
skills now require the canonical loading procedure; root AGENTS already routes
to that contract and needs no duplicated rule.

Focused evidence:

- Twelve native Chromium cases pass across loading defaults, delayed Audit reads,
  failed-read retry/continuation, shared forms, dirty/focused refresh, Dialog
  dismissal/confirmation and provider cancellation/failure. Native exit/cleanup 0:
  `.runtime/frontend/RM-2026-016-content-loading-focused.log`.
- Two focused retention cases pass again after preserving ViewBoundary's original
  slot DOM: `RM-2026-016-content-loading-retention.log` under `.runtime/frontend/`.
- The supported focused Python contract test passes (native exit/cleanup 0):
  `.runtime/frontend/RM-2026-016-content-loading-policy.log`. The test path is
  relative to the owning tests/tools/runa root; overlapping suite execution was
  refused by the supported lock and rerun sequentially, without bypass.
- Supported manual-runtime asset preparation passes:
  `.runtime/frontend/RM-2026-016-content-loading-build.log`.
- A focused served-build diagnostic holds actual detail responses to inspect
  placeholders before data, then verifies shared pulse, reduced-motion static
  fallback, 36px form geometry, right-aligned expansion and existing history links.
  All six Auth/Permissions/Audit views at 1440px PL/dark and 390px EN/light have no
  content spinner, horizontal overflow or page errors. Evidence:
  `.runtime/frontend/RM-2026-016-content-loading-browser.log` and
  `.runtime/artifacts/playwright/runa/RM-2026-016-content-loading/`. Desktop/mobile
  placeholders were visually inspected; this is controlled interaction evidence,
  not live authenticated backend acceptance or owner manual approval.

Actual page/readiness/presentation/entry-options/catalogue/assets return 200;
anonymous Admin entry retains 401. Evidence:
`.runtime/frontend/RM-2026-016-content-loading-http.json`. Current assets are ready
at http://localhost:8080/admin with existing access. Public loading/components/
architecture/Admin/Audit/Auth guidance and current changelogs are synchronized;
obsolete current Spinner defaults are replaced. The reusable public props/defaults
are additive; assessed unreleased Admin 0.1.0, Audit 1.1.0 and Auth/Permissions
2.1.0 targets remain appropriate. No dependency, migration, credential/grant or
Docker resource was changed. Active development services, dev/base-test data,
model/Qdrant caches and evidence remain preserved. Manual acceptance 11b and full
affected-area verification/release 12 remain open.

### Point 11a41 — Visible JSON continuation line and Audit acceptance

Owner request, 2026-11-01: put the truncation ellipsis on its own line. Shared
ValuePreview now renders a separate bold, normal-text-color marker below JSON
tokens only when collapsed; expanded JSON stays complete and escaped, and ordinary
text keeps its existing inline ellipsis. Audit changes/context/execution and other
module JSON consumers inherit the correction without separate renderers.
Knowledge status was current; the original public ValuePreview contract and active
point were reviewed. The public components guide and current central/Admin/Audit
changelogs are synchronized. Existing owner/customer component guidance already
routes to this guide; neither instruction product needs a new rule or signature.
Unreleased module targets remain unchanged for this compatible presentation fix.

Supported asset preparation passes in
`.runtime/frontend/RM-2026-016-json-marker-build.log`. A focused served-build
geometry diagnostic confirms a separate marker line below the last JSON token,
marker removal on expansion, retained history links and no overflow/page errors
at 1440px PL/dark and 390px EN/light. Evidence:
`.runtime/frontend/RM-2026-016-json-marker-browser.log` and
`.runtime/artifacts/playwright/runa/RM-2026-016-json-marker/`; the desktop screenshot
was inspected. No new test or broad suite is needed for this reversible layout
adjustment; full automated verification remains point 12.

The owner explicitly reports satisfaction and completion of Audit view review
subject to this requested marker adjustment, now implemented. Record this as
Audit-specific owner acceptance of the local view; the remaining Admin/Auth/
Permissions review and whole-roadmap manual point 11b remain open. Current assets
are served at http://localhost:8080/admin/runa.audit/history with existing access.
No migration, dependency, data/grant or Docker resource was changed. Active
services, dev/base-test data, caches and evidence are preserved.

### Point 11a42 — Restore spacing across loading composition

Owner regression, 2026-11-01: the Module state selector on /admin touches the
module-card list after point 11a40. Current knowledge status was fresh; original
ContentLoading and loading-guide sources confirm that the new success wrapper
separated children from the existing space-y owner.

ContentLoading now retains default-slot children directly under its root and
applies refresh pulse/aria-busy to that same root. Refresh uses a named busy region
without inserting a status child that would participate in structural spacing
selectors. Module diagnostics place space-y-5 on their actual ContentLoading
layout root. Existing pending geometry, values, focus and identity cleanup remain.

Reviewed every ContentLoading consumer: Audit payload wraps one Card, shared Admin
sections/Auth entry/welcome/cropper use initial placeholders only, AdminLoading
owns its placeholder slot, Select owns option geometry, AsyncContent owns its
content region and ViewBoundary already retains direct default-slot structure.
No second spacing-class displacement was found. Generic owner/App composition now
supports root spacing/grid classes without another success wrapper.

Four focused native Chromium cases pass: direct-child composition with dirty/focused
refresh, AsyncContent/ViewBoundary retention and Audit read/retry/continuation
using the named busy region. Evidence:
`.runtime/frontend/RM-2026-016-loading-spacing-focused.log` (native exit/cleanup 0).
The unit harness does not load Tailwind utility geometry; its assertion checks
actual child/DOM/focus ownership, while the served-build diagnostic measures real
CSS. At 1440px PL/dark and 390px EN/light, the selector-to-list gap is exactly 20px
before a filter change, during held placeholder reads and after data settles.
No page errors occur. Evidence:
`.runtime/frontend/RM-2026-016-loading-spacing-browser.log` and
`.runtime/artifacts/playwright/runa/RM-2026-016-loading-spacing/`; the desktop
screenshot was inspected. Supported manual-runtime asset preparation passes in
`.runtime/frontend/RM-2026-016-loading-spacing-build.log`.

Public loading guidance and current central/Admin changelogs are synchronized.
Owner/customer frontend references already require this canonical contract;
no extra root/skill rule or public signature is needed. Unreleased module versions
remain appropriate for this compatible layout repair. Current assets are ready at
http://localhost:8080/admin. No dependency, migration, credential/grant or Docker
resource changed; active services, dev/base-test data, caches and evidence remain
preserved. Whole-roadmap manual acceptance 11b and final verification 12 stay open.

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
quality_groups: docs,backend,frontend,e2e,database,delivery,installation,updates,security-source
quality_rationale: Provisional affected-area scope for the new mandatory Core, public declarations and adapters, transactional storage, rendered UI and protected customer extension/generator contracts. Native installation/updates verify actual Core forward history and preservation. Security-source covers new authenticated operations and composition. Mandatory baseline/hooks and real prerequisites remain required. Reassess after architecture decisions; no infrastructure/image or knowledge-runtime change is currently proposed and no all-group milestone is due at 0.7.0.
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

Create `runa.admin`, the final planned Core module, as an English administration
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

### Scope and exclusions

The [architecture review](../../architecture/RM-2026-016-admin-review.md) contains
the source inventory, complete Core API matrix, architecture, configuration audit,
four approval decisions, acceptance cases and quality rationale. It is part of
this roadmap specification. Point 2 implements the guarded entry/bootstrap
foundation; other proposed contracts retain the delivery status in the handbook.

Scope includes `/admin` dashboard and account-menu-only entry; developer-controlled
module/page navigation; complete supported Auth/Permissions/Audit administration;
generated and customizable tables/forms; field/section/page/action authorization;
compatible atomic cross-module Save; diagnostics/work/Optional lifecycle/settings/
integrations/recovery; CLI-only maintenance diagnostics/recovery; application
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
6. English-only usable defaults; light/dark, responsive and accessible. Shared form
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
  - Wire allowed Optional transitions/preview/status/resume/cancel, settings and
    integration configuration, health policy and permitted work cancellation/status.
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
- [ ] 12. Complete affected-area verification, release finalization and closure.
  - Prerequisites: all implementation points complete and committed; no unresolved
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
  - If verification requires changes, insert a fix point immediately before this one;
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

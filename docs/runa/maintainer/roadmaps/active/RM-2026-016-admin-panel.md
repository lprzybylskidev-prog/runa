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
- [ ] 5. Implement standard generated resource tables, forms and actions.
  - Prerequisites: 3–4.
  - Compose existing Table/useTable/providers/selection/views/exports and Form/fields/
    confirmations/feedback. Support automatic, partial and full replacement, public
    UUID lookups, static validation hints, custom controls and explicit command bindings.
  - Preserve owner pagination/filter/sort/count/export limits and secret set-state;
    map 422/409/safe errors and dirty navigation guards. Implement shared aggregate
    revision/read reconciliation without replay or edited-form reinitialization.
  - Author standard CRUD/read-only/field-denial/export/bulk/dirty/conflict scenarios
    and synchronize forms/tables/frontend references and customer examples.
- [ ] 6. Implement approved cross-module contributions and atomic Save.
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
- [ ] 7. Complete Auth administration through owning public APIs.
  - Prerequisites: 3–6.
  - Deliver bounded Auth-owned directory/necessary missing read APIs additively;
    identity/lifecycle/deadlines/locks, invitations, profile/login/names, email and
    secondary-address workflows, sessions/trust/security resets, policy/history/
    consequences/restore/exceptions and administrative preference reset.
  - Keep self-service, recipient/delivery and reserved operator capabilities in their
    supported audience. Integrate Permissions sections through contribution contracts.
  - Cover every applicable matrix entry with API/UI tests and document exact grants,
    proofs/revisions and independent workflows; update Auth version/changelog.
- [ ] 8. Complete Permissions administration and Audit viewer.
  - Prerequisites: 3–7.
  - Deliver assignments/direct grants, custom roles/presets/copy/add-replace and
    effective changes review, source/system read-only definitions, dedicated protected
    authority and confirmed force-delete flows. Preserve native selection and
    last-eligible-superadministrator invariants.
  - Deliver authorized Audit cursor filters/detail/entity links without payload leaks,
    total-count invention, edits or undo; optional current identity labels use guarded Auth.
  - Author owner policy/preview/step-up/Audit atomicity regressions and frontend
    interaction cases; update changed module versions/changelogs and public guides.
- [ ] 9. Deliver dashboard, module controls, work and approved maintenance behavior.
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
- [ ] 10. Deliver approved application global settings and runtime presentation.
  - Prerequisites: 1, 3–5; ARCH-004 resolved.
  - Add source-declared app-owned safe settings/defaults, storage/migration, revisions,
    validation/grants and atomic Audit, independent of module settings. Implement
    approved identity/presentation precedence and browser/mail/error fallbacks.
    Keep the Runa default logo in code and customer-selected branding/logo in DB.
  - Keep technical/.env/source compatibility fields read-only; no infrastructure
    controls or generic asset/upload subsystem. Author no-restart persistence,
    ownership/rollback/CSP/fallback and customer extension cases; document classification.
- [ ] 11. Complete generator, consumer examples and documentation integration.
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

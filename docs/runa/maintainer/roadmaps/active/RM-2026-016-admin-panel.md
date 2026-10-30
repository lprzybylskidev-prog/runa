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

Record subsequent significant owner decisions individually with dates and update
the review/roadmap.
If decisions require incompatible existing module APIs, obtain explicit module
MAJOR approval before that implementation; preserve compatible existing APIs by
default. Admin develops at 0.x.y and receives independent 1.0.0 only at verified
creation closure. Existing changed modules receive assessed independent versions
and central changelog entries; platform versioning is separate.

### Contract and behavior requirements

1. Admin requires Audit/Auth/Permissions without introducing reverse dependencies.
   Extend framework management declarations additively; keep module-owned business
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
12, after every implementation point.

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
- [ ] 3. Implement declaration discovery and authorized projection.
  - Prerequisites: 1–2.
  - Extend existing management declarations with pages/sections/resource and command
    bindings, widgets and contribution metadata. Add trusted app independent-page
    registration. Use registry/hash/schema/renderer checks and deterministic ordering.
  - Filter page/section/field reads and writes, values/options/derived counts, selection
    and exports server-side; isolate invalid declarations. Author negative disclosure,
    disabled/unavailable/stale generation and application-consumer cases; document API.
- [ ] 4. Deliver the Admin shell and public presentation/controller seams.
  - Prerequisites: 2–3.
  - Add `/admin` and named page routing/default-dispatch integration; module-left,
    page-top navigation, no empty/mandatory Overview. Extend AuthApplication menu and
    composition inputs additively; put entry only beside User panel.
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
    input/errors/revisions, compatibility validation and explicit lock/write order.
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

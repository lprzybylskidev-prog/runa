---
id: RM-2026-017
title: Comprehensive platform quality, consistency and development workflows
status: active
branch: roadmap/RM-2026-017-platform-quality-and-workflows
created_at: 2026-11-06
activated_at: 2026-11-06
closed_at: null
release_type: feature
proposed_version: 0.8.0
impact_areas:
  - frontend
  - backend
  - database
  - development
  - tooling
  - ai_environment
  - security
  - documentation
---

# RM-2026-017 — Comprehensive platform quality, consistency and development workflows

## Overview

### What and why

Prepare Runa 0.8.0 through an evidence-led review of the existing platform, its
presentation, contracts, tests, guidance and development workflows. Preserve the
accepted identity and architecture; correct demonstrated problems and complete
justified gaps in existing capabilities. This is a platform quality milestone,
not a new business-module programme or a wholesale redesign.

**Active roadmap; baseline audit authorized.** On 2026-11-06 the owner approved Gate A,
including `release_type: feature`, `proposed_version: 0.8.0`, the 12-point planning
baseline and D-01–D-06 with the D-04 modification recorded below. The initial approval explicitly withheld activation and implementation authorization.
On 2026-11-06 the owner separately instructed activation and execution of point 1.
The roadmap is now active on its declared branch. This authorizes the bounded
baseline audit only; Gate B still requires explicit approval before corrections
or point 2. Runtime behavior, schemas, configuration and versions remain unchanged.

### Scope

- Derive usable visual and interaction standards from RM-2026-016's decisions,
  corrections and current implementation; research external guidance critically.
- Audit every owner skill and its customer counterpart, instructions and current
  documentation; establish selective activation and coherent authoring guidance.
- Review all supplied frontend experiences, shared components, public replacement
  seams, localization, accessibility, readiness, asynchronous behavior and state.
- Review the backend, Core contracts, persistence, transactions, Audit, security,
  performance and test value comprehensively but proportionately.
- Review existing development tooling, dependencies, configuration, Docker,
  Dev Container and optional development-only knowledge retrieval.
- Extend existing test/quality infrastructure with durable, explicitly validated
  evidence reuse and controlled owner SIGSEGV coverage recovery.
- Improve retained roadmap reporting, manual acceptance preparation and safe cleanup.
- Deliver synchronized guidance, meaningful regressions, owner acceptance and
  complete selected-area final verification before release finalization.

### Out of scope

New business modules; a new module framework, test runner, RAG architecture or
quality-history dashboard; arbitrary file-size/test-count/coverage targets;
unmeasured optimization; speculative caches; compulsory UI linting for subjective
design; CI; bulk dependency upgrades; production deployment. Do not investigate
SIGSEGV root causes, change PHP pins speculatively, weaken guards or manufacture
native PASS. No upstream installers/scripts execute merely for research.

Published tags, dated evidence and closed roadmaps retain their historical bytes.
Do not remove roadmap discussion or rejected designs to reduce document size.
No reset or deletion of existing application data is planned or authorized by
this request. Any discovered need is discussed separately with exact ownership,
data value and scope; never use a reset as a shortcut for these improvements.

## Current-state baseline and planning evidence

### Repository and release

Inspection on 2026-11-06 used clean `main` at
`6889a98b22ee79c1ebc6344e67a8c940f0dfb000`, the RM-2026-016 merge. `VERSION` is
0.7.0, the latest local platform tag is v0.7.0, and active/paused roadmap
directories are empty. Planning did not fetch, push or independently attest the
remote GitHub Release. Recheck remote divergence, release order and draft IDs
before activation; this inspection is not permission to overwrite intervening work.

The [release workflow](../../workflows/releases.md) maps feature to MINOR and
maintenance/fix to PATCH. From 0.7.0, feature yields the requested 0.8.0;
maintenance would yield 0.7.1. The owner-approved feature classification reflects new
resumable verification capability and justified completion of existing mechanisms,
although the milestone's purpose is quality. An alternative `release` classification
requires an explicitly approved `target_version`; do not silently change semantics.
0.8.0 is not an all-group cadence milestone; 0.10.0 is the next such MINOR.

| Core owner       | Current module version | Current platform range | Dependency direction                          |
| ---------------- | ---------------------- | ---------------------- | --------------------------------------------- |
| runa.audit       | 1.1.0                  | >=0.7.0 <0.8.0         | No required Core dependencies                 |
| runa.auth        | 5.0.0                  | >=0.7.0 <0.8.0         | Audit ^1.0.0                                  |
| runa.permissions | 2.1.0                  | >=0.7.0 <0.8.0         | Auth ^5.0.0, Audit ^1.0.0                     |
| runa.admin       | 1.0.0                  | >=0.7.0 <0.8.0         | Audit ^1.0.0, Auth ^5.0.0, Permissions ^2.0.0 |

These are facts from `src/runa/modules/*/module.json`, not proposed synchronized
module bumps. Each changed module needs an independent semantic assessment,
central/module changelog and dependent/API review. Existing ranges exclude 0.8.0;
candidate preparation must reconcile them deliberately. The standing pre-1.0
policy permits coherent incompatible changes and original development migration
corrections without adapters solely for old interfaces. It does not waive real
customer data/update obligations, immutable publication or new architecture review.

### Ownership and architecture inspected

- Root `AGENTS.md`, `CLAUDE.md`, `.agents/README.md` and the nine owner skill
  entrypoints are operating guidance. The separately authored customer AGENTS and
  skill inventory were inspected as product data. No additional tracked nested
  AGENTS governs the planned source paths; runtime acquisition fixtures and vendor
  copies are separate customer/dependency contexts.
- [Documentation ownership](../../../../README.md) separates `docs/app`, the
  delivered handbook and private maintainer records. Customer source must remain
  usable with maintainer material absent. Existing historical hash protection in
  `documentation-map.json` must survive standardization.
- Laravel/Vue application source is divided between `src/runa` and minimal
  `src/app`. `src/runa/Foundation/Application.php` extends Laravel and retains
  native plus Runa maintenance state and queue-heartbeat context. The IDE's open
  maintenance files are not evidence that a maintenance guard is defective.
- [Public API boundaries](../../../handbook/architecture/public-api.md) distinguish
  extension, consumption, trusted integration and private implementation separately
  from physical delivery. `ModuleApis` and owning contracts remain the supported
  route; neither a `Contracts` name nor public language visibility grants access.
- [Native transactions](../../../handbook/architecture/module-transactions.md)
  use owning preflight/lock/validate/apply/result/refusal phases. Admin coordinates
  declarations and presentation, rather than querying foreign persistence or
  implementing a second business engine. Auth proof, current grants, record
  policies, maintenance, last-administrator invariants and transactional Audit stay
  authoritative. Maintenance diagnostics/recovery remain CLI-only.
- Frontend public facades live under `src/runa/resources/js/runa`; shared controls
  under `src/runa/resources/js/components`; Core defaults under
  `src/runa/modules/*/frontend`. `lib/http.ts`, `useViewReadiness.ts`,
  `ApplicationSessionShell.vue`, `AppContentLoading.vue`, `AppDialog.vue` and
  `AppButton.vue` already supply significant common behavior.
- The source package declares Laravel 13.37.0, Vue 3.5.44, TypeScript 6.0.3,
  Tailwind 4.3.3 and pnpm 12.14.0. These are inspected package declarations, not
  independent claims of installed/runtime versions or recommendations to upgrade.
- Existing test ownership is `tests/{backend,frontend,tools}/{runa,app}`; owner
  test composition is `tools/maintainer/test_cli.py` over shared
  `tools/application/testing/runner.py::TestService`. Native guards, suite locks,
  worker cleanup and focused native exit codes already exist.
- Existing quality implementation is `tools/application/quality_{service,state,
planning,execution,reporting,evidence}.py`; owner graph is
  [gates.yml](../../quality/gates.yml). It already records selection, candidate,
  graph identity, dependencies, exclusions, process state and timings. Unrelated
  ready nodes continue after a failure; failed dependencies block descendants.
  The missing capability is validated resumption/acceptance, not basic parallelism.
- `QualityService.run` replaces the previous run directory; `check_evidence`
  requires clean all-passed node states/exit 0 and permits only narrow metadata
  edits after its candidate. `TestService` records scope exits and cleanup, but
  does not yet provide the proposed per-case crash ledger/retries.
- [Documentation checks](../../quality/README.md) already check ownership,
  required sources, current version/roadmap, command documentation and local
  file links. `tools/application/docs_checks.py::links` strips fragments and skips
  external URLs; it does not validate heading-anchor targets or writing quality.
- `compose.yaml`, `compose.production.yaml`, Docker sources and
  `.devcontainer/devcontainer.json` preserve sibling services, host ownership,
  container toolchains and `/workspace` binding. Optional Qdrant/TEI startup is
  independent of application readiness. Production excludes AI/development tooling.
  `.codex/config.toml` points Boost at `./bin/boost-mcp`.

### Inventory and limits of planning

Tracked-source counts at the inspected commit: 81 handbook Markdown files, 101
maintainer Markdown files including history/customer material, three application
Markdown files, 159 Runa Vue files and two App Vue files. Private tests include
52 `*Test.php` files, 44 `*.test.ts` and 14 `*.spec.ts` files. These are file counts,
not test discovery or evidence that every source was audited.

Root AGENTS has 458 lines/4,031 whitespace words/32,207 UTF-8 bytes; customer
AGENTS 424/3,742/30,078; frontend SKILL.md 76/539/5,502. Record comparable before/
after context counts during consolidation, without size caps or correctness claims.

`php src/artisan runa:knowledge:status` returned exit 1: stale, 208 documents,
11,397 chunks, `branch_changed: true`, no changed/deleted documents, missing or
orphan chunks, parser/metadata change or interrupted write. Retrieval was not used
as evidence. Focused source search supplied the baseline; no service was bootstrapped,
index rebuilt or live evaluation run. Before each later point, recheck relevant
availability/freshness and use bounded English queries followed by original sources.

Planning inspected the roadmap's decision register, implementation structure,
all 101 acceptance-correction headings and their opening dispositions, selected
detailed corrections, the final repair/release record, architecture reassessment,
loading review, relevant commit history and representative current implementations.
This is systematic planning evidence, not a completed comprehensive baseline audit
or rendered acceptance. Point 1 must expand the complete decision-to-consumer ledger
and inspect remaining detailed evidence, references and affected source. No new
test, browser, benchmark, security-audit or infrastructure acceptance result is
claimed during this planning request.

### RM-2026-016 decisions carried forward

The [closed roadmap](../closed/RM-2026-016-admin-panel.md),
[architecture reassessment](../../architecture/RM-2026-016-admin-reassessment.md)
and [content-loading review](../../reviews/RM-2026-016-content-loading.md) are
historical evidence. Later explicit accepted corrections supersede intermediate
designs; the existence of a closed roadmap does not make its decisions obsolete.

| Decision chain                    | Accepted direction and reasoning                                                                                                                                                                                                       | Current correlation / audit obligation                                                                                                                       |
| --------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| ARCH-001 revised; points 2a/2b    | Refactor owning phased operations instead of special ordering around obsolete whole writes; validate the same locked pre-write snapshot.                                                                                               | ModuleTransactions/native transactions; review standalone and aggregate consumers together.                                                                  |
| ARCH-002/003; 11a87               | Preserve maintenance refusal, native reviews and security; password confirmation for protected operations/places, second factors for login.                                                                                            | Root security rule, Auth/Permissions owning commands and full-page confirmation. Earlier both-factor operation notes are historical.                         |
| 11a4 after original English Admin | Polish default and complete English secondary, sharing application preference.                                                                                                                                                         | Current AGENTS and catalogues agree; an older visual-direction paragraph conflicts (finding P-01 below).                                                     |
| 11a7 → 11a8 → 11a9/11/15/16       | Whole-page placeholder was rejected. Mount frames immediately, reveal logically complete dependent regions together, bound optional images/advertisement and keep later refreshes in place.                                            | Loading guide, session shell, useViewReadiness and local navigation/breadcrumb boundaries; inspect slow/failing settlement, not just fast final screenshots. |
| 11a17/21/22                       | Source-only health/integrations; dedicated module settings pages. Generic runtime editors were rejected because they expose the wrong configuration ownership.                                                                         | Module framework and Admin operations; do not restore removed switches/editors as missing features.                                                          |
| 11a23/24; 11a89–92                | Runtime presentation defaults and branding with source fallback; module settings snapshots; compact full-width groups, useful descriptions, question-only Save confirmation.                                                           | AdminApplicationSettings/AdminSettingsPage and owning settings adapters; snapshot load does not publish.                                                     |
| 11a25/26; 11a71/72                | One FormNotifications path for outcomes, local field errors, truthful refusal versus revision conflict, nearest-form submit progress; remove filler.                                                                                   | Forms guide/shared notifications/button behavior; preserve contextual security warnings and durable work results.                                            |
| 11a10/14                          | Sidebar chooses area/module; subviews use top bar; complete hierarchical breadcrumbs.                                                                                                                                                  | PageNavigation, layout and session shell; dialog tabs remain local.                                                                                          |
| 11a27/28/32–36/45/61/66/83/84     | Shared filters/toolbars, compact typed ranges, localized dictionaries/time, aligned labels, pinning and consistent horizontal scrolling; real sort/filter/export capability.                                                           | AppTable/useTableViewport/temporal controls and owning providers; never infer all rows loaded or invent totals/progress.                                     |
| 11a37–42                          | Readable immutable data and bounded JSON expansion. Circular content loading was rejected after 11a39; pulsing geometry became shared default. Preserve slot/layout spacing.                                                           | AppValuePreview/AppContentLoading/Dialog; Table/Button/preference cover have distinct accepted indicator ownership.                                          |
| 11a43–69                          | Task-oriented configurable user administration, reviewed native bulk effects, concise identity/status groups. Activation supersedes approval/account expiry.                                                                           | Auth details/directory/actions and owning APIs; omit disabled capabilities and empty wrappers. Do not reintroduce retired lifecycle fields.                  |
| 11a73–79                          | Generic access forms were rejected for high-cardinality roles/permissions. Use compact searchable owning editor, complete pointer/keyboard states, inline thematic groups, no separator at wrapped-row starts.                         | PermissionsAssignments/AuthAdminActionGroups; preserve native source snapshots and authorization.                                                            |
| 11a80–88                          | Consistent session/device tables and reusable own-account presentation; named current rules, optional indefinite override expiry, Audit history instead of redundant local panels.                                                     | Auth own/admin security and rules; no raw fingerprint/header retention or copied security controllers.                                                       |
| 11a93 → 11a95                     | Quiet outlines; colors express effects, not proof requirements or surrounding feedback. Mixed-effect opener is purple; concrete final action keeps its effect. Solid primary actions and uniformly blue modal openers were superseded. | fd6da8be after bffa7337; AppButton/controls.ts and actual Auth/Admin/Permissions consumers.                                                                  |
| 11a96–101                         | Definition-only Permissions pages; account access tools live in Auth details, with separate import dialogs. Reuse native bulk impact; retain required administrator instead of refusing every removal.                                 | Current declarations/owning Permissions authority implementation; no generic superadmin bypass or duplicate bulk engine.                                     |
| 11a55/94; 11a102                  | Post-format build identities matter; stale assets can correctly refuse rendering. Native tests/reports must follow current proof, grant and typed payload contracts.                                                                   | cb9e0ca4/1f304326 and build projection; finish source formatting before preparing owner assets.                                                              |

Git history reviewed includes `fd6da8be`, `bffa7337`, `d3e8656a`, `a2154f55`,
`2abe2961`, `adf092c2`, `3b331587`, `cb9e0ca4`, `1f304326`, `0f1ad8f8` and
the closure/merge. During point 1 use `git show` on relevant implementations and
earlier superseded revisions, not commit titles alone, to complete correlations.

### Verified planning findings and open hypotheses

| ID   | Evidence / present conclusion                                                                                                                                                                | Disposition                                                                                                                                                       |
| ---- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| P-01 | `handbook/design/visual-direction.md`, REF-003 language paragraph still says English Admin, conflicting with RM-016 11a4/current owner contract.                                             | Verified current-guidance contradiction; correct in documentation standardization. Do not rewrite the historical decision.                                        |
| P-02 | `handbook/architecture/module-transactions.md`, current native versions paragraph still names Auth 3.1.0 and an unreleased candidate, while manifests/VERSION are Auth 5.0.0/platform 0.7.0. | Verified stale current-state prose; check signatures independently and synchronize, not merely replace every historical number.                                   |
| P-03 | QualityService deletes the previous latest run, closure requires all-passed exit-0 nodes, and the maintainer standing exception documents a separate acceptance assessment.                  | Verified mismatch between machine representation and owner acceptance/resume needs; no runtime fix or native result rewriting authorized now.                     |
| P-04 | RM-016 final report records 791 discovered backend cases, 784 confirmed PASS/seven interrupted SIGSEGV, cleanup 0, and a failed/interrupted redundant coordinator.                           | Historical coverage and workflow evidence only. Do not claim current 791-case discovery, uninterrupted suite PASS or a fixed PHP runtime.                         |
| P-05 | Latest RM-016 report discloses retained owned browser endpoint resources after interruption.                                                                                                 | Unresolved inherited recovery inventory for point 1; inspect journals/live identities before any action. Not RM-017-created disposable resources.                 |
| P-06 | Nine owner skills already have progressive references and customer counterparts; frontend already routes forms/tables/loading/motion.                                                        | Consolidation is the starting proposal. Practical effectiveness and final GOOD/NEEDS IMPROVEMENT/REDUNDANT/BROKEN ratings remain untested.                        |
| P-07 | Docs checker validates file existence but strips link fragments.                                                                                                                             | Objective candidate extension: local heading-anchor validation, with fence/slug/duplicate-heading semantics and public-projection tests. No new framework needed. |

Remaining frontend defects, database leftovers, dependency vulnerabilities,
performance bottlenecks and redundant tests are **unknown**, not presumed findings.

## Specification

### Decisions and approval status

- 2026-11-06 — Owner requests draft preparation only, intended 0.8.0, no activation,
  implementation, large suites, browser acceptance, rebuilds or release changes.
- 2026-11-06 — Initial planning proposal: feature/0.8.0 from the released 0.7.0
  baseline. The subsequent owner review below confirms both metadata values.
- 2026-11-06 — Gate A and Gate B below are mandatory. Neither is satisfied by
  document creation, silence, automatic checks or an agent judgment.
- 2026-11-06 — No new dependency, infrastructure rewrite, migration, API break or
  runtime change is selected by this draft. Gate B selects evidenced corrections.
- 2026-11-06 — Initial planning identified D-01–D-06 for explicit disposition.
  The subsequent owner review below resolves those planning decisions. Existing
  tools and canonical procedures are not modified by this draft; approved changes
  must be implemented coherently in their owning points after activation.

#### Owner review and Gate A decisions — 2026-11-06

The owner reviewed the draft and explicitly approved its overall scope, single
comprehensive platform-quality milestone, 12-point organization and release
metadata: **feature / 0.8.0**. Independent Core versions still require assessment
under the existing release policy; they do not inherit a uniform platform bump.

- **D-01 APPROVED:** persistent state and validated evidence reuse extend existing
  quality infrastructure. An unrelated backend SIGSEGV must not restart completed
  groups. Reuse requires verified source, configuration, environment, dependency
  and execution compatibility; unsafe heuristic reuse is prohibited.
- **D-02 APPROVED:** when exact interrupted-test identity is reliable, allow at
  most two additional attempts after confirmed SIGSEGV. Preserve original crash
  evidence, distinguish initial PASS, retry PASS, confirmed interruption, ordinary
  failure and unexecuted/unknown coverage. Exhausted confirmed SIGSEGV may retain
  owner release acceptance; ordinary failures block. Recover as much remaining
  backend coverage as safely possible without repeated whole-suite/graph restarts
  or root-cause investigation.
- **D-03 APPROVED:** neutral execution/state/resume mechanics may be shared;
  maintainer SIGSEGV release acceptance must not silently become customer policy.
  Preserve documentation, delivery and ownership boundaries.
- **D-04 APPROVED WITH MODIFICATION:** the baseline UI/UX audit must not be static
  source inspection alone. Permit bounded, targeted browser inspection of
  representative existing pages/dialogs for loading, animations/transitions,
  visible layout, responsive behavior, console errors and specific suspected
  interactions. Define scope and purpose before each activity and reuse the
  already available development environment where possible. If unavailable or
  requiring expensive setup, record limitations and defer affected observations;
  do not rebuild or bypass safeguards. Full backend suites, complete E2E matrices,
  quality graphs, expensive scanners and broad infrastructure acceptance remain
  prohibited during this audit. Comprehensive automated acceptance stays last.
- **D-05 APPROVED:** retain detailed development history, owner decisions, rejected
  approaches, investigations, corrections, evidence and reasoning without arbitrary
  size limits. Every roadmap retains a detailed initial/final comparison report.
  Closed records stay immutable; post-closure publication facts may remain in Git,
  release evidence and the final owner response.
- **D-06 APPROVED:** mandatory explicit owner frontend acceptance immediately
  precedes final automated verification. Supply usable real URLs and concrete
  scenarios, with at most five representative views per shared-component category
  while covering distinct directly modified functionality. Cleanup requires exact
  ownership and safe disposition; preserve user/application state, backups,
  historical evidence and shared resources.
- **UI/UX skill architecture APPROVED:** evaluate existing skills/references first,
  without arbitrary skill counts. A dedicated skill may be proposed or implemented
  within the approved architecture when analysis demonstrates a material benefit
  to discoverability, activation or instruction quality. Every skill receives a
  practical effectiveness assessment; documentation owns technical/design standards.
- **Gate B retained:** point 1 covers frontend, backend, database, documentation,
  skills, tests, tooling, configuration, infrastructure, knowledge and workflows.
  Demonstrate issues rather than assume changes are needed. Retain full technical
  findings here and present a concise owner report with priorities, corrections,
  architectural decisions and confirmed-versus-hypothetical evidence. Stop and wait
  for explicit owner approval before corrections or point 2.
- **Scope/order retained:** preserve Runa's design identity while improving actual
  usability, keep backend work proportionate, avoid unnecessary fragmentation and
  complete verification-workflow improvements before final automated verification.
- **Authorization expressly limited:** this response resolves Gate A planning
  only. Update the draft and report the changes, then wait. Do not activate,
  create the implementation branch, change runtime code or begin point 1.

Record later owner decisions with date, accepted/rejected scope, rationale and
superseded premise. An external-advisor response relayed by the owner is owner
direction; preserve it in the roadmap history and reconcile the plan.

#### Activation — 2026-11-06

The owner explicitly requested activation and point 1 execution. `git fetch origin`
succeeded; main and origin/main were identical at
`6889a98b22ee79c1ebc6344e67a8c940f0dfb000`, with no active or paused roadmap.
The only untracked work was this owner-supplied draft. Latest local tag v0.7.0
and unchanged release baseline retain feature/0.8.0. Created the declared branch,
moved the draft to active, and retained every Gate A decision. No conflicting
early broad verification is scheduled: D-04 permits bounded rendered observation;
complete automated verification remains point 12. No release/version change.

### Gate A — Roadmap agreement and separate activation

**Gate A completed on 2026-11-06.** The owner confirmed feature/0.8.0, scope,
stage order and D-01–D-06, with D-04 modified as recorded in the decision history.
Do not request the same planning confirmations again. Agreement alone did not activate the roadmap. The separate owner instruction on
2026-11-06 authorized activation and point 1; the branch/move/metadata/commit
procedure from the [roadmap workflow](../../workflows/roadmaps.md) applies.

### Gate B — Comprehensive baseline audit approval

After activation, point 1 produces both a concise owner summary and complete
technical findings retained here. Cover every agreed area, not only frontend or
known examples. **Stop after the audit; do not implement recommendations until
the owner explicitly accepts the selected scope and architectural decisions.**

Each finding records ID, Critical/High/Medium/Low severity, affected owner and exact
files/symbols, source revision, verified fact versus hypothesis, reproduction or
measurement when available, user/engineering impact, correction, alternatives,
dependencies, effort/risk and proposed stage. Mark unmeasured claims explicitly.
Critical/High issues require correction or escalation; defer Medium/Low only with
benefit/effort/risk reasoning. Maintain a finite approved list and a requirement
coverage matrix. Missing functionality in existing mechanisms is eligible; unrelated
features or significant expansion return to the owner.

### Approved process decisions and existing-policy differences

The owner resolved these differences at Gate A on 2026-11-06. This table records
the accepted implementation direction, not a claim that canonical tools or
instruction products have already changed. D-04 governs the planned audit after
activation; D-01–D-03 and D-05–D-06 are delivered in their owning points.

| Approved decision                                            | Existing authority / difference                                                                                                                                                                  | Accepted resolution and limit                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| ------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| D-01 — Validated resume                                      | Owner AGENTS, roadmap final/background sections, release selected-scope evidence and quality guide require restart from the beginning after repairs/interruption; latest-only state is replaced. | Extend the existing latest campaign with attempt evidence and explicit resume planning. Preserve valid unrelated results; rerun changed/uncertain nodes and dependencies. Keep manual group selection and strict security/AI gates. Synchronize tools, validators, docs and owner instructions in point 9. No draft-level exception is applied.                                                                                                                                                                                                                           |
| D-02 — Controlled SIGSEGV recovery                           | Maintainer testing policy explicitly prohibits automatic retries and accepts confirmed worker 139 with cleanup 0 without repeated approval.                                                      | Owner approved original-plus-at-most-two isolated retries, coverage recovery and separate dispositions. Never retry assertions/other failures as SIGSEGV or revisit root causes. Existing owner acceptance remains the authority until this amendment is delivered.                                                                                                                                                                                                                                                                                                       |
| D-03 — Customer boundary                                     | Customer AGENTS and public testing guide retain their own restart/diagnosis recovery; owner tolerance does not grant customer acceptance.                                                        | Share neutral resume/state mechanics where useful, but inject explicit owner acceptance policy only from receipt-free composition. Keep customer crash acceptance/recovery unchanged unless the owner separately agrees a product-policy amendment.                                                                                                                                                                                                                                                                                                                       |
| D-04 — Audit execution boundary (approved with modification) | Comprehensive automated verification belongs last; targeted observation must remain distinct from a broad acceptance run.                                                                        | After activation, combine source/history/existing evidence with scoped browser inspection of representative existing pages/dialogs for actual visual and behavioral findings. State purpose and scope; reuse available development runtime. If unavailable or expensive setup is needed, record limits and defer affected observations without rebuilding or bypassing safeguards. No full suites, complete E2E matrix, full graph, expensive scanners or broad infrastructure acceptance. Final automated verification is point 12; owner manual acceptance is point 11. |
| D-05 — Retained report/history                               | Current workflow requests a concise final report and allows closure facts known before merge; closed roadmaps are immutable.                                                                     | Retain a detailed evidence-backed report in the open roadmap and give a concise chat summary. Preserve discussions without size caps. Record actual merge/tag/publication in external release/Git history and final chat, never reopen closed records to append later facts. No second reporting framework.                                                                                                                                                                                                                                                               |
| D-06 — Acceptance/cleanup details                            | Manual owner acceptance and Docker-resource cleanup are already mandatory.                                                                                                                       | Extend canonical procedures with real URL/scenario handoff, at most five representative views per shared-component class, directly changed distinct views, and exact cleanup inventories including temporary files. Preserve data, journals/reports/caches/backups/history.                                                                                                                                                                                                                                                                                               |

No broad automated check is scheduled before implementation completion. The owner
explicitly approved targeted visual inspection during the baseline audit; it is
not permission to run complete browser/backend matrices or full quality graphs.
Record the selected views/dialogs, suspected behavior, viewport/state, available
environment, observations and untested limits. If later work requires broad early
execution, stop for an explicit plan resolution rather than relabeling acceptance
as focused inspection. No browser activity is authorized before activation.

The request's data-preservation restriction is stricter than the standing permission
to rebuild identified development databases; this roadmap follows the restriction.
Published migration identities and tags remain historical evidence. Supported real
installation/update paths must be evaluated without adding compatibility machinery
for disposable unpublished states.

### UI/UX and skill architecture

Use a small architecture: retain `frontend` as mandatory selector for any frontend
behavior/presentation work; Vue and Tailwind remain technology-specific. Prefer
improving the existing seven frontend references rather than adding a skill per
design topic. Analysis may justify dedicated reusable skills when they materially
improve discoverability, activation or instruction quality, with distinct triggers
and no duplicate authority. No arbitrary count or requirement to create a skill
is imposed. Actual skill authoring uses the skill-creator workflow after activation
and Gate B approval; all skills require practical effectiveness assessments.

Canonical design/interaction standards belong in existing `handbook/design` and
`handbook/development/frontend` guides. Skills select references and verification,
not duplicate procedures. Root AGENTS keeps essential constraints and routing.
Map visual identity/tokens/components, interaction/a11y, forms/validation, tables,
navigation/state, loading/motion, responsive behavior and PL/EN writing explicitly
to their owning guide/reference. Small copy/style changes load only relevant parts;
changes affecting security/session or dependencies also load the matching domain.

Require practical WCAG 2.2 AA review, keyboard/focus, reduced motion, usable smaller
viewports, light/dark, long content and both locales. Preserve IBM Plex Mono,
accepted palette/Tabler subset and moderately compact identity. Meaningful content
has one job; remove redundant copy but retain security consequences and unusual
operation guidance. Reconcile color/action semantics with accessible labels, not
color alone. Forms remain controlled, retain dirty drafts and known UX validation;
server rules remain authoritative.

Logical readiness follows existing immediate-frame/local-region contracts. Mounting
controllers behind a boundary may be necessary to discover required dependencies;
never reveal apparently ready incomplete forms, impose artificial minimum waits or
wait for unrelated widgets. Initial failure settles to explicit retry/fallback;
refresh preserves revealed DOM and drafts. Identity/access loss clears protected
content immediately. Navigation state persistence is useful only when justified;
do not put secrets/private drafts in browser storage or URL parameters.

### External guidance research (planning, not adoption)

Reviewed official/project-hosted source pages and bounded pinned material on
2026-11-06, without installing skills. Repository activity below is the latest
commit observed through the GitHub API, not proof of security or maintenance quality.
Complete referenced executable/assets/license review precedes any later adoption.

| Source and observed revision                                                                                                                                                                                                                                                                                   | Useful ideas / assessment                                                                                                                                                                                             | Runa adaptation or rejection                                                                                                                                                                                             |
| -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| [Anthropic frontend-design](https://github.com/anthropics/skills/blob/ca45fc496a3436251f191354091d557da31c15b5/skills/frontend-design/SKILL.md), repo commit 2026-10-28; local skill license inspected as Apache-2.0                                                                                           | Product-specific intentional design, concise useful interface writing and critique; short entrypoint. Framework-neutral visual guidance, insufficient alone for admin interactions/security.                          | Reuse the reasoning for consistency and purposeful copy. Reject default novelty, new typography/palette or hero-focused redesign over accepted Runa identity.                                                            |
| [Vercel web-design-guidelines skill](https://github.com/vercel-labs/agent-skills/blob/1a2c79d4e6c4a2283dc2c614b6538cbe1ed5ca62/skills/web-design-guidelines/SKILL.md), repo commit 2026-10-27                                                                                                                  | Compact review trigger and actionable file/line findings; fetches mutable remote instructions every invocation. Root LICENSE lookup returned 404, so wrapper copying license is unresolved.                           | Keep review ideas; reject automatic remote instruction loading and wholesale installation. Review exact license before any copied text.                                                                                  |
| [Vercel interface rules](https://github.com/vercel-labs/web-interface-guidelines/blob/434b7f91364665f2f733b310ec54809bf8f37937/command.md), repo commit 2026-10-05, MIT inspected                                                                                                                              | Practical semantic controls, focus/labels, overflow, reduced motion and layout review. Some React and broad performance prescriptions need contextual treatment.                                                      | Translate ideas to Vue. Reject uncontrolled-input preference, blanket virtualization thresholds, universal URL persistence, English title-case rules and any new dependency without evidence.                            |
| [GitHub community a11y instructions](https://github.com/github/awesome-copilot/blob/60dcb901abbd87609d93d6281f1916c22b8bbd8c/instructions/a11y.instructions.md), repo commit 2026-11-06, MIT inspected                                                                                                         | Criteria-linked problems and corrective examples are useful for source-backed findings. Large cross-framework instruction with broad activation; it is community guidance hosted by GitHub, not a normative standard. | Use selectively as a research checklist, verify criteria against W3C. Reject always-loaded wildcard guidance and imported legal/compliance claims. No requirement to load 700+ lines per UI change.                      |
| [Impeccable audit](https://github.com/pbakaus/impeccable/blob/c3bb418ee38ee49a6b999b7752c1368f3123a2bd/skill/reference/audit.md) and [critique](https://github.com/pbakaus/impeccable/blob/c3bb418ee38ee49a6b999b7752c1368f3123a2bd/skill/reference/critique.md), repo commit 2026-11-05, Apache-2.0 inspected | Community separation of technical evidence from visual judgment and impact-based findings is useful. Audit reference and bounded critique orchestration were inspected, not the complete executable ecosystem.        | Reject bundled detector/installers, mandatory subagents, numerical design score as acceptance, persistent critique product and automatic polish. Source is research data; none of its operating instructions is adopted. |

[WCAG 2.2](https://www.w3.org/TR/WCAG22/) and
[WAI-ARIA patterns](https://www.w3.org/WAI/ARIA/apg/patterns/) are the primary
accessibility references; use relevant patterns without treating an example as
proof of full conformance. Source authority, specificity, license, content review,
referenced code/assets, context cost, Vue applicability and conflict analysis are
required for selected adaptations. Preserve useful existing provenance such as the
frontend skill's Sablier research pin; do not relabel it as newly reviewed.

### Approved verification direction and implementation design

Extend `QualityService`/planner/executor/evidence and `TestService`; do not introduce
a parallel runner or infer groups automatically. Owner selection stays explicit.
Before coding point 9, record complete proposed state/schema and supported command
signatures in the existing command/quality guides. This draft does not claim that
a resume option, case listener or new state enum already exists.

The latest campaign remains under the existing workspace `.runtime` roots. Add
bounded attempt records and references to preserved native evidence within that
campaign, not an unbounded cross-release history database. Starting another campaign
must not silently destroy required crash/closure evidence; preserve referenced
reports before replacing latest state. Atomic updates, stable locks, source-owned
argument arrays, symlink refusal, identity checks and process-group cleanup remain.

Record campaign/attempt/node/suite/case identity, exact selection/command/mode and
worker count, source candidate plus relevant input hashes, graph/definition/tool
identity, prerequisites, nonsecret environment/config assumptions, evidence
paths/hashes, native statuses/signals, cleanup and distinct acceptance disposition.
Secrets never enter fingerprints/reports as plaintext or reversible values.

Keep three separate layers: immutable native attempt result; coverage accounting;
owner release assessment. A failed native process remains failed. Resume planning
explains for every selected node whether evidence is reusable, stale, pending,
blocked or unknown, and why. Scope changes and source changes never silently
inherit a prior PASS. Mandatory freshness/repository/current-state checks are
reevaluated where their inputs inherently change.

Validity is checked against reviewed explicit node input/environment declarations
and dependency provenance, not a heuristic change classifier. Manual groups never
come from this check. Shared runtime, harness, lockfile, test/config/toolchain or
graph changes conservatively invalidate all potentially affected nodes. If the
dependency/input boundary cannot be established reliably, rerun the uncertain
scope. Some native phases require the exact same run/candidate/resource IDs;
never transplant old production-fixture/scanner phases into a new identity merely
because file hashes match. Explain and rerun those real prerequisites together.

Recovery algorithm under owner-approved D-02 (implementation remains pending):

1. Discover the exact selected native cases, including dataset identities, and
   record native case-start/completion evidence using a supported, bounded adapter.
   A progress dot, last printed name or parent exit 1 is not a reliable case/signal.
2. Preserve failed logs, JUnit and worker signal before supplemental execution.
   Confirm actual worker SIGSEGV/139 separately from assertion/warning/risky/
   deprecation, setup, selection, cleanup, OOM, timeout and other signals.
3. When case identity is reliable and isolation/cleanup succeeds, permit at most
   two additional executions of that exact case for the original failed attempt.
   Persist attempts before launch so interruption cannot reset the retry budget.
   Preserve the selected native execution mode and account for supplemental
   concurrency separately; isolated-case success is not full parallel-suite PASS.
4. PASS after retry retains crash history. Repeated confirmed 139 becomes
   interrupted under the owner exception; ordinary failures stay blocking and
   never consume a SIGSEGV retry allowance as if they were crashes. Attempt the
   remaining safely executable backend cases rather than abort unrelated groups.
5. If attribution is unreliable, record unknown identity/coverage. Derive remaining
   or uncertain cases from native discovery minus confirmed completions, then
   execute explicit bounded selections through the same supported runner after
   safe cleanup. Do not invent which case crashed, claim that supplemental runs
   establish the original identity, or repeatedly rerun the full suite. Cases with
   ambiguous original attempt counts retain that uncertainty; no fabricated
   three-attempt claim or retry-budget reset is permitted. Unsafe selection or
   cleanup stops recovery and remains a blocker.
6. Classify every discovered selected case as initial PASS, PASS after retry,
   confirmed SIGSEGV interruption, ordinary failure, not executed, or unknown/
   incomplete evidence. Keep native skips/policy exclusions separately with their
   reasons; never count them as PASS. Disclose duplicate supplemental coverage.
7. Consolidate ordinary backend failures, implement repairs in inserted points and
   run the complete affected backend scope in its failing mode until resolved
   under the approved exception. The complete affected-area repair requirement
   is retained; a filtered regression alone does not complete a repair.
8. Reassess evidence validity after consolidated repairs, rerun affected nodes and
   real prerequisites, then assemble final selected-scope acceptance. Unchanged
   frontend/tooling/docs results are not invalidated merely by worker 139. Source
   changes that touch them do invalidate them. Unknown unexecuted cases cannot
   be silently accepted as confirmed SIGSEGV interruptions.

The owner exception retains its corrected-runtime retirement condition. Bounded
recovery is not a new runtime tolerance, security waiver or customer acceptance
policy. Readable status must show interruptions and ordinary failures plainly;
release validation must consume the separate owner disposition without rewriting
the native coordinator to passed. Background launch still ends the agent turn and
uses supported status; resumption after context loss is explicit, never log-tail
guessing or hidden detached orchestration.

## Implementation plan

Every implementation point requires committed work,
synchronized current-state docs, affected owner/customer instruction-product
assessment and recorded necessary focused evidence. No routine suite/check runs
are implied by a point ending. Broad tests, static analysis, integration/browser
matrices, images and scanners remain in point 12. Mandatory hooks stay in force.
Dependencies below refer to completed points, not approval inferred from time.

- [x] 1. Comprehensive baseline audit and owner approval (Gate B).
  - **Objective:** establish the complete source-grounded baseline before changes.
  - **Preconditions:** Gate A metadata/process dispositions, explicit activation,
    correct roadmap branch and refreshed knowledge availability/freshness. Gate A
    and separate activation are approved; Gate B accepted on 2026-11-06.
  - **Deliverables:** complete area inventories and decision-to-consumer ledger;
    concise owner summary and full technical findings retained in this roadmap;
    severity/effort/risk/dependency ranking and finite proposed correction list.
    Cover every audit area in the matrix below and external research implications.
    Include targeted rendered inspection rather than relying on source alone,
    subject to available runtime and the approved D-04 limits.
  - **Boundaries:** read source/history and existing reports first. Any small
    diagnostic or targeted browser activity names its concrete purpose, selected
    pages/dialogs/states, command/tool scope, environment/resource ownership and
    evidence limits in advance. Reuse the available development environment;
    observe loading, transitions, layout/responsiveness, console errors and suspected
    interactions where relevant. If runtime is unavailable or setup is expensive,
    retain source findings and explicitly defer the affected rendered observations.
    No source fixes, broad browser/suite/benchmark run,
    dependency change, state mutation or environment rebuild under an audit label.
  - **Acceptance/evidence:** every source area has a reviewed/no-change/unknown
    disposition, not a file-count claim. Reproduce specific suspected problems
    only within D-04; retain targeted browser observations and distinguish them
    from source findings, hypotheses and untested states. List unavailable-runtime
    limitations, unmeasured items and final rendered/automated obligations.
    Identify actual test discovery needs without treating the historical count as
    current. Read inherited RM-016 browser recovery journals without deleting them.
  - **Documentation/approval:** commit the audit evidence and updated proposal.
    Stop and receive explicit owner approval of findings/selected scope and any
    new architecture. Record rejected/deferred recommendations and reasons.

- [x] 2. Establish Runa UI/UX standards and selective frontend skill guidance.
  - **Objective:** make accepted behavior discoverable before conformity repairs.
  - **Preconditions:** approved point-1 findings and decision ledger; external
    candidates rechecked as needed before actual adaptation.
  - **Deliverables:** canonical design/interaction standards and topic-to-guide
    mapping; concise frontend selector/reference architecture; approved source
    provenance and explicit rejected approaches. Cover forms/tables/navigation,
    accessibility, localization/copy, responsive density, async/readiness/motion.
  - **Boundaries:** preserve established identity/security/replacement seams;
    prefer existing frontend skill and references. No new UI library, detector,
    skill installer, font/icon package or undocumented global state.
  - **Acceptance/evidence:** standards resolve RM-016 supersession chains and
    explain exceptions such as Table/Button progress. Representative task walk-throughs
    show relevant discovery and selective loading; small copy change does not load
    every topic. Future rendered evidence is specified, not claimed now.
  - **Documentation/approval:** update owning handbook, frontend references,
    SOURCE.md/upstream index and customer counterpart together. New visual or
    interaction premises beyond approved findings require owner disposition.

- [ ] 3. Consolidate agent guidance and define documentation conventions/checks.
  - **Objective:** remove contradictory/duplicated instructions while preserving
    essential authority and effective guidance.
  - **Preconditions:** points 1–2; approved constitution changes only where relevant.
  - **Deliverables:** authority/trigger/reference map for root AGENTS/CLAUDE,
    nine skills and customer bundle; full per-skill assessment using the scenarios
    below; one audience-aware documentation authoring standard in the handbook's
    existing documentation structure; objective extensions to existing docs checks.
  - **Boundaries:** root stays essential global constraints/routing, guides own
    procedures, skills own selection. No arbitrary length target, new rule registry,
    semantic LLM gate or permanent skill-evaluation platform. Owner exception remains
    owner-only. Do not enact D-01/D-02 before point 9's coherent tool changes.
  - **Acceptance/evidence:** every skill rated GOOD/NEEDS IMPROVEMENT/REDUNDANT/
    BROKEN with source/scenario evidence and limits; improvements re-evaluated.
    Measure actual loaded context before/after, without equating shorter with better.
    Author tests for links/anchors, metadata, heading structure and required
    properties where objective; fence/examples and immutable history are handled.
    Run only a necessary focused parser/link fixture if uncertainty warrants it.
  - **Documentation/approval:** canonical authoring rules define document types,
    headings, English terminology/style, code/API examples, signatures, tables,
    warnings, relative links/anchors, metadata and audience boundaries. Record
    unaffected-product reasons; discuss any new constitutional premise first.

- [ ] 4. Reconcile current documentation and developer journeys.
  - **Objective:** make documentation accurate and usable by a new developer.
  - **Preconditions:** points 1–3; owning code/contract facts identified.
  - **Deliverables:** reviewed document inventory and per-document disposition;
    apply the authoring standard across current handbook/maintainer/customer/App
    documentation; correct P-01/P-02, duplication, terminology, examples and links.
    Walk architecture → setup → module creation → public API → frontend composition
    → testing → deployment/update with the actual supported commands/ownership.
  - **Boundaries:** dated evidence and closed roadmaps are reviewed/classified,
    not restyled. No copies of maintainer-only procedures into customer guidance.
    Do not claim future corrections delivered; owning implementation points update
    affected current docs again when their behavior changes.
  - **Acceptance/evidence:** every current document has correction or justified
    no-change disposition; public links work in the delivered projection, command
    examples match the registry and first-run prerequisites are explicit. Source
    walk-through evidence is not native installation acceptance.
  - **Documentation/approval:** preserve document identity/source provenance and
    historical interpretation; record consolidation map and knowledge move/delete
    obligations. No approval needed for already agreed editorial corrections.

- [ ] 5. Correct approved backend, module and PostgreSQL findings.
  - **Objective:** repair demonstrated correctness/security/performance/maintenance
    problems without reconstructing working architecture.
  - **Preconditions:** Gate B approved backend list; points 2–4 standards where
    relevant; complete changed public signatures and owner/audience/entrypoint/home/
    delivery classification before implementation.
  - **Deliverables:** localized owning fixes, current consumers/generators,
    meaningful regression tests and per-module/API version assessment. Review
    Auth/Permissions/Audit/Admin, framework/runtime/startup/HTTP/worker/CLI and App
    example boundaries, errors/logging, transactions/refusals and data integrity.
  - **Boundaries:** thin controllers, real Form Requests/resources, Actions/Queries,
    no foreign persistence/private resolver shortcuts or speculative repositories.
    Preserve proofs, guarded APIs, Policies, audit atomicity, canonical locks,
    maintenance and last-administrator rules. Do not delete dynamically resolved or
    public capability merely because static references are absent.
  - **Acceptance/evidence:** findings map to corrected owners and tests; DB objects
    have a purpose/query consumer or reviewed disposition. Original migrations,
    constraints, indexes/relations, UUIDv7/bigint identity, schemas, UTC, soft deletion
    and optimistic concurrency satisfy the database guide. Query/N+1/round-trip/
    lock/transaction changes use bounded comparable measurements only where needed.
    Prepare full rollback/concurrency/security/update tests for point 12.
  - **Documentation/approval:** synchronize public/module/database/version guides
    and changelogs in this point. Actual customer data/history is protected; any
    data rebuild or significant new architecture/behavior/security premise stops
    for owner discussion. No blanket module version increments.

- [ ] 6. Correct shared frontend controls, layouts and asynchronous behavior.
  - **Objective:** repair approved shared causes before migrating view consumers.
  - **Preconditions:** standards, instruction/docs work and any required point-5
    contract changes; relevant frontend/Vue/Tailwind/security skills selected.
  - **Deliverables:** shared component/layout fixes, public typed props/slots/
    controllers, analogous consumers and meaningful behavior tests. Cover visual
    consistency, forms/feedback, table controls, modal focus/transitions, loading,
    navigation/breadcrumbs, temporal formatting and state retention as approved.
  - **Boundaries:** one HTTP wrapper, one session/security owner, localized read
    regions, no duplicate local result banners, no extra global stores/counters,
    no giant configurable component or redesign. Keep App presentation replaceable
    without protected edits/copied security logic.
  - **Acceptance/evidence:** component-to-consumer matrix accounts for all supplied
    analogues. Dirty forms survive refresh/failure; stale responses cannot cross
    identity/access boundaries; duplicate writes are prevented without blocking
    unrelated work. Known error, empty, permission-loss, conflict and slow-image
    behavior is explicit. Author keyboard/reduced-motion/mobile/PL/EN/theme and
    loading-transition scenarios; only narrow diagnostics if necessary now.
  - **Documentation/approval:** synchronize component/pattern/forms/tables/loading/
    motion/public replacement guides and both instruction products. New interaction
    semantics beyond accepted findings return to the owner.

- [ ] 7. Bring all supplied views into the agreed standard.
  - **Objective:** deliver complete consistent experiences using corrected owners.
  - **Preconditions:** points 5–6 and approved per-view corrections.
  - **Deliverables:** Auth entry/confirmation/account/security/preferences/history;
    Admin dashboard/modules/work/settings and user administration; Permissions
    definitions/access contributions; Audit history/details; root/App/reference,
    native error/mail presentation where shared standards apply. Remove justified
    filler in both locales and complete missing meaningful behavior within scope.
  - **Boundaries:** preserve current source-configuration visibility and ordinary
    App versus module options, reviewed bulk effects, source-only health/integrations,
    Audit read-only history and native owning business rules. No new module/features.
  - **Acceptance/evidence:** full view/state inventory has corrected or justified
    no-change entries; translations and accessible names are complete; permitted
    filters/sort/page/tab state survives intended navigation without sensitive
    persistence. Changes have value-based unit/E2E regressions and replacement
    consumers. Establish final rendered matrix; no broad browser acceptance here.
  - **Documentation/approval:** update owning module/frontend/localization guides,
    current examples and relevant module changelogs. Explicitly record any UI
    premise requiring owner choice rather than inventing a redesign.

- [ ] 8. Resolve approved tooling, configuration, infrastructure and knowledge issues.
  - **Objective:** remove demonstrated development friction and stale configuration
    while preserving recently refactored architecture.
  - **Preconditions:** approved audit findings; points 3–7 changes known so retrieval
    and configuration can account for moved guides/consumers.
  - **Deliverables:** local corrections to command consistency/error/duplicate
    helpers and tests; source/env/DB configuration map, examples/naming/defaults;
    dependency usage/disposition record; bounded Docker/devcontainer repairs only
    if demonstrated. Knowledge changes reuse indexing/metadata/freshness/evals.
  - **Boundaries:** no indiscriminate upgrades, new architecture or RAG model/index
    redesign. Review package activity/license/security/compatibility/tree/overlap
    before proposing dependencies; new Composer/pnpm dependency needs explicit
    approval. Keep real secrets out of inspection output. Native Linux, host-owned
    Compose, container toolchain and dev/production isolation remain.
  - **Acceptance/evidence:** changed inputs and examples match actual consumers;
    reopen remains nondestructive. Deleted/moved docs have explicit incremental
    removal/replacement and identity semantics; golden cases cover discovery,
    accepted versus superseded RM-016 rules, missing evidence and owner/customer
    exclusion. Preserve Qdrant/model caches. Prepare live knowledge/Boost/native
    infrastructure acceptance for point 12, not a rebuild to prove the audit.
  - **Documentation/approval:** command/configuration/development/knowledge guides
    and instruction references change with their code. Stage allowlisted durable
    sources before incremental indexing; no automatic knowledge bootstrap for
    navigation. Scope any required host transition in Execution handoff first.

- [ ] 9. Deliver resumable quality and controlled owner SIGSEGV recovery.
  - **Objective:** replace repeated orchestration work with honest durable evidence.
  - **Preconditions:** explicit D-01–D-06 dispositions at Gate A, Gate B acceptance
    of exact state/command/input validity design, owning tooling changes identified.
  - **Deliverables:** existing TestService/quality integration, case/attempt/cleanup
    accounting, resume preview/execution/status, owner acceptance evidence and
    release-validator support; synchronized canonical roadmap/report/cleanup rules.
    Shared neutral mechanics and private owner policy stay separately composed.
  - **Boundaries:** implement the specification above without a new runner,
    automatic group classifier, history service, runtime retries or manufactured
    native PASS. Preserve locks, worker guards, honest exit codes, exact fixture
    phase identities, process cleanup and source/customer boundaries.
  - **Acceptance/evidence:** author deterministic fixture contracts for bounded
    retries, precise/dataset and ambiguous identity, mixed ordinary/crash failures,
    remaining coverage, interruption/context recovery, exhausted attempt budget,
    cleanup refusal, concurrent resume, stale/changed inputs, graph/config changes,
    tampered/missing evidence, real prerequisite invalidation and owner/customer
    policy isolation. Small synthetic checks may resolve implementation uncertainty;
    complete tooling/backend/live acceptance stays at point 12. A live SIGSEGV
    need not be induced to prove classification; native crash evidence is retained.
  - **Documentation/approval:** update owner/public quality/testing/commands only
    for their respective audience; owner AGENTS, templates, roadmap/release guides,
    gates and customer bundle in one coherent change. Explicitly replace obsolete
    full-restart/no-retry claims where approved, retain customer policy elsewhere.
    Record temporary exception retirement, background handoff and detailed retained
    reporting/cleanup. Discuss any unresolved behavioral premise before coding.

- [ ] 10. Final qualitative review, test-value reconciliation and runtime readiness.
  - **Objective:** compare delivered scope to the approved baseline and prepare
    actual owner acceptance after implementation.
  - **Preconditions:** points 1–9 completed/committed and every approved finding
    resolved, explicitly deferred or escalated; no outstanding architecture choice.
  - **Deliverables:** before/after finding and requirement matrix; renewed skill
    scenarios/ratings; current docs/instruction-product review; test keep/consolidate/
    remove/add ledger and regression ownership; no-loss coverage reasoning. Record
    performance/test-duration comparisons only where valid comparable evidence exists.
  - **Boundaries:** qualitative source/evidence review is distinct from automated
    final suites. Tests change with owners in earlier points; this pass finds gaps,
    not an arbitrary target reduction. Any remaining corrections get explicit
    committed repair points before manual acceptance.
  - **Acceptance/evidence:** all domains have actual dispositions; no new design
    drift, unowned docs, unresolved critical/high defect or invented performance
    gain. Prepare working application, real authorized access and current data/
    assets after final formatting. Inspect actual pages/API responses and source/build
    identities; 503/stale assets are blockers, not owner acceptance readiness.
  - **Documentation/approval:** prepare a concrete acceptance sheet with current
    base URL, resolved routes, source/build and scenarios. Core platform ranges
    may require early 0.8.0 candidate preparation; obtain explicit owner approval
    and record prepared_version fields/reason under the release workflow before
    such version changes. Never fake versions or bypass maintenance. No reset.

- [ ] 11. Owner manual frontend acceptance (penultimate point).
  - **Objective:** receive the owner's explicit result on the rendered application.
  - **Preconditions:** completed implementation/review/readiness, matching current
    assets/data and usable authorized application, acceptance sheet supplied.
  - **Deliverables:** concrete clickable URLs derived from the current environment
    and actual route/page declarations; what to inspect per view; relevant roles/
    grants/configuration, data/error/dirty/loading states, viewport, theme and PL/EN
    scenarios. At most five representative views for each changed shared-component
    class; include every directly changed distinct behavior beyond that sample.
  - **Acceptance/evidence:** owner explicitly accepts the tested source/environment.
    Agent screenshots, automated PASS and silence are insufficient. Record feedback,
    revisions and accepted limits in history. Insert repairs before this point and
    repeat affected acceptance after any later UI change.
  - **Boundaries/documentation:** use supported runtime preparation and public
    security flows, not bypass credentials/guards. Preserve existing data. No final
    automated broad gate is claimed by this manual checkpoint.

- [ ] 12. Final automated verification, retained report, cleanup and release closure.
  - **Objective:** verify the complete agreed affected-area scope and finalize 0.8.0.
  - **Preconditions:** all implementation and explicit owner acceptance complete;
    clean committed candidate, exact final quality_groups/quality_rationale recorded,
    supported graph preview reviewed, resume mechanism implemented and prerequisites
    ready. Reconcile baseline/order/module versions and any prepared candidate.
  - **Deliverables:** complete selected suites/checks and native browser matrix,
    actual current-state docs/instruction review, final evidence validity/coverage
    assessment, detailed retained report, scoped cleanup and release metadata.
  - **Evidence:** use supported commands/catalogue below. After a failed gate,
    keep active, insert fix points before manual acceptance, consolidate area repairs,
    run complete affected-area checks in the failing mode, receive results before
    committing fixes, repeat affected owner acceptance, and resume/revalidate the
    final graph under the delivered approved policy. No stale PASS or skipped gate.
  - **SIGSEGV:** preserve native failures, case coverage and cleanup; controlled
    recovery plus separate owner disposition may accept confirmed 139 only. Other
    hard failures remain blocking. Do not restart unrelated valid results, diagnose
    the runtime or demand renewed acceptance merely for the known exception.
  - **Report:** retain objectives, original/final state, delivered work/findings,
    decisions/rejected alternatives, inserted repairs, unresolved limitations,
    tests/coverage/exceptions, measured or example-supported improvements, skills/
    docs impact and operational consequences. Distinguish excluded, not executed,
    unknown, interrupted and actual PASS. Prepare release-known facts before closure;
    actual merge/tag/publication results belong to Git/release evidence and chat.
  - **Cleanup:** enumerate exact task-owned temporary files/containers/networks/
    image tags/disposable volumes before removal. Inspect ownership and journal
    state; unsafe cleanup blocks acceptance. Preserve application/base test data,
    shared caches/model/Qdrant volumes, backups/recovery state and all reports/history.
    Inherited RM-016 resources need supported recovery and separate attribution.
    Record removed, retained and unknown identities; never global prune.
  - **Release:** follow existing VERSION/platform.json alignment, independent module
    versions/constraints/changelogs and unchanged App version; current-state docs
    and knowledge closure synchronization; focused supported docs/release validation;
    close/commit, merge commit, short main sanity, immutable annotated tag, push,
    GitHub Release and branch deletion. Native failed reports remain immutable;
    a separate accepted-exception disposition must not be described as uninterrupted
    native/full-graph PASS. Do not merge with other failed gates/open points or
    unsafe worktree. No production deployment is implied.

## Baseline audit and final coverage matrix

Point 1 expands each row into exact files/capabilities, findings and reviewed
no-change entries. Points 5–9 own correction/test authoring; point 12 owns complete
automated execution. Coverage includes every current source area; representative
rendered scenarios do not imply every combinatorial state was tested.

| Area                              | Required audit questions / evidence                                                                                                                                                                                                                                                                                                                               | Owning work         |
| --------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------- |
| UI identity/shared primitives     | Typography/tokens/spacing/colors/icons/forms/dialogs/tables/navigation/status/empty/error/feedback, focus, pointer and reduced motion; accepted versus superseded decisions. Include purpose-scoped rendered inspection of representative existing pages/dialogs during point 1 where the available environment permits; record deferred observations explicitly. | 1–2, 6–7            |
| Readiness/async                   | Cold/slow/error/abort/stale requests, logical reveal boundaries, dialog initialization, layout shift/flicker, background retention, optional images, duplicate submits, dirty draft and late access loss.                                                                                                                                                         | 1, 6–7              |
| Navigation/data views             | Actual routes/back/refresh; useful filters/sort/page/tabs/preferences; safe storage; real totals, bulk selection, export, long JSON, table scrolling/pinning and mobile reachability.                                                                                                                                                                             | 1, 6–7              |
| Auth/Permissions/Audit/Admin      | All current views and extension seams, source capability omission, denied/granted access, full-page password confirmation, login-only factors, account/permission loss, immutable history and own versus selected-user behavior.                                                                                                                                  | 1, 5–7              |
| A11y/PL/EN/themes                 | WCAG 2.2 AA criteria/patterns, semantic structure, labels/errors, focus trap/restore/obscuring, contrast, zoom/reflow/targets, screen-reader-relevant DOM, actual Polish/English copy and locale/timezone formatting.                                                                                                                                             | 1–2, 6–7, 11–12     |
| Frontend architecture/performance | Public composition/controller cleanup, state ownership, duplicate requests/watchers and query amplification, unnecessary renders, table/init/bundle cost; measure before optimizing.                                                                                                                                                                              | 1, 5–7              |
| Backend/Core/public contracts     | Owner/dependency direction, thin transport, type/error/refusal contracts, shared native transactions, guarded APIs/current grants/Policies, Audit/redaction, dynamic discovery and dead code evidence.                                                                                                                                                            | 1, 5                |
| PostgreSQL                        | Tables/columns/types/naming/constraints/relations/FKs/index consumers, migrations/ledgers and original-development versus actual deployed history; queries/round trips/N+1/locks/concurrency/rollback.                                                                                                                                                            | 1, 5                |
| Tests                             | Value/unique protection, duplicate assertions/scenarios, obsolete expectations, fixtures/setup costs, meaningful missing regressions, unsafe isolation, non-SIGSEGV flakes, actual modes and native timing evidence.                                                                                                                                              | 1, 5–10, 12         |
| Instructions/skills/docs          | All nine skills and customer counterparts; authority/activation/negative triggers/context; novice journeys, correct signatures/commands, current versus historical claims, public projection and links.                                                                                                                                                           | 1–4, 10             |
| Tooling/dependencies/config       | Recent refactor boundaries, duplicate/unused helpers, process/exit/report consistency; PHP/JS/development dependency usage/security; framework/module/App/env/test/prod/examples/Compose input consumers and ownership.                                                                                                                                           | 1, 8–9              |
| Docker/Dev Container              | Build/service/health/network/volume/dependency inputs, host/container transport and isolation; demonstrated defects only, no rebuild during planning/audit.                                                                                                                                                                                                       | 1, 8, 12 if changed |
| Knowledge/Boost                   | Freshness, chunk/document identity, move/delete/orphans/locks, current versus superseded retrieval, skill discovery, golden queries and customer exclusion, independent optional startup and production absence.                                                                                                                                                  | 1, 8, 12            |
| Verification/workflow             | Persistent evidence/resume/invalidation, complete backend coverage, bounded crash retries and safe fallback, roadmap decisions/context/report/manual gates, release truth and cleanup ownership.                                                                                                                                                                  | 1, 9–12             |

### Skill effectiveness scenarios

Use task simulations/source walk-throughs and narrowly scoped practical cases;
document the prompt, expected activation/reference selection, observed behavior,
context loaded and resulting quality against the canonical standard. Do not
claim a simulated outcome is an actual agent execution or rendered result.
Full practical revalidation is retained in point 10 with final automated evidence
where relevant in point 12, without a new permanent framework.

| Skill pair (owner/customer) | Positive scenario                                                                              | Negative / boundary scenario                                                                                           |
| --------------------------- | ---------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------- |
| frontend                    | Repair detail-modal readiness/feedback or dense table filtering using current shared patterns. | Backend-only action should not load visual references; trivial copy edit should not load every UI topic.               |
| vue                         | Reactive account-bound request/disposal and stale-reply repair using public controllers.       | Plain documentation edit; no independent copied session/security orchestration.                                        |
| tailwind                    | Adjust existing theme-token responsive control and verify class discoverability.               | SQL-only change; no invented design tokens/UI dependency.                                                              |
| backend                     | Correct native use-case/refusal/query amplification and owning transaction.                    | Simple wording edit; no duplicate Admin business implementation.                                                       |
| laravel                     | Form Request/resource/route/queue correction with version-aware Boost.                         | Vue styling; no guessing or Blade application UI.                                                                      |
| postgresql                  | Explain a query-driven index/constraint and scoped migration ownership.                        | UI-only change; no reset or mechanical indexes.                                                                        |
| security                    | Proof/revoked grant/audit redaction and safe source/tool boundary review.                      | No owner exception exported to customers or infrastructure authority redesign.                                         |
| docker                      | Diagnose a concrete health/dependency/readiness problem while preserving service identities.   | No speculative rebuild/prune from a documentation-only task.                                                           |
| rag                         | Correct stale/moved/deleted guidance and validate provenance/current applicability.            | Ordinary application startup must not depend on optional retrieval; copied owner material stays excluded for receipts. |

All skills also exercise discoverability, understandable instructions, equivalent
rule ownership, out-of-scope avoidance and customer operation with private files
absent. A rating requires evidence; do not assign GOOD solely for valid frontmatter.

## Final verification selection and command boundaries

Consult [owner quality catalogue](../../quality/README.md),
[public commands](../../../handbook/tooling/commands.md), and
[owner testing](../../testing/strategy.md) before execution. Commands listed here
are existing entrypoints, not commands run during drafting:

- `python3 tools/maintainer/quality-plan` with repeated `--group=NAME` previews
  selection; exact final graph/rationale must be recorded before running it.
- `python3 tools/maintainer/quality-roadmap --background` with the same selected
  groups launches the supported final roadmap handoff. End the turn; report
  `python3 tools/maintainer/quality-status`. Resume syntax is future point-9 work
  and must be documented before use; do not invent an existing `--resume` flag.
- Supported focused suites are `python3 tools/maintainer/test-backend`,
  `test-frontend`, `test-e2e`, `test-python`; `python3 tools/maintainer/test`
  aggregates backend/frontend/tooling. Keep actual supported prefixes when issuing
  commands; these names are shorthand here, not shell aliases. Preserve native
  parallel mode, guards, locks, reporting and cleanup. Public `runa:test` remains
  application-owned, not an alternate private suite launcher.
- `./bin/pnpm run build` prepares current assets and its documented prerequisites
  for manual acceptance. It is not a complete image/quality acceptance claim.
- `python3 tools/maintainer/check-docs` and `python3 tools/maintainer/check-release`
  perform focused post-release-metadata validation under their owning workflow.

Provisional final group candidates, deliberately not an executed or final
`quality_groups` declaration:

| Groups                                                 | Why / final selection condition                                                                                                                                                                                                                                                                                                       |
| ------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| docs, tooling                                          | Documentation/instruction validators, persistent test/quality mechanics and their complete Python contracts/analysis are directly changed.                                                                                                                                                                                            |
| backend, frontend, e2e                                 | Owning behavior/contracts and full supplied rendered experience are within the approved corrections; preserve complete native scopes and actual browser/console/network evidence.                                                                                                                                                     |
| database                                               | Select for changed schema/query/integrity/migration behavior; source review alone is not database acceptance. Gate B determines actual changes.                                                                                                                                                                                       |
| knowledge                                              | Instruction/doc reorganization and retrieval correctness require strict freshness/doctor/evals and Boost; source selection alone is not live RAG evidence.                                                                                                                                                                            |
| delivery, security-source                              | Customer instruction/public projection, owner-policy isolation and security-sensitive operations/evidence need source contracts, dependency audits, Gitleaks and content-bound security review.                                                                                                                                       |
| installation, updates                                  | Expected when module platform ranges/version/update contracts and delivered tooling change: verify supported customer acquisition/update and preservation through real isolated workflows. If no such contract changes, justify exclusion explicitly rather than selecting for editorial scope alone.                                 |
| development, transport                                 | Select only for actual dev startup/configuration or command/executor transport changes and their real prerequisites.                                                                                                                                                                                                                  |
| containers or security; production; fixture-acceptance | No automatic selection from an audit. Justify actual image/Docker/service, production behavior or acceptance-harness changes independently. Use Trivy/exact images where selected; never infer production/image PASS from source security. Fixture qualification is for changed harness composition, not routine orchestration state. |

Record final groups/rationale and exact transitive prerequisites after Gate B and
implementation. 0.8.0 does not require `--all` by cadence, and comprehensive source
review is not a reason to rebuild unrelated images. If actual approved changes
affect every group, select `--all` with reasons. Excluded areas are not PASS.
Selected security/AI checks stay strict; mandatory baseline/hooks are never omitted.

Rendered final coverage includes representative roles/grants/configuration/data,
1440-class desktop and smaller laptop/tablet/phone, PL/EN, light/dark, keyboard,
reduced motion, cold/refresh/error/loading/dirty/conflict/access-loss states and
console errors. Use existing private browser harness and fixtures, not public
review routes or new production UI. Record emulation, actual engines and manual
assistive-technology limits honestly; screenshots alone do not prove interaction.

## Risks, dependencies and planning completion

- Scope is intentionally broad: Gate B must produce a finite ranked list with
  explicit no-change/deferred dispositions. Do not expand into every possible
  redesign, new feature or generic abstraction.
- Persistent evidence reuse is a correctness boundary. Under-invalidation could
  accept stale checks; over-invalidation wastes work. Prefer conservative explicit
  input/dependency validation and fail closed for uncertainty.
- Parallel test crash attribution may be incomplete. Resolve the adapter design
  before promising exact per-case retries; retained unknowns are more honest than
  inferred identities. Failed cleanup remains blocking.
- Module ranges exclude 0.8.0 today. Do not recreate RM-016's stale version/build
  readiness failures or clear maintenance as a workaround. Candidate preparation
  requires its own recorded owner authorization.
- Documentation/skill edits can leave duplicate/stale indexed decisions. Incremental
  source identity/deletion and golden retrieval checks must accompany changes;
  the planning index is currently stale on branch context.
- Shared UI fixes have wide consumer reach and can invalidate owner acceptance.
  Keep the inventory, representative sample and repeat-acceptance obligations.
- Owner/customer workflow and SIGSEGV policies differ deliberately. Shared code
  must not leak owner release exceptions or maintainer data into customer delivery.
- Inherited RM-016 browser resources may still need supported recovery. Their
  historical retention is not authorization for blind removal or evidence cleanup.

Gate A and separate activation are recorded. Point 1 is authorized; corrections
and point 2 remain gated by explicit Gate B approval. Activation changes only this
roadmap and its location; no application/runtime/configuration/schema/version
changes, full suites, browser acceptance or rebuilds were performed.
The planning-only local investigation and this document do not attest remote
publication, live application readiness, current full test discovery or audit PASS.

## Request coverage index

| Original request sections | Draft location / execution owner                                                                                           |
| ------------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| 1–2                       | Overview, current-state baseline, evidence/limits and point 1                                                              |
| 3–3.4                     | RM-016 decision matrix, external research, UI/UX skill architecture, point 2                                               |
| 4–4.1                     | Instruction baseline, skill scenarios, points 1/3/10                                                                       |
| 5–5.2                     | Documentation findings, authoring/check conventions, points 3–4                                                            |
| 6–13.3                    | Frontend standards/audit matrix, points 1/6/7/10/11/12                                                                     |
| 14–20                     | Ownership/transaction baseline, backend/database/test matrix, points 1/5/10/12                                             |
| 21–25                     | Tooling/configuration/infra/knowledge baseline, point 8 and scoped final groups                                            |
| 26–31                     | D-01–D-03, verification state/SIGSEGV proposal, point 9 and point 12                                                       |
| 32–36                     | D-04–D-06, point 9, owner acceptance/report/cleanup/closure                                                                |
| 37–40                     | Gates A/B, severity/finite scope and significant-unknown discussion boundary                                               |
| 41–44                     | Ordered dependencies, final qualitative/manual/automated stages, version/module assessment                                 |
| 45–47                     | Per-point objectives/preconditions/deliverables/boundaries/evidence/docs/approval, planning restrictions and owner handoff |

## Point 1 — Baseline audit record — 2026-11-06

**Audit deliverable accepted at Gate B on 2026-11-06.** The owner approved
the reported scope and limits and authorized continuation. No recommendation below is implemented.
Activation commit: `f0131f80`. All runtime/tooling findings refer to source
`6889a98b22ee79c1ebc6344e67a8c940f0dfb000`; activation changed only this roadmap.
Fetched origin before activation; main had zero divergence. Feature/0.8.0 remains
approved. This audit neither publishes a release nor attests remote release assets.

### Method, evidence and limits

Reviewed owning source, public signatures, tests as source, current guidance and
RM-016 decision chains. A tracked-source census records exact paths in
`.runtime/artifacts/playwright/RM-2026-017-audit/source-inventory.json` (SHA-256
`d4d92fdded50cae71a9496f491de3b960f104cfa882f80677eee91d28014dfe1`).
Its 2,023 paths establish inventory boundaries, not 2,023 manually reviewed files.
The source-area and decision ledgers below distinguish inspected mechanisms,
correlated consumers and unknown behavior. No claim of exhaustive line-by-line
inspection, security certification or combinatorial test coverage is made.

Knowledge initially refused freshness: branch changed and the newly staged active
roadmap was absent from the old corpus; 208 documents/11,397 chunks. The existing
stack was available. Required incremental indexing for the activation commit
succeeded without bootstrap/rebuild; after hook formatting and a second incremental
refresh it reported current, 209 documents/11,556 chunks, zero missing/orphans and
no interrupted write. Hooks passed on the activation commit. Bounded audit-intent
retrieval for accepted loading/action/native-transaction rules led to the original
Auth administration, Admin operations, Permissions and native transaction guides;
those originals were opened. Retrieval exposed stale current-guide prose as well
as accurate contracts: freshness does not establish factual currency. An initial
unsupported `research` intent was refused with exit 2; corrected to documented
`audit`. No result from the refused invocation was used. This is navigation
experience, not knowledge doctor/evaluation acceptance.

Laravel Boost application-info succeeded: PHP 8.4, Laravel 13.37.0, PostgreSQL,
and installed package identities corresponding to the inspected declarations.
Read-only catalog queries examined relation placement, FK actions and identity
column types without reading account/business rows. No migrations, module sync,
seed, reset, write, package installation, dependency audit, scanner, test suite,
quality graph, image build or environment restart ran for this audit. Existing
mandatory commit hooks and knowledge refresh remain distinct from broad gates.

The browser work was three bounded one-off Playwright diagnostics, using the
already installed Chromium directly against existing development Nginx. No
supported suite selects these exact read-only observations without broader harness
preparation, so no internal suite runner was substituted. Purpose/scope were
announced before activity. No test spec or production route was added. Browser
contexts/processes were closed in finally blocks. Only GET requests were made;
no login, preference change or form submission occurred. Ordinary guest session
initialization by the existing server is not represented as a database reset or
business mutation. Screenshots/JSON are preserved as audit evidence, not fixtures.

### Bounded rendered observations

| Observation               | Environment and selection                                                                                           | Actual result                                                                                                                                                                                                                                                         | Limits / disposition                                                                                                                                                                                       |
| ------------------------- | ------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| R-01 root                 | Existing `http://nginx:8080/`; Chromium, fresh guest, Polish, default system/light appearance; 1440×900 and 390×900 | HTTP 200; document language pl; no horizontal document overflow; footer shows independent application 0.0.0/platform 0.7.0; first Tab reaches skip link. Screenshots visually inspected: accepted mono typography, palette, modest corners and illustration retained. | No redesign correction justified by these two samples. No English/dark/other-engine matrix or contrast certification.                                                                                      |
| R-02 login                | Actual named route `/auth/login`, same sizes; 390px context requests reduced motion                                 | HTTP 200; associated identifier/password/remember labels; username/current-password autocomplete; no overflow; Tab reaches identifier, password, remember, submit and recovery link; no pageerror or write.                                                           | No submitted validation/authentication, authenticated pages, screen reader, zoom or modal interaction acceptance. Reduced-motion media matched; complete computed-animation cancellation was not measured. |
| R-03 delayed initial read | Login at 1440×900; hold only browser GET `/api/runa/auth/entry-options`, observe pending region, then release       | Header/main/footer remain mounted; pending state has two busy elements and no input form; after release three inputs appear and busy settles to zero. No pageerror.                                                                                                   | Confirms this initial-region case only, not every image/session/Admin completion order. Running animations observed; visual smoothness and frame-time performance unmeasured.                              |
| R-04 failed initial read  | Login at 1440×900; abort only that options GET in the browser                                                       | Frame remains, no writable form appears, localized `Spróbuj ponownie` action is visible; pending state settles; no pageerror.                                                                                                                                         | Retry was not clicked. Deliberate browser-side fault, not a claimed live server failure. No protected content was bypassed.                                                                                |
| R-05 console context      | Root/login session discovery                                                                                        | Session GET returns 401 for a fresh guest; Chromium logs resource failure on root. No transport requestfailed in ordinary root reads.                                                                                                                                 | Expected anonymous discovery, not a demonstrated application defect; do not weaken API authentication to remove the console line.                                                                          |

Artifacts: `.runtime/artifacts/playwright/RM-2026-017-audit/` contains `root.json`,
`login.json`, `readiness.json`, four width screenshots and two readiness screenshots.
The observed internal URL is container-network evidence, not a promised host URL.
No owner frontend acceptance is claimed. No protected Admin/account/Permissions/
Audit dialog was opened: no authenticated browser context was available, and this
point prohibits state mutations to establish one. The test upstream
`http://nginx-test:8080/` did not resolve and 127.0.0.1:4178 refused connection.
Starting the private harness can prepare module state/assets, so it was not started
under an audit label. Those rendered observations remain explicitly deferred to
working acceptance preparation and points 11–12; they are not source-review PASS.

### RM-016 decision-to-consumer ledger

The following completes the correction index (104 entries, including 11a102a/b)
without replacing the immutable record. Each title is from RM-016's accepted
checklist; detailed disposition evidence remains in its corresponding report.
Family keys resolve to exact current source paths below. This is a source/history
correlation, not proof that every mapped consumer/state has been executed or every
historical diagnostic independently repeated. Findings are limited to A-01–A-08.
The earlier grouped decision matrix retains reasoning; this index prevents an
individual correction from disappearing behind those ranges.

Original points 1–3 / revised ARCH-001 map to FRAMEWORK plus native owning
participants; 2a/2b replace old whole-write ordering. Point 4 maps to SHELL/ENTRY,
5–6 to Admin controllers/Aggregate and public presentation, 7 to USERS/DETAIL,
8 to ACCESS/VALUES, 9 to OPS, 10/ARCH-004 to SETTINGS, 11/11b to owner acceptance,
and 12 to immutable release evidence. ARCH-002 retains CLI-only maintenance and
ARCH-003 retains security, with later password-only refinement 11a87. Code-owned
fallback branding remains alongside explicitly accepted runtime assets (11a23).

Relevant implementations and superseded revisions were compared using git show:
fd6da8be versus bffa7337 for outline/effect/mixed intent, d3e8656a for nearest-Form
submit activity, and 1f304326 for validated bounded ManagementRead inputs. These
are actual diffs, not commit-title-only evidence. Current source and the loading
review resolve circular versus geometric indicators and retained slot spacing.

| Family    | Current source anchors (repository-relative)                                                                                                                                      |
| --------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| ENTRY     | `src/runa/modules/auth/frontend/AuthEntryFlow.vue`; `src/runa/modules/auth/frontend/useAuthEntry.ts`; `src/runa/modules/auth/frontend/AuthOperationProofPageDefault.vue`          |
| SHELL     | `src/runa/modules/auth/frontend/ApplicationSessionShell.vue`; `src/runa/modules/auth/frontend/useApplicationSession.ts`; `src/runa/resources/js/layouts/ApplicationLayout.vue`    |
| READINESS | `src/runa/resources/js/composables/useViewReadiness.ts`; `src/runa/resources/js/components/AppContentLoading.vue`; `src/runa/modules/auth/frontend/ApplicationSessionShell.vue`   |
| NAV       | `src/runa/resources/js/components/AppPageNavigation.vue`; `src/runa/modules/auth/frontend/ApplicationSessionShell.vue`                                                            |
| LOCALE    | `src/runa/modules/admin/frontend/localization.ts`; `src/runa/modules/admin/frontend/messages.ts`                                                                                  |
| OPS       | `src/runa/modules/admin/frontend/AdminOperations.vue`; `src/runa/modules/admin/frontend/AdminOperationHistory.vue`                                                                |
| SETTINGS  | `src/runa/modules/admin/frontend/AdminSettingsPage.vue`; `src/runa/modules/admin/frontend/AdminApplicationSettings.vue`                                                           |
| TABLE     | `src/runa/resources/js/components/AppTable.vue`; `src/runa/resources/js/composables/useTableViewport.ts`; `src/runa/modules/admin/frontend/useAdminTable.ts`                      |
| VALUES    | `src/runa/resources/js/components/AppValuePreview.vue`; `src/runa/modules/audit/frontend/components/admin_detail.vue`                                                             |
| FEEDBACK  | `src/runa/resources/js/components/AppFormNotifications.vue`; `src/runa/resources/js/components/AppButton.vue`                                                                     |
| USERS     | `src/runa/modules/auth/frontend/components/admin_users.vue`; `src/runa/modules/auth/frontend/AuthDirectoryFilters.vue`; `src/runa/modules/auth/backend/Queries/UserDirectory.php` |
| BULK      | `src/runa/modules/auth/frontend/AuthAdminBulkAction.vue`; `src/runa/modules/auth/backend/Actions/BulkUsers.php`; `src/runa/modules/auth/frontend/AuthAdminActionGroups.vue`       |
| DETAIL    | `src/runa/modules/auth/frontend/AuthAdminUserDetails.vue`; `src/runa/modules/auth/frontend/AuthAccountDefault.vue`                                                                |
| ACCESS    | `src/runa/modules/permissions/frontend/PermissionsAssignments.vue`; `src/runa/modules/permissions/frontend/PermissionsAccessImport.vue`                                           |
| AUTHORITY | `src/runa/modules/permissions/backend/Support/PreserveAuthority.php`; `src/runa/modules/permissions/backend/Actions/ManageAuthority.php`                                          |
| PROOF     | `src/runa/modules/auth/backend/Actions/StepUp.php`; `src/runa/modules/auth/backend/Definitions/StepUpApi.php`                                                                     |
| FRAMEWORK | `src/runa/Support/Modules/Data/ModuleTransactions.php`; `src/runa/ModuleFramework/Api/OperationGuard.php`                                                                         |
| TESTS     | `tools/application/testing/runner.py`; `tools/application/quality_evidence.py`; `tests/tools/runa/unit/test_test_runner.py`                                                       |
| DEV       | `tools/application/development_config.py`; `tools/application/development_setup.py`                                                                                               |
| BUTTON    | `src/runa/resources/js/components/AppButton.vue`; `src/runa/resources/js/runa/ui/controls.ts`                                                                                     |

| RM-016 correction                                                                                                                                      | Current consumer family | Retained / superseded disposition                                                   |
| ------------------------------------------------------------------------------------------------------------------------------------------------------ | ----------------------- | ----------------------------------------------------------------------------------- |
| 11a1 — Apply owner-requested Polish Admin label correction.                                                                                            | LOCALE                  | Completed by 11a2/4.                                                                |
| 11a2 — Complete the requested panel rename in both supported languages.                                                                                | LOCALE                  | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a3 — Complete Auth operation-proof presentation and native Admin entry composition.                                                                  | ENTRY, PROOF            | Proof strength superseded by 11a87; full-page owner retained.                       |
| 11a4 — Apply owner-approved Polish/English Admin localization.                                                                                         | LOCALE                  | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a5 — Match Auth proof heading focus and cancellation styling.                                                                                        | ENTRY, PROOF            | Cancellation color superseded by 11a93/95; reading-focus rule retained.             |
| 11a6 — Stabilize Admin menu advertisement and password admission revalidation.                                                                         | SHELL                   | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a7 — Unify initial presentation readiness and nondestructive revalidation.                                                                           | READINESS, SHELL        | Whole-page boundary superseded by 11a8/9/11/16.                                     |
| 11a8 — Keep layouts visible from startup and localize loading placeholders.                                                                            | READINESS, SHELL        | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a9 — Synchronize initial content/avatar completion within the visible frame.                                                                         | READINESS, SHELL        | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a10 — Move page-level navigation into the shared top bar.                                                                                            | NAV                     | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a11 — Coordinate current navigation and Admin initial presentation.                                                                                  | READINESS, SHELL        | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a12 — Refine the Admin dashboard and module operation dialogs.                                                                                       | OPS, FRAMEWORK          | Local content Spinner superseded by 11a40; Table/Button exceptions retained.        |
| 11a13 — Repair modal operation feedback and deliver native operation history.                                                                          | OPS, FRAMEWORK          | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a14 — Align navigation hierarchy and scope the module overview.                                                                                      | NAV                     | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a15 — Cover breadcrumb discovery with shared navigation readiness.                                                                                   | READINESS, SHELL        | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a16 — Settle account-menu admission and display controls before initial reveal.                                                                      | SHELL                   | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a17 — Make module health policy code-only and repair filtered-list loading.                                                                          | OPS, FRAMEWORK          | Source-only health retained; do not restore editor.                                 |
| 11a18 — Put module controls first and review lifecycle actions in a dialog.                                                                            | OPS, FRAMEWORK          | Placement refined by 11a19; unregister preserves data.                              |
| 11a19 — Consolidate module actions and show diagnosis in a dialog.                                                                                     | OPS, FRAMEWORK          | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a20 — Translate the lifecycle confirmation Cancel label.                                                                                             | LOCALE                  | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a21 — Remove the generic module settings editor in favor of owned pages.                                                                             | OPS, FRAMEWORK          | Generic settings editor rejected; native settings remain.                           |
| 11a22 — Make optional integration configuration source-only.                                                                                           | OPS, FRAMEWORK          | Source-only integration retained.                                                   |
| 11a23 — Deliver application display defaults, uploaded branding and settings layout.                                                                   | SETTINGS                | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a24 — Enable timezone search and remove redundant settings copy.                                                                                     | SETTINGS                | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a25 — Unify form and operation failure notifications across delivered views.                                                                         | FEEDBACK, DETAIL        | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a26 — Make shared feedback mandatory in both frontend skill entrypoints.                                                                             | FEEDBACK, DETAIL        | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a27 — Unify module record browsers and system table controls.                                                                                        | TABLE                   | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a28 — Keep filter reset beside filters and format module timestamps.                                                                                 | TABLE                   | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a29 — Remove repeated registry/authorization read amplification across supplied flows.                                                               | FRAMEWORK, USERS, SHELL | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a30 — Complete the application-wide repeated-work performance review and repairs.                                                                    | FRAMEWORK, USERS, SHELL | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a31 — Deliver Auth-owned protected-section idle access and migrate Admin.                                                                            | PROOF, SHELL            | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a32 — Keep table headers and a below-header horizontal scrollbar visible during page scrolling; use typed date/time filters throughout module views. | TABLE                   | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a33 — Unify application date/time presentation and input defaults; remove timezone annotations and maintain durable frontend guidance.               | TABLE                   | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a34 — Repair compact timestamp filters, pinned-header scroll latency and exact filtered totals.                                                      | TABLE                   | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a35 — Vertically center header labels and scroll wide tables horizontally on header wheel input.                                                     | TABLE                   | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a36 — Unify localized dictionary values in module filters, tables and read-only details.                                                             | TABLE                   | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a37 — Improve module detail readability and bounded value previews.                                                                                  | VALUES, READINESS       | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a38 — Link Audit identifiers and refine shared JSON previews.                                                                                        | VALUES, READINESS       | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a39 — Animate detail-modal reads and right-align preview expansion links.                                                                            | VALUES, READINESS       | Circular detail loader superseded by 11a40.                                         |
| 11a40 — Standardize content-loading presentation across the supplied frontend.                                                                         | READINESS, SHELL        | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a41 — Put the collapsed JSON continuation marker on its own line.                                                                                    | VALUES, READINESS       | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a42 — Restore content spacing after loading-wrapper migration.                                                                                       | READINESS, SHELL        | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a43 — Complete configuration-aware Auth user administration.                                                                                         | USERS, BULK             | Approval/expiry facts superseded by 11a50/52.                                       |
| 11a44 — Complete Auth directory filtering, security facts and bulk operations.                                                                         | USERS, BULK             | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a45 — Consolidate temporal range controls across supplied views.                                                                                     | TABLE                   | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a46 — Align Auth filter density, concise labels and direct bulk buttons.                                                                             | USERS, BULK             | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a47 — Localize the unfiltered All choice in the shared Admin catalogue.                                                                              | LOCALE                  | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a48 — Audit application-wide PL/EN presentation and repair missing defaults.                                                                         | LOCALE                  | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a49 — Refine Auth bulk action review, impact counts and deleted-user presentation.                                                                   | USERS, BULK             | Final proof authority is 11a87; ordinary bulk exclusions remain.                    |
| 11a50 — Remove redundant account expiry in favor of scheduled deactivation.                                                                            | USERS, BULK             | Independent account expiry removed.                                                 |
| 11a51 — Supply configured automatic development administrator seeding.                                                                                 | DEV                     | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a52 — Consolidate account admission into activation and scheduled lifecycle.                                                                         | USERS, BULK             | Independent approval removed.                                                       |
| 11a53 — Enable the default 30-day password lifetime.                                                                                                   | USERS, BULK             | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a54 — Group related Auth bulk operations with explicit modal choices.                                                                                | USERS, BULK             | Neutral group tone superseded by 11a56/95.                                          |
| 11a55 — Restore Auth Users runtime build/source alignment after hook formatting.                                                                       | USERS, BULK             | Source/build mismatch refused correctly; build after formatting.                    |
| 11a56 — Match grouped bulk buttons to the primary outlined avatar-removal button.                                                                      | USERS, BULK             | Effect colors refined by 11a95.                                                     |
| 11a57 — Explain blocked bulk previews and refine the bulk modal.                                                                                       | USERS, BULK             | Protected-target handling refined by 11a58/60/101.                                  |
| 11a58 — Exclude every protected superadministrator from all Auth bulk mutations.                                                                       | USERS, BULK             | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a59 — Restore spacing after additional Auth bulk modal fields.                                                                                       | USERS, BULK             | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a60 — Review all Auth bulk actions and correct eligibility/refusal handling.                                                                         | USERS, BULK             | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a61 — Order user-directory filters by task and keep dates last.                                                                                      | USERS, BULK             | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a62 — Repair private development seed configuration through editor reopen.                                                                           | DEV                     | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a63 — Deliver comprehensive Auth account creation and pre-activation invitations.                                                                    | USERS, BULK             | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a64 — Refine account creation copy and administrative verification defaults.                                                                         | USERS, BULK             | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a65 — Enforce configuration-based visibility across module presentations.                                                                            | DETAIL, USERS, SETTINGS | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a66 — Unify horizontal wheel scrolling and overflow indicators.                                                                                      | TABLE                   | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a67 — Rebuild user details around owner-approved task-based forms.                                                                                   | DETAIL, ACCESS, BULK    | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a68 — Align user details with existing Auth presentation and workflows.                                                                              | DETAIL, ACCESS, BULK    | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a69 — Refine user details after the owner's eight-point review.                                                                                      | DETAIL, ACCESS, BULK    | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a70 — Recreate the local development database at the owner's explicit request.                                                                       | DEV                     | Historical owner-requested reset only; no current reset permission.                 |
| 11a71 — Replace redundant operation copy with consistent visible progress.                                                                             | FEEDBACK, DETAIL        | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a72 — Reuse secondary-email presentation and audit actionable failures system-wide.                                                                  | FEEDBACK, DETAIL        | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a73 — Replace sprawling assignments with a dedicated compact access editor.                                                                          | DETAIL, ACCESS, BULK    | Refined by 11a74/75 and 11a96/97.                                                   |
| 11a74 — Polish the dedicated access editor's visual proportions.                                                                                       | DETAIL, ACCESS, BULK    | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a75 — Complete custom access-editor interaction affordances.                                                                                         | DETAIL, ACCESS, BULK    | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a76 — Organize account actions/status, add administrative address confirmation and compact preferences.                                              | DETAIL, ACCESS, BULK    | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a77 — Rebuild local review data at the owner's explicit request.                                                                                     | DEV                     | Historical owner-requested reset only; no current reset permission.                 |
| 11a78 — Refine action-group presentation after owner review.                                                                                           | DETAIL, ACCESS, BULK    | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a79 — Omit action separators at wrapped row starts.                                                                                                  | DETAIL, ACCESS, BULK    | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a80 — Present selected-user sessions and trusted devices as tables.                                                                                  | DETAIL, ACCESS, BULK    | Omitted table capabilities corrected by 11a83.                                      |
| 11a81 — Identify trusted browsers in own and Admin security tables.                                                                                    | DETAIL, ACCESS, BULK    | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a82 — Enrich new-browser security email with recognizable context.                                                                                   | DETAIL, ACCESS, BULK    | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a83 — Complete supplied table capabilities across account and Admin views.                                                                           | TABLE                   | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a84 — Align table filter controls beneath wrapped labels.                                                                                            | TABLE                   | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a85 — Move account policy exceptions into User and expose active rules in status.                                                                    | DETAIL, ACCESS, BULK    | Expiry cap/local history superseded by 11a86/88.                                    |
| 11a86 — Allow indefinite account rules and space operation fields.                                                                                     | DETAIL, ACCESS, BULK    | Optional indefinite expiry retained; factor premise superseded by 11a87.            |
| 11a87 — Restrict second factors to login and unify password confirmation.                                                                              | PROOF, SHELL            | Current password-only operation/place confirmation; factors login-only.             |
| 11a88 — Simplify selected-user rule presentation.                                                                                                      | DETAIL, ACCESS, BULK    | Audit owns history; removed local panel stays removed.                              |
| 11a89 — Replace policy history recovery with reusable named settings snapshots.                                                                        | SETTINGS                | Snapshots retained; historical reset is not current authorization.                  |
| 11a90 — Compact module settings and expose source descriptions.                                                                                        | SETTINGS                | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a91 — Use full workspace width and pack settings groups vertically.                                                                                  | SETTINGS                | Retain accepted owning behavior; rendered/native variants unexecuted in this audit. |
| 11a92 — Simplify settings save confirmation.                                                                                                           | SETTINGS                | Question-only Save confirmation retained.                                           |
| 11a93 — Unify system action button semantics and snapshot change markers.                                                                              | BUTTON, BULK            | Solid primary and blue opener rule superseded by 11a95.                             |
| 11a94 — Repair stale component assets after final source formatting.                                                                                   | SETTINGS, USERS         | Post-format source/build alignment remains required.                                |
| 11a95 — Refine buttons to quiet outlines and outcome-based openers.                                                                                    | BUTTON, BULK            | Quiet outlines; mixed purple; final effect retained.                                |
| 11a96 — Simplify Permissions administration and extend selected-user access tools.                                                                     | ACCESS, DETAIL          | Definition-only Permissions; account tools in Auth.                                 |
| 11a97 — Refine Permissions readonly copy and selected-user import dialogs.                                                                             | ACCESS, DETAIL          | Copy/preset use separate parameter dialogs.                                         |
| 11a98 — Remove clearing from the access import mode selector.                                                                                          | ACCESS, DETAIL          | Mode intentionally non-clearable.                                                   |
| 11a99 — Make protected authority an individual and selected-account action.                                                                            | AUTHORITY, BULK         | Protected authority remains dedicated owning transaction.                           |
| 11a100 — Align protected-authority actions with existing bulk presentation.                                                                            | AUTHORITY, BULK         | Aggregate effect presentation; no per-account list restoration.                     |
| 11a101 — Skip protected last-administrator targets in authority batches.                                                                               | AUTHORITY, BULK         | Retain required administrator under canonical authority locks.                      |
| 11a102 — Repair failures from the first final affected-area verification.                                                                              | TESTS                   | Historical repairs/results only; no new case/PASS inference.                        |
| 11a102a — Complete installation/update, tooling/docs and dependency repair verification.                                                               | TESTS                   | Historical repairs/results only; no new case/PASS inference.                        |
| 11a102b — Complete frontend unit/browser repair verification.                                                                                          | TESTS                   | Historical repairs/results only; no new case/PASS inference.                        |

No closed roadmap bytes or historical acceptance reports were edited. A mapping
to a retained source family is not permission to restore a removed field, page,
editor, proof mode or reset procedure.

### Findings and finite correction proposal

Severity ranks demonstrated impact, not an unmeasured exploit or assumed defect.
High workflow findings below are delivery gaps against approved D-01–D-03; they
are not claims that the existing tool promised those new features. No Critical
runtime/security defect was demonstrated. This does not prove absence of defects.

| ID / priority | Owner, exact location and verified fact                                                                                                                                                                                                                                                                                                                                               | Impact and proposed correction                                                                                                                                                                                                                                                                                                                                                                                         | Evidence classification                                                                                                                                                                                                                                                               |
| ------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| A-01 / Medium | Handbook `design/visual-direction.md`, REF-003 shared-layout language paragraph still mandates English Admin; RM-016 11a4, root/customer AGENTS and current catalogues require Polish default.                                                                                                                                                                                        | Misleads future presentation work. Replace the current rule and preserve the historical decision in RM-016.                                                                                                                                                                                                                                                                                                            | Source-verified contradiction, extends P-01.                                                                                                                                                                                                                                          |
| A-02 / Medium | `architecture/module-transactions.md`, current native versions section says Auth 3.1.0/unreleased platform; `modules/admin/operations.md` administrative-read paragraph says unreleased Auth 2.0.0 and later Auth 4.0.0; `modules/auth/administration.md` introduction says Auth 4.0.0/unreleased 0.7.0. Current manifest is Auth 5.0.0/platform 0.7.0.                               | Current guides and retrieval can steer consumers to an obsolete delivery state. Reconcile present-tense versions/status with actual definitions and release evidence, including Admin operations' pending-acceptance prose. AuthAdministration 2.1.0, AuthProfiles 2.1.0, PermissionsAccess 2.2.0 and AuthStepUp 2.0.0 were separately matched to Definitions; do not replace interface versions with module versions. | Source-verified current-prose staleness, extends P-02; historical changelog entries are not errors solely for naming earlier versions.                                                                                                                                                |
| A-03 / High   | `tools/application/quality_service.py::QualityService.run` removes an existing unlocked quality directory with shutil.rmtree before starting another run.                                                                                                                                                                                                                             | Latest-only execution cannot preserve/reuse the required campaign/attempt evidence automatically. Extend the existing service with preserved attempts, explicit planning and conservative input/dependency validity. Never exercise deletion of current evidence as an audit reproduction.                                                                                                                             | Source-verified lifecycle, P-03/D-01; no evidence was deleted.                                                                                                                                                                                                                        |
| A-04 / High   | `tools/application/quality_evidence.py::check_evidence` requires overall passed and every selected node passed/exit 0. `quality_state.py` has native execution states only.                                                                                                                                                                                                           | Cannot consume standing separate owner acceptance of confirmed worker SIGSEGV; RM-016 used an explicit separate disposition. Add independently validated owner assessment and coverage references without rewriting native failure; maintain strict customer closure.                                                                                                                                                  | Source-verified representation gap, P-03/D-03.                                                                                                                                                                                                                                        |
| A-05 / High   | `tools/application/testing/runner.py::TestService.run_scope/execute` records suite exit/cleanup; `ScopeResult` and results schema lack discovered-case/worker/start/completion/attempt identity. Owner composition is `tools/maintainer/test_cli.py::OWNER`.                                                                                                                          | Aggregate exit 1 cannot establish the exact interrupted case or recovered coverage. Extend the existing lifecycle with persistent bounded accounting/adapter and original-plus-at-most-two exact-case retries only after confirmed SIGSEGV. Do not infer identity from the last name/dot or parent exit.                                                                                                               | Source-verified missing proposed capability, D-02. Exact native parallel adapter reliability remains an explicit design risk, not a proven implementation.                                                                                                                            |
| A-06 / Medium | `tools/application/docs_checks.py::links` strips fragments before resolution; its fence state stores only character, not opening length/valid closing syntax.                                                                                                                                                                                                                         | Existing file PASS cannot detect a dead heading link; nested/longer fenced examples can be misinterpreted. Extend this checker with explicitly specified local anchor/fence behavior and objective fixtures for duplicate headings, inline markup, Unicode, code examples and customer logical paths.                                                                                                                  | Source-verified parser gaps/P-07. No claim that every current fragment is broken or that a new Markdown framework is needed.                                                                                                                                                          |
| A-07 / Low    | Frontend owner/customer SKILL entrypoints accumulate topic-specific mandates; e.g. every changed view routes to full forms guidance. Owner entrypoint 5,502 bytes; customer 7,240; forms 30,821 and components 70,196. Vue `references/routing-async-performance.md` unconditionally says lazy-load route components while the frontend UX reference limits splitting to heavy areas. | Discovery is useful, but trivial copy tasks can load irrelevant procedure and route guidance can imply speculative splitting. Make topic/negative triggers and section-level selection explicit, consolidate summaries in canonical guides, qualify splitting by actual architecture/measurement. Retain essential feedback/security rules.                                                                            | Source-backed instruction-quality finding; increased agent error/time is unmeasured, not a benchmark. No arbitrary size target or dedicated UX skill justified yet.                                                                                                                   |
| A-08 / Medium | `src/runa/resources/js/components/AppFormNotifications.vue` watch source contains error/success/invalid/attempt but omits locale, while callback builds the default invalid title from props.locale. `runa/ui/index.ts` exports this component directly; AppToastViewport changes labels but does not translate message.title.                                                        | A mounted invalid=true, error='', success='' notification can retain Polish text after locale changes to en. Include locale in the owning update path, retain the existing message identity/single notification, and add a PL→EN→PL regression without resetting form/controller.                                                                                                                                      | Missing reactive dependency verified in source; visible outcome deduced from explicit Vue watch semantics. Reproduction scenario specified, not claimed as a rendered reproduction. Existing feedback.test.ts checks invalid text/repeated attempts, not this locale-only transition. |

| Finding   | Stage / dependencies                                         | Effort; risk                    | Alternatives / disposition                                                                                                                                                                     |
| --------- | ------------------------------------------------------------ | ------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| A-01      | 2/4 after Gate B                                             | S; low                          | Leaving both rules contradicts accepted identity. Correct owning current prose, no historical edit.                                                                                            |
| A-02      | 4; Definitions/manifests remain authority                    | M; low/medium                   | Blind number replacement risks corrupting API versions/history. Review each present-tense claim and metadata in its context.                                                                   |
| A-03/A-04 | 9; exact supported input/state/command design recorded first | L; high evidence-integrity risk | Manual copying remains possible but fails the approved durable workflow. A parallel runner/history service adds duplicate authority; reject.                                                   |
| A-05      | 9; native event/discovery adapter and cleanup ownership      | L; high attribution risk        | Whole-suite retries waste work and were rejected. Ambiguous identity must use disclosed bounded remaining coverage and preserve unknown attempt counts. Stop recovery on unsafe cleanup.       |
| A-06      | 3; define objective authoring/anchor semantics               | M; medium parser compatibility  | File-only checks retain an acknowledged blind spot. Reject subjective writing/LLM scores as hard gates.                                                                                        |
| A-07      | 2–3, reassess 10                                             | M; medium guidance-loss risk    | Keep nine skills and improve routing first. A new UX skill remains optional only with demonstrated activation benefit. Both products need assessment; no owner authority in customer guidance. |
| A-08      | 6; regression and public component guide                     | S; low/medium shared reach      | Remounting notifications/forms on locale change loses state; translating at each consumer duplicates the defect. Correct the common owner.                                                     |

Proposed finite implementation scope is A-01–A-08 plus already approved D-01–D-06
and required release/module-range alignment. No backend business/schema repair,
new dependency, data rebuild, infrastructure rewrite or new per-view redesign is
selected by the evidence above. Points 5, 7 and 8 retain review/disposition work;
they must not manufacture changes to fill a checklist. Any additional correction
needs a recorded evidence-backed addition to the approved finite list, with owner
discussion for significant behavioral/architectural assumptions. No blanket
permission to optimize, remove tests or refactor working Core is inferred.

### Source-area inventory and disposition

Paths in this table are repository-relative; grouped paths cover the census, not
just the currently open IDE files. Reviewed/no-change means no correction selected
from the stated evidence, not all tests passing. Unknown means the stated property
was not established; it cannot support release acceptance.

| Source area / owning files                                                                                                                             | Reviewed conclusion                                                                                                                                                                                                                                             | Remaining unknown / final obligation                                                                                                                                               |
| ------------------------------------------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Foundation, Runtime, Providers; Application.php, MaintenanceState.php, RuntimeHeartbeat.php, RunaServiceProvider.php, AuthTransportServiceProvider.php | Native and derived maintenance stay independent; queue heartbeat has explicit worker context; source registration/configuration and Fortify ignoreRoutes have real owners. No demonstrated maintenance defect; preserve guards.                                 | Live failure/reconnection/queue/scheduler matrices and reused-worker behavior unexecuted.                                                                                          |
| Contracts, Support, ModuleFramework, Integration; ModuleTransactions.php, OperationGuard.php, TransactionSecurity.php, PermissionGate.php              | Native source-owned transaction phases, canonical registry/authority/subject locks, guarded rechecks, proof consumption, rollback and refusal are present. Record-policy arguments do not receive a generic grant bypass. No replacement architecture selected. | Concurrency/deadlock/rollback/late access-loss properties require native tests. Other framework engines are inventoried, not individually certified.                               |
| Auth backend; StepUp, LoginController/LoginRequest/SessionResource, TransactionSecurity, UserDirectory, AdministrationApi/ProfilesApi/StepUpApi        | Thin login transport and password-only native proof; bounded authoritative directory filters/sorts and declared versions. No factor-as-operation-password or foreign-persistence correction selected.                                                           | Every lifecycle/bulk/mail/operator branch, privacy attack path and real grant combination not executed. Preserve default eligibility/proof/Audit invariants in final backend area. |
| Permissions backend; AccessTransaction, AccessApi and Management/Definitions families                                                                  | Registry→authority order, owning proof and manual transactional Audit; guarded ordinary access distinct from protected authority. No superadmin bypass or generic Admin engine justified.                                                                       | Batch last-admin retention/revision races and every definition mutation require existing native coverage.                                                                          |
| Audit backend; RecordEvent/RecordAttempt, ReadEvents, storage/migrations                                                                               | Successful records use owning connection; attempts have independent entry; page summaries avoid payload trees, counts precede cursor and sorting uses validated source fields. Preserve immutable history.                                                      | Large/hostile payload bounds, rollback/redaction and authorized reads not exercised. No index removal from source appearances alone.                                               |
| Admin backend; Actions/Aggregate and AggregateMutation, Queries/Projection, fixed integrations                                                         | Aggregate prepares source-owned participants and uses ModuleTransactions; ordinary independent operations retain their own protocol. No direct cross-module persistence replacement selected.                                                                   | Complete declaration/generator/record-policy matrix, reviewed native effects and all resource-boundary cases remain final tests.                                                   |
| Database/Identity/Settings/Images/Logging/Mail, technical migrations, public routes/controllers and configuration                                      | Inventoried platform owners with distinct public/internal placement. Sample migrations, public settings contracts, structured-redaction and native mail guides reviewed for boundary consistency. No deletion merely for absent static imports.                 | Each untraced helper/formatter/image path is not a security/performance PASS; no standalone new capability selected.                                                               |
| App source, AppServiceProvider, resources/js/App.vue/main.ts, reference module declarations/controllers/model/migration                                | App remains minimal DefaultApplication composition; optional reference has named guarded API/HTTP/job/command/schedule owners. Customer presentation stays App-owned.                                                                                           | Full acquisition/generator/independent consumer/runtime workflows unexecuted. App version remains 0.0.0.                                                                           |
| Shared frontend resources/js/{components,composables,lib,runa}, CSS, build/error/native templates                                                      | Inspected Dialog/modal stack, FormNotifications, Button intents, Table, readiness/image and HTTP owners; preserve immediate frames/local regions, outline/effect semantics and public facades. A-08 selected; no visual redesign.                               | Full focus restoration, modal portals, table pinning/exports, dark/English/mobile zoom and image races are not certified by source samples.                                        |
| Auth frontend; session shell/useApplicationSession, useAuthEntry, own/admin account/default/detail/creation/security/preferences/history consumers     | One session/public composition owner and explicit identity/disposal fences; root/login observed. All supplied consumer families inventoried. No independent copied security controller selected.                                                                | Authenticated dirty/reconciliation/operation-proof/account-switch and security views not rendered in audit.                                                                        |
| Admin frontend; useAdminTable/useAdminForm/readiness, AdminSettingsPage, application/operations/resource-browser consumers                             | Shared controllers/table providers; snapshot load is a draft operation; current settings groups and readiness owners exist. Source-correlated with accepted corrections.                                                                                        | Protected operational/settings/data scenarios unobserved; no claim that all values, animation or tab/back state passed.                                                            |
| Permissions/Audit frontend; PermissionsAssignments/AssignmentList/AccessImport, definition consumers and Audit admin_history/admin_detail              | Owning specialized editor and shared generic table/value presentation exist; do not restore removed access page or redundant local history.                                                                                                                     | Keyboard high-cardinality selection, copy/preset reviews, JSON expansion and authorized deep links require rendered coverage.                                                      |
| src/runa/{Console,Development,Tooling,Testing,testing,frontend,build}; src bootstrap/public                                                            | Inventoried delivery/runtime/testing roles; supported test composition and development-only routing reviewed. No general Artisan/shell/reset web endpoint selected.                                                                                             | Cold dispatch, no-vendor management, full source/runtime isolation and unsupported input tests unexecuted.                                                                         |
| tools/core, tools/application, tools/maintainer, distribution, bin and hooks                                                                           | Reviewed shared test/quality/knowledge/docs/dependency/transport ownership and targeted entrypoints. A-03–A-06 own demonstrated gaps. Preserve stdlib host and container application execution.                                                                 | Every operational adapter was not executed; source inventory does not establish install/update/deploy acceptance.                                                                  |
| Dependency manifests/locks and configuration examples                                                                                                  | Laravel/Fortify have actual consumers; Vue renders supplied UI; PDF/XLSX are dynamic binary-export imports. Tests/lint/build tools have declared roles. Composer/pnpm lock updates are not selected.                                                            | Current advisory/CVE status unknown: no fresh Composer/pnpm audit/Trivy ran. No package is labelled vulnerable, unused or safe from age/name alone.                                |
| compose.yaml/compose.production.yaml, docker, .devcontainer                                                                                            | Sibling services, host-owned workspace, separate production stages and optional AI topology remain. Development Nginx observed healthy; no build/recreate/reset justified.                                                                                      | Live Docker ownership, actual production image contents and health across restarts unobserved without host access.                                                                 |
| .agents, root AGENTS/CLAUDE, customer-agent, handbook/app/maintainer docs                                                                              | Both instruction products and nine paired skill entrypoints reviewed; relevant references walked below. A-01/A-02/A-06/A-07 selected; immutable history stays untouched.                                                                                        | Individual editorial accuracy for the remaining current documents is point 4's per-document ledger; no file-count claim of full prose correctness.                                 |
| tools/application/knowledge, owner config/goldens, .codex Boost configuration                                                                          | Incremental freshness/retrieval and Boost application-info work; corpus source guard explicitly excludes customer authoring data, owner receipt scopes and generated/secrets. Stale guide contents can still be retrieved.                                      | Freshness/doctor/evals, move/delete, immutable annotation applicability and receipt exclusion require final strict knowledge checks after changes.                                 |
| tests/backend, tests/frontend, tests/tools, app/runa splits and modules                                                                                | Central ownership/config composition inspected. Test source protects separate native and consumer contracts; no automatic pruning selected. New A-08 and parser/resume/accounting regressions have clear owners.                                                | Actual current case/dataset discovery and all native results remain unexecuted; RM-016's 791 is historical only.                                                                   |

### PostgreSQL catalog observations

Read-only Boost catalog inventory returned 46 ordinary tables: shared 12,
runa_auth 22, runa_permissions 10, runa_audit 2. No ordinary/view/materialized/
foreign relation was returned for public or runa_admin. All selected FK constraints
have RESTRICT on both delete/update; querying deviations returned an empty result.
All selected id columns are bigint with identity; querying deviations returned
empty. These are catalog observations of the configured development database,
not migration replay, row validity or query-plan performance evidence.

Inspected Auth users CREATE, Permissions user_roles CREATE and Audit events CREATE
with the canonical database guide. Naming/identity helpers, UTC moments, optimistic
versions, same-module FKs and foreign UUID references have source owners; technical
rows do not acquire mechanical public IDs. Each remaining table maps to native
migration/ledger/session/credential/mail/preferences/permission/history/work/settings
owners rather than being presumed leftover. No runtime write, EXPLAIN ANALYZE,
index-drop or data cleanup was done. Index usefulness under actual workloads and
full constraint/public_id coverage remain unknown; retain existing indexes unless
a measured owning query review justifies change. No reset is authorized here.

### Skill effectiveness and context assessment

These are manual source walk-throughs, not spawned agents, model benchmarks or
rendered task execution. For each supplied positive/negative scenario, followed
the entrypoint and relevant reference to its canonical guide and a representative
consumer. Checked both customer entrypoint and references as product data; they
never became owner operating authority. GOOD here means the examined route is
usable, with the stated limits; final practical revalidation remains point 10.

| Pair       | Source walk / observed route                                                                                                                                                             | Negative boundary and rating (owner / customer)                                                                                                                                                |
| ---------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| frontend   | Detail read → loading.md and Dialog; feedback → forms/components and FormNotifications; access/definitions → owning module guide. Existing seven references already cover design topics. | Backend-only task has no visual trigger. A trivial view-copy change still meets broad per-view mandates: selective-loading ambiguity A-07. NEEDS IMPROVEMENT / NEEDS IMPROVEMENT.              |
| vue        | Stale account-bound request → routing-async-performance, public preference/customization/form contracts and useApplicationSession identity/cleanup.                                      | Docs-only task needs no Vue. No copied session controller authorized. Blanket route lazy-loading overstates actual routing needs, A-07. NEEDS IMPROVEMENT / GOOD for inspected customer route. |
| tailwind   | Existing class adjustment → classes-verification and palette/customization; finite static class maps and package-version check are explicit.                                             | SQL change has no trigger; no new tokens/helper dependency without need. GOOD / GOOD. Final build/render timing remains controlled by roadmap.                                                 |
| backend    | Aggregate refusal/side effect → failure-side-effects and module-transactions; examined Aggregate and Audit owner.                                                                        | Copy-only change needs no backend references; direct foreign model/repository replacement not justified. GOOD / GOOD; concurrency not executed.                                                |
| laravel    | Login transport → framework-conventions, LoginRequest/controller/resource; customer reference explicitly routes uncertain APIs to installed-version Boost.                               | Vue styling has no trigger; no Blade UI or guessed framework API. GOOD / GOOD; Boost application-info observed, no new Laravel implementation required docs search.                            |
| postgresql | Index/FK/concurrency → database.md and schema-indexes, mapped to catalog/migration/owning read.                                                                                          | UI task does not trigger reset/index work. GOOD / GOOD; performance benefit unmeasured.                                                                                                        |
| security   | Password/current grant/Audit → baseline, review-workflow and public audience contract; examined StepUp, PermissionGate and RecordEvent.                                                  | Owner 139 tolerance stays maintainer-only; no operator infrastructure authority layer introduced. GOOD / GOOD; no full vulnerability scan claim.                                               |
| docker     | Concrete missing test upstream → lifecycle-devcontainer, test resource lifecycle and actual journal; no Docker access means preserve/defer.                                              | Docs task does not imply rebuild/prune; production and optional AI remain separate. GOOD / GOOD; host-native operations not executed.                                                          |
| rag        | Branch/source stale → knowledge guide and ingestion-metadata; original-source verification and source exclusions inspected.                                                              | Application startup does not depend on knowledge; copied customer authoring/receipts cannot expand corpus. GOOD / GOOD; one retrieval and index refresh are not evaluation PASS.               |

Owner/customer entrypoint byte footprints respectively: backend 4,104/2,901;
Laravel 1,514/1,015; PostgreSQL 887/697; frontend 5,502/7,240; Vue 1,552/1,032;
Tailwind 1,196/1,020; security 1,355/1,086; Docker 1,006/719; RAG 1,283/885.
These are measured source bytes read for entrypoint inspection, not tokenizer or
loaded-context performance metrics. Selected reference reads were domain-specific;
shared canonical documents must not be counted nine times as independent cost.
Root AGENTS remains 458 lines/4,031 words/32,207 bytes; customer AGENTS
424/3,742/30,078; owner frontend 76/539/5,502; customer frontend 114/784/7,240.
Forms is 281 lines/3,933 words/30,821 bytes; components 645/8,437/70,196. No
consolidation has occurred, so before/after improvement is not claimed. Preserve
these comparable baselines for points 2–3/10. No skill is declared REDUNDANT or
BROKEN merely because another skill shares its handbook references.

External planning candidates remain research inputs, with the pinned/provenance/
license limits already recorded above. No upstream source was adopted or newly
attested during this audit. Existing frontend SOURCE.md retains its Sablier pin;
no installer/script or mandatory subagent instruction was executed. The source
walks justify improving existing routing first, not inventing a design skill now.
Before copying/adopting any external text, point 2 must inspect the exact selected
material/license and relevant primary accessibility standard; a planning URL is
not that evidence.

### Test value, configuration and developer journeys

Retain separate source ownership, native backend transaction/security tests,
frontend mounted reconciliation/component tests and real browser behavior tests:
asserting the same public requirement at these different boundaries is not by
itself duplication. `view-readiness.test.ts` verifies DOM/focus/draft retention and
identity withdrawal; `feedback.test.ts` verifies notification ownership/repeated
attempts; `auth-entry.test.ts` covers token/disabled registration/credential flows;
`test_test_runner.py` verifies native exits, cleanup failure, discovery and suite
locks; `test_quality_state.py` verifies malformed state refusal and preserved
native evidence. Their purposes differ. No test is proposed for deletion solely
from repeated labels, file length or a historic slow/crashed run.

Add A-08 regression to shared feedback ownership; extend existing documentation
contracts for A-06 and quality/test lifecycle contracts for A-03–A-05. Keep an
explicit keep/consolidate/remove/add ledger when implementation identifies actual
duplicates. Native case/dataset inventory must come through the supported owner
backend discovery route (TestService has an explicit list-tests/list-tests-xml
path) before case recovery is designed. It was not run in this read-only audit;
no present case count is inferred from PHP test-file counts or RM-016 reports.
Do not classify crash interruptions as ordinary flakes or remove their tests.

The source journey is consistent in ownership: README/setup → environment/editor
and environment-file guides → development module guide/public-api placement →
frontend customization → public/owner testing and command guides → production
architecture/deployment/update. Examples must retain their documented host versus
container transport. `DevelopmentSetup.setup_fingerprint` names dependency owner
inputs/locks/config/tooling; `dependencies.materialize` composes Runa+App inputs and
preserves locks; `DevelopmentRuntime` selects existing Compose transport. Source
declarations are not proof of native first acquisition, reopen or deployment.

Configuration map: platform defaults in `src/runa/config`, App contributions in
`src/app/config`, module source definitions/manifest versus native settings are
distinct; application presentation settings are not generic technical configuration.
Development creators/readers are `development_config/setup/runtime`; production
uses explicit Compose/env inputs; test layout/APP_ENV/worker ownership are selected
by TestService and its owner composition. No private env file contents or secret
values were printed, edited or copied into this report. No dependency version or
Docker pin change is selected merely because a newer version might exist.

### Verification mechanics: proposed implementation boundary

D-01–D-03 already approve the capability and policy direction. The following is a
concrete design proposal for Gate B, not a claim that commands/schema exist:

- Keep the existing quality root, stable lock, manual groups and executor. Introduce
  a versioned campaign record with campaign/attempt/node identities and immutable
  per-attempt native evidence references; current state becomes a validated view of
  that campaign. Archive referenced evidence before a new campaign replaces latest.
  Retention is bounded by campaign ownership/references, never deletion of evidence
  still needed for release, crash accounting or recovery.
- Extend the existing command parser with explicit resume planning and execution,
  provisionally `quality-plan --resume` and `quality-roadmap --resume --background`
  with the same manual groups. These are proposed names only; document exact
  supported signatures/types before point-9 code. Status shows native outcome,
  validity, coverage, cleanup and separate owner assessment. Customer transport
  exposes neutral mechanics only, without owner acceptance flags.
- A reviewed node input declaration binds source/config/toolchain/lock/harness/
  graph/environment assumptions and prerequisite provenance. No automatic group
  classifier. Missing/uncertain boundaries invalidate the potentially affected
  scope. A shared/runtime change invalidates all declared consumers. Runtime
  fixture/scanner phases bound to exact resource/run identity rerun together when
  that identity changes. Nonsecret environment declarations only; secrets are
  excluded from evidence/fingerprints. Unknown secret-dependent environment
  compatibility cannot be assumed from hashes or an unchanged public file.
- Existing TestService owns discovery, execution, locks and cleanup. An adapter
  records native case/dataset start/completion plus worker signal and attempt
  identity before supplemental execution. Persist retry budget before launch.
  Match exactly the case and mode; up to two additional attempts only for reliable
  confirmed SIGSEGV. Ordinary failure remains blocking. Preserve isolated retry
  concurrency separately from original parallel-suite coverage.
- Ambiguous attribution retains unknown original identity/count. Discover remaining
  selected cases minus reliable completions and run explicit bounded selections
  through the same supported runner only after safe cleanup. No fabricated exact
  crash identity, reset retry budget or full-suite restart loop. Unknown unexecuted
  cases are not acceptable SIGSEGV coverage.
- Private owner composition supplies the separate acceptance assessment validated
  against native evidence, coverage, absence of ordinary failures and cleanup 0.
  Never rewrite native failed state. Ordinary complete affected-area repair remains
  required; revalidate reuse against changes, then rerun only invalid nodes and real
  prerequisites under the delivered policy. Exception retirement stays tied to
  corrected runtime verification, not a retry PASS.

Before point 9, the exact event adapter/discovery/dataset mapping and full record/
command signatures need a source-backed design check with deterministic fixtures.
No unverified parallel case identity adapter is pre-approved as reliable. A changed
premise or inability to meet safe accounting returns to the owner; it cannot be
hidden by setting the coordinator to passed. No full native/crash experiment is
needed or authorized during point 1.

### Resources, inherited recovery and instruction products

Observed inherited journal `.runtime/tests/endpoint.json` records project
`runa-dev`, run `454f8f080fc946efb9fad9d86e33c264`; companion endpoint override
and lock remain. `tools/application/test_resources.py::EndpointService` uses exact
project/working-directory/service/run labels, suite/endpoint locks and journaled
cleanup. This journal alone does not establish whether containers are still alive.
Docker CLI is absent inside this workspace context; no live IDs/labels/mounts or
ownership were attested. DNS failure is not evidence that a container is safe to
delete. Preserve the journal/override/locks and inherited RM-016 evidence. Supported
host recovery needs its own exact live inventory before any removal; no host
transition/rebuild is required merely to finish this bounded audit.

Task-created resources: temporary Chromium contexts/processes, all closed;
`.runtime/artifacts/playwright/RM-2026-017-audit` evidence, retained. Removed Docker
containers/networks/image tags/volumes: none. Application/base test data, shared
Qdrant/model/pnpm/Python caches, backups and all prior quality/native reports
preserved. No global cleanup, cache pruning or inferred ownership.

Instruction-product assessment for point 1: only this owner roadmap changed.
Root AGENTS, owner skills and the customer bundle require no capability/rule update
for activation/audit evidence; their affected future changes are A-07 and point 9.
The customer product must not receive this roadmap or owner SIGSEGV assessment.
Current guides with A-01/A-02 remain deliberately unfixed pending Gate B. Final
quality groups are still provisional; no gate/area is labelled PASS by exclusion.

### Gate B owner handoff

Approve or amend the finite A-01–A-08 correction list, preservation of the current
architecture/identity, existing-skill-first approach, D-01–D-03 design boundary and
explicit rendered/host/discovery limits above. The release remains feature/0.8.0;
no reconfirmation of Gate A is requested. Core ranges exclude 0.8.0 today: defer
candidate version preparation to its separately authorized release procedure;
there is no permission here to bump versions or bypass maintenance.

Point 1 remains unchecked solely pending owner acceptance of this audit and chosen
scope. Stop before point 2 and all corrections. Protected rendered scenarios,
actual case discovery, performance measurements and advisory scans remain explicit
obligations where relevant; the owner may require further bounded audit evidence
before accepting Gate B. No silence or automatic check grants that acceptance.

### Gate B acceptance — 2026-11-06

The owner explicitly accepted the preceding audit handoff and authorized work.
This accepts A-01–A-08, the retained architecture/identity, existing-skill-first
approach, D-01–D-03 implementation boundary and recorded rendered/host/discovery
limits. The preceding handoff is retained as historical context; its waiting state
is resolved by this decision. Point 1 is complete and implementation may proceed
in the agreed order without another Gate A/B confirmation. Feature/0.8.0 remains
unchanged; no data rebuild, new dependency or candidate-version preparation is
authorized by this acceptance. Point 9 still requires source-backed exact state,
command/input design and a reliable native adapter before its implementation.

## Point 2 — UI standards and selective frontend guidance — 2026-11-06

Delivered after explicit Gate B acceptance. The existing visual-direction guide now
owns the topic-to-guide map and routes accepted refinements: shared Polish-default
Admin, immediate frames/local regions, geometric content loading with Table/Button/
preference-cover exceptions, quiet effect-colored outlines and purple mixed tools,
source-only technical policy, module snapshots, dedicated assignment presentation
and full-page password confirmation. A-01 is corrected in its canonical current
paragraph; immutable reference history is preserved. No UI/runtime behavior changed.

The same guide owns concise PL/EN copy, task/label/help/outcome responsibilities,
responsive density and identity. The architecture accessibility baseline now links
specific primary criteria, distinguishes reflow from text resizing, accounts for
target-size exceptions and directs actual modal focus/inert review. Existing form,
table, navigation, readiness, motion and customization guides still own their full
procedures/signatures. No duplicate design standard, new skill or component system
was created. These are clarifications of the accepted WCAG 2.2 AA/identity policy,
not new conformance certification or a different visual premise.

Updated both frontend skill entrypoints and customer topic selection, retaining
mandatory frontend activation but conditional topic/section loading. Owner a11y/UX
references now select canonical standards rather than repeating generic checklists;
all seven existing references remain. Removed the obsolete password/2FA and inline
proof recommendation from the owner layout reference in favor of the already
accepted shared full-page password contract. Public component/security ownership
and customer protected-source boundaries are preserved. A-07's frontend part is
delivered; Vue routing wording and wider skill consolidation remain in point 3.

### Grounding and provenance

Point-start knowledge status was current. A query overlapping the required Gate B
commit index refresh was refused by the exclusive writer; used focused sources,
then retried successfully after completion. Current-intent retrieval directed the
original component action semantics, shared skill workflow and frontend architecture;
opened those owners. No optional service was bootstrapped or broad AI gate run.

Applied runa-frontend and skill-creator, adapting progressive disclosure within the
existing owner/customer skill structure. Root owner/customer AGENTS/CLAUDE need no
new invariant: their frontend routing, public replacement, language/security and
instruction-product rules already apply. The two frontend SOURCE.md files and
upstream source index record the local consolidation and preserve original pins.

Checked W3C WCAG 2.2 Recommendation 2024-12-12 and the primary WAI-ARIA modal dialog
pattern on 2026-11-06. Used links/local paraphrases; no upstream text/code/assets,
installer, dependency or community skill was copied. Community planning candidates
remain research leads; no new source adaptation or license claim is inferred.
W3C criteria support technical review; APG examples are guidance, not conformance
proof. No changed original Sablier review date/revision is claimed.

### Selective-loading source walk-throughs

These are manual source walk-throughs against the changed selectors, not independent
agent runs or rendered acceptance. Each prompt was traced to its actual route:

| Prompt                                                   | Selected route / observed result                                                                                                                                     | Boundary / outcome                                                                                                                                         |
| -------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Shorten a login heading in Polish and English            | Frontend selector → visual-direction interface-copy section and owning Auth catalogue. Its explicit copy row does not select forms/tables/session/motion references. | Preserve meaning and accessible name. No controllers, extra skills or full form procedures needed solely for wording.                                      |
| Repair detail-dialog readiness and failure feedback      | Frontend → loading contract + forms feedback/recovery; Dialog's component section; a11y/motion only for changed focus/transition.                                    | Keeps local initial settlement, retained refresh/drafts, one FormNotifications owner and existing focus stack. No global loader or copied credential form. |
| Repair dense table filter layout and capability handling | Frontend → tables reference and relevant Table/provider/temporal sections; Vue/Tailwind for actual implementation.                                                   | Retains analogous Auth/Permissions/Audit consumer review and real server capability/export authority. No default session/credential reference load.        |
| Backend-only transaction/refusal correction              | Frontend description explicitly excludes backend-only work. Backend/security owners apply.                                                                           | No visual/a11y reference is selected by this skill.                                                                                                        |

For the inspected selection scenarios the frontend pair rating changes from NEEDS
IMPROVEMENT to GOOD, limited to these source routes. Final practical/rendition
revalidation remains point 10/12. No score or test-count target is introduced.

Measured UTF-8 entrypoint bytes before/after: owner 5,502 → 3,912; customer
7,240 → 2,042. Owner a11y reference 1,746 → 1,092; UX/performance 2,410 → 947.
Interface-copy section is 2,322 bytes and the optional full standards map 7,078.
A copy walkthrough now selects 6,234 owner / 4,364 customer bytes for entrypoint plus
copy section, or 13,312 / 11,442 including the whole map, excluding the common
translation source. Earlier broad per-view feedback routing could add the entire
30,821-byte forms guide to the 5,502/7,240-byte entrypoint even for that task.
These are measured selected-source footprints, not model tokens, total session
context, speed measurements or proof that shorter guidance is better. Canonical
handbook content is deliberately retained and can grow where standards need it.

### Focused evidence and remaining obligations

The skill-creator quick_validate invocation was unavailable because the frozen
Python environment has no PyYAML. No package was installed or toolchain changed.
A focused frontmatter parse using the existing Composer Symfony YAML library
passed for both selectors (name/type/description); focused local navigation review
confirmed 53 edited guidance Markdown link/heading targets. This resolves the
concrete metadata/navigation uncertainty; it is not the full documentation gate
or the future general anchor parser. Formatting used the existing Prettier tool.
No new executable skill script or low-value wording-matching test was added.

Future rendered obligations remain keyboard/focus, reduced motion, long/empty
content, responsive desktop/tablet/phone, PL/EN and both themes; logical loading,
error/retry, dirty/conflict and access loss retain the owning scenarios. No browser
acceptance, full tests, build, scanners, data operation or resource cleanup was
needed for these guidance-only edits. Preserve the audit artifacts, inherited
endpoint journal, all application/test data and shared caches. Mandatory hooks
and incremental staged-source indexing apply to this point's commit.

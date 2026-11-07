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

## Current execution state — point 10h completed, 2026-11-07

**Points 1–10 and 10a–10h are complete. Point 10i is next.**
Business reads now follow entry/explicit/query/own-action triggers; successful
same-session security checks do not cascade into Admin, preferences, shell profile
or public presentation reads. Real identity/access loss and expiry checks remain.
Recognized stale-state refusals reconcile affected data without write replay or
silently replacing original draft versions. Delivery no longer prefetches a newer
revision before writing. See the [point-10h source/evidence review](../../reviews/RM-2026-017-action-driven-refresh.md).

Bounded Chromium diagnostics pass. The new native two-user backend diagnostic
stopped at fixture login HTTP 503 before executing its case; no backend PASS is
claimed. Point 10i must align working runtime/test prerequisites and rerun it,
without guard bypass/reset. Owner acceptance 11 and complete final verification/
release 12 remain open. Platform VERSION stays 0.7.0, feature/0.8.0 projection
and all existing module/API candidates remain unchanged.

## Historical execution state — point 10g completed, 2026-11-07

**Points 1–10 and 10a–10g are complete. Point 10h is next.**
Own-account trusted devices now use the public Accordion, initially collapsed.
The table remains mounted across disclosure changes; refresh retains expansion
and query state. Trust-browser action/prerequisites remain outside; configuration
omission and keyed account disposal retain their existing security owners.

Focused Chromium diagnostics cover disclosure/query retention, capability omission,
account-switch cleanup and actual device revocation. No full suite, runtime/assets
alignment, owner acceptance or final PASS is claimed. Auth stays the unreleased
6.0.0 candidate; platform VERSION remains 0.7.0 and feature/0.8.0 is unchanged.
Refresh 10h, combined readiness 10i, owner acceptance 11 and final verification/
release 12 remain open. The preceding real Nginx 503 limitation remains for 10i.

## Historical execution state — point 10f completed, 2026-11-07

**Points 1–10 and 10a–10f are complete. Point 10g is next.**
The [interface-copy review](../../reviews/RM-2026-017-interface-copy.md) records
actual PL/EN candidates and dispositions across supplied view families. Login
has no redundant introduction; registration retains activation guidance without
repeating the form task; root copy retains factual product context without the
promotional assertion. Security/recovery instructions and public signatures remain.

Two focused current-component Chromium cases pass (PL/EN); the first test-host
compilation failure is retained in the review. Real Nginx observations returned
503 and did not reach the SPA. This is an explicit readiness limitation for 10i,
not owner acceptance or final PASS. No runtime guard/data/installation was changed.
Auth remains the unreleased 6.0.0 candidate; other module/API candidates and
platform VERSION 0.7.0 / approved feature-0.8.0 projection are unchanged.
Trusted-device disclosure 10g, refresh 10h, combined runtime/assets 10i, sole owner
acceptance 11 and sole final verification/release 12 remain open.

<a id="current-execution-state--point-10e-completed-2026-11-07"></a>

## Historical execution state — point 10e completed, 2026-11-07

**Points 1–10 and 10a–10e are complete. Point 10f is next.**
The [application Core matrix](../../../../app/modules/reference/core-capabilities.md)
now reconciles all four current Core modules with actual public APIs, executable
reference workflows, source/test owners and justified native/internal exclusions.
Two added application regressions cover edit-to-history projection/record authority
and independently denied credential writes/invalid settings. The
[current acceptance sheet](../../reviews/RM-2026-017-current-acceptance.md) replaces
the earlier scenario selection without altering its historical runtime evidence.

No runtime/API/schema change or new version is introduced by 10e. Source candidates
remain Framework API 2.0.0, Auth 6.0.0, Audit 1.1.1, Permissions 2.1.1, Admin 1.1.0
and reference 0.4.0; platform VERSION stays 0.7.0 and feature/0.8.0 is unchanged.
Copy/device/refresh points 10f–10h remain open; aligned runtime/assets and handoff
remain 10i, sole owner acceptance 11 and sole final verification/release 12.
No current rendered acceptance or final PASS is claimed.

<a id="current-execution-state--point-10d-completed-2026-11-07"></a>

## Historical execution state — point 10d completed, 2026-11-07

**Points 1–10 and 10a–10d are complete. Point 10e is next.**
The reference now supplies samples, settings/snapshots, authorized history and
protected managed-work pages through public Admin/Auth/UI facades, plus a typed
standalone/replacement workspace. Existing Ping page/widget remain. Admin 1.1.0
adds native custom-page identity/readiness/exit composition; reference remains
this roadmap's unreleased 0.4.0 candidate. Runtime alignment/assets remain 10i;
no rendered acceptance or final verification is claimed.

The approved native correction replaces settings generation arguments with semantic
review identities while preserving operational generation and exact native health/
authority/proof/Audit fences. Current source: Framework API 2.0.0, AuthPolicies
4.0.0 / Auth 6.0.0, Audit 1.1.1, Permissions 2.1.1, Admin 1.1.0 and reference 0.4.0.
These are source candidates, not a claim of development installation or release.
Platform VERSION remains 0.7.0 and the approved feature/0.8.0 projection is unchanged.

The owner-approved absorbed copy, device disclosure and action-driven refresh scope
remains in 10f–10h after reference presentation/coverage reconciliation. Combined
readiness remains 10i, sole penultimate owner acceptance 11 and sole final automated
verification/release 12. None is complete; no final PASS or owner acceptance is claimed.
The prior generation/admission design stop and native failures below remain
historical evidence, resolved by this point's documented native correction.

<a id="current-execution-state--expanded-scope-approved-2026-11-07"></a>

## Historical execution state — expanded scope approved, 2026-11-07

**Points 1–10, 10a and 10b remain complete. Point 10c is in progress and open.**
The owner approved the native settings generation/snapshot-admission correction
and explicitly combined draft RM-2026-018 with this active roadmap. Its complete
copy, trusted-device disclosure and action-driven refresh specification is now
[part of this roadmap](#absorbed-rm-2026-018-scope--owner-approved-2026-11-07).
The obsolete draft is removed; no second roadmap is activated and no separate
0.8.1 release is planned for this absorbed scope. Existing feature/0.8.0 remains
the release projection; platform VERSION stays 0.7.0.

Continue 10c with the [approved owning correction](../../reviews/RM-2026-017-reference-generation-admission.md),
recording exact native contracts/lock phases before runtime edits. Complete 10d
reference presentation, 10e capability reconciliation, 10f interface copy,
10g trusted-device disclosure, 10h action-driven refresh and 10i combined runtime
readiness. Point 11 remains the sole penultimate owner manual acceptance; point
12 remains the sole final automated verification/release. All these points stay
open; no final PASS, owner acceptance or release is claimed.

Application-owned candidate source adds sample editing/Audit, field grants,
settings review/publication, snapshots and protected managed work. Focused sample
and work/rollback diagnostics passed; settings/snapshot failures are retained and
require the approved native repair. Candidate app.reference is 0.4.0; development
installation is not claimed. Framework API remains 1.2.0 and Auth 5.0.2 pending
honest assessment of the actual correction. Prior design-stop records below
remain historical evidence, superseded by this explicit owner decision.

<a id="current-execution-state--point-10b-completed-2026-11-07"></a>

## Historical execution state — point 10b completed, 2026-11-07

Points 1–10, 10a and 10b were complete. Point 10c was next, to complete the reference
backend as an executable Core consumer. Gate B and feature/0.8.0 remained approved.
No final PASS, acceptance or release was claimed.

<a id="current-execution-state--point-10a-completed-2026-11-07"></a>

## Historical execution state — point 10a completed, 2026-11-07

Points 1–10 and 10a were complete; point 10b was next. Framework API 1.2.0 supplied
the foundation while Auth migration, reference backend/UI, renewed owner acceptance
and final verification remained open. The completion record below retains the
implementation evidence and staged-consumer boundaries at that checkpoint.

<a id="current-execution-state--point-10a-reference-repair-2026-11-07"></a>

## Historical execution state — point 10a reference repair, 2026-11-07

Owner manual review rejected the reference example's completeness. The owner
explicitly authorized a repair point before the existing point 11: establish the
executable-documentation obligation and complete useful current Core integration
examples. **Point 10a is current; point 11 remains open and must be repeated for
the repaired reference views.** Gate B and feature/0.8.0 remain approved. No final
verification, owner acceptance or release is claimed. Earlier point-7 exclusions
and point-10 completeness claims do not approve omitting these examples.

The owner subsequently approved a common framework settings mechanism with Auth
as an ordinary consumer, preserving module isolation and public-API communication.
The [approved design and work sequence](../../reviews/RM-2026-017-reference-settings-authorization.md)
supersede the initial callback-only proposal and its pending-decision state.
Repair work is split into points 10a–10e below; point 11 and final point 12 retain
their identities and order. This checkpoint records the agreed plan, not runtime
implementation or early version preparation.

<a id="current-execution-state--point-10-completed-2026-11-07"></a>

## Historical execution state — point 10 completed, 2026-11-07

**Gate B remains approved; points 1–10 are complete. Point 11 now awaits the
owner's manual frontend acceptance.** The
[point-10 review and acceptance sheet](../../reviews/RM-2026-017-point-10-review.md)
record finding/test/skill dispositions, source/build identity, usable routes and
bounded real Nginx observations. Current development assets include the opted-in
reference example. Platform remains 0.7.0, target release 0.8.0; no early version
preparation, owner acceptance, final automated verification or release is claimed.

<a id="current-execution-state--point-9-completed-2026-11-07"></a>

## Historical execution state — point 9 completed, 2026-11-07

The owner explicitly requested completion of point 9 after the correction and
rollback. That renewed instruction lifts its execution deferral for this point.
**Gate B remains approved; points 1–9 are complete. Point 10 is next and has not
started.** No manual acceptance, final verification or release is claimed.

The canonical [owner testing procedure](../../testing/strategy.md#verification-evidence-continuity)
now governs evidence continuity and bounded manual SIGSEGV handling. It replaces
the earlier standing automatic owner acceptance with explicit acceptance of
exhausted confirmed interruptions before release. Native failures remain intact.
No withdrawn point-9 tooling is restored. See the completion record at the end.

<a id="current-execution-state--point-9-correction-and-rollback-2026-11-07"></a>

## Historical execution state — point-9 correction and rollback, 2026-11-07

**Gate B remains approved. Points 1–8 are complete; point 9 is open and execution
is deferred at the owner's explicit request.** Do not continue point 9, move to
point 10, start final verification or prepare a release during this correction.
The roadmap lifecycle remains active; this is not a lifecycle pause or closure.

The owner clarified that SIGSEGV handling is an agent operating procedure, not
an automation project. Both proposed alternatives (dependency crash-reporting
changes and Runa worker supervision) are rejected for this requirement. There is
no pending architecture question. The complete
[owner clarification](../../reviews/RM-2026-017-point-9-owner-correction.md)
and the dated correction/rollback record at the end govern earlier conflicting
point-9 proposals and checkpoint claims. Historical investigations remain evidence
of the rejected direction, not current implementation instructions.

Point-9 campaign/resume code, case tracing, attempt recording and associated tests
and delivery claims are withdrawn in this correction. The general pre-1.0 owner
policy remains valid. Independently justified durable quality evidence remains a
separate requirement to assess with existing tools and explicit authorization;
this does not authorize restoring the withdrawn implementation.

The [renewed Gate B decision](../../reviews/RM-2026-017-renewed-gate-b-decision.md)
remains historical approval for the other roadmap work. Feature/0.8.0 and the
owner manual acceptance/final verification order are unchanged.

Owner scope amendment, 2026-11-06: explicitly include the supplied reference
module's visual alignment and modularity showcase completion in point 7, with its documentation in
points 4/7, shared-consumer review in point 6 and readiness/manual/final acceptance
in points 10–12. See the dated scope amendment at the end. This does not reopen
Gate B or authorize new unrelated business capabilities.

- RH-01: retain `runa_auth.users.remember_token` for deliberate Laravel integration
  compatibility. Current non-use is not sufficient dead-schema evidence. Document
  the rationale in the owning Auth/database guidance in point 5. Retaining the
  column does not guarantee arbitrary alternative authentication compatibility;
  all session, authentication, revocation and security guarantees still apply.
  No schema removal or migration is authorized for this finding.
- RH-02: a visible notification translates in place with relevant identity/focus
  preserved; an explicitly dismissed outcome stays dismissed on locale changes.
  A deliberate new operation/submission may announce a new outcome. Preserve A-08,
  add the dismissal PL/EN regression, and keep local notification ownership.
- RA-01: before code, define the exact owning read scope and transaction/freshness
  design. Preserve current authorization/availability, transactions/locks,
  rollback, same-connection invalidation, reconnect, long-lived worker safety,
  Audit and security. Compare SQL evidence and author meaningful regressions.
  No persistent authorization/eligibility cache or removed security check; a
  significant architectural change or unresolved correctness risk returns to
  the owner before implementation.

The original approval, its withdrawal, restart, renewed evidence and candidate
commits remain in history. The following restart state is historical and is
superseded only by this explicit renewed owner decision. Baseline approval is not
runtime/security/a11y/performance/test-suite certification; all named unknowns
remain with their assigned implementation/final checks. Feature/0.8.0, manual
frontend acceptance and final verification remain unchanged. No dependency,
reset, candidate-version preparation or production deployment approval is inferred.

<a id="current-execution-state--owner-restart-decision-2026-11-06"></a>

## Historical execution state — owner restart decision, 2026-11-06

**Gate B: APPROVAL WITHDRAWN — BASELINE AUDIT INCOMPLETE.**

The owner rejected the completeness of the original baseline audit and explicitly
withdrew the preceding Gate B approval. Point 1 is the current task again.
Implementation authorization is suspended until a new explicit Gate B decision.
Points 2–6 await renewed evidence-based verification and acceptance; points 7–12
are blocked. Release preparation and closure are not authorized at this stage.

The agent acknowledged that the frontend audit was substantially incomplete:
rendered inspection covered root/login, while authenticated functionality was
represented by source/history/test-source correlation. That evidence did not
justify comprehensive frontend conclusions or closing point 6. The short findings
list was not proof of platform consistency. Completeness of other areas must also
be reexamined, without presuming that their implementations are defective.

The original Gate B approval, findings, completion records, implementation commits
and native test results below remain historical evidence, not current acceptance.
No commits are reverted, reset, deleted or rewritten. Current source is audited
as it stands, with existing changes treated as candidates. In particular,
`e9f866d8`'s passing targeted A-08 regression remains valid evidence for that case,
not acceptance of the full shared frontend.

This is an in-place correction of an active roadmap under the owner's explicit
decision, not a new activation, pause, second roadmap or closed-record edit.
Canonical lifecycle frontmatter remains `status: active`; the ordered checklist
is reopened. No unsupported lifecycle value or validator bypass is introduced.
Gate A and the accepted feature/0.8.0 projection remain historical decisions;
the withdrawal does not prepare or publish that version.

### Renewed audit obligations

- Inventory actual scope before completing an area. Trace every relevant
  component/capability to inspection method, requirements/states, evidence,
  conclusion, unknowns, additional work and justified exclusions. File counts
  and inherited tests/acceptance alone are insufficient.
- Reaudit frontend components/layouts and all view families, backend/contracts,
  PostgreSQL objects/migrations and actual consumers, security, backend/frontend/
  E2E test quality and redundancy, owner/customer instructions/skills/docs,
  tooling/dependencies/configuration, Docker/Dev Container, Qdrant/retrieval,
  verification/SIGSEGV resilience and roadmap/release workflows.
- Prioritize real authenticated browser observations and meaningfully different
  shared consumers. Assess distinct complex interactions separately. Sample
  states/locales/themes/viewports/permissions by risk; source review cannot
  certify rendered behavior. D-04 boundaries still apply. Explicit access/runtime
  limitations remain open evidence gaps, not a substitute source-review PASS.
- No implementation corrections, new dependencies, resets, broad suites,
  complete browser matrices, quality graph, expensive scanners or infrastructure
  acceptance during this audit. Do not investigate SIGSEGV root causes.
- After renewed inspection, map existing points 2–6 changes to verified findings;
  identify valid work, missing work, premature closure and reconsiderations.
  Propose evidence-based reconciliation of points 2–12 without needless rework.
- Commit detailed evidence and prepare a new Gate B report: actual condition,
  area inventory/coverage, confirmed findings versus hypotheses, severity/impact/
  effort, supporting evidence, missing functionality/consistency, corrections,
  proposed plan, prior-work assessment, owner decisions and explicit limitations.
  Stop for new owner approval; the agent cannot restore Gate B itself.

The complete owner decision is retained in
[the restart decision record](../../reviews/RM-2026-017-restart-decision.md).
The renewed evidence package and proposed point reconciliation are now prepared
for a new Gate B decision; see the final renewed handoff below and
[the consolidated report](../../reviews/RM-2026-017-renewed-audit-synthesis.md).
Point 1 stays open pending that explicit decision. No area or prior implementation
point is accepted by this state correction or by the agent's report.

## Overview

### What and why

Prepare Runa 0.8.0 through an evidence-led review of the existing platform, its
presentation, contracts, tests, guidance and development workflows. Preserve the
accepted identity and architecture; correct demonstrated problems and complete
justified gaps in existing capabilities. This is a platform quality milestone,
not a new business-module programme or a wholesale redesign.

**Active roadmap; renewed Gate B approved, ordered implementation authorized.** On 2026-11-06 the owner approved Gate A,
including `release_type: feature`, `proposed_version: 0.8.0`, the 12-point planning
baseline and D-01–D-06 with the D-04 modification recorded below. The initial approval explicitly withheld activation and implementation authorization.
On 2026-11-06 the owner separately instructed activation and execution of point 1.
The roadmap is now active on its declared branch. Activation initially authorized the bounded
baseline audit only. The later renewed Gate B decision above now authorizes
ordered implementation of the accepted scope. Activation itself changed no
runtime behavior, schema, configuration or version.

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
- Document agent-directed SIGSEGV handling and preservation of valid verification
  results; assess independent durable-evidence needs separately using existing tools.
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

- 2026-11-07 — Owner approved the proposed native settings generation/snapshot
  admission correction after its explanation and comparison with RM-2026-018.
  Preserve exact health/security admission and genuine conflict refusal; equivalent
  successful health refresh must not alone invalidate deliberate publication.
  Declare exact signatures/source identity/lock phases before implementation.
- 2026-11-07 — Owner explicitly combined RM-2026-018 with this active roadmap
  and instructed removal of its obsolete draft. Absorb its full agreed copy,
  trusted-device and passive-refresh-removal scope under feature/0.8.0; retire
  the separate fix/0.8.1 projection. Keep one active branch/release, one manual
  acceptance and one final automated gate. No new dependency, reset, production
  deployment, early VERSION bump or broader standing policy is approved.

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

Historical Gate A interpretation: D-01–D03 implementation wording below is
superseded by the 2026-11-07 owner clarification and corrected point 9. It does
not authorize SIGSEGV automation or a new persistent orchestration system.

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

### Historical verification implementation design — rejected/superseded

Retained verbatim as the earlier interpretation. The 2026-11-07 owner
clarification rejects its programmatic SIGSEGV design and withdraws the current
campaign implementation. Follow the corrected direction below, not this section.

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

### Corrected verification direction — 2026-11-07

Point 9 defines the agent-directed procedure in [canonical owner guidance](../../testing/strategy.md#verification-evidence-continuity).
No new recovery engine, retry script, worker attribution protocol, test-attempt
ledger, dependency patch/update or automatic acceptance/skip is authorized.

- Preserve native reports and valid completed unrelated checks. Use existing
  supported commands for unresolved scope; rerun evidence invalidated by relevant
  source, dependency, environment/configuration or prerequisite changes.
- Inspect existing output/reports to identify a SIGSEGV-interrupted test reliably.
  Do not infer identity from a progress dot or classify unknown coverage as a
  confirmed crash. If identity is uncertain, record the limitation and use safe
  existing selections, broader backend scope only where necessary.
- The agent initiates at most two additional attempts for a reliably identified
  interrupted test. Record the original crash and each result. Stop retrying
  after success or an ordinary failure; native failed runs stay failed.
- Continue remaining safely executable backend tests; consolidate ordinary
  failures before repairs. Insert fix points and run complete affected-area
  verification in the failing mode where required. A filtered PASS is insufficient.
  Preserve unrelated still-valid evidence instead of restarting the entire graph.
- Exhausted confirmed SIGSEGV interruptions require explicit owner acceptance
  before release. Unknown/unexecuted coverage, unsafe cleanup and ordinary
  failures remain blocking. No owner tolerance is extended to customers.
- Retire the temporary SIGSEGV procedure/exception after the PHP correction and
  confirmation of stability under the canonical removal condition. The general
  rule against needlessly repeating valid verification remains.

The original D-01 durable-evidence objective remains separate for assessment.
Use existing mechanisms first; establish independently necessary scope and
existing owner authorization before proposing any tooling implementation.
No `--resume`, campaign schema or closure-validator integration is presumed.
The rollback initially left point 9 unstarted. The later renewed owner instruction
and completion record below supersede that execution deferral.

### Absorbed RM-2026-018 scope — owner-approved 2026-11-07

The owner explicitly combined the previously agreed draft into RM-2026-017 and
requested deletion of the redundant draft. The full specification below replaces
its earlier deferral to a later release. Its initial fix/0.8.1 metadata and
predecessor relation describe the former plan; this scope now ships with this
roadmap's existing feature/0.8.0 projection, subject to completed implementation,
renewed owner acceptance and final verification. RM-2026-018 was never activated
or released. Its ID remains accounted for by this decision and Git history.

Preserve the supplied identity and architecture. No broad visual redesign, new
table/accordion/authentication system, external dependency, freshness cache,
polling interval, age threshold, refresh coordinator or concurrency framework is
approved. Do not change trusted-device persistence/lifetime, account linking,
identity eligibility or authorization. No migration/reset or production deployment
belongs to these absorbed UI/refresh points. Retain initial/explicit reads and
backend write admission. Do not remove meaningful security/recovery instructions
or expand device disclosure to all platform tables. The distinct native settings
repair belongs to 10c; it is needed even after passive frontend reads are removed.

Source targets below reflect the original read-only investigation, not an
instruction to implement old paths blindly. Recheck current source after 10c–10e
and preserve the reference example's executable public-consumer obligation.

#### Interface copy review

Apply the existing [interface copy standard](../../../handbook/design/visual-direction.md#interface-copy-and-preference-controls).
Inventory view families and shared text owners; for each candidate record the
rendered location, purpose, PL/EN text and removal/retention rationale. A source
count or a blanket "no defect" conclusion is insufficient. Inspect relevant
rendered contexts using bounded observations; full browser matrices remain final.

Remove the login introduction "Podaj dane swojego konta." / "Enter your account
credentials." and its unused owning catalogue/description path as appropriate.
Do not replace it with another sentence restating the heading or field labels.
Review analogous entry and account descriptions, rather than fixing this string
alone. Keep security consequences, recovery/refusal guidance, unusual input
requirements, validation, accessible names and genuine results.

#### Trusted-device table disclosure

The target is the existing "Zaufane urządzenia" / "Trusted devices" section in
`src/runa/modules/auth/frontend/AuthAccountSecurity.vue`. The owner explicitly
confirmed that "linked accounts" in the initial request meant this table.

- On initial entry, show a clearly named disclosure control with its table
  collapsed. Reuse the public Accordion contract from
  [content containers](../../../handbook/development/frontend/components.md#content-containers-and-navigation).
- Expanding exposes the existing table and its permitted controls. Preserve
  filters, pagination, view configuration, refresh, authorized exports and
  revocation behavior. Closing/reopening during the same view must preserve
  existing table state; ordinary refresh must not force it open.
- Scope the disclosure to the device list. Keep the trust-browser action and
  meaningful two-factor prerequisite explanation outside the hidden list. Do
  not change the security rules or duplicate session/security orchestration.
- Preserve configuration-based omission of the entire disabled capability. Keep
  keyboard operation, expanded/control semantics, hidden-content focus handling,
  reduced motion, mobile/tablet/desktop and both themes/locales usable.
- Preserve existing loading/readiness and identity/access-loss clearing. Hidden
  content does not authorize retaining protected data after access loss.

#### Backend-owned consistency and action-driven refresh

The owner explicitly replaces the current blanket requirement to refresh visible
views on return/focus. A displayed snapshot may remain older than database state.
The frontend does not continuously determine whether that snapshot is current.
For a mutation it still sends the original expected version/revision or reviewed
fingerprint; it must not silently fetch and substitute a newer token to make an
old draft pass validation.

At execution, the backend validates current authority, availability, expected
version/review and business constraints within the owning transaction/locks.
Cover inserts, updates and deletes according to their semantics: insertion may
require uniqueness/reference checks rather than a preexisting record version;
bulk actions must revalidate the reviewed set/effects. Do not add versions
mechanically to idempotent actions. Reuse and correct existing owning safeguards
where necessary; no new concurrency system is planned.

On an actual stale-state rejection, return a stable specific error code. The
frontend shows a clear PL/EN explanation that the action was not performed and
refreshes only the affected data. Preserve dirty values and offer existing local
conflict resolution where necessary; new server values must not silently replace
the draft or advance its submission token without reconciliation. Never replay
the write, hide the refusal behind successful refresh, or retry it with a fresh
version. Treat removed targets explicitly. Business refusals sharing HTTP 409
retain their own messages and do not imply a data-version conflict.

Retain initial reads on view/section entry, user-requested refresh, filter/sort/
pagination reads, explicit operation previews and updates after the user's own
successful action. Preserve refresh-in-place presentation and draft retention for
these real reads; the reported loading placement is not itself a demonstrated
defect. Do not replace removed passive reads with timers or delayed equivalents.

##### Source-grounded removal and separation targets

The 2026-11-07 read-only investigation identified these owners; recheck actual
source after the predecessor closes rather than freezing today's implementation:

- `auth/frontend/useAuthAccount.ts`: remove broad account/list refresh on
  visibilitychange, focus and pageshow, plus trailing-read state used solely by
  those triggers. Retain action/conflict/explicit reads and their cancellation.
- `admin/frontend/useAdminResource.ts`: remove the visibility-triggered last-query
  read. Review blanket 409 invalidation alongside `useAdminForm.ts`; route actual
  stale-state errors to targeted conflict handling instead of refreshing on every
  unrelated business refusal.
- `auth/frontend/useApplicationSession.ts` and Admin's `useAdmin.ts`,
  `useAdminPage.ts`, `useAdminResource.ts`: separate security checks from the
  unconditional authorityRevision-to-bootstrap/page/resource refresh cascade.
  A successful check of the same session must not itself reload business views.
  Retain real identity/access-change invalidation and late-response fences.
- `auth/frontend/preferences/accountPreferences.ts`: avoid reloading an unchanged
  account's preference snapshot merely because a session check succeeded.
- Shared `resources/js/runa/ui/createApplication.ts`: remove focus/visibility and
  incidental history/session-driven public presentation refresh. Keep initial
  hydration, real identity transitions and refresh following an applicable own
  settings mutation. Session reads must not become branding refresh triggers.

These are paths under `src/runa/modules` except the explicitly shared resources
path. Review supplied App/reference consumers and analogous controllers too.
Keep necessary session/protected-place expiry and access checks, 401/403 handling,
and clearing of protected content on detected identity/access loss. Do not delete
all visibility/focus listeners indiscriminately: protected activity, modal focus
and decorative animation have independent purposes. No data-freshness reload may
be reintroduced through a security check's successful completion.

##### Guidance and acceptance

Update the owning public forms/reconciliation guide, affected loading/module/API
guides and root owner/customer instruction products and skill references in the
same implementation point. Replace obsolete passive-refresh rules rather than
appending contradictory exceptions. Keep one canonical procedure and retain only
reconciliation machinery needed for explicit reads, own actions and real conflicts.

Author regressions proving that focus/tab return does not fetch business data
through either direct listeners or session/preference/presentation cascades;
filters, pagination, manual refresh and own-action results still work. Include
two independent users/sessions: X changes or removes data while Y keeps the old
snapshot; Y's operation receives the correct backend refusal, shows the specific
message, refreshes affected data and preserves the draft without replay. Cover
insert uniqueness/reference conflicts, reviewed bulk changes, non-stale 409
refusals, actual access loss and security checks that do not reload business data.
Existing conflict tests are evidence to inspect, not a fresh PASS. Complete
backend/frontend/E2E verification remains the final point.

## Implementation plan

Every implementation point requires committed work,
synchronized current-state docs, affected owner/customer instruction-product
assessment and recorded necessary focused evidence. No routine suite/check runs
are implied by a point ending. Broad tests, static analysis, integration/browser
matrices, images and scanners remain in point 12. Mandatory hooks stay in force.
Dependencies below refer to completed points, not approval inferred from time.
The renewed Gate B decision restores ordered implementation. Points 2–6 remain
open until individually reconciled and completed; prior completion statements
below are retained as historical records only.

- [x] 1. Comprehensive baseline audit and owner approval (Gate B).
  - **Objective:** establish the complete source-grounded baseline before changes.
  - **Preconditions:** Gate A metadata/process dispositions, explicit activation,
    correct roadmap branch and refreshed knowledge availability/freshness. Gate A
    and separate activation remain recorded; prior Gate B acceptance was withdrawn.
    Renewed comprehensive audit and explicit owner approval are required.
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

- [x] 3. Consolidate agent guidance and define documentation conventions/checks.
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

- [x] 4. Reconcile current documentation and developer journeys.
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

- [x] 5. Correct approved backend, module and PostgreSQL findings.
  - **Historical completion declaration, acceptance withdrawn 2026-11-06:** retained the Gate B no-business/schema-repair
    disposition after bounded owner/consumer and database review. Corrected two
    obsolete source delivery comments (A-09); see the point-5 record and
    [backend review](../../reviews/RM-2026-017-backend.md).
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

- [x] 6. Correct shared frontend controls, layouts and asynchronous behavior.
  - **Historical completion declaration, acceptance withdrawn 2026-11-06:** corrected A-08 in the shared notification owner;
    Chromium locale-only regression passes with stable identity/focus. Consumer
    coverage and retained contracts are recorded in the point-6 evidence below.
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

- [x] 7. Bring all supplied views into the agreed standard.
  - **Objective:** deliver complete consistent experiences using corrected owners.
  - **Preconditions:** points 5–6 and approved per-view corrections.
  - **Deliverables:** Auth entry/confirmation/account/security/preferences/history;
    Admin dashboard/modules/work/settings and user administration; Permissions
    definitions/access contributions; Audit history/details; root/App/reference,
    native error/mail presentation where shared standards apply. Remove justified
    filler in both locales and complete missing meaningful behavior within scope.
  - **Reference module — customer modularity showcase:** align `app.reference`
    Admin presentation with current Runa visuals and review its coverage of delivered
    modularity capabilities. Complete justified missing working examples, including
    the current metadata-only Admin integration, through existing public contracts.
    Use the reference module's existing capabilities/data for a coherent example;
    include documented customer reproduction and meaningful behavior/visual coverage.
    Review layout, controls, feedback/readiness, desktop/tablet/mobile, PL/EN and
    light/dark. Necessary module-owned declarations, adapters and frontend work are
    included; new platform mechanisms or unrelated business features require
    separate discussion. Preserve optional development-only delivery and data.
  - **Boundaries:** preserve current source-configuration visibility and ordinary
    App versus module options, reviewed bulk effects, source-only health/integrations,
    Audit read-only history and native owning business rules. No new modules or
    unrelated business capabilities; the approved reference showcase demonstrates
    existing delivered platform features.
  - **Acceptance/evidence:** full view/state inventory has corrected or justified
    no-change entries; translations and accessible names are complete; permitted
    filters/sort/page/tab state survives intended navigation without sensitive
    persistence. Changes have value-based unit/E2E regressions and replacement
    consumers. Establish final rendered matrix; no broad browser acceptance here.
  - **Documentation/approval:** update owning module/frontend/localization guides,
    current examples and relevant module changelogs. Explicitly record any UI
    premise requiring owner choice rather than inventing a redesign.

- [x] 8. Resolve approved tooling, configuration, infrastructure and knowledge issues.
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

- [x] 9. Document agent-directed verification continuity and temporary SIGSEGV handling.
  - **Execution boundary:** the owner renewed execution with an explicit request
    to complete point 9 on 2026-11-07; completed in the recorded documentation commit.
    Earlier deferral/rollback records remain historical.
  - **Objective:** prevent unnecessary repeated verification while maximizing
    useful backend coverage through agent decisions and existing supported commands.
  - **Deliverables:** one canonical owner procedure and concise entrypoint links;
    coherent owner AGENTS, testing/quality, roadmap/release/template references
    and relevant skills. Assess customer products without extending owner tolerance.
  - **Procedure:** follow the corrected verification direction above: preserve
    valid unrelated results, inspect existing evidence, initiate at most two
    additional exact-test attempts when reliable, continue remaining backend
    coverage, consolidate ordinary failures, perform required affected-area runs
    and obtain explicit owner acceptance of exhausted confirmed SIGSEGV cases.
  - **Independent evidence requirement:** assess existing preservation/inspection
    capabilities and any genuine durable session-evidence need separately. Do
    not assume that the rejected campaign code or additional orchestration is
    required or authorized. Record actual limitations without fabricating PASS.
  - **Prohibited:** custom/replacement runners, Runa-managed workers, automatic
    retries/acceptance/skips, per-worker crash infrastructure, attempt ledgers,
    SIGSEGV state machines, Pest/ParaTest changes and scripts automating decisions.
  - **Acceptance/evidence:** bounded hypothetical walkthrough covering unrelated
    completed groups, success within two additional attempts, exhausted confirmed
    crashes, uncertain identity, consolidated ordinary failures and invalidated
    evidence. Necessary focused documentation/contract checks only; do not induce
    SIGSEGV or run broad suites to demonstrate instructions.
  - **Retirement:** remove the temporary PHP exception/procedure after confirmed
    correction and stability; retain the general valid-evidence continuity rule.
    Preserve native failures, history, data/cleanup and security safeguards.

- [x] 10. Final qualitative review, test-value reconciliation and runtime readiness.
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
  - **Reference readiness:** prepare the opted-in development reference example
    through supported nondestructive procedures so its actual Admin views and
    relevant APIs are usable with current assets. Preserve disabled-by-default
    delivery, existing data and production exclusion. Do not call a missing or
    unavailable example ready for owner acceptance.
  - **Documentation/approval:** prepare a concrete acceptance sheet with current
    base URL, resolved routes, source/build and scenarios. Core platform ranges
    may require early 0.8.0 candidate preparation; obtain explicit owner approval
    and record prepared_version fields/reason under the release workflow before
    such version changes. Never fake versions or bypass maintenance. No reset.

- [x] 10a. Standardize the module-framework settings mechanism.
  - **Owner decision, 2026-11-07:** reference is customer documentation in code.
    Every new Runa module and customer-facing capability must extend working
    examples, documentation and tests in its owning point. The rule/instruction
    checkpoint is committed in `6d4df9a9`; this implementation point stays open.
  - **Approved correction:** make safe settings publication a common framework
    capability and migrate Auth to it. Adding a generic path beside a retained
    Auth-special path does not complete this correction. See the approved design.
  - **Before runtime edits:** source-map current settings validation, revisions,
    authorization/publication participants, history/snapshots and recovery. Declare
    owner/audience, full public signatures, phases/lock order, native admission,
    supported entrypoints and implementation homes. Separate framework mechanics
    from module business effects and Core security capabilities.
  - **Implement:** common source-owned authorization/publication extension and
    reusable security orchestration in the existing native settings runner.
    Preserve independent-runner boundaries, secret redaction, source/generation
    checks, conflict handling and atomic proof/publication/Audit. Reuse existing
    history, operation and snapshot mechanisms; no second settings store/runner.
  - **Isolation:** Auth verifies passwords/proofs, Permissions decides grants and
    Audit records history through supported public APIs. Framework mechanics do
    not import private module classes or read foreign module tables. Any necessary
    reserved security bridge has an explicit supported contract and named callers;
    it must not become an ordinary customer bypass. HTTP cannot choose classes,
    actor authority, grants or proof policy.
  - **Deliver:** source-contract/native security/rollback regression scenarios,
    public authoring/API documentation, source-check/runtime descriptor support,
    honest Framework API/module version assessment and affected instruction products.
    Only necessary focused diagnostics and mandatory hooks here; complete suites
    remain point 12. Commit implementation, docs and checklist together.

- [x] 10b. Move Auth settings onto the common framework contract.
  - **Dependency:** completed 10a. Replace Core's special dispatch to Auth policy
    authorization/history with the same supported source-owned path available to
    other modules. Remove the obsolete branch rather than retaining an adapter.
  - **Keep Auth-owned:** setting definitions, cross-field business validation,
    password-proof implementation, policy consequences, exception invalidation and
    meaningful policy-domain history. Generic settings history/snapshots are not
    duplicated inside Auth. Preserve existing stored policies, credentials,
    publication/recovery evidence and installed upgrade identities.
  - **Deliver:** current Auth/Admin consumers, public contracts, dependent
    declarations, version/changelog assessment and regression scenarios for proof
    consumed exactly once, unchanged consequence behavior, denied/stale writes,
    Audit failure/rollback, trusted CLI and source/connection changes.
  - **Boundary:** no weaker security, dependency addition, reset or automatic
    business retry. Resolve only concrete uncertainty with focused diagnostics;
    full backend/security and affected-area checks remain final. Commit the point.

- [x] 10c. Complete the reference backend as an executable Core consumer.
  - **Approved native prerequisite, 2026-11-07:** correct settings review/publication
    generation semantics and snapshot admission in their owning framework paths.
    Preserve operational evidence, current health/expiry/source/connection checks,
    field grants, action-specific proof and atomic Audit. Before runtime edits,
    declare full public signatures, semantic review identity and lock phases;
    update Auth/reference/CLI/Admin/snapshot consumers and regressions together.
    No guessed generation, automatic retry, second runner/store, compatibility
    adapter or reference-only bypass. See the approved decision record.
  - **Dependency:** completed 10a/10b. Retain existing Ping, sample identities,
    source/CLI/work/events examples and optional production-excluded delivery.
    Use the same common settings contract as Auth, never a reference-only bypass.
  - **Settings:** provide validated public read/review/publication, redacted secret
    set/replace/clear handling and existing safe snapshot/history workflows.
    Distinguish committed values from drafts and source-owned technical policy.
  - **Audit/Permissions:** add useful sample editing with optimistic revisions,
    an approved audited input and an independently permissioned field/action.
    Guard backend projections and writes separately; hidden inputs do not grant
    authority. Show automatic single-model capture and explicit events where the
    operation needs them, with same-transaction rollback on Audit failure.
  - **Auth/work:** implement an actual action requiring its own password proof,
    distinct from Admin entry, plus authorized dispatch/status for existing managed
    work. Reuse native public transactional/work contracts and avoid side effects
    inside incompatible transaction phases. No fictional external integration.
  - **Deliver:** explicit module-owned APIs/declarations/Actions/Queries, grants,
    record policy, source/runtime/version changes and meaningful application-owned
    tests covering success, read/write denial, stale revision/review, proof expiry/
    replay, secret safety and rollback. Assess actual storage changes without
    resetting existing data. Complete public signature/source documentation in
    this point. Full suites remain final; commit the backend point.

- [x] 10d. Complete the reference Admin/frontend customer journey.
  - **Dependency:** completed 10c. Add coherent settings/history/snapshot, sample
    editing and protected-action/work views through public Admin/Auth/UI facades.
    Keep the current Ping page/widget. Use shared page navigation, forms, feedback,
    table/dialog and readiness contracts; no copied session or proof orchestration.
  - **Visible examples:** change an audited value and inspect authorized history;
    show/omit a field by its grant; permit reading independently of editing; perform
    a real password-protected action through Auth's full-page confirmation; edit
    settings and load a snapshot into a draft before deliberate publication; run
    existing work and distinguish admission from completion.
  - **Deliver:** Polish/English, light/dark, responsive and keyboard/focus behavior,
    retained dirty/pending/conflicting drafts, localized outcomes, capability/access
    withdrawal and documented typed replacement/composition seams. Add meaningful
    application component/consumer regressions for these states. No native browser
    validation conflict, fake operation or private platform import.
  - **Acceptance boundary:** necessary focused diagnostics only; broader browser/
    frontend matrices remain point 12. Synchronize owning presentation guidance,
    example source map and version/changelog assessment, then commit the point.

- [x] 10e. Reconcile reference capability coverage and current guidance.
  - **Dependency:** completed 10a–10d. Reconcile all current Core modules against
    actual executable examples: capability, public API/version, source entrypoint,
    declaration/Action, UI route or CLI reproduction, expected outcome and owning
    success/refusal tests. Record justified noninteractive/internal exclusions.
  - **Documentation:** replace obsolete read-only/no-settings-editor claims in
    reference/Admin guides and the acceptance sheet; synchronize owner/customer
    instruction products without granting protected-source edit authority. Preserve
    historical reports, rejected designs and native failed evidence.
  - **Boundary:** author remaining tests and resolve demonstrated example/guidance
    gaps in this coherent committed point. Complete suites remain 12; combined
    runtime preparation and owner handoff move to 10i after all absorbed changes.

- [x] 10f. Review supplied interface text and remove demonstrated filler.
  - **Dependency:** completed 10e. Apply the absorbed interface-copy specification
    to root, Auth entry/account, Admin, Permissions, Audit, optional reference,
    shared components and native error/mail presentation. Record rendered location,
    purpose, PL/EN text and removal/retention rationale for actual candidates.
  - Remove loginHelp and analogous redundant introductions where demonstrated;
    preserve actionable instructions, labels, constraints, accessible names,
    security consequences and outcomes. Use bounded rendered observations here;
    complete browser matrices remain 12.
  - Update owning catalogues/contracts, meaningful regressions, current guidance
    and module/version records; assess both instruction products. Commit this point.

- [x] 10g. Collapse the trusted-device table on initial account entry.
  - **Dependency:** completed 10f. Reuse public Accordion around the existing
    account-security device table. Preserve table state on close/reopen and refresh,
    filters, pagination, view configuration, explicit refresh, permitted export
    and revocation. Keep trust-browser action and useful prerequisites outside.
  - Preserve configuration omission, readiness, access-loss clearing, keyboard
    focus/expanded semantics, reduced motion, responsive PL/EN and light/dark.
    Author meaningful initial/disclosure/state/access regressions and synchronize
    guidance/module assessment in this committed point; no new dependency/runner.

- [x] 10h. Simplify data refresh and preserve backend-owned conflict handling.
  - **Dependency:** completed 10g. Remove direct passive focus/visibility/pageshow
    data reads and the specified session/authority/preference/branding cascades.
    Remove obsolete state/tests rather than retaining alternate refresh paths;
    add no timers, cache or new freshness/concurrency infrastructure.
  - Preserve initial, explicit, filter/sort/page, preview and own-action reads;
    separate real identity/access invalidation and necessary session/protected-place
    expiry checks from successful same-session checks. Keep late-response fences.
  - Inspect backend insert/update/delete and reviewed bulk checks; correct actual
    gaps within owning locks/transactions. Always submit original version/review;
    distinguish actual stale-state codes from unrelated business 409 refusals.
    Show specific PL/EN refusal, refresh only affected data, preserve/reconcile
    dirty drafts and never replay a rejected write or substitute a fresh token.
  - Author direct/cascade, two-user change/removal, uniqueness/reference/bulk,
    non-stale 409 and real access-loss regressions. Update canonical forms/loading/
    API guidance, both instruction products, skill references, affected consumers
    and module/API version/changelog assessments in the committed point.
    No broad suites before 12. The settings correction in 10c remains required.

- [x] 10h1. Correct readiness build failures.
  - Repair the reference snapshot delete/confirmation controls to use the public
    danger intent and the generic Table props assertion in the trusted-device test.
  - Synchronize the readiness evidence and instruction-product assessment; retry
    the supported asset build needed for manual acceptance. No final suite/gate.

- [ ] 10i. Prepare combined runtime and renewed owner acceptance.
  - **Dependency:** completed 10a–10h. Reconcile the entire combined scope and
    outstanding findings against actual source; insert explicit repairs before
    manual acceptance. Renew the qualitative/source and instruction-product review
    for new behavior rather than relying on the earlier point-10 readiness.
  - Prepare supported nondestructive updates, current assets and usable authorized
    data/access after formatting. No reset/new package/production deployment.
    Confirm actual pages/APIs, source/build identities and safe secret projections.
    Early candidate metadata preparation, if needed, still requires its existing
    separate owner approval; none is inferred from this scope merger.
  - Commit concrete URLs, representative grants/roles and a combined acceptance
    sheet: reference settings/snapshots/edits/Audit/protected work, purposeful copy,
    collapsed/expanded/empty devices, passive tab return without data reload,
    explicit refresh and actual conflict/draft behavior. Include relevant PL/EN,
    themes, viewports and keyboard states. Only the owner completes 11.
  - Complete suites/security/AI/delivery verification stays 12. These points
    precede the first final gate and do not invoke its broad repair exception.

- [ ] 11. Owner manual frontend acceptance (penultimate point).
  - **Objective:** receive the owner's explicit result on the rendered application.
  - **Preconditions:** completed implementation/review/readiness, matching current
    assets/data and usable authorized application, acceptance sheet supplied.
  - **Deliverables:** concrete clickable URLs derived from the current environment
    and actual route/page declarations; what to inspect per view; relevant roles/
    grants/configuration, data/error/dirty/loading states, viewport, theme and PL/EN
    scenarios. At most five representative views for each changed shared-component
    class; include every directly changed distinct behavior beyond that sample.
  - **Combined acceptance:** include the absorbed copy/device/refresh scenarios
    from 10i and two-user actual-conflict behavior. All changed UI returns to this
    single acceptance; prior observations do not approve newly delivered behavior.
  - **Reference acceptance:** include concrete URLs and scenarios for the directly
    changed reference Admin presentation and its relevant states; shared-component
    sampling does not omit these distinct example behaviors.
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
    supported graph preview reviewed, corrected agent procedure documented and
    prerequisites ready. Reconcile baseline/order/module versions and any prepared candidate.
  - **Deliverables:** complete selected suites/checks and native browser matrix,
    actual current-state docs/instruction review, final evidence validity/coverage
    assessment, detailed retained report, scoped cleanup and release metadata.
  - **Evidence:** use supported commands/catalogue below. After a failed gate,
    keep active, insert fix points before manual acceptance, consolidate area repairs,
    run complete affected-area checks in the failing mode, receive results before
    committing fixes, repeat affected owner acceptance, and resume/revalidate the
    unresolved final scope under the documented agent procedure, retaining valid
    unrelated evidence and rerunning invalidated checks and real prerequisites. No stale PASS or skipped gate.
  - **Reference verification:** cover the actual changed showcase contracts and
    visual behavior through their supported owning entrypoints, including relevant
    application-owned backend/frontend tests and real shared prerequisites.
    Account for reference coverage separately from owner-only suites. Justify
    migration, delivery and infrastructure checks by actual changed contracts;
    a styling-only part does not automatically require them.
  - **SIGSEGV:** the agent follows completed point-9 guidance using existing
    commands. Preserve native failures and cleanup evidence; attempt a reliably
    identified interrupted test at most twice more, continue remaining backend
    coverage and request explicit owner acceptance of exhausted confirmed crashes
    before release. Unknown/unexecuted coverage and ordinary failures remain
    blocking. No automation or root-cause investigation; retain valid unrelated
    results and rerun only invalidated/affected scope and real prerequisites.
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
| Verification/workflow             | Agent-directed evidence validity/continuity, remaining backend coverage, bounded manual crash retries and safe fallback, roadmap decisions/context/report/manual gates, release truth and cleanup ownership.                                                                                                                                                      | 1, 9–12             |

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
  `python3 tools/maintainer/quality-status`. Continuation uses existing supported
  selections and documented evidence; no `--resume` flag is delivered or presumed
  as a point-9 requirement.
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
| docs, tooling                                          | Documentation/instruction validators and earlier changed tooling contracts require final checks; the withdrawn point-9 campaign implementation does not itself justify additional scope.                                                                                                                                              |
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
- Existing reports may not identify the interrupted backend test reliably.
  Record uncertainty and use safe supported selections; do not add attribution
  infrastructure or infer a case identity. Failed cleanup remains blocking.
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

## Point 3 — Guidance ownership and documentation checks — 2026-11-06

Delivered the public `development/documentation.md` authoring guide within the
existing handbook structure. It owns document types/audiences, current versus
historical truth, headings/format-specific metadata, English technical prose,
complete API signatures and examples, tables/warnings, command context, relative
links/anchors and evidence limits. Ordinary guides gain no invented frontmatter.
Docs placement remains owned by docs/README; reusable instruction editing remains
in skills-policy. Both root instruction products route to those owners instead of
duplicating the authoring procedure. CLAUDE files retain their thin @AGENTS routing.

The existing documentation validator now composes internal stdlib Markdown-source
helpers for leading metadata, fenced examples, comments, inline/reference targets,
ATX/setext headings, Unicode/formatting-aware slugs, duplicate suffixes and explicit
HTML targets. Local Markdown fragments are validated; percent encoding is decoded
before path/anchor checks. Installed customer-template maps select their actual
customer headings, including self/peer links, rather than reading owner AGENTS at
the corresponding root path. Public owner-only/escape guards remain authoritative,
including links inside indented lists. Fenced examples are the authoring contract;
this is not a full CommonMark renderer or an external-URL checker.

Current documentation is required to have one nonempty leading H1 outside metadata/
examples. Existing required-source and format-specific metadata readers remain;
there is no new metadata registry or semantic writing gate. Private composition
retains immutable/closed/evidence exclusions and validates customer logical paths.
No closed record, publication tag or native verification report was rewritten.
The new helper is internal delivered tooling, not a public customer extension API;
its composition/status is documented in the Python and quality guides. No new
entrypoint or dependency. A-06 is implemented; broad current-doc reconciliation
and actual complete documentation checks remain points 4 and 12 respectively.

Source review also found that the maintainer README still projected `Active roadmap:
none` after activation. Synchronized that bounded projection to RM-2026-017 without
changing VERSION. This is a current-state documentation correction within A-02's
owning scope; other stale version/delivery claims remain for point 4.

### Instruction authority, triggers and assessment

| Product / pair             | Authority and reference selection                                                                                                                    | Source-walk rating / limits                                                                                                                                             |
| -------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Root owner AGENTS / CLAUDE | Receipt-free owner constraints and minimum domain routing; CLAUDE delegates to AGENTS. Docs ownership summaries route to placement/authoring guides. | Preserve constitution, quality timing, protected data and owner-only policy. No new always-loaded checklist.                                                            |
| Customer AGENTS / CLAUDE   | App scope and protected-platform boundary; public guides only; separately authored templates stay product data.                                      | Same docs routing without owner commands/acceptance policy.                                                                                                             |
| frontend                   | Every frontend change; explicit copy/control/layout/forms/table/loading/motion/performance selectors from point 2.                                   | GOOD / GOOD for point-2 positive/negative walks; rendered revalidation remains pending.                                                                                 |
| vue                        | Vue-specific components, account-bound reactivity and disposal; routing/async reference and canonical preference/form owners.                        | GOOD / GOOD after removing unconditional route lazy-loading. Preserve current architecture; split only justified heavy/isolated boundaries. No router dependency added. |
| tailwind                   | Installed-version styling, static classes, theme/layout or class-verification references.                                                            | GOOD / GOOD; SQL-only work does not select this skill. No token/UI dependency invention.                                                                                |
| backend                    | Nontrivial use cases, failure/transaction/dependency/performance references and public boundary.                                                     | GOOD / GOOD; copy-only work does not select backend or duplicate Admin business logic.                                                                                  |
| laravel                    | Actual framework routes/validation/ORM/jobs; installed-version Boost and relevant framework references.                                              | GOOD / GOOD; no Vue styling trigger or inferred Blade UI.                                                                                                               |
| postgresql                 | Schema/query/concurrency, database guide and selected indexing/operations references.                                                                | GOOD / GOOD; UI-only task grants no reset or mechanical indexing.                                                                                                       |
| security                   | Auth/proof/grants/Audit/secrets and real trust boundaries, baseline and focused review references.                                                   | GOOD / GOOD; never exports owner 139 acceptance or invents operator authority.                                                                                          |
| docker                     | Actual service/lifecycle/image/Compose concern; context/ownership and relevant references.                                                           | GOOD / GOOD; documentation work alone does not rebuild or prune.                                                                                                        |
| rag                        | Development-only ingestion/retrieval/freshness; source/evidence and selected metadata/evaluation references.                                         | GOOD / GOOD; no optional-AI application startup dependency or receipt scope expansion.                                                                                  |

Ratings incorporate point-1 source walks and point-2 frontend corrections, not nine
new independent agent runs. Reviewed Vue's corrected route-splitting instruction
against canonical architecture performance and the existing minimal App composition;
its negative case preserves native routing. Added equivalent customer reference
routing and local SOURCE provenance without changing original upstream pins.
Remaining seven skills need no wording edits: they already select domain references
and link shared workflow/skills-policy, which now routes authoring centrally.
No skill was declared REDUNDANT/BROKEN from frontmatter alone, and no distinct new
skill workflow was demonstrated. Practical final re-evaluation stays in point 10.

Measured root owner before/after: 32,207 → 32,042 UTF-8 bytes, 4,031 → 3,998 words;
customer 30,078 → 29,480 bytes, 3,742 → 3,667 words. Owner Vue routing reference
2,996 → 3,145 bytes: the qualified instruction is longer but avoids an incorrect
universal requirement. These source-context measurements supplement point 2 and
are not performance, token or correctness scores. No size cap was applied.

### Focused evidence, synchronization and remaining work

Knowledge status was current at point start. Bounded current-intent ownership
retrieval led to skills-policy and the knowledge applicability contract; originals
were already opened and the relevant ownership source refreshed. No bootstrap,
rebuild or live AI gate. Used backend/skill-creator guidance for the internal
parser/refactoring and existing instruction products; no Laravel/database behavior
or public framework API needed guessing.

Necessary focused ambiguity check:
`python3 tools/maintainer/test-python --scope=contracts --path=contracts/test_documentation.py --filter=DocumentationMarkdown`
returned **6 PASS / 13 deselected**, native exit 0, cleanup 0, approximately 0.05s;
report `.runtime/python/reports/runa-pytest.xml`. Cases cover absent/Unicode/formatted
anchors, duplicate/setext headings, longer/mismatched fence examples and unclosed
fences, metadata/comment/inline examples, customer self/peer projection, indented
private-link refusal, encoded paths/reference destinations and single-title
structure. The initial command supplied the repository-relative path instead of
the documented owner-suite-relative --path and was refused before execution;
corrected after inspecting the dedicated Python adapter. No failed test is hidden.
The same six focused fixtures were rerun after preserving indented-list link
checks; no broad suite or unrelated area ran.

Existing Prettier/Ruff formatting and mandatory hooks apply. Full tooling/static/
documentation/delivery/knowledge checks remain final; the fixture result is not
all-document PASS. No module/API version bump, application runtime/config change,
migration, data operation, Docker resource creation or cleanup was needed. Preserve
application/base test data, inherited endpoint state, shared caches and reports.
Owner/public quality docs and the Python guide describe actual new source-check
behavior; root owner/customer and Vue counterparts are synchronized in this point.

## Point 4 — Current documentation and developer journeys — 2026-11-06

The [per-document reconciliation ledger](../../reviews/RM-2026-017-documentation.md)
records 188 existing Markdown documents across handbook, App, owner engineering,
customer instruction products and historical evidence. The review covers audience,
structure, discovery, status/version language and command/source walk-throughs;
retained rows are justified no-change dispositions, not certification of every
signature or native workflow. The new ledger itself is an owner audit deliverable.

The handbook now provides an ordered architecture → initialization → editor →
feature/module → public API → frontend composition → tests → update/deployment
path linking canonical procedures. The command guide owns execution locations:
root Artisan forwards to src/artisan, native backend arguments resolve from src,
PHP/Node remain container tools and host setup uses the existing transport.
Development initialization and reopen/data preparation are explicitly distinct;
post-create prose now matches the actual fixed Dev Container command.

P-01/A-01 was resolved in point 2; its stale module-framework language consumer is
now corrected too. P-02/A-02 current-prose corrections match Auth module 5.0.0,
Permissions 2.1.0 and current Auth ^5.0.0 dependencies while preserving independent
AuthAdministration/AuthProfiles 2.1.0, AuthStepUp 2.0.0 and PermissionsAccess 2.2.0.
The App manifest range is >=0.2.1 <0.8.0. Current guides describe delivered Admin
pages/forms/settings/component evidence, with metadata-only declarations still
distinct from executable bindings. The deferred-concerns registry removes Admin
under its existing removal condition. Historical upgrade versions are explicitly
historical; no closed roadmap, changelog entry or dated evidence was rewritten.

The reference guide now uses the central application test path and a scoped native
invocation; its version is 0.2.0. Module generation/source guidance matches
frontend/components and the four delivered Core modules. Owner installation
scaffold prose uses central docs/app/tests, and the update example requires an
actual published target placeholder rather than stale publication instructions.
No new capability, runtime configuration, dependency, API/module/platform version,
migration or data operation is introduced by these editorial changes.

### Grounding, focused evidence and instruction products

Knowledge status was current at point start: 210 documents, 11,662 chunks, no
branch/parser/metadata drift, missing/orphan chunks or interrupted write. Bounded
current-intent retrieval returned documentation ownership and lifecycle/current
state sources; those owning documents were opened. Source evidence and the scope
limits for each developer journey are retained in the reconciliation ledger.

A one-off direct diagnostic used existing docs_checks.links on the changed guides
only because the supported complete documentation gate has no changed-path scope.
Its purpose was to resolve new navigation/public-projection and renamed-anchor
uncertainty. The first run found current inbound links to the point-specific
operations heading removed here. Updated all three current consumers, then the
same diagnostic returned **PASS for 34 changed guides**, including public
ownership and local Markdown anchors. This is not all-document/projection or
external-link acceptance. No test suite, image build, scanner, native installation,
update, deployment or browser matrix ran. Full source/docs/delivery/knowledge
checks remain point 12; mandatory formatting/hooks and staged incremental knowledge
refresh apply to this commit.

Both instruction products already route these subjects to the corrected canonical
handbook. Root AGENTS/CLAUDE, owner skills and the separate customer bundle need
no new duplicated version/procedure rule; the point-3 paired-product assessment
remains valid. The ledger records this disposition. No document was moved/deleted,
so incremental indexing replaces changed passages without a collection rebuild or
move/delete recovery. Owning future implementation points must synchronize current
prose again when behavior/versions change. No Docker resources were created or
removed; existing development/test data, caches and reports remain preserved.

## Point 5 — Backend, modules and PostgreSQL dispositions — 2026-11-06

The [backend review](../../reviews/RM-2026-017-backend.md) records bounded walks
through native transaction coordination, Auth/Permissions owners, Admin HTTP and
aggregate composition, Audit persistence/reads, maintenance/worker identity,
logging/CLI context and minimal App/reference consumers. Gate B selected no
backend business/schema repair; this point found no evidence requiring one.
Existing native owners, guards, proof/eligibility separation, canonical locks,
optimistic revisions and atomic Audit remain the design. No speculative
refactoring, cache, index removal or compatibility adapter was introduced.

Added bounded low-severity editorial finding **A-09**: AggregateController's comment
still described native execution as future roadmap point 6; AuditActorResolver's
comment described Auth enrichment as future although CoreIntegrationServiceProvider
already binds AuthAuditActor. Corrected those two owning comments after inspecting
the actual implementations. No executable behavior, signature, dependency,
configuration, migration or data changed. Admin 1.0.0, Audit 1.1.0, Auth 5.0.0,
Permissions 2.1.0 and their independent guarded API versions remain unchanged;
comment-only corrections require no semantic version/changelog entry. Candidate
0.8.0 range/version preparation remains with the later release work.

Knowledge was current at point start (211 documents / 11,715 chunks); bounded
current-intent retrieval was followed by original sources. Boost confirmed the
installed PHP 8.4 / Laravel 13.37.0 / PostgreSQL context. Read-only catalog
inspection retained the 46-table baseline, with no public/Admin relation returned.
Four bounded diagnostics found zero FK RESTRICT, bigint identity, existing
public_id required/unique or Core cross-schema FK deviations. The review maps
every catalog table to a retained purpose and distinguishes technical/pivot rows
from the 19 tables with public_id. These observations do not certify UUIDv7 row
contents, migration replay, complete index selectivity or runtime concurrency.

Existing regression assertions cover aggregate rollback in both owning Audit
failure directions, proof rollback, stale revisions, independent lock competition,
last authority, maintenance heartbeat and context redaction. They remain source
evidence until the supported complete backend execution in point 12. No duplicate
comment-text tests, broad suites, scanners, images, installation/update acceptance
or benchmark were run here. Mandatory formatting/hooks and staged incremental
knowledge synchronization apply to this commit.

Current public guides already describe the retained behavior correctly. Both
instruction products need no new reusable rule; the review records the assessment.
No Docker resources were created or removed. Application/test data, inherited
endpoint state, reports and caches remain preserved. Point 6 can now address the
approved shared frontend A-08 correction under the existing standards.

## Point 6 — Shared frontend notification locale — 2026-11-06

Corrected approved finding A-08 in `AppFormNotifications.vue`: locale is now an
explicit watcher dependency, and the default invalid summary uses that watched
value. A mounted notification follows PL→EN→PL through the existing in-place
replacement path. No controller/form remount, new submit attempt, second queue or
consumer-specific translation workaround is required. The public props/slot
signature and caller ownership of explicit error/success strings are retained.

### Component-to-consumer coverage and retained behavior

Source inspection found 38 template instances in 30 Vue consumers; all bind locale.
The public `@/runa/ui` export resolves directly to the corrected shared owner.

| Area        | Consumers / instances | Disposition                                                                                                                                   |
| ----------- | --------------------- | --------------------------------------------------------------------------------------------------------------------------------------------- |
| Shared      | 4 / 4                 | Dialog, preference feedback, table and table preferences retain their local feedback owner and locale binding.                                |
| Admin       | 6 / 10                | Forms, settings, operations/history and resource table inherit the shared correction.                                                         |
| Audit       | 1 / 1                 | Administrative detail retains caller-localized read errors.                                                                                   |
| Auth        | 16 / 20               | Entry, account/security, profile/avatar, secondary emails, history, proof, session shell and administrative consumers inherit the same owner. |
| Permissions | 3 / 3                 | Assignments, import and definition feedback retain their existing bindings.                                                                   |
| App         | 0 / 0                 | Minimal scaffold has no direct instance; public consumers receive the corrected export.                                                       |

Gate B selected no other shared layout, table, temporal, modal, loading or
navigation repair. Their existing contracts remain applicable; this point does
not manufacture redesign or claim fresh acceptance of unaffected behavior.
Existing feedback regressions retain persistent errors, repeated dismissed
attempts, recovery actions and modal focus boundaries. Broader dirty-draft,
identity/access-loss, empty/conflict, loading, slow-image, mobile/theme and reduced
motion scenarios remain in their existing owning suites and final/manual
acceptance scope; this narrow correction changes no such mechanism or styling.

### Grounding, focused evidence and instruction products

Knowledge was current at point start (212 documents / 11,747 chunks). Bounded
current-intent retrieval was followed by the original component/forms guides,
frontend selector and Vue reactivity reference. Tailwind/security skills were not
needed: no styling, guarded operation or security orchestration changed.

Added a public-export regression in `tests/frontend/runa/unit/feedback.test.ts`.
The supported diagnostic command was
`python3 tools/maintainer/test-frontend --filter='updates the mounted invalid form notification'`.
It reproduced the stale Polish title before the correction (exit 1, cleanup 0),
then passed in Chromium after the correction (1 test passed, 539 skipped;
exit 0, cleanup 0). It exercises locale-only PL→EN→PL, one persistent notification,
the same DOM node/message ID, localized close labels and retained keyboard focus.
This is a targeted behavioral diagnostic, not the complete frontend gate.
Vite reported a dynamic-import analysis warning in its generated Vitest runner;
the test completed successfully. Failure screenshot/report artifacts remain
preserved; no broad suite, build, scanner or infrastructure acceptance ran.

The public component catalogue now documents live default-summary translation and
caller-owned explicit text. Both instruction products already select that owning
catalogue and require shared feedback/reuse; no new AGENTS/skill rule or customer
template change is needed. Forms/loading/motion/replacement guides retain valid
contracts and need no duplicate locale procedure. No Core module implementation
or public API signature changed, so independent module versions/changelogs remain
unchanged. Staged incremental knowledge synchronization and mandatory hooks apply
to the point commit.

No Docker resources were created or removed. Existing application/test data,
endpoints, caches and verification evidence remain preserved. Point 7 retains its
approved per-view review/disposition scope.

## Renewed point 1 — Audit execution ledger — 2026-11-06

**In progress; not a new Gate B report or an acceptance declaration.**
The restart baseline is commit `e336cf5f`, retaining implementation through
`e9f866d8`. The initial file/hash inventory is
`.runtime/artifacts/playwright/RM-2026-017-restart/source-inventory.json`, SHA-256
`b1c37f22ac1e4bbdc76d6eda897d66e2e9b601b638607affb3d1dedc158f0314`.
It includes 2,115 tracked paths, initially marked pending renewed disposition;
this is a scope census, not evidence those paths were inspected. Generated/vendor
content is excluded from first-party inventory; authored configuration, locked
manifests, fixtures, public APIs and trusted tooling remain included. Historical
records are evidence and must retain their bytes.

### Area inventory and completeness obligations

| Area / census grouping                                  | Current renewed status                                                     | Required disposition before completion                                                                                                                                                                                            |
| ------------------------------------------------------- | -------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Frontend: 353 resource/module files                     | Partial source and real-browser inspection                                 | All Vue components, composables, public exports, supplied/default/replacement views, CSS/assets and supporting modules mapped to behavior groups; shared consumers and distinct complex interactions; risk-based rendered states. |
| Backend/config/dependencies: 844 files                  | Pending renewed capability mapping                                         | App, Foundation, HTTP, Core, module framework, settings, work/events, storage, logging, console, queue/scheduler and public/trusted/private contracts, with actual consumers and refusal/transaction boundaries.                  |
| Database: 46 migration files                            | Catalog inventory obtained; consumer review pending                        | Every current table/column/index/constraint and migration mapped to purpose and real read/write/cleanup consumers; do not infer obsolescence from age or empty data.                                                              |
| Backend tests: 130 files                                | Prior claims under review                                                  | Assertions versus requirements, fixtures, failure/rollback/concurrency and isolation, duplication and missing cross-boundary coverage; no broad run.                                                                              |
| Frontend/E2E tests: 107 files                           | Focused feedback and mocked browser transport source inspected             | Distinguish real application/API integration, fixture rendering, mocked contracts and unit evidence; review meaningful assertions and gaps, not counts.                                                                           |
| Tooling tests: 98 files                                 | Pending renewed inspection                                                 | Native process modes, evidence validity, isolation/cleanup/error handling, meaningful contracts versus implementation mirrors.                                                                                                    |
| Owner instructions: 50 files; customer product: 30      | Restart and skill selection inspected; remainder pending                   | All entrypoints/references and negative triggers; authority/constitution and customer boundary; practical task routing, correctness and duplicate rules.                                                                          |
| Documentation: 184 files                                | Previous per-document dispositions reopened                                | Handbook/App/maintainer journey correctness tied to source; all previous point-4 no-change decisions require adequate renewed evidence. Historical/proposal classification remains separate.                                      |
| Infrastructure: 21 files                                | Pending source/available runtime inspection                                | Compose/dev/prod stages, Dev Container and native Linux transport, data/cache ownership and production isolation. No expensive rebuild or host acceptance.                                                                        |
| Knowledge: 20 dedicated tooling files plus PHP adapters | Status current at restart; other obligations pending                       | Allowlist/parser/locks/incremental lifecycle/retrieval/goldens, applicability, source freshness, receipt/customer exclusions, independent startup and production absence.                                                         |
| Tooling/workflows: 183 files                            | Supported test transport and restart workflow inspected; remainder pending | Command registry/transports, setup/update/distribution/release, quality/evidence/resumption and exact SIGSEGV accounting/recovery proposal; no root-cause investigation.                                                          |
| Root/assets/configuration: 49 files                     | Pending reconciliation with owners                                         | Every remaining first-party tracked path assigned to an above capability or justified exclusion; includes direct dependencies and configuration roots.                                                                            |

These mutually exclusive census buckets are navigation aids. Cross-cutting
security, localization, accessibility, performance and public consumption require
explicit capability-level evidence across buckets. No area is complete yet.

### Previous audit completeness reassessment

The earlier root/login observations did not cover the majority authenticated
frontend. The previous backend review explicitly used bounded source walks, and
its table-purpose descriptions were partly derived from migration names/guides;
that does not establish actual consumers for every column/index. The documentation
ledger repeats broad reasons across many retained documents; the rows alone do
not show the individual source-to-procedure checks needed here. The existing
findings and test source remain useful leads, not substitutes for renewed review.

### Access and first authenticated observations

The owner authorized checking the existing account/local access without password
reset or account creation. Public seeding guidance identifies the private local
configuration inputs; a local-only process read those configured values directly
into browser memory. No credentials, cookies, session storage state or response
bodies were printed or persisted. No account, role, settings or data reset was
performed. Ordinary login and Admin password confirmation create their native
session/security/Audit records; these are disclosed audit side effects and remain
preserved. Repeated exploratory contexts were closed, not silently deleted from
server history. No production/customer endpoint was contacted.

The existing `http://nginx:8080` development endpoint accepts normal login and
Admin full-page password confirmation. No guard was bypassed. The earlier absence
of authenticated inspection was therefore not an established access impossibility.
Source census and a scoped browser diagnostic replace neither final E2E execution
nor the owner's manual acceptance.

Artifacts live under `.runtime/artifacts/playwright/RM-2026-017-restart/`:
`browser.mjs`, `browser-summary.log`, `admin-navigation.json`, `responses.json`,
page/detail screenshots and read-only catalog inventory/scripts. Initial
exploratory screenshots containing unsettled placeholders are transition samples,
not final appearance evidence; later detail captures wait for visible loading
regions to settle and use viewport captures to avoid full-page modal stitching.
Browser diagnostics use installed Playwright directly because no supported suite
selects these live read-only observations without launching its broader mocked
fixture acceptance. No test suite or application route was added.

| Observation                           | Actual evidence                                                                                                                                                                                      | Remaining inspection                                                                                                                                      |
| ------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `/app`                                | Existing account enters the minimal application shell after normal login; screenshot inspected. Empty App content is intentional minimal scaffold source, not a missing business module.             | Shell drawer/menu/keyboard and default/replacement composition, loading/error variants.                                                                   |
| `/account` and four subviews          | Real account/security, preferences, active sessions, login history and account history opened. Recorded headings/query navigation, screenshots and no document horizontal overflow in these samples. | Settled visual review of every captured subview, changes-in-place/draft retention, nested/security operations, alternate role/state and language samples. |
| `/admin`                              | Guarded full-page password confirmation followed by real module dashboard; normal side navigation and module cards appear after readiness settlement.                                                | Application settings, module detail/settings/health/work/recovery history, slow/failing admission.                                                        |
| `/admin/runa.audit/history`           | Real populated history table and detail. Detail focus remains inside modal; Escape closes and restores the initiating Details button. Screenshot visually inspected.                                 | Filtering/temporal/JSON expansion, error/empty and alternate presentation samples.                                                                        |
| `/admin/runa.auth/users`              | Real directory with dense filter/action/table composition and populated user detail. Detail/escape focus observed. Final detail headings include profile/status/emails/policies/access/preferences.  | Creation, native action groups/bulk review, access editor, security tab, independent forms/loading/drafts and other account states.                       |
| `/admin/runa.permissions/permissions` | Real definition table and read-only detail; detail/escape focus observed. Screenshot visually inspected.                                                                                             | Roles/presets, custom-definition editing, high-cardinality assignments/import and native protected-authority presentation.                                |
| 390px dark/reduced-motion account     | Actual `data-theme=dark`, no document horizontal overflow; snapshot includes ongoing initial dependency loading.                                                                                     | Settled screenshot and actual focus/drawer/motion behavior; media emulation alone does not certify motion/a11y.                                           |

Recorded API non-success statuses in this observation are guest session 401 and
Admin admission 403 before explicit password confirmation. They are expected
boundary observations, not application defects. All other recorded API responses
in this sample were successful; this says nothing about unvisited endpoints.

### Read-only PostgreSQL inventory

Configured development identity is database `runa`, role `runa`. Catalog queries
returned 46 ordinary tables plus their 46 sequences, 419 columns, 131 indexes,
565 constraints and no functions in the selected public/shared/Core schemas.
No views/materialized views/foreign tables were present in that selected object
inventory. Every existing `id` column reports identity; all inventoried foreign
keys explicitly retain update/delete RESTRICT. This is catalog evidence only.
`database-inventory.json` retains exact definitions. Actual consumers, redundant
indexes/columns, query growth and migration-source/runtime alignment are still
open; no schema has a renewed no-change disposition from this inventory alone.

### Existing implementation candidates

| Prior point / commit | Evidence retained                                       | Renewed disposition                                                                                                                                                 |
| -------------------- | ------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 2 / `79c533a0`       | Standards/guidance changes and research references      | Pending source/provenance/usability and rendered correlation.                                                                                                       |
| 3 / `86b01c65`       | Documentation checker and instruction changes/tests     | Pending parser-contract/fixture/negative-case and product-boundary review.                                                                                          |
| 4 / `00da181d`       | Current-document edits and per-document ledger          | Pending individual source/procedure correlation; repeated retain reasons are insufficient alone.                                                                    |
| 5 / `1c01d885`       | Bounded backend/catalog report and two comment changes  | Pending capability/consumer completeness review; no runtime repair implied by reopened status.                                                                      |
| 6 / `e9f866d8`       | A-08 source change and native Chromium red/green result | Retain case-specific result; full shared-component acceptance withdrawn. Inspect dismissal/repeated-attempt/language behavior as well as mounted-title translation. |

No new correction or renewed completion is authorized by this ledger. Final Gate B
summary and decisions remain pending completion of the actual audit above.

### Additional renewed observations and candidate findings

The scoped `deep-browser.mjs` observation opened real Auth policy, Permissions
roles/presets and application settings (`/admin?section=application`). Their
headings/control inventories and screenshots are retained in `deep-browser.json`.
Desktop samples reported no document horizontal overflow. The account-name draft
survived a window-focus refresh and was restored locally without submission.
The narrow dark/reduced-motion user-directory sample at 390px also reported no
document overflow. Screenshot collection is not equivalent to visual review of
every region, all states or every interaction; remaining observations stay open.

The backend, Laravel, PostgreSQL, Docker and RAG skills were selected as their
owning areas were reached. Boost confirmed installed PHP 8.4, Laravel 13.37.0 and
PostgreSQL. Version-aware authentication documentation and installed framework
source distinguish native recaller tokens from Runa's captured session persistence.
The composed/direct manifests, Compose dev/prod files, Dev Container, Docker
production-copy/exclusion paths, quality lifecycle/evidence code, native test
command composition, knowledge source discovery/startup, Auth admission, guarded
module APIs/operation fences, permission queries and selected persistence/cleanup
owners were inspected. These are specific source walks; the area table remains
open until its capability mapping is complete.

| ID / classification                                     | Evidence and practical impact                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             | Recommendation / effort / remaining work                                                                                                                                                                                                                                                                                                                                                                           |
| ------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| RA-01 / Confirmed repeated read work, Medium            | A scoped local HTTP-kernel observation using normal encrypted browser cookies obtained HTTP 200 for `/api/admin` (479 queries), `/api/runa/auth/profile` (131), `/api/runa/auth/security` (112), and `/api/admin/runa.auth/users` (581). Admin bootstrap repeated the generation SELECT 165 times and the full registry snapshot's three SELECTs 36 times each. Directory repeated settings-revision and single-subject policy-exception templates 56 times each, plus the user-public-ID lookup 46 times. This is substantial repeated work on supplied read paths, missed by the earlier bounded no-repair disposition. | Trace/batch owning read inputs and reuse only a demonstrably current, correctly locked evaluation scope. Preserve revocation, settings/health expiry, write fences and reused-worker freshness; no persistent permission cache. Medium/large effort; architecture details and analogous consumers require further inspection before the Gate B proposal. Counts do not establish row-dependent N+1 or FPM latency. |
| RA-02 / Confirmed source-to-guidance inconsistency, Low | `AppWizard.vue`'s action wrapper has `border-t border-line pt-5`; the canonical forms guide's container/field section explicitly requires spacing without a horizontal separator above form actions. Shared `AppForm` follows that rule.                                                                                                                                                                                                                                                                                                                                                                                  | Reconcile Wizard's common action presentation with the accepted standard; small effort. Source contradiction established; an actual rendered Wizard consumer remains required before claiming a visual reproduction. No correction made.                                                                                                                                                                           |
| RH-01 / Retention hypothesis, Low if confirmed          | `runa_auth.users.remember_token` exists. Repository search found only null assignments in owning credential/lifecycle changes and an inherited Authenticatable trait. `AuthenticateSession::login` explicitly calls Laravel login with `false`; Runa persistence uses SessionStore and SessionPersistence.                                                                                                                                                                                                                                                                                                                | Check the complete framework/operator/customer contract before proposing removal or explicit retention. Do not delete a credential boundary from an identifier search alone. No column or clearing write changed.                                                                                                                                                                                                  |
| RH-02 / Behavioral hypothesis, severity pending         | Adding locale to the A-08 watcher also invokes the existing `show()` branch if the notification was dismissed while invalid state remains true. The retained regression covers a mounted notification, not dismissed feedback.                                                                                                                                                                                                                                                                                                                                                                                            | Determine/render the intended dismissal-versus-new-attempt behavior on language change; preserve original A-08 red/green result and avoid reverting a working mounted-title fix. No new defect/interaction decision is asserted yet.                                                                                                                                                                               |

`query-observations.json` preserves SQL templates/counts and database-only timings,
without bindings, credentials, session cookies or response bodies. The diagnostic
uses the actual Laravel HTTP middleware/controllers with the existing authenticated
session, in a local CLI kernel rather than Nginx/PHP-FPM. It performs four selected
GETs, not a benchmark, full test or exhaustive performance audit. Native session
processing may perform its normal session-file updates. The first transport
attempt passed URL-encoded browser cookies without PHP's normal cookie decoding
and was refused with 401; retained separately as
`query-observations-unadmitted.json`. It is not authenticated-path evidence. The
corrected diagnostic applies ordinary cookie decoding and the same guards; all
four admitted results above are 200. No guard, account state or runtime source
was modified to obtain them.

Source correlation for RA-01 includes `MaintenanceState::reasons`,
`ModuleAvailability::statuses`, `ModuleRegistry::snapshot/loadSnapshot`,
`OperationGuard::enter/assertCurrent`, Auth `AccountPolicy::principal`,
`Administration::users`, `ResolvePermissions::allows`, `CredentialPolicy` and
`PolicyExceptions::values`. `UserDirectory` projects selected rows without an
obvious per-row PHP lookup loop; therefore the current observation must not be
misreported as proven row-count N+1. Distinguish repeated admission/declaration
checks from row projection before selecting a repair.

Test-quality inspection has also established a boundary requiring explicit
coverage accounting: `auth-entry.spec.ts`, `auth-account.spec.ts` and
`admin-shell.spec.ts` use browser route fulfillment for Auth/Admin API responses.
Their UI assertions remain valuable; they do not prove real backend/browser
integration. The present live observations add that missing evidence for visited
reads only. Whole-suite redundancy/gaps and other real integration owners remain
unreviewed; no test deletion or new test policy is proposed from this sample.

### Renewed dialog and documentation inspection — 2026-11-06

Continued point 1 at candidate `62b1cec7` with current knowledge, scoped real
creation/user/security/role/preset dialogs and nested access-copy focus/viewport
observations. The [detailed evidence and capability ledger](../../reviews/RM-2026-017-renewed-dialogs-and-documentation.md)
records inspected states, native side effects, test-value review, prior-point
reassessment and remaining work. It does not complete any audit area or restore
Gate B. No implementation correction or broad verification was performed.

- **RA-03 / confirmed, Low:** the current testing guide still prescribes
  `.playwright/scratch` or `tools/scratch`, contradicting canonical workspace
  `.runtime` placement. Proposed point-4 documentation reconciliation, small effort.
- **RA-04 / confirmed, Medium:** point-3 parser strips HTML comments before
  recognizing code. Two small diagnostics show literal comment delimiters inside
  fenced/inline code hiding a real broken link; the actual link/structure checker
  accepts both documents. Proposed bounded parser repair and interaction fixtures,
  medium effort/regression risk; prior point-3 acceptance remains pending.
- **RH-03 / unexplained:** some dialog captures contain generic failure feedback.
  A separate instrumented read did not reproduce it; completed authenticated
  section responses were 200. Cause, impact and correction remain unestablished.
  Do not infer preference failure or security refusal from a toast alone.

Live nested-copy evidence confirms parent inertness, forward/backward keyboard
containment and child-only Escape with focus returned into the parent. Creation
Escape restores its opener. Narrow captures show no document overflow in those
samples; they do not certify complete form reachability, a11y, exports or other
states. Existing A-08 results are preserved; the dismissed-locale sequence remains
RH-02. Authentication/activity and preferences POSTs are disclosed native effects;
method/path alone does not distinguish preference snapshots from writes. No
account, definition, access or settings Save/Apply was submitted.

### Renewed audit synthesis and new Gate B handoff — 2026-11-06

**New Gate B decision pending.** The
[consolidated renewed audit report](../../reviews/RM-2026-017-renewed-audit-synthesis.md)
is the current handoff, together with the detailed renewed evidence above and
[dialog/parser evidence](../../reviews/RM-2026-017-renewed-dialogs-and-documentation.md).
It records the actual condition, component/capability and table-consumer inventories,
inspection methods/states, verified findings, hypotheses, explicit limits,
prior-work assessment and proposed reconciliation of points 2–12. It supersedes
neither the withdrawal nor immutable historical results. Point 1 remains unchecked
until the owner explicitly accepts the renewed report and selected scope.

Inspection used clean candidate `3f7afe51`; runtime source did not change.
Knowledge was current at entry: 214 documents/11,985 chunks, no drift, missing or
orphan chunks or interrupted write. Bounded audit retrieval led to original
component action, database snapshot and module-integration contracts. Relevant
frontend/Vue/Tailwind/backend/Laravel/PostgreSQL/security/Docker/RAG selectors were
read; the customer instruction bundle remained product data. No upstream skill,
new dependency or new architecture was adopted.

#### Additional real observations and their limits

Purpose-scoped Chromium diagnostics reused existing `http://nginx:8080`. Normal
login and full-page Admin password confirmation retained their normal native
session/activity/history effects. No business form or administrative command was
submitted. Browser contexts/processes closed in finally blocks.

- Opened the real Auth module dialog at 1440×1000 and 390×844. Dependencies,
  operation/task history and diagnostic opener rendered without document overflow.
  Core correctly omitted optional-module disable/unregister controls. No diagnose,
  lifecycle, cancellation or recovery command ran.
- Opened Auth policy's Save snapshot dialog. Its committed-only explanation,
  initially focused name input and disabled empty-name Save were visually inspected.
  No snapshot was captured, loaded, deleted or applied.
- Sorted the real populated Audit table: after settlement `aria-sort=ascending`.
  Opened the temporal range popup and inspected From/Until and Clear/Cancel/Apply.
  No range was applied. At 390 px the actual horizontal scroll region measured
  client 298/content 1,986 px with no document overflow. This establishes a scrollable
  region, not complete pinned-header/row/export acceptance.
- Inspected real Audit detail. A diagnostic searching for a `Rozwiń` button was
  inappropriate: ValuePreview uses a `Pokaż wszystko` link. Zero matches must not
  become evidence that long-value expansion is absent or correct. Long JSON stays
  a distinct final scenario.
- Repeated the exact creation → close → user detail → nested copy/Security sequence
  with response metadata. A creation-section GET containing a selected public_id
  returns 422 and produces the visible generic failure. RA-05 below replaces
  RH-03's unexplained status for this reproduced sequence; it does not establish
  the cause of every historical toast.
- At 390 px inspected guest registration/recovery in English/light through the
  actual guest language control, and native 404 in English/dark. No overflow or
  pageerror in these samples. SPA query parameters alone did not select its locale;
  native error query parameters did. No authenticated preference Save occurred.
  Registration copy revealed RA-07 below.

Evidence: `completion-browser.mjs/json`, versioned first/second observations,
`completion-*.png`, `guest-completion.mjs/json` and its initial observation under
`.runtime/artifacts/playwright/RM-2026-017-restart/`. Initial captures before
settling table loading/enter transitions are preserved as transition samples.
The corrected observation waits for content and table indicators and allows
300 ms for the inspected transition. An initial selector timeout is diagnostic
failure, not a reproduced application defect. No full browser matrix was run.

#### Confirmed additions and refinement

| ID               | Severity / owner / exact evidence                                                                                                                                                                                                                                                                                                      | Impact, correction, effort/risk and proposed stage                                                                                                                                                                                                                                                                                                                                                                                        |
| ---------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| RA-01 refinement | Medium; ModuleRegistry::snapshot/loadSnapshot, ModuleSettings::reading/currentValues, AccountPolicy/LookupIdentity/CredentialPolicy/PolicyExceptions and guarded admission. Earlier four admitted read-path query counts remain 479/131/112/581.                                                                                       | Independent scalar settings reads open fresh roots; registry invalidation and lock-scoped settings snapshots explain repeated work. Batch current owning inputs or use a demonstrably locked read scope; preserve writes/rollback/reconnect, expiry/revocation and reused-worker freshness. No persistent grant/eligibility cache or claimed latency gain. M/L effort, moderate/high regression risk, point 5 with pre-code design check. |
| RA-05            | Medium; live creation-close-details sequence issues GET `/api/admin/runa.auth/users/sections/creation` with `kind` and `public_id`, returns 422 and shows a false failure. Same 422 in two observations. useAdminForm retains activated sections across subject selection; CreationSection::read correctly rejects a non-null subject. | Repair the owning active/editor lifetime and analogous create/record/bulk consumers. Do not suppress 422 or weaken backend validation. Add controller and real-consumer regressions. M effort, shared-scope risk, points 6/7. No account mutation needed to reproduce.                                                                                                                                                                    |
| RA-06            | Low; maintainer quality README manual-selection section calls the current series 0.6.x, while VERSION is 0.7.0.                                                                                                                                                                                                                        | Correct stale present-tense context, retain the agreed 0.10.0 all-group cadence. S effort/low risk, point 4.                                                                                                                                                                                                                                                                                                                              |
| RA-07            | Low; Auth frontend messages.ts::registerHelp retains administrator approval wording in PL/EN; English narrow capture confirms rendered copy. RegisterAccount and IdentitySettings implement administrator activation through active/registration_activation.                                                                           | Align copy with current activation terminology; this is not evidence of a separate runtime approval gate. S effort/low risk, point 7.                                                                                                                                                                                                                                                                                                     |

RA-02–RA-04 remain confirmed with their preceding evidence. RH-01 remains an
unproven removal/retention question: inherited Authenticatable exposes the token
contract, current Runa login uses false and logoutCurrentDevice does not rotate it.
No benefit or complete removal justification established; retain without migration.
RH-02 remains source-derived: locale-only watcher execution can recreate dismissed
invalid feedback. Proposed owner decision is to translate visible feedback while
preserving dismissal; a deliberate new attempt may announce again. Preserve A-08's
existing mounted identity/focus correction. No new interaction rule implemented.

#### Complete-scope disposition and retained unknowns

The synthesis maps shared controls and wrappers, complex pickers/files/wizard,
forms/reconciliation, dialogs/floating layers, feedback, tables/operations/values,
loading, layout/navigation, identity/assets/status and public module composition.
It separately covers supplied entry/account/user/Permissions/Audit/Admin/settings/
App/error/mail families. Grouping is by actual shared behavior, with separate
unknowns and native scenarios for distinct interactions. Source observations and
inherited regression assertions never become rendered PASS.

All 46 catalog table names map to owning consumers, including dynamic Permissions
membership names, separate framework/App ledgers, module lifecycle/work/settings,
Auth delivery/cursor/cleanup and immutable Audit storage. `table-source-map.json`
is a literal-reference inventory, supplemented by manual dynamic-consumer tracing;
it is not a static dead-code proof. Complete catalog columns/indexes/constraints
are retained in the original inventory. Similar index columns can serve different
uniqueness/reverse-lookup contracts. No index or column is declared useless without
query/contract evidence; workload selectivity and migration replay remain unknown.

The engineering review distinguishes backend tests, mounted controllers/components,
mocked browser transport and real integration reads. Keep distinct boundary coverage;
no count/label-driven deletion. Add parser code/comment interaction and section
context-transition coverage after approval. Existing registry-cache tests protect
locked reuse and same-connection invalidation; RA-01 needs supplied-read correlation.
Current test/dataset discovery and all complete native results remain final-stage
obligations, not the historical RM-016 case count.

Current documentation journeys were re-correlated with command registries,
DevelopmentSetup/Dev Container, guarded module adapters, current shared components,
catalog/migrations, production Compose/copy allowlists and knowledge corpus/startup/
indexer. RA-03/RA-06 show why prior repeated per-document “retain” reasons cannot
certify editorial correctness. Point 4 must replace those reasons with specific
owning procedure/source checks; no claim of flawless untouched prose is made.
Nine skill pairs retain distinct positive/negative routing and prior source-scenario
assessments, with frontend/Vue improvements useful candidates, not independently
executed agent trials or unconditional GOOD scores. No new skill is justified.

Dependency usage/config ownership, host/container commands, Docker/Dev Container,
production isolation, source allowlisting/index deletion/pending writes, verification
state, owner/customer SIGSEGV boundaries, manual acceptance and release/cleanup
were reassessed through their owning sources. No advisory scan, image/restart/
installation acceptance or full AI evaluation is claimed. Knowledge freshness is
source agreement, not factual currency. Live host Docker identities remain unknown;
the inherited journal is not deletion authority. Final strict security/AI gates,
real permission/config/state matrices and assistive-technology/contrast limitations
remain explicit rather than accepted by exclusion.

#### Proposed point reconciliation and owner decisions

Retain useful point-2 standards/selectors, point-3 authoring/checker direction,
point-4 accurate current-state corrections, point-5 comment corrections and point-6
A-08. Their earlier blanket completion claims remain withdrawn. After new approval:

- Point 3 repairs RA-04 before relying on general link evidence.
- Point 4 repairs RA-03/RA-06 and replaces unsupported ledger dispositions.
- Point 5 includes RA-01; the former no-backend-repair conclusion is superseded by
  measured read work. Check exact safe scope/lifetime before code; no schema reset.
- Points 6/7 handle RA-02/RA-05, the proposed RH-02 policy if accepted, and RA-07.
  Inspect analogous consumers rather than redesign all views or restore retired UI.
- Point 8 retains development/configuration/knowledge review with no evidenced
  infrastructure rewrite or dependency upgrade.
- Point 9 retains D-01–D-03 and exact state/command/input/adapter reliability checks
  before implementation. Proposed resume commands are still not delivered commands.
- Points 10–12 retain qualitative/test-value reconciliation, working owner acceptance
  preparation, explicit penultimate manual acceptance, and complete selected-area
  final verification/release last. Final groups remain provisional.

**Requested Gate B decision:** accept or amend original retained scope plus
RA-01–RA-07 and the point reconciliation; accept RA-01's correctly locked owning
read-scope boundary subject to its pre-code check; decide RH-02's dismissal rule;
accept or require additional evidence for the explicit audit limits. Defer RH-01
removal for absent benefit/contract justification. Gate A feature/0.8.0 is unchanged
and needs no reconfirmation. No dependency, reset, candidate-version preparation
or production deployment is requested. The agent does not approve this handoff.

Only owner audit records changed. No implementation repair, broad suite, quality
graph, scanner, image build or SIGSEGV investigation ran. All diagnostic browser
processes/contexts closed; evidence remains under `.runtime`. Removed containers,
networks, tags and volumes: none. Preserve application/base test data, normal
session/history, caches/model/Qdrant volumes, backups, inherited endpoint recovery
state and every prior report. No reusable instruction/customer rule changes are
needed for this audit-only record; accepted repairs assess them in their owning
points. Commit this evidence and stop for the new explicit owner decision.

### Renewed Gate B owner approval — 2026-11-06

The owner explicitly approved the renewed synthesis as an adequate evidence-based
baseline with its limitations and authorized ordered execution beginning at point 2.
The complete [decision record](../../reviews/RM-2026-017-renewed-gate-b-decision.md)
is preserved verbatim. RA-01–RA-07 are approved for their owning points; original
findings and D-01–D-06 remain. No runtime/security/a11y/performance/test-suite
certification or automatic reacceptance of points 2–6 follows.

RH-01 is resolved as retention with an architectural compatibility rationale,
not merely deferred removal. RH-02 is approved with visible in-place translation,
identity/focus preservation, persistent explicit dismissal across locale changes,
and deliberate-new-attempt reannouncement. RA-01 requires the exact current,
locked read-scope design before code and preserves every listed freshness,
transaction, invalidation and worker invariant. Significant architecture or
unresolved correctness risks still require owner discussion.

Point 1 is now complete by the owner's decision. Resume point 2, reconcile each
existing candidate in order, add the approved corrections and preserve meaningful
tests/evidence. Do not manufacture extra changes or revert correct work. The
mandatory penultimate owner manual acceptance and final verification stay in place.
This decision-record commit changes owner roadmap/review records only; current
root/skill/customer products need no update until their owning reusable changes.

## Point 2 — Renewed standards and selector reconciliation — 2026-11-06

Reconciled candidate `79c533a0` after renewed Gate B approval commit `2fd81e53`.
Retain its implementation: the standards map owns all required topics, including
accepted RM-016 supersessions, immediate frames/local regions, Table/Button/cover
indicator exceptions, Polish-default Admin, outline/effect semantics, specialized
access editors, source-only policy and full-page password confirmation. No new
standard or skill is necessary to implement the renewed finite correction list.

Applied the frontend skill for this review. Point-start knowledge was current
and current-intent retrieval selected the original frontend accessibility baseline,
visual guidance map and settings-snapshot presentation; opened their applicable
sections. Compared both frontend selectors, owner a11y/layout/UX references,
customer application reference and provenance records against those originals.
No external material was copied or newly adopted, so existing research pins/dates
and their stated license/review limits remain unchanged.

Renewed task walk-throughs are source-selection exercises, not independent agent
executions or new rendered acceptance:

| Accepted task                  | Observed selective route and consumer correlation                                                                                                               | Conclusion / later owner                                                                                                             |
| ------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| RA-07 registration terminology | Copy row → visual-direction interface copy → Auth messages/RegisterAccount. No table, session or full form reference is mandated by wording alone.              | Existing selectors support the correction; point 7 changes both locales.                                                             |
| RA-02 Wizard action divider    | Controls/form composition row → component and forms action-area sections → AppWizard/AppForm.                                                                   | Existing canonical standard already expresses spacing without the separator; point 6 repairs the owner.                              |
| RA-05 section lifetime         | Reconciliation/loading rows → public forms contract and Admin controllers → real create-close-details observation.                                              | Correct discovery expands to state/identity ownership; point 6/7 repairs source. No standard can substitute for that implementation. |
| RH-02 dismissed outcome        | Feedback row → forms feedback/recovery and FormNotifications/useToasts. Preserve the new owner decision with the owning point-6 implementation/docs/regression. | One local owner remains sufficient; no global state or framework introduced.                                                         |
| Backend-only RA-01             | Frontend selector explicitly excludes backend-only work; backend/security/database own the read-scope design.                                                   | No visual reference load is required solely for SQL repair.                                                                          |

For these source routes both frontend selectors meet this point's discovery and
selective-loading acceptance criteria. Existing measured before/after source-byte
footprints and provenance in the original point-2 record remain evidence of those
original edits, not a new benchmark. No selector bytes changed in this reconciliation.
Final practical skill assessment stays in point 10; native component/browser and
owner manual acceptance remain their assigned later stages.

Instruction-product assessment: owner and customer frontend products already
route to the same canonical standards with their correct authority boundaries;
root AGENTS/CLAUDE need no change. RH-01/RH-02 owning contract documentation belongs
with points 5/6, respectively, rather than claiming those repairs delivered now.
No runtime, dependency, configuration or schema change was needed. No routine
suite/build/scan ran; applicable original sources and actual retained consumer
evidence resolved this guidance-only review. Renewed point-2 criteria are satisfied;
commit this reconciliation, mark point 2 complete and proceed to point 3.

## Point 3 — Renewed guidance and parser reconciliation — 2026-11-06

Reconciled candidate `86b01c65` after renewed point-2 completion `18c57dcd`.
Retain its canonical documentation authoring guide, owner/customer/root routing,
Vue route-splitting qualification, bounded stdlib checker and objective fixture
scope. Corrected approved RA-04 before relying on general link evidence again.
No D-01/D-02 workflow or owner/customer crash policy was enacted early.

Point-start knowledge was current. Bounded current-intent retrieval selected
original documentation ownership and authoring links/anchors; those sources and
the Python tooling/supported test-command contracts were opened. The renewed
Gate B report supplies accepted source/consumer coverage with its limitations;
old completion status was not used as approval.

### RA-04 repair and concrete evidence

`tools/application/documentation_markdown.py::prose` now recognizes fenced code
and exact-length backtick spans before interpreting comment delimiters. It keeps
real prose between separate code examples, ignores real comment content even
when it contains fence-looking text, and normalizes inline-code line breaks for
heading analysis. Link/explicit-anchor readers share the same code-span exclusion,
including paragraph-local multiline spans. Heading text retains inline code.
The helper remains internal delivered tooling, not a renderer or customer API.
No new parser framework, dependency, command or metadata registry was introduced.

Added two behavior-focused fixture methods to existing DocumentationMarkdown:
one exercises fenced, single-backtick, nested-backtick and multiline examples with
literal comment delimiters surrounding a real missing link/heading; the other
preserves exclusion of actual comments and multiline code containing fake links,
anchors and a level-one title. They exercise actual links/structure diagnostics,
not implementation-shape assertions. Existing ownership projection, path escape,
Unicode/duplicate/explicit anchors and long-fence cases remain.

A necessary small native fixture run resolved this lexical interaction:
`python3 tools/maintainer/test-python --scope=contracts --path=contracts/test_documentation.py::DocumentationMarkdown`
completed **8 passed**, exit 0, cleanup 0 (final run 0.05 s native test time).
The fixture was repeated after adding the fake-title assertion; no unrelated
suite/check was selected. Native XML is `.runtime/python/reports/runa-pytest.xml`.
This is focused parser evidence, not full docs/tooling/quality acceptance.

The public authoring guide now states comment/code ordering and exact backtick-run
closure. Remaining complicated Markdown heading rendering, including the earlier
angle-bracket hypothesis, is not silently declared fixed. Full documentation,
projection, Python analysis and tooling checks remain final verification.

### Renewed instruction and skill assessment

Rechecked owner/customer skill entrypoint triggers/reference routes, the two skill
indices, root documentation routing and thin CLAUDE delegation against the accepted
renewed source walks. All nine pairs below have a **GOOD / GOOD** disposition for
the named source-selection scenarios only. These are manual source walks, not nine
independent agent runs, practical runtime certification or conformance scores.

| Pair       | Positive route and concrete source correlation                                                                                                         | Negative boundary / remaining evidence                                                                       |
| ---------- | ------------------------------------------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------ |
| frontend   | Point-2 copy, Wizard, section-lifetime and feedback walks select owning canonical sections and actual supplied controls.                               | Backend SQL repair does not load visual references; final rendered/practical evaluation remains point 10/12. |
| vue        | Async/disposal route → preferences/forms/current public controller contract; corrected heavy-route splitting qualifier preserves existing composition. | A docs-only edit does not activate Vue; no router installation or copied security owner.                     |
| tailwind   | Theme/layout or class-discovery route → installed-version syntax and current palette/customization owners.                                             | SQL/Markdown parser edits do not require CSS compilation; no invented design tokens.                         |
| backend    | Failure/side-effect/performance route → native ModuleTransactions and owning Actions rather than an Admin business engine.                             | Copy-only edits do not load transaction procedure; RA-01 exact safe scope remains point 5.                   |
| laravel    | Framework-conventions route → installed-version Boost, named routes, Form Requests/resources and current transport owners.                             | Vue styling does not need framework API research; no Blade application UI.                                   |
| postgresql | Database guide/index/operations route → catalog, migrations and actual consumer ledger.                                                                | UI-only work does not authorize resets or mechanical indexes; no unknown index declared useless.             |
| security   | Baseline/review route → proof/current grants, safe Audit fields and public security owners.                                                            | Customer material cannot acquire owner SIGSEGV tolerance or protected-edit authority.                        |
| docker     | Context/lifecycle route → Compose, Dev Container and exact resource journals/ownership.                                                                | Documentation-only work does not imply rebuild/prune; live host inventory remains unknown.                   |
| rag        | Source/evidence/ingestion route → tracked allowlists, freshness and verified originals.                                                                | No AI dependency for ordinary app startup, customer corpus expansion or optional bootstrap for navigation.   |

The renewed audit's actual nested-dialog/table observations correlate the frontend
routes, but do not prove every scenario or all skills effective in independent
agent execution. Final practical evaluations remain assigned to point 10 as the
owner explicitly approved. No pair is REDUNDANT/BROKEN based on similar references;
no distinct new skill workflow was demonstrated.

No root/skill/customer selector bytes changed in this point: the existing
point-2/3 context measurements remain the before/after source evidence, rather
than inventing a new size/performance gain. Documentation placement/authoring stay
canonical in docs/README and the public authoring guide; shared workflow remains
in skills-policy. Preserve original upstream pins and local provenance.

Instruction-product assessment: the public checker repair restores the already
referenced authoring contract for both instruction products. Root owner/customer
AGENTS and skill references already route there and need no duplicate parser
procedure. Customer templates remain product data with installed logical-link
boundaries; no owner commands/policy were added. Broader current-document editing
and RA-03/RA-06 remain point 4. No runtime, schema, dependency, Docker or application
state changed. Commit this repair/reconciliation and mark point 3 complete.

## Owner scope amendment — Reference module Admin UI/UX — 2026-11-06

The owner explicitly requested adding the reference module's Admin views to this
roadmap because their alignment with current Runa UI/UX was missed in the previous
roadmap. This is an authorized addition to the existing supplied-view conformity
work, not a new module, separate roadmap or agent-inferred expansion of business
features. Gate B, feature/0.8.0, point order and existing completed points remain.
Point 4 is still current; this planning amendment does not start point-7 runtime
implementation or reinstate any historical acceptance claim.

### Source-grounded scope and boundaries

Focused inspection used the frontend skill, current knowledge and original public
page/presentation ownership guidance. The source map is:

- `src/app/modules/reference/module.json`: optional development-only example,
  independent version 0.2.0, source management/frontend/slot declarations.
- `backend/Definitions/ReferenceManagement.php` under that module: inspect
  permission/action metadata, an explicitly future administrative-action
  description and an empty components list. This is not evidence of a complete
  current owned Admin page or executable inspect workflow.
- `backend/Definitions/ReferenceFrontend.php` and `frontend/components/panel.vue`:
  declared public frontend page/slot and a public HTTP ping consumer with local
  request state. Do not conflate this standalone panel with all Admin presentation.
- `docs/app/modules/reference/README.md` and `CHANGELOG.md`, with central
  application-owned reference tests: owning example documentation and evidence.

The owner first clarified that visual alignment is the main immediate concern,
then explicitly authorized completing missing examples: the reference module must
show customers Runa's modularity capabilities. The effective scope includes both
current visual conformity and justified functional completeness of the example.
The intermediate styling-only restriction is superseded. Metadata saying "future
action" remains an incomplete demonstration, not delivered functionality.

Point 7 first creates a capability-to-example ledger against delivered public
modularity/Admin guides and actual reference source. For each capability record a
working runnable demonstration, an intentional source/CLI-only demonstration with
reproduction, a justified exclusion or a missing example to implement. Cover module
identity/lifecycle/availability, owned data, guarded API/HTTP, settings, CLI/work/
scheduling/events, frontend pages/slots and Admin composition. This inventory
selects useful representative examples; it is not an instruction to reproduce
all possible framework combinations in one screen.

The already observed concrete gap is `ReferenceManagement`: it supplies action
metadata with a future-action description and no owned Admin presentation. Add a
usable module-owned Admin example using current delivered public authoring and
UI contracts. Reuse the existing reference scenario/data/API where appropriate;
keep owning validation, permissions and Audit for meaningful mutations. Include
other demonstrated gaps identified by the ledger when they improve the customer
showcase. Module-owned declarations, adapters, tests, navigation and presentation
changes needed for these examples are authorized. Record complete supported
signatures/ownership and assess independent module versions when contracts change.
New platform mechanisms, unrelated business domains or significant architectural
premises still return for discussion before implementation.

Match current Runa layout, spacing/density, typography, colors, borders, control
sizes/alignment and feedback/loading/empty/error presentation. Review actual
rendering across desktop/tablet/mobile, Polish/English and light/dark themes, with
usable focus/keyboard behavior. Demonstrations should form a coherent customer
journey and preserve the existing example's useful working capabilities.

Keep the example optional and development-only. Technical health/integration
policy remains source-owned; demonstrate the supported source/CLI contract rather
than restoring rejected generic editors. Use shared guarded session/security
orchestration and preserve example data. Do not add decorative/fake functionality
or silently make the reference module part of every customer application.

### Owning-point and acceptance mapping

- Point 4 accounts for the reference developer journey and clearly distinguishes
  working capabilities from placeholders. Point 7 synchronizes runnable examples,
  public signatures, customer reproduction and the module changelog with its code.
- Point 5 supplies any actual prerequisite from already-approved shared contract
  repairs; point 6 includes reference as an analogous shared-control consumer.
- Point 7 owns visual alignment and the approved showcase completion, including
  module-owned backend adapters/declarations where needed, meaningful regression
  coverage and independent module/API version assessment. No versions change
  merely to record this planning amendment.
- Point 10 prepares a usable opted-in development example with current assets/data,
  preserving existing state through supported operations. Point 11 includes the
  changed reference Admin behavior in the explicit owner handoff, even if a shared
  control already has its representative sample elsewhere.
- Point 12 selects complete checks for actual changed reference backend/frontend
  contracts and rendered examples, plus real prerequisites. Pure styling does not
  alone justify database/installation/image acceptance; functional changes receive
  their actual affected-area checks. Complete verification stays last.

Instruction-product assessment: implementation must assess the public module/Admin
example guides and customer-facing reference documentation together. This amendment
itself changes only the owner plan; root/skill/customer defaults need no new rule.
The example remains optional and excluded from production. No runtime/config/schema,
version, dependency, application data or resource changed for this planning request.
The prior audit synthesis remains dated evidence; this explicit owner amendment
supplements its finite scope without rewriting historical findings or claiming
that the reference Admin views were already rendered/accepted.

### Owner clarification — visual scope, 2026-11-06

The owner first clarified that the reference-module request is primarily visual.
The amendment was temporarily narrowed to existing-view appearance and responsive
presentation, preserving functionality. No runtime implementation was made under
that intermediate interpretation. The subsequent complete-showcase decision below
establishes the effective scope, including justified missing working examples;
the point-7 deliverable and acceptance mapping above now reflect that final scope.
History remains preserved, including initial amendment commit `b4a424e4`.

### Owner clarification — complete customer showcase, 2026-11-06

The owner subsequently clarified that justified missing functionality should also
be added: reference is the customer's demonstration of Runa modularity. This
supersedes the intermediate styling-only restriction above. Point 7 now includes
a source-grounded capability/example inventory and completion of meaningful missing
examples through delivered public contracts, with visual alignment retained.
The observed metadata-only Admin declaration is an explicit completion target;
this does not authorize inventing new platform mechanisms or unrelated domains.
This planning update changes no runtime, data, dependencies or versions.

## Point 4 — Renewed documentation and developer-path reconciliation — 2026-11-06

Reconciled candidate `00da181d` after renewed points 2/3. Retain its useful
A-01/A-02/version/path corrections; the prior repeated editorial-retain reasons
were insufficient. The [renewed per-document ledger](../../reviews/RM-2026-017-documentation.md)
now covers all 194 current Markdown files under docs, including six later review/
decision records, with document-specific owning comparisons and justified
historical classifications. Closed roadmaps, dated evidence and verbatim owner
decisions retain their bytes. Root discovery and both instruction products are
assessed separately; an inventory row is not a native or semantic certification.

Point-start knowledge was current: 216 documents, 12,214 chunks, no drift,
missing/orphan chunks or interrupted write. Bounded current-intent retrieval
selected original documentation ownership and RA-03 artifact placement; opened
those originals and the authoring/skills/knowledge procedures. Followed the
architecture → setup → module/API → frontend → testing → update/deployment path
through actual command registries, initialization/setup branches, generator,
Core manifests/API definitions, public frontend exports, reference declarations,
central test runner, delivery exclusions and deployment/forward-data owners.
The ledger states the exact comparisons and native/rendered limits.

### Corrections and consolidation

- RA-03: public testing now sends generated browser evidence to
  `.runtime/artifacts/playwright/`. Owner quality distinguishes that evidence from
  visible authored scratch source awaiting promotion/removal. Existing artifacts
  remain preserved; no relocation or deletion was needed.
- RA-06: removed the stale “current 0.6.x” premise from manual quality selection;
  the 0.10.0/0.15.0/0.20.0 all-group cadence remains unchanged.
- Corrected remaining future generator/reference claims, the owner architecture's
  obsolete module/test/command tree, exact approved updater file scope and missing
  supplied-module data-phase summaries. Ordinary App and optional modules retain
  separate installation/availability and customer integration ownership.
- Removed the remaining English-only Admin direction in owner localization;
  Polish-default/English-secondary UI and English technical output stay distinct.
  Qualified maintenance transition guidance with the existing pre-1.0 policy.
- Consolidated duplicate 1.0 readiness checklists: the public guide owns shared
  scale/runtime/security/recovery requirements; the existing owner entrypoint links
  it and retains full owner --all, canonical failure disposition and explicit
  owner promotion authority. This changes no policy or release decision.
- The reference guide now identifies its management action as descriptive with
  an empty component binding, distinct from the working panel/slot. Its source
  assertion is not runtime coverage; absent private suites are no customer test
  prerequisite. Approved runnable showcase completion remains point 7.

A one-off changed-document diagnostic used existing docs_checks.structure/links
because the supported complete documentation gate has no changed-path scope.
It resolved the new readiness/canonical and public-projection link uncertainty;
**PASS for all 11 changed Markdown files**, including public projection links.
A separate in-memory census comparison confirmed **194 documents, zero missing or
obsolete inventory rows**. These are focused editorial diagnostics, not full
documentation or projection acceptance. No
routine suite, complete docs gate, installation, build, image scan, deployment or
browser matrix ran. Complete selected-area verification remains point 12.

Instruction-product assessment: root owner/customer AGENTS and the nine skill
pairs already route to the corrected canonical guides, as reconciled in points
2/3; no new global constraint or duplicated procedure is needed. The owner
readiness wrapper is private; customer links target only the public checklist.
No document moved/deleted, so stage and refresh incremental knowledge in place.
No runtime/config/schema/API/module/platform version or dependency changed. No
Docker resource or application/test data was created, reset or removed; existing
reports, model caches and operation evidence remain preserved. Commit this
reconciliation and mark point 4 complete; point 5 owns the next implementation.

## Point 5 — Renewed RA-01 pre-code design — 2026-11-06

Owning scope: Auth's private synchronous account-admission computation, identity
eligibility evaluation, factor restrictions and session-lifetime evaluation. Each
computation groups its existing settings reads in a default-connection transaction;
when already inside an owning operation it uses a child savepoint. No transaction
spans the HTTP request, owning handler, external I/O or another operation. The
registry shared lock acquired by the first settings read ends with that computation
or remains owned by its existing ancestor. No authority/user lock is acquired in
this read scope; existing mutation lock order and Audit publication are unchanged.

Reuse is limited to ModuleSettings' existing WeakMap keyed by the locked
RegistrySnapshot. ModuleRegistry's conservative connection callbacks, PDO identity,
root/savepoint rollback and replacement-root invalidation remain unchanged. No new
cache of users, sessions, grants, exceptions, availability or eligibility is added.
Time-dependent checks execute on every computation. Within account admission, the
freshly loaded user is evaluated directly, and one current-session/restriction
result is consumed synchronously without an intervening callback or write. The
next admission performs all reads again. Denial still clears the cookie session;
no database business mutation is moved into this scope.

PolicyExceptions remains a current SQL read, including revision/revocation/expiry.
OperationGuard and ModuleAvailability retain their current checks. Existing
transactional EntryOptions, SessionResource, SecurityContext/profile and Admin
operation paths need no broader wrapping. Analogous standalone policy/lifetime
computations receive the same bounded treatment. Public signatures, guarded API
versions, generators and customer entrypoints remain unchanged; implementation
helpers stay private under runa.auth/backend. Auth owns these runtime computations;
no new supported application API is introduced.

Focused evidence: rerun the same four admitted HTTP-kernel GETs used by the renewed
audit, with identical principal/fixture/payload and SQL templates without bindings,
retaining new before/after files under .runtime/artifacts/playwright/
RM-2026-017-point5. This measures kernel SQL, not FPM latency. Add regressions for
bounded supplied admission, repeated-operation revocation/restrictions and local
policy reads with same-connection writes, rollback and reused computations. Existing
registry/settings reconnect and suppressed-event cases remain required final
coverage. No broad suite or matrix runs during this point.

The first comparable pass reduced SQL to 372/94/60/474 but retained registry
generation amplification in PermissionAuthorizer::allows and scalar settings
reads while collecting management declarations. Extend the same design to one
trusted permission decision and one declaration discovery: default-connection
transaction, registry shared lock anchored before nested evaluation, no cached
decision and no handler invocation. Management discovery calls source declaration
providers only; widgets/resource/write handlers stay outside its scope. These are
private framework/integration computations, with unchanged supported signatures.
Each allows call still rereads current grants/identity/expiry and availability.
This is the same approved bounded owning-read repair, not request-wide reuse.

## Point 5 — Renewed backend reconciliation and completion — 2026-11-06

Retain candidate 1c01d885's two accurate A-09 comment corrections. The renewed
RA-01 repair supersedes the historical no-backend-repair conclusion without
reopening business/schema design. The pre-code scope above was recorded before
implementation, then extended from measured residual registry amplification.

Auth now groups account ingress, eligibility, factor restrictions and session
lifetime computations. Admission anchors the registry shared lock before nested
reads, evaluates the fresh user directly and consumes its current session once.
The trusted PermissionAuthorizer similarly anchors one decision; ManagementPages
collects pure source declarations in one read transaction. No owning handler is
wrapped by declaration discovery and no grant/eligibility result survives its
computation. Existing registry/settings invalidators, current permission queries,
health/expiry, operation fences, native mutation locks/proofs and Audit stay in
place. No persistent authorization cache or security check removal was introduced.

### Comparable SQL evidence and limits

Reused the renewed audit's same local principal, four fixed admitted GETs, browser
login/password-confirmation path and HTTP-kernel diagnostic. SQL evidence contains
only templates/counts/timings, never bindings/cookies/credentials. New artifacts
are retained under `.runtime/artifacts/playwright/RM-2026-017-point5/`; original
restart evidence remains untouched. Counts reproduced the original baseline
exactly. Measurement ran before the final manifest patch bump, with 5.0.0 runtime
metadata on both sides and the corrected PHP implementation on the after side.

| HTTP-kernel GET            | Status before/after | Queries before → after | Registry inventories before → after | Settings row reads before → after |
| -------------------------- | ------------------- | ---------------------- | ----------------------------------- | --------------------------------- |
| /api/admin                 | 200 / 200           | 479 → 309              | 36 → 17                             | 26 → 8                            |
| /api/runa/auth/profile     | 200 / 200           | 131 → 94               | 15 → 9                              | 11 → 6                            |
| /api/runa/auth/security    | 200 / 200           | 112 → 60               | 14 → 5                              | 11 → 3                            |
| /api/admin/runa.auth/users | 200 / 200           | 581 → 411              | 36 → 17                             | 26 → 8                            |

Bootstrap/page generation lookups fell from 165 to 80. Remaining separately
admitted decisions retain fresh grant/account/exception/availability reads; no
absolute endpoint query ceiling or latency gain is claimed. No relation-row N+1,
lock-wait, FPM latency, full payload equivalence, concurrent publication or complete
runtime performance certification follows from these samples. Complete backend,
security/concurrency and supplied-consumer verification stays in point 12.

### Focused regression evidence

The supported scoped command was:

```bash
python3 tools/maintainer/test-backend --parallel --processes=1 --filter='bounds synchronous account admission|bounds standalone Auth policy reads|bounds one trusted permission decision|discovers only available module declarations without constructing business handlers'
```

Final result: **4 tests PASS, 62 assertions, native exit 0, cleanup 0**. Three new
cases plus the expanded existing discovery case exercise bounded inventory/settings
reads, one current-session lookup, denial after revocation, fresh grants changed
through another connection, same-generation availability loss, nested rollback,
suppressed database events, reconnect and time-dependent session expiration. They
also assert return to the caller's transaction level and no business-handler
construction during discovery. Existing registry/settings and native transactional
regressions remain required final coverage; no broad suite ran here.

The first serial focused run refused preexisting Core schemas in base runa_test;
no data/schema was removed to clear it. The supported one-worker parallel mode
provides isolated runner-owned storage. Intermediate focused failures corrected
an assertion that counted legitimate policy-revision reads as settings reloads and
an exception assertion that incorrectly expected the safe message to contain the
reason. The final run passed. Its disposable runa_test_test_1 was removed by the
supported runner under its lock; base runa_test, development data and all existing
reports/caches remain preserved. No new Docker resources were created or removed.

### Versions, documentation and instruction products

Auth independently advances 5.0.0 → **5.0.1**: compatible internal performance
correction, unchanged guarded interfaces/settings/data and a new explicit empty
read_scopes forward edge. Its historical handlers/edges remain unchanged. Updated
the current module matrix, owning guides, module/central changelogs and source
planner expectation. Audit 1.1.0, Permissions 2.1.0 and Admin 1.0.0 retain their
versions; their implementation/signatures are unchanged. Dependent ^5.0.0 ranges
accept 5.0.1. Framework API and platform VERSION stay unchanged; feature/0.8.0
candidate range/release preparation remains later work. No generator or public
signature changed, and no compatibility adapter is needed.

RH-01: retain remember_token and document its Laravel integration purpose in
Auth sessions and the database guide. Current non-use does not establish dead
schema; alternative recaller integration must preserve all Auth guarantees.
No migration, data reset, grant mutation or dependency change was made. The
existing table/index/constraint purpose dispositions remain retained, with no new
catalog repair selected. Final module source/runtime/version checks and a normal
nondestructive development forward module update/build identity refresh remain
runtime-readiness obligations before rendered consumer work/manual acceptance;
the SQL samples do not claim the final version metadata is already installed.

The architecture guide owns the bounded private-read explanation and links Auth's
session contract. Both owner and customer backend/SQL/security selectors already
route to those public contracts and the read-path performance procedure. No new
reusable rule, supported capability or consumer signature needs duplicated AGENTS
or skill text; both instruction products require no edit. Stage intended durable
sources and refresh knowledge incrementally before the mandatory commit hooks.
Point 5 is complete with this reconciliation commit; point 6 is next.

## Point 6 — Renewed shared frontend reconciliation — 2026-11-06

Reconciled candidate e9f866d8 against approved RA-02, shared RA-05 and RH-02.
Retain A-08's in-place translation and focus/identity behavior. The renewed
baseline's component inventory and explicit unknowns remain authoritative;
the historical no-other-shared-repair statement above is superseded.

Point-start knowledge was current (216 documents / 12,213 chunks). Bounded
current-intent retrieval selected component feedback, readiness and reconciliation;
opened the original catalogue, forms/loading and Admin frontend ownership sources.
Loaded frontend/Vue/Tailwind selectors and only relevant form, component,
reactivity and theme/layout references. Tailwind 4.3.3 is the installed declaration;
this correction uses established static utilities without a new token/dependency.

### Shared owners and analogous consumer dispositions

| Owner / approved finding     | Correction and actual source consumers                                                                                                                                                                                                                                                                                                   | Evidence / remaining boundary                                                                                                                                                                                                                                       |
| ---------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AppWizard / RA-02            | Remove border-t/border-line from action row, retain spacing and the existing flex wrap, buttons, step focus, progress and slot contracts. No supplied live business Wizard consumer was found; public export and advanced-forms fixture remain supported.                                                                                | Source contradiction resolved against canonical form action spacing. No fabricated real-page visual reproduction; keyboard/mobile/theme/reduced-motion acceptance remains final/manual scope.                                                                       |
| AppFormNotifications / RH-02 | Preserve the local current ID even after dismissal/expiry. Locale-driven text changes cannot reopen it while attempt/invalid outcome stay unchanged. A new attempt or subsequent outcome can announce. Retain visible-message replacement in place and local useToasts ownership.                                                        | Chromium exercises visible PL/EN identity/focus, dismissed caller-owned translated errors and deliberate reannouncement. All shared/Admin/Auth/Permissions/Audit consumers continue through the same public facade; no per-view duplicate feedback or global store. |
| useAdminForm / RA-05         | Clear activated sections with erase on accepted subject/page/access withdrawal; remove a section's lazy activation on explicit close. Existing erase cancels resources/lookups/proofs, clears protected data and fences late responses. Failed dirty confirmation returns before erase; same-subject tab/refresh retains sibling drafts. | Mounted public-controller regression: activate creation, select existing subject, activate profile, refresh, close/reopen. No creation read carries the new public_id; closed profile stops reading. Automatic headless reads remain unchanged.                     |
| Auth users / RA-05 consumer  | Source walk through admin_users selecting placeholder, selectSubject, creation/details/bulk mode filters, atomic participant activation and lazy Auth section owners confirms why retained creation activation crossed context. No backend CreationSection guard changes.                                                                | Real create-close-details/copy/Security scenario and any local bulk/close repair remain point 7, after nondestructive runtime update/current assets. Controller PASS is not real-consumer acceptance.                                                               |
| Other lazy consumers         | Reviewed AdminSection, AdminRecordBrowser/TableSection, settings/snapshots, Auth emails/status/policy/detail sections and PermissionsAssignments. Explicit editor open/close and subject selection retain their owning API.                                                                                                              | Public guide now states reactivation/remount after selecting a subject; within-subject tabs retain active state. Generated/custom consumers use the same controller, with no generated signature change. Per-view lifecycle/readiness inspection stays point 7.     |

The component-to-consumer inventory and renewed frontend matrix retain all other
controls/layouts, navigation, temporal formatting, tables, local loading, async,
accessibility and public replacement seams. No additional shared repair was
selected from those reviewed families; their preserved tests and final/manual
obligations are not a blanket fresh PASS. RA-07 and view-local RA-05/reference
showcase changes belong to point 7. No new interaction premise was required.

### Focused evidence

To resolve dismissal/state-lifetime uncertainty, ran only:

```bash
python3 tools/maintainer/test-frontend tests/frontend/runa/unit/feedback.test.ts tests/frontend/runa/unit/admin-forms.test.ts --filter='updates the mounted invalid form notification|keeps dismissed form outcomes|Admin lazy sections end'
```

**Chromium PASS: 3 tests, 2 files, native exit 0, cleanup 0**; 41 unselected cases
were skipped, not passing coverage. Retained A-08 DOM/focus assertions and the new
PL→EN→PL dismissal/new-attempt regression both pass. The controller test confirms
no wrong-context creation read or closed-section refresh and deliberate reopening.
Vite retains its generated Vitest-runner dynamic-import analysis warning; it did
not prevent this diagnostic. No broad frontend suite/browser matrix/build/static
analysis/scanner ran. Existing dirty/conflict/identity/pending-response scenarios
remain required final suite coverage. No Docker endpoint/resource was created
or removed; inherited endpoints, reports/model caches and all app/test data remain.

### Versions and synchronized guidance

Admin independently advances 1.0.0 → **1.0.1**, a compatible correction to lazy
editor lifetime. Add a new explicit editor_scopes forward edge with no data/schema
transformation; historical edges/handlers remain unchanged. The source planner
expectation and module/central changelogs follow it. Guarded AdminAccess signatures
and API 1.0.0 are unchanged; dependents accepting ^1.0.0 remain compatible. Shared
Wizard/notification owners belong to the platform; Auth source and its owning APIs
are unchanged by this point, retaining point 5's 5.0.1. No blanket module bump,
new dependency, migration or runtime data mutation. Final version/source/runtime
checks and normal development module update/build-identity preparation remain
later obligations; no current-assets acceptance is inferred.

Updated the owning forms/component and Admin frontend contracts, current Admin
version matrix and changelogs. Both instruction products' frontend/forms selectors
already route to those canonical guides; no new global rule, duplicate procedure,
new prop or separate customer instruction is needed. Generated/default/custom
consumers retain the same public methods and document their scope. Owner and
customer AGENTS/skills therefore require no edit for these localized repairs.
Stage intended sources and refresh knowledge before mandatory hooks. Point 6 is
complete with this reconciliation commit; point 7 is next.

## Point 7 — Owning design and capability ledger — 2026-11-06

Continue the approved per-view work after the shared editor correction. Reference
uses its existing nonsensitive Ping API, with no new business operation, persistent
mutation or platform mechanism. Its module-owned ManagementPageProvider declares
an inspect page and dashboard widget guarded by app.reference.reference.inspect;
Admin entry remains independently required. A private Management adapter implements
ManagementResourceReader::{read,page} and ManagementWidgetReader::widget with the
published DTO signatures. It calls ModuleApis::resolve('app.reference', Ping::class,
'1.0.0')->read(); no direct handler/settings/foreign persistence access. The
singleton rejects subjects, cursors, filters and sorting. Its content revision is
a hash of the nonsensitive response, not a fabricated durable entity ID or health
timestamp. No write interface, proof input, new grant or Audit mutation is added.
The existing Ping::read(): PingResult and HTTP signature remain unchanged.

| Delivered capability                                                               | Current example / point-7 disposition                                                                                                                                                        |
| ---------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Identity, source/installed versions, optional lifecycle and effective availability | Existing manifest, explicit sync/update/health/preview/apply reproduction; retain disabled-by-default and production exclusion.                                                              |
| Owned PostgreSQL data and initialization                                           | Existing UUIDv7 Sample and repeat-safe welcome row; retain source/install demonstration and data, no unrelated CRUD added.                                                                   |
| Guarded PHP API and named HTTP/resource                                            | Existing Ping 1.0.0 and GET /api/app/reference/ping; both panel and Admin consume guarded public paths.                                                                                      |
| Settings, encrypted secret, revisions/reinitialization                             | Existing source/CLI example; document redacted read and exact revision/generation write, never expose credential in Admin or Ping. No generic settings editor.                               |
| CLI, queue, cancellation, schedule, storage and versioned events/listeners         | Existing inspect/work/hourly schedule/observed v1; document explicit CLI reproduction and repeat-safe/at-least-once limits. Do not turn operational work into an unreviewed Admin write.     |
| Frontend page, navigation, named slot                                              | Existing page/slot panel; align shared layout, localized feedback, retained read and replacement slot. Document host composition without recursion.                                          |
| Admin page, binding, authorization, component and widget                           | Missing working example: replace future-action metadata with owned read-only inspect page, public AdminSection composition and dashboard widget using Ping.                                  |
| Cross-module editable contributions / native aggregate writes                      | Deliberately excluded: existing example has no foreign-subject business data or persisted editing use case. Route to runnable Admin generator and Core examples instead of duplicating them. |
| Integrations, source-only health and work policy                                   | No external service is used; existing source/CLI policies are the intentional demonstration. No fictional integration or generic runtime editor.                                             |

Reference advances independently 0.2.0 → 0.3.0 for additive executable Admin
composition, retaining Ping API 1.0.0 and existing migrations/upgrades. Add one
source-only forward edge. Auth retains the unreleased 5.0.1 patch assessment with
its additional local editor/copy corrections; Admin remains 1.0.1.

Point-7 implementation checkpoint: Auth's accepted close now erases the whole
selected-subject scope through public selectSubject(null); bulk entry does the
same before its section editor. Refused dirty close returns before reset. No
profile/Security/creation or stale bulk lane remains eligible for refresh after
close. RA-07 changes both registration catalogues to administrator activation.
Reference's page/section/widget use shared Card/Button/AdminSection and public
Admin props; standalone panel uses shared FormNotifications, retains the last
response and exposes typed response markup without copying orchestration.

Focused browser-component diagnostics resolve the concrete editor/read/feedback
uncertainties: Auth dialog regression PASS (1 selected, 51 skipped), application
reference presentation PASS (3 tests), both native exit 0 and cleanup 0. The Auth
test initially used the wrong accessible close label; corrected to Close dialog.
The first app frontend invocation suffered a Chromium page crash before collection;
a subsequent selected case exposed a test assertion inspecting the wrapper instead
of the notification's body portal. Corrected that assertion; all three cases then
passed. Native failure reports remain retained. The generated Vitest dynamic-import
warning remains disclosed. No broad suite or final acceptance is claimed.

Implementation is still point 7. Native Admin boundary diagnostic, committed
source, nondestructive local runtime alignment and bounded rendered observations
remain before its completion. The final full backend/frontend/E2E, locale/theme/
viewport/accessibility/identity matrices remain points 10–12, not this checkpoint.

### Point-7 stop: newly demonstrated Admin/health premise — 2026-11-06

**Point 7 remains OPEN; implementation stopped for architectural discussion.**
The focused native Admin regression now reaches the candidate page with its inspect
grant, but GET `/api/admin/app.reference/inspect/sections/response?kind=record`
returns 503 `module_unavailable`. Immediately before it, the same test confirms
reference availability and direct guarded Ping success. The denial without inspect
is the existing deliberate 404, not 403; the assertion was corrected accordingly.

Source-verified cause: Admin Queries/Access::read calls AdminEntry::read, whose
run opens/locks its default-connection transaction before invoking Projection and
the module adapter. Ping's reference.read operation has the existing hybrid health
policy. ModuleHealth::inspect refuses applicable on-use checks at a nonzero
transaction level. The native mutation-only HealthAdmissions scope is not active
for these Admin reads; a preceding direct Ping is a separate operation and cannot
legitimately supply cached authorization/health acceptance. The private admission
engine already has bounded preparation/sealing/source/policy/expiry checks, but
extending its consumer/meaning to Admin reads is an architectural premise needing
explicit review, not an application presentation workaround.

No policy changed to scheduled, no health/permission check was skipped, no private
engine was imported into reference, no generic cache/adaptor was added, and no
Admin transaction was removed. Root AGENTS' significant-unknown stop and point 7's
new-platform-mechanism boundary apply. Asked the owner whether to extend point 7
with an explicit design preserving per-operation health, source/policy/generation/
expiry fences, authorization/proof, lock consistency and worker cleanup; no new
design or implementation is approved by this checkpoint. Resolve scope/design
before further runtime changes, final acceptance or advancing to point 8.

Final focused native diagnostic: **FAIL, 1 test, 16 assertions before refusal,
native exit 1, cleanup 0**. The expected successful read remains a failing
regression rather than a skipped/pending test or rewritten PASS. Intermediate
fixture errors were corrected (Core-only history cleanup helper and the deliberate
hidden-page 404). The supported one-worker runner removed its owned
runa_test_test_1 after every run; base runa_test and development data remain intact.
No development module update, enablement/grant, reset, asset build, Docker resource
creation or deletion occurred. Existing reports/model caches and endpoints remain.

Point-7 source/docs/tests are retained as a reviewable implementation checkpoint;
their incomplete state is explicit in the example and public authoring guide.
The backend adapter/source/lifecycle regressions added to ModuleReferenceTest are
unexecuted final obligations. Frontend's 1 owner + 3 application focused PASSes
remain valid for their stated scenarios only. No broad quality graph was run.
Both instruction products already route Admin composition and health/transactions
to canonical guides; no reusable instruction change is approved at this stop, so
owner/customer AGENTS/skills need no edit. Any agreed platform repair must assess
them again along with module/API versions and current public guidance.

Checkpoint hook reconciliation: the first commit attempt was correctly refused
by the application public-import rule. Its allowlist omitted the already delivered
and documented @/runa/admin facade although the facade exports the used types and
controls. Add only admin/index.ts to the existing source allowlist, without exposing
private Admin paths. Extend the existing AST regression with public type/value and
private-implementation cases. Supported focused tooling diagnostic:
`python3 tools/maintainer/test-python --scope=contracts --path=contracts/test_api_boundary.py --filter=frontend_rule_uses_ast`
— **1 PASS, 6 deselected, exit 0, cleanup 0**. This is a narrow enforcement
correction needed to retain the customer example, not approval or implementation
of the stopped health-admission design. No rule or hook was suppressed.

### Point-7 design review requested — 2026-11-06

The owner explicitly authorized preparing and presenting the Admin/on-use/hybrid
repair design before implementation. This is not approval to implement it.
The [proposed design](../../reviews/RM-2026-017-admin-read-health-design.md)
recommends an optional pure source read-call plan and one health admission per
public invocation occurrence. Checks run outside transactions; adapter/business
reads and current authority/proof/result validation retain the locked Admin root.
It covers record/page/options/widget consumers and widget failure isolation,
refuses unplanned calls and avoids request-wide health or authorization reuse.

The exact proposed signatures, dynamic-call limits, public/internal ownership,
Framework API MINOR assessment (proposed 1.1.0), alternatives and regression
obligations are in that review document. Platform feature/0.8.0 remains unchanged.
No runtime, public contract, manifest/version, migration, test or dependency was
changed for this design-only work; no new runtime verification was executed.
Gate B remains approved, lifecycle active and point 7 open. Await the owner's
explicit design decision before implementing this prerequisite or advancing.

### Point-7 Admin read-health design approved — 2026-11-06

The owner explicitly accepted the presented
[repair design](../../reviews/RM-2026-017-admin-read-health-design.md) and instructed
recording approval and proceeding. Exact-occurrence read plans, independent health
preparation, existing locked business reads, unplanned-call refusal and the
additive Framework API 1.1.0 assessment are authorized within point 7. Preserve
all documented authority/proof/health/source/expiry/worker invariants and widget
failure isolation. The preceding discussion stop is superseded; Gate B remains
approved and the roadmap active. Point 7 remains current/open until its full
implementation, documentation, necessary focused evidence and commits are done.
Platform feature/0.8.0 and points 8–12, including owner manual acceptance and final
verification last, are unchanged. This is not release, dependency or reset approval.

### Point-7 exact-call health prerequisite implemented — 2026-11-06

Implemented the approved source-plan extension and private read coordination;
point 7 remains open for its remaining supplied-view work/readiness dispositions.
Framework API **1.1.0** is centralized in private FrameworkApi and used by graph,
availability, upgrade planning and generation. Admin 1.0.1 and reference 0.3.0
require ^1.1.0. Audit 1.1.0, Auth 5.0.1 and Permissions 2.1.0 retain their versions
and compatible ^1.0.0 framework ranges. AdminAccess 1.0.0/Ping 1.0.0 signatures,
historical upgrade handlers/edges and platform VERSION are unchanged; source-only
reference admin_showcase/Admin editor_scopes edges already cover these unreleased
module versions. No dependency, migration or reset was needed.

ManagementReadPlanProvider/ManagementReadCall are framework-owned public source
extensions; exact signatures/limits/ownership are documented in Admin declarations.
Private ManagementPages retains binding-to-provider planning without serializing
provider state into discovery fingerprints. ApiDefinitions shares the existing
full typed API descriptor validation between guarded invocation and preparation,
without constructing business implementations. Existing ModuleApis consumers keep
their public methods; constructor changes remain private framework composition.

AdminRead first selects authorized source/input, prepares separate occurrences
outside transactions and then reauthorizes source/plan/generation/principal/proof
inside the existing locked default-connection entry root. Business readers run
only there. Each prepared occurrence is consumable once; repeated/nested public
calls get separate evidence/frames. Source/policy/API mapping, expiry, rollback,
PDO/reconnect and final result fences remain current. Rollback callbacks and a
weak connection observer work with database events suppressed; unbalanced child
transactions are refused and unwound to the handler's entry level. Scope state
always clears in finally. Native mutation admission is not replaced.

Empty-plan reads retain a single entry transaction. Pure declaration/input
selection is shared only within that same admitted computation; final selection
is rebuilt after independent preparation. No decision crosses those phases.
The supplied Core health declarations are empty; their fixed entry/proof calls
retain the owning guarded checks, including a new final proof-expiry assertion.
No request-wide authorization/eligibility/health cache was introduced.

Widget refinement required by the existing Permissions contract: an isolated
reader/plan failure while the owner remains available renders an empty unavailable
widget and preserves other bootstrap content. A critical owner health failure
withdraws its effective source grants too, so the widget is omitted. Do not bypass
owner availability to retain a diagnostic. Source/flag/principal/root authority
changes still refuse the response. Native regression covers both dispositions.

Focused supported diagnostic:

```bash
python3 tools/maintainer/test-backend --parallel --processes=1 --filter='validates exact read plans|distinct Admin read occurrences|extra prepared Admin read|invalidates prepared Admin read|guards the reference Admin showcase|generates opt-in Admin consumers|refuses Admin read results'
```

**10 PASS, 147 assertions, native exit 0, cleanup 0.** This resolves call-count,
constructor-before-refusal, nested/repeated invocation, time/policy/reconnect,
suppressed-event rollback, pure plan/target validation, reference section/widget,
revocation between selection/preparation, widget failure, late proof expiry and
exact generated-consumer uncertainty. Intermediate failures corrected an identity
validator inappropriate for repeated call occurrences and test fixtures assuming
Connection::withoutEvents, a nonexistent counter and an uninstalled Mockery
package. No dependency was installed; the read-failure fixture uses existing
Monolog support. Initial widget expectation was aligned to effective-grant
withdrawal rather than bypassing availability. Native failures remain failures.

The supported runner removed its owned runa_test_test_1 after each diagnostic;
base runa_test, development data, existing endpoints, reports/model caches remain.
No Docker resource was created/deleted and no development data update/build/reset
ran in this prerequisite. Broader backend/frontend/source/version/consumer checks
remain final obligations; this selected diagnostic is not full suite acceptance.

Synchronized Admin/health/public-boundary and example guides, public allowlist,
generator, reference/customer reproduction and changelogs. Both backend instruction
selectors now route exact-call readers to the canonical declarations guide; owner
and customer products preserve their distinct scope. Root AGENTS needs no duplicate
rule. No new security exception or workaround was introduced. Commit this cohesive
prerequisite before continuing the remaining point-7 inventory/readiness work.

The reference's owned Admin section additionally composes the real public
useAdminForm/AdminSection controller in Chromium, preserving its old readonly
response while an explicit refresh is pending and binding only that button's busy
state. Supported targeted command:
`php src/artisan runa:test:frontend ../tests/frontend/app/unit/modules/reference/presentation.test.ts --filter='reference Admin section'`
— **1 PASS, 3 skipped, exit 0, cleanup 0**. Existing panel/widget cases remain
separate retained evidence, not rerun coverage. The generated Vitest dynamic-import
warning persists without blocking this diagnostic. No runtime-version or asset
readiness claim follows; the bounded real consumer observation still needs a
normal nondestructive development preparation and current dev assets/HMR.

### Point-7 reference test delivery corrected — 2026-11-06

Post-implementation delivery review found the new reference frontend cases under
app/unit/modules rather than the canonical central app/modules root. The existing
acquisition exclusion covered backend cases only, leaving frontend imports behind
when the customer declined reference. Move the file to
`tests/frontend/app/modules/reference/presentation.test.ts`, declare that root in
its manifest and add app/runa module globs to the existing Vitest configurations.
The source installer excludes this root alongside declined reference source,
backend cases and owning docs. Existing customer configurations remain customer-
owned; protected updates do not overwrite them.

Extend the existing source acquisition/reopen fixture with this frontend file and
include/exclude assertions. Supported focused tooling command:
`python3 tools/maintainer/test-python --scope=contracts --path=contracts/test_bootstrap.py --filter=reference_choices_survive_reopen_and_production_projection`
— **1 PASS, 26 deselected, native exit 0, cleanup 0**. This is the local isolated
Git/source projection fixture, not native installation, Docker or production-image
acceptance. Temporary fixture checkouts are removed by their supported lifecycle.
The moved Inspector case also passes through the application frontend command at
its canonical path: **1 PASS, 3 skipped, native exit 0, cleanup 0**. Retain earlier
commands/paths as historical evidence; current reproduction uses the new path.
The Vitest dynamic-import warning remains disclosed. Full frontend/source/delivery
checks remain final. No data/runtime/dependency change accompanied this correction.
Owner/customer test selectors already route to the canonical public test guide;
updated that guide and the owner discovery description, manifest and changelog.
No new always-loaded rule or copied customer instruction is needed.

### Point-7 supplied-view reconciliation and completion — 2026-11-06

Complete the implementation with the
[view/state dispositions and final selection](../../reviews/RM-2026-017-view-dispositions.md).
Every supplied family has a correction or a source-grounded retention reason;
the capability ledger above covers the reference showcase and justified exclusions.
Public presentation/security owners are retained, and current module/generator,
test discovery/delivery and instruction-product guidance is synchronized.

Local runtime preparation used the exact committed source `bef757e2` and the
documented trusted module plan/apply and native health/transition contracts, with
maintenance and a retained operation journal. Auth/Admin were upgraded without
reset; reference 0.3.0 was installed and enabled with its single welcome sample.
Existing users/settings/data and base test databases remain. Bounded real browser
observations confirm the repaired RA-05 sequence and reference page/refresh/widget
200 responses, with PL/EN, desktop/light and phone/tablet/dark samples and no page
errors or horizontal page overflow in those settled samples. Focused component,
backend and optional source-delivery results are recorded above; none is a full
suite or final matrix result.

Retain the initial wrong-label timeout and the later single unclassified activity
503 as failed observations. Its cause is unknown; a subsequent settled diagnostic
and three concurrent read/heartbeat pairs succeed. Final automated selection must
include concurrency and protected-content recovery; a reproducible failure needs
an owning repair. Do not manufacture a PASS or weaken the generation/proof fence.
The final rendered/manual selection is explicit in the linked review.

Stopped the task-owned Vite process on port 5174. Its generated dependency cache
was relocated from the unconfigured Vite default `src/app/node_modules/.vite` to
`.runtime/frontend/vite/RM-2026-017-point7` and the empty generated parent removed;
cache configuration is a demonstrated point-8 correction. Preserve browser/native
reports, development/reference data, base test database and knowledge/model caches.
No Docker resource was created or removed. Point 10 still owns preparing stable
current assets/data before point-11 owner acceptance. No release is prepared.
Point 7 is complete in this reconciliation commit; continue with point 8.

## Point 8 — Tooling/configuration/knowledge reconciliation — 2026-11-06

The [owning dispositions](../../reviews/RM-2026-017-tooling-dispositions.md)
record actual input consumers, dependency retention, source/native evidence limits
and the synchronized instruction products. No dependency upgrade, Docker rebuild,
new environment key, database reset or knowledge architecture change is selected.

Correct two demonstrated freshness/placement defects: Vite now selects absolute
workspace-root runtime caches, and DevelopmentSetup hashes the shared/module
frontend, registry and native error build inputs it previously omitted. Unchanged
inputs still skip installation/build; changed inputs require preparation without
introducing data operations on reopen. A real HMR startup creates the correct
cache and no source-local node_modules parent; resolving both installed Vite
configs confirms their application/error cache paths. Stop that task-owned process.
The focused real-fingerprint startup fixture passes **1 selected, 19 deselected,
native exit 0, cleanup 0**, without an actual build/install/data operation.
Also align the native frontend empty-scope detector with the canonical module
root: a module-only customer suite executes Vitest, and declined/absent tests
remain empty. Its focused command-selection fixture passes **1 selected,
13 deselected, native exit 0, cleanup 0** without starting a browser.

Extend private metadata/golden cases with source-verified RM-016 11a93 superseded
and 11a95 accepted presentation decisions, plus the current public Button source.
Exact historical hash and heading bind the annotations; closed bytes remain
unchanged. Existing missing-evidence and customer receipt/private exclusion cases
remain final obligations. Real composed configuration resolves the two statuses
and supersession link. Focused existing annotation-hash refusal fixture passes
**1 selected, 24 deselected, native exit 0, cleanup 0**. Complete golden/doctor/
Boost/native acceptance stays final, not implied by a fixture or configuration load.

The initial relevant knowledge check was stale after point-7 docs, so it was
incrementally refreshed before bounded current-intent retrieval; the canonical
artifact-placement original was opened. Refresh subsequent staged source/metadata
changes incrementally before commit; preserve the collection, models/caches and
receipt scope. Public environment/command docs, central changelog and the source
configuration map are synchronized; existing owner/customer selectors already
cover these rules and need no duplicated policy. No module contract/version
change follows. Preserve all data/reports/caches; no Docker resource was created
or removed. Point 8 is complete in this commit; point 9 is next.

## Point 9 — Campaign foundation checkpoint — 2026-11-06

**Point 9 remains open.** Before code, recorded the planned complete signatures,
state/provenance, source declarations, native/coverage/owner separation and bounded
recovery in the existing quality and command guides. Relevant current/audit local
retrieval selected the active D-01–D-03 design, whose original section was read;
knowledge was current after the point-8 incremental refresh.

The first implementation extends the existing QualityService, models and executor:
explicit validated source/env input declarations, shared harness/lock/runtime
fingerprints, retained attempts and hashed state/log proofs. Starting another
execution no longer deletes the previous quality directory. Current native attempt
state is copied to its own archive, unknown files stay preserved, and a resume
preview does not modify evidence. Default/unknown/external input boundaries rerun;
changed graph, source, command, environment or prerequisite proof invalidates reuse.
Private config values are digested, never serialized. Tampered/missing evidence,
symlinked paths and substituted in-memory native state are refused.

The same coordinator accepts the internal optional resume argument; catalogue
activation, public command integration, native report capture, final closure proof
and backend recovery remain unfinished. Do not advertise a complete supported
resume or owner crash disposition yet. Reused nodes wait for fresh prerequisites;
a new mandatory failure blocks them instead of inheriting an earlier PASS.
No real campaign, complete suite or owner SIGSEGV acceptance ran during this work.

Focused synthetic fixture evidence: the first eight input/archive cases passed
(native exit 0, cleanup 0); the real service preview/resume case then passed
(1 selected, 8 deselected, native exit 0, cleanup 0). It uses tiny true/print
processes in a disposable Git fixture, confirms unchanged frontend evidence reuse
and backend-only invalidation, and keeps original native results. Further focused
fresh-prerequisite checks are recorded with their received result before commit.
The actual backend event adapter, crash attribution and bounded recovery are next.

### Point-9 native evidence checkpoint and design stop — 2026-11-06

The fresh-prerequisite/resume focused fixtures pass **2 selected, 8 deselected,
native exit 0, cleanup 0**. Commit `777b07b9` preserves that campaign foundation;
catalogue/report/closure/recovery integration is still pending, not delivered PASS.

Neutral TestService now reserves an independent backend attempt before native
execution and preserves its command, JUnit and native/cleanup result. Configured
PHPUnit case tracing records worker identity, exact case/dataset/filter names and
preparation/outcome/completion without assertion or exception payloads. It is
inactive outside the supported attempt lifecycle. No customer retry/acceptance is
introduced; independent module versions are unaffected by testing infrastructure.
The public testing guide documents current evidence and its limitations. Existing
owner/customer instruction products already require honest native results and
preserved evidence; no new duplicated rule is needed for this neutral checkpoint.

The installed Pest source invalidates the assumed parent adapter seam: handlers
overwrite --runner, and parent dispatch is sealed before extension bootstrap.
The first two focused native diagnostics each execute **1 passing test / 5
assertions but return native exit 1, cleanup 0** because registration in that
already sealed parent is refused. These failed reports remain untouched.
Explicitly mark that unavailable parent tracing and keep worker tracing through
its supported extension API; the same selected native diagnostic then passes
**1 test / 5 assertions, native exit 0, cleanup 0**. It proves worker events only,
not actual crash attribution. Remove the ineffective experimental runner override.
Supported runner fixtures for failure propagation/cleanup pass **2 selected,
12 deselected, native exit 0, cleanup 0**; the reservation/native-signal fixture
passes **1 selected, 14 deselected, native exit 0, cleanup 0**. No SIGSEGV was induced or its
root cause investigated. Scoped runner cleanup removed its worker database and
preserved the base test/development data; no Docker resources were created.

This changes a significant accepted implementation premise. **Stop the native
startup/recovery implementation and return to owner discussion**, keeping the
roadmap active and point 9 open. The concrete
[adapter design and alternative](../../reviews/RM-2026-017-native-evidence-adapter-design.md)
explain the installed-source evidence and proposed constrained native startup
adapter. Asked the owner whether to authorize that adjustment; do not infer
approval from the prior high-level D-02 decision, elapsed time or the worker
trace PASS. No output-text 139 heuristic, private reflection, vendor patch,
alternate scheduler or silent D-02 deferral is implemented.

### Point-9 owner clarification — correct the owner, no adapters — 2026-11-06

The owner initially approved the native evidence adapter proposal, then explicitly
stopped further work and questioned why an adapter was needed before platform
1.0.0. Clarified that the demonstrated constraint belongs to external Pest/ParaTest,
not a Runa backward-compatibility requirement. The owner then directed this to be
recorded and applied: before Runa 1.0.0, use a coherent source-owned correction,
without adapters or workarounds. The earlier adapter approval is superseded;
its retained design now clearly identifies the proposal as historical.

Correct or restructure the owning Runa test integration and update current
consumers/tests/docs. The current startup, internal APIs and class structure are
not compatibility constraints. Do not layer a startup adapter/decorator/shim over
that path, preserve an obsolete path in parallel or silently defer D-02. External
library constraints remain real and must be assessed, but do not authorize a
bypass, private hook or heuristic. Existing native result honesty, complete
coverage accounting, bounded retry budgets, cleanup, data protection and customer
policy separation remain required. Dependency additions/updates retain their
explicit approval policy. Record the next concrete integration design under this
standing direction; no repeated permission for necessary Runa API changes.

Synchronize the root owner contract and canonical pre-1.0 release policy, current
quality specification and changelog. Existing backend skill already routes to the
owner pre-1.0 rule and needs no copied procedure. Customer instruction products
remain unchanged: this is owner-only Runa maintenance authority and does not grant
customers permission to edit protected platform source. Do not remove or rename
existing supported integration entrypoints merely because their names use
"adapter"; this decision rejects an adapter/workaround repair strategy.

This checkpoint records the decision only: no runtime/source implementation,
dependency change, test execution, data operation, release or roadmap closure.
Point 9 remains open/current; the approved D-01–D03 outcomes remain pending.

### Point-9 direct integration assessment — 2026-11-06

Followed the latest owner direction by inspecting the owning TestService and
installed Pest/ParaTest/Laravel dispatch, discovery and worker termination sources.
Local knowledge status was current (220 documents, no stale/missing/orphan chunks);
bounded current-intent retrieval was followed by original-source inspection.

The exception seam has an additional decisive limitation: ParaTest's
`WorkerCrashedException::fromProcess` formats OS exit/assignment into its message,
retains no structured worker/signal fields and sets the exception code to 0.
`WrapperWorker::getExitCode` is the last test-status byte, not an OS termination
getter; its Process and the Pest wrapper's workers are private. Thus even a
different Runa startup catching that exception cannot obtain the approved
machine evidence. No console parsing, private reflection, vendor modification or
startup workaround is introduced.

The [direct integration assessment](../../reviews/RM-2026-017-native-evidence-integration-boundary.md)
records exact installed versions, source contracts and two concrete architecture
routes: obtain a supported upstream evidence contract while retaining native
scheduling, or amend the roadmap so the existing Runa test lifecycle owns native
worker supervision. The latter changes the explicit no-new-runner/native-mode
premise; the former requires dependency assessment and separately approved exact
changes. Neither is silently authorized by pre-1.0 API freedom. Implementation
stops for this owner decision under the significant-unknown rule. Gate B and the
roadmap remain active, point 9 remains open, and D-02 is not deferred or complete.

No runtime/configuration/dependency changes, tests, broad verification, live crash
experiments, data operations or resource deletion ran in this continuation.
Existing native evidence is preserved. Instruction products need no change for
this unresolved engineering assessment; customer policy remains unchanged.

## Point-9 owner correction and implementation withdrawal — 2026-11-07

The owner explicitly rejected the automated interpretation and supplied the
[complete corrected requirement](../../reviews/RM-2026-017-point-9-owner-correction.md).
The latest instruction narrows immediate work to roadmap reconciliation and
withdrawal of changes already introduced under point 9; do not execute point 9
now. No new feasibility point is needed: the dependency-versus-worker-management
question and its investigation are obsolete for this requirement. All previous
approval/investigation/checkpoint records above remain historical evidence.

### Exact withdrawal scope

Restore these tracked sources to the point-8 checkpoint `644f34ab`:

- Shared quality executor, models, service and state; remove `quality_campaign.py`
  and its dedicated contract tests. This withdraws the incomplete resume/proof/
  archive implementation from `777b07b9`, including its otherwise independent
  D-01 mechanics, because the owner requested withdrawal of point-9 implementation.
  The independent evidence objective remains for assessment, not implicit reapproval.
- Shared TestService, its runner unit tests, owner/application PHPUnit XML;
  remove CaseEvents, CaseEvidence and CaseExtension from `76f51886`. No per-case
  trace, attempt/JUnit reservation or extra recovery infrastructure remains.
- Public testing/command guides and maintainer quality guide: remove point-9
  delivery claims and the rejected campaign implementation specification. Remove
  its unreleased changelog claim; earlier roadmap entries preserve the design.

Keep root AGENTS and the canonical release guide's general pre-1.0 no-workaround
policy from `629e73e5`: it is reusable owner policy, not SIGSEGV implementation.
Keep points 1–8, meaningful commits, native reports, runtime archives, data and all
existing resources. No reset, history rewrite or resource cleanup is performed.
Both dated native-evidence analyses remain available unchanged as historical
investigations of a rejected direction, not a pending architecture decision.

### Instruction products and remaining work

The corrected checklist and final-verification point describe the intended agent
workflow and explicit owner acceptance. Canonical root/testing/quality/roadmap/
release/template and relevant skill synchronization belongs to the deferred
point 9; it is not performed or claimed complete here. Customer instructions and
skills need no edit for this withdrawal: restored shared tooling introduces no
new consumer contract, and the corrected temporary exception is owner-only.
Independent module versions and platform/app versions do not change.

Verification of this withdrawal uses exact source equality against the committed
point-8 baseline, removed-symbol/reference inspection, diff whitespace checks and
mandatory commit hooks. No backend/frontend/tooling suite, quality campaign,
live SIGSEGV, image build or scanner pipeline is run. Knowledge is refreshed for
changed durable sources before commit. Point 9 stays unchecked and deferred.

## Point 9 — Agent procedure and evidence continuity completed — 2026-11-07

The owner explicitly instructed completion of the corrected point 9. Implemented
only the agreed guidance: canonical owner testing procedure, root AGENTS summary,
owner roadmap/quality/release entrypoints, backend skill routing and neutral
shared roadmap-template evidence fields. Replaced the obsolete standing automatic
SIGSEGV PASS and unconditional whole-graph restarts. Historical correction,
rollback, investigations, native reports and prior decisions remain unchanged.
No runner, campaign, automatic retry/acceptance, attempt ledger, worker integration,
dependency, runtime configuration, module/API version or application change.

### Existing evidence capabilities and limits

Source inspection: `tools/application/quality_service.py` retains latest native
step state/logs but deletes the completed latest directory on a new intentional
run; `tools/maintainer/owner_cli.py` selects `.runtime/quality`. The testing runner
already forwards native backend selection arguments and `--filter`, keeps guarded
suite execution and cleanup, and offers no reliable new crash attribution.
Quality state represents native results, not combined cross-run owner assessments;
closure validators do not automatically merge separately obtained results.

Use existing supported commands and manually preserve reports before replacement
under `.runtime/verification/retained/`, outside the replaceable quality directory.
Record candidate/environment/prerequisite validity, original evidence paths,
coverage, remaining checks and decisions in the roadmap or linked retained review.
These existing capabilities plus a committed reasoning record satisfy the present
cross-session need. Automatic archival, machine resume and automatic closure
composition remain unavailable, not delivered or implied. No independently
necessary tooling implementation is established or authorized by this assessment.
Missing local evidence cannot support a carried-forward PASS.

### Bounded hypothetical procedure walkthrough

These are source-reviewed hypothetical decisions, not executed tests or native
verification results. No SIGSEGV was induced and no suite was run for this point.

| Scenario                                                            | Required agent action and disposition                                                                                                                                                                                                     |
| ------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Frontend/security checks pass; backend worker crashes               | Preserve reports before replacement, confirm signal and cleanup, keep unrelated valid checks, execute remaining supported scope; coordinator FAILED stays FAILED.                                                                         |
| Reliable exact case passes on first or second extra attempt         | Record original crash and later native PASS with mode/selection; stop attempts after success and continue remaining coverage. Never relabel the original run.                                                                             |
| Exact case confirms SIGSEGV in both extra attempts                  | Stop after two, continue other safe tests, record exhausted interrupted case and coverage; request explicit owner acceptance before release. No automatic PASS.                                                                           |
| Worker signal known but exact case identity uncertain               | Do not infer from last progress output; record unknown identity and select safe remaining or necessary broader backend scope. Unknown/unexecuted cases block release.                                                                     |
| Several ordinary backend failures found                             | Continue safely reachable scope, consolidate related failures, insert repair points, run complete affected-area suites in the failing mode with real prerequisites before completing/committing repairs. Preserve valid unrelated groups. |
| Source/configuration/dependency/prerequisite changes                | Assess actual validity, identify invalidated checks and rerun them plus real prerequisites; uncertain validity requires adequate broader selection. Revalidate the full agreed scope without stale PASS.                                  |
| Cleanup fails or crash is timeout/OOM/unknown                       | Outside SIGSEGV tolerance; preserve blocking failure, resolve safely. Neither prior standing policy nor an owner crash exception waives cleanup/ordinary gates.                                                                           |
| Session changes or latest reports will be overwritten               | Preserve evidence outside `.runtime/quality`, record provenance/remaining work and the cumulative two-attempt budget; receiving agent inspects it. Missing proof cannot become PASS.                                                      |
| Closure validator cannot represent explicitly accepted interruption | Preserve native failed validator/coordinator; separate owner decision names accepted cases and complete independent remaining check evidence. No runtime patch, fabricated all-green result or unrelated waiver.                          |

### Documentation and instruction-product disposition

Owner AGENTS, testing/quality/roadmap/release guides and backend skill now route to
one canonical procedure; the shared template only adds neutral provenance,
coverage and decision fields. The public testing/command guides already document
supported selectors and customer recovery; leave their customer restart/diagnosis
policy intact. Customer AGENTS/skills require no change because no delivered
execution behavior or customer acceptance authority changes. No new skill is
needed. Central unreleased changelog describes the owner workflow correction.
Retirement requires a confirmed corrected runtime and complete parallel backend
verification without the exception; retain the general evidence-continuity rule.

Validation is limited to source/decision consistency, existing selector and report
replacement inspection, local link/whitespace review, incremental knowledge
freshness and mandatory commit hooks. Full suites, quality graph, scanners and
runtime acceptance remain point 12. No data operation or resource cleanup occurred;
all application/base test data, native reports, backups and model/Qdrant caches are
preserved. Point 9 completes with this commit; point 10 remains unstarted.

Focused documentation result: `python3 tools/maintainer/check-docs` — **PASS,
native exit 0** after restoring the active roadmap's historical heading anchors
used by retained review links. This validates placement, links/anchors and current
README/command metadata, not final suites or rendered acceptance. The public
`runa:doctor:docs` diagnostic rejects owner-only links by design and cannot replace
this owner entrypoint. `git diff --check` passes. Incremental knowledge refresh
and mandatory hooks complete the commit boundary.

## Point 10 — Qualitative review and runtime readiness — 2026-11-07

Completed the [finding/requirement matrix, test-value ledger, renewed skill
scenarios and concrete owner acceptance sheet](../../reviews/RM-2026-017-point-10-review.md).
Reconciled A-01–A-09, RA-01–RA-07, RH-01/RH-02, reference scope and the approved
Admin health premise against source and retained owning-point evidence. The
point-9 owner correction governs rejected automation; current runner limitations
are explicit. RM-2026-018's separately agreed copy/device/refresh corrections
remain draft scope. No new architecture choice or demonstrated Critical/High
application defect was found within this review's stated limits.

No test deletion/consolidation or arbitrary reduction is justified. The ledger
keeps distinct backend freshness/security, component lifetime/feedback, actual
consumer, parser/projection and runner cleanup protections; withdrawn campaign
fixtures remain withdrawn with their rejected implementation. Comparable point-5
query reductions are retained without an invented latency, bundle or suite-duration
gain. All nine owner/customer skill selectors have source-grounded positive and
negative walkthrough ratings; these are not independent agent benchmarks.

Read-only local ModuleUpdates plans show source/installed agreement and no new
upgrade steps. Existing reference data and authorized normal login/password proof
are usable. No migration/reset/reseed or grant/settings change. Current 0.7.0
satisfies the source ranges, so no early 0.8.0 candidate preparation/approval is
needed. Independent module/release range reconciliation remains final work.

Prepared SPA and native/emergency error assets through the documented container
build and prerequisites. The initial default production-mode build correctly
excluded reference, so its real page observation timed out despite API 200.
Preserve that failed observation. An explicit existing Vite development-mode SPA
build includes the optional example; environment/reference docs now explain this
selection without weakening production exclusion. Real Nginx browser reads then
confirm reference page/refresh/widget, dashboard, user directory, settled account
form and the RA-05 create-close-details-copy-Security sequence. No unexpected
failed API, page error or toast in the successful bounded observations. The first
account screenshot is only a loading frame; retain it alongside the settled one.

Evidence is retained under `.runtime/artifacts/playwright/RM-2026-017-point10/`,
including failed/successful JSON, screenshots and `build-identity.json`. Current
entry is `/build/assets/index-zpiZqt8q.js`; owner URL is `http://localhost:8080`,
observed internally through its existing Nginx sibling. Runtime implementation
is unchanged from source `efbb2e60`; this point adds only review/public build
guidance. No HMR process remains, no Docker resources were created/deleted and
all development/base test data, credentials, backups, recovery journals,
knowledge/model/package caches and prior reports remain preserved.

Owner/customer instruction products already select the canonical public
presentation/build procedures and require no duplicated rule or new skill. The
new owner review remains private. Stage durable sources, refresh knowledge and
run mandatory hooks at the commit boundary. This point runs no full suite,
quality graph, security scanner, native installation or image pipeline. Build
prerequisites and bounded real readiness observations are manual-runtime preparation.
Point 11 stays unchecked pending the owner's explicit tested-source/environment
acceptance; point 12 remains the final automated verification/release point.

Focused results: owner `check-docs` PASS/native exit 0; `git diff --check` PASS;
served development component registry exactly matches its current source
discovery, including reference. These checks do not certify final suites or
replace owner acceptance. Mandatory hooks complete the commit boundary.

## Point 11 feedback and point 10a repair authorization — 2026-11-07

The owner inspected the reference Admin page and rejected its functional
completeness: a Ping card/widget is insufficient executable customer documentation.
The owner explicitly requires examples of Audit on an input mutation, separate
permission-controlled fields and writes, and an Auth password-protected operation.
The subsequent instruction authorizes a repair point before the existing point 11,
recording the enduring obligation and expanding the reference. Feature/0.8.0
remains approved; there is no second active roadmap or release/version approval.

Added point 10a without renumbering the existing manual/final points. The
[canonical owner rule](../../development/reference-module.md) governs every new
Runa module and customer-facing addition, with executable source/reproduction,
public contracts, meaningful tests and a capability matrix in the same point.
Root AGENTS and owner backend selector route to it. The public module guide and
customer backend selector describe consuming/adapting the optional example;
customer root AGENTS needs no owner maintenance authority. Existing frontend,
Laravel, security and PostgreSQL selectors already route implementation to the
correct public contracts and need no duplicate policy. Historical point-7/10
claims and dated evidence remain preserved; they do not accept the omission.

Before runtime edits, inspected native Admin commands, ModuleSettings publication,
Core AuditParticipation, Auth policy publication, snapshots, proof, public API and
module source boundaries. Knowledge was current at point entry, and bounded
current-intent retrieval was followed by original-source reads. Relevant backend,
Laravel, frontend/Vue/Tailwind, security and PostgreSQL skills were selected;
Boost version-aware validation/transaction documentation was queried.

The dedicated settings editor exposes a significant missing public authoring
seam: independent settings publication has no module-owned authorization callback
inside its actual transaction; Core only delegates that hook to Auth policy.
A proof check before update or an ambient wrapper would not preserve the required
atomic protocol. The [concrete native settings authorization proposal](../../reviews/RM-2026-017-reference-settings-authorization.md)
records current source, the exact optional provider contract and required refusal/
rollback/authority checks. Requested explicit owner agreement under the roadmap's
significant-unknown rule before implementing this new public extension premise.
Point 10a remains open; no implementation, manual acceptance or final PASS is claimed.

This checkpoint changes only instructions/planning/documentation. No runtime,
module version, dependency, migration, build, settings/grant mutation or data reset
has occurred. Existing assets/data/credentials/reports/caches and resources remain.
Focused documentation/link consistency and mandatory hooks apply to this coherent
checkpoint, with staged-source incremental knowledge refresh before commit.

## Common settings refactor approved and planned — 2026-11-07

After the initial settings-authorization design stop, the owner first requested
architecture discussion, then clarified that safe settings handling should be a
framework standard available to every module. The owner explicitly approved the
restated discovery/problem/repair direction and requested this detailed plan.
This resolves the earlier pending decision. It does not approve preserving Auth's
special route alongside a new reference route or moving private module engines
into the framework.

The [approved responsibility and execution specification](../../reviews/RM-2026-017-reference-settings-authorization.md#approved-direction--2026-11-07)
owns the current design; the initial proposal remains clearly historical. Split
the repair into 10a framework, 10b Auth migration, 10c reference backend, 10d
reference presentation and 10e coverage/runtime readiness. Dependencies are ordered;
each implementation owns its documentation/tests/version assessment and commit.
The already committed executable-reference maintenance rule remains effective.

Framework coordinates native settings mechanics and supported extension contracts.
Auth owns password/proof verification and policy-specific effects; Permissions
owns grants; Audit owns history persistence. Communicate through supported public
APIs/source contracts, never foreign private classes or tables. The common path
must replace special Auth dispatch. Reuse current operation history/snapshot
storage and distinguish generic history from Auth domain consequences. Preserve
atomic publication/proof/Audit, native multi-stage recovery, current authority,
proper lock/admission ordering, trusted CLI and secret-safe projections.

No new major premise is left for routine reconfirmation. Point 10a must still
record the complete source-grounded signatures, phases and version implications
before runtime code. Significant new discoveries retain the normal discussion
boundary; small implementation choices remain autonomous. Feature/0.8.0 is the
previously approved release projection and is unchanged; actual module/API bumps
are assessed honestly in their owning implementation points. No dependency,
reset, early candidate preparation or production operation is implied.

This task is planning/documentation only. No runtime/configuration/schema/data,
module manifest/version, test source or built asset changes. The existing point 11
remains open for renewed owner acceptance after 10a–10e, and point 12 retains final
complete affected-area automated verification/release. No broad verification is
scheduled in the new implementation points. Root/domain/customer instructions
need no additional edit for this planning record: the general isolation/public API,
pre-1.0 and reference-maintenance rules already apply; synchronize actual new
public contracts and changed consumers in their implementation points.

## Point 10a — Common settings framework completed — 2026-11-07

Implemented against planning baseline `21e5a865` on the existing roadmap branch.
The checkout was clean. Knowledge status was current (226 documents, 12,810 chunks);
a bounded current-intent roadmap query located the approved sequence, then the
original roadmap/authorization record and owning source were opened. Loaded the
backend, Laravel, security and PostgreSQL skills and relevant transaction/public
API/database/security guidance. Laravel Boost's installed Laravel 13 documentation
confirmed pessimistic locking and explicit transaction-attempt semantics.

### Contract and source decisions

Before runtime edits, authored the public
[native settings publication contract](../../../handbook/architecture/module-settings.md):
framework ownership/audience, complete extension and adapter signatures, source
placement, pure discovery, admission, actual registry/authority/actor lock order,
transaction boundary, operator authority and publication/recovery phases. Current
source mapping confirmed existing encrypted values/revisions in `shared.modules`,
transition/snapshot evidence, SettingsDefinitions validation, native application/
compensation, mandatory Audit and Auth-specific PolicyHistory participation.

- The single source-owned SettingsProvider can additionally implement public
  SettingsAccessProvider, SettingsAuthorization and SettingsParticipant. Access
  metadata declares operation grants, additional field read/write grants and an
  optional source-owned MutationProof. Runtime descriptor checks use the same
  SettingsDefinitions resolver, validating known keys without invoking mutation
  hooks. Physical declaration ownership and container substitution refusal remain.
- The native ModuleSettings runner invokes owning authorization before reservation,
  and owning synchronous persistence for publish, restore and reserved upgrade.
  Required framework participation remains mandatory and independent. Restoring
  a failed application does not consume the initiating password proof again.
- Private SettingsSecurity prepares exact operation/field/proof/Audit admission
  outside the transaction; under the registry lock it takes the existing authority
  lock and Auth-owned actor lock, rechecks source/current grants, validates the
  candidate and consumes proof in the publication transaction. It is an explicit
  additional named caller of reserved TransactionSecurity, whose actual Core/Auth
  implementation remains unchanged. No foreign private class/table access, new
  credential implementation, callback registry or alternative publication runner.
- Human actor history comes from trusted PrincipalScope. System/mismatched actor
  metadata cannot select authenticated authority. Principal-free console execution
  retains operator behavior; a route-bearing HTTP request without identity refuses.
  Jobs do not inherit a browser proof. Connection/PDO/principal/generation checks
  guard the transaction; declaration identity includes metadata and actual provider/
  handler file hashes. Source drift refuses publication or retains native recovery.
- readAuthorized supplies the existing redacted shape while omitting denied fields.
  Internal owning read/value helpers remain distinct. Initial publication proof,
  candidate, source history and mandatory Audit roll back together; later handler
  failure still uses native compensation/fencing. Final grant checks precede the
  fence; ordinary operational admission must refuse once the fence exists.
- Existing Audit settings events and Admin SettingsSnapshots remain the generic
  history/snapshot mechanisms. No migration, new table/store, data reset, secret
  rewrite, dependency, HTTP endpoint or frontend change was introduced.

### Versions, current guidance and staged consumers

Framework API advances 1.1.0 → 1.2.0 for additive source contracts/authorized
projection. Existing ^1.x consumers retain valid ranges; the generator already
reads the FrameworkApi constant and its expected-version regression is updated.
No Core module implementation/signature changed, so no independent module manifest
bump is justified here. Platform VERSION and projected feature/0.8.0 are unchanged.

The public source allowlist now exposes the four settings extension symbols while
TransactionSecurity/TrustedPrincipal retain exact named caller restrictions. Public
API/module/transaction/authoring guides, central unreleased changelog and both
backend skill products route to the same canonical settings guide. Root owner and
customer AGENTS need no change: their public API, module isolation, atomic Audit,
protected-source and roadmap rules already cover this extension. No broader policy
or customer platform-edit authority is added.

The approved dependency sequence deliberately places Auth's consumer migration/
old-dispatch removal in 10b and the executable reference backend/UI in 10c/10d.
Those remain open: the old Auth path is neither presented as the completed design
nor wrapped in a new compatibility adapter. Existing reference data/source/UI are
preserved, with no expanded interactive coverage claimed before their owning points.

### Focused evidence and limits

Only checks resolving new publication/access-boundary uncertainty ran:

- `python3 tools/maintainer/test-backend --parallel --processes=1
--filter='ModuleSettingsSecurityTest|runs source-owned authorization|rolls back source-owned history|refuses changed settings provider'`:
  **8 PASS, 62 assertions**, 1.63 seconds. Proves source hooks/restore, native source
  drift recovery, framework field access/actor/proof orchestration and atomic
  rollback of a transactional proof/history stand-in when mandatory Audit refuses.
- Additional focused native filters `refuses a changed default connection`:
  **1 PASS, 5 assertions**, 0.79 seconds; `rejects a stale reviewed revision`:
  **1 PASS, 6 assertions**, 0.89 seconds. Both refuse before proof consumption.
- `python3 tools/maintainer/test-python --scope=contracts
--path=contracts/test_api_boundary.py
--filter=php_aliases_reserved_callers_and_public_support_remain_distinct`:
  **1 PASS, 6 deselected**, 0.05 seconds. Public settings extension imports pass;
  application access to the reserved security bridge/private coordinator refuses.

The first native diagnostic failed because the test fixture assumed a nonexistent
`shared.cache`; corrected it to a connection-local PostgreSQL temporary evidence
table, then received the passing results above. No failure suppression or runtime
schema repair. Source-only Auth/Audit and transactional proof stand-ins isolate
framework guarantees; these are not certification of actual Auth credentials,
Permissions persistence or real Audit integration. Their migration/regressions and
complete affected suites remain in the approved later points/final verification.
No full suite, static analysis, scanner/image/installation or browser gate ran.

The supported runner printed scoped cleanup and removed its disposable
`runa_test_test_1` worker after each native run; no worker cleanup failure. Temporary
fixture sources/evidence tables are removed by fixture/connection lifecycle. No
new Docker container/network/image/volume was provisioned; active development,
base development/test databases, caches, backups and existing reports are preserved.
Mandatory hooks and staged-source incremental knowledge synchronization apply to
this implementation commit. Point 10b is the next authorized implementation point;
manual acceptance and release remain later gates.

## Point 10b — Auth common settings migration completed — 2026-11-07

Implemented from clean `3b0001ef` on the existing roadmap branch, following the
owner's instruction to execute the next point. Knowledge status was current
(227 documents, 12,847 chunks); a bounded current-intent settings query was checked
against the original common-settings contract, approved design, current point and
point-10a completion record. Loaded backend, Laravel, security and PostgreSQL
skills, with focused database/transaction/public-API/security guidance. Boost's
Laravel 13 docs confirmed pessimistic locking within the owning transaction.

### Owning source, authorization and unchanged domain behavior

Before runtime edits, updated the Auth policy guide with exact declaration/hook
ownership, current operations/proof, placement, publication phases and the existing
registry → common authority → Auth actor lock order. `IdentitySettings` now
implements SettingsAccessProvider and SettingsParticipant: policy read/write grants
and one-use `runa.auth.policy.publish` password proof are pure source metadata;
its publication hook invokes its own PolicyHistory. No owner authorization hook
is needed because common settings security already enforces these requirements.

Removed both Auth-specific dispatch branches and their installed-version/schema
probes from Core AuditParticipation, PolicyHistory's separate proof consumer and
the obsolete global-participant authorization invocation from ModuleSettings.
SettingsAuthorization remains the optional provider-owned business-rule extension;
mandatory Audit persistence remains independent. There is one native runner and
one proof consumption path, with no adapter or duplicate Auth route.

Auth retains policy definitions/cross-field rules, domain history, forced password
changes, exception invalidation and the baseline initializer. Native restore and
reserved upgrade invoke the same provider publication hook without consuming the
initiating proof again. Stored values/credentials and existing upgrade identities
are preserved. AuthPolicies 3.0.0 signatures/proxies and Admin's reviewed publisher
remain current. The Admin reader deliberately retains its owning guarded read
inside the admitted read transaction; it cannot nest the independent
readAuthorized transaction. Named snapshots and generic settings/Audit history
remain in their existing framework stores.

Private test fixture OperatorSettings now explicitly models principal-free console
setup between HTTP requests and restores the previous request in finally; it
refuses an authenticated principal. Auth entry/Admin configuration fixtures use it
rather than accidentally treating a leftover HTTP-kernel request as operator
execution. Runtime HTTP checks remain strict. Regression authorization stand-ins
isolate native admission/lock changes; the existing Admin case exercises actual
Permissions grants and actual Auth proof/Audit persistence.

### Versions, guidance and instruction-product assessment

Auth advances 5.0.1 → 5.0.2 as a correction with unchanged guarded public APIs and
data contracts, requiring Framework API ^1.2.0 for its actual source interfaces.
The `common_settings` 5.0.1 → 5.0.2 source-only update records the integration
without migrations/default rewrites; all earlier identities remain intact.
Permissions/Admin dependency ranges already accept this Auth PATCH, and no other
module behavior/version changes here. Framework remains 1.2.0, platform VERSION
0.7.0 and approved target feature/0.8.0 remain unchanged.

Updated Auth README, administration/policy guides, module changelog, central
unreleased changelog, current Admin/transaction version references and
module-foundations guidance, removing obsolete global
SettingsAuthorization/old Auth proof-consumption claims. Both instruction products
were assessed: owner/customer backend skills already route to the canonical native
settings and Auth policy guides; their root AGENTS already require public APIs,
module isolation and atomic security/Audit. No new reusable rule or instruction
change is needed. Reference's working source/UI remain unchanged; their consumer
implementation is explicitly sequenced into approved dependent points 10c/10d,
with no expanded interactive coverage claimed here.

### Focused diagnostics and limits

Only concrete migration/authority/fixture uncertainties were checked, through
`python3 tools/maintainer/test-backend --parallel --processes=1`:

- Filter `consumes policy publication proof only|refuses denied and stale native Auth|refuses changed Auth settings source|keeps settings history effects|Auth administration publishes through`:
  **6 PASS, 88 assertions**, 15.93 seconds. Covers one consumption/Audit event,
  replay refusal, policy/history/Audit rollback, consequences/operator restore,
  missing/lost grants, stale review, source substitution/connection change and
  actual Admin publication. No candidate or proof consumption survives a refused transaction.
- Filter `updates Auth common settings participation|upgrades encrypted identity settings additively|test_isolated_target_planner_finds_the_compatible_core_forward_edges|expires and revalidates only allowlisted account exceptions`:
  **4 PASS, 48 assertions**, 5.98 seconds. Covers unchanged encrypted policy
  bytes/revision/password/history/prior ledger rows on the new update edge,
  idempotence, existing reserved-default upgrade, target planner and unchanged
  account-exception invalidation.
- Filter `projects safe entry form options|keeps registration opt-in generic|Auth creation verification defaults on|Auth creation atomically configures credentials`:
  **4 PASS, 83 assertions**, 10.45 seconds. Confirms console setup between real
  requests without weakening publication and retaining ordinary entry/Admin flows.
- After fixture synchronization, repeated only the affected security consumers
  `Auth administration publishes through|refuses denied and stale native Auth|consumes policy publication proof only`:
  **3 PASS, 58 assertions**, 9.37 seconds.

No broad backend/security suite, static analysis, scanner/image, installation or
browser gate ran; these remain final point 12, following reference completion and
renewed owner manual acceptance. Existing native recovery is retained, not newly
certified for every failure mode by these samples. Mandatory hooks and incremental
staged-source knowledge synchronization apply to this point's commit.

Each supported native run reported scoped cleanup of its disposable
`runa_test_test_1` worker with exit/cleanup 0. Fixture-created alternate connections
were purged and source substitutions restored. No task-created Docker container,
network, image tag or volume required cleanup. Active development, base
runa/runa_test data, credentials, existing reports, caches and backups are preserved.
No migration, data reset, dependency or production change. Point 10c is next;
manual acceptance and final release remain open.

## Point 10c — Native generation/admission design stop — 2026-11-07

Read the active point, approved settings design, current source and relevant
Backend/Laravel/Security/PostgreSQL skills. Local knowledge status was current:
227 documents, 12866 chunks, no stale/missing/orphan chunks. Bounded current-intent
retrieval was followed by original public settings, transactions, snapshots,
Audit, Permissions, source-boundary and reference maintenance guidance.
Laravel Boost confirmed Laravel 13.37.0/PHP 8.4 and supplied version-aware
validation guidance.

The [decision record](../../reviews/RM-2026-017-reference-generation-admission.md)
contains the source-verified failure, proposed owning correction and bounded
native evidence. Implementation stops for the significant generation/admission
premise; point 10c remains unchecked. The shared snapshot adapter trial was
withdrawn to avoid introducing an unsupported nested admission path.
No changes to public framework signatures or platform runtime configuration are
approved by this checkpoint.

Candidate application source uses native ModuleTransactions, SettingsAccess,
Audit automatic/manual capture and SettingsSnapshots. Its monotonic sample
revision migration is forward-only because this roadmap explicitly preserves
existing data; it has not been applied to development storage. The source-only
0.3.0 to 0.4.0 upgrade edge preserves existing sample UUIDs/settings.
The candidate declares optional Core dependencies for its new guarded consumers,
retaining public Ping/CLI/source behavior without Admin. Existing published
upgrade identities remain untouched.

Owner/customer instruction products need no new reusable rule at this checkpoint:
the existing public API, native settings, reference maintenance and significant
unknown rules already require this stop. Public current-state completion claims
and candidate changelog/documentation must be synchronized with the eventual
chosen implementation before point completion. Changes remain uncommitted and
unfinished, without release/verification certification.

## Owner scope consolidation and native correction approval — 2026-11-07

After the generation/admission explanation, the owner asked whether correcting
it would be discarded by RM-2026-018. Source review confirmed distinct mechanisms:
018 removes passive frontend data-refresh triggers/cascades, whereas required
backend health, authority, expected-version/review and integrity checks remain.
Settings and snapshot failures occur during explicit actions without passive reads.
The owner then approved the proposed correction and explicitly requested combining
018 with this active roadmap, followed by explicit deletion of the redundant draft.

The complete agreed specification and exclusions were transferred above before
removing the draft. Points 10f–10h absorb its three implementation outcomes;
10i consolidates runtime readiness. Existing 10c owns the native settings repair,
10e retains reference coverage, 11 stays penultimate owner acceptance and 12 stays
final verification/release. Earlier dated deferrals to RM-2026-018 remain historical
records, superseded by this decision. The separate 0.8.1 projection is retired;
accepted feature/0.8.0 and the current roadmap branch remain unchanged. No second
activation, release, VERSION change, reset or dependency is introduced.

This is a planning/approval commit only, preserving the unfinished 10c source and
tests outside its staged changes. The former design stop is resolved by the owner;
implementation and failing/unexecuted coverage remain open. Record full native
signatures/lock phases before the next runtime edits. Scope checks found no broad
verification before implementation completion: manual acceptance is 11 and all
complete automated suites/gates remain 12.

Instruction-product assessment: this scope consolidation changes no standing
constitution. Owner/customer guidance changes for action-driven refresh belong
in 10h with the implementation; do not prematurely remove current contracts.
Current roadmap ownership, preservation of history, native security and public
source boundaries already cover the approved native correction. Local knowledge
was stale from the unfinished active-roadmap edits; used focused original sources
and refresh the staged corpus incrementally at this commit boundary.

## Point 10c — Native review/admission correction and reference backend completed — 2026-11-07

Continued the existing application-owned candidate work at `8190d43b`; no initial
work was discarded. Knowledge status was current (227 documents, 12,885 chunks)
and bounded current-intent retrieval preceded focused source review. Loaded
backend/Laravel/security/PostgreSQL guidance and opened the canonical settings,
database, security, public API, reference and testing/command procedures. Installed
Laravel 13 Boost documentation confirmed explicit transaction and pessimistic-lock
semantics. No external dependency or additional roadmap was introduced.

### Native contract and exact scope

The [approved correction record](../../reviews/RM-2026-017-reference-generation-admission.md#concrete-native-contract-and-lock-design--2026-11-07)
records full signatures, ownership, semantic identity and lock phases before runtime
edits. Settings read/readAuthorized now add `review_id`; publication and capture
compare the original revision and identity. Registry/source/install/enabled/fences,
provider/handler hashes/access/definitions and normalized health state participate;
equivalent successful observation renewal excludes timestamps. Real result/source/
revision or fresh/expired changes invalidate. Operational generation still advances
and protects synchronous native authority; its publication value is captured after
required independent checks, and transition history records the locked generation.
No authorization cache, guessed generation, automatic write replay or extra runner.

SnapshotSection prepares standalone owner read/write, Audit and delete-specific
Auth operations before locks, or joins the exact existing mutation/Admin prepared
scope. Missing/expired/unplanned evidence still refuses. Nested result reads retain
the existing scope. Read/write grants, proof, principal/connection/level and final
availability/expiry fences remain. ReferenceTools snapshot operations use the same
source-owned `settings.snapshots` operation key; prepared API frames therefore admit
exact adapter calls without a blanket root scope. One store and transaction remain.

This changes public settings arguments incompatibly. Honest assessment is Framework
API **2.0.0**, AuthPolicies **4.0.0**, Auth **6.0.0** under standing pre-1.0 owner
authorization; no compatibility facade is retained. Audit **1.1.1** and Permissions
**2.1.1** update framework/Auth compatibility without changing their public APIs;
Admin retains this roadmap's existing **1.0.1** compatible-correction candidate.
All supplied manifests, current Auth consumers, CLI (`--review`), generator fixture
expectations and settings tests are updated. Source-only module forward edges
retain existing storage/history; the generator already derives FrameworkApi::VERSION.
Platform/application VERSION and published history are unchanged.

### Reference delivered backend and documentation

ReferenceTools **1.0.0** / reference **0.4.0** adds named authenticated FormRequest/
Resource HTTP, Actions/Query/Policy, independent field grants, revision-checked
Sample editing with opt-in safe code Audit, redacted encrypted settings review,
one-use own publication proof, committed-label snapshots, independent AuditReads
authority and real managed-work admission/status. Dispatch prepares work before
locks and writes explicit Audit atomically. Existing Ping, CLI/schedule/work/events,
optional integration and production exclusion remain. No UI placeholder is presented
as the new journey. The nondestructive, owner-prefixed migration adds positive
Sample revision to retained rows; explicit runtime update is deferred to 10i.

Updated the application reference source/signature/endpoint/grant/capability guide,
central/module changelogs, native settings/framework/transaction/snapshot/CLI docs,
Auth policy/account/Admin compatibility guides, seeding and module-development
usage. Owner and customer backend skills route consumers to the semantic review
contract. Root owner/customer AGENTS require no change: existing public boundary,
proof/Audit, pre-1.0 versioning and native workflow rules already govern this work;
no new constitutional or protected-edit permission is introduced. Presentation
catalogues remain unchanged because frontend implementation belongs to 10d.

### Focused diagnostic evidence and remaining verification

Only bounded checks resolving the demonstrated generation/admission/atomicity
uncertainties ran; no complete suite, analysis, browser matrix, installation
acceptance, image/scanner pipeline or final quality gate ran.

- Application entrypoint `runa:test:backend ../tests/backend/app/modules/reference/CoreExamplesTest.php --parallel --processes=1`: settings review/secret/proof, committed snapshot and failed-Audit publication rollback selections passed, **3 cases / 53 assertions**. Health evidence is deliberately renewed after advancing time; original review remains usable. Rollback preserves settings/revision and unconsumed proof; a deliberate later successful publication invalidates the old review.
- Owner `python3 tools/maintainer/test-backend --parallel --processes=1 --filter=...`: semantic renewal/result/expiry/source diagnostic passed, **1 case / 9 assertions**; real hybrid snapshot prepared-read/absent/expired/revoked-admission diagnostic passed, **1 case / 8 assertions**.
- Earlier focused owner selection for exact nested/repeated prepared calls, extra-call refusal and Auth named snapshot/Audit/proof behavior passed, **3 cases / 53 assertions**. Subsequent compatibility metadata changes still require the complete affected-area checks at 12; these are bounded diagnostic observations, not full current-source certification.
- Preserved intermediate native failures: initial worker preparation found a missing Auth forward edge (fixed); invoking a private owner fixture through the application runner failed its fixture lookup (used the documented owner runner); a rollback assertion initially included another case's historical proof (scoped to the current actor); explicit app installation found the inherited migration filename lacked its owner prefix (renamed before delivery). These are not PASS or full-verification repairs. The original design-stop failures remain in their record.
- Existing sample edit/work/proof/revocation/rollback diagnostics from the earlier candidate remain bounded evidence; application tests author those cases plus field omission and independent Audit read denial. Complete backend/source/runtime/version/CLI and frontend verification remains 12; runtime alignment remains 10i.

Supported runners listed and removed only their disposable `runa_test_test_1`
workers after execution. No task Docker container/network/image/volume was created
or removed. Base test/development data, existing services, Qdrant/model/pnpm caches,
backups, reports and recovery state were preserved. No development reset, implicit
migration, candidate platform-version preparation, deployment or owner acceptance
occurred. Point 10d is next; this point does not authorize skipping its UI work.

## Point 10d completion — reference customer presentation, 2026-11-07

### Delivered source and composition

Added `/admin/app.reference/samples`, `/settings`, `/history` and `/work` through
native module page/section declarations; the shared Admin top bar owns navigation.
Their custom page uses projected section/field grants, real guarded ReferenceTools
HTTP and public Admin/Auth/UI facades. Ping inspect/widget remain independent.
Native fallback readers declare exact ReferenceTools calls and bounded cursor
reads; they do not introduce a second business API or write path.

Application-owned `workspace.vue` and `useReferenceWorkspace` provide the same
samples/settings/snapshot/history/work presentation for a standalone shell and
its typed default-slot replacement. The standalone caller supplies its existing
admitted identity/capability projection and connects the exposed exit check to its
router. Neither component starts another session. The registry Ping panel remains
public/nonsensitive rather than inventing a protected identity for static mounting.

Usable defaults compose Card/Form/FormNotifications, source-projected PL/EN setting
names/descriptions, TextInput/RadioGroup, Table, ContentLoading, FormConflict and
ConfirmDialog. Semantic tokens/shared controls retain responsive light/dark and
keyboard/focus contracts. Code/credential presentation follows independent field
grants; readonly consumers have no write controls. Secret keep/replace/clear remains
local and is redacted in review/history/snapshots. A single view lane retains
content and dirty drafts, serializes settings/snapshot intents and fences late
responses on authority withdrawal/disposal. Actual stale/missing reasons reconcile
without write replay; business conflicts retain their own localized outcome.
Snapshots capture committed labels and load only into drafts. Protected publication,
snapshot deletion and work dispatch use distinct native Auth full-page password
actions. Cancellation sends no business write; work admission does not imply
completion. The controller checks native settings `succeeded` separately from
snapshot `committed` and work `admitted` outcomes.

The owning native composition gap was a small public frontend extension: Admin
`useAdminPageContext(page, {beforeLeave?, dirty?, settled?})` exposes admitted
identity and joins existing readiness/exit ownership, preserving scope cleanup and
fencing late navigation consent. It adds no session/cache/runner or retry machinery.
Its full public signature, audience, implementation and delivery are recorded in
the Admin frontend guide before consumption. Admin is honestly assessed as compatible
MINOR **1.1.0** with a source-only forward edge, preserving AdminAccess 1.0.0 and
storage. Reference remains **0.4.0**, completing the same unreleased feature;
ReferenceTools 1.0.0, Framework API 2.0.0 and other Core APIs are unchanged here.
The reference declares optional Admin ^1.1.0 for its actual new frontend consumer.
Platform VERSION remains 0.7.0; no installation or early release preparation occurred.

### Tests and current guidance

Authored application regressions cover sample conflict/explicit resolution, removed
subjects, identity withdrawal/late reads, exact reviewed publication/native proof,
cancelled protected work and actual admission/status, snapshot draft loading/review
invalidation, business-conflict isolation, readonly/omitted field rendering and a
real custom-slot consumer with disposal. Added a native Admin lifecycle/navigation
consumer regression and an application backend scenario for independent Admin
read/write/field projections and the guarded native settings fallback.

Synchronized reference source/reproduction/composition guidance, Admin public
frontend/snapshot/examples/version guides, module and central changelogs, owner
frontend layout/forms references and customer frontend application reference.
Root owner/customer AGENTS and other domain skills require no change: existing
public-boundary, single-session/proof, grant, roadmap and test ownership rules
already apply. No new customer permission to edit protected source is introduced.
The full capability matrix/acceptance-sheet reconciliation remains the explicit
next point 10e; immutable historical reports are untouched.

### Bounded diagnostics and remaining acceptance

Local knowledge status was current before research; relevant sources were opened.
Only focused diagnostics resolving concrete implementation uncertainties ran:

- `php src/artisan runa:modules:check app`: no source errors for app.reference.
- Application `runa:test:frontend ../tests/frontend/app/modules/reference/workspace.test.ts -t 'retains a conflicting sample|fences late reads|publishes the exact'`:
  **3 selected cases passed**, 4 then-authored cases excluded, native exit/cleanup 0.
- Owner `python3 tools/maintainer/test-frontend tests/frontend/runa/unit/admin-shell.test.ts -t 'custom Admin pages share admitted identity'`:
  **1 selected case passed**, 48 excluded, native exit/cleanup 0.
- After correcting explicit native result-status handling, application selection
  `-t 'publishes the exact|renders readonly settings'`: **2 selected cases passed**,
  8 excluded, native exit/cleanup 0. These selections precede the final metadata/
  readiness/source refinements; they are bounded observations, not current-source
  complete certification. The full owning checks and all authored cases remain 12.

The runners emitted Vite's dependency dynamic-import analysis warning from generated
Vitest runtime code; it is preserved in native output, not suppressed or called a
warning-free full result. No complete frontend/backend suite, static analysis,
browser matrix, build/image/scanner pipeline or final gate ran. No owner manual
acceptance is claimed. Combined working-runtime/assets preparation remains 10i,
sole owner acceptance 11 and sole final verification/release 12.

No task-created Docker resources or worker databases were needed for these focused
frontend runs; none were removed. Existing development/test data, services, caches,
backups, reports and recovery state were preserved. Mandatory formatting/lint,
staged knowledge freshness and commit hooks apply to this point's commit.

## Point 10e completion — Core capability and guidance reconciliation, 2026-11-07

### Source-grounded coverage and dispositions

Compared every current Core manifest/API declaration (Admin 1.1.0, Auth 6.0.0,
Audit 1.1.1, Permissions 2.1.1) to ReferenceManagement/Permissions/Settings,
ReferenceToolsHandler, EditSample, DispatchWork, SamplePolicy/Model, snapshot
adapter, workspace controller/presentation and application test bodies. Knowledge
status was current (227 documents, no stale/missing/orphan chunks); current-intent
retrieval led to original reference/Admin/maintenance sources. Boost confirmed
Laravel 13.37.0 and its version-aware HTTP assertion documentation.

The application-owned `core-capabilities.md` now records public API versus module
versions, actual declaration/Action/Query sources, UI/CLI reproduction, expected
success/refusal behavior and named application test ownership. All four Core
modules have real owning examples: Admin composition, Permissions independent
read/write/field grants, automatic/explicit Audit and authorized history, Auth
shared action-specific proof. Framework settings/snapshots/work and existing
Ping/lifecycle/health/events/storage examples remain distinct from Core modules.
The matrix explicitly disposes remaining public API families through native Core
views or domain exclusions; it does not claim every foreign account operation
belongs in the sample. Private registry/security/recovery mechanisms, genuine
external integrations and foreign-subject aggregate edits are not invented APIs.

Removed the obsolete pending-settings-editor claim and incomplete Admin coverage
summary, updated Admin reference guidance and the reference changelog, and linked
the detailed matrix from the central application guide. The new current acceptance
sheet lists all reference routes, grants, safe concrete operations, standalone
composition and current source candidates. It explicitly withholds runtime/build
readiness until 10i and owner acceptance until 11. Dated point-10 report, rejected
designs and original failed evidence retain their bytes. Copy/device/refresh
expectations remain pending their owning points, not relabeled complete here.

### Meaningful test gaps and bounded evidence

Added two application-owned backend scenarios: invalid sample input emits no
history, a committed audited edit is visible through authorized sample history
without reprojecting code, and withdrawing sample read refuses that filtered
history; settings publication grant alone refuses credential replacement/clear,
and invalid label review leaves committed revision/value unchanged. No runtime
implementation, dependency, schema or API contract changed.

The focused diagnostic resolved whether these new cases actually reach the
owning workflows and preserve the documented projection/authority boundaries:

- Initial supported application backend selection without parallel preparation:
  **2 failed**, login fixture HTTP 503 before either scenario, native exit 1,
  cleanup 0. This base-mode fixture refused login before reaching the new assertions;
  no guard was bypassed and no base/development update was performed to hide it.
- `php artisan runa:test:backend ../tests/backend/app/modules/reference/CoreExamplesTest.php --parallel --processes=1 --filter='shows an audited sample change|refuses credential replacement'`:
  **2 passed / 24 assertions**, native exit 0, cleanup 0, 4.77 seconds. The supported
  worker preparation supplied isolated current Core. This is a two-case diagnostic,
  not complete backend/reference coverage or final-verification evidence.
- `git diff --check` passes; staged formatting/lint and knowledge freshness remain
  mandatory commit-hook checks. No complete suite, static analysis, browser matrix,
  asset/image build, scanner or final quality gate ran.

Supported runner scoped cleanup listed/removed only `runa_test_test_1`. No task
Docker containers, networks, tags or volumes were created or deleted. Base test
and development databases/data, active services, Qdrant/model/pnpm caches,
backups, reports and recovery state were preserved. Native diagnostics remain in
supported runner evidence; initial failure is retained above rather than called PASS.

### Instruction products and versions

Owner reference-maintenance guide now locates the detailed map; the customer
backend selector routes to it through the selected application guide, retaining
protected-source restrictions and ordinary App ownership. Root owner/customer
AGENTS and remaining skills already cover public boundaries, Audit, native proof,
selective activation, test ownership and final-only broad checks; no new reusable
rule or duplicated procedure is needed. Customer templates were read as product
data only, never owner authority. No module/API/Framework increment is warranted
for documentation and application tests completing this unreleased reference
0.4.0 scope; current manifest ranges and platform projection remain unchanged.

All 10e dependencies are committed. Combined runtime preparation remains 10i;
point 10f is next, with 11/12 still the sole acceptance/final gates.

## Point 10f completion — supplied interface copy, 2026-11-07

The [source-grounded copy review](../../reviews/RM-2026-017-interface-copy.md)
records view-family inventory, actual locations, PL/EN text, purpose and
removal/retention rationale, including shared feedback and native error/mail
presentation. Removed both loginHelp entries, the repeated registration task
sentence and the root promotional assertion. Existing optional-description
resolution already omits the login paragraph; public controller/replacement
signatures remain unchanged. Preserve activation, reset/session consequences,
recovery/setup instructions, field constraints, settings/snapshot semantics,
work admission/completion distinction and real refusal/outcome copy.

Updated the public Auth entry guide and module/central changelogs. Auth stays the
unreleased 6.0.0 candidate; no additional API/module/platform version is justified
for compatible copy correction within that release. No dependency, schema,
permission, runtime configuration or persistence change. Owner/customer AGENTS
and frontend skills already select the canonical copy contract and retain public
presentation/security boundaries; neither needs a duplicated rule. Customer
instruction templates were assessed only as product data.

Knowledge was current before research; original guidance was opened. Supported
focused Chromium diagnostics first failed because the new test host required
unsupported runtime template compilation. Correcting that host to a render
function yielded **2 passed / 17 excluded**, native exit 0, cleanup 0. The cases
render current PL/EN login/recovery components, preserve required validation with
no write and verify that necessary recovery guidance remains. Native Vite generated
dependency analysis warning is retained. No complete suite or final gate ran.

Bounded real Nginx observations at root/login/register/recovery/unknown paths
returned HTTP 503; screenshots/evidence retain the actual maintenance shell and
failed username wait. No current SPA, authenticated view or new built-copy
acceptance is claimed. Runtime/assets alignment and real-page review remain 10i,
owner acceptance 11 and complete automated verification/release 12. No bypass,
reset, installation or operational repair was attempted in this copy point.
No task-created Docker resources/worker databases were needed or removed;
existing development/test data, active services, caches, backups, reports and
recovery state were preserved. Point 10g is next.

## Point 10g completion — trusted-device disclosure, 2026-11-07

### Delivered behavior and source boundaries

AuthAccountSecurity composes public Accordion around the existing Table and Load
older control. Expansion is view-local and initially false; the mounted Table
retains its query, column/view state, export owner and cursor/revocation behavior.
Ordinary reads do not alter expansion. Trust-browser action and factor prerequisite
remain outside the collapsed region. Disabled capability removes the section and
resets expansion/removal state. Existing account-keyed disposal clears protected
rows/cursor/loading availability; no hidden-content cache or second security/session
owner was added. Native button Enter/Space activation, aria-expanded/controls,
inert hidden content and reduced-motion collapse come from the existing public
Accordion. Semantic component tokens and unchanged table overflow preserve the
responsive/theme implementation; complete rendered acceptance remains 11/12.

Knowledge status was current (230 documents, no stale/missing/orphan chunks);
current-intent retrieval led to original account/security and component contracts.
Inspected public Accordion/Collapse, native account/list disposal and existing
consumers/tests. Tailwind remains 4.3.3 with existing spacing/tokens only.
Updated the owning public account presentation guide and Auth/central changelogs.
This compatible UI correction completes the same unreleased Auth 6.0.0 candidate;
no further module/API increment, dependency, migration or runtime change is needed.
Framework/Core candidates, reference 0.4.0, platform 0.7.0 and approved feature/0.8.0
projection remain unchanged. Public controller and presentation signatures remain.

Owner/customer AGENTS and frontend/Vue/Tailwind/security selectors already route
to the canonical public control, account replacement and capability/data-clearing
contracts; no new reusable operating rule or skill reference is needed. Customer
frontend instructions were assessed as product data, without authority to edit
protected platform source. Current prose replaces the obsolete always-inline-table
claim; earlier historical evidence is preserved.

### Bounded diagnostic evidence

The supported owner test-frontend command targeted auth-account.test.ts only:

- Initial `-t 'trusted disclosure'`: two fixture/assertion failures, native exit 1,
  cleanup 0. Browser structuredClone rejected a reactive query proxy; the fixture's
  standalone refresh did not simulate session withdrawal. Corrected query snapshot
  serialization and exercised the real account-key switch/disposal boundary.
- `-t 'trusted disclosure|trusted devices live|trusted management keeps|device metadata'`:
  four passed, 78 excluded, native exit 0 / cleanup 0. The final alternative matched
  no additional case; this is not device-metadata coverage.
- After formatting and making the existing revocation scenario explicitly open
  disclosure, `-t 'trusted disclosure|trusted device management keeps isolated'`:
  three passed, 79 excluded, native exit 0 / cleanup 0. Covers initial inert/ARIA
  linkage, mounted query retention through toggles/hidden refresh, visible external
  action/prerequisite, capability removal/reset, hidden-data disposal on switch,
  and existing cursor/proof/revocation behavior.

Native Vite generated dependency dynamic-import warning remains in runner output.
No full suite, static analysis, browser matrix, asset/image build, scanner or final
gate ran. Native test reports/screenshots retain the initial failures. Runtime/assets
alignment and real Nginx readiness remain 10i, sole owner manual acceptance 11 and
sole complete automated verification/release 12. No acceptance or final PASS is
claimed. Mandatory staged formatting/lint, knowledge freshness and commit hooks
apply to this completion commit.

No task-created Docker containers/networks/images/volumes or worker databases were
needed or removed. Existing development/test data, active services, Qdrant/model/
pnpm caches, backups, reports and recovery state were preserved. Point 10h is next.

## Point 10h completion — action-driven refresh, 2026-11-07

The [owning review](../../reviews/RM-2026-017-action-driven-refresh.md) records
actual source owners, backend lock/transaction/uniqueness/reference/bulk safeguards,
trigger removal, original-version corrections, current documentation/instruction
products and all diagnostic failures and limits. Auth account conflict recovery
reads only profile or preference/delivery data as applicable; Admin resource refusal
refresh is local, independent writes refresh their section and aggregate conflicts
reconcile participating form data. Actual security/access loss remains immediate.
No timer/cache/coordinator, dependency, migration/reset or production change.

Final bounded evidence after formatting: owner selection across five files,
25 passed / 190 excluded, native exit 0 / cleanup 0; after narrowing account
conflict reads, Auth account selection 14 passed / 68 excluded, exit 0 / cleanup 0.
Public presentation actual-identity versus unchanged-session diagnostic: 1 passed /
4 excluded, exit 0 / cleanup 0. Application two-view simulated-transport case:
1 passed / 11 excluded, exit 0 / cleanup 0. These are scoped diagnostics only.
The new native two-user/session HTTP case is authored but its attempted run failed
at fixture login HTTP 503 (exit 1 / cleanup 0, no case coverage). Combined readiness
10i must repair runtime/test prerequisites and rerun this focused case; final native
backend/complete automated coverage remains 12. No broad gate or acceptance ran.

Forms/loading/API, Auth account/preferences and Admin frontend guides replace
obsolete passive-refresh rules. Both owner/customer AGENTS and frontend reference
products route to the canonical action-driven procedure; security/Vue/backend
selectors need no duplicated policy. Auth 6.0.0 and Admin 1.1.0 remain the existing
unreleased candidates with unchanged public signatures; no extra version/forward
edge is introduced. Framework API 2.0.0, AuthPolicies 4.0.0, Audit 1.1.1,
Permissions 2.1.1, reference 0.4.0, platform 0.7.0 and target 0.8.0 are unchanged.

No task-created Docker containers/networks/images/volumes were required or removed.
Native scoped worker cleanup reported []; existing development/test data, active
services, Qdrant/model/pnpm caches, backups, reports and recovery state are preserved.
Mandatory staged formatting/lint, source freshness and commit hooks apply.
Point 10i is next; 11/12 stay open.

## Point 10h1 completion — readiness build repairs, 2026-11-07

The first supported manual-runtime asset build in 10i failed before asset output:
reference workspace snapshot deletion used nonexistent ButtonIntent destroy twice;
the generic Table wrapper in the trusted-device query-retention test inferred no
named props. The inserted repair uses the existing public danger intent for both
delete controls and the established typed TableQuery props projection in tests.
No new interface, behavior premise, dependency, version edge or migration.

Repeated `pnpm --dir src run build` passes, including TypeScript, Vite and native
static error shells. This is required manual-runtime asset preparation, not a
final frontend gate or complete test suite. Existing chunk-size and ineffective
dynamic-import warnings remain unsuppressed. The development-mode reference
registry build and actual runtime readiness still belong to 10i.

The public components guide's action-button semantics already specify danger for
stored-data deletion. The reference guide and both owner/customer instruction
products already route to that contract; no reusable guidance change is needed.
Frontend/Vue selectors apply; no CSS/Tailwind contract change is introduced.
Point 10i remains open, followed by owner acceptance 11 and final verification 12.

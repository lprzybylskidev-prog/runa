---
id: RM-2026-018
title: Agent workflow enforcement and recoverable quality evidence
status: active
branch: roadmap/RM-2026-018-agent-workflow-and-quality-evidence
created_at: 2026-11-08
activated_at: 2026-11-08
closed_at: null
release_type: fix
proposed_version: 0.8.1
quality_groups: docs,tooling,knowledge,delivery
quality_rationale: 'Agent guidance, quality planning/execution and closure evidence require docs/tooling; changed indexed guidance requires strict knowledge checks; shared quality tooling and customer instruction assessment require delivery source contracts. No application behavior, database, frontend, image or native installation/update change currently justifies their broad suites. Reassess the exact graph against the final changes before execution.'
impact_areas:
  - tooling
  - documentation
  - ai_environment
---

# RM-2026-018 — Agent workflow enforcement and recoverable quality evidence

## Overview

### What

Correct existing agent instructions, final-verification ordering and closure
evidence validation so that documented constraints govern execution before tools
are invoked. Support complete, candidate-bound evidence obtained through the
existing manual SIGSEGV recovery procedure without rewriting failed executions.

### Why

RM-2026-017 exposed premature full backend execution, misleading acceptance
progress, repeated expensive work and a mismatch between manual evidence
continuity and the validator's single successful graph requirement. Its historical
owner-approved representation exception remains immutable and grants no future
waiver. This roadmap corrects the current instructions and owning tools.

### Scope

- Established decisions, pre-execution prerequisites, terminal recovery conditions
  and accurate progress reporting in the existing instruction products.
- Final complete backend execution after every other required validation stage.
- Existing quality planner, executor, evidence model and closure validator, with
  focused automated regression scenarios.
- Canonical owner verification/recovery/handoff documentation, relevant skills and
  assessment of separately authored customer instructions and delivered tooling.
- Proportionate final verification and platform release 0.8.1.

### Out of scope

- Reopening or editing closed roadmaps, historical results or published tags.
- Application functionality, frontend presentation, database changes and new
  dependencies; unrelated full backend/browser/installation/update acceptance.
- PHP SIGSEGV diagnosis, runtime/dependency changes, alternative runners, automatic
  retries, skips, recovery orchestration or new acceptance exceptions.
- A second validator, independent tracking system, duplicate checklist, agent
  simulation framework or unrestricted manual override.

## Specification

### Authority and execution boundaries

Use the existing [roadmap workflow](../../workflows/roadmaps.md),
[quality contract](../../quality/README.md),
[evidence continuity](../../testing/strategy.md#verification-evidence-continuity)
and [temporary SIGSEGV procedure](../../testing/strategy.md#backend-sigsegv-owner-acceptance).
Replace conflicting current rules in their canonical homes; keep always-loaded
summaries short and assess both instruction products under the
[guidance policy](../../../handbook/ai/skills-policy.md#editing-reusable-guidance).

Before a governed operation, identify its applicable constraints, stage,
prerequisites and actual evidence, validity/cost of existing results, remaining
work and handoff boundary. Satisfy prerequisites before invoking the operation.
Do not turn established prohibitions into routine permission requests. Reopen an
explicit decision only for materially new evidence making it inapplicable,
contradictory or unsafe; identify the decision, evidence and decision needed.
Recurrence of the known fault is not new evidence. Ordinary new defects and small
implementation choices remain open to normal investigation.

### Final complete backend suite runs last

The full backend suite is the final test suite of the entire selected acceptance
scope. Before starting it, every applicable non-backend test, analysis, lint,
tooling/workflow test, browser check, packaging/build, installation/update/client
acceptance, database check, correction and relevant regression must have valid
successful evidence. No known defect requiring application changes or planned
test suite may remain. Apply this across supported owner execution modes and
manual continuation, not just a dependency on Playwright.

Correct graph dependencies rather than adding a cycle or weakening checks. In
particular, reassess the current `database-migrations -> backend-tests` dependency
and final test-bearing nodes that currently follow the main graph. Keep all real
prerequisites, selected checks and hard failure semantics. Failed, blocked or
missing predecessors must prevent final backend launch. The graph preview must
expose the actual order, and regression tests must exercise execution blocking.

Short focused backend diagnostics remain permitted under the implementation
workflow. After a genuinely new post-backend defect, assess invalidation, repair,
complete affected non-backend checks first, and repeat the full backend last only
if its evidence was invalidated. Reconcile the existing complete affected-area
repair rule with this order in its canonical guides; preserve mandatory repair
coverage and receive required results before completing a repair point.

After backend completion, only collection/reporting, non-test release bookkeeping
and closure operations that introduce no unverified behavior may remain. Account
for test-bearing release checks and hooks before the final backend boundary;
retain mandatory focused metadata validation and post-merge sanity under the
release workflow without hiding another planned acceptance suite after backend.

### SIGSEGV recovery and evidence semantics

Preserve original native logs, process results, exit codes, modes/worker counts,
candidate/environment identity and cleanup results. Confirm actual PHP SIGSEGV
from reliable diagnostics; an arbitrary parent exit 1 or text containing 139 is
insufficient. Identify the exact test/dataset reliably, never from the last
progress indicator alone. Preserve the existing maximum of two additional manual
attempts across sessions and supported selection/mode rules.

An authorized successful retry is terminal for that exact selection: record
recovered PASS, stop retrying, preserve the failed original and continue remaining
mandatory coverage. Do not investigate, classify it as unresolved or request owner
approval for the recovered case. Stop the SIGSEGV retry path on an ordinary
failure. Exhausted confirmed interruptions require the existing explicit owner
disposition; unknown coverage, ordinary failures and unsafe cleanup still block.
Do not invent new tolerances or automatically accept exhausted cases.

Distinguish process execution status, test/dataset verification, aggregate gate
acceptance and explicit owner exceptions. An original FAILED execution remains
FAILED after recovery. A recovered case alone does not complete the suite.

Extend the existing evidence model and validator to admit both a complete native
successful graph and a complete valid evidence set after recognized SIGSEGV,
manual authorized recovery and remaining checks. Both paths enforce the same
mandatory coverage, prerequisites, candidate compatibility and failure rules.
Recovery validation checks evidence; it never schedules or retries tests.

Validate native provenance/artifact relationships, exact case/dataset coverage,
attempt ordering/budget, actual modes, cleanup and graph/command/scope identity.
Reject missing, malformed, incompatible, stale, failed, blocked or invalidated
evidence, assertion failures presented as SIGSEGV and duplicate evidence used to
inflate coverage. Reject inconsistent exception classification of recovered PASS.
Do not accept unsupported agent claims or mutate historical coordinator state.
Use retained native artifacts and existing roadmap/review evidence; add only the
necessary fields/links to existing infrastructure, with no separate attempt ledger
or persistence engine. Specify the exact supported evidence input and its trust
boundary in the owning guide before implementing it.

### Progress and long-running handoff

Report against the complete roadmap and selected acceptance scope. At meaningful
milestones state verified work, remaining mandatory gates, blockers and next
operation. Distinguish pending/running/passed/failed/blocked/invalidated results,
recovered tests and excluded checks. Keep implementation completion, preliminary
validation, final acceptance, release and formal closure separate. No unsupported
completion percentages, timing promises or claim that only closure remains while
technical gates are pending.

For operations governed by the existing background quality handoff, successful
launch is the turn's terminal condition. Perform only minimal launch confirmation,
then provide the exact supported status command, logs/reports, success/exit-status
interpretation and remaining work, and end the turn. No polling, waits, partial
output inspection, edits or subsequent agent work; resume only on new user input.
The supported coordinator may execute its agreed graph; do not introduce detached
command chains to evade handoff. Short synchronous permitted diagnostics retain
their existing behavior; duration alone does not create a new handoff workflow.

### Decisions

- 2026-11-08 — The owner explicitly approved extending the existing test result
  producer with revision, command, mode and native-report bindings. Use the
  bounded contract below; no new runner, automatic retries or SIGSEGV diagnosis.

- 2026-11-08 — The owner replaced the attachment's prohibition on a new roadmap
  with an explicit instruction to create one and release 0.8.1 after completion.
- 2026-11-08 — The owner explicitly confirmed `release_type: fix` and
  `proposed_version: 0.8.1`: a PATCH correction after platform 0.8.0. VERSION stays
  unchanged during planning; drafting is not activation authorization.
- 2026-11-08 — The supplied task requires the full backend suite to run after
  all other selected testing, successful authorized recovery to terminate handling
  without owner approval, truthful aggregate reporting and mandatory background
  handoff. Preserve existing coverage, security and historical evidence constraints.

- 2026-11-08 — The owner authorized activation and continuous execution of this roadmap, retaining the mandatory final background handoff.

## Current execution state

Activation is committed as `13e3c715`. Point 1 is complete under the approved
producer-side evidence extension; point 2 is complete; point 3 is next. No broad test suite or final quality run has
been started.

### Evidence-contract finding and proposed decision

Source review on 2026-11-08 established:

- `tools/application/quality_evidence.py::check_evidence` requires a single native
  passed graph and verifies its commands, selection and candidate. It has no
  machine-readable link to separate native backend reports or continuations.
- `tools/application/testing/runner.py::TestService.execute` writes schema 1 of
  `.runtime/tests/{owner}-results.json`: owner, scope, status, exit code and cleanup
  exit code. It records no candidate, command, run identity, time, runtime identity,
  inventory or report hashes. Subsequent commands overwrite that file. Native
  backend discovery returns before writing it.
- Existing native JUnit and TeamCity artifacts identify tests/datasets and their
  outcomes, but do not independently bind them to a repository candidate or the
  guarded runner's cleanup. A TeamCity unfinished case alone does not establish
  which worker actually crashed. Existing ParaTest `_status` files record native
  progress; their contents are not a reliable process exit-139 receipt.
- The historical RM-2026-017 coverage assessment is an agent-authored read-only
  assessment of retained reports, not a native provenance receipt. Its explicit
  one-release representation decision cannot supply future validator authority.

Consequently, accepting new manually annotated candidate/attempt/coverage fields
alone would violate this roadmap's requirement to reject unsupported agent claims.
The requested recovered path needs a producer-side provenance contract, not only
a consumer-side validator change. This expands the implementation premise from
interpreting existing artifacts to recording facts that are currently absent.

Owner-approved bounded contract (2026-11-08):

1. Extend the existing shared Python test result format and its producer, rather
   than adding a runner, service, separate ledger or persistence engine. Record
   native invocation identity, owner, candidate/working-state identity, literal
   command/selection, execution mode, start/end, environment compatibility identity
   and the existing process/cleanup outcomes. Do not record secrets.
2. Bind the result to the exact native discovery inventory and JUnit/TeamCity/
   diagnostic artifacts with owned paths and content hashes captured by the
   producer. Preserve original bytes and native exit codes. Retain each original
   result/artifact set before subsequent invocations overwrite latest output.
3. Keep discovery, retry and continuation invocation agent-directed through the
   existing supported entrypoints. Add no automatic retry, skip, recovery decision,
   PHP worker ownership or attribution instrumentation. If existing native crash
   diagnostics cannot reliably attribute an interrupted case, refuse recovered
   acceptance for that unknown evidence rather than guessing.
4. Reference retained producer records from the existing roadmap evidence metadata.
   Extend the existing closure validator to check provenance, exact identity-set
   coverage, compatible candidates/modes/environments, cleanup, chronological
   retry limits and successful recovery terminal conditions. A list of claimed
   case names, an unbound XML file or a hash supplied only by an agent is
   insufficient. Preserve the current complete native graph path.
5. Reassess final quality scope for the shared test-lifecycle producer change.
   Exercise its recording/selection behavior with deterministic process fixtures
   through the supported tooling suite. Select real backend execution only if a
   changed native runtime contract requires it; then place it last. No PHP/runtime
   dependency change or historical-record rewrite is proposed.

This proposal concerns evidence production and its trust boundary, not the known
PHP fault's root cause. The existing no-investigation decision remains binding.

### Approved concrete evidence interface

- Extend the existing shared test selection with an opt-in backend `--evidence`
  option. The existing process transport captures native output for this mode,
  preserves its bytes in private artifacts and displays it after completion.
  Ordinary invocation output and native runner ownership remain unchanged.
- Record schema 2 in the existing latest `{owner}-results.json`; keep existing
  scope/exit/cleanup fields. An evidence-enabled backend scope additionally binds
  a unique run ID, clean candidate/working-state identity, literal native command,
  mode/workers, times, configuration/runtime fingerprints and native artifact hashes.
- Native discovery of the same selection records PHPUnit's XML inventory before
  tests execute. Use native TeamCity/JUnit and ParaTest temporary reports in a
  unique workspace-root `.runtime/tests/native/<run-id>/` artifact directory.
  This is report storage, not a worker lifecycle or attempt ledger.
- The original full graph's backend evidence is linked to its coordinator run/step.
  Owner roadmap `quality_recovery` lists paths to retained existing result records,
  original first and manual invocations in chronological order. No extra state file
  or manual PASS/exception field is accepted. The owner composition alone enables
  this recovery path; customer tolerance remains unchanged.
- The validator reads safe owned artifacts, verifies producer hashes and source/
  environment/command compatibility, derives exact case IDs from native inventory
  and TeamCity and deduplicates completed coverage. It accepts only confirmed native
  SIGSEGV, a reliably identified affected case, at most two additional attempts,
  successful terminal recovery and complete remaining coverage. Ordinary errors,
  ambiguous attribution, incomplete artifacts and failed cleanup remain blockers.
- Point 1 instruction assessment: root and customer AGENTS plus owner/public roadmap
  workflow share operational decision/progress/handoff guidance. Owner backend skill
  and owner testing guide retain the SIGSEGV-only procedure. Customer domain skills
  require no change because they already route to the shared workflow and acquire
  no owner recovery permission. No application view/API/module contract changes.

## Implementation plan

Implementation points deliver code, regression scenarios and synchronized current
guidance. Only small fast checks resolving a concrete uncertainty belong there;
broad suites, static analysis and acceptance remain in point 5. Mandatory hooks
remain in force. Each completed point requires a logical commit and checklist
update under the owning workflow. No frontend change is planned, so owner manual
frontend acceptance is not applicable; reassess if scope changes.

- [x] 1. Reconcile operational instructions and establish evidence contracts.
  - Prerequisites: explicit owner activation; read the active point, relevant
    decisions, knowledge and owning sources.
  - Inspect root/nested agent instructions, relevant skills, canonical testing,
    roadmap/release/quality guides, customer product data, existing native reports,
    artifact relationships and closure tests. Establish exactly which coverage,
    retry, provenance and compatibility facts current artifacts can prove.
  - Clarify binding decisions, pre-execution checks, successful recovery terminal
    status, backend-last repair/release ordering, progress reporting and handoff
    in their existing homes; remove competing claims.
  - Specify the minimal evidence extension and owning implementation locations.
    If existing artifacts cannot reliably prove necessary facts without a new
    architectural premise or prohibited runner/attribution machinery, stop and
    discuss that exact limitation before implementing evidence acceptance.
  - Assess owner and customer products: share applicable operational guidance,
    preserve owner-only SIGSEGV policy and protected-platform boundaries, and
    record reasons for products that need no change.
- [x] 2. Enforce the final backend boundary in quality planning and execution.
  - Prerequisites: point 1 contract.
  - Correct the owning graph/planner/executor and previews so every selected
    non-backend validation finishes successfully before full backend launch,
    including database checks and test-bearing final nodes. Preserve complete
    selections, failure propagation, locks, modes and command transports.
  - Add deterministic graph/executor regressions for pending/failed/blocked
    predecessors, broad selections, no cycles, no test-bearing successor and
    invalidation/revalidation ordering. Do not run real expensive suites merely
    to demonstrate their scheduling. Synchronize quality/command documentation.
- [ ] 3. Validate complete recovered evidence through the existing closure path.
  - Prerequisites: points 1–2 and sufficient native artifact contracts.
  - Extend the existing evidence model/validator with the agreed candidate-bound
    recovery input; retain the normal complete graph path and original failures.
  - Enforce exact coverage, retry budget/order, identity/mode/cleanup, current
    graph/commands/scope, provenance and compatibility without automatic recovery,
    owner approval for recovered PASS or broad manual overrides.
  - Add fixture regressions for first/second retry success, exhausted recovery,
    historical FAILED preservation, recovered case with missing remaining
    coverage, inconsistent exception classification, complete graph and complete
    recovered acceptance, assertions, stale/incompatible evidence, missing cases,
    failed cleanup, duplicates and excess attempts. No real crash reproduction.
  - Synchronize owning quality/recovery/evidence guidance and supported usage.
- [ ] 4. Complete cross-contract regression scenarios and instruction review.
  - Prerequisites: points 1–3.
  - Review integration with closure/release checks and delivered tooling; add
    missing focused scenarios for altered evidence, prerequisite failure and
    truthful aggregate state using existing tests, not a second validator.
  - Source-review instruction scenarios that cannot be automated: binding versus
    revisable decisions, ordinary new defects, complete-scope progress, successful
    asynchronous launch/handoff, no polling/automatic continuation and permitted
    short diagnostics. Assess all A–Z scenarios from the supplied task.
  - Confirm current-state docs and both instruction products agree with code;
    preserve closed roadmap/report bytes. Complete implementation and commit
    before broad final verification.
- [ ] 5. Final verification, release 0.8.1, merge and final report.
  - Reassess and record `quality_groups`/`quality_rationale`; preview the exact
    supported graph. Initial scope is docs/tooling/knowledge/delivery with their
    mandatory baseline and real prerequisites. Explain any added heavy group;
    excluded backend/browser/native installation suites are not PASS.
  - Run complete affected validator/workflow/tooling suites and selected source,
    delivery and strict knowledge checks through supported commands. Keep broad
    verification here, retaining valid unrelated evidence after repairs. No full
    Laravel or browser matrix is implied by this tooling correction.
  - If real dependencies require full backend acceptance, complete every other
    mandatory validation and correction first and execute full backend last.
    Use the established manual SIGSEGV procedure if needed, preserving coverage.
  - Use background quality execution when applicable, provide the supported
    `python3 tools/maintainer/quality-status` handoff and end the turn immediately
    after successful launch; await a new user instruction before continuing.
  - Record candidate/source/environment, selected commands, native results,
    artifact paths, coverage, invalidation assessments and any genuinely required
    explicit exception disposition. Insert repair points under the owning
    workflow if final verification finds work; do not conceal failures.
  - Review current-state documentation, finalize VERSION/manifests/changelog and
    closure metadata for 0.8.1 under the release workflow, then merge with a merge
    commit, perform supported main sanity, tag/publish and remove the merged
    roadmap branch. Preserve all hard gates and historical evidence.
  - Report changed files/contracts, enforcement and remaining limits, actual test
    scope/results, release/merge identity and removed/preserved task resources.

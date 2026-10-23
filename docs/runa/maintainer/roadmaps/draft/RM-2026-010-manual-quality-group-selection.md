---
id: RM-2026-010
title: Manual quality group selection for owner and customer workflows
status: draft
branch: roadmap/RM-2026-010-manual-quality-group-selection
created_at: 2026-10-23
activated_at: null
closed_at: null
release_type: maintenance
proposed_version: 0.4.2
impact_areas:
  - tooling
  - development
  - security
  - ai_environment
  - documentation
---

# RM-2026-010 — Manual quality group selection for owner and customer workflows

## Overview

### What and why

Let the agent or operator explicitly select quality/test groups through supported
command flags. A roadmap that does not affect containers or installation should
not launch their expensive acceptance solely because it has a broad tooling or
development impact area. Final verification runs the complete agreed selection
and its prerequisites, with honest scope evidence.

The agent normally runs tests in this project and owns choosing a proportionate
selection from the roadmap, changed behavior and relevant dependencies. Do not
build a source-diff classifier or automatic test-selection engine for that task.

### Order and release

Execute after [RM-2026-009](../closed/RM-2026-009-customer-agent-delivery-and-update-review.md)
completes its current full verification, release, closure and merge. Its released
baseline is 0.4.1; this roadmap projects maintenance PATCH **0.4.2**, explicitly
confirmed by the owner. Refresh the predecessor link and released baseline at
activation without editing the predecessor once closed.

Creation and a separate planning commit on the current RM-009 branch are
authorized now. This draft does not activate a second roadmap, change current
quality behavior or interrupt/reconfigure the running RM-009 gate. Do not apply
its future group flags to that run. Activation remains a separate owner decision.

### Scope

- Manual group flags for existing owner and customer quality plan/run commands.
- A clear group catalogue with reusable existing steps and explicit prerequisites.
- Selection-aware planning, execution, status and final-release evidence.
- Matching native Artisan/host transport/owner entrypoint argument contracts.
- Synchronized owner/customer instructions, workflow policy and examples.
- Regression scenarios for correct selection, exclusions, failure handling and
  representative owner/customer workflows.

### Out of scope

No automatic selection from Git diffs, file-pattern inference, AI classification,
changed-test detection, automatic risk scoring, new CI, new test framework,
separate coordinator, dependency additions or new status/history service. Do not
change individual product behaviors, Docker topology or installation semantics.
Do not optimize test bodies merely to reduce this roadmap's duration. Existing
resource ownership, cleanup, lock and production-isolation contracts stay in force.

## Specification

### Decisions

- 2026-10-23 — Owner confirms release_type maintenance and proposed_version 0.4.2
  after RM-009's projected 0.4.1. This increments PATCH rather than introducing a
  feature/minor release; no version manifest changes during planning.
- 2026-10-23 — Owner chooses manual flags selecting test groups, for both Runa
  owner engineering and customer application development.
- 2026-10-23 — Owner clarifies that the agent normally runs tests and can choose
  groups manually. Automatic source-change classification is explicitly excluded.
- 2026-10-23 — Draft now, wait for RM-009 completion before activation or
  implementation. Keep current running verification and its selection unchanged.

### Baseline and policy reconciliation

Current tools/application/quality.py selects base plus impact-area steps;
quality-final also appends final steps. Owner tooling uses its own gate definition
and private roadmap root. The tooling area currently bundles installer/update/
transport acceptance; the customer base includes broad checks and its final set
includes container/security/knowledge checks. Existing entrypoints currently
accept only their documented options, including --background where supported.

Inspect the actual graph, management argument registry and owner/customer command
transports before finalizing flag syntax. Use the existing coordinator and supported
entrypoints rather than exposing internal Python runners as a parallel interface.

The exact mandatory baseline and group-to-step matrix are proposed implementation
inputs, not a silent amendment to present unconditional gates. Agree the matrix
in point 1 before changing policy or runtime selection. Explicitly reconcile current
final AI/security/release requirements in both contracts and workflows; stop on an
unresolved constitutional decision instead of treating this draft as permission
to bypass it. No test-selection policy changes during RM-009.

### Manual flags and group catalogue

Provide a repeatable group option on existing plan/roadmap/final entrypoints, plus
an explicit all-groups option. Proposed spelling is --group=NAME and --all; finalize
it against the existing command registry in point 1. Multiple named groups form a
union. No automatic classification from changed files or inferred scope.

The agent chooses and records the selection for the roadmap's final verification,
using actual scope and dependencies as engineering judgment. The owner can steer
that choice. Do not ask the owner to choose every group on every invocation.
Impact areas remain useful descriptive roadmap metadata; they must not silently
pull an unrelated installer/image suite into an explicit group selection.

Define groups from existing capabilities: documentation, backend, frontend,
browser/E2E, database, development/runtime, containers/images, installation,
updates, command/host transport, security and knowledge where available. Finalize
names/granularity from the real steps rather than creating empty ceremonial groups.
Only expose owner tests to owner workflows; customer choices use delivered tests
and commands and never require maintainer files or platform harnesses.

Separate image builds/scans and native installation acceptance from generic
"tooling". Selecting a lightweight tools/docs group must not run a container build
merely because the current graph co-locates those checks. Conversely, a chosen
installation/update group keeps its real required source/runtime/data/security
acceptance; do not produce a misleading cheap version of the same group.

Use the existing dependency graph to include genuine prerequisites of selected
steps, deduplicate shared work and preserve ordering/exclusive groups. This
mechanical dependency expansion is not automatic scope selection. Show why each
prerequisite was included. Reject unknown groups, contradictory all/group options,
unavailable owner/customer groups and malformed arguments before launching work.
Never allow arbitrary command strings or executable paths through group flags.

Proposed compatibility behavior: flag-free calls retain their current documented
selection until an explicit migration decision in point 1; new agent examples use
explicit groups. Plan/run/final must resolve the same explicit selection. Do not
silently interpret missing flags as a successful empty verification.

### Baseline, evidence and final closure

Proposed always-required lightweight baseline: roadmap/branch/release-state and
source ownership checks, secret protection, documentation consistency and existing
commit hooks. Resolve exact configuration/audit/freshness prerequisites and their
scope in the agreed point-1 matrix. Heavy acceptance belongs to the explicitly
selected groups and real prerequisites, rather than an unrelated unconditional
base/final entry. Keep hard-fail severity for every selected prerequisite/gate.
Security, data-loss and production-health failures never become warnings merely
because scope is manually selected.

Plan output lists the requested groups, mandatory baseline, added prerequisites,
exact commands and excluded groups without executing them. Execution stores this
resolved selection with branch/commit and results. Status shows the same scope,
elapsed times, failures and logs. Distinguish successful selected-scope verification
from a run of all groups; an excluded group is not a passing test.

Final roadmap evidence records the chosen groups and a concise scope rationale.
Closure validates that this agreed selection and its prerequisites passed; a
narrow exploratory run cannot replace the final agreed plan. If the scope changes,
update its plan before execution and run the full newly agreed final selection.
Preserve fix-point insertion and full rerun from the beginning after failures.
Do not reuse stale results to fill omitted selected steps.

Keep the existing locks, background coordinator, latest-run storage, exit codes,
interruption semantics, host/container guards, customer/owner separation and scoped
resource cleanup. Only final full verification uses the established background
handoff and correct built-in status command. Necessary small focused checks inside
implementation points still finish with observed results before point completion.

### Documentation and acceptance scenarios

Synchronize root owner AGENTS/skills and the separately authored customer bundle,
public/owner quality guides, command help, roadmap/release policies and templates.
State that the agent manually selects groups. Explain representative docs-only,
backend-only, frontend/E2E, installer/update, container and explicit all-group
choices in both supported scopes. Do not document unimplemented flags as available.

Prepare regressions demonstrating that excluded expensive commands are never
spawned; prerequisites are expanded exactly once; invalid selections do not mutate
run evidence/start children; failures/interruption propagate; plan/run/status agree;
customer clones need no owner tooling; and final closure respects the recorded
selection. Test the real management/host transport as well as the selector.
Run broad acceptance, suites, static analysis, builds and scanners only in the
final verification point, after implementation is complete.

## Implementation plan

- [ ] 1. Agree the group catalogue, mandatory baseline, CLI and closure contract.
  - Requires explicit activation after RM-009 closure/merge/release. Refresh the
    actual baseline, local knowledge and owner/customer graph/transport contracts.
    Produce a concrete group-to-step/prerequisite matrix and resolve policy changes
    with the owner before implementation. Record compatibility/default behavior.
- [ ] 2. Implement explicit group selection in the existing quality coordinator.
  - Requires 1. Add deterministic validation, unions, prerequisite expansion,
    planning/evidence/status scope and focused regression scenarios. Keep current
    locking/interruption/exit/resource semantics; no change classifier or new runner.
    Synchronize affected current documentation in this point.
- [ ] 3. Wire both supported command products and synchronize policy/guidance.
  - Requires 2. Propagate allowed flags through owner and customer Artisan/host
    registration/transports; preserve production and ownership guards. Apply the
    agreed baseline/final matrix, closure evidence and compatibility transition.
    Update both instruction products, help, workflows and examples in the same point.
    Prepare integration scenarios without early broad acceptance.
- [ ] 4. Full verification, release finalization, merge and final report.
  - Requires 1–3 implemented and committed. Review current-state docs and both
    instruction products. Run all applicable selection/coordinator/transport/
    owner/customer acceptance, security and isolation checks through the supported
    final workflow; do not validate this implementation solely with a narrow group
    selected by the implementation under test. Use the existing background handoff.
    Insert fix points and repeat the complete agreed final run after failures.
    Finalize maintenance 0.4.2 against the actual predecessor, close, merge with a
    merge commit, sanity-check main, tag/push/publish and remove the branch under
    the established release workflow.

## Planning status

Only the draft and its planning commit are authorized now. Runtime implementation,
activation, revised gate policy and release remain pending. Exact baseline and
flag/default details require the point-1 agreement; no new CLI option exists yet.

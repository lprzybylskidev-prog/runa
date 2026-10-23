---
id: RM-2026-010
title: Manual quality group selection for owner and customer workflows
status: active
branch: roadmap/RM-2026-010-manual-quality-group-selection
created_at: 2026-10-23
activated_at: 2026-10-23
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

RM-009 is closed and merged on main at 58c159f; VERSION and published tag
v0.4.1 establish the released baseline. On 2026-10-23 the owner requests execution
of this next roadmap. Activation is authorized; the concrete policy proposal below
was approved in the point-1 agreement before runtime implementation.

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

- 2026-10-23 — Owner accepts the complete point-1 contract and authorizes
  implementation, including selection-scoped security/AI policy and --all final
  verification for RM-010.

- 2026-10-23 — Owner requests execution of the next roadmap in one continuous
  effort. Activate RM-010 after confirming RM-009 closure and the clean, updated
  main baseline. Point 1 still requires explicit agreement on the concrete gate
  policy; activation does not silently approve that constitutional change.

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

### Agreed point-1 contract

Inspected sources: tools/application/quality.py, quality.yml and executors/quality.py;
tools/maintainer/entrypoint (quality-plan launcher); owner quality/gates.yml;
src/runa/management/development.json and ToolingCommand.php; owner quality,
roadmap/release workflows and public command catalogue. Local knowledge status was
current on main; retrieval corroborated these source contracts. No broad checks ran.

Owner approved this complete contract on 2026-10-23 ("akceptuje działaj").
Implementation is authorized. Flags become available only when implemented.

CLI and compatibility:

- Repeatable --group=NAME and standalone --all on plan/roadmap/final, with the same
  explicit resolved step set in all three modes. --background remains limited to
  roadmap/final; status reads stored scope and accepts no selection flags.
- Reject --all with --group, empty/unknown/unavailable groups, extra positional
  arguments and malformed flags before mutating latest-run evidence or starting
  children. Deduplicate repeated groups and shared prerequisite steps.
- Flag-free invocations preserve the exact existing impact-area/base/final behavior.
  New agent examples use explicit groups. No automatic scope inference is added.
- Explicit selected final runs retain lightweight release/closure checks in their
  resolved baseline. A final roadmap selection and rationale are recorded in the
  roadmap; closure checks evidence against it, candidate commit and results.
  Exploratory runs cannot substitute for this final selection.

Mandatory baseline for every explicit owner selection: repository-state,
secret-scan, debug-residue, maintenance-consistency, documentation-consistency,
tool-ownership-contracts, repository-boundary-contracts, source-tree-cleanliness,
release-metadata-contract and version-changelog-roadmap. Source ownership checks
remain source-only; they do not acquire/build a customer fixture. Retain mandatory
commit hooks and knowledge freshness. Configuration/configuration-templates are
prerequisites of runtime/tool checks that actually need them, rather than reasons
to include all backend tests. Preserve each selected node's hard-fail severity.

Mandatory customer baseline: application-config, application-config-templates,
application-docs, application-secrets, plus the existing delivered debug-residue
check. Retain customer hooks, source ownership protection and knowledge freshness.
No owner roadmap/release tooling is added to customer selections.

The following matrix maps explicit groups to existing owner step IDs. Dependencies
are expanded transitively using depends_on; conditional ordering applies only when
both nodes are selected. Every added prerequisite is reported with its parent.
Baseline steps are omitted from the table to avoid duplication.

| Group        | Owner selected steps                                                                                                | Customer selected capabilities                                                                                                           |
| ------------ | ------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- |
| docs         | documentation-ownership-contracts                                                                                   | application-docs (baseline)                                                                                                              |
| tooling      | quality-runner-contract, tooling-help                                                                               | delivered tooling doctor                                                                                                                 |
| backend      | backend-tests, php-static-analysis, module-development-contracts, formatting, lint                                  | application-tests, application-analysis, application-module-contracts, application-module-versions, application-format, application-lint |
| frontend     | frontend-build, frontend-typecheck, frontend-tests, formatting, lint                                                | existing build, application-types, runa:test:frontend, application-format, application-lint                                              |
| e2e          | frontend-browser, frontend-render-diagnostics                                                                       | runa:test:e2e and delivered frontend diagnostics/build prerequisites                                                                     |
| database     | database-migrations, database-constraints, seeding-contract                                                         | delivered database-migrations/database/seeding diagnostics with application tests                                                        |
| development  | dependency-composition-contracts, development-startup-contract, development-stack, hook-contract                    | delivered development and hooks diagnostics                                                                                              |
| containers   | repository-container-scan, gosu-disposition-contracts                                                               | application-container-security (existing Trivy contract)                                                                                 |
| installation | installation-contracts, installation-host-acceptance, bootstrap-source-contracts, customer-initialization-contracts | unavailable: owner acquisition acceptance is not delivered                                                                               |
| updates      | update-preflight-contracts, update-execution-contracts, installation-host-acceptance                                | unavailable: owner updater acceptance is not delivered                                                                                   |
| transport    | host-executor-contracts, host-executor-acceptance, management-transport-contracts, management-host-acceptance       | unavailable: owner transport acceptance is not delivered                                                                                 |
| production   | production-operation-contracts, customer-deployment-source-contracts, production-acceptance                         | unavailable: owner production fixture acceptance is not delivered                                                                        |
| security     | dependency-audit, security-review-contracts, expanded-security-review, gosu-disposition-contracts                   | application-dependencies and application-container-security                                                                              |
| knowledge    | documentation-ownership-live, knowledge-doctor, knowledge-evals, boost-mcp                                          | application-knowledge-doctor, application-knowledge-eval, delivered Boost diagnostic                                                     |

Customer entries naming existing commands reuse those commands as graph nodes;
they do not introduce new test frameworks or expose private owner harnesses.
Do not add groups for nonexistent customer installer/updater/transport acceptance.
The containers group necessarily keeps image build/scanning acceptance; security's
expanded review depends on that scan and therefore also runs it. Installation and
updates retain the full current installation-host acceptance, including updater,
team propagation and customer-knowledge scenarios. Production keeps its real data,
deployment and isolation acceptance. Do not split these into cheaper imitations.

Approved policy reconciliation:

- Explicit docs/tooling/backend selections may exclude image scans, full dependency
  audits, AI doctor/evals and installation acceptance unless selected transitively.
  Secret protection, hooks/freshness, ownership and release consistency remain
  mandatory. Full security/AI gates are strict when selected; excluded means not run.
- Update both owner/customer contracts and affected handbook/maintainer workflows
  to replace unconditional full AI/security requirements with this explicit policy.
  Legacy flag-free behavior remains unchanged during compatibility migration.
- The agent chooses a proportionate final selection and records its rationale;
  owner steering overrides it. Ordinary group choices need no repeated approval.
- This roadmap changes the coordinator, transports, evidence and both instruction
  products. Its own final verification uses --all, including installation, real
  image/security/production and knowledge checks; this implementation is not
  validated only by its own narrow selection. Final verification remains the sole
  broad execution point and uses the supported background handoff.

Owner/customer instruction products are intentionally unchanged during this
planning point: no reusable runtime capability or approved gate policy has changed.
Both products will be synchronized in implementation points 2–3.

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

- [x] 1. Agree the group catalogue, mandatory baseline, CLI and closure contract.
  - Requires explicit activation after RM-009 closure/merge/release. Refresh the
    actual baseline, local knowledge and owner/customer graph/transport contracts.
    Produce a concrete group-to-step/prerequisite matrix and resolve policy changes
    with the owner before implementation. Record compatibility/default behavior.
- [x] 2. Implement explicit group selection in the existing quality coordinator.
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

Activated with owner execution authorization on 2026-10-23, from clean main at
58c159f (0.4.1). Owner approved the point-1 catalogue, baseline, CLI, compatibility
and closure contract on 2026-10-23. Implementation points 2–3 are authorized. Release metadata remains maintenance 0.4.2 as previously confirmed.

## Point 2 outcome

Implemented explicit resolver, validation, prerequisite inclusion reasons, scope
evidence/status and graph fingerprint in the existing coordinator. Added isolated
regressions for exclusion, deduplication, legacy/all scopes and invalid graphs.
Public transports and catalogues follow in point 3. No broad verification ran.
Owner quality guide reflects the internal availability boundary. Both instruction
products are assessed: public invocation policy is synchronized in point 3 when
flags become supported; neither product exposes internal runners.

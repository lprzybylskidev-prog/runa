---
id: RM-2026-015
title: Proportionate quality verification and container fixture reuse
status: active
branch: roadmap/RM-2026-015-proportionate-quality-and-fixture-reuse
created_at: 2026-10-29
activated_at: 2026-10-29
closed_at: null
release_type: maintenance
proposed_version: 0.6.2
quality_groups: docs,tooling,development,installation,updates,transport,production,security
quality_rationale: Verify changes to quality selection, shared acceptance environments, isolated command execution and evidence ownership across the actual installation, update, transport, production and security harnesses being changed. This roadmap changes those harnesses themselves; their final acceptance is relevant even though an isolated future knowledge-ranking change would not require them. Reconcile the exact selection and prerequisites before final execution; backend and application UI suites are excluded unless implementation actually affects them.
impact_areas:
  - tooling
  - development
  - documentation
  - production
  - security
---

# RM-2026-015 — Proportionate quality verification and container fixture reuse

## Overview

### What and why

Reduce final-verification time by selecting checks for actual changed behavior and
avoiding repeated preparation of equivalent disposable environments. Preserve the
assertions, real execution, failure reporting and isolation those checks establish.

The owner initially requested 0.6.3 after RM-013 and planned RM-014. On
2026-10-29 the owner cancels and removes RM-014 and explicitly requests shifting
subsequent draft release versions. This roadmap now targets **0.6.2**, directly
after released [RM-013](../closed/RM-2026-013-knowledge-retrieval-reliability.md)
maintenance 0.6.1. Retain the owner-delegated `maintenance` classification:
compatible engineering/process optimization, a PATCH increment from 0.6.1.
Activation confirms the released 0.6.1 baseline and ordering. VERSION remains
unchanged until verified release finalization.

The owner also requests an explicit execution rule: a change confined to knowledge
retrieval must not automatically trigger full installation/update/deployment
acceptance. Verification needs a concrete relationship to the changed capability,
its supported entrypoints and real prerequisites.

### Scope

Manual scope selection and the existing quality catalogue/resolver; separation of
narrow source/transport/projection checks from broad native acceptance; reusable
isolated acceptance fixtures; grouped image/command probes; phase timings and
resource ownership; owner/customer guidance and regression contracts. Preserve
supported commands, the existing coordinator and exact selected-scope evidence.

### Out of scope

No backend SIGSEGV diagnosis, dependency/image pin updates, business behavior,
production topology redesign, CI, automatic Git-diff classifier, second
coordinator, permanent shared test daemon or verification-history dashboard.
No global prune, shared-cache deletion, persistent database reset, scanner
suppression, weaker selected security/AI checks or manufactured PASS.

## Specification

### Decisions

- 2026-10-29 — Owner requests starting the next roadmap, authorizing activation
  of RM-015. Confirm clean main equals origin/main at f34903b4 and VERSION 0.6.1;
  retain the previously authorized maintenance 0.6.2 release metadata. The plan
  reserves broad verification for point 6; no earlier broad gate is required.

- 2026-10-29 — Owner cancels/removes RM-014 and requests version reordering.
  Retarget this draft from 0.6.3 to maintenance 0.6.2 after released 0.6.1;
  retain RM-015 identity and draft status. The standing owner SIGSEGV acceptance
  rule lives in the owner testing guide; retries/diagnosis are not this scope.

- 2026-10-29 — Owner requests a follow-up draft targeting 0.6.3 and delegates
  release-type selection. Agent selects maintenance after planned fix 0.6.2;
  release metadata is authorized for planning, not roadmap activation.
- 2026-10-29 — Owner prioritizes grouping compatible container-based checks and
  selecting verification for changed areas. Full installation/deployment must
  not follow from a knowledge-only change without a demonstrated affected contract.
- Owner explicitly requests closing and committing RM-013 before adding this
  draft on main. RM-013 closed and released as 0.6.1 after 47/47 selected checks
  passed; this separate planning commit preserves its implementation, raw results
  and backend exclusion. RM-014 was subsequently cancelled and removed on 2026-10-29; its proposed release no longer precedes this draft.

### Observed baseline and limits

The completed c929ecda verification took 2368.4 seconds. Its serial heavy stages
were installation/update/team/customer-knowledge acceptance (1037.4 seconds),
production/deployment acceptance (841.8 seconds), and production setup/security
scan (381.5 seconds): 2260.7 seconds in total. These are observed elapsed times
on one host/candidate, not portable performance guarantees.

Those three logs contain 138 distinct Compose container-creation names, including
97 one-off `*-run-*` names. This is not a complete Docker-event inventory and does
not mean each assertion gets a new environment. Many assertions already share
stacks. BuildKit cache hits show image-layer reuse already occurs.

Production acceptance and repository scanning independently invoke the same
installation harness and repeat production source preparation, TLS/SMTP rejection
and recovery, migrations, image preparation and health/isolation checks. In that
scan stage the first scanner messages appeared about 370 seconds after stage
start, approximately eleven seconds before stage completion. Setup dominates
that particular measurement; do not equate all stage time with scanner cost.

The existing harness can take both `--security-scan` and `--customer-deployment`.
It scans the healthy selected revision before deployment-failure scenarios, which
intentionally finish with an unhealthy fenced deployment. Preserve that ordering.
`DeploymentRuntime.artisan` uses a fresh `compose run --rm` process; image
verification also starts separate PHP, shell, FPM and Nginx probes. Some cold
execution is part of the behavior under test and must remain real.

### Proportionate execution rule

1. Before final verification, manually identify changed behaviors and contracts,
   their direct checks, and each real prerequisite. Record why each heavy area is
   included or excluded, using the existing roadmap declaration and graph preview.
2. Choose complete supported suites for the affected areas. A tooling path,
   shared entrypoint, security label or historical impact area alone does not
   establish a need for installation, update, deployment or browser acceptance.
3. Separate narrow customer-source projection, command wiring, production guard
   and corpus-isolation checks from complete acquisition/deployment acceptance.
   Test the changed boundary without secretly selecting an entire unrelated stack.
4. Keep mandatory baseline/hooks and genuine prerequisites. Remove accidental
   broad prerequisite edges by understanding their original invariant and placing
   its check at the appropriate scope; do not simply delete the invariant.
5. Selected security/AI checks stay strict. Distinguish source/dependency review
   from actual runtime-image scanning in the catalogue. Scan real images when
   their affected inputs or the agreed selected scope require it; never label an
   excluded image scan PASS or silently narrow a selected scanner.
6. Default to the smallest sufficient manual selection for ordinary releases.
   Owner policy requires `--all` at minor milestones 0.10.0, 0.15.0, 0.20.0 and
   subsequent multiples of five, and before 1.0.0. PATCH releases do not advance
   this cadence. Record full-run evidence at each due milestone; a scoped run
   does not satisfy it. Use additional `--all` only with an explicit rationale. Retain documented flag-free compatibility and
   owner steering; no automatic change classifier or hidden test selection.
7. Ordinary implementation permits only necessary small focused checks. Broad
   suites, builds, scanners, acceptance and performance comparisons belong to
   final verification after implementation is committed. Failed final checks use
   the complete affected-area repair loop, then restart the agreed final graph.

Examples are reasoning cases, not a filename classifier:

| Changed contract                                                                                 | Expected verification relationship                                                                                                                               |
| ------------------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Internal knowledge ranking/chunking with stable packaging, entrypoints and runtime boundaries    | Python/knowledge checks, source grounding, fixed evaluations and real knowledge acceptance; full installation/deployment requires an additional concrete reason. |
| Delivered knowledge command, customer instruction substitution or production exclusion           | Relevant command/projection/customer-isolation/production-guard checks; broad native acceptance only for installation/deployment behavior actually changed.      |
| Installer, updater, deployment, Compose topology, image assembly or acceptance-fixture lifecycle | Complete affected native workflows, exact images and relevant security/isolation checks.                                                                         |
| This roadmap's scheduler/catalogue/shared fixture implementation                                 | Exercise every affected selected scope and their combinations; broad acceptance is necessary to validate the harness changes.                                    |

### Shared environments and command execution

- Reuse one freshly prepared production fixture when production acceptance and
  image scanning select the same exact revision, images, role, settings and
  compatible scenario state. Scan healthy images before injecting failures.
- Preserve separate meaningful phase results for setup, scanning, deployment,
  failures and cleanup. Reuse must not erase selected checks, hide a missing phase
  or convert a failed/unexecuted phase into PASS. Bind evidence to exact source,
  images, requested groups and the existing graph/command fingerprint.
- Retain standalone production-only and security-only workflows. Combined
  selection should avoid duplicate setup; it must not force unrelated scenarios
  into a narrow selection or introduce a second scheduler.
- Group compatible read-only PHP/shell/configuration probes for one immutable
  image and execution context. Retain separate image roles and native exits.
  A reused container does not imply a reused Laravel/PHP process or global state.
- Reuse running fixture services only when identity, release, credentials, mounts,
  state and lifecycle semantics match. Never execute a target-release migration
  in an old running release merely to avoid a new container.
- Preserve genuinely fresh acquisition/first-boot, migrations, cold transport,
  restart/recovery, immutable-image identity and deliberate failure scenarios.
  First prove that a shared phase leaves the next scenario's prerequisites valid.
- Do not redesign normal deployment operations just to accelerate tests. Broader
  runtime lifecycle changes require separate agreement if discovered necessary.
- Own temporary resources per run and compatible scenario group. Show exact scoped
  cleanup identities; preserve development/test data, model/dependency/build
  caches, backups and reports. Failed/interrupted runs retain recoverable cleanup
  ownership; sharing must not permit one phase to destroy another's live resources.

### Measurement and acceptance

Use existing timing/state/log evidence for initial analysis. Extend the existing
reporting with meaningful phase costs: source/dependency preparation, image build,
container startup/health, actual assertions, scanning and cleanup. Record cold
versus cached conditions, source/image identity and resource counts without secrets.
Do not create a separate telemetry system or run broad baseline experiments during
implementation points.

Final verification must demonstrate elimination of duplicate compatible production
setup, fewer compatible one-off probes, preserved required scenarios/assertions,
correct independent and combined group selection, truthful failure/interruption
results, and complete cleanup ownership. Measure elapsed time and resource counts
on comparable conditions; explain changed workloads/cache conditions and remaining
costs. The roughly six-minute duplicate setup is a candidate saving, not an
approved guarantee or justification for weakening coverage.

## Implementation plan

- [x] 1. Establish the changed-contract scope and cost model from existing evidence.
  - Read the active roadmap, source catalogue, current harnesses and prior timings.
  - Identify repeated setup and indispensable fresh-state scenarios; document
    dependencies and the before/after scenario matrix without broad reruns.
- [ ] 2. Make proportionate manual selection concrete in the existing catalogue.
  - Separate narrow source/projection/guard checks from broad acceptance and
    reconcile genuine prerequisite edges, selection explanations and exclusions.
  - Author regression contracts for isolated knowledge/tooling changes, changed
    delivery boundaries, and explicit broad or combined selections.
- [ ] 3. Reuse compatible production preparation across acceptance and scanning.
  - Retain exact image/source identity, healthy-scan-before-failure ordering,
    standalone workflows, phase results and scoped cleanup/recovery ownership.
  - Implement through existing harness/coordinator composition and author failure,
    interruption, missing-phase and selection-combination contracts.
- [ ] 4. Group compatible probes and expose preparation versus assertion costs.
  - Reduce redundant transient image probes; preserve required cold starts,
    process isolation, native exits and real deployment lifecycle scenarios.
  - Add bounded phase/resource measurements to existing reports and synchronize
    affected current-state guides in the same point.
- [ ] 5. Synchronize owner/customer execution guidance and instruction products.
  - Reconcile root AGENTS, skills, customer authoring bundle and canonical quality/
    testing/workflow guides around the agreed proportionate execution rule.
  - Remove duplicated obsolete blanket instructions; retain strict selected checks,
    mandatory baseline, final-only broad verification and immutable history.
- [ ] 6. Complete final verification, measured comparison, release and closure.
  - Finish and commit points 1–5 first. Review current-state documentation and
    finalize quality groups/rationale against actual changes; preview exact graph.
  - Run complete affected suites and independent/combined native acceptance,
    scanning and failure/cleanup scenarios, with performance measurements only here.
  - Use supported `python3 tools/maintainer/quality-roadmap --background` with the
    agreed repeated groups; end the turn and provide quality-status. Preserve
    native reports and exclusions. Insert repair points for failures, complete
    affected-area checks and restart final verification from the beginning.
  - Show and remove only owned disposable resources after their final use.
    Preserve persistent data, caches, backups and verification evidence.
  - Finalize maintenance 0.6.2 after refreshing the predecessor baseline. Perform
    release metadata/documentation synchronization, merge commit, main sanity,
    annotated tag/publication and branch deletion only after actual required PASS.

## Point 1 — Source-verified scope and cost model

Reviewed 2026-10-29 against activation commit c034719c. Knowledge status was
current (191 documents, 9251 chunks before activation); the bounded current-intent
query `quality group prerequisites production fixture scanning` returned the
owner/public quality guides and AGENTS. Opened the owner guide and relevant source
before acting. No broad baseline rerun, build, scanner or acceptance was executed.

### Existing timing evidence

Read `.runtime/artifacts/RM-2026-013-failed-c929ecda/state.json` and its three
native logs. Candidate is c929ecdacfbb3ac0007ea45cbc27d30f55a90736; overall
2368.371 seconds. This historical run failed unrelated selected gates; these
measurements establish cost, not PASS for this roadmap.

| Existing stage               |  Seconds | Distinct logged container creations | One-off names |
| ---------------------------- | -------: | ----------------------------------: | ------------: |
| installation-host-acceptance | 1037.401 |                                  53 |            23 |
| production-acceptance        |  841.809 |                                  64 |            58 |
| repository-container-scan    |  381.504 |                                  21 |            16 |

Counts use distinct `Container NAME Creating/Created` names within each log;
they are not a Docker-event inventory. Native reports remain under
`.runtime/ownership/installation-host-{b49593dfd0,531259f52b,74a00f7136}.json`.
Existing caches were available; a new cold-host comparison is not implied.

### Source findings and before/after scenario matrix

`gates.yml` already keeps docs/tooling/knowledge selections independent of broad
native acceptance. Remaining coarse boundaries are explicit security selection
(expanded review depends on image scanning) and the absence of named narrow
delivery/production-guard selection. Preserve existing broad group semantics;
expose narrow source boundaries explicitly instead of weakening security.
`quality_planning.resolve` records group/prerequisite reasons and a complete graph
fingerprint; reuse it, not a second classifier/coordinator.

| Contract/scenario                         | Current execution                                                                                                                                                   | Planned relationship                                                                                                                                                                                  |
| ----------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Internal knowledge ranking                | Knowledge live acceptance, doctor/evals and mandatory source checks                                                                                                 | Keep independent of installation/update/production/image acceptance.                                                                                                                                  |
| Customer source/instructions/corpus scope | Real disposable Git projection in test_documentation.py; receipt fencing in test_repository_boundary.py; live disposable Qdrant transition in documentation_live.py | Expose narrow source/guard checks and retain real knowledge transition when affected. No Docker production setup prerequisite.                                                                        |
| Command wiring and production denial      | Command-adapter, production and deployment boundary contracts                                                                                                       | Make directly selectable without native deployment.                                                                                                                                                   |
| Standalone production acceptance          | Fresh installer fixture plus customer deployment exercise                                                                                                           | Preserve all native setup, forward data changes, recovery and failure assertions.                                                                                                                     |
| Standalone image security acceptance      | Fresh production-only fixture plus repository/exact selected runtime image scans                                                                                    | Preserve strict scans and raw reports.                                                                                                                                                                |
| Production plus image security            | Two complete production-only fixture preparations                                                                                                                   | Prepare one exact compatible production fixture; validate/scan its healthy selected images, then exercise customer deployment failures. Preserve separate phase evidence and selected graph identity. |
| Installation/update/team propagation      | Shared native installer fixture with additional distinct source/release states                                                                                      | Retain fresh acquisition, teammate source comparison, target-release migrations and actual update/restart transport. Do not reuse a running old-release PHP process.                                  |
| Immutable PHP image inspection            | Three equal-context read-only transient containers: PHP policy, shell tool exclusion, FPM config                                                                    | Group compatible probes in one container with separate processes and failure propagation; retain distinct Nginx image/context.                                                                        |
| Resource cleanup                          | Installer finally block removes exact projects/tags and preserves active development identities                                                                     | Keep one fixture owner through all shared phases; persist recovery identities before setup and preserve failed/interrupted evidence.                                                                  |

`installation_host.main` already accepts both `--security-scan` and
`--customer-deployment`; the scan runs before the deliberately unhealthy fenced
failure scenarios. This supplies the composition point. Its report currently
records images, revisions, assertions and removals but no preparation/scan/cleanup
phase costs or pre-setup recovery inventory. Add these within the existing report.
`ProductionRuntime.verify_images` currently launches four probes (three PHP-image,
one Nginx-image); `DeploymentRuntime.artisan` deliberately uses fresh `compose run`.
Optimize immutable read-only probes first; leave runtime migration execution intact.

### Dependencies, instruction assessment and remaining evidence

Point 2 owns selectable narrow boundaries; point 3 owns exact graph-bound shared
fixture evidence and truthful failure/interruption handling; point 4 owns grouped
probes and timings. Implement the independent/combined and missing-phase regression
scenarios there, execute their broad/native acceptance only in point 6. Final
comparison must disclose workload/cache differences from c929ecda rather than
promise the observed six-minute setup saving.

This point changes planning/evidence only. Root owner instructions, domain skills
and customer instruction products need no behavior update here; their affected
execution guidance is assessed and synchronized with implementation in points 2–5.
No dependencies, runtime configuration, databases or Docker resources were changed.

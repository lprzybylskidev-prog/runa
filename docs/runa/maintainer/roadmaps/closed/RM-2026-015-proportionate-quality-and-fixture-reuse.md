---
id: RM-2026-015
title: Proportionate quality verification and container fixture reuse
status: closed
branch: roadmap/RM-2026-015-proportionate-quality-and-fixture-reuse
created_at: 2026-10-29
activated_at: 2026-10-29
closed_at: 2026-10-29
release_type: maintenance
proposed_version: 0.6.2
quality_groups: docs,tooling,development,installation,updates,transport,production,security,fixture-acceptance
quality_rationale: Verify the changed owner/customer quality catalogues, coordinator command composition and reporting through docs/tooling; development and transport cover changed management group discovery and mandatory hook integration. Installation/updates exercise the modified shared installer harness and its customer/team paths; production/security cover grouped runtime image policy, additive deployment costs, actual exact-image scanning and source review. Fixture-acceptance additionally qualifies fresh standalone production and scanner paths alongside the combined fixture. Backend, frontend, database, browser and knowledge-runtime behavior is unchanged and those suites are excluded; mandatory source ownership, secrets, freshness and release checks remain selected. No all-group milestone is due for maintenance 0.6.2.
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
- [x] 2. Make proportionate manual selection concrete in the existing catalogue.
  - Separate narrow source/projection/guard checks from broad acceptance and
    reconcile genuine prerequisite edges, selection explanations and exclusions.
  - Author regression contracts for isolated knowledge/tooling changes, changed
    delivery boundaries, and explicit broad or combined selections.
- [x] 3. Reuse compatible production preparation across acceptance and scanning.
  - Retain exact image/source identity, healthy-scan-before-failure ordering,
    standalone workflows, phase results and scoped cleanup/recovery ownership.
  - Implement through existing harness/coordinator composition and author failure,
    interruption, missing-phase and selection-combination contracts.
- [x] 4. Group compatible probes and expose preparation versus assertion costs.
  - Reduce redundant transient image probes; preserve required cold starts,
    process isolation, native exits and real deployment lifecycle scenarios.
  - Add bounded phase/resource measurements to existing reports and synchronize
    affected current-state guides in the same point.
- [x] 5. Synchronize owner/customer execution guidance and instruction products.
  - Reconcile root AGENTS, skills, customer authoring bundle and canonical quality/
    testing/workflow guides around the agreed proportionate execution rule.
  - Remove duplicated obsolete blanket instructions; retain strict selected checks,
    mandatory baseline, final-only broad verification and immutable history.
- [x] 6. Repair initial final-verification failures.
  - Preserve failed candidate 3bf3508d logs and native reports.
  - Update the spawn-refusal contract for authoritative quality environment values
    and correct strict Python fixture/JSON typing without suppressions.
  - Place new private installer reports/workspaces below `.runtime/acceptance/installation`,
    preserving legacy `.runtime/ownership` reports and its existing permissions.
  - Run the complete owner Python unit/contracts suite and strict typecheck, then
    complete native combined production/scanning and installation/update/team/
    customer-knowledge acceptance before committing this repair.
  - Refresh source review and knowledge; restart the declared final graph.
- [x] 7. Complete final verification, measured comparison, release and closure.
  - Finish and commit points 1–6 first. Review current-state documentation and
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

## Point 2 — Explicit narrow verification scopes

Added owner `delivery` for existing projection/customer-source/transport contracts
and dedicated production-source/deployment-boundary nodes. These use source and
fake-process contracts, never native installation/deployment. Production selection
also includes its source nodes. Added `security-source`: owner dependency audits,
security contracts and exact content-bound agent review; customer dependency audits.
Existing `security` still selects actual runtime image scans. The source review's
image prerequisite is conditional on image selection, retaining scan-before-review
when both run without inventing a runtime prerequisite for source-only review.

Regression contracts cover knowledge/tooling/delivery/source-security isolation
despite broad historical impact areas, baseline retention, exact graph hashes,
independent/combined scans and owner/customer catalogue separation. They are authored
for final verification; no broad checks were run in this implementation point.
Public quality/command guides document the complete CLI signature, audience,
implementation home and development-only delivery. Customer group discovery is
synchronized. Root AGENTS and both skill products already route group selection
to those canonical guides; no new always-loaded rule is needed in this point.

## Point 3 — One production fixture for combined acceptance and scanning

Source-owned conditional commands resolve within the existing graph. Combined
selection makes production acceptance run its existing healthy scan before
deployment failures, then makes the scanner node validate the completed phase
evidence. Standalone groups retain native preparation and neither silently runs
the other's scenarios. Both node identities remain; selected frontend build/render
dependencies also precede the shared scan. Commands/alternatives and dependencies
are included in the existing graph fingerprint, preview and closure contract.

The coordinator supplies fresh per-run identity and authoritative child scope.
Private shared evidence binds run, source candidate, branch, selection and complete
command graph; requires exact production image IDs/revision and all five successful
ordered phases including cleanup. Atomic native reports persist ownership before
setup and retain interrupted/running phases for recovery. Fixtures now live under
`.runtime/ownership` rather than host temporary directories. SIGTERM participates
in normal guarded cleanup. Existing exact project/tag cleanup and development
identity preservation remain in force; no cross-run reuse or runtime migration
execution change is introduced.

Authored independent/combined/all/legacy selection, ambiguous command, phase
failure/interruption/missing/stale/ordering and cleanup-failure regressions for the
final point. The only diagnostic so far compiled the changed Python source to
check indentation after wrapping existing phases; no suite or native acceptance
ran. Owner quality guide is synchronized; customer instructions need no private
fixture procedure, and domain skills continue routing to canonical lifecycle rules.

## Point 4 — Grouped immutable probes and bounded phase costs

PHP policy, tool exclusion and FPM configuration now share one read-only PHP-image
container, retaining separate processes, literal code arguments, first-failure
native exits and existing capability restrictions. Nginx keeps its own image/alias
context. Each verification invocation needs two rather than four probe containers;
runtime migration/Artisan cold execution is unchanged. A focused supported probe
diagnostic checks synthetic shell/PHP/FPM native failure propagation; broad image
and deployment acceptance remains scheduled for point 6.

Additive phase costs are retained in existing deployment release/progress records,
without new journal transitions, data behavior or telemetry storage. They measure
source preparation, image/dependency layers, image/config assertions, startup,
forward data, restart/health and optional endpoint assertions. Installer reports
copy the successful preparation attempt's costs, record preserved-cache/workload
conditions and count unique removed project survivors/image tags. Top-level scan,
deployment-scenario and cleanup phases remain separate; nested timings are not
summable and transient creation counts remain native-log evidence.

Production architecture and owner quality guides document the actual boundaries
and measurement limits. Both instruction products retain their existing lifecycle
rules; point 5 reconciles their selection hints. No dependency or topology change,
database reset or migration was introduced.

### Final matrix wiring (points 3–4 follow-up)

The explicit owner-only `fixture-acceptance` group schedules standalone production
and scanner qualification using their existing native entrypoint, in addition to
combined prerequisites. It is selected for this harness change, not pulled into
ordinary tooling/knowledge/production groups. This deliberately increases this
roadmap's validation workload; compare the ordinary combined stage separately
from qualification runs. Fresh run/source/command-bound producer/consumer contracts
also cover actual atomic evidence publication and producer failure. Certificate
fixture labels are included in exact project cleanup, including interruption.
The focused grouped-shell diagnostic completed 4 PASS (0.05 seconds of test time).

Source review before final verification identified and removed a prerequisite
publication race: completion is now durable before a dependent is spawned.
Shared evidence requires a clean candidate as well as exact HEAD/branch/run/graph.
Standalone scanner qualification retains per-fixture raw report directories so it
cannot overwrite combined evidence. Reports remain below `.runtime/security` and
native fixture ownership is validated before creating its workspace directory.
Authored deterministic spawn-time publication and report-path containment contracts.
The focused real-store producer/consumer diagnostic passed (one case, 0.13 seconds)
before these extra guards; the complete contracts remain part of final verification.

## Point 5 — Canonical execution guidance and instruction products

Owner AGENTS and customer AGENTS now give short selection hints before retrieval,
because blanket native/image selection can otherwise happen before the owning
guide is opened. Procedures remain in the canonical owner/public quality guides.
Owner security/Docker references and the customer security reference route to those
guides; the customer product contains no owner fixture procedure or platform cadence.
The public group table and command discovery agree on `security-source`.

Replaced obsolete separate-production-fixture claims in owner quality/deployment
guidance; synchronized testing discovery and the maintainer README active-roadmap
projection. Root README, workflows and skills-policy already describe affected-area
closure, final-only broad verification, strict selected security/AI checks and
mandatory baseline/hooks correctly; no duplicate workflow or new constitution rule
was added. Historical roadmaps/reports remain unchanged.

## Final verification preparation

Current-state source review on 2026-10-29 covered root README, maintainer README,
owner/public quality and command guides, owner testing/deployment guides, public
production architecture, owner roadmap/release workflows, root AGENTS, affected
owner domain references and customer AGENTS/security defaults. Findings were the
stale separate-fixture claims, missing public source-security table entry and active
roadmap projection; all are corrected before the gate. No environment/startup,
database, topology, dependency, module API/version or application presentation
contract changed. Additive deployment diagnostics and management group discovery
are development/operator tooling; Python implementation remains internal.

Final declared scope includes actual native installation/update/team/transport and
production/security because their harness/command/evidence contracts changed.
The extra fixture qualification runs are necessary for independent versus combined
behavior; they are excluded from the ordinary combined-path cost comparison.
Backend/frontend/browser/database/knowledge-runtime suites are excluded, never PASS.
The final checklist stays open and release 0.6.2 remains unfinalized until results,
comparison, cleanup, focused release checks and normal merge/publication complete.

### Exact preview and source review

Supported quality-plan preview resolves 47 nodes for the declared nine groups.
Graph fingerprint is
`b6a69241d8c66456e0697d26b54e3fece9e4b965231c8c39adfdd994a451ed62`.
Exact commands/reasons and structured graph are retained under
`.runtime/artifacts/RM-2026-015-quality-plan.{txt,json}`. The combined production
command includes healthy scanning and publishes shared evidence; the selected
scanner command verifies that completed evidence. Two additional native nodes
exercise standalone modes under the same production-simulation exclusion group.

Reviewed the 20 changed source-surface files against main, including literal command
arguments, run/source/graph binding, atomic phase publication, private report paths,
failure/interruption propagation, image process isolation and exact cleanup scope.
Report and content hashes are in
`.runtime/artifacts/RM-2026-015-security-review.md` and
`.runtime/security-review.json`. No blocking source finding remains; this is not
test/scanner PASS. Full selected results and the final measured comparison remain
pending. Source review also made optional evidence access explicit before final
execution; all code and guidance are committed before starting the background gate.

Host source-inspection imports created CPython bytecode outside the canonical
runtime tree. Relocated those generated caches without overwriting existing copies
into `.runtime/python/pycache`; the exact inventory is retained in
`.runtime/artifacts/RM-2026-015-bytecode-relocation.json`. Future ad hoc host
inspection disables bytecode generation. No persistent service/data/cache deletion
or Docker resource cleanup was needed during implementation.

## Point 6 — Initial gate repair

The first final run at 3bf3508d96d81ae285c5af01e75ec643105d2c5f finished
34 passed, five failed and eight blocked in 171.61 seconds. Original state/logs
are preserved at `.runtime/artifacts/RM-2026-015-failed-3bf3508d`.
Failures have three causes: the existing spawn-refusal contract expected the old
exact environment, strict Pyright rejected seven fixture/JSON/optional-field type
uses, and native installer startup refused legacy `.runtime/ownership` mode 775.
Both native installer nodes stopped before allocating scenario resources; their
dependent scan/qualification/release checks were blocked, not PASS.

Repair uses exact authoritative-environment assertions and validated JsonValue
conversion/explicit payload types, with no suppressions. New private installer
reports/workspaces use `.runtime/acceptance/installation`; legacy ownership evidence
and mode remain unchanged. A regression proves that private reports can coexist
with preserved group-writable legacy evidence. Owner quality/deployment procedures
are synchronized; this private path change does not alter customer APIs or require
new owner/customer instruction rules.

Complete repair Python unit/contracts acceptance passed 511 cases with three
intentional deselections in 33.21 seconds, cleanup exit 0; the native XML is copied
to `.runtime/artifacts/RM-2026-015-repair-python-pytest.xml`. Complete strict Python
analysis passed with zero errors/warnings; formatting and lint also passed.
Native combined production/scanning and complete installer/update/team/customer
knowledge acceptance both completed PASS. They use the documented native
entrypoint in the foreground and retain real failing scenario modes. The combined
repair run does not publish shared quality PASS from a dirty repair checkout;
exact coordinator publication is covered by the complete Python suite and must
run again in the restarted final graph after this repair is committed.

Native combined production/scanning repair completed PASS with 50 assertions and
all five phases (including cleanup) passing. Evidence is
`.runtime/acceptance/installation/installation-host-c88827e5cc.json`, with separate
raw scanner reports under `.runtime/security/installation-host-c88827e5cc`.
Scoped cleanup removed six production containers, two disposable volumes and 19
owned unused image tags, plus the synthetic SMTP container/network. Active
development/test identities/data, shared caches, backups and evidence were preserved;
the report records the independently proven natural queue lifetime rollover.
The full installation/update/team/customer-knowledge repair run completed exit 0,
77 assertions and all selected phases PASS. Its complete native log is retained
at `.runtime/artifacts/RM-2026-015-repair-installation.log`; atomic report:
`.runtime/acceptance/installation/installation-host-13844e5033.json`.
Preparation took 1167.443 seconds, healthy validation 13.725 seconds and cleanup
6.482 seconds. Cleanup removed 30 owned project containers, two project networks,
ten disposable fixture volumes and six unused task image tags, plus the synthetic
SMTP container/network. Active development/test data and identities, persistent
model/dependency/build caches, backups, historical ownership reports and all
verification evidence remain preserved. No natural queue rollover occurred in
this run. These repair runs qualify affected areas; they do not replace the new
complete final graph, which restarts from the beginning after this repair commit.

Source review of the repair confirms private installer state no longer traverses
the group-writable legacy directory; no chmod/adoption exception was introduced.
JsonValue conversions preserve scenario content, exact spawn assertions retain
literal transport and safe failure diagnostics, and optional-field accesses are
explicit. Root owner/customer instructions and skills need no new rule for this
internal path/type repair; owning quality/deployment guides are synchronized.

## Final report

RM-2026-015 finalizes maintenance release **0.6.2**, the approved PATCH from 0.6.1.
All implementation and repair points are committed. The complete restarted final
graph passed **47/47**, with no failed/blocked/interrupted steps, on candidate
`eee96c7109ca435ae2686dd0b2f67a40464964d5` in **3368.879 seconds**.
Requested groups are development, docs, fixture-acceptance, installation, production,
security, tooling, transport and updates; the exact declaration and rationale above
match recorded evidence. Graph fingerprint:
`b6a69241d8c66456e0697d26b54e3fece9e4b965231c8c39adfdd994a451ed62`.
Native logs/state remain in `.runtime/quality`; the final status snapshot is
`.runtime/artifacts/RM-2026-015-final-status.txt`. Shared producer/consumer evidence
is `.runtime/quality/production-fixture.json`; original failed evidence is preserved
separately. Backend/frontend/database/browser/knowledge-runtime groups were excluded,
not PASS; customer application tests inside installer acceptance retain their real
scope. No all-group milestone is due at 0.6.2.

### Measured costs and limits

| Comparable work on the final candidate | Seconds | Logged Compose creations | Logged one-off names |
| --- | ---: | ---: | ---: |
| Standalone production acceptance | 824.993 | 64 | 58 |
| Standalone scanner including its preparation | 379.190 | 21 | 16 |
| Separate modes combined | 1204.183 | 85 | 74 |
| Shared production/scanning plus evidence verification | 874.928 | 64 | 58 |
| Observed saving | 329.255 (27.3%) | 21 (24.7%) | 16 (21.6%) |

The shared producer itself took 874.716 seconds; its scanner evidence consumer
took 0.212 seconds. Native producer report
`.runtime/acceptance/installation/installation-host-aac93bcb5d.json` records
preparation 394.889s, healthy validation 12.987s, actual scan 9.925s, customer
deployment/failure scenarios 445.932s and cleanup 10.213s. Standalone reports are
`installation-host-b71ea44577.json` and `installation-host-bb84be5fe0.json` in the
same directory. Their preparations were 334.477s and 352.964s, and the standalone
scan itself took 10.115s. Setup elimination, rather than scanner acceleration,
explains the saving. Code and native policy acceptance also establish two instead
of four immutable image probe containers per verification call; those `docker run`
probes are not included in the Compose creation-name counts.

This is one candidate/host with sequential qualification and preserved caches,
not a controlled cold/warm benchmark or portable guarantee. The shared fixture ran
before standalone qualification; startup/health and cache variation remain material.
Against historical c929ecda production+scan cost 1223.313s, observed saving is
348.385s (28.5%), but that candidate/workload differs and its whole graph failed
unrelated checks. Full installation/update/team/customer knowledge cost rose from
historical 1037.401s to 1164.868s, so no installation-speed improvement is claimed.

The final graph intentionally adds 1204.183s of standalone qualification, yielding
3368.879s total (56m09s). Subtracting that extra work gives an arithmetic 2164.696s
(36m05s); it is not a separately executed ordinary graph or an all-group result.
Historical whole-run 2368.371s is not directly comparable because selection,
candidate and results differ. Structured measurements/limits are retained in
`.runtime/artifacts/RM-2026-015-performance.json`.

### Cleanup, documentation and release disposition

All four native installation/production fixtures record successful cleanup. Their
reports and current Docker inspection confirm no surviving owned project
containers/networks/volumes or source fixture workspaces. Reported project cleanup
totals: 47 containers, two networks, 16 disposable volumes and 47 owned image tags;
synthetic SMTP/certificate resources are additionally handled by exact inventory.
These counts exclude the separately reported host/management transport cleanup.
Exact report references and protected-resource dispositions are retained in
`.runtime/artifacts/RM-2026-015-final-cleanup.json`. Active development/test data,
Qdrant/model/dependency/build caches, rollback resources, backups, legacy ownership
reports and all verification evidence remain preserved. No global prune occurred.

The inserted repair point corrected initial contract/type/private-state failures
and completed the full affected-area repair loop before final graph restart.
Current-state documentation review and owner/customer instruction synchronization
are recorded above. Closure removes the active README claim, keeps application
version and independent module versions unchanged, and updates only approved
release metadata after PASS. No migration, topology/dependency change or real
production deployment occurred. Focused docs/release validation, merge commit,
main sanity, immutable annotated v0.6.2 tag and GitHub publication follow the
normal release workflow; their actual results are reported in chat without
rewriting this closed historical record.

# Local Quality System

The application intentionally has no CI/GitHub Actions pipeline.

Quality is enforced locally through:

- versioned Git hooks,
- `php artisan runa:quality:roadmap`,
- framework/tool commands,
- roadmap final verification.

`tools/application/quality.yml` is the canonical gate definition.

### `php artisan runa:quality:plan`

Preview explicit groups, or the legacy impact-area selection without flags.

Do not execute the full gate.

### `php artisan runa:quality:roadmap`

Run the complete gate required for the current roadmap.

### `php artisan runa:quality:final`

Run final closure verification. Validate and run the complete agreed final scope and its mandatory checks.

### `php artisan runa:quality:status`

Inspect the latest run.

While running:

- show the last 30 lines from every active parallel log,
- finish with `Process is still running.`

When finished or interrupted:

- show the last 30 lines from each relevant log,
- show full log paths,
- finish with the overall status.

## Manual group selection

The agent normally chooses proportionate groups using the roadmap, changed behavior
and relevant dependencies; the owner may steer it. No source-diff classifier chooses
tests. Use repeated `--group=NAME` or standalone `--all` on plan/roadmap/final.
Combining them, malformed arguments and unavailable groups fail before execution.
`--background` is supported only on roadmap/final. Status accepts no selection flags.
Flag-free calls retain their legacy base/impact/final behavior for compatibility.
Explicit plan/roadmap/final resolve the same mandatory checks and dependency graph.

| Group       | Delivered application checks                                                       |
| ----------- | ---------------------------------------------------------------------------------- |
| docs        | Documentation consistency (also mandatory)                                         |
| tooling     | Tooling diagnostics and shared Python setup/format/lint/strict typing              |
| backend     | App tests, PHP analysis, module source/version contracts, formatting/lint          |
| frontend    | Frontend build, types, tests, formatting/lint                                      |
| e2e         | Browser tests and frontend diagnostics; includes build/frontend-test prerequisites |
| database    | Application tests and database/migration/seeding diagnostics                       |
| development | Development and hook diagnostics                                                   |
| containers  | Existing Trivy container/repository security contract                              |
| security    | Dependency audits and Trivy                                                        |
| knowledge   | Knowledge doctor/evals and Boost                                                   |

The exact step matrix is in `tools/application/quality.yml`. Every explicit selection
includes configuration/templates, repository/roadmap state, documentation, secrets,
debug-residue and knowledge freshness. Commit hooks and protected-source safeguards
remain mandatory. Selected prerequisites are deduplicated and shown with inclusion
reasons. Heavy audits/scans and AI doctor/evals are strict when selected directly or
transitively. Excluded groups mean not run, never PASS. A selected-scope PASS differs
from all-group verification. Security/data-loss/production-isolation failures block.

Platform acquisition/updater/host-transport acceptance and production-fixture groups
are unavailable in customer projects. Do not substitute private platform harnesses;
use delivered application/deployment workflows for customer operations.

Host examples (inside the devcontainer use `php artisan`):

```sh
./bin/artisan runa:quality:plan --group=docs
./bin/artisan runa:quality:plan --group=docs --group=tooling
./bin/artisan runa:quality:plan --group=backend
./bin/artisan runa:quality:plan --group=frontend --group=e2e
./bin/artisan runa:quality:plan --group=containers
./bin/artisan runa:quality:plan --all
./bin/artisan runa:quality:final --all --background
./bin/artisan runa:quality:status
```

Before final verification, record `quality_groups: docs,backend` (comma-separated,
no spaces) or `quality_groups: all` and a nonempty `quality_rationale` string in the
application roadmap frontmatter. New roadmaps require this agreement. Final refuses
a mismatching selection; plan remains a nonexecuting preview. Closure on the roadmap
branch checks stored scope, graph/commands, complete results, branch and candidate
commit. Only reviewed application release metadata may follow PASS. An exploratory
run cannot replace the agreed final plan. Change the plan before changing scope,
then rerun its complete graph; no stale result reuse. State/status show the same
scope, exclusions, reasons, timings and logs. Final background execution ends the
agent turn; use the built-in status command later.

## Latest run only

Do not build a quality-history database/dashboard.

Persist only the latest local run under a gitignored directory such as:

`.runtime/application-quality/`

Starting a new run replaces the prior completed/interrupted run; reject a concurrent start while a run is still active.

## Parallel execution

Independent steps may run in parallel.

Steps that share mutable database/environment/migration/production-stack state must be sequenced safely.

Every parallel process has its own log.

## Timeouts

Do not impose arbitrary automatic timeouts on quality commands.

The user decides when a process is genuinely hung.

## Background behavior

Long final checks may run in the background.

When an agent starts a long final quality run, it must end its turn rather than poll continuously.

The user may later provide `php artisan runa:quality:status` or relevant full logs.

If the run is interrupted by crash/reboot/manual termination:

- record `interrupted`,
- show the last known step,
- final verification must rerun from the beginning.

## Severity

`hard_fail` blocks roadmap closure.

`warning` may proceed only with explicit justification in the final report.

The following categories cannot be downgraded merely to finish:

- security,
- migration/data-loss safety,
- production health/isolation.

## Debug residue

Temporary debug output is allowed during an active roadmap when useful.

Final verification rejects accidental residue including common forms such as:

- `var_dump`,
- `dd`,
- `dump`,
- stray `echo`,
- `print_r`,
- `console.log`,
- `console.debug`,
- `debugger`,
- temporary breakpoints/hacks,

unless the occurrence is intentionally permanent and justified. The debug checker recognizes only the exact reviewed native-error HTML rendering statement and browser-fixture JSON output statement at their specific paths. Other output/debug expressions in those files or identical text elsewhere still fail; isolated quality contracts verify this boundary.

## TODO/FIXME/HACK

Allowed during implementation.

Before roadmap closure:

- remove them, or
- link them to a real `MT-*` maintenance item or explicitly planned future roadmap.

Do not create fake maintenance backlog entries solely to make the quality gate green.

## Scratch files

Temporary scripts belong under a visible location such as:

`tools/scratch/`

Scratch source files must not be hidden by `.gitignore`; they must remain visible to repository review until removed or promoted.

Before roadmap closure:

- remove them, or
- promote them to a durable tool/test.

Generated runtime artifacts may be gitignored.

## Execution contract

`php artisan runa:quality:final --background` is the canonical long-running entrypoint. `roadmap --background` has the same background lifecycle. The process survives terminal closure. One run is active at a time; refuse another start while it is running so its state/logs cannot be overwritten. Replace the previous completed/interrupted run only on a new intentional start.

While running, status tails each process log and finishes with `Process is still running.` When finished or interrupted, tail each relevant log, show full-log paths and finish with the overall status. Preserve per-step results and the last known step locally. Do not add automatic runner/step deadlines; application network timeouts or separately designed job semantics are different concerns.

The runner uses tools/application/quality.yml. Independent checks may overlap; checks sharing mutable test/operation state use exclusive groups. Actual changed-surface security review requires content-bound findings/disposition, never a stub claiming reasoning occurred.

Commit coherent implementation/cleanup before final verification. The final checklist remains open during the gate; afterward finalize application manifest/changelog/closure evidence under [the release workflow](../workflows/releases.md), refresh knowledge and run focused checks. Substantive code/procedure changes invalidate prior PASS.

`php artisan runa:doctor:docs` validates required public documentation, application roadmap directories, placement and local links, including rejection of links to undelivered owner material. This supplements semantic source review and does not prove real deployment or remote release publication.

The gate selects application roadmaps under docs/app/roadmaps. Run runa:quality:plan before execution. Dependency/container scans and knowledge doctor/evaluation are strict when selected directly or as prerequisites; failures block closure.

## Stage timings

The shared quality runner records UTC start/finish timestamps and monotonic elapsed seconds for every started stage in its latest `state.json`. Python owner `quality-status` and application `runa:quality:status` show elapsed seconds for running/completed stages; the completed report also shows total elapsed time. Parallel stage durations overlap and must not be summed as total runtime. Pending/blocked stages have no execution duration; older reports without timing fields remain readable. Timings describe quality stages (such as backend tests or browser acceptance), not every assertion. Test-runner logs retain their own detailed timings. Manual groups do not change test bodies or add Playwright skip flags.

Knowledge CLI doctor/evaluation/freshness checks and development/hook diagnostics
that invoke that CLI share the knowledge-state exclusive group. Even read commands
hold the same exclusive CLI lock; scheduling them concurrently can produce a lock
refusal. Serialization retains strict checks and requires no automatic retry or
weaker freshness policy.

Quality planning, execution, state/status and evidence are shared services with
explicit application paths. The private owner planner and release rules are not
part of customer execution. See [Python tooling](../development/python-tooling.md)
for the delivered development configuration and application composition contract.

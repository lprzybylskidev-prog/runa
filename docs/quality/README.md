# Local Quality System

Runa intentionally has no CI/GitHub Actions pipeline.

Quality is enforced locally through:

- versioned Git hooks,
- `./bin/quality`,
- framework/tool commands,
- roadmap final verification.

`docs/quality/gates.yml` is the canonical gate definition.

## Modes

### `./bin/quality show`

Show the checks selected for the active roadmap based on its `impact_areas`.

Do not execute the full gate.

### `./bin/quality roadmap`

Run the complete gate required for the current roadmap.

### `./bin/quality final`

Run final closure verification. Semantically equivalent to the strictest applicable roadmap gate and release consistency checks.

### `./bin/quality status`

Inspect the latest run.

While running:

- show the last 30 lines from every active parallel log,
- finish with `Process is still running.`

When finished or interrupted:

- show the last 30 lines from each relevant log,
- show full log paths,
- finish with the overall status.

## Latest run only

Do not build a quality-history database/dashboard.

Persist only the latest local run under a gitignored directory such as:

`.runtime/quality/`

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

The user may later provide `./bin/quality status` or relevant full logs.

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

`./bin/quality final --background` is the canonical long-running entrypoint. `roadmap --background` has the same background lifecycle. The process survives terminal closure. One run is active at a time; refuse another start while it is running so its state/logs cannot be overwritten. Replace the previous completed/interrupted run only on a new intentional start.

While running, status tails each process log and finishes with `Process is still running.` When finished or interrupted, tail each relevant log, show full-log paths and finish with the overall status. Preserve per-step results and the last known step locally. Do not add automatic runner/step deadlines; application network timeouts or separately designed job semantics are different concerns.

The runner uses the dependency graph in `gates.yml`; independent nodes may run concurrently and shared-state checks are serialized. Production Compose/build/health/isolation and image scanning share `production-simulation`, matching their common production operation lock. A deterministic command may prepare evidence, but `review-changed-surface` also requires an actual agent review with findings/disposition. It cannot be a stub that claims human/agent reasoning occurred.

Before final verification, commit implementation/cleanup so the gate checks a coherent candidate. Its own checklist item is necessarily pending during execution. Version/changelog/closure metadata are finalized afterward using focused consistency checks, as defined in `docs/workflows/releases.md`. If substantive code/config/behavior changes occur after the gate, invalidate the result and run the full final gate again.

`doctor docs` checks documented public command names and the bounded README state contract defined in the release workflow. `doctor release` includes the same checks in every final gate, regardless of selected impact areas. Offline documentation/release/knowledge contracts run in the final contract step. These checks do not interpret capability prose, prove a real deployment or establish remote publication. The recorded source review in the roadmap workflow covers semantic consistency and historical context.

## Runner implementation

The runner executes at most four ready nodes concurrently, preserving definition order for scheduling. `exclusive_group` serializes all members; frontend unit tests also share the test wrapper's lock and therefore belong to `testing-state`. Dependencies are selected as a union, deduplicated by ID and validated for missing nodes, conflicts and cycles before execution. Optional dependencies apply only when both steps are selected. Final nodes wait for every selected base/impact node.

Commands execute as argument arrays without a shell. Missing commands fail with exit code 127; failed dependencies block their descendants while unrelated checks continue. Nonzero warning steps remain visible in evidence and block their dependents; warnings require release-report justification. Missing or unimplemented commands fail closed; command existence alone does not prove acceptance.

A stable OS file lock outside the latest-run directory prevents concurrent starts. State is replaced atomically and records the branch, commit, roadmap, impact areas, per-step status and last launched step. Background execution detaches from the terminal and inherits the lock. Signals terminate active command process groups and mark the run interrupted. If the coordinator crashes, status detects the released lock and records interruption; a new run refuses to overwrite evidence while recorded child processes still exist. Stop surviving children before restarting. A final run always starts from the beginning.

`./bin/quality-check debug-residue` checks tracked and untracked application/test PHP, JS, TS and Vue sources for debug calls, and rejects files in either visible scratch directory. CLI output in durable tooling is intentional and outside this application-source rule. The checker reports source locations without printing source payloads. Focused runner contracts: `python3 tools/testing/quality.py`.

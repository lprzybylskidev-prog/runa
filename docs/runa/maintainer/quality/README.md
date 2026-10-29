# Local Quality System

Runa intentionally has no CI/GitHub Actions pipeline.

Quality is enforced locally through:

- versioned Git hooks,
- `python3 tools/maintainer/quality-roadmap`,
- framework/tool commands,
- roadmap final verification.

`docs/runa/maintainer/quality/gates.yml` is the canonical gate definition.

## Modes

### `python3 tools/maintainer/quality-plan`

Preview explicit groups, or the legacy impact-area selection when no flags are supplied.

Do not execute the full gate.

### `python3 tools/maintainer/quality-roadmap`

Run the complete gate required for the current roadmap.

### `python3 tools/maintainer/quality-final`

Run final closure verification. Validate the agreed final scope and run its complete graph plus mandatory release checks.

### `python3 tools/maintainer/quality-status`

Inspect the latest run.

While running:

- show the last 30 lines from every active parallel log,
- finish with `Process is still running.`

When finished or interrupted:

- show the last 30 lines from each relevant log,
- show full log paths,
- finish with the overall status.

## Owner backend SIGSEGV disposition

The [owner testing policy](../testing/strategy.md#backend-sigsegv-owner-acceptance)
accepts backend test execution as PASS when a confirmed worker SIGSEGV/139 is its
only failure and guarded cleanup succeeds. Preserve the failed native/coordinator
reports and disclose the policy assessment separately. It does not waive any
other failed or unexecuted gate, including at all-group milestones. No retry loop,
automatic state rewrite or new command is introduced by this documentation policy.

## Manual group selection

Ordinary roadmap closure selects only changed behaviors/contracts and their real
prerequisites, plus the mandatory baseline/hooks. Each heavy native installation,
update, production, browser or image-scan selection needs a concrete affected
contract; shared tooling or security labels alone do not justify unrelated suites.
Knowledge ranking alone does not require complete installation/deployment. Use
`delivery` for changed source/projection/command/production-guard contracts and
`security-source` for dependency audits and content-bound source review. Neither
prepares a native installation or production fixture. Add broad groups when their
runtime behavior or images are affected; an excluded image scan is not PASS.

Platform-wide verification uses supported `--all` for release 0.10.0, then 0.15.0,
0.20.0 and subsequent 0.x.0 minor milestones divisible by five. The current 0.6.x
series is followed by the first scheduled checkpoint at 0.10.0. PATCH releases do
not count as five additional minor releases. A skipped checkpoint must be completed
before a later release is finalized. Always run full verification before 1.0.0,
including its readiness procedure; this does not authorize a major release.
Record milestone scope, exact candidate, graph, results and raw evidence in its
roadmap/final report. Scoped runs cannot satisfy an all-group checkpoint. Further
all-group runs remain possible with explicit justification or owner instruction.
This policy is manual release planning; automatic cadence enforcement and catalogue
changes are not claimed as implemented.

The agent normally selects proportionate groups from roadmap scope, changed behavior
and real dependencies. The owner can steer that choice. There is no Git-diff
classifier or automatic scope selection. Record the final choice and rationale in
roadmap frontmatter as `quality_groups: docs,tooling` (comma-separated, no spaces)
or `quality_groups: all`, with a nonempty `quality_rationale` string. This declaration
is required for new roadmaps; older flag-free workflows remain compatible.

Use repeatable `--group=NAME` or standalone `--all` on `quality-plan`,
`quality-roadmap` and `quality-final`. Do not combine them. `--background` applies
only to roadmap/final; status accepts no flags. Without selection flags, the legacy
base/impact-area selection (and final extras for final) remains unchanged.
Explicit plan/roadmap/final resolve the same graph. Final rejects a selection that
differs from the roadmap declaration. Plan lists exact commands, mandatory checks,
prerequisites with reasons and excluded groups without creating run evidence.

| Group           | Scope                                                                                                      |
| --------------- | ---------------------------------------------------------------------------------------------------------- |
| docs            | Documentation ownership source contracts                                                                   |
| tooling         | Quality coordinator contracts and tooling diagnostics                                                      |
| backend         | Owner backend tests, PHP analysis, module contracts, formatting/lint                                       |
| frontend        | Build, types, unit tests, formatting/lint                                                                  |
| e2e             | Browser and render diagnostics; includes build/unit prerequisites                                          |
| database        | Migration, constraints and seeding acceptance; includes backend tests                                      |
| development     | Dependency composition, startup, live development and hooks                                                |
| containers      | Real repository/image scan and assessed artifact disposition                                               |
| installation    | Acquisition/bootstrap/customer initialization and complete native acceptance                               |
| updates         | Preflight/execution and complete native installation/update acceptance                                     |
| transport       | Management/host executor source and native acceptance                                                      |
| production      | Production operation/source contracts and real isolated deployment acceptance                              |
| security        | Dependency audit, security contracts and changed-surface review; includes image scan                       |
| security-source | Dependency audit, security contracts and content-bound review; excludes image scanning                     |
| delivery        | Customer projection/instructions, command transport and production source/guard contracts; no native stack |
| knowledge       | Documentation live contracts, knowledge doctor/evals and Boost                                             |

The authoritative step matrix is `gates.yml` (`mandatory`, `groups`, `group_steps`).
Mandatory owner checks cover repository/release state, secrets, debug/scratch
residue, maintenance/docs consistency, source ownership and knowledge freshness.
Runtime configuration/template checks are genuine prerequisites. Existing hooks
remain mandatory. Heavy suites/audits/AI checks are required when selected directly
or transitively. Security/data-loss/production-health failures remain blocking.
An excluded group was not run; a selected-scope PASS never claims all-group coverage.

Representative previews (replace `quality-plan` with `quality-final` only after
implementation is committed and its final agreement is recorded):

```sh
python3 tools/maintainer/quality-plan --group=docs
python3 tools/maintainer/quality-plan --group=docs --group=tooling
python3 tools/maintainer/quality-plan --group=backend
python3 tools/maintainer/quality-plan --group=frontend --group=e2e
python3 tools/maintainer/quality-plan --group=installation --group=updates
python3 tools/maintainer/quality-plan --group=containers
python3 tools/maintainer/quality-plan --group=knowledge --group=delivery --group=security-source
python3 tools/maintainer/quality-plan --all
python3 tools/maintainer/quality-final --all --background
```

Installation/update groups retain full updater/team/customer-knowledge acceptance;
production retains actual data/security/isolation acceptance. `security` retains
real image scans and content-bound agent findings; `security-source` requires the
same audits/review but leaves images unexecuted. Source review waits for image
scanning when both are selected; its content binding does not itself require
building a production fixture. No lightweight tools/docs group
pulls these suites in merely because `impact_areas` includes tooling/security.

State/status retain requested/excluded groups, mandatory checks, prerequisite
reasons, graph fingerprint, branch/commit, rationale, timings and actual results.
Closure validates complete PASS against the agreed scope and unchanged candidate
(except reviewed release-only metadata). Missing, failed, interrupted, stale,
command-altered or narrower evidence blocks closure. Changing final scope requires
updating the roadmap and rerunning its entire selection from the beginning.

Customer catalogues use delivered application commands only; see the
[public quality guide](../../handbook/quality/README.md). Owner acquisition/update,
production-fixture and transport-acceptance groups are not customer capabilities.

## Latest run only

Do not build a quality-history database/dashboard.

Persist only the latest local run under a gitignored directory such as:

`.runtime/quality/`

Starting a new run replaces the prior completed/interrupted run; reject a concurrent start while a run is still active.

## Parallel execution

The owner-only `fixture-acceptance` group qualifies changes to the acceptance
harness itself: it runs the combined production/scanning prerequisites and fresh
standalone production-only and scan-only invocations. It deliberately adds native
work and is not a prerequisite of ordinary tooling/knowledge/delivery/production
selection. Use it for changed fixture composition or at an explicit all-group
checkpoint, not as a shortcut to measure an ordinary release's elapsed cost.

When both production acceptance and repository/image scanning are selected, the
source-owned `command_if_selected` alternatives compose them through one installer
invocation. The production node scans the healthy exact selected images before
deployment/failure scenarios; the scanner node then validates the persisted phases
instead of preparing a second fixture. Both selected node IDs/results remain in
the graph. Standalone selections keep their full original native workflows.
Resolved commands, alternatives and dependencies enter the graph fingerprint and
plan preview. Selected frontend preparation still precedes the shared scan.

The coordinator supplies a fresh run ID and the exact evidence directory/step to
children, overriding ambient values. Shared evidence under
`.runtime/quality/production-fixture.json` must match run ID, branch, candidate,
selection and command graph; it requires successful preparation, healthy validation,
scanning, deployment and cleanup with ordered timings and exact image IDs. A failed
producer blocks its scanner dependent; missing/stale/incomplete phase evidence
fails verification. This is private owner composition, not customer evidence reuse.

Installer native reports under `.runtime/ownership/installation-host-*.json` are
atomically updated before setup and at phase transitions. They retain the fixture
root, exact projects/SMTP/certificate ownership and interrupted/failed phase state
for recovery, including abrupt termination where the last phase remains RUNNING.
SIGTERM/interrupt attempts normal guarded cleanup. Successful fixtures are removed;
failed source context and all reports remain. No prior-run resource is reused.

Native installer reports retain cache/workload conditions, unique removed project
resource/tag counts and successful production preparation `phase_costs` from the
existing deployment journal. Those split source preparation, image/dependency
build, configuration/image assertions, startup, forward data and restart/health;
top-level phases separately measure preparation, healthy validation, scanning,
deployment scenarios and cleanup. These nested timings overlap and must not be
summed as independent stage costs. They describe the successful preparation attempt;
the outer preparation duration also includes intentional rejected/resumed attempts.
Auto-removed transient containers are counted from native creation logs during the
final comparison, not represented as removed project survivors in the JSON counts.

Independent steps may run in parallel.

Steps that share mutable database/environment/migration/production-stack state must be sequenced safely.

Every parallel process has its own log.

Owner browser and render diagnostics both run through their native host entrypoints
and own a separate journaled test endpoint lifecycle. Render diagnostics must not
reuse an endpoint already cleaned up by the preceding browser suite. Existing
manual endpoints remain externally owned.

Owner parallel backend tests use the standard ParaTest worker lifecycle. Temporary
native-crash process-isolation overrides and debugger shims are absent.
Owner Core fixtures remove their own settings/transition history along with their
owned schemas and registry identities. Cleanup requires a guarded disposable
worker and an exact Core identity; foreign histories and the base database remain
protected. Process exit does not prove PostgreSQL has released a dead client's
session lock: recovery fixtures synchronize with the actual lock release before
invoking recovery once.

## Timeouts

Do not impose arbitrary automatic timeouts on quality commands.

The user decides when a process is genuinely hung.

## Background behavior

Long final checks may run in the background.

When an agent starts a long final quality run, it must end its turn rather than poll continuously.

The user may later provide `python3 tools/maintainer/quality-status` or relevant full logs.

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

`python3 tools/maintainer/quality-final --background` is the canonical long-running entrypoint. `roadmap --background` has the same background lifecycle. The process survives terminal closure. One run is active at a time; refuse another start while it is running so its state/logs cannot be overwritten. Replace the previous completed/interrupted run only on a new intentional start.

While running, status tails each process log and finishes with `Process is still running.` When finished or interrupted, tail each relevant log, show full-log paths and finish with the overall status. Preserve per-step results and the last known step locally. Do not add automatic runner/step deadlines; application network timeouts or separately designed job semantics are different concerns.

The runner uses the dependency graph in `gates.yml`; independent nodes may run concurrently and shared-state checks are serialized. Production fixture build/health/isolation and image scanning share `production-simulation`, serializing the host acceptance resources while each customer fixture retains its own operation lock. A deterministic command may prepare evidence, but `review-changed-surface` also requires an actual agent review with findings/disposition. It cannot be a stub that claims human/agent reasoning occurred.

Before final verification, commit implementation/cleanup so the gate checks a coherent candidate. Its own checklist item is necessarily pending during execution. Version/changelog/closure metadata are finalized afterward using focused consistency checks, as defined in `docs/runa/maintainer/workflows/releases.md`. If substantive code/config/behavior changes occur after the gate, invalidate the result and run the full final gate again.

`doctor docs` checks documented public command names and the bounded README state contract defined in the release workflow. `doctor release` includes the same checks in every final gate, regardless of selected impact areas. Offline documentation/release/knowledge contracts run in the final contract step. These checks do not interpret capability prose, prove a real deployment or establish remote publication. The recorded source review in the roadmap workflow covers semantic consistency and historical context.

## Runner implementation

The runner executes at most four ready nodes concurrently, preserving definition order for scheduling. `exclusive_group` serializes all members; frontend unit tests also share the test wrapper's lock and therefore belong to `testing-state`. Dependencies are selected as a union, deduplicated by ID and validated for missing nodes, conflicts and cycles before execution. Optional dependencies apply only when both steps are selected. The repository/container scan waits for selected production/frontend builds and frontend render diagnostics (which depend on browser tests): frontend builds replace generated native-error output, and browser tests replace prior report output. Scanning these writers concurrently can encounter disappearing files. Generated output remains scanned; no vulnerability or secret check is excluded. Final nodes wait for every selected base/impact node.

Commands execute as argument arrays without a shell. Missing commands fail with exit code 127; failed dependencies block their descendants while unrelated checks continue. Nonzero warning steps remain visible in evidence and block their dependents; warnings require release-report justification. Missing or unimplemented commands fail closed; command existence alone does not prove acceptance.

A stable OS file lock outside the latest-run directory prevents concurrent starts. State is replaced atomically and records the branch, commit, roadmap, impact areas, per-step status and last launched step. Background execution detaches from the terminal and inherits the lock. Signals terminate active command process groups and mark the run interrupted. If the coordinator crashes, status detects the released lock and records interruption; a new run refuses to overwrite evidence while recorded child processes still exist. Stop surviving children before restarting. A final run always starts from the beginning.

`./bin/artisan runa:check:debug-residue` checks tracked and untracked application/test PHP, JS, TS and Vue sources for debug calls, and rejects files in either visible scratch directory. CLI output in durable tooling is intentional and outside this application-source rule. The checker reports source locations without printing source payloads. Private scheduler/evidence contracts use `python3 tools/maintainer/test-python --scope=contracts --filter=test_quality_`; obsolete private filename launchers are removed.

## Owner test routing

Owner database-constraints and seeding-contract gates use the dedicated maintainer backend runner with Database and Seeding filters in disposable parallel workers. The database-migrations native guard entrypoint delegates its full acceptance to DatabaseTest in one disposable worker under the same suite lock/cleanup; it never resets the persistent base runa_test. Destructive DatabaseOwnership fixtures refuse the base before any mutation. The selected assertions, prerequisites and hard-failure policy are unchanged. Customer runa:doctor:database/seeding commands continue to select application tests only. The standalone platform browser document process loads the private test bootstrap, including its fixture autoloader; it does not add private fixtures to customer Composer autoloading.

## Production fixture routing

Owner production gates never target the maintenance checkout or customer development. production-acceptance runs the native installation harness with --production-only --customer-deployment; repository-container-scan runs --production-only --security-scan. Each creates a separate complete customer repository and locally prepared production role, builds exact images and tests actual health/AI isolation. Security scans retain the owner repository scope while resolving images from the customer fixture and auditing owner development locks. Fresh TLS/SMTP rejection/resume and scoped cleanup are part of both runs. Selected frontend artifact preparation precedes repository scanning. See [production acceptance](../production/deployment.md).

## Security source scope and additional-service fixture

Repository Trivy scanning excludes generated dependency trees at root vendor/node_modules and relocated src/vendor/src/node_modules, along with .git/.runtime/.pnpm-store. It retains first-party source/configuration, Dockerfiles and composed committed src/composer.lock/src/pnpm-lock.yaml, including development dependencies. Dependency fixture Dockerfiles and nested package locks are not first-party delivery inputs. The same exclusions apply to native and containerized scanners.

Real customer customization/update/team/deployment acceptance uses the shared immutable fixture in tools/maintainer/service_fixtures.py: Redis 8.4.7 Alpine 3.22 at sha256:0514fa59e3d84e2f3be66731c5401fc2a85b8ea71b49c0116c850f6a558076f4. The helper fetches that exact artifact when absent and rejects inconsistent local identity. The owner security gate scans it alongside the actual production images; a failing fixture scan remains blocking. Redis runs only in disposable test projects, with non-root user, read-only filesystem, dropped capabilities and no published port. It is absent from baseline development/production services and is not an application dependency.

Runtime scans use strict owner/customer behavior without PHP, gosu, Brotli or curl
exception callbacks. Historical acceptance policies are retired. The actual
PostgreSQL derivative and PHP/Nginx images must satisfy the same unfiltered scan;
see [runtime security](../../handbook/security/postgres-gosu-policy.md).

Native updater acceptance derives its original release tag from the installed platform manifest and synthesizes a strictly newer patch target. Never fix these tags to historical release numbers: the original independently downloaded source must match the installed protected tree before target migration/seed changes are introduced. Production rejection remains before release acquisition; source-integrity checks are unchanged.

## Stage timings

The shared quality runner records UTC start/finish timestamps and monotonic elapsed seconds for every started stage in its latest `state.json`. Python owner `quality-status` and application `runa:quality:status` show elapsed seconds for running/completed stages; the completed report also shows total elapsed time. Parallel stage durations overlap and must not be summed as total runtime. Pending/blocked stages have no execution duration; older reports without timing fields remain readable. Timings describe quality stages (such as backend tests or browser acceptance), not every assertion. Test-runner logs retain their own detailed timings. Manual group selection does not change individual test bodies or add Playwright skip flags.

Native host-executor acceptance preserves exact development/test container IDs and
start times. The established queue lifetime exception requires one restart of the
same queue container, a running healthy state, and exactly one logged
`Worker STOPPED Maximum run time exceeded` during observation. Other services,
multiple restarts, missing/other exit evidence and unhealthy states still fail.
This matches native installation acceptance; it does not waive crashes or change
queue configuration.

Native installation acceptance protects persistent development and manually owned
test containers belonging to this checkout. Containers carrying the automatic
runa.testing.run label belong to the concurrent browser runner and its recovery
journal; they are excluded from the persistent-service snapshot. Their own runner
continues to verify exact ownership and cleanup. Missing or restarted persistent
services still fail the installation preservation check.

Knowledge CLI doctor/evaluation/freshness checks and development/hook diagnostics
that invoke that CLI share the knowledge-state exclusive group. CLI readers share admission while writers remain exclusive. The quality graph
retains conservative serialization because live acceptance also indexes temporary
state. This does not change reader concurrency or introduce retries or weaker
freshness policy.

## Python tooling group

Both explicit `tooling` and legacy tooling impact selection include frozen Python
setup, Ruff format/lint, strict Pyright, unit/contracts and separate wheel/package
contracts. Setup is a prerequisite, all nodes retain hard failure and share
`testing-state` ownership with native suites. Commands, scope filters, markers,
reports and physical projection boundaries are documented in the
[Python owner workflow](../tooling/python-rebuild/toolchain.md). These checks run
in final verification; migration points do not claim the full Python scope passes.
Customer quality includes the delivered frozen Python development infrastructure
and application analysis commands. Owner suites remain private. Python setup is
a real prerequisite of migrated owner scheduler/security contracts; those nodes
share `testing-state` with the other native suites.

Owner composition passes private roadmap/gate/state paths to the shared quality
services. No owner code patches imported application modules. Release-only allowed
paths belong to the private release validator. Owner `.githooks` select the private
history policy only in receipt-free maintenance context; customer snapshots use
shared staged checks. The RM-010 first-closure exception and the completed
2026-10-22 version-backfill transition are retired; exact historical hashes remain
protected and historical bytes/decisions are retained.

## Shared aggregate tests and private suite discovery

`python3 tools/maintainer/test` runs Runa backend/frontend/tooling by default.
Repeated `--scope=backend|frontend|tooling|e2e` and `--filter=TEXT` select scopes;
native arguments require one scope. Focused `test-backend`, `test-frontend` and
`test-e2e` retain their native engines. `test-python --scope=unit|contracts|integration|e2e`
selects private pytest directories and accepts `--path`, `--filter`, `--marker`
and `--collect-only`. Owner commands delegate to the configured app container;
no host Python development install is required. E2E endpoints retain host-owned
journals. A customer receipt refuses owner test commands, even if private source
was copied there. Public Artisan always composes application ownership.

The shared delivered `TestService` owns one suite lock, process-group lifecycle,
worker cleanup and honest scope reporting. Owner results live in
`.runtime/tests/runa-results.json` and Python JUnit in
`.runtime/python/reports/runa-pytest.xml`. All independent scopes run after a
failure; focused runs return native statuses, aggregates return 1 on failure or
130 on interruption. Empty owner suites are failures. The command does not run
the release quality graph. Final gates retain explicit focused suite nodes and
resource exclusivity; ordinary aggregate commands are developer workflows.

Private Python cases use importable `test_*.py` files under
`tests/tools/runa/{unit,contracts,integration,e2e}`. Useful unittest
assertions remain collected by pytest. Private fixture objects live in `support`,
without inheriting other suites. Shared customer fakes/workspace helpers ship
under `tools/application/testing/support`; PHP and browser helpers under
`src/runa/Testing` remain delivered development support. Platform cases/fixtures
and native acceptance harnesses stay private. Native Docker acceptance modules
run on the host through their supported quality nodes; pytest live-service cases
have explicit registered markers/prerequisites. No unit fixture starts services.
The private browser harness prepares Core descriptors and forward-only updates in
the guarded testing database before serving requests. It retains application data
and uses the actual checkout commit; customer browser runners never load this fixture.
The scenario map is [test migration](../tooling/python-rebuild/test-migration.json).

The owner PHP analysis gate passes `--configuration=../tools/maintainer/phpstan.neon`.
It includes the delivered maximum-level application rules, every platform source
root and the private Auditable model consumer fixture. The delivered `src/phpstan.neon`
selects application source; it never references an excluded private fixture. Trait
analysis is exercised through the real fixture used by Audit runtime regressions.

## Public boundary and projection evidence

The existing php-static-analysis node invokes runa:analyse, including public PHP
imports/signatures before PHPStan. The lint node owns customer TS/Vue imports,
frontend-typecheck owns actual exported/consumer types, and python-tests includes
the audience/placement/generated/update-preservation contracts. Installation-host
acceptance authors customer PHP/Vue/Python cases without owner files. Production
acceptance and repository-container-scan use real runtime absence/retained-identity
checks and the operator module diagnostic. See the [consumer matrix](../architecture/api-boundary/README.md#public-consumer-and-final-quality-matrix).
All retain existing prerequisites/severity/resource ownership; no duplicated node
or earlier implementation gate is needed. RM-2026-012 final selection remains all.

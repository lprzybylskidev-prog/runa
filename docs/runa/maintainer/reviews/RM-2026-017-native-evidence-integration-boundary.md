# RM-2026-017 — Native worker evidence integration boundary

## Status and scope — 2026-11-06

Point 9 remains open. This source review follows the owner's direction to correct
the owning Runa integration without adapters, decorators, startup shims or
compatibility workarounds. It does not revive the
[superseded startup proposal](RM-2026-017-native-evidence-adapter-design.md).
No implementation, dependency change or revised architecture is approved by this
record. D-01–D-03 remain required; D-02 is not silently deferred.

The checked-in lock and installed source identify Pest v5.3.4
(`159514bacefb30fd674aa74c52738f8a535bfaa9`), ParaTest v7.27.4
(`6d73b615611a2da7af85637d99d1b0efac9fcd00`) and Symfony Process v8.1.9
(`59a682fd049b2d4d37c444f5ac21b7d01b8c7189`). Findings below concern these
installed versions; they make no claim about other published versions.

## Verified source boundary

| Source under `src/vendor`                                                                                           | Observed contract                                                                                                                                                                                                            | Consequence                                                                                                                                                                                                                       |
| ------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `pestphp/pest/src/Plugins/Parallel/Handlers/Parallel.php::handleArguments` and `Handlers/Laravel.php::ensureRunner` | Pest selects its wrapper, then Laravel's parallel runner; Laravel's resolver constructs Pest's wrapper.                                                                                                                      | Passing a different `--runner` through the existing Pest command does not establish Runa ownership.                                                                                                                               |
| `pestphp/pest/src/Plugins/Parallel/Paratest/WrapperRunner.php::run`                                                 | The final wrapper seals the parent PHPUnit facade before constructing SuiteLoader.                                                                                                                                           | Configured extension registration happens too late for parent discovery tracing; existing worker events remain usable.                                                                                                            |
| `brianium/paratest/src/WrapperRunner/SuiteLoader.php::__construct`                                                  | Bootstrap and extension registration precede suite building/filtering, but follow the Pest wrapper's seal.                                                                                                                   | Removing Runa's sealed-facade catch cannot repair that ordering.                                                                                                                                                                  |
| `pestphp/pest/src/Plugins/Parallel/Paratest/WrapperRunner.php::assignAllPendingTests` and `waitForAllToFinish`      | The wrapper owns private worker objects and throws their crash exceptions.                                                                                                                                                   | Runa's outer TestService observes the native parent return, not each worker's OS termination.                                                                                                                                     |
| `brianium/paratest/src/WrapperRunner/WorkerCrashedException.php::fromProcess`                                       | The final internal exception formats the process exit, assignment, environment and output into its message, then constructs `new self($error, 0, $previousException)`. It retains no typed worker/process/signal properties. | Catching the actual exception would still supply no structured exit or worker identity. Its exception code is 0, not the child exit. Parsing its message is explicitly disallowed and risks copying sensitive environment/output. |
| `brianium/paratest/src/WrapperRunner/WrapperWorker.php::getExitCode` and `isFree`                                   | The public exit accessor returns the cached last test-status byte. The Symfony Process and current assignment are private.                                                                                                   | This accessor is not an OS crash/signal getter. In any event, the wrapper's worker collection is private.                                                                                                                         |
| `brianium/paratest/src/RunnerInterface.php`                                                                         | The runner contract exposes `run(): int`; the inspected Options has no worker lifecycle observer.                                                                                                                            | Implementing a delegating RunnerInterface cannot recover information that the delegated engine does not expose.                                                                                                                   |

The earlier proposal's condition, "if the installed API cannot expose reliable
signal/worker data ... mark it unknown", is now resolved for this exception seam:
the structured data is absent. A startup rewrite alone cannot make it available.
This conclusion follows from source inspection, not a new live crash experiment.
No SIGSEGV was induced and no root-cause investigation occurred.

## Concrete architecture choices for owner discussion

### A — Retain native scheduling and obtain an upstream evidence contract

Keep TestService as the lock, selection, report, cleanup and owner-policy boundary;
keep Pest/ParaTest responsible for scheduling, workers and native results. First
assess whether a released upstream version exposes suitable supported lifecycle
data. If none does, an upstream change would be needed before this route can be
implemented. No suitable release or upstream acceptance has been established.

The required contract must expose selected-case discovery before worker launch
and termination records containing worker token, PID, native exit/signal and the
assigned selection. Exact interrupted case identity still comes from ordered
worker events; an assignment can contain several tests and is not automatically
an exact case. Parent extension registration must precede facade sealing.
Reports must remain available on exceptions, with normal cleanup and native
failure semantics. Runa would consume that supported contract directly and
replace its obsolete integration path, without a catch-only startup decorator.

This route requires dependency research and a separately reviewed exact change:
activity, license, security history, compatibility, tree/size and overlap, followed
by explicit owner approval before changing Composer inputs. No vendor edit,
runtime monkey patch, dependency fork or dependency update is authorized here.
No upstream issue/message is sent without explicit instructions.

### B — Let the owning Runa test lifecycle supervise native worker processes

Restructure the existing TestService so it directly owns worker launch, wait status
and cleanup, while each worker still executes tests through native Pest/PHPUnit.
Replace the old parallel path rather than adding a second selectable scheduler.
The direct child boundary can supply real exit/signal information without parsing
console output. Discovery and dataset filtering, file-level grouping, Laravel
parallel callbacks, database tokens, result aggregation, stop policies, reports,
coverage and interruption cleanup all need an explicit design and parity review.
Shared mechanics must remain neutral; only owner composition performs D-02 recovery.

This changes who schedules tests and what the recorded parallel mode means. It is
an architectural amendment to the roadmap's explicit "no new runner" and
"preserve selected native execution mode" boundaries, even if implemented inside
TestService. Pre-1.0 authorization to change Runa APIs does not silently approve
this amendment. No such scheduler is implemented by this review.

## Decision and verification boundary

The owner must select the dependency-contract route or explicitly amend scheduling
ownership before dependent implementation. Neither route is approved by elapsed
time, D-02's high-level approval or the instruction to avoid adapters. The existing
manual confirmed-SIGSEGV exception is unchanged; unconfirmed worker events cannot
be accepted under it. Ordinary failures remain blocking.

Only focused source inspection and current local knowledge status/search ran in
this continuation. No test suite, quality campaign, scanner, image build, database
operation or resource cleanup ran. Existing reports and data remain preserved.
Owner/customer instruction products need no edit: this records an unresolved
owner engineering premise and introduces no reusable rule or delivered capability.

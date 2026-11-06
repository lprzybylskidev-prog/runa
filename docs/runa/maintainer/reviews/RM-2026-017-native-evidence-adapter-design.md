# RM-2026-017 — Native parallel crash evidence adapter

## Status and demonstrated constraint

**Proposed; owner decision required before changing native startup.** Point 9
remains open. D-02 approved bounded recovery through the existing engine; it did
not approve an alternate backend engine, duplicated scheduling or vendor patch.
Root AGENTS requires discussion when implementation reveals a significant changed
architectural/behavioral premise.

Installed sources inspected: Pest 5.3.4, its parallel handlers and native wrapper;
ParaTest's Options/RunnerInterface/SuiteLoader/WorkerCrashedException;
Laravel's ParallelRunner/RunsInParallel; PHPUnit's Extension/Tracer and lifecycle
value objects. No package was installed or updated.

Pest's parallel argument handlers overwrite a supplied `--runner` and select
Laravel ParallelRunner with Pest's own native WrapperRunner. The native Pest
wrapper seals PHPUnit's parent event facade before SuiteLoader bootstraps configured
extensions. Attempting a normal extension tracer there yields a sealed-facade
error; the corresponding diagnostic had a passing selected test but native exit 1
and is retained as failed evidence. A custom `--runner` argument therefore cannot
supply the intended transparent catch adapter on this entrypoint.

Workers do support the configured PHPUnit extension. After explicitly recording
that parent tracing is unavailable and avoiding that expected sealed registration,
the same supported native diagnostic passes: **1 test, 5 assertions, parent exit 0,
cleanup 0**, with separate worker preparation/PASS/completion events and a native
JUnit report. It does not prove a parent signal/case association. No SIGSEGV was
induced and no root-cause investigation occurred.

Evidence remains under `.runtime/tests/backend/runa/`:
`de7989c5d4e34dfb8ad22de099e9a4d8` and the subsequent failed diagnostic retain
native exit 1; `39e0702d84554217a97bc2d79a8b6a4b` contains the passing corrected
observation. Native results have not been rewritten. Worker databases were removed
by the supported cleanup; base test/development data remain.

A console string mentioning 139, the last displayed case or an unfinished worker
alone cannot establish the actual crashed worker. Neither broad regex acceptance
nor interpreting every absent completion as a crash is acceptable. Existing
standing manual owner disposition remains available only for confirmed native
SIGSEGV evidence; this checkpoint grants no new automatic acceptance.

## Proposed implementation for approval

Keep TestService, its supported owner command, selection, suite lock, worker guards,
cleanup and native return codes. Add a small development-only PHP **startup adapter**
for the installed native Pest/ParaTest path. It would:

1. Parse only the existing supported backend command arguments and bind the exact
   testing owner/config/test directory/mode/worker count. Retain literal argv and
   the existing testing environment, without printing secrets.
2. Bootstrap the existing Pest kernel and its ordinary worker/parallel integrations,
   then compose a catch-only decorator at the native parallel dispatch boundary.
   Delegate every test discovery, scheduling, worker creation, assertion and result
   calculation to the existing native engine. No copied WrapperRunner scheduling,
   vendor file edit, Reflection access to private worker state or new dependency.
3. Persist complete selected-case discovery before workers run. Use native
   case/filter identities including datasets. Per-worker PHPUnit events remain the
   source of actual preparation/outcome/completion, not progress text.
4. Catch only the actual typed WorkerCrashedException from native dispatch and
   record its supported native exit/worker assignment information. Bind that
   machine record to the attempt and worker event stream. If the installed API
   cannot expose reliable signal/worker data without parsing payload text, mark
   it unknown and refuse automatic crash acceptance. Do not silently add private
   reflection, message heuristics or vendor patches to rescue attribution.
5. Rethrow/return exactly the native failure after flushing evidence. The existing
   bounded owner recovery and separate disposition consume these records later;
   they never replace the original failed result.

The implementation must establish the exact supported composition seam before
adding the adapter to normal commands. Tiny native success/assertion/skip/dataset
fixtures and synthetic typed-crash fixtures demonstrate parity and source/version
binding. Actual crashes need not be induced. A mismatch blocks recovery. The
adapter is a source-owned testing transport, excluded from production, not a new
application service or customer crash-tolerance policy.

This is an explicit adjustment to the earlier assumption that an ordinary PHPUnit
extension plus `--runner` decoration can observe the existing Pest parent. It may
require a deliberately version-bound startup composition instead. Approval would
authorize developing that seam, not bypassing any refusal if it proves unavailable.

## Alternative within the unchanged startup boundary

Retain the current native launch and neutral worker events, add separate supported
selected-case discovery, and expose only coverage plus explicit unknown crash
attribution. No automatic supplemental SIGSEGV retry or owner-disposition result
would be claimed for the unavailable signal path. Complete validated quality
resumption independently. This preserves the startup constraint, but **defers part
of approved D-02**, so the owner must explicitly accept that scope change; the
agent cannot close point 9 or the roadmap by silently selecting it.

## Existing safe work and decision boundary

Campaign input/archive/resume foundations are committed at `777b07b9`; point 9
remains incomplete. Neutral test-event capture and immutable per-attempt native
reports are a separate checkpoint, with no automatic recovery enabled. Both owner
and application entrypoints retain native failure behavior. The experimental
unused runner decorator was removed rather than shipping an ineffective override.

The requested decision is whether to authorize the constrained native startup
adapter above, or retain startup and defer the unavailable automatic D-02 portion.
No release, full suite, image build, reset, dependency or policy exception follows.

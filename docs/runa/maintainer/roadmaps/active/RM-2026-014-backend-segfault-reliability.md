---
id: RM-2026-014
title: Backend test segfault diagnosis and reliability
status: active
branch: roadmap/RM-2026-014-backend-segfault-reliability
created_at: 2026-10-28
activated_at: 2026-10-29
closed_at: null
release_type: fix
proposed_version: 0.6.2
quality_groups: backend,tooling,development,containers,security
quality_rationale: Diagnose intermittent PHP backend worker crashes and their execution environment, verify supported runner lifecycle and actual parallel backend execution, and retain security and mandatory prerequisites. Reconcile additional groups with the established root cause before final verification.
impact_areas:
  - backend
  - tooling
  - development
  - documentation
---

# RM-2026-014 — Backend test segfault diagnosis and reliability

## Overview

Diagnose and correct intermittent PHP worker SIGSEGV/exit 139 during supported
backend testing, including failures seen only in the full quality workload.
Preserve native failure reporting, test assertions, isolation and cleanup.

The owner explicitly requested a fix roadmap targeting **0.6.2**, after
[RM-2026-013](../closed/RM-2026-013-knowledge-retrieval-reliability.md) maintenance 0.6.1,
on 2026-10-28. This is a PATCH increment from released predecessor v0.6.1.
The owner explicitly activates this roadmap on 2026-10-29. VERSION remains
0.6.1 until verified fix release 0.6.2.

Scope includes PHP/runtime/extensions, Pest/ParaTest and supported transports,
Docker resources, test concurrency/order, evidence collection and the temporary
owner acceptance exception. The cause is unknown; Docker is a hypothesis, not
an established fault. No business behavior redesign, CI, new coordinator,
unapproved dependency or destructive data reset is authorized.

## Specification

### Decisions

- 2026-10-29 — Owner explicitly extends point 1 to temporary installation of gdb
  in the existing development container and narrow native-stack diagnostics.
  This permits tracing the official reproducer and a focused historical failure
  area through the supported runner. It does not authorize PHP pin/configuration
  changes, a rebuild, full-suite repetition or data deletion. Retain raw evidence
  and remove the temporary debugger/packages after their last diagnostic use.

- 2026-10-29 — Owner authorizes activation and execution after released RM-013.
  Retain confirmed fix 0.6.2. Owner also adopts affected-area verification for
  ordinary closures and periodic all-group verification at minor milestones
  0.10.0, 0.15.0, 0.20.0 onward, plus mandatory pre-1.0.0 readiness. PATCH
  releases do not advance this minor-version cadence. Record this policy in both
  instruction products and canonical workflow guides without adding runtime gate
  machinery; RM-015 retains catalogue/fixture optimization implementation.

- 2026-10-28 — Owner selects release_type fix and proposed_version 0.6.2, after
  RM-013, and defers backend segfault diagnosis until then.
- 2026-10-28 — Owner supplies a ChatGPT research lead about php-src#24488 and
  requests planning only. Record it as an unverified hypothesis for point 1;
  RM-013 knowledge/Qdrant work remains first. No investigation, reproducer,
  runtime change or RM-014 activation is authorized by this planning update.
- 2026-10-28 — Until this roadmap resolves the fault, the owner permits acceptance
  of backend test segfaults as a disclosed exception, using the recorded complete
  backend PASS as supporting evidence. This is owner release policy only, not a
  customer permission, runtime recovery mechanism or conversion of FAILED into
  actual PASS. Assertion failures, cleanup failures, other crashes, security
  failures and unexecuted dependent gates are outside this exception.
- Preserve raw failed and successful reports. Do not change suite exits, fabricate
  coordinator state, silently retry indefinitely or treat a restart as root-cause
  proof. The public nondestructive restart procedure remains applicable.
- Remove this temporary acceptance exception after the corrected replacement
  passes final verification here. Remove exception-only policy/flags/tests if any
  were subsequently introduced, preserving historical evidence.

### Existing evidence and limits

RM-012 candidates bc741df4 and 36fd9d19 failed with exit 139 at PermissionsTest.php
and AuthEntryTest.php respectively. Between them, restarting all 11 runa-dev
containers and passing development doctor was followed by a complete four-worker
backend PASS: 588 tests, 6117 assertions, 219.10 seconds, cleanup 0. No PHP source
changed between these runs. The later full workload crashed again; the sequence
establishes intermittent execution failure, not its cause. Failed logs remain in
.runtime/artifacts/RM-2026-012-failed-bc741df4 and
.runtime/artifacts/RM-2026-012-failed-36fd9d19. RM-012 also had an unrelated browser
geometry assertion failure, which is not waived by this decision.

Require actionable crash evidence and a supported correction before closure.
Record exact versions, execution mode, concurrency, relevant resource state and
reproduction conditions without secrets. Use narrow diagnostics to distinguish
PHP/extension faults, test-order/global state and resource/concurrency effects.
Keep application toolchains in containers and preserve data, caches and reports.
Changes to dependency pins require the existing deliberate review/approval policy.

### Prioritized research lead: PHP garbage collection and SimpleXML

Owner-supplied external analysis points to
[php-src#24488](https://github.com/php/php-src/issues/24488), described as
"GC double-frees the SimpleXMLElement properties table", and suggests a PHP
8.4.27 regression. The issue, affected versions, necessary conditions, links to
Laravel/Pest/ParaTest and fix availability were **not independently verified**
when this draft was agreed. The activation investigation below records verified
findings; historical suite causation remains unproven. The lead itself does not
authorize a PHP update/rollback.

After activation, use this bounded diagnostic sequence:

1. Read the official issue, linked patch and release records. Establish the exact
   mechanism, affected/fixed versions and required object/GC conditions. Check
   other officially documented 8.4.27 regressions only where symptoms justify it.
2. Trace whether those conditions are reachable in Runa or installed dependencies:
   direct/indirect SimpleXML use, cyclic references, object introspection or
   serialization, and GC interaction with Mockery/Laravel/Pest. SimpleXML's mere
   presence does not establish reachability of the faulty path.
3. If an official minimal reproducer exists, inspect it before executing it as a
   focused diagnostic in the existing container. It must require no database,
   source/test edits, configuration changes, service restart/rebuild or data
   deletion. Record the exact upstream revision, command, actual runtime and exit
   result under .runtime; do not invent a substitute when the official case is
   available. An unsafe reproducer requires a separately agreed safe approach.
4. Compare with retained RM-012 failure logs and inspect any existing core dumps
   or native backtraces. A worker's reported test file need not identify where
   memory corruption began; SIGSEGV alone cannot identify this PHP defect.
5. Report facts, inferences and gaps separately. Classify this specific hypothesis
   as likely, possible but unproven, unlikely or excluded with supporting evidence,
   and identify the smallest next discriminating diagnostic. A single reproducer
   PASS does not exclude an intermittent defect; reproducing an upstream defect
   in the same runtime does not alone prove it caused the historical suite crashes.

This sequence does not authorize full suites/quality runs, automatic retries,
changes to PHP/dependency locks, Xdebug/OPcache/GC settings, container lifecycle or
destructive operations during investigation. Preserve reports, databases, volumes
and caches. Any supported correction belongs to point 2 after evidence review;
broad repetition and workload acceptance remain in the final point.

### Activation investigation — 2026-10-29

Facts:

- Actual development runtime: PHP 8.4.27 CLI NTS Linux x86_64, SimpleXML
  8.4.27, Xdebug 3.5.3 and OPcache 8.4.27. GC is enabled; CLI OPcache is
  disabled and JIT is disabled. No runtime settings were changed.
- [Official issue #24488](https://github.com/php/php-src/issues/24488) describes
  a SimpleXML properties-table double free after materializing properties and
  collecting a cycle containing the XML object. Plain XML presence or ordinary
  property access alone does not establish the required conditions.
- Inspected the official regression case from
  [PR #24494 revision 1cf7720d](https://github.com/php/php-src/blob/1cf7720d219bac8aa9c17722abaf3ab241f59962/ext/simplexml/tests/gh24488.phpt),
  extracted its unmodified `--FILE--` block and executed it once with:
  `docker exec -w /workspace runa-dev-app-1 php .runtime/artifacts/RM-2026-014/gh24488.php`.
  Actual result: exit **134**, stderr `zend_mm_heap corrupted`, no stdout.
  The source, extracted case, SHA-256, command, runtime, native exit and raw
  outputs are retained under `.runtime/artifacts/RM-2026-014/`. This diagnostic
  used no database, source/test changes, configuration changes or restart.
- The upstream correction is
  [php-src commit f9634847](https://github.com/php/php-src/commit/f9634847258a8c3ed6c12bb79104eba60bb631ea):
  release the properties table with `zend_array_release` instead of
  `zend_hash_release`. PR #24494 was closed after manual application; it was not
  merged through that PR. The corrected branch NEWS targets PHP 8.4.28 with an
  unset release date. The [official release listing](https://www.php.net/releases/index.php?json&version=8.4)
  still lists 8.4.27 as latest on the investigation date; no released corrected
  8.4 image was established.
- Focused source inspection found no direct SimpleXML use in Runa backend code
  or its backend cases. ParaTest constructs SimpleXMLElement when parsing JUnit
  reports in its coordinator; its inspected parser does not establish the
  materialized-properties/cycle path in a crashing worker. Mockery is not an
  installed dependency. Other dependency XML paths require reachability evidence.
- Retained suite failures report worker SIGSEGV/exit 139, including AuthEntryTest
  and PermissionsTest; they supply no native stack identifying SimpleXML. No
  existing relevant core/backtrace was found in the inspected runtime and host
  crash locations. The host core handler uses Apport; gdb was absent from the
  development container at initial inspection. Current resource snapshots cannot establish resource
  pressure at historical failure time.

Inference: **possible but unproven** as the cause of historical backend worker
crashes. The official regression is confirmed in the installed runtime, but the
reproducer's exit 134 is not the recorded worker exit 139, and neither a reachable
worker XML cycle nor a matching native stack has been established. This finding
does not justify changing application assertions, disabling GC or lowering
parallelism. Rolling back a security release is not an approved correction.

Next discriminating diagnostic: capture a native worker crash stack with matching
runtime symbols and identify whether it reaches the SimpleXML/GC path. There is
no retained core to analyze with the current tools. Adding debugger support,
changing core capture or temporarily backporting the official runtime correction
requires an explicit diagnostic/correction plan beyond the bounded unchanged-
container reproducer. The owner subsequently approved temporary gdb installation
and focused tracing, as recorded in Decisions; PHP changes remain unapproved. Do not silently rebuild or change PHP pins to obtain a
passing suite. Point 1 remains open pending that evidence/plan decision; point 2
has not started. Both instruction products already carry the reusable quality
policy; this diagnostic introduces no delivered capability or customer rule.

### Approved native-stack diagnostics — 2026-10-29

Installed Debian gdb 13.1-3 with `--no-install-recommends` in the existing
`runa-dev-app-1`; 13 new packages, no upgraded packages. Exact package versions
and the unchanged image identity are retained in
`.runtime/artifacts/RM-2026-014/gdb-install.json`. This temporary tooling is not a
Dockerfile/dependency-pin change and must be removed after the last approved use.

- The unchanged official GH-24488 reproducer under gdb stopped on SIGABRT after
  `zend_mm_heap corrupted`. The visible stack includes `_zend_new_array`,
  `execute_ex` and the abort path; many PHP frames lack symbols because the
  installed PHP executable is stripped. This captures the manifestation, not
  the original double-free site. Raw stack: `gh24488-gdb.txt` in the artifact
  directory above. No core file or PHP payload/local-variable dump was requested.
- Ran the supported owner entrypoint under multiprocess gdb tracing:
  `python3 tools/maintainer/test-backend --parallel --processes=4 --filter=AuthEntryTest`.
  All descendants remained under debugger observation; native fault signals would
  be logged and delivered to the child so the runner could report/clean up.
  Result: **76 tests, 1861 assertions PASS, 189.76 seconds**, runner exit 0,
  cleanup 0, no captured SIGSEGV/SIGABRT. The supported cleanup removed its
  inactive `runa_test_test_4` worker database; the base database was preserved.
  `auth-entry-gdb.txt`, `auth-entry-results.json` and `worker-diagnostic.gdb`
  retain the trace, native result and exact diagnostic setup. This focused run is
  not full-backend acceptance and does not exclude an intermittent/order-dependent
  crash. Four workers were configured; the selected file ran in one worker.

- The same supported entrypoint with `--filter=PermissionsTest` reproduced an
  actual worker **SIGSEGV/exit 139** under gdb. Native frames include
  `zend_object_std_dtor`, `zend_objects_store_del`, `zend_array_destroy`,
  `execute_ex` and `zend_call_function`; the top frames point into unrecognized
  memory. The runner reported failure (exit 1) and successful cleanup (0),
  removing its inactive worker database. Raw trace and native result are retained
  as `permissions-gdb.txt` and `permissions-results.json`. This is actionable
  worker evidence, but does not yet identify the initial corruption or SimpleXML.
- A harmless PHP type probe established that Xdebug supplies compatible Zend
  structure types even though the PHP executable lacks complete symbols. A second
  focused Permissions diagnostic uses those types to capture only PHP function
  names/source locations, native addresses and destructor disassembly when a
  fault occurs; no PHP arguments, object properties or application payloads are
  dumped. Its purpose is to locate the fault, not retry until PASS. That run
  passed: **33 tests, 360 assertions, 94.63 seconds**, runner/cleanup 0, with no
  fault to inspect. Raw trace/result: `permissions-php-stack-gdb.txt` and
  `permissions-php-stack-results.json`. This unchanged-runtime failure/PASS
  sequence confirms intermittent behavior within the focused selection.
- Validated the PHP-stack reader against a harmless function calling
  `gc_collect_cycles()`; it reports only the expected function names/locations.
  A further focused Permissions run adds native verbose output to distinguish
  test progress and capture the PHP stack if the fault recurs. This is a bounded
  diagnostic decision, not an automatic retry policy or acceptance run. Result:
  **33 tests, 360 assertions PASS, 93.60 seconds**, runner/cleanup 0; no native
  fault occurred and therefore no PHP crash stack was available. Trace/result:
  `permissions-verbose-php-stack-gdb.txt` and
  `permissions-verbose-php-stack-results.json`. The three Permissions traces
  preserve FAILED/PASS/PASS separately. There are no additional automatic reruns.
- After the last approved trace, simulated and then purged exactly the 13 newly
  installed packages listed in `gdb-install.json`, without autoremove or unrelated
  package changes. `gdb-removal.txt` retains the scoped operation. All test child
  processes exited through their supported wrapper; worker cleanup returned 0.
  Development services, base test data, Qdrant/model caches, source and all raw
  successful/failed reports remain preserved.

### Proposed next diagnostic scope — owner decision pending

The current stripped PHP stack ends in invalid memory during object destruction;
no native source line identifies the original corruption. The official SimpleXML
regression remains possible but unproven as the worker cause. Do not implement
that upstream patch merely to obtain a passing retry.

Proposed explicit extension of point 1:

1. Build one disposable diagnostic image, tagged `runa-rm014-native-debug:8.4.27`,
   from the exact current development image
   `sha256:b98cd06c77128d28123c43fe49f0e0a33f52719e969c3001b7782c1142032751`.
   Keep build files, logs and evidence solely under
   `.runtime/artifacts/RM-2026-014/`. Do not rebuild/restart development services
   or change release Dockerfiles/pins.
2. Compile a separate debug/AddressSanitizer PHP CLI from the existing official
   PHP 8.4.27 archive, SHA-256
   `dcf93923b0e38e0d5252b25b796520b5a47761a08b3488b6d0b5d1115764d3f0`.
   Rebuild necessary extensions, retaining their versions, for that diagnostic
   ABI. Record the exact compiler/configure options and capability differences.
   Install build/debug packages only in that disposable image. No rollback,
   third-party PHP patch or production/development runtime replacement.
3. Validate the known upstream reproducer in that image, then run at most three
   focused `PermissionsTest` diagnostics through the supported owner wrapper,
   preserving four-worker configuration and its suite lock/database guards and
   cleanup. Use `USE_ZEND_ALLOC=0` only inside this instrumented diagnostic to
   expose allocator accesses to ASan; document the difference from native runs.
   Keep ASan memory-value dumps disabled and retain source locations/native fault
   results separately from the existing native-mode evidence. Stop after an
   actionable failure or the bounded attempts; absence of failure is inconclusive.
4. Remove the exact disposable container/image resources after use; preserve all
   reports, development/base test databases and shared caches. Review the first
   invalid-access evidence before choosing any supported correction in point 2.

This is a focused instrumentation build before implementation is complete, which
exceeds the currently approved unchanged-runtime/gdb scope and the ordinary
final-only image-build rule. Obtain the owner's explicit decision on this
exception before building or running it. No such image has been built yet.

## Implementation plan

Ordinary implementation permits only necessary small focused diagnostics.
Implement broad repetition/concurrency scenarios before running them in the final
point. Mandatory hooks and final-verification repair rules remain in force.

- [ ] 1. Reconcile evidence and establish a diagnostic hypothesis.
  - Prerequisite: RM-013 closed, merged and released; explicit owner activation.
  - Verify the prioritized php-src#24488 lead using the bounded sequence above;
    do not presume that the external diagnosis or reported fix applies to Runa.
  - Inspect actual runtime/source versions, crash evidence and test resource
    ownership. Capture an actionable stack/core diagnostic where supported.
  - Identify likely mechanisms with focused checks, without claiming a cause from
    a successful restart alone. Discuss any changed architectural premise.
- [ ] 2. Implement the supported correction and regression scenarios.
  - Correct the evidenced cause without hiding crashes, lowering assertions or
    bypassing native runners/locks/cleanup. Add targeted regression coverage and
    final workload/repetition scenarios. Synchronize affected current guides and
    assess owner/customer instructions and module compatibility as applicable.
- [ ] 3. Full verification, exception retirement, release and final report.
  - Require points 1–2 committed; review current guidance and select/preview the
    exact proportionate quality graph with all real prerequisites.
  - Run complete parallel backend tests under isolated and full-quality workload
    conditions, including repeated executions sufficient to assess the diagnosed
    mechanism. State measured limits; do not claim mathematical absence of flakes.
  - Run the selected final graph using quality-roadmap --background and end the
    turn with quality-status instructions. Repair failures through inserted fix
    points and full affected-area reruns before restarting the final graph.
  - After corrected verification, retire the temporary exception and any obsolete
    workaround machinery/current guidance; reverify substantive policy changes.
  - Preserve data/caches/reports and inspect exact disposable cleanup scopes.
  - Finalize fix 0.6.2 through the release workflow, merge commit, main sanity,
    annotated tag/publication and branch deletion. Do not close with the original
    segfault still waived.

## Execution state

Activated on 2026-10-29 with explicit owner authorization. Point 1 is in progress.
The upstream SimpleXML reproducer fails in this runtime, but historical suite
causation remains unproven; see the activation investigation. No implementation
correction has started. The interim exception remains an owner decision,
not evidence that PHP code cannot cause the crash. All-group milestone verification
is not due for fix 0.6.2; select complete affected suites and real prerequisites.

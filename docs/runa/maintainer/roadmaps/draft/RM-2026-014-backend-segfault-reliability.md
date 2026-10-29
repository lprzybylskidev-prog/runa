---
id: RM-2026-014
title: Backend test segfault diagnosis and reliability
status: draft
branch: roadmap/RM-2026-014-backend-segfault-reliability
created_at: 2026-10-28
activated_at: null
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
on 2026-10-28. This is a PATCH increment from that planned predecessor; refresh
the baseline and release ordering at activation. Drafting does not activate work,
change VERSION or authorize implementation before RM-013 closes and the owner
activates this roadmap.

Scope includes PHP/runtime/extensions, Pest/ParaTest and supported transports,
Docker resources, test concurrency/order, evidence collection and the temporary
owner acceptance exception. The cause is unknown; Docker is a hypothesis, not
an established fault. No business behavior redesign, CI, new coordinator,
unapproved dependency or destructive data reset is authorized.

## Specification

### Decisions

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
Laravel/Pest/ParaTest and fix availability have **not been independently verified**
for this draft. Treat this as a concrete research lead, not an established cause
or an approved PHP update/rollback.

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

## Planning state

Draft only; no diagnosis or implementation is claimed. The interim exception is
an explicit owner decision, not evidence that PHP code cannot cause the crash.

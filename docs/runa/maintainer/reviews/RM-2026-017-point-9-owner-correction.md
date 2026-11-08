# RM-2026-017 — Owner Clarification and Correction of Point 9

Owner decision received 2026-11-07. The supplied clarification below is retained
verbatim. The owner's later instruction limits immediate execution to correcting
the roadmap and withdrawing point-9 implementation. Do not execute point 9 or its
canonical instruction changes yet. This later limit supersedes section 9's
broader immediate execution authorization; it does not alter the intended workflow.

## 1. Critical clarification of the original requirement

The owner has identified a fundamental misunderstanding in the specification of SIGSEGV handling.

The original requirement was **not to implement a programmatic SIGSEGV recovery mechanism**.

The requirement was to improve **the AI agent's operating procedure during the final roadmap verification stage**.

This distinction is mandatory.

The previous specification incorrectly transformed an agent-workflow requirement into an engineering task involving native test-runner integration, per-worker crash attribution, automated retries and execution orchestration.

That interpretation is explicitly rejected.

The owner does not authorize building such a mechanism.

## 2. Actual problem to solve

The backend test environment experiences intermittent PHP SIGSEGV failures.

The underlying problem has already been investigated, and the owner has decided to wait for an upstream PHP correction.

Do not investigate the root cause.

The actual problem concerns the agent's behavior after a SIGSEGV occurs during the final roadmap verification.

Previously, the agent repeatedly:

1. Started the complete quality verification process.
2. Successfully completed several potentially expensive verification groups.
3. Encountered an intermittent SIGSEGV during backend testing.
4. Abandoned the useful results of the preceding groups.
5. Restarted the entire quality verification process.
6. Repeated the same expensive work unnecessarily.

The owner wants this behavior corrected.

**The objective is to prevent unnecessary repeated verification and maximize useful backend test coverage despite intermittent SIGSEGV failures.**

This is primarily an agent-instruction and workflow problem.

## 3. Required agent behavior

During final verification, when backend testing encounters a confirmed or suspected SIGSEGV:

### A. Preserve unaffected verification results

Do not automatically restart the entire quality verification process.

Record which verification groups have already completed successfully.

Preserve their existing native results and evidence.

Continue only with the unresolved or affected scope, using existing supported commands and capabilities.

Do not rerun successful unrelated groups merely because backend testing encountered a SIGSEGV.

If source changes, dependency changes, configuration changes or another relevant validity condition make earlier evidence stale, identify the affected groups and rerun them as required.

Never reuse invalid or unverified evidence.

### B. Identify the interrupted backend test where possible

Inspect available native output, logs, reports and test-runner diagnostics.

Attempt to identify the specific test interrupted by SIGSEGV.

Use only information that the existing tools actually provide.

Do not invent a test identity based on an unreliable progress indicator.

If the exact test cannot be identified reliably, explicitly record that limitation.

Use existing test selection capabilities to continue safely with the remaining known or uncertain backend scope.

Do not implement a new worker-attribution system.

### C. Perform up to two additional attempts

If the interrupted test can be identified reliably, the agent should manually initiate up to **two additional attempts** using existing supported test commands.

These are agent-directed execution attempts, not automatic retries implemented in a runner.

If the test passes during one of these attempts:

- Record that the original attempt encountered SIGSEGV.
- Record the successful subsequent attempt.
- Continue the remaining backend verification.
- Do not claim that the original interrupted native run passed.

If the test repeatedly encounters confirmed SIGSEGV:

- Record the attempts and available native evidence.
- Classify the case as interrupted by SIGSEGV.
- Continue other safely executable backend tests where possible.
- Request explicit owner acceptance of the unresolved SIGSEGV exception before release approval.

The agent must not retry indefinitely.

Ordinary assertion failures, setup failures, timeouts and other unrelated errors are not SIGSEGV exceptions.

### D. Complete as much backend testing as possible

Do not immediately restart the full backend suite after every interruption.

The agent should systematically continue the remaining backend test scope using supported commands and reliable available evidence.

The objective is to execute as much of the required backend coverage as possible and discover all reasonably reachable failures.

If exact remaining-case selection cannot be established safely, document the uncertainty and use an appropriate existing supported broader backend selection when necessary.

Do not fabricate complete coverage.

Do not misclassify unknown or unexecuted cases as accepted SIGSEGV.

### E. Consolidate ordinary backend failures

This requirement is especially important.

Do not repair a small number of failures and immediately restart every verification group.

Instead:

1. Continue backend verification as far as safely possible.
2. Collect the ordinary failures discovered.
3. Group related failures.
4. Implement the necessary corrections.
5. Run appropriate affected-area regressions.
6. Reevaluate which previous verification evidence remains valid.
7. Continue unresolved backend verification without unnecessarily repeating unrelated successful groups.

Where complete affected-area verification is required by the existing testing policy, perform it.

Do not use a single passing filtered test as proof that an entire affected area is correct.

The goal is to avoid repeatedly discovering small batches of backend failures followed by expensive full-process restarts.

## 4. Explicitly prohibited implementation

Do not implement any of the following to satisfy this requirement:

- A custom test runner.
- A replacement for ParaTest.
- Runa-managed worker orchestration.
- Automatic SIGSEGV retries.
- Per-worker crash-event infrastructure.
- A custom crash-attribution protocol.
- New test execution state machines specifically for SIGSEGV.
- ParaTest or Pest forks, patches or dependency changes for crash reporting.
- A new persistent database or ledger of individual test execution attempts.
- Code intended to automatically skip or accept crashing tests.
- New scripts that reproduce the agent's decision procedure.

**The agent itself must make the decisions and execute existing commands.**

This requirement must not be transformed back into an infrastructure development task.

## 5. Distinguish workflow guidance from existing quality infrastructure

RM-2026-017 also contains a separate objective concerning unnecessary repetition of successful verification groups.

Review this objective carefully.

The owner does not want a new SIGSEGV recovery engine.

Where existing quality tools already provide sufficient mechanisms for preserving, inspecting or reusing evidence, use them correctly.

If there is a genuinely independent requirement for durable verification evidence across interrupted sessions, do not automatically discard that requirement.

However, distinguish it explicitly from agent-directed SIGSEGV handling.

Do not assume that a new persistent orchestration mechanism is authorized merely because preserving successful verification work is desirable.

Do not implement additional quality infrastructure under the SIGSEGV requirement.

Any independently justified code changes to quality infrastructure must have a clearly established requirement, supported design and existing owner authorization.

## 6. Correct the active roadmap

Update RM-2026-017 to reflect this owner clarification.

Required changes:

1. Preserve the complete history of the original requirement, the incorrect interpretation and the subsequent correction.
2. Record that the previous programmatic SIGSEGV recovery design was rejected by the owner.
3. Remove the proposed ParaTest modification versus Runa-managed worker orchestration decision.
4. Remove the requirement to implement automatic per-test crash attribution and retry infrastructure.
5. Replace the SIGSEGV portion of point 9 with agent operating instructions and associated documentation updates.
6. Update the relevant acceptance criteria accordingly.
7. Reevaluate the inserted feasibility-investigation point and remove or narrow obsolete work resulting solely from the rejected design.
8. Review all subsequent roadmap points for references to the rejected implementation.
9. Ensure final verification follows the corrected agent procedure.
10. Preserve already existing code changes that are independently valid and explicitly authorized; do not revert unrelated work.

The analysis recorded in:

`docs/runa/maintainer/reviews/RM-2026-017-native-evidence-integration-boundary.md`

must remain available as historical evidence of the rejected direction.

Do not silently erase the investigation or pretend that the design change never occurred.

## 7. Update canonical agent instructions

Apply the corrected workflow to the appropriate existing authoritative documentation and agent instructions.

Identify the actual ownership and precedence of:

- Root AGENTS.md.
- Relevant testing and roadmap skills.
- Maintainer testing/quality documentation.
- Roadmap and release verification procedures.

Avoid duplicating the full procedure across every document.

Keep one authoritative description and concise references from the appropriate instruction entrypoints.

The instructions must be explicit enough that an agent carrying out a future roadmap can follow them without needing the owner to repeat this explanation.

Preserve the distinction between owner-specific SIGSEGV acceptance and customer-facing policy.

Do not silently extend the owner's temporary PHP SIGSEGV exception to customer applications.

## 8. Acceptance criteria

The corrected work is acceptable when:

- The SIGSEGV behavior is documented as an agent-directed workflow.
- The agent knows not to restart the full verification process unnecessarily.
- Two additional attempts are permitted for a reliably identified SIGSEGV-interrupted test.
- Original crashes remain visible in the evidence.
- Remaining backend coverage is pursued safely.
- Ordinary failures are collected and corrected in consolidated batches.
- Previously successful unrelated verification is not repeated without a validity reason.
- No native failures are rewritten as successful results.
- Uncertain or unexecuted backend coverage remains explicitly classified.
- Exhausted confirmed SIGSEGV exceptions still require owner acceptance.
- Existing testing/security/release safeguards remain authoritative.
- No automatic SIGSEGV recovery code is introduced.
- Obsolete architectural questions are removed from the active decision queue.
- Related roadmap, skill and documentation references are coherent.

Do not trigger a live SIGSEGV or run the complete quality suite merely to demonstrate this instruction change.

Use a bounded, hypothetical execution scenario to verify that the instructions clearly describe the expected agent decisions.

## 9. Immediate execution boundaries

The owner has clarified the requirement and rejected the previous programmatic design.

You are authorized to:

- Reconcile the current roadmap.
- Correct the documentation and agent instructions.
- Remove obsolete proposed work from the future plan.
- Perform only necessary focused documentation/contract checks.
- Commit these changes.

You are not authorized to:

- Continue implementing a SIGSEGV automation engine.
- Modify ParaTest/Pest integration for worker crash attribution.
- Introduce new dependencies or runner architecture.
- Begin broad final quality verification.
- Prepare or publish the release.
- Reinterpret this clarification as permission for unrelated infrastructure changes.

After completing the corrections, report:

1. The exact scope that was removed.
2. The final agent-directed SIGSEGV procedure.
3. Which instruction and documentation owners were updated.
4. How independent quality-evidence requirements were preserved or separated.
5. Any remaining genuine blockers, if they exist.

**Do not ask the owner to choose between ParaTest modification and custom worker management. Both options are rejected for this requirement.**

The implementation must follow the owner's actual intention: improve the AI agent's decision-making during final verification, not build software to automate those decisions.

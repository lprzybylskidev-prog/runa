# RM-2026-017 — Mandatory Restart From the Baseline Audit

## Owner decision

I am rejecting the completeness of the previous RM-2026-017 baseline audit and withdrawing my previous Gate B approval.

Your admission that the frontend audit was substantially incomplete has undermined confidence in the evidence used to approve the implementation scope.

This does not prove that every other audit area was handled incorrectly. However, their completeness can no longer be assumed.

**RM-2026-017 must restart from point 1.**

This is a mandatory owner decision, not a request for an optional review.

## 1. Stop the current execution

Immediately stop progressing through the roadmap.

Do not begin point 7.

Do not continue frontend corrections, backend refactoring, documentation consolidation or other implementation work.

Do not close the roadmap or prepare the 0.8.0 release.

The current implementation authorization is suspended until the new Gate B approval.

## 2. Preserve the complete history

Do not delete the roadmap, rewrite Git history, reset the branch or discard existing commits.

Preserve:

- All previous audit findings.
- Owner decisions.
- Previous Gate B approval.
- The subsequent withdrawal of that approval.
- Implementation history of points 2–6.
- Existing test results.
- Verified defects and corrections.
- The admission that the previous frontend audit was incomplete.
- The reasons for restarting.

The complete history must remain understandable to future maintainers and agents.

Existing code changes may remain in the branch, but their acceptance status is no longer established merely by previous completion declarations.

Do not automatically revert working changes.

## 3. Restore the roadmap's execution state

Update RM-2026-017 to record:

**Gate B: APPROVAL WITHDRAWN — BASELINE AUDIT INCOMPLETE.**

Points 1–6 must no longer be represented as fully accepted and completed.

Point 1 becomes the active task again.

Points 2–6 are pending renewed evidence-based verification and acceptance.

Point 7 and all subsequent points remain blocked.

Preserve the original completion records as historical facts, but distinguish them from the current execution state.

If the existing roadmap tooling does not directly support this status transition, use the supported roadmap correction procedure and record the necessary state reconciliation explicitly.

Do not bypass workflow validators or falsify roadmap metadata.

## 4. Restart the comprehensive baseline audit

Conduct a new, systematic baseline audit covering the complete agreed scope.

This includes:

- All frontend components and views.
- Backend architecture and module contracts.
- PostgreSQL schemas and migrations.
- Backend, frontend and E2E test quality.
- Agent skills and activation rules.
- AGENTS.md and related instructions.
- Technical, maintainer and customer documentation.
- Development tooling.
- Dependencies and configuration.
- Docker and Dev Container infrastructure.
- Qdrant and knowledge retrieval.
- Verification orchestration and SIGSEGV resilience.
- Roadmap execution and release workflows.

Do not limit the audit to previously identified issues.

Do not assume that a component is correct merely because it has existing tests, documentation or historical acceptance.

Do not assume a defect exists merely because a component has not been modified recently.

## 5. Mandatory inventory and traceability

Before declaring any audit area complete, establish an inventory of its actual scope.

For every relevant area, record:

- The inventory of components, files, capabilities or contracts.
- What was inspected.
- How it was inspected.
- Which requirements and states were covered.
- What evidence supports the conclusion.
- What remains unknown or unverified.
- Whether additional inspection is necessary.
- Any justified exclusions.

Use appropriate grouping for genuinely shared behavior.

Do not generate artificial documentation volume by repeating identical findings across every consumer.

However, every relevant component or capability must have a traceable disposition.

A statement such as "reviewed the frontend" is not sufficient.

Neither is a count of files discovered.

## 6. Frontend audit requirements

The previous frontend audit was inadequate.

The new audit must explicitly cover:

- Shared components and layouts.
- Authentication.
- Own-account and security interfaces.
- Admin Panel.
- User administration.
- Permissions.
- Audit.
- Application-facing views.
- Forms.
- Tables.
- Modals.
- Notifications.
- Navigation.
- Loading and transition behavior.
- Empty/error states.
- Localization.
- Themes.
- Accessibility.
- Responsive layouts.
- Relevant user permissions and security states.

Source inspection alone is insufficient for claims about rendered appearance or interactive behavior.

Inspect representative real application views in the browser using the existing development environment and the approved D-04 boundaries.

Do not restrict rendered inspection to the public landing page and login screen again.

Prioritize authenticated application views, since they contain the majority of Runa's administrative functionality.

For shared components, inspect representative consumers with meaningfully different configurations.

Distinct complex interactions must receive their own assessment.

Use risk-based sampling instead of attempting every possible combination of state, language, role and viewport.

If a specific runtime or access limitation prevents rendered inspection, state that limitation explicitly.

Do not silently replace browser verification with source review.

Do not claim full rendered coverage when only a small sample was inspected.

The complete automated browser acceptance matrix remains reserved for the final verification stage.

## 7. Backend and other audit areas

The same standard of evidence applies beyond frontend.

Review the backend, database, security, tests, skills, documentation, tooling and infrastructure against the actual acceptance requirements.

Explicitly investigate the completeness of the previous audit in these areas.

The new audit must identify actual problems and justified improvements without inventing work.

Review test quality and redundancy, not merely test counts.

Review database objects and their actual consumers, including potentially obsolete tables, columns and indexes.

Review documentation and skills for correctness, contradictions, duplication and practical usability.

Preserve architectural boundaries and established security invariants.

Do not perform broad automated quality verification during this audit.

Do not investigate SIGSEGV root causes.

## 8. Reevaluate work previously performed in points 2–6

All existing changes remain available as evidence and implementation candidates.

However, previous completion status is not proof that their requirements were satisfied.

After completing the renewed baseline audit:

- Map existing changes to the newly verified findings.
- Identify which implementations are still valid.
- Identify missing work.
- Identify premature closure claims.
- Identify any changes that require correction or reconsideration.
- Propose the appropriate reconciliation of points 2–6.

Do not reimplement working solutions unnecessarily.

Do not use previous implementation effort as justification for preserving a flawed decision.

## 9. New Gate B report

Prepare a complete owner-facing audit report containing:

1. An executive summary of the actual platform condition.
2. An area-by-area inventory and coverage summary.
3. Confirmed findings, separated from hypotheses.
4. Severity, impact and estimated remediation effort.
5. Evidence supporting each significant finding.
6. Missing functionality and consistency problems.
7. Recommended corrections.
8. Proposed changes to points 2–12.
9. Assessment of previously implemented work.
10. Explicit decisions requiring owner approval.
11. Unverified areas and limitations.

Preserve the detailed technical evidence in the roadmap.

The report must make it possible to distinguish a genuinely audited area with no findings from an area that was not properly inspected.

Do not optimize for a short findings list.

Do not manufacture findings to justify the renewed audit.

## 10. Mandatory Gate B stop

After completing the renewed audit:

- Update and commit the roadmap history and evidence.
- Present the complete owner-facing summary.
- List all decisions requiring approval.
- Stop.

Do not implement the newly identified corrections.

Do not restart point 2.

Do not treat the old Gate B approval as valid.

Do not approve Gate B yourself.

Wait for a new explicit owner decision.

## Core principle

**An audit is complete when its agreed scope has been examined with adequate evidence, not when the agent has run out of known defects to fix.**

The objective is to restore confidence in the roadmap's execution and ensure that Runa 0.8.0 is based on a genuinely comprehensive baseline.

Do not confuse completing a checklist with completing the underlying work.

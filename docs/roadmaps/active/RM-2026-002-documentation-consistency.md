---
id: RM-2026-002
title: Documentation consistency and release workflow fixes
status: active
branch: roadmap/RM-2026-002-documentation-consistency
created_at: 2026-10-18
activated_at: 2026-10-18
closed_at: null
release_type: fix
impact_areas:
  - documentation
  - tooling
  - ai_environment
---

# RM-2026-002 — Documentation consistency and release workflow fixes

## Overview

### What

Repair current-state documentation and the agent/release workflow that allowed stale README content to survive release 0.1.0. Deliver patch release 0.1.1 after explicit user activation approval.

### Why

The previous final gate passed, but its checks did not verify the README's version, roadmap status or delivery claims. Existing documentation duties were general and were not translated into a mandatory current-state review at closure.

### Scope

- Audit current-state documentation, configuration, durable guidance and their implementation sources; distinguish history, requirements, proposals and live operational guidance.
- Repair confirmed stale statements without rewriting historical evidence.
- Clarify documentation ownership within implementation points and release finalization.
- Extend existing doctor/release/quality tooling with deterministic documentation checks and regression contracts.
- Reconcile affected knowledge metadata and refresh the development index.
- Verify, close, merge and publish 0.1.1 under the existing local workflow.

### Out of scope

Business functionality, general refactoring, dependency upgrades/additions, CI, production deployment and architectural redesign. Do not modify the closed RM-2026-001, published v0.1.0 tag/release or historical commits. Do not fetch missing visual assets or implement deferred designs. Do not build a natural-language documentation parser or a new workflow skill.

## Specification

### Audit evidence and root cause

Planning audit performed on main at `35668a401659416feae60811274ba91108efbf9b` with an initially clean worktree. VERSION and CHANGELOG identify 0.1.0; RM-2026-001 is closed; the local annotated v0.1.0 tag identifies the release merge. No roadmap is active. Remote-tracking main matches local main; remote freshness and GitHub publication are not independently established by this local comparison. The user reports an existing published GitHub Release; `gh` is unavailable in this environment.

Confirmed findings:

| Source                                                                         | Finding                                                                                                                                                                 | Disposition                                                                                                               |
| ------------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------- |
| README.md, Current state                                                       | Calls RM-2026-001 active, reports initial 0.0.0 as current context, says delivered quality/knowledge/production work remains unavailable, and describes 0.1.0 as future | Replace with accurate current-state facts and supported capability summary                                                |
| docs/tooling/commands.md, introduction and lifecycle/quality sections          | Retains planning-package language and pending point-14 host acceptance despite implemented commands and completed evidence                                              | Reconcile command status and remove obsolete execution milestones                                                         |
| docs/development/environment.md, production build descriptions                 | Says point 14 still completes deployment/operations and operational self-test                                                                                           | Link implemented production operations and preserve genuine onboarding prerequisites                                      |
| docs/production/architecture.md, production entrypoint description             | Describes deployment automation as remaining point-14 work                                                                                                              | Describe actual deployment workflow                                                                                       |
| docs/quality/README.md, runner implementation                                  | Says remaining environment roadmap must deliver production/security/release commands                                                                                    | Remove obsolete bootstrap status while retaining fail-closed behavior                                                     |
| docs/development/knowledge.md, service acceptance narrative and search example | Says complete production isolation remains points 13–15; example filters closed RM-2026-001 as active                                                                   | Preserve dated service measurements; correct current guidance and use a valid history filter                              |
| docs/ai/laravel-boost.md, baseline setup                                       | Calls real-session acceptance remaining although docs/development/editor-and-agents.md and closed point 8 record success                                                | Link ongoing verification procedure without claiming original acceptance is outstanding                                   |
| tools/knowledge/corpus.py, metadata; retrieved docs/workflows/roadmaps.md      | Metadata searches the entire document for id/status, so fenced example RM-2026-003 is classified as a real draft roadmap                                                | Restrict roadmap identity/status to actual roadmap frontmatter/path; add narrow regression coverage and refresh the index |

Items intentionally preserved: initial 0.0.0 in the versioning workflow is historical initialization; changelog entries, dated security/host/model measurements and closed-roadmap point narratives are history. REF-003 is genuinely absent, so its pending asset status is not a delivery defect. Business modules, authentication and shell designs are not delivered merely because requirements exist. Production tooling/simulation acceptance does not mean real production deployment: docs/project/state.yml still correctly records `ever_deployed: false`.

Root cause supported by source and history:

1. AGENTS.md and docs/workflows/roadmaps.md already require related durable documentation in each completed point. Leaving stale delivery claims violated that responsibility; this is not solely a missing-test defect.
2. RM-2026-001's final plan includes extensive runtime/quality/release verification but no explicit README/current-state closure review. Its release-metadata fix point 16b added VERSION/changelog/roadmap contracts without README coverage.
3. tools/project/checks.py `docs()` only checks whether executable public command names appear in docs/tooling/commands.md. It does not validate descriptions, versions or roadmap assertions. Its name and gate label suggest broader coverage than implemented.
4. tools/project/release_checks.py validates VERSION, changelog, roadmap/checklist/report, release arithmetic and tags. It never reads README or production-state documentation.
5. docs/workflows/releases.md lists VERSION, CHANGELOG and roadmap closure edits after the full gate but does not require updating or reviewing current-state documents in that transition. Generic documentation duties therefore have no explicit closure owner/evidence or corresponding rejection test.
6. Git history shows README last changed in `fd52e38`, before later quality/knowledge/production delivery. Closure commit `249064b` changed only VERSION, CHANGELOG and the roadmap. The latest retained final gate records PASS at `b4239c2`; its documentation log says only that every public command is documented. Both `./bin/doctor docs` and `./bin/doctor release` reproduced PASS against the stale README during this audit.

The evidence supports combined agent omission, insufficiently explicit closure procedure and narrow automated coverage. The number of passing checks did not establish semantic documentation freshness. It does not establish an internal reasoning cause beyond the recorded actions.

### Documentation ownership and review

- AGENTS.md retains a concise overarching rule and links to the detailed workflow; update existing completion/documentation clauses rather than duplicating policy.
- docs/workflows/roadmaps.md owns point-level documentation responsibility and the mandatory final review: identify affected current-state sources, compare capability/command/deployment claims with implementation, resolve stale statements and record review scope/results.
- docs/workflows/releases.md owns the active-to-closed transition, version/changelog/current-state synchronization and focused checks after metadata changes. Clarify which reviewed release-only edits retain full-gate evidence; substantive changes still require an inserted fix point and a new full gate.
- docs/quality/README.md documents exact automated coverage and the manual-review boundary. docs/README.md owns the distinction between historical evidence and current guidance.
- Existing skill references are updated only when their domain procedure needs a specific correction; otherwise retain their existing content. Knowledge refresh guidance remains in the existing knowledge documentation. No new skill or repeated checklist across every skill.
- Before closure, record factual review evidence in the active roadmap; include the closure transition and affected README/current-state facts. Final publication facts belong in chat after merge, without editing closed history.

### Deterministic current-state contract

Introduce one explicitly delimited, human-readable block in README Current state with a small documented field schema. The block is a checked projection, never a new authoritative state file. Keep version/roadmap/deployment claims in this block rather than repeating them in uncontrolled current-state prose.

- Current version equals VERSION and agrees with existing release validation.
- Active roadmap equals the single active roadmap's ID, or an explicit none value when absent. Draft/paused/closed examples do not count as active.
- Real production deployment status equals docs/project/state.yml; simulation is not real deployment.
- Reject missing/duplicate blocks, malformed/duplicate/missing fields and invalid source state with actionable errors. Use exact bounded syntax, not heuristic scanning of arbitrary Markdown.
- Do not add a remote publication field that local repository metadata cannot prove. Verify GitHub publication separately at release time.
- Reuse a shared read-only validator through doctor docs and doctor release, including focused post-closure/main sanity checks. Ensure final verification always selects this contract, even when a future roadmap lacks the documentation impact area.
- Before the full gate the README reflects the existing VERSION and active roadmap. During reviewed release finalization it changes together with VERSION/changelog/closed status; rerun focused documentation/release checks before closure commit and after merge.
- Preserve normal planning commits after a published tag. Assess the existing release validator's requirement that an existing tag identify HEAD: distinguish a published release identity from a later planning-only main commit, while continuing to reject wrong release tags. Specify and test the exact baseline comparison before implementation; do not weaken tag integrity to accept arbitrary drift.

Manual review covers free prose, actual capability delivery, command semantics, environment startup and consistency across agent guidance. Automated checks make no claim to interpret these fully.

### Regression and acceptance coverage

- A fixture reproduces the released stale README and demonstrates rejection; test separate version, active/closed/none-roadmap and production-state mismatches.
- Positive cases include active pre-gate state, closed pre-publication state, merged release state, and valid planning-only main changes following a release tag.
- Test malformed/missing/duplicate block fields, multiple active roadmaps and invalid deployment metadata.
- Historical versions, closed roadmaps, fenced examples and dated reports remain accepted and unmodified.
- Test the actual doctor/release integration and gate selection, not only a helper mirroring its implementation.
- Knowledge metadata contracts distinguish real roadmap frontmatter from fenced examples in workflow/template documentation, preserving stable identity for actual roadmaps. Run applicable existing offline/live retrieval checks and golden evals after the narrow fix.
- No new dependencies. Run the complete gate selected by the declared impacts, not merely documentation tests. Review changes to impact areas if implementation broadens the surface.

### Decisions

- 2026-10-18 — Point 1 confirmed the recorded source findings after origin refresh. The README block uses exact markers `<!-- runa:current-state -->` / `<!-- /runa:current-state -->` and three ordered list fields: `Current version`, `Active roadmap`, `Production ever deployed`; values are inline code, with `none` and lowercase booleans. YAML source state uses the installed Symfony parser, not a second YAML implementation. Invalid production timestamps and roadmap frontmatter fail explicitly.
- 2026-10-18 — Existing annotated tags must identify HEAD, except on main where a tagged ancestor with identical release/runtime content may be followed only by changes under docs/roadmaps/draft/*.md. Validate the tagged release metadata itself as well as ancestry and the exact changed-path allowlist; reject runtime changes, forged/stale metadata and non-ancestor tags. This narrow exception supports approved draft planning without weakening release identity.
- 2026-10-18 — The final gate always checks docs through release validation; add offline docs/knowledge regression contracts to the existing final contract step. Post-gate release-only edits may change the checked README version/active-roadmap fields and roadmap review evidence, but capability prose or validator changes require a fix point and a new full gate. Remote access succeeded for fetch; release publication will be verified separately.

- 2026-10-18 — The user approved execution through release ("Ogarnij całość"). Refreshed origin/main; activated the approved roadmap on its dedicated branch.

- 2026-10-18 — User requested audit and draft only before explicit approval. This document is not activation authorization; no implementation, version bump, branch activation, merge, tag or publication occurs in the planning phase.
- 2026-10-18 — Proposed release is fix 0.1.0 -> 0.1.1. Preserve published and closed history; improve existing mechanisms with bounded deterministic checks plus mandatory source review.
- 2026-10-18 — The workflow clarifications and final-gate selection change are submitted for user approval here because quality/release workflow belongs to the project constitution.

## Implementation plan

- [x] 1. Confirm audit dispositions and finalize the documentation/release contract.
  - Prerequisites: explicit user approval, normal activation and branch creation from refreshed main.
  - Completion: recheck findings against current sources; settle README field syntax, post-gate edits, planning-after-tag validation and review evidence; retain the audit/specification here without creating a duplicate report.
- [ ] 2. Repair current-state documentation and clarify agent ownership.
  - Prerequisites: point 1.
  - Completion: reconcile confirmed documentation defects, add the README projection, update the owning workflow/agent rules and only affected skill procedures; preserve history and deferred features. Review prose against source and run proportionate structural/format checks. Commit and update this checklist.
- [ ] 3. Enforce deterministic documentation consistency in existing quality/release tooling.
  - Prerequisites: point 2.
  - Completion: shared read-only validation, doctor integration, reliable final-gate selection and regression tests for stale README, lifecycle transitions, historical immunity and release-tag integrity; document exact coverage. Run focused contracts, commit and update this checklist.
- [ ] 4. Correct knowledge classification and verify guidance synchronization.
  - Prerequisites: point 3.
  - Completion: narrow frontmatter/path classification fix and regressions; review AGENTS/skills/workflows for contradiction or duplication; refresh the index incrementally and run applicable knowledge checks/evals; record source-based current-state review evidence. Commit and update this checklist.
- [ ] 5. Full verification, release finalization, merge, and final report.
  - Prerequisites: points 1–4 committed and complete, safe synchronized worktree.
  - Completion: run the full applicable local final gate under the existing background handoff contract. Any findings require a fix point immediately before this item and full verification restarted. After PASS, synchronize 0.1.1 VERSION/changelog/README and closure facts, refresh knowledge and run focused docs/release validation; close on the branch, merge with a merge commit, sanity-check main, create annotated v0.1.1, push and publish the GitHub Release, then delete the roadmap branch. Verify publication using available authenticated tooling. Inspect and show exact task-created disposable Docker resources before authorized scoped cleanup; preserve development databases/caches/backups/reports. Report checks, review evidence, release identity and cleanup. Do not edit closed history afterward.

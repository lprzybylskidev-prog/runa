# Roadmap Workflow

## Purpose

A roadmap is the unit of planned implementation, branch ownership, verification, and release. It is not merely a task list.

No application/runtime implementation may begin without an active roadmap.

## Lifecycle

Roadmap directories:

- `docs/roadmaps/draft/`
- `docs/roadmaps/active/`
- `docs/roadmaps/paused/`
- `docs/roadmaps/closed/`

At most one roadmap may be active.

Closed roadmaps are immutable.

## IDs and filenames

ID format:

`RM-YYYY-NNN`

Example:

`RM-2026-003`

Filename format:

`RM-2026-003-user-management.md`

Branch format:

`roadmap/RM-2026-003-user-management`

One roadmap owns one branch and one release.

## Required frontmatter

Each roadmap contains:

```yaml
---
id: RM-2026-003
title: User management
status: draft
branch: roadmap/RM-2026-003-user-management
created_at: 2026-10-17
activated_at: null
closed_at: null
release_type: feature
impact_areas:
  - backend
  - frontend
  - database
---
```

Allowed release types:

- `feature`
- `fix`
- `maintenance`
- `release`

Impact areas are a short explicit list such as:

- `frontend`
- `backend`
- `database`
- `production`
- `development`
- `ai_environment`
- `security`
- `tooling`
- `documentation`

Impact areas drive local quality-gate selection.

## Required structure

Every roadmap contains:

1. Overview
   - what,
   - why,
   - scope,
   - out of scope.
2. Specification
   - agreed behavior/constraints,
   - a `Decisions` subsection for significant dated decisions.
3. Implementation plan
   - ordered checklist,
   - final checklist item is always full verification/release/merge/report.

## Discussion and activation

If no roadmap is active and implementation is requested:

- stay in discussion/specification mode,
- do not change application/runtime configuration,
- draft/update the roadmap,
- wait for the user to explicitly say the roadmap is ready.

The agent cannot self-approve.

Draft roadmaps are planning documents and may be edited/committed on `main`.

Activation:

1. inspect worktree/remote state, then safely update `main` without overwriting local work,
2. ensure the draft is current,
3. create the roadmap branch from updated `main`,
4. move the roadmap from `draft/` to `active/` on that branch,
5. set `status: active` and `activated_at`,
6. commit the activation state.

## Empty-repository bootstrap exception

The first repository bootstrap has no commit from which a roadmap branch can be created.

`RM-2026-001` may therefore use exactly one planning-only bootstrap exception:

1. audit/reconcile the specification package,
2. commit the English planning/docs/skills baseline to `main`,
3. push `main`,
4. create the RM-2026-001 branch,
5. activate the roadmap there,
6. begin runtime implementation only after activation.

That initial baseline commit must contain no application/runtime implementation. This exception exists only to create the repository history needed for the normal roadmap workflow.

## Roadmap points

Each implementation point should be a coherent, self-contained chunk that can reasonably be completed in a context-limited agent session.

Before finalizing the roadmap, sanity-check point dependencies and order.

Do not mechanically create one point per file/class. Design points around outcomes.

## Starting a point

Before each point, refresh only minimum necessary context:

1. active roadmap and current point,
2. relevant previous decisions and dependencies,
3. relevant local skills,
4. relevant code,
5. local knowledge/RAG availability and source freshness.

In practice query available RAG before broad docs/code discovery, then open authoritative source sections for significant rules. If it is absent or unavailable, use focused repository search; do not bootstrap tooling just for a context refresh.

Never load all docs/skills/repository source merely "to be safe."

For significant rules found by RAG, open the original source document before acting.

## Completing a point

A point is complete when:

- implementation is complete,
- local point dependencies are satisfied,
- proportionate lightweight checks pass,
- affected current-state documentation and relevant durable docs/skills are updated in the same point, including removal of superseded claims,
- a logical commit is created,
- the roadmap checklist reflects completion.

Avoid giant mixed commits. Also avoid mechanical one-point-one-file or one-line-one-commit behavior. Commits should reflect coherent work.

## Unknowns discovered during implementation

If work reveals a significant unknown that changes:

- user-visible behavior,
- architecture,
- data model,
- a security assumption,
- a project-constitution rule,
- an agreed premise,

stop implementation and return to discussion/specification mode.

After agreement:

- update the roadmap,
- record the dated decision,
- continue.

Small technical implementation decisions remain agent-autonomous.

## Pausing

To pause:

- move `active/` -> `paused/`,
- set status accordingly,
- keep the roadmap branch,
- record relevant current state.

No other roadmap may become active until the prior roadmap is paused/closed.

## Resuming

Before resuming:

- inspect current `main`,
- inspect roadmap branch divergence,
- understand intervening changes,
- resolve compatibility intentionally.

Do not blindly rebase.

Move `paused/` -> `active/` and continue.

## Final verification point

The final point runs the full applicable local quality gate and performs release finalization.

Before the gate, review current-state documentation against the delivered implementation: README, affected command/environment/deployment guidance, project-state metadata, agent rules and domain skill procedures. Check available versus deferred capabilities, startup instructions, CLI semantics and development/production boundaries. Classify dated reports, closed roadmaps and genuine proposals separately; do not rewrite history to describe newer behavior.

Record the reviewed sources, findings and disposition in the active roadmap. A deterministic docs PASS does not replace this source review. Capability/procedure corrections belong to completed implementation or inserted fix points before a new full gate. At closure, verify the state transition itself does not leave an active-roadmap claim or future-release wording behind; follow the release workflow for focused post-metadata validation.

If a failure requires code/config/docs work:

1. leave the roadmap active,
2. add a new fix point immediately before the final verification point,
3. implement and complete the fix point,
4. rerun final verification from the beginning.

Do not patch silently "inside" the verification point and pretend it stayed green.

If the issue changes an architectural/behavioral assumption, return to discussion.

## Background quality execution

Long final quality runs may execute in the background.

When the agent starts a long final gate:

- it must end its current turn/work,
- it must not poll in a loop waiting for completion,
- the user can later provide `./bin/quality status` output or relevant full logs.

The quality tool stores only the latest run.

If interrupted/crashed/rebooted:

- status becomes `interrupted`,
- show where it stopped,
- final verification reruns from the beginning.

## Closing

Before merge, on the roadmap branch:

- all implementation points are complete,
- full gate passes,
- version/changelog/release metadata are finalized,
- roadmap moves `active/` -> `closed/`,
- status becomes `closed`,
- `closed_at` is set.

Then:

1. merge to `main` with a merge commit,
2. run a short sanity check on `main`,
3. create the immutable annotated version tag,
4. push branch/main/tag as required,
5. publish the GitHub Release,
6. delete the roadmap branch.

Closed roadmap history is immutable after merge.

## Final report

Keep the final report concise. Include:

- what was implemented,
- significant decisions,
- any inserted fix points,
- gate result,
- migrations/infrastructure changes,
- version/tag/release,
- merge/branch/roadmap ID,
- known limitations or warnings.

Warnings require justification.

Security, migration/data-loss, or production-health failures cannot be downgraded to warnings merely to close a roadmap.

## Final point state and verification evidence

During the final gate, the current roadmap remains active and its final checklist item remains open. Checks must not require that pending item to be complete before it can be verified. After PASS and focused release-metadata checks, complete the checklist/closure metadata on the roadmap branch. The closure commit may record a final report using facts known before merge; report merge/tag/publication results in chat without editing the now-closed roadmap.

Final gate evidence records branch, commit, selected impact areas and results. Final release-only edits permitted by `docs/workflows/releases.md` receive focused validation; substantive changes invalidate the prior result. Do not interpret a stale PASS as proof for changed runtime code.

Do not wait or poll after launching a background gate. Ask the user to return the status/log output when ready; an interrupted run is never a PASS.

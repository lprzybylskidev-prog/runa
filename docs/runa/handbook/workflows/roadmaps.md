# Roadmap Workflow

## Purpose

A roadmap is the unit of planned implementation, branch ownership, verification, and release. It is not merely a task list.

No application/runtime implementation may begin without an active roadmap.

## Lifecycle

Roadmap directories:

- `docs/app/roadmaps/draft/`
- `docs/app/roadmaps/active/`
- `docs/app/roadmaps/paused/`
- `docs/app/roadmaps/closed/`

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
proposed_version: 0.1.0
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

`proposed_version` is a required canonical `X.Y.Z` projection of the application version after this roadmap is implemented. Set it from the expected predecessor release and `release_type`, including agreed roadmap order; drafts may target a later version than the next active release. Refresh it when that order or baseline changes, verify it at activation and reconcile it with the actual version at closure before the roadmap becomes immutable. It is not the version shipping the draft, a version bump, activation approval or permission for a major release. An explicit `target_version` required by a release policy remains a separate approved release target.

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

While agreeing the roadmap, explicitly ask the user which `release_type` and `proposed_version` to use. Offer a reasoned proposal based on scope, current version and agreed predecessor order, but obtain confirmation instead of silently treating it as accepted. Record the answer in frontmatter and Decisions. If the user has already explicitly supplied or confirmed both values in the same planning discussion, use that decision without repeating the question. Unconfirmed agent suggestions remain provisional; confirmation of release metadata is not activation approval. Reconfirm a changed proposal if later scope/order changes require a different classification or projected version.

If no roadmap is active and implementation is requested:

- stay in discussion/specification mode,
- do not change application/runtime configuration,
- draft/update the roadmap,
- wait for the user to explicitly say the roadmap is ready.

The agent cannot self-approve.

Draft roadmaps are planning documents and may be edited/committed on `main`.

If work on an active roadmap produces an agreed substantial follow-up scope deserving a separate roadmap, create or update its draft in `docs/app/roadmaps/draft/` immediately in the current checkout. Commit the draft as a separate planning commit on the current roadmap branch before ending that planning task, without another user reminder. Use the active roadmap ID as the commit scope and name the future roadmap in the subject. Preserve existing unrelated changes. The draft travels with the current branch into its normal merge; do not defer recording it until its own implementation or release. Record agreed dependencies/order, but leave it in draft: activation requires explicit user approval, and implementation and eventual release version are independent of the release carrying the planning document. Refresh predecessor links when their roadmaps close, without editing closed records. An unresolved idea is not an agreed specification.

Activation:

1. inspect worktree/remote state, then safely update `main` without overwriting local work,
2. ensure the draft is current,
3. create the roadmap branch from updated `main`,
4. move the roadmap from `draft/` to `active/` on that branch,
5. set `status: active` and `activated_at`,
6. commit the activation state.

## Roadmap points

Each implementation point should be a coherent, self-contained chunk that can reasonably be completed in a context-limited agent session.

Before finalizing the roadmap, sanity-check point dependencies and order.

Implementation points deliver code, test code and synchronized documentation. Do not schedule routine test runs or verification after every point. Only a small, fast, focused check needed to answer a concrete implementation question belongs there; no check is needed merely because a point ends. Mandatory commit hooks remain in force.

Schedule full test suites, broad integration/concurrency/installation acceptance, static analysis, image builds/scanner pipelines and full quality/security/AI gates in the final verification point, after all implementation work. Implement their test harnesses and scenarios earlier without running the broad acceptance. Then perform full verification, insert fixes if needed and repeat final verification under the existing failure procedure. Do not create an earlier acceptance point that duplicates the final gate or makes later implementation depend on its PASS.

Apply this check when creating, importing, reviewing, activating and executing roadmaps, regardless of whether the author is the user, ChatGPT, another agent or an external tool. If a supplied or existing roadmap requires broad verification earlier, stop and identify the conflicting point and this rule to the user before activation or further execution. Wait for the user to resolve the plan; do not silently run the checks, relabel them as lightweight, rearrange an agreed plan or treat normal roadmap precedence as approval of an exception.

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
- any necessary small, fast, focused checks pass; no broad verification is required at this stage,
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
- the user can later provide `php artisan runa:quality:status` output or relevant full logs.

The quality tool stores only the latest run.

If interrupted/crashed/rebooted:

- status becomes `interrupted`,
- show where it stopped,
- final verification reruns from the beginning.

## Closing

Before merge, on the roadmap branch:

- all implementation points are complete,
- full gate passes,
- application version/changelog/release metadata are finalized,
- roadmap moves `active/` -> `closed/`,
- status becomes `closed`,
- `closed_at` is set.

Then:

1. merge to `main` with a merge commit,
2. run a short sanity check on `main`,
3. create the immutable annotated application version tag,
4. push branch/main/tag as required,
5. publish the application release,
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

Final gate evidence records branch, commit, selected impact areas and results. Final release-only edits permitted by `docs/runa/handbook/workflows/releases.md` receive focused validation; substantive changes invalidate the prior result. Do not interpret a stale PASS as proof for changed runtime code.

Do not wait or poll after launching a background gate. Ask the user to return the status/log output when ready; an interrupted run is never a PASS.

## Execution handoff

The active application roadmap owns one optional `Execution handoff` section for substantial host/container transitions. Persist it on disk before recommending **Dev Containers: Reopen Folder Locally**; commit coherent work when applicable without marking an unfinished point complete. The matching template is in `docs/runa/handbook/workflows/roadmap-template.md`. Do not create a separate state store or copy this policy into every roadmap.

Record:

- Roadmap ID/current point, branch/base commit, worktree state, timestamp and transition direction.
- Reason, completed implementation/checks, evidence paths and remaining tasks.
- Expected environment, verified host checkout path (or explicitly unknown), Compose project/files, required tools/services and observed resource identities/state.
- Exact startup procedure/commands, relevant source links, intended targets and protected resources; no secrets.
- Verification commands, host completion criteria and remaining gates.
- Results/issues, removed/preserved resources, current service/configuration state and readiness to return.

The receiving session reads AGENTS, the active roadmap and handoff; verifies repository path, branch/worktree and actual relevant services before mutation. Host execution uses Docker/Python/Git and project containers for the application toolchain. Follow `docs/runa/handbook/development/editor-and-agents.md` for startup/recovery. Before returning, update the same handoff with results and whether **Dev Containers: Reopen in Container** is safe. The container session verifies current state and resumes without repeating completed work. Do not automate editor window switching. Point completion still requires implementation, docs, checks, commit and checklist synchronization.

Application release metadata belongs to `src/app/application.json` and `docs/app`; never bump protected src/runa/platform.json or publish to the Runa upstream repository. Follow [application release finalization](releases.md).

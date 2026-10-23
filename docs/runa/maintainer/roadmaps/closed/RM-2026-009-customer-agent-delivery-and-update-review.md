---
id: RM-2026-009
title: Customer agent delivery and project update review
status: closed
branch: roadmap/RM-2026-009-customer-agent-delivery-and-update-review
created_at: 2026-10-22
activated_at: 2026-10-23
closed_at: 2026-10-23
release_type: fix
proposed_version: 0.4.1
impact_areas:
  - development
  - production
  - tooling
  - security
  - ai_environment
  - documentation
---

# RM-2026-009 — Customer agent delivery and project update review

## Overview

### Goal and execution order

Deliver distinct owner and customer AI instruction sets, maintain both while developing Runa, and give the customer a self-contained agent environment for building applications on Runa. Extend development updates with a complete review of delivered project files and explicit consent to replace customized non-platform files. Review environment-file responsibilities and operation guidance.

Execute immediately after [RM-2026-006](../closed/RM-2026-006-module-framework.md) finishes verification, closure, merge and release. RM-006 is closed for release 0.4.0; this roadmap is active with owner approval on 2026-10-23. The owner approved creation of this draft on 2026-10-22; this is not approval to activate it or implement its scope during RM-006. Refresh the predecessor link when RM-006 closes, without changing its closed contents. The owner classifies this as corrective work: planned fix release **0.4.1** after RM-006's projected 0.4.0. Reconcile the actual released baseline at activation; no platform version or release is changed by this draft.

### Scope

- Audit and rewrite root AGENTS, supplied skills and references for owner work; author a separate customer instruction product under `docs/runa/maintainer/customer-agent/`.
- Deliver customer instructions in place of owner instructions during installation, preserve them in customer repositories and support reviewed updates without losing customer extensions.
- Enforce distinct owner/customer knowledge corpora, with empty customer Qdrant storage and first-start indexing of delivered customer knowledge only.
- Clarify point-scoped testing versus final background quality execution and the appropriate built-in status commands.
- Extend update review beyond Docker/Compose/devcontainer to all delivered project source, with file-level change reporting and accepted additions/replacements/removals outside protected platform source.
- Audit `.env*` responsibilities, templates, naming, generation, consistency and safe operator guidance.

### Exclusions

Do not implement business modules, require application modularity, add another AI runtime or rebuild the existing documentation hierarchy. `docs/runa/handbook` remains customer-facing and `docs/runa/maintainer` owner-only. Do not introduce CI, new dependencies without approval, automatic production platform updating, secret-bearing reports, automatic schema/data rollback, or a force/repair override for modified `src/runa`. Existing security, ownership, transaction, migration and release gates remain in force.

### Planning baseline and sources

Planning inspected owner branch `roadmap/RM-2026-006-module-framework` at `e188012`, with unfinished point-18 changes already present. Those changes remain owned by RM-006 and must not be mixed into this draft's implementation. Refresh current code and decisions at activation.

Relevant sources are root `AGENTS.md`, `docs/runa/maintainer/agent-contract.md`, `.agents/skills/**`, `docs/README.md`, public AI/workflow/quality guidance, `tools/application/repository_boundary.py`, knowledge configuration/corpus/startup, and `distribution/{bootstrap,installation,update,update_customization,update_execution,update_runtime}.py`. Inspect their existing tests and actual delivery paths before choosing implementation details.

Observed gaps: customer-facing frontend guidance currently directs reusable components into `src/runa`; root instructions combine application scope with owner guidance loading. The current updater rejects modified protected source, reviews infrastructure differences but preserves that infrastructure on continue, and does not expose a complete project file change/action inventory. Local root inventory contains six environment templates and eight private environment files; `.env.prod.backup` has no established runtime consumer from the focused source inspection. These are planning findings, not proof of complete audits or authorization to delete private files.

## Specification

### Decisions

- 2026-10-23 — Owner authorizes activation and execution, and explicitly approves preparing broad acceptance during implementation and running it only in final point 9. Earlier points permit only small, fast checks that resolve a concrete uncertainty; their scenario descriptions do not require early broad verification.

- 2026-10-22 — Owner specifies `release_type: fix` and `proposed_version: 0.4.1`, following RM-006 at 0.4.0. Treat this as correction of existing delivery/workflow/update behavior, not a feature release; refresh the projection if the actual release baseline changes.

- 2026-10-22 — Owner accepts this work as a separate roadmap immediately following module-framework delivery, rather than an inserted point in RM-006. Creation is authorized now; activation remains a separate lifecycle action after the predecessor completes.
- 2026-10-22 — Root `AGENTS.md` and `.agents/skills/` become owner instructions for writing Runa. Customer AGENTS/skills are authored under `docs/runa/maintainer/`, rewritten for application work, and substituted into customer delivery. The customer bundle is maintained as a product, not adopted as authority by the owner agent.
- 2026-10-22 — Developing Runa requires maintaining both instruction sets in the same relevant implementation point. Customer application agents separately maintain their local application guidance. Keep skills concise, practical and grounded in delivered code; use handbook references rather than duplicate documentation.
- 2026-10-22 — Customers may write application modules or write ordinary Laravel application code while consuming Runa modules. Both are supported. Customer agents must not manually modify protected platform files; unsupported requirements must use public extension points or be raised as platform limitations.
- 2026-10-22 — Customer Qdrant starts empty and indexes only delivered customer documentation and instructions at first development startup. No owner vectors/index/snapshots/data are delivered. Preserve customer indexes on reopen; production remains AI-free.
- 2026-10-22 — Modified `src/runa` remains an unconditional update blocker. Expand the review of other delivered project files and allow exact reviewed additions/replacements/removals after explicit operator consent. Customization outside protected source is reviewable rather than a permanent refusal; preserve application-owned work and private data.
- 2026-10-22 — Audit environment-file design and operation, including `.env.prod.backup`; simplify only where justified by consumers and secret boundaries, not merely to reduce file count.
- 2026-10-22 — Both owner and customer AI instructions must prioritize existing supported project commands. If no suitable command exists, propose adding one only for a genuine reusable operational need; a one-off check of a specific case does not justify another command.
- 2026-10-22 — Prevent agent-initiated bypass of the established test/quality workflow in both instruction products. Run supported checks through their documented project entrypoints, never substitute direct internal runners or detached log-only processes when a supported workflow exists. Scoped point checks remain scoped; full final verification uses the quality coordinator and its built-in status interface.

### Owner and customer instruction products

Keep the active owner entrypoint at root `AGENTS.md` with owner skills in `.agents/skills/`. Make the owner contract, roadmap locations, test/quality commands and release scope unambiguous. Remove conflicting application-only scope from the owner entrypoint without weakening platform rules.

Author customer `AGENTS.md` and a `skills/` tree under `docs/runa/maintainer/customer-agent/`. Review every existing skill and reference; retaining a copy without checking its application meaning is insufficient. Customer instructions describe good code and use of public Runa capabilities, never platform maintenance, owner commands or editing protected source. Reference code and extension seams before promising an API or behavior. In particular, application components and application-owned patterns remain in application source; extracting reusable UI does not authorize changes to Runa components.

Cover ordinary Laravel use and optional application modules, source/data/storage ownership, HTTP/API consumption, public UI/components, configuration, seeding, migrations, managed module contracts where applicable, testing, diagnosis, knowledge and quality. Load only relevant skills/references. Do not turn skills into a second handbook or load all references by default. Maintain one authority per rule; shared good-code guidance may have common authoring sources only if both delivered products remain complete and understandable.

Owner agents must assess both products when implementation establishes or changes a reusable rule or public capability, update affected guidance in the same point and record a reason when one product is unaffected. Customer agents must maintain their local AGENTS/skills for their application domain and established patterns without changing owner authoring templates or protected platform source. Update obsolete guidance instead of appending contradictory rules. Do not persist task noise.

Verify instruction discovery for Codex and Claude, using concise adapters where needed rather than competing rule copies. The owner bundle and customer authoring tree must not accidentally become interchangeable agent authority.

### Installation, repository propagation and updates of guidance

Customer development delivery materializes the customer bundle at root `AGENTS.md` and `.agents/skills/`, excluding the owner versions and maintainer authoring directory. Test the real acquisition/projection pipeline: sparse checkout alone must not discard the staged customer replacements or rematerialize owner instructions on reopen. Versioned customer repositories must retain the right instructions for ordinary teammate clones without an inherited acquisition receipt.

Treat delivered customer guidance as an identifiable release input so future review can distinguish shipped defaults from customer additions/edits/deletions. Do not erase customer rules or entire skill directories on update. Report conflicts and proposed changes; require reviewed reconciliation for customer-owned extensions. The report/consent mechanism below does not grant blanket authority to overwrite application instructions. Do not silently overwrite existing instructions when adopting the new layout in an older customer project.

### Knowledge isolation and first startup

Owner retrieval uses owner operating instructions and relevant owner/public knowledge. Exclude the customer instruction authoring bundle from operating-rule retrieval; owner agents may open it explicitly to edit or verify the product. Ensure indexed planning or documentation examples cannot promote customer templates into owner instruction authority.

Each fresh customer development project starts with its own empty Qdrant data and creates the configured collection/index from delivered sources at first startup. Deliver no owner Qdrant volume, index, snapshot, corpus payload or precomputed vectors. Check actual Compose volume identities and initialization behavior rather than relying on names or documentation claims. Do not clear existing owner/customer volumes or shared model caches to prove a fresh install.

The customer corpus includes the allowed delivered handbook/application documentation and installed customer AGENTS/skills. It excludes owner instructions, maintainer files, original authoring templates, secrets, runtime output and excluded source. Test empty state before initialization, successful first indexing and retrieval, absence of owner-only facts, removal of obsolete source identities and preservation on repeat startup. Subsequent source changes use incremental indexing. Application readiness remains independent of optional AI availability; dedicated knowledge doctor/evaluation and final AI gates remain strict. No Qdrant, model service or knowledge runtime is included or started in production.

### Built-in command precedence

Require this rule in both owner and customer AGENTS and relevant skills: discover and use the existing supported project command for the intended operation before choosing a direct internal runner, raw log reader or ad hoc script. Select the correct owner/application scope and documented host/container transport. Preserve command-owned guards, locks, reporting, exit codes and status tracking; do not silently bypass them or recommend a status command that cannot observe the launched process. For final quality monitoring, provide the appropriate built-in quality status command rather than substituting `tail`.

For tests and acceptance, explicitly prohibit bypassing a suitable supported entrypoint by launching its underlying script directly, detaching it or redirecting it to a standalone log as a replacement for the established workflow. Inspect the supported entrypoint and its scope before launch. A direct runner is permitted only when it is itself the documented scoped entrypoint or when a focused one-off diagnostic has no suitable supported command; explain that case and receive the result within the point. Its use never authorizes substituting it for final quality or handing the user an untracked background test. Do not claim that quality status observes a check launched outside its coordinator. Apply these requirements to both owner and customer guidance, and review command-selection examples for scoped tests, host acceptance and final verification.

When no suitable command exists, explain that limitation. Propose a new command only when the missing operation serves a genuine recurring or reusable need and adds value beyond existing commands. Do not create commands for task noise, one-off diagnostics or narrowly specific acceptance probes. Such checks may use a proportionate focused invocation without inventing a permanent CLI; receive their results under the ordinary point workflow. A proposal is not permission to expand implementation scope. Command precedence does not mean launching the entire quality graph for a scoped implementation check.

### Point checks and final quality behavior

Make the same execution distinction explicit in owner/customer contracts, skills and relevant existing workflow documentation:

- For an ordinary implementation point, run relevant proportionate checks, receive their results, fix failures and continue until the point is complete. The duration of a scoped check does not itself authorize ending the turn and delegating monitoring to the user. Respect real host handoffs and blockers separately.
- Only the roadmap's final full verification uses the established background quality handoff: launch the correct quality workflow, end the turn and avoid repeated polling. Give `python3 tools/maintainer/quality-status` for owner work or `php artisan runa:quality:status` for application work, with the documented host transport where necessary.
- Status commands read their own quality-run state; do not imply they track arbitrary standalone test processes. A raw `tail` command is not a replacement for the standard final quality status interface.

Synchronize overly broad background-gate wording while retaining the existing final verification, failure insertion and release rules. Do not invent a requirement to run the entire graph after each point.

### Complete delivered-file review and approved update actions

Retain independent original/target release acquisition and the absolute `src/runa` integrity gate. Any unexpected changed, added, missing or otherwise mismatched protected platform source blocks before mutation; no consent can waive it, including on resume.

Inventory the remaining delivered project against its installed release baseline and the selected target release. Cover current non-infrastructure files as well as Docker, Compose, devcontainer, build/bootstrap/tool configuration, environment templates, public docs and delivered instruction products. Account for mode/type changes and safe path handling as well as content. Classify local additions, edits and deletions, upstream additions/updates/deletions and overlapping changes. Review project additions without treating arbitrary untracked files as disposable release files.

Report exact relative file paths, local difference category, proposed action (add/update/remove/preserve/manual reconciliation) and conflicts, plus the existing affected-service/devcontainer impact where meaningful. Distinguish a local modification relative to the installed release from a change introduced by the target release. Customer code in `src/app`, application documentation in `docs/app`, private environment files/secrets, storage/database data, backups, caches and generated artifacts are not an unrestricted replacement set. Do not read/report secret values or scan the whole dependency/runtime tree just to claim project coverage.

Non-platform customization produces a review and an explicit continue/abort decision. Continue may authorize replacing or removing exactly the reviewed eligible delivered files with target release state, unlike the current infrastructure-preserving decision. Explain the potential extra reconciliation work before consent. Bind consent to the exact plan, local differences, selected release identities and affected actions; revalidate under the operation lock and require fresh review when inputs change. No mutation on abort or missing consent. Keep read-only modes read-only and support both interactive and explicit unattended decisions.

Preserve customer application work and local instruction extensions. Resolve conflicts with the operator rather than silently deleting custom files. Baseline bookkeeping must reflect the actual delivered customer projection, including substituted instructions, rather than mistake owner source for customer defaults.

Extend existing journaled staging, atomic replacement and failure/resume semantics to the approved multi-file actions. Do not replace the updater with a second installer, rewrite customer Git history, automatically update production from upstream Runa, or equate source restoration with schema/data rollback. Publish concrete manual reconciliation requirements. Production continues deploying the complete reviewed customer commit.

### Environment-file review

Map every root private env file and versioned template to its creator, readers, environment, required keys and permissions without exposing values. Current names to inspect are `.env`, `.env.testing`, `.env.dev.local`, `.env.dev.database`, `.env.prod`, `.env.prod.database`, `.env.prod.compose`, `.env.prod.backup` and their supplied templates.

Assess `.env.test.example` versus `.env.testing`, `.env.dev.example` versus `.env`, repeated application/initialization credentials, safe credential-change procedures and development/production checkout separation. Establish `.env.prod.backup` ownership/purpose before any cleanup; lack of a runtime reference alone is not deletion authority. Keep needed separation of Laravel credentials, Compose inputs and PostgreSQL administrative initialization secrets. Validate mismatch diagnostics and preservation on repeat install/recovery; never silently rotate existing database roles.

Make justified naming/generation/documentation improvements with a safe transition for existing files. If the audit exposes a significant change to infrastructure or secret architecture beyond these decisions, return to discussion. Do not merge files solely for appearance or add a generic secrets system.

## Implementation plan

- [x] 1. Refresh baseline and define instruction/delivery/update ownership contracts.
  - Requires RM-006 closed, merged and released, then explicit activation of this roadmap. Inspect actual source and local knowledge freshness, both instruction sets' intended readers, existing installer/projection/receipt/update behavior and env consumers. Record the delivered-file classification, guidance ownership/update strategy, compatibility transition and relevant decisions. Resolve significant unknowns before implementation.
- [x] 2. Rewrite owner instructions and author the concise customer bundle.
  - Requires 1. Audit all AGENTS/skills/references; implement owner-only operating scope and the customer product under maintainer docs. Ground application guidance in actual public code, support classic Laravel and optional modules, prohibit protected edits, define maintenance of both products during Runa work and local guidance during app work. Require built-in command precedence and justified proposals only for reusable missing operations, keeping one-off checks proportionate. Explicitly prohibit bypassing supported test/acceptance entrypoints with internal or detached log-only runners. Clarify point checks versus final background quality and tool-specific status. Verify public links, instruction discovery and representative agent tasks, including scoped/host/final command selection, bypass rejection and missing-command cases, without changing runtime behavior.
- [x] 3. Deliver customer instructions through installation and customer repositories.
  - Requires 2. Update verified projection/materialization and baseline records to substitute customer files and exclude owner instructions. Cover fresh acquisition, repeat/reopen, reference include/exclude variants, ordinary teammate clones and safe adoption for existing installations. Preserve local guidance extensions and synchronize installer/distribution docs. Prepare real-Git delivery regression scenarios for final verification.
- [x] 4. Isolate knowledge products and verify empty customer Qdrant startup.
  - Requires 3. Update source allowlists/metadata so customer authoring templates are not owner operating guidance and only delivered customer sources enter customer retrieval. Prepare final acceptance scenarios for fresh empty isolated storage, first-start corpus/doctor/retrieval, no owner payloads, repeat preservation, incremental changes, optional-AI degradation and production exclusion. Preserve existing knowledge/model caches and record scoped disposable resource cleanup.
- [x] 5. Implement complete delivered-file differences and proposed update plans.
  - Requires 3. Compare installed/default/target projections across non-platform delivered files, including instruction products. Report exact local/upstream differences and add/update/remove/preserve/reconcile actions, protected/application/private/generated ownership and infrastructure impact. Retain unconditional `src/runa` mismatch refusal and secret-safe output. Verify read-only operation, path/type handling, customer additions/deletions and target removal conflicts.
- [x] 6. Execute explicitly approved non-platform updates with safe recovery.
  - Requires 5. Extend consent fingerprints, lock-time revalidation, staging and journal/resume for exact eligible file actions. Test missing consent/abort without mutation, accepted overwrite/deletion, changed-plan renewal, interruption/resume and preservation of application source/docs/instruction extensions/secrets/data/Git history. Keep protected-source refusal absolute and production customer-commit deployment unchanged. Update reconciliation/operator guidance in the same point.
- [x] 7. Audit and simplify environment-file handling where justified.
  - Requires 1 and 6. Complete the creator/consumer/secret-boundary inventory; resolve backup-file provenance, naming/template consistency and duplicated-credential checks. Implement justified compatible changes and migration guidance, with proportionate tests for existing settings/repeat/recovery and no secret output or unapproved data deletion. Synchronize both instruction products if operator workflow changes.
- [x] 8. Prepare integrated customer delivery and update acceptance.
  - Requires 2–7. Implement the scenarios and harness for real isolated customer installation, instruction substitution, teammate clone, clean first knowledge initialization and both supported app structures. Prepare complete update review, consent/abort, changed local state, accepted upstream additions/replacements/deletions, preserved customer extensions and failure/resume, absolute modified-platform rejection, and built production image isolation checks. Do not run broad acceptance here; execute it in point 9.
- [x] 9. Preserve completed file actions when renewing a changed resume review.
  - Static final integration review found that a fresh consent plan could turn already completed, normalized file actions into pending replacements with obsolete before-state. Retain unchanged completed actions and renew only real new differences; existing changed-resume regression covers the mixed completed/changed case. No runtime behavior or scope decision changes.
- [x] 10. Repair delivered handbook references, fixture baseline and security review evidence.
  - Final gate failed on customer handbook links to owner-only frontend references, a workspace-only regression whose target still changes services, and stale security evidence. Centralize delivered frontend contracts in the public handbook, keep owner skill routing and customer instructions synchronized, align the isolated target baseline, and perform a content-bound review of this roadmap's actual changed surface. Preserve all hard gates and rerun final verification from the beginning.
- [x] 11. Reconcile the fixture customer service before team publication and production acceptance.
  - Attempt 2 passed the actual reviewed update but later production fixture setup still assumed the pre-update Redis descriptor existed. Add explicit customer reconciliation after the verified updater completes and before the customer commit/push. Keep the updater's exact target application and real custom-service deployment checks intact. Add a focused transition regression and refresh changed-surface evidence.
- [x] 12. Reconcile fixture environment instructions before publication and production provisioning.
  - Requires 11. Restore both public environment templates together with the customer service after approved target replacement. Cover actual file replacement, publication/clone, production provisioning and repeat preservation with a focused regression; review the remaining downstream deployment assumptions. No updater policy or private environment mutation.
- [x] 13. Full verification, release finalization, merge and final report.
  - Requires 1–12 complete and committed. Review current-state docs, both instruction products, source ownership, knowledge/installer/update/env procedures and release metadata against code; record sources/findings/disposition. Execute the integrated acceptance prepared in point 8 as part of the full applicable owner quality workflow, security and production isolation gates through the established background interface; end the turn and provide the built-in owner status command. Insert fix points and rerun full verification from the beginning if needed. Finalize version/changelog, refresh knowledge, close/merge with a merge commit, run main sanity, publish immutable tag/release and remove the roadmap branch under the existing workflows.

## Acceptance and planning status

Every implementation point requires any necessary small, fast, focused checks, synchronized current-state guidance, a logical commit and checklist update. Test actual customer projections and runtime behavior; static path checks alone do not prove instruction usability, fresh vector storage or safe update recovery. Activation is authorized; no implementation, release or verification PASS is claimed before execution.

## Execution evidence

### Point 1 — baseline and ownership

- 2026-10-23: main/origin/main both dd91880, clean worktree; RM-006 closed and VERSION 0.4.0. Release projection remains the owner-approved fix 0.4.1. Knowledge status current (145 documents, 1987 chunks); focused retrieval confirmed release/update ownership sources.
- Reviewed bootstrap acquisition/projection, repository boundary, installation scaffold, updater preflight/customization/journal, knowledge configuration/corpus and environment consumers. Existing updater preserves infrastructure after consent and covers no general delivered-file actions. Existing sparse acquisition delivers root agent defaults without substitution.
- Ownership and compatible transition contract: [customer delivery](../../tooling/customer-delivery.md). New-bundle releases use staged customer substitutions, historical releases retain historical projection; modified existing instructions require reconciliation. Per-file execution extends the existing journal rather than a second installer. Private unexplained backups stay preserved. No data reset or runtime mutation performed.

### Point 2 — instruction products

- Root AGENTS now owns platform work; the maintainer contract is a compatibility link to one authority. Both products have thin CLAUDE discovery adapters. Customer AGENTS and all nine concise skills/references are authored separately, covering ordinary Laravel and optional modules, public UI composition, data/storage/configuration ownership and supported commands.
- Reviewed existing skill/reference/provenance content and actual UI facade, HTTP wrapper, App provider/route layout and module guide; dispositions are recorded in the customer-delivery guide. Owner skills retain engineering detail and now require assessment of both products. Public documentation placement and workflow rules are synchronized. No runtime behavior changed. Broad instruction/delivery acceptance remains final-point work.

### Point 3 — verified instruction delivery

- External self-contained bootstrap captures regular release-authored customer inputs only after full Git object/file verification, substitutes root AGENTS/CLAUDE and the complete skills product after sparse projection, and stages additions/replacements/deletions. Customer commits and ordinary clones retain their instructions; receipt hashes record substitutions without replacing independent release acquisition. Historical releases remain supported without inventing customer defaults. Existing-project adoption is reserved for reviewed update reconciliation.
- Added real-Git scenarios for both reference selections, sparse reapply and a receipt-free teammate clone; public documentation fixtures now consume the customer product. Full regression execution remains in point 9. Updated acquisition and customer guidance; no database/environment changes.

### Point 4 — knowledge product isolation

- Corpus discovery unconditionally excludes customer authoring templates even in broad owner allowlists. Incremental refresh removes their previously indexed source identities. Customer templates already describe installed-only sources and preserved project storage; owner ingestion references and public knowledge procedures are synchronized. No model/topology/schema redesign is needed.
- Prepared real fresh customer acceptance in the existing isolated installation fixture: inspect actual new Qdrant volume, assert empty collections before independent startup, initialize through the real job, doctor/eval/retrieve, assert absence of owner paths/content, repeat-preserve and incrementally add/remove application sources. The inspected existing model cache is external and preserved; the outer fixture owns exact disposable cleanup. Existing optional-AI degradation and production isolation remain final checks.
- Existing Compose default is runa-dev; installation checks reject another checkout's containers. Acceptance explicitly uses a unique project identity and new Qdrant volume, rather than reusing or clearing owner data. No Docker resources created during implementation.

### Point 5 — complete delivered-file review

- Added bounded release-tracked three-way inventory, with mode/type/content identities and exact per-file actions. Application/private/generated/owner/protected ownership is explicit; no dependency/runtime traversal or secret-value reporting. Modified instructions and collisions with customer additions become preserved manual reconciliation. Other eligible local edits may be replaced only by exact reviewed consent. Release identities participate in the fingerprint.
- Preflight exposes complete file/action/conflict inventory plus existing infrastructure service/workspace impact, and no-op considers pending file review. Prepared regressions for upstream additions/deletions, local overlaps, instruction extensions, read-only preservation, mode changes and symlink parents. Public updater guidance describes the new review; execution is the next point.

### Point 6 — consent, replacement and recovery

- Extended the existing operation journal with per-file approved actions, private incoming/previous bytes, durable rename states and re-consent history. Resume rechecks independent release identities and retained bytes before normalizing known transitions; unknown content or unsafe filesystem boundaries blocks. Existing src/runa checks remain unconditional. Abort/missing consent occurs before locking/journaling/mutation; unattended continue needs the inspected exact fingerprint.
- Completion reports preserved manual instruction conflicts and infrastructure rebuild/recreate/reopen follow-up. Updated obsolete infrastructure-preservation claims and prepared file add/replace/remove, instruction extension and rename-interruption regressions. Existing actual-Docker update acceptance now expects the approved target infrastructure. Application source/data/locks/secrets/Git history stay preserved; no runtime operation executed during implementation. Both instruction products already require exact reviewed consent and preserved extensions; no further product change needed.

### Point 7 — environment ownership and consistency

- Audited eight private root inputs and six templates by key names/permissions only, plus dev environment creation, PostgreSQL init, production provision/validation, Laravel template coverage and backup-preservation regression. All existing private files are mode 600. No values or private content persisted.
- New public environment-file guide records creators/readers, template/runtime naming, role separation, repeat preservation and intentional credential-change procedure. No renaming/merging is justified by actual consumers. .env.prod.backup has BACKUP_DIRECTORY/BACKUP_RETENTION keys, no active Runa consumer and explicit external-backup preservation coverage; its historical local creator remains unproven, so it is preserved without adoption/deletion.
- Development preparation now rejects unsafe secret-file types/permissions and test/init credential mismatch without printing values or rotating existing roles. Prepared repeat/mismatch/link regressions. Owner and customer Docker references and editor guidance synchronized. No private file, DB, volume or running service changed.

### Point 8 — integrated acceptance preparation

- Extended actual isolated installation/update/team acceptance with fresh empty customer Qdrant initialization and retrieval, real approved non-infrastructure file addition/replacement/removal, preserved local AGENTS extensions, abort/no-consent and failure/resume. Existing bootstrap contracts cover include/exclude reference variants, sparse reapply and ordinary receipt-free clone; existing module/native generator/customer tests cover both application structures. Production acceptance and scanners remain in the final graph.
- Documentation validation now checks authoring-template links at their installed logical paths with the public boundary enforced, instead of treating root-relative customer instructions as owner-directory links. Added a regression for public/owner link rejection and live retrieval exclusion of authoring templates. Synchronized maintainer README active state and reader scope. No broad tests or Docker resources launched.

- Integrated review also binds untracked infrastructure inputs already inspected by the existing Compose review to exact hashes in the file inventory; a regression rejects stale consent after their contents change. Unrelated untracked/private/dependency trees are never scanned or treated as disposable.

### Point 9 — final review correction

- Final source review found a resume-consent bookkeeping defect: renewing one changed local file recreated normalized completed actions with obsolete before-state. New renewal logic retains identical completed/planned action identities and archives only superseded actions. The existing changed-resume regression now exercises a mixture of completed VERSION and changed Compose inputs. The focused documented update-execution regression passed (1 test, 0.499s, container transport). Full final verification has not yet started.

## Current-state documentation review

- Reviewed root README/AGENTS/CLAUDE, docs/README and maintainer README/contract; both instruction products and owner domain references; public roadmap/skills/knowledge/editor/environment/update/reconciliation guidance; owner bootstrap/update/ownership procedures; quality graph and release workflow against current source. Historical closed roadmaps and dated evidence remain unchanged.
- Disposition: corrected obsolete claims that updater replacement is platform-only or preserves every infrastructure file, added the external package input update_files.py and required unattended fingerprint to operator examples, retained dependency workspace ownership, and refreshed obsolete future-release wording. Customer authoring links are validated at installed public locations; owner retrieval excludes them. Application readiness and production AI exclusion remain unchanged.
- Current platform VERSION stays 0.4.0 until a full PASS. The approved fix target is 0.4.1. Only final verification/release remains open; no release, merge, tag or publication PASS is claimed. No implementation Docker resources were created or removed; existing development/test data, Qdrant/model caches, backups and reports remain preserved.

### Final verification attempt 1 — failed

- Commit fc308af; total elapsed 1361.6s. Failed: installation-host-acceptance and documentation-ownership-contracts (missing installed frontend tables reference), update-preflight-contracts (target service changes in workspace-only fixture), expanded-security-review (evidence still bound to previous roadmap). Backend 418 tests, PHP static analysis, dependency audits, production acceptance/image scans, update execution, bootstrap, knowledge doctor/evals and remaining completed gates passed. PASS results do not waive a fresh full run after fixes.
- Logs remain in .runtime/quality; fixture logs confirm scoped disposable containers/volumes/image tags were removed, with active development/test identities and Qdrant/model caches preserved. No release or closure performed.

### Point 10 — failed-gate repairs and changed-surface review

- Moved the detailed component/form/table/motion/page contracts into delivered handbook development/frontend guides; owner skill references are thin routes to that single authority. Updated all public references to the formerly owner-only files, PostgreSQL skill discovery and the customer frontend skill. Customer projection regression passes with only the installed customer bundle.
- Aligned the workspace-only updater fixture's original/local/target service definitions; its focused regression passes and actual upstream service changes remain reported.
- Actual security review covered all 21 changed non-document code/instruction/test files. Found and repaired pre-generation dangling secret symlinks for .env/.env.dev.database/.env.testing; regression passes for all three, before any target creation. Review report and content-bound evidence are retained under .runtime/verification/RM-2026-009-final and .runtime/security-review.json. No scanner suppression or gate weakening.
- Focused documented container checks: customer projection 1 test PASS (0.081s), infrastructure workspace review 1 test PASS (0.109s), dangling secret links 1 test PASS (0.001s). Full installation/knowledge/update acceptance awaits the new full gate; no scoped test result substitutes for it. Current-state documentation review includes the new public guide locations and synchronized owner/customer routing.

### Final verification attempt 2 — failed

- Commit 60ff410; total elapsed 1658.3s. Only installation-host-acceptance failed: KeyError redis during subsequent production fixture setup after the accepted release removed the custom descriptor. Actual fresh empty Qdrant initialization/retrieval/incremental preservation, real non-platform file actions, instruction-extension preservation, abort/no-consent, failure/resume and customer commit/push had passed. All other gates passed, including the repaired documentation/updater contracts and security evidence.
- Fixture logs confirm scoped dev/team/simulation resources were cleaned and active development identities/data/model caches preserved. Keep the roadmap active and rerun the complete gate after the fixture correction.

### Point 11 — customer service reconciliation in integrated acceptance

- The actual updater continues applying the exact approved target Compose. The fixture now performs an explicit subsequent customer reconciliation of its Redis descriptor before Team commits/pushes the complete customer project and production acceptance consumes that descriptor. Existing release services cannot be overwritten by this fixture step.
- Focused documented container regression passes (1 test, 0.286s): approved replacement removes the prior descriptor, follow-up identifies Redis, customer reconciliation restores only that service, and an attempted collision with the target app service is refused. No broad acceptance was run inside the fix point.
- Source security review refreshed for the two changed acceptance files: fixture-local trusted JSON manipulation, explicit collision refusal, no changed production/updater/secret boundary or subprocess construction. The previous full review remains the baseline; local attempt-3 evidence binds the current source. Owner/customer instruction products are unaffected because this is acceptance orchestration for their already documented manual reconciliation contract.

### Final verification attempt 3 — failed

- Commit d24b439; total elapsed 2058.3s. Only installation-host-acceptance failed (828.9s): production provisioning received no empty TEAM_RELEASE_MARKER instruction because approved template replacement correctly removed the fixture-local additions. Point 11 reconciled the service descriptor but omitted its two public environment templates. All other gates passed; this is not a final PASS.
- Real fresh customer knowledge initialization, updater failure/resume, publication, teammate initialization and initial production TLS/SMTP rejection/resume passed before the late deployment assertion. Scoped disposable resources were removed; active development identities/data, Qdrant/model caches, backups and reports were preserved. The failed installation log is archived under .runtime/verification/RM-2026-009-final/attempt-3-failures before the next run.

### Point 12 — complete customer fixture reconciliation

- One explicit fixture reconciliation now restores the customer service and its empty instruction in both public templates after exact approved target replacement. It validates service/key collisions before any write and does not touch private settings. Added an immediate integrated assertion before publication; retained the strict downstream production assertion.
- Expanded the focused regression across real updater file actions, Git publication/ordinary clone and actual production environment provisioning. It proves release templates are reset first, both restored templates are committed, private development settings stay local, fresh production receives exactly one empty instruction, and repeat provisioning preserves manually supplied values and database credentials. Collision refusal preserves all three public files. Focused documented container check: 1 test PASS (0.432s).
- Reviewed the remaining Team deployment path: its two real deployments, exact customer revision/data/seed checks, service environment/health, both owners' storage and production source equality remain intact. No additional fixture-local prerequisite was identified. Full runtime deployment acceptance remains part of the next complete gate.
- Owner/customer instruction products need no change: only acceptance orchestration changed for the already documented manual service/template reconciliation procedure. No product runtime, scanner policy, dependency, migration or container topology changed. Security review retains the prior reviewed surface and adds this fixture-only change; no new blocking findings.

### Execution handoff — verification attempt 4

- Commit point 12 and refresh staged durable knowledge before the supported native-host command python3 tools/maintainer/quality-roadmap --background. End the turn after launch and provide python3 tools/maintainer/quality-status. Do not apply RM-2026-010's proposed flags or reuse earlier passing gates. Release/closure remain pending a complete PASS.


### Final verification attempt 4 — passed

- All 42 selected owner quality checks passed on source commit 9f02ec7ab78b9316c18a0497fc236ae09b42d06b, branch roadmap/RM-2026-009-customer-agent-delivery-and-update-review. Elapsed 2183.928s (36m 24s); completed 2026-10-23 at 16:09:48 Europe/Warsaw. The only later pre-closure tree change is the separately committed RM-2026-011 draft; verified product source is unchanged.
- Evidence archived at .runtime/verification/RM-2026-009-final/attempt-4-pass/quality, including coordinator state and every gate log. Backend: 418 tests, 2473 assertions. Static analysis, audits/secrets/image scans, exact changed-surface security review, owner/customer contracts, fresh Qdrant initialization/retrieval, knowledge doctor/evals and documentation gates pass.
- Actual installation/update/team acceptance now completes through customer service/template reconciliation, ordinary teammate initialization and repeated exact-customer production deployment, preserving both owners' data/storage and private settings. Synthetic failures/resume, TLS/SMTP and production AI isolation remain covered. These disposable production simulations are not real production deployments.
- Cleanup evidence in installation-host-acceptance, production-acceptance, repository-container-scan and host acceptance logs records scoped removal of test-created containers/networks/image tags/volumes. Active development identities were preserved, allowing documented natural queue-worker lifetime rollovers; development/test data, rollback resources, Qdrant/model/shared build caches, backups and reports remain protected.
- Closure review: inspected README/AGENTS and both instruction products, public guides and owner release/roadmap metadata against the completed implementation; no new capability/procedure change follows PASS. Final edits are version/changelog/checklist/closure evidence, checked maintainer state and predecessor/status wording in planning-only successor drafts. Root VERSION and platform manifest become 0.4.1; application version is preserved. Focused docs/release checks and knowledge refresh precede commit, and repeat on main after merge.

## Final report

RM-2026-009 delivers fix release 0.4.1: separate verified customer instruction delivery, complete consent-bound delivered-file review/execution with resumable source recovery, safe private environment checks and synchronized public frontend/operator guidance. Inserted fixes covered consent renewal, public references/fixture baselines/security evidence and complete customer service/template reconciliation. All 42 final gates pass.

No product dependency addition, database migration/reset, destructive data change or real production deployment occurred. Temporary acceptance resources were scoped-cleaned while active data, rollback material, caches, backups and reports were preserved. Existing owner-only PostgreSQL/gosu accepted-risk boundaries remain unchanged; the scan PASS is not blanket customer production risk acceptance. RM-2026-010 and RM-2026-011 are planning-only follow-ups, not activated work.

Release metadata and closure are finalized on the roadmap branch. Merge-commit/main sanity, immutable v0.4.1 tag, push/publication and branch deletion follow the established release workflow; their actual results are reported in chat without modifying this closed record after merge.

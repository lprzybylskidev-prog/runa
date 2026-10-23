---
id: RM-2026-009
title: Customer agent delivery and project update review
status: active
branch: roadmap/RM-2026-009-customer-agent-delivery-and-update-review
created_at: 2026-10-22
activated_at: 2026-10-23
closed_at: null
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
- [ ] 9. Full verification, release finalization, merge and final report.
  - Requires 1–8 complete and committed. Review current-state docs, both instruction products, source ownership, knowledge/installer/update/env procedures and release metadata against code; record sources/findings/disposition. Execute the integrated acceptance prepared in point 8 as part of the full applicable owner quality workflow, security and production isolation gates through the established background interface; end the turn and provide the built-in owner status command. Insert fix points and rerun full verification from the beginning if needed. Finalize version/changelog, refresh knowledge, close/merge with a merge commit, run main sanity, publish immutable tag/release and remove the roadmap branch under the existing workflows.

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

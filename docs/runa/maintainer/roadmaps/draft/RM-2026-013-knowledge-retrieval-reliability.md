---
id: RM-2026-013
title: Knowledge retrieval reliability and agent grounding
status: draft
branch: roadmap/RM-2026-013-knowledge-retrieval-reliability
created_at: 2026-10-28
activated_at: null
closed_at: null
release_type: maintenance
proposed_version: 0.6.1
quality_groups: docs,tooling,backend,development,knowledge,installation,updates,production,security
quality_rationale: Knowledge command contracts, locks, corpus ownership, provenance, retrieval and agent guidance affect delivered development tooling. Include real customer acquisition/update, production isolation and security evidence alongside strict knowledge and tooling checks; preserve mandatory checks and all actual prerequisites. Reconcile the selection with the implemented scope before final verification.
impact_areas:
  - ai_environment
  - tooling
  - development
  - documentation
  - security
---

# RM-2026-013 — Knowledge retrieval reliability and agent grounding

## Overview

### What and why

Make the existing local knowledge system useful for architectural history,
cross-module reasoning and consistency audits through supported commands. Improve
both retrieval mechanics and the agent workflow that consumes them. Preserve
Qdrant as a rebuildable development aid, with source documents as authority.

Three owner-requested exercises exposed concrete limitations: recovering five
earlier decisions, explaining Auth/Permissions/Audit integration, and auditing
architectural knowledge for superseded or uncertain claims. The exercises used
Qdrant content only. Repository workflow documents were consulted afterwards to
prepare this draft; no implementation source was inspected for those analyses.

The owner explicitly requested version **0.6.1** and confirmed
`release_type: maintenance` on 2026-10-28. This is a PATCH increment after the
planned compatible feature **0.6.0**, not after today's VERSION 0.5.0. The required
predecessor is [RM-2026-012](../active/RM-2026-012-runa-architecture-refactor.md).
Refresh that link, actual baseline and projected arithmetic after its release.
This draft does not activate work, change VERSION or extend RM-012 implementation.

### Scope

Supported evidence retrieval and context expansion; read concurrency and truthful
lock diagnostics; explicit temporal/provenance metadata; current/history/audit
retrieval behavior; source documentation reconciliation; representative retrieval
evaluations; owner and customer agent consumption guidance. Include the existing
Python engine and narrow Artisan adapters required to expose these capabilities.

### Out of scope

No business-module behavior, authorization or Audit redesign. No new platform
runtime dependency on knowledge services, production AI, cloud provider, LLM answer
service, automatic architecture adjudication, chat ingestion, full-code indexing,
CI, new quality coordinator or mandatory host installation. No new dependency,
model replacement, reranker, HyDE or agent framework is pre-approved. Do not tune
Qdrant internals without evidence of a storage/filtering bottleneck.

Closed roadmaps and dated evidence remain immutable. Do not turn every historic
wording difference into a defect or promise complete contradiction detection.

## Specification

### Decisions

- 2026-10-28 — Owner requests a separate 0.6.1 roadmap based on all three Qdrant-only
  exercises and explicitly confirms maintenance classification. Activation remains
  a later owner decision after RM-012 closes, merges and releases.
- 2026-10-28 — Improve agent query formulation, evidence handling and uncertainty
  reporting alongside the engine. The exercises do not establish that the current
  embedding model must be replaced. Measure retrieval gaps before proposing that
  separately reviewed change.
- 2026-10-28 — Correct the first exercise's preliminary diagnosis: existing
  `--explain` already returns full retrieved content and structured metadata.
  The confirmed gap is convenient supported evidence reading/context expansion,
  not absence of full-text retrieval. Existing filters and score diagnostics also
  already exist; extend them rather than rebuilding them.
- 2026-10-28 — Retain the single collection, embedding-space invariants,
  incremental reuse, branch/pending-write safety, owner/customer corpus separation,
  optional application-independent startup and production prohibition.
- 2026-10-28 — Implementation points deliver code, test scenarios and synchronized
  guidance. Broad suites, live concurrency/installation acceptance, strict analysis,
  image builds/scans and full AI gates run only in the final point. Existing hooks
  remain mandatory; no earlier full-verification prerequisite is authorized.

### Read-only exercise evidence and limits

At exercise time, status reported `current`, 188 documents, 3088 chunks, zero
changed/deleted/missing/orphan chunks, and no interrupted/branch/parser drift.
That establishes reported source synchronization, not factual correctness.
The indexed context was `roadmap/RM-2026-012-runa-architecture-refactor`; the sampled
payloads recorded Git commit `aeba1e9a2f24f9e747dc2bf494dca87af5d2572b`.
This is provenance, not proof that every claim applies to the latest release.

Search used the existing owner knowledge composition root, metadata filters and
`--explain`. Full selected payloads were also read through Qdrant REST scroll.
No index/rebuild/source edits or business operations were performed during the
exercises. REST access was diagnostic; agents should not need that bypass for
ordinary research. The audit sampled the relevant roadmaps, module guides,
changelogs, foundations, production and knowledge documentation; it was not an
exhaustive corpus or implementation audit.

#### Exercise 1: earlier architectural decisions

Recovered the development-only RAG baseline, independent application/AI startup,
platform/customer ownership, complete customer-revision deployment and atomic
module transitions. A concrete chronological correction was recovered:
RM-005 Decisions on 2026-10-20 excluded platform migrations from deploy;
the 2026-10-21 decision explicitly included scoped forward platform data steps.

Sources in Qdrant:

- `docs/runa/maintainer/roadmaps/closed/RM-2026-001-development-environment.md`,
  Overview/Why and Knowledge/RAG stack:
  `0e4a596a-63ae-5ac0-9812-55267106cc46`,
  `d1d3dea2-2aae-5c49-90e6-715f9b1f5886`.
- `docs/runa/maintainer/roadmaps/closed/RM-2026-003-development-infrastructure-improvements.md`,
  Application and knowledge lifecycle:
  `8fe3070d-c6b1-5393-a784-4be0703689f2`,
  `1ee28d1a-fe83-57d1-99c9-21f1f47d8039`.
- `docs/runa/maintainer/roadmaps/closed/RM-2026-005-platform-installation-updates.md`,
  Decisions: earlier `a80e3acd-eb26-5489-9440-f95ccda25202`, later
  `4104f6c5-6b69-57f0-98e1-ccd6624719b6`.
- `docs/runa/maintainer/roadmaps/closed/RM-2026-006-module-framework.md`,
  Dependencies and administrative transitions:
  `c4378550-cb62-583b-8c06-fa3f6a3d8904`.

No retrieved evidence explained why Qdrant was selected over another vector
database. Preserve that absence; do not manufacture alternatives or rationale.

#### Exercise 2: Auth, Permissions and Audit integration

**Retrieved facts:** Auth owns identity, authentication/security state and current
eligibility; Permissions owns role/direct grants and authorization assignments;
Audit owns append-only safe history. Auth requires Audit; Permissions requires
Auth and Audit; Audit has no reverse Auth/Permissions module dependency.
Optional actor-name enrichment belongs to the framework bridge and cannot make
Audit persistence depend on Auth. Public UUIDv7 values and guarded contracts cross
module boundaries; foreign SQL/models/relations/FKs do not. Grants never replace
eligibility, session/security requirements, module availability or record policies.
Business changes and required Audit history share the default transaction;
bulk/pivot changes need explicit manual events. Private identity/authorization
resolution is trusted non-recursive framework integration, not a customer API.

Sources in Qdrant:

- `docs/runa/handbook/modules/auth/README.md`, Public values and access — chunk ID
  `0b9498ac-6015-569b-bee2-212b77543128`;
  Independent eligibility: `78c19895-db1a-57d4-95c3-4049ee992613`.
- `docs/runa/handbook/modules/permissions/README.md`, Owned storage and
  authorization: `22cca14c-486f-5cc7-b79b-9933082f9ae9`,
  `99586935-0809-59d0-b4b8-bcb0dfe4e41b`.
- `docs/runa/handbook/modules/audit/README.md`, Core management and optional
  actor labels: `56873a8e-5f4d-5d92-8c9e-a4f5bbaf3801`;
  manual aggregates: `a38dc79d-3a65-538b-964a-48eba21de3d2`.
- `docs/runa/maintainer/architecture/core-module-foundations.md`, Transaction
  ownership and Audit contracts: `ed81f78d-704a-5fa4-8f7d-3e5ccd377f64`;
  non-recursive authorization: `82fc9396-c3a8-559e-a040-f7bfbfeb3732`.

**Agent-derived hypothetical, not an observed defect:** an application feature
copies a user's access by writing Permissions pivots directly, joins Auth tables
to check eligibility, and queues a later Audit event. This would bypass owning
authorization/concurrency rules and lose transactional history. A compliant
design uses the guarded owning access-copy/assignment API, its reviewed preview
and revisions, approved Auth public reads and the owning transaction's manual
Audit event. It preserves record policies and any required proof; it does not
call reserved identity bridges or add a reverse Audit dependency. Evaluate
retrieval of those constraints, not whether generated prose matches this wording.

#### Exercise 3: consistency and currency audit

| Confirmed observation                                                                                        | Evidence and precedence                                                                                                                                                                                                                                                                                                                                                                                                                                                           | Classification and disposition                                                                                                                                                                                                                                                            |
| ------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Deploy excluded platform migrations on 2026-10-20 and included required forward steps on 2026-10-21.         | RM-005 chunks above; current `docs/runa/handbook/production/deployment.md`, `d7d62586-a208-5e5c-aad5-38125778239d`, agrees with the later decision.                                                                                                                                                                                                                                                                                                                               | Legitimate evolution inside immutable history. Present both for history; prefer the later applicable decision and current guide for the delivered behavior.                                                                                                                               |
| RM-001 prescribed production sparse checkout; RM-005 later removed production-only source reduction.         | RM-001 Production isolation `d6fc8403-24fa-54dc-85c9-6640536dfa27`; RM-005 Decisions `ff02f8e3-2c14-5f7f-8b98-6f51cb64676a`; current production architecture `ad767569-e12e-59da-ab22-ae99a992838c`.                                                                                                                                                                                                                                                                              | Later policy retains the complete customer source revision on the host while excluding AI from runtime images/services. Distinguish host source from running production; preserve the old roadmap.                                                                                        |
| Module guides/foundations retain pre-stable version claims while closure and changelogs record stable 1.0.0. | Audit README `2baf2c73-8151-5f93-b1d1-9e70bfbc6a01` says 0.5.0; Auth README `5d3308df-ef27-58d3-90c8-88bbda0ee4e7` says 0.21.8; Permissions README `b1a400a9-40c1-5ce5-93bf-50ea58282a3f` says 0.5.9. RM-011 Execution status `2ad2808a-1ee8-5968-a8f5-a714bd979892` says all three ship 1.0.0 with aligned constraints; their 1.0.0 changelog chunks are `2ef0afc8-ef52-5f3d-be03-76acc4387dbd`, `4185d8e6-65ef-558e-8c19-64e8b4f7ea7a`, `fd982711-596a-50c9-bfba-81cd1734930e`. | Confirmed documentation mismatch, not proof of a runtime defect. Closure/change records outweigh older delivery prose for the recorded release. Verify actual source/version applicability during implementation and correct residual current guidance after RM-012's own reconciliation. |
| A query for current module versions ranked old foundations first.                                            | `current Auth Audit Permissions module versions dependency compatibility`, documentation filter, top-k 6: first chunk `19bcbe46-6101-59f9-b613-934a1bd7a0d5` describes Auth 0.13.0 and Permissions 0.5.1. A historical acceptance review also surfaced: `d375826d-4eec-53ed-933b-aebd88faf257` describes Auth 0.12.0.                                                                                                                                                             | Historical/current meaning is not reliably conveyed by `document_type=documentation`. Preserve dated evidence and make its applicability visible; retrieval score is not authority.                                                                                                       |
| Search can return an intermediate execution note saying point 9 is open, despite later completion evidence.  | Active RM-012 query for knowledge refactoring returned `2400345e-8f0f-5a21-b42d-da534b2bc9f6`, dated 2026-10-27, explicitly an in-progress continuation.                                                                                                                                                                                                                                                                                                                          | The fragment alone cannot establish current execution state. Fetch the authoritative status/checklist and later outcome before concluding; active-roadmap status does not make every paragraph current.                                                                                   |
| Reported freshness cannot certify factual currency or the date a statement took effect.                      | Knowledge Freshness `225aed47-edae-507d-97bb-7ac3f4efd11d`; sampled older payloads lack `indexed_at` while retaining hashes/Git context.                                                                                                                                                                                                                                                                                                                                          | Report source synchronization separately from applicability. Missing effective dates remain unknown; do not treat ingestion time, Git commit or document age as approval/release dates.                                                                                                   |

No SQL/code inspection established actual installed module versions. Documents
without explicit applicable version/date cannot be certified current from this
audit alone. The architecture authority hierarchy remains unchanged; these are
Qdrant-only conclusions with an explicit verification limit.

### Supported evidence access and context

Add a narrow read operation to the existing knowledge command family, exposed
through the normal Artisan registry, for a returned chunk ID. Support bounded
section/neighbor expansion and structured output for search/read/status. Keep
normal search concise and preserve existing `--explain`, filters and exit codes.
Finalize exact option spelling in point 2 without creating a second CLI registry.

Return content plus document/chunk IDs, source path, heading, source hash,
branch/Git context, index/schema identity, applicable date/version/state when
explicitly known, and citation coordinates where the parser can establish them.
Unknown values are explicit; never fabricate line numbers or effective dates.
Use deterministic section/chunk ordering and enforce total output/context budgets.
Reading does not reindex, embed or silently fall back to original source files.

IDs and expansions must belong to the current allowlisted corpus and branch;
reject metadata points, stale/removed references and cross-owner IDs. A known UUID
is not access authority. Current citations must detect source-hash drift; historic
citations retain their recorded identity. Normal implementation still opens and
verifies the original significant rule before acting. Explicit Qdrant-only research
reports that source verification was deliberately outside its scope.

### Reader/writer isolation and diagnostics

Concurrent read-only searches should succeed against a stable index. Index,
initialize and rebuild remain exclusive. Audit which commands are actually reads
before assigning lock modes; preserve pending-write checks and prevent partial
document state from being presented as a coherent result. Define bounded contention
behavior and truthful diagnostics for a busy reader versus active index writer.

The observed parallel searches returned `Knowledge indexing is running` even
though the competing requested operations were reads. Existing documentation says
all CLI commands share one lock. This supports investigating serialization and
misleading wording, not a claim that Qdrant itself cannot serve parallel reads.
Keep existing lock-path/inode/ownership/symlink safeguards and avoid a second
coordinator, auto-killing holders or bypassing locks through direct REST reads.

### Applicability and decision history

Keep source synchronization, document purpose and statement applicability separate.
Represent current guidance, historical evidence, proposed work and unknown scope
using explicit source declarations or reviewed configuration. Directory/type/age
alone must not convert a dated report into current authority. Preserve the
distinction between intended behavior, implementation evidence and released state.

Use a minimal source-owned decision annotation convention with stable IDs, problem,
accepted solution, explicit rationale when recorded, status, date/version scope and
`supersedes` links where established. Missing rationale stays missing. Annotate new
or mutable current records; use reviewed external annotations referencing hashes
and sections for immutable history. Do not rewrite closed records or create a
parallel authoritative decision database. Owner annotations never enter customer
delivery/corpora. Application-owned annotations stay within application ownership.

Version payload/schema changes deliberately. Prefer incremental metadata/parser
refresh with reuse of unchanged semantic vectors where compatible. If the existing
schema contract requires a rebuild, document the exact reason, model/cache/data
preservation and supported command before execution; no collection deletion is
authorized merely by drafting this roadmap.

### Retrieval and agent consumption

Provide explicit current-guidance, historical-decision and audit comparison intent
within the existing retrieval service. Do not infer intent through an LLM or a Git
change classifier. Preserve legacy call compatibility and existing source filters.
Make ranking factors/applicability visible in explain output; a high score or newer
timestamp is never proof of truth. Current behavior needs applicable guidance and
delivery evidence; history needs original and superseding decisions; audit needs
both sides with unresolved cases visible. Never hide contrary evidence in audit.

Retain small result budgets and source diversity, but allow bounded multiple
sections from one document and explicit expansion. The observed configuration
`per_source=1` returned only one fragment even for a single-source query with larger
top-k; this obstructs multi-section reasoning. Avoid solving it by dumping entire
roadmaps or loading the whole corpus.

Agents should formulate short semantic/identifier queries, inspect applicability,
expand missing context and distinguish retrieved facts, their own deductions and
missing evidence. Use English technical queries for the English baseline corpus;
paired Polish/English cases assess usability without claiming multilingual support.
Any reviewed alias/glossary adjustment must demonstrate a gap and retain original
terms. Do not automatically translate whole conversations or change the model.

### Evaluation and diagnostics

Extend the existing golden/doctor system rather than adding an answer-generation
platform. Freeze representative expected sources/sections and bounded context
coverage for all three exercises, including supersession, dependency direction,
reserved APIs, transaction boundaries, missing rationale and uncertain versions.
Keep owner historical cases private and supply customer-safe representative cases.

Compare unchanged baseline and candidate using the same expectations: hit/coverage
within stated top-k/context budgets, source diversity, temporal relevance, negative
cases and rough latency. Verify evidence availability and correct labels, not
generated prose or guessed semantic truth. Known contradictory current sources
require a reviewed resolution; unresolved evidence stays explicitly unresolved.
No positive fixture substitutes for the original real-world retrieval cases.

Diagnostics distinguish service/model failure, lock contention, missing/stale index,
pending writes, missing provenance and unknown applicability. Keep existing strict
freshness/AI gates; do not fail hooks merely because an LLM suspects a contradiction.
Place local evaluation reports under `.runtime/knowledge/` and preserve existing
reports, model/Qdrant caches and development/test data.

## Implementation plan

Only necessary small, fast checks resolving a concrete uncertainty may run before
the final point. Each point includes its test code, affected current documentation,
instruction-product assessment, commit and checklist update. Do not run broad
acceptance as a prerequisite for subsequent implementation.

- [ ] 1. Reconcile the predecessor and freeze the three research cases.
  - Prerequisite: RM-012 closed, merged and released; owner activation of RM-013.
  - Refresh actual layout, public command contracts and source/version authority.
    Establish a compact source/claim ledger from the exercise evidence above.
  - Inspect what RM-012 already fixed; correct only residual mutable current-state
    contradictions, label historical engineering notes and preserve dated evidence.
    Do not modify module runtime behavior to make prose agree.
  - Define fixed expected evidence, uncertainty labels and baseline result budgets;
    implement regression fixtures without executing broad/live acceptance.

- [ ] 2. Deliver supported full evidence reading, citations and context expansion.
  - Prerequisite: point 1 ownership/source map and existing typed engine boundaries.
  - Implement bounded read/section expansion, structured responses and stable
    provenance using the existing service and narrow Artisan adapters.
  - Cover deterministic ordering, missing/stale IDs, output limits, corpus guards,
    unavailable service and compatibility with existing concise/explain output.
  - Update command/help and public knowledge guidance in this point.

- [ ] 3. Allow safe parallel readers and correct contention diagnostics.
  - Prerequisite: point 2 read paths identified; preserve existing write protocol.
  - Separate shared reads from exclusive writers with bounded lock behavior.
    Prevent pending/mixed-index reads and retain lock ownership/inode safeguards.
  - Implement deterministic reader/reader, reader/writer, interruption and recovery
    scenarios; defer broad real-service concurrency acceptance to point 8.

- [ ] 4. Deliver explicit applicability and decision-history metadata.
  - Prerequisite: point 1 claim ledger and point 3 isolation contract.
  - Add minimal source annotations, provenance fields and immutable-history
    references; distinguish historical evidence, proposals and current guidance.
  - Version contracts and implement supported incremental/backfill or justified
    rebuild handling with embedding reuse where compatible. Preserve removed-source,
    branch/lifecycle identity, owner/customer and pending-write behavior.
  - Implement migration, missing-date/rationale and supersession fixtures; update
    the authoritative knowledge/decision-authoring guidance in this point.

- [ ] 5. Improve retrieval for current state, history and audit questions.
  - Prerequisite: points 2–4 evidence, isolation and applicability contracts.
  - Implement explicit intent, bounded multi-section retrieval/context expansion,
    transparent applicability ranking and preserved source filters/legacy calls.
  - Cover conflicting versions, superseded deploy rules, host/runtime scope,
    in-progress versus completed notes and unknown effective dates.
  - Retain the current provider/model and source-diversity baseline unless measured
    evidence and the required separate approvals justify an additional proposal.

- [ ] 6. Extend representative retrieval evaluation and truthful diagnostics.
  - Prerequisite: points 1–5 candidate behavior and fixed expectations.
  - Integrate evidence coverage, negative/uncertain cases, paired language probes,
    budgets/latency and structured reports into existing golden/doctor commands.
  - Keep public/customer cases separate from private owner history. Add real
    source/hash/branch/lifecycle, reader isolation, customer install/update and
    production-denial scenarios to the existing final verification harnesses.
  - Implement the scenarios now; run their full comparison and live acceptance
    only in point 8. Do not weaken existing golden expectations to make changes pass.

- [ ] 7. Synchronize agent behavior, delivery and durable guidance.
  - Prerequisite: points 2–6 stable supported behavior.
  - Update affected root owner guidance and existing RAG skill/references, plus
    separately authored customer guidance. Teach query intent, English baseline
    queries, evidence expansion, source verification and explicit unknowns.
  - Document Qdrant-only research versus source-verified implementation, history
    precedence, failure fallback and strict final AI gates without duplicating
    handbook procedures or making retrieved text operating authority.
  - Reconcile public commands, package resources, owner annotations/evals and
    production exclusions. Record unaffected instruction products with reasons.
    This planning-only draft itself changes no delivered capability or agent rule.

- [ ] 8. Full verification, release finalization, merge and final report.
  - Prerequisites: points 1–7 complete and committed; only this point remains open.
  - Review current-state docs, CLI/help, source/version claims, owner/customer
    products, startup and production exclusions against actual implementation.
    Record findings and preserve closed roadmaps and dated evidence.
  - Reconcile frontmatter quality groups/rationale with final scope and preview the
    exact graph using `python3 tools/maintainer/quality-plan` with repeated groups
    from the declaration. Inspect mandatory checks and every real prerequisite.
  - Run the identical group selection with `python3 tools/maintainer/quality-roadmap
--background`; end the turn and provide `python3 tools/maintainer/quality-status`.
    No polling or direct internal-runner substitution. Selected AI/security gates
    stay strict; excluded groups are not PASS.
  - Include pinned Python format/lint/strict analysis, relevant PHP command and
    tooling suites, real CPU/CUDA/fallback retrieval, lock/recovery concurrency,
    baseline/candidate evaluation for the three tasks, customer acquisition/update
    corpus isolation and actual production knowledge prohibition/security checks.
  - Require full cited evidence access without direct REST, parallel reader
    success, protected writes, preserved source/schema/branch identities and no
    unexplained regression in fixed retrieval expectations or bounded latency.
    Report measured limits; do not claim perfect knowledge or factual verification.
  - If verification requires changes, insert a fix point immediately before this
    point and rerun the entire selected graph. Discuss any changed premise.
  - Inspect and show exact task-created disposable resources before scoped cleanup;
    preserve active development/test databases, caches, backups and reports.
  - Finalize maintenance 0.6.1, changelog and closure synchronization through the
    release workflow; perform focused permitted metadata checks, merge commit,
    main sanity, tag/publication and branch deletion only after all gates pass.
    Reconcile any actually changed independent module versions/contracts; do not
    bump Auth/Permissions/Audit merely for corrected knowledge prose.

## Planning state

Draft only. No implementation point is started and no release gate has run.
The two additional research tasks were completed without changing sources or the
Qdrant index. Their grounded findings and the separately labeled hypothetical are
recorded above as planning evidence, not current implementation claims.

---
id: RM-2026-013
title: Knowledge retrieval and agent guidance maintainability
status: active
branch: roadmap/RM-2026-013-knowledge-retrieval-reliability
created_at: 2026-10-28
activated_at: 2026-10-29
closed_at: null
release_type: maintenance
proposed_version: 0.6.1
quality_groups: docs,tooling,backend,development,knowledge,installation,updates,production,security
quality_rationale: Knowledge command contracts, locks, corpus ownership, provenance, retrieval and the owner/customer instruction and documentation system affect delivered development tooling. Include real customer acquisition/update, production isolation and security evidence alongside documentation, instruction-product, strict knowledge and tooling checks; preserve mandatory checks and all actual prerequisites. Reconcile the selection with the implemented scope before final verification.
impact_areas:
  - ai_environment
  - tooling
  - development
  - documentation
  - security
---

# RM-2026-013 — Knowledge retrieval and agent guidance maintainability

## Overview

### What and why

Make the existing local knowledge system useful for architectural history,
cross-module reasoning and consistency audits through supported commands. Improve
both retrieval mechanics and the agent workflow that consumes them. Preserve
Qdrant as a rebuildable development aid, with source documents as authority.

Review AGENTS, skills and documentation as one maintained knowledge system, rather
than optimizing retrieval over an increasingly duplicated corpus. Establish clear
homes for rules, concise instruction entrypoints and an ongoing editing procedure
that prevents avoidable growth. Together with RM-012's architecture boundaries,
this reduces recurring structural rework; it cannot guarantee that future product
requirements will never justify a refactor.

Three owner-requested exercises exposed concrete limitations: recovering five
earlier decisions, explaining Auth/Permissions/Audit integration, and auditing
architectural knowledge for superseded or uncertain claims. The exercises used
Qdrant content only. Repository workflow documents were consulted afterwards to
prepare this draft; no implementation source was inspected for those analyses.

The owner explicitly requested version **0.6.1** and confirmed
`release_type: maintenance` on 2026-10-28. This is a PATCH increment after the
released compatible feature **0.6.0**. The completed
predecessor is [RM-2026-012](../closed/RM-2026-012-runa-architecture-refactor.md).
RM-012 closed, merged and released as v0.6.0 on 2026-10-28.
Activation preserves VERSION until release finalization and does not extend RM-012 implementation.

### Scope

Supported evidence retrieval and context expansion; read concurrency and truthful
lock diagnostics; explicit temporal/provenance metadata; current/history/audit
retrieval behavior; source documentation reconciliation; representative retrieval
evaluations; owner and customer agent consumption guidance. Include the existing
Python engine and narrow Artisan adapters required to expose these capabilities.
Review root owner AGENTS, all existing owner skills/references/provenance, the
separately authored customer AGENTS/CLAUDE/skills product, and current repository,
public and maintainer documentation. Include ownership, duplication, conflicting
instructions, obsolete claims, discoverability, context cost and corpus composition.
Preserve historical evidence and local customer extensions during reconciliation.

### Out of scope

No business-module behavior, authorization or Audit redesign. No new platform
runtime dependency on knowledge services, production AI, cloud provider, LLM answer
service, automatic architecture adjudication, chat ingestion, full-code indexing,
CI, new quality coordinator or mandatory host installation. No new dependency,
model replacement, reranker, HyDE or agent framework is pre-approved. Do not tune
Qdrant internals without evidence of a storage/filtering bottleneck.

Closed roadmaps and dated evidence remain immutable. Do not turn every historic
wording difference into a defect or promise complete contradiction detection.
Do not add an instruction framework, a second documentation registry, arbitrary
hard file-size limits or a new universal quality gate. Policy changes beyond the
agreed editorial maintenance scope require separate owner discussion. This roadmap
does not reopen RM-012's platform architecture or impose new customer app layers.

## Specification

### Decisions

- 2026-10-29 — Owner explicitly approves activation and execution of RM-013.
  Confirmed maintenance 0.6.1 follows released v0.6.0; implementation begins on
  the dedicated roadmap branch. Broad verification remains exclusively point 10.

- 2026-10-28 — Owner requests a separate 0.6.1 roadmap based on all three Qdrant-only
  exercises and explicitly confirms maintenance classification. Activation remains
  a later owner decision after RM-012 closes, merges and releases.
- 2026-10-28 — Improve agent query formulation, evidence handling and uncertainty
  reporting alongside the engine. The exercises do not establish that the current
  embedding model must be replaced. Measure retrieval gaps before proposing that
  separately reviewed change.
- 2026-10-28 — The owner expands this draft to review AGENTS, all skills and the
  documentation system together with Qdrant, and to prevent future instruction
  bloat. Retain confirmed maintenance 0.6.1: this is compatible consolidation and
  maintenance, with no new platform capability or change to constitutional gates.
  RM-012 establishes implementation boundaries; RM-013 reconciles how agents and
  humans discover and maintain the resulting contracts. Avoid duplicate work by
  reviewing the predecessor's final delivery before applying residual corrections.
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

### Instruction and documentation architecture

Apply the existing [documentation ownership](../../../../README.md) and
[skills policy](../../../handbook/ai/skills-policy.md) to the whole current system.
Use one maintained source for each procedure or contract and purposeful references
from its consumers. A concise critical invariant may remain in AGENTS when it is
needed before any retrieval; detailed procedures and examples belong in their
authoritative handbook/maintainer guide. Do not remove essential safety, authority
or failure rules solely to shorten a file. Explicit summaries must link to their
canonical source and must not become an independently evolving specification.

Review and reconcile these responsibilities:

- Root owner AGENTS: authority, essential invariants, workflow entrypoints and
  ownership boundaries required for every task, without accumulating delivery
  reports, per-command recipes or repeated domain procedures.
- SKILL.md entrypoints: precise activation criteria, minimal domain workflow and
  selective reference loading. Existing domain references guide implementation
  choices and route shared procedures to the authoritative documentation. No
  automatic loading of every skill or all its references.
- Public handbook: delivered contracts, usage, supported commands and procedures
  for ordinary Laravel applications and optional app modules. Maintainer guides:
  private platform engineering, release policy and audit evidence. App docs:
  customer-owned application decisions and procedures.
- Customer AGENTS/CLAUDE/skills: a separately authored application product, with
  no owner authority or protected-platform editing permission. Keep role-specific
  differences intentional, preserve local extensions and avoid maintaining two
  conflicting copies of the same installed customer rule.
- Qdrant: discoverable, correctly classified context from the allowed current and
  historical sources. Root/skill navigation must work when RAG is unavailable;
  a retrieval hit is never an instruction override or proof of current behavior.

Create a compact review record using the existing documentation map and roadmap
evidence locations rather than a parallel inventory system. Cover all existing
skills and the current documentation tree; group documents by owning contract
where useful, but do not describe a sample as a complete review. Record ownership,
canonical source, duplicates/conflicts, applicability and concrete disposition.
Check commands, examples, public signatures, dependency/version/delivery claims and
links against delivered source during implementation. Review source provenance
without claiming upstream content or licenses were freshly researched when they
were not; a substantive upstream update follows the existing review policy.

Classify immutable history and dated evidence separately from current guides.
Preserve their bytes, annotate externally where needed and prevent them from
masquerading as current instructions. Keep authoring templates out of owner
retrieval and private owner sources out of customer corpora. Navigation and
allowlisting must follow relocations without broadening source authority.

### Preventing recurring instruction growth

Use an editing procedure in the existing authoritative guides, referenced briefly
from owner/customer entrypoints. For each reusable guidance change, identify its
reader, trigger, canonical home and affected products; update or replace an
existing rule before adding another. Explain why anything added to always-loaded
instructions is needed for all tasks. Remove superseded duplicate text and retain
only justified summaries with links. Keep task progress and abandoned approaches
in their proper execution/history records, outside current instruction products.

Record reproducible before/after line or word counts and review actual mandatory
context loaded for representative tasks. Counts reveal growth and duplication;
they are review signals, not correctness metrics or arbitrary hard caps. A larger
rule needs a scope/placement explanation, not an automatic exception system.
Keep review evidence compact and avoid replacing prose bloat with sprawling links,
tiny files or an ever-growing rule registry. Extend existing checks only for
deterministic broken ownership/link/structure contracts within their present
scope; do not delegate semantic truth or approval to an LLM classifier.

Acceptance must demonstrate ordinary navigation without prior chat context:
starting a backend or frontend task, using a public module contract, finding a
current operational procedure, tracing a historical decision and maintaining a
customer extension. The agent should select the relevant skill/reference and
canonical document without reading the entire corpus. Include RAG available and
unavailable paths, historical counterexamples and owner/customer separation.
Implement these scenarios before final verification; run broad acceptance only
after all implementation points are complete.

## Implementation plan

Only necessary small, fast checks resolving a concrete uncertainty may run before
the final point. Each point includes its test code, affected current documentation,
instruction-product assessment, commit and checklist update. Do not run broad
acceptance as a prerequisite for subsequent implementation.

- [x] 1. Reconcile the predecessor and freeze the three research cases.
  - Prerequisite: RM-012 closed, merged and released; owner activation of RM-013.
  - Refresh actual layout, public command contracts and source/version authority.
    Establish a compact source/claim ledger from the exercise evidence above.
  - Inspect what RM-012 already fixed; correct only residual mutable current-state
    contradictions, label historical engineering notes and preserve dated evidence.
    Do not modify module runtime behavior to make prose agree.
  - Define fixed expected evidence, uncertainty labels and baseline result budgets;
    implement regression fixtures without executing broad/live acceptance.
  - Inventory the instruction products and current documentation by reader and
    canonical contract; capture size/context baselines and predecessor coverage.
    Plan all-skill review without loading every reference before its own review task.

- [x] 2. Deliver supported full evidence reading, citations and context expansion.
  - Prerequisite: point 1 ownership/source map and existing typed engine boundaries.
  - Implement bounded read/section expansion, structured responses and stable
    provenance using the existing service and narrow Artisan adapters.
  - Cover deterministic ordering, missing/stale IDs, output limits, corpus guards,
    unavailable service and compatibility with existing concise/explain output.
  - Update command/help and public knowledge guidance in this point.

- [x] 3. Allow safe parallel readers and correct contention diagnostics.
  - Prerequisite: point 2 read paths identified; preserve existing write protocol.
  - Separate shared reads from exclusive writers with bounded lock behavior.
    Prevent pending/mixed-index reads and retain lock ownership/inode safeguards.
  - Implement deterministic reader/reader, reader/writer, interruption and recovery
    scenarios; defer broad real-service concurrency acceptance to point 10.

- [x] 4. Deliver explicit applicability and decision-history metadata.
  - Prerequisite: point 1 claim ledger and point 3 isolation contract.
  - Add minimal source annotations, provenance fields and immutable-history
    references; distinguish historical evidence, proposals and current guidance.
  - Version contracts and implement supported incremental/backfill or justified
    rebuild handling with embedding reuse where compatible. Preserve removed-source,
    branch/lifecycle identity, owner/customer and pending-write behavior.
  - Implement migration, missing-date/rationale and supersession fixtures; update
    the authoritative knowledge/decision-authoring guidance in this point.

- [x] 5. Improve retrieval for current state, history and audit questions.
  - Prerequisite: points 2–4 evidence, isolation and applicability contracts.
  - Implement explicit intent, bounded multi-section retrieval/context expansion,
    transparent applicability ranking and preserved source filters/legacy calls.
  - Cover conflicting versions, superseded deploy rules, host/runtime scope,
    in-progress versus completed notes and unknown effective dates.
  - Retain the current provider/model and source-diversity baseline unless measured
    evidence and the required separate approvals justify an additional proposal.

- [x] 6. Extend representative retrieval evaluation and truthful diagnostics.
  - Prerequisite: points 1–5 candidate behavior and fixed expectations.
  - Integrate evidence coverage, negative/uncertain cases, paired language probes,
    budgets/latency and structured reports into existing golden/doctor commands.
  - Keep public/customer cases separate from private owner history. Add real
    source/hash/branch/lifecycle, reader isolation, customer install/update and
    production-denial scenarios to the existing final verification harnesses.
  - Implement the scenarios now; run their full comparison and live acceptance
    only in point 10. Do not weaken existing golden expectations to make changes pass.

- [x] 7. Review and reconcile the current documentation system.
  - Prerequisites: point 1 review inventory, RM-012 final architecture and points
    2–6 supported knowledge behavior.
  - Review all existing owner/customer skill products and current documentation
    coverage; map each reusable rule/procedure to its canonical home. Identify
    duplicated/conflicting guidance, stale commands/contracts/version claims,
    broken navigation and mixed current/historical ownership with concrete evidence.
  - Correct current guides, consolidate duplicate procedures and repair references
    and corpus classification. Preserve historical bytes and distinguish deliberate
    owner/customer differences from accidental drift. Record reviewed coverage and
    disposition in existing owner documentation/roadmap evidence locations.
  - Keep any behavior or constitutional question requiring a changed premise in
    discussion; do not resolve an architectural conflict by rewriting guidance alone.

- [ ] 8. Consolidate instruction products and establish growth prevention.
  - Prerequisite: point 7 canonical ownership and reviewed corrections.
  - Refine owner AGENTS and every affected existing SKILL.md/reference to retain
    essential invariants, precise triggers and selective loading. Move duplicated
    procedural detail to the authoritative guide with purposeful navigation.
  - Reconcile separately authored customer AGENTS/CLAUDE/skills without copying
    owner policy, broadening public APIs or overwriting local application extensions.
    Record explicit reasons for each reviewed product requiring no edit.
  - Document the rule-placement/update procedure in existing skills/documentation
    guidance. Record before/after size and representative context costs, justified
    retained summaries and supported maintenance checks. No arbitrary hard cap,
    new skill for this task, parallel rule registry or automatic semantic gate.
  - Add navigation/selection and duplicate-removal regression scenarios to existing
    harnesses; broad end-to-end acceptance remains point 10.

- [ ] 9. Synchronize agent behavior, delivery and knowledge composition.
  - Prerequisite: points 2–8 stable supported behavior and concise source products.
  - Update affected root owner guidance and existing RAG skill/references, plus
    separately authored customer guidance. Teach query intent, English baseline
    queries, evidence expansion, source verification and explicit unknowns.
  - Document Qdrant-only research versus source-verified implementation, history
    precedence, failure fallback and strict final AI gates without duplicating
    handbook procedures or making retrieved text operating authority.
  - Reconcile public commands, package resources, owner annotations/evals and
    production exclusions. Record unaffected instruction products with reasons.
    This planning-only draft itself changes no delivered capability or agent rule.

- [ ] 10. Full verification, release finalization, merge and final report.
  - Prerequisites: points 1–9 complete and committed; only this point remains open.
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
  - Verify the complete instruction/documentation review record and justified
    remaining duplication. Run representative fresh-context navigation cases with
    and without RAG, correct skill/reference selection, customer extension
    preservation and retrieval of canonical current versus historical sources.
    Compare documented size/context baselines without treating fewer words as
    sufficient proof of quality. No critical invariant may disappear for brevity.
  - If verification requires changes, insert a fix point immediately before this
    point and rerun the entire selected graph. Discuss any changed premise.
  - Inspect and show exact task-created disposable resources before scoped cleanup;
    preserve active development/test databases, caches, backups and reports.
  - Finalize maintenance 0.6.1, changelog and closure synchronization through the
    release workflow; perform focused permitted metadata checks, merge commit,
    main sanity, tag/publication and branch deletion only after all gates pass.
    Reconcile any actually changed independent module versions/contracts; do not
    bump Auth/Permissions/Audit merely for corrected knowledge prose.

## Execution state

Activated on 2026-10-29 after owner approval. Point 1 is complete; point 2 is complete; point 3 is complete; point 4 is complete; point 5 is complete; point 6 is complete; point 7 is complete; point 8 is next. No release
gate has run. The earlier research findings and separately labeled hypothetical
remain planning evidence rather than current implementation claims.

### Point 1 — predecessor and fixed research evidence (2026-10-29)

Source reconciliation, immutable-history limits, frozen research query budgets,
complete grouped review inventory and instruction/context size baselines are
recorded in [the review record](../../architecture/RM-2026-013-knowledge-review.md).
Corrected residual public module versions and the Audit consumer dependency;
labeled intermediate foundations as historical engineering material. Manifests,
module behavior and historical bytes are unchanged. Owner/customer instructions
need no rule change for this source-only reconciliation. No broad checks ran;
mandatory hooks and incremental source refresh accompany the point commit.

### Point 2 — supported evidence reading (2026-10-29)

Added development-only `runa:knowledge:read` through the existing named Artisan
catalogue and internal Python service. It verifies allowed sources/current hash,
manifest and branch, expands exact sections or bounded neighbors in source order,
reports whole omitted chunks and refuses insufficient target/serialized budgets.
Unique literal source bodies receive one-based citation lines; ambiguous matches
remain null. Search/status structured options preserve legacy output and exits.
Focused offline evidence cases passed (2 tests, cleanup 0), resolving ordering,
coordinates, omitted budgets, stale/pending state and no embedding writes.
Synchronized command/knowledge guides and owner/customer RAG entrypoints. Root
owner/customer AGENTS need no new always-loaded rule: existing source grounding
already applies. No new dependency, module behavior or version change.

### Point 3 — shared readers and exclusive writes (2026-10-29)

CLI read/search/status/stale/doctor/eval now use shared admission on the existing
stable-inode lock; index/rebuild/initialize remain exclusive throughout their
operation. Contention returns immediately with mode-specific diagnostics. Remote
pending/freshness refusal and all core lock ownership/no-follow checks remain.
Two focused offline lock scenarios passed (reader coexistence, blocked writer/read,
interruption release, legacy inode preservation and symlink refusal; cleanup 0).
Real-service concurrency/recovery scenarios remain final acceptance. Updated the
canonical knowledge procedure; owner/customer RAG entrypoints already link there,
so no further instruction-product edit is needed for this internal lock change.

### Point 4 — applicability and decision provenance (2026-10-29)

Added payload schema 2 alongside unchanged embedding/chunk schema 1. Reviewed
source-purpose rules, standalone source/section declarations and hash-bound owner
annotations preserve explicit unknown dates/versions/rationale and supersession.
RM-005's excluded/forward deployment decisions are annotated externally against
its exact source hash/full heading; closed bytes remain unchanged. Fenced examples
do not become declarations. Missing/moved annotation sections or changed hashes
refuse persistence. Customer composition never loads owner annotations.
Metadata policy/parser fingerprints require explicit incremental index refresh;
unchanged semantic vectors/IDs are reused, without collection deletion or rebuild.
Four narrow offline scenarios passed: vector reuse/backfill, explicit supersession,
immutable hash refusal and literal fenced examples. Canonical guides and both RAG
reference products are synchronized; no additional always-loaded AGENTS rule is
needed. Broad migration/retrieval acceptance remains point 10.

### Point 5 — explicit intent and bounded source diversity (2026-10-29)

Search supports legacy/current/history/audit intent and explicit per-source budgets;
exact-source filters now allow multiple sections within top-k. Unrestricted legacy
source diversity remains unchanged. Current/history ranking uses declared purpose,
with visible multipliers; audit neutralizes status/purpose discounts and retains
contrary evidence. No age heuristic, model/dependency change or inferred truth.
A focused offline scenario passed for current/history ranking, neutral audit,
source expansion/budgets and invalid intent/limit refusal. Existing real research
cases remain frozen for final baseline/candidate comparison. Updated public
CLI/knowledge guidance and owner/customer RAG references; their entrypoints already
link to these procedures, so root instruction products require no new invariant.

### Point 6 — coverage evaluation and final acceptance scenarios (2026-10-29)

Extended golden validation/evaluation with explicit source/section/purpose/decision
coverage, unknown-field labels, forbidden sources, context budgets, source diversity,
latency and separately visible diagnostic probes. Original English cases remain
required. Doctor separates unknown applicability from invalid metadata/missing
provenance; eval preserves timestamped reports in .runtime/knowledge.
Added final-only unchanged pinned-search/current-corpus comparison with module hash,
raw-hit regressions and candidate evidence coverage. It reuses current compatible
transport seams and explicitly does not claim historical installation replay.
Existing documentation-live acceptance now exercises CPU/CUDA/fallback and actual
concurrent source-verified reads/write exclusion; customer acceptance uses named
search/read and refuses deleted IDs. Existing production development guards apply
to the new catalogue entry. One focused offline evaluator case passed, proving
missing coverage/forbidden sources fail and probe failures remain visible.
No broad/live acceptance has run. Public procedures are synchronized; owner/customer
RAG products already reference the canonical guide and need no duplicated recipe.

### Point 7 — documentation contract/ownership review (2026-10-29)

Completed the grouped current-documentation/skill-product coverage review in the
existing review record, with canonical owners, intentional scope differences,
source comparisons, concrete findings and limits. Corrected active-state projection,
scaffold compatibility, deferred RM-011 delivery claims, profile/sign-out design
claims, API-map closure routing and Python payload-schema prose. Explicit reviewed
purpose rules distinguish public module changelogs, private evidence/security/
engineering history, current workflows and proposals. Historical source bytes,
upstream pins and customer extension ownership are preserved.
All nine owner/customer skill families were assessed for point 8 consolidation;
canonical public procedures remain their source of detail. No new constitutional
rule, runtime behavior, dependency or broad check was introduced. Both instruction
products need consolidation rather than duplicated fixes to these guide claims.

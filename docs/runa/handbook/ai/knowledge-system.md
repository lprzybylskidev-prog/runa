# Project Knowledge System

## Goal

Runa uses a development-only local RAG system to reduce context usage and improve retrieval of durable project decisions.

The knowledge system is an assistant to source navigation. It is not the source of truth.

## Runtime topology

Development stack includes sibling services:

- Qdrant,
- local embedding service.

Host Docker Compose owns the services.

The app devcontainer talks to them over the Compose network.

No Docker-in-Docker.

The knowledge stack never runs in production.

## Embeddings

Use a provider abstraction.

Default provider:

- local embedding model/service.

An external embedding provider may be added later behind the same abstraction if a roadmap establishes a real need and the dependency/configuration policy is satisfied. It is not baseline.

Support:

- NVIDIA CUDA where available,
- CPU fallback.

Provider contract should expose capabilities such as:

- provider/model ID,
- embedding dimension,
- maximum input budget,
- batch capability/size.

Validate dimensions before collection writes.

Do not silently mix vectors from different embedding spaces/models in one collection.

GPU vs CPU execution must preserve the same semantic indexing pipeline.

If GPU OOM triggers CPU fallback, report it clearly.

Persist model cache locally across rebuilds. Never commit it or include it in production.

## Initial indexed corpus

Initially index durable project knowledge only:

- `AGENTS.md`,
- `.agents/skills/**`,
- `docs/app/**` and `docs/runa/handbook/**`,
- active/paused roadmaps,
- README,
- selected configuration/procedural files when useful.

Do not initially index the entire application source tree. Source search remains the primary code-navigation mechanism until a later need justifies selective code indexing.

Never index:

- raw chat transcripts,
- `.env`/secrets,
- vendor,
- `node_modules`,
- runtime logs,
- coverage,
- build artifacts,
- generated browser artifacts.

## Closed roadmaps

Closed roadmap retrieval should favor durable historical value:

Index/prioritize:

- Overview,
- Specification,
- Decisions,
- final outcome/report.

Implementation checklists may be lower priority or omitted when they add mostly task noise.

Closed roadmaps have lower default retrieval priority than current code/active specifications.

## Chunking

Chunk structurally/semantically, not by one fixed character/token window.

Use document-type-aware chunking.

Preserve heading hierarchy in metadata/content context.

Examples:

- Markdown sections/subsections,
- skill procedures/references,
- roadmap Overview/Specification/Decisions,
- configuration blocks.

Oversized sections may be split further with a small overlap.

Do not use large overlap that creates near-duplicate context.

Parent-child/hierarchical chunks are optional future optimization if evaluations show small chunks lose needed context.

## Metadata

Useful metadata includes:

- `document_id`,
- `source_path`,
- `git_branch`,
- `document_type`,
- `section`,
- heading path,
- `scope`,
- `roadmap_id`,
- roadmap `status`,
- `tags`,
- `git_commit`,
- `source_hash`,
- `chunk_id`,
- `chunk_schema_version`,
- `indexed_at`,
- embedding model/provider/version.

`document_id` should be stable independently from the filesystem path where practical.

Chunk IDs should be deterministic/derivable so reindex behavior is predictable.

Only actual roadmap files in draft/active/paused/closed directories receive roadmap identity/status, taken from their leading frontmatter and checked against filename/location. Workflow and template examples are documentation; never infer their identity from fenced examples or body text. An active-to-closed move retains the real roadmap document identity while updating status and source path.

Create Qdrant payload indexes only for fields actually used in retrieval filtering.

## Ingestion

Index incrementally by source/chunk hashes.

A full rebuild is reserved for explicit/manual use or a real model/chunk-schema/index-schema repair/change. Normal edits use incremental indexing.

Expected behavior:

- unchanged chunks are not re-embedded,
- changed chunks are replaced,
- deleted documents/chunks are removed,
- rename may be remove+insert; sophisticated rename detection is optional,
- no orphan vectors.

Cosmetic/whitespace changes should avoid unnecessary semantic churn where practical.

Parser failure is explicit; never silently index an empty document as success.

Document replacement should be atomic-ish:

- prepare/validate new chunks/embeddings,
- replace the previous document state only when the new set is ready,
- avoid half-old/half-new state without an error signal.

Indexer source roots are allowlisted.

Do not follow symlinks outside allowed repository roots.

Ignore binary files unless an explicit parser is supported.

Batch embedding requests sensibly.

Batch size is a performance setting and must not change semantic output.

Retry transient provider failures; do not retry permanent input/model/dimension errors indefinitely.

## Search

Use hybrid retrieval:

- dense semantic,
- lexical/sparse,
- metadata filters,
- simple fusion such as RRF initially.

Weights/fusion behavior are configurable.

Do not hardcode one score threshold forever. Tune from real evaluations.

Prefer a small top-k.

Avoid returning many near-identical chunks from one source when diverse supporting context is available.

Known context should restrict retrieval using metadata, for example:

- active roadmap,
- document type,
- roadmap status,
- scope.

For exact identifiers/names, combine semantic retrieval with lexical/exact search.

Query formulation should be concise and semantic rather than passing an entire user conversation to retrieval.

LLM query rewriting, multi-query retrieval, HyDE, agentic loops, and rerankers are not baseline features.

Add them only if evaluations expose a real retrieval gap.

If a reranker is introduced later, rerank a small candidate set.

## Authority and grounding

A RAG result is untrusted retrieved context.

For significant project rules:

1. retrieve,
2. open the original source file/section,
3. verify the current content,
4. act from the authoritative source.

The hierarchy in `AGENTS.md` still applies.

Retrieval should be branch-aware when the same knowledge exists across relevant refs:

- prefer the current active roadmap branch context,
- then current `main`,
- then lower-priority historical/closed material.

Current code/current active specification should rank above historical roadmap content.

Age alone does not make a stable architecture document obsolete.

Deprecated historical documents may remain indexed with explicit metadata but should be deprioritized.

## Freshness

Staleness is a first-class state.

The index should record enough source hash/Git context to detect when indexed content differs from current repository content.

If search runs against a stale index:

- warn clearly,
- prefer source search/opening or reindex before relying on results.

Pre-commit does not automatically reindex.

When implementation or release finalization changes durable current-state documentation, update the source in the owning roadmap point, stage the intended allowlisted files, and refresh incrementally before committing. Repeat after closure moves or branch changes so obsolete active-roadmap metadata cannot remain preferred. Index freshness proves agreement with source hashes, not factual correctness of the source; the roadmap documentation review remains required.

If knowledge sources changed and the index is stale, the hook blocks/instructs:

`./bin/artisan runa:knowledge:index`

## CLI

Canonical interface:

- `./bin/artisan runa:knowledge:search`
- `./bin/artisan runa:knowledge:index`
- `./bin/artisan runa:knowledge:initialize` (first-index startup)
- `./bin/artisan runa:knowledge:status`
- `./bin/artisan runa:knowledge:rebuild`
- `./bin/artisan runa:knowledge:stale`
- `./bin/artisan runa:knowledge:doctor`
- `./bin/artisan runa:knowledge:eval`

It should work from both host and devcontainer where appropriate, with consistent semantics.

Search supports a diagnostic mode such as:

`./bin/artisan runa:knowledge:search "query" --explain`

Explain mode may show:

- dense score,
- sparse score,
- metadata filters,
- fusion/rank,
- source/chunk metadata.

Normal search stays concise.

Allow manual top-k increase for diagnostics.

## Evaluation

Maintain a small versioned set of representative "golden queries" with expected relevant source documents/sections.

Measure retrieval quality, not prose-generation quality.

For important queries, pass/fail can be based on whether the expected source appears in a small top-k.

Do not optimize only top-1.

Run the same eval set when changing:

- embedding model,
- chunking,
- retrieval fusion,
- metadata strategy,
- reranking.

Do not build an ML research platform. A focused representative eval corpus is enough.

Track rough retrieval performance so a new pipeline does not accidentally make normal lookup unacceptably slow.

Meaningful retrieval regressions block a pipeline change until understood.

## Qdrant

Use one logical collection for the coherent project knowledge corpus by default.

Do not create:

- collection-per-file,
- collection-per-roadmap status,
- collection-per-branch.

Represent those concerns with metadata/filtering.

Named vectors are introduced only when multiple representations genuinely require them.

Embedding dimensionality/model identity is a collection invariant.

For the development-only corpus, changing embedding model normally favors a clean rebuild rather than production-style zero-downtime dual-vector migration.

Do not tune HNSW, quantization, sharding, replication, or snapshots prematurely.

Git/source files are the knowledge source of truth; Qdrant is rebuildable.

Qdrant snapshots are not required as the primary backup of project knowledge.

## Doctor

`./bin/artisan runa:knowledge:doctor` should validate:

- Qdrant health,
- embedding service health,
- embedding dimensions/model metadata,
- index schema version,
- source hashes/staleness,
- missing indexed sources,
- orphan chunks,
- end-to-end retrieval smoke test.

Report useful corpus statistics such as:

- document count,
- chunk count,
- average/median chunk size,
- stale docs,
- orphan count.

Do not add Prometheus/Grafana for this development-only knowledge stack.

## Production prohibition

Production:

- contains no Qdrant/embeddings service,
- cannot invoke knowledge CLI,
- does not include AI/RAG runtime dependencies,
- does not expose knowledge ports/volumes.

Production self-test verifies these assertions.

## Baseline model and service lifecycle

Select an English technical-documentation embedding model; multilingual support is not required. Record the researched model/provider, license, limits, dimensions and pinned version during the environment roadmap. Do not silently install a large RAG framework or an external API provider as part of the local provider abstraction.

Qdrant and local embeddings start with the development stack automatically, independently of application readiness. The restartable `knowledge-init` service calls `./bin/artisan runa:knowledge:initialize` after bounded readiness probes; it creates a missing index under the shared CLI lock and retries failures independently. Existing stale indexes require explicit incremental indexing. Application setup and post-create contain no knowledge operations. Application diagnostics report bounded AI degradation without failing application readiness; dedicated knowledge doctor/evals and final AI gates remain strict. Application code has no runtime dependency on these services.

Staleness is primarily a source/content and index-schema comparison, with Git context for traceability. A commit changing only non-indexed content does not by itself require re-embedding unchanged sources. After branch changes/merge remove or supersede obsolete branch chunks so stale policy cannot win retrieval.

Source templates may be indexed only after secret-safe allowlisting; actual `.env` files and credentials are excluded. Temporary audit material is not durable knowledge.

## Customer and owner corpus boundary

Public configuration indexes application/public documentation and supplied agent guidance only. Owner source/golden additions live in excluded tools/maintainer files and load only in a receipt-free owner checkout. A customer receipt prevents all maintainer roots from entering the corpus, including copied private documents. Real application and owner roadmaps use separate identities while preserving identity across lifecycle moves. Validate source freshness, removal of obsolete paths and retrieval/doctor behavior after relocation. See docs/README.md for placement rules.

# Development knowledge system

## Selection and boundaries

The supplied development stack uses Qdrant 1.19.2, TEI 1.9.4 and BAAI/bge-small-en-v1.5 at revision 5c38ec7c405ec4b44b94cc5a9bb96e735b38267a. Model dimensions, input limits and service URLs are explicit in tools/application/knowledge/config.json. Application requests and production runtime have no dependency on this stack.

TEI is an actively released Rust inference service supporting this BERT model on CPU and NVIDIA CUDA. It supplies tokenization and batching, avoiding a Python/PyTorch/Transformers installation in the Laravel devcontainer. The knowledge client uses Python's standard library only: no new Composer or pnpm dependency, SDK or RAG framework. Qdrant stores vectors and metadata; TEI only produces embeddings, so responsibilities do not overlap.

Both paths explicitly use float32, CLS pooling, normalized embeddings and no automatic truncation. Flash attention is disabled so the float32 BERT path uses the same model/pooling/precision contract on CPU and CUDA. GPU batching and floating-point implementation can produce small numerical differences; evaluation must tolerate these without treating a different model as compatible. Client batch size is 16; the server token batch limit is 8192 on CPU and 2048 on CUDA. Chunk input budget is 420 tokens, including heading context; the remaining model budget accommodates future retrieval query preparation.

Selected immutable upstream image references:

- CPU: `ghcr.io/huggingface/text-embeddings-inference:cpu-1.9.4@sha256:2538ea1c9640d3763b15af668039d24172d063b42337b0c27796fc2be180c78d`.
- CUDA: `ghcr.io/huggingface/text-embeddings-inference:cuda-1.9.4@sha256:c831a5cedb89d62f7b0fdb59e2d73e8c30e10c1d5006897640e2ce58a71cedba`.

Both registry references were resolved on 2026-10-17 and include linux/amd64 manifests. The current host is x86_64 with RTX 4070 Laptop, compute capability 8.9, driver 580.159.03 and Docker NVIDIA runtime. CUDA container access and inference were verified on this host. Other architectures require a deliberate compatible image selection, not an implicit substitution.

## Lifecycle and hardware

`embeddings` is the CPU sibling and `qdrant` is the vector-store sibling. The generated local Compose override adds `embeddings-gpu` when host detection selects CUDA. CPU remains available even while CUDA is enabled, providing fallback after service errors or GPU OOM. Provider fallback prints an explicit diagnostic; a model/revision/runtime mismatch fails instead of hiding incompatible configuration.

Run on the host:

```sh
./bin/artisan runa:dev:embeddings:set auto
./bin/artisan runa:dev:build
./bin/artisan runa:dev:up
```

`auto` checks NVIDIA compute capability >=7.5 and the Docker NVIDIA runtime. `cpu` disables GPU selection; `cuda` requires host detection to pass. The ignored `.env.dev.local` stores the requested mode and resolved CUDA availability; preparation regenerates the override for normal Compose and VS Code startup. Changing resource profiles preserves this hardware choice. An old GPU sibling may remain after switching to CPU; the knowledge CLI follows the saved CPU choice and does not use it. Stop/remove that obsolete sibling explicitly on the host if needed; persistent caches are retained.

Services run without published ports, Docker socket, privileged mode or extra capabilities. Derived images use the developer UID/GID for persistent-volume ownership. Qdrant has an HTTP readiness check; TEI health probes inference readiness. The `qdrant-data` and shared `embedding-cache` named volumes survive rebuilds. Pinned model files download on first startup into the cache; CPU and GPU can start independently and reuse the same persistent cache. No model code is executed from the repository; TEI uses its built-in implementation and the model's safetensors weights.

## Ingestion and freshness

Use `php artisan runa:knowledge:<operation>` in the configured development PHP terminal.
The host setup/recovery transport `./bin/artisan` delegates to the app container. Status/stale exit 1 when missing, stale or incompatible; errors also exit nonzero with a diagnostic. Search/eval refuse stale indexes; doctor reports freshness failures with repair instructions. All commands return nonzero on failures.

The corpus is the intersection of Git-tracked paths and `tools/application/knowledge/config.json`'s explicit allowlist: Markdown under `docs/app`, `docs/runa/handbook` and `.agents/skills`, AGENTS/README/docs index and the application quality definition. Newly created durable documents must be staged before indexing. Untracked notes, raw chat files, application source, environment files, dependencies, logs and generated artifacts are excluded. Symlink sources or parents are rejected. Do not place transcripts or secrets in allowlisted durable documentation.

Markdown is split at headings, respecting code fences; oversized sections split at paragraph, whitespace, then character boundaries with actual model-token counts. Config registries preserve top-level key/entry context. Frontmatter supplies roadmap metadata; closed-roadmap implementation sections are omitted. Empty, binary, invalid UTF-8 and malformed fenced sources fail explicitly.

Roadmap document IDs derive from roadmap IDs and survive lifecycle path changes. Other documents use deterministic path IDs (renames are remove+insert). Chunk IDs derive from document, headings, content hash and duplicate occurrence. Whitespace at source edges/line endings is normalized; meaningful code whitespace remains significant.

The provider validates model/revision/runtime/input limits and vector dimensions before collection writes. Consumed limits must be integers, automatic truncation must be explicitly disabled, and vector coordinates must be finite numbers rather than booleans. Missing or malformed consumed capability fields fail with fixed contract diagnostics; unrelated service metadata remains accepted. `runa_knowledge` is one rebuildable collection, with a reserved metadata point describing model/schema identity and the source/chunk manifest. Retrieval filters `kind=chunk` and the current branch to exclude metadata and obsolete branch context. The small local corpus uses unindexed metadata filters; add payload indexes only if measured filtering cost warrants them.

Unchanged chunks are reused without embedding; source/Git metadata refreshes independently. A small embedding capability probe still runs on each index command. All changed vectors are prepared before content mutation. A remote `pending` marker is written before mutation and cleared only after upserts and obsolete/orphan deletion complete. Thus interrupted writes are visible even if local runtime state is lost; rerunning incremental indexing repairs them. A stable-inode checkout lock now gives search/read/status/stale/doctor/eval shared snapshot admission; index/rebuild/initialize retain exclusive admission across preparation, persistence and cleanup. Readers can run concurrently, while writers exclude every reader and other writer. Contention fails immediately with a reader-versus-writer diagnostic; it does not imply Qdrant itself lacks concurrent reads. Interrupted writes retain the pending marker and block search/evidence until explicit incremental repair. Locks are never replaced or unlinked. Do not run two checkouts against the same Compose project/collection.

Freshness compares source hashes, model/schema identity, parser implementation hash, branch, missing points, orphans and pending state. Parser implementation changes reparse sources while reusing unchanged chunk embeddings; embedding/chunk identity changes still require an explicit schema bump. Additive payload schema 2 has a separate reviewed metadata-policy fingerprint and refreshes incrementally without changing the embedding space or deleting the collection. A new Git commit alone does not require embeddings. Model/schema changes require an explicit `./bin/artisan runa:knowledge:rebuild`. Sources modified during preparation cause failure before content writes; modifications during persistence leave the final status stale.

## Package and ownership composition

Knowledge services use qualified `tools.application.knowledge` imports. The fixed
source executor selects the application composition root or the excluded owner
composition root in `tools.maintainer.knowledge_cli`; an acquisition receipt always
selects customer scope. Public configuration/golden loaders read only delivered
resources. Owner additions are explicit composition inputs, and source discovery
still excludes private material under customer receipts and all customer authoring
templates. Source allowlisting checks paths relative to the selected workspace.

`KnowledgeIndexer` and `Retriever` receive an explicit workspace/process/clock
context, store and embedding-provider factory. HTTP clients and retry waiting are
injectable. Configuration validates identities, service URLs, budgets, source paths
and retrieval weights before network operations. Collection manifests, chunk IDs,
model/schema identity, golden expectations and the pending-write protocol retain
their existing serialized format. Invalid service JSON and malformed source
manifests fail explicitly; response bodies are never included in error messages.

The parser fingerprint remains a hash of the actual corpus implementation. Package
migration or parser edits can therefore mark an existing index stale. Incremental
`runa:knowledge:index` reparses sources, updates metadata and reuses unchanged
semantic chunk vectors; a parser move alone does not authorize collection deletion,
model changes or `rebuild`. Existing regular operator-owned knowledge locks keep
their inode. Optional readiness has a separate bounded probe process; failed
initialization retries independently without blocking application setup.

## Supply-chain review and acceptance

Primary sources reviewed on 2026-10-17:

- Model metadata/license/limits: https://huggingface.co/BAAI/bge-small-en-v1.5 and https://huggingface.co/api/models/BAAI/bge-small-en-v1.5.
- Runtime release/license/hardware: https://github.com/huggingface/text-embeddings-inference/releases/tag/v1.9.4 and its pinned README/Dockerfiles/backend source.
- TEI HTTP contract: `router/src/http/types.rs`, `router/src/http/server.rs` and `router/src/lib.rs` at tag v1.9.4.
- Qdrant release/license: https://github.com/qdrant/qdrant/releases/tag/v1.19.2.
- Qdrant REST writes/scroll: https://api.qdrant.tech/api-reference/points/batch-update and https://api.qdrant.tech/api-reference/points/scroll-points (1.19.x).
- Published upstream advisories: https://github.com/huggingface/text-embeddings-inference/security/advisories and https://github.com/qdrant/qdrant/security/advisories.

The services bring their own native/OS dependency trees in images, not application dependency trees. The model repository also contains pickle/ONNX variants; this setup uses the safetensors path and does not install FlagEmbedding or execute remote Python. Reviewing advisories is not proof that built images are vulnerability-free. Trivy scans of the actual derived images were required for the baseline acceptance recorded below; future changed images require fresh scans. No finding is suppressed by this setup.

## Runtime images and acceptance

CPU embeddings and Qdrant use Ubuntu 24.04 runtime base `sha256:68ee22004a9109b44973313b02ff7b1de250f1de0f5ecb628d73f579aaff33ac`, resolved from the official registry on 2026-10-17. Qdrant's upstream executable/config/SBOM source is `qdrant/qdrant:v1.19.2@sha256:b7b0444c4c351c970b98e90a6f89c2ee4287c65b44e52b4cb503fa5b2aa927ad`. The dashboard is omitted and disabled: the local knowledge client uses REST only.

CPU copies the exact pinned TEI executable and upstream Intel MKL helper libraries. It installs OpenSSL/C++/OpenMP/certificate/probe runtime dependencies and explicitly supplies `libiomp5.so` through the OpenMP runtime. Qdrant installs unwind/lzma/certificate/probe dependencies. Both Dockerfiles execute `--version` during build to reject missing libraries or incompatible libc. CUDA uses the pinned upstream CUDA image with OS security updates and development headers removed. No Python ML stack or development LLVM/XML chain is installed in the CPU runtime.

This runtime selection resolves the HIGH/CRITICAL findings observed in the original Debian images. Package metadata and Qdrant's cargo SBOM remain available; no scanner finding is suppressed. The Ubuntu base is maintained by Canonical: https://hub.docker.com/_/ubuntu. Runtime dependencies were checked through host `ldd`, upstream Dockerfiles and actual inference on the final images.

Trivy 0.76.0 scanned the following final images on 2026-10-17 with `--scanners vuln --severity HIGH,CRITICAL`; all three reported zero findings. The host then confirmed that these exact image IDs are running healthy with zero restarts.

| Service         | Verified image ID                                                         |
| --------------- | ------------------------------------------------------------------------- |
| Qdrant          | `sha256:0b7cd585d993448d6112f6471a9aad9e2ce38f5b7ae24a124506e27728f90f71` |
| CPU embeddings  | `sha256:e438de0a1733663497428f18a8fd42092d036fdff75164c6731637a744930f0f` |
| CUDA embeddings | `sha256:065ac5a3fa3ea776cb73282c985bf3468a96b579fa5f7b3e875200b0d1d484e6` |

Reports are local artifacts under `.runtime/security/knowledge-*.json`; future image rebuilds require fresh scans. This is vulnerability-scan evidence for the selected severities and detected components, not a claim that binaries or images have no possible defects.

CUDA startup exceeded the initial 2 GiB container RAM limit (Docker OOM/exit 137). The accepted limit is 4 GiB with a 2048-token server batch; a post-startup host snapshot showed approximately 482 MiB RAM (CPU approximately 270 MiB). These are steady-state snapshots, not startup peak measurements. Pinned model cache reuse was verified across service recreation. New-image startup completed in approximately 8 seconds for CPU, 12 seconds for Qdrant and 159 seconds for CUDA on the current host; this is acceptance evidence, not a performance guarantee.

## Retrieval, evaluation and diagnostics

Dense search uses Qdrant's 1.19.x [query-points API](https://api.qdrant.tech/api-reference/search/query-points), with the pinned model's query prefix. A standard-library BM25 implementation scans the same filtered chunk payloads for lexical matching. It retains exact identifiers and also matches their component words and basic English inflections (for example version/versions and dependency/dependencies). This is intentionally local lexical retrieval rather than another stored sparse-vector representation. Weighted reciprocal-rank fusion combines both ranked lists. There are no additional dependencies, embedding spaces or collections. Loading/scanning the corpus per CLI process is suitable for this small development corpus; revisit it if measured latency grows.

`tools/application/knowledge/config.json` controls candidate count, dense/lexical weights, RRF constant, source diversity and active/historical multipliers. Dense/lexical baseline: 60 candidates per channel, dense weight 2, lexical weight 1, RRF constant 60, one result per source, active multiplier 1.03 and historical multiplier 0.8. Initial equal weights returned the expected isolation-policy document sixth; dense weighting and source diversity brought all eight golden cases into top five without changing the expectations. This is a small baseline, not evidence of general optimality. No global similarity cutoff is applied.

Filters are exact matches: `--document-type`, `--scope`, `--roadmap-id`, `--status` and `--source-path`. Both retrieval channels use identical filters. Only the currently indexed checkout branch is searched; the collection does not retain parallel main/branch copies. Switching branches requires incremental indexing before search, preventing old branch policy from winning. Active roadmap content receives a small boost; closed/paused/deprecated content is discounted. Stable architecture documents have no age penalty.

Normal output includes path, heading and a bounded excerpt, defaulting to five distinct sources for unrestricted searches. Filters whose candidate set contains exactly one source allow multiple sections within top-k (including a roadmap-ID filter); explicit `--per-source=1..10` bounds multi-section retrieval across sources. `--top-k` accepts 1–50. `--explain` emits JSON with filters, configuration, channel ranks/scores, fused scores and full source/hash/commit/model metadata. Result text is untrusted context. Open the returned source file and verify the relevant section before acting on a project rule; for example, retrieve production prohibition, then read `docs/runa/handbook/ai/knowledge-system.md`'s Production prohibition section.

`tools/application/knowledge/golden.json` covers architecture, Git, roadmap workflow, isolation, embeddings, exact names, section grounding and roadmap/source filtering. Eval returns per-case paths, pass/fail and query latency; a required source (and optional section) must appear within its top-k. Initialization/freshness/provider checks occur before per-query timing. Keep expected sources stable when comparing retrieval changes and review meaningful regressions. When a referenced roadmap closes, update its path/status filter together while retaining the same historical source and query; examples and workflow templates are not roadmap instances. Doctor checks collection dimensions/distance, model/runtime/pooling/input limits, manifest/schema/source freshness, missing/orphan chunks, per-chunk model/schema metadata and an end-to-end filtered retrieval smoke query; it reports document/chunk counts and mean/median chunk word counts. Service or model failures exit nonzero rather than reporting a healthy partial result.

`./bin/artisan runa:knowledge:rebuild` explicitly discards this development collection and re-embeds the allowlisted corpus. It validates provider readiness and parses current sources before deletion. Do not use it for normal edits. A service failure after deletion leaves a missing/stale index and requires rerunning index/rebuild; no old index is promised during rebuild. All CLI commands share the checkout lock, so reads/evaluation cannot race a CLI index write.

The real-service retrieval contracts use a disposable collection and temporary checkout. They cover dense plus lexical signals, exact identifiers, source diversity, metadata filtering, empty results, invalid queries, stale/pending/branch/missing states, corrupted model metadata and explicit rebuild. Existing offline ingestion and CPU/CUDA acceptance remain separate commands above.

## Documentation ownership and corpus

The customer allowlist includes AGENTS.md, README.md, docs/README.md, tools/application/quality.yml, Markdown under docs/app, docs/runa/handbook and .agents/skills. Only staged/tracked regular sources are indexed; secrets, artifacts and raw conversations stay excluded. Application roadmap metadata is parsed only under docs/app/roadmaps lifecycle directories; examples/templates remain documentation. Application roadmap identity is separate from Runa owner roadmap identity even if numeric IDs match.

Owner checkouts explicitly add their private roots and historical golden cases through excluded tools/maintainer configuration. A customer acquisition receipt disables those additions and forbids private maintainer sources even if accidentally copied or broadly allowlisted. Incremental refresh removes old paths/chunks; source moves do not authorize an automatic destructive rebuild. Doctor and public retrieval evaluation must pass after documentation changes.

Customer instruction authoring templates are excluded from owner retrieval even with a broad owner allowlist. Customer retrieval reads only the installed AGENTS/skills product. Incremental indexing removes obsolete source identities after substitutions or lifecycle moves. For multiple checkouts use an explicitly separate Compose project identity; never point a fresh customer project at an existing owner's Qdrant volume. Inspect actual volume mounts and preserve model caches.

## Evidence reading and structured output

Runa owns the development-only evidence CLI. Application developers and agents
consume `php artisan runa:knowledge:read <chunk_id> [--neighbors=0..5] [--section]
[--max-chars=1000..60000] [--json]` through the existing Artisan transport.
The implementation lives in `tools/application/knowledge/evidence.py`; Python
classes are internal, not an application extension API. Production remains denied.

Use IDs from `runa:knowledge:search --json` (or existing `--explain`). Search JSON
preserves filters, retrieval settings, full hit payloads and ranking signals;
ordinary search stays concise. Status accepts `--json` and retains its existing
JSON shape and native stale/nonzero exit semantics. Read always emits JSON, with
response schema version, collection/chunk schema identity, results, content size
and omitted-chunk count. It never embeds, indexes or reads a different collection.

Read verifies the current allowed corpus, manifest, branch and source hash. Missing
IDs require a new search; stale/missing/incompatible indexes and pending writes
fail explicitly. Neighbor expansion stays within one document; section expansion
selects the exact heading path. Results retain manifest/source order. The requested
chunk is always included or the command refuses an insufficient budget; expansion
omits whole chunks and reports the count, without silent text truncation. The
default content budget is 12000 characters; serialized response is capped at
256000 characters. Full payloads retain source, document/chunk identity, heading,
hash, Git context, indexing timestamp and embedding metadata when recorded.

Citations include source path/hash and one-based body line coordinates only for a
unique literal match in the verified current source. Ambiguous coordinates are
null; an indexing timestamp or Git revision never supplies an effective date or
release version. Open the cited original source before acting on a rule. Use
bounded expansion to gather missing context, and distinguish source facts, agent
inferences and absent evidence in the answer.

## Applicability and decision annotations

Payload schema 2 separates an `applicability` record from source/hash/Git freshness.
It always records `purpose` (`current_guidance`, `historical_evidence`, `proposal`,
`unknown`), nullable `effective_date`/`applicable_version`, and a `decisions` list.
Reviewed `metadata_rules` in the existing knowledge configuration assign purpose
by explicit literal/glob source patterns, in order; the last matching rule wins.
Unmatched sources remain unknown. These reviewed declarations are not evidence
that every sentence in a guide is currently applicable. Section declarations
refine mixed records, and contradictions still need source review.

A source author may add a standalone single-line HTML comment outside code fences:

```html
<!-- runa-knowledge {"purpose":"historical_evidence","effective_date":null,"applicable_version":null,"decisions":[]} -->
```

Place it before the first heading for source scope or inside a section for that
section. Declarations are excluded from embedded semantic text; examples inside
fences remain literal content. Each decision has `id`, `problem`, `solution`,
nullable `rationale`, `status` (`accepted`, `superseded`, `proposed`, `unknown`),
nullable ISO `date` and `version`, and `supersedes` (stable decision IDs). Record
only source-supported facts. Missing rationale/date/version stays null; an index
timestamp, Git commit or document age cannot fill the gap. Section-level decision
annotations describe the section as a whole, not a claim that every returned
fragment contains its full supporting text. Expand/read the section before using it.

Immutable history uses reviewed external annotations in the existing owner
`tools/maintainer/knowledge.json`: exact `source_path`, SHA256 `source_hash`, full
heading `section`, and `metadata` with the same applicability shape. A changed
hash or missing section refuses indexing until reviewed; never rewrite closed
history. These annotations aid navigation and do not replace the original decision
record. Owner configuration/annotations are excluded from customer delivery and
receipt-scoped composition. Application authors keep declarations in their own
allowlisted docs/app sources; editing protected platform configuration is unsupported.

The metadata policy has a separate manifest fingerprint. Schema-1 indexes become
explicitly stale and use supported `runa:knowledge:index` for payload backfill;
unchanged semantic chunk IDs/vectors are reused. Model/cache/collection/data stay
intact; no rebuild is needed for this additive metadata change.

Parser updates also make an existing index explicitly stale. Passage-level
chunking keeps paragraphs and top-level list items independently retrievable,
retains fenced examples and heading context, and splits over-budget passages
without truncation. Run normal incremental indexing after this update: unchanged
pieces retain IDs/vectors, changed pieces receive deterministic IDs and obsolete
IDs are removed. Expand a section through supported read rather than guessing an
old ID. Model/space pins and persistent data remain unchanged. A real embedding
space mismatch still requires separately reviewed explicit rebuild. Branch,
removed-source, pending-write and source guards retain their normal behavior.

## Retrieval intent and evidence limits

`runa:knowledge:search <query> [--intent=legacy|current|history|audit]
[--per-source=1..10] [--top-k=1..50] [--json|--explain]` preserves all existing
exact metadata filters and concise output. Omitted intent uses RRF/status
ranking without purpose bias; ranking order can improve across compatible updates. Unrestricted default diversity remains one chunk per source; a
single-source filter can return up to top-k sections. Explicit per-source budgets
allow bounded comparison without dumping entire documents. Use `read --section`
for missing neighboring evidence rather than continually increasing top-k.

Current intent applies declared-purpose factors (current guidance 1.2, history
0.65, proposal 0.5, unknown 1) alongside existing status factors. History intent
uses history 1.2/current 0.9/proposal 0.8/unknown 1 and neutral status. Audit uses
neutral purpose/status factors: it does not hide historical or contrary evidence.
All channels still share exact filters; no mode infers truth or uses an LLM.
JSON/explain exposes intent, purpose weights, per-hit status/purpose multipliers,
channel ranks/scores and full applicability. Unknown dates remain unknown.

Retrieval also follows one bounded hop of explicit relative Markdown citations.
It considers five leading sources after intent weighting, ranks their first sixteen
passages for the query, and follows at most three distinct target sources per seed
(fifteen total). Both seeds and targets must already belong to the exact filtered,
current-branch corpus. External/absolute paths, unindexed targets, self-links and
recursive traversal are excluded; this performs no file/network fetch. The citing
passage supplies lexical context, including explicit release identifiers, to select
one target chunk. The reference channel adds RRF weight 3 before normal target
status/purpose factors and final top-k/source budgets. JSON/explain exposes its
weight and per-hit reference signal. Citations aid navigation, not authority or
proof of applicability; open and verify the original target source.

Use short English semantic/identifier queries for the English baseline model.
Current-state questions need applicable public guidance plus delivery evidence;
history needs original and superseding decisions; audits need both sides and
explicit unresolved cases. A small top-k is a limited retrieval window, not proof
that no contrary decision exists. Expand/read cited sections and open originals.
High score, source synchronization, recent Git context and ingestion time establish
neither current behavior nor approval. Polish query probes measure observed
usability; they do not establish multilingual model support.

## Evaluation coverage and reports

Golden cases retain their original required source/optional section and bounded
top-k. They may additionally declare explicit `intent`, `per_source`, `max_chars`,
`expected` evidence (source/section, purpose, decision IDs, nullable scope fields)
and `forbidden_sources`. Evaluation accounts for selected hit content before
bounded section expansion through the supported evidence service. Missing coverage,
forbidden sources and required-source regressions fail; output reports each
coverage result, distinct sources, context characters, unknown scope and elapsed
time. Unknown scope checks establish faithful labels, not factual correctness.

`probe_only: true` marks a diagnostic query: its actual pass/fail remains visible
but it does not affect required-case PASS. Polish/English probes are reported this
way without relaxing any original English expectation. They do not certify
multilingual support. Doctor reports unknown applicability separately from missing
provenance/invalid schema/model metadata; unknown applicability alone is not failure.

`runa:knowledge:eval` prints JSON and preserves timestamped evaluation reports under
`.runtime/knowledge`. Owner-only real-corpus comparison runs in final documentation
acceptance: the exact search module from activation commit 36460e62 and the current
search run on the identical current corpus/model with compatible current transport
seams. It records the baseline module hash, branch/commit, raw-hit coverage,
per-case latency and candidate bounded evidence coverage. This measures retrieval
changes; it is not a replay of an old installation or proof of historical truth.
Baseline PASS becoming candidate FAIL is a regression; candidate required evidence
must pass. Existing CPU/CUDA/fallback, interruption/branch, concurrent-reader and
customer-source/production-isolation scenarios run only during final acceptance.

## Agent research and implementation grounding

A Qdrant-only exercise reports retrieved source claims and its sampled scope;
it does not establish actual installed code, manifest versions or runtime behavior.
For implementation, open the applicable original and inspect the relevant source,
then label which claims are source-verified. Keep deductions and unresolved gaps
separate. Prefer later applicable decisions/current contracts over superseded
history, while retaining original and contrary evidence for history/audit questions.
Use short English queries, explicit intent and bounded search/read expansion.
On unavailable services, lock contention or staleness, report the concrete reason
and use focused repository search; do not bootstrap optional AI merely to navigate.
Recheck after recovery/at the next relevant point. Fallback does not waive mandatory
freshness/hooks or selected strict final doctor/eval gates.

Python evidence/applicability modules ship through the existing application
knowledge package; config/golden resources remain explicit package data. Owner
annotations/goldens and customer authoring templates stay outside customer source
and corpus composition. Production omits the development package and catalogue;
physical presence in a development checkout never makes knowledge a runtime
application dependency or an extension API. Final delivery tests inspect the real
projection, receipt scope and production denial rather than trusting prose alone.

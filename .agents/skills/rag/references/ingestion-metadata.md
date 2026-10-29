# RAG Ingestion, Chunking, and Metadata

1. RAG quality must be measurable, but ingestion first preserves source structure and traceability.
2. Chunking is document-type aware; Markdown, roadmaps, skills, config, and changelog need not use one algorithm.
3. Preserve heading hierarchy as chunk context/metadata.
4. Use small retrieval chunks with optional larger parent context only if evaluations show it helps.
5. Parent-child chunking is not a mandatory baseline.
6. Every chunk is traceable to source file, document ID, section/heading path, and relevant roadmap/status context.
7. Do not chunk only on blank lines.
8. Do not force one fixed token count for every document type.
9. Split oversized sections further only as needed.
10. Keep overlap small; large overlap creates near-duplicate retrieval results.
11. Treat metadata as retrieval design, not decoration. Roadmap identity/status comes only from real roadmap frontmatter in lifecycle directories, with ID/filename/status/location agreement. Fenced examples and workflow/template documents remain documentation, even when they show roadmap fields.
12. Use stable `document_id` where practical independently from path; record relevant Git branch/ref metadata for branch-aware retrieval.
13. Use deterministic/derivable chunk IDs.
14. Create payload indexes only for metadata fields actually filtered on.
15. Record `indexed_at` and pipeline/chunk schema version.
16. Record embedding provider/model/version and dimensions in index/collection metadata.
17. A changed chunk schema/payload contract may require a rebuild.
18. Use one logical collection for the coherent Runa knowledge corpus by default.
19. Do not create collection-per-file/branch/roadmap-status; use metadata.
20. Named vectors are only for a genuine multi-representation need.
21. Vector dimensionality/model identity is a collection invariant.
22. Never silently mix embeddings from incompatible models/spaces.
23. A model change normally triggers a clean dev-only rebuild rather than production-grade zero-downtime dual-vector migration.
24. HNSW tuning is postponed until a real quality/performance issue exists.
25. Quantization is postponed until RAM/storage evidence demands it.
26. Sharding/replication is outside the local dev baseline.
27. Qdrant snapshots are not the knowledge source-of-truth backup; Git/source files are rebuildable authority.
28. Staleness is first-class and tied to source hashes/Git state; normal edits use incremental indexing and full rebuild is reserved for explicit model/chunk/schema/repair needs.
29. Source/chunk hashing should avoid unnecessary re-embedding after pure cosmetic changes where practical.
30. Deleted documents/chunks are removed deterministically; no orphan vectors.
31. Rename detection is optional; remove+insert is correct.
32. Indexer source roots are allowlisted rather than "index the entire repo."
33. Symlinks must not escape allowed source roots.
34. Ignore binary files unless an explicit parser exists.
35. Parser failure is explicit and must not silently produce an empty "successful" document.
36. Document reindex is atomic-ish: prepare new chunks/embeddings before replacing the prior set.
37. Batch embeddings when the provider supports it.
38. Batch size is a performance setting, not document semantics.
39. Respect provider limits/retry behavior.
40. Do not infinitely retry permanent input/model/dimension errors.
41. Never index secrets, `.env`, raw conversations, vendor/node_modules, runtime logs, coverage, build outputs, or generated browser artifacts.
42. Closed roadmaps prioritize Overview/Specification/Decisions/final outcome over noisy implementation checklist history.
43. CHANGELOG may be searchable history but should not outrank current code/docs.
44. Maintenance/deprecation registries should carry precise metadata for focused lookup.

Owner retrieval excludes docs/runa/maintainer/customer-agent regardless of broad allowlists. Customer indexes consume installed guidance only. Verify fresh project-scoped Qdrant storage by actual mounts and empty collections before initialization; preserve existing caches/collections and use incremental removal after source changes.

Parser/package changes may mark the index stale without changing its embedding
space. Follow the public knowledge guide's package/ownership composition procedure:
refresh incrementally and reuse unchanged semantic vectors; relocation alone never
authorizes collection deletion. Keep private corpus/golden composition out of the
delivered configuration loader. Test storage/HTTP/time boundaries through injected
knowledge dependencies rather than replacing module globals.

For explicit purpose, nullable applicability and source-owned decision declarations,
follow `docs/runa/handbook/development/knowledge.md#applicability-and-decision-annotations`.
Metadata refresh reuses semantic vectors; unknown dates/rationale remain unknown.

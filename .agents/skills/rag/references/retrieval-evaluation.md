# Retrieval, Grounding, Evaluation, and Diagnostics

1. Maintain a small versioned set of representative golden queries with expected source documents/sections.
2. Evaluate retrieval quality, not the language model's prose quality.
3. Keep the eval set small and representative rather than building an ML research platform.
4. Run the same evals when changing embedding model, chunking, fusion, metadata strategy, or reranking.
5. Do not optimize only top-1; a correct source in a small top-k is a meaningful success target.
6. Prefer result diversity over five near-identical chunks from one source.
7. Deduplicate/limit near-duplicate chunks at ingest/retrieval when they crowd out useful context.
8. Do not hardcode one global score threshold forever; tune from evals.
9. Hybrid dense + lexical/sparse retrieval remains the baseline.
10. Dense/sparse weights/fusion behavior are configurable.
11. Start with a simple fusion strategy such as RRF before complex ranking pipelines.
12. A reranker is optional and must be justified by evaluation gaps.
13. If introduced, rerank only a small candidate set.
14. Apply metadata filtering when context such as active roadmap/document type/status is known.
15. Keep query preprocessing minimal initially.
16. Add multi-query retrieval only if evals demonstrate phrasing/recall problems.
17. Agent search queries should describe the semantic problem concisely rather than dumping an entire prompt/conversation.
18. Exact identifiers/names benefit from lexical/exact search combined with semantic retrieval.
19. A stale index is explicitly reported.
20. Do not trust stale retrieval silently; reindex or open/search source directly.
21. Search results should expose source hash/commit metadata where useful for freshness checks.
22. Every result is traceable to source path + section.
23. RAG output is not authoritative by itself.
24. For significant rules, open the source file after retrieval and verify current content.
25. Default search output stays concise: small top-k, metadata, relevant fragment.
26. Allow explicit diagnostic top-k increases.
27. Explain/debug mode may show dense score, sparse score, filters, fusion/rank, chunk metadata.
28. Normal mode should not flood agent context with retrieval diagnostics.
29. `knowledge doctor` includes an end-to-end retrieval smoke query against a known source.
30. Doctor detects mixed dimensions/model metadata as hard failures.
31. Doctor detects both orphan chunks and missing indexed sources.
32. Doctor reports basic corpus stats such as docs/chunks/size/stale/orphans.
33. Do not add Grafana/Prometheus for this development-only knowledge system.
34. Meaningful golden-query regression blocks a pipeline change until understood.
35. Measure rough retrieval latency/performance so a new pipeline does not accidentally become unusably slow.
36. Retrieval should prefer the active roadmap branch context, then current main, then lower-priority historical material; current code/active specification ranks above historical decisions.
37. Closed roadmaps may receive lower retrieval boost.
38. Old stable architecture docs are not invalid merely because of age.
39. Deprecated docs should be metadata-marked/deprioritized when retained for history.
40. Removed/obsolete knowledge must not continue winning search after source deletion/update.

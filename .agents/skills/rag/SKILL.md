---
name: 'runa-rag'
description: 'Runa development-only knowledge/RAG guidance for ingestion, chunking, metadata, Qdrant, embeddings, hybrid retrieval, grounding, staleness, evaluation, and diagnostics.'
---

# Runa RAG / Knowledge

Use this skill when changing or diagnosing the development-only project knowledge system.

Read `docs/ai/knowledge-system.md`. For implemented commands, source allowlisting, image/model pins and hardware lifecycle, consult `docs/development/knowledge.md`; it documents search filters, RRF/BM25 configuration, golden evals, doctor, explicit rebuild and source-grounding workflow.

Load only:

- `references/ingestion-metadata.md`
- `references/retrieval-evaluation.md`
- `references/qdrant-embeddings.md`

The knowledge system never becomes a production application dependency.

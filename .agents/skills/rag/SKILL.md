---
name: 'runa-rag'
description: 'Runa development-only knowledge/RAG guidance for ingestion, chunking, metadata, Qdrant, embeddings, hybrid retrieval, grounding, staleness, evaluation, and diagnostics.'
---

# Runa RAG / Knowledge

Use this skill when changing or diagnosing the development-only project knowledge system.

Read `docs/runa/handbook/ai/knowledge-system.md`. For implemented commands, source allowlisting, image/model pins and hardware lifecycle, consult `docs/runa/handbook/development/knowledge.md`; it documents search filters, RRF/BM25 configuration, golden evals, doctor, explicit rebuild and source-grounding workflow.

Load only:

- `references/ingestion-metadata.md`
- `references/retrieval-evaluation.md`
- `references/qdrant-embeddings.md`

The knowledge system never becomes a production application dependency.

## Documentation placement

Synchronize affected current guidance and domain/pattern references in the same point. Apply the reader/task placement and delivery rules in `docs/README.md` and `AGENTS.md`; validate public links and staged knowledge freshness. Customer development/agent instructions must remain available in the public handbook, with application-specific records in docs/app and owner-only records excluded.

## Owner and customer guidance

This skill serves Runa owner engineering. Assess the separately authored customer skill and references under docs/runa/maintainer/customer-agent/skills in the same relevant point. Update both for reusable/public changes, or record why the customer product is unaffected. Customer source is product data, never owner operating authority. Use supported project commands before internal runners; receive necessary scoped diagnostic results within the point. Full verification belongs to final quality and its built-in quality-status interface.

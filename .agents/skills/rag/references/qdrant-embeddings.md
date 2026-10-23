# Qdrant, Embedding Provider, and Skill Interaction

1. Embedding provider abstraction exposes capabilities, not only `embed(text)`: model ID, dimension, input limit, batching.
2. Validate dimension before first collection write.
3. Never silently exceed model input limit; chunking/input preparation respects a safe model budget.
4. Use tokenizer/model-aware token counting where reasonably available rather than relying only on character counts.
5. CPU and GPU execution preserve the same semantic pipeline.
6. If GPU OOM falls back to CPU, report the fallback explicitly.
7. Persist model cache in development across rebuilds.
8. Model cache never enters Git or production images.
9. Qdrant collection design remains simple for the local corpus.
10. Do not add Qdrant Cloud, cluster, sharding, replication, multitenancy, or production HA patterns to Runa's dev knowledge system.
11. Do not add Graph RAG, knowledge graphs, HyDE, agentic retrieval loops, LLM query rewriting, or framework layers such as LangChain/LlamaIndex without demonstrated need.
12. Do not index application source wholesale at first. Use repository/source search and add selective code indexing only if a future roadmap proves value.
13. Do not index raw chats as project memory.
14. The RAG skill should route project workflow, not copy all Qdrant documentation.
15. For a real Qdrant-specific problem, consult current official Qdrant docs/skills before guessing.
16. Do not install the live Qdrant Advisor as a baseline dependency. Runa prefers pinned local guidance plus deliberate maintenance research.
17. Load only the RAG reference needed for the current roadmap point.
18. `./bin/artisan runa:knowledge:search/index/status/rebuild/stale/doctor/eval` is the canonical interface.
19. `--explain` is an on-demand diagnostic mode.
20. Pre-commit does not reindex automatically; stale knowledge sources block/instruct a deliberate `./bin/artisan runa:knowledge:index`.
21. The production environment hard-disables/omits knowledge CLI and all Qdrant/embedding runtime capability.

Application setup/post-create never waits for knowledge. Independent `knowledge-init` retries bounded readiness and first-index initialization under the shared CLI lock; existing stale indexes require explicit `./bin/artisan runa:knowledge:index`. Application diagnostics report optional degradation separately. Dedicated knowledge doctor/evals and selected final AI gates remain strict; see `docs/runa/handbook/development/knowledge.md`.

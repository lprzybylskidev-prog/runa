# Qdrant and embedding services

The [development knowledge guide](../../../../docs/runa/handbook/development/knowledge.md)
owns actual images/model/provider contracts, tokenizer limits, CPU/CUDA/fallback,
collection/schema identity, caches, optional startup and supported commands.

Preserve one coherent embedding space, explicit dimension/input validation and
reported CPU fallback. Keep model caches and Qdrant data during ordinary recovery.
Do not add cluster tuning, a reranker/model/framework/cloud service or wholesale
code indexing without measured need and separately approved scope/dependencies.
For uncertain service APIs consult current official documentation; no upstream
research or compatibility claim is implied by a local metadata review.
Knowledge remains optional development tooling and never a production dependency.

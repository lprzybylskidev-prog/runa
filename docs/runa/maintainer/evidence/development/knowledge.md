# Owner procedure and historical evidence

Source classification: `docs/runa/handbook/development/knowledge.md`. These owner-only procedures and dated execution records are retained separately from customer guidance. Historical claims keep their original context; current public operations are documented in the handbook.

RM-2026-001 point 9 uses Qdrant 1.19.2 (Apache-2.0), Hugging Face Text Embeddings Inference (TEI) 1.9.4 (Apache-2.0), and `BAAI/bge-small-en-v1.5` (MIT), pinned to model revision `5c38ec7c405ec4b44b94cc5a9bb96e735b38267a`. The model provides 384-dimensional English embeddings with a 512-token input limit; weights contain approximately 33.36 million parameters (about 133 MB in float32). This is a modest local documentation baseline, not a claim of optimal retrieval quality; the versioned golden queries below measure project retrieval quality.

Application setup and devcontainer post-create do not probe or initialize knowledge. The independent `knowledge-init` service performs bounded readiness probes, then calls `./bin/artisan runa:knowledge:initialize` under the shared CLI lock. It initializes only a missing collection or an empty collection left by interrupted first initialization. Existing collections receive freshness diagnostics and explicit `./bin/artisan runa:knowledge:index` instructions when stale; reopen does not automatically re-embed changed documents. Unavailable services, a held indexing lock or an indexing failure produce a delayed nonzero exit and Compose retries with `restart: on-failure`. No application service depends on this job or AI readiness. A successful job exits 0; a later normal startup reruns it. `./bin/artisan runa:dev:up` waits for application services first, then starts AI services without waiting for model download, indexing or GPU health. If AI startup itself fails, it reports degradation; repair and rerun startup. `./bin/artisan runa:dev:knowledge-status` inside the development container reports bounded service/freshness diagnostics; dedicated knowledge doctor/eval and final AI gates remain strict. Application requests, workers and database code do not call knowledge services. Production images use explicit application copies and contain neither this CLI nor `tools/application/knowledge`; production Compose must omit all knowledge services (baseline isolation acceptance is recorded in closed RM-2026-001).

```sh
./bin/artisan runa:knowledge:index
./bin/artisan runa:knowledge:status
./bin/artisan runa:knowledge:stale
./bin/artisan runa:knowledge:index --device cpu
python3 tools/maintainer/testing/knowledge.py
python3 tools/maintainer/testing/knowledge-live.py
```

Eleven offline contracts and the real-service disposable-collection tests pass on the final images. Live checks cover CPU/CUDA dimensions and cosine similarity (>0.99999), token-budget rejection, CPU fallback, initial/no-op indexing, add/edit/delete, orphan cleanup, interrupted-state recovery and branch refresh. The disposable collection is removed afterward. The existing corpus survived service recreation; repeated CPU/CUDA indexing embedded zero unchanged chunks. Full setup and its fingerprint fast path pass without dependency changes or destructive migrations. Retrieval acceptance is recorded below; complete production isolation baseline evidence is recorded in closed RM-2026-001. These dated service measurements are historical evidence, not current image scan results.

```sh
./bin/artisan runa:knowledge:search "PostgreSQL public_id UUIDv7"
./bin/artisan runa:knowledge:search "production knowledge prohibition" --source-path docs/runa/handbook/ai/knowledge-system.md
./bin/artisan runa:knowledge:search "hybrid retrieval" --roadmap-id RM-2026-001 --status closed --explain
./bin/artisan runa:knowledge:search "embedding fallback" --top-k 10 --device cpu
./bin/artisan runa:knowledge:doctor
./bin/artisan runa:knowledge:eval
python3 tools/maintainer/testing/knowledge-retrieval.py
```

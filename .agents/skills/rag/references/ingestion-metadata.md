# Ingestion and metadata

Load [the knowledge architecture](../../../../docs/runa/handbook/ai/knowledge-system.md)
for allowlists, structural/token-budgeted chunks, deterministic identity and one
embedding space. The [development knowledge guide](../../../../docs/runa/handbook/development/knowledge.md)
owns source discovery, current pins, freshness, pending-write recovery and the
explicit incremental payload migration.

Use its applicability/decision-annotation section for reviewed source purpose,
nullable dates/version/rationale and immutable-history hash/section references.
Preserve unknown values and historical bytes; source freshness is not factual truth.
Keep source/model/schema/branch identity and removed-source cleanup intact.

Owner corpus/annotations stay private; customer receipts and authoring-template
exclusions apply even if files were copied. Never ingest secrets, raw chats,
dependencies, runtime state or the full application source tree by default.
Parser-only or additive metadata updates reuse unchanged vectors; collection/model
changes require the owning guide's separately reviewed explicit migration.

# Documentation ownership

Classify a document by its reader and task, not by whether it mentions development.

- `docs/app`: application-owned documentation, decisions and roadmaps. The lifecycle directories `roadmaps/draft`, `active`, `paused` and `closed` start empty. Keep application records here, never under `src/app/docs`. Application module documentation belongs in `docs/app/modules/<code>/`, never inside `src/app/modules/<code>/docs`.
- `docs/runa/handbook`: delivered Runa guidance for application developers, operators and agents, including development/testing, architecture/public contracts, security, quality, workflows/templates, Boost and knowledge tooling.
- `docs/runa/maintainer`: owner-only Runa engineering, platform roadmaps/release metadata, dependency maintenance and audit evidence. It is absent from customer dev/prod source and images and from their knowledge corpus.

The root README describes the delivered application workflow. In the owner checkout root AGENTS and .agents/skills govern Runa maintenance. Customer AGENTS/CLAUDE/skills are separately authored under docs/runa/maintainer/customer-agent and substituted at acquisition. Customer instructions, hooks and tools/application remain available to customer development. Owner tools under `tools/maintainer` and `distribution` are excluded from delivery. One authoritative document owns each rule; split mixed material instead of copying an entire owner guide into the handbook.

## Maintenance rules

Use English. Follow the authority hierarchy in AGENTS.md. Update affected current-state documentation with implementation, replace obsolete claims and preserve historical evidence. Never persist chat transcripts, task chatter, temporary debug notes or speculation as durable knowledge.

Closed roadmaps and dated reports retain their original contents when moved. Their old paths and dates describe historical source layout; do not rewrite them to claim current behavior. Current procedures and live tools use the paths above. Full roadmap verification includes a source review of current guidance; deterministic link/state checks supplement that review.

On 2026-10-22 the owner explicitly authorized a one-time metadata-only exception for the seven existing closed roadmaps: add `proposed_version` from their actual released versions. Their remaining bytes are unchanged. `docs/runa/maintainer/documentation-map.json` records the exact original and updated hashes; the completed migration recorded exact before/after hashes and removal of the single new field restored the original bytes. The transition exception is retired; the owner hook enforces the resulting immutable hashes. This exception does not authorize later historical edits or changes to published tags/release evidence.

The knowledge allowlist includes app/public guidance and supplied skills. Owner checkouts explicitly add owner material through their excluded maintainer configuration. Customer receipt contexts never add maintainer documents even if private files were accidentally copied locally. Stage new durable sources before incremental indexing; knowledge freshness checks remain mandatory.

Module source does not own documentation directories. Runa module developer/operator guidance belongs in `docs/runa/handbook/modules/<code>/`; owner engineering records still belong in `docs/runa/maintainer`. Manifest documentation paths are repository-root-relative metadata and do not require documentation files at runtime. Generators follow the same central placement.

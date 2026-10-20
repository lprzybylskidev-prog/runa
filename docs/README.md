# Documentation ownership

Classify a document by its reader and task, not by whether it mentions development.

- `docs/app`: application-owned documentation, decisions and roadmaps. The lifecycle directories `roadmaps/draft`, `active`, `paused` and `closed` start empty. Keep application records here, never under `src/app/docs`.
- `docs/runa/handbook`: delivered Runa guidance for application developers, operators and agents, including development/testing, architecture/public contracts, security, quality, workflows/templates, Boost and knowledge tooling.
- `docs/runa/maintainer`: owner-only Runa engineering, platform roadmaps/release metadata, dependency maintenance and audit evidence. It is absent from customer dev/prod source and images and from their knowledge corpus.

The root README and AGENTS contract address the application workflow. Supplied `.agents/skills`, hooks and `tools/application` remain available to customer development. Owner tools under `tools/maintainer` and `distribution` are excluded from delivery. One authoritative document owns each rule; split mixed material instead of copying an entire owner guide into the handbook.

## Maintenance rules

Use English. Follow the authority hierarchy in AGENTS.md. Update affected current-state documentation with implementation, replace obsolete claims and preserve historical evidence. Never persist chat transcripts, task chatter, temporary debug notes or speculation as durable knowledge.

Closed roadmaps and dated reports retain their original contents when moved. Their old paths and dates describe historical source layout; do not rewrite them to claim current behavior. Current procedures and live tools use the paths above. Full roadmap verification includes a source review of current guidance; deterministic link/state checks supplement that review.

The knowledge allowlist includes app/public guidance and supplied skills. Owner checkouts explicitly add owner material through their excluded maintainer configuration. Customer receipt contexts never add maintainer documents even if private files were accidentally copied locally. Stage new durable sources before incremental indexing; knowledge freshness checks remain mandatory.

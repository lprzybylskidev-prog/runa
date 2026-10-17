# Runa Documentation

This directory is the durable project knowledge base. Keep documents focused, current, and non-duplicative.

## Authority

If documents conflict, use the authority hierarchy defined in `AGENTS.md`:

1. active roadmap,
2. current code,
3. `AGENTS.md`,
4. project skills and durable docs,
5. README/general docs.

## Sections

- `architecture/` — application, database, API, frontend, configuration, naming, deferred concerns
- `ai/` — skills, knowledge/RAG, Laravel Boost policy
- `workflows/` — roadmap, Git, releases
- `testing/` — backend/frontend/E2E testing
- `quality/` — local quality gate and gate definition
- `development/` — development environment
- `production/` — runtime, deploy, rollback, backup
- `security/` — security baseline
- `data/` — seeding
- `maintenance/` — external dependency registry, maintenance backlog, deprecations
- `release/` — release-readiness requirements
- `tooling/` — canonical command surface
- `roadmaps/` — roadmap lifecycle instances

## Documentation rules

- English only.
- Update existing material instead of blindly appending.
- Remove obsolete statements when a durable rule changes.
- Do not preserve task chatter, temporary debugging notes, speculation, or abandoned designs.
- Keep implementation details close to the document that owns them.
- Do not create a new document merely to avoid editing the correct existing document.

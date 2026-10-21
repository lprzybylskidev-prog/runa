---
name: 'runa-postgresql'
description: 'Runa PostgreSQL guidance for schema/types/constraints/indexes, query planning, migrations, concurrency, extensions, and operational safety. Project database architecture remains authoritative.'
---

# Runa PostgreSQL

Load this skill for PostgreSQL schema/query/migration/concurrency work.

Read `docs/runa/handbook/architecture/database.md` first for durable project invariants.

Then load only:

- `references/schema-indexes.md`
- `references/operations-performance.md`

Runa explicitly forbids database views, stored procedures/functions, and business-logic triggers unless the project constitution is changed.

## Documentation placement

Synchronize affected current guidance and domain/pattern references in the same point. Apply the reader/task placement and delivery rules in `docs/README.md` and `AGENTS.md`; validate public links and staged knowledge freshness. Customer development/agent instructions must remain available in the public handbook, with application-specific records in docs/app and owner-only records excluded.

---
name: "runa-postgresql"
description: "Runa PostgreSQL guidance for schema/types/constraints/indexes, query planning, migrations, concurrency, extensions, and operational safety. Project database architecture remains authoritative."
---

# Runa PostgreSQL

Load this skill for PostgreSQL schema/query/migration/concurrency work.

Read `docs/architecture/database.md` first for durable project invariants.

Then load only:
- `references/schema-indexes.md`
- `references/operations-performance.md`

Runa explicitly forbids database views, stored procedures/functions, and business-logic triggers unless the project constitution is changed.

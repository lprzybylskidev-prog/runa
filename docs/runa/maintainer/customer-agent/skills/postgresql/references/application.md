# Application postgresql guidance

Use PostgreSQL with strong simple constraints, query-driven indexes and short transactions. Respect application/platform ownership, bigint internal keys, UUIDv7 public IDs, UTC and optimistic edit revisions. Committed production migrations are immutable; do not reset data during ordinary reopen.

Load only the relevant canonical procedure:

- Database schema/reset/migration policy: `docs/runa/handbook/architecture/database.md`.
- Isolated test workers: `docs/runa/handbook/testing/strategy.md`.
- Module-owned schemas and public references: `docs/runa/handbook/architecture/module-framework.md`.
- Transactional Audit: `docs/runa/handbook/modules/audit/README.md`.

Maintain application-specific patterns in local guidance/docs/app. Ordinary App
code and optional app modules are supported; copied owner files or a Contracts
namespace never authorize platform/internal APIs. Preserve installed local
extensions during reviewed updates. This default contains no owner-only procedure.

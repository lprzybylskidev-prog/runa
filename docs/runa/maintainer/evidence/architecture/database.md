# Owner procedure and historical evidence

Source classification: `docs/runa/handbook/architecture/database.md`. These owner-only procedures and dated execution records are retained separately from customer guidance. Historical claims keep their original context; current public operations are documented in the handbook.

Before a major module reaches production, review its schema/query paths and migration safety. Full release-scale performance requirements are in `docs/runa/maintainer/release/1.0-readiness.md`.

This is deliberate roadmap work, never automatic startup/reopen behavior. Ordinary reopen performs no database migrations or seeding and preserves existing data and credentials. After any actual production deployment, use additive migrations and production data safeguards. See `docs/runa/handbook/development/environment.md` for normal initialization and `docs/runa/maintainer/production/deployment.md` for production evidence and operations.

RM-2026-001 creates only `shared.migrations`, `shared.jobs` and `shared.failed_jobs`. All three use bigint `GENERATED ALWAYS AS IDENTITY`, verified against `information_schema.columns`. Laravel's default migration repository emits an integer serial ID, so `IdentityMigrationRepository` overrides only table creation with the project schema/identity contract. Business schemas, users, auth/session tables and batch tables are absent until an owning use case needs them.

Private `tools/maintainer/migrate`, `migrate-status`, `migrate-fresh`, `migrate-refresh`, `migrate-reset`, `migrate-rollback`, `wipe` and `seed` select only Runa through the external `distribution/platform-database.php` bootstrap. Neither root is customer-delivered and there is no customer Artisan owner selector. Native `make:migration` writes app migrations; the owner uses `tools/maintainer/make-migration`.

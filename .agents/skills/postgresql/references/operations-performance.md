# PostgreSQL Migrations, Performance, and Concurrency

1. Large-table migrations require an explicit lock/rewrite/backfill/runtime risk assessment.
2. Use expand-contract only for live changes that genuinely cannot be deployed safely in one compatible step.
3. Do not write fake reversible migration logic when rollback would actually lose data or be unsafe.
4. Batch large backfills when one giant update would create unacceptable locks/WAL/runtime impact.
5. Consider `CREATE INDEX CONCURRENTLY` for a large live table when a normal build would block writes unacceptably.
6. For expensive existing-data validation, consider staged constraints such as `NOT VALID` plus later validation when appropriate.
7. Verify real PostgreSQL syntax; do not invent conveniences such as unsupported `ADD CONSTRAINT IF NOT EXISTS`.
8. Prefer Laravel Schema Builder when it expresses the intended PostgreSQL semantics correctly.
9. Raw PostgreSQL in migrations is allowed for PG-specific capability that Schema Builder cannot safely/clearly express.
10. Raw SQL migration intent must be clear so later refactors do not weaken semantics.
11. Ensure statistics/ANALYZE state is credible before interpreting surprising plans.
12. Use realistic data distributions for performance evaluation, not only high row counts.
13. Before a major module reaches production, review types, constraints, nullability, indexes, query paths, and migration safety.
14. Do not add PgBouncer/pooling until actual connection pressure justifies it.
15. Do not treat `max_connections` as "more is faster"; diagnose connection behavior first.
16. Retry only known transient deadlock/serialization failures and only when the operation is safely retryable/idempotent.
17. Prefer short transactions and stable update/lock order before relying on retries.
18. Pessimistic locking such as `FOR UPDATE` is an explicit use-case decision, not normal CRUD policy.
19. Advisory locks are only for real global coordination needs.
20. Runa's default business edit concurrency remains optimistic.
21. Do not introduce hard edit locks merely because two users might open the same record.
22. Committed migrations that may have run in production are immutable; use the canonical policy in `docs/architecture/database.md`.
23. Before first production deployment, clean/edit committed migrations as needed to keep the initial schema coherent. Necessary identified Runa dev/test resets and volume rebuilds are authorized within an active roadmap under `docs/architecture/database.md`; inspect actual context, show exact scope, preserve unrelated/durable data and retain test guards. Never reset automatically on reopen.
24. If an uncommitted migration is wrong, fix it instead of adding a compensating migration.
25. Actual real-deployment evidence overrides stale false metadata; simulation does not. Stop and reconcile contradictory evidence before migration/reset work, using the canonical database policy.
26. Runa does not use database RLS as the baseline authorization layer.
27. Runa does not use business-logic triggers.
28. Runa does not use stored procedures/functions/views.
29. `pg_stat_statements` may be introduced later as a diagnostic extension, not as a baseline dependency.
30. Backup trust requires actual restore verification, not merely successful dump creation.

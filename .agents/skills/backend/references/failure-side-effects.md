# Failure, Side Effects, and Dependencies

1. Classify dependency failures as retryable or non-retryable.
2. Retries require idempotency/deduplication when duplicate effects would be dangerous.
3. Graceful degradation is only for a consciously optional dependency/capability.
4. Background jobs must expose enough failure context for diagnosis without leaking secrets.
5. Propagate a correlation/request identifier through logs and future jobs/integrations where useful.
6. Time-dependent logic must be controllable/testable.
7. Randomness that affects logic must be controllable/testable.
8. Identify invariants and enforce them in the correct layer:
   - database for simple durable relational/value invariants,
   - application code for business workflow/invariants.
9. Do not use exceptions as routine flow for an expected normal result when an explicit result is clearer.
10. Keep database transactions short.
11. Do not perform long file operations or external HTTP calls inside a database transaction.
12. Dispatch jobs/events after commit when they depend on committed state.
13. Nested transactions/savepoints are conscious choices.
14. Use stable update/lock order where possible to reduce deadlocks.
15. Events describe facts that happened rather than commands.
16. Critical immediate cross-module behavior should normally be an explicit call rather than an event detour.
17. Jobs delegate business work to Actions/Services rather than becoming the business layer.
18. Idempotency keys/deduplication belong on costly/non-reversible duplicate-risk operations, not mechanically on ordinary CRUD.
19. Dedicated application exceptions represent meaningful categories and are mapped centrally.
20. Do not catch `Throwable` solely to manufacture a generic 500.
21. Technical exceptions are logged internally; user/API contracts receive stable safe error information.

Production deployment records each mutating phase and retains HTTP/writer fencing after migration, seeding or health failure. Arbitrary application seeders may commit partial effects; matching source fingerprints do not prove schema/data compatibility or authorize automatic rollback/replay. Preserve manual maintenance and unresolved-operation ownership. See docs/runa/handbook/production/deployment.md for the current recovery contract.

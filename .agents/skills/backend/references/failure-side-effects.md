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

Complete customer-repository deployment orders scoped forward platform migrations/required transactional idempotent seeds before application migrate/explicit DatabaseSeeder. Record the exact failed phase and preserve maintenance/writer fencing; no blind retry after uncertain data effects and no claim that code rollback reverses data. Ordinary local startup remains independent of explicit data preparation.

## Module installation boundaries

Use ModuleUpdates for explicit owner-scoped module installation, never lifecycle toggles or request boot. Pure upgrade descriptors/preflight declare forward version paths; schema migrations and transactional upgrade completion records commit atomically. Nontransactional retry requires explicit idempotency, otherwise retain the source/owner/fingerprint fence and follow documented operator recovery. Reuse the caller's installation/deployment journal/lock/maintenance; the platform updater selects only Runa modules. App-module invocation and ordering stay explicit in the customer deployment. See docs/runa/handbook/architecture/module-framework.md for the delivered contract.

Meaningful business/security mutations require [Audit history](../../../../docs/runa/handbook/modules/audit/README.md). Use Auditable with source-owned approved fields inside the default PostgreSQL transaction; automatic capture supports single-model save/delete/restore and scoped suppression. Bulk SQL, relationship/pivot changes and aggregate operations require explicit manual events on that same transaction. Suppression never disables explicit records. Follow the guide for nested redaction, fail-closed rollback, independent refusal and permission-protected bounded reads; arbitrary Eloquent writes are not automatically captured.

Use the [Auth preference/delivery contract](../../../../docs/runa/handbook/modules/auth/preferences.md) for source-declared account preferences, optimistic revisions, explicit safe guest adoption and email-only routing. Never store credentials/drafts in preferences, infer legacy ownership, bypass mandatory primary recipients or cache recipient decisions across jobs. Security mail uses reference-only managed work and revalidates state before its persisted single SMTP attempt; recovery retains direct mail.

Follow the [Auth policy/operator contract](../../../../docs/runa/handbook/modules/auth/policies-and-operators.md)
for atomic settings/history/Audit/proof consumption, bounded account exceptions,
preview-bound bulk revocation and trusted CLI initialization/recovery. Keep private
one-time material out of command arguments/output and logs; operator access is the
customer's infrastructure boundary. Do not grant a temporary administrator flag or
call guarded business gateways behind a settings/update fence.

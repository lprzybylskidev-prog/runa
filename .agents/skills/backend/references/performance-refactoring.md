# Performance and Refactoring

Follow [read-path performance](../../../../docs/runa/handbook/architecture/application-architecture.md#read-path-performance)
for measurement, analogous-consumer repair, authorization boundaries and
transaction/rollback freshness. This includes metadata, declarations and
preferences, not only Eloquent relations.

1. Treat demonstrated N+1 behavior as a quality bug in normal application paths.
2. Inspect relations and repeated infrastructure queries on lists and serialization.
3. Heavy queries should not fetch more columns/relations than needed.
4. Select pagination strategy by use case; use cursor pagination only when it brings a concrete benefit.
5. Refactoring must not alter behavior unless behavior change was agreed.
6. Protect risky refactors with meaningful tests.
7. Do not optimize without evidence.
8. Do not introduce caches, queues, search infrastructure, or denormalization solely because future scale is imaginable.
9. Prefer a readable implementation over a clever but opaque one when both meet the real workload.
10. When a performance problem is real, measure the relevant layer before changing architecture.

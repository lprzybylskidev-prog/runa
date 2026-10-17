# Performance and Refactoring

1. Treat N+1 behavior as a quality bug in normal application paths.
2. Consciously inspect relations/query count on lists and resource serialization.
3. Heavy queries should not fetch more columns/relations than needed.
4. Select pagination strategy by use case; use cursor pagination only when it brings a concrete benefit.
5. Refactoring must not alter behavior unless behavior change was agreed.
6. Protect risky refactors with meaningful tests.
7. Do not optimize without evidence.
8. Do not introduce caches, queues, search infrastructure, or denormalization solely because future scale is imaginable.
9. Prefer a readable implementation over a clever but opaque one when both meet the real workload.
10. When a performance problem is real, measure the relevant layer before changing architecture.

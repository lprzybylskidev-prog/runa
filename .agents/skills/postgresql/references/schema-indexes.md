# PostgreSQL Schema, Types, Constraints, and Indexes

For framework module data, use the [module boundary contract](../../../../docs/runa/handbook/architecture/module-framework.md#delivered-postgresql-boundaries): derive `runa_<code>`/`app_<code>` ownership through ModuleIdentity, migrate through transactional ModuleSchema and use ModuleModel for exposed domain entities. Validate real identity/FK DDL; only same-module bigint FKs with RESTRICT actions are allowed. Keep foreign-module UUIDv7 references free of FKs and validate through public APIs, with explicit handling of the validation/deletion race. Preserve retained schema claims and registry provenance; never adopt an existing unclaimed schema or infer wipe ownership from a name alone.

1. Normalize first; denormalize only after measurement demonstrates a concrete benefit.
2. Prefer `text` over arbitrary `varchar(255)` unless a real length invariant exists.
3. Use `timestamptz` for real moments in time.
4. Use exact `numeric` when fractional precision is required; never float for money.
5. Store booleans as boolean.
6. Avoid quoted identifiers; use lowercase snake_case.
7. Understand normal UNIQUE + NULL semantics. Use `UNIQUE NULLS NOT DISTINCT` only for a real supported-version need.
8. A CHECK that evaluates to NULL does not replace NOT NULL.
9. Use exclusion constraints (for example `EXCLUDE USING gist`) when they accurately encode non-overlap/range invariants.
10. Native range types may simplify genuinely range-centric domains; do not use them ceremonially.
11. Choose index type for the operator/query: B-tree, GIN, GiST, BRIN as appropriate.
12. Design composite indexes with real leftmost-prefix/query behavior in mind.
13. Use covering `INCLUDE` columns only for a measured/high-value index-only-scan opportunity.
14. Expression indexes must match the actual query expression/semantics.
15. Consider BRIN only for very large naturally ordered data where it fits.
16. A sequential scan is not automatically bad.
17. Use `EXPLAIN (ANALYZE, BUFFERS)` for real diagnostic/performance review when safe.
18. Do not impose a universal latency target such as 100 ms on every query.
19. Do not dogmatically ban `SELECT *`; use purposeful projection on heavy report/list paths.
20. Avoid predicates that wrap indexed columns in functions when an equivalent index-friendly range is clearer.
21. Leading wildcard search requires a deliberate search/index strategy if it becomes important at scale.
22. Use JSONB for genuinely semi-structured data, not to avoid relational design.
23. If JSONB is queried materially, choose GIN/operator class from the real operators.
24. Do not represent normal relations as JSON/arrays of foreign IDs.
25. Partition only after a real operational/performance need.
26. If partitioning is ever introduced, choose the partition key from actual access/retention patterns.
27. Cardinality/selectivity matters when deciding index usefulness.
28. Redundant/overlapping indexes are write/storage debt; check existing indexes before adding another.
29. Do not automatically remove an apparently unused index without understanding statistics/window/workload.
30. Index names are deterministic and understandable.
31. PostgreSQL does not automatically index every foreign key; decide FK indexes from actual access/update/delete patterns.
32. Prefer set-based operations over row-by-row application loops for bulk work when semantics/readability permit.
33. Window functions are useful for natural ranking/aggregation use cases.
34. CTEs are chosen for clarity/semantics; inspect the plan for heavy paths.
35. Temporary tables are reserved for concrete heavy operations, not normal application communication.
36. Do not install PostgreSQL extensions "just in case."
37. Track every installed extension as a runtime dependency.
38. `citext` is not the automatic solution to all case-insensitive fields.
39. Collation/locale-sensitive sort/uniqueness semantics must be deliberate where business correctness depends on them.
40. Full-text/trigram search is introduced only for a real need and after evaluating normal search.

Durable dictionaries use code/name_pl/name_en/description_pl/description_en, projected deliberately from source pl/en descriptors through DictionaryEntry. Authority-affecting operations use the shared registry/advisory/Auth/Permissions lock order and exact Connection described in [module foundations](../../../../docs/runa/handbook/architecture/module-foundations.md); no cross-module SQL/FK/model exception follows.

Meaningful business/security mutations require [Audit history](../../../../docs/runa/handbook/modules/audit/README.md). Use Auditable with source-owned approved fields inside the default PostgreSQL transaction; automatic capture supports single-model save/delete/restore and scoped suppression. Bulk SQL, relationship/pivot changes and aggregate operations require explicit manual events on that same transaction. Suppression never disables explicit records. Follow the guide for nested redaction, fail-closed rollback, independent refusal and permission-protected bounded reads; arbitrary Eloquent writes are not automatically captured.

Auth identifier ownership uses normalized unique claims, with retention by default and explicit release on future deletions. Restore must reacquire claims atomically without stealing a newer identity or transferring history; expiry, activation, approval, deletion and locks remain independent. Follow the [Auth identity/lifecycle guide](../../../../docs/runa/handbook/modules/auth/README.md) for the authoritative procedure.

Use the [Auth entry transport contract](../../../../docs/runa/handbook/modules/auth/transport.md)
for native session/CSRF bootstrapping, registration/invitation/verification and
private single-use delivery boundaries. Do not add duplicate Fortify routes or put
credentials/tokens in module APIs, events, metadata or queue payloads. Password/2FA flows use the credential procedure below.

Use the [Auth session/abuse contract](../../../../docs/runa/handbook/modules/auth/sessions.md)
for tracked proof/deadline validation, own/admin revocation and standalone abuse
windows. Ordinary polling is not user activity; a heartbeat never moves the captured
absolute deadline. Remembered cookies cannot recreate revoked sessions. Technical
cleanup preserves Audit history. Do not add a native recaller or permanent account
lock from attacker-controlled counters.

Use the [Auth credential security contract](../../../../docs/runa/handbook/modules/auth/security.md)
for password policy/recovery, mandatory factors, trusted devices and AuthStepUp.
Consume server-chosen session/action-bound step-up inside the business transaction;
trust or recent login never substitutes for required fresh proof. Never expose
factor secrets, password history or OTPs through module APIs, logs or Audit.

Use the [private Auth profile/address contract](../../../../docs/runa/handbook/modules/auth/profile.md)
for versioned own/admin changes, recipient-bound verification, notification-only
secondary emails, avatar ownership/cleanup and safe own-history summaries. Other
modules consume guarded public contracts; never expand security delivery to
secondary recipients or expose general Audit detail for self-service history.

Use the [Auth preference/delivery contract](../../../../docs/runa/handbook/modules/auth/preferences.md) for source-declared account preferences, optimistic revisions, explicit safe guest adoption and email-only routing. Never store credentials/drafts in preferences, infer legacy ownership, bypass mandatory primary recipients or cache recipient decisions across jobs. Security mail uses reference-only managed work and revalidates state before its persisted single SMTP attempt; recovery retains direct mail.

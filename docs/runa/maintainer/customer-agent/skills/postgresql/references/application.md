# Application postgresql guidance

Read docs/runa/handbook/architecture/database.md before changes. PostgreSQL only; public holds no application tables, shared holds shared technical/application objects. Use bigint identity keys, unique non-null UUIDv7 public_id for exposed durable entities, bigint internal FKs with RESTRICT/RESTRICT, UTC moments, timestamps and normally soft deletion. Prefer simple NOT NULL/CHECK/UNIQUE/FK constraints, exact numeric money and query-driven indexes. No default ENUMs, views, procedures/functions or business triggers. Optimistic concurrency is the business edit default.

Native application migrations/wipes preserve platform objects. App modules use ModuleIdentity/ModuleSchema/ModuleModel with owned app_<code> schema and ModuleStorage; never foreign-module SQL/FKs/models/raw paths. External UUID references require public lookup plus an explicit concurrent-deletion consistency contract. Migrations that may have run in production are immutable. Identified application dev/test resets require actual ownership inspection and shown scope under an active app roadmap; no automatic reopen reset, CASCADE, shared cache deletion or assumption that platform objects belong to the app. Use real query plans only where safe; measure before indexing/partitioning/pooling. Source rollback never proves data recovery.

Durable dictionaries use code/name_pl/name_en/description_pl/description_en; DictionaryEntry explicitly projects existing localized source descriptors. Required Audit and owner mutations share the exact Connection/transaction. Follow docs/runa/handbook/architecture/module-foundations.md for authority lock order; it grants no foreign SQL/model/FK access.

Meaningful business/security mutations require Audit history. Use public Auditable Contracts with approved fields inside the default PostgreSQL transaction. Bulk SQL, relationship/pivot changes and aggregate operations require explicit manual Audit events in that same transaction; scoped automatic suppression never disables them. Follow docs/runa/handbook/modules/audit/README.md for guarded ModuleApis, redaction, rollback/refusal boundaries and permission-protected bounded reads. Never invoke the protected bootstrap/settings writer or assume arbitrary Eloquent writes are captured.

Auth normalized identifier claims arbitrate ownership in PostgreSQL. Default deletion reserves identifiers; explicit reuse never transfers history. Restore reacquires claims atomically and cannot steal a newer account’s identifier. Keep lifecycle predicates independent. Follow the public Auth identity/lifecycle contract in `docs/runa/handbook/modules/auth/README.md`; cookie/session/CSRF entry transport, registration and invitation/verification are delivered under `docs/runa/handbook/modules/auth/transport.md`; password/2FA use the credential guide below. Do not add duplicate Fortify routes or send credentials/tokens through module APIs, events, metadata or queue payloads.

Use `docs/runa/handbook/modules/auth/sessions.md` for tracked session/deadline checks,
own/admin revocation, real-activity heartbeat and bounded account/source login locks.
Polling never renews activity; remember persistence cannot restore a revoked session
or move its captured absolute deadline. Technical cleanup preserves Audit history.
Do not add a recaller or attacker-triggered permanent account lock. Consume delivered
public contracts; protected platform source remains read-only.

Use `docs/runa/handbook/modules/auth/security.md` for shared password policy/recovery,
mandatory factors, trusted devices and AuthStepUp. Keep server-chosen step-up and
its every-action consumption inside the business transaction. Trust/recent login
never substitutes for required fresh proof; keep credentials/OTP/history private.

Use `docs/runa/handbook/modules/auth/profile.md` for versioned private profile and
address workflows, notification-only verified secondary emails, authenticated avatar
ownership/cleanup and safe own-history summaries. Consume guarded public contracts;
never route core security mail to secondaries or grant general Audit read access
for an own-account history feature. Protected platform implementation remains read-only.

Use docs/runa/handbook/modules/auth/preferences.md for the delivered AuthPreferences/
AuthDelivery contracts. Declare exact source-owned preference schemas and notification
categories, use optimistic revisions, and keep account caches/requests isolated on
logout or switch. Legacy guest adoption requires explicit ownership confirmation;
server values win and reset never resurrects legacy state. Do not put credentials,
drafts or permission state in preferences, disable mandatory primary security mail,
cache recipients across jobs, or import Auth private SQL/models. Reference-only
managed security mail rechecks state and uses a persisted single SMTP attempt;
critical recovery retains direct mail. Application code consumes these contracts
without requiring conversion into an app module; protected Runa remains read-only.

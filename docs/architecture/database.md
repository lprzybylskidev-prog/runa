# Database Architecture

## Database engine

PostgreSQL is the only supported application database.

PostgreSQL-specific features are acceptable when they materially improve correctness or performance and remain understandable. Portability to MySQL/MSSQL is not a goal.

## Schema ownership

Each application module owns a short, stable English `snake_case` PostgreSQL schema, for example:

- `auth`
- `hr`
- `files`
- `work`

Do not add a redundant `module_` prefix.

The `public` schema must not contain Runa application tables.

Shared/global technical or application tables use the `shared` schema.

Laravel technical tables such as migrations, jobs, batches, failed jobs, and cache tables belong in `shared`.

Password reset data belongs with authentication once that module exists.

Models use schema-qualified `$table` values. Migrations create/use explicit schemas. Do not rely on a magical `search_path`.

Cross-schema foreign keys are allowed where the relation is real.

## Identifiers

Internal primary key:
- PostgreSQL bigint identity.

Durable domain/module entities exposed outside the backend also have:
- `public_id` UUIDv7,
- unique,
- non-null.

Use `public_id` in:
- routes,
- API contracts,
- frontend state,
- events,
- external integrations.

Use bigint IDs for internal foreign keys.

Do not add `public_id` mechanically to:
- technical tables,
- pivot tables,
- cache tables,
- queue tables,
- other records with no external identity need.

## Foreign keys

Every foreign key uses:
- `ON DELETE RESTRICT`
- `ON UPDATE RESTRICT`

Do not use CASCADE or SET NULL as convenient alternatives. Ordinary business deletion is soft deletion. A future audited physical-deletion/anonymization mechanism is intentionally deferred.

A foreign key does not automatically imply an index in PostgreSQL. Add indexes from real join/filter/update/delete access patterns, not ritual.

## Business rows

Business entities normally have:
- `created_at`,
- `updated_at`,
- `deleted_at` for soft deletion.

Physical deletion is not ordinary CRUD behavior for business entities.

Technical and pivot tables only receive timestamps when they are useful.

Store moments in UTC.

Use `timestamptz` for real moments in time.

Use `timestamp without time zone` only for a value whose semantics genuinely represent a local wall-clock value independent of timezone.

Date-only values remain dates.

## Types

Prefer PostgreSQL `text` instead of arbitrary `varchar(255)` unless a real business/data length constraint exists.

Use boolean for boolean values.

For exact decimal values:
- use integer smallest units when the domain fits,
- otherwise use PostgreSQL `numeric`,
- never use binary floating point for money or other exact financial values.

Avoid quoted/case-sensitive identifiers.

Avoid PostgreSQL ENUM as the default. Prefer:
- PHP native enum when useful,
- `varchar`/`text`,
- optional CHECK for a stable set,
- dictionary/reference tables when the set is dynamic.

## Integrity

Use the database for simple durable invariants:
- NOT NULL,
- CHECK,
- UNIQUE,
- foreign keys.

Use the application for business workflows and state transitions.

A CHECK expression accepting NULL does not replace NOT NULL.

Understand PostgreSQL unique NULL semantics. If a use case genuinely requires NULL values to compare as equal, consider `UNIQUE NULLS NOT DISTINCT` on a supported PostgreSQL version.

For active soft-deleted rows, prefer partial uniqueness when the rule is "unique among non-deleted rows":

`WHERE deleted_at IS NULL`

Do not encode business logic in stored procedures/functions/views/triggers.

Runa does not use:
- database views,
- stored procedures,
- stored functions.

Triggers are not a business-logic mechanism. A future low-level technical invariant would require an explicit project decision before introducing a trigger.

## Query and performance guidance

Indexes are justified by concrete access paths; do not mechanically index every FK or treat every sequential scan as a defect. Normalize first and avoid speculative database infrastructure. N+1 in normal paths is a quality defect.

Load `.agents/skills/postgresql/references/schema-indexes.md` for types, constraints, index/operator selection and query design. Load `.agents/skills/postgresql/references/operations-performance.md` for plans, statistics, backfills and live migration safety. These references own detailed PostgreSQL engineering guidance rather than duplicating it here.

Before a major module reaches production, review its schema/query paths and migration safety. Full release-scale performance requirements are in `docs/release/1.0-readiness.md`.

## Concurrency

Business edit forms use optimistic concurrency by default.

Detect stale data and reject/resolve the conflict explicitly. Do not silently overwrite a newer edit.

The concrete token may be:
- a dedicated version number,
- `updated_at`,
- another explicit concurrency token.

Choose per model/use case.

Last-write-wins is allowed only when explicitly safe.

Do not add hard record-edit locks by default. Multiple users may open a record.

Pessimistic locks such as `FOR UPDATE` are deliberate tools for concrete atomic behavior, not CRUD defaults.

Advisory locks are reserved for genuinely global coordination requirements.

Transactions should be short.

Do not perform slow external calls or long file work in a transaction.

Keep update/lock order stable where possible to reduce deadlocks.

Retry only known transient deadlock/serialization conditions, and only when the operation is safe/idempotent. Retry must not hide a flawed transaction design.

## Migration policy

The repository tracks whether production has ever been deployed in `docs/project/state.yml`.

Before first production deployment:
- committed migrations may be edited/cleaned while the database model is still pre-production,
- prefer one migration per database object/table where practical,
- define logical PostgreSQL column order in the original migration rather than adding meaningless MySQL-style `after()` concepts.

After `production.ever_deployed` becomes true:
- never edit a committed migration that may have run in production,
- all production schema changes use new migrations,
- if a migration is still uncommitted, edit it rather than adding a fix migration.

If a production-local marker says deployment happened but the versioned repository state still says false, stop migration work and reconcile the state.

Large-table migrations require an explicit risk assessment:
- lock duration,
- table rewrites,
- backfill cost,
- production traffic impact.

For risky live changes, use expand/contract only when needed:
1. add compatible new shape,
2. deploy code that understands transition state,
3. backfill safely,
4. switch reads/writes,
5. remove old shape in a later safe step.

Large backfills should be batched when one giant update would create unacceptable locks/WAL/runtime impact.

For large production index creation, consider PostgreSQL concurrent index creation when blocking normal writes would be unacceptable.

For expensive constraint validation on existing data, consider PostgreSQL staged validation such as `NOT VALID` followed by validation when appropriate.

Do not invent PostgreSQL syntax such as unsupported `ADD CONSTRAINT IF NOT EXISTS`; verify actual syntax for the installed version.

Prefer Laravel Schema Builder where it expresses the intended PostgreSQL semantics correctly.

Use raw PostgreSQL SQL in migrations when a PostgreSQL-specific feature cannot be expressed safely/clearly through Schema Builder. The reason and semantics must be obvious so a future refactor does not accidentally weaken the constraint/index.

Do not write a fake reversible `down()` when data loss or irreversible transformation makes rollback unsafe. Document the real rollback limitation and rely on the deployment/rollback plan.

## Extensions

Do not install PostgreSQL extensions preemptively.

Examples that may be justified later:
- `pg_trgm`,
- `btree_gist`,
- `citext`,
- `pg_stat_statements`.

Every installed extension is a runtime dependency and must be tracked in the maintenance registry and present consistently where required.

Do not treat `citext` as the automatic solution for all case-insensitive behavior. Decide field semantics first.

Collation/locale-sensitive sorting/uniqueness must be deliberate when business correctness depends on language, case, or accents.

PostgreSQL full-text/trigram search is introduced only for a concrete search need and after measuring the existing approach.

`pg_stat_statements` is a useful future diagnostic option, not a baseline requirement.

## Connection management

Do not add PgBouncer or another pooler until deployment concurrency/connection pressure demonstrates the need.

Do not respond to connection exhaustion by blindly increasing `max_connections`. Diagnose process count, pooling behavior, transactions, and workload first.

## Deliberate non-features

The database baseline does not include:
- Row Level Security as a second authorization system,
- partitioning before a proven need,
- replication/sharding architecture,
- generic trigger-based auditing,
- stored application logic.

## Identity implementation check

Verify the actual PostgreSQL DDL generated by the installed Laravel version. A helper named `id()` is not proof of `GENERATED ... AS IDENTITY`; preserve the agreed bigint identity semantics explicitly if the helper emits a serial column.

Partial uniqueness among non-deleted records is the normal business convention, with an explicit documented exception when a particular field must remain globally unique across deleted records. `public_id` itself remains globally unique and non-null.

When introducing a table, consider expected volume, relationships, filtering, sorting, uniqueness and soft-delete access paths without adding speculative indexes.

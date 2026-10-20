# Seeding Strategy

Runa has three seed categories with distinct intent.

## 1. Production seeders

Production seeders contain mandatory system/reference data.

They may run in:

- production,
- development,
- testing.

They are categorically idempotent.

### Update rules

System-managed records may be upserted/updated by the seeder.

User-managed records may only have required initial existence ensured. Production seeders must not overwrite user-managed values on rerun.

Deletion is prohibited by default. Destructive reference-data change belongs in an explicit roadmap/migration/data operation.

Do not create a separate "seeder already ran" registry. Idempotence is the contract.

A one-time non-idempotent data transformation is a data migration/dedicated operation, not a production seeder.

## 2. Development seeders

Development seeders run only in development/testing.

They create a persistent, incremental demo/development corpus.

Every new feature/module should extend this corpus with a small set covering important:

- cases,
- types,
- states,
- edge conditions.

The goal is a living functional gallery, not millions of meaningless random rows.

When a roadmap changes a feature/module, its final gate should confirm the relevant development/demo corpus still covers the important new states/cases.

### Counts

Do not use one global scale multiplier.

Each seeder has independent count options for its inserted element groups through development/testing config/env. For example `DEV_SEED_USERS_COUNT`, `DEV_SEED_USERS_RANDOM` and `DEV_SEED_USERS_SEED` are read through `config(...)` and documented in dev/testing examples. These are not code-only constants.

Never expose those tuning variables in production templates.

### Determinism

Deterministic by default.

Faker and every dictionary/relationship choice use controlled PRNG behavior, stable input ordering and independent per-seeder generators. Changing one seeder must not shift every later dataset. Determinism covers meaningful scenario data; generated UUIDs/timestamps need not be byte-identical unless canonical fixtures require it.

Each development seeder has its own random mode and seed:

- `random: false` -> stable deterministic dataset,
- `random: true` -> intentionally non-deterministic extra dataset.

Canonical demo records needed by E2E/demo flows use stable identities/public IDs where appropriate.

Bulk extra records use normal UUIDv7 public IDs.

### Demo credentials

Predictable development/test login accounts are allowed.

Passwords come from development/test environment configuration, not hardcoded source.

Production never contains default-password accounts.

## 3. Occasional/manual seeders

Occasional seeders represent explicit scenarios and are never part of automatic `migrate:fresh --seed`.

Run them manually through the project seed command.

## Semantic responsibility

One seeder owns one semantic responsibility, not necessarily one table.

Example:

- a future permission-system seeder may create permissions, roles, and assignments if those records form one system responsibility.

Do not create generic `InitialDataSeeder` dumping grounds.

## Factories

Factories generate records/states.

Seeders compose meaningful scenarios.

Use named factory states for important domain states.

## Dependency graph

Seeders declare explicit dependencies, such as `dependsOn`.

The runner uses hybrid discovery:

- fixed namespaces/registration contract/attribute/metadata,
- deterministic ordering,
- topological dependency sort,
- cycle detection,
- missing-dependency detection.

Production seeders execute before development seeders.

Occasional seeders are outside the automatic graph except for dependencies explicitly required by the requested occasional scenario.

## Commands

Canonical interface:

- `php artisan runa:seed`
- `php artisan runa:seed:production`
- `php artisan runa:seed:development`
- `php artisan runa:seed:occasional <Seeder>`
- `php artisan runa:seed:plan`

Expected behavior:

`php artisan migrate:fresh --seed`

- production environment: production seeders only,
- development/testing: production + development seeders.

## Deploy

Deployment order includes:

1. migrations,
2. production seeders,
3. optimization/cache work,
4. service restart/update,
5. health checks.

Production seeder failure is a deployment failure.

Production mode rejects explicit development seeding as well as excluding it from the default graph. Category dispatch for `migrate:fresh --seed` does not authorize a destructive production reset; normal deployment uses safe migrations followed by production seeders.

## Implemented foundation

`DatabaseSeeder` delegates Laravel's normal `db:seed`/`migrate:fresh --seed` entrypoint to the same validated graph as `php artisan runa:seed`. The existing destructive test-database guard still governs reset commands; seeding does not weaken it. The initial graph is deliberately empty: technical queue tables need no reference rows, and there are no business entities or demo accounts yet.

Extend `Runa\Support\Seeding\ManagedSeeder`, implement `category()` and `seed(SeedContext)`, and declare `dependsOn()` as class names. Discovery combines immediate PHP files under `src/runa/database/seeders/{Production,Development,Occasional}` (Runa\Database\Seeders) and `src/app/database/seeders/{Production,Development,Occasional}` (Database\Seeders), with explicit platform/application seeding config registrations. Registration supports classes outside those fixed directories; dependencies must themselves be registered/discovered. Names are sorted deterministically, then topologically ordered. The entire graph is checked for missing dependencies, cycles and category violations before any seed is executed. Production may depend only on production; development cannot depend on occasional scenarios. The occasional command accepts a unique short name or full registered class name and executes only its dependency closure. Production rejects any closure containing development seeders.

Development options live under `seeding.development.<ClassBasename>`: boolean `random` (default false), integer `seed` (default 1), and a `counts` map of independent nonnegative integer group sizes. `SeedContext` supplies an independent native PHP random generator and validated `count(group, default)`; it never changes global PRNG state. Production ignores development options. The first real demo seeder must add its own typed config/env tuning and dev/test template documentation; there are no unused fake tuning variables in this foundation. Sort source dictionaries/relationship candidates before sampling. If Faker is needed later, follow dependency policy and seed it independently rather than using uncontrolled global state.

Production seeders must still enforce idempotent upserts and preserve user-managed values; the framework does not infer ownership or suppress reruns. Each future populated production seeder needs a meaningful idempotence test. `./bin/artisan runa:doctor:seeding` covers graph ordering, categories, missing/cyclic dependencies, direct development rejection, Laravel/CLI dispatch, independent determinism and the empty repeatable baseline.

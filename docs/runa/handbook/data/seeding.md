# Seeding Strategy

Framework module seed source lives inside its owner root at `database/seeders/`. `ModuleDataPaths::seeders()` validates and returns sorted regular PHP files without executing them. Loose module seeders are never automatically loaded. Explicit UpgradeHandler initialization can invoke owned required seed logic through the delivered ModuleUpdates contract; native application/platform seed commands keep their existing owner boundaries. Runa updates select only supplied Runa modules, while app-module installation is explicitly wired into customer deployment. Module seeders must not read foreign-module tables or write outside their claimed schema. See [module data boundaries](../architecture/module-framework.md#delivered-postgresql-boundaries).

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

Application seeders remain idempotent and have no run-once registry. Explicit platform data preparation journals required seed steps transactionally for safe resume; this does not replace seeder idempotence.

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

Passwords are supplied privately through the supported initial setup prompt, or application-owned development/test configuration; never hardcode distributed credentials. Supplied Core fixtures start with no password and preserve an existing password on every repeat.

Production never contains default-password accounts.

## 3. Occasional/manual seeders

Occasional seeders represent explicit scenarios and are never part of automatic `migrate:fresh --seed`.

Run them manually through the project seed command.

## Semantic responsibility

One seeder owns one semantic responsibility, not necessarily one table.

Example:

- a module-owned development contributor may prepare related permissions, roles and assignments as one system responsibility through public owner contracts; platform dictionaries/system roles remain required module initialization.

Do not create generic `InitialDataSeeder` dumping grounds.

## Factories

Factories generate records/states.

Seeders compose meaningful scenarios.

Use named factory states for important domain states.

## Dependency graph

Seeders declare explicit dependencies, such as `dependsOn`.

The application runner uses explicit `seeding.register` entries only:

- no folder, namespace or attribute autodiscovery,
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

1. application migrations,
2. application `DatabaseSeeder` (production graph by default),
3. optimization/cache work,
4. service restart/update,
5. health checks.

Production seeder failure is a deployment failure.

Production mode rejects explicit development seeding as well as excluding it from the default graph. Category dispatch for `migrate:fresh --seed` does not authorize a destructive production reset; normal deployment uses safe migrations followed by production seeders.

## Implemented foundation

The application `DatabaseSeeder` delegates Laravel's normal `db:seed`/`migrate:fresh --seed` entrypoint to the same validated graph as `php artisan runa:seed`. The existing destructive test-database guard still governs reset commands; seeding does not weaken it. The application registration starts empty. Supplied Core development scenarios use the separate explicit contributor selection below; technical production initialization never creates demo accounts.

Extend `Runa\Support\Seeding\ManagedSeeder`, implement `category()` and `seed(SeedContext)`, and declare `dependsOn()` as class names. Register each application class explicitly in `src/app/config/seeding.php` under `register`; dependencies must also be registered. Classes must reside within `src/app` and extend `ManagedSeeder`; platform seeders cannot enter this graph. A loose file under any category folder does not execute automatically. The application `DatabaseSeeder` may also explicitly call ordinary application seeders using Laravel's standard API. Names are sorted deterministically, then topologically ordered. The entire graph is checked for missing dependencies, cycles and category violations before any seed is executed. Production may depend only on production; development cannot depend on occasional scenarios. The occasional command accepts a unique short name or full registered class name and executes only its dependency closure. Production rejects any closure containing development seeders.

Development options live under `seeding.development.<ClassBasename>`: boolean `random` (default false), integer `seed` (default 1), and a `counts` map of independent nonnegative integer group sizes. `SeedContext` supplies an independent native PHP random generator and validated `count(group, default)`; it never changes global PRNG state. Production ignores development options. The first real demo seeder must add its own typed config/env tuning and dev/test template documentation; there are no unused fake tuning variables in this foundation. Sort source dictionaries/relationship candidates before sampling. If Faker is needed later, follow dependency policy and seed it independently rather than using uncontrolled global state.

Production seeders must still enforce idempotent upserts and preserve user-managed values; the framework does not infer ownership or suppress reruns. Each future populated production seeder needs a meaningful idempotence test. `php artisan runa:doctor:seeding` selects the application seeding tests. Add meaningful application contracts for graph ordering, categories, idempotence, dispatch and deterministic fixture behavior when introducing seeders; private platform regressions are not a customer dependency.

## Platform ownership

Explicit first installation and customer runa:dev:initialize execute the narrow forward platform primitive and that required seed root before application data preparation. Owner update uses the same platform primitive; native application commands cannot select it. Its `shared.platform_seed_runs` record and seed data share one transaction per exact release commit/root. Successful retries skip the completed step; exceptions roll back the record and its data. Keep required seed effects transactional; external/nontransactional side effects need a separately designed recovery operation. Manual owner seed execution deliberately has no run-once record. Ordinary startup/reopen does neither owner's migrations or seeding. Customer deployment runs forward Runa migrations and its required idempotent seed root, then application migrate and db:seed; neither owner discovers loose files. See [database ownership](../architecture/database.md#platformapplication-source-ownership).

## Explicit module development contributors

`runa:seed`, native `db:seed`, application `DatabaseSeeder` and production seeding
retain the application-only graph. Opt into the additional supplied module corpus
explicitly, after supported forward initialization/update has installed the enabled
Core modules at their exact current source versions:

```bash
php artisan runa:seed:plan --modules
php artisan runa:seed:development --modules
php artisan runa:seed:accounts
php artisan runa:seed:accounts administrator --setup
```

The final command needs an interactive container terminal and reads the initial
password twice with hidden input. Current Auth password rules apply. It never
accepts credentials in argv or prints them, and refuses to replace any existing
password. Normal password change/reset and trusted operator recovery remain the
ways to change an established credential. The local administrator login/email is
listed by `runa:seed:accounts`; its initial identity uses `dev.administrator` and
`dev.administrator@example.test`. No command output contains a setup token, OTP,
password hash or factor secret. The setup command creates no authenticated session.
Use normal sign-in and complete all current verification/factor requirements.

A first fixture initialization uses the real Auth singleton and Permissions system
role, atomically with Audit. An already initialized non-fixture administrator or a
colliding identifier refuses; the corpus never adopts an unrelated account or adds
a second first administrator. Use an isolated development installation if the
existing initialization belongs to another scenario. Do not reset an existing
database merely to obtain fixtures. Disabling operator recovery also disables this
first-administrator setup. Production initialization remains separate mandatory
reference data and contains no development accounts.

`DevelopmentContributor` declares `seeders()` and `dependsOn()` (module IDs).
`seeding.contributors` is an explicit module-ID/class map: supplied entries are
`runa.auth` and `runa.permissions`. A future module contributes its own
DevelopmentContributor and development ManagedSeeder classes under its owned
backend root; an application can extend the map in `src/app/config/seeding.php`
while retaining wanted supplied entries. Dependencies must also be listed.
No directory scanning, request-selected classes or owner test harness is used.
Application classes remain in `seeding.register`; never register protected module
classes there. Contributor classes/seeders must belong to the indicated module.
Contributor dependencies become seeder graph edges without importing foreign
private classes. Missing/cyclic contributor or seeder dependencies and category
violations refuse before the first seeder executes, including empty-contributor
cycles. `--modules` is available only on the development and plan commands.

Every public fixture entry and direct development ManagedSeeder run checks the
console, local/testing environment, acquisition receipt and configured production
environment. `APP_ENV=local`, `--force` and explicit CLI selection cannot override
an installation recorded as production. Writes require current enabled installed
source/data, no module update/settings fence, the default PostgreSQL transaction,
registry then shared authority locks and mandatory Audit. These fixed local fixture
contracts do not relax normal HTTP/module authorization, step-up or last-admin rules.
Cross-module contributions exchange safe public references through designated
DevelopmentAccounts/DevelopmentAccess contracts, never foreign SQL/models.

Auth and Permissions each own an initially empty `development_fixtures` provenance
table. Stable scenario keys bind existing UUIDv7 references in the same transaction
as creation/history. Reruns skip completed accounts/assignments/dictionaries, preserve
changed names, private passwords/factors and grants, and create no duplicate Audit.
They neither revive deleted records nor refresh elapsed schedules/sessions. Missing
or deleted required scenario subjects refuse rather than silently recreating them.
Each module's transaction rolls back its provenance and all business writes on Audit
failure; a completed earlier contributor remains valid if a later one fails. Repair
that failure and rerun. These ledgers are technical metadata, not an alternative
identity/grant store. Neither production updates nor normal reopen invoke them.

### Supplied scenarios and independent tuning

| Scenario                                | Initial purpose                                                     |
| --------------------------------------- | ------------------------------------------------------------------- |
| administrator                           | Real protected role; private initial setup; ordinary security steps |
| reader / direct                         | Separate ordinary role grant and direct permission                  |
| pending / unverified                    | Approval refusal and email verification flow                        |
| password_change / second_factor         | Forced password change and enrolled email factor                    |
| sessions                                | Real tracked session row with undistributed random browser material |
| inactive / locked / expired / scheduled | Independent lifecycle/time restrictions                             |
| extra_N                                 | Small incremental ordinary account corpus                           |

Permissions also supplies a custom reader role with `runa.audit.read`, a preset
containing that role plus direct `runa.permissions.introspection.read`, and empty
extra roles. It does not flatten role grants, copy system membership or provide an
Admin management UI. Accounts other than the administrator also support private
initial password setup by their listed scenario key. Email verification/OTP uses
normal local mail delivery (Mailpit); mandatory TOTP still requires real enrollment.
The `sessions` row is not a browser credential and never authenticates the operator.

Only dev/test templates carry the following independent config inputs. Counts bound
extra groups to 0–100; canonical scenarios remain present. Lowering counts does not
delete existing data. Random mode affects only new generated names; stable scenario
keys stay idempotent. Sort relationship candidates before any future sampling.

| Seeder config (fully qualified class under `seeding.development`)   | Inputs                                                                                                    |
| ------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------- |
| `Runa\Modules\Auth\Development\AuthDevelopmentSeeder`               | `DEV_SEED_AUTH_USERS_COUNT=3`, `DEV_SEED_AUTH_RANDOM=false`, `DEV_SEED_AUTH_SEED=17`                      |
| `Runa\Modules\Permissions\Development\PermissionsDevelopmentSeeder` | `DEV_SEED_PERMISSIONS_ROLES_COUNT=2`, `DEV_SEED_PERMISSIONS_RANDOM=false`, `DEV_SEED_PERMISSIONS_SEED=17` |

Each ManagedSeeder gets its own SeedContext generator; it never seeds global PRNG
state. Fully qualified config keys prevent module basename collisions; existing
application basename options remain compatible. Keep future knobs in local/testing
configuration and templates, with no demo variables in production templates.

### Manual Auth navigation scenario

Use [the account UI contract](../modules/auth/account-ui.md) and a development URL
served by Nginx. Prepare the module corpus and private administrator password above;
this is explicit local preparation, never an automatic reopen step.

1. With `public_welcome_enabled=true` (default), open `/` logged out. Follow sign-in,
   enter the discovered account and private password, and complete required security
   steps. Verify the authenticated entry uses ApplicationLayout.
2. Open the existing user dropdown, follow the profile link and inspect own account,
   preferences, sessions and security views. Use the same dropdown to sign out.
   Verify navigation returns through `/`, the welcome has logged-out links and a
   fresh request to authenticated application/profile content requires sign-in.
3. Read current Auth settings with `runa:modules:settings runa.auth`. Through its
   documented revision/generation-checked stdin write, set only
   `public_welcome_enabled=false`. Open `/` logged out: expect `/auth/login`.
   Repeat sign-in, security steps, application, dropdown profile and sign-out;
   return through `/` now reaches sign-in. An existing session entering `/` must
   resolve to the application or pending required steps without a redirect loop.
4. Restore the original setting through a fresh revision/generation read. Exercise
   the unverified, second-factor and blocked accounts separately; do not weaken
   production security policy to skip their expected restrictions. Review in Polish
   and English, light/dark and desktop/tablet/mobile during final frontend acceptance.

Before handing a frontend to an owner/customer for manual review, explicitly
prepare the actual development environment: forward-install/update mandatory Core
modules, publish current frontend/error assets, run the module development corpus
and privately initialize the administrator password. Ordinary reopen remains
nondestructive and does not run seeds. Core modules are mandatory/enabled after
normal initialization; an absent registry or stale assets is not a reason to ask
the reviewer to enable Core manually or memorize a login URL.

Verify the real served guest welcome, conditional registration, normal sign-in,
profile and sign-out before handing over the URL and privately supplied development
credentials. Keep passwords out of source, documentation, argv, logs and screenshots;
preserve established credentials on repeats. Local public registration may be
explicitly enabled through audited revision-checked settings for a requested
manual scenario; production defaults do not change. Read current readiness from
the running application, not from owner fixtures or an old verification result.
This targeted operational preparation does not constitute full integrated
browser/installation acceptance or the owner's visual acceptance.

The default authenticated application view is empty. Account self-service is named
Panel użytkownika (User panel) and is reached only through the user dropdown,
not the sidebar or a welcome card. The dropdown groups initials/name identity
above left-aligned icon/action rows with a divider and preserved keyboard focus.
ApplicationLayout omits its heading region when title, heading and actions are
absent. Session warnings/errors remain available; custom application content still
uses the public session-scoped slot.

# PostgreSQL Alpine locale and first-deployment assessment

Assessment date: 2026-10-17. Status: proposal awaiting user agreement and disposable candidate execution. Official candidate: `postgres:18.6-alpine3.24@sha256:77f585114c32fbca283dc835b0596f4e52b51b4c6662d7810b2f4084f60a1873`. Runtime Compose, entrypoint, gosu, existing volumes and database initialization settings are unchanged.

## Observed current state

Read-only queries against the actual development cluster returned:

| Database    | Provider   | LC_COLLATE / LC_CTYPE | Locale version |
| ----------- | ---------- | --------------------- | -------------- |
| `runa`      | libc (`c`) | `en_US.utf8`          | `2.36`         |
| `runa_test` | libc (`c`) | `en_US.utf8`          | `2.36`         |

The same cluster has ICU collations `pl-x-icu` and `pl-PL-x-icu`, recorded/actual version `153.120.42`. Development PHP reports ICU `72.1`. The actual SQL Polish-letter probe under explicit `pl-x-icu` returned `a,ą,c,ć,e,ę,l,ł,n,ń,o,ó,s,ś,z,ź,ż`; explicit `C` ordering returned `a,c,e,l,n,o,s,z,ó,ą,ć,ę,ł,ń,ś,ź,ż`. Native PHP Collator produced the Polish ordering too. This demonstrates why an encoding setting or `LANG` alone cannot establish Polish collation semantics. It does not establish equality between all libc/ICU versions or verify the Alpine server's runtime result.

Versioned `docs/project/state.yml` still records `production.ever_deployed: false`; observed deployments are local simulations. No real production dataset has been identified. This proposal is a first-install baseline, not a design for migrating a live production system. Preserve current development/simulation volumes until their disposition is explicitly agreed; no automatic destructive reset is authorized here.

## musl, locale and Polish behavior

Alpine uses musl rather than glibc. Its libc locale behavior must not be assumed equivalent to the current glibc `en_US.utf8` cluster. The [official PostgreSQL image documentation](https://github.com/docker-library/docs/blob/master/postgres/README.md) describes ICU support in Alpine variants since PostgreSQL 15. The [official candidate Dockerfile](https://github.com/docker-library/postgres/blob/master/18/alpine3.24/Dockerfile) builds PostgreSQL with ICU and includes full ICU locale data. These are available capabilities, not proof of Runa's selected initialization settings.

Recommended **new-cluster** initialization, subject to the user's separate decision:

```dotenv
POSTGRES_INITDB_ARGS=--encoding=UTF8 --locale=C.UTF-8 --locale-provider=icu --icu-locale=pl-PL
```

Use ICU for database ordering/casing with Polish locale `pl-PL`; keep UTF-8 encoding and deterministic, case/accent-sensitive default equality. Do not substitute libc's default locale for ICU or enable nondeterministic/accent-insensitive equality implicitly. For the fixture, `a != ą`, `A != a` and composed/decomposed spellings remain unequal with deterministic collation, while order is Polish. Case-insensitive search or locale-specific user preferences remain future feature decisions, not a reason to alter equality globally now.

The [PostgreSQL locale](https://www.postgresql.org/docs/18/locale.html) and [collation documentation](https://www.postgresql.org/docs/18/collation.html) distinguish locale providers, deterministic comparison and encoding. Changing the UI language does not itself change the stored database comparison contract.

## Index and version implications

Collation participates in text ordering, range comparisons, sorting, uniqueness and B-tree index behavior. Changing a provider/library/version can invalidate assumptions of collation-dependent indexes. [PostgreSQL's collation-version guidance](https://www.postgresql.org/docs/18/sql-altercollation.html) requires rebuilding affected objects before refreshing recorded version metadata when definitions change. Refreshing a version marker alone does not repair an index.

Do not attach the Alpine candidate to the current Bookworm cluster, change `POSTGRES_INITDB_ARGS` expecting it to alter an initialized volume, or silently reinterpret an existing database. Official initialization arguments apply only to a new cluster. Since this is before real production, choose the agreed provider/locale once, initialize a clean production cluster at first install and validate it. No complex existing-production-volume migration is proposed.

For development/test, changing the baseline also needs deliberate handling of their existing local data; preserve it and decide whether a simple logical export/import or explicitly authorized regeneration is appropriate. Do not build a generalized migration platform for this initial technical dataset.

## Development, test and production parity

If the candidate is approved, use the same exact PostgreSQL version, Alpine image digest, ICU data/library version, initialization flags, UTF-8 encoding and deterministic comparison semantics for development, isolated tests and production. A shared PostgreSQL major alone is insufficient. Doctor/acceptance should compare provider, database locale, recorded/actual collation versions and the Polish order/equality/index fixture in each environment. Any ICU/library/image update is a deliberate maintenance change with repeated checks.

PHP's development runtime remains Debian for the existing browser toolchain; the production PHP candidate is Alpine. These PHP runtimes need language/casing/transliteration checks, but PHP ICU equality must not become a substitute for authoritative database equality. Do not claim full cross-version collation equivalence from one alphabet fixture; inspect differing ICU versions and pin the database environments consistently before accepting the baseline.

## Disposable candidate verification

Run on the Docker host:

```bash
python3 tools/testing/postgres-alpine.py
```

The probe uses the official digest, unchanged official entrypoint and gosu. It creates a uniquely named temporary container, no published ports, network `none`, tmpfs database storage, private random credentials and no persistent volume/bind mounts. It removes that exact temporary container on normal failure/success/interruption. It never connects the candidate to development, testing or simulation volumes and never invokes Runa migrations.

It waits for TCP readiness (the final server, not the initialization-only Unix-socket server), initializes an ICU `pl-PL` UTF-8 cluster, tests provider/version metadata, Polish order/casing, deterministic equality, indexed range/order versus sequential results, and verifies an index plan. SQL uses a temporary table in a rolled-back transaction; no domain model is introduced.

Host evidence supplied on 2026-10-17 confirms the exact official candidate passed this probe: PostgreSQL 18.6 Linux/musl, UTF8, ICU provider `i`, locale `pl-PL`, recorded/actual version `153.136.48`. Polish casing/order/ranges, deterministic equality and index/sequential agreement passed. The temporary container was removed. The devcontainer itself has no Docker CLI/socket. Offline parser/assertion and isolation contracts are separate from actual candidate execution. Its host output is required before runtime acceptance. PostgreSQL gosu findings are assessed separately in [the security report](../security/gosu-assessment.md); no suppression is installed by this test.

## Approved direction and existing-volume audit (2026-10-17)

The user approved official PostgreSQL 18.6 Alpine 3.24 with unchanged upstream entrypoint/gosu, a narrowly bound not-affected disposition for the exact 25 assessed findings, and UTF-8/ICU/pl-PL parity in development, testing and production. Any image/gosu/binary drift or new finding requires reassessment. Existing-volume deletion/modification remains explicitly prohibited without separate permission. No suppression or runtime switch has been implemented at this audit stage.

Read-only database catalog inspection found the development database `runa`, base test database `runa_test`, workers `runa_test_test_1` through `_4` and the additional test database `runa_test_test_4_test_4`. Every database uses libc (`c`), `en_US.utf8` for both collation and character classification, version `2.36`; the administrative `postgres` database has the same locale. These databases share the development Compose PostgreSQL service/`postgres-data` volume; they are not independent test volumes. The additional nested worker database is observed test residue, not permission to delete it.

Actual table/row inspection of `runa` and each test database found only `shared.migrations` (one migration), `shared.jobs` (zero rows) and `shared.failed_jobs` (zero rows). No application business tables or queued/failed job data were found. No payloads/secrets were read or printed. This database audit cannot inspect the host's physical Docker volume names or identify unrelated volumes without Docker-host evidence. No existing volume has been changed or deleted.

Proposed safe reinitialization for separate user approval:

1. Record actual Compose project, container mounts and old volume identity on the host; reject unexpected context. Preserve old volumes and private settings. Stop development processes before switching their database dependency.
2. Select a new dedicated named database volume for the approved Alpine/ICU baseline, leaving the existing Bookworm volume untouched. Use the approved digest, original upstream entrypoint/gosu and explicit UTF8/ICU/pl-PL initdb settings. Recreate the development PostgreSQL service and its dependents only after explicit permission; do not attach the Alpine server to the old cluster.
3. Initialize technical roles/schemas/databases using the existing Runa initialization script, migrate the new development/test databases and regenerate parallel test databases through guarded test tooling. No business dataset needs migration according to the inspected state.
4. Verify actual provider/locale/recorded-and-actual version, Polish order/equality/index fixtures, all test workers and runtime health. Keep the old volume until the user separately authorizes cleanup.
5. Apply the same baseline to first real production initialization. Existing local production simulation remains separate; do not point its old Bookworm cluster at Alpine. Agree a new isolated simulation volume before repeating deploy/backup/restore/security acceptance.

This proposal avoids destroying even the apparently disposable technical data and provides a return path. If the user identifies additional data requiring preservation, stop and agree a logical export/restore plan before switching. No existing live production migration framework is needed because real production has not been deployed.

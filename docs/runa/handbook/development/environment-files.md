# Environment file responsibilities

Keep private files out of Git, logs, reports and knowledge. Generated secret files
are private owned regular files (mode 600). Templates contain names/defaults only.
Use a dedicated production checkout; a development APP_ENV does not override its
recorded installation role. Do not copy another workstation's acquisition receipt.

| Private input      | Creator / template                                                   | Readers and responsibility                                                                          |
| ------------------ | -------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| .env               | Development prepare/setup; .env.dev.example                          | Laravel local runtime, application DB credentials, key and mail settings                            |
| .env.testing       | Development prepare/setup; .env.test.example                         | Laravel/Pest/parallel testing, separate test role and DB, isolated mail/queue settings              |
| .env.dev.local     | Development profile/prepare                                          | Compose and resource override, UID/GID, worker counts, optional CPU/CUDA selection; no DB secrets   |
| .env.dev.database  | Development prepare; .env.dev.database.example documents keys        | Compose PostgreSQL initialization: distinct POSTGRES_PASSWORD, APP_DB_PASSWORD and TEST_DB_PASSWORD |
| .env.prod          | External production initialization; .env.prod.example documents keys | Production Laravel runtime application/key/DB/mail settings                                         |
| .env.prod.database | External production initialization; .env.prod.database.example       | Compose PostgreSQL administrator initialization and non-superuser application password              |
| .env.prod.compose  | External production initialization; .env.prod.compose.example        | Image revision, loopback listener, real/simulation and TLS selection                                |
| .env.prod.backup   | Externally/customer managed if present; no supplied template         | No current Runa runtime consumer; preserve for the external backup owner                            |

The six templates document the three Laravel environments, two DB initialization
roles and production Compose inputs. The private development profile is generated
by supported commands. `.env.test.example` intentionally generates Laravel's
`.env.testing`; `.env.dev.example` intentionally generates the default `.env`.
Renaming either requires consumer changes and brings no operational simplification.

## Credentials and repeat operation

Application DB_PASSWORD must agree with APP_DB_PASSWORD in its initialization
file; testing DB_PASSWORD must agree with TEST_DB_PASSWORD. The administrator
password remains distinct. PostgreSQL initialization reads these only when its
data directory is first created: editing a file never changes an existing role.
Development preparation diagnoses mismatches and unsafe secret-file permissions
without printing values or rotating roles. Production initialization/resume writes
only missing settings and verifies the existing pair. Repeat/reopen preserves
credentials, data and customer files; ordinary reopen performs no data preparation.

For an intentional password change, identify the actual checkout/project, role,
volume and all consumers first. Back up required settings outside Git. Coordinate
stopping/restarting consumers and change the existing PostgreSQL role through an
authorized private administrative session, then reconcile runtime/initialization
inputs for that same role. Avoid shell arguments/history/logs containing secrets.
Validate through supported configuration diagnostics and actual readiness before
restoring writers. Do not regenerate files, reset a volume or assume a template
edit rotates credentials. Uncertain ownership requires clarification.

Use `./bin/artisan runa:doctor:config` and `runa:doctor:templates` through the
supported transport. Template coverage does not prove an existing DB role accepts
a password; readiness/real application tests establish that separately. Review
image/recreate/reopen requirements when changing Compose inputs.

`.env.prod.backup` is not generated, consumed, migrated or deleted by the Runa
installer/updater. Existing preservation regression coverage is deliberate. Its
presence does not authorize changing the external backup schedule or destination.
The backup owner must establish provenance before cleanup; no runtime reference
is not evidence that a private file is disposable.

Dev/test templates additionally document independent `DEV_SEED_AUTH_*` and
`DEV_SEED_PERMISSIONS_*` count/random/seed options for the optional module corpus.
They contain no default account password and are absent from production templates.
Use the [private initial setup command](../data/seeding.md#explicit-module-development-contributors)
inside an interactive development container terminal; repeat/reopen preserves it.

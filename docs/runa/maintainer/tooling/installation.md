# First installation

First development acquisition uses the separately supplied distribution/bootstrap.py and a published Runa release. Production initialization uses distribution/initialize.py and a complete customer repository. Both invoke the acquired application's protocol-1 installation gateway in a separate process; external launchers remain excluded from customer source. Internal host installation services ship with application tooling and remain absent from production images. Native Linux/Python 3.11+, Git 2.39+, Docker Engine 26+ and Compose 2.35+ are required; application toolchains stay in containers.

```sh
python3 bootstrap.py --repository lprzybylskidev-prog/runa --tag <published-protocol-1-tag> \
  --destination /absolute/first-development --install later --non-interactive
python3 initialize.py --repository https://github.com/customer/application.git \
  --destination /absolute/production --target main --install later \
  --settings-file /private/production-settings.json
```

Use an actually published protocol-1 release; historical releases require their matching historical installer. Production initialization accepts a customer branch/tag/full SHA, verifies safe source paths/files and rejects owner-only tracked material and the Runa release remote. Origin/history remain the customer's; the local role is prepared independently of first-dev acquisition evidence. Default target is main; ambiguous branch/tag names require explicit refs. New destination publication never overwrites existing files. Keep the printed exact-commit gateway command for postponed install and explicit --resume. Teammates use [public local initialization](../../handbook/development/environment.md#customer-repository-and-local-initialization).

Development and production host checkouts contain the same complete versioned customer source. Common owner exclusions remain; there is no production-only sparse reduction. Production images/services still exclude development/AI tooling and never start Qdrant/embeddings/devcontainer. No customer runa:install alias or production platform updater exists.

`--profile=low|standard|high` is dev-only;
omitting it preserves the existing/default resource selection. `--simulation` is
prod-only and uses isolated resources. Without
`--non-interactive`, a terminal production installation can collect required
settings and read the SMTP password without echo. Automation fails rather than
prompting when settings are missing.

## Customer scaffold and development

The delivered `src/app` is the clean initial application scaffold: application
manifest (Runa, independently versioned 0.0.0), empty presentation configuration,
provider/routes/SPA entrypoints, dependency declarations, database/test extension
entrypoints and empty Actions/modules/config/docs/lang/database/frontend/test
extension roots. The welcome screen remains the platform UI consumer; no sample
business entity/module is installed. The reserved lowercase modules root adds no
module framework or registration.

First installation materializes missing empty extension directories and records
the scaffold once per installation mode. Existing files and manifest versions
are never replaced. After the marker exists, repeat does not recreate a directory
deliberately removed by the application developer. Missing delivered manifest or
linked extension paths fail instead of triggering an ambiguous repair.

Development delegates to the existing resource-profile/prepare/up mechanics,
including stable private database/test credentials, bind mounts, setup
fingerprints, locked dependencies, devcontainer and independent
knowledge initialization. Optional AI degradation does not make application
startup fail. It refuses to take over a development Compose project belonging to
another checkout. Ordinary reopen/start remains `runa:dev:up`; installer repeat
is a preservation check, not an upgrade or forced restart.

## Production settings

Use a dedicated clean production checkout without development `.env` files. Keep
the JSON settings outside tracked source, owned by the operator with mode 600 or
400, at most 64 KiB; symlinks, unknown fields, newline/NUL injection and unsafe
ports/URLs are rejected. Example shape (replace all operator inputs):

```json
{
  "app_url": "https://app.example.com",
  "http_port": 8081,
  "tls": {
    "mode": "builtin",
    "certificate": "/private/tls/fullchain.pem",
    "private_key": "/private/tls/key.pem",
    "https_port": 443,
    "bind": "0.0.0.0"
  },
  "smtp": {
    "host": "smtp.provider.example",
    "port": 587,
    "scheme": "smtp",
    "username": "provider-user",
    "password": "<provider-password>",
    "from": "sender@example.com",
    "recipient": "operator@example.com"
  }
}
```

Optional `administrator` settings contain `email`, `name` and optional `login`.
After the real application/TLS/SMTP checks succeed, the installer runs native
`runa:auth:initialize --invite --confirm` in the installed release. It commits the
real Permissions role and encrypted invitation work atomically and records only
safe admission in its existing journal. Complete repeats do nothing; a crash before
journal recording safely reuses the same recipient/admission, including completed
password setup. Omit the object to initialize later through the documented private
CLI handoff (also supported for development/ordinary customer clones). No password
or token belongs in settings, command output or transport logs. The installer does
not claim that queued invitation admission proves SMTP delivery; failed delivery
uses explicit Auth resend/operator recovery.

The installer generates distinct random admin/runtime database passwords and an application
key once, writes raw mode-600 env files and preserves them on retries. Dollar
signs remain literal. Existing unowned production settings/resources are refused;
the installer never adopts an unknown database or rotates secrets to make startup
appear successful.

Built-in TLS generates a standard Nginx overlay, mounts the supplied full chain
and key read-only, exposes HTTPS at the chosen address/port and retains loopback
HTTP for diagnostics. Nginx stays nonroot, with no new service, capability or
Docker socket. Certificate/key inputs must be regular absolute paths without
symlinks; the key must be private/owned and unencrypted. The pinned container PHP
runtime checks key matching and certificate validity (at least one day left).
Private TLS state stays under mode-700 `.runtime/production/tls`; individual
read-only files are readable by nonroot Nginx independently of host UID. No key
enters an image or Git. Certificate issuance, DNS and provider accounts remain
operator inputs, not an installer-provided certificate service.

To deliberately use an existing trusted TLS terminator, set `tls` to
`{"mode":"external"}`. Configure it to forward to the selected loopback HTTP
port first; installation then verifies the actual public HTTPS origin/readiness
with certificate/hostname checking. Production FastCGI treats the verified HTTPS
deployment as HTTPS without trusting arbitrary forwarded headers. Either mode
accepts optional `tls.ca_file` for an explicitly supplied private CA; certificate
verification is never disabled. URL/listener ports must agree in built-in mode.

SMTP always requires TLS: `smtp` normally uses STARTTLS, `smtps` uses implicit TLS.
Supply username/password together, or leave both empty only for an authorized
relay. `recipient` explicitly authorizes one installation verification email. The
native `runa:mail:test <recipient>` command validates configuration and
submits a fixed technical message without application data; provider exceptions
are redacted. Success proves provider acceptance, not final inbox placement;
check the recipient mailbox for downstream delivery/filtering.

Success requires actual HTTPS root/readiness and SMTP acceptance after the
existing exact-image/config/migration/seeding/health/isolation sequence. A failed
verification cannot complete the installation journal. Production still uses
only PHP-FPM/Nginx/PostgreSQL/queue/scheduler and two durable storage/database
volumes. Shared operational helpers remain host source, never application-image content; AI/RAG/development tooling remains excluded from runtime.

## Repeat, failure and recovery

Private `.runtime/production/install-dev.json` / `install-prod.json` records source,
mode, simulation, selected profile and started/failed/complete state. Production
settings are separately private in `install-settings.json`; never attach these
files to a public issue. `scaffold-<mode>.json` records initial scaffold creation.
These are first-install records. External development updates share the operation lock/pending path and archive their own exact-release completion journals; see [updates](updates.md).
No trusted source baseline is stored in the installation. Repository-reference comparison belongs to the external development-only owner updater.
Acquisition records the verified download digest; staged releases record their exact commit and operation ownership, without generating a trusted local file baseline.

After failure, keep source, settings, volumes and deployment markers. Correct the
reported prerequisite/provider failure and rerun the same command with `--resume`.
Retain supplied certificate/CA inputs until installation completes. Resume validates
and refreshes corrected certificate/key contents at their originally selected
paths. Explicit `--resume --settings-file=...` can correct SMTP inputs while
preserving infrastructure choices and generated application/database keys. Recovery checks
that pending first-install state, project/resources and target belong together;
it cannot turn a pending update into a new install. Platform migrations retain their own history; required platform seed data and its release/root completion record commit atomically, so failed steps can retry and successful steps do not duplicate on resume. No schema reversal or volume reset occurs.
Existing generated credentials remain stable. A completed repeated install exits
without touching application source, configuration, images, services or data.
For normal later source changes use the application lifecycle or platform
deployment/update procedure, rather than another first installation.

Offline contracts: `python3 tools/maintainer/test-python --scope=contracts --path=contracts/test_installation.py`; PHP acceptance:
`runa:test --filter=InstallationTest`. Native disposable acceptance:
`python3 -m tests.tools.runa.acceptance.installation_host` (requires the active development image
and YAML reader). It uses isolated projects/ports/fresh volumes and synthetic
TLS/SMTP, preserves active development and model caches, prints exact cleanup
scope, and keeps verification reports. Simulation acceptance is not a real
production deployment or live provider/domain acceptance.

## Database initialization boundary

Development installation prepares configuration and starts PostgreSQL, PHP and the one-shot dependency setup service. It explicitly waits for setup to exit successfully before invoking the external owner database bootstrap, then starts the remaining development services. Ordinary setup/startup/reopen never migrates or seeds.

The owner-only distribution/platform-database.php remains the explicit maintenance/reset/selected-seeder entrypoint and is excluded from customer delivery. Explicit first acquisition, customer clone preparation and production deploy instead use src/runa/database/prepare.php: narrow forward migrations and the fixed required seed root, with transactional exact-source completion in shared.platform_seed_runs. They then run application migrate and explicit DatabaseSeeder where the coordinated workflow owns both data scopes. Ordinary reopen performs neither. Development updater execution uses only the platform phases. No customer Artisan platform-maintenance/reset command is exposed. See [database boundaries](../../handbook/architecture/database.md#platformapplication-source-ownership).

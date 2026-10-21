# First installation

The owner approved a revised whole-project customer-repository workflow on 2026-10-21; implementation remains open in [RM-2026-005 points 10a–10c](../roadmaps/active/RM-2026-005-platform-installation-updates.md). The target is first-dev acquisition from Runa, teammate setup and production initialization from the customer repository, with identical versioned dev/prod source sets and different local env/runtime artifacts. The commands below describe the existing implementation, including its upstream production acquisition and sparse projection, until those points replace it.

Use the host terminal and the verified checkout produced by
[bootstrap](bootstrap.md). No host PHP, Composer, Node or pnpm is required.
Native Linux/Python 3.11+, Git 2.39+, Docker Engine 26+ and Compose 2.35+ with
daemon access are required. The target is an exact annotated release tag or full
lowercase commit SHA matching the checked-out source; branches are rejected.

Installation is initiated by the separately supplied `distribution/bootstrap.py`
(keep its adjacent distribution/installation.py helper outside the destination), with `--mode dev|prod` and
`--install now|later`. It verifies and projects source before handing off to a fixed
external owner helper. The postponed command is printed with the exact acquired commit.
Keep it for an explicit `--resume` after failure. Neither `runa:install` nor
`bin/install-prod` is installed as a customer entrypoint. No Artisan registry, including the owner checkout, registers installation. The external helper requires an explicitly acquired installation target.

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
volumes. Operational installation helpers are sparse-checkout inputs, never
application-image content; AI/RAG/development tooling remains excluded.

## Repeat, failure and recovery

Private `.runtime/production/install-dev.json` / `install-prod.json` records source,
mode, simulation, selected profile and started/failed/complete state. Production
settings are separately private in `install-settings.json`; never attach these
files to a public issue. `scaffold-<mode>.json` records initial scaffold creation.
These are first-install records; the full update journal remains later roadmap work.
No trusted source baseline is stored in the installation. Repository-reference comparison belongs to the external owner updater, which remains pending in RM-005.
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

Offline contracts: `python3 tools/maintainer/testing/installation.py`; PHP acceptance:
`runa:test --filter=InstallationTest`. Native disposable acceptance:
`python3 tools/maintainer/testing/installation-host.py` (requires the active development image
and YAML reader). It uses isolated projects/ports/fresh volumes and synthetic
TLS/SMTP, preserves active development and model caches, prints exact cleanup
scope, and keeps verification reports. Simulation acceptance is not a real
production deployment or live provider/domain acceptance.

## Database initialization boundary

Development installation prepares configuration and starts PostgreSQL, PHP and the one-shot dependency setup service. It explicitly waits for setup to exit successfully before invoking the external owner database bootstrap, then starts the remaining development services. Ordinary setup/startup/reopen never migrates or seeds.

`distribution/platform-database.php` stays with the owner. The installer passes its trusted source to prepared PHP via argument-vector execution; the helper is neither copied into customer source/images nor registered as Artisan commands. Installation runs only platform `migrate` and explicitly required platform `DatabaseSeeder`, with per-release atomic seed completion in `shared.platform_seed_runs`. Application migrations and application seeders are never part of this installation path. Production installation uses the same private callback in the operational sequence; ordinary customer deployment instead executes application `migrate` and `db:seed`. The later external updater will reuse these owner routines after its repository comparison. See [database boundaries and transition constraints](../../handbook/architecture/database.md#platformapplication-source-ownership).

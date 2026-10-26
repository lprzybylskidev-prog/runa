# RM-2026-011 runtime remediation — 2026-10-26

## Owner decision and scope

The owner explicitly requests correction of curl/libcurl CVE-2026-92392 and all
remaining PHP, Brotli and gosu findings, deletion of their temporary exceptions,
focused native security verification and immediate full verification after PASS.
This supersedes the earlier rollback/wait-only risk acceptance; those dated
assessments remain historical evidence. No new scanner exemption is authorized.

## Delivered correction

- PHP 8.4.27 in development/production/management restores the upstream PHP core
  security fixes. Base digests are pinned in docker/php/Dockerfile; production
  explicitly installs corrected curl/libcurl and Brotli package floors.
- docker/postgres/Dockerfile preserves PostgreSQL 18.6, ICU 78.1, the official
  entrypoint and data layout. It installs libcurl 8.23.0-r0 and Brotli 1.2.1-r0
  or newer and builds gosu 1.20 source revision
  29b939aa5bd4dc588af765b8248f6e39150d2565 with pinned Go 1.27.3.
  The source archive SHA256 is
  2b70da8a5480fafedc16bb1fc91b572f064d03df736cbd91a616af2f1b66ab51;
  upstream Go module checksums are verified. The runtime contains only the binary.
- PostgreSQL defaults to USER postgres (UID/GID 70). A disposable isolated named
  volume initialized PostgreSQL/ICU pl-PL without root; existing development
  PGDATA was already UID 70 and starts without ownership changes. Custom bind
  mounts need operator-prepared ownership. The entrypoint file remains identical.
- Production builds and verifies the complete exact-SHA PHP/Nginx/PostgreSQL set
  before starting the database or performing data steps. Partial sets fail closed;
  fixture cleanup includes the new PostgreSQL image tags.

## Exception retirement

Remove the owner runtime/gosu callbacks and policies, shared gosu disposition
module and policy, their dedicated tests/quality node, the obsolete documentation
contract, and scanner callback arguments. All scanner failures now remain failures
for owner and customer alike; raw reports and normal scanner/review/audit tests
remain. Preserve prior dated assessments and private raw evidence.

MT-2026-001 tracks eventual replacement of the downstream PostgreSQL packaging
with a verified corrected official image and removal of its dedicated build
machinery. This is packaging maintenance, not remaining vulnerability acceptance.
See [runtime security](../../handbook/security/postgres-gosu-policy.md) for the
current operating contract and removal inventory. MT-2026-002 tracks delivery of
the restored strict runtime in the pending platform release.

## Verification

Focused source contracts cover unfiltered scanner failure propagation and complete
immutable image sets. Native security execution exercises real fresh installation,
SMTP failure/resume, runtime health/isolation, actual selected images and locked
dependency audits. Full all-group verification remains required before release.
The preceding native crashes are not attributed to a proven PHP/Xdebug/hardware
cause by this security correction.

Final native security execution passes: repository, PHP/Nginx/PostgreSQL/Redis
images and locked dependency audits have no selected findings or dispositions.
Fresh initialization, SMTP rejection/resume, health/isolation and owned-resource
cleanup pass. Private report installation-host-3fccf1d0e3.json and its raw scanner
JSON/logs retain the evidence. The full all-group gate remains pending.

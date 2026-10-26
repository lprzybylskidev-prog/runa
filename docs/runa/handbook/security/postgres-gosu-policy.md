# PostgreSQL runtime security

Runa builds its PostgreSQL runtime from the pinned official PostgreSQL 18.6 Alpine
3.24 base in docker/postgres/Dockerfile. The upstream entrypoint, database engine,
ICU locale configuration and persistent data directory remain unchanged.

The derivative installs corrected Alpine curl/libcurl (at least 8.23.0-r0) and
Brotli (at least 1.2.1-r0). It builds the exact gosu 1.20 source revision
29b939aa5bd4dc588af765b8248f6e39150d2565 with pinned Go 1.27.3 in a separate
stage, verifies the source archive SHA256 and Go module checksums, and copies only
the static binary into the runtime. No Go compiler is delivered. The unchanged
entrypoint runs directly as postgres by default; a build probe also verifies the
patched gosu privilege-drop path for deliberate root-mode operator recovery.

Development uses runa-postgres:dev. The image defaults to USER postgres
(UID/GID 70). Docker-managed named volumes
initialize without root; existing PGDATA remains owned by UID 70. Operators using
custom bind mounts must prepare compatible directory ownership before startup;
startup no longer recursively changes ownership as root. Production builds
runa-postgres:<exact SHA> together with PHP/Nginx before starting services or changing data. Do not reuse an
incomplete image set. Replacing the image preserves database volumes; follow the
normal deployment and backup/recovery procedures.

The scanner has no gosu, PHP, Brotli or curl exception callback or policy. It scans
the actual selected images and retains unfiltered raw reports. HIGH/CRITICAL
findings, secrets, operational scanner errors and dependency audit failures block
verification. Earlier risk decisions are historical evidence, not active waivers.

Upstream references: [gosu 1.20](https://github.com/tianon/gosu/releases/tag/1.20),
[curl advisory](https://curl.se/docs/CVE-2026-92392.html),
[Brotli 1.2.1](https://github.com/google/brotli/releases/tag/v1.2.1).

## Upstream replacement and retirement

The derivative is temporary downstream packaging, not a scanner exception.
MT-2026-001 tracks replacing it when an exact official PostgreSQL image contains
corrected curl/Brotli and gosu dependencies/toolchain and passes real compatibility
and strict image scanning. Then remove docker/postgres/Dockerfile, its Go stage and
pins, the Compose build/tag wiring and unused build/cleanup-specific support;
restore the verified official image reference in both environments. Retain ordinary
scanner/installation coverage and dated evidence. Any PHP package floor that becomes
redundant in a verified corrected base is retired in that same deliberate update.

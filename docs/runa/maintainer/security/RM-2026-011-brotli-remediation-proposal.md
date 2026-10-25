# RM-2026-011 Brotli remediation proposal — 2026-10-25

Status: the initial wait-only decision below is superseded by the owner
authorization recorded in [temporary runtime risk acceptance](RM-2026-011-runtime-risk-acceptance.md). The owner now requests PHP rollback and bounded known-risk acceptance while waiting for stable upstream artifacts. The derived PostgreSQL image proposal remains unselected. Earlier candidate evidence below is retained as historical investigation.

## Observed failure

Sequential verification passes the complete owner browser suite (386 passed),
render diagnostics, knowledge doctor/evaluation, production deployment/failure
acceptance (48 checks), customer installation/update/team/knowledge acceptance
(75 checks), and quality scheduler contracts (19 tests in the required container).
The real repository/runtime image stage fails on CVE-2026-111497 in brotli-libs
1.2.0-r1 in both the selected PostgreSQL image and built production PHP image.
Repository, Nginx, Redis and locked dependency audits introduce no blocking result
in that run. The preexisting owner PostgreSQL/gosu policy correctly refuses a
new OS-package finding. Raw reports are preserved, not filtered or accepted.

The [Brotli 1.2.1 release](https://github.com/google/brotli/releases/tag/v1.2.1)
fixes this decoder out-of-bounds write. Existing runtime pins are unchanged:

- PostgreSQL 18.6 Alpine 3.24 index: sha256:77f585114c32fbca283dc835b0596f4e52b51b4c6662d7810b2f4084f60a1873.
- Production PHP 8.4.27 Alpine 3.24 index: sha256:8193f7c711715c88452ebe51d4ee7769172a3fa917446a8820cf84192da2c555.

The latest official PostgreSQL tag resolves to
sha256:898c60cb390d1085bc97bdd6a1ae968757421df394b071848b52b1c09c89d6a5.
Its actual scan still includes vulnerable brotli-libs 1.2.0-r1, so replacing the
pin alone does not resolve the gate. The latest matching PHP tag resolves to
sha256:93aa3f61216b73cc6f878235c9772849449f4efc18dfe3d6678eed645076c00d;
no clean-image claim is inferred from that registry lookup.

## Tested candidate, not an applied implementation

A disposable Dockerfile derives from each existing exact base and applies:

```dockerfile
RUN apk add --upgrade --no-cache "brotli-libs>=1.2.1-r0"
```

The actual resolver upgrades brotli-libs to 1.2.1-r0 and musl from 1.2.6-r2
to 1.2.6-r3. The patched PHP base scan contains zero HIGH/CRITICAL findings and
zero secrets; this is a base candidate, not a complete rebuilt application image.
The patched PostgreSQL scan contains only the exact 26 previously assessed gosu
signatures (including severity/fixed-version details) and no secrets.

The patched PostgreSQL retains PostgreSQL 18.6 and gosu 1.19 / Go 1.24.6.
Its gosu SHA256 remains
52c8749d0142edd234e9d6bd5237dff2d81e71f43537e2f4f66f75dd4b243dd0.
The entrypoint SHA256 is
9c440299ae04a0a79d55b8bf03307036d890a40979d2fb698073c9050d4b20a5.
The binary is root-owned 0755; passwd/group remain root-owned 0644. No existing
cluster, application/test data or database volume was attached to these probes.
No production build, derived-image policy, Compose setting or customer default
was changed. Database/locale/initialization compatibility is still to be tested
against a disposable cluster after an implementation decision.

## Owner decision — 2026-10-25

The owner selects waiting for PostgreSQL upstream to publish a corrected official
image. Keep current runtime configuration and exact-artifact gosu policy; do not
introduce a derived PostgreSQL image or accept the Brotli finding. The owner also
explicitly authorizes another complete all-group verification now. Run unchanged
strict security checks and retain any known failure; this permission does not
waive a hard gate, authorize release/merge with a failure or declare remediation
complete. The earlier derived-image recommendation below remains an unapplied
proposal, not the selected implementation.

## Unapplied remediation proposal

Recommended option: authorize a narrowly scoped Runa-derived PostgreSQL image
using the current official exact base plus the tested Brotli correction, alongside
the same production PHP package correction. Keep the upstream entrypoint and
privilege drop, PostgreSQL/PHP versions, production isolation and durable data.
Integrate the derived image into supported development/production build, selection,
scanner and disposable-resource lifecycle commands. Reassess the existing
owner-only pending-upstream gosu decision against the actual new image; bind any
approved continuation to exact derived-image/source/binary/finding evidence and
expiry. Do not reuse the official RepoDigest proof for a different local image,
weaken artifact checks, ship owner risk acceptance to customers or hide Brotli.
Repeat scoped compatibility/security acceptance and then all changed final stages.

Alternative: retain the official-image-only baseline and wait for a verified clean
upstream artifact. Release and point 20 remain blocked while Brotli is present.
No recommendation to accept the new Brotli vulnerability is made.

The root AGENTS roadmap-execution rule requires discussion when a significant
unknown changes architecture/security assumptions or a previously agreed premise.
The new PostgreSQL image responsibility and exact-artifact gosu binding require
this decision before product implementation. The existing owner acceptance is
historical evidence, not approval for a different image.

## Evidence and ownership

Private reproducibility evidence:
.runtime/artifacts/RM-2026-011/sequential-attempt-5/results.json and its named logs,
registry lookups, candidate Dockerfiles under .runtime/security/candidates/RM-2026-011,
raw candidate scans, binary/mode probes and installation reports under
.runtime/ownership/installation-host-{e8f27b4346,c6452090f1,2f0af5c800}.json.
The failed initial host invocation of scheduler contracts used the wrong transport;
the required container invocation passes all 19 cases. No product change was made
for that diagnostic invocation error.

All native fixtures print scoped resource inventories and remove their disposable
containers/networks/volumes/image tags. Active development and base test data,
Qdrant/model caches, backups and reports are preserved. Candidate probe containers
are automatically removed. Candidate images are disposable and their exact removal
inventory is retained with the private evidence. Shared upstream/build caches stay.

This document records an owner decision proposal, not a delivered capability.
Root/customer operating instructions and the public security policy remain unchanged.

# RM-2026-001 point 14 security review

Reviewed on 2026-10-17 by the Codex agent against commit `432d290`, including the point-14 implementation and its subsequent PHP/PostgreSQL remediation. This completes the point-specific review; it does not attest to the full-roadmap final gate or a real production deployment.

## Reviewed boundaries

- `tools/project/operations.py`: exact Git targets, sparse release checkouts, private raw configuration, explicit production context, operation locks, immutable image checks, deployment recovery markers and migration/seeder compatibility before application rollback.
- Backup and restore: dedicated private paths, constrained backup IDs, checksums, rejection of archive traversal/links/devices, actual temporary database restore, writer coordination, preserved pre-restore source, explicit destructive confirmation and restored database grants. Retention is confined to validated backups in the operator-selected dedicated directory.
- `tools/project/production.py`, production Compose, Dockerfile, entrypoint and database initializer: restricted runtime role, separate admin credentials, loopback-only HTTP, non-root application/Nginx, read-only application roots, limited capabilities, production-only build inputs and AI/RAG isolation. Official PostgreSQL entrypoint/gosu remain unchanged.
- `tools/project/security.py`, `gosu_disposition.py` and `bin/security`: private unfiltered scanner reports, failure propagation, all-image collection, matching-lockfile audit delegation, unchanged exact-artifact decision scope, expiry and live gosu hash/version checks. Configuration hashes from the actual scanned Docker-save archive bind Trivy reports to the approved digest reference; inspected manifest IDs are recorded separately.
- `bin/project` and quality host/development dispatch: explicit real-deployment evidence, one-way versioned state, argument-array subprocesses, no automatic server-side Git push, and host-only production gates.
- Selective ICU switch and inventory helpers: new data volume, preserved rollback volume, no app/devcontainer recreation, read-only old-volume checks, resumable failure evidence and environment-free inventory output. No cleanup is performed by inventory.

## Findings and disposition

No unresolved blocking finding was identified in this reviewed surface. The original runtime-image findings were resolved by the explicitly approved Alpine preparation and final-image verification. Exactly 25 assessed gosu findings retain the separately approved `not affected` decision in `postgres-gosu-not-affected.json`, scoped to the pinned image, amd64 binary, embedded Go version, CVE set and assessment evidence, with review due 2026-11-16. Image/binary/finding drift or expiry blocks acceptance; no global severity or unfixed exemption exists.

Previous review corrections remain verified: recovery markers precede mutation, failed backups recover services, archive links/traversal are refused, restore preserves its source during retention and reapplies grants, unmanaged resources cannot be adopted silently, and database/scanner secret details are not printed in normal diagnostics. The simulation-context name in the operator documentation was corrected to match current code.

## Verification and limits

All 38 focused contracts passed: operations 16, production 2, security 3, gosu disposition 4, quality 7, wrappers 3 and maintenance 3. Contracts cover unsafe archives, interrupted operations, rollback restrictions, caller stdin preservation, stale review evidence, early scanner failure collection and artifact/finding drift rejection.

Reviewed host evidence independently establishes fresh guided installation, exact-image capability probes, five healthy simulation services, root/readiness, image/environment/volume/sparse isolation, UTF8/ICU/pl-PL parity, coordinated backup, real isolated restore verification, database/storage restoration, and complete security exit 0. Upgrade/rollback evidence was obtained earlier in the same point before the database baseline change; database/seeder rollback guards remain covered by contracts. The approved dev/test switch preserved the old volume and active app/unrelated container identities.

Production has never been deployed. No real-production marker, volume deletion or Docker cleanup was authorized or performed by this review. The devcontainer has no Docker CLI/socket; actual host results, rather than offline tests, provide runtime acceptance. Full-roadmap security review and final release gates remain scheduled for the roadmap's final verification point.

# RM-2026-001 final changed-surface security review

Reviewed on 2026-10-18 by Codex on the host. This source review supplements the point-14 review; release acceptance still requires the complete final quality gate.

## Boundaries reviewed

- HTTP controllers, routing, request IDs and configuration validation: minimal health output, database exceptions hidden by readiness, constrained correlation IDs, no application write/authentication surface, and fail-closed production settings. Thin controllers and focused support classes do not require additional application layers.
- Frontend HTTP and preference handling: relative API paths, same-origin credentials, optional storage, typed unknown responses and no raw HTML rendering. Production Nginx supplies restrictive CSP and security headers; the public surface contains no business data or login implementation.
- Production operations and images: exact Git target validation, sparse clean checkouts, explicit simulation context, private configuration, operation locks, recovery markers, restricted database role, non-root PHP/Nginx, read-only roots, restricted capabilities and separate protected volumes. The host continuation archived obsolete private state instead of adopting the pre-ICU database.
- Backup/restore: constrained IDs, integrity hashes, traversal/link/device rejection, isolated verification database, coordinated writers and explicit destructive confirmation. Current simulation upgrade created a coordinated backup successfully; prior point-14 restore evidence remains documented separately.
- Scanner handling: collection continues after image failure; raw reports remain private. The gosu decision binds the archive configuration, approved digest, architecture, live binary/version, exact CVE set and expiry. No broader suppression was introduced.
- Development knowledge: tracked allowlisted sources, symlink/private-file rejection, bounded embedding inputs, dimension/model validation and explicit fallback. Production omits knowledge services and tooling.
- Release tooling: read-only metadata checks validate version progression, roadmap/changelog identity and annotated tags; they do not publish or fabricate runtime evidence.

## Findings and verification

Two host findings were repaired in points 16c and 16d: the missing VERSION frontend build input and inherited Nginx libexpat CVE-2026-77214. Both actual production image builds and runtime probes passed. The corrected Nginx image scan at `6576296` reports no HIGH/CRITICAL vulnerability or secret finding. Alpine confirms the fixed package at https://security.alpinelinux.org/vuln/CVE-2026-77214.

The preceding complete current-image scan passed repository, PHP and dependency checks; PostgreSQL matched exactly the approved 25 gosu findings. Its sole blocking image finding was the now-corrected libexpat package. Raw reports remain under `.runtime/security`; the final gate must rerun all selected scans and checks against its selected artifacts.

Focused operations (16), scanner (3), gosu (4) and production (2) contracts passed. Production contracts must run without another live production operation holding its lock. Development doctor, knowledge freshness/retrieval, documentation/maintenance/tooling checks and release metadata checks passed. Full final quality PASS is not asserted by this report.

No unresolved source-review blocking finding remains in the inspected boundaries. Real production remains never-deployed; local simulation evidence does not establish real HTTPS/SMTP operation. Future business/authentication surfaces require their own review.

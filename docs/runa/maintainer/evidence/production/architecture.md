# Owner procedure and historical evidence

Source classification: `docs/runa/handbook/production/architecture.md`. These owner-only procedures and dated execution records are retained separately from customer guidance. Historical claims keep their original context; current public operations are documented in the handbook.

First installation uses the same Artisan installer as development. Standard
Nginx can serve HTTPS with installer-generated configuration and read-only supplied
certificate/key mounts, retaining nonroot runtime and the existing five services.
An explicitly selected existing TLS terminator is verified instead. HTTPS
root/readiness and SMTP provider acceptance must pass before successful real
installation success is recorded. See [installation](../../maintainer/tooling/installation.md)
for private inputs, retry ownership, preserved secrets/scaffold and simulation.

The implemented deployment workflow runs migrations before worker/scheduler startup, replaces processes after code updates, and performs the operational self-test. Production containers do not run the development setup job. Follow docs/runa/maintainer/production/deployment.md for installer and deployment procedures.

The production entrypoint initializes missing storage directories without deleting data, validates runtime configuration, creates a per-container configuration cache, and execs the selected process. No migration or seeding runs implicitly at startup. Migrations and production seeding must finish before queue/scheduler startup; deployment automation is described in docs/runa/maintainer/production/deployment.md. Storage/cache contents are never supplied from the developer checkout or embedded in images. Recreate containers after environment changes; a simple restart does not reload Compose environment definitions.

`python3 tools/maintainer/testing/production.py` checks topology and production command safety without Docker access. Actual build/image/stack checks require the host's Docker daemon; source tests do not prove container startup. Deployment/self-test/isolation automation and baseline host lifecycle acceptance are recorded in closed RM-2026-001, including the explicit manual editor verification boundary.

The production PHP runtime uses the pinned Alpine PHP image below; PostgreSQL uses the approved Alpine/ICU baseline pinned in compose.production.yaml. Full baseline production simulation/security acceptance is recorded in closed RM-2026-001 and docs/runa/maintainer/security/RM-2026-001-final-review.md.

The upstream base reported zero HIGH/CRITICAL findings, but this is not acceptance of the built Runa image. Actual Docker build, final-image probes, HTTP/background runtime, backup/restore, application/browser checks and final-image Trivy results remain required. No Docker-host evidence for the newly built Alpine PHP image has yet been supplied. PostgreSQL remains on its previous runtime configuration pending the separate [gosu](../../maintainer/security/gosu-assessment.md) and [locale](../../maintainer/production/postgresql-alpine-assessment.md) decision.

Production requires the Linux Docker host/software described in [deployment.md](../../maintainer/production/deployment.md). Compose CPU/memory limits are initial per-service ceilings, not minimum host hardware or throughput guarantees. The administrator sizes RAM/CPU/storage and backup space for actual workloads, concurrent services and startup peaks, measures pressure/latency/OOM events, then deliberately adjusts limits. Development resource presets are independent; no resource numbers changed in RM-2026-003.

These boundaries follow [Nginx FastCGI interception](https://nginx.org/en/docs/http/ngx_http_fastcgi_module.html#fastcgi_intercept_errors) and [internal error-page routing](https://nginx.org/en/docs/http/ngx_http_core_module.html#error_page). The real-container host verification in `docs/runa/maintainer/production/deployment.md` is required in addition to offline configuration contracts.

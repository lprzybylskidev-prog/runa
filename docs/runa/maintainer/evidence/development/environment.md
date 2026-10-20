# Owner procedure and historical evidence

Source classification: `docs/runa/handbook/development/environment.md`. These owner-only procedures and dated execution records are retained separately from customer guidance. Historical claims keep their original context; current public operations are documented in the handbook.

Use the current supported Node LTS available at RM-2026-001 implementation time, pinned to an exact version.

The devcontainer initializes the resource override before Compose starts and attaches as `runa`. Application setup configures the implemented versioned Git hooks. Opening or rebuilding does not delete application data. See [toolchain selections](../../maintainer/development/toolchain.md) and [resource profiles](resources.md).

The initial production build target copies an allowlist of runtime files and locked production dependencies into the shared PHP runtime. Its Nginx image uses a separate production configuration with restrictive browser headers and readiness checks. `compose.production.yaml` defines all five production services; deployment, isolation verification and production operations are implemented. Follow docs/runa/maintainer/production/deployment.md for installer and deployment procedures.

Initialization scripts run only for an empty volume. Changing local passwords does not rewrite an existing database. Reconcile credentials/roles deliberately; do not delete a volume to repair ordinary configuration. Rebuild/reopen preserves databases. Production uses its own Compose project, volume and `.env.prod.database`; production operations are documented in docs/runa/maintainer/production/deployment.md.

The production Dockerfile has explicit `production` and `production-nginx` targets that consume the same locked frontend build stage. PHP gets native translation data/license and the SPA HTML/assets; Nginx gets only static frontend assets/configuration. No host-built frontend output is copied as a build input. Production container behavior and host build checks are documented in `docs/runa/handbook/production/architecture.md` and `docs/runa/handbook/tooling/commands.md`; operational self-tests and their baseline host simulation evidence are recorded in closed RM-2026-001.

Owner browser verification uses `src/runa/tools/testing/frontend.mjs` with project Playwright, Chromium and Firefox. Its screenshots are ignored under `artifacts/playwright/foundation`. Run `PLAYWRIGHT_BROWSERS_PATH=/opt/playwright node src/runa/tools/testing/frontend.mjs` inside the container after provisioning those browsers. The development image provisions the pinned browsers and system dependencies; baseline acceptance is recorded in closed RM-2026-001. Customer `php artisan runa:test:e2e` instead runs `src/app/tests/e2e` directly against testing Nginx/PHP-FPM; platform tests/harnesses are excluded from customer source.

```sh
php artisan runa:runtime:health queue
php artisan runa:runtime:health scheduler
php artisan schedule:list
php tools/maintainer/testing/runtime.php
python3 tools/maintainer/testing/services.py
```

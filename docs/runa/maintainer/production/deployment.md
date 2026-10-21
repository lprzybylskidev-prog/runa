# Owner installation and deployment acceptance

Customer deployment and recovery have one authority: [the public application deployment guide](../../handbook/production/deployment.md). It defines explicit customer remote selection, default `main`/branch/tag/SHA targeting, protected-source preservation, frozen builds, application-only data steps, maintenance ownership and recovery evidence. Application deployment never retrieves Runa releases or runs platform migrations/seeds. Owner platform installation/update remains external.

## Initial production installation

Use the separately delivered [GitHub bootstrap](../tooling/bootstrap.md) and [installer](../tooling/installation.md) against a new dedicated checkout. Public acquisition needs no token; future private access uses hidden input/private credentials. Acquisition can be postponed, never overwrites customer source and leaves no owner launchers in the installation.

The installer selects an immutable platform release, provisions separate admin/runtime credentials and private settings once, projects production source, builds exact images and runs its private platform-only migration/required-seed callback. It validates supplied certificate-backed HTTPS or an explicitly selected trusted terminator, then submits an SMTP verification message. It runs no application migrations/seeds. An interrupted initial install uses its explicit `--resume`; successful repeats preserve source/settings/secrets/data. Bootstrap is not a customer deployment or platform-update command.

The sparse allowlist retains runtime/build/deploy inputs and excludes owner tools/tests/docs and development/AI content. Production runtime images exclude operational Python/Git tooling too. PHP/Composer/pnpm builds run in containers; no host PHP/Node, Docker-in-Docker or runtime Docker socket is required. Host prerequisites remain Linux, Python 3.11+, Git 2.39+, Docker Engine 26+ and Compose 2.35+.

## Isolated acceptance

Only dedicated acquired `mode=prod` fixtures may execute production gates. Development or unknown targets remain blocked regardless of caller `APP_ENV`. Keep production gate routing separate from the maintenance checkout; RM-2026-005 point 13 reconciles the remaining final-gate target preparation without weakening this boundary.

Set `RUNA_PRODUCTION_MODE=simulation` and pass `--simulation`. Installer `simulation_project` selects a fresh `runa-prod-simulation-*` project with loopback TLS and disposable databases/storage. Real mode selects `runa-prod` and refuses custom simulation project names. Unknown existing containers/volumes cannot be adopted. Neither simulations nor real installation publish a production-history flag.

Run native installation/customer deployment acceptance on the Docker host:

```bash
python3 tools/maintainer/testing/installation-host.py --customer-deployment
```

The harness snapshots current source into isolated fixtures, exercises fresh customer dev/prod, native tests/docs/hooks, TLS/SMTP rejection/resume and repeated installation. The deployment extension uses an actual separate customer Git repository and production images/PostgreSQL/HTTP. It proves default/explicit targets, application code/data changes, no duplicate seeds on no-op, protected source/data/storage preservation, lock/manual-maintenance ownership, failed fetch/build/migration/seeding/health and recovery fencing. Fixture-only reviewed recovery retains partial data and never reverses schema. Reports live under `.runtime/ownership`; the harness prints exact task-created containers/networks/volumes before cleanup and preserves active development/test data, caches, backups and evidence.

For focused production-only verification, add `--production-only`; it skips development installation/tests and retains fresh TLS/SMTP/platform setup and the full customer deployment extension. The harness also removes inspected unused image tags created by its own fixtures.

Focused source and recovery contracts are `tools/maintainer/testing/customer-deployment.py` and `tools/maintainer/testing/operations.py`. Contract mocks do not replace native Docker/PostgreSQL acceptance or security/image scanning. Full production acceptance and final release gates remain required by the active roadmap.

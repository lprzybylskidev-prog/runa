# Owner installation and deployment acceptance

Customer deployment and recovery have one authority: [the public application deployment guide](../../handbook/production/deployment.md). It defines explicit customer remote selection, default `main`/branch/tag/SHA targeting, complete-project source selection, frozen builds, ordered forward platform/application data steps, maintenance ownership and recovery evidence. Customer deployment never retrieves Runa releases; the selected customer commit supplies platform and application data steps. Owner platform installation/update remains external.

## Initial production installation

Use the separately delivered customer-repository initializer and [installer](../tooling/installation.md) against a new dedicated checkout. Operator Git credentials remain external; first-development Runa release acquisition is a separate flow. Acquisition can be postponed, never overwrites customer source and leaves no owner launchers in the installation.

The initializer selects an exact customer revision; installation provisions separate admin/runtime credentials and private settings once, stages the complete customer source, builds exact images and runs forward platform migrations/required seeds before application migrate/explicit DatabaseSeeder. It validates supplied certificate-backed HTTPS or an explicitly selected trusted terminator, then submits an SMTP verification message. An interrupted initial install uses its explicit `--resume`; successful repeats preserve source/settings/secrets/data. Bootstrap is not a customer deployment or platform-update command.

The complete customer host source matches development at the selected commit, with common owner-only exclusions; runtime images/services exclude AI/development material. Production runtime images exclude operational Python/Git tooling too. PHP/Composer/pnpm builds run in containers; no host PHP/Node, Docker-in-Docker or runtime Docker socket is required. Host prerequisites remain Linux, Python 3.11+, Git 2.39+, Docker Engine 26+ and Compose 2.35+.

## Isolated acceptance

Only dedicated customer-repository `mode=prod` fixtures may execute production
gates. Development/unknown targets remain blocked regardless of caller APP_ENV.
Standalone production acceptance prepares fresh images/data and invokes public
validation, health and AI isolation; standalone scanning prepares its own healthy
fixture. When both are selected, the existing quality graph shares one preparation,
scans the healthy exact images, then runs deployment/failure scenarios. Separate
selected results require exact-run phase evidence, including successful cleanup;
see [fixture composition](../quality/README.md#parallel-execution).
Trivy retains owner repository scope while Composer/pnpm audits use the owner
development toolchain. Selected deployment lock hashes must match the scanned
candidate or the scan blocks. No production operation targets the maintenance checkout.

Set `RUNA_PRODUCTION_MODE=simulation` and pass `--simulation`. Installer `simulation_project` selects a fresh `runa-prod-simulation-*` project with loopback TLS and disposable databases/storage. Real mode selects `runa-prod` and refuses custom simulation project names. Unknown existing containers/volumes cannot be adopted. Neither simulations nor real installation publish a production-history flag.

Run native installation/customer deployment acceptance on the Docker host:

```bash
python3 -m tests.tools.runa.acceptance.installation_host --customer-deployment
```

The harness snapshots current source into isolated fixtures, exercises fresh customer dev/prod, native tests/docs/hooks, TLS/SMTP rejection/resume and repeated installation. The deployment extension uses an actual separate customer Git repository and production images/PostgreSQL/HTTP. It proves default/explicit targets, platform/application code/schema/reference-data changes, additional real Redis services, idempotent required seeds/no-op preservation, existing history/data/storage preservation, lock/manual-maintenance ownership, failed fetch/build/migration/seeding/health and recovery fencing. Fixture-only reviewed recovery retains partial data and never reverses schema. Installer reports and disposable workspaces live under private `.runtime/acceptance/installation`; legacy `.runtime/ownership` evidence is preserved. The harness prints exact task-created containers/networks/volumes before cleanup and preserves active development/test data, caches, backups and evidence.

For focused production-only verification, add `--production-only`; it skips development installation/tests and retains fresh TLS/SMTP/platform setup and the full customer deployment extension. The harness also removes inspected unused image tags created by its own fixtures.

Focused source and recovery contracts are `tests/tools/runa/contracts/test_customer_deployment.py` and `tests/tools/runa/contracts/test_operations.py`. Contract mocks do not replace native Docker/PostgreSQL acceptance or security/image scanning. Full production acceptance and final release gates remain required by the active roadmap.

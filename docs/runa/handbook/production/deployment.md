# Application deployment

One customer repository versions the whole delivered project: Runa, application, scaffold and infrastructure. Production initializes from that repository and deploys a selected tested customer commit, including both owners' required forward data steps. Development and production retain the same versioned source set; private env/data/dependencies and runtime artifacts differ. Update Runa externally on development, verify and commit/push the project, then deploy that revision. Production never fetches a Runa release or runs a separate platform updater.

Prepare changed private environment/service prerequisites using the selected release's [manual reconciliation procedure](../release/platform-reconciliation.md) before deployment. Customer container customizations stay in the reviewed complete project revision.

Production commands require the clone's locally prepared production role (mode=prod); APP_ENV and --simulation cannot override it. Run from the installation root in the separately prepared PHP operator terminal; initial setup/recovery uses ./bin/artisan and ./bin/artisan --operator. Runtime containers have no deployment commands or Docker access. Help and explicit --plan are nonexecuting.

## Select the customer repository

Use the existing customer project repository, with the complete delivered project. Select its existing remote explicitly, or add a separate named customer remote:

```bash
git remote add customer <customer-repository-url>
git config --local runa.applicationRemote customer
```

If `origin` already identifies the customer repository, select `origin` instead. Selection never repairs/repoints remotes, changes push configuration or writes customer history. Without explicit selection, deploy refuses to guess a repository. The selected effective URL must identify the customer repository, never the receipt's owner Runa repository. Supported transports are credential-free HTTPS, SSH and an explicit separate absolute local repository. Keep credentials in operator credential helpers/SSH setup; interactive Git credential prompts are disabled. Tokens/passwords in URLs, multiple remote URLs and unsupported helpers are rejected.

## Deploy a revision

```bash
php artisan runa:deploy
php artisan runa:deploy release/customer-v2
php artisan runa:deploy customer-release-42
php artisan runa:deploy <full-lowercase-commit-SHA>
```

A successful initial production installation must already be recorded; acquisition alone cannot be used as an application deployment. The default target is `main`. Branches, lightweight/annotated tags and exact 40-character lowercase SHAs are supported independently of the application's version-number scheme. Ambiguous branch/tag names require `refs/heads/...` or `refs/tags/...`. Options, revision expressions and abbreviated commit identities are unsupported. `--plan` previews dispatch without fetching or executing preflight; it is not proof that the target can deploy. A configured isolated simulation additionally requires matching `RUNA_PRODUCTION_MODE=simulation` and `--simulation`.

Deploy fetches only the selected customer source into disposable staging and resolves its exact commit. The selected source may change src/runa, scaffold and container definitions. There is no old/new platform byte comparison or source-integrity gate in production; that check belongs to the development updater. Safe paths, supported file types, no application symlinks/submodules, common owner-only exclusions and immutable successful-release ownership remain checked. A recorded successful checkout must still match its own source commit. Operator working-tree edits never become expected-source authority and are not overwritten.

A complete detached project is staged under .runtime/production/releases/<SHA>. Private production settings are copied with mode 600, and the SHA identifies PHP/Nginx images. Preserved Composer/pnpm declarations/locks must compose and frozen builds/configuration must pass. Complete image pairs are reused and verified; incomplete pairs require inspection. Pre-mutation build/config failures preserve the running deployment and restore its selected tag. Temporary fetch staging is removed; immutable releases/evidence remain for diagnosis.

Deploy records pending state, starts PostgreSQL, fences HTTP and stops the core PHP-FPM/queue/scheduler writers. Data steps are ordered:

1. Forward Runa migrations, using the Runa migration repository.
2. The fixed required Runa seed root, with atomic exact-source completion records.
3. All supplied Runa-module migrations/upgrades, including disabled modules, through the shared source-linked completion ledger.
4. Application migrate --force, using its separate repository.
5. The application's explicit DatabaseSeeder via db:seed --force. Application module installers require an explicit customer-owned invocation/order; deployment never silently discovers and executes them.

Neither owner discovers loose seed files. Required platform seeds must be idempotent; their effects and completion record share a transaction. Application seeders must also be repeatable. Native application migrate/fresh/wipe/seed commands remain application-only; coordinated deployment adds no destructive platform command.

Restart/recreate the selected production services, including declared customer additions such as Redis, and check real HTTP/readiness, image identities and AI/dev isolation. Optional profile services run only when explicitly selected. Unknown/orphan containers require manual reconciliation; deploy never prunes containers or volumes to force success. Both durable storage trees, existing migration histories/data, production secrets and customer Git remotes/history remain preserved. Successful state records independent application/platform manifest versions. Repeating a successful commit verifies health without replaying data steps.

Reconcile release-required Docker/Compose/devcontainer/bootstrap/build/env changes manually on development and commit versioned changes. Prepare actual production env/secrets and any prerequisites/service activation before deployment, following release instructions. Additional services/volumes are supported; actual config/build/runtime failures still stop deployment. Keep additional customer writers stopped during data transitions. No automatic infrastructure-transition or backup engine is supplied. Configuration caches are per container; zero downtime is not promised.

## Failure and recovery

All production operations share a nonblocking lock. An existing Laravel/shared HTTP maintenance marker blocks deploy before it can claim or clear manual maintenance. An unresolved `.runtime/production/in-progress.json` blocks subsequent deploy/rollback.

`deployment.json` records successful current/previous source/image identities, independent platform/application versions and UTC timestamps. `in-progress.json` records the target, previous deployment, current phase and whether data steps started. Keep the entire `.runtime/production` directory and production env files private. Do not print expanded Compose/env configuration.

Migration, seeding, module-upgrade, restart or health failure returns nonzero, retains recovery evidence, enables HTTP maintenance and stops writers. If fencing itself fails, diagnostics explicitly require immediate operator intervention. Partial seeding may have committed data; equal migration/seeder source hashes do not prove compatibility. No automatic code/schema rollback or blind seed replay occurs.

Inspect the recorded phase, images, database and application state under the application's recovery procedure. Preserve failure evidence before deliberately clearing a resolved marker; deleting it is not recovery. Externally managed customer backups may be part of that procedure. There is no bundled backup/restore subsystem or mandatory backup prerequisite.

`php artisan runa:deploy:rollback` retains the previous-image recovery boundary, checks source fingerprints and requires explicit recorded proof of schema/data compatibility. Current deployments perform arbitrary application data steps and record compatibility as unproven, so automatic rollback is blocked. Use a reviewed operator recovery procedure; code/image rollback cannot reverse schema/data changes.

Production self-test verifies services, root/readiness and AI/dev isolation. See [production architecture](architecture.md), [host executor](../tooling/host-executor.md), [database safety](../architecture/database.md) and [public commands](../tooling/commands.md).

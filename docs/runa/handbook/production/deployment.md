# Application deployment

Production commands require an installer-selected production installation (`mode=prod`). Development, missing or unknown acquisition modes are rejected before production work. `APP_ENV` and `--simulation` cannot override installation ownership. Help and explicit `--plan` remain nonexecuting. Run from the installation root in the separately prepared PHP operator terminal; initial setup/recovery uses `./bin/artisan`, and `./bin/artisan --operator` enters that terminal. Runtime containers do not expose deployment commands or Docker access.

## Select the customer repository

Use the existing customer project repository, with `src/app` and the supplied project/build inputs. Select its existing remote explicitly, or add a separate customer remote without changing the acquired release upstream:

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

A successful initial platform installation must already be recorded; acquisition alone cannot be used as an application deployment. The default target is `main`. Branches, lightweight/annotated tags and exact 40-character lowercase SHAs are supported independently of the application's version-number scheme. Ambiguous branch/tag names require `refs/heads/...` or `refs/tags/...`. Options, revision expressions and abbreviated commit identities are unsupported. `--plan` previews dispatch without fetching or executing preflight; it is not proof that the target can deploy. A configured isolated simulation additionally requires matching `RUNA_PRODUCTION_MODE=simulation` and `--simulation`.

Deploy fetches only the selected customer source into disposable local staging, resolves its exact commit and compares protected source against the installed project and its last successful immutable release. A committed platform change in the operator checkout, or a modified successful release checkout, also blocks before production commands can use that source. Changes, deletions, additions or mode changes outside `src/app`, `docs/app` and the composed dependency manifests/locks block deployment. Owner-only excluded source is never materialized. Modified/untracked protected installation source, application symlinks and submodules also block. Customer application and composed Composer/pnpm inputs may change; their composition and frozen-lock installation must validate before runtime mutation. Platform updates remain external owner operations.

A clean detached production projection is prepared under `.runtime/production/releases/<SHA>`. Private production settings are copied with mode 600; the exact SHA selects PHP/Nginx image tags. Complete existing image pairs are reused and verified; an incomplete pair requires explicit inspection. Build and configuration failure leave the current deployment running and restore its selected tag. Temporary fetch staging is removed on success and failure; immutable release checkouts/images remain available for diagnosis.

After preflight, deploy records the pending operation, starts the database, enables the shared HTTP maintenance marker and stops PHP-FPM, workers and scheduler. It then runs only application `migrate --force` and `db:seed --force`. The application `DatabaseSeeder` selects explicit calls/registrations; loose files and Runa seeds are never discovered. Writers are recreated, HTTP is reopened and services, root/readiness, image identities and production isolation are checked. Laravel configuration caches are prepared per container. Public storage uses the existing Nginx volume subpaths. Version switching can briefly interrupt transport; zero downtime is not promised.

The operation preserves protected platform source, both durable storage trees, production credentials, platform migration/seed history and application data. It neither fetches the owner's Runa repository nor performs platform migrations/seeding. Existing customer remotes, refs and working-tree application edits are untouched. A repeated successful commit verifies health without rerunning data steps.

## Failure and recovery

All production operations share a nonblocking lock. An existing Laravel/shared HTTP maintenance marker blocks deploy before it can claim or clear manual maintenance. An unresolved `.runtime/production/in-progress.json` blocks subsequent deploy/rollback.

`deployment.json` records successful current/previous source/image identities, independent platform/application versions and UTC timestamps. `in-progress.json` records the target, previous deployment, current phase and whether data steps started. Keep the entire `.runtime/production` directory and production env files private. Do not print expanded Compose/env configuration.

Migration, seeding, restart or health failure returns nonzero, retains recovery evidence, enables HTTP maintenance and stops writers. If fencing itself fails, diagnostics explicitly require immediate operator intervention. Partial seeding may have committed data; equal migration/seeder source hashes do not prove compatibility. No automatic code/schema rollback or blind seed replay occurs.

Inspect the recorded phase, images, database and application state under the application's recovery procedure. Preserve failure evidence before deliberately clearing a resolved marker; deleting it is not recovery. Externally managed customer backups may be part of that procedure. There is no bundled backup/restore subsystem or mandatory backup prerequisite.

`php artisan runa:deploy:rollback` retains the previous-image recovery boundary, checks source fingerprints and requires explicit recorded proof of schema/data compatibility. Current deployments perform arbitrary application data steps and record compatibility as unproven, so automatic rollback is blocked. Use a reviewed operator recovery procedure; code/image rollback cannot reverse schema/data changes.

Production self-test verifies services, root/readiness and AI/dev isolation. See [production architecture](architecture.md), [host executor](../tooling/host-executor.md), [database safety](../architecture/database.md) and [public commands](../tooling/commands.md).

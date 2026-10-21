# Reconciling a platform release

One developer receives the external Runa update. The team and production receive the reviewed complete customer revision through the customer repository. Plan the change on an application roadmap and arrange customer-managed backups/recovery before any data transition.

## Release instructions

For each selected release, obtain its source/scaffold differences and environment instructions from the owner. Record the supported starting versions, target compatibility range, required Composer/pnpm composition, platform migration/seed effects, manual Docker/devcontainer/Compose/bootstrap changes, required environment keys, restart order and verification/recovery expectations. Secrets and actual private environment values never belong in release notes or Git.

The planned Runa 0.3.0 ownership split requires a customer repository containing the complete delivered project, excluding owner tools and local generated state. Establish customer history from that delivered source; sparse exclusion does not remove owner files from inherited Git objects. Older releases with a different source layout need a separately reviewed migration; they are not supported inputs to the source-replacement updater. Do not treat synthetic acceptance tags as published upgrade paths.

Customer container definitions and added services are supported. Compare their behavior with the target release requirements, preserve intentional customization and make the necessary changes manually. The updater reports affected service names/devcontainer and waits for continue/abort; continue preserves those files. A customization decision cannot waive protected-source, compatibility, lock or runtime failures.

## Updating developer checkout

1. Select the exact owner release and inspect a read-only external plan. Resolve every blocker. Review dependency conflicts before changing manifests/locks; dependency policy still applies.
2. Manually reconcile the target composed Composer/package manifests, their locks, framework/bootstrap/build inputs, Dockerfiles, Compose/devcontainer definitions and environment templates. Preserve application declarations, custom services and secrets. Use the selected container toolchain to resolve an approved lock change; the updater does not resolve or replace locks.
3. Prepare required private environment values on this machine. Application settings belong in `.env` for development and `.env.prod` for production; `.env.dev.local` accepts only the documented development profile/device keys. Manually provision any additional service env files at their selected runtime paths. Build/recreate affected infrastructure explicitly, in prerequisite order, then confirm actual services/health/mounts match the selected definitions. Restart alone does not apply changed images or Compose configuration. Rebuild/reattach the devcontainer when its image changes.
4. Stop custom data writers. Obtain a fresh plan and bind consent to its infrastructure fingerprint. The external owner applies the update only to src/runa and this development database. Failure requires inspection and explicit same-release resume; preserve operation evidence. Changed customization requires a new review.
5. Inspect source/settings/data and run application quality/tests, including affected rendered UI. Explicitly prepare any pending application migrations/DatabaseSeeder; the owner updater performs only Runa data steps. Keep both data owners' histories and private storage intact.
6. Review the complete diff and commit/push the updated customer project, including platform source, reconciled scaffold/infrastructure, public templates and affected application documentation. The updater never commits or pushes it. Keep secrets, operation journals, dependencies and generated output untracked.

## Team and production propagation

Teammates preserve their local settings/data, pull the selected customer revision and reconcile their private environment values. Run `./bin/artisan runa:dev:initialize` explicitly to install/build the selected inputs and apply forward Runa migrations/required seeds before application migrations/DatabaseSeeder. Required Runa seed completion is transactional; application seeders must be repeatable. Inspect partial application effects before retrying a failure. Ordinary reopen/setup does not apply data changes.

On production, manually prepare private environment/TLS/SMTP/service prerequisites for that revision, then deploy it with `./bin/artisan runa:deploy <customer-tag-or-full-sha>` from the production customer checkout. Deployment fetches only the customer repository, builds its whole selected project and runs both owners' forward data steps. There is no production platform-update operation or upstream release fetch. Verify the recorded commit, actual runtime health and data, then retain external recovery material according to customer policy. Code/image rollback alone cannot reverse schema/data.

Runa's fixed forward migration/required-seed phases are the ordered contribution seams for future module work. They do not discover modules or loose seeders. Module registration/state belongs to its own roadmap.

See [platform updates](../production/platform-updates.md), [development initialization](../development/environment.md#customer-repository-and-local-initialization) and [deployment](../production/deployment.md).

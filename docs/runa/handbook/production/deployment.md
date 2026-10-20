# Application deployment

Production uses the separate configured production installation and images; development or missing/unknown acquisition modes cannot run production/deploy operations. Help and explicit --plan remain nonexecuting. APP_ENV changes and simulation flags do not grant production access.

`php artisan runa:deploy <target>` currently requires an explicit prepared target and operator review. Do not assume it fetches the customer repository's main branch automatically; that repository acquisition workflow is not yet delivered. Use the configured production operator environment and inspect private operation state before recovering an interrupted operation. Do not retry a mutating operation blindly after a disconnect.

Application migration/seeding uses only application-owned objects and explicitly registered DatabaseSeeder calls. Loose platform/customer seeder files are not automatically discovered. Platform installation/update and platform migrations/seeds are externally owner-managed. Ordinary startup/reopen runs neither owner's migrations or seeds.

Customer backups and recovery procedures are externally managed; there is no bundled backup/restore subsystem or mandatory backup launcher. Code/image rollback cannot reverse schema/data changes. Validate data compatibility before rollback, preserve maintenance ownership and recover from the recorded failed stage. See [production architecture](architecture.md), [host executor](../tooling/host-executor.md), [database safety](../architecture/database.md) and [public commands](../tooling/commands.md).

Production self-test verifies services, root/readiness and AI/dev isolation. Never route production checks to development data or use caller environment overrides to bypass installation ownership.

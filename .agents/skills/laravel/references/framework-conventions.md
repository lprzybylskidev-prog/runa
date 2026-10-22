# Laravel Framework Conventions

1. Prefer current Laravel conventions before creating custom framework-level abstraction, unless Runa explicitly overrides them.
2. When unsure about an API/current-version behavior, use Laravel Boost version-aware documentation before relying on memory.
3. Use Artisan generators when they create correct boilerplate and reduce manual mistakes.
4. Named routes are always required.
5. Keep route definitions focused and organized; application behavior belongs in controllers/actions, not closure routes.
6. Do not use `php artisan serve`; Runa uses Nginx -> PHP-FPM.
7. Use dependency injection rather than `app()` service-locator access in business/application code.
8. Laravel facades are acceptable pragmatically at clear framework boundaries.
9. Use Laravel/framework encryption, hashing, random/token, CSRF, cookie/session, and other security primitives instead of reimplementing them.
10. Hide sensitive attributes where model/resource serialization could otherwise expose them.
11. Use Collections where they improve clarity; do not convert every array/query result into a Collection ritualistically.
12. Follow Runa architecture rather than assuming framework starter-kit conventions.
13. Production/basic health endpoint should be minimal and safe. Deep diagnostics belong to operator doctor commands.
14. Use maintenance mode only when an operation actually needs it.
15. Current installed framework/version behavior wins over historical Laravel advice.
16. Generic Boost guidelines are advisory; active Runa roadmap/code/project rules win.
17. A final review of a substantial Laravel change may include simplification of the changed/relevant area. Do not opportunistically refactor the entire application.
18. Do not adopt Laravel Cloud/Nightwatch/starter-kit workflow assumptions unless a future roadmap explicitly chooses them.

## Source and runtime ownership

Laravel's base path is src/. Platform classes use Runa under src/runa; App under src/app is the customer extension root. Technical defaults/data/routes belong to Runa, with distinct customer configuration/migration/seeder contributions. Follow docs/runa/handbook/architecture/application-architecture.md for the source/runtime composition before using generators, package discovery or caches. The thin Runa Application subclass points resourcePath to app/resources; native namespace discovery uses src/composer.json. src/tests/Pest.php loads only application test contributions. Keep platform/application filesystem and cache stores separate; do not put writable state inside either source tree.

## Module public entrypoints

Module HTTP routes use source-owned `HttpProvider` descriptors and the framework dispatcher, with named owner/code prefixes and `ApiOperation` identity. The dispatcher avoids Laravel's early controller construction while gathering middleware. Use plain invokable controllers, constructor-injected services and explicit Request/FormRequest/scalar route arguments; controller middleware and implicit model binding are rejected. Permission decisions use `OperationAuthorizer`, whose default denies protected operations. Module PHP consumers retain typed `GuardedApi` proxies from `ModuleApis`, never unchecked implementations. Rebuild route caches after explicit source synchronization; stale module descriptors must leave CLI synchronization/health recovery bootable through rejecting HTTP fallbacks. See the public module contract for exact supported types and error semantics.

## Module installation boundaries

Use ModuleUpdates for explicit owner-scoped module installation, never lifecycle toggles or request boot. Pure upgrade descriptors/preflight declare forward version paths; schema migrations and transactional upgrade completion records commit atomically. Nontransactional retry requires explicit idempotency, otherwise retain the source/owner/fingerprint fence and follow documented operator recovery. Reuse the caller's installation/deployment journal/lock/maintenance; the platform updater selects only Runa modules. App-module invocation and ordering stay explicit in the customer deployment. See docs/runa/handbook/architecture/module-framework.md for the delivered contract.

## Module generators

Use `runa:make:module owner.code [type] [name]` for module-local generation, following docs/runa/handbook/development/modules.md. Native `make:*` retains ordinary application paths. Generated controllers use owned HttpProvider declarations and named centrally guarded routes; public APIs use GuardedApi proxies. Run reusable ModuleContractSuite source checks, module tests and synchronized runtime checks; generator success is not implementation acceptance.

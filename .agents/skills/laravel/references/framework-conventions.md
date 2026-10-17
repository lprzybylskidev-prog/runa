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

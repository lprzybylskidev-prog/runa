# Application laravel guidance

Read docs/runa/handbook/architecture/application-architecture.md. Use Laravel Boost version-aware documentation before guessing APIs. App namespace maps to src/app; providers are declared in src/app/providers.php, routes/config/resources in their App roots. Native make commands create ordinary App code; module generation uses runa:make:module app.code and exact preview/hash overwrite authorization. Never edit platform providers/routes/defaults.

Use named API routes, dependency injection, explicit mass assignment, Form Requests and JsonResources. Avoid N+1 and queries hidden in casts/accessors. Use framework security/cache/filesystem primitives. Never use env outside configuration or artisan serve. Declare app seeding explicitly; native migrations/seeding are app-only. Follow docs/runa/handbook/data/seeding.md and architecture/database.md. Reopen performs no data mutation.

Discover supported commands in docs/runa/handbook/tooling/commands.md. Application tests use runa:test (or native test), runa:test:frontend and runa:test:e2e under src/app/tests. Preserve suite locks, guarded workers and actual child exit status. Do not use private platform tests or bypass these entrypoints with internal runners. Prepare broad test scenarios during implementation; execute full suites in final verification.

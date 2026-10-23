# Application laravel guidance

Read docs/runa/handbook/architecture/application-architecture.md. Use Laravel Boost version-aware documentation before guessing APIs. App namespace maps to src/app; providers are declared in src/app/providers.php, routes/config/resources in their App roots. Native make commands create ordinary App code; module generation uses runa:make:module app.code and exact preview/hash overwrite authorization. Never edit platform providers/routes/defaults.

Use named API routes, dependency injection, explicit mass assignment, Form Requests and JsonResources. Avoid N+1 and queries hidden in casts/accessors. Use framework security/cache/filesystem primitives. Never use env outside configuration or artisan serve. Declare app seeding explicitly; native migrations/seeding are app-only. Follow docs/runa/handbook/data/seeding.md and architecture/database.md. Reopen performs no data mutation.

Discover supported commands in docs/runa/handbook/tooling/commands.md. Application tests use runa:test (or native test), runa:test:frontend and runa:test:e2e under src/app/tests. Preserve suite locks, guarded workers and actual child exit status. Do not use private platform tests or bypass these entrypoints with internal runners. Prepare broad test scenarios during implementation; execute full suites in final verification.

Module HttpEndpoint access declarations use the shared pre-controller account policy and trusted principal scope. Keep session/CSRF enforcement explicit and do not recursively call protected identity APIs during authorization. See docs/runa/handbook/architecture/module-foundations.md; these seams alone are not authentication. App code never edits their protected bindings.

Meaningful business/security mutations require Audit history. Use public Auditable Contracts with approved fields inside the default PostgreSQL transaction. Bulk SQL, relationship/pivot changes and aggregate operations require explicit manual Audit events in that same transaction; scoped automatic suppression never disables them. Follow docs/runa/handbook/modules/audit/README.md for guarded ModuleApis, redaction, rollback/refusal boundaries and permission-protected bounded reads. Never invoke the protected bootstrap/settings writer or assume arbitrary Eloquent writes are captured.

Auth owns its ModuleModel/Authenticatable implementation and public UUIDv7 identity. Consume guarded identity/lifecycle APIs; do not add a second users table/model or mutate private account state directly. Follow the public Auth identity/lifecycle contract in `docs/runa/handbook/modules/auth/README.md`; cookie/session/CSRF entry transport, registration and invitation/verification are delivered under `docs/runa/handbook/modules/auth/transport.md`; password/2FA use the credential guide below. Do not add duplicate Fortify routes or send credentials/tokens through module APIs, events, metadata or queue payloads.

Use `docs/runa/handbook/modules/auth/sessions.md` for tracked session/deadline checks,
own/admin revocation, real-activity heartbeat and bounded account/source login locks.
Polling never renews activity; remember persistence cannot restore a revoked session
or move its captured absolute deadline. Technical cleanup preserves Audit history.
Do not add a recaller or attacker-triggered permanent account lock. Consume delivered
public contracts; protected platform source remains read-only.

Use `docs/runa/handbook/modules/auth/security.md` for shared password policy/recovery,
mandatory factors, trusted devices and AuthStepUp. Keep server-chosen step-up and
its every-action consumption inside the business transaction. Trust/recent login
never substitutes for required fresh proof; keep credentials/OTP/history private.

Use `docs/runa/handbook/modules/auth/profile.md` for versioned private profile and
address workflows, notification-only verified secondary emails, authenticated avatar
ownership/cleanup and safe own-history summaries. Consume guarded public contracts;
never route core security mail to secondaries or grant general Audit read access
for an own-account history feature. Protected platform implementation remains read-only.

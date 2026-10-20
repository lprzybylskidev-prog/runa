# Queues, Scheduler, Console, and Testing

## Jobs

1. Jobs are for asynchronous/heavy/long work.
2. A job should delegate business logic to an Action/Service rather than embedding the whole use case.
3. Define queue/retry/backoff behavior explicitly per job.
4. Make duplicate-risk jobs idempotent when possible.
5. Dispatch after commit when the job depends on committed database state.
6. Failed jobs use standard Laravel failure mechanisms plus safe actionable context.

## Scheduler

7. Declare scheduled behavior using the current supported Laravel scheduler mechanism.
8. Do not require host cron as the application architecture when the production scheduler container can run the supported mechanism.
9. Add overlap/concurrency protection when a scheduled task would be unsafe to run concurrently.
10. Do not apply overlap locks mechanically to tasks that are naturally safe.

## Console commands

Runtime-independent host operations share the explicit management command registry with normal local/testing Artisan. Normal php artisan host commands execute through the authenticated private workspace executor; only explicit --plan is inert. Use `./bin/artisan --management list` for cold discovery and --recover-host/--operator for setup/recovery; application providers, dotenv, DB and keys are outside that bootstrap. Local development tooling runs PHP Actions/fixed local processes. Operational and optional development registries remain separate. Production runtime has no management commands, executor socket or credentials; only the separate operator environment receives transport access. Follow docs/tooling/host-executor.md and the current command reference, including operation receipts after self-restart/disconnection and no blind mutation retry.

First-install host plans use runa:install dev/prod, existing dev/deploy mechanics,
private settings and explicit initial-install resume. Preserve the scaffold and
generated secrets; repeat is not an update/reset. Native SMTP verification is an
application command and must redact provider failures. Follow
docs/tooling/installation.md for the implemented lifecycle and acceptance scope.

11. Console commands return meaningful exit codes.
12. Output/help is concise, actionable, and English-only.
13. Destructive commands require clear confirmation interactively.
14. If a destructive command needs automation, provide an explicit safe non-interactive mode instead of bypassing safety implicitly.
15. Do not print secrets/sensitive payloads.

## Testing

16. Prefer Pest when the installed version stack supports it cleanly; otherwise use PHPUnit.
17. Feature tests are primary for application behavior crossing Laravel boundaries.
18. Unit tests focus on isolated logic where that scale is genuinely clearer.
19. Use framework fakes selectively and preserve meaningful integration behavior.
20. Security/behavior regressions receive focused tests when reproducible.

## Additional framework checks

Use single-server scheduling only when the deployment topology and task semantics require it; do not add it mechanically. Do not queue listeners solely because they can implement `ShouldQueue`.

For future HTTP integrations, framework tests fake outgoing HTTP and prevent accidental real network calls. The integration module still owns adapter/provider design; this is test safety, not a premature module architecture.

Use Laravel cache and filesystem abstractions where infrastructure changes should remain transparent to application behavior. Detailed cache/storage lifecycle stays with its owning feature/module.

## Project seeding

Use the shared `ManagedSeeder` / `SeedGraph` foundation and `docs/data/seeding.md` for reference/demo/manual scenarios. Validate dependencies before execution and retain the direct development-environment guard. Keep scenario options in Laravel config and use each `SeedContext`'s independent generator; never seed global randomness or invent demo entities merely to populate the framework.

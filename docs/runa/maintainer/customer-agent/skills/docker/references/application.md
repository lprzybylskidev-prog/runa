# Application docker guidance

Read docs/runa/handbook/development/editor-and-agents.md and tooling/commands.md. Use supported runa:dev lifecycle/build/recreate/profile commands with the documented host transport. Inspect actual project/files, mounts and service identities. Preserve source bind mounts; keep PHP/Composer/Node/pnpm in containers. No Docker-in-Docker, privileged containers, Docker socket convenience mounts, chmod 777, global prune or incidental orchestrator/CI changes. Use service DNS, bounded meaningful health checks, explicit dev/prod scope and selective operations where appropriate.

Reopen preserves database/storage/model/Qdrant caches and runs no migrations/seeding. Explicit runa:dev:initialize handles ordinary teammate clones; do not copy another workstation receipt. Optional AI startup is independent of app readiness. Production excludes AI/dev/owner tooling and deploys the complete customer commit from its explicitly configured repository. Preserve operation journals/maintenance on failure; image rollback does not reverse data. Cleanup only proven task-created disposable resources after showing exact identities; preserve durable data, caches, backups and reports. Infrastructure updates use the external reviewed release procedure, never a src/runa repair override.

Environment creators/readers, template names and safe credential reconciliation are documented in docs/runa/handbook/development/environment-files.md. Preserve private backup configuration; no implicit credential rotation or volume reset. Development preparation checks both application and test credentials against initialization inputs.

Use docs/runa/handbook/architecture/project-principles.md#generated-tooling-and-build-state
for generated private build/tooling artifacts: one workspace-root .runtime/<area>,
no src/.runtime or module-local runtime roots. Preserve public asset/Laravel storage
contracts and unrelated reports, backups, caches and operation/recovery state.

Supported test runners own disposable workers and automatically created browser
containers according to the [test resource lifecycle](../../../../docs/runa/handbook/testing/strategy.md#test-resource-lifecycle).
Use its guarded teardown/recovery; preserve manual endpoints, base test data,
volumes and evidence. Do not replace it with broad cleanup.

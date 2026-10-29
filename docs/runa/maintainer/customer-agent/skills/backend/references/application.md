# Application backend guidance

Use thin controllers, Form Requests for substantial writes, Actions for use cases, Services for shared integrations and Query classes for complex reads. Keep Eloquent; do not add a repository layer by default. Make transactions, commit-dependent effects and retry safety explicit.

Load only the relevant canonical procedure:

- Application design: `docs/runa/handbook/architecture/application-architecture.md`.
- Supported adapters and source ownership: `docs/runa/handbook/architecture/public-api.md`.
- Optional module declarations and versions: `docs/runa/handbook/development/modules.md`.
- Module lifecycle/work/events/settings: `docs/runa/handbook/architecture/module-framework.md`.
- Transactional history: `docs/runa/handbook/modules/audit/README.md`.
- Identity and security: `docs/runa/handbook/modules/auth/README.md`.
- Grants and assignment/authority: `docs/runa/handbook/modules/permissions/README.md`.

Maintain application-specific patterns in local guidance/docs/app. Ordinary App
code and optional app modules are supported; copied owner files or a Contracts
namespace never authorize platform/internal APIs. Preserve installed local
extensions during reviewed updates. This default contains no owner-only procedure.

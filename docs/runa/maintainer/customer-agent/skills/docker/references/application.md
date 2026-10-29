# Application docker guidance

Identify development versus production context and exact project/resource ownership before operations. Keep PHP/Node tooling in containers, host orchestration stdlib-only and ordinary reopen nondestructive. Never use global prune or remove uncertain/durable resources.

Load only the relevant canonical procedure:

- Development lifecycle: `docs/runa/handbook/development/environment.md`.
- Editor/reopen and host handoff: `docs/runa/handbook/development/editor-and-agents.md`.
- Environment files: `docs/runa/handbook/development/environment-files.md`.
- Production responsibility/isolation: `docs/runa/handbook/production/architecture.md`.
- Customer deployment: `docs/runa/handbook/production/deployment.md`.
- Scoped test resource lifecycle: `docs/runa/handbook/testing/strategy.md`.
- Knowledge caches/startup: `docs/runa/handbook/development/knowledge.md`.

Maintain application-specific patterns in local guidance/docs/app. Ordinary App
code and optional app modules are supported; copied owner files or a Contracts
namespace never authorize platform/internal APIs. Preserve installed local
extensions during reviewed updates. This default contains no owner-only procedure.

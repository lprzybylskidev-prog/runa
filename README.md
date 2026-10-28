# Runa application foundation

Runa supplies one Laravel application with Vue 3, TypeScript, Tailwind CSS and PostgreSQL. Customer business code lives under `src/app`; the supplied platform lives under `src/runa`. Use public extension points and application configuration rather than editing platform source.

Read [AGENTS.md](AGENTS.md) before agent-assisted work. [Application documentation](docs/app/README.md) and the [Runa handbook](docs/runa/handbook/README.md) describe the customer workflow. [Documentation placement](docs/README.md) defines ownership and distribution.

## Development

Development uses Docker Engine/Compose and Nginx -> PHP-FPM -> Laravel. PHP, Composer, Node/pnpm, PostgreSQL and browser tooling run in project containers. Follow [environment setup](docs/runa/handbook/development/environment.md) and [editor/agent guidance](docs/runa/handbook/development/editor-and-agents.md). Use `php artisan runa:...` inside the configured development environment; `./bin/artisan` is the initial setup/recovery host transport.

The development environment retains Git hooks, local quality/security checks, Laravel Boost, Codex and optional Qdrant/embeddings knowledge retrieval. Application readiness is independent of AI services. Production excludes all AI and development tooling.

For optional local Auth/Permissions manual scenarios, account discovery and private
initial password setup, follow the [development corpus guide](docs/runa/handbook/data/seeding.md#explicit-module-development-contributors). No default password is distributed.

Core [Audit](docs/runa/handbook/modules/audit/README.md),
[Auth](docs/runa/handbook/modules/auth/README.md) and
[Permissions](docs/runa/handbook/modules/permissions/README.md) supply transactional
history, session/CSRF authentication, private account views and current action
grants. [Trusted initial administrator setup/recovery](docs/runa/handbook/modules/auth/policies-and-operators.md)
uses the actual protected system role. No administration UI is supplied.

## Application work

Application UI defaults to Polish with English support and light/dark themes; administration UI is English-only. Technical source, APIs, commands and repository documentation are English-only.

Use one active application roadmap under `docs/app/roadmaps`, a roadmap branch, Conventional Commits and local verification. See [roadmap workflow](docs/runa/handbook/workflows/roadmaps.md), [Git](docs/runa/handbook/workflows/git.md) and [quality](docs/runa/handbook/quality/README.md). This project has no CI pipeline.

[Application tests](docs/runa/handbook/testing/strategy.md) default to backend, frontend and tooling through `runa:test`. Native `php artisan test` remains PHP-only; focused Runa commands and opt-in browser tests are documented there. [Commands](docs/runa/handbook/tooling/commands.md) documents the public interface. [Production guidance](docs/runa/handbook/production/architecture.md) covers runtime isolation and operations.

Application identity/version in `src/app/application.json` is independent of the protected platform identity in `src/runa/platform.json`. Platform installation and updates are owner-operated external delivery procedures; customer repositories contain no owner installer/updater, platform test suites, maintenance reports or platform release history.

Runa views support [unchanged defaults, application composition and full frontend
replacement](docs/runa/handbook/development/frontend/customization.md). Customer
presentation stays in src/app through public contracts; platform defaults and
security/session orchestration stay in src/runa. The same rule applies to future
Runa module views.

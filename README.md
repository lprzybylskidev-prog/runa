# Runa

Runa is a Laravel-based business application designed to evolve under an explicit, roadmap-driven engineering workflow. This repository is intended to contain the application, development environment, production runtime definition, local quality tooling, AI/Codex guidance, and the project knowledge system in one versioned source of truth.

## Current state

<!-- runa:current-state -->

- Current version: `0.2.1`
- Active roadmap: `RM-2026-005`
- Production ever deployed: `false`

<!-- /runa:current-state -->

The released foundation provides a non-root devcontainer, Nginx/PHP-FPM/Laravel, PostgreSQL with an isolated guarded test database, and a responsive Vue SPA with Polish/English and light/dark/system preferences. Mailpit, a database queue worker, scheduler, local quality gates, Git hooks, Laravel Boost and development-only knowledge retrieval are implemented.

Production tooling provides isolated runtime images, deployment, guarded rollback, coordinated backups/restores, health and isolation checks, with local simulation acceptance. No business modules are included. Simulation acceptance does not constitute a real production deployment.

The standalone [GitHub bootstrap](docs/tooling/bootstrap.md) downloads a verified
release into a new directory before the application environment exists. Public
access needs no token; optional private credentials support future restricted access.
Use [unified installation](docs/tooling/installation.md) for dev/prod setup,
preserved application scaffolding and credentials, production HTTPS/SMTP and resume.

Use `./bin/artisan runa:dev --help` from the host or `php artisan help runa:dev` inside the prepared container for development lifecycle commands and [the command reference](docs/tooling/commands.md) for the implemented tooling. The state fields above are checked against VERSION, roadmap directories and docs/project/state.yml; release publication is verified separately.

## Core stack direction

- Laravel backend
- PostgreSQL database
- Vue 3 + TypeScript frontend
- Tailwind CSS
- Vite
- Nginx + PHP-FPM
- Docker Compose and VS Code Dev Containers
- Qdrant + local embeddings for development-only project knowledge retrieval
- Laravel Boost for version-aware Laravel MCP tooling
- Codex CLI inside the development container

Selected versions and compatibility research are in `docs/development/toolchain.md`. Localization provenance and safe snapshot updates are in `docs/development/localization.md`.

## Engineering model

Runa does not implement application changes without an active roadmap. One roadmap corresponds to one branch and one release. Planning may happen on `main`; implementation may not.

The repository intentionally has no CI or GitHub Actions quality pipeline. Quality enforcement is local, explicit, versioned, and driven by project scripts and Git hooks.

Read `AGENTS.md` first. It contains the compact operating contract and routes to the detailed documentation and local skills.

## Documentation map

- `AGENTS.md` — compact agent operating contract
- `docs/architecture/` — application, database, API, frontend, configuration, naming
- `docs/workflows/` — roadmap, Git, release workflow
- `docs/testing/` — backend/frontend/E2E testing strategy
- `docs/quality/` — local quality-gate model
- `docs/development/` — development environment requirements
- `docs/production/` — production runtime, deployment, rollback, backup
- `docs/ai/` — local skills, Laravel Boost, knowledge/RAG system
- `docs/security/` — security baseline
- `docs/data/` — seeding model
- `docs/maintenance/` — dependency/upstream registry, backlog, deprecations
- `docs/release/` — release-readiness guidance
- `docs/tooling/` — canonical command surface
- `docs/roadmaps/` — roadmap lifecycle and instances

## Language policy

Repository content is English-only: code, identifiers, technical logs, CLI output/help, documentation, scripts, configuration, database names, API contracts, commit messages, and agent guidance.

User-facing application UI supports Polish and English, with Polish as the default. The mandatory administration panel is English-only.

## Repository

Canonical remote:

`https://github.com/lprzybylskidev-prog/runa`

Git identity for project work:

- Name: `Łukasz Przybylski`
- Email: `lprzybylskidev@gmail.com`

The identity values above are repository metadata requirements. All repository prose remains English.

---
id: RM-2026-001
title: Initial development and production environment
status: active
branch: roadmap/RM-2026-001-development-environment
created_at: 2026-10-17
activated_at: 2026-10-17
closed_at: null
release_type: feature
impact_areas:
  - development
  - production
  - backend
  - frontend
  - database
  - tooling
  - security
  - ai_environment
  - documentation
---

# RM-2026-001 — Initial development and production environment

## Overview

### What

Build the initial Runa repository/runtime foundation from the audited planning package:

- exact pinned development runtime/toolchain,
- Laravel backend,
- PostgreSQL,
- Vue 3 + TypeScript + Tailwind frontend foundation,
- Docker Compose + VS Code devcontainer,
- Nginx/PHP-FPM,
- development mail/queue/scheduler,
- testing/static-analysis/formatting/linting,
- versioned local Git hooks,
- local quality runner,
- Laravel Boost + Codex CLI/MCP,
- development-only Qdrant + embeddings knowledge system,
- production containers,
- production installer/deploy/rollback/backup/self-test,
- project maintenance/release/version tooling.

This roadmap establishes the engineering environment. It does not implement business modules.

### Why

Runa is intended to be developed heavily with coding agents while remaining reproducible, reviewable, and maintainable by humans. The repository must enforce its workflow locally and keep project-specific knowledge close to the source.

### Scope

Included:

- development environment,
- initial clean Laravel/Vue application shell,
- project tooling,
- database/runtime infrastructure,
- quality/testing infrastructure,
- AI knowledge tooling for development,
- production runtime/deployment/backup baseline,
- validation of all accepted environment assumptions.

### Out of scope

- business feature modules,
- roles/permissions implementation,
- audit module,
- file/upload module,
- integrations module,
- business admin settings themselves,
- Redis/Horizon,
- Meilisearch,
- Kubernetes/orchestration beyond Compose,
- CI/GitHub Actions,
- public/external API versioning,
- production RAG/AI runtime,
- full design-system/product UI build.

## Specification

### Repository and Git

Canonical repository:

`https://github.com/lprzybylskidev-prog/runa`

Git identity:

- `Łukasz Przybylski`
- `lprzybylskidev@gmail.com`

Default branch: `main`.

This roadmap branch:

`roadmap/RM-2026-001-development-environment`

The pre-roadmap audit reconciles documents without commits. After explicit user readiness for activation, the agent may create exactly one planning-only baseline commit on `main` to establish empty-repository history, then create this branch and activate the roadmap. Runtime implementation begins only afterward. A request to audit does not authorize self-approval of this draft.

No GitHub Actions/CI files may be added.

### Dependency approval during this roadmap

Dependencies explicitly mandated by the accepted Runa specification count as already approved for this roadmap, subject to current compatibility/security review.

Examples include the required framework/tooling families:

- Laravel,
- Laravel Boost,
- Vue 3,
- TypeScript,
- Tailwind CSS,
- Vite,
- ESLint,
- Prettier,
- `vue-tsc`,
- Larastan/PHPStan,
- Pest if cleanly compatible (otherwise PHPUnit),
- Vitest,
- Vue Test Utils,
- Playwright.

Any additional Composer/pnpm package not explicitly mandated still requires explicit user approval.

The user approved a pinned Polish translation-data snapshot on 2026-10-17. Do not add `laravel-lang/lang` or `laravel-lang/common` as project dependencies. Import only required framework translation data with exact provenance, MIT attribution and deliberate diff-based maintenance.

Do not add convenience packages such as class-merging helpers, UI frameworks, state managers, validation frameworks, VueUse, or Tailwind plugins without a demonstrated need plus approval.

### Version/toolchain resolution

Before implementation depends on version-specific behavior:

- inspect current official supported versions,
- choose mutually compatible versions,
- pin exact runtime/tool versions,
- record them in repository configuration and maintenance registry.

PHP 8.4 is preferred if compatible with the selected Laravel/toolchain.

Node uses a current supported LTS and is pinned exactly.

pnpm is the standard package manager via Corepack/project pin.

Composer uses a controlled pinned major/minor line.

Never use mutable `latest` runtime/base image tags.

### Laravel application baseline

Install the selected Laravel version as a clean application.

Remove framework starter/demo/welcome artifacts that do not belong to Runa.

The user application is a Vue SPA, not a Blade-rendered product UI.

Development runtime is Nginx -> PHP-FPM -> Laravel. Do not use `php artisan serve`.

Implement only the minimum clean application shell/health behavior needed to validate the environment. Do not invent business modules.

Named routes are required.

### Configuration

Create/maintain:

- appropriate development environment example,
- testing environment example,
- production environment example,
- ignored local developer override path such as `.env.dev.local`.

No secrets in repository/images/docs.

No `env()` outside `config/*.php`.

Implement deterministic configuration validation/doctor checks for required, empty, typed/range, contradictory, and environment-unsafe values.

### PostgreSQL

PostgreSQL only.

Development includes:

- normal Runa database,
- isolated `runa_test` database.

Implement the schema conventions in `docs/architecture/database.md`, including:

- no Runa tables in `public`,
- `shared` for Laravel technical/global tables,
- explicit schemas,
- appropriate migration table placement/configuration,
- bigint identity internal PK model,
- UUIDv7 public IDs for externally durable business entities when such an entity first exists,
- no views/stored functions/procedures.

At this roadmap stage, create only technical tables required by Laravel/tooling. Do not invent business tables merely to demonstrate conventions.

Test guard must prevent destructive test commands against non-test databases.

### Frontend baseline

Create Vue 3 + TypeScript + Tailwind + Vite foundation.

TypeScript strict mode.

Polish user UI is default and English is secondary.

Admin panel convention is English-only, but the roadmap does not build the business admin module.

Establish PL/EN UI localization and the approved pinned snapshot of Polish Laravel validation data, using the native translator.

Support light/dark foundation and responsive application shell without designing business pages.

Use a single thin HTTP client/wrapper rather than scattered fetch calls.

Disable native form validation behavior where controlled custom UX requires it.

### Polish Laravel localization assessment and approved decision

Research performed on 2026-10-17 before point 5. The user subsequently approved the pinned translation-data snapshot and explicitly prohibited adding the lang/common packages. Implementation and update procedure: `docs/development/localization.md`.

| Candidate                                                 | Activity and compatibility                                                                                                                                                                                                                                                                   | Dependency footprint over the current 73-package Laravel lock                                                                                                                                                          |
| --------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `laravel-lang/lang` 15.37.4                               | Released 2026-10-09; PHP ^8.2; its required publisher 16.8.0 explicitly supports Laravel 13. Composer resolved successfully with PHP 8.4/Laravel 13.37.                                                                                                                                      | 86 packages total, 13 additional packages. Includes publisher/locales/config support.                                                                                                                                  |
| `laravel-lang/common` 6.8.0                               | Released 2026-03-17; resolves with the selected stack and pulls the same lang/publisher family.                                                                                                                                                                                              | 98 packages total, 25 additional packages. Adds actions, attributes, HTTP statuses, model/route/starter-kit/MoonShine translation integrations beyond this foundation's needs.                                         |
| Pinned translation-data snapshot from `Laravel-Lang/lang` | Uses the same reviewed 15.37.4 source revision with Laravel's native translation loader. Copy only required PL framework translation data, preserve attribution and verify keys/placeholders against installed Laravel's English catalog. Approved for the required validation catalog only. | No additional Composer/pnpm packages. The five upstream Polish source files total 83,853 bytes before selecting/converting the required catalog. No publisher runtime. Updates become explicit repository maintenance. |

Both packages and the translation source are MIT-licensed. The upstream license is copyright Laravel Lang Team 2013–2026 and requires preservation of its notice. Source revision for lang 15.37.4: `a3a9ef9b039d10db442bfb3bed0a97c9b50f30a7`.

The direct-package Packagist advisory records for lang/common/publisher were empty on the research date. A separate temporary lang lockfile was resolved and audited without installing its packages or changing the application lockfile: no vulnerability advisories, but Composer audit returned failure for abandoned `dragon-code/pretty-array` and `dragon-code/support`. The common dry-run graph reports the same abandoned packages. Absence of published advisories is not a guarantee of future safety. No scanner finding was suppressed.

The focused lang package provides framework translation catalogs plus publication/update support. The common meta-package overlaps future modules and third-party integrations that Runa has not selected; it provides no necessary frontend Vue localization capability. A source-data snapshot would cover framework messages using the built-in loader, independently of frontend UI translations and without changing English-only technical/backend defaults.

Approved decision: use a pinned translation-data snapshot with MIT attribution, exact provenance, key/placeholder checks and deliberate review through diffs. The update tool must never overwrite local corrections. No Composer localization packages are permitted.

Primary research sources:

- https://repo.packagist.org/p2/laravel-lang/lang.json
- https://repo.packagist.org/p2/laravel-lang/common.json
- https://repo.packagist.org/p2/laravel-lang/publisher.json
- https://packagist.org/api/security-advisories/?packages[]=laravel-lang/lang&packages[]=laravel-lang/publisher&packages[]=laravel-lang/common
- https://github.com/Laravel-Lang/lang/tree/a3a9ef9b039d10db442bfb3bed0a97c9b50f30a7/locales/pl
- https://github.com/Laravel-Lang/lang/blob/a3a9ef9b039d10db442bfb3bed0a97c9b50f30a7/LICENSE

### Formatting/lint/static analysis

Implement:

- Pint for PHP,
- Prettier for Vue/TS/JS/JSON/Markdown/CSS,
- ESLint with type-aware strict TypeScript rules and appropriate Vue rules,
- `vue-tsc`,
- Larastan/PHPStan at the highest analysis level supported by the installed stack; never lower it to accommodate code errors.

Canonical wrappers:

- `./bin/format`
- `./bin/lint`
- `./bin/typecheck`
- `./bin/analyse`

Unexpected warnings should not be normalized as success.

### Testing

Backend:

- Pest if cleanly supported, otherwise PHPUnit,
- Feature tests primary,
- isolated `runa_test`,
- parallel-safe test foundation.

Frontend:

- Vitest,
- Vue Test Utils.

Browser:

- Playwright,
- Chromium + Firefox installed,
- Chromium primary.

Do not add arbitrary coverage threshold.

Create minimal meaningful environment smoke/contract tests rather than fake business test volume.

### Development Compose/devcontainer

Sibling-container topology managed by host Docker Compose:

- app/devcontainer,
- Nginx,
- PostgreSQL,
- Qdrant,
- embeddings,
- Mailpit,
- queue worker,
- scheduler.

VS Code attaches to the app devcontainer.

No Docker-in-Docker.

All services have meaningful health checks.

Devcontainer bootstrap is idempotent.

Rebuild/reopen does not destroy normal development data or run `migrate:fresh`.

GPU embeddings support NVIDIA/CUDA where available and fall back to CPU.

Persist embedding model cache locally.

### PHP extensions

Baseline development and production runtime capability as applicable:

- `pdo_pgsql`
- `mbstring`
- `intl`
- `bcmath`
- `opcache`
- `pcntl`
- `zip`
- `curl`
- `xml`

Do not install GD/Imagick until a real feature needs one.

Xdebug:

- development-only,
- off by default,
- `./bin/xdebug on|off|status`,
- VS Code debug configuration ready.

### VS Code

Automatically install/configure through devcontainer customizations:

- Intelephense,
- PHP Namespace Resolver,
- Vue Official,
- Tailwind CSS IntelliSense,
- ESLint,
- Prettier,
- EditorConfig,
- Docker,
- GitLens,
- `cweijan.vscode-database-client2`.

Do not auto-install the Codex/OpenAI VS Code extension.

The user installs the Codex extension manually after opening the container.

Preconfigure Database Client for dev PostgreSQL if safely possible without secrets; otherwise provide exact manual steps.

### Resource profiles

Implement:

- low,
- standard,
- high,
- custom.

Default: standard.

Local ignored override/config, no editing Compose YAML to switch profile.

Initial high profile values:

```dotenv
DEV_CPU_LIMIT=20
DEV_MEMORY_LIMIT=16G
TEST_WORKERS=12
PLAYWRIGHT_WORKERS=4
```

Implement:

`./bin/dev profile`

Doctor shows effective profile/resources.

### Laravel Boost and Codex CLI

Install Laravel Boost as a development dependency.

Install/configure Codex CLI in devcontainer.

Register Boost MCP using the current official supported mechanism.

Follow `docs/ai/laravel-boost.md` for the canonical setup-safety procedure: protect Runa guidance, remove Boost-generated skills immediately and preserve working MCP. Acceptance requires an actual version-aware call from the Codex agent session after cleanup, with results matching installed packages.

### Knowledge/RAG stack

Implement the requirements from `docs/ai/knowledge-system.md`.

Services:

- Qdrant,
- local embeddings with CUDA/CPU fallback.

Implement:

- provider abstraction/capabilities,
- structural/document-aware chunking,
- metadata,
- hybrid dense + lexical/sparse retrieval,
- incremental indexing,
- staleness detection,
- deterministic chunk/document identity,
- allowlisted sources,
- deleted/orphan cleanup,
- concise search output,
- `--explain`,
- golden retrieval evals,
- end-to-end doctor.

Canonical command:

`./bin/knowledge search|index|status|rebuild|stale|doctor|eval`

Initial corpus includes durable docs/skills/roadmaps/selected procedural config, not raw chats and not whole source code.

Knowledge changes do not auto-reindex in pre-commit. Stale index blocks/instructs explicit indexing.

### Git hooks

Create versioned `.githooks/`.

Configure `core.hooksPath .githooks`.

No Husky.

`pre-commit` remains fast:

- staged Gitleaks,
- format changed files,
- quick lint changed files,
- knowledge staleness,
- repository/basic secret/large-binary sanity.

`commit-msg`:

- Conventional Commits,
- roadmap ID scope when active.

No full test/static/security suite per commit.

### Local quality runner

Implement `docs/quality/gates.yml`.

Implement:

- `./bin/quality show`
- `./bin/quality roadmap`
- `./bin/quality final`
- `./bin/quality status`

Latest run only under gitignored `.runtime/quality`.

Independent steps may run parallel with separate logs.

Shared mutable DB/environment/production steps are sequenced safely.

No arbitrary automatic timeouts.

While running, status prints last 30 lines of each active log then `Process is still running.`

After finish/interruption, status prints last 30 lines and full latest-run log paths, then the overall status.

Interrupted run is marked interrupted. Final verification reruns from the beginning.

### Debug/scratch hygiene

During roadmap work, temporary debug output and visible scratch scripts are allowed when useful.

General scratch scripts go under `tools/scratch/`; browser exploration uses `.playwright/scratch/`. Both source directories remain visible to Git.

Playwright generated artifacts go under gitignored `artifacts/playwright/`.

Final gate rejects accidental debug residue.

TODO/FIXME/HACK must be removed or linked to a real maintenance item/future roadmap before closure; do not create fake backlog just to pass.

### Security tooling

Implement/use:

- Gitleaks,
- Trivy,
- `composer audit`,
- `pnpm audit`.

Trivy should inspect relevant repository/container/image concerns including final built production image.

Do not add redundant scanner tooling unless a real gap exists.

### Seeding framework

Implement the production/development/occasional model from `docs/data/seeding.md`.

This roadmap only needs technical/reference seed infrastructure and small demo foundation where required for environment/browser acceptance. Do not invent business data models.

Implement:

- semantic seed responsibility,
- explicit dependency graph/topological sorting/cycle detection,
- deterministic defaults,
- per-seeder random mode/seed concept,
- environment separation,
- wrapper commands.

### Production containers

Baseline sibling services:

- app/PHP-FPM,
- Nginx,
- PostgreSQL,
- queue worker,
- scheduler.

Use database queue driver initially.

No Redis/Horizon.

No Qdrant/embeddings/knowledge service.

No development tooling in runtime image.

Build production image with multi-stage strategy where appropriate and non-root runtime where practical.

Control logs/rotation and persistent storage.

Implement minimal safe health endpoint and operator/deep doctor separation.

### Production isolation

Enforce all five layers:

1. no AI services in production Compose,
2. production env disables knowledge CLI,
3. production image excludes AI/dev material where not required,
4. production sparse checkout excludes development AI/docs/services as designed,
5. production self-test proves no AI ports/services/volumes/knowledge CLI/directories.

### Production installer/deploy/rollback

Implement interactive production installer.

Implement `./bin/deploy` with exact-version deployment, locked dependencies, frontend build, migrations, production seeders, Laravel optimization, service update/restart, health/self-test.

Implement `./bin/deploy rollback`.

Rollback must never imply unsafe automatic database reversal.

Record previous deploy version/commit/image identity.

### Backup

Implement:

- `./bin/backup create`
- `./bin/backup verify`
- `./bin/backup restore`

Backup PostgreSQL and application persistent storage.

Configurable destination/retention.

Verification includes meaningful restore validation; do not merely check that a file exists.

Do not force a cloud provider.

### Production deployed state

Honor `docs/project/state.yml`.

Do not set `production.ever_deployed: true` during environment simulation.

Implement `./bin/project mark-production-deployed` for the future first real production deployment state transition.

The value is one-way false -> true.

### Release/version tooling

Implement/validate:

- `VERSION`,
- generated `CHANGELOG.md`,
- roadmap-driven version computation,
- annotated tags,
- GitHub Release creation workflow,
- maintenance registry/backlog/deprecation validation.

This roadmap is `feature`. From initial `0.0.0`, normal version semantics produce `0.1.0` unless the user explicitly changes the version decision before closure.

### Decisions

- 2026-10-17 — Development startup uses an idempotent one-shot setup dependency with a fingerprint-based fast path before HTTP/background services; runtime health uses instance-bound heartbeats plus database readiness. Development captures SMTP in persistent Mailpit; production uses configurable TLS SMTP without Mailpit.
- 2026-10-17 — Toolchain pins and compatibility assessment are recorded in `docs/development/toolchain.md`; TypeScript 6.0.3 is selected to satisfy the supported typescript-eslint range.

- 2026-10-17 — Runa uses local quality/hook enforcement; no CI/GitHub Actions baseline.
- 2026-10-17 — First environment build is itself the first roadmap, rather than an out-of-band bootstrap implementation.
- 2026-10-17 — The empty repository may receive one planning-only baseline commit before RM-2026-001 branch creation.
- 2026-10-17 — Laravel Boost is retained for MCP/version-aware Laravel tooling, but Boost-generated skills are removed in favor of Runa's adapted local skills.
- 2026-10-17 — Codex/OpenAI VS Code extension is installed manually by the user, not automatically by devcontainer configuration.
- 2026-10-17 — AI/RAG is development-only and absent from production at service, config, image/checkout, CLI, and self-test layers.
- 2026-10-17 — Qdrant and the embedding service are sibling development containers; no Docker-in-Docker.
- 2026-10-17 — Database queue driver is the initial queue mechanism; Redis/Horizon is deferred.
- 2026-10-17 — PostgreSQL is the only database engine.
- 2026-10-17 — Polish user UI is default, English secondary; administration panel remains English-only.
- 2026-10-17 — The user approved the Laravel-Lang/lang 15.37.4 translation-data snapshot at commit `a3a9ef9b039d10db442bfb3bed0a97c9b50f30a7`, limited to required Polish framework messages, with MIT attribution and controlled diff review. No lang/common Composer dependencies.

## Implementation plan

- [x] 1. **Activate and validate the roadmap baseline**
  - Prerequisites: Explicit user activation readiness, audited planning baseline and roadmap branch.
  - Confirm branch/remote/Git identity and that the audited package is clean.
  - Confirm this roadmap is the only active roadmap after activation.
  - Validate docs/skills source metadata and ensure no transcript/audit prompt is staged.
  - Resolve current supported framework/tool/runtime versions from official sources and record pinned selections plus dependency assessment.
  - Update maintenance registry with selected versions/review dates.

- [x] 2. **Build the base development container toolchain**
  - Prerequisites: Point 1.
  - Create host Compose/devcontainer skeleton without Docker-in-Docker.
  - Pin PHP/Composer/Node/pnpm/base image versions.
  - Add required PHP extensions and matching production base-image definitions for application runtime parity.
  - Configure non-root development user/UID-GID behavior.
  - Configure Corepack/pnpm and base CLI tooling.
  - Establish resource profile configuration and `./bin/dev profile`.
  - Ensure build/rebuild is deterministic/idempotent.
  - Verified: real image build and cached rebuild, non-root execution, required PHP extensions/tool versions, FastCGI health, resolved preset/custom limits and cache persistence through container recreation. VS Code GUI reopen remains part of first-run acceptance.

- [x] 3. **Install and clean the Laravel application**
  - Prerequisites: Point 2.
  - Install the pinned Laravel version inside the intended environment. Generate into an isolated temporary directory if the framework installer requires an empty directory; merge deliberately without overwriting audited docs/skills/VERSION.
  - Remove starter/welcome/demo artifacts.
  - Configure Nginx/PHP-FPM application runtime and matching production definitions; never use `artisan serve`.
  - Establish minimal named health/application route(s) required for environment validation.
  - Implement configuration/env templates and config validation foundation.
  - Keep repository prose/code English-only.
  - Verified: locked dependency installation/audit, PHP syntax, named minimal API route via Nginx/PHP-FPM, safe request IDs, configuration rejection cases, idempotent setup/key preservation and a built production PHP foundation without Composer/Node/compiler/Git/AI material.

- [x] 4. **Configure PostgreSQL and testing database foundation**
  - Prerequisites: Point 3.
  - Add PostgreSQL sibling service and meaningful health check, with the corresponding production definition.
  - Create normal development DB plus isolated `runa_test`.
  - Configure explicit schema conventions (`shared`, no Runa app tables in `public`).
  - Place Laravel technical DB objects consistently. Remove unused generated auth/user/session structures or defer them to their owning module; do not invent an auth module to retain starter tables. Verify bigint identity in real DDL.
  - Add destructive-test environment/database guards.
  - Verify migrations/bootstrap work from a clean state without creating fake business tables.
  - Verified: development/test/temporary parallel database migrations, bigint ALWAYS identity in real DDL, empty public schemas, non-superuser role separation, destructive-command regression checks, authenticated TCP health and idempotent credential/setup behavior. Full parallel runner/storage isolation follows in point 7.

- [x] 5. **Build the Vue, TypeScript, Tailwind, localization, and HTTP foundation**
  - Prerequisites: Points 3–4; user-approved pinned localization-data snapshot.
  - Install/configure Vue 3, TS strict, Vite, Tailwind using current-version syntax.
  - Establish responsive/light-dark clean application shell.
  - Establish user-facing PL-default/EN-secondary localization infrastructure.
  - Import only required Polish framework messages from the approved snapshot, record exact provenance/license/maintenance metadata and provide a review-only update tool that preserves local changes.
  - Establish admin-panel English-only convention without implementing business admin features.
  - Create the thin HTTP client/wrapper and minimal API connectivity smoke.
  - Configure controlled form/browser-validation baseline.
  - Add real component/page/form/table catalogue entries only for implemented reusable patterns; do not fabricate business screens.
  - Verified: 137 native Polish validation messages and interpolation, offline provenance/license/key checks and review-only candidate generation; strict Vue/TS build, frozen installation and dependency audits; rendered PL/EN and light/dark/system at desktop/tablet/mobile, 200% text, keyboard skip, storage denial, failure/retry and Firefox smoke; Vite proxy/HMR page; locked PHP/Nginx production frontend builds and runtime-data isolation. Browser provisioning becomes reproducible in point 7.

- [x] 6. **Complete the development Compose services**
  - Prerequisites: Points 2–5.
  - Add/finish Nginx, Mailpit, queue worker, scheduler sibling services and production runtime counterparts (outbound SMTP, no Mailpit).
  - Add meaningful health checks and correct dependency readiness behavior.
  - Ensure persistent dev data survives rebuild/reopen.
  - Ensure normal reopen does not run destructive bootstrap.
  - Verify container DNS/ports/mounts/permissions using resolved Compose config.
  - Verified: separate empty checkout/project startup, authenticated DB and Mailpit readiness, real queued email delivery, actual scheduled pulse and instance-aware health rejection tests, full container recreation with database/storage/mail/config persistence, cached setup fast path, profile caps/DNS/loopback ports/non-root mounts, matching PHP build/UID definitions and standalone sibling build. Production service isolation, TLS SMTP validation and scheduler CLI/image storage hygiene pass; complete production deployment remains points 13–14. No new migrations.

- [x] 7. **Configure formatting, lint, static analysis, and automated tests**
  - Prerequisites: Points 4–6.
  - Pint, Prettier, ESLint, `vue-tsc`, Larastan/PHPStan.
  - Pest or PHPUnit according to compatibility decision.
  - Vitest + Vue Test Utils.
  - Playwright Chromium + Firefox.
  - Parallel-safe backend test foundation.
  - Implement `./bin/format`, `./bin/lint`, `./bin/typecheck`, `./bin/analyse`, `./bin/test`.
  - Add small meaningful environment/application smoke tests.
  - Verified: pinned Pint/Prettier/ESLint/vue-tsc and Larastan/PHPStan `max`; Pest 5 / Laravel plugin compatibility; 11 backend tests (29 assertions) serially and on four isolated PostgreSQL/storage workers; 8 Vitest/Vue Test Utils tests in Chromium; 3 critical E2E projects (Chromium, Firefox, mobile Chromium) through isolated testing Nginx/PHP-FPM. Checkout-wide suite lock rejection, development health, production frontend build, locale snapshot integrity, Composer/pnpm audits and resolved testing-profile isolation pass. Browsers are baked only into the development stage. No new migrations or runtime dependencies.

- [x] 8. **Configure Xdebug, VS Code, Codex CLI, and Laravel Boost**
  - Prerequisites: Points 3 and 7; user/editor action if required for actual MCP discovery.
  - Xdebug dev-only/off by default with wrapper and VS Code config.
  - Configure automatic installation of required VS Code extensions except the manual Codex/OpenAI extension; verify format-on-save and debug configuration.
  - Configure/pre-document Database Client connection.
  - Install Codex CLI in devcontainer.
  - Install Laravel Boost using current official mechanism.
  - Protect/checksum Runa skills before Boost setup.
  - Remove Boost-generated skills/instructions immediately after setup and verify local Runa skills are intact.
  - Register Boost MCP.
  - Execute a real Boost version-aware tool call from the actual Codex agent session and verify the result. If session reload/manual extension setup is required, keep acceptance pending until the agent can really invoke MCP.
  - Implementation status (2026-10-17): Xdebug 3.5.3, VS Code customizations/debug configuration, Codex CLI 0.163.0, Boost 2.10.3 and host/container MCP launcher are implemented. Setup registers the container MCP through the official Codex CLI and remains idempotent. Runa instruction/skill checksums remained identical; MCP-only installation generated no skills or guidelines, and the unrelated Copilot MCP file was removed.
  - Verified: development image build; Codex version and MCP registration inside/ outside the container; default-off/on/off Xdebug; actual PHP-FPM HTTP trigger, DBGp source breakpoint, resume and HTTP 200; Composer and isolated Codex npm audits; PHPStan highest level; 11 backend tests/29 assertions; canonical formatting checks. Transport-only MCP checks returned Laravel 13.37.0 application information and Laravel 13.x documentation.
  - Real-session Boost acceptance passed (2026-10-17): after reopening inside the devcontainer, the actual Codex agent invoked Boost `application-info` and `search-docs` successfully. Inspection returned PHP 8.4, Laravel 13.37.0, PostgreSQL and Boost 2.10.3; the documentation search returned `laravel/framework@13.x` health-route guidance, matching the installed framework. No setup regeneration or runtime configuration change was needed.
  - Manual editor acceptance passed (2026-10-17): the user confirmed format-on-save and the VS Code breakpoint/resume HTTP debugging flow inside the devcontainer. Xdebug was returned to `mode=off` and its status verified afterward. Point 8 is complete. Setup and acceptance instructions: `docs/development/editor-and-agents.md`.

- [x] 9. **Build Qdrant, embeddings, and knowledge ingestion**
  - Prerequisites: Points 2 and 6.
  - Add sibling Qdrant and local embedding services with health checks and automatic devcontainer startup. Select an English technical-documentation model after license/security/compatibility assessment; register the actual model/runtime dependencies.
  - Implement CUDA path and CPU fallback.
  - Persist model cache.
  - Implement provider capability abstraction.
  - Implement allowlisted structural ingestion, deterministic IDs, metadata, incremental hash state, deletion/orphan cleanup, and staleness tracking.
  - Ensure secrets/raw chats/vendor/runtime artifacts are excluded.
  - Completed (2026-10-17): Qdrant 1.19.2 and TEI 1.9.4 CPU/CUDA siblings with health checks, automatic Compose/devcontainer startup, persistent Qdrant/model-cache volumes and explicit CPU fallback. Selected MIT BGE small English v1.5 at `5c38ec7c405ec4b44b94cc5a9bb96e735b38267a` (384 dimensions/512 tokens). No Composer/pnpm dependency was added.
  - Implemented allowlisted tracked-source ingestion, structural/model-token-aware chunks, deterministic IDs, source/Git/model metadata, incremental hashes/vector reuse, stale/parser/branch detection, deleted/orphan cleanup and recoverable interrupted-write state. Commands: `./bin/knowledge index|status|stale`; retrieval/rebuild/doctor/eval remain point 10.
  - Verified: 11 offline contracts; real CPU/CUDA cosine >0.99999; token-budget rejection/fallback; live initial/no-op/add/edit/delete/orphan/recovery/branch acceptance in a disposable collection; persisted corpus/cache across service recreation; full setup and fingerprint fast path. No unchanged document chunks were re-embedded.
  - Security/runtime acceptance: final pinned Ubuntu 24.04 CPU/Qdrant runtime images retain exact upstream binaries and omit unused dashboard/development dependencies. CUDA uses the patched upstream image; 4 GiB RAM/2048-token batches resolved startup OOM. Trivy 0.76.0 reports zero HIGH/CRITICAL vulnerabilities in all three final images; host confirmed those exact image IDs healthy with zero restarts. No suppressions. Setup, provenance, limits and evidence: `docs/development/knowledge.md`.

- [x] 10. **Implement hybrid knowledge retrieval, evaluation, and doctor**
  - Prerequisites: Point 9.
  - Dense + lexical/sparse retrieval with simple configurable fusion.
  - Metadata filtering/branch-roadmap preference.
  - Concise search output and `--explain`.
  - Golden retrieval query dataset and `./bin/knowledge eval`.
  - `./bin/knowledge search|index|status|rebuild|stale|doctor|eval`.
  - End-to-end smoke retrieval, dimensions/model/schema/orphan/missing/stale checks.
  - Verify source-file grounding workflow.
  - Completed (2026-10-17): all seven knowledge commands work, including explicit preflight-validated rebuild. Dense Qdrant query + standard-library BM25 use shared metadata/current-branch filters and configurable weighted RRF, source diversity and active/historical preference. No additional dependencies or embedding representations.
  - Added eight fixed golden queries, concise source/section excerpts, diagnostic ranks/scores/hash/model metadata, and doctor checks for model/schema/freshness/missing/orphan states, corpus statistics and end-to-end retrieval. Stale search/eval fail with explicit indexing instructions; original-source grounding was verified against production isolation policy.
  - Verified: 11 offline ingestion contracts, three real-service retrieval contracts in disposable collections (filters/exact identifiers/diversity/stale/branch/missing/mixed-model/rebuild), CPU/CUDA live ingestion acceptance and 8/8 golden queries at top five. Warm per-query retrieval was approximately 10–50 ms on the current corpus; doctor passed with zero missing/orphan/invalid-metadata chunks. Updated knowledge command/operator and RAG skill guidance.

- [x] 11. **Implement seeding, Git hooks, maintenance validation, and project command contracts**
  - Prerequisites: Points 7–10.
  - Production/development/occasional seeder graph and wrappers.
  - `.githooks/` plus `core.hooksPath`.
  - Fast pre-commit and Conventional/RM-aware commit-msg.
  - Verify hook rejection with temporary safe fixtures and preserve intentional partial staging.
  - Stale-knowledge check.
  - Maintenance/deprecation/TODO consistency checks.
  - English `--help` and meaningful exit codes for durable commands.
  - Implement lifecycle/passthrough wrappers (`dev`, `prod`, `artisan`, `composer`, `pnpm`, `logs`) and global/focused doctor; keep host-only Docker operations explicit without Docker-socket access. Production operations are completed with their later implementation points.
  - Update `docs/tooling/commands.md` to exact implemented surface.
  - Completed (2026-10-17): shared validated seeder graph with fixed category discovery/explicit registration, deterministic topological ordering, missing/cycle/category checks, independent native random contexts, Laravel DatabaseSeeder dispatch and seed CLI. Empty initial graph creates no artificial domain/demo records; production rejects development seeders directly and through dependencies.
  - Installed versioned pre-commit/commit-msg hooks and pinned checksum-verified Gitleaks 8.30.1. Checks inspect staged blobs, preserve partial staging, format only safe fully staged files, run quick lint/syntax checks and block stale knowledge without automatic indexing. Installed-hook rejection fixtures use a disposable repository and create no commits.
  - Added safe application passthrough/lifecycle/log wrappers, global/focused doctor, package-to-maintenance-version mappings and maintenance/deprecation/comment consistency checks. Production lifecycle/release diagnostics explicitly fail as pending until their later implementation; host-only Docker commands remain outside the devcontainer.
  - Verified: 16 backend tests/55 assertions in four workers, maximum-level static analysis, installed-hook contracts, three wrapper-context/argument tests, three maintenance rejection tests, configuration/Boost/help/docs checks and live development diagnostics. Host forwarding is simulated without Docker access; full host lifecycle acceptance remains point 15. No Composer/pnpm dependencies, business tables or CI were added.

- [ ] 12. **Implement the local quality runner**
  - Prerequisites: Point 11; production-dependent blocks become runnable after points 13–14.
  - Implement `docs/quality/gates.yml` execution.
  - `show`, `roadmap`, `final`, `status`, including explicit `--background` support.
  - Implement deterministic dependency selection/deduplication and one-active-run protection; finish remaining production doctor blocks in points 13–14 before final verification.
  - Latest-run-only state.
  - Parallel independent logs, safe sequencing for shared state.
  - Last-30-line status behavior.
  - No arbitrary timeouts.
  - Interrupted-state handling.
  - Debug residue/scratch cleanup checks.
  - Verify no CI/GitHub Actions were introduced.

- [ ] 13. **Build production images and production Compose**
  - Prerequisites: Points 2–7 and 11–12; earlier paired production definitions.
  - Multi-stage/minimal production runtime as appropriate.
  - Nginx, PHP-FPM/app, PostgreSQL, queue worker, scheduler.
  - Database queue driver.
  - Persistent storage/static handling.
  - Safe liveness/readiness/health behavior.
  - Non-root/minimal privileges where practical.
  - Logging/rotation.
  - No development-only services/tooling.

- [ ] 14. **Implement production installer, deployment, rollback, backups, and isolation self-test**
  - Prerequisites: Point 13; shared tooling from points 11–12.
  - `./bin/install-prod` interactive production installer/requirements/config.
  - Complete production wrappers and doctor/gate commands declared earlier, including real agent security-review evidence.
  - Sparse checkout design.
  - `./bin/deploy` exact-version flow.
  - Production seeders.
  - `./bin/deploy rollback` with migration safety.
  - `./bin/backup create|verify|restore`.
  - `./bin/project mark-production-deployed`.
  - Production AI/RAG isolation at all five required layers.
  - Trivy final runtime-image scan.
  - Production self-test.

- [ ] 15. **Perform first-run acceptance from a fresh developer perspective**
  - Prerequisites: Points 1–14; host terminal and manual editor evidence where needed.
  - Validate clone/reopen/rebuild behavior from a clean state as far as feasible.
  - Validate all development services and health checks.
  - Validate Laravel via Nginx/PHP-FPM.
  - Validate PostgreSQL and test DB guards.
  - Validate frontend build/render, TS/lint/static analysis/tests.
  - Validate queue/scheduler/Mailpit.
  - Validate Qdrant/embeddings on GPU when available and CPU fallback.
  - Validate knowledge doctor/evals/search/explain.
  - Validate Codex CLI + real Boost MCP call after Boost-generated skills are removed.
  - Provide exact manual VS Code steps for the user to install/test the Codex extension and Database Client if automatic verification is impossible.
  - Build/start/test the production stack.
  - Validate production deploy/rollback/backup/restore behavior in a safe local production simulation where feasible.
  - Prove production has no AI/RAG runtime.
  - Fix acceptance findings through inserted roadmap fix points rather than hiding them inside this checklist item.

- [ ] 16. **Full final verification, release finalization, merge, and report**
  - Prerequisites: Point 15 and every inserted fix point; all applicable commands implemented.
  - Run `./bin/quality final` with all impact areas.
  - If a failure needs work, insert a fix point immediately before this point and rerun final verification from the beginning.
  - Resolve all hard failures.
  - Justify any allowed warnings.
  - Confirm no scratch/debug residue or temporary audit material remains.
  - Confirm docs/skills/tooling help match implementation, all declared gate commands exist, and the maintenance registry covers every actual external component.
  - Review changed Laravel code for unnecessary complexity while preserving behavior; review the complete durable documentation for drift without refactoring unrelated application areas.
  - Run focused release-metadata validation after version/changelog/closure edits. Substantive post-gate changes require the full final gate again.
  - Compute expected feature version (`0.1.0` from initial `0.0.0` unless explicitly changed).
  - Update `VERSION` and generated `CHANGELOG.md`.
  - Move this roadmap from `active/` to `closed/` and set close metadata on the roadmap branch.
  - Commit final release metadata.
  - Merge to `main` with a merge commit preserving roadmap history.
  - Run short main sanity check.
  - Create annotated tag `v0.1.0` (or the user-approved computed version).
  - Push and publish GitHub Release from the same roadmap facts.
  - Delete roadmap branch.
  - Produce the concise final roadmap report.

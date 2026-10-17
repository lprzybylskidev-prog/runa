# Initial toolchain selections

Reviewed on 2026-10-17 for RM-2026-001. These are implementation selections, not permission to upgrade dependencies automatically. Composer and pnpm lockfiles will record the resolved transitive trees when the relevant roadmap points install them.

| Component | Selected version | Primary source |
|---|---|---|
| PHP | 8.4.26 | https://www.php.net/releases/index.php?json&version=8.4 |
| PHP base | `php:8.4.26-fpm-bookworm` | https://hub.docker.com/_/php |
| Composer | 2.10.3 | https://getcomposer.org/versions |
| Node.js LTS | 24.22.0 | https://nodejs.org/dist/index.json |
| Node base | `node:24.22.0-bookworm-slim` | https://hub.docker.com/_/node |
| Corepack | 0.36.0 | https://registry.npmjs.org/corepack/0.36.0 |
| pnpm | 12.14.0 | https://registry.npmjs.org/pnpm/12.14.0 |
| Laravel | 13.37.0 | https://repo.packagist.org/p2/laravel/framework.json |
| PostgreSQL | 18.6 | https://www.postgresql.org/versions.json |
| Nginx | 1.31.7 | https://github.com/nginx/nginx/releases/tag/release-1.31.7 |
| Qdrant | 1.19.2 | https://github.com/qdrant/qdrant/releases/tag/v1.19.2 |
| Mailpit | 1.31.6 | https://github.com/axllent/mailpit/releases/tag/v1.31.6 |
| Vue | 3.5.44 | https://registry.npmjs.org/vue/3.5.44 |
| TypeScript | 6.0.3 | https://registry.npmjs.org/typescript/6.0.3 |
| Tailwind CSS / Vite adapter | 4.3.3 | https://registry.npmjs.org/@tailwindcss/vite/4.3.3 |
| Vite | 8.3.4 | https://registry.npmjs.org/vite/8.3.4 |
| Vue Vite adapter | 6.0.10 | https://registry.npmjs.org/@vitejs/plugin-vue/6.0.10 |
| ESLint recommended JS adapter | 10.0.1 | https://registry.npmjs.org/@eslint/js/10.0.1 |
| Vue ESLint parser | 10.3.0 | https://registry.npmjs.org/vue-eslint-parser/10.3.0 |
| Vitest Playwright provider | 5.0.5 | https://registry.npmjs.org/@vitest/browser-playwright/5.0.5 |
| ESLint | 10.13.0 | https://registry.npmjs.org/eslint/10.13.0 |
| TypeScript ESLint | 8.71.2 | https://registry.npmjs.org/typescript-eslint/8.71.2 |
| Vue ESLint | 10.11.1 | https://registry.npmjs.org/eslint-plugin-vue/10.11.1 |
| Prettier | 3.9.10 | https://registry.npmjs.org/prettier/3.9.10 |
| vue-tsc | 3.3.13 | https://registry.npmjs.org/vue-tsc/3.3.13 |
| Pint | 1.32.1 | https://repo.packagist.org/p2/laravel/pint.json |
| Larastan / PHPStan | 3.13.0 / 2.3.1 | https://repo.packagist.org/p2/larastan/larastan.json |
| Pest / Laravel plugin | 5.3.2 / 5.0.1 | https://repo.packagist.org/p2/pestphp/pest-plugin-laravel.json |
| Vitest | 5.0.5 | https://registry.npmjs.org/vitest/5.0.5 |
| Vue Test Utils | 2.5.1 | https://registry.npmjs.org/@vue/test-utils/2.5.1 |
| Playwright | 1.64.1 | https://registry.npmjs.org/@playwright/test/1.64.1 |
| Laravel Boost | 2.10.3 | https://repo.packagist.org/p2/laravel/boost.json |
| Gitleaks | 8.30.1 | https://github.com/gitleaks/gitleaks/releases/tag/v8.30.1 |
| Trivy | 0.76.0 | https://github.com/aquasecurity/trivy/releases/tag/v0.76.0 |

## Compatibility and dependency assessment

Laravel 13 requires PHP 8.3 or later; PHP 8.4.26 is a supported security release and meets the preferred project line. Pest 5 requires PHP 8.4 and its Laravel plugin requires Laravel 13.23 or later; this selection satisfies both. Larastan 3.13 explicitly supports Laravel 13 and PHPStan 2.3. Boost 2.10 supports Laravel 13. These packages have distinct roles: testing, analysis and development MCP. They do not belong in production dependencies.

Node 24.22 is the current LTS entry returned by the Node release index and satisfies Corepack, Vite, ESLint and Vitest engine requirements. TypeScript 7.0.2 was deliberately not selected: typescript-eslint 8.71.2 supports TypeScript below 6.1. TypeScript 6.0.3 remains inside that range. Vue 3.5 is the stable release; 3.6 prereleases are excluded. Tailwind's Vite adapter and the Vue Vite adapter explicitly support Vite 8.

Package registry metadata identifies MIT licenses for the selected Composer tools, Vue, Tailwind, Vite, ESLint, Prettier and test utilities, and Apache-2.0 for TypeScript and Playwright. These are actively released upstream components with complementary required responsibilities. Adapters and parsers are the integration pieces of the mandated tool families, not an additional UI/state/validation framework. The initial dependency tree will necessarily include framework and testing transitive packages; inspect actual lockfile sizes and audit results during installation rather than inventing counts before resolution.

PHP, Laravel and JavaScript tooling have published security histories; current versions alone do not prove absence of vulnerabilities. Installation must run Composer/pnpm audits against the actual lockfiles, and final image/filesystem checks must use Trivy. Findings block affected implementation until resolved or handled according to the security policy. This document records compatibility research, not a clean security scan of an unbuilt environment.

The embedding model/runtime remains unselected; evaluate the model at point 9. The user approved vendored Polish Laravel validation data from Laravel-Lang/lang 15.37.4 at commit `a3a9ef9b039d10db442bfb3bed0a97c9b50f30a7`, with no Composer localization dependency; see `localization.md`. Codex CLI version/configuration is resolved from official OpenAI documentation at point 8.

Point 7 installed the selected formatting/analysis/test packages. The matching Vitest browser provider, ESLint recommended-JS config and Vue parser are MIT-licensed integration components with compatible peer ranges, verified against their exact npm version metadata on 2026-10-17. The provider requires matching Vitest 5.0.5; its Playwright peer resolves to the existing 1.64.1. No additional application runtime dependency was added. `pnpm-workspace.yaml` records only the deliberately reviewed exact ESLint release age exception emitted by pnpm during this installation; it does not permit unbounded version exceptions. Future dependency changes require deliberate roadmap review.

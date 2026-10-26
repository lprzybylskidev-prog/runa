# Current toolchain selections

The initial baseline was reviewed on 2026-10-17 for RM-2026-001. The temporary
PHP rollback in RM-2026-011 is retired on 2026-10-26: PHP 8.4.27 is selected in
development/production/management. The PostgreSQL derivative updates curl/Brotli
and builds exact gosu 1.20 source using Go 1.27.3; Go exists only in the image
build stage. Other selections retain their registry review dates. These selections
do not authorize automatic dependency upgrades; Composer/pnpm locks remain unchanged.

| Component                     | Selected version                                  | Primary source                                                               |
| ----------------------------- | ------------------------------------------------- | ---------------------------------------------------------------------------- |
| PHP                           | 8.4.27                                            | https://www.php.net/releases/index.php?json&version=8.4                      |
| PHP base                      | `php:8.4.27-fpm-bookworm`                         | https://hub.docker.com/_/php                                                 |
| Composer                      | 2.10.3                                            | https://getcomposer.org/versions                                             |
| Node.js LTS                   | 24.22.0                                           | https://nodejs.org/dist/index.json                                           |
| Node base                     | `node:24.22.0-bookworm-slim`                      | https://hub.docker.com/_/node                                                |
| Corepack                      | 0.36.0                                            | https://registry.npmjs.org/corepack/0.36.0                                   |
| pnpm                          | 12.14.0                                           | https://registry.npmjs.org/pnpm/12.14.0                                      |
| Laravel                       | 13.37.0                                           | https://repo.packagist.org/p2/laravel/framework.json                         |
| PostgreSQL                    | 18.6                                              | https://www.postgresql.org/versions.json                                     |
| Nginx                         | 1.31.7                                            | https://github.com/nginx/nginx/releases/tag/release-1.31.7                   |
| Text Embeddings Inference     | 1.9.4                                             | https://github.com/huggingface/text-embeddings-inference/releases/tag/v1.9.4 |
| BGE small English             | v1.5 / `5c38ec7c405ec4b44b94cc5a9bb96e735b38267a` | https://huggingface.co/BAAI/bge-small-en-v1.5                                |
| Qdrant                        | 1.19.2                                            | https://github.com/qdrant/qdrant/releases/tag/v1.19.2                        |
| Mailpit                       | 1.31.6                                            | https://github.com/axllent/mailpit/releases/tag/v1.31.6                      |
| Vue                           | 3.5.44                                            | https://registry.npmjs.org/vue/3.5.44                                        |
| TypeScript                    | 6.0.3                                             | https://registry.npmjs.org/typescript/6.0.3                                  |
| Tailwind CSS / Vite adapter   | 4.3.3                                             | https://registry.npmjs.org/@tailwindcss/vite/4.3.3                           |
| Vite                          | 8.3.4                                             | https://registry.npmjs.org/vite/8.3.4                                        |
| Vue Vite adapter              | 6.0.10                                            | https://registry.npmjs.org/@vitejs/plugin-vue/6.0.10                         |
| ESLint recommended JS adapter | 10.0.1                                            | https://registry.npmjs.org/@eslint/js/10.0.1                                 |
| Vue ESLint parser             | 10.3.0                                            | https://registry.npmjs.org/vue-eslint-parser/10.3.0                          |
| Vitest Playwright provider    | 5.0.5                                             | https://registry.npmjs.org/@vitest/browser-playwright/5.0.5                  |
| ESLint                        | 10.13.0                                           | https://registry.npmjs.org/eslint/10.13.0                                    |
| TypeScript ESLint             | 8.71.2                                            | https://registry.npmjs.org/typescript-eslint/8.71.2                          |
| Vue ESLint                    | 10.11.1                                           | https://registry.npmjs.org/eslint-plugin-vue/10.11.1                         |
| Prettier                      | 3.9.10                                            | https://registry.npmjs.org/prettier/3.9.10                                   |
| vue-tsc                       | 3.3.13                                            | https://registry.npmjs.org/vue-tsc/3.3.13                                    |
| Pint                          | 1.32.1                                            | https://repo.packagist.org/p2/laravel/pint.json                              |
| Larastan / PHPStan            | 3.13.0 / 2.3.1                                    | https://repo.packagist.org/p2/larastan/larastan.json                         |
| Pest / Laravel plugin         | 5.3.2 / 5.0.1                                     | https://repo.packagist.org/p2/pestphp/pest-plugin-laravel.json               |
| Vitest                        | 5.0.5                                             | https://registry.npmjs.org/vitest/5.0.5                                      |
| Vue Test Utils                | 2.5.1                                             | https://registry.npmjs.org/@vue/test-utils/2.5.1                             |
| Playwright                    | 1.64.1                                            | https://registry.npmjs.org/@playwright/test/1.64.1                           |
| Codex CLI                     | 0.163.0                                           | https://registry.npmjs.org/@openai/codex/0.163.0                             |
| Xdebug                        | 3.5.3                                             | https://pecl.php.net/package/xdebug/3.5.3                                    |
| Laravel Boost                 | 2.10.3                                            | https://repo.packagist.org/p2/laravel/boost.json                             |
| Gitleaks                      | 8.30.1                                            | https://github.com/gitleaks/gitleaks/releases/tag/v8.30.1                    |
| Trivy                         | 0.76.0                                            | https://github.com/aquasecurity/trivy/releases/tag/v0.76.0                   |

## Compatibility and dependency assessment

Laravel 13 requires PHP 8.3 or later; PHP 8.4.27 meets the preferred project line but deliberately lacks the latest security fixes during the temporary rollback; see [the bounded owner risk decision](../security/RM-2026-011-runtime-risk-acceptance.md). Pest 5 requires PHP 8.4 and its Laravel plugin requires Laravel 13.23 or later; this selection satisfies both. Larastan 3.13 explicitly supports Laravel 13 and PHPStan 2.3. Boost 2.10 supports Laravel 13. These packages have distinct roles: testing, analysis and development MCP. They do not belong in production dependencies.

Node 24.22 is the current LTS entry returned by the Node release index and satisfies Corepack, Vite, ESLint and Vitest engine requirements. TypeScript 7.0.2 was deliberately not selected: typescript-eslint 8.71.2 supports TypeScript below 6.1. TypeScript 6.0.3 remains inside that range. Vue 3.5 is the stable release; 3.6 prereleases are excluded. Tailwind's Vite adapter and the Vue Vite adapter explicitly support Vite 8.

Package registry metadata identifies MIT licenses for the selected Composer tools, Vue, Tailwind, Vite, ESLint, Prettier and test utilities, and Apache-2.0 for TypeScript and Playwright. These are actively released upstream components with complementary required responsibilities. Adapters and parsers are the integration pieces of the mandated tool families, not an additional UI/state/validation framework. The initial dependency tree will necessarily include framework and testing transitive packages; inspect actual lockfile sizes and audit results during installation rather than inventing counts before resolution.

PHP, Laravel and JavaScript tooling have published security histories; current versions alone do not prove absence of vulnerabilities. Installation must run Composer/pnpm audits against the actual lockfiles, and final image/filesystem checks must use Trivy. Findings block affected implementation until resolved or handled according to the security policy. This document records compatibility research, not a clean security scan of an unbuilt environment.

Point 9 selects TEI 1.9.4 and BAAI/bge-small-en-v1.5 at revision `5c38ec7c405ec4b44b94cc5a9bb96e735b38267a`; selection, image digests, security assessment and acceptance are documented in `knowledge.md`. The user approved vendored Polish Laravel validation data from Laravel-Lang/lang 15.37.4 at commit `a3a9ef9b039d10db442bfb3bed0a97c9b50f30a7`, with no Composer localization dependency; see `localization.md`. Codex CLI and Xdebug selections are recorded above; configuration is documented in `editor-and-agents.md`.

Point 7 installed the selected formatting/analysis/test packages. The matching Vitest browser provider, ESLint recommended-JS config and Vue parser are MIT-licensed integration components with compatible peer ranges, verified against their exact npm version metadata on 2026-10-17. The provider requires matching Vitest 5.0.5; its Playwright peer resolves to the existing 1.64.1. No additional application runtime dependency was added. `src/pnpm-workspace.yaml` records only the deliberately reviewed exact ESLint release age exception emitted by pnpm during this installation; it does not permit unbounded version exceptions. Future dependency changes require deliberate roadmap review.

Point 8 adds Boost 2.10.3 (MIT) with five new locked development packages and no upgrades to existing packages. Composer audit reported no advisories. Codex CLI 0.163.0 (Apache-2.0) supports Node >=16; its launcher selects one native platform package rather than a JavaScript runtime dependency tree. Xdebug 3.5.3 uses the Xdebug license and supports PHP 8.4. Both are development-only. Review sources: https://github.com/openai/codex, https://registry.npmjs.org/@openai/codex/0.163.0, https://xdebug.org/docs/compat and https://pecl.php.net/package/xdebug. No scanner result is suppressed.

The isolated Codex 0.163.0 npm lock audit (launcher plus selected platform package, 3 audited packages) reported zero vulnerabilities on 2026-10-17. This temporary lock is diagnostic evidence under ignored `.runtime`, not an application dependency.

## Python engineering tools

The reviewed Python pins, complete wheel graph and official Pyright Node artifact
are recorded in the [Python dependency review](../tooling/python-rebuild/dependency-review.md).
Use the [private setup/testing workflow](../tooling/python-rebuild/toolchain.md);
these tools do not change the host stdlib-only operational contract or customer
Composer/pnpm manifests. No image rebuild is required to initialize this private venv.

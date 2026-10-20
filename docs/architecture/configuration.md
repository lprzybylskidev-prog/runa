# Configuration and Settings

## Separation

Technical/infrastructure configuration belongs in environment variables plus Laravel config files.

Business settings belong in the application database and the mandatory administration panel.

Do not turn environment variables into a substitute for business configuration.

## Laravel environment rules

Never call `env()` outside Laravel configuration files.

Every new technical option must update, as applicable:

- a dedicated `src/runa/config/*.php` entry (or customer `src/app/config/*.php` override),
- environment handling,
- the relevant `.env*.example` templates,
- configuration validation/doctor checks,
- documentation/help.

## Validation

Configuration must be validated before critical runtime/deploy operations.

Detect:

- missing required values,
- invalid empty values,
- wrong types,
- out-of-range values,
- contradictory settings,
- unsafe production/development combinations,
- example-template drift.

Critical misconfiguration is a hard failure. Suspicious but non-fatal configuration is a warning with an actionable explanation.

## Environment templates

The initial environment must provide appropriate templates, including development, testing, and production examples.

Local developer overrides live in ignored local files such as `.env.dev.local`.

Do not commit secrets.

## Feature flags

Simple technical global flags may use config/environment.

Business, user, tenant, or module enablement belongs in a future application feature mechanism.

Do not scatter raw `if (env(...))` checks.

Temporary flags must have a removal plan and must not become permanent accidental architecture.

## Administration panel

The administration panel is a mandatory core part of Runa and is not disableable.

It is English-only.

It manages business settings. Exact business modules/settings are introduced by later roadmaps.

## Implemented validation entry point

Run `php artisan runa:config:validate` before critical operations. Add `--templates` in development/quality checks to require every literal `env()` key in project `config/*.php` to appear in all three `.env.dev.example`, `.env.test.example` and `.env.prod.example` files. Templates intentionally leave secrets blank; local setup generates them without printing them. Production runtime images do not need development templates.

HTTP bootstrap also fails closed on invalid application configuration. Validation reports setting names, never secret values. The initial checks cover required strings, application environment/key/HTTP URL, boolean flags, PostgreSQL-only default connection, port bounds, SSL mode, log level and production debug/knowledge/HTTPS contradictions. Services introduced later extend this contract with their own settings. CLI key generation remains possible before the key exists; deployment/lifecycle commands must explicitly validate before performing critical work.

## Platform and application composition

The Laravel base path is `src/`; technical defaults are owned by `src/runa/config/`. The thin customer provider lives in `src/app/Providers/`, with optional trusted PHP config arrays under `src/app/config/`. The platform provider merges each override into the matching namespace before config caching, preserving unspecified defaults. Non-array namespaces/overrides fail explicitly; cached configuration is loaded without re-reading overrides. No repository-root config link remains.

Private `.env`, `.env.testing` and production environment files remain at repository root. Bootstrap loads that environment path explicitly. File disks separate application/platform private/public ownership; the default local disk resolves to application-private storage. Cache consumers use the application file store or explicit platform runa_file store. Full composition details belong to [Application Architecture](application-architecture.md#delivered-source-and-runtime-composition).

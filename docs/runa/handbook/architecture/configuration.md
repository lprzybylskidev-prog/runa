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

Module enablement and declared operational settings use the delivered DB-backed [module contracts](module-framework.md). User/tenant feature policy remains future application scope.

Do not scatter raw `if (env(...))` checks.

Temporary flags must have a removal plan and must not become permanent accidental architecture.

## Administration panel

The administration panel is a mandatory core part of Runa and is not disableable.

It is English-only.

It manages business settings. Exact business modules/settings are introduced by later roadmaps.

## Implemented validation entry point

Run `php artisan runa:config:validate` before critical operations. Run the separate `php artisan runa:config:templates` in development/quality checks to require every literal `env()` key in project `config/*.php` to appear in all three `.env.dev.example`, `.env.test.example` and `.env.prod.example` files. Templates intentionally leave secrets blank; local setup generates them without printing them. Production runtime images do not need development templates.

HTTP bootstrap also fails closed on invalid application configuration. Validation reports setting names, never secret values. The initial checks cover required strings, application environment/key/HTTP URL, boolean flags, PostgreSQL-only default connection, port bounds, SSL mode, log level and production debug/knowledge/HTTPS contradictions. Services introduced later extend this contract with their own settings. CLI key generation remains possible before the key exists; deployment/lifecycle commands must explicitly validate before performing critical work.

## Platform and application composition

The Laravel base path is `src/`; technical defaults are owned by `src/runa/config/`. The thin customer provider lives in `src/app/Providers/`, with optional trusted PHP config arrays under `src/app/config/`. The platform provider merges each override into the matching namespace before config caching, preserving unspecified defaults. Non-array namespaces/overrides fail explicitly; cached configuration is loaded without re-reading overrides. No repository-root config link remains.

## Application and platform identity

Customer src/app/application.json uses schema_version 1 with id (stable lowercase ASCII ID, at most 64 characters), name (nonempty text), independent SemVer version and runa (supported platform range). Optional branding.logo accepts src and accessible_name. Only absolute same-origin asset paths or credential-free HTTPS URLs are allowed; remote browser hosts require an explicit CSP allowance. Unknown fields/schema versions, malformed JSON, symlinks, oversized files and unsupported constraints fail validation. Keep secrets/private paths outside manifests.

Protected src/runa/platform.json uses schema_version 1, fixed platform id/name runa/Runa and stable X.Y.Z version. PHP/browser/error/mail/build identity comes from that manifest. Root VERSION controls owner release arithmetic; customer scaffold VERSION is independent. Release checks synchronize the platform manifest with owner VERSION while preserving the application manifest. The supplied application is a Runa-named placeholder at independent 0.0.0.

Platform range syntax accepts full numeric X.Y.Z (components up to nine digits), exact or =, >, >=, <, <=, ^, ~, whitespace intersection and || union. Operators attach directly to full versions. Caret advances the first nonzero component (^0.0.3 stops before 0.0.4); tilde advances the minor component. Wildcards, partial versions, hyphen ranges, commas, prerelease/build platform targets and Composer stability flags are unsupported in every alternative. Application versions accept full SemVer including prerelease/build metadata. This is a deliberately bounded contract, not a general Composer constraint parser.

php artisan runa:application:check validates installed identity/compatibility; --platform-version=0.3.0 checks a candidate before an external update. Normal configuration also validates manifests. A developer deliberately adapts application behavior and compatibility before an incompatible update; the updater never rewrites that declaration.

GET /api/identity (api.identity) returns a no-store public DTO under data: applicationId, applicationName, applicationVersion, platformVersion and optional logo with src/accessibleName. Schema/ranges/private paths/configuration/credentials remain private. Vue IdentityProvider applies the bounded runtime response and retains build-time identity if it fails, without delaying page mounting. Native mail/error pages consume the same projection; static emergency documents use build-time validation. HTTPS manifest logos are mail URL defaults; local browser paths need a trusted mail_logo_path for email CID embedding. Canonical identity fields cannot be overridden in customer branding configuration.

## Module operational settings

Source-owned typed/localized descriptors and backend validation define module settings; runtime values persist encrypted in the database with optimistic revisions. Trusted CLI management and reversible immediate/reinitialize handlers are delivered; mandatory synchronous Audit participation is delivered; the real Admin UI remains future scope. Auth additionally owns [policy versions, consequences and temporary exceptions](../modules/auth/policies-and-operators.md). Use the [settings contract](module-framework.md#delivered-declarative-settings) for redacted reads, secret-safe hooks, external-key recovery and unresolved compensation fences. No environment-specific automatic overwrite/default subsystem is provided.

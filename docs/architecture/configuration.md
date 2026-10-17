# Configuration and Settings

## Separation

Technical/infrastructure configuration belongs in environment variables plus Laravel config files.

Business settings belong in the application database and the mandatory administration panel.

Do not turn environment variables into a substitute for business configuration.

## Laravel environment rules

Never call `env()` outside Laravel configuration files.

Every new technical option must update, as applicable:
- a dedicated `config/*.php` entry,
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

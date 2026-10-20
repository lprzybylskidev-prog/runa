# Owner procedure and historical evidence

Source classification: `docs/runa/handbook/architecture/api.md`. These owner-only procedures and dated execution records are retained separately from customer guidance. Historical claims keep their original context; current public operations are documented in the handbook.

`GET /api/identity`, named `api.identity`, returns `data` containing applicationId, applicationName, applicationVersion, platformVersion and optional `logo: {src, accessibleName}`. It is public, read-only and no-store; it does not expose manifest compatibility, internal paths or configuration. See [application identity](../../maintainer/architecture/application-architecture.md#application-identity-and-compatibility).

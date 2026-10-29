# Application security guidance

Enforce authorization and integrity server-side. Treat frontend guards as UX; validate inputs, escape outputs and never log secrets. Use parameterized SQL, literal process arguments and guarded public adapters. Keep availability, eligibility, grants, step-up and record policies independent.

Load only the relevant canonical procedure:

- Security and exception retirement: `docs/runa/handbook/security/baseline.md`.
- API audiences and reserved integration: `docs/runa/handbook/architecture/public-api.md`.
- Identity/security: `docs/runa/handbook/modules/auth/README.md`.
- Transaction-safe history: `docs/runa/handbook/modules/audit/README.md`.
- Protected authority: `docs/runa/handbook/modules/permissions/authority.md`.
- Production/operator responsibility: `docs/runa/handbook/production/architecture.md`.
- Dependency/source versus real image checks: `docs/runa/handbook/quality/README.md#manual-group-selection`.

Maintain application-specific patterns in local guidance/docs/app. Ordinary App
code and optional app modules are supported; copied owner files or a Contracts
namespace never authorize platform/internal APIs. Preserve installed local
extensions during reviewed updates. This default contains no owner-only procedure.

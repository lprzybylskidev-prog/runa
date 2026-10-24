# Deferred Module Concerns

This registry records areas intentionally left undefined until the responsible future application module is designed. It is not a backlog of implementation tasks. Its purpose is to prevent generic infrastructure guidance from prematurely freezing module-specific architecture.

Remove an entry when the responsible module is implemented and move its durable rules into the correct project documentation/skills.

| Concern               | Responsible future scope | What remains deferred                                                                                                                | Removal condition                               |
| --------------------- | ------------------------ | ------------------------------------------------------------------------------------------------------------------------------------ | ----------------------------------------------- |
| Data management       | Data-management module   | Physical deletion/anonymization and its audited lifecycle                                                                            | Approved module policy is delivered             |
| Files/uploads         | Filesystem module        | General upload storage/scanning/metadata/versioning and file lifecycle; Auth private avatars already have their own bounded contract | Filesystem policy is delivered                  |
| External integrations | Integrations module      | Provider credentials, adapter/retry/circuit and inbound/outbound contracts; LDAP/OIDC/SSO remain excluded                            | Integration policy is delivered                 |
| Administration        | Admin module             | Audit viewer, user/role/preset/assignment UI and generic management renderer; typed backend contracts already exist                  | Approved Admin UI is delivered                  |
| Notifications         | Notifications module     | General notifications and operational in-app delivery; direct Auth/security email is delivered                                       | Notifications runtime is delivered              |
| Throttling            | Throttling module        | General reusable throttling; Auth already owns bounded login/OTP abuse defenses                                                      | General throttling contract is delivered        |
| Teams                 | Teams module             | Team membership and team-scoped grants                                                                                               | Teams policy is delivered                       |
| Import                | Import module            | General import and CSV/XLSX user import                                                                                              | Import policy is delivered                      |
| External factors      | Future Auth scope        | WebAuthn and external identity providers                                                                                             | Explicitly approved Auth extension is delivered |
| Logs                  | Logs module              | Authorized bounded diagnostic log read/search UI/API; structured logging is delivered                                                | Logs read/search contract is delivered          |

Generic secure coding, transaction, API, and dependency rules continue to apply. Do not invent module-specific policy merely because a generic skill mentions it.

Audit append-only storage, automatic/manual capture and authorized reads are
implemented under the [Audit contract](../../handbook/modules/audit/README.md).
[Auth](../../handbook/modules/auth/README.md) supplies identity, eligibility,
sessions/CSRF, credentials/factors, private self-service, preferences, security
email, policy history and trusted operator recovery.
[Permissions](../../handbook/modules/permissions/README.md) supplies declarations,
current grants, ordinary access/preset/copy operations, protected authority and
last-superadministrator participation. First-administrator integration and explicit
local development fixtures are implemented. Independent versions and limitations
belong in their public guides/changelogs; this registry records remaining scope.
Frontend customization reconciliation remains RM-2026-011 point 19; final integrated
acceptance/release remains point 20. Implementation is not a final PASS claim.

## Auth-owned user preferences

The 2026-10-19 persistence/adoption decision is delivered by Auth 0.6.0; follow the
[public preference and delivery contract](../../handbook/modules/auth/preferences.md).
PostgreSQL is authoritative for signed-in accounts, guest storage remains separate,
and legacy adoption requires explicit ownership confirmation. Future domain views
must declare their exact table/statistics schemas; no generic undeclared JSON store
or Notifications runtime is delivered. Own-account controls are delivered under the [account UI contract](../../handbook/modules/auth/account-ui.md).

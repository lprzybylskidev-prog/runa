# Deferred Module Concerns

This registry records areas intentionally left undefined until the responsible future application module is designed. It is not a backlog of implementation tasks. Its purpose is to prevent generic infrastructure guidance from prematurely freezing module-specific architecture.

Remove an entry when the responsible module is implemented and move its durable rules into the correct project documentation/skills.

| Concern               | Responsible future scope    | What is intentionally deferred                                                                       | Removal condition                                      |
| --------------------- | --------------------------- | ---------------------------------------------------------------------------------------------------- | ------------------------------------------------------ |
| Audit trail           | Audit module                | Future module business coverage; store, automatic/manual capture and authorized reads delivered      | Audit module roadmap establishes durable policy        |
| Files/uploads         | Filesystem module           | Upload storage, scanning, metadata/versioning, content disposition, virus scanning, file lifecycle   | Filesystem module roadmap establishes durable policy   |
| External integrations | Integrations module         | HTTP adapter conventions, provider credentials, retries/circuit behavior, inbound/outbound contracts | Integrations module roadmap establishes durable policy |
| Roles and permissions | Authorization system/module | Exact role/permission storage, policy integration, inheritance, assignment UX                        | Authorization roadmap establishes durable policy       |

Generic secure coding, transaction, API, and dependency rules continue to apply. Do not invent module-specific policy merely because a generic skill mentions it.

A future data-management module owns physical deletion/anonymization; Audit will retain immutable history indefinitely and supplies no deletion/retention jobs. Module identity/discovery and dependency/cascade planning are defined in the public [module framework contracts](../../handbook/architecture/module-framework.md); administrative transitions and operational enforcement are delivered framework capabilities. The future Logs read/search module remains deferred; the framework now supplies structured origin/correlation/redaction with private bounded file rotation and container stderr under the public module contract. RM-2026-011 actively implements Audit/Auth/Permissions in that order; its shared foundations and Audit store, automatic/manual capture, transaction participation and authorized reads are delivered; Auth identity storage, independent eligibility and audited lifecycle Actions are delivered; Auth cookie/CSRF entry transport, registration/invitations and verification are delivered; operational sessions, password recovery/policy, 2FA, trusted devices and step-up are delivered; private profile, primary/secondary address workflows, avatar and safe own history are delivered; account preferences/security delivery are delivered; policy management and Auth entry/account UI are delivered; Permissions dictionaries, declarations and current authorization are delivered, ordinary assignments, custom roles, presets and access copy are delivered, protected authority/last-superadministrator participation and confirmed custom-role force deletion are delivered, with operator/bootstrap integration still assigned to its owning point. Fortify is explicitly mandated for Auth; no starter kit or additional unmandated dependency is approved.

## Auth-owned user preferences

The 2026-10-19 persistence/adoption decision is delivered by Auth 0.6.0; follow the
[public preference and delivery contract](../../handbook/modules/auth/preferences.md).
PostgreSQL is authoritative for signed-in accounts, guest storage remains separate,
and legacy adoption requires explicit ownership confirmation. Future domain views
must declare their exact table/statistics schemas; no generic undeclared JSON store
or Notifications runtime is delivered. The remaining own-account controls belong
to RM-2026-011's Auth UI points.

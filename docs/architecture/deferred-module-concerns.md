# Deferred Module Concerns

This registry records areas intentionally left undefined until the responsible future application module is designed. It is not a backlog of implementation tasks. Its purpose is to prevent generic infrastructure guidance from prematurely freezing module-specific architecture.

Remove an entry when the responsible module is implemented and move its durable rules into the correct project documentation/skills.

| Concern | Responsible future scope | What is intentionally deferred | Removal condition |
|---|---|---|---|
| Audit trail | Audit module | Audit record schema, actor/context model, business event coverage, retention/query UX | Audit module roadmap establishes durable policy |
| Files/uploads | Filesystem module | Upload storage, scanning, metadata/versioning, content disposition, virus scanning, file lifecycle | Filesystem module roadmap establishes durable policy |
| External integrations | Integrations module | HTTP adapter conventions, provider credentials, retries/circuit behavior, inbound/outbound contracts | Integrations module roadmap establishes durable policy |
| Roles and permissions | Authorization system/module | Exact role/permission storage, policy integration, inheritance, assignment UX | Authorization roadmap establishes durable policy |

Generic secure coding, transaction, API, and dependency rules continue to apply. Do not invent module-specific policy merely because a generic skill mentions it.

The audit module will also define audited physical deletion/anonymization. Do not implement that mechanism during environment setup. Broader module activation/dependency architecture and the future application log module are likewise deferred; the baseline uses ordinary Laravel/container logging. Authentication implementation and its exact package choice remain deferred alongside authorization; no starter auth stack is implicitly approved.

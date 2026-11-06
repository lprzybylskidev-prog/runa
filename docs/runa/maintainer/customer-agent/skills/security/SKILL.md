---
name: runa-security
description: Runa customer guidance for authorization, secrets, files/processes, dependencies and security reviews.
---

# Runa application security

Load for authorization, secrets, files/processes, dependencies and security reviews. Read the active application roadmap, query available local knowledge and inspect analogous App code. Project rules override generic advice. Load references/application.md only when needed. Preserve protected platform source.

Follow `docs/runa/handbook/ai/skills-policy.md#shared-point-workflow` for shared
point timing, canonical docs and local-extension maintenance. Application scope
and protected-platform boundaries remain governed by installed AGENTS.

For password-protected sections, use the Auth-owned
`docs/runa/handbook/modules/auth/security.md#protected-section-idle-access`
through guarded APIs and the public frontend controller. Keep source-owned section
policy, current authorization and operation-proof freshness separate; background
reads never renew access and expired access always requires reconfirmation.

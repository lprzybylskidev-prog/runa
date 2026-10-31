---
name: 'runa-security'
description: 'Runa secure-coding and security-review guidance for PHP/Laravel/Vue/Docker/agent workflows. Load for security-sensitive changes or explicit security reviews.'
---

# Runa Security

Load for:

- authentication/authorization boundaries,
- secrets/crypto,
- raw SQL/shell/network/file handling,
- browser security,
- dependency/supply-chain changes,
- security reviews,
- agent/skill/RAG trust boundaries.

Read `docs/runa/handbook/security/baseline.md`.

Then load only the relevant references:

- `references/coding-browser.md`
- `references/supply-chain-agent.md`
- `references/review-workflow.md`

Security hard rules still apply even when this skill is not fully loaded.

## Shared workflow

Follow [shared skill workflow](../../../docs/runa/handbook/ai/skills-policy.md#shared-point-workflow)
for source ownership, instruction-product assessment, supported commands and final-only broad verification.

For password-protected sections, use the Auth-owned
[protected-section idle contract](../../../docs/runa/handbook/modules/auth/security.md#protected-section-idle-access)
through guarded APIs and the public frontend controller. Keep source-owned section
policy, current authorization and operation-proof freshness separate; background
reads never renew access and expired access always requires reconfirmation.

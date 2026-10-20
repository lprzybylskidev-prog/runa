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

## Documentation placement

Synchronize affected current guidance and domain/pattern references in the same point. Apply the reader/task placement and delivery rules in `docs/README.md` and `AGENTS.md`; validate public links and staged knowledge freshness. Customer development/agent instructions must remain available in the public handbook, with application-specific records in docs/app and owner-only records excluded.

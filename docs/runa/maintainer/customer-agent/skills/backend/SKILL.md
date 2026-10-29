---
name: runa-backend
description: Runa customer guidance for application use cases, transactions, public integrations and failure handling.
---

# Runa application backend

Load for application use cases, transactions, public integrations and failure handling. Read the active application roadmap, query available local knowledge and inspect analogous App code. Project rules override generic advice. Load references/application.md only when needed. Preserve protected platform source.

Use `docs/runa/handbook/architecture/public-api.md` to distinguish supported
extension/consumption APIs from platform internals. Consume documented backend
adapters and frontend facades from application-owned code; neither a Contracts
namespace nor a resolvable import permits private integration access.

Follow `docs/runa/handbook/ai/skills-policy.md#shared-point-workflow` for shared
point timing, canonical docs and local-extension maintenance. Application scope
and protected-platform boundaries remain governed by installed AGENTS.

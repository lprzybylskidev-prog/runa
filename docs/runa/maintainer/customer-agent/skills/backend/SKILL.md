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

For Admin integration, check `docs/runa/handbook/modules/admin/README.md` for
contract ownership and delivery status. Consume only APIs implemented in the
installed version; a specification does not authorize protected edits or direct
foreign persistence. Optional Admin page declarations use the canonical
bootstrap registration procedure; retain owning record Policies and guarded
reads, and respect the documented write/rendering delivery status.

Follow `docs/runa/handbook/ai/skills-policy.md#shared-point-workflow` for shared
point timing, canonical docs and local-extension maintenance. Application scope
and protected-platform boundaries remain governed by installed AGENTS.

Application-owned transactional contributions follow
`docs/runa/handbook/architecture/module-transactions.md`. Implement only App-owned
participants, use current documented guarded API versions and retain owning grants,
proof and Audit. The protocol does not permit protected edits or foreign persistence.

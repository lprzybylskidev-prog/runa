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

Maintain application patterns and current docs in the same point. Use docs/app for domain records, the delivered handbook for Runa contracts, and supported command entrypoints. Only necessary small, fast diagnostics belong before final verification; receive their results and continue. Full suites and broad acceptance belong to final quality, whose background status interface is runa:quality:status. Never replace supported tests with internal or detached log-only runners.

## Manual quality scope

Choose quality groups proportionately, record the final selection/rationale in the
roadmap and preview it through supported commands. Mandatory checks/hooks and real
prerequisites remain required; selected security/AI gates remain strict. Excluded
groups are not PASS. See docs/runa/handbook/quality/README.md for the catalogue and evidence contract.

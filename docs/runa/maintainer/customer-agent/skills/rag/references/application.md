# Application rag guidance

Query available knowledge before broad loading and verify significant rules in original sources. Use short English queries with explicit current/history/audit intent, bounded read expansion and distinct facts/inferences/unknowns. On unavailable/stale/locked services report the reason and use focused source search; do not bootstrap AI just for navigation.

Load only the relevant canonical procedure:

- Commands, evidence, applicability and reports: `docs/runa/handbook/development/knowledge.md`.
- Knowledge architecture/trust: `docs/runa/handbook/ai/knowledge-system.md`.
- Local guidance maintenance: `docs/runa/handbook/ai/skills-policy.md`.

Maintain application-specific patterns in local guidance/docs/app. Ordinary App
code and optional app modules are supported; copied owner files or a Contracts
namespace never authorize platform/internal APIs. Preserve installed local
extensions during reviewed updates. This default contains no owner-only procedure.

# Source Metadata — RAG / Qdrant

## Primary upstream research source

- Repository: `https://github.com/qdrant/skills`
- Reviewed commit: `d2127f6e1b9c6611fff10fe939c6fb17761aaec2`
- Adaptation date: `2026-10-17`
- Update model: manual review; never auto-overwrite local skill from upstream `main`.

## Runa adaptation

Qdrant's skills informed search-quality, model-migration, payload/filtering, and the principle that skills should answer when/why rather than duplicate docs. Runa deliberately keeps a smaller offline-pinned project skill instead of live Qdrant Advisor, and rejects cluster/sharding/quantization complexity for the development-only corpus until evidence demands it.

## Local-only material

The local skill also contains Runa-specific rules that do not come from upstream, including project architecture, workflow, dependency, quality, language, and accepted exception decisions. These local sections must not be deleted during upstream maintenance merely because no upstream equivalent exists.

## Local authority

This skill is Runa-specific. Project rules, active roadmap decisions, and current code override upstream guidance.

No upstream helper script is trusted/executed merely because it belongs to the source repository. Future updates must be reviewed as diffs, including referenced scripts/assets.

See `docs/runa/handbook/ai/skills-policy.md`.

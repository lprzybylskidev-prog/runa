# Source Metadata — Security

## Primary upstream research source

- Repository: `https://github.com/openai/skills`
- Reviewed commit: `49f948faa9258a0c61caceaf225e179651397431`
- Adaptation date: `2026-10-17`
- Update model: manual review; never auto-overwrite local skill from upstream `main`.

## Runa adaptation

The OpenAI security-best-practices skill informed the focused, evidence-based review model and project-overrides concept. Runa expands it with PHP/Laravel/Vue/Docker/agent/RAG supply-chain rules. Generic requirements for unrelated languages and heavyweight security programs were omitted.

## Local-only material

The local skill also contains Runa-specific rules that do not come from upstream, including project architecture, workflow, dependency, quality, language, and accepted exception decisions. These local sections must not be deleted during upstream maintenance merely because no upstream equivalent exists.

## Local authority

This skill is Runa-specific. Project rules, active roadmap decisions, and current code override upstream guidance.

No upstream helper script is trusted/executed merely because it belongs to the source repository. Future updates must be reviewed as diffs, including referenced scripts/assets.

See `docs/runa/handbook/ai/skills-policy.md`.

## Customer adaptation

Reviewed and rewritten on 2026-10-23 from the existing local Runa skill and its references. This records the inherited upstream research baseline, not a fresh upstream review. Customer procedures use delivered public contracts and application ownership; owner implementation commands and protected-source writes are excluded.

Source-placement and audience routing was reconciled on 2026-10-28 for local
RM-2026-012 point 19. The delivered public boundary/projection guides own the
procedures; no upstream refresh, dependency or protected-edit authority was added.

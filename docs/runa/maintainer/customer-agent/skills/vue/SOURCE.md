# Source Metadata — Vue

## Primary upstream research source

- Repository: `https://github.com/vuejs-ai/skills`
- Reviewed commit: `c9d355ff23f654309dd02006be671859df0a134c`
- Adaptation date: `2026-10-17`
- Update model: manual review; never auto-overwrite local skill from upstream `main`.

## Runa adaptation

Current Vue-oriented public skill guidance was filtered against Runa's frontend rules. Options API as the standard, Pinia/VueUse/Pinia Colada day-one assumptions, Nuxt/SSR/hydration, JSX defaults, Vapor Mode, and validation/codegen dependencies were intentionally excluded.

## Local-only material

The local skill also contains Runa-specific rules that do not come from upstream, including project architecture, workflow, dependency, quality, language, and accepted exception decisions. These local sections must not be deleted during upstream maintenance merely because no upstream equivalent exists.

## Local authority

This skill is Runa-specific. Project rules, active roadmap decisions, and current code override upstream guidance.

No upstream helper script is trusted/executed merely because it belongs to the source repository. Future updates must be reviewed as diffs, including referenced scripts/assets.

See `docs/runa/handbook/ai/skills-policy.md`.

## Customer adaptation

Reviewed and rewritten on 2026-10-23 from the existing local Runa skill and its references. This records the inherited upstream research baseline, not a fresh upstream review. Customer procedures use delivered public contracts and application ownership; owner implementation commands and protected-source writes are excluded.

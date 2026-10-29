# Source Metadata — Laravel

## Primary upstream research source

- Repository: `https://github.com/laravel/boost`
- Reviewed commit: `f1411f90d54cb01ffe30606b7ca92789aad737d9`
- Adaptation date: `2026-10-17`
- Update model: manual review; never auto-overwrite local skill from upstream `main`.

## Runa adaptation

Laravel Boost and current Laravel conventions were used as the main framework-aware source. Runa intentionally does not retain Boost-generated skills. Useful guidance was adapted into this local skill, while starter-kit assumptions, Laravel Cloud/Nightwatch assumptions, and conventions conflicting with Runa architecture were excluded.

## Local-only material

The local skill also contains Runa-specific rules that do not come from upstream, including project architecture, workflow, dependency, quality, language, and accepted exception decisions. These local sections must not be deleted during upstream maintenance merely because no upstream equivalent exists.

## Local authority

This skill is Runa-specific. Project rules, active roadmap decisions, and current code override upstream guidance.

No upstream helper script is trusted/executed merely because it belongs to the source repository. Future updates must be reviewed as diffs, including referenced scripts/assets.

See `docs/runa/handbook/ai/skills-policy.md`.

Local Runa API/presentation/source-placement routing follows the canonical public
handbook and owner contract. RM-2026-012 established those boundaries; RM-2026-013
consolidated local routing without upstream refresh, copied upstream text, new
dependency or new customer authority. Original research pins/dates above are unchanged.

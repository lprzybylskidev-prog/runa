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

Public/internal API boundary routing was added from the owner-approved
RM-2026-012 source review on 2026-10-27. Its authoritative rule is
`docs/runa/handbook/architecture/public-api.md`; no upstream refresh or new
dependency was involved.

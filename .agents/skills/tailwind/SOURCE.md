# Source Metadata — Tailwind

## Primary upstream research source

- Repository: `https://github.com/sablier-labs/agent-skills`
- Reviewed commit: `41e29982b5551b17c3fc6dc81720310bb05b2482`
- Adaptation date: `2026-10-17`
- Update model: manual review; never auto-overwrite local skill from upstream `main`.

## Runa adaptation

Tailwind v4/current-version guidance and practical utility patterns were adapted. Other reviewed community sources informed class discoverability and project-conventions-first rules. Runa rejected automatic variant libraries, animation packages, stack-utility abstraction, dogmatic `@apply` bans, and migrations performed incidentally.

## Local-only material

The local skill also contains Runa-specific rules that do not come from upstream, including project architecture, workflow, dependency, quality, language, and accepted exception decisions. These local sections must not be deleted during upstream maintenance merely because no upstream equivalent exists.

## Local authority

This skill is Runa-specific. Project rules, active roadmap decisions, and current code override upstream guidance.

No upstream helper script is trusted/executed merely because it belongs to the source repository. Future updates must be reviewed as diffs, including referenced scripts/assets.

See `docs/runa/handbook/ai/skills-policy.md`.

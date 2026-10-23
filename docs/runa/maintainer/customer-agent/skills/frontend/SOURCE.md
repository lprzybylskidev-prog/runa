# Source Metadata — Frontend

## Primary upstream research source

- Repository: `https://github.com/sablier-labs/agent-skills`
- Reviewed commit: `41e29982b5551b17c3fc6dc81720310bb05b2482`
- Adaptation date: `2026-10-17`
- Update model: manual review; never auto-overwrite local skill from upstream `main`.

## Runa adaptation

Frontend research combined community guidance with the already-established Runa frontend contract. Aesthetic dogma, mandatory Storybook, mandatory Lighthouse/axe dependencies, microfrontends, and design-system package architecture were rejected. The local skill focuses on measurable UX/accessibility and rendered acceptance.

## Local-only material

The local skill also contains Runa-specific rules that do not come from upstream, including project architecture, workflow, dependency, quality, language, and accepted exception decisions. These local sections must not be deleted during upstream maintenance merely because no upstream equivalent exists.

Motion rules and the complete component inventory in `references/motion.md` were adapted from the existing accepted Runa ApplicationLayout navigation and the owner’s RM-2026-004 point-21 request (2026-10-19), without a new external dependency or upstream animation framework.

## Local authority

This skill is Runa-specific. Project rules, active roadmap decisions, and current code override upstream guidance.

No upstream helper script is trusted/executed merely because it belongs to the source repository. Future updates must be reviewed as diffs, including referenced scripts/assets.

See `docs/runa/handbook/ai/skills-policy.md`.

## Customer adaptation

Reviewed and rewritten on 2026-10-23 from the existing local Runa skill and its references. This records the inherited upstream research baseline, not a fresh upstream review. Customer procedures use delivered public contracts and application ownership; owner implementation commands and protected-source writes are excluded.

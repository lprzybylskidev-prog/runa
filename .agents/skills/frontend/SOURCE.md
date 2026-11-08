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

Local Runa API/presentation/source-placement routing follows the canonical public
handbook and owner contract. RM-2026-012 established those boundaries; RM-2026-013
consolidated local routing without upstream refresh, copied upstream text, new
dependency or new customer authority. Original research pins/dates above are unchanged.

Mandatory per-view FormNotifications routing is local Runa guidance sourced from
`docs/runa/handbook/development/frontend/forms.md` and its shared component contract.
It adds no upstream dependency or protected-platform edit authority.

Read/request amplification review routing comes from local RM-2026-016 runtime
measurements and the public application architecture/loading guides. No upstream
refresh, dependency or workflow/quality-gate change is involved.

## Current selection and standards sources

Topic selection follows the public UI standards map in
`docs/runa/handbook/design/visual-direction.md#ui-standards-and-guidance-map`.
The frontend selector remains mandatory for frontend work, but loads only the
affected topic and canonical sections. Feedback/loading/security rules retain
their owning contracts; a copy-only task does not load unrelated procedures.
This is a local consolidation of accepted Runa behavior, not an upstream refresh.

Accessibility references were checked on 2026-11-06 against W3C's
[WCAG 2.2 Recommendation, 2024-12-12](https://www.w3.org/TR/2024/REC-WCAG22-20241212/)
and [WAI-ARIA modal dialog pattern](https://www.w3.org/WAI/ARIA/apg/patterns/dialog-modal/).
The handbook accessibility baseline owns their application. Only links and local
paraphrases are used; no upstream text/code/assets, installer or dependency was
copied. APG is pattern guidance rather than conformance evidence. Original
Sablier provenance remains unchanged; community planning candidates were not
adopted or relabelled as newly reviewed.

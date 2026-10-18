---
name: 'runa-frontend'
description: 'Framework-agnostic Runa frontend guidance for accessibility, responsive UX, component states, visual acceptance, performance, and design-system discipline. Use with Vue/Tailwind skills for implementation.'
---

# Runa Frontend

Use this skill for user-interface behavior and quality that is broader than Vue or Tailwind syntax.

Load only relevant references:

- `references/accessibility-responsive.md`
- `references/ux-design-performance.md`

Before editing UI, inspect analogous existing pages/forms and search the shared component catalogue. Reuse the established structure and controls. After changing a reusable component/pattern, inspect existing layouts for applicable uses and replace matching provisional elements with the finalized shared component in the same task. Recheck affected layout behavior and rendering; update its catalogue entry and page references in that task.

For discovery and maintenance load:

- `references/components.md` — real component paths/contracts/examples
- `references/page-layouts.md` — canonical page structures
- `references/forms.md` — shared field/error/action patterns
- `references/tables.md` — list/filter/statistics/table behavior

Project frontend architecture is in `docs/architecture/frontend.md`.

Before visual design or UI changes, read `docs/design/visual-direction.md` and the relevant design documents it links, including `docs/design/color-palette.md` for colors. Inspect only the reference images relevant to the task and follow their explicitly selected features. Distinguish approved decisions from open proposals; do not infer implementation approval from reference images. Preserve the active roadmap's discussion/approval gates.

Consider motion when designing component states: make appropriate visual changes smooth and subtle, avoid animating everything, preserve immediate feedback and respect reduced-motion preferences. Follow the motion decisions in `docs/design/visual-direction.md`.

Vue implementation details belong to the Vue skill. Tailwind implementation details belong to the Tailwind skill.

Use concise, factual, professional interface copy. Avoid generic promotional slogans and motivational filler; omit text that adds no useful information.

Keep repeated controls and their hover/focus/disabled/transition styling in shared components (for example `AppButton`, `AppTooltip`), not page-local copies. Check the component catalogue before adding markup; extend existing components when a real additional use requires it. Shared layout or design tokens alone do not replace reusable controls.

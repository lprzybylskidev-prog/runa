---
name: 'runa-frontend'
description: 'Framework-agnostic Runa frontend guidance for accessibility, responsive UX, component states, visual acceptance, performance, and design-system discipline. Use with Vue/Tailwind skills for implementation.'
---

# Runa Frontend

Use this skill for user-interface behavior and quality that is broader than Vue or Tailwind syntax.

Load only relevant references:

- `references/accessibility-responsive.md`
- `references/ux-design-performance.md`

Before editing UI, inspect analogous existing pages/forms and search the shared component catalogue. Reuse the established structure and controls. After changing a reusable component/pattern, update its catalogue entry in the same task.

For discovery and maintenance load:

- `references/components.md` — real component paths/contracts/examples
- `references/page-layouts.md` — canonical page structures
- `references/forms.md` — shared field/error/action patterns
- `references/tables.md` — list/filter/statistics/table behavior

Project frontend architecture is in `docs/architecture/frontend.md`.

Before visual design or UI changes, read `docs/design/visual-direction.md` and the relevant design documents it links, including `docs/design/color-palette.md` for colors. Inspect only the reference images relevant to the task and follow their explicitly selected features. Distinguish approved decisions from open proposals; do not infer implementation approval from reference images. Preserve the active roadmap's discussion/approval gates.

Consider motion when designing component states: make appropriate visual changes smooth and subtle, avoid animating everything, preserve immediate feedback and respect reduced-motion preferences. Follow the motion decisions in `docs/design/visual-direction.md`.

Vue implementation details belong to the Vue skill. Tailwind implementation details belong to the Tailwind skill.

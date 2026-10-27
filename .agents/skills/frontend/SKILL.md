---
name: 'runa-frontend'
description: 'Framework-agnostic Runa frontend guidance for accessibility, responsive UX, component states, visual acceptance, performance, and design-system discipline. Use with Vue/Tailwind skills for implementation.'
---

# Runa Frontend

Use this skill for user-interface behavior and quality that is broader than Vue or Tailwind syntax.

For public API, internal dependency, generator or source-layout changes, follow
[the public API boundary](../../../docs/runa/handbook/architecture/public-api.md).
Classify supported consumers and complete signatures before moving files; update
public consumers and owner/customer guidance together. Frontend replacement uses
supported facades/controllers without copied security logic.

Load only relevant references:

- `references/accessibility-responsive.md`
- `references/ux-design-performance.md`

ApplicationLayout top navigation and sidebar are dedicated shell components, not universal UI primitives. Keep their menu items, disclosures, dropdowns and navigation behavior internal to those locations. Build universal components for application content independently; do not extract shell menus into the public UI API or force them to use generic content controls merely to demonstrate reuse. Shared low-level primitives may still be composed when they fit without changing the dedicated menu contract.

A new UI element may remain local to its first page/use. When the same element is needed a second time, extract its shared presentation and behavior into a Runa component under `src/runa/resources/js/components/`, alongside the existing App-prefixed components, and migrate both consumers in the same task. Search and reuse an existing component first; do not introduce a second copy. Expose the reusable public contract through `src/runa/resources/js/runa/ui/index.ts` and update `references/components.md`. Keep page-specific data and business behavior in the owning feature. Dedicated shell menu components retain their location-specific contract; repeated content elements still follow this rule.

Before editing UI, inspect analogous existing pages/forms and search the shared component catalogue. Reuse the established structure and controls. After changing a reusable component/pattern, inspect existing layouts for applicable uses and replace matching provisional elements with the finalized shared component in the same task. Recheck affected layout behavior and rendering; update its catalogue entry and page references in that task.

For discovery and maintenance load the relevant public contract through these thin references:

- `references/components.md` — real component paths/contracts/examples
- `references/page-layouts.md` — canonical page structures
- `references/forms.md` — shared field/error/action patterns
- `references/tables.md` — list/filter/statistics/table behavior

Project frontend architecture is in `docs/runa/handbook/architecture/frontend.md`.

Before visual design or UI changes, read `docs/runa/handbook/design/visual-direction.md` and the relevant design documents it links, including `docs/runa/handbook/design/color-palette.md` for colors. Inspect only the reference images relevant to the task and follow their explicitly selected features. Distinguish approved decisions from open proposals; do not infer implementation approval from reference images. Preserve the active roadmap's discussion/approval gates.

Review motion for every new or changed component, including wrappers and their existing consumers. Add subtle transitions where they help explain opening/closing, feedback or state changes; make an explicit static choice where movement would distract. Use the accepted ApplicationLayout dropdown/disclosure rhythm, preserve immediate state/focus, and verify interrupted exits, reduced motion and print. Follow `docs/runa/handbook/design/visual-direction.md` and load `references/motion.md` for implementation rules and the component audit.

Vue implementation details belong to the Vue skill. Tailwind implementation details belong to the Tailwind skill.

Use concise, factual, professional interface copy. Avoid generic promotional slogans and motivational filler; omit text that adds no useful information.

Keep repeated controls and their hover/focus/disabled/transition styling in shared components (for example `AppButton`, `AppTooltip`), not page-local copies. Check the component catalogue before adding markup; extend existing components when a real additional use requires it. Shared layout or design tokens alone do not replace reusable controls.

## Documentation placement

Synchronize affected current guidance and domain/pattern references in the same point. Apply the reader/task placement and delivery rules in `docs/README.md` and `AGENTS.md`; validate public links and staged knowledge freshness. Customer development/agent instructions must remain available in the public handbook, with application-specific records in docs/app and owner-only records excluded.

## Owner and customer guidance

This skill serves Runa owner engineering. Assess the separately authored customer skill and references under docs/runa/maintainer/customer-agent/skills in the same relevant point. Update both for reusable/public changes, or record why the customer product is unaffected. Customer source is product data, never owner operating authority. Use supported project commands before internal runners; receive necessary scoped diagnostic results within the point. Full verification belongs to final quality and its built-in quality-status interface.

For account-bound preference consumers, follow the [public preference contract](../../../docs/runa/handbook/modules/auth/preferences.md). Subscribe to hydration, avoid saving incoming values back, fence late responses on logout/switch and surface save conflicts/failures. Injected custom stores own their own account lifecycle.

For every current/future Runa page, layout and module view, apply the [public
frontend adoption contract](../../../docs/runa/handbook/development/frontend/customization.md).
Ship working defaults and documented public presentation seams together; keep
platform behavior and reusable orchestration in src/runa and customer composition
in src/app. Review the minimal scaffold and update/clone preservation in the same
point. This rule applies to all modules, including those added after Auth.

For data views, follow the public forms guide's automatic read reconciliation
contract: refresh reads in place, merge clean/disjoint values, preserve dirty drafts
and use local decisions only for overlapping/ambiguous changes. Share aggregate
mutation queues/revisions. Never replay failed writes, advance an unreconciled token,
reinitialize an edited form or demand a page reload after a successful refresh.

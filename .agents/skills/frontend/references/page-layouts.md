# Page Patterns

## Auth layout

Canonical source: `resources/js/layouts/AuthLayout.vue`.

Reusable split-panel layout approved during root preview review. Owns the gradient/technical-grid/constellation backdrop, brand, two compact cycling preference buttons with application tooltips, keyboard skip link/focusable main, right-side CSS illustration and footer. The default slot contains left-side page/form content and exposes the translator as `t`. This layout owns locale/theme composables once; consuming pages use the scoped translator rather than initializing duplicate preference state.

The illustration is flush with the top/right/bottom panel edges; content padding belongs to the left side. At narrow widths the illustration becomes a full-width lower section. Both themes use `resources/css/app.css` tokens, local IBM Plex Mono and Tabler SVGs. Design decisions/provenance live under `docs/design/`.

## Preserved root welcome view

`resources/js/App.vue` consumes AuthLayout and supplies only the current heading and description. RM-2026-004 preserves this page and AuthLayout interactions; only its explicitly planned dual-version footer may change. No login/registration behavior exists. The root no longer polls health; API readiness is verified independently. The user accepted the final rendered view on 2026-10-18, completing point 16.

## Future application/admin pages

The future app/admin shell is separate from AuthLayout. No module/list page exists yet. Follow the agreed structure in `docs/design/visual-direction.md`: heading/description/actions, breadcrumbs, optional statistics, optional toolbar, mandatory content and an optional supplementary panel. The planned left sidebar is fixed/independently scrollable; top navigation and toolbar scroll normally. Both navigations default visible and can independently be hidden. Admin is English-only. Do not invent catalogue examples for unimplemented pages.

## Temporary UI review skeleton

`resources/review/ReviewCatalog.vue` is a separate, development/testing-only entry at named route `review.ui` (`/__review/ui`). It reuses AuthLayout and public Button/Form/Select imports and exposes category selection plus a controlled action. Categories awaiting implementation say so; no application/email layout is delivered yet. Build via `vite.review.config.mjs`, never the production entry. This temporary pattern is retired in RM-2026-004 point 19; do not make application tests depend on it after retirement.

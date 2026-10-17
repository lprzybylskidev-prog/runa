# Page Patterns

## Auth layout

Canonical source: `resources/js/layouts/AuthLayout.vue`.

Reusable split-panel layout approved during root preview review. Owns CSS circular backdrop, brand, two compact cycling preference buttons with application tooltips, keyboard skip link/focusable main, right-side CSS illustration and footer. The default slot contains left-side page/form content and exposes the translator as `t`. This layout owns locale/theme composables once; consuming pages use the scoped translator rather than initializing duplicate preference state.

The illustration is flush with the top/right/bottom panel edges; content padding belongs to the left side. At narrow widths the illustration becomes a full-width lower section. Both themes use `resources/css/app.css` tokens, local IBM Plex Mono and Tabler SVGs. Design decisions/provenance live under `docs/design/`.

## Temporary root view

`resources/js/App.vue` consumes AuthLayout and supplies only the current heading and description. Removing this page later must preserve AuthLayout and its shared assets. No login/registration behavior exists. The root no longer polls health; API readiness is verified independently. User acceptance of point 16 remains pending.

## Future application/admin pages

The future app/admin shell is separate from AuthLayout. No module/list page exists yet. Follow the agreed structure in `docs/design/visual-direction.md`: breadcrumbs, optional statistics, optional filters, then main content. Admin is English-only. Do not invent catalogue examples for unimplemented pages.

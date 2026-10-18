# Page Patterns

## Minimal layout

Canonical source: `resources/js/layouts/MinimalLayout.vue`, public import `MinimalLayout` from `@/runa/ui`. AuthLayout was removed at the owner's request during RM-2026-004 point 2.

Owns the accepted backdrop, default identity header, cycling language/theme controls, skip link/focusable main and configurable footer. Optional named slots: header, content, illustration, actions, footer. Content falls back to the default slot; slots expose translator `t`. The supplied Runa illustration is built in and visible by default; content occupies the left panel, the illustration the right at `lg`, stacking on narrow screens. Set `illustration: false` explicitly for one column, or override the illustration slot. Actions follow content. Footer stays in normal flow at the viewport bottom on short pages and after long content. `footer.visible: false` hides even a custom footer slot.

The layout owns locale/theme composables once. Consumers use scoped `t`, preserving existing browser keys, cover timing, inert content and focus restoration. Identity overrides are scoped with `IdentityProvider`; no global installation is required. Custom headers replace the default brand and preferences together.

## Preserved root welcome view

`resources/js/App.vue` consumes MinimalLayout directly with only the existing heading/description as content. The layout itself supplies `AppBrandIllustration.vue` by default. Illustration, geometry, backdrop and preference interactions are preserved. The footer now includes independent application/platform versions. No authentication behavior exists. The original root was accepted on 2026-10-18; the point-2 extraction/footer awaits separate owner visual acceptance.

## Future application/admin pages

The future app/admin shell is separate from MinimalLayout. No module/list page exists yet. Follow the agreed structure in `docs/design/visual-direction.md`: heading/description/actions, breadcrumbs, optional statistics, optional toolbar, mandatory content and an optional supplementary panel. The planned left sidebar is fixed/independently scrollable; top navigation and toolbar scroll normally. Both navigations default visible and can independently be hidden. Admin is English-only. Do not invent catalogue examples for unimplemented pages.

## Temporary UI review skeleton

`resources/review/ReviewCatalog.vue` is a separate, development/testing-only entry at named route `review.ui` (`/__review/ui`). It reuses MinimalLayout and public Button/Form/Select imports and exposes category selection plus a controlled action. Categories awaiting implementation say so; no application/email layout is delivered yet. Build via `vite.review.config.mjs`, never the production entry. This temporary pattern is retired in RM-2026-004 point 19; do not make application tests depend on it after retirement.

Minimal review: `/__review/ui?preview=minimal` scopes Atlas identity, independent application version and actual platform VERSION. Controls exercise short/long content, optional illustration and footer visibility. All web previews retain PL/EN and light/dark preference controls. This is temporary review content, not a shipping application.

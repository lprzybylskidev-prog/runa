# Page Patterns

## Temporary root welcome view

Canonical source: `resources/js/App.vue`.

Use a semantic header with brand/preferences, a skip link targeting focusable main content, and a restrained footer. The current foundation is a temporary welcome/connection design preview, neither the auth layout nor the future app/admin shell. It uses a CSS circular backdrop, a two-column welcome/abstract illustration panel that stacks on narrow screens, with the illustration filling the right column. The user explicitly removed the connection-status panel; the root no longer polls API health. API readiness remains verified independently. The layout uses a shared max-width container, responsive spacing and semantic light/dark tokens from `resources/css/app.css`.

Locale/theme state is owned once at the application root through `useLocale`/`useTheme`. Administration must use English at its future boundary; no administration module exists yet. Preserve keyboard focus and preference usability when browser storage is blocked.

The approved palette, local IBM Plex Mono and selected Tabler SVGs are implemented here. Design direction and asset provenance live under `docs/design/`. The view awaits user visual acceptance under roadmap point 16.

No list/index page exists yet. When one is introduced, inspect existing analogous pages and retain the established breadcrumbs, page header, filters, statistics and main-content hierarchy where applicable. Do not fabricate catalogue examples.

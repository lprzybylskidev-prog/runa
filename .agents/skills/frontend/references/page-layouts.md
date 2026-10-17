# Page Patterns

## Application shell

Canonical source: `resources/js/App.vue`.

Use a semantic header with brand/preferences, a skip link targeting focusable main content, and a restrained footer. The current foundation is a single welcome/connection page, not a business dashboard. The layout uses a shared max-width container, responsive spacing and semantic light/dark tokens from `resources/css/app.css`.

Locale/theme state is owned once at the application root through `useLocale`/`useTheme`. Administration must use English at its future boundary; no administration module exists yet. Loading/success/failure/retry of a read is local to the connection section, with an announced status and abort cleanup. Preserve keyboard focus and preference usability when browser storage is blocked.

No list/index page exists yet. When one is introduced, inspect existing analogous pages and retain the established breadcrumbs, page header, filters, statistics and main-content hierarchy where applicable. Do not fabricate catalogue examples.

# Frontend Architecture

## Stack

- Vue 3
- TypeScript with strict mode
- Tailwind CSS
- Vite
- ESLint
- Prettier
- `vue-tsc`

No UI framework is installed by default.

No global state library is installed by default.

## Language

User-facing UI:

- Polish is the default language.
- English is the secondary language.

The mandatory administration panel is English-only and is not translated to Polish.

Backend, tooling, logs, source code, and documentation remain English-only.

Polish Laravel validation/localization resources are required from the start for user-facing messages. Technical backend output, machine codes and admin messages remain English. The approved implementation uses pinned vendored translation data with Laravel’s native loader; no `laravel-lang/lang` or `laravel-lang/common` dependency is permitted. Provenance, MIT attribution and deliberate update instructions are in `docs/development/localization.md`.

## Responsive and themes

Design every user-facing feature for:

- desktop,
- tablet,
- mobile.

Support light and dark themes from the beginning.

Do not treat mobile as a stripped-down product. Adapt presentation without hiding important functionality.

Breakpoints follow content needs rather than a catalog of device models.

## HTTP client

The SPA uses one thin HTTP client/wrapper.

It owns transport concerns such as:

- base URL,
- CSRF/cookie behavior,
- standard headers,
- normalized error envelope handling,
- network/session-wide concerns.

It must not globally toast every API error. Feature-specific presentation remains near the use case.

Do not scatter raw `fetch` calls.

## Validation

Frontend validation is UX-authoritative for conditions the client already knows. It should prevent known invalid submissions immediately.

Backend validation/integrity is always authoritative for security and final correctness.

Where useful, expose simple shared validation metadata:

- required,
- min/max,
- length,
- simple format,
- enum choices.

Do not attempt to serialize the entire Laravel validation system into the frontend. Complex business and authorization state remains backend-owned.

Custom form components disable native browser validation/messages when those conflict with controlled application UX.

Neutralize browser autofill colors where necessary to preserve theme consistency.

Where simple validation metadata is shared from backend to frontend, include a consistency check so obvious required/min/max/length/format/enum drift is detected rather than silently diverging.

## Shared UX states

Provide consistent patterns for:

- loading,
- empty state,
- network error,
- retry,
- forbidden,
- not found.

Forms have local submit/busy state and consistent common error behavior.

Busy state is not the same as disabled state.

Block accidental duplicate submissions while preserving the user's form data after server errors.

## Accessibility baseline

WCAG 2.2 AA is the project baseline, not a formal certification claim.

- Prefer semantic HTML before ARIA.
- All functionality must be keyboard accessible.
- No keyboard traps.
- Focus must be visible.
- Manage focus for modal/temporary layers and return it appropriately.
- Forms have real labels; placeholders are not labels.
- Instructions and errors are programmatically related to controls.
- Important dynamic messages are exposed to assistive technology.
- Color is not the only carrier of meaning.
- Design-system contrast targets AA.
- UI must remain functional at 200% zoom/text scaling.
- Minimum WCAG 2.2 target size is approximately 24x24 CSS px; prefer about 44x44 for frequent touch actions when practical.
- Respect `prefers-reduced-motion` without breaking state communication.
- No critical action/information may exist only on hover.
- Screen-reader checks are performed periodically for critical/complex flows rather than mechanically for every button.

Accessibility is part of normal component review.

## Content robustness

Test:

- long text,
- missing/empty content,
- Polish/English translation expansion.

Do not design only against ideal short English labels.

## Component/UI behavior

Complex components have explicit state ownership.

Rendering/computed logic must not contain non-obvious side effects.

Relevant controls define complete states:

- normal,
- hover,
- focus,
- active,
- selected,
- disabled,
- busy.

Use clear action hierarchy:

- primary,
- secondary,
- destructive.

Action text describes the outcome (`Save changes`, not generic `OK`).

Use modals only when the interaction genuinely benefits from modality.

Protect destructive actions proportionally to consequence, using confirmation, undo, or equivalent behavior as appropriate.

## Design system

Use pragmatic design tokens for repeated:

- colors,
- spacing,
- radii,
- typography.

Add a token only when it reduces real drift/repetition.

Design pages around user tasks and information hierarchy, not around maximizing card/component count.

Do not default every layout to a dashboard grid of cards.

Motion communicates state/change; it is not decoration by default.

Do not rebuild the whole design system during an unrelated roadmap.

## Performance

Understand actual render/request/table/image/bundle costs before optimizing.

Lazy-load/code-split genuinely heavy isolated areas, not every component.

Do not render huge collections blindly. Add virtualization only when data volume and measured behavior justify it.

Media should use known dimensions/aspect ratios and lazy loading where sensible to avoid layout shift and wasted transfer.

## Acceptance

Code appearance is not frontend acceptance.

For frontend changes, inspect the rendered UI at representative sizes:

- desktop around 1440x900,
- tablet around 1024x768,
- mobile around 390x844.

Check:

- relevant light/dark states,
- PL/EN where user-facing,
- console errors/warnings,
- failed network requests,
- accidental 404/500 responses,
- forms and validation.

Rendered behavior is the acceptance truth.

## Component and page discovery

Before creating a page or form, inspect two analogous existing pages/forms when available and search existing shared controls/layouts. If fewer exist, inspect what is available and establish the first pattern deliberately. Never invent examples that do not exist.

Reuse suitable project controls instead of native replacements, including date inputs, selects, labels, errors and actions. The ApplicationLayout workspace hierarchy is heading/description/actions, breadcrumbs, optional statistics, optional toolbar, then mandatory content, with an optional supplementary panel. The sidebar stays viewport-fixed and scrolls independently; the top header remains visible while the toolbar scrolls normally. These shell features are implemented in RM-2026-004 point 3 and await visual acceptance; business modules remain deferred.

The maintained catalogue lives in `.agents/skills/frontend/references/components.md`; page, form and table patterns live alongside it. Update those references in the same task whenever a reusable component or pattern is established, replaced or removed. Record real paths, intended use, relevant contracts and canonical examples. Persist reusable user corrections without waiting for a separate documentation request.

Disable native form validation for controlled application forms and show errors through the common application UX. Preserve labels, input semantics, autofill/password-manager support and accessible error associations. Block known invalid submits and duplicate submits; preserve recoverable input on 409/422/server failures. A conflict offers a clear refresh/recovery path instead of silent overwriting.

## Public UI boundary and temporary review

Import individual existing primitives from `@/runa/ui` (Vite, Vitest and TypeScript share the alias). The facade exports Button, Tooltip, Icon, Form, Select, LoadingOverlay and Logo without importing a layout, router, registry or global installation. Include the application's `resources/css/app.css` for theme tokens/fonts/styles. The underlying App-prefixed components remain the single implementations.

Public identity, navigation, preference-storage, selection/export/provider and print types live behind the same facade. Identity, exact-path page resolution and versioned preferences are implemented. Selection/export/provider/print types remain integration contracts; export engines are not delivered yet. Existing `runa.locale`/`runa.theme` behavior remains unchanged. Application versions are independent of platform versions; URL-derived route context is separate from persisted navigation appearance.

The temporary `resources/review/` entry is built separately with `./bin/pnpm exec vite build --config vite.review.config.mjs` into ignored `public/ui-review/`. In `local`/`testing`, named route `review.ui` serves `/__review/ui`; a missing build returns 404. Other environments do not register it, and its controller rejects stale review routes outside local/testing. No environment flag enables production review. The main production entry/import graph and Docker build output copies exclude this separate entry/output. `http://localhost:8080/__review/ui` uses the normal development Nginx endpoint. Category controls currently demonstrate existing controls only; the minimal layout preview is available at `/__review/ui?preview=minimal`; application layout preview is available at `/__review/application/overview`; the native email preview is available at `/__review/email`; native error samples are available at `/__review/errors/{403,404,419,429,500,503}` and the generated emergency document at `/__review/errors/emergency`. `/__review/not-found` is a real branded 404. There is no catch-all success route. Remove temporary infrastructure at roadmap point 21 after explicit final visual acceptance.

## Minimal layout and branding

`MinimalLayout`, `IdentityProvider` and `Footer` are public exports from `@/runa/ui`. The root uses MinimalLayout directly; AuthLayout was removed by owner request. MinimalLayout includes the supplied Runa illustration by default; the content/default slot occupies the left side, matching `/`. Named header/content/illustration/actions/footer slots permit overrides; `illustration: false` explicitly selects a single column. The default header owns existing locale/theme controls; replacing it replaces those controls too. Footer visibility applies to custom footer slots as well.

```vue
<script setup lang="ts">
import { IdentityProvider, MinimalLayout } from '@/runa/ui';
</script>
<template>
  <IdentityProvider
    :identity="{
      applicationId: 'atlas',
      applicationName: 'Atlas',
      applicationVersion: '2.4.1',
    }"
  >
    <MinimalLayout :footer="{ copyright: false, repository: false }">
      <template #default="{ t }"
        ><h1>{{ t('title') }}</h1></template
      >
      <template #actions><a href="/">Home</a></template>
    </MinimalLayout>
  </IdentityProvider>
</template>
```

The example supplies an application version only; platformVersion defaults to actual root VERSION. Configure versions independently. Without a provider bundled Runa identity works out of the box. Provider overrides are reactive, subtree-scoped and normalized; image assets use an absolute same-origin path or credential-free HTTPS URL. Invalid/missing/failed logos fall back to the supplied mark plus application name. External logo hosts must also be permitted by the deployment CSP. FooterOptions independently hides the whole footer or its application/platform/copyright/repository elements, and customizes copyright/link text and URL. The footer stays in document flow; long content cannot slide beneath it. Point 2 was visually accepted by the owner on 2026-10-18. The reported intermittent tooltip dismissal defect is explicitly deferred to point 9, including integration of finalized Runa controls into MinimalLayout.

## Application layout, route context and preferences

Import `ApplicationLayout`, `usePageResolver`, `createPageResolver`, `createPreferenceStore`, `usePreference` and their types from `@/runa/ui`. ApplicationLayout is independent of module discovery, Auth and a router installation. Supply navigation descriptors, a title and current URL-derived RouteContext; handle its `navigate(href, event)` event with your resolver. `localeChange(locale)` emits the initial and subsequent locale so callers can translate navigation labels without initializing another locale composable. Modified/middle clicks retain native browser behavior. Standalone component consumers still initialize no shell or preferences.

```ts
const pages: PageDescriptor[] = [
  {
    routeKey: 'projects.index',
    moduleKey: 'projects',
    areaKey: 'list',
    href: '/projects',
    serverRouteName: 'projects.index',
    title: 'Projects',
  },
];
const { current, navigate } = usePageResolver(pages);
```

Every descriptor must have an exact same-origin path and corresponding named server entry serving the application's Vue entry document. The resolver rejects duplicate paths, external paths and query/hash in registered descriptors. Navigation can preserve query/hash, which do not select a module. Back/forward and refresh reconstruct context from the URL; unknown paths remain server 404s, never catch-all success pages. Call the composable in component setup so its popstate listener is disposed. The host application registers its real server entries; this point registers only five synthetic local/testing entries (`review.application.overview/projects/activity/archive/team`) and no production application pages.

ApplicationLayout props: `navigation`, optional `topNavigation`, `current`, `title`/`description`, independent `sidebar`/`topbar` (both true), `width` (contained/fluid), `footer`, optional `preferenceStore`, `userName` and `userInitials`. Navigation entries use stable keys, route/module/area identity, label, optional typed Tabler icon, absolute same-origin href, availability and children. Missing sidebar icons use the existing Tabler layers icon. Hidden entries are omitted; disabled/invalid-link entries cannot navigate. Availability is presentation only; every backend operation still requires server authorization.

Workspace order is heading/description/actions, breadcrumbs, optional statistics, optional toolbar, then content and optional supplementary panel. Slots: heading, actions, breadcrumbs, statistics, toolbar, content (falls back to default), supplementary, sidebar-bottom, user-menu and footer; slots expose `t`, sidebar-bottom also exposes `collapsed`. Slots provide composition, not finished KPI/table/dialog components. The initials disclosure contains only an unavailable-account explanation by default, with no fake authentication operations. `topbar: false` hides module-area navigation and avatar, retaining utility controls and the phone sidebar trigger. The left sidebar never relocates; desktop collapse/group expansion are stored, while active selection always comes from `current`. A single optional supplementary region stacks below content on narrow screens. Footer configuration and identity are shared with MinimalLayout.

Below 64rem the sidebar is an inert offscreen panel until opened. Its drawer traps Tab/Shift+Tab, closes on Escape/backdrop and restores focus; successful navigation closes it and focuses main. Body scrolling is locked only while the drawer is open, and listeners/body style are cleaned up on unmount or resize. At desktop size the sidebar is viewport-fixed with its own scrollable navigation region. The top header is sticky and remains visible during page scrolling; the toolbar scrolls normally. The owner's later point-3 feedback supersedes the original nonsticky-header premise. The sidebar brand row and top header both use 64px height; the Runa mark uses the same 42px square as MinimalLayout. The collapse/expand or phone-drawer control precedes top navigation. Sidebar groups are whole-row disclosure buttons, with their chevron inside the control rather than beside a navigable parent label. The top header uses a compact 64px row, with the navigation scrollbar flush to its bottom edge. Top navigation overflows horizontally without widening the document. Its internal AppTopNavigation renders group triggers and teleports each open dropdown card outside the scrolling nav into body. Cards are viewport-clamped below the trigger/header, overlay workspace content and never change header height; sidebar groups continue expanding inline. Escape returns focus, Tab/Shift+Tab reconnect to the trigger/next navigation item, and outside clicks, navigation, document scroll and resize dismiss the card; horizontal nav scrolling updates its anchor position. While it overflows, wheel events over navigation move it horizontally and do not scroll the document, including at either end; horizontal trackpad deltas and line/page wheel modes are supported, while Ctrl+wheel keeps native zoom behavior. Collapsed link tooltips use a narrow right-placement extension of the existing tooltip; the separately reported hover-dismissal defect remains assigned to point 9.

Both layouts share AppPreferenceControls/useDisplayPreferences, preserving the original cover timing, inert content and focus return. Locale/theme still read and write `runa.locale`/`runa.theme`; the versioned store imports legacy values when no scoped record exists. New values use `runa.preferences:<encoded application>:<module>:<view>:<control>` with `{ version, value }` JSON. `createPreferenceStore(adapter)` accepts the existing read/write/remove interface. Each definition supplies a version, default, type guard and optional migration/legacy key. Invalid/corrupt/unknown versions use defaults; reset removes the scoped entry. Writes validate finite JSON values before storage. Adapter denial/quota failure switches that store to memory for its lifetime. Use stable application IDs for a mounted layout; create a new store/session to retry a failed adapter. No account sync or security state belongs here.

Shared print rules hide navigation, backdrop, footers and `[data-print-hide]`, remove sidebar offset and format content. Views can explicitly override these selectors with their own print stylesheet (matching specificity and `!important` for hidden regions). The review entry is separate from the production graph; its controls exercise optional regions, width and long-content behavior. Point 3 awaits owner visual acceptance.

## Native email layout

Standard Laravel Markdown mailables (`<x-mail::message>`) and non-Auth `MailMessage` notifications use the default Runa theme through `mail.markdown`, without a custom mail engine. Server-rendered email views are an explicit exception to the Vue-only application UI rule. No Auth notification class or callback is customized.

`config/branding.php` exposes application ID/name/version, independent platform version, logo inputs and footer visibility. Bundled versions read the actual root VERSION; set application/platform versions independently for an external application, matching the Vue IdentityProvider fields. The mail logo is a high-density snapshot of the actual Vue light-theme brand tile, with the unchanged supplied PNG as its source; VERSION is shared with Vue. Both web CSS and email colors read `resources/css/light-palette.css`; email resolves these safe literal colors at configuration load and Laravel inlines the theme. Keep this palette as simple six-digit hexadecimal custom properties. Web dark tokens remain separate and unchanged. After changing branding/palette, rebuild cached configuration and restart long-running mail workers.

Email is light-only, centered at 600px, uses presentation tables, approximately 4px corners and the shared light palette. IBM Plex Mono falls back to Consolas/Courier New/monospace; it does not download web fonts. Mobile styles reduce padding and fit the card to the viewport. Some email clients enforce their own dark-mode colors; browser previews cannot certify their rendering.

The default header matches MinimalLayout: a 42px violet brand tile and adjacent `runa.` with an accent-colored dot. Other application identities retain their configured names. Default logo delivery is CID from the trusted `branding.mail_logo_path` PNG/JPEG. `MailBranding` adds the inline MIME part at Laravel's `MessageSending` boundary only when rendered HTML references its reserved CID. This works with anonymous Markdown components, which do not inherit `$message`. No database, JavaScript, Vite manifest or SMTP service is needed for template rendering. Set `mail_logo_url` to a credential-free absolute HTTPS URL to use a public image instead; the server does not fetch it. Invalid URLs/missing local images omit the image while the application name remains visible. `mail_logo_alt` overrides image alternative text; name is the default. Local paths are trusted developer configuration, never request input.

Override the header/footer/message/layout independently through `resources/views/vendor/runa-mail/{html,text}/...blade.php` (namespace `runa-mail`). Supply matching text overrides when changing meaningful HTML regions. Native actions/panels/subcopy retain Laravel's `mail::` component contracts; customize native components through the configured Markdown component paths or a caller's component/theme. A custom body uses an ordinary Markdown view with `<x-mail::message>`; a fully custom layout may use `<x-mail::layout>` with header/default/subcopy/footer slots. Footer defaults to independent application/platform versions with Polish/English platform attribution; `branding.mail_footer` independently controls whole-footer/application/platform visibility. No source edits are needed by consumers.

Laravel generates text automatically from Markdown/text components. For custom HTML with lossy conversion, use native `Content(html: ..., text: ...)` or `Mailable::text(...)` and provide an explicit text view. Raw HTML views must provide email-compatible inline CSS themselves; the automatic theme inliner belongs to Markdown rendering. Keep URLs as text alternatives to important actions. Dynamic values must use Blade escaping; raw slots are trusted template markup, not unsanitized user HTML.

Production PHP explicitly includes the native/namespaced views, mail brand-tile PNG, shared light palette and VERSION. Temporary email fixtures remain under `resources/review/mail` and are excluded from runtime copies. The named local/testing `review.email` route serves HTML by default, `?format=text` and `?format=eml`, with `locale=pl|en`, `long=1`, and `images=cid|blocked|public`. It uses only Laravel's array transport and a synthetic `.invalid` recipient, never external delivery. HTML preview substitutes CID bytes as a data URL; downloaded EML retains actual related MIME/CID structure. This temporary route rejects production/staging registration and stale-route invocation, and is removed in point 21.

## HTTP error pages

HTTP errors use **MinimalLayout.vue itself**, also used by the welcome dashboard and reserved for future Auth screens. They are not a second PHP/CSS shell. `resources/errors/render.mjs` pre-renders the actual MinimalLayout with IdentityProvider and a content-only `MinimalErrorContent.vue` slot during `pnpm run build:errors` (also part of the normal frontend build). This includes the same AppBackdrop/constellations, default AppBrandIllustration, 42px logo/header, responsive split/stack composition and AppFooter. No Auth behavior is introduced.

MinimalLayout's optional `staticPreferences: { locale, theme }` mode is for build-time rendering: it avoids browser storage/DOM hooks and renders the existing AppPreferenceControls/AppButton/AppIcon as ordinary GET forms. Language/theme controls work without JavaScript and retain the other selection. Normal interactive layout consumers retain their existing preferences, cover timing, persistence and focus behavior. The static mode does not add runtime Node/Vue rendering to PHP or Nginx.

Generated private HTML shells live in ignored `resources/views/errors/generated/`, with native status/PL/EN/theme and default/custom-identity variants. Laravel's six native error views retain a thin `errors/page.php` document wrapper which selects a pre-rendered shell and escapes application name, independent versions and request ID into fixed markers. The same wrapper finalizes static 503 documents. Localized copy stays in `resources/errors/messages.json`. Exception messages/traces are not rendered; API/JSON/status/retry contracts remain native. Private shells are shipped as runtime view assets, independently of public Vite JS/build output; deleting `public/build` does not prevent error rendering.

The build-only SSR entry uses the server-renderer export already supplied by the locked Vue dependency. Generated `public/runa-error/styles.css`, the original logo and local font assets are emitted from the real component styles/main stylesheet; there is no separately maintained error layout stylesheet, copied illustration or second palette implementation. Only a CSS system-theme selector is added from the emitted shared dark token block. No SSR JavaScript bundle is published or copied into runtime images. Generated views/assets must be rebuilt after changing shared layouts/components/styles, localization or identity inputs.

Development setup runs the normal frontend build and `php resources/errors/build.php`. For a manual rebuild, run `./bin/pnpm run build:errors` then `php resources/errors/build.php`. The PHP finalizer does not boot Laravel, load secret/runtime env or query a database; it uses the committed non-secret branding config and actual VERSION. Production builds the shared shells/assets in the Node stage, finalizes static documents in an isolated PHP build stage and copies only generated HTML/CSS/assets into PHP/Nginx images. Host-generated output is excluded from Docker context. Keep root `vite.errors.config.mjs` in managed sparse checkouts.

Default locale is Polish; `?lang=pl|en` or Accept-Language selects native pages. `?theme=light|dark` selects an explicit theme, otherwise system colors apply through CSS. Browser-local SPA preferences are not readable by PHP; the native controls use query parameters. Technical HTTP codes remain English. The independent Nginx emergency response uses the same pre-rendered MinimalLayout and selects static PL/EN/system/light/dark documents from the whitelisted query values; see `docs/production/architecture.md`. Its metadata is fixed at build time, since a failed application cannot provide runtime identity.

Local/testing samples under `/__review/errors/{403,404,419,429,500,503}` preserve real statuses; `/__review/not-found` is an actual branded 404 and `/__review/errors/emergency` previews the generated document through PHP. The preview is not proof of Nginx fallback. All review registrations/controllers reject production/staging and retire at point 21. Permanent error views/assets remain.

## Browser favicon

The shared browser icon is the committed `public/favicon.ico`, derived from the actual MinimalLayout brand tile, with 16/32/48px frames. The HTML entry heads (`resources/index.html` and `resources/review/index.html`) declare it once for both MinimalLayout and ApplicationLayout, including their review routes. Native/static emergency error documents reference the same `/favicon.ico`; both production images copy it independently of the frontend bundle and PHP availability. Provenance and regeneration are documented in [design assets](../design/assets.md#browser-favicon). No layout/component contract changes are required.

## Shared action controls

Public `@/runa/ui` action imports include Button, ButtonGroup and Icon, with exported ControlSize/ControlTone/ButtonVariant types. Controls remain shell-independent. Button preserves its original neutral/md appearance and compact compatibility; explicit variants use shared semantic tokens. Busy state blocks native activation. ButtonGroup joins adjacent controls with one divider. ApplicationLayout consumes Button while retaining its unique initials profile control. Action-list/split buttons and reusable avatars are deferred until a concrete need exists, per owner review. See the component catalogue for full contracts.

The temporary `/__review/ui` controls category grows with each visual roadmap point; later component groups remain unimplemented until their own point. The review entry remains development/testing only and retires in point 21.

## Shared feedback and floating layers

Public `@/runa/ui` exports Alert, Badge, Status, Tooltip, Popover, ToastViewport and setup-scoped useToasts. Feedback uses success/info/warning/danger roles; badge/status also accept neutral. Readable labels and alert symbols supplement color. Static badges/statuses do not announce automatically; Alert provides live semantics unless explicitly disabled. ToastViewport supplies one polite region, explicit close controls and top-right responsive placement. Its local queue defaults to 5-second notifications and persistent errors, pauses remaining time on hover/focus and restores the invoking control when a focused toast is dismissed. Features retain important errors inline; no notification history or server service is added.

Tooltip and Popover share internal useFloatingLayer for viewport-clamped measured placement, scroll/resize/content updates, top-layer Escape/outside dismissal and cleanup. Tooltip separates pointer and keyboard focus, dismisses on click/scroll and bridges the gap into its content without stale hover. Popover is a named nonmodal dialog with focus entry/return and Tab exit. ApplicationLayout reuses Popover for its existing user-menu slot while preserving its unique initials button; AppPreferenceControls supplies finalized Tooltip to both layouts. Specialized navigation anchoring/route semantics remain internal to AppTopNavigation. All controls remain shell-independent; the temporary feedback catalog remains excluded from production.

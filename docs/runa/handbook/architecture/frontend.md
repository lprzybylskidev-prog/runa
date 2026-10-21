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

Motion communicates state/change; it is not decoration by default. Shared popup/content cues use 140ms, disclosures/control colors 160ms, following the accepted shell reference. Internal AppCollapse retains disclosure drafts while making closed content inert; useContentMotion reveals changed retained content without remounting. Leaving floating/dialog/list content stops accepting input immediately, and interrupted reopening restores it. Reduced-motion and print remove nonessential motion, including active programmatic animations. The complete component audit and maintenance rules are in `.agents/skills/frontend/references/motion.md`; the point-21 extension was visually accepted on 2026-10-19.

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

The maintained catalogue lives in `.agents/skills/frontend/references/components.md`; page, form and table patterns live alongside it. Update those references in the same task whenever a reusable component or pattern is established, replaced or removed. Record real paths, intended use, relevant contracts and canonical examples. Persist reusable user corrections without waiting for a separate documentation request.

Disable native form validation for controlled application forms and show errors through the common application UX. Preserve labels, input semantics, autofill/password-manager support and accessible error associations. Block known invalid submits and duplicate submits; preserve recoverable input on 409/422/server failures. A conflict offers a clear refresh/recovery path instead of silent overwriting.

## Public UI boundary and isolated tests

Import individual existing primitives from `@/runa/ui` (Vite, Vitest and TypeScript share the alias). The facade exports Button, Tooltip, Icon, Form, Select, LoadingOverlay and Logo without importing a layout, router, registry or global installation. Include the application's `src/runa/resources/css/app.css` for theme tokens/fonts/styles. The underlying App-prefixed components remain the single implementations.

Public identity, navigation, preference-storage, selection/export/provider and print types live behind the same facade. Identity, exact-path page resolution and versioned preferences are implemented. Selection/export/provider/print contracts include delivered bounded browser export adapters; domain endpoints and server jobs remain application-owned. Existing `runa.locale`/`runa.theme` behavior remains unchanged. Application versions are independent of platform versions; URL-derived route context is separate from persisted navigation appearance.

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

The example supplies an application version only; platformVersion defaults to src/runa/platform.json. Configure versions independently. Without a provider the build-time application manifest ID/name/version and platform manifest version work out of the box. Normal startup refreshes these public fields from `/api/identity` through `loadApplicationIdentity`, using the existing HTTP wrapper and IdentityProvider; failed requests retain build-time identity without delaying initial rendering. Provider overrides are reactive, subtree-scoped and normalized; image assets use an absolute same-origin path or credential-free HTTPS URL. Invalid/missing/failed logos fall back to the supplied mark plus application name. External logo hosts must also be permitted by the deployment CSP. FooterOptions independently hides the whole footer or its application/platform/copyright/repository elements, and customizes copyright/link text and URL. The footer stays in document flow; long content cannot slide beneath it. Point 2 was visually accepted by the owner on 2026-10-18. The reported intermittent tooltip dismissal defect was corrected in point 9, including integration of finalized Runa controls into MinimalLayout.

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

Workspace order is heading/description/actions, breadcrumbs, optional statistics, optional toolbar, then content and optional supplementary panel. Slots: heading, actions, breadcrumbs, statistics, toolbar, content (falls back to default), supplementary, sidebar-bottom, user-menu and footer; slots expose `t`, sidebar-bottom also exposes `collapsed`. Slots provide composition; public Statistics/Statistic supply optional KPI content; Table/TableActions/TablePreferences and dialogs are available as independent primitives. The initials disclosure contains only an unavailable-account explanation by default, with no fake authentication operations. `topbar: false` hides module-area navigation and avatar, retaining utility controls and the phone sidebar trigger. The left sidebar never relocates; desktop collapse/group expansion are stored, while active selection always comes from `current`. A single optional supplementary region stacks below content on narrow screens. Footer configuration and identity are shared with MinimalLayout.

Below 64rem the sidebar is an inert offscreen panel until opened. Its drawer traps Tab/Shift+Tab, closes on Escape/backdrop and restores focus; successful navigation closes it and focuses main. Body scrolling is locked only while the drawer is open, and listeners/body style are cleaned up on unmount or resize. At desktop size the sidebar is viewport-fixed with its own scrollable navigation region. The top header is sticky and remains visible during page scrolling; the toolbar scrolls normally. The owner's later point-3 feedback supersedes the original nonsticky-header premise. The sidebar brand row and top header both use 64px height; the Runa mark uses the same 42px square as MinimalLayout. The collapse/expand or phone-drawer control precedes top navigation. Sidebar groups are whole-row disclosure buttons, with their chevron inside the control rather than beside a navigable parent label. The top header uses a compact 64px row, with the navigation scrollbar flush to its bottom edge. Top navigation overflows horizontally without widening the document. Its internal AppTopNavigation renders group triggers and teleports each open dropdown card outside the scrolling nav into body. Cards are viewport-clamped below the trigger/header, overlay workspace content and never change header height; sidebar groups continue expanding inline. Escape returns focus, Tab/Shift+Tab reconnect to the trigger/next navigation item, and outside clicks, navigation, document scroll and resize dismiss the card; horizontal nav scrolling updates its anchor position. While it overflows, wheel events over navigation move it horizontally and do not scroll the document, including at either end; horizontal trackpad deltas and line/page wheel modes are supported, while Ctrl+wheel keeps native zoom behavior. Collapsed link tooltips use a narrow right-placement extension of the existing tooltip; the hover-dismissal correction was delivered and accepted in point 9.

Both layouts share AppPreferenceControls/useDisplayPreferences, preserving the original cover timing, inert content and focus return. Locale/theme still read and write `runa.locale`/`runa.theme`; the versioned store imports legacy values when no scoped record exists. New values use `runa.preferences:<encoded application>:<module>:<view>:<control>` with `{ version, value }` JSON. `createPreferenceStore(adapter)` accepts the existing read/write/remove interface. Each definition supplies a version, default, type guard and optional migration/legacy key. Invalid/corrupt/unknown versions use defaults; reset removes the scoped entry. Writes validate finite JSON values before storage. Adapter denial/quota failure switches that store to memory for its lifetime. Use stable application IDs for a mounted layout; create a new store/session to retry a failed adapter. No account sync or security state belongs here.

Shell controls use a 36px sidebar toggle inset 24px from the header left edge. Top-navigation overflow has decorative dotted edge indicators, shown only in directions containing hidden content; scroll, viewport/sidebar resizing and navigation-content changes refresh them. Indicators fade over 160ms, with reduced-motion support, and do not intercept input. The collapsed sidebar centers disabled icons identically to enabled entries, omits the supplementary sidebar-bottom slot and retains the platform version without the RUNA prefix in its footer.

Collapsed sidebar content follows a no-reserved-space rule: remove headings, labels, nested navigation subtrees and supplementary sections from layout, including their margins/gaps/padding. The icon list starts 8px below the brand row and uses the ordinary icon-row spacing; expanded-only 28px section padding does not carry over. Nested expansion state is preserved for reopening, but its containers and transition wrappers are unmounted while collapsed. The brand icon and the explicitly retained version footer remain.

Shared print rules hide navigation, backdrop, footers and `[data-print-hide]`, remove sidebar offset and format content. Views can explicitly override these selectors with their own print stylesheet (matching specificity and `!important` for hidden regions). The review entry is separate from the production graph; its controls exercise optional regions, width and long-content behavior. Point 3 was visually accepted by the owner on 2026-10-18.

## Native email layout

Standard Laravel Markdown mailables (`<x-mail::message>`) and non-Auth `MailMessage` notifications use the default Runa theme through `mail.markdown`, without a custom mail engine. Server-rendered email views are an explicit exception to the Vue-only application UI rule. No Auth notification class or callback is customized.

`src/runa/config/branding.php` projects canonical ID/name/application version from `src/app/application.json` and platform version from `src/runa/platform.json`. `ApplicationIdentity` supplies public API and mail/error consumers. Configure identity in the application manifest; customer branding config retains mail assets/footer visibility but cannot override canonical identity fields. The mail logo is a high-density snapshot of the actual Vue light-theme brand tile, with the unchanged supplied PNG as its source; the platform and application manifest identities are shared with Vue. Both web CSS and email colors read `src/runa/resources/css/light-palette.css`; email resolves these safe literal colors at configuration load and Laravel inlines the theme. Keep this palette as simple six-digit hexadecimal custom properties. Web dark tokens remain separate and unchanged. After changing branding/palette, rebuild cached configuration and restart long-running mail workers.

Email is light-only, centered at 600px, uses presentation tables, approximately 4px corners and the shared light palette. IBM Plex Mono falls back to Consolas/Courier New/monospace; it does not download web fonts. Mobile styles reduce padding and fit the card to the viewport. Some email clients enforce their own dark-mode colors; browser previews cannot certify their rendering.

The default header matches MinimalLayout: a 42px violet brand tile and adjacent `runa.` with an accent-colored dot. Other application identities retain their configured names. Default logo delivery is CID from the trusted `branding.mail_logo_path` PNG/JPEG. `MailBranding` adds the inline MIME part at Laravel's `MessageSending` boundary only when rendered HTML references its reserved CID. This works with anonymous Markdown components, which do not inherit `$message`. No database, JavaScript, Vite manifest or SMTP service is needed for template rendering. Set `mail_logo_url` to a credential-free absolute HTTPS URL to use a public image instead; the server does not fetch it. Invalid URLs/missing local images omit the image while the application name remains visible. `mail_logo_alt` overrides image alternative text; name is the default. Local paths are trusted developer configuration, never request input.

Override the header/footer/message/layout independently through `src/runa/resources/views/vendor/runa-mail/{html,text}/...blade.php` (namespace `runa-mail`). Supply matching text overrides when changing meaningful HTML regions. Native actions/panels/subcopy retain Laravel's `mail::` component contracts; customize native components through the configured Markdown component paths or a caller's component/theme. A custom body uses an ordinary Markdown view with `<x-mail::message>`; a fully custom layout may use `<x-mail::layout>` with header/default/subcopy/footer slots. Footer defaults to independent application/platform versions with Polish/English platform attribution; `branding.mail_footer` independently controls whole-footer/application/platform visibility. No source edits are needed by consumers.

Laravel generates text automatically from Markdown/text components. For custom HTML with lossy conversion, use native `Content(html: ..., text: ...)` or `Mailable::text(...)` and provide an explicit text view. Raw HTML views must provide email-compatible inline CSS themselves; the automatic theme inliner belongs to Markdown rendering. Keep URLs as text alternatives to important actions. Dynamic values must use Blade escaping; raw slots are trusted template markup, not unsanitized user HTML.

Production PHP includes native/namespaced views, the mail brand-tile PNG, shared light palette and platform manifest. No mail preview controller, route or fixture namespace is registered by the application. Permanent mail/browser templates live under tests/Fixtures and use only the array transport with synthetic .invalid recipients. Test HTML substitutes CID bytes locally; MIME retains actual related parts. Production has no test templates or browser-document adapter.

## HTTP error pages

HTTP errors use **MinimalLayout.vue itself**, also used by the welcome dashboard and reserved for future Auth screens. They are not a second PHP/CSS shell. `src/runa/resources/errors/render.mjs` pre-renders the actual MinimalLayout with IdentityProvider and a content-only `MinimalErrorContent.vue` slot during `pnpm run build:errors` (also part of the normal frontend build). This includes the same AppBackdrop/constellations, default AppBrandIllustration, 42px logo/header, responsive split/stack composition and AppFooter. No Auth behavior is introduced.

MinimalLayout's optional `staticPreferences: { locale, theme }` mode is for build-time rendering: it avoids browser storage/DOM hooks and renders the existing AppPreferenceControls/AppButton/AppIcon as ordinary GET forms. Language/theme controls work without JavaScript and retain the other selection. Normal interactive layout consumers retain their existing preferences, cover timing, persistence and focus behavior. The static mode does not add runtime Node/Vue rendering to PHP or Nginx.

Generated private HTML shells live in ignored `src/.runtime/runa-errors/`, with native status/PL/EN/theme and default/custom-identity variants. Laravel's six native error views retain a thin `errors/page.php` document wrapper which selects a pre-rendered shell and escapes application name, independent versions and request ID into fixed markers. The same wrapper finalizes static 503 documents. Localized copy stays in `src/runa/resources/errors/messages.json`. Exception messages/traces are not rendered; API/JSON/status/retry contracts remain native. Private shells are shipped as runtime view assets, independently of public Vite JS/build output; deleting `src/public/build` does not prevent error rendering.

The build-only SSR entry uses the server-renderer export already supplied by the locked Vue dependency. Generated `src/public/runa-error/styles.css`, the original logo and local font assets are emitted from the real component styles/main stylesheet; there is no separately maintained error layout stylesheet, copied illustration or second palette implementation. Only a CSS system-theme selector is added from the emitted shared dark token block. No SSR JavaScript bundle is published or copied into runtime images. Generated views/assets must be rebuilt after changing shared layouts/components/styles, localization or identity inputs.

Development setup runs the normal frontend build and `php src/runa/resources/errors/build.php`. For a manual rebuild, run `./bin/pnpm run build:errors` then `php src/runa/resources/errors/build.php`. The PHP finalizer does not boot Laravel, load secret/runtime env or query a database; it validates the customer application manifest and uses the committed non-secret branding config and actual platform manifest version. Production builds the shared shells/assets in the Node stage, finalizes static documents in an isolated PHP build stage and copies only generated HTML/CSS/assets into PHP/Nginx images. Host-generated output is excluded from Docker context. Keep root `src/vite.errors.config.mjs` in managed sparse checkouts.

Default locale is Polish; `?lang=pl|en` or Accept-Language selects native pages. `?theme=light|dark` selects an explicit theme, otherwise system colors apply through CSS. Browser-local SPA preferences are not readable by PHP; the native controls use query parameters. Technical HTTP codes remain English. The independent Nginx emergency response uses the same pre-rendered MinimalLayout and selects static PL/EN/system/light/dark documents from the whitelisted query values; see `docs/runa/handbook/production/architecture.md`. Its metadata is fixed at build time, since a failed application cannot provide runtime identity.

Native error tests register synthetic failures only inside their own test process. Browser scenarios use a test-only PHP kernel adapter for real exception negotiation/status/headers and generated emergency documents; missing application paths still pass through Nginx/PHP-FPM. The harness is not proof of a production Nginx failure: the separate host fallback/isolation gate remains required. There are no application error-preview routes.

## Browser favicon

The shared browser icon is the committed `src/public/favicon.ico`, derived from the actual MinimalLayout brand tile, with 16/32/48px frames. The application HTML entry (`src/app/resources/index.html`) declares it once; test harness HTML references the same asset. Native/static emergency error documents reference the same `/favicon.ico`; both production images copy it independently of the frontend bundle and PHP availability. Provenance and regeneration are documented in [design assets](../design/assets.md#browser-favicon). No layout/component contract changes are required.

## Dedicated shell navigation and universal UI

The ApplicationLayout top menu and sidebar, including their entries, disclosures and dropdown compositions, are dedicated to those shell locations. AppTopNavigation and AppNavigation remain internal implementations, not public universal UI controls. The universal components developed from roadmap point 8 onward serve content throughout applications and work independently of these menus and the shell. A generic dropdown, accordion or tabs component must be designed for those content uses, not generalized from a shell menu or forced into one. Reusing a suitable low-level Button, Tooltip or positioning behavior does not make the shell navigation universal; preserve its dedicated layout and interaction contracts.

## Shared action controls

Public `@/runa/ui` action imports include Button, ButtonGroup and Icon, with exported ControlSize/ControlTone/ButtonVariant types. Controls remain shell-independent. Button preserves its original neutral/md appearance and compact compatibility; explicit variants use shared semantic tokens. Busy state blocks native activation. ButtonGroup joins adjacent controls with one divider. ApplicationLayout consumes Button while retaining its unique initials profile control. Action-list/split buttons and reusable avatars are deferred until a concrete need exists, per owner review. See the component catalogue for full contracts.

The retired catalog has no application entry. Isolated component scenarios remain under tests/frontend/fixtures/browser; reusable component contracts stay in the frontend skill catalogue.

## Shared feedback and floating layers

Public `@/runa/ui` exports Alert, Badge, Status, Tooltip, Popover, ToastViewport and setup-scoped useToasts. Feedback uses neutral/success/info/warning/danger roles. Neutral alerts/toasts use the ordinary raised surface, secondary text and control border, without a status tint. Readable labels and alert symbols supplement color. Static badges/statuses do not announce automatically; Alert provides live semantics unless explicitly disabled. ToastViewport supplies one polite region, explicit close controls and top-right responsive placement. Its local queue defaults to 5-second notifications and persistent errors, pauses remaining time on hover/focus and restores the invoking control when a focused toast is dismissed. Features retain important errors inline; no notification history or server service is added.

Tooltip and Popover share internal useFloatingLayer for viewport-clamped measured placement, scroll/resize/content updates, top-layer Escape/outside dismissal and cleanup. Tooltip separates pointer and keyboard focus, dismisses on click/scroll and bridges the gap into its content without stale hover. Popover is a named nonmodal dialog with focus entry/return and Tab exit. ApplicationLayout reuses Popover for its existing user-menu slot while preserving its unique initials button; AppPreferenceControls supplies finalized Tooltip to both layouts. Specialized navigation anchoring/route semantics remain internal to AppTopNavigation. All controls remain shell-independent; isolated feedback scenarios remain test-only.

### Content containers and navigation primitives

The public `@/runa/ui` facade exports Card (header/actions/body/footer slots), Divider, controlled Accordion, Tabs, Dropdown, Breadcrumbs and Pagination. These components work without a shell or router. Tabs use stable typed IDs, named content slots, automatic arrow/Home/End activation and preserved hidden panels. Dropdown composes the existing floating Popover as an arbitrary-content nonmodal disclosure; no action-list/split-button API is added. Breadcrumbs exposes optional native-event interception for the page resolver. Pagination emits bounded page requests and never fetches data. Callers supply localized accessible names and own selection/loading/query changes. The component catalogue documents exact props, fallback behavior and events; specialized shell navigation remains internal.

```vue
<Tabs v-model="section" :items="sections" label="Sekcje">
  <template #overview>...</template>
  <template #details>...</template>
</Tabs>
<Pagination
  v-model="page"
  :pages="pageCount"
  label="Strony"
  previous-label="Poprzednia strona"
  next-label="Następna strona"
/>
```

### Loading, asynchronous states and statistics

Public Spinner is shared by busy Button and LoadingOverlay; decorative embedding avoids duplicate status announcements and preserves the accepted layout transition timing/colors. Skeleton provides bounded decorative text/block/circle placeholders. Progress supports determinate/indeterminate values, finite bounds, localized valueText and semantic tones. Reduced motion removes spinner, skeleton and indeterminate-progress animation. EmptyState composes readable title/description and optional caller actions.

AsyncContent selects a controlled loading/error/empty/success slot, with Spinner/Alert/EmptyState defaults and an optional retry event. It never fetches, cancels requests or infers successful completion. Callers own request races, formatted errors and persistent form state (inactive slots unmount). Localize its default text through props; custom slots own their status announcements.

Statistic supports label/value/icon/trend/visualization slots and neutral/semantic tones without domain calculations. Statistics always exposes the global/filtered switch and provides the selected mode to its content slot. A view supplies both datasets and keeps global values independent of filters. useStatisticsMode uses the existing validated, versioned preference store, scoped by application/module/view identity, with a per-view default and session fallback. Remount on view-identity changes. The isolated application scenario consumes these primitives in its statistics slot and keys persistence by route.

```ts
const mode = useStatisticsMode(
  { applicationId: 'atlas', moduleKey: 'projects', viewKey: 'list' },
  'filtered',
);
```

```vue
<Statistics
  v-model="mode"
  label="Statystyki"
  global-label="Globalne"
  filtered-label="Filtrowane"
>
  <template #default="{ mode: active }">
    <Statistic label="Otwarte tematy"
      :value="active === 'global' ? totals.open : filteredTotals.open" />
  </template>
</Statistics>
```

### Form foundation and exact numeric inputs

Form extends the original controlled AppForm with feedback/actions slots, responsive columns, busy fieldset disabling, valid/dirty metadata and explicit focusInvalid() recovery. FormSection preserves fields through controlled collapse. FormField owns visible labels, stable IDs and linked helper/warning/error descriptions. TextInput covers text/textarea/password/email/url/tel and forwards native field/autofill attributes with controlled novalidate UX.

NumberInput stores exact string/null values. Complete values normalize to decimal dots; PL/EN formatting preserves digits/zeros without binary conversion. Invalid intermediate drafts remain visible and in the model, so validation cannot accidentally submit a stale previous value. Validate with parseDecimal and serialize with serializeDecimal before an API call; optional scale rejects excessive precision rather than rounding. Currency/percentage kinds do not impose domain ranges or rescale values.

useForm accepts finite JSON field values, isolates submitted snapshots, blocks concurrent/known-invalid writes and tracks nested dirty baselines. Successful submission advances only the submitted baseline; reset() restores that baseline and reset(serverSnapshot) explicitly loads new data. ApiError 422 maps exact nested Laravel keys; 409 marks conflict without replacing input, and other failures remain recoverable. Screens own immediate business validation, accessible error summaries, optimistic-lock tokens and deliberate comparison/reload/retry. No business write endpoints or account storage are provided; optional masks/navigation guards are described below. Detailed component/decimal/error examples are maintained in the frontend form reference.

### Additional input foundation

DateInput, TimeInput, DateTimeInput, DateRangeInput, Checkbox, Switch, RadioGroup, FileInput, ColorInput, HiddenInput and TagsInput are public primitives. Date-only strings never undergo timezone conversion; moment editing requires an explicit zone and UTC serialization, rejecting DST gaps and requiring an explicit policy for overlaps. Incomplete date/time text drafts and pending tags expose validity so a screen can block stale-value submission. Date/time/color pickers are application-owned: a Monday-first calendar with month/year navigation, 24-hour hour/minute columns, and HSV color plane/sliders/palette use the existing Popover/Button primitives. Text entry remains available with localized dates and unchanged canonical/UTC model contracts. Native date/time/color inputs and popups are not used. File state stays outside finite-JSON useForm snapshots; file selection/type/count/size feedback performs no upload. FileInput provides a themed dropzone, keyboard/touch chooser and selected metadata rows; multiple batches append, single selection replaces, invalid selections stay visible, and readonly/disabled/busy fieldsets prevent edits. Backend integrity and upload authorization remain external. Detailed contracts and reset/recovery guidance live in the frontend form reference.

### Composable selection

Select supports single values and shell-independent searchable/multiple selection with FormField messages. All modes use the shared themed option panel rather than browser-owned option popups; readonly controls do not open. SelectionProvider receives query, opaque cursor, bounded limit and AbortSignal; it returns a page of stable string-ID options and nextCursor. The component renders one page (default 40, maximum 100), cancels obsolete requests and rejects stale responses, exposes loading/error/retry/empty states and preserves selection across queries/pages. Consumers supply selectedOptions for initially selected off-page labels and own transport, authorization and durable option metadata. Multiple values use string[], single values use string with empty-string clear; no new dependency or global installation is required.

### Advanced form composition

MaskedInput reuses TextInput while separating raw/null models, formatted display and mask completeness from feature validation. Positional tokens (# digits, A letters, * alphanumeric, backslash escapes) support caret-aware editing, paste/deletion and IME; formatMask and parseMaskedInput distinguish raw data from formatted text.

useDependentFields validates a stable acyclic graph, synchronously clears invalidated descendants, aborts requests and ignores stale results. Local options and bounded remote loaders share loading/error/ready/retry/cancel state. Large searchable option domains continue to use SelectionProvider.

useDirtyGuard is opt-in and accepts application-owned confirmation UI. It preserves values on refused exits and installs beforeunload only for dirty enabled forms. Its beforeLeave adapter integrates with usePageResolver’s optional guard for known named routes and resolver-owned Back/Forward history; external history owners supply their own adapter. Unguarded navigate remains synchronous; guarded navigate returns Promise<boolean>. No fields are validated through browser prompts and no drafts enter preference storage.

Wizard is a controlled shell-independent view with steps, current-step semantics, optional progress, scoped content and busy/error focus. useWizard validates forward transitions and all steps before completion, permits backtracking without losing values and keeps failures retryable. Values/errors/submission remain caller-owned useForm concerns; no backend wizard engine or server drafts exist. See the frontend form reference for detailed APIs and lifetime boundaries.

### Modal dialogs and shared layer ownership

Dialog, AlertDialog and ConfirmDialog are public controlled primitives. Each requires a visible title and boolean modelValue; Dialog provides content/footer slots, description/error, sm/md/lg/xl/full sizes, initial focus, localized dismissal and busy/closable/Escape/outside policies. ConfirmDialog starts on Cancel, emits confirm without automatically closing and leaves async busy/error/retry state to its caller. AlertDialog uses alertdialog semantics and closes on explicit acknowledgment.

The internal modal/floating layer stack gives only its newest layer Escape/outside ownership. Dialogs trap Tab/Shift+Tab, keep focus inside, restore connected invoking controls, make background body roots inert (including newly inserted roots) and preserve previous inert/scroll styles on cleanup. Nested dialog layers return to the parent. Tooltip/Popover/Select panels teleport into their owning dialog’s portal boundary, retaining viewport positioning while escaping the dialog body’s scroll clipping. Busy blocks user dismissal and disables form controls; confirmation handlers still own duplicate-write prevention. Dialog closeOnOutside defaults true (set false for close-button-only pointer dismissal) and requires a complete backdrop pointer gesture; closeOnEscape defaults true. Scroll locks have shared ownership with the existing dedicated mobile drawer, without changing that drawer into a generic Dialog.

The dialog body scrolls within a bounded viewport while header/footer remain available. Theme overlay opacity reuses the approved light 28% / dark 52% palette; fade transitions respect reduced motion. No dialog service, global installation, business workflow or server write is required. The isolated advanced-form scenario supplies ConfirmDialog as useDirtyGuard’s confirmation UI; the dialog scenario keeps fixtures and test controls in separate Cards.

## Table processing

The public `Table` component supports explicit bounded client and paginated server modes through typed columns, stable row keys, controlled query/selection, cell/action/filter slots and cancellable providers. `useTable` ignores obsolete replies. Server counts distinguish exact/stale/unknown; uncertain totals use previous/next navigation. `createApiTableProvider` normalizes allowlisted query fields and uses the shared HTTP transport; application decoders validate unknown `data` + `meta` responses. No backend table engine or domain endpoint is provided. See the [table contracts and integration guidance](../../../../.agents/skills/frontend/references/tables.md). Column personalization and named browser-local views are available through optional TablePreferences/useTableViews; cross-page explicit/all-matching selection, TableActions/OperationPanel, bounded real CSV/XLSX/PDF adapters, separate printing and application-owned server/bulk providers are available. See the table reference for limits, idempotency, cancellation and license notices.

Semantic feedback components supply a reactive nearest-parent tone context. Nested feedback inherits omitted tones; Buttons inside colored feedback default to the matching outline variant. Explicit tones/variants override inheritance, and a neutral surface resets it. This also covers slotted actions and AsyncContent retry buttons. Standalone component defaults remain unchanged; ordinary form validation and shell navigation keep their independent contracts.

Table personalization persists presentation/query snapshots in the existing scoped PreferenceStore (schema v2, v1 migration). TablePreferences provides column visibility, drag/keyboard ordering, width controls and named-view create/select/update/rename/delete/default/reset. Hidden columns remain part of query processing; only explicit descriptor removal invalidates filters/sorts. Current and saved snapshots reconcile unavailable/new columns before application. Applying a view resets pagination to page 1. See the table reference for public integration, bounds, default/re-entry behavior and storage fallback; this is local UX state, never a permissions model.

Table toolbar composition groups Search/page size with optional view preferences and sm icon export/print buttons. Export scope and PDF orientation live in the shared settings Popover; selection/bulk controls appear contextually. The toolbar slot exposes a per-instance feedbackTarget for TableActions, keeping operation feedback below the header without changing alignment. Print tables use separate zero-spacing cell borders and uniform 1pt CSS separators.

## Verified independent consumer and accessibility review

The public consumer fixture at tests/frontend/fixtures/StandaloneConsumer.vue composes forms, selections, tabs, confirmation, tables and exports without importing a layout or initializing router/preferences. Its bare entry supplies document locale/theme and shared CSS. Playwright builds it independently from the scenario harness into .runtime/browser-harness/standalone, preventing shared shell chunks. Test sources, runner and output are excluded from production graph/Docker context. The in-memory production/public-consumer boundary check remains. Generic component composables are supported; shell/router/preference initialization is not implicit.

# Page Patterns

Public layout props and scoped slots are exported as `MinimalLayoutProps`,
`MinimalLayoutSlots`, `ApplicationLayoutProps` and `ApplicationLayoutSlots` from
`@/runa/ui`. Use them with `defineProps` / `defineSlots` in application wrappers;
forward received props and relevant slots. `LayoutSlotContext.t` accepts public
`MessageKey` values. These are presentation contracts, not routing/session APIs.

Bundled MinimalLayout and ApplicationLayout pages share `/favicon.ico`, declared once in the application and isolated test HTML entry heads. Native/static errors declare the same icon in their PHP document wrapper. Keep document metadata at the HTML entry level rather than duplicating head mutations in layout components. External consumers supply their own document head and icon asset; importing a layout does not mutate their favicon.

## Minimal layout

Canonical source: `src/runa/resources/js/layouts/MinimalLayout.vue`, public import `MinimalLayout` from `@/runa/ui`. Use MinimalLayout for compact public pages rather than a separate auth wrapper point 2.

Owns the accepted backdrop, default identity header, cycling language/theme controls, skip link/focusable main and configurable footer. Optional named slots: header, header-actions, content, illustration, actions, footer. Content falls back to the default slot; slots expose translator `t`. The supplied Runa illustration is built in and visible by default; content occupies the left panel, the illustration the right at `lg`, with a compact 240px illustration above content on narrow screens. Set `illustration: false` explicitly for one column, or override the illustration slot. Actions follow content. Footer stays in normal flow at the viewport bottom on short pages and after long content. `footer.visible: false` hides even a custom footer slot.

MinimalLayout alone enables occasional constellation-point glows: a random visible
point brightens gently for three seconds, followed by 7–15 seconds of quiet.
It avoids repeating the preceding point and leaves geometry/links stationary.
Set `constellationGlow: false` to opt out in a wrapper. Reduced motion, printing,
hidden tabs and static-preference/native-error rendering remain static. Timers and
animations cancel on opt-out/unmount. ApplicationLayout retains its static shared
backdrop; this behavior must not be enabled there as a side effect of shared CSS.

Its default brand illustration moves one randomly selected point at a time along
its stationary ellipse for one six-second lap with gentle pulsation, then waits a
random 8–14 seconds. The point returns to its original position; the next selection
excludes the preceding point. The other five points stay still. Set `orbitMotion: false`
to opt out; a custom illustration slot owns its own motion. Reduced motion, print,
hidden tabs and static-preference/native-error output disable this effect too.
ApplicationLayout does not opt into either decorative effect.

The layout owns locale/theme composables once. Consumers use scoped `t`, preserving guest compatibility keys, cover timing, inert content and focus restoration; signed-in display values use the shared account preference adapter. Identity overrides are scoped with `IdentityProvider`; no global installation is required. Custom headers replace the default brand and preferences together.

## Preserved root welcome view

The source-owned welcome catalogue uses localized module manifests, including
Admin 0.1.0 with the existing adjustments icon. Catalogue visibility does not
indicate installation, access grants or rendered Admin availability; see
[Admin delivery status](../../modules/admin/README.md#delivery-status-and-acceptance-ownership).

`src/app/resources/js/App.vue` delegates to public DefaultApplication; its platform-owned AuthWelcome composes MinimalLayout with the existing heading/description and session-aware entry links. The layout itself supplies `AppBrandIllustration.vue` by default. Illustration, geometry, backdrop and preference interactions are preserved. The footer now includes independent application/platform versions. Exact `/auth/...` paths now render the lazy AuthEntry component using this same layout; see the [Auth entry contract](../../modules/auth/entry-ui.md). The original root was accepted on 2026-10-18; the point-2 layout/footer and subsequent mobile corrections were accepted on 2026-10-18.

## Application layout

Optional ApplicationLayoutProps accept explicit `undefined` as well as omission;
Vue wrappers may forward it and the existing component defaults still apply.
AdminLayout forwards the typed public slots, including the sidebar-bottom
`collapsed` value, without widening their props to a string-indexed map.

Canonical source: `src/runa/resources/js/layouts/ApplicationLayout.vue`, public `ApplicationLayout` from `@/runa/ui`. Point 3 was visually accepted by the owner on 2026-10-18. Account-dropdown/self-service use the shared ApplicationSessionShell; Admin shell
delivery and acceptance follow the [Admin frontend guide](../../modules/admin/frontend.md); Auth entry/restricted views use MinimalLayout.

Supply optional userAvatar with userName/userInitials for the profile disclosure; custom wrappers forward these props. Supply typed navigation/topNavigation descriptors, URL-derived current context, title/description, independent sidebar/topbar visibility and contained/fluid width. The sidebar stays fixed at desktop widths, scrolls independently and persists collapse/groups by application identity. Below 64rem it becomes a focus-trapped, Escape/backdrop-dismissable drawer with focus return; it never relocates to another side. The top header stays visible with sticky positioning; toolbar scrolls with the page. The brand row and header are both 64px high, the Runa mark sits in the same 42px square as MinimalLayout, and sidebar collapse/expand is a 36px sm control before top navigation, inset 24px from the header left edge. Sidebar groups are full-width disclosure buttons with a centered chevron inside; their parent label does not navigate. Hidden sidebar headings, labels, nested subtrees and supplementary sections reserve no space; the collapsed icon list starts 8px below the brand row. Collapsed enabled/disabled icons share exact centering; the sidebar footer retains just the platform version. Dotted left/right edge indicators fade in when more top-navigation content is available in that direction and disappear at its ends or when it fits. The header is 64px high; its horizontal navigation scrollbar sits at the bottom edge. Wheel input over overflowing top navigation is consumed for horizontal movement (also at the ends), leaving Ctrl+wheel zoom native. Top-navigation dropdowns are floating cards teleported outside the nav scroller; they overlay content without stretching the 64px header. Sidebar groups expand inline. Escape/outside click/document scroll/resize dismiss cards; nav scrolling repositions the open card, and Tab boundaries preserve navigation focus order. The circular initials disclosure remains a reusable slot seam; AuthApplication supplies the actual profile link and sign-out action together through that seam.

Workspace slots follow heading/actions → breadcrumbs → statistics → toolbar → content/default + optional supplementary region. Other slots: sidebar-bottom, user-menu and footer. All expose t; sidebar-bottom exposes collapsed. Footer/identity and extracted preference controls are shared with MinimalLayout. Both layouts use the finalized Tooltip via AppPreferenceControls; ApplicationLayout uses sm language/theme controls and retains its unique circular initials trigger and delegates its existing user-menu content to Popover. `topbar: false` hides the area navigation/avatar while retaining utility controls and access to a visible sidebar. Print hides navigation/footer/unnecessary controls by default and may be explicitly overridden.

Use `usePageResolver` with exact paths and matching named server entries; never store active selection as a preference. Applications must register their own named entry paths; exact-path behavior is exercised by isolated test descriptors. Unknown application paths remain real 404s. No routing dependency is installed.

## Isolated browser scenarios

There are no application preview/catalog routes. Test customer pages through tests/backend/app and the isolated testing Nginx endpoint; see docs/runa/handbook/testing/strategy.md. Platform fixture harnesses are owner-only.

Use public components directly; keep executable composition examples in the existing test fixtures and durable contracts in this skill. Do not add a catalog, demo navigation or environment-enabled preview route to the application merely to demonstrate a component.

## Email layout

Native Laravel Markdown/default notification layout (including direct Auth mail): `config/mail.php`, `src/runa/resources/views/mail/{html,text}` and overridable `runa-mail` namespace. See `docs/runa/handbook/architecture/frontend.md` (Native email layout) for body/header/actions/footer/text/CID contracts. Use shared `src/runa/resources/css/light-palette.css` tokens, table layout, light theme and system monospace fallbacks; do not import Vue into mail. Branding is configured in `config/branding.php` with separate application/platform versions. Permanent test-only templates and array-transport browser adapters live under tests/Fixtures, with no application preview route. Browser screenshots do not prove Outlook/Gmail/Apple Mail compatibility.

## HTTP errors

Use **MinimalLayout.vue directly**, with error-specific content in its normal slot; it is also the welcome-dashboard template and the Auth entry template. Do not create an error-only shell/card/background/header/footer. `src/runa/build/errors/render.mjs` builds native HTML from this exact component, its shared backdrop/illustration/footer and existing controls. `staticPreferences` renders native GET navigation without browser/storage hooks; ordinary interactive consumers are unchanged. `src/runa/resources/views/errors/page.php` only wraps/selects/escapes generated private shells for native Laravel errors and the static 503 finalizer. No Node/Vue/JS runtime is needed by the resulting pages. `docs/runa/handbook/architecture/frontend.md` owns generation/copy/locale/theme contracts; the generated files and standalone assets are ignored build output. Error-specific content does not duplicate shell markup or CSS.

Content navigation uses public Breadcrumbs in ApplicationLayout’s breadcrumbs slot, intercepting its navigate event through the page resolver when appropriate. Statistics/Statistic provide global/filtered metrics with optional route-scoped account/guest preferences; providers own calculations. Card/Accordion/Tabs/Dropdown/Pagination are content primitives, independent of dedicated shell navigation.

Forms compose Form/FormSection/FormField and normalized inputs. Neither accepted layout contains provisional business forms. Native error preference GET forms and root/shell preference buttons retain their own contracts. Temporal, logical, file, color, tags, searchable/multiple selection, masks and dependent-field controls are optional content imports; test scenarios demonstrate keyboard, DST, limits and error recovery without shipping a business workflow.

Wizard and form content belong in one Card, with fixture simulation controls isolated from product flows. Caller-owned useForm state preserves drafts; dirty confirmation is opt-in for real editable screens. Dialog content composes Select and nested confirmation using shared layer ownership. ApplicationLayout’s drawer remains its dedicated menu composition; low-level scroll locking coexists with dialogs.

## Module pages and named contributions

Applications may explicitly compose ModulePage in either existing layout and pass moduleNavigation(view, locale) into ApplicationLayout. The module adapter does not install navigation, replace App.vue or require the bundled shell. Direct refresh uses exact named module entries and URL-derived route context. Resolve availability/build identity before constructing a page or starting its data requests. Place ModuleSlot at a declared host location when cross-module contributions are needed; preserve ordinary Vue slots for local composition. Render unknown/loading/unavailable states deliberately, and refresh the projection at navigation/recovery. See the public module-framework frontend contract and component catalogue for the actual props and persistence behavior.

The optional development `app.reference.panel` demonstrates a shell-independent module page with shared Card/Button, explicit HTTP loading/error/retry state and Polish/English props. Source lives under `src/app/modules/reference/frontend/components`; central usage is in `docs/app/modules/reference/README.md` when selected. No page entrance motion; shared controls retain their feedback. Production never loads this component.

AuthWelcome uses MinimalLayout for session-aware public entry. AuthApplication uses
the existing ApplicationLayout for `/app` and `/account`, with no parallel shell or
Admin page. Follow [account UI composition](../../modules/auth/account-ui.md), including
restricted reauthentication, activity and safe draft ownership.

Auth rendering acceptance covers desktop/tablet/phone, PL/EN, both themes, keyboard
focus/errors and reduced motion. Keep it separate from real session/CSRF/permission
acceptance: intercepted browser responses cannot prove server enforcement. Prepare
both rendered scenarios and real backend boundaries for final verification. Private
local accounts/setup and the complete navigation procedure live in the
[seeding guide](../../data/seeding.md#explicit-module-development-contributors).

## Presentation ownership for all module views

Apply [frontend adoption and replacement](customization.md) to every current/future
Runa view. Usable defaults stay in src/runa; customer pages, layout wrappers, CSS
and complete presentation replacements stay in src/app. Use public composition or
headless contracts, retaining server security and platform session orchestration.
AuthPresentationProvider supplies Auth subtree overrides; the static errorLayout.ts
entry selects the same layout for native errors and emergency 503.

The public MinimalLayout `header-actions` slot adds actions beside the existing
language/theme controls while retaining the default identity/header. Welcome places
sign-in, policy-dependent registration and authenticated entry here; these controls
do not belong in the welcome content panel. The header action group wraps on narrow
screens. Replacing the whole header replaces this default group as well.

Welcome header authentication/application entry uses quiet text links beside
preferences, without button surfaces/borders. Preserve muted default text,
underline/color on hover, visible keyboard focus and 44px touch targets.

## Shared authenticated shell

`ApplicationSessionShell` from `@/runa/auth` composes the supplied layout with one
caller-owned ApplicationSessionController, profile/account menu, expiry warning,
reauthentication and safe content admission. AuthApplication creates a controller
only when none is supplied; AdminApplication uses that same public shell directly.
Extra source-owned links occupy the account-menu seam; User panel and sign-out
remain supplied. Admin entry is advertised only there. See the complete
[composition and props](../../modules/admin/frontend.md#shared-session-shell-and-account-menu-seam).

ApplicationLayout accepts optional `locale` for a fixed presentation scope. Its
shared controls inherit that locale without persisting an account-language change;
the language cycle is hidden while the theme control remains usable. Standalone
layouts bootstrap account preferences; inside the session shell they reuse its
session admission rather than issuing another session read.

For action proof before application admission, ApplicationSessionShell and
AuthApplication expose an optional admission slot outside ApplicationLayout;
native paused-session reauthentication retains priority. Admin uses this slot for
AuthOperationProofPage (MinimalLayout), loading and refusals. The slot is a
presentation boundary, not authorization. Keep one shared session controller and
an identity-bound Auth proof controller; see the [Auth proof contract](../../modules/auth/entry-ui.md#operation-proof-composition).

## Initial presentation readiness

Both layouts always render their frame immediately. Optional readiness: ViewReadiness
controls the content/default region only (including MinimalLayout actions); ApplicationLayout also
accepts userAvatarImage: ImageReadiness for a prepared shared avatar. Defaults
remain immediately usable for independent/static composition. Supplied Auth
screens and the shared session shell declare initial dependencies and preserve
mounted content during refresh. Follow [view readiness](loading.md) for exact
public signatures, optional fallbacks and presentation replacement requirements.

## Page-level navigation composition

The sidebar selects an application area/module. Subviews within the selected area
use the shared horizontal top bar, including account tabs and Admin application
settings; do not duplicate them as navigation buttons inside content. Record
selection, save/retry/review and other use-case actions remain content controls.

UI owns public `PageNavigation` and `usePageNavigationHost` consumption/composition
from `@/runa/ui`, implemented in shared components/composables, delivered in the
frontend runtime. PageNavigation accepts `entries: readonly NavigationEntry[]`,
`current?: RouteContext`, `label?: string`, `disabled?: boolean`,
`loading?: boolean`; it emits
`navigate(href: string, event: MouseEvent)`. In an ApplicationLayout subtree it
registers with that layout's host and renders no duplicate content menu. Without
a host, it supplies a labeled standalone navigation fallback with controlled
buttons, keeping independent presentation usable. Latest mounted publisher owns
the page context; disposal removes its registration without clearing a newer one.

Publish PageNavigation before dependent content is ready and set loading while its
initial data settles. Loading renders an animated skeleton in the actual header
(or standalone fallback), withholding actionable links without a duplicate content
placeholder.

`usePageNavigationHost()` returns readonly computed `entries`, `current`, `loading`,
and `navigate(href,event): boolean` for a custom App layout to compose the existing
top navigation. Call once in layout setup; retain the same mounted layout instance
when page entries change. It handles only available registered links and delegates
selection/guards to the publishing controller. Explicit layout topNavigation
entries remain supported; page entries join that top bar without replacing sidebar
context. Custom layouts render a local skeleton while host loading is true.
`ApplicationLayout.navigationLoading?: boolean` supplies the same placeholder for
initial sidebar/top navigation discovery before a page publisher mounts. Neither
helper starts sessions, authorizes access or performs writes.

`usePageResolver` also returns readonly current `href: ComputedRef<string>` alongside its
existing methods/current descriptor so query-driven subviews react to deliberate
navigation and browser history. Account uses `?tab=...` (default details); Admin
dashboard uses `?section=application` (default Modules); selected module editors
use `?module=...&section=health|settings|integrations`. Named server entry paths
remain unchanged. Native modified clicks retain their link behavior; guarded
navigation preserves the existing dirty/write protections and never replays writes.

### Breadcrumb hierarchy

UI owns public ApplicationBreadcrumbSlotContext from @/runa/ui, implemented in
src/runa/resources/js/runa/ui/layouts.ts and delivered in the frontend runtime.
ApplicationLayout's breadcrumbs slot receives t plus optional navigation,
topNavigation, current and navigate(href: string, event: MouseEvent): void.
The default layout supplies its current sidebar entries, merged page/top entries,
page-published RouteContext (falling back to its own current) and existing navigation
handler. Custom layouts can supply the same scope without another router/session.

The shared session shell derives breadcrumb labels from that same presentation
context. Render enabled Home, the area, owning module where applicable, then the
current page/subview. Prior levels are safe links; only the leaf has aria-current.
Admin dashboard defaults to Home / Administrator panel / Modules; application
settings uses that page's label. Owner pages use Home / Administrator panel /
owner / page; account uses Home / Application / User panel / subview. Query-driven
page navigation updates the leaf together with the top bar, including browser
history. Dialog-local module tabs are not additional page breadcrumb levels.
Breadcrumb navigate events reuse the layout's existing navigation handler and
page dirty guards; modified clicks retain native link behavior.

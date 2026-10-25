# Page Patterns

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

The layout owns locale/theme composables once. Consumers use scoped `t`, preserving guest compatibility keys, cover timing, inert content and focus restoration; signed-in display values use the shared account preference adapter. Identity overrides are scoped with `IdentityProvider`; no global installation is required. Custom headers replace the default brand and preferences together.

## Preserved root welcome view

`src/app/resources/js/App.vue` delegates to public DefaultApplication; its platform-owned AuthWelcome composes MinimalLayout with the existing heading/description and session-aware entry links. The layout itself supplies `AppBrandIllustration.vue` by default. Illustration, geometry, backdrop and preference interactions are preserved. The footer now includes independent application/platform versions. Exact `/auth/...` paths now render the lazy AuthEntry component using this same layout; see the [Auth entry contract](../../modules/auth/entry-ui.md). The original root was accepted on 2026-10-18; the point-2 layout/footer and subsequent mobile corrections were accepted on 2026-10-18.

## Application layout

Canonical source: `src/runa/resources/js/layouts/ApplicationLayout.vue`, public `ApplicationLayout` from `@/runa/ui`. Point 3 was visually accepted by the owner on 2026-10-18. Account-dropdown/self-service integration and Admin UI remain separate; Auth entry/restricted views use MinimalLayout.

Supply optional userAvatar with userName/userInitials for the profile disclosure; custom wrappers forward these props. Supply typed navigation/topNavigation descriptors, URL-derived current context, title/description, independent sidebar/topbar visibility and contained/fluid width. The sidebar stays fixed at desktop widths, scrolls independently and persists collapse/groups by application identity. Below 64rem it becomes a focus-trapped, Escape/backdrop-dismissable drawer with focus return; it never relocates to another side. The top header stays visible with sticky positioning; toolbar scrolls with the page. The brand row and header are both 64px high, the Runa mark sits in the same 42px square as MinimalLayout, and sidebar collapse/expand is a 36px sm control before top navigation, inset 24px from the header left edge. Sidebar groups are full-width disclosure buttons with a centered chevron inside; their parent label does not navigate. Hidden sidebar headings, labels, nested subtrees and supplementary sections reserve no space; the collapsed icon list starts 8px below the brand row. Collapsed enabled/disabled icons share exact centering; the sidebar footer retains just the platform version. Dotted left/right edge indicators fade in when more top-navigation content is available in that direction and disappear at its ends or when it fits. The header is 64px high; its horizontal navigation scrollbar sits at the bottom edge. Wheel input over overflowing top navigation is consumed for horizontal movement (also at the ends), leaving Ctrl+wheel zoom native. Top-navigation dropdowns are floating cards teleported outside the nav scroller; they overlay content without stretching the 64px header. Sidebar groups expand inline. Escape/outside click/document scroll/resize dismiss cards; nav scrolling repositions the open card, and Tab boundaries preserve navigation focus order. The circular initials disclosure remains a reusable slot seam; AuthApplication supplies the actual profile link and sign-out action together through that seam.

Workspace slots follow heading/actions → breadcrumbs → statistics → toolbar → content/default + optional supplementary region. Other slots: sidebar-bottom, user-menu and footer. All expose t; sidebar-bottom exposes collapsed. Footer/identity and extracted preference controls are shared with MinimalLayout. Both layouts use the finalized Tooltip via AppPreferenceControls; ApplicationLayout uses sm language/theme controls and retains its unique circular initials trigger and delegates its existing user-menu content to Popover. `topbar: false` hides the area navigation/avatar while retaining utility controls and access to a visible sidebar. Print hides navigation/footer/unnecessary controls by default and may be explicitly overridden.

Use `usePageResolver` with exact paths and matching named server entries; never store active selection as a preference. Applications must register their own named entry paths; exact-path behavior is exercised by isolated test descriptors. Unknown application paths remain real 404s. No routing dependency is installed.

## Isolated browser scenarios

There are no application preview/catalog routes. Test customer pages through src/app/tests and the isolated testing Nginx endpoint; see docs/runa/handbook/testing/strategy.md. Platform fixture harnesses are owner-only.

Use public components directly; keep executable composition examples in the existing test fixtures and durable contracts in this skill. Do not add a catalog, demo navigation or environment-enabled preview route to the application merely to demonstrate a component.

## Email layout

Native Laravel Markdown/default notification layout (including direct Auth mail): `config/mail.php`, `src/runa/resources/views/mail/{html,text}` and overridable `runa-mail` namespace. See `docs/runa/handbook/architecture/frontend.md` (Native email layout) for body/header/actions/footer/text/CID contracts. Use shared `src/runa/resources/css/light-palette.css` tokens, table layout, light theme and system monospace fallbacks; do not import Vue into mail. Branding is configured in `config/branding.php` with separate application/platform versions. Permanent test-only templates and array-transport browser adapters live under tests/Fixtures, with no application preview route. Browser screenshots do not prove Outlook/Gmail/Apple Mail compatibility.

## HTTP errors

Use **MinimalLayout.vue directly**, with error-specific content in its normal slot; it is also the welcome-dashboard template and the Auth entry template. Do not create an error-only shell/card/background/header/footer. `src/runa/resources/errors/render.mjs` builds native HTML from this exact component, its shared backdrop/illustration/footer and existing controls. `staticPreferences` renders native GET navigation without browser/storage hooks; ordinary interactive consumers are unchanged. `src/runa/resources/views/errors/page.php` only wraps/selects/escapes generated private shells for native Laravel errors and the static 503 finalizer. No Node/Vue/JS runtime is needed by the resulting pages. `docs/runa/handbook/architecture/frontend.md` owns generation/copy/locale/theme contracts; the generated files and standalone assets are ignored build output. Error-specific content does not duplicate shell markup or CSS.

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

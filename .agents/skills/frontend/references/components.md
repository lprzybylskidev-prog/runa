# Component Catalogue

Search this catalogue and the source before introducing a new control. Keep entries limited to implemented components. Public imports use `@/runa/ui`: Button, Tooltip, Icon, Form, Select, LoadingOverlay and Logo map to their existing App-prefixed/RunaLogo implementations. No layout/router installation is required; load the application theme CSS in the consuming entry. IdentityProvider, MinimalLayout and Footer are also public; navigation and preferences now have resolver/storage implementations; export types remain contracts without implemented engines.

## AppSelect

- Source: `resources/js/components/AppSelect.vue`.
- Use for the current small, native selection controls. It preserves native keyboard behavior and wraps the select in a visible label.
- Props: `label`, string `modelValue`, readonly `options` with string `value`/`label`.
- Emits: `update:modelValue`; callers validate their narrower domain union.
- Minimum 44px control height, 4px corners and subtle border transitions; shared runa-field-control input/focus/autofill treatment, a CSS chevron and appearance:none for the closed select. Native option keyboard/selection behavior is retained.
- Available for genuine selection controls; root language/theme preferences now use explicit buttons.

## AppForm

- Source: `resources/js/components/AppForm.vue`.
- Prefer for controlled application forms: always `novalidate`, prevents native navigation, emits the submit event only when not busy and exposes `aria-busy`.
- Props: busy/valid/dirty, label/description and responsive columns; slots default/feedback/actions. Busy disables contained controls; valid=false emits invalid instead of submit. focusInvalid() supports async error recovery.
- Field validation remains the owning feature's responsibility; FormField/TextInput/NumberInput and useForm now supply reusable field/error/value contracts (see forms.md).
- Retained as the controlled form wrapper; the root preference buttons do not require a form.

## AppIcon

- Source: `resources/js/components/AppIcon.vue`.
- Typed subset of four Tabler outline icons with optional `size` (24 default) and `color` (`currentColor` default); decorative SVGs are hidden from assistive technology. Put an accessible name on the owning control when it is icon-only.
- Local source assets, licenses and revision/hash provenance: `docs/design/assets.md`.

## Root preference buttons

- Sources: `resources/js/components/AppPreferenceControls.vue`, `resources/js/composables/useDisplayPreferences.ts`, consumed by both web layouts.
- Exactly two buttons (44px md in MinimalLayout, 36px sm in ApplicationLayout): language cycles PL/EN, theme cycles system/light/dark with corresponding icons. Accessible names describe current/next values; short application tooltips identify the action. Both controls use AppButton. Existing composables preserve preferences. No select or submit step.

## AppTooltip

- Source: `resources/js/components/AppTooltip.vue`; public `Tooltip`. Existing `text`, optional bottom/right placement and scoped `descriptionId` remain compatible. The description ID is present only while shown.
- Pointer and keyboard-focus states are separate. A short 80ms bridge allows entering teleported content; pointer leave from both trigger/content dismisses unless keyboard focus remains. Click, Escape, outside pointer and document scroll dismiss; rapid enter/leave and preference transitions do not retain stale hover state. Escape stays suppressed until a new hover/focus session.
- Uses shared `useFloatingLayer` for measured viewport-clamped fixed positioning, resize/content observation, top-layer Escape/outside dismissal and teardown. Both placements teleport outside scroll clipping; no runtime DOM access during static error rendering.
- Tooltip is descriptive only; use Popover for interactive content. Opacity transition is 140ms with reduced-motion support. Keep an accessible name on icon-only triggers.

## AppAlert, AppBadge and AppStatus

- Sources: `AppAlert.vue`, `AppBadge.vue`, `AppStatus.vue` under `resources/js/components/`; public `Alert`, `Badge`, `Status`. Semantic `FeedbackTone`: neutral/success/info/warning/danger for alerts, toasts, badges and statuses. Neutral alerts/toasts use the ordinary raised surface, secondary text and control border, with no status tint. All use shared theme role tokens.
- Alert accepts optional title, content/actions slots, dismissible and localized closeLabel. Emits close; the owner controls removal. Danger uses role alert, other tones role status; announce=false disables live semantics for static catalogs or an existing live region. Symbols and readable content supplement color.
- Badge is static inline labeling/counts, size sm/md, no live role. Status pairs a decorative dot with required caller-provided readable slot content; announce opt-in enables role status.

## AppPopover

- Source: `resources/js/components/AppPopover.vue`; public `Popover`. Required label names its nonmodal dialog; optional align start/end. Trigger slot receives toggle/open/panelId; default slot receives close. Caller owns the trigger label, aria-expanded/controls and aria-haspopup=dialog.
- Focus moves to the first control (or panel); Escape closes the top layer and returns to its trigger. Outside pointer dismisses without stealing focus. Tab leaves the nonmodal panel through normal neighboring trigger order; Shift+Tab from the first control returns to the trigger. Shared viewport positioning flips/clamps, reacts to scroll/resize/content and escapes clipping. Nested popovers dismiss one level per Escape.
- ApplicationLayout's unique initials profile control now uses Popover for its existing user-menu slot; navigation remains specialized because its route/availability/scroll/Tab contracts differ. No generic action-list or split buttons are reintroduced.

## ToastViewport and useToasts

- Sources: `AppToastViewport.vue`, internal `AppToast.vue`, `composables/useToasts.ts`; public `ToastViewport`, `useToasts`, ToastMessage/FeedbackTone types.
- Local setup-scoped queue: `const { messages, show, dismiss } = useToasts()`. Pass messages to ToastViewport and bind dismiss. show accepts title, optional description/tone/duration and returns its ID. Default duration is 5000ms, danger defaults to persistent (0); invalid/nonfinite durations are persistent. This is transient feedback, not a notification center.
- Viewport is top-right and fits narrow screens; an initially empty polite live region announces additions once. Explicit localized close buttons are always present. Hover or focus pauses the remaining time; resume waits until both have left. Removing a focused toast through useToasts restores its invoking control when available. Timers/listeners clean up with components.
- Keep actionable/important errors inline as well as in transient feedback; the host feature owns that error state. Standalone ToastViewport consumers can provide readonly ToastMessage[] and handle dismiss themselves, including any focus destination. No shell/provider/global store installation.

## AppButton

- Source: `resources/js/components/AppButton.vue`; public `Button`. Native attributes/listeners inherit; safe default `type="button"`. No shell dependency.
- `variant`: neutral (compatible default), solid, outline, soft, ghost. `tone`: primary, secondary, success, danger, warning, info, accent. `size`: sm (36px), md (44px), lg (52px). Use md for usual touch actions.
- `busy` disables activation and exposes aria-busy plus a decorative spinner; slot text supplies context. `disabled` uses native semantics. `aria-pressed` supports toggle feedback. `iconOnly` (or compatible `compact`) makes a square; provide an accessible name. Tooltip is separate.
- Colors use finite CSS token mappings, without inline styles so build-time native error rendering retains its CSP contract. Reduced motion preserves the static busy indicator.
- Used by MinimalLayout preferences and ApplicationLayout preferences, sidebar/drawer controls and profile trigger. Shell disclosures/navigation and modal backdrop remain specialized internal compositions.

## AppButtonGroup

- Source: `resources/js/components/AppButtonGroup.vue`; public `ButtonGroup`. Required `label` names the group; slotted native buttons retain Tab navigation and caller-owned pressed state.
- Joined outer corners and overlapping borders create a single divider without gaps. Soft buttons now change to their semantic solid/foreground pair on hover.
- Action-list/split buttons and reusable avatars were removed at the owner's request; build these only when an actual application need appears. ApplicationLayout retains its unique initials profile control.

## AppFooter

- Source: `resources/js/components/AppFooter.vue`.
- `poweredByLabel` defaults to English `Powered by` for standalone use. MinimalLayout passes the current translation (`Oparte na` in Polish), reusing its existing locale state.
- Public `Footer` consumes `useIdentity()`: `ApplicationName vX.Y.Z | Powered by Runa vA.B.C`; both bundled versions default to actual root VERSION.
- `options: FooterOptions` independently controls visible/application/platform/copyright/repository; optional copyrightText/repositoryUrl/repositoryLabel customize content. URL validation rejects unsafe links. Copyright and GitHub remain default supplemental elements. Removed obsolete versionLabel prop.
- Layout footer slots can replace the footer; layout footer.visible hides the entire region. Canonical use: MinimalLayout.

## IdentityProvider

- Source: `resources/js/components/AppIdentityProvider.vue`; public `IdentityProvider`, `useIdentity`, `defaultIdentity`, `resolveIdentity` from `@/runa/ui`.
- Reactive partial ApplicationIdentity overrides scope to descendants, with safe bundled defaults and independent application/platform versions. No global setup or shell dependency. Nested providers are independent scopes with bundled defaults, not implicit merges with their parents.
- Logo accepts same-origin absolute asset paths or credential-free HTTPS URLs. Missing/invalid URLs use the supplied Runa mark and application name; blank accessible names fall back to the application name. Failed image loading restores the mark; changed sources retry.

## AppBrandIllustration

- Source: `resources/js/components/AppBrandIllustration.vue`. Decorative RunaLogo/grid/orbits; desktop retains the original composition, while narrow screens use a 240px panel and scaled artwork. The `RUNA` corner signature always identifies the platform and is never replaced by the business application name. MinimalLayout supplies it as the default illustration; consumers supply only content. The illustration slot can override it; `illustration: false` explicitly hides it.

## AppLoadingOverlay

- Source: `resources/js/components/AppLoadingOverlay.vue`.
- Teleported opaque full-screen loading cover, centered circular spinner and accessible status label. Props: `active`, `label`; emits `covered` after enter and `revealed` after leave.
- Shared 340ms smooth opacity transitions and 700ms spinner cycle, disabled for reduced motion. Cover colors are captured at activation so theme updates do not abruptly recolor the visible overlay. The fully covered class must not override leave-transition opacity. Caller owns pending operation, inert/busy state and focus restoration; MinimalLayout provides the canonical language/theme flow.

## AppBackdrop

- Source: `resources/js/components/AppBackdrop.vue`.
- Shared decorative CSS backdrop: large diffuse violet/rose and cyan/blue corner gradients with a quieter center, and a masked 88px technical grid at theme-specific opacity (light 9%, dark 4.5%). Page-background rings/arcs are removed. No raster texture, animation or external assets.
- Decorative, fixed, pointer-transparent and clipped within its own viewport. Place inside an isolated layout stacking context behind opaque reading surfaces. Canonical use: MinimalLayout. Follow the current background direction in `docs/design/visual-direction.md`.

## RunaLogo

- Source: `resources/js/components/RunaLogo.vue`.
- Shared decorative project mark based on the supplied transparent PNG alpha mask. Inherits `currentColor`; CSS mask crops transparent padding without modifying the original image. Default height 28px with the mark's natural aspect ratio; CSS may override height.
- Use in brand links and decorative artwork instead of the temporary Tabler mark. The owning link/control supplies its accessible name; the decorative mark is hidden from assistive technology. Source/provenance: `docs/design/assets.md`.

## AppConstellation

- Source: `resources/js/components/AppConstellation.vue`.
- Decorative deterministic SVG with thin lines and softly layered star points; `variant` selects one of three distinct branched patterns with 6–8 points. Desktop widths scale with the viewport (roughly 48–62vw with 500–1200px limits); mobile retains two cropped structures at 580/650px. Position them partly off-screen and behind the card, not as small isolated ornaments. Lines retain thin non-scaling strokes. No motion, randomness, image asset or dependency.
- Uses theme color tokens and is hidden from assistive technology. Canonical use: three peripheral details in AppBackdrop, reduced to two quieter details on narrow screens.

## ApplicationLayout and AppNavigation

- Sources: `resources/js/layouts/ApplicationLayout.vue`, internal recursive `resources/js/components/AppNavigation.vue`; public import ApplicationLayout. Slots/props and route/preference contracts are documented in `docs/architecture/frontend.md` and page-layouts.md.
- AppNavigation reuses existing AppIcon/AppTooltip; supports link availability, URL-derived active state, whole-row disclosure buttons for groups and collapsed labels. Group labels do not navigate; leaf entries remain links. A centered CSS chevron lives inside the disclosure control, matching top-navigation convention. Right-position tooltip is a narrow shell requirement, not completion of the later feedback/layer group. All sidebar entries have a supplied or fallback Tabler icon.
- Header and sidebar share a static 44px CSS grid over their opaque surface, using 30% of the theme backdrop-grid opacity and secondary-text color; forced-colors removes the decorative texture.
- The initials profile trigger remains a dedicated 44px circular button; its shell selector takes precedence over the shared button corner style. ApplicationLayout preference buttons use sm (36px) with 18px icons. The sidebar toggle uses an 18×14px geometric three-bar mark centered in a 36px sm square, 24px from the header left edge, avoiding font-baseline glyph shifts. Header toggle and initials avatar expose visible hover/focus/pressed states through the semantic primary palette in both themes.
- Existing AppPreferenceControls and useDisplayPreferences are shared shell composition, preserving root timing/focus. MinimalLayout can supply staticPreferences for build-time native error rendering; the same controls/buttons/icons render GET forms in navigation mode without DOM/storage access. Do not create another copy when later finalizing buttons/feedback; update both consumers. AppNavigation/profile/drawer composition is finalized with the owning later component points, not a competing exported dropdown/modal system.

- Collapsed sidebar renders an icon-only list with 8px vertical padding; hidden headings/labels/submenus and their wrappers reserve no space. Saved expansion restores on reopening. It centers enabled/disabled icons identically, omits the supplementary sidebar-bottom slot and displays only the platform version in its footer. Top navigation has dotted edge cues only where more content is scrollable; cues fade over 160ms and respect reduced motion. Scroll/resize/content changes refresh the edge state.

- Shell motion: desktop sidebar width/workspace offset and phone drawer slide use 180ms ease-out; inline groups expand with a 160ms grid/opacity transition; top and initials cards fade with 4px travel over 140ms. Closing panels become inert immediately; reduced-motion disables transitions. Repeated toggles can reverse ongoing transitions.

## AppTopNavigation

- Source: `resources/js/components/AppTopNavigation.vue`; internal application-shell navigation, not a generic dropdown component. Distinct from the sidebar's recursive AppNavigation.
- Horizontal group labels are disclosure buttons. Their dropdown card is teleported to body outside the scroll container, positioned under the trigger/header and clamped to the viewport. Header stays 64px when open; sidebar groups retain inline expansion. The panel reuses AppNavigation for its child links.
- Escape, outside click, route change, document scroll and resize dismiss it; nav scrolling updates its anchor position; focus returns on Escape. Tab boundaries connect the popup back to normal navigation order. General tooltip/popover floating behavior is implemented in point 9; route navigation retains its specialized anchoring and keyboard contract.

## Content containers and navigation

- Public `Card`, `Divider`, `Accordion`, `Tabs`, `Dropdown`, `Breadcrumbs`, `Pagination` map to `resources/js/components/App*.vue`; types `TabItem`/`BreadcrumbItem` come from `@/runa/ui`. No shell, router or global installation is required.
- Card offers header/actions/default/footer slots; absent header/footer reserve no space. Card and Accordion headers use the lighter raised surface and semibold ordinary-text titles above darker neutral bodies (muted surface in light theme, ordinary surface in dark theme). The card footer matches the lighter header surface, with smaller secondary text and compact spacing. Divider is a semantic horizontal rule, or a decorative labeled section break.
- Accordion requires boolean v-model and label; optional disabled. Native Enter/Space toggles through update:modelValue; its hidden content stays mounted. Compose independent accordions for multiple expansion, or control their values for exclusive expansion.
- Tabs requires string v-model, unique stable item IDs and label. Each item has id/label/optional disabled; named slots match IDs. Horizontal arrows wrap across enabled tabs; Home/End move to endpoints and automatically activate. Inactive panels stay mounted and hidden. If the controlled selection disappears/is disabled, the first enabled item is presented; the owner should reconcile its model. Empty/all-disabled lists have no active panel.
- Dropdown is a controlled boolean v-model disclosure for arbitrary interactive content, with required localized label and optional disabled trigger. It composes the existing Popover and Button, with named nonmodal dialog semantics, viewport placement, outside/Escape dismissal and trigger focus return. Its default slot receives close. It is not an action-list/split-button API or a replacement for dedicated shell menus. Popover additionally supports optional controlled v-model while preserving its existing local default.
- Breadcrumbs requires label and readonly items (label/optional href). The final item uses aria-current=page; earlier safe same-origin absolute/public HTTPS URLs are anchors. Unsafe URLs render text. Optional navigate(href, MouseEvent) lets a resolver preventDefault while preserving modified-click/native navigation behavior. ApplicationReview now uses this primitive in the shell breadcrumbs slot.
- Pagination requires numeric v-model/pages and localized label/previousLabel/nextLabel; optional disabled. It emits requested pages without fetching or owning data. It renders at most five numbered controls plus previous/next, with gaps; current page uses aria-current. Invalid/nonfinite bounds normalize to one page; out-of-range selection is clamped for display. Consumers own query/page resets and loading/disabled state.

## Loading and asynchronous content

- Public `Spinner`, `Skeleton`, `Progress`, `EmptyState`, `AsyncContent` map to `resources/js/components/App*.vue`. `AsyncContentState` is loading/error/empty/success. No fetch/provider lifecycle is hidden in these primitives.
- Spinner requires localized label; sm/md/lg are 14/24/44px. Standalone uses role=status; decorative removes announcement for an already named parent. Button busy state and LoadingOverlay now compose this shared spinner, preserving overlay timing/focus/color capture. Reduced motion removes rotation.
- Skeleton is decorative/aria-hidden, with text (1–10 lines, default 3), block or circle geometry. Callers provide the loading status; reduced motion removes pulsing. Skeletons do not implement avatars or imply actual data progress.
- Progress requires localized label; value null/absent/nonfinite is indeterminate. Finite value is clamped to 0..max (positive finite max, otherwise 100). ARIA uses actual value/max units; visible percentage is derived only for determinate progress. Optional valueText supplies a localized meaningful equivalent. FeedbackTone selects neutral/success/info/warning/danger; reduced motion stops travel/transition while leaving the status visible. The caller owns completion/failure/cancellation.
- EmptyState requires title, optional description/icon; icon and actions slots support composition. It is static by default. AsyncContent announces its default empty state through role=status.
- AsyncContent requires controlled state and offers loading/error/empty/success slots, with default slot as success fallback. Defaults compose Spinner, Alert and EmptyState; all text is configurable (English standalone defaults; application callers supply PL/EN). Optional retryLabel exposes a retry button and retry event; custom error slot receives retry(). Retry never invents success or fetches data. Only the active state is mounted, so hoist form/draft state if it must survive switching; network cancellation and stale-response handling belong to the caller. aria-busy reflects loading; custom slots own their appropriate accessible status semantics.

## Statistics and per-view modes

- Public `Statistic`, `Statistics`, `useStatisticsMode`; `StatisticsMode` is global/filtered. Statistic requires label/value (string or number); optional description/icon/trend and FeedbackTone. Semantic text/top indicator supplements the supplied labels and trend text; no numeric calculations or automatic good/bad direction inference. Slots label/value/icon/trend/visualization support custom summaries/charts; callers provide accessible chart equivalents and formatted values.
- Statistics requires controlled modelValue, label/globalLabel/filteredLabel, optional disabled; always presents two pressed-state buttons and passes mode to its default slot. Its responsive grid holds ordinary Statistic children; no shell/provider dependency. Print hides the switch while preserving the rendered values. Callers supply global/filtered datasets; changing a filter must not change global values.
- `useStatisticsMode(scope, defaultMode = 'global', store?)` uses versioned existing preference storage under control statistics.mode. Scope has stable applicationId/moduleKey/viewKey; each view may choose its own default. Values validate to global/filtered; corrupt/unsupported input falls back safely, denied storage uses existing session memory. Scope/default/store are fixed for a mount: remount a view on identity changes. ApplicationReview keys its statistics composition by route and demonstrates actual filtered sample values. No account sync, aggregation service or global store is introduced.

## FormSection, FormField, TextInput, NumberInput and useForm

- Sources: `resources/js/components/App{FormSection,FormField,TextInput,NumberInput}.vue`, `resources/js/composables/useForm.ts`, `resources/js/lib/decimal.ts`; public facade exports components, form types, useForm/laravelFieldErrors and parseDecimal/serializeDecimal/formatDecimal.
- FormSection supplies semantic fieldsets, descriptions, 1/2/3 responsive columns and controlled collapse preserving values. FormField exposes linked labels/descriptions/errors through scoped slot IDs and invalid state.
- TextInput supports text/textarea/password/email/url/tel, native autofill attributes and required/disabled/readonly/invalid states; nullable is opt-in. NumberInput supplies exact string/null values for integer/decimal/currency/percentage, localized decimal separators and optional scale without rounding; incomplete values remain in the model with inline errors. Units such as PLN/% appear beside the input through TextInput's noneditable suffix slot, remain linked to its accessible description and stay outside its value.
- useForm tracks cloned finite-JSON values, nested dirty baselines and controlled busy/errors; maps 422 dot keys, preserves 409/server-failure drafts and supports explicit server-snapshot reset. No hidden network requests, dirty-navigation guards, masks or business rules. Detailed API and numeric serialization contracts: forms.md.

## Date, logical, file and additional fields

Public facade exports DateInput, TimeInput, DateTimeInput, DateRangeInput, Checkbox, Switch, RadioGroup, FileInput, ColorInput, HiddenInput and TagsInput from their App-prefixed components. AppTemporalInput/AppBooleanInput are internal compositions. All fields reuse FormField and established theme/state styles; native checkbox/radio keyboard semantics remain intact. Checkbox supports boolean/null (indeterminate), Switch boolean, RadioGroup string/null with disabled options and isolated group names. Readonly blocks edits, disabled removes interaction. Custom label slots replace the default FormField label.

DateInput/TimeInput use canonical string/null values; DateRangeInput uses { start, end } date-only strings/null with chronological validation. DateTimeInput requires timeZone and stores an explicit-offset instant normalized to UTC on edit. Native date/time/color pickers remain browser-owned; locale controls labels/helpers and date-only display formatting, not every browser popup. See forms.md for draft and DST contracts. FileInput uses readonly File[] with accept/count/size UX validation and localized messages; it never uploads. ColorInput accepts null or #RRGGBB and retains invalid text drafts. HiddenInput is a native hidden serialization field, never a security boundary. TagsInput commits trimmed entries with Enter (outside IME), rejects duplicates/limits, clears drafts with Escape and supports removal; it exposes validity and focus().

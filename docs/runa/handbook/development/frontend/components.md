# Component Catalogue

Search this catalogue and the source before introducing a new control. First-use page-local elements are allowed; on second use, extract the common application element into `src/app/resources/js/components/`, migrate both consumers and document the local application contract in the same task. Consume platform exports through `@/runa/ui`; platform extension requires a Runa owner release. Keep entries limited to implemented components. Public imports use `@/runa/ui`: Button, Tooltip, Icon, Form, Select, LoadingOverlay and Logo map to their existing App-prefixed/RunaLogo implementations. No layout/router installation is required; load the application theme CSS in the consuming entry. IdentityProvider, MinimalLayout and Footer are also public; navigation and preferences now have resolver/storage implementations; bounded CSV/XLSX/PDF engines, printing and operation/provider contracts are available.

## Localized defaults

Shared feedback/loading controls, PageNavigation, Footer and TagsInput resolve
omitted copy through the application locale scope and preferences (Polish by
default, English secondary). Explicit copy props remain authoritative. FileInput,
NumberInput, ColorInput and temporal fields use their existing locale prop for
default errors and actions. Language changes update visible errors without
discarding invalid numeric/temporal drafts or pending tags. ModulePage localizes
its unavailable fallback using its locale prop. MinimalLayout publishes its locale
to descendants, including browser-independent static error rendering.

## Motion composition

All components have been reviewed for purposeful motion; see `motion.md` for the complete inventory, timing and accessibility rules. Internal AppCollapse (`src/runa/resources/js/components/AppCollapse.vue`) preserves disclosure DOM/drafts while clipping natural height and applying immediate inert/aria-hidden. It is not a public facade export. Internal useContentMotion (`src/runa/resources/js/composables/useContentMotion.ts`) reveals retained Wizard/CalendarPicker/AsyncContent updates without remounting; `src/runa/resources/js/lib/motion.ts` owns immediate exit deactivation and interrupted-entry restoration. Consumers inherit shared behavior instead of adding duplicate page entrances. Point-21 motion was accepted on 2026-10-19.

## AppSelect

Admin consumers can use public `AdminField` through `@/runa/admin` to compose these
shared controls from authorized field metadata, including optional bounded lookup
providers. Generated Admin forms compose shared drafts and explicit owning writes. See the exact
[Admin field contract](../../modules/admin/frontend.md#delivered-field-composition)
for replacement precedence, props/events and remaining form delivery.

- Source: `src/runa/resources/js/components/AppSelect.vue`; public `Select`, `SelectOption`, `SelectionProvider`, `SelectionPage`.
- Auth grouped bulk modals reuse Select for operation choices; native command reviews/proofs stay in the Auth/Admin controllers. See [Auth bulk presentation](../../modules/auth/administration.md#native-bulk-user-operations).
- All selection modes use the shared themed floating dialog/listbox, including simple single selection. Searchable adds a search field; multiple changes the value contract. No browser-owned option popup is used. String values are stable IDs; multiple=true uses string[]. Disabled options cannot be chosen. FormField supplies label/helper/warning/errors and required semantics; disabled/readonly block edits, clearable is explicit.
- Search is locale-aware for local options; provider queries debounce (250ms default), abort prior requests and ignore stale responses even if a provider ignores cancellation. Loading/error/retry/empty states remain distinct. Escape restores trigger focus, outside pointer dismisses, arrows/Home/End move over enabled options and Enter selects; IME composition is preserved. Multiple selection stays open until Done.
- Rendering is bounded to one page (40 default, at most 100). Local pages offer previous/next; remote providers receive query/cursor/limit/AbortSignal and return options/nextCursor. Remote next replaces the visible page; the provider owns cursor semantics, transport, ordering and authorization, without whole-dataset fetching. No automatic request on selection changes.
- selectedOptions supplies labels for initial/off-page values. Labels for selected remote options survive page/query changes; unknown IDs remain visible as IDs. Selection events expose option snapshots; IDs remain the model. Keep durable option labels in caller state when unmounting. locale defaults to pl; pass en for English presentation. All closed selection modes share the same CSS chevron anchored 16px from the control’s right edge, independent of selected-label length. Closed controls retain normalized field styling; readonly controls never open the option panel.
- providerSearch defaults true. Set false for a cursor provider whose contract has no search parameter: it keeps bounded next-page selection and keyboard navigation without inventing a filter or displaying a nonfunctional search field.

## AppForm

- Source: `src/runa/resources/js/components/AppForm.vue`.
- Prefer for controlled application forms: always `novalidate`, prevents native navigation, emits the submit event only when not busy and exposes `aria-busy`.
- Props: busy/valid/dirty, label/description and responsive columns; slots default/feedback/actions. Busy disables contained controls; valid=false emits invalid instead of submit. focusInvalid() supports async error recovery.
- Field validation remains the owning feature's responsibility; FormField/TextInput/NumberInput and useForm now supply reusable field/error/value contracts (see forms.md).
- Retained as the controlled form wrapper; the root preference buttons do not require a form.

## AppIcon

- Source: `src/runa/resources/js/components/AppIcon.vue`.
- Typed outline icon set (including clipboard-list, key and shield-lock for module identity) with optional `size` (24 default) and `color` (`currentColor` default); decorative SVGs are hidden from assistive technology. Put an accessible name on the owning control when it is icon-only.
- Local source assets, licenses and revision/hash provenance: `docs/runa/handbook/design/assets.md`.

## Root preference buttons

- Sources: `src/runa/resources/js/components/AppPreferenceControls.vue`, `src/runa/resources/js/composables/useDisplayPreferences.ts`, consumed by both web layouts.
- Exactly two buttons (44px md in MinimalLayout, 36px sm in ApplicationLayout): language cycles PL/EN, theme cycles system/light/dark with corresponding icons. Accessible names describe current/next values; short application tooltips identify the action. Both controls use AppButton. Existing composables preserve preferences. No select or submit step.

## AppTooltip

Tooltip accepts `placement?: 'bottom' | 'right'` (bottom default) and
`align?: 'start' | 'end'` (end default). Its root is the measured anchor; use a
content-sized wrapper such as w-fit when composing it in a stretching grid.
Viewport clamping and collision fallback remain active.

- Source: `src/runa/resources/js/components/AppTooltip.vue`; public `Tooltip`. Existing `text`, optional bottom/right placement and scoped `descriptionId` remain compatible. The description ID is present only while shown.
- Pointer and keyboard-focus states are separate. A short 80ms bridge allows entering teleported content; pointer leave from both trigger/content dismisses unless keyboard focus remains. Click, Escape, outside pointer and document scroll dismiss; rapid enter/leave and preference transitions do not retain stale hover state. Escape stays suppressed until a new hover/focus session; a repeated pointerenter while the anchor is still hovered, or a delayed first pointerenter during the same keyboard focus, does not reopen a dismissed tooltip. Leave reschedules a single hover-bridge timer and dismissal cancels it, preventing obsolete callbacks from disturbing later interactions.
- Uses shared `useFloatingLayer` for measured viewport-clamped fixed positioning, resize/content observation, top-layer Escape/outside dismissal and teardown. Both placements teleport outside scroll clipping; no runtime DOM access during static error rendering.
- Tooltip is descriptive only; use Popover for interactive content. Opacity transition is 140ms with reduced-motion support. Keep an accessible name on icon-only triggers.

## AppAlert, AppBadge and AppStatus

- Sources: `AppAlert.vue`, `AppBadge.vue`, `AppStatus.vue` under `src/runa/resources/js/components/`; public `Alert`, `Badge`, `Status`. Semantic `FeedbackTone`: neutral/success/info/warning/danger for alerts, toasts, badges and statuses. Neutral alerts/toasts use the ordinary raised surface, secondary text and control border, with no status tint. All use shared theme role tokens.
- Alert accepts optional title, alignment=start/center (start default), content/actions slots, dismissible and localized closeLabel. Emits close; the owner controls removal. Danger uses role alert, other tones role status; announce=false disables live semantics for static catalogs or an existing live region. Symbols and readable content supplement color.
- Badge is static inline labeling/counts, size sm/md, no live role. Status pairs a decorative dot with required caller-provided readable slot content; announce opt-in enables role status.

## AppPopover

- Source: `src/runa/resources/js/components/AppPopover.vue`; public `Popover`. Required label names its nonmodal dialog; optional align start/end. Trigger slot receives toggle/open/panelId; default slot receives close. Optional picker uses a viewport-bounded 360px panel with compact padding; initialFocus targets a selector inside the panel. Tabbing excludes roving tabindex=-1 items. Caller owns the trigger label, aria-expanded/controls and aria-haspopup=dialog.
- Focus moves to the first control (or panel); Escape closes the top layer and returns to its trigger. Outside pointer dismisses without stealing focus. Tab leaves the nonmodal panel through normal neighboring trigger order; Shift+Tab from the first control returns to the trigger. Shared viewport positioning flips/clamps, reacts to scroll/resize/content and escapes clipping. Nested popovers dismiss one level per Escape.
- ApplicationLayout's unique initials profile control now uses Popover for its existing user-menu slot; navigation remains specialized because its route/availability/scroll/Tab contracts differ. No generic action-list or split buttons are reintroduced.

## ToastViewport and useToasts

- Sources: `AppToastViewport.vue`, internal `AppToast.vue`, `composables/useToasts.ts`; public `ToastViewport`, `useToasts`, ToastMessage/FeedbackTone types.
- Toast content is centered horizontally and vertically between its icon and close control; recovery actions are centered with the text. Empty descriptions reserve no extra line.
- Local setup-scoped queue: `const { messages, show, dismiss } = useToasts()`. Pass messages to ToastViewport and bind dismiss. show accepts title, optional description/tone/duration and returns its ID. Default duration is 5000ms, danger defaults to persistent (0); invalid/nonfinite durations are persistent. This is transient feedback, not a notification center.
- Viewport accepts an actions slot scoped with message; modal consumers teleport inside the active modal focus/inert boundary and wait for its target before rendering. Viewport is top-right and fits narrow screens; an initially empty polite live region announces additions once. Explicit localized close buttons are always present. Hover or focus pauses the remaining time; resume waits until both have left. Removing a focused toast through useToasts restores its invoking control when available. Timers/listeners clean up with components.
- Form-wide outcomes use top-right notifications only; field-specific corrections remain associated with their controls. Persistent error notifications retain explicit recovery actions. Standalone ToastViewport consumers can provide readonly ToastMessage[] and handle dismiss themselves, including any focus destination. No shell/provider/global store installation.

## FormNotifications

- Public FormNotifications composes the existing local toast queue/viewport. Props:
  error/success strings, invalid boolean, locale (pl default) and attempt (0 default).
  Error wins over success; simultaneous partial success is included as description.
  Errors/invalid summaries are persistent, successes use the standard timeout.
- Changing locale updates the default invalid summary and viewport/close labels
  in place, preserving the current notification identity and keyboard focus.
  Explicit error/success strings remain owned and localized by the caller; changing
  language requires no form/controller remount or new submit attempt. Dismissed or
  expired outcomes stay absent across locale changes, including translated caller
  strings; increment attempt for a deliberate new submission.
- Bind state owned by the form; increment attempt on each explicit submit so a
  dismissed identical validation failure is reported again. One current result
  replaces its predecessor. The actions slot supplies explicit recovery buttons.
  The component never submits, reloads or retries by itself.
- Use it for form outcomes instead of duplicating a banner near the form. Keep
  linked field errors and normal static instructional/loading/empty states.
  Modal consumers retain keyboard access and one polite live announcement.

## AppButton

- Source: `src/runa/resources/js/components/AppButton.vue`; public `Button`. Native attributes/listeners inherit; safe default `type="button"`. No shell dependency.
- `variant`: neutral, solid, outline, soft, ghost. `tone`: neutral, primary, secondary, success, danger, warning, info, accent. Public `ButtonIntent` supplies action meaning; `ButtonTone` includes neutral. `size`: sm (36px), md (44px), lg (52px). Use md for usual touch actions.
- `busy?: boolean` disables activation and exposes aria-busy plus a decorative spinner; slot text supplies context. When omitted, a `type="submit"` Button inherits the nearest public Form's busy state; other buttons and standalone submit buttons default to false. Explicit true/false overrides this local default. The private form-activity injection is UI implementation, not another public entrypoint. `disabled` uses native semantics. `aria-pressed` supports toggle feedback. `iconOnly` (or compatible `compact`) makes a square; provide an accessible name. Tooltip is separate.
- Intent overrides a low-level tone. With no explicit variant, every colored intent uses outline; neutral uses neutral. Shipped views use no solid action fills, including main Save and confirmation actions. The low-level solid variant remains available for explicit custom presentation; it is not the default visual language. Without intent/tone, the button is neutral. Busy preserves the action color and blocks activation; unavailable controls use disabled tokens.
- Colors use finite CSS token mappings, without inline styles so build-time native error rendering retains its CSP contract. Reduced motion preserves the static busy indicator.
- Used by MinimalLayout preferences and ApplicationLayout preferences, sidebar/drawer controls and profile trigger. Shell disclosures/navigation and modal backdrop remain specialized internal compositions.

### Action button semantics

Owner-approved defaults apply across application and module views. Pass explicit
`intent` from source-owned operation metadata; never infer it from translated text,
HTTP verb, feedback ancestors or password confirmation requirements.

| Intent  | Meaning and examples                               | Token role       |
| ------- | -------------------------------------------------- | ---------------- |
| commit  | Save, apply, create, activate                      | success / green  |
| cancel  | Safe Cancel, Close, Back                           | neutral          |
| discard | Discard unsaved work, restore defaults             | warning / yellow |
| inspect | Load, refresh, preview, export                     | info / blue      |
| caution | Deactivate, set a lock, cancel running work        | warning / yellow |
| danger  | Delete stored data, revoke sessions/devices/access | danger / red     |
| mixed   | Tool combining operations with different effects   | primary / purple |
| neutral | Navigation, selection, disclosure, acknowledgment  | neutral          |

Use quiet outlines with semantic text and borders for action buttons, including the
main Save. Do not introduce solid fills for emphasis. Soft/ghost remain suitable
for selection/navigation and compact controls; their hover stays subtle. Keep the
same semantic role on hover, pressed and busy states in both themes. Labels and
focus indicators remain required; color alone never identifies an action.

Color describes the intended effect even when clicking first opens a modal or
requests password proof. Create/edit/save tools are green, deletion/revocation red
and deactivation/default-reset yellow. Blue is for actual reads, refresh and preview,
not a general modal-opening color. A tool combining different effects (activation /
deactivation, deletion / restoration, set / clear lock) uses mixed / purple until
an option is selected. Its final confirmation uses that concrete effect's color.
Several choices with the same effect retain that effect's color. Restrict mixed
to the currently offered choices and source-owned parameter alternatives.

Password proof does not turn a green save red. Safe cancellation remains neutral
even inside a danger alert/dialog; discarding an edited draft is yellow.

`ConfirmDialog.confirmIntent?: ButtonIntent` defaults to commit and its Cancel is
neutral. Controllers that ask about destructive changes or discarded drafts must
supply the corresponding intent in their confirmation projection. Public
`BulkProvider.actions` may declare `intent?: ButtonIntent`; omission means neutral.
Declare its effect or mixed intent explicitly; the Admin adapter can derive known
destructive/save/create effects from its native command metadata. A mutation tool
retains that meaning while opening its review. The separate Preview control is blue. These are presentation contracts and grant no backend authority.

## AppButtonGroup

- Source: `src/runa/resources/js/components/AppButtonGroup.vue`; public `ButtonGroup`. Required `label` names the group; slotted native buttons retain Tab navigation and caller-owned pressed state.
- Joined outer corners and overlapping borders create a single divider without gaps. Soft buttons retain a subtle semantic background on hover.
- Action-list/split buttons remain absent until an actual application need appears. The current Auth profile requirement uses public Avatar for shared image/initials presentation; ApplicationLayout retains its dedicated profile disclosure control.

## AppFooter

- Source: `src/runa/resources/js/components/AppFooter.vue`.
- `poweredByLabel` defaults to the current translation (`Oparte na` / `Powered by`). MinimalLayout also supplies its current translation.
- Public `Footer` consumes `useIdentity()`: `ApplicationName vX.Y.Z | Powered by Runa vA.B.C`; versions default to the independent application and platform manifests.
- `options: FooterOptions` independently controls visible/application/platform/copyright/repository; optional copyrightText/repositoryUrl/repositoryLabel customize content. URL validation rejects unsafe links. Copyright and GitHub remain default supplemental elements. Removed obsolete versionLabel prop.
- Layout footer slots can replace the footer; layout footer.visible hides the entire region. Canonical use: MinimalLayout.

## IdentityProvider

- Source: `src/runa/resources/js/components/AppIdentityProvider.vue`; public `IdentityProvider`, `useIdentity`, `defaultIdentity`, `resolveIdentity` from `@/runa/ui`.
- Reactive partial ApplicationIdentity overrides scope to descendants, with safe bundled defaults and independent application/platform versions. No global setup or shell dependency. Nested providers are independent scopes with bundled defaults, not implicit merges with their parents.
- Logo accepts same-origin absolute asset paths or credential-free HTTPS URLs. Missing/invalid URLs use the supplied Runa mark and application name; blank accessible names fall back to the application name. Failed image loading restores the mark; changed sources retry.

## AppBrandIllustration

- Source: `src/runa/resources/js/components/AppBrandIllustration.vue`. Decorative RunaLogo/grid/orbits; desktop retains the original composition, while narrow screens use a 240px panel and scaled artwork. The `RUNA` corner signature always identifies the platform and is never replaced by the business application name. MinimalLayout supplies it as the default illustration; consumers supply only content. The illustration slot can override it; `illustration: false` explicitly hides it. Points are real child elements on stationary ellipses. The illustration is static by default; interactive MinimalLayout opts into occasional full point laps and pulsation, with `orbitMotion: false` as its public opt-out. See the layout/motion guides for timing and cancellation.

## AppLoadingOverlay

- Source: `src/runa/resources/js/components/AppLoadingOverlay.vue`.
- Teleported opaque full-screen loading cover, centered circular spinner and accessible status label. Props: `active`, `label`; emits `covered` after enter and `revealed` after leave.
- Shared 340ms smooth opacity transitions and 700ms spinner cycle, disabled for reduced motion. Cover colors are captured at activation so theme updates do not abruptly recolor the visible overlay. The fully covered class must not override leave-transition opacity. Caller owns pending operation, inert/busy state and focus restoration; MinimalLayout provides the canonical language/theme flow.

## AppBackdrop

- Source: `src/runa/resources/js/components/AppBackdrop.vue`.
- Shared decorative CSS backdrop: large diffuse violet/rose and cyan/blue corner gradients with a quieter center, and a masked 88px technical grid at theme-specific opacity (light 9%, dark 4.5%). Page-background rings/arcs are removed. No raster texture or external assets. The backdrop stays static by default; MinimalLayout alone opts into sparse point glows.
- Decorative, fixed, pointer-transparent and clipped within its own viewport. Place inside an isolated layout stacking context behind opaque reading surfaces. Canonical use: MinimalLayout and ApplicationLayout. Follow the current background direction in `docs/runa/handbook/design/visual-direction.md`.

## RunaLogo

- Source: `src/runa/resources/js/components/RunaLogo.vue`.
- Shared decorative project mark based on the supplied transparent PNG alpha mask. Inherits `currentColor`; CSS mask crops transparent padding without modifying the original image. Default height 28px with the mark's natural aspect ratio; CSS may override height.
- Use in brand links and decorative artwork instead of the temporary Tabler mark. The owning link/control supplies its accessible name; the decorative mark is hidden from assistive technology. Source/provenance: `docs/runa/handbook/design/assets.md`.

## AppConstellation

- Source: `src/runa/resources/js/components/AppConstellation.vue`.
- Decorative deterministic SVG with thin lines and softly layered star points; `variant` selects one of three distinct branched patterns with 6–8 points. Desktop widths scale with the viewport (roughly 48–62vw with 500–1200px limits); mobile retains two cropped structures at 580/650px. Position them partly off-screen and behind the card, not as small isolated ornaments. Lines retain thin non-scaling strokes. Geometry and links remain static; AppBackdrop can animate point opacity when explicitly enabled, without an image asset or dependency.
- Uses theme color tokens and is hidden from assistive technology. Canonical use: three peripheral details in AppBackdrop, reduced to two quieter details on narrow screens.

## ApplicationLayout and AppNavigation

- Sources: `src/runa/resources/js/layouts/ApplicationLayout.vue`, internal recursive `src/runa/resources/js/components/AppNavigation.vue`; public import ApplicationLayout. Slots/props and route/preference contracts are documented in `docs/runa/handbook/architecture/frontend.md` and page-layouts.md.
- AppNavigation reuses existing AppIcon/AppTooltip; supports link availability, URL-derived active state, whole-row disclosure buttons for groups and collapsed labels. Group labels do not navigate; leaf entries remain links. A centered CSS chevron lives inside the disclosure control, matching top-navigation convention. Right-position tooltip is a narrow shell requirement, not completion of the later feedback/layer group. All sidebar entries have a supplied or fallback Tabler icon.
- Header and sidebar share a static 44px CSS grid over their opaque surface, using 30% of the theme backdrop-grid opacity and secondary-text color; forced-colors removes the decorative texture.
- The profile trigger remains a dedicated 44px circular button without a border or background frame around its avatar; visible keyboard focus remains. Its shell selector takes precedence over shared button surfaces/corners. ApplicationLayout preference buttons use sm (36px) with 18px icons. The sidebar toggle uses an 18×14px geometric three-bar mark centered in a 36px sm square, 24px from the header left edge, avoiding font-baseline glyph shifts. Header toggle and initials avatar expose visible hover/focus/pressed states through the semantic primary palette in both themes.
- Existing AppPreferenceControls and useDisplayPreferences are shared shell composition, preserving root timing/focus. MinimalLayout can supply staticPreferences for build-time native error rendering; the same controls/buttons/icons render GET forms in navigation mode without DOM/storage access. Do not create another copy when later finalizing buttons/feedback; update both consumers. AppNavigation/profile/drawer composition is finalized with the owning later component points, not a competing exported dropdown/modal system.

- Collapsed sidebar renders an icon-only list with 8px vertical padding; hidden headings/labels/submenus and their wrappers reserve no space. Saved expansion restores on reopening. It centers enabled/disabled icons identically, omits the supplementary sidebar-bottom slot and displays only the platform version in its footer. Top navigation has dotted edge cues only where more content is scrollable; cues fade over 160ms and respect reduced motion. Scroll/resize/content changes refresh the edge state.

- Shell motion: desktop sidebar width/workspace offset and phone drawer slide use 180ms ease-out; inline groups expand with a 160ms grid/opacity transition; top and initials cards fade with 4px travel over 140ms. Closing panels become inert immediately; reduced-motion disables transitions. Repeated toggles can reverse ongoing transitions.

## AppTopNavigation

- Source: `src/runa/resources/js/components/AppTopNavigation.vue`; internal application-shell navigation, not a generic dropdown component. Distinct from the sidebar's recursive AppNavigation.
- Horizontal group labels are disclosure buttons. Their dropdown card is teleported to body outside the scroll container, positioned under the trigger/header and clamped to the viewport. Header stays 64px when open; sidebar groups retain inline expansion. The panel reuses AppNavigation for its child links.
- Escape, outside click, route change, document scroll and resize dismiss it; nav scrolling updates its anchor position; focus returns on Escape. Tab boundaries connect the popup back to normal navigation order. General tooltip/popover floating behavior is implemented in point 9; route navigation retains its specialized anchoring and keyboard contract.

## PageNavigation

Public `PageNavigation` from `@/runa/ui` publishes subview links to its nearest
ApplicationLayout top bar without a duplicate content menu. A custom App layout
uses `usePageNavigationHost`; independent composition retains labeled controlled
buttons (including available child destinations). Props, events, host lifetime
and URL/guard behavior are defined in [page layouts](page-layouts.md#page-level-navigation-composition).
Sidebar entries select areas/modules; page-level navigation belongs in the top
bar. Forms and record/operation actions remain in content.

## Horizontal scrolling

Supplied Table, Tabs (including modal tabs) and ApplicationLayout top navigation
share horizontal wheel handling and decorative dotted left/right edge cues.
Cues appear only toward hidden content, update on scrolling, resizing and content
changes, and disappear when content fits. They ignore pointer input and respect
reduced motion. Keep this behavior consistent in future horizontal viewports.

Over an overflowing menu/tab strip or table header/scroll rail, the dominant wheel
axis moves content horizontally; pixel, line and page deltas are supported. At an
endpoint the gesture remains within that horizontal control. Ctrl+wheel preserves
native zoom. Content that fits retains normal vertical scrolling. Table body rows
also retain vertical wheel scrolling. Native touch/trackpad/scrollbar access and
existing keyboard/focus semantics remain available.

Ownership is Runa UI; public consumption remains Table/Tabs/ApplicationLayout from
`@/runa/ui` with their existing props/events/slots. Their private
useHorizontalScroll implementation and runa-horizontal-frame styling are delivered
runtime internals, not a new customer helper API. Customers compose the supplied
components or implement the same behavior in their own presentation; they do not
import the private composable. Vertical dialogs, popovers, tooltips, selects and
toasts use their existing wrapping/height constraints, not horizontal wheel
conversion for ordinary vertical content.

## Content containers and navigation

- Public `Card`, `Divider`, `Accordion`, `Tabs`, `Dropdown`, `Breadcrumbs`, `Pagination` map to `src/runa/resources/js/components/App*.vue`; types `TabItem`/`BreadcrumbItem` come from `@/runa/ui`. No shell, router or global installation is required.
- Card offers header/actions/default/footer slots; absent header/footer reserve no space. Card and Accordion headers use the lighter raised surface and semibold ordinary-text titles above darker neutral bodies (muted surface in light theme, ordinary surface in dark theme). The card footer matches the lighter header surface, with smaller secondary text and compact spacing. Divider is a semantic horizontal rule, or a decorative labeled section break.
- Accordion requires boolean v-model and label; optional disabled. Native Enter/Space toggles through update:modelValue; its hidden content stays mounted. Compose independent accordions for multiple expansion, or control their values for exclusive expansion.
- Tabs requires string v-model, unique stable item IDs and label. Each item has id/label/optional disabled; named slots match IDs. Horizontal arrows wrap across enabled tabs; Home/End move to endpoints and automatically activate. Inactive panels stay mounted and hidden. If the controlled selection disappears/is disabled, the first enabled item is presented; the owner should reconcile its model. Empty/all-disabled lists have no active panel.
- Dropdown is a controlled boolean v-model disclosure for arbitrary interactive content, with required localized label and optional disabled trigger. It composes the existing Popover and Button, with named nonmodal dialog semantics, viewport placement, outside/Escape dismissal and trigger focus return. Its default slot receives close. It is not an action-list/split-button API or a replacement for dedicated shell menus. Popover additionally supports optional controlled v-model while preserving its existing local default.
- Breadcrumbs requires label and readonly items (label/optional href). The final item uses aria-current=page; earlier safe same-origin absolute/public HTTPS URLs are anchors. Unsafe URLs render text. Optional navigate(href, MouseEvent) lets a resolver preventDefault while preserving modified-click/native navigation behavior. the isolated ApplicationScenario test fixture now uses this primitive in the shell breadcrumbs slot.
- Pagination requires numeric v-model/pages and localized label/previousLabel/nextLabel; optional disabled. It emits requested pages without fetching or owning data. It renders at most five numbered controls plus previous/next, with gaps; current page uses aria-current. Invalid/nonfinite bounds normalize to one page; out-of-range selection is clamped for display. Consumers own query/page resets and loading/disabled state.

## Loading and asynchronous content

- Public `Spinner`, `Skeleton`, `Progress`, `EmptyState`, `AsyncContent` map to `src/runa/resources/js/components/App*.vue`. `AsyncContentState` is loading/refreshing/error/empty/success. Refreshing retains the success subtree and exposes aria-busy; see [view readiness](loading.md). No fetch/provider lifecycle is hidden in these primitives.
- Spinner requires localized label; sm/md/lg are 14/24/44px. Standalone uses role=status; decorative removes announcement for an already named parent. Button busy state and LoadingOverlay now compose this shared spinner, preserving overlay timing/focus/color capture. Reduced motion removes rotation.
- Skeleton is decorative/aria-hidden, with text (1–10 lines, default 3), block or circle geometry. Callers provide the loading status; reduced motion removes pulsing. Skeletons do not fetch identity data or imply actual data progress.
- Progress requires localized label; value null/absent/nonfinite is indeterminate. Finite value is clamped to 0..max (positive finite max, otherwise 100). ARIA uses actual value/max units; visible percentage is derived only for determinate progress. Optional valueText supplies a localized meaningful equivalent. FeedbackTone selects neutral/success/info/warning/danger; reduced motion stops travel/transition while leaving the status visible. The caller owns completion/failure/cancellation.
- EmptyState requires title, optional description/icon; icon and actions slots support composition. It is static by default. AsyncContent announces its default empty state through role=status.
- AsyncContent requires controlled state and offers loading/error/empty/success slots, with default slot as success fallback. Defaults compose ContentLoading, Alert and EmptyState; omitted copy follows the current application language and all text remains configurable. Optional retryLabel exposes a retry button and retry event; custom error slot receives retry(). Retry never invents success or fetches data. Only the active state is mounted (success/refreshing share one subtree), so hoist form/draft state if it must survive switching; network cancellation and stale-response handling belong to the caller. aria-busy reflects loading/refreshing; custom slots own their appropriate accessible status semantics.

## Statistics and per-view modes

- Public `Statistic`, `Statistics`, `useStatisticsMode`; `StatisticsMode` is global/filtered. Statistic requires label/value (string or number); optional description/icon/trend and FeedbackTone. Semantic text/top indicator supplements the supplied labels and trend text; no numeric calculations or automatic good/bad direction inference. Slots label/value/icon/trend/visualization support custom summaries/charts; callers provide accessible chart equivalents and formatted values.
- Statistics requires controlled modelValue, label/globalLabel/filteredLabel, optional disabled; always presents two pressed-state buttons and passes mode to its default slot. Its responsive grid holds ordinary Statistic children; no shell/provider dependency. Print hides the switch while preserving the rendered values. Callers supply global/filtered datasets; changing a filter must not change global values.
- `useStatisticsMode(scope, defaultMode = 'global', store?)` uses versioned existing preference storage under control statistics.mode. Scope has stable applicationId/moduleKey/viewKey; each view may choose its own default. Values validate to global/filtered; corrupt/unsupported input falls back safely, denied storage uses existing session memory. Scope/default/store are fixed for a mount: remount a view on identity changes. the isolated ApplicationScenario test fixture keys its statistics composition by route and demonstrates actual filtered sample values. No account sync, aggregation service or global store is introduced.

## FormSection, FormField, TextInput, NumberInput and useForm

- Sources: `src/runa/resources/js/components/App{FormSection,FormField,TextInput,NumberInput}.vue`, `src/runa/resources/js/composables/useForm.ts`, `src/runa/resources/js/lib/decimal.ts`; public facade exports components, form types, useForm/laravelFieldErrors and parseDecimal/serializeDecimal/formatDecimal.
- FormSection supplies semantic fieldsets, descriptions, 1/2/3 responsive columns and controlled collapse preserving values. FormField exposes linked labels/descriptions/errors through scoped slot IDs and invalid state.
- TextInput supports text/textarea/password/email/url/tel, native autofill attributes and required/disabled/readonly/invalid states; nullable is opt-in. NumberInput supplies exact string/null values for integer/decimal/currency/percentage, localized decimal separators and optional scale without rounding; incomplete values remain in the model with inline errors. Units such as PLN/% appear beside the input through TextInput's noneditable suffix slot, remain linked to its accessible description and stay outside its value.
- useForm tracks cloned finite-JSON values, nested dirty baselines and controlled busy/errors; maps 422 dot keys, preserves 409/server-failure drafts and supports explicit server-snapshot reset. useForm itself has no hidden network requests or business rules; optional dirty guards and masks compose it separately. Detailed API and numeric serialization contracts: forms.md.

## Date, logical, file and additional fields

Public facade exports DateInput, TimeInput, DateTimeInput, DateRangeInput, DateTimeRangeInput, Checkbox, Switch, RadioGroup, FileInput, ColorInput, HiddenInput and TagsInput from their App-prefixed components. AppTemporalInput/AppTemporalRangeInput/AppBooleanInput are internal compositions; AppCalendarPicker, AppTimePicker and AppPickerSlider are internal picker controls. All fields reuse FormField and established theme/state styles; native checkbox/radio keyboard semantics remain intact. Checkbox supports boolean/null (indeterminate), Switch boolean, RadioGroup string/null with disabled options and isolated group names. Readonly blocks edits, disabled removes interaction. Custom label slots replace the default FormField label.

DateInput/TimeInput/DateTimeInput accept optional size sm/md (md default); the input and picker trigger share 36px/44px heights. DateInput/TimeInput use canonical string/null values; DateRangeInput uses { start, end } date-only strings/null with chronological validation. DateTimeInput defaults to the application timezone (optional explicit timeZone for a domain-specific zone), omits timezone annotations and stores an explicit-offset instant normalized to UTC on edit. Date/time/color use application-owned Popover panels and text inputs, never native date/time/datetime-local/color pickers. Date fields display DD.MM.YYYY in PL and YYYY-MM-DD in EN; moment fields add 24-hour time. Calendar supports month/year selection, Monday-first roving day navigation, Home/End and PageUp/Down (Shift for years). Time panels expose scrollable hour/minute lists with arrow/Home/End navigation, preserving explicit seconds/fractions from text. The inline color trigger is frameless, retaining its keyboard focus indicator; the field border and color chip remain. Color panels expose an HSV plane, three keyboard/pointer sliders and a palette, with six-digit hex text entry. Escape/outside/Done dismiss; focus returns on Escape/Done. See forms.md for draft and DST contracts. FileInput uses readonly File[] with accept/count/size UX validation and localized messages; it never uploads. Its application-styled dropzone is a real keyboard/touch picker button, with file-drag feedback, accepted types/limits, selected count/total size and metadata rows with extension icons, size and remove actions. Multiple selections/drops append batches; single selection replaces the previous value. Invalid/over-limit files remain visible for correction; readonly hides editing controls and disabled/busy fieldsets block drops. Removal restores focus to the next removal action or chooser. Optional locale formats sizes, dropzone/metadata copy and default choose/remove/error labels; explicit copy props override these defaults. ColorInput accepts null or #RRGGBB and retains invalid text drafts. HiddenInput is a native hidden serialization field, never a security boundary. TagsInput commits trimmed entries with Enter (outside IME), rejects duplicates/limits, clears drafts with Escape and supports removal; it exposes validity and focus().

## MaskedInput, Wizard and advanced form composables

- Sources: AppMaskedInput.vue, AppWizard.vue; useDependentFields.ts, useDirtyGuard.ts, useWizard.ts; lib/mask.ts. Public imports: MaskedInput, Wizard, formatMask, parseMaskedInput, useDependentFields, useDirtyGuard, useWizard and advancedForms types.
- MaskedInput reuses TextInput’s field/autofill/error states. Raw/null model, formatted and completeness events remain separate from domain validation; positional token masks preserve native editing/IME.
- Dependent fields expose keyed loading/error/ready/options plus retry/cancel over an acyclic multilevel graph; values clear synchronously and requests abort on invalidation. Dirty guards protect only opted-in navigation/unload, preserving drafts and requiring a caller-owned confirmation UI.
- Wizard composes Button/Progress, controlled steps and scoped content/feedback/actions slots. useWizard handles validation, backtracking, busy/retry and finish without owning values or a server draft. Full contracts and integration examples belong to forms.md.

## Dialog, AlertDialog and ConfirmDialog

- Sources: AppDialog.vue, AppAlertDialog.vue, AppConfirmDialog.vue; internal useModalLayer.ts, lib/layers.ts and lib/scrollLock.ts. Public DialogProps/ConfirmDialogProps/DialogSize/DialogCloseReason types accompany the component exports.
- All use controlled boolean v-model and a required visible title; description and error are optional. Sizes sm/md/lg/xl/full cap at 380/560/800/1120px/viewport respectively, always fitting narrow screens. Body scrolls independently; header/footer stay available. A title-only header centers the title against the close button; a header with description keeps top alignment. Content and footer slots compose existing controls.
- Dialog error feedback uses FormNotifications inside the modal portal, with dismissible persistent failure feedback and no inline duplicate.
- initialFocus accepts a selector in the panel; by default custom Dialog focuses its first content control or panel, ConfirmDialog focuses Cancel and AlertDialog focuses acknowledgment. Modal focus traps Tab/Shift+Tab, contains programmatic focus and restores the invoking control after close. Nested dialogs and their floating Select/Popover/Tooltip panels share dismissal ownership; one Escape dismisses only the newest layer.
- Escape defaults on, backdrop dismissal defaults on (closeOnOutside=false selects close-button-only pointer dismissal) and requires down/up on the backdrop. closable=false disables user dismissal; busy blocks closing and disables contained native fields. close emits close/escape/outside reason plus update:modelValue(false); parent-owned model changes remain authoritative. Prior inert/scroll state is restored on close/unmount, including dynamic body siblings and nested locks.
- ConfirmDialog supplies localized cancel/confirm buttons, configurable labels/confirmIntent and emits confirm while staying open. Caller owns busy, error, retry and success closure; never close before a failed operation can be recovered. Cancel and initial focus favor the nondestructive action. AlertDialog uses role=alertdialog and emits acknowledge before requesting close. locale defaults pl; pass en for English.
- The accepted application drawer remains specialized; it only reuses the scroll-lock primitive. No provisional dialog composition exists in either layout. The isolated advanced-form scenario uses ConfirmDialog without changing useDirtyGuard’s application-owned confirmation seam.

### Table

`Table` includes shared icon-only refresh/reset controls, compact dictionary filters, default in-memory column/views controls and explicitly authorized bounded export/print adapters; see the exact optional props in [tables.md](tables.md).

`Table` (`src/runa/resources/js/components/AppTable.vue`) and `useTable` provide client/server processing, controlled query and cross-page explicit/all-matching row selection, column render/filter/sort adapters (sorting defaults on with explicit `sortable: false` opt-out and server capability gating), async/retry states and accessible horizontal scrolling. Public types and provider integration are documented in [tables.md](tables.md). `Checkbox` accepts `hideLabel` for an accessible visually hidden label in compact table cells.

### Nested semantic colors

Alert (including Toast/AsyncContent errors), Badge, Status, Progress and Statistic establish a reactive semantic tone context for nested feedback. Omitted feedback tones inherit; explicit tones establish a new context and neutral resets it. Button never inherits feedback tone. Its explicit action intent follows [action button semantics](#action-button-semantics), including blue AsyncContent retry and neutral dismissal. This works through wrappers and slots without descendant CSS overrides. Ordinary input validation and shell navigation retain their own contracts.

### TablePreferences and useTableViews

`src/runa/resources/js/components/AppTablePreferences.vue` supplies a sm icon-only ghost settings button with a localized accessible name/Tooltip, matching export settings, plus the optional sm Available views selector (Default view first) and shared Dialog for column visibility, drag/keyboard order, a compact numeric width/px field with measured initial value and automatic reset; named views support create/select/update/rename/delete/default/reset. View-operation outcomes use FormNotifications inside the preferences dialog; width errors remain beside their fields. `useTableViews` owns scoped persistence, schema migration/reconciliation and controlled query snapshots. Import both through `@/runa/ui`; connect its columns to Table's optional columnState. See [tables.md](tables.md) for the public contract and example. No existing layout has provisional table settings to replace.

### Small field controls

Select, TextInput and NumberInput accept size=sm (36px) and size=md (44px, unchanged default). Select sizes its trigger, popup options/search/actions consistently; TextInput sizes suffixes as well. TextInput hideLabel retains an associated visually hidden label for compact inline controls. NumberInput forwards its declared size to the text control. Use sm with sm Buttons in tables, toolbars and secondary view/test settings. Keep ordinary data-entry forms, touch-first MinimalLayout preferences and dialog primary actions at their established size. ScenarioForms/ScenarioSelection test fixtures include standalone small examples; test application toolbar and secondary fixture selectors use sm. Other specialized inputs retain their existing form sizes until a concrete compact use needs them.

### TableActions and OperationPanel

TableActions composes sm icon export/print actions, a scope/orientation settings Popover and contextual bulk controls with the reusable OperationPanel and useOperation controller. Real bounded browser adapters and an optional API export provider are exported through @/runa/ui; no shell or backend jobs are installed. OperationPanel supports progress, permission errors, partial counts, retry/cancel and download. Table's toolbar slot exposes feedbackTarget; pass it to TableActions to keep operation feedback in the full-width outlet below aligned controls. See [tables.md](tables.md) for contracts, bounds, server authorization/idempotency and notices. Both existing layouts were inspected: neither has provisional export/bulk controls to replace; the test-only ScenarioTables fixture demonstrates both client and simulated server integrations.

## Independent consumer verification

Verify independent application consumers through tests/backend/app: compose public forms, selections, Tabs, ConfirmDialog, Table and TableActions with caller-owned state and bounded fixtures. Import the shared CSS and initialize only the needed locale/theme; a shell, resolver, registry or preference bootstrap is optional. Use the real testing Nginx endpoint for application E2E. Platform fixture harnesses and regressions remain owner-only and are not installed customer dependencies. Test tools/output never enter production.

## Import and extension example

Load `src/runa/resources/css/app.css` once in the consuming entry, then use individual public components without a shell/router installation:

```vue
<script setup lang="ts">
import { ref } from 'vue';
import { Button, TextInput } from '@/runa/ui';
const name = ref('');
</script>
<template>
  <TextInput v-model="name" label="Nazwa" />
  <Button size="sm" :disabled="!name.trim()">Zapisz</Button>
</template>
```

Extend presentation through existing slots/props first: for example Table's `cell-<key>`/`actions` and ApplicationLayout's toolbar/statistics slots. Keep authorization, business validation and writes in the owning feature; add a reusable prop only when multiple real consumers share its behavior. Keep application composition coverage under tests/backend/app; test scenarios are test assets, never application demo routes.

IdentityProvider keeps its existing reactive subtree contract. Normal application startup resolves the public backend identity with `loadApplicationIdentity` from the UI facade; build-time fallback uses the customer application manifest ID/name/version and separate platform manifest version; customer scaffold VERSION is not a runtime identity input. See docs/runa/handbook/architecture/configuration.md for canonical metadata and compatibility, and docs/runa/handbook/architecture/frontend.md for consumer behavior.

## Optional module page and slot adapters

- `src/runa/resources/js/components/ModulePage.vue` and `ModuleSlot.vue`; public imports through `@/runa/modules` or the UI facade. They introduce no replacement layout/control styling or motion.
- ModulePage takes nullable view, built components, href, optional locale and unavailableText, with an unavailable scoped slot. It reconstructs exact route context and mounts only available pages with matching manifest hashes. Gate before setup/import, rather than starting page requests then hiding content.
- ModuleSlot takes nullable view, components, name and exact scalar values. It preserves backend contribution order and stable identity, rejects incompatible props/builds, and exposes ordinary fallback content. Host/contributor availability and permission projection are backend-owned.
- createModuleComponents wraps the opt-in virtual local registry with raw lazy Vue components. useModuleFrontend explicitly refreshes through the existing HTTP wrapper, clears stale presentation and cancels/ignores obsolete requests. Follow the public module-framework frontend section for schema, build inclusion, scalar props, ordering persistence and permission limits.

## Account preference adapter

Public `accountPreferences` and `createAccountPreferenceStore` complement the
existing PreferenceStore/usePreference contract. Display/navigation/statistics/table
consumers subscribe to account hydration and keep guest values separate. Public `PreferenceFeedback` (`AppPreferenceFeedback.vue`) is reused by both layouts
and shows save/load/conflict feedback through shared notifications. Rejected writes
refresh the server snapshot automatically without replay. Retry loading appears only
when synchronization fails; feedback is static and announced, with no added motion.
See [Auth preferences](../../modules/auth/preferences.md) for exact declarations,
revisions, safe legacy adoption and logout/late-response behavior. Custom standalone
stores remain opt-in; standalone components initialize no account session.

## Auth entry composition

`AuthEntry`, `entryPage` and `EntryPage` are exported separately from `@/runa/auth`.
The lazy module-owned flow reuses MinimalLayout and the shared Form, TextInput,
RadioGroup, Checkbox, Button, Spinner and Alert components. Changing page resets
and cancels the old flow. See [entry screens](../../modules/auth/entry-ui.md) for
exact routes, public form constraints, server restrictions, safe returns and
in-memory one-time material. Account management/session-expiry UI uses AuthApplication below.

`AuthOperationProofPage` reuses the public password-only useOperationProof
controller for action-bound confirmation. The shared Auth shell hosts its standalone
MinimalLayout presentation and preserves operation drafts. Inline/modal credential
forms are retired. See [proof contracts](../../modules/auth/entry-ui.md#operation-proof-composition).

### Retained presentation visibility

UI owns public providePresentationVisibility(visible:MaybeRefOrGetter<boolean>):void,
implemented in resources/js/composables/usePresentationVisibility and exported from
@/runa/ui. A child inherits its parent's visibility; Dialog portals hide and release
focus/scroll ownership while false, retaining mounted content and controlled drafts.
Auth uses it when the full password-confirmation page temporarily owns presentation.
It grants no access and never clears protected data; the owning session/controller
still handles identity loss. It ships with the ordinary frontend runtime.

## LinkButton and Auth application composition

`LinkButton` maps to `AppLinkButton.vue`: a native anchor with required href and
existing button variants/tone (neutral by default), preserving open-in-new-tab and keyboard semantics.
Use Button for mutations and LinkButton for styled navigation; it inherits the
shared control focus/color/motion without duplicating local button markup. Auth
welcome and application dropdown/entry use it. It does not authorize a destination.

Public `AuthEntry`, `AuthWelcome` and `AuthApplication` load lazily from `@/runa/auth`.
The latter accepts optional account=true and composes the existing ApplicationLayout,
profile/sign-out slot, self-service forms, bounded Table lists, real-activity/expiry
boundary and explicit safe draft review. See the [account UI](../../modules/auth/account-ui.md).
Custom shells/forms must preserve request/account ownership and never replay writes.

## Application boot and presentation contracts

Public `@/runa/ui` exports `createApplication(root)` (Vue App with non-blocking
public identity hydration), `HttpErrorLayout`, `HttpErrorContent` and
`HttpErrorLayoutProps`. Shared CSS is imported through `@/runa/ui/styles.css`.
`@/runa/http` exports the single `request`/`ApiError` transport boundary.
`@/runa/auth` exports DefaultApplication, AuthPresentationProvider and its typed
component map, AuthEntry/AuthApplication/AuthAccount/AuthWelcome/AuthAvatarCropper and the shared
entry/account/welcome/session controllers. See [frontend adoption](customization.md)
for supported props, slots, lifetime and security obligations. All future Runa
module views follow the same usable-default/public-replacement requirement.
Canonical custom consumers are owner-only fixtures under
tests/frontend/runa/unit/fixtures/customization; they are excluded from delivery.

MinimalLayout exposes `header-actions` with translator `t` inside the default
header action group beside locale/theme controls. Use it for page entry actions
without duplicating identity/preferences. Welcome authentication links use this
slot and wrap responsively; custom layout wrappers forward it.

Button and LinkButton share the single src/runa/resources/css/button.css style
source through scoped imports, retaining variants, themes, focus and reduced motion
without making link styling depend on mounting a separate button instance.

Public TextLink from @/runa/ui (AppTextLink.vue) renders a native href anchor with
quiet muted 12px text, no surface/border or permanent underline, hover underline/
color, visible keyboard focus, 44px touch target and reduced-motion handling.
Welcome and Auth entry navigation share this implementation. Form submits use
Button outline variants; active tabs retain their independent selection styling.

ApplicationLayout omits the heading region for an empty title without heading or
action slots. Dedicated Auth user-dropdown rows remain shell-local: compact
avatar/initials and name identity, divider, 44px left-aligned icon/label navigation/sign-out.
The public Icon catalogue includes logout. Panel użytkownika/User panel appears
only in the user dropdown; the default /app workspace has no placeholder content.

The default account view composes four existing Cards with independent Forms and
shared FormNotifications outcome feedback. AuthAvatarCropper exports a local,
bounded square crop through `@/runa/auth`; see [the account UI contract](../../modules/auth/account-ui.md)
and [public customization](customization.md). The account card hosts it in the
shared Dialog with explicit Apply/Cancel. The canvas displays a circular bright
frame and dimmed surrounding image; direct mouse/touch dragging, wheel/pinch
zoom and keyboard alternatives replace sliders. Mask pixels never enter export.
AppPickerSlider remains the internal color-picker control, with optional min=0;
disabled fieldsets/inert ancestors block edits.

## Avatar

Public Avatar from `@/runa/ui` renders a decorative circular image with initials
fallback and object-fit cover. Props: initials (required), src (default empty),
size in px (default 40), loading (default false) for pending identity metadata.
A shared circular Skeleton pulses while metadata or the first image is pending.
Image loading includes download and decode, with a 2.5-second bound and initials
fallback on error/timeout. Same-identity replacements retain the decoded image
while loading; explicit removal clears it. Optional image: ImageReadiness shares
one prepared image owner between multiple avatars. Source/identity fencing,
timeouts and composition are documented in [view readiness](loading.md).
Reduced motion/print inherit Skeleton's static fallback. The parent owns the
accessible name, authorization and safe image URL; no profile storage or global
private image cache lives in this primitive. During initial loading its background
is transparent and Skeleton fills its exact bounds. ApplicationLayout accepts
userAvatar, userLoading and userAvatarImage alongside userName/userInitials.

## Form conflict decisions

`FormConflict` (`AppFormConflict.vue`) composes the shared Alert and Button without
extra motion. It renders localized current values (`current`), `locale`, `busy` and
optional `allowKeep` (default true), and emits `accept`/`keep`; its default slot can show the current server image or
other feature-owned comparison content. It never fetches,
discards, rebases or saves by itself. Place it inside the affected form, following
[automatic reconciliation](forms.md#automatic-read-reconciliation); disable Keep
when the edited target has disappeared. Preserve normal field errors. Explicit
resolution restores lost focus to the containing form when its decision buttons
unmount; background refresh never moves focus.

MinimalLayout and ApplicationLayout accept homeHref (default /) for the brand link.
Auth presentations supply /app while welcome is disabled or policy is unknown, and
/ when enabled. Custom layout wrappers forward it; it is navigation presentation,
not an authorization or server-routing decision.

## Account history detail

AuthHistoryDetail composes public Dialog with loading placeholders, Alert and Button for an explicit
read-only event modal, PL/EN before/after pairs and legacy/security explanations.
The controller fences overlapping/closed reads and supports retry. Public presentation
replacement and exported types are documented in the
[history contract](../../modules/auth/history.md); custom application presentation
lives in src/app. Existing Dialog owns motion/focus; no new transition is added.

Public replacement and controller types are listed in
[frontend adoption](customization.md#public-presentation-types). Import the named
Auth, layout, module page/slot and static-error contracts through their respective
facades. Supplied components consume the same prop/slot/event declarations;
customer wrappers need no private source imports.

## Admin shell and replacement

Public `@/runa/admin` supplies AdminApplication, AdminLayout, AdminLoading,
AdminEntryProof and AdminPresentationProvider, useAdmin/useAdminPage,
useAdminPresentation and resolveAdminComponent. Shell state and entry proof remain
platform-owned; nested providers inherit omitted overrides. Compiled owner/build
identity is checked before choosing an owned module loader or explicitly registered
App component. Missing/mismatched components show safe isolated unavailable states.
Generated field/form/table/action components remain specified for subsequent points;
follow the exact [Admin runtime status and contracts](../../modules/admin/frontend.md).
ApplicationSessionShell is exported by `@/runa/auth` for both Auth and Admin
composition; its account menu is a dedicated shell seam, not a business control.

Auth administrative defaults `AuthAdminUsers` / `AuthAdminSection` are exported
through `@/runa/auth`; Admin section dispatch/failure boundary and cancellable read
composition remain under `@/runa/admin`. See the complete
[administration presentation contract](../../modules/auth/administration.md#presentation-and-application-consumption).

`AdminRecordBrowser` is the public catalogue/detail composition from `@/runa/admin`,
with shared subject selection and dirty guards. Permissions page/section defaults
are exported by `@/runa/permissions`; Audit history/detail defaults by `@/runa/audit`.
Use the exact [record-browser contract](../../modules/admin/frontend.md#record-browser)
and owning [Permissions](../../modules/permissions/administration.md) /
[Audit](../../modules/audit/administration.md) presentation signatures.

Admin operational defaults `AdminOperations` and
`AdminOperationResult` plus `useAdminOperations` are exported by `@/runa/admin`.
Their typed props/slots/controller and replacement keys are documented in
[operational dashboard composition](../../modules/admin/frontend.md#operational-dashboard-and-replacements).
They reuse shared controls and retain native lifecycle, settings and cooperative
work outcomes; no copied session/proof or foreign engine logic belongs in App views.

Application settings reuse those primitives through public `AdminApplicationSettings`
and `useAdminApplicationSettings`, with per-field authority, source reset and dirty
read reconciliation. Replace `AdminPresentation.applicationSettings` using the
[controller contract](../../modules/admin/frontend.md#application-global-settings-and-runtime-branding).
The shared shell's public presentation loader and IdentityProvider apply runtime
branding without changing canonical identity.

Admin defaults and their Auth/Permissions/Audit sections follow the shared PL/EN
application preference. Public useAdminLocale/adminText handle localized AdminText
metadata and App presentation messages; use them in replacements and pass the
reactive locale to shared controls. See [Admin localization](../../modules/admin/frontend.md#polish-and-english-presentation).

## View readiness

Public useViewReadiness, ViewBoundary and useImageReadiness separate initial
reveal from background reads and local writes. Their complete signatures, identity
invalidation, bounded image fallback and supplied view audit are in the canonical
[view readiness guide](loading.md). They add no session owner or authorization.

## Source module presentation

UI owns `ModulePresentation`, `moduleCatalogue: readonly ModulePresentation[]`
and `getModulePresentation(id: string): ModulePresentation` exported by `@/runa/ui`.
The complete shape is `{readonly id: string; readonly name: {pl:string;en:string};
readonly description: {pl:string;en:string}; readonly icon: IconName}`. Source home
is `src/runa/resources/js/runa/ui/modulePresentation.ts`, delivered in the frontend
runtime. Names/descriptions come from validated platform/App module manifests;
platform icons use one source-owned map and unknown modules use layers-intersect
with their ID as the name. Welcome filters this catalogue to platform modules;
Admin applies it only to already authorized owners/diagnostic results. This source
catalogue does not describe installation, health, enabled state or access.

Application branding reuses FileInput, Select, fields and Button. Image draft/Canvas
behavior belongs to the public [settings presentation](../../modules/admin/frontend.md#application-display-defaults-image-drafts-and-page-headings).
Public useApplicationDisplayDefaults/formatApplicationInstant provides shared
display defaults and instant formatting; explicit user preferences win.

## Protected-section activity composition

Public `useProtectedActivity` from `@/runa/auth` composes the caller's admitted
session and owning section root without another session owner. Its `refresh`
reads status without renewal; scoped trusted input renews still-valid access.
Keep it mounted with replacement presentation, including relevant portals, and
clear protected content through `onExpired`. Complete signatures, server endpoint
preconditions and examples belong to the
[Auth idle-access contract](../../modules/auth/security.md#protected-section-idle-access).

### Value preview

`ValuePreview` is a UI-owned, shell-independent escaped read-only value renderer
for application/module consumers. Import `ValuePreview` and `ValuePreviewProps`
from `@/runa/ui`; implementation lives in
`src/runa/resources/js/components/AppValuePreview.vue` and ships in development
and production assets. Props: `text: string`, `format?: 'text'|'json'` (default
`text`), `locale?: 'pl'|'en'` (default `pl`). It has no events or remote reads.

The collapsed preview shows at most eight source lines and 480 Unicode code
points; collapsed JSON ends with a distinct ellipsis on its own line. Longer text has a right-aligned localized Show all/Collapse text link inside the preview
with aria-expanded and aria-controls. Activating it preserves the URL and keyboard
focus. Expansion displays the complete supplied value; replacement
text resets expansion. JSON mode formats complete JSON without reserializing its
numeric/string tokens or duplicate keys. Syntax highlighting renders key, string,
number, literal and punctuation spans through Vue text interpolation using existing
light/dark theme colors; forced-colors uses system text. Incomplete/invalid JSON
parts retain their escaped text and receive best-effort token coloring. Plain-text
mode remains uncolored. Formatting never infers dates or translates payload values. Authors retain
server authorization, redaction, continuation and record-lifecycle ownership;
collapse is presentation, not a security boundary.

### Content loading defaults

Use public `ContentLoading` for localized pulsing text/form/detail/image/navigation
geometry and retained-content refresh. `AsyncContent` and `ViewBoundary` select
this default automatically. `Dialog` accepts loading/loadingLabel/loadingShape
and an optional loading slot; ConfirmDialog/AlertDialog cannot confirm/acknowledge
unfinished content. Busy is separate mutation progress. Full signatures, ownership,
source placement, lifecycle and allowed circular-indicator owners live in the
[loading guide](loading.md#content-loading-presentation). Do not implement another
view-local animation or attach a global HTTP counter to the presentation.

DateRangeInput and DateTimeRangeInput now present one compact labelled field and
one range summary. Their shared popup contains From/Until temporal editors,
Clear/Cancel/Apply, open-end support and atomic valid-value publication. The public
TemporalRangeInputProps/DateRangeValue, reset/focus handles and precise datetime
zone/draft contract are documented in [forms](forms.md#compact-temporal-ranges).
Filters use size sm; ordinary forms retain md. Input layout no longer expands
into two labelled fields in its surrounding grid.

## Readonly boolean facts

Public `BooleanBadge` from `@/runa/ui` is owned by the platform component system
(`resources/js/components/AppBooleanBadge.vue`), delivered as frontend assets and
supported for App/module composition. Props: `value: boolean|null`,
`locale?: 'pl'|'en'` (pl), `tone?: BadgeTone`, `size?: 'sm'|'md'` (sm); no events
or slots. It renders localized Yes/No (Tak/Nie), null as an em dash, true as
success and false as neutral unless tone overrides it. Reuse it for readonly facts
and table boolean cells; filters and editable booleans retain their controls.
Admin exports `adminBooleanTone(key: string, value: boolean): BadgeTone` from
`@/runa/admin` for its shared account-risk mapping; see the Admin frontend guide.

### External control labels

Select accepts `hideLabel?: boolean`. Checkbox/Switch (BooleanInputProps), temporal
inputs (TemporalInputProps) and TagsInput accept `id?: string`; temporal inputs and
TagsInput also accept `hideLabel?: boolean`. These compose the existing TextInput,
NumberInput and FormField label presentation. Use an explicit stable ID for a
visible external label; hideLabel keeps the built-in label visually hidden.
The Admin module settings editor uses this contract for its compact rows; see
[named settings](../../modules/admin/settings-snapshots.md#localized-setting-identity-and-compact-presentation).

# Component Catalogue

Search this catalogue and the source before introducing a new control. First-use page-local elements are allowed; on second use, extract the common application element into `src/app/resources/js/components/`, migrate both consumers and document the local application contract in the same task. Consume platform exports through `@/runa/ui`; platform extension requires a Runa owner release. Keep entries limited to implemented components. Public imports use `@/runa/ui`: Button, Tooltip, Icon, Form, Select, LoadingOverlay and Logo map to their existing App-prefixed/RunaLogo implementations. No layout/router installation is required; load the application theme CSS in the consuming entry. IdentityProvider, MinimalLayout and Footer are also public; navigation and preferences now have resolver/storage implementations; bounded CSV/XLSX/PDF engines, printing and operation/provider contracts are available.

## Motion composition

All components have been reviewed for purposeful motion; see `motion.md` for the complete inventory, timing and accessibility rules. Internal AppCollapse (`src/runa/resources/js/components/AppCollapse.vue`) preserves disclosure DOM/drafts while clipping natural height and applying immediate inert/aria-hidden. It is not a public facade export. Internal useContentMotion (`src/runa/resources/js/composables/useContentMotion.ts`) reveals retained Wizard/CalendarPicker/AsyncContent updates without remounting; `src/runa/resources/js/lib/motion.ts` owns immediate exit deactivation and interrupted-entry restoration. Consumers inherit shared behavior instead of adding duplicate page entrances. Point-21 motion was accepted on 2026-10-19.

## AppSelect

- Source: `src/runa/resources/js/components/AppSelect.vue`; public `Select`, `SelectOption`, `SelectionProvider`, `SelectionPage`.
- All selection modes use the shared themed floating dialog/listbox, including simple single selection. Searchable adds a search field; multiple changes the value contract. No browser-owned option popup is used. String values are stable IDs; multiple=true uses string[]. Disabled options cannot be chosen. FormField supplies label/helper/warning/errors and required semantics; disabled/readonly block edits, clearable is explicit.
- Search is locale-aware for local options; provider queries debounce (250ms default), abort prior requests and ignore stale responses even if a provider ignores cancellation. Loading/error/retry/empty states remain distinct. Escape restores trigger focus, outside pointer dismisses, arrows/Home/End move over enabled options and Enter selects; IME composition is preserved. Multiple selection stays open until Done.
- Rendering is bounded to one page (40 default, at most 100). Local pages offer previous/next; remote providers receive query/cursor/limit/AbortSignal and return options/nextCursor. Remote next replaces the visible page; the provider owns cursor semantics, transport, ordering and authorization, without whole-dataset fetching. No automatic request on selection changes.
- selectedOptions supplies labels for initial/off-page values. Labels for selected remote options survive page/query changes; unknown IDs remain visible as IDs. Selection events expose option snapshots; IDs remain the model. Keep durable option labels in caller state when unmounting. locale defaults to pl; pass en for English presentation. All closed selection modes share the same CSS chevron anchored 16px from the control’s right edge, independent of selected-label length. Closed controls retain normalized field styling; readonly controls never open the option panel.

## AppForm

- Source: `src/runa/resources/js/components/AppForm.vue`.
- Prefer for controlled application forms: always `novalidate`, prevents native navigation, emits the submit event only when not busy and exposes `aria-busy`.
- Props: busy/valid/dirty, label/description and responsive columns; slots default/feedback/actions. Busy disables contained controls; valid=false emits invalid instead of submit. focusInvalid() supports async error recovery.
- Field validation remains the owning feature's responsibility; FormField/TextInput/NumberInput and useForm now supply reusable field/error/value contracts (see forms.md).
- Retained as the controlled form wrapper; the root preference buttons do not require a form.

## AppIcon

- Source: `src/runa/resources/js/components/AppIcon.vue`.
- Typed subset of four Tabler outline icons with optional `size` (24 default) and `color` (`currentColor` default); decorative SVGs are hidden from assistive technology. Put an accessible name on the owning control when it is icon-only.
- Local source assets, licenses and revision/hash provenance: `docs/runa/handbook/design/assets.md`.

## Root preference buttons

- Sources: `src/runa/resources/js/components/AppPreferenceControls.vue`, `src/runa/resources/js/composables/useDisplayPreferences.ts`, consumed by both web layouts.
- Exactly two buttons (44px md in MinimalLayout, 36px sm in ApplicationLayout): language cycles PL/EN, theme cycles system/light/dark with corresponding icons. Accessible names describe current/next values; short application tooltips identify the action. Both controls use AppButton. Existing composables preserve preferences. No select or submit step.

## AppTooltip

- Source: `src/runa/resources/js/components/AppTooltip.vue`; public `Tooltip`. Existing `text`, optional bottom/right placement and scoped `descriptionId` remain compatible. The description ID is present only while shown.
- Pointer and keyboard-focus states are separate. A short 80ms bridge allows entering teleported content; pointer leave from both trigger/content dismisses unless keyboard focus remains. Click, Escape, outside pointer and document scroll dismiss; rapid enter/leave and preference transitions do not retain stale hover state. Escape stays suppressed until a new hover/focus session. Leave reschedules a single hover-bridge timer and dismissal cancels it, preventing obsolete callbacks from disturbing later interactions.
- Uses shared `useFloatingLayer` for measured viewport-clamped fixed positioning, resize/content observation, top-layer Escape/outside dismissal and teardown. Both placements teleport outside scroll clipping; no runtime DOM access during static error rendering.
- Tooltip is descriptive only; use Popover for interactive content. Opacity transition is 140ms with reduced-motion support. Keep an accessible name on icon-only triggers.

## AppAlert, AppBadge and AppStatus

- Sources: `AppAlert.vue`, `AppBadge.vue`, `AppStatus.vue` under `src/runa/resources/js/components/`; public `Alert`, `Badge`, `Status`. Semantic `FeedbackTone`: neutral/success/info/warning/danger for alerts, toasts, badges and statuses. Neutral alerts/toasts use the ordinary raised surface, secondary text and control border, with no status tint. All use shared theme role tokens.
- Alert accepts optional title, content/actions slots, dismissible and localized closeLabel. Emits close; the owner controls removal. Danger uses role alert, other tones role status; announce=false disables live semantics for static catalogs or an existing live region. Symbols and readable content supplement color.
- Badge is static inline labeling/counts, size sm/md, no live role. Status pairs a decorative dot with required caller-provided readable slot content; announce opt-in enables role status.

## AppPopover

- Source: `src/runa/resources/js/components/AppPopover.vue`; public `Popover`. Required label names its nonmodal dialog; optional align start/end. Trigger slot receives toggle/open/panelId; default slot receives close. Optional picker uses a viewport-bounded 360px panel with compact padding; initialFocus targets a selector inside the panel. Tabbing excludes roving tabindex=-1 items. Caller owns the trigger label, aria-expanded/controls and aria-haspopup=dialog.
- Focus moves to the first control (or panel); Escape closes the top layer and returns to its trigger. Outside pointer dismisses without stealing focus. Tab leaves the nonmodal panel through normal neighboring trigger order; Shift+Tab from the first control returns to the trigger. Shared viewport positioning flips/clamps, reacts to scroll/resize/content and escapes clipping. Nested popovers dismiss one level per Escape.
- ApplicationLayout's unique initials profile control now uses Popover for its existing user-menu slot; navigation remains specialized because its route/availability/scroll/Tab contracts differ. No generic action-list or split buttons are reintroduced.

## ToastViewport and useToasts

- Sources: `AppToastViewport.vue`, internal `AppToast.vue`, `composables/useToasts.ts`; public `ToastViewport`, `useToasts`, ToastMessage/FeedbackTone types.
- Local setup-scoped queue: `const { messages, show, dismiss } = useToasts()`. Pass messages to ToastViewport and bind dismiss. show accepts title, optional description/tone/duration and returns its ID. Default duration is 5000ms, danger defaults to persistent (0); invalid/nonfinite durations are persistent. This is transient feedback, not a notification center.
- Viewport is top-right and fits narrow screens; an initially empty polite live region announces additions once. Explicit localized close buttons are always present. Hover or focus pauses the remaining time; resume waits until both have left. Removing a focused toast through useToasts restores its invoking control when available. Timers/listeners clean up with components.
- Keep actionable/important errors inline as well as in transient feedback; the host feature owns that error state. Standalone ToastViewport consumers can provide readonly ToastMessage[] and handle dismiss themselves, including any focus destination. No shell/provider/global store installation.

## AppButton

- Source: `src/runa/resources/js/components/AppButton.vue`; public `Button`. Native attributes/listeners inherit; safe default `type="button"`. No shell dependency.
- `variant`: neutral (compatible default), solid, outline, soft, ghost. `tone`: primary, secondary, success, danger, warning, info, accent. `size`: sm (36px), md (44px), lg (52px). Use md for usual touch actions.
- `busy` disables activation and exposes aria-busy plus a decorative spinner; slot text supplies context. `disabled` uses native semantics. `aria-pressed` supports toggle feedback. `iconOnly` (or compatible `compact`) makes a square; provide an accessible name. Tooltip is separate.
- Colors use finite CSS token mappings, without inline styles so build-time native error rendering retains its CSP contract. Reduced motion preserves the static busy indicator.
- Used by MinimalLayout preferences and ApplicationLayout preferences, sidebar/drawer controls and profile trigger. Shell disclosures/navigation and modal backdrop remain specialized internal compositions.

## AppButtonGroup

- Source: `src/runa/resources/js/components/AppButtonGroup.vue`; public `ButtonGroup`. Required `label` names the group; slotted native buttons retain Tab navigation and caller-owned pressed state.
- Joined outer corners and overlapping borders create a single divider without gaps. Soft buttons now change to their semantic solid/foreground pair on hover.
- Action-list/split buttons and reusable avatars were removed at the owner's request; build these only when an actual application need appears. ApplicationLayout retains its unique initials profile control.

## AppFooter

- Source: `src/runa/resources/js/components/AppFooter.vue`.
- `poweredByLabel` defaults to English `Powered by` for standalone use. MinimalLayout passes the current translation (`Oparte na` in Polish), reusing its existing locale state.
- Public `Footer` consumes `useIdentity()`: `ApplicationName vX.Y.Z | Powered by Runa vA.B.C`; versions default to the independent application and platform manifests.
- `options: FooterOptions` independently controls visible/application/platform/copyright/repository; optional copyrightText/repositoryUrl/repositoryLabel customize content. URL validation rejects unsafe links. Copyright and GitHub remain default supplemental elements. Removed obsolete versionLabel prop.
- Layout footer slots can replace the footer; layout footer.visible hides the entire region. Canonical use: MinimalLayout.

## IdentityProvider

- Source: `src/runa/resources/js/components/AppIdentityProvider.vue`; public `IdentityProvider`, `useIdentity`, `defaultIdentity`, `resolveIdentity` from `@/runa/ui`.
- Reactive partial ApplicationIdentity overrides scope to descendants, with safe bundled defaults and independent application/platform versions. No global setup or shell dependency. Nested providers are independent scopes with bundled defaults, not implicit merges with their parents.
- Logo accepts same-origin absolute asset paths or credential-free HTTPS URLs. Missing/invalid URLs use the supplied Runa mark and application name; blank accessible names fall back to the application name. Failed image loading restores the mark; changed sources retry.

## AppBrandIllustration

- Source: `src/runa/resources/js/components/AppBrandIllustration.vue`. Decorative RunaLogo/grid/orbits; desktop retains the original composition, while narrow screens use a 240px panel and scaled artwork. The `RUNA` corner signature always identifies the platform and is never replaced by the business application name. MinimalLayout supplies it as the default illustration; consumers supply only content. The illustration slot can override it; `illustration: false` explicitly hides it.

## AppLoadingOverlay

- Source: `src/runa/resources/js/components/AppLoadingOverlay.vue`.
- Teleported opaque full-screen loading cover, centered circular spinner and accessible status label. Props: `active`, `label`; emits `covered` after enter and `revealed` after leave.
- Shared 340ms smooth opacity transitions and 700ms spinner cycle, disabled for reduced motion. Cover colors are captured at activation so theme updates do not abruptly recolor the visible overlay. The fully covered class must not override leave-transition opacity. Caller owns pending operation, inert/busy state and focus restoration; MinimalLayout provides the canonical language/theme flow.

## AppBackdrop

- Source: `src/runa/resources/js/components/AppBackdrop.vue`.
- Shared decorative CSS backdrop: large diffuse violet/rose and cyan/blue corner gradients with a quieter center, and a masked 88px technical grid at theme-specific opacity (light 9%, dark 4.5%). Page-background rings/arcs are removed. No raster texture, animation or external assets.
- Decorative, fixed, pointer-transparent and clipped within its own viewport. Place inside an isolated layout stacking context behind opaque reading surfaces. Canonical use: MinimalLayout. Follow the current background direction in `docs/runa/handbook/design/visual-direction.md`.

## RunaLogo

- Source: `src/runa/resources/js/components/RunaLogo.vue`.
- Shared decorative project mark based on the supplied transparent PNG alpha mask. Inherits `currentColor`; CSS mask crops transparent padding without modifying the original image. Default height 28px with the mark's natural aspect ratio; CSS may override height.
- Use in brand links and decorative artwork instead of the temporary Tabler mark. The owning link/control supplies its accessible name; the decorative mark is hidden from assistive technology. Source/provenance: `docs/runa/handbook/design/assets.md`.

## AppConstellation

- Source: `src/runa/resources/js/components/AppConstellation.vue`.
- Decorative deterministic SVG with thin lines and softly layered star points; `variant` selects one of three distinct branched patterns with 6–8 points. Desktop widths scale with the viewport (roughly 48–62vw with 500–1200px limits); mobile retains two cropped structures at 580/650px. Position them partly off-screen and behind the card, not as small isolated ornaments. Lines retain thin non-scaling strokes. No motion, randomness, image asset or dependency.
- Uses theme color tokens and is hidden from assistive technology. Canonical use: three peripheral details in AppBackdrop, reduced to two quieter details on narrow screens.

## ApplicationLayout and AppNavigation

- Sources: `src/runa/resources/js/layouts/ApplicationLayout.vue`, internal recursive `src/runa/resources/js/components/AppNavigation.vue`; public import ApplicationLayout. Slots/props and route/preference contracts are documented in `docs/runa/handbook/architecture/frontend.md` and page-layouts.md.
- AppNavigation reuses existing AppIcon/AppTooltip; supports link availability, URL-derived active state, whole-row disclosure buttons for groups and collapsed labels. Group labels do not navigate; leaf entries remain links. A centered CSS chevron lives inside the disclosure control, matching top-navigation convention. Right-position tooltip is a narrow shell requirement, not completion of the later feedback/layer group. All sidebar entries have a supplied or fallback Tabler icon.
- Header and sidebar share a static 44px CSS grid over their opaque surface, using 30% of the theme backdrop-grid opacity and secondary-text color; forced-colors removes the decorative texture.
- The initials profile trigger remains a dedicated 44px circular button; its shell selector takes precedence over the shared button corner style. ApplicationLayout preference buttons use sm (36px) with 18px icons. The sidebar toggle uses an 18×14px geometric three-bar mark centered in a 36px sm square, 24px from the header left edge, avoiding font-baseline glyph shifts. Header toggle and initials avatar expose visible hover/focus/pressed states through the semantic primary palette in both themes.
- Existing AppPreferenceControls and useDisplayPreferences are shared shell composition, preserving root timing/focus. MinimalLayout can supply staticPreferences for build-time native error rendering; the same controls/buttons/icons render GET forms in navigation mode without DOM/storage access. Do not create another copy when later finalizing buttons/feedback; update both consumers. AppNavigation/profile/drawer composition is finalized with the owning later component points, not a competing exported dropdown/modal system.

- Collapsed sidebar renders an icon-only list with 8px vertical padding; hidden headings/labels/submenus and their wrappers reserve no space. Saved expansion restores on reopening. It centers enabled/disabled icons identically, omits the supplementary sidebar-bottom slot and displays only the platform version in its footer. Top navigation has dotted edge cues only where more content is scrollable; cues fade over 160ms and respect reduced motion. Scroll/resize/content changes refresh the edge state.

- Shell motion: desktop sidebar width/workspace offset and phone drawer slide use 180ms ease-out; inline groups expand with a 160ms grid/opacity transition; top and initials cards fade with 4px travel over 140ms. Closing panels become inert immediately; reduced-motion disables transitions. Repeated toggles can reverse ongoing transitions.

## AppTopNavigation

- Source: `src/runa/resources/js/components/AppTopNavigation.vue`; internal application-shell navigation, not a generic dropdown component. Distinct from the sidebar's recursive AppNavigation.
- Horizontal group labels are disclosure buttons. Their dropdown card is teleported to body outside the scroll container, positioned under the trigger/header and clamped to the viewport. Header stays 64px when open; sidebar groups retain inline expansion. The panel reuses AppNavigation for its child links.
- Escape, outside click, route change, document scroll and resize dismiss it; nav scrolling updates its anchor position; focus returns on Escape. Tab boundaries connect the popup back to normal navigation order. General tooltip/popover floating behavior is implemented in point 9; route navigation retains its specialized anchoring and keyboard contract.

## Content containers and navigation

- Public `Card`, `Divider`, `Accordion`, `Tabs`, `Dropdown`, `Breadcrumbs`, `Pagination` map to `src/runa/resources/js/components/App*.vue`; types `TabItem`/`BreadcrumbItem` come from `@/runa/ui`. No shell, router or global installation is required.
- Card offers header/actions/default/footer slots; absent header/footer reserve no space. Card and Accordion headers use the lighter raised surface and semibold ordinary-text titles above darker neutral bodies (muted surface in light theme, ordinary surface in dark theme). The card footer matches the lighter header surface, with smaller secondary text and compact spacing. Divider is a semantic horizontal rule, or a decorative labeled section break.
- Accordion requires boolean v-model and label; optional disabled. Native Enter/Space toggles through update:modelValue; its hidden content stays mounted. Compose independent accordions for multiple expansion, or control their values for exclusive expansion.
- Tabs requires string v-model, unique stable item IDs and label. Each item has id/label/optional disabled; named slots match IDs. Horizontal arrows wrap across enabled tabs; Home/End move to endpoints and automatically activate. Inactive panels stay mounted and hidden. If the controlled selection disappears/is disabled, the first enabled item is presented; the owner should reconcile its model. Empty/all-disabled lists have no active panel.
- Dropdown is a controlled boolean v-model disclosure for arbitrary interactive content, with required localized label and optional disabled trigger. It composes the existing Popover and Button, with named nonmodal dialog semantics, viewport placement, outside/Escape dismissal and trigger focus return. Its default slot receives close. It is not an action-list/split-button API or a replacement for dedicated shell menus. Popover additionally supports optional controlled v-model while preserving its existing local default.
- Breadcrumbs requires label and readonly items (label/optional href). The final item uses aria-current=page; earlier safe same-origin absolute/public HTTPS URLs are anchors. Unsafe URLs render text. Optional navigate(href, MouseEvent) lets a resolver preventDefault while preserving modified-click/native navigation behavior. the isolated ApplicationScenario test fixture now uses this primitive in the shell breadcrumbs slot.
- Pagination requires numeric v-model/pages and localized label/previousLabel/nextLabel; optional disabled. It emits requested pages without fetching or owning data. It renders at most five numbered controls plus previous/next, with gaps; current page uses aria-current. Invalid/nonfinite bounds normalize to one page; out-of-range selection is clamped for display. Consumers own query/page resets and loading/disabled state.

## Loading and asynchronous content

- Public `Spinner`, `Skeleton`, `Progress`, `EmptyState`, `AsyncContent` map to `src/runa/resources/js/components/App*.vue`. `AsyncContentState` is loading/error/empty/success. No fetch/provider lifecycle is hidden in these primitives.
- Spinner requires localized label; sm/md/lg are 14/24/44px. Standalone uses role=status; decorative removes announcement for an already named parent. Button busy state and LoadingOverlay now compose this shared spinner, preserving overlay timing/focus/color capture. Reduced motion removes rotation.
- Skeleton is decorative/aria-hidden, with text (1–10 lines, default 3), block or circle geometry. Callers provide the loading status; reduced motion removes pulsing. Skeletons do not implement avatars or imply actual data progress.
- Progress requires localized label; value null/absent/nonfinite is indeterminate. Finite value is clamped to 0..max (positive finite max, otherwise 100). ARIA uses actual value/max units; visible percentage is derived only for determinate progress. Optional valueText supplies a localized meaningful equivalent. FeedbackTone selects neutral/success/info/warning/danger; reduced motion stops travel/transition while leaving the status visible. The caller owns completion/failure/cancellation.
- EmptyState requires title, optional description/icon; icon and actions slots support composition. It is static by default. AsyncContent announces its default empty state through role=status.
- AsyncContent requires controlled state and offers loading/error/empty/success slots, with default slot as success fallback. Defaults compose Spinner, Alert and EmptyState; all text is configurable (English standalone defaults; application callers supply PL/EN). Optional retryLabel exposes a retry button and retry event; custom error slot receives retry(). Retry never invents success or fetches data. Only the active state is mounted, so hoist form/draft state if it must survive switching; network cancellation and stale-response handling belong to the caller. aria-busy reflects loading; custom slots own their appropriate accessible status semantics.

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

Public facade exports DateInput, TimeInput, DateTimeInput, DateRangeInput, Checkbox, Switch, RadioGroup, FileInput, ColorInput, HiddenInput and TagsInput from their App-prefixed components. AppTemporalInput/AppBooleanInput are internal compositions; AppCalendarPicker, AppTimePicker and AppPickerSlider are internal picker controls. All fields reuse FormField and established theme/state styles; native checkbox/radio keyboard semantics remain intact. Checkbox supports boolean/null (indeterminate), Switch boolean, RadioGroup string/null with disabled options and isolated group names. Readonly blocks edits, disabled removes interaction. Custom label slots replace the default FormField label.

DateInput/TimeInput use canonical string/null values; DateRangeInput uses { start, end } date-only strings/null with chronological validation. DateTimeInput requires timeZone and stores an explicit-offset instant normalized to UTC on edit. Date/time/color use application-owned Popover panels and text inputs, never native date/time/datetime-local/color pickers. Date fields display DD.MM.YYYY in PL and YYYY-MM-DD in EN; moment fields add 24-hour time. Calendar supports month/year selection, Monday-first roving day navigation, Home/End and PageUp/Down (Shift for years). Time panels expose scrollable hour/minute lists with arrow/Home/End navigation, preserving explicit seconds/fractions from text. The inline color trigger is frameless, retaining its keyboard focus indicator; the field border and color chip remain. Color panels expose an HSV plane, three keyboard/pointer sliders and a palette, with six-digit hex text entry. Escape/outside/Done dismiss; focus returns on Escape/Done. See forms.md for draft and DST contracts. FileInput uses readonly File[] with accept/count/size UX validation and localized messages; it never uploads. Its application-styled dropzone is a real keyboard/touch picker button, with file-drag feedback, accepted types/limits, selected count/total size and metadata rows with extension icons, size and remove actions. Multiple selections/drops append batches; single selection replaces the previous value. Invalid/over-limit files remain visible for correction; readonly hides editing controls and disabled/busy fieldsets block drops. Removal restores focus to the next removal action or chooser. Optional locale formats sizes and dropzone/metadata copy; existing choose/remove/error labels remain caller-localized. ColorInput accepts null or #RRGGBB and retains invalid text drafts. HiddenInput is a native hidden serialization field, never a security boundary. TagsInput commits trimmed entries with Enter (outside IME), rejects duplicates/limits, clears drafts with Escape and supports removal; it exposes validity and focus().

## MaskedInput, Wizard and advanced form composables

- Sources: AppMaskedInput.vue, AppWizard.vue; useDependentFields.ts, useDirtyGuard.ts, useWizard.ts; lib/mask.ts. Public imports: MaskedInput, Wizard, formatMask, parseMaskedInput, useDependentFields, useDirtyGuard, useWizard and advancedForms types.
- MaskedInput reuses TextInput’s field/autofill/error states. Raw/null model, formatted and completeness events remain separate from domain validation; positional token masks preserve native editing/IME.
- Dependent fields expose keyed loading/error/ready/options plus retry/cancel over an acyclic multilevel graph; values clear synchronously and requests abort on invalidation. Dirty guards protect only opted-in navigation/unload, preserving drafts and requiring a caller-owned confirmation UI.
- Wizard composes Button/Progress, controlled steps and scoped content/feedback/actions slots. useWizard handles validation, backtracking, busy/retry and finish without owning values or a server draft. Full contracts and integration examples belong to forms.md.

## Dialog, AlertDialog and ConfirmDialog

- Sources: AppDialog.vue, AppAlertDialog.vue, AppConfirmDialog.vue; internal useModalLayer.ts, lib/layers.ts and lib/scrollLock.ts. Public DialogProps/ConfirmDialogProps/DialogSize/DialogCloseReason types accompany the component exports.
- All use controlled boolean v-model and a required visible title; description and error are optional. Sizes sm/md/lg/xl/full cap at 380/560/800/1120px/viewport respectively, always fitting narrow screens. Body scrolls independently; header/footer stay available. A title-only header centers the title against the close button; a header with description keeps top alignment. Content and footer slots compose existing controls.
- initialFocus accepts a selector in the panel; by default custom Dialog focuses its first content control or panel, ConfirmDialog focuses Cancel and AlertDialog focuses acknowledgment. Modal focus traps Tab/Shift+Tab, contains programmatic focus and restores the invoking control after close. Nested dialogs and their floating Select/Popover/Tooltip panels share dismissal ownership; one Escape dismisses only the newest layer.
- Escape defaults on, backdrop dismissal defaults on (closeOnOutside=false selects close-button-only pointer dismissal) and requires down/up on the backdrop. closable=false disables user dismissal; busy blocks closing and disables contained native fields. close emits close/escape/outside reason plus update:modelValue(false); parent-owned model changes remain authoritative. Prior inert/scroll state is restored on close/unmount, including dynamic body siblings and nested locks.
- ConfirmDialog supplies localized cancel/confirm buttons, configurable labels/tone and emits confirm while staying open. Caller owns busy, error, retry and success closure; never close before a failed operation can be recovered. Cancel and initial focus favor the nondestructive action. AlertDialog uses role=alertdialog and emits acknowledge before requesting close. locale defaults pl; pass en for English.
- The accepted application drawer remains specialized; it only reuses the scroll-lock primitive. No provisional dialog composition exists in either layout. The isolated advanced-form scenario uses ConfirmDialog without changing useDirtyGuard’s application-owned confirmation seam.

### Table

`Table` (`src/runa/resources/js/components/AppTable.vue`) and `useTable` provide client/server processing, controlled query and cross-page explicit/all-matching row selection, column render/filter/sort adapters, async/retry states and accessible horizontal scrolling. Public types and provider integration are documented in [tables.md](tables.md). `Checkbox` accepts `hideLabel` for an accessible visually hidden label in compact table cells.

### Nested semantic colors

Alert (including Toast/AsyncContent errors), Badge, Status, Progress and Statistic establish a reactive semantic tone context for descendants. An omitted child tone inherits the closest context; explicit tones override it and establish a new context. Buttons in a colored context default to that tone and the outline variant; explicit variants/tones remain authoritative. A neutral context restores neutral feedback and neutral button defaults. Standalone defaults are unchanged. This applies through wrapper components and slots without shell dependencies; use component props rather than descendant CSS overrides. Ordinary form input validation and dedicated shell navigation retain their own contracts.

### TablePreferences and useTableViews

`src/runa/resources/js/components/AppTablePreferences.vue` supplies a sm icon-only ghost settings button with a localized accessible name/Tooltip, matching export settings, plus the optional sm Available views selector (Default view first) and shared Dialog for column visibility, drag/keyboard order, a compact numeric width/px field with measured initial value and automatic reset; named views support create/select/update/rename/delete/default/reset. `useTableViews` owns scoped persistence, schema migration/reconciliation and controlled query snapshots. Import both through `@/runa/ui`; connect its columns to Table's optional columnState. See [tables.md](tables.md) for the public contract and example. No existing layout has provisional table settings to replace.

### Small field controls

Select, TextInput and NumberInput accept size=sm (36px) and size=md (44px, unchanged default). Select sizes its trigger, popup options/search/actions consistently; TextInput sizes suffixes as well. TextInput hideLabel retains an associated visually hidden label for compact inline controls. NumberInput forwards its declared size to the text control. Use sm with sm Buttons in tables, toolbars and secondary view/test settings. Keep ordinary data-entry forms, touch-first MinimalLayout preferences and dialog primary actions at their established size. ScenarioForms/ScenarioSelection test fixtures include standalone small examples; test application toolbar and secondary fixture selectors use sm. Other specialized inputs retain their existing form sizes until a concrete compact use needs them.

### TableActions and OperationPanel

TableActions composes sm icon export/print actions, a scope/orientation settings Popover and contextual bulk controls with the reusable OperationPanel and useOperation controller. Real bounded browser adapters and an optional API export provider are exported through @/runa/ui; no shell or backend jobs are installed. OperationPanel supports progress, permission errors, partial counts, retry/cancel and download. Table's toolbar slot exposes feedbackTarget; pass it to TableActions to keep operation feedback in the full-width outlet below aligned controls. See [tables.md](tables.md) for contracts, bounds, server authorization/idempotency and notices. Both existing layouts were inspected: neither has provisional export/bulk controls to replace; the test-only ScenarioTables fixture demonstrates both client and simulated server integrations.

## Independent consumer verification

Verify independent application consumers through src/app/tests: compose public forms, selections, Tabs, ConfirmDialog, Table and TableActions with caller-owned state and bounded fixtures. Import the shared CSS and initialize only the needed locale/theme; a shell, resolver, registry or preference bootstrap is optional. Use the real testing Nginx endpoint for application E2E. Platform fixture harnesses and regressions remain owner-only and are not installed customer dependencies. Test tools/output never enter production.

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

Extend presentation through existing slots/props first: for example Table's `cell-<key>`/`actions` and ApplicationLayout's toolbar/statistics slots. Keep authorization, business validation and writes in the owning feature; add a reusable prop only when multiple real consumers share its behavior. Keep application composition coverage under src/app/tests; test scenarios are test assets, never application demo routes.

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
and shows save/load/conflict feedback through existing Alert/Button components with an
explicit reload action; feedback is static and announced, with no added motion.
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

## LinkButton and Auth application composition

`LinkButton` maps to `AppLinkButton.vue`: a native anchor with required href and
existing button variants/tone, preserving open-in-new-tab and keyboard semantics.
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
src/runa/tests/frontend/fixtures/customization; they are excluded from delivery.

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
initials/name identity, divider, 44px left-aligned icon/label navigation/sign-out.
The public Icon catalogue includes logout. Panel użytkownika/User panel appears
only in the user dropdown; the default /app workspace has no placeholder content.

The default account view composes four existing Cards with independent Forms and
shared Alert/ToastViewport success feedback. AuthAvatarCropper exports a local,
bounded square crop through `@/runa/auth`; see [the account UI contract](../../modules/auth/account-ui.md)
and [public customization](customization.md). The account card hosts it in the
shared Dialog with explicit Apply/Cancel. The canvas displays a circular bright
frame and dimmed surrounding image; direct mouse/touch dragging, wheel/pinch
zoom and keyboard alternatives replace sliders. Mask pixels never enter export.
AppPickerSlider remains the internal color-picker control, with optional min=0;
disabled fieldsets/inert ancestors block edits.

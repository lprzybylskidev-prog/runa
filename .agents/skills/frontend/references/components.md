# Component Catalogue

Search this catalogue and the source before introducing a new control. Keep entries limited to implemented components. Public imports use `@/runa/ui`: Button, Tooltip, Icon, Form, Select, LoadingOverlay and Logo map to their existing App-prefixed/RunaLogo implementations. No layout/router installation is required; load the application theme CSS in the consuming entry. IdentityProvider, MinimalLayout and Footer are also public; navigation and preferences now have resolver/storage implementations; export types remain contracts without implemented engines.

## AppSelect

- Source: `resources/js/components/AppSelect.vue`.
- Use for the current small, native selection controls. It preserves native keyboard behavior and wraps the select in a visible label.
- Props: `label`, string `modelValue`, readonly `options` with string `value`/`label`.
- Emits: `update:modelValue`; callers validate their narrower domain union.
- Minimum 44px control height, 4px corners and subtle border transitions; shared palette input, border and focus tokens.
- Available for genuine selection controls; root language/theme preferences now use explicit buttons.

## AppForm

- Source: `resources/js/components/AppForm.vue`.
- Prefer for controlled application forms: always `novalidate`, prevents native navigation, emits the submit event only when not busy and exposes `aria-busy`.
- Props: optional `busy`; emits `submit: SubmitEvent`; default slot contains fields/actions.
- Field validation remains the owning feature's responsibility. No business field/error framework exists yet.
- Retained as the controlled form wrapper; the root preference buttons do not require a form.

## AppIcon

- Source: `resources/js/components/AppIcon.vue`.
- Typed subset of four Tabler outline icons; decorative SVGs are hidden from assistive technology. Put an accessible name on the owning control when it is icon-only.
- Local source assets, licenses and revision/hash provenance: `docs/design/assets.md`.

## Root preference buttons

- Sources: `resources/js/components/AppPreferenceControls.vue`, `resources/js/composables/useDisplayPreferences.ts`, consumed by both web layouts.
- Exactly two 44px buttons: language cycles PL/EN, theme cycles system/light/dark with corresponding icons. Accessible names describe current/next values; short application tooltips identify the action. Both controls use AppButton. Existing composables preserve preferences. No select or submit step.

## AppTooltip

- Source: `resources/js/components/AppTooltip.vue`.
- Application-styled tooltip on pointer hover or keyboard focus, dismissible with Escape. Shared 140ms ease-out opacity enter/leave transition; disabled for reduced-motion preferences. Pointer can enter the tooltip; it stays visible until pointer leave, focus leave or dismissal.
- Props: `text`, optional placement (bottom default/right for collapsed sidebar); right placement teleports to the viewport to escape sidebar scroll clipping and hides on scroll. Existing hover-dismissal defect remains deferred to point 7. Scoped slot supplies `descriptionId` for the owning control's `aria-describedby`. Keep an accessible name on the control.

## AppButton

- Source: `resources/js/components/AppButton.vue`.
- Shared native button with slot content and scoped appearance: borders, 4px radii, hover/active/focus/disabled states and subtle transitions respecting reduced motion.
- Props: `type` (defaults to safe `button`), `compact` (44px square), `disabled`. Native attributes, ARIA labels and click listeners inherit onto the button.
- Use this component for actions rather than styling native buttons independently in each view. Extend it only for demonstrated new variants; do not introduce speculative component APIs.
- Canonical use: shared language/theme controls in both web layouts. Icon-only uses require an accessible name; tooltip remains a separate component.

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
- AppNavigation reuses existing AppIcon/AppTooltip; supports link availability, URL-derived active state, nested disclosure groups and collapsed labels. Right-position tooltip is a narrow shell requirement, not completion of the later feedback/layer group. All sidebar entries have a supplied or fallback Tabler icon.
- Existing AppPreferenceControls and useDisplayPreferences are shared shell composition, preserving root timing/focus. Do not create another copy when later finalizing buttons/feedback; update both consumers. AppNavigation/profile/drawer composition is finalized with the owning later component points, not a competing exported dropdown/modal system.

## AppTopNavigation

- Source: `resources/js/components/AppTopNavigation.vue`; internal application-shell navigation, not a generic dropdown component. Distinct from the sidebar's recursive AppNavigation.
- Horizontal group labels are disclosure buttons. Their dropdown card is teleported to body outside the scroll container, positioned under the trigger/header and clamped to the viewport. Header stays 64px when open; sidebar groups retain inline expansion. The panel reuses AppNavigation for its child links.
- Escape, outside click, route change, document scroll and resize dismiss it; nav scrolling updates its anchor position; focus returns on Escape. Tab boundaries connect the popup back to normal navigation order. Final generic floating layers remain the owning later component points.

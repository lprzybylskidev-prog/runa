# Component Catalogue

Search this catalogue and the source before introducing a new control. Keep entries limited to implemented components.

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

- Source: `resources/js/layouts/AuthLayout.vue`.
- Exactly two 44px buttons: language cycles PL/EN, theme cycles system/light/dark with corresponding icons. Accessible names describe current/next values; short application tooltips identify the action. Both controls use AppButton. Existing composables preserve preferences. No select or submit step.

## AppTooltip

- Source: `resources/js/components/AppTooltip.vue`.
- Application-styled tooltip on pointer hover or keyboard focus, dismissible with Escape. Shared 140ms ease-out opacity enter/leave transition; disabled for reduced-motion preferences. Pointer can enter the tooltip; it stays visible until pointer leave, focus leave or dismissal.
- Props: `text`; scoped slot supplies `descriptionId` for the owning control's `aria-describedby`. Keep an accessible name on the control.

## AppButton

- Source: `resources/js/components/AppButton.vue`.
- Shared native button with slot content and scoped appearance: borders, 4px radii, hover/active/focus/disabled states and subtle transitions respecting reduced motion.
- Props: `type` (defaults to safe `button`), `compact` (44px square), `disabled`. Native attributes, ARIA labels and click listeners inherit onto the button.
- Use this component for actions rather than styling native buttons independently in each view. Extend it only for demonstrated new variants; do not introduce speculative component APIs.
- Canonical use: language/theme controls in AuthLayout. Icon-only uses require an accessible name; tooltip remains a separate component.

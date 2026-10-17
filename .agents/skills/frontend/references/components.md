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

- Source: `resources/js/App.vue`.
- Exactly two 44px buttons: language cycles PL/EN, theme cycles system/light/dark with corresponding icons. Accessible names and application tooltips describe current and next values. Existing composables preserve preferences. No select or submit step.

## AppTooltip

- Source: `resources/js/components/AppTooltip.vue`.
- Application-styled tooltip on pointer hover or keyboard focus, dismissible with Escape. Pointer can enter the tooltip; it stays visible until pointer leave, focus leave or dismissal.
- Props: `text`; scoped slot supplies `descriptionId` for the owning control's `aria-describedby`. Keep an accessible name on the control.

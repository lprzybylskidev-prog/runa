# Component Catalogue

Search this catalogue and the source before introducing a new control. Keep entries limited to implemented components.

## AppSelect

- Source: `resources/js/components/AppSelect.vue`.
- Use for the current small, native selection controls. It preserves native keyboard behavior and wraps the select in a visible label.
- Props: `label`, string `modelValue`, readonly `options` with string `value`/`label`.
- Emits: `update:modelValue`; callers validate their narrower domain union.
- Minimum 44px control height; shared theme, border and focus tokens.
- Canonical use: locale/theme preferences in `resources/js/App.vue`.

## AppForm

- Source: `resources/js/components/AppForm.vue`.
- Prefer for controlled application forms: always `novalidate`, prevents native navigation, emits the submit event only when not busy and exposes `aria-busy`.
- Props: optional `busy`; emits `submit: SubmitEvent`; default slot contains fields/actions.
- Field validation remains the owning feature's responsibility. No business field/error framework exists yet.
- Canonical use: display preferences in `resources/js/App.vue`, which persist on selection changes and require no submit button.

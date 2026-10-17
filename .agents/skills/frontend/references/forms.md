# Form Patterns

The implemented controlled wrapper is `resources/js/components/AppForm.vue`, used by the preference form in `resources/js/App.vue`. It disables native validation/navigation and guards duplicate submission through `busy`. Select controls use `AppSelect.vue` with visible labels, native keyboard semantics and caller-side enum validation.

There are no business fields, field-error components or business submit flows yet. Add their canonical paths only when implemented. Known invalid values must be rejected immediately by the owning frontend feature, with accessible field errors; backend validation remains authoritative. Preserve recoverable input after 409/422/server failures and provide a deliberate recovery path for conflicts.

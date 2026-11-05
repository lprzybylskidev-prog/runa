# Forms reference

Use for field/value contracts, feedback, dirty drafts and read reconciliation.

The authoritative delivered contract is [the public forms guide](../../../../docs/runa/handbook/development/frontend/forms.md).
For screen/controller replacement read [frontend adoption](../../../../docs/runa/handbook/development/frontend/customization.md);
for exact module behavior follow its linked public guide. Maintain contracts and
examples there, with application-specific patterns in local App guidance.
Customer composition never authorizes protected platform edits or copied security logic.

For concise shared labels/layout, consolidated facts and omission of redundant copy,
follow `docs/runa/handbook/development/frontend/forms.md#concise-form-composition`;
apply capability visibility to dependent controls as well as whole forms.

For visible mutation/read feedback and the local Form/submit-Button busy contract,
follow `docs/runa/handbook/development/frontend/forms.md#visible-operation-progress`.
Generic scope or loading copy never substitutes for shared progress presentation.

For actionable failures, follow the public forms guide's
`Values, dirty baselines and request failures` and `Automatic read reconciliation`.
Keep stable business reasons distinct from stale revisions across adapters and
aggregate writes; use reviewed localized copy and affected fields. A generic
fallback is for unknown failures, never a substitute for a known refusal.
Compose Auth secondary addresses through
`docs/runa/handbook/modules/auth/account-ui.md#shared-secondary-email-presentation`;
the shared fields retain controller-owned grants, proof and reconciliation.

For module settings editors, use the public named-snapshot composition in
`docs/runa/handbook/modules/admin/settings-snapshots.md`. Keep Audit in its own
viewer; loading a snapshot edits a draft and applying it uses the module's native
publication. Use the shared AdminSettingsPage/controller rather than copied proof logic.

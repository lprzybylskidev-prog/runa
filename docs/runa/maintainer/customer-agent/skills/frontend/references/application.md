# Application frontend guidance

Inspect analogous App pages and the public component catalogue before new markup. Extract repeated application controls into src/app/resources/js/components and maintain local patterns. Consume public facades without protected edits. Rendered behavior is acceptance truth: keyboard/focus, responsive, PL/EN, themes, reduced motion and full loading/error/dirty states.

Select only the relevant public guide/section; the
`docs/runa/handbook/design/visual-direction.md#ui-standards-and-guidance-map`
owns the topic map and accepted refinements. A copy-only task stops at interface
copy and its owning translations. Expand to the matching topics for behavior.

| Topic                                    | Canonical guide (under docs/runa/handbook)                                |
| ---------------------------------------- | ------------------------------------------------------------------------- |
| Visual identity, copy and density        | design/visual-direction.md; design/color-palette.md                       |
| Accessibility/keyboard/responsive review | architecture/frontend.md#accessibility-baseline                           |
| Controls and action semantics            | development/frontend/components.md; select the component/intent section   |
| Page/navigation composition              | development/frontend/page-layouts.md#page-level-navigation-composition    |
| Validation/outcomes                      | development/frontend/forms.md#values-dirty-baselines-and-request-failures |
| Dirty drafts/refresh                     | development/frontend/forms.md#automatic-read-reconciliation               |
| Temporal fields/timezone                 | development/frontend/forms.md#dates-times-and-application-timezone        |
| Data views and provider capabilities     | development/frontend/tables.md                                            |
| Loading/async settlement                 | development/frontend/loading.md                                           |
| Motion/reduced motion                    | development/frontend/motion.md                                            |
| Public replacement/session/capabilities  | development/frontend/customization.md                                     |
| Measured request/query performance       | architecture/application-architecture.md#read-path-performance            |
| Account default/replacement composition  | modules/auth/account-ui.md                                                |
| Admin delivery and examples              | modules/admin/frontend.md; modules/admin/examples.md                      |

The module-specific routes below apply only to the corresponding task. Their
presence does not require loading every module guide for a shared copy edit.

Maintain application-specific patterns in local guidance/docs/app. Ordinary App
code and optional app modules are supported; copied owner files or a Contracts
namespace never authorize platform/internal APIs. Preserve installed local
extensions during reviewed updates. This default contains no owner-only procedure.

For password proof before entry or a sensitive operation, use the public Auth
`docs/runa/handbook/modules/auth/entry-ui.md#operation-proof-composition`
with its shared full-page default. Reuse the caller's session and
proof controller; presentation replacements do not duplicate credential requests.

Admin follows the shared application language preference (Polish default, English
secondary), including module defaults and Auth entry proof. For localized metadata
and App presentation catalogues, follow `docs/runa/handbook/modules/admin/frontend.md#polish-and-english-presentation`;
preserve mounted controllers and dirty drafts when the language changes.

For initial reveal, background reads and shared prepared avatars, follow
`docs/runa/handbook/development/frontend/loading.md`. Declare explicit regions,
retain mounted drafts/focus and preserve the owning controller's security fences.

For area/module sidebar versus subview top-bar placement and nested PageNavigation
or custom-layout usePageNavigationHost, follow
`docs/runa/handbook/development/frontend/page-layouts.md#page-level-navigation-composition`.
Preserve the owning controller's guards and history; keep operation controls in content.

When a supplied module table defect is reported, assess Auth/Permissions/Audit
for the same issue and consume the supported public correction without editing
protected Runa. See the Admin frontend and public table guides for shared dialog,
refresh/filter/reset and separately authorized export/print behavior. Apply the
public table capability contract to application-owned consumers, including bounded
withTableFilters composition and actual server sort/filter support.

For renewable password-protected sections, use public `useProtectedActivity`
and guarded AuthStepUp section APIs from
`docs/runa/handbook/modules/auth/security.md#protected-section-idle-access`.
Preserve controller scope, admitted identity and protected roots/portals when
replacing presentation; automatic reads never count as user activity.

For application-owned Auth forms, compose the public identity and preference
fields documented in `docs/runa/handbook/modules/auth/account-ui.md#shared-identity-and-preference-fields`.
Keep draft reconciliation and guarded writes with the composing controller;
shared presentation controls do not grant access or authorize protected edits.

For concise shared labels/layout, consolidated facts and omission of redundant copy,
follow `docs/runa/handbook/development/frontend/forms.md#concise-form-composition`;
apply capability visibility to dependent controls as well as whole forms.

For readonly boolean facts reuse public BooleanBadge from the component catalogue;
Admin assignment composition may use public PermissionsAssignments from
`docs/runa/handbook/modules/permissions/README.md#searchable-assignment-catalogue`.
Keep native guarded review/proof with the supplied controller.

For visible mutation/read feedback and the local Form/submit-Button busy contract,
follow `docs/runa/handbook/development/frontend/forms.md#visible-operation-progress`.
Generic scope or loading copy never substitutes for shared progress presentation.

For actionable failures, follow the public forms guide's
`Values, dirty baselines and request failures` and `Automatic read reconciliation`,
including its action-driven triggers, original submission tokens and separation
of security checks from business-data reads.
Keep stable business reasons distinct from stale revisions across adapters and
aggregate writes; use reviewed localized copy and affected fields. A generic
fallback is for unknown failures, never a substitute for a known refusal.
Compose Auth secondary addresses through
`docs/runa/handbook/modules/auth/account-ui.md#shared-secondary-email-presentation`;
the shared fields retain controller-owned grants, proof and reconciliation.

For high-cardinality domain controls, follow
`docs/runa/handbook/development/frontend/forms.md#specialized-collection-editors`.
Prefer the owning dedicated editor while preserving shared chrome, bounded remote
search, off-page selections and native security orchestration. Reuse does not mean
stacking unrelated generic inputs or duplicating the selected catalogue.

Custom native controls require rendered pointer/hover/pressed/focus and disabled
checks, including checkbox hit areas; composing shared paint alone does not supply
interaction states. The Permissions catalogue guide documents the supplied default.

For module settings presentation, compose the public named-snapshot editor and
controller described in `docs/runa/handbook/modules/admin/settings-snapshots.md`.
Loading changes only a draft; publication remains with its owning module and Audit
has a separate viewer. Application composition never authorizes platform edits.

Setting metadata retains the stable technical key and separate human names and
explanations in Polish/English. Preserve the owning descriptions through declared
Admin fields; use the public settings help/external-label contract in the linked
snapshot guide rather than deriving labels or maintaining a second text registry.

For action colors and hierarchy follow
`docs/runa/handbook/development/frontend/components.md#action-button-semantics`.
Use the public explicit intent/confirmation contracts for application controls;
feedback context and password requirements never determine action meaning.

The canonical action policy requires quiet outlines, effect colors even before a
modal opens, and purple mixed-effect tools with a concrete semantic final action.
Blue denotes reads/refresh/preview; never use solid action fills in supplied views.

For Permissions presentation composition and selected-user copy/preset tools use
`docs/runa/handbook/modules/permissions/administration.md`. Keep definition management
separate from account access, and preserve the native reviewed source fingerprints.

Custom Admin pages using owning HTTP compose public `useAdminPageContext` for the
native admitted identity and exit guard. Follow the custom-page lifecycle contract
in `docs/runa/handbook/modules/admin/frontend.md`; the optional reference workspace
is a working consumer. Reuse the single shell and Auth confirmation host. Connect
the exposed exit check to standalone routing; never import private platform state.

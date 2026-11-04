# Application frontend guidance

Inspect analogous App pages and the public component catalogue before new markup. Extract repeated application controls into src/app/resources/js/components and maintain local patterns. Consume public facades without protected edits. Rendered behavior is acceptance truth: keyboard/focus, responsive, PL/EN, themes, reduced motion and full loading/error/dirty states.

Load only the relevant canonical procedure:

- Visual direction and selected references: `docs/runa/handbook/design/visual-direction.md`.
- Components and contracts: `docs/runa/handbook/development/frontend/components.md`.
- Page structures: `docs/runa/handbook/development/frontend/page-layouts.md`.
- Fields, feedback and automatic read reconciliation: `docs/runa/handbook/development/frontend/forms.md`.
- Measured read/request growth and review coverage: `docs/runa/handbook/architecture/application-architecture.md#read-path-performance`.
- Table providers/selection/loading/export: `docs/runa/handbook/development/frontend/tables.md`.
- Motion: `docs/runa/handbook/development/frontend/motion.md`.
- Presentation replacement and controller lifetime: `docs/runa/handbook/development/frontend/customization.md`.
- Account screens/security cleanup: `docs/runa/handbook/modules/auth/account-ui.md`.
- Admin delivery status and catalogue versus access: `docs/runa/handbook/modules/admin/README.md#delivery-status-and-acceptance-ownership`.

Maintain application-specific patterns in local guidance/docs/app. Ordinary App
code and optional app modules are supported; copied owner files or a Contracts
namespace never authorize platform/internal APIs. Preserve installed local
extensions during reviewed updates. This default contains no owner-only procedure.

For password/2FA proof before entry or a sensitive operation, use the public Auth
`docs/runa/handbook/modules/auth/entry-ui.md#operation-proof-composition`
with its inline or MinimalLayout page default. Reuse the caller's session and
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
refresh/filter/reset and separately authorized export/print behavior.

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

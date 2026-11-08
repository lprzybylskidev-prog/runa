# Components reference

Use for controls, exported props/events/handles and component reuse.

The authoritative delivered contract is [the public components guide](../../../../docs/runa/handbook/development/frontend/components.md).
For screen/controller replacement read [frontend adoption](../../../../docs/runa/handbook/development/frontend/customization.md);
for exact module behavior follow its linked public guide. Maintain contracts and
examples there, with application-specific patterns in local App guidance.
Customer composition never authorizes protected platform edits or copied security logic.

For nested subview links, use public PageNavigation and the UI-owned host contract
in `docs/runa/handbook/development/frontend/page-layouts.md#page-level-navigation-composition`;
reuse the shell renderer rather than adding a content tab/dropdown implementation.

For renewable password-protected sections, use public `useProtectedActivity`
and guarded AuthStepUp section APIs from
`docs/runa/handbook/modules/auth/security.md#protected-section-idle-access`.
Preserve controller scope, admitted identity and protected roots/portals when
replacing presentation; automatic reads never count as user activity.

For horizontal viewports follow the public components guide's
[horizontal scrolling contract](../../../../docs/runa/handbook/development/frontend/components.md#horizontal-scrolling):
reuse consistent wheel handling and directional dotted overflow cues, including modal tabs.

For Auth identity and preference composition, use the existing public
[shared fields](../../../../docs/runa/handbook/modules/auth/account-ui.md#shared-identity-and-preference-fields)
consumed by creation, user administration and own-account views. Their pure
presentation contracts retain the composing controller's draft/security ownership.

Readonly boolean facts and Admin cells reuse public BooleanBadge from the component
catalogue. Searchable role/direct-permission assignment composition uses the public
PermissionsAssignments contract in `docs/runa/handbook/modules/permissions/README.md#searchable-assignment-catalogue`.

Auth secondary-address lists/editors reuse the public `AuthSecondaryEmailFields`
contract in `docs/runa/handbook/modules/auth/account-ui.md#shared-secondary-email-presentation`.
Keep owning confirmation/security behavior in the composing controller.

For high-cardinality domain controls, follow
`docs/runa/handbook/development/frontend/forms.md#specialized-collection-editors`.
Prefer the owning dedicated editor while preserving shared chrome, bounded remote
search, off-page selections and native security orchestration. Reuse does not mean
stacking unrelated generic inputs or duplicating the selected catalogue.

When composing custom native controls, verify computed pointer/hover/pressed/focus
and disabled behavior in the rendered build, including checkbox hit areas. Follow
`docs/runa/handbook/modules/permissions/README.md#searchable-assignment-catalogue`
for the supplied access editor's interaction states.

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

For selected-account review composition reuse public `AuthAdminBulkAction` through
`@/runa/auth`; its typed composition contract is in
`docs/runa/handbook/modules/auth/administration.md`. The native owning module retains
selection admission, reviewed writes, password proof and transactional Audit.

For action/module/feedback iconography, follow the public components guide's
Semantic icons contract. Reuse the typed Tabler catalogue and actionIcons;
keep action color, icon identity and proof independent. Known domain tools use
source-owned semantic family/command mappings across single and bulk contexts;
reserve the generic action glyph for operations without an authored meaning. Preserve visible action
labels, busy geometry and accessible icon-only names. Module authors declare
frontend/icon.ts in their owned module; never edit protected presentation maps.

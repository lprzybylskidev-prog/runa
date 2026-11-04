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

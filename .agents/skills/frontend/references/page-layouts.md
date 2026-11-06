# Page Layouts reference

Working Admin partial/full consumers and the authoring map live in
`docs/runa/handbook/modules/admin/examples.md`. Keep examples out of the minimal
application scaffold and compose public controllers rather than copying internals.

Use for shell/page composition, welcome, account and native-error defaults.

The authoritative delivered contract is [the public page-layouts guide](../../../../docs/runa/handbook/development/frontend/page-layouts.md).
For screen/controller replacement read [frontend adoption](../../../../docs/runa/handbook/development/frontend/customization.md);
for exact module behavior follow its linked public guide. Maintain contracts and
examples there, with application-specific patterns in local App guidance.
Customer composition never authorizes protected platform edits or copied security logic.
Welcome catalogue entries are source metadata, not installation or Admin access;
follow the public guide's Admin delivery-status reference before adding navigation.

For administrative Auth pages, follow `docs/runa/handbook/modules/auth/administration.md`
for public default composition and exact owning grants/proofs/revisions. Reuse
Admin's section dispatch/failure boundaries and shared subject controller; clear
independent read lanes when changing subjects. App replacements remain App-owned.

For Permissions/Audit administrative defaults, follow
`docs/runa/handbook/modules/permissions/administration.md` and
`docs/runa/handbook/modules/audit/administration.md`. Their public facades and
AdminRecordBrowser compose owning guarded workflows; App presentation replacements
retain the shared session, dirty guards and native proof/review protocols.

For operational dashboard/editor replacements, follow
`docs/runa/handbook/modules/admin/frontend.md#operational-dashboard-and-replacements`
and the owning fixed operation catalogue. Keep the shared controller's session,
dirty-draft, explicit retry and native pending/recovery semantics in App composition.

For application settings and runtime branding composition, follow
`docs/runa/handbook/modules/admin/frontend.md#application-global-settings-and-runtime-branding`
and its owning settings contract. Use public App registration/adapters and shared
controllers; preserve canonical identity and technical/source policy ownership.

For password confirmation before protected entry or a sensitive operation, use the public Auth
[operation-proof composition](../../../../docs/runa/handbook/modules/auth/entry-ui.md#operation-proof-composition)
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

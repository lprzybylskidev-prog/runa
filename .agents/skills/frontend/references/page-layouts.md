# Page Layouts reference

The authoritative delivered contract is [the public page-layouts guide](../../../../docs/runa/handbook/development/frontend/page-layouts.md). Load it only for the current task. Maintain platform capability contracts there and owner-specific implementation decisions in the active owner roadmap. Customer application patterns belong in their local App guidance; extracting application controls never authorizes protected-source edits.

For delivered login and restricted-session screens, use the [Auth entry contract](../../../../docs/runa/handbook/modules/auth/entry-ui.md) and its public `@/runa/auth` composition. Preserve server-driven restrictions, explicit token confirmation and flow cleanup.

Use the [Auth application/account contract](../../../../docs/runa/handbook/modules/auth/account-ui.md) for welcome policy, post-security
navigation, shared-shell profile/sign-out, real-activity expiry and safe memory draft
review. Preserve application-owned composition and server guards; never replay a
write after login or persist credentials/drafts in browser preferences.

All Runa views, including future module views, follow [frontend adoption and
replacement](../../../../docs/runa/handbook/development/frontend/customization.md).
Default screens, composition and full customer presentation are supported levels.
Maintain public seams and account/security cleanup; application examples stay in
owner fixtures rather than enlarging the delivered scaffold. Native errors and
emergency output share one static build-time presentation contract.

Use the single workspace-root .runtime tree for private generated build/tooling
state; see [artifact placement](../../../../docs/runa/handbook/architecture/project-principles.md#generated-tooling-and-build-state). Resolve paths consistently
from src and build stages. Keep only required generated runtime assets in explicit
production copies; preserve unrelated caches/reports/backups/operation state.

Before handing over a frontend for manual review, prepare and inspect the actual
served development environment, current assets, mandatory Core data and private
fixture-account setup through [manual preparation](../../../../docs/runa/handbook/data/seeding.md#manual-auth-navigation-scenario).
Code/fixture coverage does not establish live readiness or owner appearance acceptance.

Place public welcome sign-in/conditional registration and authenticated entry
beside language/theme controls using MinimalLayout's public header-actions slot.
Forward this slot in custom layout wrappers; preserve the default identity and
preferences rather than copying their implementation into pages. Keep narrow
headers wrapping without overflow.

Welcome header authentication/application entry uses quiet text links beside
preferences, without button surfaces/borders. Preserve muted default text,
underline/color on hover, visible keyboard focus and 44px touch targets.

Keep the supplied /app workspace empty; customer content is explicit composition.
Self-service is Panel użytkownika/User panel, exposed through the dedicated user
dropdown rather than sidebar or placeholder welcome content. Keep menu identity
and icon/action rows consistent with the shell; do not extract shell menus into
universal content primitives.

For related settings, use the visible card-editor pattern in
[public form guidance](../../../../docs/runa/handbook/development/frontend/forms.md#related-settings-in-one-editor).
Use shared Card and feedback components; keep ordinary fields directly editable
and independent operations in separate forms with their own outlined submits.
Preserve explicit security steps and scoped credential drafts/validation/cleanup;
use public account orchestration and AuthAvatarCropper rather than private copies.
Forward ApplicationLayout's width prop in wrappers; the supplied account shell is fluid.

Host avatar adjustment in the shared Dialog with explicit local Apply/Cancel.
Use a dimmed surrounding image and bright circular frame, direct pointer panning,
wheel/pinch zoom and keyboard alternatives; do not add redundant position sliders.
Applying a crop prepares the card preview; the card's Save owns the upload.

Account Preferences stacks its Cards at every width, with a header full-reset
confirmation, explicit language/theme
options sharing the existing preference cover, and independent inline email rows
with Switch/recipient Select/Save/Revert. Preserve other dirty rows; confirm the
atomic presentation-and-delivery reset. Shell controls still cycle compactly.
Use the public account UI/preferences guides for revision and recovery behavior.

Account defaults use concise helper copy and a centered avatar. Device management
appears only for a nonempty guarded active trusted-device list; refresh it through
the public controller and keep independent cursor/busy state. See the account UI
guide; do not infer authority or historical trust from button visibility.

Only MinimalLayout opts into sparse constellation-point glow and occasional
brand-illustration point laps. Preserve the static
ApplicationLayout backdrop and the public opt-out/reduced-motion contract; see the
public page-layouts and motion guides.

For every new Runa module, complete its welcome icon and localized manifest metadata
and inspect the default catalogue under the [module development guide](../../../../docs/runa/handbook/development/modules.md#welcome-catalogue).
Customer welcome replacements remain application-owned.

Account-history event details follow the public
[Auth history contract](../../../../docs/runa/handbook/modules/auth/history.md).
Use the default or public presentation seam, not a generic Audit payload viewer.

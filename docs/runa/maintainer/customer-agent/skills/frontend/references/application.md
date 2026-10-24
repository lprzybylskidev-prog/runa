# Application frontend guidance

Read docs/runa/handbook/architecture/frontend.md and relevant design/visual-direction.md and design/color-palette.md. Search existing application components and the public facade src/runa/resources/js/runa/ui/index.ts before creating controls. Import exported controls through @/runa/ui; read protected source for contracts without editing it. Extract repeated application controls into src/app/resources/js/components and maintain the local catalogue here. Never extract into src/runa or change its facade. Unsupported public capabilities require composition or a platform limitation report.

Compose MinimalLayout for compact public screens or ApplicationLayout with named exact application entry routes. Keep shell menus dedicated. Use heading/actions, breadcrumbs, optional statistics/filters, content and supplementary areas consistently. Public Form/FormField/inputs/useForm own presentation; feature validators own rules. Form disables native validation; preserve drafts on 422/409 and offer deliberate reconciliation. NumberInput uses exact decimal strings; temporal fields preserve incomplete drafts and require timezone/DST decisions; FileInput values are separate from finite JSON useForm data. Providers own HTTP, server authorization and bounded pagination.

Tables use stable public_id keys, typed providers and validated envelopes. Never infer authorization from hidden columns/actions. Shared loading/empty/error/retry, dialog focus return and keyboard behavior must remain usable on mobile/tablet/desktop, light/dark and PL/EN. Admin UI is English. Use semantic labels, visible focus, associated errors, zoom-safe content and reduced-motion behavior. Exiting content becomes inert immediately; preserve focus/drafts during interrupted transitions. Do not add duplicate page entrances or animate dense data sorting. Rendered behavior is acceptance truth. Add application patterns here when they become reusable.

Detailed public contracts and examples live in docs/runa/handbook/development/frontend/components.md, forms.md, tables.md, motion.md and page-layouts.md. Load only the relevant guide; keep application-specific additions in local App guidance. These public guides are delivered independently of the owner skill reference layout.

Use docs/runa/handbook/modules/auth/preferences.md for the delivered AuthPreferences/
AuthDelivery contracts. Declare exact source-owned preference schemas and notification
categories, use optimistic revisions, and keep account caches/requests isolated on
logout or switch. Legacy guest adoption requires explicit ownership confirmation;
server values win and reset never resurrects legacy state. Do not put credentials,
drafts or permission state in preferences, disable mandatory primary security mail,
cache recipients across jobs, or import Auth private SQL/models. Reference-only
managed security mail rechecks state and uses a persisted single SMTP attempt;
critical recovery retains direct mail. Application code consumes these contracts
without requiring conversion into an app module; protected Runa remains read-only.

Use the delivered AuthEntry component from @/runa/auth for entry and restricted-session flows; follow docs/runa/handbook/modules/auth/entry-ui.md. Preserve the application-owned App.vue integration when customizing navigation. Keep token proofs and credentials in component memory, honor server-declared restrictions and require explicit resubmission after CSRF/conflict failures; never edit protected Auth source.

Use the [Auth application/account contract](../../../../../handbook/modules/auth/account-ui.md) for welcome policy, post-security
navigation, shared-shell profile/sign-out, real-activity expiry and safe memory draft
review. Preserve application-owned composition and server guards; never replay a
write after login or persist credentials/drafts in browser preferences.

For backend authority behind these screens, read
`docs/runa/handbook/modules/auth/README.md`,
`docs/runa/handbook/modules/auth/transport.md`,
`docs/runa/handbook/modules/auth/sessions.md`,
`docs/runa/handbook/modules/auth/security.md` and
`docs/runa/handbook/modules/auth/profile.md`. UI visibility never grants access.
Use `docs/runa/handbook/modules/auth/policies-and-operators.md` and
`docs/runa/handbook/modules/permissions/README.md` with
`docs/runa/handbook/modules/permissions/authority.md` for actual current grants,
step-up and last-superadministrator constraints. Local manual scenarios and private
setup follow `docs/runa/handbook/data/seeding.md#explicit-module-development-contributors`;
no distributed password or owner test harness is required by customer workflows.

For all current/future Runa module views use
docs/runa/handbook/development/frontend/customization.md. Runa supplies usable
defaults plus documented public presentation replacement. Customer screens,
wrappers and CSS belong in src/app and survive reviewed updates/clones. Use typed
public facades/controllers, preserve identity/page cleanup, CSRF, restrictions,
reauthentication and explicit retries; never copy private security orchestration.
Native errors and generated emergency 503 share one static, CSP-constrained
presentation build. Keep the application scaffold minimal; domain pages are added
only for real customer requirements.

Use docs/runa/handbook/architecture/project-principles.md#generated-tooling-and-build-state
for generated private build/tooling artifacts: one workspace-root .runtime/<area>,
no src/.runtime or module-local runtime roots. Preserve public asset/Laravel storage
contracts and unrelated reports, backups, caches and operation/recovery state.

Before handing over a frontend for manual review, prepare and inspect the actual
served development environment, current assets, mandatory Core data and private
fixture-account setup through [manual preparation](docs/runa/handbook/data/seeding.md#manual-auth-navigation-scenario).
Code/fixture coverage does not establish live readiness or owner appearance acceptance.

Place public welcome sign-in/conditional registration and authenticated entry
beside language/theme controls using MinimalLayout's public header-actions slot.
Forward this slot in custom layout wrappers; preserve the default identity and
preferences rather than copying their implementation into pages. Keep narrow
headers wrapping without overflow.

Welcome header authentication/application entry uses quiet text links beside
preferences, without button surfaces/borders. Preserve muted default text,
underline/color on hover, visible keyboard focus and 44px touch targets.

For Auth step/tab changes, keep programmatic focus on the noninteractive heading
(tabindex=-1) for reading context, without an interactive outline on that heading.
Scope this presentation exception to the reading target; retain visible keyboard
focus on links, inputs, buttons, error feedback and other interactive controls.

Use outlined submit buttons for forms, retaining destructive tone/busy/disabled
states. Use public TextLink for quiet welcome/Auth form navigation: no permanent
underline or button surface, hover underline and visible keyboard focus. Reuse the
shared implementation; do not restore page-local copies.

Keep the supplied /app workspace empty; customer content is explicit composition.
Self-service is Panel użytkownika/User panel, exposed through the dedicated user
dropdown rather than sidebar or placeholder welcome content. Keep menu identity
and icon/action rows consistent with the shell; do not extract shell menus into
universal content primitives.

For related settings, use the visible card-editor pattern in
`docs/runa/handbook/development/frontend/forms.md#related-settings-in-one-editor`.
Use shared Card and feedback components; keep ordinary fields directly editable
and independent operations in separate forms with their own outlined submits.
Preserve explicit security steps and scoped credential drafts/validation/cleanup;
use public account orchestration and AuthAvatarCropper rather than private copies.
Forward ApplicationLayout's width prop in wrappers; the supplied account shell is fluid.

Host avatar adjustment in the shared Dialog with explicit local Apply/Cancel.
Use a dimmed surrounding image and bright circular frame, direct pointer panning,
wheel/pinch zoom and keyboard alternatives; do not add redundant position sliders.
Applying a crop prepares the card preview; the card's Save owns the upload.

Use public FormNotifications for successful saves and form-wide failures only in
the top-right viewport. Do not duplicate outcome banners above forms. Preserve
associated field errors, persistent failure/recovery actions and explicit retries;
increment attempt on each submit. Modal notifications stay within modal focus.

Use public Avatar for repeated profile-image/initials presentation. Forward the
ApplicationLayout userAvatar prop in wrappers, and consume current profile display
through useApplicationSession rather than copying private profile orchestration.
Profile/image presentation is never authentication or authorization state.

Follow the public forms guide for independent Card forms, uneven-height column
stacks, explicit security-method actions and confirmed destructive removal.

For authenticator setup, reuse public AuthTotpSetup with the controller's private
QR/manual-key state; see the presentation contract. Do not send enrollment keys
to external QR services or persist them as preferences/assets.

Use public AuthRecoveryCodesDialog for first/replacement recovery codes, preserving
explicit saved acknowledgement and controller cleanup. Keep confirmation questions
in the dialog body when the title already identifies the action.

Compose secondary notification addresses inside account details through public
saveSecondary/secondaryErrors. Keep the collection's draft and submissions
independent from identity/password/avatar edits, including while requests are
pending; retain verified-recipient and explicit removal confirmation rules.

Table data columns sort by default; use sortable: false to opt out and preserve
server capability allowlists. Compare date instants rather than localized text.
Use the public tables guide for the complete contract.

Account factor actions belong in shared dialogs; trusted-device management is part
of two-factor settings and new trust requires an active factor plus fresh proof.
Follow the public account UI/presentation guides for independent controller state,
secret cleanup, feedback focus and legacy custom-presentation seams.

Account Preferences stacks its Cards at every width, with a header full-reset
confirmation, explicit language/theme
options sharing the existing preference cover, and independent inline email rows
with Switch/recipient Select/Save/Revert. Preserve other dirty rows; confirm the
atomic presentation-and-delivery reset. Shell controls still cycle compactly.
Use the public account UI/preferences guides for revision and recovery behavior.

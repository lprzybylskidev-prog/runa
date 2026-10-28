# Application entry and own-account screens

Auth 0.9.0 adds `AuthWelcome` and `AuthApplication` to `@/runa/auth`. The explicit
`0.8.0 -> 0.9.0` ModuleUpdates edge adds the `public_welcome_enabled` setting with
default true, without a migration or dependency. Fresh installations use the same
default. Existing identities, sessions and settings remain intact. Audit ^0.3.0 and
framework API ^1.0.0 remain compatible; apply the normal fenced module update.

## Entry, routes and settings

The bundled application-owned App.vue selects AuthEntry for exact `/auth/...`
paths, AuthApplication for `/app`, AuthApplication with `account` for `/account`,
and AuthWelcome for `/`. DefaultApplication owns that composition; use its public presentation provider or
the documented headless contracts when customizing App.
Named document routes are `runa.auth.pages.application`, `runa.auth.pages.account`
and the [existing entry routes](entry-ui.md). Unknown paths remain 404. Document
GETs contain no account data and never grant access; every account request uses
the server's module/session/eligibility/CSRF boundaries. No Admin UI is supplied.

With public welcome enabled, guests see sign-in and registration only when public
registration is enabled. Authenticated visitors see application access; restricted
visitors first finish their security steps. With welcome disabled, direct GET `/`
redirects to the named sign-in route when Auth is available. The public options
projection also sends the frontend to sign-in; a loaded document never treats the
flag as authentication. An existing session resolves into the app or its required
security flow. Disabling welcome neither revokes sessions nor relaxes checks.

The trusted operator reads current revision/generation with:

```sh
./bin/artisan runa:modules:settings runa.auth
```

Then writes a revision-checked JSON patch using those actual values (replace R/G):

```sh
printf '%s' '{"values":{"public_welcome_enabled":false}}' | ./bin/artisan runa:modules:settings runa.auth --write --revision=R --generation=G
```

Use true to restore welcome. This uses existing settings publication/history/Audit,
not an environment variable or a new privileged HTTP endpoint. Web policy writes
retain their permission and fresh step-up requirements. See [policy administration](policies-and-operators.md).

After login and required email/factor/password steps, AuthEntry navigates to a
validated internal `return` path or `/app`. `/`, Auth/API paths, encoded paths,
queries, external URLs and dot segments fall back to `/app`. An optional
`authenticated(session)` callback supports in-memory reauthentication composition;
its caller owns the destination and must still use guarded account APIs.

## Shell and self-service

AuthApplication composes the existing ApplicationLayout and actual application/
account navigation. A guarded own-profile read supplies display name, initials
and private avatar to both the top-bar trigger and dropdown identity. Successful
own-profile reads after save/remove update both immediately through the session
controller; revisioned URLs refresh replaced bytes. Initials remain the fallback
when absent, disabled or unreadable. Profile reads are presentation-only, optional
and do not block an otherwise valid shell or poll. They never grant access. Profile and CSRF-protected POST sign-out
share its existing user dropdown, with native links, keyboard/focus return and
pending/failure feedback. Successful sign-out clears account state and safe drafts
before navigating through `/`, so welcome policy determines the destination.
Back/refresh and direct entry recheck the server session. The entry page supplies
account access, without speculative business statistics or management navigation.

The supplied application/account shell uses ApplicationLayout width="fluid".
It renders that shell immediately while the server session is checked. Account
loading uses the existing Skeleton inside the four destination Cards, retaining
that structure through account initialization instead of a spinner or a temporary
MinimalLayout. Skeletons contain no identity values or interactive forms; protected
content mounts only after session validation. Capability-dependent content is
resolved from guarded reads. Loading failures expose retry without changing shells;
expired/restricted sessions still enter AuthEntry. Existing loaded forms stay
mounted during ordinary read refreshes. Notification preferences use skeleton rows
inside their existing Card while their first read is pending.
`AuthApplication` accepts a `loading` slot with `{ account: boolean }` for custom
page structure. The account fallback is public `AuthAccountLoading`, replaceable
through `AuthPresentationProvider.components.accountLoading`; both initialization
stages use that same presentation.
The default account editor groups identity, private avatar, password and two-factor
settings in four shared Cards. Details, avatar and password have visible Forms;
two-factor actions open shared dialogs. Each submission keeps its own credential
draft, validation and pending state. Wide screens use two
independent column stacks: details/password on the left, avatar/two-factor on the
right. A taller neighboring card does not add space between details and password;
narrow screens retain the same reading order in one column. Saving one card does not submit or clear
another card's draft. Preferences follows Account and security in the default navigation; sessions and
optional histories follow it.
Session/history and trusted-device tables show the shared loading spinner during
their guarded reads; empty-result text appears only after a completed read.
Table-tab entry uses one refresh pipeline, without a second rows read after data
appears. The centered spinner retains an accessible label without visible text.
Operation successes and form-wide failures use shared FormNotifications only in
the top-right viewport, with explicit operation descriptions and recovery actions.
Persistent errors do not duplicate a banner above the form; linked field errors
remain beside controls. Entry, avatar/crop and preference outcomes share this rule.

The details Card also contains secondary notification addresses below its primary
identity form. The default navigation has no separate secondary-address tab.
Verified/pending badges sit beside each address and wrap when space is limited.
Quiet per-address edit/reissue/remove actions accompany
a counter/limit; the compact add/edit Form is independent. Only unverified addresses
offer reissue. Removal requires shared modal confirmation, with failures retained
inside its focus boundary. Feature/self-service flags and the configured limit
control availability. Writes use the current profile version and reload it, never
mutating the identity form's draft (including during a pending request), passwords,
selected avatar or security setup. See saveSecondary in the public controller.

The details card validates its selected name/login/email edits before writing,
then uses the existing guarded endpoints sequentially with refreshed profile
versions. These writes are not one transaction: failure stops the sequence,
reports partial completion and never replays completed operations. A pending email
change is requested once and still requires email confirmation. Password and factor
changes each use their own fresh credential/proof submission. TOTP setup explicitly
displays a key, requires the app code and fresh proof, then requires acknowledgement
of recovery codes. Account lifecycle operations retain explicit confirmation.

The avatar card accepts a local JPEG, PNG or WebP original up to 20 MiB, 40 million
pixels and 12000 pixels per side. Selecting an original opens the shared Dialog. The surrounding image is dimmed
outside a bright circular avatar frame. Drag with mouse/touch to position it;
use the mouse wheel or a two-finger pinch to zoom, without sliders. Keyboard
arrows move, +/- zoom and Home resets. Ctrl+wheel keeps browser zoom native.
Apply crop prepares the circular preview on the card; Cancel/Escape dismiss
without upload. An accepted crop survives canceled readjustment. Saving exports
a 512x512 PNG, without original metadata or the preview's dark mask; only that cropped image is sent to the existing bounded upload API. Invalid
files, decode failures and output above the server's current avatar_max_kib stop
submission. Original files/object URLs stay in memory and are released on replacement
or unmount. The backend remains authoritative for MIME, size, dimensions and storage.

Avatar removal uses an outlined danger button and shared ConfirmDialog; its question is body content beneath the modal title. Cancel
preserves the current image; confirming sends only the versioned deletion and
reports its result through the existing top-right notifications. Pending image
selection must first be saved or discarded before removing the stored image.

The two-factor card lists each policy-available method with its enabled/disabled
state, explanation and explicit Set up/Disable action. Every factor action opens a
shared Dialog containing its current-password/proof fields and named submission.
Cancel, Escape and X clear the local proof, enrollment secret/QR and validation;
pending submissions cannot be dismissed or replayed. Feedback/recovery actions
stay inside the dialog focus layer. Other card drafts remain untouched.
Authenticator enrollment keeps the same dialog through begin/confirm, displays the
private QR/manual key and requires fresh proof at each stage. Success hands
single-display recovery codes to the shared acknowledgement-only Dialog. Initial
and replacement codes use the same modal; Escape/outside/X cannot discard them.
Secrets/QR/codes remain memory-only and excluded from print. Email enrollment
requires a verified primary address.

Trusted devices appear within the two-factor card when policy enables the feature;
there is no separate default navigation tab. Trust this browser is disabled until a
usable factor is enrolled. Its Dialog accepts a fresh factor code through the
existing backend boundary, without inventing an additional password requirement.
Manage devices opens a bounded, sortable Table in a Dialog, with explicit older-page
loading and refresh. Each revoke action requires modal confirmation. List errors
retain the dialog and provide refresh; CSRF errors provide explicit renewal without
write replay. Disabling/losing a factor never weakens the server's trust checks.
Legacy trust tab/choose/submit seams remain for custom presentations; new independent
trusted-device state/actions are documented in the public presentation contract.

Preferences stacks appearance/language and email Cards at every width, with the confirmed full-reset
action at the right of its page heading. This view uses explicit language/theme
RadioGroups rather than the shell's compact cycling buttons. It retains automatic
account persistence and the same cover/apply/focus sequence; choosing the current
value does not save or animate. Shell controls retain their existing behavior.

Optional email categories are edited directly in independent Form rows: a Switch
controls subscription, a multiple Select chooses primary/verified-secondary
recipients, and dirty rows expose Save/Revert. Edits stay local until Save. Saving,
reverting or refreshing one row preserves other unsaved row drafts.
Incoming snapshots merge clean/disjoint fields without overwriting dirty ones;
overlapping recipient edits use local FormConflict decisions. Recipient
selection is disabled while the category is off, preserving its configured values.
Known empty-recipient errors stop locally; server revision/recipient/security rules
remain authoritative. Required-message summaries are omitted from Preferences;
mandatory/sensitive delivery remains primary-only on the server. Empty/loading/failed
reads retain the Card/retry.
Errors and explicit recovery use the shared notification area; no automatic replay.

The header reset ConfirmDialog explains that presentation, navigation, declared
saved table layouts/filters/views and email subscriptions/recipients return to
source defaults. Cancel does not write. Confirmation uses the distinct atomic
reset_all operation with one optimistic revision and Audit; failure retains the
modal and provides explicit recovery. Success discards unsaved delivery drafts and
reloads authoritative defaults. Legacy scoped/presentation-only reset APIs remain
available to replacement presentations. See [preferences](preferences.md).

Own-account sections reuse Card/Form/FormSection/TextInput/RadioGroup/FileInput/Table:

- Versioned exclusive naming/login edits and protected primary email change.
- Password change, optional authenticator/email enrollment, factor removal and
  recovery-code replacement, honoring the current required method and proof policy.
- Feature-gated secondary addresses inside Account details, with verification
  status, independent compact add/edit, explicit reissue and confirmed removal.
- Private JPEG/PNG/WebP avatar, size constraints, removal and initials fallback.
- Bounded own sessions and trusted devices, revocation, explicit trust issuance
  using a fresh factor, and explicit older-page loading.
- Immediate account theme/language preferences, explicit reset and enabled email
  categories with verified secondary recipients. Mandatory/sensitive security mail
  remains primary-only. Delivery writes use the account preference revision.
- Independently enabled own login/account summaries through the private history
  API. These show only safe event/time/outcome summaries, never general Audit details.

Disabled features disappear. Read-only fields and avatar policy remain enforced by
the backend if policy changes after load. Self-deactivate/delete require an explicit
confirmation, fresh password step-up and the Permissions-backed neutral authority invariant, including protection of the last eligible superadministrator. Unavailable authority integration refuses the operation.
Secrets/files clear after attempts/unmount. TOTP setup keys and single-display
recovery codes stay in memory and are hidden from print. Recovery codes require an
explicit saved acknowledgement. No token/password/OTP/draft enters preferences,
localStorage, sessionStorage or URL queries. Raw backend error text is never shown.

## Expiry, real activity and drafts

A mounted application boundary reads session/CSRF once, then refreshes on visible
return or restored browser history. Local timers display the configured warning
and stop ordinary account UI at the known idle/absolute deadline. There is no
background polling. Trusted typing/click/scroll/touch events may send at most one
activity heartbeat per server interval when extension is enabled; GETs and mouse
movement never count. A continuation click cannot move the absolute deadline.
The backend remains authoritative, including stricter policy and revocation.

A current 401 or `account_restricted` response replaces account content with the
normal login/restricted flow. Request epochs fence late session/activity responses;
unmounted account forms abort reads/writes and ignore late results. They never
replay a write after login, 419 or conflict. CSRF renewal and reloading current
versions are explicit actions; the user reviews and submits again.

Only name/login drafts can survive this in-memory reauthentication boundary. They
are keyed to the original account and offered through an explicit restore-for-review
action. A different account discards them. Successful logout/reset of draft ownership
also fences late unmounts from restoring discarded values. Reloading the browser
discards memory; credentials, email mutations, files and security proofs are never
retained as drafts. Custom application forms must explicitly select similarly safe
fields and maintain their own ownership/cancellation contracts.

Rendered owner fixtures cover shell/dropdown/account states and navigation. Safe local account discovery/private initial setup and the complete manual
welcome/sign-in/application/profile/dropdown sign-out path are documented in the
[development corpus](../../data/seeding.md#manual-auth-navigation-scenario).
Integrated browser/security acceptance remains final verification; mock transports
are not evidence of a production login or delivery.

## Customer presentation

Use [frontend adoption and replacement](../../development/frontend/customization.md)
for unchanged defaults, public layout/content composition and complete customer
presentation. AuthPresentationProvider replaces views/layouts within its subtree;
public headless controllers retain the same flow/session cleanup. Put customer
files in src/app. This is the general Runa view rule, also required for future
modules. DefaultApplication owns supplied path composition; App.vue is thin wiring.

Programmatic heading focus announces the current Auth step/tab to assistive
technology. These headings retain tabindex=-1 and suppress only their own outline;
inputs, links, buttons and error-feedback focus retain visible keyboard indicators.

The default authenticated application view is empty. Account self-service is named
Panel użytkownika (User panel) and is reached only through the user dropdown,
not the sidebar or a welcome card. The dropdown groups initials/name identity
above left-aligned icon/action rows with a divider and preserved keyboard focus.
ApplicationLayout omits its heading region when title, heading and actions are
absent. Session warnings/errors remain available; custom application content still
uses the public session-scoped slot.

Own sessions use the concise End action for both current and other sessions.
Account data tables consume the global default-sortable column contract. Date
columns compare original instants while displaying localized dates; history sorting
only covers rows already loaded through the existing cursor. Revocation permissions,
current-session sign-out and trusted-device Revoke behavior remain unchanged.

## Automatic account refresh

One mounted `useAuthAccount(accountId)` owns the account lifetime, abort signal,
confirmed profile version, refresh coalescing and write gate. Its internal profile,
list/history/trust, validation and delivery collaborators share those dependencies;
they do not create another account store or session controller. Section saves stay
in one sequential pipeline so completed writes are not replayed and later writes
use the refreshed version. Custom screens use the public controller from
`@/runa/auth` once per keyed account, including its returned conflict and list state.

The public account controller refreshes profile, security/options, active lists,
loaded trusted devices and visible delivery settings on tab changes, visible return,
window focus/pageshow and rejected conflicts. Reads coalesce and retain account/view
fences. It does not poll, remount the view, move focus or replay writes. Read failures
preserve drafts and expose Retry loading. Preference hydration refreshes visible
delivery categories; theme, navigation, table and delivery writes share one queue
and current account revision. Session refresh of the same account retains cached
presentation until the authoritative snapshot arrives; logout/switch still fences it.

Clean profile fields merge automatically. Dirty identity/email fields, secondary
address editors, avatar files and password/factor drafts remain in memory. Overlapping
identity/secondary/recipient edits render FormConflict locally. The controller
exposes refresh, detailConflicts/resolveDetails and avatarConflict/resolveAvatar;
resolution changes local state only and requires an explicit subsequent save.
The current profile has no separate avatar-content revision, so an external profile
version change while an image draft exists conservatively requires local image
review; it cannot safely prove that replacement bytes are unchanged. Submission,
cancel, expiry and factor-proof cleanup retain their existing security contracts.

## Concise account presentation

Default password hints show the configured minimum and enabled complexity rules;
the server's fixed byte limit still applies but is omitted from helper copy. Omit
redundant verified-email, secondary-address introduction, avatar instructions,
enrolled-method summaries/status lines, trust explanation and delivery-editor
introduction. Keep actionable verification errors and enrollment prerequisites.
The avatar preview is centered within its Card. Trust actions appear directly
inside the two-factor Card without a redundant subsection heading.

Trusted-device management is visible only when trustedRows contains an active
record from the existing guarded current-device endpoint. Load that list at
initialization, visible refresh and after security changes; preserve its independent
cursor and busy state when opening its dialog. Expired/revoked/missing devices do
not keep the action visible. This is presentation availability only; server trust,
factor proof, validation and authorization rules remain unchanged.

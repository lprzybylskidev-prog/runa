# Frontend adoption and presentation replacement

Runa ships usable welcome, sign-in/security, application/account and HTTP error
screens. Customers can retain them, compose application pages from public layouts
and controls, or own their complete frontend. New Runa views follow this same
rule: usable defaults and a documented presentation replacement contract.
Application changes live in `src/app`; `src/runa` remains protected.

## Ownership and startup

The supplied `src/app/resources/js/App.vue` renders `DefaultApplication` from
`@/runa/auth`. That platform component selects exact Auth pages, `/app`, `/account`
and welcome. When welcome is enabled, the default `/app` has Home / Application breadcrumbs and an otherwise empty workspace: no page heading, welcome card or account links. Native unknown server paths remain 404 responses.
`main.ts` imports the shared CSS through `@/runa/ui/styles.css` and mounts
`createApplication(App)` from `@/runa/ui`. This public helper returns a Vue App,
so customers can install their own plugins before mounting. It hydrates public
identity without delaying rendering; failed requests retain manifest defaults.

The retained application files are manifest/dependency files, the HTML entry,
Vue root/bootstrap, named welcome route, provider/seed/test/config extension
entrypoints and the development reference module. Each is an application-owned
integration seam. Platform screen selection and identity orchestration stay in
Runa; no forwarding application controller is needed. `src/app/config/branding.php`
is an optional presentation override (empty by default).

Customers own `src/app/resources/index.html` metadata, their own assets/styles,
route wiring and added pages/components. Application identity/version/logo belongs
in `src/app/application.json`; platform version remains independent. Theme tokens
come from the shared CSS; add application CSS after that import. Tailwind scans
application resources/modules and platform source. Keep complete utility names
statically discoverable. See [branding/layouts](../../architecture/frontend.md).

## Compose with supplied screens

`AuthApplication` also accepts a `loading` slot with `{ account: boolean }` for the destination page skeleton before session validation. Do not mount protected controllers or forms in this slot. Its default account loading presentation is shared with the default account screen.

A customer page can render `AuthApplication` from `@/runa/auth` with a default
scoped slot. It runs the existing application session boundary and supplies the
current unrestricted `session`; account changes remount its content; the shell retains its profile/sign-out dropdown,
expiry warning, failures and reauthentication. Its `account` prop selects supplied
account self-service, taking precedence over the content slot. Put the page behind
an application-owned named server route and enforce its own backend grants/policies.

```vue
<script setup lang="ts">
import { AuthApplication } from '@/runa/auth';
import { Button } from '@/runa/ui';
</script>
<template>
  <AuthApplication v-slot="{ session }">
    <h1>{{ session.display_name }}</h1>
    <Button>Application action</Button>
  </AuthApplication>
</template>
```

For independent page composition, `MinimalLayout`, `ApplicationLayout`,
`IdentityProvider` and controls remain available from `@/runa/ui`. Importing them
does not require a module registry or router. The supplied dedicated shell menu
components remain internal. See [page layouts](page-layouts.md) and
[components](components.md) for props, slots and navigation descriptors.

## Replace presentation in the default application

`AuthPresentationProvider` from `@/runa/auth` accepts a `components` object. It
applies only to descendants; nested providers inherit unspecified entries.
Omitted entries use supplied defaults. Pass imported component definitions, not
names, URLs or backend metadata. Keep the map stable during an active form.

| Entry               | Customer component contract                                                                                                                                                                                                                                                                                          |
| ------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `minimalLayout`     | Accept and render the default content slot, including its `t` translator slot argument for welcome, and forward `header-actions` for the welcome entry links; accept layout props including homeHref. A wrapper around public MinimalLayout can forward all slots/props and override header, illustration or footer. |
| `applicationLayout` | Accept ApplicationLayout props and its default/user-menu/breadcrumbs slots. Forward width/navigation/current/title/user identity (including userAvatar/userLoading/homeHref) and preserve the account/sign-out controls.                                                                                             |
| `entry`             | Accept `page: EntryPage` and optional `authenticated(session)` callback. Use `useAuthEntry(page, authenticated)` once per keyed page. This includes reauthentication after expiry.                                                                                                                                   |
| `account`           | Accept `accountId: string`; use `useAuthAccount(accountId)` once. The wrapper remounts on account identity change.                                                                                                                                                                                                   |
| `accountLoading`    | Static initial account structure, without account props or reads. Used before session validation and while the default account controller initializes. Public `AuthAccountLoading` is the supplied fallback.                                                                                                         |
| `welcome`           | Use `useAuthWelcome()` once to retain welcome policy and session-aware links.                                                                                                                                                                                                                                        |

```vue
<script setup lang="ts">
import { AuthPresentationProvider, DefaultApplication } from '@/runa/auth';
import CustomerMinimalLayout from './layouts/CustomerMinimalLayout.vue';
const components = { minimalLayout: CustomerMinimalLayout };
</script>
<template>
  <AuthPresentationProvider :components="components">
    <DefaultApplication />
  </AuthPresentationProvider>
</template>
```

`MinimalLayout` itself is always the supplied primitive, so an override wrapper
can compose it without recursion. Preserve locale/theme controls when replacing
its header, accessible landmarks/skip links, focus behavior and reduced motion.
The supplied Auth components use the provider for layout selection, including
loading/error and restricted states. It does not rewrite independent application
uses of the public layouts. Avoid selecting the public Auth wrapper itself as
its own override; compose a customer component instead.

## Own the complete frontend

Customers can replace the HTML/Vue root and all screens while keeping the backend.
The public `@/runa/auth` facade exports the same controllers the supplied views
use; private module/resource imports and copied orchestration are unnecessary.
Controllers must run inside Vue setup and be disposed with their view. Their
returned refs/computeds remain reactive: retain refs when destructuring. A changed
entry page or account ID requires a keyed remount. Returned types are inferred;
EntryPage/EntrySession/EntryOptions/SecurityOptions/FactorMethod, ActivitySession,
AccountTab/AccountTask/AccountSection and Profile/AccountRow are public types.

Bind controller-owned template refs with explicit callbacks when destructuring
them: assign the exposed Form instance to `form` and the heading element to
`heading`, clearing them on unmount. This keeps strict unused-binding checks and
the controller validation/focus contract aligned.

| Controller                           | Presentation responsibilities                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| ------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `useAuthEntry(page, authenticated?)` | Render `screen`, title/description, ready/busy/failure/notice, `fieldNames`, values/errors/labels/passwordHint, naming/remember/method choices, enrollment and recovery-code states. Call submit, initialize/refresh, refreshCsrf, sendEmail, logout, codesSaved and changeMethod for the corresponding explicit actions. Bind form/heading refs for validation/focus. Honor needsProof, hasSubmit, methods, enrollmentRequired and token-page restrictions. Without callback the controller handles the approved return; with callback the parent resumes its session boundary.                                                                                                                                                                     |
| `useAuthAccount(accountId)`          | Render server capabilities/tabs, profile/security, task/fields/errors, proofMethods, busy/failure/notice and explicit draft review. Use saveSection(details/avatar/password/security) with sectionFields/sectionErrors and overviewAction/removeAvatar for independent cards; bind form to the submitting Form. Existing saveOverview and choose/submit remain supported. Use refresh for nondestructive read reconciliation and render detailConflicts/resolveDetails and avatarConflict/resolveAvatar locally. Call selectTab, revoke, readPage, saveDelivery, resetPreferences, restoreDraft, sendCode/sendVerification and refreshCsrf for the corresponding actions. Bind form/heading/feedback refs. Never silently resubmit a restored draft. |
| `useAuthWelcome()`                   | Render loading/failed/retry, options and optional session. The controller enforces disabled welcome. Link a restricted session to `/auth/security`, an unrestricted one to `/app`, guests to sign-in and registration only when enabled.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| `useApplicationSession(navigate?)`   | One boundary for the mounted private application. Render loading/failure/pending/warning/remaining; unmount protected content when paused or without a session. Render AuthEntry or a replacement with authenticated callback while paused. Use refresh/logout/activity and render only the current unrestricted session. Navigation defaults to document navigation; an optional callback integrates a customer router.                                                                                                                                                                                                                                                                                                                             |

The default Preferences presentation uses the same public account replacement seam.
Use `saveDelivery(choice)` with a cloned per-row draft and explicit Save/Revert;
`selectTab('preferences')` deliberately reloads categories. `refresh()` reconciles reads without resetting drafts or focus; render the local conflict seams and preserve explicit write retries. Preserve unrelated dirty
rows across successful writes and reloads. Current revisions, verified recipients
and mandatory/sensitive primary-only rules stay server-owned. The confirmed
`resetPreferences(true)` resets presentation and delivery together through the
adapter's explicit `resetAll()` operation. The no-argument `resetPreferences()` and existing scoped/presentation-only
adapter/PHP reset APIs retain their old scope. Reuse the shared display controller
and cover; Preferences offers explicit options while shell controls still cycle.
Never duplicate persistence, replay a failed write or import protected source from
customer code. See the public account UI/preferences contracts for behavior.

`useAuthAccount` also exposes `trustedRows`, `trustedCursor`, `canTrustDevice`,
`loadTrustedDevices(append = false)` and `revokeTrustedDevice(publicId)` for trusted
browser presentation within the two-factor section. Loading/revoking returns a
boolean result, uses the same guarded account lifetime and leaves session/history
rows untouched. Revoke removes a row only after backend success; loading more is
explicit and bounded. Set `overviewAction` to `trustIssue` and submit
`saveSection('security')` with fresh `method`/`sectionFields.security.code`.
`canTrustDevice` requires policy availability and a usable enrolled factor; it is
presentation validation, never authorization. Use the existing backend proof;
trust issuance does not require a new current-password field. Legacy trust
`selectTab`/`choose`/`submit` remain available. Defaults put all factor/trust actions
in shared dialogs; customer replacements own presentation while the controller
retains orchestration, cancellation and private draft cleanup.

`useAuthAccount.saveSecondary(action, input)` accepts secondaryAdd/secondaryChange/
secondaryRemove with email/address_id and explicit confirmed=true for removal;
it returns whether the write and authoritative profile reload completed.
Bind secondaryErrors.email to the independent email field. It honors public
capabilities and limits, uses existing guarded versioned endpoints and preserves
all other drafts throughout the request/failure. Confirm removal deliberately,
keep write retries explicit, refresh reads automatically without discarding drafts
and render overlapping conflicts locally. The supplied
view integrates this collection into Account details; legacy addresses tab and
choose/submit controller contracts remain available to replacement presentations.

AuthRecoveryCodesDialog is public through `@/runa/auth`: pass the current
`codes`/`recoveryCodes` array, locale and busy state; handle `acknowledge` by clearing
account codes or calling entry `codesSaved`. It opens only for a nonempty array,
traps focus and requires explicit saved acknowledgement; never save codes as
preferences or replay generation when closing. Initial enrollment and replacement
share this presentation.

AuthTotpSetup is public through `@/runa/auth`: pass `secret`, `qrCode` and locale
from the current account controller (`secret`/`qrCode`) or entry controller
(`enrollmentSecret`/`enrollmentQr`). It displays the server-provided bounded SVG
data URI with the manual fallback key, excludes both from print and uses no
remote QR service. Keep these credentials ephemeral; use controller cleanup,
never preference/browser storage or a third-party image URL.

AuthAvatarCropper is also public through `@/runa/auth`: pass a local File, locale
and current maxBytes, wait for ready(true), then await the exposed crop() method
for a 512x512 PNG File. ready(false) invalidates the previous crop. The component
owns source decoding, bounded original limits, circular masked preview, pointer/keyboard
panning, wheel/pinch zoom and object-URL cleanup; it never uploads. Pass the resulting File to the account
controller's files ref and explicitly saveSection('avatar'). Compose it inside public
Dialog with explicit Apply/Cancel; export only on Apply and upload only on Save. Dispose it with the
account/page; do not copy its private implementation.

useApplicationSession also exposes optional reactive profile presentation from the
guarded own-profile route. Subscribe through the public controller; same-account
profile reads with a nonstale version refresh name/initials/avatar after edits.
It clears presentation on pause/logout/switch and ignores foreign or stale responses.
The default /app and /account views supply localized Home / Application and
Home / Application / User panel breadcrumbs through the shell slot when public
welcome is enabled. With welcome disabled (or its policy not yet available), omit
Home and use /app for the brand link in authenticated and Auth entry layouts.
useApplicationSession exposes welcomeEnabled from the existing public entry options;
this optional read never blocks a valid session and is fenced against stale results.
Custom ApplicationLayout wrappers forward userAvatar, userLoading, homeHref and breadcrumbs; use public Avatar for a
custom user identity row. The session controller exposes profileLoading for its
optional presentation read; combine it with initial loading when passing Avatar
loading. Image readiness is handled by Avatar itself. These presentation fields never establish authorization.

Keep private forms scoped to the current account and unmount them on pause/switch.
In a full customer shell, key the private subtree by session.public_id.
The session controller fences responses, revokes preference ownership and observes
shared HTTP failures. It never replays writes. Use `request`, `ApiError` and `RequestCancelledError` from
`@/runa/http` for application requests; relative paths are below `/api`. It retains
same-origin CSRF/cookies and the session lifecycle observer. Do not scatter fetch,
store credentials in browser persistence or implement a second session mechanism.

Server authentication, current eligibility/restrictions, CSRF, module availability,
grants, record policies, recovery proofs and Audit remain authoritative. Custom
presentation must expose every applicable server-driven security step and retain
explicit retry, fragment cleanup, secret cleanup and accessible error/focus paths.
A headless API enables presentation replacement; it does not excuse omitting
recovery, 2FA enrollment/challenge, password change or explicit confirmation.
See [entry](../../modules/auth/entry-ui.md), [account](../../modules/auth/account-ui.md)
and [transport](../../modules/auth/transport.md).

## Native errors and emergency 503

All native HTTP error documents and the pre-generated emergency 503 use the same
build-time layout selection. Optionally add
`src/app/resources/js/errorLayout.ts`, exporting a synchronous SSR-safe Vue
component as its default export. With no file, Runa uses `HttpErrorLayout`, which
composes the actual MinimalLayout and supplied error content.

The component receives `HttpErrorLayoutProps` from `@/runa/ui`: `staticPreferences`
(locale/theme), `footer`, and safe localized `error` status/code/title/description/
home/retry data. Its default slot contains the supplied error content. Forward the
slot to retain status, actions and request ID; use public `HttpErrorContent` with
`error` props when deliberately rearranging that content. A minimal override can
wrap public HttpErrorLayout, forward its props/slot, and import application CSS.
No PHP/Blade application UI or second error renderer is required.

Build through normal `pnpm run build` or `pnpm run build:errors`, then generate
standalone assets through the supported production pipeline. Both native and
emergency output use the same generated templates and CSS. The emergency PHP
build merges non-secret application branding overrides without booting Laravel,
loading env files or connecting to a database. Customer branding overrides used
here must also be usable in this standalone build context (path-only helpers,
plain values; no container/request/database access).

Error components run without window/document, network, session, router or a live
application. Forward `staticPreferences` to MinimalLayout to disable preference
hydration and render language/theme links rather than JavaScript controls. Output
must contain no scripts, inline styles or handlers; the existing generator rejects
these and preserves the normal restrictive CSP. CSS/system themes and escaped
runtime substitutions remain shared. Native status, JSON negotiation, retry/no-store
headers and secret-safe request IDs remain backend/Nginx responsibilities. Do not
replace those guards when changing appearance. Rebuild the assets after changes;
outage responses cannot read live application state.

## Delivery and updates

Acquisition supplies the small default application only at first installation.
Application frontend, errorLayout.ts, styles, manifests, routes and local guidance
remain customer-owned. Reopen and teammate clones preserve committed customer
files. Updates preserve application-owned files, including frontend/error/style
customizations; they do not inspect or apply upstream replacements to customer
domain contents. Exact per-file review governs eligible delivered files and local
instruction reconciliation; it never turns protected platform updates into an
automatic replacement of customer screens. Keep application code and guidance committed in the
customer repository. New platform defaults arrive under protected src/runa; compare
new contracts with local presentation during the reviewed update.

Owner-only examples and acceptance fixtures live under src/runa/tests and
maintainer tooling, never as added customer business screens/modules. Rendered
responsive/theme/keyboard/security-flow and delivery acceptance runs in the final
roadmap verification; custom code still needs its own rendered acceptance.

The private error SSR renderer is bundled with its dependencies because its output
lives under workspace-root .runtime, outside src/node_modules. Keep this build tool
self-contained when changing artifact paths; only generated HTML/CSS/assets enter
production runtime images. See [manual preparation](../../data/seeding.md#manual-auth-navigation-scenario)
for actual environment readiness before frontend review.

Welcome entry links belong in MinimalLayout's public `header-actions` slot beside
language/theme preferences, including the signed-in application link. Customer
layout wrappers forward this slot; replacing the full header deliberately owns its
identity, entry and preference presentation. The default slot remains page content.

Welcome header authentication/application entry uses quiet text links beside
preferences, without button surfaces/borders. Preserve muted default text,
underline/color on hover, visible keyboard focus and 44px touch targets.

The default authenticated application workspace is empty beneath its breadcrumbs. Account self-service is named
Panel użytkownika (User panel) and is reached only through the user dropdown,
not the sidebar or a welcome card. The dropdown groups initials/name identity
above left-aligned icon/action rows with a divider and preserved keyboard focus.
ApplicationLayout omits its heading region when title, heading and actions are
absent. Session warnings/errors remain available; custom application content still
uses the public session-scoped slot.

When consuming shared HTTP directly, treat RequestCancelledError as cancellation
of a departing document's observation, not a server success. Avoid an error flash
in that discarded view, retain real live errors, and never replay a canceled write:
its server completion may be unknown. New explicit reads may reconcile state.
The same lifecycle wraps response-body reads; idle pages carry no beforeunload
request guard, and synchronous dirty-form guards take precedence.

For secondary address changes/removal, pass the editor's confirmed `baselineEmail`
with its address identity to saveSecondary. Render local conflicts when the address
changes or disappears; do not replace its baseline merely because another form
refreshed the aggregate profile version. This comparison is client UX; backend
version/permission/verification rules remain authoritative.

The default welcome uses `.runa`, a PL/EN platform introduction and a build-time
catalogue of delivered Runa modules. Tooltips reuse manifest names/descriptions;
icons share the brand tile and support pointer hover and keyboard focus. This is
a product catalogue, not a live availability dashboard. The existing `welcome`
presentation replacement owns any customer-specific introduction/module catalogue;
`useAuthWelcome()` continues to own session and welcome-policy behavior.

The default account dropdown identity shows only avatar and display name, without
a redundant account caption. Account deactivation uses warning outline actions;
account deletion uses danger outline actions, including confirmation submits.

Account history provides a replaceable `historyDetail` presentation and typed detail
controller state; follow the [Auth history contract](../../modules/auth/history.md)
for props/events, lifecycle fencing, safe output and old-event limitations.

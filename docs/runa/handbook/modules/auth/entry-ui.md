# Auth entry screens

Auth supplies entry and restricted-session Vue screens using the existing
MinimalLayout, Polish/English copy, shared form controls and theme tokens. The
historical source-only `0.7.0 -> 0.8.0` update changed no stored data or settings.
Apply through ModuleUpdates; unchanged installations remain no-ops. Account
self-service and session-expiry integration are supplied by [the account UI](account-ui.md).

The default login view uses its heading and labelled fields without an
introductory paragraph. Registration explains possible activation requirements;
recovery and restricted steps retain their conditional outcomes, security
consequences and instructions. The entry controller keeps its optional description
(empty for login), including for customer presentation replacements.

## Composition and routes

Import `AuthEntry`, `entryPage` and `EntryPage` from `@/runa/auth`. The component
loads lazily, accepts one `page` prop and discards the previous flow when it changes.
The bundled App selects it for exact Auth paths and composes the public welcome
and authenticated application/account views separately. Custom applications can compose the same component; no router,
global state package or replacement shell is required. Preserve this integration
when maintaining an application-owned App.vue.

| Path                    | Page prop         | Named GET route                   |
| ----------------------- | ----------------- | --------------------------------- |
| `/auth/login`           | `login`           | `runa.auth.pages.login`           |
| `/auth/register`        | `register`        | `runa.auth.pages.register`        |
| `/auth/forgot-password` | `forgot-password` | `runa.auth.pages.forgot_password` |
| `/auth/reset-password`  | `reset-password`  | `runa.auth.pages.reset_password`  |
| `/auth/invitation`      | `invitation`      | `runa.auth.pages.invitation`      |
| `/auth/verify-email`    | `verify-email`    | `runa.auth.pages.verify_email`    |
| `/auth/confirm-address` | `confirm-address` | `runa.auth.pages.confirm_address` |
| `/auth/security`        | `security`        | `runa.auth.pages.security`        |

These GET routes serve only the application document, with no-store and
no-referrer headers. They do not resolve identity, consume tokens or grant access.
Unknown paths remain 404. Every data operation still enters the availability,
session, CSRF, eligibility and permission boundaries of the named module endpoint.
No administrative page or duplicate Fortify route is registered.

```vue
<script setup lang="ts">
import { AuthEntry, entryPage } from '@/runa/auth';
const page = entryPage(window.location.pathname);
</script>
<template><AuthEntry v-if="page" :page="page" /></template>
```

## Server-driven forms and restricted sessions

Initialize CSRF through the shared same-origin HTTP wrapper. Public
`GET /api/runa/auth/entry-options` (`runa.auth.entry.options`) projects only
public-welcome availability, registration availability, identifier mode, remembered-session availability and
new-password constraints. It never returns the full encrypted settings, account
existence, lifecycle blockers, secrets or policy history. The server remains the
authority if policy changes after the form loads. Registration links/forms disappear
when disabled; a direct registration visit explains that access is unavailable.

The own-session and restricted security projections select the next step: email
verification, required factor enrollment/challenge, then required password change.
Change-specific factor policy also sends an account with no usable factor to
enrollment first. Mandatory TOTP never offers email OTP as an alternative.
The security projection adds `recovery_codes_enabled` and
`trusted_device_eligible` and `email_verified`; other fields and existing contracts remain compatible.
After each successful security operation, reload session/security state before
showing ordinary access. An unknown restriction or malformed projection fails
closed. Refresh/resend/sign-out are explicit supporting actions.

TOTP setup displays a manual setup key, then requires current password and the app's
six-digit code. Enrollment returns recovery codes once. Keep them visible until
the user acknowledges saving them; then discard them and refresh session state.
Email enrollment first directs unverified-primary accounts to confirmation and is
followed by an emailed OTP challenge. Password changes preserve the configured
session policy and honor `reauthenticate` instead of claiming an active session.
Trust issuance and optional own-account factor management use the [account UI](account-ui.md).
General privileged administration dialogs remain deferred.

## Secrets, redirects and failures

Consume `#token=...` into component memory and immediately replace the URL without
the fragment, including malformed fragments. Never submit on GET/mount. The user
must confirm the POST, or enter/confirm a new password for invitation/reset.
Refreshing after fragment removal requires reopening the mail link. No token,
password, setup key, recovery code or OTP enters preferences, browser storage,
navigation query, analytics or a module API. Password/code inputs clear after an
attempt; token proofs remain only for deliberate retry until success or leaving.
Changing page/unmounting cancels requests and discards secrets; late responses
cannot populate a new flow. Setup keys are excluded from print.

`return` accepts a conservative local path of ordinary slash-separated letters,
digits, underscores and hyphens. External URLs, encoded paths, dot segments,
backslashes, queries, `/` and Auth/API destinations fall back to `/app`. After all
restrictions clear, AuthEntry navigates to that validated destination. The optional
`authenticated(session)` callback lets AuthApplication finish reauthentication in memory. No external
redirect or automatic write replay occurs.

Local validation handles required fields, exclusive naming, identifiers, password
complexity/UTF-8 byte bounds, confirmation and code shape. Forms use novalidate,
password-manager autocomplete, linked field errors and focus recovery. Safe fixed
PL/EN messages handle validation, generic credentials/proof refusal, reused
passwords and the post-credential `account_inactive` refusal. Inactive/future-
activation accounts can complete invitation password setup and email confirmation
before activation, without receiving an authenticated session. Other messages cover
token expiry/replay, disabled registration, 401, 409, 419, 429 and service
failure; raw backend strings are never rendered. A 419 refreshes CSRF only on
request and requires explicit resubmission. Conflicts preserve nonsecret values
and offer an account refresh when relevant. Background reads never extend activity.

Flow transitions are deliberately immediate and focus the heading; alerts announce
results and form failures focus invalid controls after controls become enabled.
Form-wide outcomes use top-right FormNotifications, with persistent failures and
explicit CSRF/reload actions in the notification; do not duplicate form banners. Shared controls retain their reduced-motion feedback.
The existing layout supplies responsive desktop/tablet/mobile, light/dark themes,
language/theme controls and identity/footer. Browser acceptance fixtures cover
locale, reduced motion, overflow, keyboard errors and private fragments; complete
cross-browser/integration acceptance remains part of final roadmap verification.

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

TOTP enrollment shares public AuthTotpSetup with the user panel: scan its locally
rendered QR image or enter the manual key, then confirm the authenticator code.
The private image uses the existing no-store begin response and no external QR
service. Setup images/keys are omitted from print and cleared with enrollment
state on completion, method change, sign-out and disposal.

Recovery codes use the same AuthRecoveryCodesDialog as account enrollment and
replacement. The modal body contains the one-time codes and storage instructions;
its sole close path is the explicit saved acknowledgement, after which the normal
restricted-session refresh resumes. Codes stay print-hidden and in memory only.

## Operation proof composition

Auth owns the password-only useOperationProof controller and replaceable
AuthOperationProofPage, imported through @/runa/auth and implemented in Auth frontend.
The former inline AuthOperationProof export is retired. Second factors belong to
login only; no operation view discovers or requests eligible factor methods.

```ts
type ProofRequirement = 'none' | 'password';
interface PasswordConfirmation {
  request(controller: OperationProofController): Promise<boolean>;
  cancel(controller?: OperationProofController): void;
}
function useOperationProof(options: {
  identity: () => string | undefined;
  action: () => string;
  requirement: () => ProofRequirement;
  locale?: () => 'pl' | 'en';
  confirmation?: PasswordConfirmation;
}): OperationProofController;
```

OperationProofController exposes password Ref<string>, open/busy/confirmed
Ref<boolean>, error ComputedRef<string>, requirement ComputedRef<ProofRequirement>,
begin()/confirm():Promise<void>, request():Promise<boolean> and reset():void.
request opens the shared full-page confirmation host and resolves false on cancel,
reset, identity/access loss or disposal. It never silently replays a refused write.
The composing owner preserves its reviewed subject/draft/revision and rechecks them
before executing the original submitted intent once after successful confirmation.

ApplicationSessionShell supplies one host for account/Admin consumers, retaining
mounted drafts and suspending underlying modal portals/focus while the existing
MinimalLayout-based Auth page takes over. No second session, browser storage or
credential-bearing URL is introduced. Application-owned shell replacements can
compose public providePasswordConfirmation(): {active:ShallowRef<OperationProofController|undefined>,
complete(confirmed?:boolean):void,cancel(controller?:OperationProofController):void}
and usePasswordConfirmation():PasswordConfirmation|undefined. Render the existing
AuthOperationProofPage for active, wire confirmed to complete and cancel to cancel,
and suspend retained presentation through the public UI visibility contract.
Missing host fails closed; standalone protected entry can still render the page
with its existing controller and explicit begin/confirm events.

AuthOperationProofPage accepts controller, optional locale, title, description and
homeHref; its default slot receives {controller}. Emits confirmed/cancel. It uses
existing operationProofPage/MinimalLayout replacements, PL/EN fields, focus and
FormNotifications. Confirmation sends action/current_password to native step-up;
credentials clear on every settled attempt/reset/identity change/disposal. It
returns no transferable authorization token. Backend grants/proof/session/revision
checks remain authoritative. Native login entry continues to own 2FA challenges
and authenticator setup-code validation.

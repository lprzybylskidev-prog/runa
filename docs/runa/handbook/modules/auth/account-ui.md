# Application entry and own-account screens

Auth 0.9.0 adds `AuthWelcome` and `AuthApplication` to `@/runa/auth`. The explicit
`0.8.0 -> 0.9.0` ModuleUpdates edge adds the `public_welcome_enabled` setting with
default true, without a migration or dependency. Fresh installations use the same
default. Existing identities, sessions and settings remain intact. Audit ^0.3.0 and
framework API ^1.0.0 remain compatible; apply the normal fenced module update.

## Entry, routes and settings

The bundled application-owned App.vue selects AuthEntry for exact `/auth/...`
paths, AuthApplication for `/app`, AuthApplication with `account` for `/account`,
and AuthWelcome for `/`. Preserve that explicit composition when customizing App.
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

AuthApplication composes the existing ApplicationLayout, identity initials and
actual application/account navigation. Profile and CSRF-protected POST sign-out
share its existing user dropdown, with native links, keyboard/focus return and
pending/failure feedback. Successful sign-out clears account state and safe drafts
before navigating through `/`, so welcome policy determines the destination.
Back/refresh and direct entry recheck the server session. The entry page supplies
account access, without speculative business statistics or management navigation.

Own-account sections reuse Form/FormSection/TextInput/RadioGroup/FileInput/Table:

- Versioned exclusive naming/login edits and protected primary email change.
- Password change, optional authenticator/email enrollment, factor removal and
  recovery-code replacement, honoring the current required method and proof policy.
- Feature-gated secondary addresses, change/reissue verification and removal.
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
confirmation, fresh password step-up and the neutral authority invariant; before
Permissions integration the server still refuses the invariant-dependent operation.
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

Rendered owner fixtures cover shell/dropdown/account states and navigation. Real
installation accounts/manual discovery and integrated browser/security acceptance
are completed in the later fixture and final-verification points; mock transports
are not evidence of a production login or delivery.

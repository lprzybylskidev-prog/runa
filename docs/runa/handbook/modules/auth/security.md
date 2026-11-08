# Passwords, second factors, trust and step-up

The historical Auth 0.4.0 release introduced the forward `0.3.0 -> 0.4.0` credential/settings upgrade.
Install through ModuleUpdates. Existing password hashes retain their values and
start their password age at the upgrade; hashes cannot prove previous complexity.
No dependency or service is added. Fortify's installed Google2FA engine generates
and checks TOTP; native Laravel hashing, encryption, cookies and CSRF remain in use.
Package routes remain disabled. Entry/restricted screens use the [Auth UI contract](entry-ui.md); optional own-account
management uses the [account UI contract](account-ui.md).

Use the current [Auth module and API versions](README.md); the edge above
identifies retained installation history.

## Password policy and recovery

All new passwords, including registration, invitation setup, own changes and reset,
use the same source-owned policy. Length defaults to at least 12 characters and
never exceeds 72 bytes (bcrypt boundary); NUL is rejected. Mixed case, numbers and
symbols are independently configurable. Complexity affects passwords chosen after
publication; a stored hash cannot be retrospectively validated. Explicit forced
change uses the existing lifecycle restriction. Versioned policy publication and
explicit force-change consequences use the [policy management contract](policies-and-operators.md).

Password history retains up to `password_history` previous hashes (default 5,
0–24), plus comparison against the current hash. History is private credential
storage, never Audit metadata or a public value. Zero retains no previous hashes;
reusing the current password is still refused. `password_expiry_days` defaults to
30 days, with range 0–3650; 0 explicitly disables periodic expiry. Current policy/time is checked at ingress, so an
expired or forced password restricts ordinary operations immediately.
The per-account `password_change_required` flag forces a one-time change; it does
not enable or disable periodic expiry for that account. Complete
required 2FA before changing it. Verification, factor enrollment/challenge, session
inspection/revocation, activity and password change are explicit supporting routes;
no other module can opt out by marking its endpoint Restricted.

Own password change requires a fresh consumed password confirmation for
`runa.auth.self.password`. Only the restricted Auth password-change screen
completing login supplies current_password directly. No password-change-specific
factor option remains. The optional
`sessions` input is `keep_all`, `revoke_others` or `revoke_all`, defaulting to
`password_session_policy` (`revoke_others`). All choices invalidate trusted devices,
old step-up proofs and outstanding entry tokens. `revoke_all` requires login again.
The retained current session keeps its existing authentication/absolute deadline.
Initial invitation setup does not force another password change or issue a session.

Anonymous forgot-password accepts an identifier and always returns accepted for
well-formed input, including unknown/ineligible accounts. Only an eligible account
with an existing password receives a queued link. Reset uses a 256-bit random
secret, hashed equality storage, `reset_minutes` (default 30, range 5–60), current
recipient and security generation, and locked single-use consumption. It sets the
new password and revokes all sessions/trust atomically with Audit. It neither logs
in, verifies an email nor removes any enrolled/required second factor. Links use
POST bodies after fragment consumption, like invitations; never put them in server
query strings. SMTP failure requires explicit resend, with the existing single
attempt delivery contract. Administrator reset is email-only; there is no
administrator-set or visible temporary password.

## Factors and enrollment

`totp_enabled` and `recovery_codes_enabled` default true; `email_otp_enabled`
defaults false. They independently control method availability. The required policy
`two_factor_required` is `optional` (default), `any`, `totp` or `email`.
Configuration refuses a required method whose transport is disabled, or `any` with
no enabled method. Optional accounts with an enrolled permitted factor still need
that factor on a new login. `any` permits the account's enrolled enabled choices.
Required TOTP accepts TOTP or its single-use recovery codes, never password or
email OTP. An account without a suitable method gets
`two_factor_enrollment_required`; an enrolled account awaiting confirmation gets
`two_factor_required`. The native session is restricted until completion. These
restrictions supplement independent lifecycle/password/verification checks.

Post-login enrollment requires a fresh password confirmation for the exact
`runa.auth.self.security.<operation>` action. Required enrollment during login
uses the current password on the Auth login-completion screen. Starting TOTP returns a secret, otpauth URI and `qr_code` SVG data URI only in
the authorized no-store response. BaconQrCode, already installed through Fortify,
renders the same URI locally with black/white contrast and a quiet margin. No
external QR service, separate image request or persisted QR file is involved. The encrypted pending secret expires after ten
minutes. Confirmation requires its six-digit `enrollment_code`; only then does it
become active. The first eight random 128-bit recovery codes are returned once;
only their SHA-256 hashes persist. Rotation requires its own fresh password confirmation, replaces all older codes and returns the new values once. Disabling
a method requires password confirmation and is refused while policy requires 2FA. Existing
material remains retained when a policy disables a method; it cannot authenticate
while disabled. There is no WebAuthn or separate QR image generation endpoint.

TOTP permits a one-step clock window and persists the exact accepted counter under
the user lock. Its next accepted counter must be newer, including across workers,
login and enrollment. Database rollback also restores replay state. Recovery
consumption and Audit are in that same transaction. Codes never appear in models'
public projection, module APIs, queue payloads, logs or Audit fields.

Email enrollment requires password confirmation and an already verified primary
address; enabling alone does not confirm the session. Email OTP is a six-digit
random value, privately encrypted only while delivery is pending. Equality uses a
keyed HMAC bound to its token UUID; it is never an offline-searchable plain numeric
hash. Tokens bind current identity, recipient, security generation and session-ID
hash. `email_otp_minutes` defaults to 5 (range 1–10). Reissue invalidates the previous
email OTP. Consumption is single-use and refuses expiry, foreign sessions and stale
generations. Delivery rechecks current account, enrollment, policy and live session.
Mail uses the configured application origin and private single-attempt job; the code
is in the email body, never a URL. No actual OTP enters the job envelope.

Unsafe security operations share an account-wide fixed five-minute window, capped
by `security_attempt_limit` (default 10, range 1–30), across sessions/workers. The
targeted [entry/proof request cap](throttling.md) also applies before controllers
for its declared operations, without counting ordinary account/preferences work. Failed attempts commit their count
before a generic 422 `security_proof_invalid`; repeating a blocked request never
extends the window or permanently locks/deactivates the account. Independent
`security.refused` Audit records contain no submitted secret. More specific password
validation/history failures remain safe 422 responses. All writes and successful
security history roll back together on Audit failure.

Enrollment completion, method changes and recovery-code rotation revoke other
sessions, all trust and old step-up proofs. TOTP confirmation proves that factor
for the current session; email enablement requires a subsequent OTP challenge.
Privileged factor reset clears enrollment/recovery material and revokes every
session/trusted device and pending token. New login must enroll a method allowed
by current policy: mandatory TOTP cannot become an email/password bypass.

## Trusted devices

The default account view groups trust under Two-factor authentication. New trust
requires a usable enrolled factor and a fresh factor proof; the UI disables issuance
before enrollment and the backend independently refuses it. Management and factor
actions use the shared dialog/focus/ephemeral-draft contract in [account UI](account-ui.md).

`trusted_devices_enabled` defaults false; `trusted_device_days` defaults to 30
(range 1–90). Issuance requires password confirmation for `runa.auth.self.trust_issue` and
a permitted factor already verified in the current login session. It never asks
for another code outside login. Security status exposes trusted_device_eligible
so the account UI omits issuance before that login prerequisite is met. The native
encrypted `runa_trust` cookie is host-only, HttpOnly, SameSite=Lax and Secure in
production. Only a SHA-256 equality hash, UUIDv7, user FK, security generation,
verified method, creation/expiry/revocation and last-use timestamps, plus nullable
allowlisted browser/platform labels persist. No raw User-Agent, IP address or
hardware fingerprint is stored for this presentation. Labels describe the browser
at issuance, may be unavailable or inaccurate and never establish trust identity.
Last use records successful issuance or login use, never list reads. The original
CREATE migration owns this unpublished schema; development rebuilds apply it.

Responses never return its secret/hash. The Auth-owned guarded `AuthSecurity` API 1.2.0
`trustedDevices(string $publicId, ?string $before = null): array` and own
`GET trusted-devices` return bounded rows with the complete shape
`{public_id:string, browser:?string, platform:?string, current:bool,
created_at:?string, last_used_at:?string, expires_at:string}`. The implementation
lives in Auth Actions/TrustedDevices and its public contract in Contracts/AuthSecurity;
Admin consumes the guarded API, while authenticated self-service uses the owning
HTTP entrypoint. `current` compares the request's private cookie hash only within
the authorized target's records; it never exposes a token or selects authority.

At most 20 unrevoked devices remain per account;
issuance revokes the oldest excess rows.

Trust may satisfy ordinary login 2FA when its method remains enrolled and permitted
by policy. Expiry, stricter lifetime, revocation or generation changes invalidate it.
It does not extend session deadlines, authenticate without the password, satisfy
step-up or restore revoked sessions. Revoking trust prevents future use; separately
revoke an already admitted session when needed. Password change, lifecycle blocking,
privileged recovery and factor changes invalidate trust. Own/admin list/revoke
uses bounded public UUIDv7 cursors. Disabling trust preserves its technical rows;
cleanup deletes expired/revoked technical records, never Audit/password history.

## Shared step-up contract

Resolve `AuthStepUp` API version `2.0.0` through ModuleApis. The consuming server
chooses the action, bounded TTL and reuse policy. Confirmation accepts only action
and current_password, rechecks an unrestricted session and stores no factor mode
or credentials. It binds identity, session hash, security generation, action and
captured expiry. Login, remember and trusted-device timestamps never replace it.
`step_up_seconds` defaults to 300, range 1–900.

Call assertVerified(action, ttlSeconds: 300, everyAction: false) inside the same
owning default transaction before mutation. Missing/stale proof yields
403 step_up_required. everyAction=true consumes proof atomically with the mutation
and Audit; rollback restores it. Idle protected-place access remains independently
renewable only by real foreground activity. Jobs/CLI do not inherit browser proof.
See the exact public signatures and composition below.

`AuthSecurity` API `1.2.0` provides `requestPasswordReset(publicId, expectedVersion)`,
`resetFactors(publicId, expectedVersion)`, `trustedDevices(publicId, before)` and
`revokeDevice(publicId, deviceId)`. Versioned all-device revocation is
`revokeDevices(publicId, expectedVersion)`, with the same trust grant and owning
transactional Audit. Dedicated permissions are respectively
`runa.auth.passwords.reset`, `runa.auth.two_factor.reset` and
`runa.auth.trust.manage`. Reset mutations additionally require the neutral authority
invariant and every-action password confirmation, keyed `runa.auth.password.admin_reset`
or `runa.auth.two_factor.reset`, in the caller's transaction. The integrated Permissions participant enforces the last-eligible-superadministrator invariant; missing or unavailable integration denies the operation.
Missing/stale reset targets return false. An ineligible password-reset target throws
`IdentityConflict('password_reset_unavailable')`; Admin retains that safe reason,
including the transaction rollback and unconsumed proof. No password or submitted
identifier appears in this refusal.
Infrastructure emergency recovery uses its separate guarded workflow.

## Named HTTP surface

All paths have prefix `/api/runa/auth/`, native CSRF and no-store responses. Route
names have prefix `runa.auth.`. Public password recovery creates no session.

| Method | Path                     | Route suffix             | Input/result                                                                                  |
| ------ | ------------------------ | ------------------------ | --------------------------------------------------------------------------------------------- |
| POST   | password/forgot          | password.forgot          | identifier; generic accepted                                                                  |
| POST   | password/reset           | password.reset           | token/password/confirmation; no login                                                         |
| POST   | password/change          | password.change          | password/confirmation, optional sessions; password proof (restricted login: current_password) |
| GET    | security                 | security.status          | own required/enrolled/enabled methods; no secrets                                             |
| POST   | security/totp-begin      | security.totp_begin      | password proof; restricted login: current_password; secret/URI once                           |
| POST   | security/totp-confirm    | security.totp_confirm    | enrollment_code and password proof (restricted login: current_password); recovery codes once  |
| POST   | security/email-enable    | security.email_enable    | password proof; restricted login: current_password                                            |
| POST   | security/email-send      | security.email_send      | own session-bound OTP delivery                                                                |
| POST   | security/challenge       | security.challenge       | method and code; confirms current session                                                     |
| POST   | security/disable         | security.disable         | target and password proof                                                                     |
| POST   | security/recovery-rotate | security.recovery_rotate | password proof; new codes once                                                                |
| POST   | trusted-devices          | trust.issue              | password proof and previously factor-verified login; private cookie issuance                  |
| GET    | trusted-devices          | trust.list               | optional before UUIDv7 cursor; own bounded rows                                               |
| POST   | trusted-devices/revoke   | trust.revoke             | public_id; own device only                                                                    |
| POST   | step-up                  | step_up.confirm          | action/current_password; legacy requirement/method/code prohibited                            |

Private email changes, profile/avatar ownership and forced reverification use the
[profile contract](profile.md). Password replacement also cancels pending address
requests/links. Step-up follows current verification policy; explicit forced
reverification still prevents it until the address is verified.

## Protected-section idle access

Auth owns renewable section access separately from fixed-age operation proofs.
The delivered runtime implementation remains in Auth's Actions/StepUp, guarded by
`AuthStepUp` API 2.0.0. Public server consumers resolve it through ModuleApis and call
`assertActive(string $action, int $ttlSeconds = 300): bool`
or `activity(string $action, int $ttlSeconds = 300,
bool $renew = false): array{remaining_seconds: float, heartbeat_seconds: float}`
inside their default-connection business transaction. These are browser-session
APIs, not CLI proof inheritance. Owners choose unique section action keys,
and TTL (1–900 seconds) in trusted source; never accept them from HTTP
input. Check owning grants, record policies and availability in that transaction.
A named authenticated GET endpoint calls activity without renewal; its CSRF-protected
POST counterpart passes renew true after the same owning authorization checks.
The request body does not carry policy or client timestamps. Invalid action throws InvalidArgumentException; invalid TTL or missing owning
transaction throws LogicException. Native guard failures propagate as
OperationRejected; missing/expired proof is HTTP 403 step_up_required.

Confirmation uses the existing credential endpoint and binds the same user/native
session/security generation/action. Idle deadlines start at confirmation;
activity extends only still-valid access under the minimum captured, current policy
and caller TTL. Reads and assertActive do not renew. Expiry, consumed proof, revoked
session, restricted identity, or principal mismatch refuses access;
activity cannot restore it. The original verified_at/expires_at remain unchanged,
so fixed-age and every-action consumers never acquire freshness from section use.
Idle activity is technical session metadata, without per-keystroke Audit; actual
confirmation, proof consumption and protected business writes retain native Audit.

Public `ProtectedActivityStatus` has numeric `remaining_seconds` (0–900, exclusive
zero) and `heartbeat_seconds` (0–30, exclusive zero). A bootstrap may supply this
status to avoid an extra initial GET; its reads never renew.

The public frontend facade `@/runa/auth` supplies
`useProtectedActivity(options: ProtectedActivityOptions): { refresh(): Promise<void> }`.
Options are `endpoint: string` (shared HTTP client path),
`identity: () => string | undefined` (admitted account/native-session identity),
`active: () => boolean`, optional
`initial: () => ProtectedActivityStatus | undefined` (owning bootstrap status), `root: () => HTMLElement | undefined`,
`onExpired: (cause: unknown) => void`. The controller belongs to its Vue effect scope.
The root covers the protected presentation. For a full-document section use
`document.documentElement`, including teleported dialogs; embedded sections keep
portals inside their root. Public composition must keep this
controller mounted and pass the current protected root; replacement presentation
must not copy activity/security logic. `refresh` reads current server status without
renewing it, including after credential confirmation or successful navigation.
Trusted clicks, pointer presses, typing, wheel and touch scrolling inside that
visible root count; programmatic scrolling never counts.
pointer motion, focus, visibility, background requests and synthetic events do not.
Activity sends at most one request per server heartbeat interval (at most 30 seconds,
shortened for smaller TTL), with one trailing batch of real input. No idle heartbeat
or business write replay runs. Hidden documents, scope disposal, access loss and
identity changes cancel pending activity and fence late responses. Expiry rechecks
the server without renewal so another tab's real activity can be respected.

Admin consumes action `runa.admin.enter`, password, 300 seconds. Other application
sections implement their own guarded endpoints and use this same controller;
activity outside their root never extends their access. The Admin document, navigation and its dialogs form one section while its
controller is admitted and the current route belongs to /admin. Embedded sections
choose their own narrower root.

Example for an application-owned reports section (inside its normal authenticated,
authorized default-connection transaction):

```php
$access = $apis->resolve('runa.auth', AuthStepUp::class, '2.0.0');
$access->assertActive('app.reports.enter', 300);
// In the named activity endpoint, after the same current reports permission check:
return $access->activity('app.reports.enter', 300, $request->isMethod('POST'));
```

```ts
useProtectedActivity({
  endpoint: '/reports/activity',
  identity: () => admittedSessionIdentity.value,
  active: () => reportsAdmitted.value,
  root: () => reportsRoot.value,
  onExpired: clearReportsAndRequestProof,
});
```

No section gains authority from the activity endpoint or its reported deadline.
The server cannot prove a human gesture from HTTP; frontend trusted-event filtering
prevents accidental/background renewal, while the server exclusively enforces
current authorization and refuses renewal after expiry.

## Password confirmation after login

Auth owns password-only confirmation for sensitive operations and protected places.
Second-factor challenges belong only to login, including restricted login completion;
verifying a new authenticator's enrollment code remains setup validation. No
post-login operation may request a second factor, even when login 2FA is enabled.
Use the existing AuthOperationProofPage with the admitted session owner and keep
operation parameters/drafts mounted while showing that full-page presentation.
Inline/modal proof forms are retired. Cancel performs no domain mutation.

Native AuthStepUp 2.0.0 is resolved through the guarded public Auth facade. Its
password-only signature removes the obsolete requirement argument:
`assertVerified(string $action, int $ttlSeconds = 300, bool $everyAction = false): bool`,
`assertActive(string $action, int $ttlSeconds = 300): bool` and
`activity(string $action, int $ttlSeconds = 300, bool $renew = false): array`.
The activity result remains `{remaining_seconds: float, heartbeat_seconds: float}`.
Implementation remains in Auth backend Contracts/Actions/Proxies/Definitions and
ships in the normal backend runtime. Confirmation transport accepts action and
current_password only. Proofs remain bound to user, session, security generation
and action, with credentials absent from stored proof rows. Native writes consume
fresh operation proof in the same transaction; protected-place access retains
its separate bounded idle renewal. Frontend confirmation is never authorization.

# Passwords, second factors, trust and step-up

Auth 0.4.0 adds the forward `0.3.0 -> 0.4.0` credential/settings upgrade.
Install through ModuleUpdates. Existing password hashes retain their values and
start their password age at the upgrade; hashes cannot prove previous complexity.
No dependency or service is added. Fortify's installed Google2FA engine generates
and checks TOTP; native Laravel hashing, encryption, cookies and CSRF remain in use.
Package routes remain disabled. Entry/restricted screens use the [Auth UI contract](entry-ui.md); optional own-account
management is separate.

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
0 (disabled), with range 0–3650. Current policy/time is checked at ingress, so an
expired or forced password restricts ordinary operations immediately. Complete
required 2FA before changing it. Verification, factor enrollment/challenge, session
inspection/revocation, activity and password change are explicit supporting routes;
no other module can opt out by marking its endpoint Restricted.

Own change always requires `current_password`. `password_change_factor` defaults
to false; enabling it additionally requires a fresh enrolled permitted factor in
the same request. It never treats device trust as a fresh proof. The optional
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

Enrollment requires the current password and a fresh existing permitted factor
when one is enrolled. Starting TOTP returns a secret and otpauth URI only in the
authorized no-store response. The encrypted pending secret expires after ten
minutes. Confirmation requires its six-digit `enrollment_code`; only then does it
become active. The first eight random 128-bit recovery codes are returned once;
only their SHA-256 hashes persist. Rotation requires the same current-password and
factor proof, replaces all older codes and returns the new values once. Disabling
a method requires these proofs and is refused while policy requires 2FA. Existing
material remains retained when a policy disables a method; it cannot authenticate
while disabled. There is no WebAuthn or QR image generation endpoint.

TOTP permits a one-step clock window and persists the exact accepted counter under
the user lock. Its next accepted counter must be newer, including across workers,
login, enrollment and step-up. Database rollback also restores replay state. Recovery
consumption and Audit are in that same transaction. Codes never appear in models'
public projection, module APIs, queue payloads, logs or Audit fields.

Email enrollment requires the current password and an already verified primary
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
source cap also applies before controllers. Failed attempts commit their count
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

`trusted_devices_enabled` defaults false; `trusted_device_days` defaults to 30
(range 1–90). Issuance requires a fresh permitted second-factor proof. The native
encrypted `runa_trust` cookie is host-only, HttpOnly, SameSite=Lax and Secure in
production. Only a SHA-256 equality hash, UUIDv7, user FK, security generation,
verified method, creation/expiry and revocation timestamps persist. Responses
never return its secret/hash. At most 20 unrevoked devices remain per account;
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

Resolve `AuthStepUp` API version `1.0.0` through ModuleApis. The consuming server
chooses its action key, `password`, `second_factor` or `both`, TTL and reuse policy;
never take these requirements from a client or assume a menu permission proves it.
Confirmation checks actual credentials/factors again, even on a trusted device.
It requires a fully unrestricted session. `step_up_seconds` defaults to 300, range
1–900. The server proof binds identity, tracked native session hash, security
generation, exact action, requirement, verified factor method and captured expiry.
A `both` confirmation satisfies either single requirement; a password-only proof
cannot satisfy `both`. Login/remember/trust timestamps are never step-up proofs.

Call `assertVerified(action, requirement, ttlSeconds: 300, everyAction: false)`
inside the same default PostgreSQL business transaction, before mutation, using
the common registry/authority lock order. It requires the current request session
and trusted principal, rechecks account/session state and bounds age by both the
captured expiry and stricter current/caller TTL. A previously accepted factor must
still satisfy current method policy; changing to mandatory TOTP invalidates an
email-based second-factor proof. Missing or mismatched proof yields
403 `step_up_required`. No secret or opaque proof is exchanged across the API.
TTL mode permits recent reuse for that action. `everyAction: true` locks and consumes
the proof atomically with the business change and Audit; rollback restores both.
It cannot authorize a second committed action. The next action must reconfirm.
Cleanup bounds expired proof storage. Jobs/CLI cannot inherit browser proof state.

`AuthSecurity` API `1.0.0` provides `requestPasswordReset(publicId, expectedVersion)`,
`resetFactors(publicId, expectedVersion)`, `trustedDevices(publicId, before)` and
`revokeDevice(publicId, deviceId)`. Dedicated permissions are respectively
`runa.auth.passwords.reset`, `runa.auth.two_factor.reset` and
`runa.auth.trust.manage`. Reset mutations additionally require the neutral authority
invariant and every-action `both` step-up, keyed `runa.auth.password.admin_reset`
or `runa.auth.two_factor.reset`, in the caller's transaction. Until Permissions
and the trusted invariant are integrated, administrative operations remain denied.
Infrastructure emergency recovery uses its separate guarded workflow.

## Named HTTP surface

All paths have prefix `/api/runa/auth/`, native CSRF and no-store responses. Route
names have prefix `runa.auth.`. Public password recovery creates no session.

| Method | Path                     | Route suffix             | Input/result                                                                |
| ------ | ------------------------ | ------------------------ | --------------------------------------------------------------------------- |
| POST   | password/forgot          | password.forgot          | identifier; generic accepted                                                |
| POST   | password/reset           | password.reset           | token/password/confirmation; no login                                       |
| POST   | password/change          | password.change          | current_password/password/confirmation, optional sessions/method/code       |
| GET    | security                 | security.status          | own required/enrolled/enabled methods; no secrets                           |
| POST   | security/totp-begin      | security.totp_begin      | current_password, existing method/code; secret/URI once                     |
| POST   | security/totp-confirm    | security.totp_confirm    | current_password/enrollment_code, existing method/code; recovery codes once |
| POST   | security/email-enable    | security.email_enable    | current_password, existing method/code                                      |
| POST   | security/email-send      | security.email_send      | own session-bound OTP delivery                                              |
| POST   | security/challenge       | security.challenge       | method and code; confirms current session                                   |
| POST   | security/disable         | security.disable         | current_password/method/code/target                                         |
| POST   | security/recovery-rotate | security.recovery_rotate | current_password/method/code; new codes once                                |
| POST   | trusted-devices          | trust.issue              | method/code; private cookie issuance                                        |
| GET    | trusted-devices          | trust.list               | optional before UUIDv7 cursor; own bounded rows                             |
| POST   | trusted-devices/revoke   | trust.revoke             | public_id; own device only                                                  |
| POST   | step-up                  | step_up.confirm          | action/requirement, current_password and/or method/code                     |

Private email changes, profile/avatar ownership and forced reverification use the
[profile contract](profile.md). Password replacement also cancels pending address
requests/links. Step-up follows current verification policy; explicit forced
reverification still prevents it until the address is verified.

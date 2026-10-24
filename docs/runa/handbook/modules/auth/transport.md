# Auth entry transport

Auth 0.2.0 adds the explicit transactional `0.1.0 -> 0.2.0` edge after initial
identity installation. Its additive migration creates owned `runa_auth.entry_tokens`
with bigint identity, UUIDv7, same-module RESTRICT user FK, hashed single-use secret,
UTC deadlines, revocation generation and private delivery state. No default user,
credential or administrative authority is installed. Existing stored settings gain
only missing immediate defaults under the reserved updater; retained values stay
unchanged, revision advances once and synchronous `settings.upgrade` Audit persists
in that transaction. Matching installed versions remain no-ops.

## Native primitives and browser transport

Fortify **1.36.2** is pinned in the platform Composer declaration and composed lock.
It supplies the authentication callback, CreatesNewUsers extension binding and
PrepareAuthenticatedSession regeneration primitive. Native Laravel web/session guard
uses Auth's private Authenticatable/MustVerifyEmail model, with public_id as its
identifier. No starter UI, Sanctum, JWT, passkeys or package-provided routes exist.
Fortify routes are suppressed during provider registration, including cold setup.

All API cookie requests use EncryptCookies, AddQueuedCookiesToResponse, StartSession
and ValidateCsrfToken. Bootstrap with `GET /api/runa/auth/csrf` and retain same-origin
cookies; unsafe requests send the decrypted XSRF cookie through `X-XSRF-TOKEN` using
the existing HTTP wrapper. A missing/incorrect proof returns 419
`csrf_token_mismatch`. CSRF also applies to application API writes; there is no
blanket exclusion for anonymous login, registration or one-time token completion.
Health/ready do not resolve account state. Anonymous public APIs do not depend on
Auth readiness merely because its source is registered.

The server-side file session is encrypted, JSON serialized, host-only, HttpOnly,
SameSite=Lax, with production Secure cookies and HTTPS. File storage uses Laravel's
private runtime storage, with a 30-day storage ceiling and expire-on-close ordinary cookie.
Production configuration validation rejects weakened defaults. Infrastructure and
cookie settings live in platform config, with normal application override rules;
registration/token policy belongs to encrypted module settings, not environment
credentials. Auth 0.3.0 supplies [operational session controls](sessions.md): bounded concurrency,
activity/absolute deadlines, optional remembered-cookie persistence, own list/revoke,
protected administrative contracts, technical trust storage and cleanup.
Auth 0.4.0 adds [password recovery/2FA, trust issuance and step-up](security.md).

Login verifies hashes before evaluating all independent account blockers, including
scheduled activation. Unknown, incorrect, pending approval, inactive, deleted,
expired or locked identities get the same 422 `invalid_credentials`; no lifecycle
reason or account existence is returned. Absent/passwordless accounts perform the
same expensive hashing work. Successful admission synchronously records Audit,
invalidates the old session, logs in through the native guard, regenerates ID/CSRF
and binds security_version. Login refusal is independently recorded without an
identifier or credential. Fortify's credential-bearing Failed event is not emitted.
Logout audits the known session subject, clears current-device authentication,
invalidates session and regenerates CSRF. No remember token is created or rotated.

Every supplied authenticated session is checked against its tracked session proof,
activity/absolute deadlines, fresh eligibility and revocation generation before module controllers/FormRequests and ordinary app APIs.
A mismatch/blocker yields 401 `authentication_required`. Pending verification or
forced/expired password or pending second factor yields 403 `account_restricted`
on ordinary operations.
Only Auth's explicit restricted supporting-operation allowlist is admitted; marking
another module endpoint Restricted cannot bypass this boundary. Verification can
remove only its own restriction; other blockers/password restrictions still apply.
The same checks immediately restrict sessions after an authorized reverification
mutation; the administrative policy workflow is assigned to its later point.

Auth responses use no-store, safe `{data: ...}` projections or `{error: {code: ...}}`.
Validation returns bounded field names with fixed messages, never echoed input.
Unexpected Auth failures return `auth_unavailable`; debug SQL/credential exception
text is not rendered. Non-login unsafe entry operations retain the small source cap (30/minute), returning
429 with Retry-After. Login has configurable account/source windows and bounded
temporary locks under the [session/abuse contract](sessions.md), with generic 422
refusals and optional review escalation instead of permanent attacker-triggered locks.

## Named module endpoints

Names are `runa.auth.<operation>`; paths below are under `/api/runa/auth/`.

| Method | Path                  | Operation             | Access/result                                                |
| ------ | --------------------- | --------------------- | ------------------------------------------------------------ |
| GET    | entry-options         | entry.options         | Public; safe form constraints and availability only          |
| GET    | csrf                  | csrf                  | Public; issues same-origin CSRF/session cookies              |
| POST   | login                 | login                 | Public; identifier/password; own UUIDv7/display/restrictions |
| POST   | logout                | logout                | Public; permits clearing stale cookies                       |
| GET    | session               | session               | Restricted; own safe identity/restrictions                   |
| POST   | register              | register              | Public; generic accepted response, no session                |
| POST   | invitations/accept    | invitation.accept     | Public; token/password/confirmation, no session              |
| POST   | verification/complete | verification.complete | Public; token, no session                                    |
| POST   | verification/resend   | verification.resend   | Restricted; own primary only, generic accepted response      |

Input supports exactly the existing complete exclusive naming alternatives and
identifier normalization. Registration and initial invitation passwords use the shared configurable
[credential policy](security.md), defaulting to at least 12 characters and at most 72 UTF-8 bytes without NUL, protecting native
bcrypt's input boundary. The shared policy also enforces configured complexity/history.
Explicitly supplied activation/approval/verification flags are prohibited; unknown
fields are never mass assigned. Known frontend validation and entry UI use the [screen contract](entry-ui.md). No management HTTP endpoints or placeholder pages are added.

## Registration, verification and invitations

| Module setting              | Default   | Bounds/meaning                  |
| --------------------------- | --------- | ------------------------------- |
| public_registration         | false     | Explicit opt-in                 |
| registration_approval       | automatic | automatic or administrator      |
| invitation_minutes          | 1440      | 5–10080 minutes                 |
| verification_minutes        | 60        | 5–1440 minutes                  |
| invitation_reminder_minutes | 0         | 0 disables, maximum 10080       |
| invitation_reminder_count   | 0         | Maximum 10 per invitation chain |

Enabled registration creates a new UUIDv7 identity, native hash and synchronous Audit
atomically. Approval and verification stay independent. An approved registration
queues primary-email verification when required; a pending approval sends no actionable message.
It issues no authenticated session. A uniqueness conflict returns the same 202
accepted payload as a creation; PostgreSQL claims arbitrate races. Disabled
registration returns 403 `registration_unavailable` independently of existence.
Administrator creation still uses the existing guarded AuthLifecycle contract and
common NewIdentity invariants; it creates no password.

Resolve `AuthInvitations` 1.0.0 through ModuleApis. `invite(publicId, expectedVersion)`
issues or manually resends for an eligible passwordless identity; resend rotates
and invalidates prior tokens. `invalidate(publicId, expectedVersion)` revokes pending
invitations. Both require `runa.auth.invitations.manage`, current availability and
the neutral authority participant (default denial until Permissions integration).
Neither returns a token, email, internal ID or model. Before activation/deactivation,
expiry, locks, deletion or pending approval, issuance/delivery/consumption refuses.

Random 256-bit secrets use SHA-256 equality hashes; only a pending private delivery
copy is encrypted with the externally managed Laravel key. Never serialize these
values into ModuleApis, module metadata/events, Audit or queue payloads. The module
job receives only a UUIDv7 token reference. Token acceptance locks the current user
before token, checks expiry/recipient/security generation/current eligibility and
consumes it atomically with password setup or verification and Audit. Concurrent
replay loses; failure rolls everything back. Initial password setup clears the
password-change restriction and revokes older generations; completion never logs
in or bypasses required 2FA. There is no administrator-visible temporary password.

## Queue, mail and reminders

Token, encrypted module work, database queue envelope and Audit commit together.
Only committed work can execute through ModuleWork's normal availability/source
checks. `entry.deliver` rechecks current account/token predicates before SMTP. It
persists one attempt and destroys the recoverable copy before submission, then
holds registry/authority/user/token locks through the bounded SMTP handoff so a
concurrent lifecycle change cannot race actionable delivery. Transport errors are
replaced with fixed safe messages. A crash/ambiguous SMTP result never automatically
resends that secret; explicitly resend to rotate it. SMTP acceptance is not proof
of inbox delivery. This is at most one SMTP attempt per token, not guaranteed mail
transport exactly-once delivery. Normal invitation/verification state remains usable
until consumption, invalidation or expiry; Audit history is never cleaned here.

Mail uses the configured existing Laravel mail adapter and Polish entry copy.
Links use validated configured app.url, never request Host. The secret is in the
fragment (`/auth/invitation#token=...` or `/auth/verify-email#token=...`), which is not
sent to access logs. The [Auth Vue screen](entry-ui.md) consumes and removes the fragment and POSTs
the proof only after confirmation; no GET consumes a token.
Credential security mail always targets the current primary email. The [profile address contract](profile.md) separately permits new/secondary-address verification and mandatory captured-old-primary change notices. Configurable localized
security delivery and private account preferences use the [preference contract](preferences.md) and [account UI](account-ui.md).

`runa:auth:entry.remind` is a declared operational command that enqueues bounded
reminder work; its declared schedule runs each minute with overlap protection.
When enabled, it considers up to 100 sent pending invitations and rechecks current
policy, count, expiry, identity/recipient/security generation under the same lock
order. Rotation consumes one reminder allowance and makes old actionable links
invalid; repeated work sees the invalidated source token. It never reminds deleted,
inactive, otherwise blocked, consumed, invalidated or expired invitations. Manual
resend starts a new chain. No reminder is enabled by default.

Full concurrency, cookie/browser, module update/acquisition, production image and
security acceptance belongs to final verification. The focused owner AuthEntryTest
also prepares replay/rollback, enumeration, stale delivery, deadline, real CSRF and
encrypted-settings upgrade regressions. This guide describes the current backend contract; entry screens are documented
separately and do not claim final release acceptance.

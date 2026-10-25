# Private account, addresses and avatar

Auth 0.5.0 adds the additive `0.4.0 -> 0.5.0` profile/settings upgrade and requires
Audit `^0.3.0` for its bounded own-history contract. Run normal fenced ModuleUpdates;
do not run module migrations independently. Existing identity/lifecycle, entry,
session and credential API signatures remain compatible. The private [account UI](account-ui.md) consumes this backend capability. No public
profile is supplied.

## Own-account transport

All paths below are relative to `/api/runa/auth/`, use the normal cookie/CSRF
transport and named module routes, and return private no-store responses. Except
`email/complete`, they require an unrestricted current tracked session. Submitted
user IDs never select another account on own endpoints. JSON responses use the
existing `data` envelope. Known validation failures are 422, stale versions and
identifier conflicts are 409, disabled/readonly operations are 403; unavailable
Audit rolls mutations back and returns safe 503. Session loss uses established 401.

| Method | Path                     | Operation / input                                                    |
| ------ | ------------------------ | -------------------------------------------------------------------- |
| GET    | profile                  | `profile.show`; own private projection                               |
| PATCH  | profile                  | `profile.edit`; version, complete naming alternative, optional login |
| POST   | profile/email            | `email.change`; version, email, current_password                     |
| POST   | email/complete           | `email.complete`; token in POST body; public single-use redemption   |
| POST   | profile/secondary-emails | `secondary.add_own`; version, email                                  |
| PATCH  | profile/secondary-emails | `secondary.change_own`; version, address_id, email                   |
| DELETE | profile/secondary-emails | `secondary.remove_own`; version, address_id                          |
| GET    | profile/avatar           | `avatar.read`; own image bytes                                       |
| POST   | profile/avatar           | `avatar.upload`; multipart version and avatar                        |
| DELETE | profile/avatar           | `avatar.remove`; version                                             |
| GET    | profile/history          | `profile.history`; kind=login/account, optional before UUIDv7        |
| POST   | profile/lifecycle        | `profile.self_lifecycle`; version, operation=deactivate/delete       |

`profile` returns public_id, version, display_name, the exclusive name or
first_name/surname representation, login, primary email/verification, secondary
public_id/email/verified_at rows, initials, has_avatar and current capabilities.
It never returns internal IDs, private storage paths, credentials or tokens.
Name/login edits are complete replacements: supply either name or both first_name
and surname, plus the desired login (null clears it). Naming/normalization rules
match identity creation. Login-only installations cannot clear login. Self naming,
login and primary-email edits default allowed and have separate
`own_name_editable`, `own_login_editable`, `own_email_editable` switches.
Optimistic version checks run under the account lock. No-op edits retain version
and do not manufacture history. Names, addresses and login values are absent from
these manual Audit payloads.

## Primary address change and verification

An own change verifies the current password and checks version before issuing
private new-address verification. The old primary remains authoritative for login,
recovery and security until completion. `email_change_old_confirmation` defaults
false; when enabled a second independently single-use old-address link must also
complete. Either order works; replay does not complete another side. Each link and
its owning request expire after `email_change_minutes` (default 60, range 5–1440).
A new request cancels the former request; resubmit with the latest version to resend.
Token equality hashes and encrypted pending delivery material stay in Auth storage.
The `profile.deliver` job contains only a public mail reference. Native single-SMTP-
attempt delivery rechecks current eligibility, address ownership, security generation,
expiry and completion/removal immediately before sending. Failed or ambiguous sends
require explicit reissue. Links use the configured origin and
`/auth/confirm-address#token=...`; fragments never enter server access URLs.
The browser must submit the token in the POST body and clear its fragment when the
later Auth UI consumes it. This backend point does not introduce that screen.

Completion rechecks uniqueness while holding registry, authority and account locks.
Primary claims remain PostgreSQL unique, including retained deleted-account claims.
A competing owner leaves the old primary and unconsumed confirmation intact. The
confirmed new primary becomes verified. Completion notifies the captured old primary;
this mandatory security notice intentionally survives the generation/address change
it reports and later deactivation/deletion, and never routes to a secondary address. It contains no actionable token. Trust, pending credential
links and step-up proofs are invalidated. `email_change_session_policy` captures
keep_all/revoke_others/revoke_all, default revoke_others, when the request is issued.
The initiating opaque session hash selects the retained session; confirmation from
another browser cannot designate another retained session. Revocation cannot revive
an expired session. Password replacement cancels pending address-change requests
and actionable address links as well as credential links.

`email_verification` is all (default), new or disabled. Creation captures whether
that account was created while verification was enabled. New mode requires it for
those accounts; all immediately restricts every unverified account, including
existing sessions, and disabled removes that policy restriction. Explicit
administrative force-reverification remains required independently of this switch.
It removes normal access on the next ingress without revoking the restricted
session; successful single-use verification clears the forced flag. Every other
lifecycle/session/password/factor predicate must still pass. Invitations continue
to prove their addressed primary during initial password setup.

## Protected administrative contracts

Resolve `AuthProfiles` 1.0.0 through guarded ModuleApis. No general administration
page or user-management HTTP CRUD is supplied. Ordinary administrative operations require current Permissions grants and must also pass the neutral AuthorityGuard under the same default PostgreSQL transaction; authority-changing operations remain denied until its protected-authority integration.

| Method                                                       | Permission                     | Additional requirements                                                                   |
| ------------------------------------------------------------ | ------------------------------ | ----------------------------------------------------------------------------------------- |
| edit(publicId, expectedVersion, ProfileChange)               | runa.auth.profiles.manage      | exclusive complete naming/login value                                                     |
| changeEmail(publicId, expectedVersion, email)                | runa.auth.emails.manage        | every-action both-factor step-up for `runa.auth.email.admin_change`                       |
| replaceVerifiedEmail(publicId, expectedVersion, email)       | runa.auth.emails.verify_bypass | configured bypass and every-action both-factor step-up for `runa.auth.email.admin_bypass` |
| requireVerification(publicId, expectedVersion)               | runa.auth.emails.reverify      | every-action both-factor step-up for `runa.auth.email.require_verification`               |
| addSecondary(publicId, expectedVersion, email)               | runa.auth.secondary.manage     | enabled secondary addresses and current ownership rules                                   |
| changeSecondary(publicId, expectedVersion, addressId, email) | runa.auth.secondary.manage     | resets verification; address belongs to target                                            |
| removeSecondary(publicId, expectedVersion, addressId)        | runa.auth.secondary.manage     | address belongs to target                                                                 |
| notificationEmails(publicId)                                 | runa.auth.addresses.read       | verified notification-only secondaries for an eligible account                            |

Normal administrative email change verifies the new address before replacement;
it does not require old-address consent. Bypass defaults disabled through
`admin_email_verification_bypass` and requires its distinct permission. Both
completion paths revoke all sessions/trust, advance security generation and notify
the former primary. All account changes and proof consumption commit with Audit;
a failure cannot consume proof while rolling back the mutation.

## Secondary notification addresses

`secondary_emails_enabled` defaults false. `secondary_self_add`,
`secondary_self_change` and `secondary_self_remove` independently default true
within that enabled feature. The default limit is 5 (`secondary_email_limit`,
1–20). Each address is verified separately; changing it resets verification and
invalidates prior links. Resubmitting the same unverified address through change
reissues a lost link; a verified unchanged address is a no-op refusal. Removal
soft-deletes the address and immediately removes routing; stale links/jobs refuse.
Own address-mail mutations share the bounded security attempt window.

No account may duplicate its primary or its own current secondary. Cross-account
sharing defaults prohibited across primary claims and current secondary addresses.
`secondary_email_sharing` allows secondary sharing, with independent per-account
verification; primary uniqueness always remains mandatory. Policy changes do not
silently erase already stored addresses; later writes and confirmations enforce
current ownership policy. All owning creation/registration/restore/address writes
share registry/authority arbitration; validation alone never grants ownership.

The guarded notificationEmails result contains only verified current secondaries,
not the primary. These addresses are never identifiers for login, reset, invitation,
OTP or other core security mail. A business module must decide sensitivity/disclosure
before requesting notification routing. Category/channel/recipient preferences and
the public delivery declaration layer follow in roadmap point 9.

## Avatar ownership and storage

`avatar_enabled` defaults true and `avatar_editor` selects user, admin, both
(default) or neither. Disabling hides has_avatar and refuses reads/writes while
preserving the stored image; initials remain available for both name alternatives.
Own reads always use the current authenticated identity. Administrative multipart
POST and DELETE `users/avatar` use `avatar.admin_upload`/`avatar.admin_remove`,
require `runa.auth.avatars.manage`, target public_id, version and AuthorityGuard.
They are backend domain operations, with no administration UI.

Uploads use native content/MIME/image/dimension validation for JPEG, PNG and WebP,
1–4096 pixels per dimension, and `avatar_max_kib` (default 2048, range 1–5120).
SVG and unsupported formats are refused. Container boundaries and nonpixel
metadata are inspected for active PHP/HTML/SVG/script markers and appended payloads;
compressed image pixels are never scanned as source text. Ordinary XML/XMP
processing instructions remain valid metadata. This is not an image decoder or a
general-purpose sanitizer; authenticated image content type, nosniff, restrictive
CSP and private storage remain required. The default UI accepts larger local
originals and sends a bounded 512px PNG crop; see [account UI](account-ui.md).
Original names never become paths. The private ModuleStorage namespace uses the
owning account UUIDv7 and a fresh UUIDv7 file name. Image bytes return only through
the authenticated own route, with fixed validated content type, nosniff, restrictive
CSP and private no-store. No public storage URL or Filesystem module is introduced.

Upload requires a root operation. It first commits a private unreferenced cleanup
candidate, writes the blob, then rechecks ownership/policy/version and atomically
publishes its reference with Audit. A failure preserves the prior avatar. Candidates
not published within 30 minutes become disposable; a late publish refuses. Replaced
or removed references become disposable only after successful publication/commit.
Bounded cleanup retries exact private files, never current references (including
disabled avatars), through the existing sessions.cleanup job/schedule. Storage
cleanup failure retains durable candidates and cannot undo a committed profile
change. Expired profile mail/request technical rows are also cleaned; Audit and
secondary-address identity/history are preserved.

## Self lifecycle and history

Self-deactivation and self-soft-delete default disabled, with independent
self_deactivate/self_delete switches. Both require expected version and every-action
password step-up for `runa.auth.self.deactivate` or `runa.auth.self.delete`, plus the
same neutral authority/last-superadmin guard as administration. They revoke all
sessions/trust and write Audit atomically. Only an authorized administrator can
undo them. The pre-Permissions authority refusal remains in force.

`own_login_history` and `own_account_history` independently default false. Enabled
reads call Audit's public OwnHistory 1.0.0 through ModuleApis, binding the trusted
human UUIDv7 to the Auth user entity and a fixed action allowlist. Each append-desc
page has at most 50 entries and an opaque UUIDv7 continuation. A cursor outside
that same account/filter is refused. Only event public_id, UTC occurred_at, action
and outcome return: no actor identities, entity graph, changes, context, execution,
correlation, secrets or general Audit detail/read permission. Administrative
changes about the account can appear without disclosing the administrator.
Account history includes `delivery.preferences_changed`, `preferences.reset_all`
and `preferences.admin_reset` alongside profile/security changes. Previously recorded
events are visible through the same bounded filter. Language/theme edits and ordinary
presentation-only preference changes are not recorded as account-history events.
Unknown-account anonymous login refusals cannot be attributed to a user's history.
No Audit viewer or user-management UI is included.

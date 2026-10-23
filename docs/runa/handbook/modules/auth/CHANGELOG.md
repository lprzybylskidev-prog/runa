# Auth module changelog

## 0.5.0

Add private versioned profile, configurable naming/login self-edits, primary-address
confirmation with optional old consent and captured session policy, independently
verified notification-only secondaries, force-reverification, authenticated private
avatar storage/cleanup, optional guarded self lifecycle and independently enabled
safe own login/account history. Add permissioned AuthProfiles 1.0.0 administrative
contracts with specific both-factor email step-up/bypass permission and old-primary
notice; administrative replacement revokes all sessions/trust. Add profile forward
migration and reserved settings upgrade, current/new-account verification policy,
address ownership arbitration across create/restore/change, and credential-driven
pending-address cancellation. Require Audit ^0.3.0 for OwnHistory; existing public
API signatures and framework API remain compatible. UI/preferences/Permissions
integration remain separate roadmap points.

## 0.4.0

Add shared password complexity/history/expiry/change/recovery, TOTP with transactional
replay counters and hashed single-use recovery, optional session-bound email OTP,
required-method enforcement, revocable device trust and AuthStepUp 1.0.0 with
session/action-bound TTL or every-action proofs. Add protected AuthSecurity 1.0.0
for email-only administrative reset, privileged factor recovery and device reads/
revocation. Reset requires current authority plus every-action both-factor step-up;
ordinary administrative access remains denied until Permissions. Add the forward
credential migration and reserved 0.3.0 -> 0.4.0 settings upgrade. Existing public
identity/lifecycle/invitation/session signatures and framework API stay compatible.

## 0.3.0

Add transactional tracked session admission, unlimited/default or capped concurrency
with oldest eviction, captured absolute/current idle deadlines, optional persistence
of the same remembered session cookie, session proof/generation checks on ingress,
own list/revoke/activity APIs and protected AuthSessions 1.0.0 admin contract.
Add technical session/trust/login-window storage, lifecycle revocation, bounded
cleanup and configurable privacy-preserving account/source abuse windows with
bounded temporary locks and optional review escalation. Add explicit 0.2.0 -> 0.3.0
settings-default upgrade; untracked older sessions require reauthentication.
Existing APIs remain compatible. Trust issuance, passwords/2FA, UI and Permissions
remain separate following points.

## 0.2.0

Add pinned Fortify integration, guarded named cookie/session/CSRF entry transport,
registration with independent approval/verification, single-use hashed invitations
and verification, protected invitation resend/invalidation API 1.0.0 and queued
eligibility-aware single-attempt mail with bounded optional reminders. Add owned
entry-token migration and explicit 0.1.0 -> 0.2.0 settings-default upgrade with
transactional Audit. Preserve existing identity/lifecycle API signatures; add native
verification interface without an unaudited mark-verified shortcut. No Auth UI,
operational session controls, remember-me, password recovery/2FA or Permissions yet.

## 0.1.0

Initial Core identity/lifecycle foundations requiring Audit ^0.2.0. Owned PostgreSQL
users, immutable UUIDv7 references, compatible Authenticatable model, exclusive
naming alternatives, normalized email/login claims with race-safe reservation/reuse
and conflict-safe inactive restoration. Independent current-time eligibility,
versioned audited lifecycle Actions, security revocation generations and neutral
authority integration. Permission-protected AuthIdentities/AuthLifecycle APIs 1.0.0,
initial identifier settings and transactional fenced initialization without users.
No authentication HTTP transport, credential flow, operational sessions or UI yet.

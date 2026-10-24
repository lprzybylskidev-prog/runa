# Auth module changelog

## 0.9.0

Add public welcome/application/account composition, exact named application/account
GETs, session-aware welcome navigation and post-security internal returns. Add
public_welcome_enabled (true by default) and the 0.8.0 -> 0.9.0 defaults upgrade.
Compose profile/sign-out in the existing user dropdown and deliver versioned own
profile/email/avatar/security/sessions/trust/preferences/private-history views.
Add throttled real-activity/idle warnings, 401/restricted reauthentication, safe
account-bound memory drafts with explicit review, and logout/late-response cleanup.
No migration, new dependency, permission shortcut or Admin UI.

## 0.8.0

Add lazy public AuthEntry composition and exact named entry pages, safe public form
options, compatible own-security flags, login/registration/recovery/invitation/
verification/address confirmation and restricted factor/password screens. Reuse
MinimalLayout and shared PL/EN themed form controls. Consume fragments without GET
writes or browser persistence; fence late responses, validate redirects and require
explicit retry after CSRF/conflict errors. Add the source-only 0.7.0 -> 0.8.0 edge;
no migration, settings change, dependency or Admin UI. Account UI remains separate.

## 0.7.0

Add AuthPolicies/AuthBulkSessions 1.0.0, mandatory transactional policy history
and pre-publication step-up authorization, comparison/consequence previews, new-version
restore, future-only/force-change complexity handling and bounded versioned account
lifetime exceptions with immediate expiry/revalidation. Add atomic preview-bound
bulk revocation, idempotent scheduled lifecycle materialization and trusted local
operator initialize/recover/restore-policy commands with private single-use handoff,
external disablement and pending-recovery login fencing. Add the explicit 0.6.0 ->
0.7.0 storage/defaults edge and neutral OperatorAuthority integration contract;
actual Permissions grants and installer wiring remain in their integration point.
No new dependency, infrastructure service or Admin UI.

## 0.6.0

Add AuthPreferences/AuthDelivery 1.0.0, source-owned versioned preference/category
declarations, PostgreSQL account revisions, bounded validation/migration, own
snapshot/write/reset/explicit guest adoption and audited permissioned admin reset.
Adapt display/navigation/statistics/table consumers to an account cache with
revision-ordered writes, guest separation, reset tombstones, late-response fencing
and visible failure/reload feedback. Add email-only category/address routing,
mandatory primary recipients, reference-only security notices, bounded idempotent
expiry reminders, new-browser HMAC inventory and single-attempt mail with a future
trusted delivery adapter/direct fallback. Add forward storage/settings upgrade
0.5.0 -> 0.6.0. Existing API signatures/framework API/Audit requirement stay unchanged;
policy/UI/Permissions integration remains assigned to later roadmap points.

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

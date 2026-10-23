# Auth module changelog

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

# Auth regression coverage

Owner integration regressions live in `src/runa/tests/Feature/AuthIdentityTest.php` and `AuthEntryTest.php`.
Use the guarded maintainer backend runner and explicit filters for a necessary
focused diagnostic. Full identity/concurrency/delivery acceptance belongs to final
roadmap verification. Customer workflows do not depend on this owner harness.

AuthEntryTest additionally prepares operational session proof/deadline/activity,
concurrency-cap eviction, remembered persistence, scoped revocation/Audit rollback,
account/source windows and cleanup regressions. Full acceptance remains the final
roadmap gate. See docs/runa/handbook/modules/auth/sessions.md.

AuthEntryTest also prepares credential-policy/history/expiry, reset replay and
mandatory-TOTP preservation, factor enrollment/recovery/Audit rollback, cross-worker
proof locking, session-bound expiring email OTP, real private trust cookies and
step-up action/session/principal/TTL/single-commit consumption regressions. See
`docs/runa/handbook/modules/auth/security.md`. Full browser, concurrent and delivery
acceptance remains final verification. No production mail is sent by these fixtures.

AuthEntryTest additionally prepares private profile version/policy/naming and
self-history isolation, primary old/new consent/replay/uniqueness/Audit rollback,
secondary per-account verification/sharing/removal and notification-only routing,
admin bypass/permission/both-factor proofs, existing-session reverification,
private avatar MIME/ownership/disable preservation/rollback/cleanup, optional
self-lifecycle proof rollback, private at-most-once recipient-aware mail, exact
expiry, credential cancellation and captured session-policy/worker-lock scenarios.
See `docs/runa/handbook/modules/auth/profile.md`; integrated acceptance is final-only.

AuthEntryTest prepares declared preference persistence/adoption/reset, stale revisions,
account isolation, permissioned typed API, mandatory/invalid routing, reminder
idempotence/obsolescence and single SMTP attempt regressions. Browser-store tests
cover late account replies, logout cancellation, guest separation and rejected writes.
See `docs/runa/handbook/modules/auth/preferences.md`; broad runtime/render/installation
acceptance remains the final roadmap gate.

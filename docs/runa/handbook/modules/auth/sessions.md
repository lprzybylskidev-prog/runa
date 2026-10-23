# Auth sessions and login abuse defenses

Auth 0.3.0 adds the explicit `0.2.0 -> 0.3.0` settings-default upgrade and owned
`sessions`, `trusted_devices` and `login_windows` migrations. Upgrade through the
normal fenced ModuleUpdates transport. Existing untracked 0.2.0 sessions require
login again. No dependency, new service or native Laravel recaller is introduced.
Auth 0.4.0 adds [trust issuance, second factors and reusable step-up](security.md),
with a forward credential migration; session signatures remain compatible.

## Admission, deadlines and revocation

Native web guard and Fortify regeneration remain in use. A successful root admission
locks registry, authority and user, inserts the operational session and writes
`login.succeeded` Audit (or `login.credentials_accepted` while 2FA is pending)
on the default PostgreSQL transaction. Store only the SHA-256
session-ID equality hash, UUIDv7 public reference, user FK, security generation,
remember choice, authentication/activity timestamps, captured absolute deadline and
revocation timestamp. Never return the session-ID hash, internal keys or credentials.
No IP address or user-agent fingerprint is stored in the session history.

Concurrency is unlimited by default. A positive limit evicts the oldest still-live
session by authentication time and internal-ID tie break before admitting a new one.
Admission and eviction roll back together when Audit fails. Registry serialization
also coordinates simultaneous admissions with lifecycle/update operations. Logout
revokes its bound row, records Audit and invalidates native cookie authentication.
Lifecycle generation changes revoke operational sessions/trust atomically; later
activation never restores them. Scheduled eligibility predicates still run at each
ingress. Removing account expiry may restore eligibility, independently of revoked
sessions, inactivity and absolute deadlines.

Every relevant authenticated module/application ingress verifies current eligibility,
security generation, stored public reference, session-ID hash, non-revocation and
both deadlines. A missing/untracked/expired/revoked session returns established 401
`authentication_required`. Restricted accounts may inspect/revoke their own sessions
and send activity alongside the existing session/verification support endpoints;
this does not authorize ordinary operations or another module's Restricted route.

`remember: true` is optional on login. It is effective only when remembered lifetime
is positive. It persists the same encrypted native session cookie across browser
restart; it does not mint a second credential, use `remember_token` or reconstruct a
session from a recaller. Idle timeout continues to apply. Remembered duration is capped by the ordinary
absolute lifetime, including stricter current policy. The captured deadline is
never moved by polling, activity or browser restart. Tightening the corresponding
current absolute/remember policy can expire existing sessions sooner; relaxing it
never extends the captured deadline. Turning remember duration to zero invalidates
remembered sessions. Native file/cookie lifetime is a 30-day storage ceiling; it is
not an authorization deadline. Ordinary cookies remain expire-on-close; remembered
cookies have an expiry. Old cookie replay cannot restore a removed/revoked row.

## Activity and own APIs

All paths are under `/api/runa/auth/`, named `runa.auth.<operation>`, use the existing
session/CSRF middleware and return no-store responses. UI integration follows in
the Auth screen points. No automatic write replay or localStorage drafts exist here.

| Method | Path             | Operation        | Input/result                                                                |
| ------ | ---------------- | ---------------- | --------------------------------------------------------------------------- |
| GET    | session          | session          | Adds own session timestamps and activity policy                             |
| GET    | sessions         | sessions.list    | Optional UUIDv7 `before`; newest 100 live own rows, `meta.next_cursor`      |
| POST   | sessions/revoke  | sessions.revoke  | UUIDv7 `public_id`; generic accepted, foreign IDs have no effect            |
| POST   | session/activity | session.activity | `activity`: typing, click, scroll or touch; current safe session projection |

Clients send a heartbeat only after real interaction and no faster than the returned
`heartbeat_seconds`. GET/polling requests never update `activity_at`. Mouse movement
is not an accepted activity kind. The server coalesces early heartbeats and changes
only activity time, never authentication time or captured expiry. Disabling extension
leaves activity time unchanged. A browser's claim of human interaction is not a
cryptographic proof; the absolute server deadline remains binding for every client.
`warning_seconds`, `idle_minutes` and `extension` are returned for future warning UX.

Resolve protected `AuthSessions` 1.0.0 through ModuleApis for administrative
`listFor(publicId, before = null)`, `revoke(publicId, sessionId)` and
`revokeAll(publicId)`. The list is cursor-bounded to 100 safe rows. Every method
requires `runa.auth.sessions.manage` and current availability/authority. Default
permission denial remains until Permissions integration; no admin HTTP page or
bypass is supplied. Mutation Audit is synchronous, scoped to the subject, and
rolls back revocation on persistence failure. Already revoked/foreign/absent IDs
are harmless no-ops. Session revocation alone does not revoke trusted devices;
account lifecycle and [password/2FA policies](security.md) govern both stores explicitly.

## Settings

These are immediate encrypted module settings, updated with the existing revision/
generation-aware settings workflow. Infrastructure cookie security remains in config.

| Setting                   | Default | Bounds/meaning                                         |
| ------------------------- | ------- | ------------------------------------------------------ |
| session_limit             | 0       | 0 unlimited; 1–100; applied at next admission          |
| session_idle_minutes      | 120     | 1–43200; current policy at ingress                     |
| session_absolute_minutes  | 720     | 1–43200; ordinary absolute lifetime                    |
| session_remember_minutes  | 0       | 0 disabled; otherwise 1–43200                          |
| session_heartbeat_seconds | 30      | 10–300; minimum activity write interval                |
| session_warning_seconds   | 120     | 0–3600; UI warning lead time                           |
| session_extension         | true    | Activity can renew inactivity within absolute deadline |
| abuse_window_seconds      | 300     | 30–3600; fixed counting window                         |
| abuse_account_limit       | 10      | 1–1000; failed attempts per normalized account         |
| abuse_source_limit        | 60      | 1–10000; all login attempts per source                 |
| abuse_lock_seconds        | 300     | 30–3600; bounded temporary lock                        |
| abuse_escalation_limit    | 0       | 0 disabled; 1–1000 failures emits review Audit         |

## Abuse windows and cleanup

Standalone PostgreSQL counters are serialized in admission transactions. Source
counts include successful logins; account counts include failures and are shared
across sources and current identifier aliases through the UUIDv7 identity. Absent
accounts use trimmed lowercase identifier input. HMAC-SHA256 keyed by the external
application key hides raw identifiers/source addresses in technical counters.
Neither fingerprints nor lock reasons/timestamps are returned to anonymous callers.
Attempts rejected by account/source counters use the same 422 `invalid_credentials`
as absent, incorrect or ineligible credentials. Rejected counter state commits;
refusal Audit is independently persisted afterward. Audit unavailability fails closed.
The first attempt reaching a threshold starts a temporary lock. Blocked traffic
never slides its deadline; expired windows restart. No automatic permanent security
lock or lifecycle change is triggered. Optional `login.abuse_escalated` Audit
requests authorized review without making attacker-driven permanent lockout possible.
Choose an escalation threshold within the failure limit to observe it before lock.

The separate pre-controller unsafe-entry cap (30/minute, 429 with Retry-After)
continues on non-login entry operations. Login uses the configurable DB defenses.
`runa:auth:sessions.cleanup` is an operational ModuleWork command scheduled every
five minutes. Each run deletes at most 500 rows per technical table: expired/idle,
revoked or stale-generation sessions, expired/revoked/stale-generation trust, and
expired abuse windows. Native file sessions retain their independent Laravel GC.
Cleanup never queries or deletes Audit history, users or invitation/verification
proofs. Remaining eligible rows are handled by later scheduled batches.

Prepared regressions cover deadline/activity boundaries, remembered-cookie expiry,
oldest eviction, stale/foreign proof refusal, Audit rollback, lifecycle revocation,
window/temporary-lock expiry, safe escalation and cross-connection lock arbitration.
Full parallel/browser/installation/security acceptance remains final verification.

The [profile contract](profile.md) adds captured email-change session policy,
revoke-all administrative replacement, and bounded expired profile mail/request
and unreferenced-avatar cleanup through the same sessions.cleanup work. Current
private avatars, disabled-avatar storage, Audit and address identities are preserved.

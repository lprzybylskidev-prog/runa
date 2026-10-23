# Auth identities and lifecycle

Core `runa.auth` initially ships at independent version `0.1.0`, requiring
`runa.audit ^0.2.0` and framework API `^1.0.0`. Its owned `runa_auth.users`
storage is installed through the standard fenced module updater, after Audit.
The transactional `null -> 0.1.0` initialization creates no user, password or
administrative shortcut. Matching installed versions are no-ops. There is no
Permissions dependency or cross-module SQL, model, relationship or foreign key.

RM-2026-011 point 4 supplies identity/lifecycle foundations. Cookie/CSRF/Fortify
transport, registration/invitations, operational sessions/trusted devices, 2FA,
private profile/preferences, Permissions and Auth screens follow in later points.
There is no usable login endpoint or management UI in this delivery.

## Public values and access

Resolve version `1.0.0` contracts through `ModuleApis`:

```php
use Runa\Modules\Auth\Contracts\AuthIdentities;
use Runa\Support\Modules\Api\ModuleApis;

$identities = app(ModuleApis::class)->resolve('runa.auth', AuthIdentities::class, '1.0.0');
$identity = $identities->identity($publicId);
$eligibility = $identities->eligibility($publicId);
```

`identity` requires `runa.auth.identities.read`; `eligibility` requires
`runa.auth.eligibility.read`. Both require current module availability first.
An unknown UUIDv7 returns null; invalid references are rejected. `Identity`
contains only immutable publicId, displayName, deleted and optimistic version.
Historical lookup includes soft-deleted accounts and never reassigns their history.
No bigint ID, address, login, model, credential, session or proof is exposed.
Eligibility reasons are authorized server information, never anonymous responses.
Default authorization denies these operations until Permissions is integrated.

`AuthLifecycle` version `1.0.0` offers `create(NewIdentity): Identity`, requiring
`runa.auth.users.create`, and `change(publicId, expectedVersion, LifecycleChange)`
requiring `runa.auth.users.manage`. These are administrative backend contracts,
without HTTP routes. A permission does not substitute for the separate authority
invariant participant; the supplied default `AuthorityGuard` refuses mutations.
Owning private identity lookup is independent of protected APIs, so future account
policy/permission resolution need not authorize its own identity recursively.

## Names, identifiers and reservation

Every account has one immutable UUIDv7, bigint identity, mandatory primary email,
UTC timestamps and soft deletion. Supply either `name`, or both `firstName` and
`surname`, never a mixture or incomplete alternative. Name parts are trimmed,
nonempty, free of control characters and at most 160 characters. The consistent
display name is the single name or first name plus surname.

Email is trimmed and lowercased, validated as an ASCII email of at most 254 bytes.
Login is optional, trimmed and lowercased: 1–64 ASCII letters/digits/dots/underscores/
hyphens, beginning with a letter or digit. An at sign is excluded from login, so
`either` lookup can distinguish the namespaces without ambiguous cross-field matches.
There are no provider-specific dot/plus email rewrites. Model serialization exposes
only public_id; default mass assignment is refused. Auth is a ModuleModel with
Laravel's Authenticatable contract/trait and a public_id authentication identifier.
Credential/verification adapters are added with their audited flows later.

Auth declares immediate settings through normal encrypted module settings:

| Key                       | Default | Meaning                                                            |
| ------------------------- | ------- | ------------------------------------------------------------------ |
| identifier_mode           | email   | email, login or either; new accounts require a login in login mode |
| reuse_deleted_identifiers | false   | Whether subsequent deletions release their identifier claims       |

Unique nullable `email_claim`/`login_claim` columns arbitrate concurrent ownership
in PostgreSQL. Non-deleted rows must claim their own normalized identifiers.
By default deletion retains claims. With reuse enabled, deletion releases them;
another creation gets a new identity and no previous business/Audit history.
Policy changes govern subsequent deletions: they neither retrospectively release
reserved identifiers nor reclaim already released ownership. Operators can resolve
a retained reservation deliberately through an authorized restore/delete sequence;
conflicting identifiers must first be resolved through owning account workflows.
Restore reacquires both claims atomically, so a conflict leaves the old account
deleted. Validation checks alone never decide uniqueness. There is no implicit
identifier takeover, account merge, historical rekeying or physical deletion.

## Independent eligibility

Every relevant ingress must evaluate current state and UTC time. Never cache an
initial login result or rely on scheduler cadence. `Eligibility` reports all current
blockers separately: deleted, approval_required, before_activation/inactive,
deactivated, expired, security_locked and attempt_locked. Email verification and
password change remain distinct restricted-flow requirements. `mayAuthenticate()`
requires no blockers; `mayOperate()` additionally requires no restrictions.
Transport must also apply its own current session, 2FA and step-up checks.

Activation before activate_at is denied even when the active flag is true. At
activate_at, scheduled activation becomes effective independently of the stored
active flag. At deactivate_at and expires_at the corresponding blockers apply;
at attempt_locked_until the temporary lock ends. Removing/changing expiry restores
eligibility immediately when every other predicate allows it. Approval, activation,
verification, expiry and both lock types never implicitly change one another.

`LifecycleChange` operations: approve, activate, deactivate, delete, restore,
schedule_activation, schedule_deactivation, set_expiry, set_security_lock,
set_attempt_lock and require_password_change. Date values use canonical
`YYYY-MM-DDTHH:MM:SSZ`; null clears a date. Scheduling accepts future activation/
deactivation only and requires activation before deactivation when both exist.
The two boolean changes require `enabled`. Explicit activate/deactivate and
delete/restore clear schedules. Restore leaves the account inactive. Reactivation
and restoration are administrative operations, including after self changes.

## Atomicity, revocation and recovery

Actions own the default PostgreSQL transaction/savepoint and follow the shared
[authority lock order](../../architecture/module-foundations.md): registry shared
lock/settings reads, authority advisory lock, Auth row, then invariant participant
and any Permissions rows. They recheck expected version under the row lock.
Every meaningful lifecycle change increments version and records one manual Audit
aggregate event on that exact transaction. No-op changes emit no duplicate event.
Audit failure rolls back the identity change, including a caught nested savepoint.
Names, email, login, credentials and tokens are absent from lifecycle Audit payloads.

Deactivate/delete/restore/activate and an applied administrative security lock
advance security_version and erase remember_token. Rescheduling/cancelling an
already reached deactivation also advances that generation, as does changing an
inactive account's activation schedule, so changing dates cannot revive old access.
Future operational sessions
and trusted-device records must bind that generation and reject a mismatch;
reactivation never restores old credentials. This point supplies that persistent
revocation contract; it does not claim operational session/trust storage already
exists. Scheduled deadlines also require current-time ingress checks and later
lifecycle/session orchestration. Temporary locks and expiry stay independent.

Root conflicts and invariant refusals are recorded through Audit's independent
attempt API after rollback. Nested callers must first roll back their root and
record the safe refusal there; an inner savepoint cannot claim independent history.
Unavailable Audit propagates a persistence failure, never a confirmed history claim.
Use owning Actions; direct private model writes/bulk SQL bypass the lifecycle
workflow and are unsupported for application consumers. Delete/restore/forceDelete
model helpers refuse; destructive migration rollback is also refused.

Independent module changes are recorded in [CHANGELOG](CHANGELOG.md). Full tests,
concurrency, installation/delivery and operational Auth acceptance run in the final
roadmap verification after their owning implementation points.

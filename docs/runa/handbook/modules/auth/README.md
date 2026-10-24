# Auth identities, lifecycle and entry transport

Core `runa.auth` ships at independent version `0.19.1`, requiring
`runa.audit ^0.5.0` and framework API `^1.0.0`. Its owned `runa_auth.users`
storage is installed through the standard fenced module updater, after Audit.
The transactional `null -> 0.1.0` initialization creates no user, password or
administrative shortcut. Matching installed versions are no-ops. There is no
Permissions dependency or cross-module SQL, model, relationship or foreign key.

RM-2026-011 points 4–10 supply identity/lifecycle foundations and
[Fortify cookie/session/CSRF transport, registration and invitations](transport.md).
[Operational sessions, remembered persistence and abuse defenses](sessions.md) are
also available. [Password policy/recovery, 2FA, trusted devices and step-up](security.md)
are delivered, along with [private profile, verified address changes, avatar and own history](profile.md).
[Account preferences and security delivery](preferences.md) are delivered.
[Policy history, account exceptions, bulk sessions and operator contracts](policies-and-operators.md) are delivered.
[Entry/restricted screens](entry-ui.md) and [welcome/application/account screens](account-ui.md)
are delivered. [Permissions action grants and introspection](../permissions/README.md),
ordinary assignment/preset/copy and [protected authority](../permissions/authority.md)
are delivered. First-administrator bootstrap grants the actual Permissions system role through the trusted operator adapter; installer invitations and private no-mail CLI setup are delivered.
The explicit [development corpus](../../data/seeding.md#explicit-module-development-contributors) supplies local scenarios, safe account discovery and private initial setup, independently of production initialization.
There is no management UI. Administrative operations require current native grants.

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
Permission checks use the current Permissions grants; missing readiness or grants deny these operations.

`AuthLifecycle` version `1.0.0` offers `create(NewIdentity): Identity`, requiring
`runa.auth.users.create`, and `change(publicId, expectedVersion, LifecycleChange)`
requiring `runa.auth.users.manage`. These are administrative backend contracts,
without HTTP routes. A permission does not substitute for the separate authority
invariant participant; `AuthorityGuard` lazily resolves the trusted Permissions
participant and refuses missing/stale integration. Deactivate/delete exclude the
target from usable administrators; non-removing work uses an empty exclusion set.
Owning private identity lookup is independent of protected APIs, so account
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
The model also implements MustVerifyEmail. Verification writes require the owning
single-use token Action; native direct markEmailAsVerified is deliberately refused.

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
Transport checks session revocation generation, password age and session-specific
factor restrictions; sensitive consumers use the separate step-up contract.

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
Operational sessions and trusted-device rows bind that generation and are revoked
in the same lifecycle transaction. Reactivation never restores them. The
[session/abuse contract](sessions.md) defines proof binding, deadlines, own/admin
revocation, remembered-cookie persistence, heartbeat and cleanup. Trust issuance and 2FA follow the [credential contract](security.md). Scheduled deadlines also require current-time ingress checks;
future lifecycle orchestration does not replace those checks. Temporary locks and
account expiry stay independent.

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

Entry/restricted screens use the [Auth entry UI contract](entry-ui.md); own-account screens and session-expiry integration use the [account UI contract](account-ui.md).

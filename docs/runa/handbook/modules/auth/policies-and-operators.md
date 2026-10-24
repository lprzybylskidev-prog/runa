# Auth policies and trusted operator recovery

Auth 0.7.0 adds the transactional `0.6.0 -> 0.7.0` storage/defaults edge,
AuthPolicies 1.0.0 and AuthBulkSessions 1.0.0. Audit ^0.3.0 and framework API
^1.0.0 remain unchanged. Install through the fenced ModuleUpdates workflow;
ordinary startup does not migrate or initialize an administrator.

## One current policy and immutable history

ModuleSettings remains the sole writable current configuration. Auth stores an
append-only policy snapshot per actual settings revision in `policy_versions`,
including the installation baseline, actor, directional changes and consequences.
It exposes no history update/delete operation. A settings publication, restoration
or upgrade and its history/Audit commit on the same default PostgreSQL connection.
Failure of either required store rolls back publication. The independent settings
observer is not used for mandatory persistence.

An optional framework SettingsAuthorization participant runs after candidate and
revision validation, before the candidate write/reservation fence, in that same
transaction. Auth consumes an actor's every-action both-factor proof for
`runa.auth.policy.publish` there, against the old policy. History persists after
the actual stored write. A refused publication also restores the proof's unused
state. Compensation does not require the user's proof a second time.

AuthPolicies provides bounded history (50 revisions per page), old/new comparison
through `preview`, revision/generation-checked `publish`, historical `restore` and
account exception reads/writes. Restore republishes a snapshot as a NEW revision;
it never clears forced-password flags, restores revoked sessions/trust or reverses
previous effects. ModuleSettings validates restored values against current source
rules. Trusted operator settings writes retain the existing CLI trust boundary;
web/API consumers require `runa.auth.policy.read`, `runa.auth.policy.write` or
`runa.auth.policy.exceptions` as declared. Administrative gateways require current Permissions grants and their independent security predicates. There is no settings administration UI.

`password_policy_effect` defaults to `future_only`. When `force_change` accompanies
an actual complexity change, all non-deleted password-bearing accounts must choose
a new password. The preview reports that count: hashes cannot establish which old
passwords satisfy new complexity. Existing lifecycle/verification restrictions,
current factor requirements and captured session deadlines remain independently
authoritative. A future-only change does not force password replacement.

## Temporary account exceptions

The explicit allowlist contains only `password_expiry_days` (1–3650) and
`session_idle_minutes` (1–43200). Exceptions cannot disable password expiry with
zero, change absolute session deadlines, relax 2FA/step-up, reactivate accounts or
bypass identity/permission rules. Global expiry may still be disabled with zero.

A write requires an authorized actor, fresh both-factor every-action proof for
`runa.auth.policy.exception`, a reason without secrets, an expiry no later than
30 days, the current global policy revision and the last per-key mutation version
(initially zero). Passing null revokes the exception. Versions continue across
revocation/recreation; stale requests cannot overwrite a recreated exception.
Bounded authorized reads expose public identity, key/value, reason, actor, versions,
expiry and revocation. Revalidation on any policy publication conservatively revokes
existing exceptions; an administrator must explicitly reconsider/reissue them.
No policy change silently extends an exception.

Ingress, session projection/cleanup and password-expiry reminder calculation read
current effective values. Expired exceptions stop applying immediately without
waiting for cron. Cleanup preserves sessions covered by a live extension; an
extension never moves the session's captured absolute deadline.

## Bulk session revocation

AuthBulkSessions accepts an immutable SessionSelection for one/selected UUIDv7
accounts or an explicit all-account selection. Preview contains exact unrevoked
session identities/counts and optional trust impact. Execution requires
`runa.auth.sessions.bulk_revoke`, a fresh both-factor every-action proof for the
same action, and the matching preview fingerprint. Re-read account/security/policy
versions and target rows under registry/authority/user locks; changed targets
require a new preview. Other modules do not query Auth tables.

Each operation is atomic and limited to 100 accounts and 10000 total session/trust
rows. All-account mode exceeding these limits refuses before mutation; it never
silently truncates or partially succeeds. The neutral BulkUserSelection bridge calls guarded PermissionsSelections.users
on both preview and execution, requiring current `runa.permissions.users.select`.
Selection intent is never a reusable grant; Auth still resolves current targets
and requires its own bulk permission and both-factor proof. Each target
receives a manual Audit event in the same transaction; Audit failure rolls back
revocations and proof consumption. No management UI is supplied.

## Scheduled lifecycle

`runa:auth:lifecycle.apply` admits a managed job; the minute schedule uses the same
entrypoint. Each run considers at most 100 due accounts and rechecks current dates
and state under registry, authority and user locks. Deactivation takes precedence
when both dates are due; activation preserves a future deactivation date.
Applied transitions clear only consumed schedules, advance security/account
versions, revoke sessions/trust and synchronously audit the transition. Replay of
already materialized dates does nothing. Eligibility still evaluates the actual
current dates before the scheduler runs. The neutral guard now participates in
Permissions' [last-superadministrator invariant](../permissions/authority.md).
Last-admin materialization refusal rolls back that account's changes, records
independent `authority.refused` and continues unrelated due accounts. A due date
still blocks ingress before materialization; inspect future schedule/expiry lockout
consequences and retain the trusted operator recovery path. The trusted OperatorAuthority bridge supplies actual protected-role membership
for first-administrator installation and emergency recovery.

## Operator commands and initialization contract

Access to run the dedicated Artisan commands is trusted operator access. The
customer protects host/CLI access under the [production responsibility boundary](../../production/architecture.md#responsibility-boundary).
No extra recovery container, authority service or separate database credential is
required. Run commands in the configured application PHP environment using the
existing container toolchain; PHP is not required on the host. These native local
commands are not development-only tooling or queued module jobs.

- `php artisan runa:auth:initialize --email=admin@example.test --name='Initial Admin' --output=/private/operator/setup.json --confirm`
- `php artisan runa:auth:recover --user=UUIDV7 --output=/private/operator/recovery.json --confirm`
- `php artisan runa:auth:restore-policy --revision=N --confirm`

Create the private output directory first (mode 700). Paths are absolute in the
PHP process filesystem; a container path is not automatically a host path. Output
must be a NEW regular file in a private directory with resolved, non-symlink path.
The command creates it with mode 600 and refuses an existing file. Account mutation
and writing the small handoff share a database transaction; a file/write failure
rolls back the mutation. Failure removes only that command's newly created file.
Inspect uncertain crash/commit outcomes before retrying; no crash-atomic claim is
made across the filesystem and PostgreSQL.

Stdout/stderr contain only a safe result, never the token. The private JSON holds
the account UUIDv7, random one-time token and 900-second lifetime. Only its SHA-256
hash is stored in Auth. Submit the token/new confirmed password through the existing
no-store CSRF-protected `POST /api/runa/auth/password/reset` body, never a query
string, command argument, issue attachment or transport log. Remove the private
file after handoff/use; do not put it in source control or retrieval. Auth screens
for this handoff are delivered in their later UI points.

Initialization has an installer-callable trusted local OperatorAccounts action and
a persistent singleton ownership record. It creates a new identity and asks the
neutral OperatorAuthority adapter to grant the actual Permissions system role in
that transaction. A matching repeat before password setup rotates only the token
for the same identity; it never grants another role. A different recipient or a
completed password setup refuses ordinary initialization. Initial token redemption
attests the operator-supplied initial email, like invitation setup; it sets no
forced-change flag merely for being a new account. There is no `is_admin` column.

OperatorAuthority is bound to a lazy trusted framework bridge and Permissions'
owned role participant. It validates the default transaction, current enabled
Core source/data and absence of fences. It reads only Permissions-owned tables;
Auth owns its identity, token and singleton state. Initialization commits exactly
one protected membership/revision and authority.initialized event with the Auth
identity/history/token. Audit failure rolls back all writes. Concurrent different
recipients cannot both initialize. Recovery checks retained system membership
without ordinary user grants, eligibility or the ordinary lifecycle guard, so an
inactive/locked administrator or broken account policy remains recoverable.
Source/data/update fences and Audit remain mandatory; repair them first.
Policy restoration uses trusted ModuleSettings and grants no role.

For working mail, use the same initialize command with `--invite --confirm` and
omit `--output`. It queues the existing encrypted Auth invitation flow in the same
transaction, invalidates the unused private setup material, and exposes no token
in stdout/argv/installer state. Invitation redemption installs a password and
attests the supplied email; ordinary login still enforces mandatory factors.
Repeated invitation admission for the same email/login is a no-op while an
unclaimed or successfully sent invitation is live, or after password setup.
An expired/failed-attempt invitation can be reissued explicitly. A different
recipient or lost system membership refuses. Normal private-file initialization
continues to refuse after password setup; it is not a recovery shortcut.
The production installer accepts optional `administrator: {email, name, login?}`
settings, queues setup only after application/TLS/SMTP acceptance and records safe
admission in its existing resumable journal. Omission leaves setup to trusted CLI.
Development and customer clones initialize explicitly using the same local CLI;
ordinary reopen/start/update never issues another administrator.

Recovery targets an existing administrator, including an inactive one, but never
restores a soft-deleted identity. It explicitly reactivates/approves/unlocks the
account, clears activation/deactivation/expiry dates and old factor enrollment,
revokes all sessions/trust/proofs/entry tokens, advances revocation generation and
requires completion using the new token. An account-wide pending-recovery blocker
prevents login with the old password while that token is outstanding. Redemption
rechecks administrator membership, generation, current lifecycle, deadline and
single-use state, and installs a policy-valid new password atomically with Audit.
It creates no session and does not mark an existing unverified email verified.
Normal login then requires current email verification and mandatory factor enrollment
and challenge. Mandatory TOTP is never replaced by a full email/password session.
An expired token leaves the account pending until an operator issues a new one.

Set `AUTH_OPERATOR_RECOVERY_ENABLED=false` in the external Laravel environment to
disable operator initialization, account recovery, token redemption and policy
restoration completely. Rebuild/recreate configuration caches through the supported
deployment procedure. This switch is deliberately outside mutable Auth policy, so
restoring an old policy cannot turn operator recovery back on. A policy restore
creates a new history/Audit version and preserves prior executed security effects.
Module source/storage, update/maintenance fencing and Audit availability remain
required; these commands do not repair missing code or bypass an unresolved update.

Development-only account discovery and private initial password setup are supplied
through the explicit [module fixture workflow](../../data/seeding.md#explicit-module-development-contributors).
The fixture administrator uses this same real initialization/singleton/authority
contract and refuses an installation already owned by another administrator.
It neither resets an existing password nor overrides current security policy.

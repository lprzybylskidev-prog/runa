<!-- runa-knowledge {"purpose":"current_guidance","effective_date":"2026-10-30","applicable_version":"0.7.0","decisions":[]} -->

# Auth administration

Auth 3.0.0 supplies source-owned administration declarations and adapters, consumed
by Admin without a reverse backend dependency. Business behavior remains available
through the owning guarded APIs without Admin. This is unreleased source for
platform 0.7.0; complete browser/security/release verification belongs to
RM-2026-016 point 12.

## Guarded administrative reads

Auth owns public consumption contract `AuthAdministration` API `2.0.0` and its final
readonly DTOs under `Runa\Modules\Auth\Contracts`. Resolve through
`ModuleApis::resolve('runa.auth', AuthAdministration::class, '2.0.0')`.
Private implementation is `backend/Queries/Administration`, with a guarded proxy
and `Definitions/AdministrationApi`; all are runtime source. Customer code never
imports the private query, models or management adapters.

```php
interface AuthAdministration
{
    public function users(UserDirectoryQuery $query): UserDirectoryPage;
    public function profile(string $publicId): AdministrativeProfile;
    public function lifecycle(string $publicId): array;
    public function facts(string $publicId): array;
}
UserDirectoryQuery::__construct(?string $after = null, int $limit = 25,
    ?string $search = null, string $state = 'current', array $filters = [])
UserDirectoryPage::__construct(array $items, ?string $next, ?int $total = null)
AdministrativeUser::__construct(string $publicId, string $displayName,
    string $state, int $version, array $details = [])
AdministrativeProfile::__construct(string $publicId, int $version,
    ?string $name, ?string $firstName, ?string $surname, ?string $login,
    ?array $primaryEmail, array $secondaryEmails, int $preferenceRevision)
```

Directory requires `runa.auth.users.read`; `deleted`/`all` additionally require
`runa.auth.users.deleted.read`. Results contain up to 100 users in UUID ascending
order and state `current|deleted`. `details` contains safe account metadata:
active/security-lock flags, activation/deactivation/attempt-lock
moments and created/updated dates. Login additionally requires `profiles.read`;
email and verification/date require `emails.read`; live-session presence/count,
latest live login and activity require `sessions.manage`. Hidden values never
cross the guarded read. Session counts aggregate a bounded account batch using
Auth's native SessionStore criteria, including current policy, per-account idle
exceptions, security generation and captured expiry; presence means a live session,
not proof that a person is currently at their keyboard. `authenticated_at` and
`activity_at` cover live sessions. `last_login_at` and `last_activity_at` cover all
retained sessions, including revoked/expired rows; cleanup can remove this history,
so neither is an immutable all-time login record. Two-factor facts include enabled
and required state using native CredentialPolicy semantics, TOTP/email enrollment,
pending TOTP setup/deadline and remaining recovery-code count. Invitations expose
current pending/sent/expiry facts, credentials expose set/change/expiry state, and
authorized metadata includes effective verification requirement, operator recovery,
avatar set-state and current security-generation trust count.
The owning reader supplies `total`, the exact authorized filtered count before
cursor/limit, including zero. The nullable DTO default represents unavailable
counts for source-authored results; the supplied directory always counts in SQL.
Counts retain deleted-state and name/login field authority and do not imply a
snapshot across concurrent writes or arbitrary numbered cursor jumps.
Search is literal trimmed text, 1–160 characters, against names; login participates
only with `runa.auth.profiles.read`. `%`, `_` and backslash remain literal.
Cursors are opaque, at most 2048 bytes, and bound to state/search/all typed filters. They confer no
authority; each page checks current grants. A changed filter needs a fresh cursor.

Profile requires `runa.auth.profiles.read`. Names/login use nullable owning naming
rules. Primary email requires `runa.auth.emails.read`, otherwise `primaryEmail` is
null; authorized shape is `{email:string,verified:bool,verification_required:bool}`,
with effective verification policy. Secondary email requires
`runa.auth.secondary.read`, otherwise `secondaryEmails` is empty without counts or
set-state. Authorized shape is up to 100 `{public_id:string,email:string,verified:bool}`
entries. Preference revision requires `runa.auth.preferences.reset`, otherwise zero;
Admin omits its descriptor/value without that grant.

The current `facts(string $publicId): array<string,bool|int|string|null>`
entry requires `runa.auth.identities.read`. It serves existing-identity security,
invitation and avatar detail cards using their exact field grants, without
requiring unrelated directory authority. It returns identity public_id/version/
deleted state and only authorized, enabled metadata; users.read facts remain
absent without that grant. Its guarded operation is `administration.facts`, with
implementation/proxy/definition in the same owning runtime sources above.
`lifecycle` retains its original `runa.auth.users.read` entry guard and returns public_id, version,
deleted and the safe directory metadata above, plus deleted_at. Credential set-state,
password changed/expiry dates require `passwords.reset`; factor enrollment booleans
and remaining recovery-code count require `two_factor.reset`. No password, secret,
code/hash, internal ID or security generation leaves this projection. Avatar set-state
and upload limit require `avatars.manage` and enabled administrator avatar editing.
UTC moments use explicit offsets. These are account/session facts; the independently
permissioned `AuthIdentities::eligibility` read supplies effective blockers and
restrictions.

Current configuration filters both declarations and account-state projection.
Disabled email verification removes status/date/require-verification; verified
replacement also requires admin bypass opt-in. Zero password_expiry_days removes
expiry/password-change-required display and its lifecycle command, while ordinary
password reset remains available. Registration activation policy applies to public
registration; administrator-controlled registration creates inactive accounts.
The ordinary activation action admits these accounts. There are no separate
approval facts, filters or commands; NewIdentity exposes only the active flag. Disabled secondary emails, trust,
TOTP/email factors/recovery codes and administrator avatar editing omit their
sections/fields/actions. Self-service edit flags never remove separately authorized
administrator operations. Existing policy exceptions remain readable/revocable;
new password-expiry exceptions are omitted from selectable keys when expiry is off.
Backend declaration input intersection refuses manually submitted hidden commands.

Profile/lifecycle reject missing or unauthorized deleted subjects with safe 404;
deleted reads need the extra grant. Invalid queries/cursors fail with safe 422;
ordinary availability/grant failures retain guarded API semantics. Constructors
reject invalid DTO bounds with InvalidArgumentException.

## Pages and independent actions

Named Admin transport remains `/api/admin/{owner}/{page}/sections/{section}` for
reads, with `/preview` and `/commands` for writes. User page is
`/admin/runa.auth/users`; policy uses `policy`. The separate bulk_sessions page
is removed. User details use accessible tabs, keeping controller drafts and the
complete authorized atomic Save participant set across presentation switches.
Every operation requires Admin entry/current session/password proof, current owning
availability, page/section/field grants and native operation checks. The Users page
requires `runa.auth.identities.read`. Selecting an account uses its UUID and the
shared form controller's dirty-navigation confirmation, never a foreign model.

| Section / action                | Native API and grants beyond page entry                                          | Revision, proof and behavior                                                                                                             |
| ------------------------------- | -------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- |
| directory / lifecycle           | AuthAdministration; `users.read`, extra `users.deleted.read` as above            | Bounded cursors / current account version                                                                                                |
| eligibility                     | AuthIdentities; `eligibility.read`                                               | Current blockers/restrictions; no credential projection                                                                                  |
| profile / access                | AuthProfiles 2.0.0 / Permissions contribution                                    | Existing native atomic Save; [composition](../admin/composition.md)                                                                      |
| creation                        | AuthLifecycle.create; `users.create`                                             | No subject, revision 0, names or first/surname, normalized email/login; no credentials                                                   |
| lifecycle change                | AuthLifecycle.change; `users.manage`                                             | Exact account version; explicit confirmation, last-eligible administrator and identifier reuse/restore checks                            |
| invitation / invalidate         | AuthInvitations; `invitations.manage`                                            | Exact version, resend rotates private token; invalidate confirms; no secret result                                                       |
| primary email / change          | AuthProfiles.changeEmail; `profiles.read`, `emails.manage`                       | Version, both-factor action `runa.auth.email.admin_change`, native recipient confirmations                                               |
| verified replacement            | AuthProfiles.replaceVerifiedEmail; `profiles.read`, `emails.verify_bypass`       | Version, policy opt-in, confirmation, both-factor `runa.auth.email.admin_bypass`                                                         |
| require verification            | AuthProfiles.requireVerification; `profiles.read`, `emails.reverify`             | Version, confirmation, both-factor `runa.auth.email.require_verification`                                                                |
| secondary add/change/remove     | AuthProfiles; `profiles.read`, `secondary.read`, `secondary.manage`              | Version, address UUID, native policy/limits/verification; remove confirms                                                                |
| sessions list/revoke/revoke-all | AuthSessions; `sessions.manage`                                                  | Target UUID and session UUID; native bounded before-cursor, explicit revoke confirmation                                                 |
| trusted devices list/revoke     | AuthSecurity; `trust.manage`                                                     | Bounded before-cursor; device UUID, confirmation; no token/factor metadata                                                               |
| password reset                  | AuthSecurity.requestPasswordReset; `passwords.reset`                             | Version, confirmation, both-factor `runa.auth.password.admin_reset`; private reset delivery                                              |
| factor reset                    | AuthSecurity.resetFactors; `two_factor.reset`                                    | Version, confirmation, both-factor `runa.auth.two_factor.reset`; native authority/revocation                                             |
| preferences reset               | AuthPreferences.resetAccount; `profiles.read`, `preferences.reset`               | Account preference revision, confirmation; no foreign preference editor                                                                  |
| exceptions                      | AuthPolicies; `policy.exceptions`                                                | Policy and per-key exception revisions, bounded reason/expiry, both-factor `runa.auth.policy.exception`; null value revokes              |
| policy / history / restore      | AuthPolicies + owning ModuleSettings; `policy.read`, `policy.write`              | Exact settings revision/generation, reviewed differences and consequences, both-factor `runa.auth.policy.publish`; restore also confirms |
| bulk selected sessions          | AuthBulkSessions; `sessions.bulk_revoke` and native Permissions selection grants | Max 100 accounts/10,000 session/trust IDs; exact native preview fingerprint, confirmation; no additional operation proof                 |

Account creation is in the directory Card header's right actions area. All
available directory fields are filterable, with one compact DateTimeRangeInput for each inclusive date range. Separate small bulk action buttons sit immediately below filters, above the
standard table toolbar. Each button opens a reviewed operation for the captured
selection; related operations share a primary outlined button and modal operation selector; only granted and currently enabled actions appear.
Selected UUIDs are captured for review; no manual ID entry or all-account toggle
exists in this UI. Session revocation admits at most 100 accounts; other reviewed
user operations admit at most 99, reserving one native transaction subject for the
actor. Native Permissions selection authority, exact fingerprints and explicit
reviewed confirmation remain mandatory; operation proof follows the policy below. All-matching selection is
never converted to all accounts. Optional trust revocation appears only while
trusted devices are enabled. The public native AuthBulkSessions all-account API
remains available to its original consumers.

Avatar upload/removal reuse guarded POST/DELETE `/api/runa/auth/users/avatar` with
target public_id and expected version, independent from aggregate Save. The Auth
section default consumes AdminFormController.runIndependent for the shared mutation
lane, cancellation, feedback and reconciliation. FileInput enforces current type/size
UX limits; the native server remains content/dimension/size/authority/Audit owner.
A transient declared upload-pending flag participates in dirty navigation without
storing files or drafts server-side. Changes after file selection require explicit
local reconciliation; removal confirms. No foreign avatar bytes or storage path
are exposed by the metadata read.

All named stronger proofs are single use. Lifecycle accepts every supported intent:
activate/deactivate/delete/restore, schedule activation/deactivation,
set/clear expiry and attempt lock, set/clear security lock and password-change
requirement. Auth declaration adapters map cleared optional name/login text to null before
constructing NewIdentity/ProfileChange, so switching naming modes and clearing a
login preserves the native nullable contract. The form submits an operation, deadline and flag; only the relevant
argument enters LifecycleChange. Restore remains inactive and preserves historical
UUID. Native conflict/refusal/Audit behavior remains authoritative.

Policy fields are typed from Auth's existing definitions and split into bounded
sections of at most 30 settings. Each independently previews/publishes its patch
against the current global revision/generation; it is not a mixed-owner Save.
Restore reads immutable owning history and previews the exact full candidate.
Publication shows committed only for native `succeeded`; pending/recovery states
retain operation identity. History pages contain at most 50 entries.

The default collection presentations carry bounded authorized lists as JSON text
in declared readonly text fields (`addresses`, `sessions`, `devices`, `exceptions`,
`history`), decoded only for display. No nested list bypasses field authorization.
Use-entry controls copy public IDs or exact source revisions into controlled
inputs. Sessions/trust/history support older/latest results without totals or
export adapters. Failed reads remain unavailable; changing subjects clears and
cancels obsolete collection lanes. Reconciliation preserves unrelated dirty drafts.

Email, invitation, lifecycle, security, preferences, policy and bulk operations
remain independent owning transactions/runners with their existing atomic Audit,
proof consumption, outbox and refusal semantics. They cannot join profile/access
Save. An action revoking the actor's session can require re-entry after its native
commit; the client must refresh rather than replay the action. Self-service,
recipient/delivery routing, operator recovery/bootstrap, private identity/grant
resolution and arbitrary shell commands retain their original audiences.

## Presentation and application consumption

The source-default components are `runa.auth.admin_users` and
`runa.auth.admin_section`, compiled under Auth's existing manifest/build identity.
Public `@/runa/auth` exports lazy `AuthAdminUsers` with `AdminPageProps` and
`AuthAdminSection` with `AdminSectionPresentationProps` from `@/runa/admin`.
They have no custom events or slots. Application pages may compose these defaults
or replace presentation through the public Admin controllers and dispatch contract;
keep current session/proof/security orchestration in Runa. Source component keys
retain precedence over generic providers; full replacement can own the public
Admin page dispatcher without copying security logic.

`AdminSectionContent` publicly composes a projected section with the same
`{section,page,controller,components?}` props. `AdminComponentBoundary` takes
`{identity:string,onFailure?:(failed:boolean)=>void}`, has a default slot and no
emits; use failure callbacks to block unavailable required editors. These are
Admin-owned public presentation consumption, implemented in its frontend and
exported through `@/runa/admin`; no server capability or new runtime dependency is
introduced. App source remains under `src/app`; no sample page/provider is added
to the minimal scaffold.

Bounded collection entries use public ValuePreview for escaped long read-only
values and structured JSON; known instant fields retain application-timezone
formatting. Shared Admin read-only fields and reviewed differences use the same
expansion control without changing collection pagination or security protocols.

## Native bulk user operations

Auth owns guarded public consumption API `AuthBulkUsers` 2.0.0 (runtime source under
backend/Contracts, Definitions/BulkUsersApi and BulkUsersProxy; private engine in
backend/Actions). Resolve it through ModuleApis; customer code never imports the
engine, models or locked phases.

```php
interface AuthBulkUsers {
    public function preview(SessionSelection $selection, BulkUserChange $change): array;
    public function execute(SessionSelection $selection, BulkUserChange $change, string $fingerprint): array;
}
BulkUserChange::__construct(string $operation, ?string $at = null, ?bool $enabled = null)
// preview: {fingerprint:string, accounts:int, affected_accounts:int, sessions:int, trusted_devices:int}
// execute: {accounts:int}
```

Only explicit nonempty selections of at most 99 accounts are accepted. Each
related inventory is bounded to 10,000 safe state rows; excess refuses. Every operation requires users.read,
current Permissions user-selection authority and its ordinary owning mutation
permission. The private source catalogue filters disabled features and defines
activate/deactivate/delete/restore/schedule_activation/
schedule_deactivation/set_attempt_lock/set_security_lock/
require_password_change plus invite/invalidate_invitation/require_verification/
password_reset/factor_reset/revoke_trust/reset_preferences/remove_avatar/
revoke_exceptions, and bulk-only toggle_activation/toggle_deletion/
toggle_security_lock. These toggles take no date/flag parameters and invert each
account's stored active/deleted/security-lock state under owning locks. Their
owner/audience/entrypoint/signature/source home are the AuthBulkUsers contract
above; private BulkUsers resolves ordinary LifecycleChange plans at runtime.
Deletion inversion requires users.deleted.read and a fresh password proof, including
when the selection contains both retained and deleted accounts. Restore always
leaves accounts inactive. Reviewed execution rechecks current settings, exact account and
related-state fingerprint and record policies. Delete, restore, deletion inversion, password reset,
factor reset and exception revocation require one fresh password proof for
`runa.auth.users.bulk.<operation>`, consumed once through ModuleTransactions after
validation. All other bulk operations, including session/trust revocation, require
no additional operation proof. Admin entry proof and native grants still apply. The batch is atomic: refusal,
stale input, last-administrator loss or Audit failure rolls back every selected
account and proof. No per-user HTTP loop or single-record proof reuse is involved.
Standalone and bulk use the same owning locked validation/apply phases. Native
password reset validates that a password exists and the account may authenticate
before revoking credentials; an unavailable reset cannot partially revoke state.
The root locks retained subjects (including deleted restore targets); each owner
still validates target state. Only restore and deletion inversion accept deleted targets, and both
additionally require users.deleted.read. File
uploads, account creation, unique profile/address edits and per-record assignment
reviews remain individual workflows because they require distinct target input.
Avatar removal commits only existing owning metadata/Audit; its durable native
cleanup handles bytes after commit, never external storage work inside the batch.

UserDirectoryQuery additionally accepts `array $filters = []` as its fifth argument.
Each available directory field supports an authorized typed filter: literal substring
for text, equality for booleans/integers/dictionaries, and a JSON-encoded object
`{from?: UTC instant, until?: UTC instant}` for inclusive moment ranges. Null/empty
filters are omitted. Unknown, disabled or unauthorized keys are refused before
querying. The full normalized filter set participates in the opaque cursor identity;
SQL filtering precedes exact count and cursor pagination. Moment controls use the
application timezone while transport remains canonical UTC.

Admin adapters normalize zero-fraction UTC DateTimeInput values to Auth's native
whole-second command values (including nullable cleared deadlines). Nonzero
fractional command deadlines remain refused. Directory date ranges accept UTC
instants with up to six fractional digits; inclusive comparisons preserve that
precision. Native Auth DTO signatures and timezone authority remain unchanged.

Bulk review counts project only session/trust rows actually revoked by the selected
operation. Unrelated preference/avatar/invitation changes do not imply session
revocation. Admin omits zero unrelated impacts and disabled trust presentation;
accounts remain the captured selection count.

Auth directory moment filters consume the shared DateTimeRangeInput with one
standard field label per range. The popup commits from/until together; the
existing JSON range payload and exact authorized filtering remain unchanged.

Auth directory controls use size sm (36px), matching Audit's shared filter controls.
Concise PL/EN labels apply consistently to filters, column headings and account
facts: Two-factor authentication omits the redundant enabled suffix. Bulk actions
are wrapping buttons, with a shared selection count and per-operation 99/100 limits.
Activity (activate/deactivate/invert), deletion (delete/restore/invert), lifecycle
schedules (activation/deactivation), invitations (send/invalidate) and security lock
(on/off/invert) each share a primary outlined button. The modal uses the public Select to
choose only currently projected, authorized commands, then renders that command's
parameters and starts an authoritative impact preview. Binary inversion uses each
account's stored flag; dates and invitation delivery are explicit operations, not
binary inversion. Attempt lock also has a primary outlined button with explicit set-deadline/clear choices. Force password
change is a separate one-way bulk action (no boolean switch); standalone lifecycle
and native BulkUserChange APIs still support clearing the forced-change flag.
Operation changes clear the old preview/proof, retain the captured selection and
require a new impact review before confirmation. No writes are automatically replayed. Selected accounts and
`affected_accounts` are separate: lifecycle plans count only dirty account state;
revocation/removal counts only accounts with matching rows or avatars. Credential
resets, verification mail, invitations and preference reset count each account
whose owning operation performs a reset/delivery/revision change. Activate on
eight already active and two inactive accounts previews ten selected, two affected.
Zero-impact batches disable Confirm. Editing inputs invalidates the preview and
refreshes impact without replaying a write. Confirm opens a password step only
for the protected operations above; its explicit confirmation submits once.
Cancel returns to the unchanged review; failed writes require a new explicit
review/attempt. There is no separate identity button or duplicate confirmation.

Actions follow account lifecycle, invitation/email, credentials/locks, revocations
and presentation reset order. Semantic success/danger/warning button tones use
the shared theme in both modes. The directory omits the account-state column
and picker entry. Its Deleted filter has Not deleted (default), Deleted and All
choices using unchanged current/deleted/all wire values; deleted records use
danger row colors. Native deleted-read authority remains required.

Account lifecycle uses active, activate_at and deactivate_at. There is no account
expires_at fact, filter or set_expiry command. Auth 3.0.0 upgrades existing expiry
deadlines into the earlier deactivation deadline before dropping the column;
activation conflicts refuse the forward migration without discarding either date.
Removed action/date labels remain readable only for historical Audit records.
Reminder mail uses account_deactivation and the current deactivate_at, preserving
stored enablement, administrator-copy recipient and delivery preferences across
the explicit owning upgrade. Old pending notices are revalidated against the
current deadline before delivery; password/session/token deadlines are unchanged.

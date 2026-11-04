<!-- runa-knowledge {"purpose":"current_guidance","effective_date":"2026-10-30","applicable_version":"0.7.0","decisions":[]} -->

# Auth administration

Auth 3.1.0 supplies source-owned administration declarations and adapters, consumed
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
replacement also requires admin bypass enablement. Zero password_expiry_days removes
expiry/password-change-required display and its lifecycle command, while ordinary
password reset remains available. The initial password-change choice in creation
remains independent of periodic password expiry. Registration activation policy applies to public
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

| Section / action                | Native API and grants beyond page entry                                                                                                 | Revision, proof and behavior                                                                                                                                                            |
| ------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| directory / lifecycle           | AuthAdministration; `users.read`, extra `users.deleted.read` as above                                                                   | Bounded cursors / current account version                                                                                                                                               |
| eligibility                     | AuthIdentities; `eligibility.read`                                                                                                      | Current blockers/restrictions; no credential projection                                                                                                                                 |
| profile / access                | AuthProfiles 2.0.0 / Permissions contribution                                                                                           | Native standalone form or complete aggregate Save; [composition](../admin/composition.md)                                                                                               |
| creation                        | AuthLifecycle 2.1.0 create; `users.create`, `invitations.manage` for invitation, `emails.verify_bypass` for administrative verification | No subject, revision 0; configured creation uses single-use `runa.auth.users.create` (password, both factors for administrative verification), current password policy and atomic Audit |
| lifecycle change                | AuthLifecycle.change; `users.manage`                                                                                                    | Exact account version; explicit confirmation, last-eligible administrator and identifier reuse/restore checks                                                                           |
| invitation / invalidate         | AuthInvitations; `invitations.manage`                                                                                                   | Exact version, resend rotates private token; invalidate confirms; no secret result                                                                                                      |
| primary email / change          | AuthProfiles.changeEmail; `profiles.read`, `emails.manage`                                                                              | Version, both-factor action `runa.auth.email.admin_change`, native recipient confirmations                                                                                              |
| verified replacement            | AuthProfiles.replaceVerifiedEmail; `profiles.read`, `emails.verify_bypass`                                                              | Version, policy enablement, confirmation, both-factor `runa.auth.email.admin_bypass`                                                                                                    |
| require verification            | AuthProfiles.requireVerification; `profiles.read`, `emails.reverify`                                                                    | Version, confirmation, both-factor `runa.auth.email.require_verification`                                                                                                               |
| secondary add/change/remove     | AuthProfiles; `profiles.read`, `secondary.read`, `secondary.manage`                                                                     | Version, address UUID, native policy/limits/verification; remove confirms                                                                                                               |
| sessions list/revoke/revoke-all | AuthSessions; `sessions.manage`                                                                                                         | Target UUID and session UUID; native bounded before-cursor, explicit revoke confirmation                                                                                                |
| trusted devices list/revoke     | AuthSecurity; `trust.manage`                                                                                                            | Bounded before-cursor; device UUID, confirmation; no token/factor metadata                                                                                                              |
| password reset                  | AuthSecurity.requestPasswordReset; `passwords.reset`                                                                                    | Version, confirmation, both-factor `runa.auth.password.admin_reset`; private reset delivery                                                                                             |
| factor reset                    | AuthSecurity.resetFactors; `two_factor.reset`                                                                                           | Version, confirmation, both-factor `runa.auth.two_factor.reset`; native authority/revocation                                                                                            |
| preferences reset               | AuthPreferences.resetAccount; `preferences.read`, `preferences.reset`                                                                   | Account preference revision and confirmation; presentation-only reset                                                                                                                   |
| exceptions                      | AuthPolicies; `policy.exceptions`                                                                                                       | Policy and per-key exception revisions, bounded reason/expiry, both-factor `runa.auth.policy.exception`; null value revokes                                                             |
| policy / history / restore      | AuthPolicies + owning ModuleSettings; `policy.read`, `policy.write`                                                                     | Exact settings revision/generation, reviewed differences and consequences, both-factor `runa.auth.policy.publish`; restore also confirms                                                |
| bulk selected sessions          | AuthBulkSessions; `sessions.bulk_revoke` and native Permissions selection grants                                                        | Max 100 accounts/10,000 session/trust IDs; exact native preview fingerprint, confirmation; no additional operation proof                                                                |

Account creation is in the directory Card header's right actions area. All
creation options are owned by Auth. `AuthLifecycle::create(NewIdentity $input,
?IdentitySetup $setup = null): Identity` is the guarded runtime entrypoint. Both
DTOs live in Auth's public `backend/Contracts`; private persistence/orchestration
stays in `Actions/ChangeIdentity`. The optional setup describes initial Auth state;
omitting it creates only the identity. Its complete signature is:

```php
IdentitySetup::__construct(string $credentials = 'none', ?string $password = null,
    bool $emailVerified = false, ?string $activateAt = null,
    ?string $deactivateAt = null, bool $securityLocked = false,
    ?string $attemptLockedUntil = null, bool $passwordChangeRequired = false)
```

`credentials` is `none|invitation|password`; only password mode accepts a secret
or forced first-login password change. The password parameter/property is a
write-only secret at ingress: default JSON/debug serialization omits or redacts
it; no read/response contains it. Configured creation consumes password proof,
escalated to both factors for administrative verification. The bounded creation
preview selects that requirement without echoing or fingerprinting secrets; the
writer independently repeats it against the submitted options. Dates use canonical UTC seconds and must
be future moments; activation precedes deactivation. Active-now and scheduled
activation are exclusive. Initial password hashing uses current owning policy and
does not send invitation/reset/password-change mail. Invitation mode issues its
token and durable delivery work immediately in the creation transaction, including
inactive/future-activation accounts; explicit initial security/attempt locks are
incompatible with invitation mode. Normal transport, expiry, single-use, recipient,
generation, deletion and other security guards remain. Invitation acceptance verifies
the email and sets the first password without activating or authenticating the
account. Administrative verification also requires the existing bypass policy
enablement and emits an explicit safe `email.admin_verified` event. Initial credentials
emit `password.initial_set` without secret values. Failure rolls back the entire
creation, token/work, success Audit and proof consumption.

Management `creation` uses `CreationSection::INPUTS`, plus read-only identifier and
password-policy metadata. Password/confirmation reads expose only `{set:false}`;
they never return a draft or hash. Capability fields use their own invitation/
verification grants, while the native writer always repeats authorization.
The default creation presentation groups identity, account activity, first password,
verification with an access/delivery summary. Verification is omitted when the
policy or its grant is unavailable; the policy defaults enabled and remains
editable in the Auth settings view. Initial security/attempt locks remain available
through the native setup API and existing individual account workflows; the default
creation form does not present them. Roles,
direct grants, policy exceptions, secondary addresses and avatar changes retain
their separate existing owning workflows after creation; enrolled factors, recovery
codes, sessions/trust and historical timestamps are not editable initial facts.

Public frontend `AuthAdminCreation` from `@/runa/auth` accepts
`AdminSectionPresentationProps & {components?: AdminComponentRegistry}` (types from
`@/runa/admin`), owns its Form and has no emitted events or slots. Its implementation
lives in `frontend/AuthAdminCreation.vue`; the default Auth section delegates creation
to it outside the record/bulk AdminForm. It uses the supplied AdminFormController
for drafts, guarded reads, proof selection, writes, safe errors and reconciliation.
The default section replacement remains `AuthAdminSection`, and customers may
replace that section/page through Admin's public composition contract. Mount the
creation presentation outside another Form. A full replacement retains explicit
creation preview/proof/submission, temporal validity, secret cleanup and current
identity/access-loss handling; no custom security backend is needed.

All
available directory fields are filterable. The filter presentation orders identity/profile
search first, then account state, credentials/security and sessions/trust, with all
date ranges last in lifecycle, verification/invitation, credential and activity
order. This presentation order is independent of table-column order and retains
only projected authorized fields. Date filters use one compact DateTimeRangeInput for each inclusive date range. Separate small bulk action buttons sit immediately below filters, above the
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
set/clear attempt lock, set/clear security lock and password-change
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
// preview: {fingerprint:string, accounts:int, affected_accounts:int, sessions:int, trusted_devices:int, skipped_accounts:int, ineligible_accounts:int}
// execute: {accounts:int, skipped_accounts:int}
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
Invitation and password-reset batches skip ineligible ordinary accounts before
planning: invitation requires no existing password and allows inactive/future-
activation identities; reset requires a password and current sign-in eligibility.
Other security blockers, reached deactivation, deletion and token guards remain
required for invitations as for standalone invitation issue/delivery/acceptance.
`skipped_accounts` is the total of protected and ineligible accounts;
`ineligible_accounts` reports only the latter, without double counting. Preview
fingerprints include that classification and execution refuses changed eligibility,
including clock-driven schedule transitions, rather than silently changing targets.
The modal shows separate protected/eligibility omission notices and counts; an
all-skipped selection has zero impact and cannot be confirmed. No skipped account
receives tokens, credential/session changes or mutation Audit events.
Business refusals retain safe source-owned codes (future date, schedule order,
deleted account), with localized messages; they do not claim a stale write.
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
(on/off/invert) each share a primary outlined button. The compact modal presents selected/candidate counts together and right-aligns
confirmation. It uses the public Select to
choose only currently projected, authorized commands, then renders that command's
parameters and starts an authoritative impact preview. Binary inversion uses each
account's stored flag; dates and invitation delivery are explicit operations, not
binary inversion. Attempt lock also has a primary outlined button with explicit set-deadline/clear choices. Force password
change is a separate one-way bulk action (no boolean switch); standalone lifecycle
and native BulkUserChange APIs still support clearing the forced-change flag.
Selecting the current operation preserves its valid review/proof. Actual
operation changes clear the old preview/proof, retain the captured selection and
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
and presentation reset order. Grouped buttons use the shared primary outlined
style; individual actions retain their semantic tones in both themes. The directory omits the account-state column
and picker entry. Its Deleted filter has Not deleted (default), Deleted and All
choices using unchanged current/deleted/all wire values; deleted records use
danger row colors. Native deleted-read authority remains required.

Account lifecycle uses active, activate_at and deactivate_at. There is no account
expires_at fact, filter or set_expiry command. The unpublished Auth 3.0.0 schema defines this directly; no intermediate expiry
conversion migration or settings-rename machinery is delivered. Removed action/date
labels remain readable only for historical Audit records. Reminder mail uses
account_deactivation and the current deactivate_at, including enablement,
administrator-copy routing and delivery preferences. Old pending notices are revalidated against the
current deadline before delivery; password/session/token deadlines are unchanged.

Every Auth bulk action excludes protected superadministrator role members, including
inactive, locked and deleted retained members. The original captured selection and
99/100 limits remain; `accounts` in user preview is selected count,
`skipped_accounts` is the total omission count (protected plus, for invitation/reset,
ineligible ordinary accounts), and `affected_accounts` counts changes only among
admitted targets. Execution `accounts` counts admitted accounts. The compact modal warns that protected accounts will be skipped, displays
the skipped count and permits confirmation when ordinary accounts are affected.
A selection containing only protected accounts has zero impact and cannot be
confirmed. No skipped account receives bulk mutations, Audit events or session/trust
revocations. Exact membership is reread under the shared authority lock and included
in the preview fingerprint, so promotion/demotion requires a new explicit review.
Single-account native actions retain the last-eligible-administrator invariant;
individual-action presentation remains outside this bulk-protection change.

## Task-based user details

Auth 3.1.0 replaces the default per-section tabs with at most three nonempty
areas: User, Security, Rules and exceptions. User starts with individual action
buttons, then editable identity/avatar, account status/access conditions, emails,
assignments and preferences. Security groups credential/factor facts, sessions and
trusted devices. Rules and exceptions holds bounded exception history and editing.
Existing lifecycle schedules, invitation/verification/password/factor operations,
reset, avatar management and all native read grants remain available. Individual
operations use their native target APIs rather than the bulk API, whose protected
superadministrator exclusions remain unchanged.

Identity and assignment forms can execute their native standalone writer through
the section command route; the optional shared aggregate Save still requires the
complete current participant set. Independent forms submit only their own fields,
refresh authoritative reads in place and preserve other dirty forms. A preview is
required where declared; explicit submission opens the owning proof fields and
executes once after successful confirmation. Required grants, native stronger
proof, last-administrator checks and atomic Audit are never replaced by UI state.

Revision tokens, exception/policy versions and selected record UUIDs remain in the
controller. Session/device/address actions select the actual row. Exception editing
selects the current key's latest version automatically; an existing exception can
be edited or explicitly revoked. Bounded session/device paging remains available.
No token/version input is presented to an operator.

Additional guarded public consumption APIs, implemented with their owning proxies
and Actions under Auth `backend`, are runtime capabilities for authorized server
consumers through `ModuleApis`; management adapters are private:

```php
// AuthSecurity API 1.1.0, permission runa.auth.trust.manage
public function revokeDevices(string $publicId, int $expectedVersion): bool;
// AuthPolicies API 1.1.0, permission runa.auth.policy.exceptions
// Fresh both-factor runa.auth.policy.exception proof, consumed once in transaction.
public function revokeExceptions(string $publicId, int $expectedVersion): bool;
```

Both lock the authority and target, compare the current identity version and reuse
the owning transactional revocation/Audit phases. Other methods in those contracts
retain their signatures and demands. Exact-version consumers use 1.1.0. See
[administrative preferences](preferences.md#administrative-account-preferences)
for the separate preference revision, recipient grants and complete signatures.

Presentation replacement continues through public `AdminPageProps`,
`AdminSectionPresentationProps`, `AdminField` and `AdminFormController`, plus the
manifest-bound section/component registry and `useAdminPresentation`. Unknown
contributions remain visible in User. Customer field/section replacements retain
their existing resolution and authority boundaries; customers never copy private
Auth controllers or access foreign models. The new default detail components are
private presentation implementations, not additional customer imports.

`AuthPolicies` 1.1.0 additionally exposes
`exceptionVersions(string $publicId): array<string, int>` under
`runa.auth.policy.exceptions`. It projects each key's current monotonic version
independently of the bounded history page, so editing another rule remains correct
even after more than 50 historical changes to a different rule. These values stay
in the form controller; they are not operator inputs.

### Consistency with creation and account forms

The default selected-user form is titled User details/Dane użytkownika and composes
public `AuthIdentityFields`, also used by creation and own-account details. Read-only
`identifier_mode` comes from the guarded profile record, never from a writable
operator field; the native write still accepts exactly the four identity inputs.
Name-format changes clear the inactive fields only after an explicit choice.
Admin field replacements remain supported through the identity component's named
slots and the existing guarded AdminField resolution.

Toolbar buttons share the bulk view's action-tone catalogue. Mixed operation groups
retain their shared group tone; password/verification/exception actions use warning,
and factor/session/trust revocation uses danger. Individual operation confirmation
uses the selected action's tone; Cancel retains the ordinary neutral treatment.
Session/device/address row actions use the same semantic outline buttons.

Email forms display one native change method at a time, validate email immediately
and submit that method on Enter. Save/Revert affect only their owning form and
preserve unrelated drafts. Preferences share appearance/notification controls and
terminology with the account view, with one explicit atomic Save preferences.
Avatar selection uses the existing AuthAvatarCropper, 20 MB source-image bound,
the target's final upload bound and local preview/adjust/revert. Captured target and
revision survive cropping; a concurrent change requires a local conflict decision.
The current private avatar read remains own-account only; administrative preview
shows the newly prepared local image and never substitutes the operator's image.
No new avatar-read authority or private byte route is introduced.

See [shared fields](account-ui.md#shared-identity-and-preference-fields) for the
complete public composition contracts. Native profile/address/avatar/proof/Audit
orchestration remains in the existing owners.

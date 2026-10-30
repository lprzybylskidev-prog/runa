<!-- runa-knowledge {"purpose":"proposal","effective_date":null,"applicable_version":"0.7.0","decisions":[]} -->

# Admin Core operations and application settings

Status: RM-2026-016 point 1 specification. Existing operations below are available
through their owning guarded APIs; their Admin adapters, new reads/settings and
UI are not implemented except for the private point-2 cached diagnostics foundation
described in [delivery status](README.md#delivery-status-and-acceptance-ownership).
It has no HTTP diagnostics or management command yet. API versions remain exactly `1.0.0`, independently of
module versions. Each adapter binds explicitly to the named method and current
ApiOperation declaration; a management key is never assumed to be a PHP method.

## Core operation coverage

All entries additionally require Admin entry/password proof, owning grants,
availability and record Policies. Existing owner guides remain canonical for
DTO constructors, validation, safe errors, bounds and stronger proof requirements:
[Auth](../auth/README.md), [profiles](../auth/profile.md),
[security](../auth/security.md), [policies](../auth/policies-and-operators.md),
[Permissions access](../permissions/access.md),
[authority](../permissions/authority.md), [Audit](../audit/README.md).

| Contract                       | Exact methods and Admin disposition                                                                                                                                                                                                                                                                                                                       |
| ------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AuthIdentities                 | `identity`, `eligibility`: guarded subject/label lookup, never directory SQL from Admin                                                                                                                                                                                                                                                                   |
| AuthLifecycle                  | `create`, `change`: user creation and every supported LifecycleChange intent/deadline; independent actions, preserve restore/reuse/last-superadministrator rules                                                                                                                                                                                          |
| AuthInvitations                | `invite`, `invalidate`: rotate/resend and invalidate, never expose token                                                                                                                                                                                                                                                                                  |
| AuthProfiles                   | `edit`: complete profile name/firstName/surname/login replacement, atomic eligible; `changeEmail`, `replaceVerifiedEmail`, `requireVerification`: separate verified/bypass/reverification flows; `addSecondary`, `changeSecondary`, `removeSecondary`: independent address actions; `notificationEmails`: recipient-only, not editable address projection |
| AuthSessions                   | `listFor`, `revoke`, `revokeAll`: bounded per-subject session list/actions; retain current session semantics                                                                                                                                                                                                                                              |
| AuthBulkSessions               | `preview`, `revoke`: selected/all-user native selection, exact reviewed fingerprint and both-factor proof; independent workflow                                                                                                                                                                                                                           |
| AuthSecurity                   | `requestPasswordReset`, `resetFactors`, `trustedDevices`, `revokeDevice`: safe independent security controls; no credential viewer                                                                                                                                                                                                                        |
| AuthPolicies                   | `history`, `exceptions`, `preview`, `publish`, `restore`, `exception`: complete history/consequences/review/publication and account exceptions including revoke/expiry; separate settings runner                                                                                                                                                          |
| AuthPreferences                | `resetAccount`: administrative reset with target account revision; `read`, `write`, `reset`: self-service only, excluded from foreign-account editing                                                                                                                                                                                                     |
| AuthDelivery                   | `recipients`: authorized task-specific recipient resolution only, no delivery console, raw routing or second dispatch engine                                                                                                                                                                                                                              |
| AuthStepUp                     | `assertVerified`: internal use through the guarded API for entry/action proof; no generic HTTP assertion endpoint                                                                                                                                                                                                                                         |
| Permissions                    | `allows`: current trusted actor only; `explain`: bounded effective-access introspection                                                                                                                                                                                                                                                                   |
| PermissionsSelections          | `users`: native selected/all-subject selection validation, never permission to read foreign persistence                                                                                                                                                                                                                                                   |
| PermissionsAccess              | `assignments`, `definitions`, `definition`: owning read views; `previewAssignments`, `previewCopy`, `previewPreset`, `applyAssignments`: exact reviewed add/replace/copy/preset flow; `previewDefinition`, `saveDefinition`, `deleteDefinition`: custom role/permission/preset management, source/system immutable                                        |
| PermissionsAuthority           | `previewAuthority`, `applyAuthority`: dedicated protected assignment; `previewRoleDeletion`, `forceDeleteRole`: explicit destructive reviewed custom-role action; both independent                                                                                                                                                                        |
| AuditReads                     | `page`, `detail`, `metadata`: cursor filters/detail/authorized links, current labels through guarded Auth only; no invented count, write, delete, retention or undo                                                                                                                                                                                       |
| AuditRecords                   | `record`, `attempt`: owning server transaction/refusal plumbing only; never generic Admin write endpoints                                                                                                                                                                                                                                                 |
| OwnHistory / OwnHistoryDetails | `page` / `detail`: current-identity self-service only, excluded from general Admin authority                                                                                                                                                                                                                                                              |
| Reserved integration           | AuthorizationIdentity, BulkUserSelection, OperatorAuthority, SecurityMailDelivery, DevelopmentAccounts, AuditWriter, AuditActorResolver, DevelopmentAccess stay reserved; no operator/bootstrap/development HTTP controls                                                                                                                                 |

The private compatibility ledger enumerates **every** currently declared API
method, its complete native signature, source definition, operation, permission,
API version and audience. New Admin handlers must preserve those bindings; the
public table above records their disposition rather than exposing private classes.

## Additive Auth reads

Add a new guarded `Runa\Modules\Auth\Contracts\AuthAdministration` API `1.0.0`,
implemented by Auth-owned Queries/proxy/definition, with these complete signatures:

```php
interface AuthAdministration
{
    public function users(UserDirectoryQuery $query): UserDirectoryPage;
    public function profile(string $publicId): AdministrativeProfile;
}
UserDirectoryQuery::__construct(?string $after = null, int $limit = 25,
    ?string $search = null, string $state = 'current')
UserDirectoryPage::__construct(array $items, ?string $next)
AdministrativeUser::__construct(string $publicId, string $displayName,
    string $state, int $version)
AdministrativeProfile::__construct(string $publicId, int $version,
    ?string $name, ?string $firstName, ?string $surname, ?string $login,
    ?array $primaryEmail, array $secondaryEmails, int $preferenceRevision)
```

All DTOs are Auth-owned final readonly values. `items` is
`list<AdministrativeUser>`; search is trimmed plain text, 1–160 characters when
present; after opaque max 2048 bytes, limit 1–100. State filter is
`current|deleted|all`; returned state is a supported owning lifecycle state.
Stable cursor order is public_id ascending; no arbitrary column/order or invented
total. Directory requires new `runa.auth.users.read`; deleted/all additionally
requires `runa.auth.users.deleted.read`. No email lookup shortcut through search:
search only authorized display-name/login fields and only if their read grants
permit it. When login cannot be read, exclude login from the search predicates.

Profile requires new `runa.auth.profiles.read` and enforces field projection:
primary email additionally needs new `runa.auth.emails.read`, secondary addresses
new `runa.auth.secondary.read`, preference revision existing
`runa.auth.preferences.reset`. `primaryEmail` is null when denied, otherwise
`array{email:string,verified:bool,verification_required:bool}`;
secondaryEmails is `list<array{public_id:string,email:string,verified:bool}>`,
empty when denied, max 100. Absence of a grant cannot leak set-state/counts.
Name/login values can be null by owning naming rules; adapters omit denied field
descriptors entirely rather than treating null as a writable value. Preference
revision uses 0 when not authorized and is omitted from Admin projection. Existing
Identity/Eligibility and all existing interface methods remain unchanged.
Unknown/not-visible subjects fail with the owning safe 404; revision conflicts
retain IdentityConflict. Admin never queries users/addresses/preferences directly.

## Framework operation adapters and Audit

These are named trusted `Runa\Integration\Core\Admin*` bridges for Admin handlers,
not customer imports. Requests name stable commands only. The owning engine keeps
its current public signature; each bridge enforces the permission below plus
target/source/revision checks. No generic engine-method dispatcher is allowed.

| Command / grant                                           | Exact engine entrypoint                                                                                                                             | Concurrency, outcome and Audit disposition                                                                                                                    |
| --------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| diagnostics.read / `runa.admin.diagnostics.read`          | ModuleRegistry.snapshot(), ModuleManagement.read(string $module): ManagementView and cached ModuleAvailability.statuses()                           | Registry state filter, generation and safe observed/expiry/reason data; no diagnostic execution on read                                                       |
| health.diagnose / `runa.admin.health.diagnose`            | ModuleHealth.diagnose(string $id): array                                                                                                            | Independent deadline/lock; persisted observations, safe fixed codes; diagnostic observation is not a business mutation event                                  |
| health.configure / `runa.admin.health.configure`          | ModuleHealth.configure(string $id, array $overrides, int $generation): void                                                                         | Existing 4096-byte bound/policy; add mandatory synchronous Audit to the same update transaction                                                               |
| modules.preview / `runa.admin.modules.manage`             | ModuleTransitions.preview(string $intent, string $target): AdministrativePreview                                                                    | Only existing allowed Optional intents; exact reviewed graph/source/generation and active work                                                                |
| modules.apply / same                                      | ModuleTransitions.apply(AdministrativePreview $preview): TransitionResult                                                                           | Independent reservation/hook/publication runner; add Audit participation to reservation/final publication/compensation state boundaries                       |
| modules.status/resume/cancel / same                       | ModuleTransitions.status/resume/cancel(string $operationId): TransitionResult                                                                       | Exact native status/recovery and generation; add missing meaningful mutation Audit in the owning transaction; no blanket recovery                             |
| settings.read / `runa.admin.settings.read`                | ModuleSettings.read(string $module): array                                                                                                          | Redacted typed values/set-state only, generation/revision; never use private value() for HTTP                                                                 |
| settings.update / `runa.admin.settings.manage`            | ModuleSettings.update(string $module, array $patch, int $expectedRevision, int $expectedGeneration, ?SettingsActor $actor = null): TransitionResult | Independent runner; derive actor from trusted context; existing SettingsParticipant publication Audit, no duplicate wrapper success event                     |
| settings.status/recover / same                            | ModuleSettings.status/recover(string $id): TransitionResult                                                                                         | Owning recovery only, no generic undo; preserve SettingsParticipant restore Audit                                                                             |
| integrations.configure / `runa.admin.integrations.manage` | ModuleIntegrations.configure(string $module, string $key, bool $enabled, array $config, int $expectedGeneration): int                               | Existing declared target/config validation and 8192-byte bound; add Audit inside same generation/config transaction                                           |
| work.read / `runa.admin.work.read`                        | ModuleWork.active(string $module, int $limit = 100): array; status(string $id): WorkStatus                                                          | Default 100 plus explicit truncation; bounded requested limit up to native 1000, subject to response byte limit; safe progress code, no fabricated percentage |
| work.cancel / `runa.admin.work.cancel`                    | ModuleWork.requestCancellation(string $id): WorkStatus                                                                                              | Cooperative request only, owning cancellable policy; add transaction/revision-safe Audit at the actual cancel_requested write, not after it                   |

If a signature above is abbreviated, parameter/return types are inherited exactly
from the explicitly named first signature, never inferred from an HTTP payload.
Source inspection at point 1 confirms SettingsParticipant publication hooks exist;
health configure, integration configure, lifecycle and cancellation need the new
transactional Audit integration in point 9. A post-commit wrapper record is
insufficient. Recovery retains pending/recovery_required and owner cancellation
limits; do not show committed before native completion. The new transaction/Audit
participation is internal; preserve current engine callers and signatures.

Diagnostics contain platform/app/module versions, registry/install/source and
effective availability, dependencies and persisted health freshness, plus only
allowlisted PHP/runtime version data. No environment variables, URLs/credentials,
paths, raw exceptions, upstream bodies, model counts or queue payloads. Default
filter is registered+enabled; explicit registry-state filtering can show disabled,
missing/uninstalled and unavailable modules under diagnostics authority. Per-widget
failure is isolated without hiding required form failures.

## Maintenance matrix

| State                                               | Admin HTML/protected APIs                              | Supported operator behavior                      |
| --------------------------------------------------- | ------------------------------------------------------ | ------------------------------------------------ |
| Normal, Core healthy, current grant/proof           | Allowed subject to every owning check                  | Existing CLI unchanged                           |
| Manual maintenance                                  | Blocked under existing middleware/module guard         | Existing diagnostics/recovery CLI only           |
| Update maintenance/fence                            | Blocked; no proof or admission exception               | Owning update/recovery CLI only                  |
| Failed/missing Auth, Permissions, Audit or database | Fail closed, safe unavailable screen                   | Existing operator recovery, never browser bypass |
| Optional module unavailable                         | Hide/refuse its contributions; unrelated pages may run | Existing module-specific diagnosis/recovery      |
| Health/ready endpoints and scheduled recovery       | Their existing narrow exceptions only                  | No new Admin admission context                   |

No install/update/code upload/migration/reset/Artisan/shell/maintenance-marker
mutation HTTP endpoint exists in this specification.

## Application settings contracts and storage

Distinct from module settings: ordinary App source declares a small set of dynamic
business/presentation values; the platform supplies guarded storage and projection.
No fake app module and no app business data under `runa_admin`. New public extension
values live under `Runa\Contracts\Settings`; adapters under
`Runa\Support\Settings`. These exact signatures are specified:

```php
interface ApplicationSettingProvider
{
    /** @return list<ApplicationSetting> */
    public function settings(): array;
    /** @return list<ApplicationLogo> */
    public function logos(): array;
}
ApplicationSetting::__construct(string $key, string $type, string|int|bool|null $default,
    string $title, string $readPermission, string $writePermission,
    ?int $maxLength = null, array $choices = [])
ApplicationLogo::__construct(string $key, string $src, string $accessibleName)

// Container-owned public adapters, not new service bindings supplied by requests.
ApplicationSettings::register(ApplicationSettingProvider $provider): void
ApplicationSettings::read(): array
ApplicationSettings::update(array $patch, int $expectedRevision): array
ApplicationPresentation::read(): array
```

Register only during trusted App bootstrap, frozen before request use; refuse
duplicate keys/providers and foreign Core replacement. Key syntax follows
ApiOperation, max 64 settings and 32 logos; type `string|integer|boolean|enum`,
non-secret scalar/null defaults, no callbacks/config paths. Strings maxLength
1–4096, enum unique choices max 64 with safe key grammar; no implicit coercion.
Titles max 160, same English/no-control rule as Admin descriptors. Defaults match
the declared scalar type, never arbitrary objects.
Reads return `{revision:int,values:array<string,scalar|null>}` filtered by current
read grants; update accepts only known, readable+writable keys, checks grants and
expected revision inside the same transaction and returns the same shape. No
request may choose the actor, audit module or permission. Validation uses safe 422,
unknown/denied keys fail closed, stale revision 409, mandatory Audit failure 503.
Sensitive/encrypted infrastructure options are deliberately not supported here.

`shared.application_settings` is a singleton technical aggregate: bigint identity
id, singleton CHECK/UNIQUE key, positive monotonic bigint revision, JSONB object
values, UTC created_at/updated_at. No external row identity, therefore no mechanical
public_id or soft deletion; clients address the aggregate, never its internal ID.
CHECK object type and positive revision, no business triggers/functions/views.
App-owned migration adds this table explicitly, outside automatic reopen; no
foreign keys or coupling to Admin install data. The platform adapter validates
source-owned keys on every read/write, applies explicit defaults and records one
redacted `application.settings.changed` manual Audit event through AuditRecords in
the same default transaction. No claim that Eloquent captures bulk JSON changes.
App schema/version migration ownership is documented at delivery; owner module
upgrades never overwrite a customer's migration or source provider.

The supplied scaffold remains minimal: no sample business settings. Platform
presentation has built-in optional keys `presentation.name` (string, 1–160) and
`presentation.logo` (enum of source-owned logo keys); App can declare safe extra
values using the provider. Built-in changes require `runa.admin.application.read`
and `runa.admin.application.manage`; extra keys retain their declared grants.
Null removes the runtime override and restores source fallback. No generic upload
system: customer deploys logo assets through App source/public assets, declares
same-origin approved URLs in ApplicationLogo and selects a key stored in DB.
Source validation rejects credentials, query/fragment, traversal, remote origins,
data/javascript URLs, markup and non-image assets. Only reviewed PNG/JPEG/WebP/SVG
static assets already served under existing CSP are selectable; SVG is source-owned
trusted code, never an administrator-uploaded string. No outbound URL fetching.

## Presentation precedence and consumers

`ApplicationPresentation::read(): array` returns the non-secret public projection
`{name:string,logo:array{src:string,accessibleName:string}|null,revision:int}`,
using only the two approved presentation keys, never all app settings. This is
available separately through named GET `/api/presentation`, route
`runa.presentation.show`; no Admin grant required for already public branding.
Canonical `/api/identity` and its keys/manifest validation remain unchanged.
Admin bootstrap includes the same projection. No-store/revalidation prevents a
stale browser override being treated as current after an update/account change.

| Consumer                                           | Effective presentation and failure behavior                                                                                                  |
| -------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| Browser app/Admin shared branding                  | Valid persisted override → app manifest default → code-owned Runa default; each current read sees committed revision without restart         |
| Server-rendered normal errors/mail                 | Same validated runtime projection when DB is available; source defaults on storage failure; no recursive DB failure rendering                |
| Static/native emergency errors and build artifacts | Validated build-time manifest/Runa defaults only; no database access or baked runtime customer override                                      |
| Existing explicit technical mail-logo/CID config   | Preserve developer-owned transport/CID mechanics; adapt selected source asset only through that policy, never load a DB path/remote logo URL |
| Canonical identity/version/compatibility           | Always source-owned, unaffected by presentation settings                                                                                     |

The Runa logo remains in protected code. Customer selection and displayed-name
overrides persist in the database; customers own their approved asset catalogue.
Unknown/removed/invalid persisted logo keys fall back safely, never fetch a URL or
break emergency rendering. Technical mail/CSP/layout policy remains source/config
owned and can reject a selected asset for that consumer. These rules do not make
platform version, application ID, compatibility, APP_URL/KEY, debug, credentials,
drivers, knowledge endpoints or infrastructure editable.

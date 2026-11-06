<!-- runa-knowledge {"purpose":"current_guidance","effective_date":"2026-10-30","applicable_version":"0.7.0","decisions":[]} -->

# Admin Core operations and application settings

Admin 1.0.0, delivered with platform 0.7.0, supplies independent commands, native
atomic profile/access Save, Core administration, framework operations and
application settings. Auth owns its declarations, reads and operation adapters;
[Auth administration](../auth/administration.md) records their grants, signatures,
proofs and independent protocols. [Permissions administration](../permissions/administration.md)
and the [Audit viewer](../audit/administration.md) retain their owning contracts.
The fixed AdminOperations API below supplies framework diagnostics/settings/work
with native transactional Audit. Platform-owned application settings supply
runtime presentation.

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
| AuthBulkSessions               | `preview`, `revoke`: selected/all-user native selection, exact reviewed fingerprint and password proof; independent workflow                                                                                                                                                                                                                              |
| AuthSecurity                   | `requestPasswordReset`, `resetFactors`, `trustedDevices`, `revokeDevice`, `revokeDevices`: safe independent security controls; no credential viewer                                                                                                                                                                                                       |
| AuthPolicies                   | `history`, `exceptions`, `activeExceptions`, `exceptionVersions`, `revokeExceptions`, `preview`, `publish`, `exception`: complete history/consequences/review/publication and account exceptions including revoke/expiry; separate settings runner                                                                                                        |
| AuthPreferences                | `account`, `updateAccount`: guarded target-account language/theme/delivery preferences with separate read/manage/address grants; `resetAccount`: presentation reset; `read`, `write`, `reset`: current principal only                                                                                                                                     |
| AuthDelivery                   | `recipients`: authorized task-specific recipient resolution only, no delivery console, raw routing or second dispatch engine                                                                                                                                                                                                                              |
| AuthStepUp                     | `assertActive`/`activity`: guarded section admission and renewal; `assertVerified`: separate fixed-age/action proof; no generic HTTP assertion endpoint                                                                                                                                                                                                   |
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
API version and audience. It records the historical baseline; current handlers follow reviewed current contracts and versions, not a frozen signature requirement. The
public table above records their disposition rather than exposing private classes.

## Administrative Auth reads

The delivered `AuthAdministration` API 2.1.0, DTO signatures, bounded queries,
field projection and lifecycle read are owned by the
[Auth administration guide](../auth/administration.md#guarded-administrative-reads).
The implementation stays in Auth; Admin never queries foreign users, addresses or
preferences. Existing Identity/Eligibility and other guarded signatures remain
defined by their owning contracts. Auth module 5.0.0 includes these reads;
AuthAdministration uses its independent API version 2.1.0. Installation/update
history is declared by Auth, never inferred from the interface version.

## Framework operation adapters and Audit

These are named trusted `Runa\Integration\Core\Admin*` bridges for Admin handlers,
not customer imports. Requests name stable commands only. The owning engine keeps
its current public signature; each bridge enforces the permission below plus
target/source/revision checks. No generic engine-method dispatcher is allowed.

| Command / grant                                  | Exact engine entrypoint                                                                                                   | Concurrency, outcome and Audit disposition                                                                                                                    |
| ------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| diagnostics.read / `runa.admin.diagnostics.read` | ModuleRegistry.snapshot(), ModuleManagement.read(string $module): ManagementView and cached ModuleAvailability.statuses() | Registry state filter, generation and safe observed/expiry/reason data; no diagnostic execution on read                                                       |
| health.diagnose / `runa.admin.health.diagnose`   | ModuleHealth.diagnose(string $id): array                                                                                  | Independent deadline/lock; persisted observations, safe fixed codes; diagnostic observation is not a business mutation event                                  |
| modules.preview / `runa.admin.modules.manage`    | ModuleTransitions.preview(string $intent, string $target): AdministrativePreview                                          | Only existing allowed Optional intents; exact reviewed graph/source/generation and active work                                                                |
| modules.apply / same                             | ModuleTransitions.apply(AdministrativePreview $preview): TransitionResult                                                 | Independent reservation/hook/publication runner; add Audit participation to reservation/final publication/compensation state boundaries                       |
| modules.status/resume/cancel / same              | ModuleTransitions.status/resume/cancel(string $operationId): TransitionResult                                             | Exact native status/recovery and generation; add missing meaningful mutation Audit in the owning transaction; no blanket recovery                             |
| settings.status/recover / same                   | ModuleSettings.status/recover(string $id): TransitionResult                                                               | Owning recovery only, no generic undo; preserve SettingsParticipant restore Audit                                                                             |
| work.read / `runa.admin.work.read`               | ModuleWork.history(string $module, int $limit = 100): array; status(string $id): WorkStatus                               | Default 100 plus explicit truncation; bounded requested limit up to native 1000, subject to response byte limit; safe progress code, no fabricated percentage |
| work.cancel / `runa.admin.work.cancel`           | ModuleWork.requestCancellation(string $id): WorkStatus                                                                    | Cooperative request only, owning cancellable policy; add transaction/revision-safe Audit at the actual cancel_requested write, not after it                   |

If a signature above is abbreviated, parameter/return types are inherited exactly
from the explicitly named first signature, never inferred from an HTTP payload.
SettingsParticipant publication hooks retain their native behavior. The framework
uses OperationalParticipant for
lifecycle boundaries and cancellation. A post-commit wrapper record is
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
`Runa\Support\Settings`. The delivered signatures are:

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
ApplicationSettings::update(array $patch, int $expectedRevision, ?ApplicationImages $images = null): array
ApplicationPresentation::read(): array
```

Register only during trusted App bootstrap, frozen before request use; refuse
duplicate keys/providers and foreign Core replacement. Logo key uploaded is reserved
for runtime raster assets. Key syntax follows
ApiOperation, max 64 settings and 32 logos; type `string|integer|boolean|enum|timezone`,
non-secret scalar/null defaults, no callbacks/config paths. Timezone values use validated IANA identifiers. Strings maxLength
1–4096, enum unique choices max 64 with safe key grammar; no implicit coercion.
Titles max 160, same plain-text/no-control rule as Admin descriptors. Defaults match
the declared scalar type, never arbitrary objects.
Reads return `{revision:int,values:array<string,scalar|null>}` filtered by current
read grants; update accepts only known, readable+writable keys, checks grants and
expected revision inside the same transaction and returns the same shape. No
request may choose the actor, audit module or permission. Validation uses safe 422,
unknown/denied keys fail closed, stale revision 409, mandatory Audit failure 503.
Sensitive/encrypted infrastructure options are deliberately not supported here.

Setting metadata/value intersections deduplicate identical grants within each
read or prewrite phase, then discard those decisions before the next phase.
Public presentation selects SHA-256 image fingerprints using PostgreSQL's built-in
bytea hashing rather than transferring all image bytes. An asset request fetches
only its validated image column; protected settings read asset-presence flags.
Public response fields, immutable URLs and current grant enforcement are unchanged.

`shared.application_settings` is a singleton technical aggregate: bigint identity
id, singleton CHECK/UNIQUE key, positive monotonic bigint revision, JSONB object
values, UTC created_at/updated_at. No external row identity, therefore no mechanical
public_id or soft deletion; clients address the aggregate, never its internal ID.
CHECK object type and positive revision, no business triggers/functions/views.
The platform owns the forward migration
`src/runa/database/migrations/2026_11_03_000009_create_application_settings.php`
and the protected implementation under `src/runa/Settings`; both are delivered source for platform 0.7.0. There are no foreign keys or Admin-owned rows.
The public Contracts/Settings values and Support/Settings adapters above are thin
entrypoints to that platform implementation. App owns registered definitions,
selected values and deployed assets, never the platform migration.

Explicit first installation/development initialization runs the platform migration
primitive before required platform seeding/Core installation and App migration/
seeding. Forward update uses the existing platform-migrations stage before Core
forward steps and application migrations; it preserves existing values and revision.
Delivery includes this protected migration and records it in shared.migrations
through existing platform migration ownership. The protected database inventory and installation/update fixtures include this table. Reopen, provider
boot, Admin install and HTTP never migrate or create the table. No App migration
stub or customer-authored prerequisite is generated. Protected settings reads/writes
fail safely when storage is absent; public presentation alone falls back to source
defaults with revision 0. Admin availability cannot create or repair the store. The platform adapter validates
source-owned keys on every read/write, applies explicit defaults and records one
redacted `application.settings.changed` manual Audit event through AuditRecords in
the same default transaction. No claim that Eloquent captures bulk JSON changes.
Platform updates never overwrite a customer's application migration or source
provider. Module settings and Admin lifecycle do not own this global store.

The supplied scaffold remains minimal: no sample business settings. Platform
presentation has built-in optional keys `presentation.name` (string, 1–160) and
`presentation.logo` (source-owned logo keys or uploaded). Built-ins also cover
language/theme/timezone defaults and automatic/custom favicon; see the uploaded
branding contract below. App can declare up to 58 safe extra values using the provider. Built-in changes require `runa.admin.application.read`
and `runa.admin.application.manage`; extra keys retain their declared grants.
Null removes a runtime override and restores source fallback. Deployed logo assets
remain selectable through ApplicationLogo; uploaded logo/favicon PNGs use the bounded
transactional contract below rather than arbitrary paths or a generic file library.
Source validation rejects credentials, query/fragment, traversal, remote origins,
data/javascript URLs, markup and non-image assets. Only reviewed PNG/JPEG/WebP/SVG
static assets already served under existing CSP are selectable; SVG is source-owned
trusted code, never an administrator-uploaded string. No outbound URL fetching.

### App registration and guarded use

Owner: platform for storage/adapters, App for declarations and assets. Audience:
ordinary trusted App PHP, including optional App module consumers. Entry points
are the two container-owned `Runa\Support\Settings` adapters above and the four
`Runa\Contracts\Settings` declarations. Runtime delivery includes the platform
migration; internal `Runa\Settings` engines are not customer imports. Registration
requires a real `App\` class under `src/app`, excluding module provider classes;
register the App provider during `AppServiceProvider::boot`. Registration is atomic
and freezes after all providers boot or at first catalogue use. It never queries
or initializes storage. App modules consume these adapters but do not replace the
global source catalogue or grant themselves new authority.

```php
// src/app/Settings/PresentationSettings.php
namespace App\Settings;

use Runa\Contracts\Settings\ApplicationLogo;
use Runa\Contracts\Settings\ApplicationSettingProvider;

final class PresentationSettings implements ApplicationSettingProvider
{
    public function settings(): array { return []; }
    public function logos(): array
    {
        return [new ApplicationLogo('customer', '/brand/customer-logo.png', 'Customer logo')];
    }
}

// In AppServiceProvider::boot():
app(\Runa\Support\Settings\ApplicationSettings::class)
    ->register(new \App\Settings\PresentationSettings);
```

Deploy the static asset before registering/selecting it. Additional business keys
use ApplicationSetting with already source-declared qualified read/write grants;
they are not environment/configuration mappings. Strings are nonempty UTF-8 plain
text without control/format characters; null selects the declared default. Integer
values are restricted to the JavaScript safe integer range. Omitted keys retain
stored values; null removes that override. Removed definitions/asset keys remain
in storage for customer deployment restoration but are never returned as current
values/options. Invalid persisted types select source defaults. No values are
cached in long-lived PHP workers.

Protected `read/update` require a trusted current principal, current Permissions
availability, grants and registry/authority locks; writes additionally require
available Audit and record one `application.settings.changed` event with fixed
module `runa.audit`. The event contains changed key names and the new revision,
never values, configuration or a fabricated aggregate UUID. These public adapters
do not require Admin to own or initialize storage. Admin's HTTP wrapper additionally
requires its own current entry/password proof. Arbitrary request actor/module/
permission fields are rejected. Audit failure rolls back values and revision.

The fixed AdminOperations read view `application` accepts no input and returns
`{revision,values,definitions}`. Definitions are keyed by readable setting and
contain only `{title,type,max_length,choices,writable}`; unreadable defaults,
choices and grant identifiers are absent. Command `application.update` accepts
only `{revision:int,patch:object}` and returns `{revision,values}`. Each patched
key requires both declared read/write grants. Extra App keys retain their own
grants independently of built-in `runa.admin.application.read/manage`. The shared
operational transport retains its 64 KiB input limit. PHP callers may supply a
bounded patch up to 512 KiB. Revisions are positive safe integers; no silent retry.
Protected storage failure is 503; public presentation alone selects source defaults
and revision 0. Maintenance behavior is unchanged.

## Presentation precedence and consumers

`ApplicationPresentation::read(): array` returns the non-secret public projection
`{name:string,logo:array{src:string,accessibleName:string}|null,favicon:?string,defaults:array,revision:int}`,
using only approved name/logo/favicon/display-default fields, never all app settings. This is
available separately through named GET `/api/presentation`, route
`runa.presentation.show`; no Admin grant required for already public branding.
Canonical `/api/identity` and its keys/manifest validation remain unchanged.
Admin bootstrap includes the same projection. No-store/revalidation prevents a
stale browser override being treated as current after an update/account change.

| Consumer                                           | Effective presentation and failure behavior                                                                                                                                  |
| -------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Browser app/Admin shared branding                  | Valid persisted override → app manifest default → code-owned Runa default; each current read sees committed revision without restart                                         |
| Server-rendered normal errors/mail                 | Same validated runtime projection when DB is available; source defaults on storage failure; no recursive DB failure rendering                                                |
| Static/native emergency errors and build artifacts | Validated build-time manifest/Runa defaults only; no database access or baked runtime customer override                                                                      |
| Existing explicit technical mail-logo/CID config   | Preserve developer-owned transport/CID mechanics; attach validated source assets or uploaded PNG bytes through CID, never fetch a remote logo or accept a DB filesystem path |
| Canonical identity/version/compatibility           | Always source-owned, unaffected by presentation settings                                                                                                                     |

The Runa logo remains in protected code. Customer selection and displayed-name
overrides persist in the database; customers own their approved asset catalogue.
Unknown/removed/invalid persisted logo keys fall back safely, never fetch a URL or
break emergency rendering. Technical mail/CSP/layout policy remains source/config
owned and can reject a selected asset for that consumer. These rules do not make
platform version, application ID, compatibility, APP_URL/KEY, debug, credentials,
drivers, knowledge endpoints or infrastructure editable.

The native transaction redesign is retained by independent Auth 5.0.0 and
Permissions 2.1.0, with guarded AuthProfiles 2.1.0 / PermissionsAccess 2.2.0. Other current guarded
interfaces remain at their declared versions. The
[native transaction contract](../../architecture/module-transactions.md) and
[current composition guide](composition.md) supersede the original unchanged-API
lock/preview ordering proposal. No generic framework method dispatch is introduced.

## Operational HTTP and presentation contract

Owner: framework for engines, Admin for guarded coordination. Supported audience:
application presentation through AdminOperations 1.0.0 and the `@/runa/admin`
controller; the `Runa\Integration\Core\AdminOperations` implementation is reserved.
Runtime mode: independent native runners; synchronous configuration uses the entry
transaction. Exact guarded PHP signatures:

```php
interface AdminOperations
{
    /** @param array<string,mixed> $input @return array<string,mixed> */
    public function read(string $view, array $input = []): array;
    /** @param array<string,mixed> $input @return array<string,mixed> */
    public function execute(string $command, array $input): array;
}
```

Named GET `/api/admin/operations/{operation}` (`runa.admin.operations.read`) and
POST at the same path (`runa.admin.operations.execute`) admit only the fixed
views/commands below. No request selects a class, engine method, actor or SQL.
Unknown keys, stale generations, malformed UUIDs and unreviewed lifecycle writes
fail closed. Every invocation rechecks entry/password proof and its exact grant.

Read views: `capabilities` (entry-only safe grant advertisement), `diagnostics` (`filter=enabled|registered|all`), `module` (`module`),
`history` (`module`, optional UUIDv7
`cursor`, `limit=1..100`; per-kind management authority), `work` (`module`, optional
`limit=1..1000`), `work.status` (`id`), `modules.status` (`id`), `settings.status`
(`id`). Module reads expose safe diagnostic state without health policy configuration.
There is no generic module settings read/update endpoint or settings.read grant.
Administrator-editable settings belong to an owned page with history and its
module-specific publication workflow, as Auth policy already supplies. The
settings.manage grant remains limited here to retained operation history/status/
recovery; it does not authorize a generic edit. ModuleSettings remains the native
settings/revision/redaction/publication engine.

Health policies and optional integration enabled/config values are defined exclusively
in module source; no management grant, configuration read/write command or editor exists. Explicit diagnosis and observed health remain available.

Commands: `health.diagnose` (`module`), `modules.preview` (`module`, `intent=enable|disable|unregister`),
`modules.apply` (`review`, `confirmed=true`), `modules.resume` / `modules.cancel`
(`id`), `settings.recover` (`id`), `work.cancel` (`id`). Lifecycle review is a ten-minute,
actor-bound authenticated encrypted capability over the complete native preview;
apply recomputes it and the engine rechecks source, plans, work and generation.
Only Optional targets are admitted. A request-scoped trusted authority check is
repeated by native participants inside reservation/publication transactions; it
unwinds on refusal and never changes CLI authority. Grant/proof loss during hooks
fences publication and retains the native operation for authorized reconciliation.
Results retain native terminal/pending/recovery
statuses; refusal/failure never becomes committed success.

Framework OperationalParticipant is reserved integration, implemented by Audit:
`recording(Connection $connection, string $module, string $operation, array $context): void`.
Only fixed technical identities/revisions enter history; configuration, secrets,
runner tokens and private work payloads never do. Transition reservation/durable phases/publication/compensation and first cooperative
cancellation requests record inside their actual PostgreSQL write transaction.
Installed Audit refusal rolls back that boundary. Framework-only installations
without Audit retain a no-op participant. Settings use existing SettingsParticipant.

## Module operation history

AdminOperations.read('history', ['module' => string, 'cursor' => optional UUIDv7,
'limit' => optional int 1..100, default 25]) returns
`{operations:list<{public_id:string,target:string,intent:'enable'|'disable'|'unregister'|'settings',status:string,created_at:string,updated_at:string,actions:list<string>}>,next_cursor:?string}`.
This additive guarded AdminOperations 1.0.0 read is runtime-delivered through the
existing named operational route, owned by Admin's trusted framework adapter.
Existing entry/session/password/availability admission applies. modules.manage
permits lifecycle rows; settings.manage permits settings rows. Neither grant
exposes the other kind. Empty authority is refused. Rows match the selected target
or include it in the affected module set; newest UUIDv7 first with exclusive cursor.
Only safe metadata is selected; private encrypted payloads, snapshots, tokens,
internal IDs and setting values never cross this read. Actions advertise native
status/resume/cancel or settings status/recover only for the row's allowed kind and
state. Execution retains its independent current grant/native-state checks.

Framework owns internal ModuleOperationHistory::read(string $module, array $intents,
?string $cursor = null, int $limit = 25): array at
src/runa/ModuleFramework/ModuleOperationHistory.php. It is a trusted-integration
read engine over shared.module_transitions, delivered at runtime, not a customer
extension/consumption API. No new persistence, migration or Audit write is involved.
History describes recorded lifecycle/settings operations, not all module activity;
other business changes retain their owning Audit history.

## Task history

The framework-owned public runtime adapter ModuleWork.history(string $module,
int $limit = 100): array returns a newest-first bounded list of WorkStatus values
from retained native work, including terminal states. Its implementation remains
in src/runa/Support/Modules/Work/ModuleWork.php, consumed by trusted management
and module authors through the container-owned adapter. Limits are 1..1000; it
uses the same status/lease/capability projection as active(), without decrypting
work payloads or introducing another store. Active-only lifecycle fences retain
active() unchanged. AdminOperations.read('work', ['module' => string, optional
'limit' => int]) uses history with the existing work.read grant and explicit
truncation. Terminal evidence offers status reads, never cancellation/replay.

## Display defaults and uploaded branding

The platform owns optional built-ins presentation.locale (pl/en, source pl),
presentation.theme (system/light/dark, source system), presentation.timezone (IANA,
source Europe/Warsaw) and presentation.favicon (automatic/uploaded). Explicit
account/browser language/theme wins over application defaults; timezone affects
instant presentation, never UTC storage or date-only values.

Platform public value Runa\Contracts\Settings\ApplicationImages lives in
src/runa/Contracts/Settings/ApplicationImages.php (runtime). Constructor
`__construct(array $png)` accepts raw validated PNG bytes keyed logo/logo_icon/favicon;
logo and its generated 64px icon are paired. The owning extension is
`ApplicationSettings::update(array $patch, int $expectedRevision, ?ApplicationImages $images = null): array`.
Admin application.update transports optional base64 images with its existing patch,
revision and authority; a single transaction publishes settings, binary assets and
redacted Audit. PNG is bounded to 512 KiB/1024px for logos and 32 KiB/exactly64px for
icons. Shared public Runa\Support\Images\RasterImageContent::safe(string $bytes,
string $mime): bool lives under src/runa/Support/Images; its internal format parser
under src/runa/Images is reused by Auth and settings. It validates container metadata,
not a complete pixel decoder. Browser Canvas decodes bounded PNG/JPEG/WebP input,
preserves aspect ratio/transparency and generates PNG without original metadata.

Binary columns branding_logo/branding_logo_icon/branding_favicon share the existing
protected singleton and revision, so refusal/rollback cannot orphan files. Public
GET /api/presentation/assets/{kind}/{hash}.png serves only current logo/icon/favicon
bytes matching their SHA-256, fixed image/png, nosniff and immutable cache headers.
Public presentation projects selected URLs and display defaults, never raw bytes.
Custom favicon wins over automatic uploaded-logo icon; resets restore automatic/source
presentation. Static/emergency build assets remain source-owned. Runtime mail logo
URLs remain same-origin and do not trigger outbound image fetches.

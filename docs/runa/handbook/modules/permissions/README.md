# Permissions

Current Core `runa.permissions` source develops at independent version `2.1.0`
for platform >=0.7.0 <0.8.0, requiring `runa.auth ^2.0.0 || ^3.0.0`, `runa.audit ^1.0.0` and framework API `^1.0.0`.
Install with the existing fenced module updater, after Audit and Auth. The explicit
transactional `null -> 0.1.0` edge creates dictionaries and the protected
`runa.permissions.super_admin` role, with **no user assignments**. Matching installed
versions are no-ops. Source-owned [Admin administration](administration.md) is supplied without a backend dependency on Admin or any new package.

See the [built-in contract audiences](../../architecture/public-api.md#built-in-module-contracts-and-execution)
for public APIs, local helpers and reserved integration. Contract paths remain
stable; private module implementation is not an application extension seam.

## Owned storage and authorization

`runa_permissions.permissions` and `roles` persist `code`, `name_pl`, `name_en`,
`description_pl`, `description_en`, UUIDv7 public identities, source ownership,
optimistic versions, timestamps and soft deletion. Codes remain globally unique,
including retained rows. Source-owned codes start with their module ID plus `.`;
custom dictionaries use their own distinct code space. The system role is protected
and nondeletable. Guarded [ordinary assignment, custom-role, preset and access-copy
operations](access.md) are delivered. Dedicated [protected-authority management](authority.md) is delivered.

`role_permissions`, `user_roles` and `user_permissions` use unique assignment pairs,
bigint identity keys and same-schema RESTRICT/RESTRICT foreign keys. Auth subjects
are UUIDv7 references only, without Auth SQL, foreign keys, models or relationships.
Use owning Actions for meaningful writes with same-default-connection Audit.

Effective action grants are the union of direct grants and active assigned roles.
The protected system role supplies every current/future **declared** ordinary action
grant; an unknown permission remains denied. Account eligibility, live HTTP sessions,
required password/factor steps, step-up, domain rules and module availability remain
independent. No generic superadmin `Gate::before` bypass of record policies exists.

The shared OperationAuthorizer resolves current trusted identity through a dedicated
non-recursive Auth public contract at the framework boundary. Application consumers
must not use that internal bridge or resolve private implementations. Missing data,
incompatible versions, stale source, maintenance or unavailable dependencies fail
closed. No assignment/eligibility cache is retained: workers read committed database
state on the next request, including role membership changes. No logout, polling or
WebSocket is needed. Disabled Optional modules retain all rows and assignments;
their operations stay unavailable and re-enabling restores applicable grants.

## Module-owned declarations

Reuse the manifest's existing `management` declaration category. A
`ManagementProvider::permissions()` list declares **local** keys with Polish/English
names and descriptions. Permissions qualifies them as `<module-id>.<key>` and
projects the five persisted dictionary columns using DictionaryEntry. Do not use
another owner's fully qualified keys. Names are bounded to 160 characters and
descriptions to 1000; duplicate or unsupported declarations refuse synchronization.
Existing management render metadata stays compatible; it does not grant access.

Implement optional `RoleProvider` alongside ManagementProvider to supply
`roles(): list<RoleDefinition>`. Each RoleDefinition contains a local single-part
DictionaryEntry code and a unique list of the same module's local permission keys:

```php
public function roles(): array
{
    return [new RoleDefinition(
        new DictionaryEntry('reader', 'Czytelnik', 'Reader', 'Odczyt danych.', 'Read data.'),
        ['records.read'],
    )];
}
```

Declare `records.read` through the provider's existing `permissions()` method and
use `<module-id>.records.read` in protected ApiOperation/HTTP adapters. Implement
the other ManagementProvider methods normally; no new manifest category is needed.
Predefined roles are source-owned and read-only to administrative editing. They are
never automatically assigned. Removing declarations from source preserves their
database definitions, existing grants and references. Retirement requires an explicit
owner migration/lifecycle decision, never automatic deletion.

Synchronization runs only in explicit transactional install/update preparation.
Initial Permissions installation catches up registered available source definitions,
including disabled modules; later owner-scoped updates synchronize that module in
the same reserved transaction. Source changes require deliberate module version/
upgrade decisions. No normal request or provider boot writes dictionaries.

Before updates, inspect source/state changes through the existing module diagnostic:

```sh
./bin/artisan runa:permissions:declarations --payload='{"module":"app.documents"}'
```

This trusted operator read reports old/new dictionaries and role memberships,
affected distinct-user counts and a source/state fingerprint. It does not grant or
apply anything. The preparation adapter rechecks a reviewed preview under registry
and shared authority locks before applying changes; stale previews refuse. Every
changed definition records complete old/new values/memberships and impacted-user
count in Audit. Bounded per-definition events and a nested savepoint make Audit
failure roll back the whole sync, including when an outer caller catches it.
Idempotent/no-op synchronization produces no duplicate declaration events.

## Public permission checks and introspection

Resolve `Permissions` API `1.0.0` through ModuleApis:

```php
use Runa\Modules\Permissions\Contracts\Permissions;
use Runa\Support\Modules\Api\ModuleApis;

$permissions = app(ModuleApis::class)->resolve('runa.permissions', Permissions::class, '1.0.0');
$allowed = $permissions->allows('app.documents.records.read');
```

`allows` checks only the current server-owned human PrincipalScope. A client cannot
choose the actor; a missing principal is denied. Prefer protected ApiOperation
declarations for module ingress so availability and authorization run before input
validation/controller construction. Ordinary application code may use Laravel
`Gate::allows('app.documents.records.read')` within the authenticated server scope.
Namespaced action checks with record arguments continue to the record gate/policy
after the grant check; plain `update`/`delete` model policies retain their own logic.
Policies can compose permission checks with module-specific record rules.

`explain($userPublicId, $after = null)` requires
`runa.permissions.introspection.read`. It returns an owned immutable PermissionPage
with eligibility, system-role status and at most 100 permission entries: code,
direct assignment, up to 32 role public IDs (plus truncation), module availability
and effective grant. `next` is the exclusive code cursor. No foreign bigint IDs,
private Auth details or credentials appear. This is a bounded backend API, not a
management page. Source dictionaries may include unassigned ordinary actions;
effective is still false without a grant and current eligibility/availability.

## Delivery boundary

Custom-role/preset CRUD and reviewed assignment/copy operations use PermissionsAccess
2.1.0 under the [ordinary access contract](access.md). Protected superadmin
assignment/removal, last-superadmin participation and confirmed custom-role force
deletion use the separate [protected authority contract](authority.md). The neutral
AuthorityGuard now resolves the trusted Permissions participant and refuses missing
integration. Trusted OperatorAuthority assigns the real system role exactly once
under Auth's initialization ownership and the common registry/authority transaction.
Installer invitations and private no-mail CLI setup use that same participant.
No request-boot grant or security bypass is introduced.

See [CHANGELOG](CHANGELOG.md), [module foundations](../../architecture/module-foundations.md)
and [Auth](../auth/README.md). Full integration/security/installation verification
belongs to the roadmap's final verification point.

## Public user selection

Resolve PermissionsSelections 1.0.0 through ModuleApis and pass UserSelection with
one/selected UUIDv7 publicIds (unique, at most 100), or empty publicIds and explicit
all=true. `users` requires current `runa.permissions.users.select`; it returns
canonical sorted selection intent, never a durable authorization or resolved Auth
model. AuthBulkSessions rechecks this public operation on preview AND execution
through its neutral BulkUserSelection bridge. The independent Auth bulk capability,
exact impact fingerprint, explicit Admin review confirmation and atomic
limits remain mandatory. The owning Auth operation expands all accounts inside its
transaction; unknown selected identities or overflow refuse the complete operation.
There is no foreign SQL, copied identity store or implicit role-management grant.

Source-only 0.3.0 -> 0.4.0 preparation introduces this API, operator participation
and localized selection declaration; it transforms no retained assignments.
Matching-source preparation is a no-op. Missing Core source/data or an unresolved
reservation refuses operator recovery; ordinary business gateways also enforce
availability and current Auth eligibility. Fix source/update recovery first.

The explicit [development corpus](../../data/seeding.md#explicit-module-development-contributors)
adds fixed local role/direct/preset examples through DevelopmentAccess and an owned
scenario ledger. It never replaces ordinary guarded PermissionsAccess or step-up,
never grants protected membership through ordinary fixtures and never runs in
production initialization. The actual first fixture administrator uses Auth's
existing trusted operator participant.

Native assignment composition follows the [owning phase contract](../../architecture/module-transactions.md); arbitrary ambient whole-write nesting is intentionally replaced.

### Shared table export grant

`runa.permissions.tables.export` authorizes supplied table export/printing across
modules. It never grants access to a page, record or field. Owning read permissions,
availability and record Policies remain mandatory and are rechecked on export.
It is declared by Permissions 2.1.0 and synchronized by `table_exports` forward
preparation. Source role declarations may grant it through the ordinary contract;
no automatic ordinary-user assignment or generic superadmin bypass is added.

### Searchable assignment catalogue

Permissions owns the public `PermissionsAssignments` presentation exported through
`@/runa/permissions`, implemented in `src/runa/modules/permissions/frontend`
and supported for platform/App Admin-section composition, taking `AdminSectionPresentationProps` and emitting
`submit(section: string, command: string, fields?: string[])`. It has no slots and ships in frontend
assets, uses the existing Admin controller/resource lane and leaves native review,
proof, grants, protected membership and transactions with Permissions. Public
PermissionsAccess 2.1.0 extends `definitions(string $kind, ?string $after = null,
array $filters = []): array` with optional `search: string` (at most 160 characters)
and `public_ids: list<string>` (at most 100 UUIDv7 references). The return shape
remains `{items: list<{public_id,code,name_pl,name_en,version}>,next: ?string,total:int}`.
Kinds remain role/permission/preset. Filters run in the owning bounded PostgreSQL
read; pagination preserves the public-ID cursor and totals describe the filtered
set. Proxies, exact-version consumers and native declarations use 2.1.0. This
additive runtime server-consumption API remains guarded through ModuleApis; no
foreign query or grant resolver becomes a presentation API.

The default is a dedicated compact access editor: one catalogue, role/direct-
permission selectors with draft counts, integrated server search, All/Assigned
scope, dense selectable rows and a bounded scroll region. The same selected entry
is never repeated in a separate chip/list above the catalogue. Each category keeps
its own query/cursor across switching; inactive categories load on demand.
Catalogue pages contain at most 25 entries. Selected-ID hydration is bounded to
100; it also keeps unavailable assignments removable from Assigned without exposing
UUIDs. Existing native assignment limits remain 100 per category, independent of
the available catalogue size. Visible-page select/deselect preserves off-page
choices. A failed or pending read cannot authorize selecting stale results.

The toolbar uses the shared small search-field size with bounded desktop width;
category controls fit their labels on wide screens and distribute space on mobile.
Category/scope/page buttons expose pointer, hover, pressed and keyboard-focus
states. Disabled pagination and locked row/bulk choices use a default cursor and
avoid actionable hover treatment. Native checkboxes inherit their row's cursor;
reduced-motion preferences suppress added transitions.

Quiet scope controls, aligned counters and lightweight pagination keep chrome
subordinate to catalogue rows. Touch rows remain taller on narrow screens.

Enter in search runs a read and never submits the enclosing access form. Native
checkboxes, focus treatment and semantic field chrome retain Runa's shared visual
language inside the dedicated editor. Keyboard navigation, readonly browsing,
local loading, Polish/English and light/dark/mobile presentation remain supported.
Review/Revert belongs to the complete roles/direct-permissions draft; the native
controller owns dirty guards, optimistic conflicts, review and operation proof.

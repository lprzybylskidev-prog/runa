<!-- runa-knowledge {"purpose":"proposal","effective_date":null,"applicable_version":"0.7.0","decisions":[]} -->

# Admin declarations, bindings and wire values

Status: specified by RM-2026-016 point 1; not implemented. See [ownership and
delivery](README.md). PHP signatures below are declaration specifications, not
copyable runtime implementations. Named value classes are final readonly, with
public promoted constructor properties. Interfaces and values in this section
belong to `Runa\Contracts\Modules`, unless explicitly named otherwise.

## Optional provider and descriptors

Keep `ManagementProvider::{permissions,actions,components}(): array`, optional
`ManagementEntityProvider::entities(): array`, every current constructor/default,
`ModuleManagement` schema 1 and `ManagementRenderers` v1 registrations unchanged.
Legacy entities remain metadata; they do not acquire executable CRUD implicitly.
The new optional interface is an independent additive extension:

```php
interface ManagementPageProvider extends ManagementProvider
{
    /** @return list<ManagementPage> */
    public function pages(): array;
    /** @return list<ManagementBinding> */
    public function bindings(): array;
    /** @return list<ManagementContribution> */
    public function contributions(): array;
    /** @return list<ManagementWidget> */
    public function widgets(): array;
}
```

Constructor signatures and complete property types:

```php
ManagementPage::__construct(string $key, string $title, string $permission,
    array $sections, int $order = 0, ?string $group = null,
    bool $default = false, ?string $component = null, int $schemaVersion = 1)
ManagementSection::__construct(string $key, string $title, string $readPermission,
    ?string $writePermission, string $binding, array $fields,
    array $commands = [], int $order = 0, bool $required = true,
    ?string $component = null, int $schemaVersion = 1)
ManagementFieldAccess::__construct(ManagementField $field, string $readPermission,
    ?string $writePermission = null, ?string $component = null)
ManagementCommandDefinition::__construct(string $key, string $title,
    ManagementOperation $safety, string $binding,
    string $mode = 'independent', ?string $participant = null,
    bool $destructive = false)
ManagementApiTarget::__construct(string $module, string $contract,
    string $version, array $methods)
ManagementBinding::__construct(string $key, string $handler, array $targets,
    ?string $participant = null)
ManagementContribution::__construct(string $key, string $host,
    ManagementSection $section, int $order = 0)
ManagementWidget::__construct(string $key, string $title, string $permission,
    string $binding, int $order = 0, ?string $component = null)
```

Collections: sections `list<ManagementSection>`; fields
`list<ManagementFieldAccess>`; commands `list<ManagementCommandDefinition>`;
targets `list<ManagementApiTarget>`; methods `list<string>`. `handler` is a
`class-string<ManagementHandler>`, participant a
`class-string<ManagementParticipant>`; targets name actual guarded contracts/API
versions/methods used by that handler, not a reflection invocation instruction.
The declaration loader validates ownership, required/optional dependencies and
targets before executing a handler. Ordinary App registrations use owner `app`
and container-bound App classes; module registrations use their retained module ID.

Keys/permissions/binding identities use `ApiOperation` syntax and 128-byte bound.
New page/section/field/command permission references are fully qualified native
grant codes. The inherited ManagementProvider.permissions() remains the existing
module-local declaration contract; registration never creates grants. Ordinary
App providers without a manifest return an empty permissions list and reference
existing native codes (including explicitly reviewed custom Permissions definitions)
plus their App-owned record Policies. New module-owned source dictionaries continue
through explicit module install/update synchronization, never provider boot. An
independent page does not require inventing a module or permission engine.
Page keys additionally use `[a-z][a-z0-9_]{0,63}`. Title/group are English plain
text, 1–160 characters, no control characters; group may be null. Order is within
±1,000,000. Each provider has at most 32 pages, 128 bindings, 64 contributions and
32 widgets; pages have at most 32 sections; each section retains the current
64-field/32-command limits. Keys are unique within owner/type; at most one default
page per owner. Default selection is the first authorized default, otherwise the
first authorized page; an empty owner is absent. Sort by order, then owned ID.
Invalid/conflicting declarations have safe diagnostic codes; they cannot shadow
another owner's declaration. Aggregate limits apply across a single owner's
providers as well as per provider. Component keys must resolve in the owning
manifest/build registry, never to request-provided imports. App independent
components use the app build registry through the same owned-key validation.

Command mode is `independent` or `atomic`; atomic requires a participant and
revisionRequired=true. A destructive command needs explicit confirmation; preview
and stepUp cannot be weaker than the target operation. Bindings without targets
are allowed only for App-owned operations and named trusted framework adapters;
Core business operations always name their actual guarded target.

Hosts use `<owner>.<page>.<section>`; an app contribution can target a documented
foreign host but its section, bindings, permissions, data and component remain
app-owned. Initial stable hosts: `runa.auth.users.profile`,
`runa.auth.users.access`, `runa.admin.dashboard.widgets` (widget-only). Visual
components use existing SlotHost/SlotContribution schema 1. Profile/access hosts
use contract `admin.subject_section`, version 1, with exact flat scalar props
`publicId:string`, `revision:string`, `readonly:boolean`; dashboard uses contract
`admin.dashboard_widget`, version 1, with `generation:number`, `revision:string`.
The editable
contribution above is separate from a visual slot. No nested DTOs in slot props.

## Owned handler signatures

```php
interface ManagementHandler
{
    public function page(ManagementRead $input): ManagementPageResult;
    public function read(ManagementRead $input): ManagementRecord;
    public function options(ManagementRead $input): ManagementOptions;
    public function preview(ManagementWrite $input): ManagementReview;
    public function execute(ManagementWrite $input): ManagementOutcome;
}
ManagementRead::__construct(?string $publicId = null, ?string $cursor = null,
    int $limit = 25, array $filters = [], array $sort = [])
ManagementWrite::__construct(string $command, ?string $publicId,
    string $revision, array $values, ?array $review = null,
    bool $confirmed = false)
ManagementRecord::__construct(?string $publicId, string $revision, array $values)
ManagementPageResult::__construct(array $items, ?string $next, ?int $total = null)
ManagementOptions::__construct(array $items, ?string $next)
ManagementReview::__construct(string $fingerprint, array $changes,
    string $requirement, bool $everyAction)
ManagementOutcome::__construct(string $status, ?ManagementRecord $record = null,
    ?string $operationId = null)
```

`filters` is `array<string, scalar|null>`, `sort` is
`list<array{key:string,direction:'asc'|'desc'}>`; at most 16 filters, 4 sort keys.
Only source-declared readable/filterable/sortable fields are accepted. Limit is
1–100 or the lower owner limit; cursor is opaque, at most 2048 bytes. `values` is
an allowlisted field map of recursively bounded JSON values, max depth 8 and
64 KiB encoded; no objects/callbacks/model instances. Existing field type/control
bounds remain authoritative. Public IDs are UUIDv7; technical source module IDs,
keys, operation IDs and opaque cursors retain their existing owning grammar.
`items` in PageResult is `list<ManagementRecord>`, in Options is
`list<array{value:string,label:string}>`; options are at most 100 per page.
Unknown totals remain null, never estimated. `changes` is an authorized JSON map
of owner-defined review differences; it carries no secret values. `review` is the
exact serialized owning preview, including its fingerprint, not just a boolean
confirmation. Owner adapters reconstruct and validate the owning readonly DTO.

`status` is `committed`, `pending` or `recovery_required`; only committed may carry
refreshed record data. Independent operation IDs use their native public identity
and status API. `requirement` is `none|password|second_factor|both`. No proof token
is accepted in these DTOs; Auth owns proof in the current server session. Unsupported
handler methods reject with `OperationRejected('admin_operation_unavailable',
'runa.admin', <declared operation>, 404)` rather than empty success.

Handlers validate and authorize their owning reads/writes, invoke explicit public
APIs and map projections. They cannot access foreign persistence. A handler method
does not bypass Admin entry, page/section/field checks or owning record Policies.
The Admin projector intersects all of these authorities, including exports,
lookups, counts, review changes and validation errors. Denied fields disappear
entirely; read-only/unknown submitted keys are refused, never silently ignored.
Sensitive fields expose `{set:boolean}` only and never a stored secret. Required
participation becoming unavailable blocks Save; independent pages remain usable.

## App registration and guarded Admin API

Admin contracts live in `Runa\Modules\Admin\Contracts`:

```php
interface AdminRegistry
{
    public function register(\Runa\Contracts\Modules\ManagementPageProvider $provider): void;
}
interface AdminAccess
{
    public function bootstrap(): array;
    public function page(string $owner, string $page): array;
    public function read(string $owner, string $page, string $section, array $query): array;
    public function preview(string $owner, string $page, array $input): array;
    public function save(string $owner, string $page, array $input): array;
    public function command(string $owner, string $page, string $section, array $input): array;
}
```

Registry is bootstrap-only, frozen before request discovery; duplicate or foreign
replacement throws `InvalidArgumentException`. It is not a guarded API and is not
available over HTTP. App providers register instances implemented in `src/app`.
Module providers continue through existing manifests. AdminAccess arrays use the
exact transport shapes below, avoiding foreign/framework DTOs through ModuleApis.
Admin private implementations own framework conversion. API version is `1.0.0`;
all methods require `runa.admin.enter` plus password proof for action
`runa.admin.enter`, TTL 300 seconds, everyAction=false. Other grants/proof remain
additive. HTML shell admission exposes no protected bootstrap values.

| Method / path                                                | Named route               | Operation                                       |
| ------------------------------------------------------------ | ------------------------- | ----------------------------------------------- |
| GET `/admin`                                                 | `runa.admin.dashboard`    | Empty application shell, then guarded bootstrap |
| GET `/admin/{owner}/{page}`                                  | `runa.admin.page`         | Application shell, owner/page grammar above     |
| GET `/api/admin`                                             | `runa.admin.bootstrap`    | AdminAccess.bootstrap                           |
| GET `/api/admin/{owner}/{page}`                              | `runa.admin.page.read`    | AdminAccess.page                                |
| GET `/api/admin/{owner}/{page}/sections/{section}`           | `runa.admin.section.read` | AdminAccess.read                                |
| POST `/api/admin/{owner}/{page}/preview`                     | `runa.admin.preview`      | AdminAccess.preview                             |
| POST `/api/admin/{owner}/{page}/save`                        | `runa.admin.save`         | AdminAccess.save                                |
| POST `/api/admin/{owner}/{page}/sections/{section}/commands` | `runa.admin.command`      | AdminAccess.command                             |

All API operations are authenticated, CSRF-protected, no-store and maintenance
blocked. Reads never mutate configuration or run diagnosis; diagnosis is a command.
Query `kind` selects only `record|page|options`, with the ManagementRead fields.
Command input is ManagementWrite, mapped camelCase to the exact wire spelling
below; there is no class/method/permission query parameter. The owning adapter
controls filter, selection, pagination, export and payload bounds. HTTP transport
uses Form Requests and JsonResources; administrative write errors are English.

## Wire schema 1

All response objects reject unknown executable fields. JSON values are finite,
bounded as above. Snake case is canonical on the wire; frontend controllers map
to camelCase only in local state. Complete shape notation (`?` means optional):

```text
Bootstrap = {schema_version:1, generation:int, revision:string,
  owners:[{id:string,title:string,default_page:string,
    pages:[{key:string,title:string,href:string,group:string|null,order:int}]}],
  dashboard:{widgets:[Widget]}, presentation:{name:string,
    logo:{src:string,accessibleName:string}|null,revision:int}}
Page = {schema_version:1, owner:string, key:string, title:string,
  generation:int, revision:string, sections:[Section]}
Section = {key:string,title:string,readonly:boolean,required:boolean,
  component:string|null,fields:[Field],commands:[Command],record:Record|null,
  diagnostic:string|null}
Field = {key:string,type:string,label:string,readonly:boolean,sensitive:boolean,
  filterable:boolean,sortable:boolean,lookup:boolean,
  required:boolean,max_length:int|null,choices:[string],multiple:boolean,
  component:string|null}
Command = {key:string,title:string,mode:'independent'|'atomic',destructive:boolean,
  requirement:'none'|'password'|'second_factor'|'both',preview_required:boolean}
Widget = {id:string,title:string,component:string|null,values:JsonMap,
  observed_at:string|null,expires_at:string|null,diagnostic:string|null}
Record = {public_id:string|null,revision:string,values:JsonMap}
Read = Record | {items:[Record],next:string|null,total:int|null}
  | {items:[{value:string,label:string}],next:string|null}
Write = {command:string,public_id:string|null,revision:string,values:JsonMap,
  review?:JsonMap|null,confirmed?:boolean}
AggregateInput = {revision:string,participants:[{id:string,input:Write}]}
Preview = {revision:string,participants:[{id:string,review:
  {fingerprint:string,changes:JsonMap,requirement:string,every_action:boolean}}]}
Save = {status:'committed',revision:string,
  participants:[{id:string,record:Record|null}]}
CommandResult = {status:'committed'|'pending'|'recovery_required',
  record:Record|null,operation_id:string|null}
Error = {message:string,reason:string,
  errors?:{<participant.section.field>:[string]},correlation_id?:string}
```

Generation is nonnegative; owner revision values are opaque strings (1–256 bytes).
Aggregate revisions are server fingerprints of ordered participant identities,
source hashes/generation, subject UUIDs, owning revisions and related reads. No
submitted fingerprint grants authority. At most 16 participants; total aggregate
input max 256 KiB; reject duplicate participants and unknown/missing required ones.
Admin responses are bounded to 1 MiB, or a lower owning API limit. Exceeding the
bound returns a safe limit error; never silently drop required fields/participants.
Timestamps are RFC3339 UTC. No internal database IDs, paths, raw exceptions, secrets
or private config appear in any projection. Page descriptors are permission-filtered
before serialization. Removed authority causes protected state to unmount, not
reuse a stale bootstrap. A newly forbidden dirty field is cleared from client
memory; drafts of still-readable fields are preserved.

Outcome invariants: pending/recovery_required require a native operation_id and
record=null; committed aggregate Save has no partial/pending participant results.
Field lookup=true uses the section's authorized options adapter and its owning
reference binding; it never serializes a model or arbitrary URL. Filter/sort flags
are removed or false when their underlying field is not readable. The new page
projection has schema 1 independently of legacy ModuleManagement schema 1; do not
reinterpret either one's serialized shape as the other.

Malformed declarations/DTOs throw `InvalidArgumentException`; owning validation
and revision conflicts retain their safe errors. HTTP maps unauthenticated to 401,
authorization to 403, hidden/missing identities to 404, stale revision/preview,
lost participants or incompatible composition to 409, validation to 422, throttling
to 429 and unavailable maintenance/module/Audit to 503. Existing Auth proof
challenge response semantics take precedence over a generic 403. Never echo
unauthorized submitted values. Root rollback and refusal handling are specified
in [composition](composition.md).

<!-- runa-knowledge {"purpose":"current_guidance","effective_date":"2026-10-30","applicable_version":"0.7.0","decisions":[]} -->

# Admin declarations, bindings and wire values

Status: point 3 implements declarations/discovery, capability interfaces,
field-bound options, typed widget results and source-bound component/build
projection. Point 4 adds compiled resolution, the shared session shell, entry
advertisement and page-read/document HTTP. Point 5 adds section-read HTTP, the frontend resource read lane, independent
write/preview HTTP and generated forms. Point 6 delivers native atomic coordination
and profile/access adapters; point 7 adds complete Auth administrative declarations. Point 8 adds owning Permissions pages and the immutable Audit viewer; points 9–10 deliver framework operations and settings. Point 11 adds [generator and consumer examples](examples.md). See
[ownership and delivery](README.md). Named value classes are final readonly, with
public promoted constructor properties. Interfaces and values in this section
belong to `Runa\Contracts\Modules`, unless explicitly named otherwise.

## Optional provider and descriptors

`ManagementProvider::{permissions,actions,components}(): array` and optional
`ManagementEntityProvider::entities(): array` still serve metadata consumers through
`ModuleManagement` schema 1 and `ManagementRenderers` v1. Entities describe existing
non-executable metadata; pages describe executable owned bindings. Neither grants
implicit CRUD. This distinction is intentional, not a signature freeze. The optional
page extension is:

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

`ManagementSection.view` is source-owned presentation intent, `record` (default)
or `table`; other values are refused. Both use the existing resource reader
capability. It grants no read or command authority. Admin projects it unchanged
for authorized sections, including unavailable sections. The table default is
delivered; generated record forms retain their stated delivery status. Module and
App providers select `view: 'table'` for automatic bounded collection composition,
without changing handler capabilities or guessing intent from field names.

```php
ManagementPage::__construct(string $key, string $title, string $permission,
    array $sections, int $order = 0, ?string $group = null,
    bool $default = false, ?string $component = null, int $schemaVersion = 1)
ManagementSection::__construct(string $key, string $title, string $readPermission,
    ?string $writePermission, string $binding, array $fields,
    array $commands = [], int $order = 0, bool $required = true,
    ?string $component = null, int $schemaVersion = 1, string $view = 'record')
ManagementFieldAccess::__construct(ManagementField $field, string $readPermission,
    ?string $writePermission = null, ?string $component = null)
ManagementCommandDefinition::__construct(string $key, string $title,
    ManagementOperation $safety, string $binding,
    string $mode = 'independent', ?string $participant = null,
    bool $destructive = false, string $intent = 'action', ?string $proofAction = null)
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
target shape before executing a handler; current availability is checked per binding. Ordinary App registrations use owner `app`
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
interface ManagementHandler {}
interface ManagementResourceReader extends ManagementHandler
{
    public function page(ManagementRead $input): ManagementPageResult;
    public function read(ManagementRead $input): ManagementRecord;
}
interface ManagementOptionsReader extends ManagementHandler
{
    public function options(ManagementOptionsRead $input): ManagementOptions;
}
interface ManagementWidgetReader extends ManagementHandler
{
    public function widget(ManagementRead $input): ManagementWidgetResult;
}
interface ManagementWriter extends ManagementHandler
{
    public function preview(ManagementWrite $input): ManagementReview;
    public function execute(ManagementWrite $input): ManagementOutcome;
}
ManagementRead::__construct(?string $publicId = null, ?string $cursor = null,
    int $limit = 25, array $filters = [], array $sort = [])
ManagementOptionsRead::__construct(string $field, ?string $publicId = null,
    ?string $cursor = null, int $limit = 25, array $filters = [])
ManagementWidgetResult::__construct(array $values, ?string $observedAt = null,
    ?string $expiresAt = null)
ManagementWrite::__construct(string $command, ?string $publicId,
    string $revision, array $values, ?array $review = null,
    bool $confirmed = false)
ManagementRecord::__construct(?string $publicId, string $revision, array $values)
ManagementPageResult::__construct(array $items, ?string $next, ?int $total = null)
ManagementOptions::__construct(string $field, array $items, ?string $next)
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

The capability interfaces and read/result DTOs above are implemented in the
point-3 implementation. Generated forms and independent write dispatch are delivered in point 5. A handler
implements only its actual capabilities. Resource, lookup, widget and write bindings
require their corresponding interface; no reader implements dummy write methods.
A source-declared unsupported capability is a structural declaration error.

`ManagementOptionsRead.field` is the exact declared field key within the requested
section. It is required only for `kind=options`; record/page requests reject it.
The result repeats that field identity and refuses a mismatch. Validate that field's
read grant, lookup flag, owning reference policy and readable filter dependencies
before invoking the adapter. An unreadable sibling lookup does not block it.
Unknown/denied fields yield a safe 404 without labels, values, counts or field lists.
Options use the same subject UUID, cursor, limit and scalar filter bounds as reads;
sorting and writes are not accepted. Owners authorize option rows and labels before
pagination. Filters cannot reference denied fields to infer their values.

`ManagementWidgetResult` contains bounded safe JSON and explicit nullable RFC3339
UTC timestamps. Expiry requires observation and must be later than observation;
future observations are invalid. No observation means freshness is unknown, never
fresh. Expired values remain visibly stale with `admin_widget_stale`; unavailable
reads yield empty values/null timestamps with `admin_widget_unavailable`. Widgets
never run diagnosis or invent timestamps from the request time or record revision.
Owning health widgets use the persisted health observation/expiry. The projector
validates the typed result rather than extracting special keys from `values`.

Handlers validate and authorize their owning reads/writes, invoke explicit public
APIs and map projections. They cannot access foreign persistence. A handler method
does not bypass Admin entry, page/section/field checks or owning record Policies.
The Admin projector intersects all of these authorities, including exports,
lookups, counts, review changes and validation errors. Denied fields disappear
entirely; read-only/unknown submitted keys are refused, never silently ignored.
Sensitive fields expose `{set:boolean}` only and never a stored secret. Required
participation becoming unavailable blocks Save; independent pages remain usable.

## App registration and guarded Admin API

AdminAccess reads, independent command/previewCommand, native aggregate preview/save
and AdminRegistry are implemented. All routes below are delivered; owning Core
handlers and framework operations retain their documented independent protocols.

Admin contracts live in `Runa\Modules\Admin\Contracts`:

```php
interface AdminRegistry
{
    public function register(\Runa\Contracts\Modules\ManagementPageProvider $provider): void;
}
interface AdminAccess
{
    public function entry(): array;
    public function bootstrap(): array;
    public function page(string $owner, string $page): array;
    public function read(string $owner, string $page, string $section, array $query): array;
    public function preview(string $owner, string $page, array $input): array;
    public function save(string $owner, string $page, array $input): array;
    public function command(string $owner, string $page, string $section, array $input): array;
    public function previewCommand(string $owner, string $page, string $section, array $input): array;
}
```

Registry is bootstrap-only, frozen before request discovery; duplicate or foreign
replacement throws `InvalidArgumentException`. It is not a guarded API and is not
available over HTTP. App providers register instances implemented in `src/app`.
Module providers continue through existing manifests. AdminAccess arrays use the
exact transport shapes below, avoiding foreign/framework DTOs through ModuleApis.
Admin private implementations own framework conversion. API version is `1.0.0`;
all protected methods require `runa.admin.enter` plus password proof for action
`runa.admin.enter`, TTL 300 seconds, everyAction=false. Other grants/proof remain
additive. The specified `entry()` advertisement is the sole proof-free exception:
see [shared session shell](frontend.md#shared-session-shell-and-account-menu-seam)
for its exact `{available:boolean}` result and admission. It exposes no protected
bootstrap values. HTML shell admission also exposes no protected bootstrap values.

| Method / path                                                | Named route                  | Operation                                       |
| ------------------------------------------------------------ | ---------------------------- | ----------------------------------------------- |
| GET `/admin`                                                 | `runa.admin.dashboard`       | Empty application shell, then guarded bootstrap |
| GET `/admin/{owner}/{page}`                                  | `runa.admin.page`            | Application shell, owner/page grammar above     |
| GET `/api/admin/entry`                                       | `runa.admin.entry`           | AdminAccess.entry, minimal advertisement        |
| GET `/api/admin`                                             | `runa.admin.bootstrap`       | AdminAccess.bootstrap                           |
| GET `/api/admin/{owner}/{page}`                              | `runa.admin.page.read`       | AdminAccess.page                                |
| GET `/api/admin/{owner}/{page}/sections/{section}`           | `runa.admin.section.read`    | AdminAccess.read                                |
| POST `/api/admin/{owner}/{page}/preview`                     | `runa.admin.preview`         | AdminAccess.preview                             |
| POST `/api/admin/{owner}/{page}/save`                        | `runa.admin.save`            | AdminAccess.save                                |
| POST `/api/admin/{owner}/{page}/sections/{section}/preview`  | `runa.admin.command.preview` | AdminAccess.previewCommand                      |
| POST `/api/admin/{owner}/{page}/sections/{section}/commands` | `runa.admin.command`         | AdminAccess.command                             |

Section reads use ordinary GET scalar parameters for `kind`, `field`, `public_id`,
`cursor` and `limit`, and bracket parameters `sort[0][key]` / `sort[0][direction]`.
The optional `filters` parameter is one URL-encoded JSON object, not bracket fields:
`filters={"active":false,"limit":12,"empty":"","missing":null}` preserves exact
scalar/null semantics. It is bounded to 64 KiB and 16 entries; owning readable
filter allowlists still apply. The HTTP adapter converts only the validated integer
limit; malformed filters, extra query keys, invalid kinds, foreign/denied fields and
unsupported capabilities fail closed. The native PHP API still accepts the typed
query array directly. Admission precedes Form Request validation and handler loading;
all results and errors are `no-store, private`.

All API operations are authenticated, CSRF-protected, no-store and maintenance
blocked. Reads never mutate configuration or run diagnosis; diagnosis is a command.
Query `kind` selects only `record|page|options`; options use ManagementOptionsRead,
including required `field`, while other reads use ManagementRead.
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
  generation:int, revision:string, component:Component|null, sections:[Section]}
Section = {key:string,title:string,view:'record'|'table',readonly:boolean,required:boolean,
  component:Component|null,fields:[Field],commands:[Command],record:Record|null,
  diagnostic:string|null}
Field = {key:string,type:string,label:string,readonly:boolean,sensitive:boolean,
  filterable:boolean,sortable:boolean,lookup:boolean,
  required:boolean,max_length:int|null,choices:[string],multiple:boolean,
  component:Component|null}
Command = {key:string,title:string,mode:'independent'|'atomic',destructive:boolean,
  requirement:'none'|'password'|'second_factor'|'both',preview_required:boolean,
  inputs:[string],bulk:boolean,action:string|null,available:boolean,
  intent:'action'|'save'|'create'|'delete'}
Widget = {id:string,title:string,component:Component|null,values:JsonMap,
  observed_at:string|null,expires_at:string|null,diagnostic:string|null}
Component = {key:string,owner:string,manifest_hash:string|null,build_id:string|null}
Record = {public_id:string|null,revision:string,values:JsonMap}
Read = Record | {items:[Record],next:string|null,total:int|null}
  | {field:string,items:[{value:string,label:string}],next:string|null}
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
semantic source/build/policy state, actor/subject UUIDs, owning revisions and related reads.
Unchanged successful health observation publication does not change semantic revisions. No
submitted fingerprint grants authority. At most 15 business participants (plus the native Admin guard); total aggregate
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

### Component identity and binding availability

Every page/section/field/widget component uses the Component shape above; null
selects the supplied default. `key` is an owned registry key, never a path/import.
`manifest_hash` is the current source manifest SHA256 for a module, null only for
ordinary App. `build_id` is a lowercase SHA256 identity from the generated trusted
frontend build manifest, covering the owner's component entries and source content.
The server projects that same build manifest; the compiled registry retains its
identity. A matching source file alone does not admit a browser component. The
resolver matches owner/key/build_id and, for modules, manifest_hash against the
existing manifest-bound registry. App registry entries carry buildId and component;
keys retain the app prefix. No database/request value selects a module loader.
A non-null component with `build_id=null` explicitly denotes missing, malformed
or stale build evidence; it must never select the default or a loader.
Missing/mismatched builds show a safe unavailable component; they never load an
older implementation under a new declaration. Other components remain usable;
a required form component failure blocks Save. Custom full presentation retains
these checks. The build manifest contains no absolute paths or secrets.

In development-server mode, source changes atomically republish the same evidence
and invalidate/reload the virtual registry; no API-supplied import is added.
The existing Vite module-components plugin emits `public/build/runa-components.json`
(schema 1) and retains the same `builds` named export in its virtual registry.
Each owner entry contains `manifest_hash`, `build_id`, sorted relative `sources`
(SHA256 values), and sorted owned `components` keys. Module evidence covers its
entire `frontend` directory; ordinary App evidence covers `app/resources` and
keys from `js/components/admin`. Identity is SHA256 of the module manifest hash
(or literal `app`), newline, then sorted `<relative-path>:<sha256>\n` rows.
Source-bearing reads rehash the tree, including added/imported helpers. Production
uses the compiled evidence because development-only frontend source is absent;
module manifest identity and component membership still must match. Malformed,
missing, noncanonical or over-4-MiB evidence produces unavailable components.
Changes during the Vite build refuse publication of mismatched registry evidence.
The manifest exposes only relative names/hashes; Admin HTTP projects only the
Component shape. Discovery revisions include build availability and identities.

Discovery validates structure, ownership, dependency declaration and exact target
contract/version/method shape separately from current runtime target availability.
Malformed declarations may reject the owner with a safe diagnostic. An unavailable
optional target does not invalidate structurally valid unrelated pages. Recheck
each actual binding at projection/read/write admission. A denied section is omitted;
an authorized unavailable section retains only safe descriptors with record=null,
commands=[], readonly=true and `admin_binding_unavailable`. A widget fails in its
own result. An unavailable page required binding keeps that page in a safe blocked
state; independent pages under the same owner remain readable. Do not read values,
options or counts through an unavailable binding. A required lost participant blocks
the entire form Save with 409, even when hidden by a presentation replacement;
optional omitted sections are excluded explicitly from the participant set. Every
write revalidates availability; discovery is never authority for a later request.

Point 3 covers two pages from one owner with one unavailable optional target,
independent sibling lookup grants, mismatched option identities, typed widget
freshness and page/build identity. Points 4–5 cover compiled registry mismatch,
required component blocking and controlled lookup requests; final point 12 runs
complete affected-area verification. No rendered or runtime PASS follows from
these revised signatures.

Malformed declarations/DTOs throw `InvalidArgumentException`; owning validation
and revision conflicts retain their safe errors. HTTP maps unauthenticated to 401,
authorization to 403, hidden/missing identities to 404, stale revision/preview,
lost participants or incompatible composition to 409, validation to 422, throttling
to 429 and unavailable maintenance/module/Audit to 503. Existing Auth proof
challenge response semantics take precedence over a generic 403. Never echo
unauthorized submitted values. Root rollback and refusal handling are specified
in [composition](composition.md).

## Delivered discovery and read projection

Management discovery reads only installed/registered, enabled and available owners
whose manifest hash still matches their source. Existing legacy management
providers keep their schema and do not become executable pages. It checks aggregate
owner limits, default uniqueness, handler/participant source ownership, declared
dependencies, exact guarded API versions/methods and component identity. Handlers
are inspected without constructing them. They are constructed only for an admitted
read after rechecking target availability. Optional target unavailability is
isolated to its section/widget, preserving unrelated pages. Invalid/conflicting declarations discard that owner's pages, bindings,
contributions and widgets with `admin_declaration_unavailable`; other owners remain
independent. Mixed registry generations fail with `availability_changed`.

Ordinary App providers implement ManagementPageProvider in `src/app/Management`.
During an application provider's boot, explicitly bind each App handler class and
register a Laravel `afterResolving(AdminRegistry::class, ...)` callback that calls
`$registry->register($provider)`. This source-owned bootstrap callback is resolved
by AdminAccess before its first discovery; cold installation/recovery boot never
loads unavailable module classes. Do not eagerly resolve AdminRegistry in a
provider constructor or during cold command boot. App providers return no
permission declarations and consume already existing native grants plus their
record Policies. Registration freezes before the first declaration
discovery; duplicates, foreign classes and late replacement throw
InvalidArgumentException. App classes under `App\Modules` use module declarations
instead. Provider registration never installs modules or synchronizes grants.

Source component keys resolve to their owning module's existing
`frontend/components/<key>.vue`; App independent keys use `app.<key>` with source
`src/app/resources/js/components/admin/<key>.vue`. Symlinks, imports, foreign keys
and missing source are refused in source-bearing environments. Production strips
Vue sources and validates the generated evidence against the current module
manifest instead. Browser registry resolution, development-server evidence publication and shell
replacement are implemented in point 4; final rendered acceptance remains point 12.

Bootstrap selects the first authorized declared default, otherwise the first
page ordered by order/key. Owners with no authorized page disappear. Page reads
return authorized section/field/command descriptors with record=null; section
reads explicitly fetch record/page/options through the owning handler. No private
handler names, API targets or source paths appear on the wire; component build
identities use only the explicit public shape above. Contributions
retain owner authority and use section identity `<owner>.<contribution>.<section>`;
they appear only under an authorized existing host. Host sections sort by order/key,
then contributions by contribution order/owned identity. The dashboard host accepts
widgets only. A missing or lost editable participant conflicts before any apply; the delivered
coordinator revalidates the complete writable atomic Save set under root locks. Independent
commands retain their owning writer and current admission.

Read projection removes denied and undeclared fields, choices and controls. It
rejects unknown query fields, unreadable/nonfilterable/nonsortable filter/sort keys,
internal IDs and excessive limits. Options authorize only the requested declared lookup field; denied siblings
do not block an allowed lookup and a mismatched result field is refused. Owning handlers must authorize returned records, option labels,
selection/counts/exports and record Policies before producing their bounded DTO;
Admin never estimates totals or filters an unauthorized count after disclosure.
Typed field projection rejects nested objects in scalar fields. Sensitive values
must already be exactly `{set:boolean}`; a raw secret refuses the result. Unexpected
handler failures become `admin_read_unavailable`, with no exception message.
Widgets have an independently authorized ManagementWidgetReader binding; their
owning handler returns safe JSON with explicit observation/expiry. Expired values
carry admin_widget_stale; failures yield empty values/null timestamps plus
`admin_widget_unavailable`. No arbitrary export endpoint is introduced.

The shared private write-input intersection refuses read-only sections/fields,
unknown keys, inaccessible command inputs and unconfirmed destructive commands.
It is not an application entrypoint. Independent commands consume it under entry
admission, then execute the native owning writer after the entry root ends.
Metadata cannot weaken native proofs, previews, revisions, Policies or Audit.

## Independent command execution

The framework owns intent/inputs; Admin projects/consumes them. intent explicitly
selects action/save/create/delete presentation. A section admits at most one save
and one create command; delete requires destructive=true. Inputs are projected
only after read/write grants pass. bulk advertises an existing owning operation,
never generic SQL. available checks the command binding independently of its
reader. proofAction is the exact native proof action, projected as nullable action;
non-none stepUp requires it. No action is inferred from owner/key names.

AdminAccess adds previewCommand(string $owner,string $page,string $section,
array $input): array at guarded API 1.0.0, operation command.preview with
runa.admin.enter. Its result is {fingerprint:string,changes:JsonMap,
requirement:string,every_action:bool}. Both independent transports accept
ManagementWrite, at most 256 KiB HTTP with existing JSON/depth/field limits.
Empty transport maps serialize as objects; native PHP retains arrays. Admission
precedes FormRequest validation and handler loading. Unknown/read-only input
refuses. Preview does not confirm destruction; execute requires confirmation and
review when declared, with exact native validation in the owning protocol.

Entry admission resolves a source-owned writer, then ends its transaction before
preview/execute. Independent lifecycle/network/file work never runs inside that
entry root. Post-write projection reauthorizes current entry/fields. A write may
already have committed when response admission fails; never replay it. Preview
changes include only readable nonsecret field keys; owners return safe field-bound
differences. Validation errors retain only command inputs with fixed messages.
HTTP error scope entry versus operation distinguishes entrance proof from native
operation proof. Aggregate routes execute source-bound native participants;
[composition](composition.md) owns compatibility, revisions and transaction phases.

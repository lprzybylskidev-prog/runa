<!-- runa-knowledge {"purpose":"current_guidance","effective_date":"2026-10-30","applicable_version":"0.7.0","decisions":[]} -->

# Admin frontend contract

Status: points 4–5 deliver the PL/EN session shell, guarded reads and independent
commands, compiled component resolution, presentation providers, generated record
forms and cursor tables. Public facade `@/runa/admin` exports AdminApplication,
AdminLayout, AdminPage, AdminSection, AdminForm, AdminField, AdminAction,
AdminResourceTable, AdminLoading, AdminEntryProof, AdminPresentationProvider,
useAdminPresentation, resolveAdminComponent, useAdmin/useAdminPage/useAdminForm,
useAdminResource/useAdminTable and validateAdminField. Source home is
`src/runa/modules/admin/frontend`. Application replacements live in
`src/app/resources/js`; HTTP uses `@/runa/http`, UI primitives use `@/runa/ui`.

Record forms execute explicit independent commands or compatible native atomic
preview/Save through the shared form owner. Point 6 delivers server coordination
and initial profile/access record adapters. Point 9 delivers operational dashboard,
module settings, lifecycle and work controls. Point 10 delivers application global settings and runtime branding. Point 8 supplies owning Permissions and Audit pages through their public presentation facades. Point 7 supplies the Auth Users, policy and bulk-session pages; see [Auth administration](../auth/administration.md). Final rendered/release acceptance remains point 12.

## Controllers and public types

The following types and implemented functions use that public facade. Function
and component runtime delivery follows the status above.
Wire types `AdminBootstrap`, `AdminPageData`, `AdminSectionData`, `AdminFieldData`,
`AdminCommandData`, `AdminWidgetData`, `AdminRecord`, `AdminReadResult`,
`AdminWrite`, `AdminAggregateInput`, `AdminPreview`, `AdminSaveResult`,
`AdminCommandResult` and `AdminError` correspond exactly to the named
[wire schema](declarations.md#wire-schema-1) values; never `any`.
`AdminJson` is recursively `null | boolean | number | string | AdminJson[] |
{[key:string]:AdminJson}`; no Date, File, VNode, callback or component crosses HTTP.
`AdminFieldValue` is AdminJson; `AdminFieldPath` is the declared
`participant.section.field` identity; `AdminConflict` is
`{path:AdminFieldPath,base:AdminJson,local:AdminJson,remote:AdminJson}` and contains
only still-readable fields. Controller shapes are complete:

```ts
import type { Component, ComputedRef, Ref, VNode } from 'vue';
import type {
  ApplicationSessionController,
  ApplicationNavigate,
  ActivitySession,
} from '@/runa/auth';
import type {
  ApplicationLayoutProps,
  ApplicationLayoutSlots,
  NavigationEntry,
  RouteContext,
} from '@/runa/ui';

type AdminState =
  | 'loading'
  | 'ready'
  | 'proof_required'
  | 'forbidden'
  | 'unavailable'
  | 'failed';
interface AdminLocation {
  owner: string;
  page: string;
}
interface AdminController {
  session: ApplicationSessionController;
  state: ComputedRef<AdminState>;
  bootstrap: Ref<AdminBootstrap | undefined>;
  location: ComputedRef<AdminLocation | undefined>;
  error: Ref<AdminError | undefined>;
  entryProof: OperationProofController;
  refresh(): Promise<void>;
  navigate(location: AdminLocation): Promise<boolean>;
  confirmEntry(): Promise<void>;
  cancelEntry(): void;
}
interface AdminPageController {
  state: ComputedRef<AdminState>;
  page: Ref<AdminPageData | undefined>;
  error: Ref<AdminError | undefined>;
  refresh(): Promise<void>;
}
interface AdminFormController {
  state: ComputedRef<AdminState>;
  dirty: ComputedRef<boolean>;
  busy: ComputedRef<boolean>;
  values: Ref<Record<AdminFieldPath, AdminFieldValue>>;
  errors: Ref<Record<AdminFieldPath, readonly string[]>>;
  conflicts: ComputedRef<readonly AdminConflict[]>;
  review: Ref<AdminPreview | undefined>;
  error: Ref<AdminError | undefined>;
  set(path: AdminFieldPath, value: AdminFieldValue): void;
  refresh(): Promise<void>;
  preview(): Promise<AdminPreview | undefined>;
  save(): Promise<AdminSaveResult | undefined>;
  reset(): void;
  resolve(path: AdminFieldPath, choice: 'local' | 'remote'): void;
  sections: ComputedRef<readonly AdminSectionData[]>;
  records: Ref<Record<string, AdminRecord>>;
  blocked: ComputedRef<boolean>;
  confirmation: Ref<{ title: AdminText; description: string } | undefined>;
  confirm(accepted: boolean): void;
  beforeLeave(): Promise<boolean>;
  selectSubject(publicId: string | null): Promise<boolean>;
  fieldPath(section: string, field: string): AdminFieldPath;
  lookup(section: string, field: string): AdminFieldLookup;
  execute(
    section: string,
    command: string,
  ): Promise<AdminCommandResult | undefined>;
  reviewCommand(
    section: string,
    command: string,
  ): Promise<AdminCommandReview | undefined>;
  commandReviews: Ref<Record<string, AdminCommandReview>>;
  editSection(section: string, publicId: string | null): Promise<boolean>;
  closeSection(section: string): Promise<boolean>;
  setAvailable(identity: string, available: boolean): void;
  notice: Ref<string>;
  proof(
    section: string,
    command: string,
  ): import('@/runa/auth').OperationProofController;
  activateSection(section: string): Promise<void>;
  operations: Ref<
    Record<
      string,
      { status: 'pending' | 'recovery_required'; operationId: string }
    >
  >;
}
interface AdminResourceController {
  state: ComputedRef<AdminState>;
  result: Ref<AdminReadResult | undefined>;
  busy: ComputedRef<boolean>;
  error: Ref<AdminError | undefined>;
  read(
    query: AdminReadQuery,
    options?: { signal?: AbortSignal },
  ): Promise<void>;
  command(input: AdminWrite): Promise<AdminCommandResult | undefined>;
  preview(input: AdminWrite): Promise<AdminCommandReview | undefined>;
}
interface AdminRecordQuery {
  kind: 'record' | 'page';
  public_id?: string;
  cursor?: string;
  limit?: number;
  filters?: Record<string, string | number | boolean | null>;
  sort?: readonly { key: string; direction: 'asc' | 'desc' }[];
}
interface AdminOptionsQuery {
  kind: 'options';
  field: string;
  public_id?: string;
  cursor?: string;
  limit?: number;
  filters?: Record<string, string | number | boolean | null>;
}
type AdminReadQuery = AdminRecordQuery | AdminOptionsQuery;
type AdminComponentRegistry = Readonly<
  Record<string, { readonly buildId: string; readonly component: Component }>
>;
function useAdmin(options?: {
  navigate?: ApplicationNavigate;
  session?: ApplicationSessionController;
  collections?: Readonly<Record<string, AdminCollectionAdapters>>;
}): AdminController;
function useAdminPage(
  admin: AdminController,
  location: Ref<AdminLocation>,
): AdminPageController;
function useAdminForm(
  page: AdminPageController,
  options?: {
    publicId?: Ref<string | null>;
    confirmLeave?: () => boolean | Promise<boolean>;
    components?: AdminComponentRegistry;
    autoRead?: boolean;
  },
): AdminFormController;
function useAdminResource(
  page: AdminPageController,
  section: string,
): AdminResourceController;
```

`entryProof` is the public Auth OperationProofController for the fixed native
`runa.admin.enter` password action. Render public `AdminEntryProof` with
`controller: AdminController`, or bind that input and call `confirmEntry()` once
on explicit submission. AdminEntryProof is a thin public AuthOperationProofPage consumer,
with localized PL/EN title/description, a confirmed-to-bootstrap refresh and cancel-to-/app
navigation. Its Auth-owned page uses MinimalLayout before panel admission;
ApplicationSessionShell keeps one session owner and renders the admission slot
outside ApplicationLayout. AuthPresentation.operationProofPage/minimalLayout remain
replaceable. AdminEntryProof has no slots or emitted events. Confirmation clears
the password, admits no duplicate submission and refreshes only bootstrap;
account/session invalidation and disposal erase it. Failure never replays a
business operation. `authorityRevision` on ApplicationSessionController increments
after successful session validation; unchanged activity/heartbeat observations do
not trigger new Admin advertisements or descriptor reads. Session identity,
restrictions, suspension, loading, pending logout and that revision fence protected
reads. Pre-admission password input uses the current unrestricted account/session
identity rather than the observation revision: same-session revalidation keeps its
page and input mounted while bootstrap is checked in the background. An unchanged
native step_up_required result preserves that form; a real denial/invalidation or
successful bootstrap admission clears its credentials. Same-session observations retain mounted bootstrap/page presentation and dirty
forms while revalidating; request/operation stamps still invalidate every old
authority observation. Real identity/access loss clears protected state immediately.
No proof/business write is replayed. See [view readiness](../../development/frontend/loading.md).

Composables mount once per keyed account/page; scope disposal aborts requests and
removes listeners/dirty guards. They own cancellation, session/CSRF/proof handling,
stale response rejection and localized PL/EN feedback. The same aggregate mutation
queue/revision is shared by forms and independent sections; no second competing
controller for one record. useAdmin reuses a supplied session controller; otherwise
it creates one through the existing Auth composable. The default AdminApplication
shares that single controller with its shell/account menu; never mount another
Auth session owner for the same shell. Form publicId selects the subject for section reads;
null addresses a new record or singleton as declared by its handler. A subject
change passes the dirty-navigation guard before resetting the keyed form; app
routers retain ownership of how that public ID is represented in their URL.
Read reconciliation follows the existing public
[forms guide](../../development/frontend/forms.md): merge clean/disjoint changes,
preserve dirty values, resolve overlap locally, never replay a write. `reset`
explicitly discards local edits to the latest reconciled readable snapshot.
`save` requires resolved conflicts and current reviewed differences; it never
calls preview silently to replace a stale review. Failure returns undefined and
populates safe error state; callers do not infer success. `navigate` returns false
when dirty navigation is declined. No unconditional polling or new global store.

## Delivered resource reads

`useAdminResource(page, section)` owns one section-read lane. Call `read(query)`
explicitly for a record, bounded cursor page or field-specific options. `result`
contains only the decoded requested shape; unknown totals remain null. A second
identical pending read joins its promise; a different query cancels/fences the
previous read. The controller snapshots caller query values and uses only the
current page's readable section/fields. Denied/unknown lookup fields never issue
a request. Malformed responses, foreign subjects, unknown field values and raw
secrets clear the result and produce safe feedback without exposing payloads.

The page controller created by `useAdminPage` retains its owning shared session
internally. Account/access/page invalidation and scope disposal erase resource
results and cancel/fence pending responses. Queries never carry their subject or
filters into a different account/session or owning page. An explicit same-session page refresh
retains the mounted page while the read is pending; successful descriptor updates
refresh the last resource query. Returning to a visible document also refreshes
that query, with no timer/polling. A custom headless page controller owns timely
invalidation of its page/state; use the supplied controller to retain native
session admission. Call resource composables in a Vue scope and dispose them with
their page. This resource read lane does not own editable drafts; all its writes and reads join the shared page
mutation/revision lane used by useAdminForm.

`command(input)` executes one projected independent command; `preview(input)`
returns AdminCommandReview. Unknown/read-only/atomic/unavailable commands, forbidden
input keys, missing review and missing destructive confirmation refuse locally.
No write is retried. Safe errors preserve drafts; 409 triggers shared current reads.
Entry-scope proof denial returns to native entry confirmation; operation-scope
proof denial stays in the editor and uses the separate owning action proof.
AdminCommandReview is `{fingerprint:string,changes:Record<string,AdminJson>,
requirement:'none'|'password'|'second_factor'|'both',every_action:boolean}`.
An optional `read(query,{signal})` cancels that lane and erases its retained query
when the caller cancels, for field-bound selection lifetimes.

```ts
import { useAdminResource } from '@/runa/admin';
// page is the supplied useAdminPage controller in the same Vue scope.
const resource = useAdminResource(page, 'profile');
await resource.read({ kind: 'record', public_id: publicId });
// For a readable reference field, retain the owning opaque next cursor.
await resource.read({ kind: 'options', field: 'manager', limit: 25 });
```

## Delivered cursor table composition

Admin owns `useAdminTable(page: AdminPageController, section: string,
options?: {adapters?: MaybeRefOrGetter<AdminCollectionAdapters>,
select?: (publicId:string|null)=>Promise<boolean>}): AdminTableController`
and `AdminResourceTable` through `@/runa/admin`. Their implementation lives under
Admin frontend and is bundled with the consuming presentation. Module and App
page replacements can use this source-owned read presentation without private
imports, an extra HTTP client or another session owner. The default page also
composes this controller/component automatically for `view: 'table'` sections.
Public collection adapters, views, selection and record editing compose the same
owner-controlled infrastructure described below.

```ts
interface AdminTableController {
  section: ComputedRef<AdminSectionData | undefined>;
  resource: AdminResourceController;
  query: Ref<TableQuery>;
  columns: ComputedRef<readonly TableColumn<AdminRecord>[]>;
  provider: ComputedRef<TableProvider<AdminRecord>>;
  error: ComputedRef<AdminError | undefined>;
  refresh(): Promise<void>;
  selection: Ref<
    import('@/runa/ui').RowSelection<import('@/runa/ui').TableQuery>
  >;
  views: import('@/runa/ui').TableViewsController;
  adapters: ComputedRef<AdminCollectionAdapters>;
  selected: Ref<string | null | undefined>;
  select(publicId: string | null): Promise<boolean>;
  confirmation: Ref<string | undefined>;
  confirm(accepted: boolean): void;
}
interface AdminResourceTableProps {
  controller: AdminTableController;
}
```

Create the controller once in the owning page's Vue scope. It owns the resource
read lane: change `query` or call `refresh`, rather than sending independent
record/options reads through `resource`. The latter exposes read status/error/busy
and decoded results. Do not reuse a controller between pages or accounts.
`useAdminResource` retains native admission, visibility refresh, response fencing
and disposal. Account/access/page changes erase table filters and cursor state;
same-session observations and descriptor updates preserve allowed query controls,
reset cursor/selection intent and restart page 1.
Views use an ephemeral memory store by default; durable views require an explicit
source-owned, account-aware preference scope/store with its owning declaration.
Records and secrets are never preference values.

The adapter uses the existing server Table/useTable provider contract. Only the
owner's current bounded cursor page is rendered; it is never sorted, filtered or
sliced locally. Page numbers refer only to already visited opaque cursors. Query
changes restart at page 1; unvisited numbered jumps normalize to page 1 without
fetching intervening pages. A refreshed next cursor invalidates later cursors.
Nullable owner totals are preserved. Provider `counts` remains `unknown`, even
when the owner supplies a total, so exact totals never enable arbitrary numbered
jumps. The displayed total remains the owner's value, including null, rather
than a count of downloaded rows. Page size is bounded to 100 by the existing read
contract; owners retain their lower limits and may refuse oversized requests.

Columns contain only projected readable fields. Sorting/filter keys follow their
flags; sensitive fields never support either. Search is disabled because the
Admin read contract has no generic search field. Query filter strings convert to
native integer/boolean values for those field types; invalid conversions fail
locally without issuing a broader unfiltered read. Backend validation and record
Policies remain authoritative. Missing/duplicate UUIDv7 row identities refuse
rendering, with no fallback to internal IDs or row positions. Secrets display
only Set/Not set; booleans use Yes/No, null uses the shared table placeholder.

The supplied component supports PL/EN and reuses Table's semantic tokens, horizontal
keyboard scrolling, loading/empty/error feedback and previous/next controls.
It emits no custom events. Slots are `toolbar({feedbackTarget: string})`,
`filters({query: TableQuery, update(value: TableQuery): void})`,
`actions({row: AdminRecord})`, and `cell-<field>({row: AdminRecord,
value: string | number | null})`. Omitted slots retain default controls/cells;
custom filters use `update` to reset paging. Row actions are application-owned
presentation only and confer no mutation authority. Custom column/field components
can be composed in cell slots; resolve projected component identities through
`resolveAdminComponent` before rendering them.

An application-owned replacement under `src/app/resources/js` can compose:

```vue
<script setup lang="ts">
import { AdminResourceTable, useAdminTable } from '@/runa/admin';
import type { AdminPageController } from '@/runa/admin';
const props = defineProps<{ controller: AdminPageController }>();
// Use the actual projected section key, including its contribution namespace.
const table = useAdminTable(props.controller, 'directory');
</script>
<template>
  <AdminResourceTable :controller="table">
    <template #cell-name="{ value }"
      ><strong>{{ value }}</strong></template
    >
  </AdminResourceTable>
</template>
```

## Generated forms and command lifecycle

Admin owns `useAdminForm` and the generated AdminPage/AdminForm/AdminSection/
AdminAction presentation through `@/runa/admin`; implementation is in Admin
frontend and delivery is bundled presentation. The signatures above are the
complete public contract. One creating Vue scope owns the controller per page;
later consumers receive the same controller, with no competing draft/read lane.
The default page uses autoRead=false: mounted default/custom record sections join
with activateSection(key). Headless consumers default to automatic reads. Required
editable sections that have not joined, failed reads and unavailable declared
field/section builds block aggregate Save. setAvailable(identity,available) joins
custom boundaries to that contract; default boundaries report failures themselves.

fieldPath(section,field) uses the projected section namespace and field key.
Values/errors/conflicts use that exact path. records retains confirmed subjects/
revisions; sections lists active editors. Descriptor/visibility changes and shared
mutation completion refresh native reads without polling. Baseline/local/remote
comparison merges clean/disjoint changes and preserves edited fields. Overlaps
retain the old write revision until resolved locally through resolve(path,choice).
reset deliberately discards to the latest readable baseline. Forbidden fields
leave values/errors/snapshots/conflicts; newly read-only fields revert. Account,
access or page invalidation erases protected state and credentials, fences late
responses and cancels confirmations. Same-session observations preserve drafts
while fencing old requests and cancelling stale reviews/confirmations. Changed sensitive snapshots clear secret
replacements and require re-entry without putting secrets in conflict panels.

execute(section,command) sends only that declaration's writable inputs. intent
explicitly distinguishes action/save/create/delete; no method/key is guessed.
editSection/closeSection guard only that editor and preserve sibling drafts;
selectSubject guards the whole form. Null selects a new record, a public UUID an
existing record. One page mutation/revision lane covers forms and independent
sections; concurrent intents refuse rather than queue and rebase. Known static
errors refuse locally. 422 maps safe owner-field errors; 409 refreshes reads while
preserving drafts, without replay. Failed reads can explicitly Retry loading.

reviewCommand explicitly obtains a preview; changes to input/source/revision
invalidate it, and execute never silently regenerates it. Destructive commands
use ConfirmDialog and recheck exact input after approval. Native operation proof
uses public Auth useOperationProof/AuthOperationProof with explicit proofAction;
see the [Auth proof contract](../auth/entry-ui.md#operation-proof-composition).
Proof confirmation never executes a write. Owning guards enforce proof, exact
review, record Policies and transactional Audit. commandReviews exposes previews;
notice exposes safe success. operations retains pending/recovery_required plus
native operationId, blocks blind resubmission and never marks a draft committed.

preview/save supply atomic frontend composition: exact participants, reviewed
fingerprints, shared revision and decoding every result before changing baselines.
Native multi-owner server execution is delivered with the [composition contract](composition.md).
At most 15 business sections share the root. Independent commands retain separate
protocols. Initial profile/access records are delivered; complete Core coverage
retains its owning points.

beforeLeave integrates a custom router, including its Back/Forward history guard.
AdminController.navigate invokes registered guards before the supplied router.
Default document links use guarded requestLeave; beforeunload uses useDirtyGuard.
Cancel retains the view/draft and a busy mutation refuses navigation. Modified/
new-tab links retain browser behavior. Arbitrary pushState/popstate belongs to
the application's guarded router; do not bypass its beforeLeave adapter. Default
AdminForm renders confirmation and resolves confirm/cancel/Escape; headless
consumers may supply confirmLeave. FormNotifications owns success/failure feedback.

## Collection selection, views and owning adapters

The public source-owned composition type is:

```ts
interface AdminCollectionAdapters {
  exports?: ExportProvider<TableQuery>;
  bulk?: BulkProvider<TableQuery>;
  print?: PrintAdapter<TableQuery>;
  preferences?: { scope: PreferenceScope; store: PreferenceStore };
}
```

All provider/store types come from `@/runa/ui`. Pass adapters to useAdminTable or
AdminApplication.collections keyed by `<page-owner>.<page>.<projected-section>`.
DefaultApplication forwards adminCollections. Missing providers mean no controls.
These are explicit source objects, never executable wire metadata. Owners retain
endpoint/grant/review/proof/idempotency/cancellation/selection/size protocols.
Core providers arrive with their owning pages; App consumers can compose existing
public providers now. No universal export endpoint or browser scan of all pages.

selection reuses explicit/all-matching public selection. Unknown cursor counts
never manufacture all-matching UI or arbitrary jumps. Explicit public UUIDs
survive paging; context loss clears them. All-matching clears after query changes.
views reuses TableViewsController/TablePreferences for order/visibility/widths and
named views. Defaults are ephemeral memory, erased on context loss. Durable views
require an explicit account-aware owning preference store/scope/declaration;
records and secrets are never preference values. A dynamic store follows source
adapter availability without inventing undeclared platform preference keys.

Exports require a server provider and visible/readable/nonsecret columns; foreign,
hidden or secret keys refuse before start. A cursor page is never a complete
export. TableActions retains native format/scope/status/failure/cancellation.
Bulk actions intersect provider actions with available independent commands with
bulk=true. Confirm the selection, then recheck context/query/selection before one
native start; changed membership/concurrent mutation refuse. Owners retain native
review/strong proof/selection staleness/Audit. TableActions accepts an optional
export provider for honest bulk-only composition. Operation status/retry/cancel
handles are context-fenced; foreign-context download results are not projected.

select(publicId|null) invokes the optional editor guard and updates selected;
undefined means closed. Defaults compose create/open/close with the shared form
without changing App routing. Public toolbar/filter/row/cell slots remain usable.

## Delivered field composition

Admin owns public `AdminField` and
`validateAdminField(field: AdminFieldData, value: AdminFieldValue, locale?: 'pl'|'en'): readonly string[]`
through `@/runa/admin`, implemented under Admin frontend and delivered as built
presentation. Module and ordinary App consumers can compose controlled fields
without a form controller, private imports, transport or another session owner.
`AdminFieldProps` and `AdminFieldEmits` below are the complete runtime signature;
the component has no slots. Import `SelectionProvider` and `SelectOption` from
`@/runa/ui` for its optional `AdminFieldLookup` input.

The component resolves a field-specific owned build first, then the subtree field
provider, then its supplied default. Missing/stale declared builds never fall back.
Each field has its own render boundary. Read-only descriptor/consumer state and
busy state block update emissions, including emissions from custom replacements.
These are UX guards; backend grants, field admission and validation remain authority.

Defaults compose existing FormField/TextInput/NumberInput/Checkbox/Select/TagsInput.
Text and dictionary choices use the shared localized selection; dictionaries without
choices use text. Integers emit safe numbers when complete, null when empty, and
the exact incomplete/out-of-range string otherwise, so invalid drafts never round
or reuse a stale valid value. UUID fields accept only public UUIDv7 values through
static hints; multiple UUIDs use TagsInput when there is no reference lookup.
UTC timestamps use an explicit RFC3339 text control without implicit browser-timezone
conversion. Boolean null retains the shared indeterminate state. Read-only values
are associated text, including Yes/No and Set/Not set, rather than editable controls.

Secret set-state never fills a password input. Replacement text is transient
controlled input, with no reveal control or persistence here; the owning consumer
must erase it on account/authority/subject invalidation. Static validation checks
required/max-length/choices/type/public UUID/UTC syntax only. It returns safe messages (English for omitted locale; supplied fields use the live locale) and never echoes values. After an edit the default merges these hints
with supplied owner errors; untouched fields do not display required errors.
Unicode length counts code points; IME input follows TextInput. Do not rely on
native validation or truncate drafts with a native maxlength.

A lookup requires an explicit field-bound `lookup.provider`; otherwise it renders
unavailable instead of accepting arbitrary free text. `selectedOptions` supplies
labels for selected/off-page UUIDs. Select owns bounded cursor pages, cancellation,
keyboard/focus and PL/EN feedback; the owning consumer supplies authorized
transport/filter semantics. Passing this presentation input grants no authority.
useAdminForm supplies field-bound option providers automatically. The field
component itself sends no write; Save/preview orchestration belongs to the form.

```vue
<script setup lang="ts">
import { ref } from 'vue';
import { AdminField } from '@/runa/admin';
import type { AdminFieldData, AdminFieldValue } from '@/runa/admin';
defineProps<{ field: AdminFieldData }>();
const value = ref<AdminFieldValue>(null);
</script>
<template>
  <AdminField
    :field="field"
    :model-value="value"
    :errors="[]"
    :readonly="false"
    :busy="false"
    @update:model-value="value = $event"
  />
</template>
```

Generic field replacements render public UI controls
using the same props/events, as the customer composition fixture does.

## Presentation provider, components, props, slots and events

```ts
interface AdminPresentation {
  layout?: Component;
  page?: Component;
  section?: Component;
  field?: Component;
  form?: Component;
  widget?: Component;
  action?: Component;
  loading?: Component;
}
interface AdminPresentationProviderProps {
  components: AdminPresentation;
}
interface AdminPresentationProviderSlots {
  default?(): VNode[];
}
function useAdminPresentation(): ComputedRef<AdminPresentation>;

interface AdminApplicationProps {
  navigate?: ApplicationNavigate;
  components?: AdminComponentRegistry;
  session?: ApplicationSessionController;
  collections?: Readonly<Record<string, AdminCollectionAdapters>>;
}
interface AdminApplicationSlots {
  default?(scope: { controller: AdminController }): VNode[];
  loading?(): VNode[];
}
type AdminLayoutProps = ApplicationLayoutProps & {
  controller: AdminController;
};
type AdminLayoutSlots = ApplicationLayoutSlots;
interface AdminPageProps {
  controller: AdminPageController;
  form: AdminFormController;
  components?: AdminComponentRegistry;
}
interface AdminPageSlots {
  default?(scope: AdminPageProps): VNode[];
  heading?(scope: AdminPageProps): VNode[];
  actions?(scope: AdminPageProps): VNode[];
}
interface AdminSectionProps {
  section: AdminSectionData;
  controller: AdminFormController;
  components?: AdminComponentRegistry;
}
interface AdminSectionSlots {
  default?(scope: AdminSectionProps): VNode[];
}
interface AdminFieldProps {
  field: AdminFieldData;
  modelValue: AdminFieldValue;
  errors: readonly string[];
  readonly: boolean;
  busy: boolean;
  lookup?: AdminFieldLookup;
  components?: AdminComponentRegistry;
}
interface AdminFieldLookup {
  provider: SelectionProvider;
  selectedOptions?: readonly SelectOption[];
  searchable?: boolean;
}
type AdminFieldEmits = {
  'update:modelValue': [value: AdminFieldValue];
  availability: [available: boolean];
};
interface AdminFormProps {
  controller: AdminFormController;
}
interface AdminFormSlots {
  default?(scope: AdminFormProps): VNode[];
  actions?(scope: AdminFormProps): VNode[];
  review?(scope: AdminFormProps): VNode[];
}
type AdminFormEmits = { saved: [result: AdminSaveResult] };
interface AdminWidgetProps {
  widget: AdminWidgetData;
}
interface AdminWidgetSlots {
  default?(scope: AdminWidgetProps): VNode[];
}
interface AdminActionProps {
  command: AdminCommandData;
  busy: boolean;
  disabled: boolean;
}
type AdminActionEmits = { invoke: [] };
```

The delivered facade component set (runtime delivery is listed above): `AdminApplication`, `AdminPresentationProvider`,
`AdminLayout`, `AdminPage`, `AdminSection`, `AdminField`, `AdminForm`,
`AdminWidget`, `AdminAction`, `AdminLoading`. AdminLoading has no props/events,
and a single optional default slot `(): VNode[]`. Provider/page/section/widget/
layout/application have no custom emitted events; otherwise events are exactly
those above. Local components can consume only some props or forward `$attrs`,
matching current Auth replacement compatibility. Partial providers inherit parent
overrides and defaults for omitted entries. Field/section/page-specific component
keys take precedence over the generic provider entry, then supplied defaults.
A presentation override cannot replace a handler, permission or security controller.

The optional components input is the explicit source-owned App component registry
for independent page/field/section keys declared by App PHP providers. Keys use
the `app.` prefix and match the source/build identity projection; duplicate/unknown
or foreign module keys fail closed. App bootstrap imports these components
statically and passes entries with their generated buildId, never resolves a request/database URL or private
module source path. A projected non-null component whose build_id is null is unavailable; it never
selects a default or loader. Module components continue through existing manifest-bound
build discovery. The supplied dispatcher forwards an App registry only when the
application explicitly configures one; no sample screen is added to the scaffold.

Page replacements receive `{controller: AdminPageController}`; section
replacements receive the exported `AdminSectionPresentationProps`:
`{section: AdminSectionData, page: AdminPageController, controller: AdminFormController}`.
The page prop lets section replacements compose `useAdminTable(page, section.key)`
or `useAdminResource(page, section.key)` in their own scope. Default dispatch mounts
no competing read controller for a replacement. A section-specific source component
takes precedence over a provider; an unavailable non-null build never falls back
to the provider or default table. Binding diagnostics render a safe warning before
mounting any section presentation. Each section keeps its own render boundary, so
one broken custom presentation does not hide healthy siblings.

A source-declared table mounts one bounded collection lane, with localized shared
controls, secret set-state and existing cursor/query/disposal behavior. Source
`view` is presentation intent, never additional authority. The default `record`
intent mounts one existing resource lane, reads the projected subject UUID (or
the owning singleton when null), and displays only returned, readable values.
Loading, safe failure/explicit Retry, descriptor/visibility refresh and account
invalidation retain `useAdminResource` behavior. Omitted values have no fabricated
empty field; secret values show only Set/Not set. Editable values bind to the one
page form controller and execute explicit declared commands. Form/controller props
are delivered, including required-content availability blocking. Defaults render
page/section headings, record values, collection tables, safe unavailable/empty states and dashboard widget values;
the owning backend remains the write authority.

The public resolver signature is
`resolveAdminComponent(identity: AdminComponent | null, components?: AdminComponentRegistry): {component: Component | undefined; available: boolean}`.
`AdminComponent` is the exact wire Component identity. Null returns an undefined
component and available=true, allowing the selected provider/default. An unavailable
non-null identity returns a safe supplied unavailable component and available=false.
Module loaders are chosen only after owner/manifest/build/key agreement; App keys
also require a matching explicit static registry entry. Use this resolver in full
replacements; it does not import a path from metadata. Default rendering isolates
component setup/render/load failures to its boundary. Required form blocking is
implemented by useAdminForm and field/section boundaries; source identity validation alone is not Save authorization.

Defaults remain usable without configuration: ApplicationLayout, existing Form/
FormSection/controls, Table/useTable, confirmation and feedback. Admin uses the same Polish/English preference as the application, including
shared controls, proof and errors; changing language preserves mounted controllers
and dirty drafts. Light/dark, responsive keyboard and
focus behavior are required in final rendered acceptance. No runtime/template
compiler, new framework, UI dependency or duplicated table backend.

## Shared session shell and account menu seam

Point 4 implements the existing AuthApplication session/menu/activity orchestration
into public `ApplicationSessionShell` in the `@/runa/auth` facade, implemented under
Auth frontend. It is the shared platform shell used by AuthApplication and
AdminApplication. Props and slots are specified as:

```ts
interface ApplicationSessionShellProps {
  session: ApplicationSessionController;
  navigation?: readonly NavigationEntry[];
  topNavigation?: readonly NavigationEntry[];
  current?: RouteContext;
  locale?: 'pl' | 'en';
  accountMenu?: readonly AuthAccountMenuEntry[];
}
interface ApplicationSessionShellSlots {
  default?(scope: { session: ActivitySession }): VNode[];
  loading?(scope: { account: boolean }): VNode[];
  'account-menu'?(scope: {
    session: ActivitySession;
    entries: readonly AuthAccountMenuEntry[];
  }): VNode[];
}
interface AuthAccountMenuEntry {
  key: string;
  label: string;
  href: string;
  current?: boolean;
}
```

NavigationEntry/RouteContext use public `@/runa/ui` types. The shell has no emitted
events and never creates a session controller. AuthApplication accepts these same
optional navigation/current/locale/accountMenu props and optional
`session?: ApplicationSessionController`, plus `account?: boolean`; it forwards
the account-menu slot. Without a supplied session, the outer application creates
one useApplicationSession controller. AdminApplication passes the same controller
to useAdmin and ApplicationSessionShell directly; it does not nest AuthApplication.
Only the creator owns disposal, request observation, heartbeat/activity listeners,
logout and session refresh. Consumers never dispose a supplied controller. Account
switch/expiry fences every Admin response and clears protected state; shell locale
stays scoped. Actual facade consumers change together in point 4, without old-shape
adapters or copied security logic. App routers can supply a controller across route
changes; the default dispatcher owns one for its mounted application boundary.

A minimal advertisement is implemented on AdminAccess:
`public function entry(): array`, guarded API operation `entry` under version
1.0.0, session-authenticated GET `/api/admin/entry`, named `runa.admin.entry`.
It returns only `{available:boolean}`. It checks current operational Auth session,
Admin/Core availability, maintenance and current `runa.admin.enter` authority,
without requiring or consuming entry password proof. Missing grant/unavailable
Optional-independent dependencies yield false without diagnostic detail; missing
session returns 401, maintenance keeps the normal blocked response. Backend/API
failure leaves the link hidden. It returns no page/owner/widget/bootstrap data,
permissions list, user identity, proof state or configurable URL. The fixed route
precedes dynamic page routes. Every protected AdminAccess method still requires
the existing action-bound entry proof and native owning checks. The advertisement
cannot authorize a subsequent request or bypass protected bootstrap.

The shell fetches advertisement once after session validation and revalidates on
session/authority refresh and return to the application. A known same-session link
stays visible during the optional background read, then follows its result;
malformed/failed/denied reads hide it. Session/account change, restriction or lost
session immediately hides it and cancels/fences stale responses. Reopening the
menu and unchanged activity updates do not start another read. It uses the shared HTTP client
and session controller, no second activity loop or global store. Default link URLs
come from named routes. Extra source-owned accountMenu links use same-origin URLs;
links do not declare grants. Replacing the additional account-menu slot retains
User panel/sign-out and session security. No protected controller mounts before
session admission. HTML shells carry no protected bootstrap values.

`ApplicationLayoutProps.locale?: 'pl' | 'en'` fixes a presentation locale without
writing account preference state. Supplied Admin does not pin that optional locale;
it follows the shared application language and retains both preference controls.
Consumers that explicitly pin a locale hide the language cycle while retaining
the theme control. Descendant shared controls and
reauthentication inherit that scope. Standalone layouts retain optional preference
session initialization; a shared ApplicationSessionShell supplies ownership so they
do not issue a competing session read. Only the controller creator installs the
session observer/activity listeners and disposes them. A supplied controller lives
in the caller's Vue scope and survives shell replacement.

`DefaultApplication` accepts `adminComponents?: AdminComponentRegistry` and
`adminCollections?: Readonly<Record<string,AdminCollectionAdapters>>`; it dispatches
`/admin` and `/admin/{owner}/{page}` to AdminApplication without changing the App
scaffold. App routers may mount AdminApplication themselves and supply navigate and
one session. Module links select their authorized default_page, top navigation lists
only declared selected-owner pages, and the dashboard stays its own route; no
synthetic Overview is inserted. Other paths retain the existing Auth/welcome mapping.

The default entry link is next to User panel in the account dropdown only. Inside
Admin, module/independent-page navigation is on the left, selected module pages on
top; no empty/mandatory Overview. Custom full presentation keeps these supported
controllers and server enforcement while owning its markup/navigation.

## Auth administration composition

Auth supplies public lazy `AuthAdminUsers` and `AuthAdminSection` through
`@/runa/auth`; their props and source-key precedence are documented in
[Auth administration](../auth/administration.md#presentation-and-application-consumption).
The Admin facade also exports `AdminSectionContent` (section/page/controller and
optional components), and `AdminComponentBoundary` (identity/onFailure, default
slot). They compose existing controllers/rendering and do not add security logic.
`AdminResourceController.clear(): void` cancels and clears its read lane, erases
its retained query and discards late responses; it does not submit/cancel an owning
business write. Use it before changing an independent collection's subject.
Selecting a subject in useAdminForm overrides initial descriptor record IDs;
account/page ownership changes erase that selection along with protected drafts.

Optional independent workflow failures remain local to their commands and do not
block another section's atomic Save. Required sections and every atomic Save
participant still block when their content/read/component is unavailable. This
preserves failure isolation without omitting a failed Save participant.

## Record browser

Public `@/runa/admin` exports `AdminRecordBrowser` from
`src/runa/modules/admin/frontend/AdminRecordBrowser.vue`. Its exported `AdminRecordBrowserProps` extend
`AdminPageProps` with optional `allowCreate?: boolean` and
`initialFilters?: Record<string,string>`. It expects a declared `catalogue` table
section and composes every other section through public AdminSectionContent and
component failure boundaries. The exported `AdminRecordBrowserSlots.toolbar` receives
`{table: AdminTableController}`; it can bind approved filters to table.query.
Initial filters are applied once per mounted page. Table selection calls shared
form.selectSubject; creating selects null. Failed dirty-draft confirmation preserves
the prior subject and draft. Read filters never authorize new fields or operations.
Reuse the existing controller props for App-owned partial/full presentation.

Permissions and Audit defaults export their named page/section components through
`@/runa/permissions` and `@/runa/audit`; exact supplied pages/protocols live in their
[Permissions](../permissions/administration.md) and [Audit](../audit/administration.md)
guides. No security/session ownership moves into customer presentation.

## Operational dashboard and replacements

`@/runa/admin` exports `AdminOperations`, `AdminOperationalEditor`,
`AdminOperationResult`, `useAdminOperations`, `AdminOperationsController`,
`AdminOperationView`, `AdminOperationCommand` and `AdminOperationData`.
The dashboard composes operational content alongside independently isolated module
widgets. `AdminOperations` props: `{ admin: AdminController }`; default slot:
`{ controller: AdminOperationsController }`. The component exposes `controller`.
`AdminPresentation.operations?: Component` replaces that default with the same prop.

`AdminOperationalEditor` props are `{ controller: AdminOperationsController;
module: string; kind: 'settings' | 'health' | 'integrations'; writable?: boolean }`.
`AdminPresentation.operationalEditor?: Component` replaces it with the same props;
its `saved` event requests safe dashboard read reconciliation after publication.
`AdminOperationResult` accepts `{ result: AdminOperationData }` and renders native
outcomes/IDs, health reasons and cooperative cancellation without inventing success.
These views reuse Card/Select/TextInput/NumberInput/Checkbox/ConfirmDialog and
support PL/EN. Settings use native types and secret set-state; unsupported custom
types require a module-specific editor replacement. Source-validated health overrides
and non-sensitive integration maps use controlled JSON input.

`useAdminOperations(admin: AdminController): AdminOperationsController` owns one
request/write lane, session/proof failure propagation, context cancellation, dirty
navigation and confirmation. Returns `busy: ComputedRef<boolean>` for writes and `refreshing: ComputedRef<boolean>` for reads;
`dirty`, `conflict`: `Ref<boolean>`; `error: Ref<string>`;
`confirmation: Ref<string|undefined>`; `snapshot: Ref<AdminOperationData|undefined>`;
`changed`, `contextRevision`: `Ref<number>`; and these methods:

```ts
read(view: AdminOperationView, input?: AdminOperationData): Promise<AdminOperationData | undefined>
execute(command: AdminOperationCommand, input: AdminOperationData, destructive?: boolean): Promise<AdminOperationData | undefined>
refresh(filter?: string): Promise<void>
confirm(accepted: boolean): void
beforeLeave(): Promise<boolean>
```

`AdminOperationData = Record<string, AdminJson>`. The exact finite views/commands,
inputs, grants and native result shapes are in the
[operational transport contract](operations.md#operational-http-and-presentation-contract-point-9).
Replacement editors set `dirty`, call `beforeLeave()` before target/editor changes,
clear account-local data on `contextRevision`, and observe `changed` for refreshed
reads. Keep dirty drafts during read refresh/conflicts; an accepted reload may
replace them. Explicit retries only: never replay failed or cancelled writes.
Context invalidation cancels confirmations, aborts observation of requests and
removes prior snapshots; a cancelled write may have completed on the server.

Lifecycle preview retains affected modules/active work and an opaque actor-bound
review. Changing the intent/target discards it; Apply confirms the reviewed input
and native execution rechecks it. Pending/recovery/failed results remain visible.
Retained operation UUID controls invoke only native status/resume/cancel/recover;
maintenance/update/Core failure still blocks all protected HTTP.

Operational controller `refreshing: ComputedRef<boolean>` describes reads; `busy`
blocks only the deliberate write lane. Background reads keep editors available.
An explicit write may supersede an optional pending read; its response is fenced
and neither request is replayed. Same-session capability refresh preserves the
selected editor. An explicit operational reload cannot overwrite an edit made
while that read was pending; the existing generation stays unchanged for native
conflict/review handling. Read ownership, strict observation stamps and real context-loss
cleanup remain in the controller.

## Application global settings and runtime branding

`AdminApplicationSettings({controller: AdminOperationsController})` is the PL/EN
default editor. Its default slot receives `{controller: AdminApplicationSettingsController}`;
its exposed `settings` is the same controller. Replace it through
`AdminPresentation.applicationSettings`, or use the public
`useAdminApplicationSettings(operations: AdminOperationsController)` once in an
App-owned component. All symbols/types are exported from `@/runa/admin`.
Source home: `src/runa/modules/admin/frontend`; App replacements stay in App.

The controller returns `operations`, `revision: Ref<number>`,
`fields: ShallowRef<AdminApplicationSetting[]>`, `base/draft: Ref<Record<string,
string|number|boolean|null>>`, `dirty/valid: ComputedRef<boolean>`,
`conflicts: Ref<Set<string>>`, `invalid: Ref<string>`, `loaded/saved: Ref<boolean>`,
`refresh(): Promise<void>`, `edit(key, value): void`,
`resolve(key, keepDraft): void`, `save(): Promise<void>`.
Each field has `key,title,type,maxLength,choices,writable`; definitions and values
are server-authorized. Disable controls while operations are busy. Show invalid,
operation error/conflict and saved feedback; never infer write authority from the
presence of a field. Null/reset restores its source default. No technical settings
or asset upload control is supplied.

Reuse the passed operational controller rather than creating another session or
write lane. Refresh merges disjoint reads, preserves dirty edits and exposes
same-field overlaps with the current value. Resolve each overlap locally before
Save. A failed/stale write retains drafts and is never replayed. Fixed storage/Audit 503 refusals remain local errors with drafts intact; authentication/grant loss and real Core/maintenance refusal still close the protected shell. Account/access changes clear settings and fence late responses; same-session observations preserve dirty settings while invalidating old requests/reviews. The dashboard switches editors
through the existing dirty-navigation confirmation. Full App replacement retains
these protocols; it does not copy private session/security code.

`@/runa/ui` also exports the public `ApplicationPresentation` type
`{name,logo,revision}` and `loadApplicationPresentation(signal?: AbortSignal):
Promise<ApplicationPresentation | undefined>`. Failure returns undefined so a
consumer can restore source defaults. `createApplication` hydrates presentation
separately from canonical identity and refreshes after application-setting writes,
Auth session changes and focus/visibility/history navigation. Per-app epochs cancel
older reads; unmount cleans observers/listeners. The root/layout renders immediately while logo decoding updates branding in place; failed background reads retain confirmed presentation. It never stores branding in guest
preferences or changes canonical IDs/versions. Custom roots may use the same loader
and IdentityProvider; retain lifecycle fencing and source fallback.

## Polish and English presentation

Admin follows the existing application locale
(Polish default, English secondary), without a separate Admin preference/session.
Admin owns public `AdminText = string | {pl: string; en: string}` and
`AdminMessages = Readonly<Record<string, string>>`. `useAdminLocale()` returns
`{locale: Ref<'pl'|'en'>, t(text: AdminText): string}` for composition; `adminText(text,
locale, messages?)` is its pure counterpart. Source English strings are translation
keys; supplied Core presentation has a reviewed Polish catalogue. Unknown strings
remain source text. Localized metadata maps select their explicit language.
`AdminApplication.messages?: AdminMessages` extends/overrides the Polish catalogue
for source-owned App/module presentation. Implementation stays in Admin frontend,
public entrypoint `@/runa/admin`, bundled runtime presentation. Translations never
change machine keys, business values or authorization. Field and owner display
names retain both source locales in projection. Management page/section/command/
widget titles and optional page groups accept English strings or explicit PL/EN
maps; `DeclarationRules` validates both nonempty bounded language values. Existing
backend technical diagnostics remain English; views translate reviewed UI labels
and safe messages. Locale changes rerender presentation without reinitializing
controllers, refetching protected data or discarding drafts.

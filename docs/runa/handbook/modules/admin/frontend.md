<!-- runa-knowledge {"purpose":"current_guidance","effective_date":"2026-10-30","applicable_version":"0.7.0","decisions":[]} -->

# Admin frontend contract

Status: point 4 delivers the session shell, entry/menu, named document and page-read
routes, compiled component resolution, nested presentation providers and
`useAdmin`/`useAdminPage`. Delivered facade components are `AdminApplication`,
`AdminLayout`, `AdminLoading`, `AdminEntryProof` and `AdminPresentationProvider`;
`useAdminPresentation`, `resolveAdminComponent` and the read side of
`useAdminResource`, `useAdminTable` and `AdminResourceTable` are also delivered. Source
home is `src/runa/modules/admin/frontend`; public facade is `@/runa/admin`.
Application replacements live in `src/app/resources/js`. All HTTP uses `@/runa/http`;
all layout/forms/table/theme controls use existing `@/runa/ui` contracts.

Automatic collection-section dispatch is delivered through source-declared
`ManagementSection(view: 'table')`. Record forms/actions, form controllers and their
default components remain specified for points 5–6; no business write is available from
this shell. All wire/controller types below are exported, including types for
those future runtime functions. Rendered acceptance remains point 12.

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
  entryProof: { password: Ref<string>; busy: ComputedRef<boolean> };
  refresh(): Promise<void>;
  navigate(location: AdminLocation): Promise<boolean>;
  confirmEntry(): Promise<void>;
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
}
interface AdminResourceController {
  state: ComputedRef<AdminState>;
  result: Ref<AdminReadResult | undefined>;
  busy: ComputedRef<boolean>;
  error: Ref<AdminError | undefined>;
  read(query: AdminReadQuery): Promise<void>;
  command(input: AdminWrite): Promise<AdminCommandResult | undefined>;
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
}): AdminController;
function useAdminPage(
  admin: AdminController,
  location: Ref<AdminLocation>,
): AdminPageController;
function useAdminForm(
  page: AdminPageController,
  options?: { publicId?: Ref<string | null> },
): AdminFormController;
function useAdminResource(
  page: AdminPageController,
  section: string,
): AdminResourceController;
```

`entryProof.password` is transient input for the fixed native
`runa.admin.enter` password action. Render public `AdminEntryProof` with
`controller: AdminController`, or bind that input and call `confirmEntry()` once
on explicit submission. It has no slots or emitted events. Confirmation clears
the password, admits no duplicate submission and refreshes only bootstrap;
account/session invalidation and disposal erase it. Failure never replays a
business operation. `authorityRevision` on ApplicationSessionController increments
after successful session validation; heartbeat updates do not trigger new Admin
advertisements. Session identity, suspension, loading, pending logout and that
revision fence protected reads.

Composables mount once per keyed account/page; scope disposal aborts requests and
removes listeners/dirty guards. They own cancellation, session/CSRF/proof handling,
stale response rejection and English-scoped feedback. The same aggregate mutation
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
internally. Account/authority/page invalidation and scope disposal erase resource
results and cancel/fence pending responses. Queries never carry their subject or
filters into a different account/session or owning page. An explicit same-session page refresh
retains the mounted page while the read is pending; successful descriptor updates
refresh the last resource query. Returning to a visible document also refreshes
that query, with no timer/polling. A custom headless page controller owns timely
invalidation of its page/state; use the supplied controller to retain native
session admission. Call resource composables in a Vue scope and dispose them with
their page. This resource read lane does not own editable drafts or mutation
revisions; form reconciliation remains in the rest of point 5.

`command(input)` is deliberately unavailable at this checkpoint: it returns
undefined, sets `admin_operation_unavailable` and sends no request. It does not
claim a business write or silently replay an operation. Generated record forms,
preview/save and native write coordination retain their planned
delivery status. Entry proof refusal from a resource read returns the owning Admin
controller to native entry confirmation; stronger operation proof remains owner-owned.

```ts
import { useAdminResource } from '@/runa/admin';
// page is the supplied useAdminPage controller in the same Vue scope.
const resource = useAdminResource(page, 'profile');
await resource.read({ kind: 'record', public_id: publicId });
// For a readable reference field, retain the owning opaque next cursor.
await resource.read({ kind: 'options', field: 'manager', limit: 25 });
```

## Delivered cursor table composition

Admin owns `useAdminTable(page: AdminPageController, section: string): AdminTableController`
and `AdminResourceTable` through `@/runa/admin`. Their implementation lives under
Admin frontend and is bundled with the consuming presentation. Module and App
page replacements can use this source-owned read presentation without private
imports, an extra HTTP client or another session owner. The default page also
composes this controller/component automatically for `view: 'table'` sections.
Editable forms, bulk/export command bindings and mutation reconciliation remain
planned in point 5.

```ts
interface AdminTableController {
  section: ComputedRef<AdminSectionData | undefined>;
  resource: AdminResourceController;
  query: Ref<TableQuery>;
  columns: ComputedRef<readonly TableColumn<AdminRecord>[]>;
  provider: ComputedRef<TableProvider<AdminRecord>>;
  error: ComputedRef<AdminError | undefined>;
  refresh(): Promise<void>;
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
and disposal. Account/authority/page changes erase table filters and cursor state;
same-context descriptor updates preserve allowed query controls and restart page 1.
No filters or records are persisted to browser preferences at this checkpoint.

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

The supplied component is English and reuses Table's semantic tokens, horizontal
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
}
interface AdminPageSlots {
  default?(scope: AdminPageProps): VNode[];
  heading?(scope: AdminPageProps): VNode[];
  actions?(scope: AdminPageProps): VNode[];
}
interface AdminSectionProps {
  section: AdminSectionData;
  controller: AdminFormController;
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
}
type AdminFieldEmits = { 'update:modelValue': [value: AdminFieldValue] };
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

The complete target facade component set (runtime delivery is listed above): `AdminApplication`, `AdminPresentationProvider`,
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
`{section: AdminSectionData, page: AdminPageController}`.
The page prop lets section replacements compose `useAdminTable(page, section.key)`
or `useAdminResource(page, section.key)` in their own scope. Default dispatch mounts
no competing read controller for a replacement. A section-specific source component
takes precedence over a provider; an unavailable non-null build never falls back
to the provider or default table. Binding diagnostics render a safe warning before
mounting any section presentation. Each section keeps its own render boundary, so
one broken custom presentation does not hide healthy siblings.

A source-declared table mounts one bounded collection lane, with English shared
controls, secret set-state and existing cursor/query/disposal behavior. Source
`view` is presentation intent, never additional authority. The default `record`
intent retains its unavailable-controls placeholder until generated forms arrive. The form/controller prop in
the complete target signatures arrives with point 5. Defaults render
page/section headings, collection tables, safe unavailable/empty states and dashboard widget values;
they do not pretend to supply editable controls before their owning point.

The public resolver signature is
`resolveAdminComponent(identity: AdminComponent | null, components?: AdminComponentRegistry): {component: Component | undefined; available: boolean}`.
`AdminComponent` is the exact wire Component identity. Null returns an undefined
component and available=true, allowing the selected provider/default. An unavailable
non-null identity returns a safe supplied unavailable component and available=false.
Module loaders are chosen only after owner/manifest/build/key agreement; App keys
also require a matching explicit static registry entry. Use this resolver in full
replacements; it does not import a path from metadata. Default rendering isolates
component setup/render/load failures to its boundary. Required form blocking is
owned by point 5; source identity validation alone is not Save authorization.

Defaults remain usable without configuration: ApplicationLayout, existing Form/
FormSection/controls, Table/useTable, confirmation and feedback. Admin is English
including shared controls/proof/errors; this scoped locale does not persist a
change to the account's ordinary app language. Light/dark, responsive keyboard and
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
session/authority refresh and return to the application; it cancels/fences stale
responses and removes the link on loss of authority. It uses the shared HTTP client
and session controller, no second activity loop or global store. Default link URLs
come from named routes. Extra source-owned accountMenu links use same-origin URLs;
links do not declare grants. Replacing the additional account-menu slot retains
User panel/sign-out and session security. No protected controller mounts before
session admission. HTML shells carry no protected bootstrap values.

`ApplicationLayoutProps.locale?: 'pl' | 'en'` fixes a presentation locale without
writing account preference state; supplied Admin passes en. It hides the language
cycle while retaining the theme control. Descendant shared controls and
reauthentication inherit that scope. Standalone layouts retain optional preference
session initialization; a shared ApplicationSessionShell supplies ownership so they
do not issue a competing session read. Only the controller creator installs the
session observer/activity listeners and disposes them. A supplied controller lives
in the caller's Vue scope and survives shell replacement.

`DefaultApplication` accepts `adminComponents?: AdminComponentRegistry`; it dispatches
`/admin` and `/admin/{owner}/{page}` to AdminApplication without changing the App
scaffold. App routers may mount AdminApplication themselves and supply navigate and
one session. Module links select their authorized default_page, top navigation lists
only declared selected-owner pages, and the dashboard stays its own route; no
synthetic Overview is inserted. Other paths retain the existing Auth/welcome mapping.

The default entry link is next to User panel in the account dropdown only. Inside
Admin, module/independent-page navigation is on the left, selected module pages on
top; no empty/mandatory Overview. Custom full presentation keeps these supported
controllers and server enforcement while owning its markup/navigation.

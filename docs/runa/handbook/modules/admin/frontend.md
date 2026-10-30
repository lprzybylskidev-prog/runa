<!-- runa-knowledge {"purpose":"current_guidance","effective_date":"2026-10-30","applicable_version":"0.7.0","decisions":[]} -->

# Admin frontend contract

Status: point 4 delivers the session shell, entry/menu, named document and page-read
routes, compiled component resolution, nested presentation providers and
`useAdmin`/`useAdminPage`. Delivered facade components are `AdminApplication`,
`AdminLayout`, `AdminLoading`, `AdminEntryProof` and `AdminPresentationProvider`;
`useAdminPresentation` and `resolveAdminComponent` are also delivered. Source
home is `src/runa/modules/admin/frontend`; public facade is `@/runa/admin`.
Application replacements live in `src/app/resources/js`. All HTTP uses `@/runa/http`;
all layout/forms/table/theme controls use existing `@/runa/ui` contracts.

Business tables/forms/actions, resource/form controllers and their default
components remain specified for points 5–6; no business write is available from
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

Point-4 page replacements receive `{controller: AdminPageController}`; section
replacements receive `{section: AdminSectionData}`. The form/controller prop in
the complete target signatures arrives with point 5. Defaults currently render
page/section headings, safe unavailable/empty states and dashboard widget values;
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

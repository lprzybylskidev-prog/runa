<!-- runa-knowledge {"purpose":"proposal","effective_date":null,"applicable_version":"0.7.0","decisions":[]} -->

# Admin frontend contract

Status: specification for RM-2026-016; exports below are not implemented. Source
home is `src/runa/modules/admin/frontend`; public facade is `@/runa/admin`.
Application replacements live in `src/app/resources/js`. All HTTP uses `@/runa/http`;
all layout/forms/table/theme controls use existing `@/runa/ui` contracts.

## Controllers and public types

All following types, components and functions are public exports of that facade.
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
} from '@/runa/auth';
import type { ApplicationLayoutProps, ApplicationLayoutSlots } from '@/runa/ui';

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
interface AdminReadQuery {
  kind: 'record' | 'page' | 'options';
  public_id?: string;
  cursor?: string;
  limit?: number;
  filters?: Record<string, string | number | boolean | null>;
  sort?: readonly { key: string; direction: 'asc' | 'desc' }[];
}
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
  components?: Readonly<Record<string, Component>>;
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

Facade component names: `AdminApplication`, `AdminPresentationProvider`,
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
statically and passes the map, never resolves a request/database URL or private
module source path. Module components continue through existing manifest-bound
build discovery. The supplied dispatcher forwards an App registry only when the
application explicitly configures one; no sample screen is added to the scaffold.

Defaults remain usable without configuration: ApplicationLayout, existing Form/
FormSection/controls, Table/useTable, confirmation and feedback. Admin is English
including shared controls/proof/errors; this scoped locale does not persist a
change to the account's ordinary app language. Light/dark, responsive keyboard and
focus behavior are required in final rendered acceptance. No runtime/template
compiler, new framework, UI dependency or duplicated table backend.

## Additive Auth menu and navigation seam

Existing AuthApplicationProps `account?: boolean` and current slots remain
unchanged. Add optional `navigation?: readonly NavigationEntry[]`,
`topNavigation?: readonly NavigationEntry[]`, `current?: RouteContext`,
`locale?: 'pl' | 'en'` and `accountMenu?: readonly AuthAccountMenuEntry[]`, all
using public `@/runa/ui` navigation types. Export `AuthAccountMenuEntry` through
`@/runa/auth` as `{key:string,label:string,href:string,current?:boolean}`.
Values are app-owned trusted presentation; same-origin named-route URLs only.
They are links, not arbitrary action callbacks or authorization declarations.

Add slot `account-menu?(scope:{session:ActivitySession,entries:readonly
AuthAccountMenuEntry[]}): VNode[]` through the existing Auth facade; it replaces
only additional menu entries, retaining User panel/sign-out and session security.
Admin entry is advertised only after a current guarded availability/grant check
without protected bootstrap data; opening Admin then requests its entry proof.
Existing `useApplicationSession` gets no required constructor argument or changed
return member; any optional navigation input is additive. The custom application
router can mount AdminApplication directly at its named paths without using the
minimal default dispatcher. Existing `/modules/...` paths retain their semantics.

The default entry link is next to User panel in the account dropdown only. Inside
Admin, module/independent-page navigation is on the left, selected module pages on
top; no empty/mandatory Overview. Custom full presentation keeps these supported
controllers and server enforcement while owning its markup/navigation.

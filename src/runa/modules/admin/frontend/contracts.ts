import type { Component, ComputedRef, Ref, VNode } from 'vue';
import type {
  ApplicationNavigate,
  ApplicationSessionController,
} from '@/runa/auth';
import type { ApplicationLayoutProps } from '@/runa/ui';
import type { TableColumn, TableProvider, TableQuery } from '@/runa/ui';
export type AdminJson =
  null | boolean | number | string | AdminJson[] | { [key: string]: AdminJson };
export type AdminFieldValue = AdminJson;
export type AdminFieldPath = string;
export interface AdminComponent {
  key: string;
  owner: string;
  manifest_hash: string | null;
  build_id: string | null;
}
export interface AdminRecord {
  public_id: string | null;
  revision: string;
  values: Record<string, AdminJson>;
}
export interface AdminFieldData {
  key: string;
  type: string;
  label: string;
  readonly: boolean;
  sensitive: boolean;
  filterable: boolean;
  sortable: boolean;
  lookup: boolean;
  required: boolean;
  max_length: number | null;
  choices: string[];
  multiple: boolean;
  component: AdminComponent | null;
}
export interface AdminCommandData {
  key: string;
  title: string;
  mode: 'independent' | 'atomic';
  destructive: boolean;
  requirement: 'none' | 'password' | 'second_factor' | 'both';
  preview_required: boolean;
}
export interface AdminSectionData {
  key: string;
  title: string;
  readonly: boolean;
  required: boolean;
  component: AdminComponent | null;
  fields: AdminFieldData[];
  commands: AdminCommandData[];
  record: AdminRecord | null;
  diagnostic: string | null;
}
export interface AdminWidgetData {
  id: string;
  title: string;
  component: AdminComponent | null;
  values: Record<string, AdminJson>;
  observed_at: string | null;
  expires_at: string | null;
  diagnostic: string | null;
}
export interface AdminPageData {
  schema_version: 1;
  owner: string;
  key: string;
  title: string;
  generation: number;
  revision: string;
  component: AdminComponent | null;
  sections: AdminSectionData[];
}
export interface AdminBootstrap {
  schema_version: 1;
  generation: number;
  revision: string;
  owners: {
    id: string;
    title: string;
    default_page: string;
    pages: {
      key: string;
      title: string;
      href: string;
      group: string | null;
      order: number;
    }[];
  }[];
  dashboard: { widgets: AdminWidgetData[] };
  presentation: {
    name: string;
    logo: { src: string; accessibleName: string } | null;
    revision: number;
  };
}
export type AdminState =
  | 'loading'
  | 'ready'
  | 'proof_required'
  | 'forbidden'
  | 'unavailable'
  | 'failed';
export interface AdminError {
  message: string;
  reason: string;
  errors?: Record<AdminFieldPath, readonly string[]>;
  correlation_id?: string;
}
export interface AdminLocation {
  owner: string;
  page: string;
}
export interface AdminController {
  session: ApplicationSessionController;
  state: ComputedRef<AdminState>;
  bootstrap: Ref<AdminBootstrap | undefined>;
  location: ComputedRef<AdminLocation | undefined>;
  error: Ref<AdminError | undefined>;
  /** Transient input, erased after submission, account invalidation and disposal. */
  entryProof: { password: Ref<string>; busy: ComputedRef<boolean> };
  refresh(): Promise<void>;
  navigate(location: AdminLocation): Promise<boolean>;
  confirmEntry(): Promise<void>;
}
export interface AdminPageController {
  state: ComputedRef<AdminState>;
  page: Ref<AdminPageData | undefined>;
  error: Ref<AdminError | undefined>;
  refresh(): Promise<void>;
}
export type AdminComponentRegistry = Readonly<
  Record<string, { readonly buildId: string; readonly component: Component }>
>;
export interface AdminPresentation {
  layout?: Component;
  page?: Component;
  section?: Component;
  field?: Component;
  form?: Component;
  widget?: Component;
  action?: Component;
  loading?: Component;
}
export interface AdminApplicationProps {
  navigate?: ApplicationNavigate | undefined;
  components?: AdminComponentRegistry | undefined;
  session?: ApplicationSessionController | undefined;
}
export type AdminLayoutProps = ApplicationLayoutProps & {
  controller: AdminController;
};

export interface AdminApplicationSlots {
  default?(scope: { controller: AdminController }): VNode[];
  loading?(): VNode[];
}
export type AdminLayoutSlots = import('@/runa/ui').ApplicationLayoutSlots;
export interface AdminPresentationProviderProps {
  components: AdminPresentation;
}
export interface AdminPresentationProviderSlots {
  default?(): VNode[];
}
export interface AdminEntryProofProps {
  controller: AdminController;
}

/** Resource reads are delivered; form and write coordination follow separately. */
export interface AdminRecordQuery {
  kind: 'record' | 'page';
  public_id?: string;
  cursor?: string;
  limit?: number;
  filters?: Record<string, string | number | boolean | null>;
  sort?: readonly { key: string; direction: 'asc' | 'desc' }[];
}
export interface AdminOptionsQuery {
  kind: 'options';
  field: string;
  public_id?: string;
  cursor?: string;
  limit?: number;
  filters?: Record<string, string | number | boolean | null>;
}
export type AdminReadQuery = AdminRecordQuery | AdminOptionsQuery;
export type AdminReadResult =
  | AdminRecord
  | { items: AdminRecord[]; next: string | null; total: number | null }
  | {
      field: string;
      items: { value: string; label: string }[];
      next: string | null;
    };
export interface AdminWrite {
  command: string;
  public_id: string | null;
  revision: string;
  values: Record<string, AdminJson>;
  review?: Record<string, AdminJson> | null;
  confirmed?: boolean;
}
export interface AdminAggregateInput {
  revision: string;
  participants: { id: string; input: AdminWrite }[];
}
export interface AdminPreview {
  revision: string;
  participants: {
    id: string;
    review: {
      fingerprint: string;
      changes: Record<string, AdminJson>;
      requirement: string;
      every_action: boolean;
    };
  }[];
}
export interface AdminSaveResult {
  status: 'committed';
  revision: string;
  participants: { id: string; record: AdminRecord | null }[];
}
export interface AdminCommandResult {
  status: 'committed' | 'pending' | 'recovery_required';
  record: AdminRecord | null;
  operation_id: string | null;
}
export interface AdminConflict {
  path: AdminFieldPath;
  base: AdminJson;
  local: AdminJson;
  remote: AdminJson;
}
export interface AdminFormController {
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
export interface AdminResourceController {
  state: ComputedRef<AdminState>;
  result: Ref<AdminReadResult | undefined>;
  busy: ComputedRef<boolean>;
  error: Ref<AdminError | undefined>;
  read(query: AdminReadQuery): Promise<void>;
  command(input: AdminWrite): Promise<AdminCommandResult | undefined>;
}
/** One cursor-backed collection lane; instantiate inside the owning page scope. */
export interface AdminTableController {
  section: ComputedRef<AdminSectionData | undefined>;
  resource: AdminResourceController;
  query: Ref<TableQuery>;
  columns: ComputedRef<readonly TableColumn<AdminRecord>[]>;
  provider: ComputedRef<TableProvider<AdminRecord>>;
  error: ComputedRef<AdminError | undefined>;
  refresh(): Promise<void>;
}
export interface AdminResourceTableProps {
  controller: AdminTableController;
}
export interface AdminResourceTableSlots {
  toolbar?(scope: { feedbackTarget: string }): VNode[];
  actions?(scope: { row: AdminRecord }): VNode[];
  filters?(scope: {
    query: TableQuery;
    update: (value: TableQuery) => void;
  }): VNode[];
  [key: `cell-${string}`]: (scope: {
    row: AdminRecord;
    value: string | number | null;
  }) => VNode[];
}

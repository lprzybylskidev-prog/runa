export type AdminText = string | { readonly pl: string; readonly en: string };
export type AdminMessages = Readonly<Record<string, string>>;
import type { Component, ComputedRef, Ref, VNode } from 'vue';
import type {
  ApplicationNavigate,
  ApplicationSessionController,
} from '@/runa/auth';
import type { ApplicationLayoutProps, ButtonIntent } from '@/runa/ui';
import type { TableColumn, TableProvider, TableQuery } from '@/runa/ui';
// String precedes Boolean in Vue runtime prop inference: an empty text draft stays a string.
export type AdminJson =
  string | number | boolean | null | AdminJson[] | { [key: string]: AdminJson };
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
  label: AdminText;
  description?: AdminText;
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
  title: AdminText;
  mode: 'independent' | 'atomic';
  destructive: boolean;
  requirement: 'none' | 'password';
  preview_required: boolean;
  inputs: string[];
  bulk: boolean;
  action: string | null;
  available: boolean;
  intent: 'action' | 'save' | 'create' | 'delete';
}
export interface AdminSectionData {
  export_allowed?: boolean;
  view: 'record' | 'table';
  key: string;
  title: AdminText;
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
  title: AdminText;
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
  title: AdminText;
  generation: number;
  revision: string;
  component: AdminComponent | null;
  sections: AdminSectionData[];
}
export interface AdminBootstrap {
  activity: import('@/runa/auth').ProtectedActivityStatus;
  schema_version: 1;
  generation: number;
  revision: string;
  owners: {
    id: string;
    title: AdminText;
    default_page: string;
    pages: {
      key: string;
      title: AdminText;
      href: string;
      group: AdminText | null;
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
  entryProof: import('@/runa/auth').OperationProofController;
  refresh(): Promise<void>;
  navigate(location: AdminLocation): Promise<boolean>;
  confirmEntry(): Promise<void>;
  cancelEntry(): void;
}
export interface AdminPageController {
  state: ComputedRef<AdminState>;
  page: Ref<AdminPageData | undefined>;
  error: Ref<AdminError | undefined>;
  refresh(): Promise<void>;
}
/** Live section replacement props sharing the owning page form controller. */
export interface AdminSectionPresentationProps {
  section: AdminSectionData;
  page: AdminPageController;
  controller: AdminFormController;
}
export type AdminComponentRegistry = Readonly<
  Record<string, { readonly buildId: string; readonly component: Component }>
>;
export interface AdminPresentation {
  operationHistory?: Component;
  moduleCard?: Component;
  operations?: Component;
  applicationSettings?: Component;
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
  messages?: AdminMessages | undefined;
  collections?: Readonly<Record<string, AdminCollectionAdapters>> | undefined;
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

/** Bounded authorized resource query shared by default and custom presentations. */
export interface AdminRecordQuery {
  kind: 'record' | 'page';
  export?: boolean;
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
  sections: ComputedRef<readonly AdminSectionData[]>;
  records: Ref<Record<string, AdminRecord>>;
  blocked: ComputedRef<boolean>;
  confirmation: Ref<
    { title: AdminText; description: string; intent: ButtonIntent } | undefined
  >;
  confirm(accepted: boolean): void;
  beforeLeave(): Promise<boolean>;
  selectSubject(publicId: string | null): Promise<boolean>;
  fieldPath(section: string, field: string): AdminFieldPath;
  lookup(section: string, field: string): AdminFieldLookup;
  execute(
    section: string,
    command: string,
    options?: { confirmReviewed?: boolean },
  ): Promise<AdminCommandResult | undefined>;
  reviewCommand(
    section: string,
    command: string,
  ): Promise<AdminCommandReview | undefined>;
  commandReviews: Ref<Record<string, AdminCommandReview>>;
  runIndependent(
    section: string,
    action: (record: AdminRecord, signal: AbortSignal) => Promise<void>,
  ): Promise<boolean>;
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
export interface AdminCommandReview {
  fingerprint: string;
  changes: Record<string, AdminJson>;
  requirement: AdminCommandData['requirement'];
  every_action: boolean;
}
export interface AdminResourceController {
  /** Cancel and clear this read lane, including late responses. */
  clear(): void;
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
/** One cursor-backed collection lane; instantiate inside the owning page scope. */
export interface AdminTableController {
  section: ComputedRef<AdminSectionData | undefined>;
  resource: AdminResourceController;
  query: Ref<TableQuery>;
  columns: ComputedRef<readonly TableColumn<AdminRecord>[]>;
  provider: ComputedRef<TableProvider<AdminRecord>>;
  error: ComputedRef<AdminError | undefined>;
  refresh(): Promise<void>;
  exportRows(signal: AbortSignal): Promise<readonly AdminRecord[]>;
  selection: Ref<
    import('@/runa/ui').RowSelection<import('@/runa/ui').TableQuery>
  >;
  views: import('@/runa/ui').TableViewsController;
  adapters: ComputedRef<AdminCollectionAdapters>;
  selected: Ref<string | null | undefined>;
  select(publicId: string | null): Promise<boolean>;
  confirmation: Ref<{ title: AdminText; intent: ButtonIntent } | undefined>;
  confirm(accepted: boolean): void;
}
export interface AdminCollectionAdapters {
  exports?: import('@/runa/ui').ExportProvider<import('@/runa/ui').TableQuery>;
  bulk?: import('@/runa/ui').BulkProvider<import('@/runa/ui').TableQuery>;
  print?: import('@/runa/ui').PrintAdapter<import('@/runa/ui').TableQuery>;
  preferences?: {
    scope: import('@/runa/ui').PreferenceScope;
    store: import('@/runa/ui').PreferenceStore;
  };
}
export interface AdminFormProps {
  controller: AdminFormController;
}
export interface AdminPageProps {
  controller: AdminPageController;
  form: AdminFormController;
  components?: AdminComponentRegistry | undefined;
}
export interface AdminPageSlots {
  default?(scope: AdminPageProps): VNode[];
  heading?(scope: AdminPageProps): VNode[];
  actions?(scope: AdminPageProps): VNode[];
}
export interface AdminFormSlots {
  default?(scope: AdminFormProps): VNode[];
  actions?(scope: AdminFormProps): VNode[];
  review?(scope: AdminFormProps): VNode[];
}
export type AdminFormEmits = { saved: [result: AdminSaveResult] };
export interface AdminSectionProps {
  embedded?: boolean;
  section: AdminSectionData;
  controller: AdminFormController;
  components?: AdminComponentRegistry | undefined;
}
export interface AdminSectionSlots {
  commandReview?(scope: {
    command: AdminCommandData;
    review: AdminCommandReview;
  }): VNode[];
  default?(scope: {
    section: AdminSectionData;
    controller: AdminFormController;
  }): VNode[];
}
export interface AdminActionProps {
  command: AdminCommandData;
  busy: boolean;
  disabled: boolean;
}
export type AdminActionEmits = { invoke: [] };
export interface AdminResourceTableProps {
  controller: AdminTableController;
  selectable?: boolean;
  rowClass?: ((row: AdminRecord) => string | undefined) | undefined;
}
/** The owning consumer supplies an authorized, field-bound lookup lane. */
export interface AdminFieldLookup {
  provider: import('@/runa/ui').SelectionProvider;
  selectedOptions?: readonly import('@/runa/ui').SelectOption[];
  searchable?: boolean;
}
export interface AdminFieldProps {
  clearable?: boolean | undefined;
  id?: string | undefined;
  hideLabel?: boolean | undefined;
  field: AdminFieldData;
  modelValue: AdminFieldValue;
  errors: readonly string[];
  readonly: boolean;
  busy: boolean;
  lookup?: AdminFieldLookup | undefined;
  components?: AdminComponentRegistry | undefined;
}
export type AdminFieldEmits = {
  'update:modelValue': [value: AdminFieldValue];
  availability: [available: boolean];
};
export interface AdminResourceTableSlots {
  afterFilters?(): VNode[];
  toolbar?(scope: { feedbackTarget: string }): VNode[];
  actions?(scope: { row: AdminRecord }): VNode[];
  filters?(scope: {
    query: TableQuery;
    update: (value: TableQuery) => void;
    resetKey: number;
    setValidity: (key: string, valid: boolean) => void;
  }): VNode[];
  [key: `cell-${string}`]: (scope: {
    row: AdminRecord;
    value: string | number | null;
  }) => VNode[];
}

/** Cursor catalogue plus shared subject/detail composition. */
export interface AdminRecordBrowserProps extends AdminPageProps {
  allowCreate?: boolean;
  initialFilters?: Record<string, string>;
}
export interface AdminRecordBrowserSlots {
  toolbar?(props: { table: AdminTableController }): VNode[];
}

export interface AdminModuleCardProps {
  module: import('./useAdminOperations').AdminOperationData;
  disabled?: boolean;
  interactive?: boolean;
  details?: boolean;
}

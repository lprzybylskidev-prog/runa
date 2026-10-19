export type ExportFormat = 'csv' | 'xlsx' | 'pdf';
export type ExportCell = string | number | boolean | null;

export interface ExportColumn<Row> {
  readonly key: string;
  readonly label: string;
  readonly value: (row: Row) => ExportCell;
  readonly format?: 'text' | 'number' | 'date' | 'datetime';
}

/** Query snapshots are owned/validated by the application, not the UI library. */
export type RowSelection<Query> =
  | { readonly mode: 'explicit'; readonly publicIds: readonly string[] }
  | {
      readonly mode: 'all-matching';
      readonly query: Query;
      readonly excludedPublicIds: readonly string[];
      readonly matchingCount: number;
    };

export type ExportScope<Query> =
  | { readonly kind: 'all' }
  | { readonly kind: 'filtered'; readonly query: Query }
  | { readonly kind: 'selected'; readonly selection: RowSelection<Query> };

export interface ExportCapabilities {
  readonly execution?: 'client' | 'server';
  readonly formats: readonly ExportFormat[];
  readonly scopes: readonly ('all' | 'filtered' | 'selected')[];
  readonly cancellation: boolean;
}

export interface ExportRequest<Query> {
  readonly format: ExportFormat;
  readonly scope: ExportScope<Query>;
  readonly columnKeys: readonly string[];
  readonly filename: string;
  readonly orientation?: 'portrait' | 'landscape';
  readonly operationKey?: string;
}

export interface ExportDownload {
  readonly filename: string;
  readonly mimeType: string;
  readonly source: Blob | URL;
}

export interface OperationFailure {
  readonly code: string;
  readonly message: string;
  readonly retryable: boolean;
}

export type OperationStatus =
  | { readonly state: 'queued' }
  | { readonly state: 'running'; readonly progress?: number }
  | {
      readonly state: 'completed';
      readonly download?: ExportDownload;
      readonly succeeded?: number;
    }
  | { readonly state: 'failed'; readonly error: OperationFailure }
  | {
      readonly state: 'partial';
      readonly download?: ExportDownload;
      readonly error: OperationFailure;
      readonly succeeded: number;
      readonly failed: number;
    }
  | { readonly state: 'cancelled' };

export interface ExportOperation {
  readonly publicId: string;
  poll(signal: AbortSignal): Promise<OperationStatus>;
  retry?: (signal: AbortSignal) => Promise<OperationResult>;
  cancel?: (signal: AbortSignal) => Promise<void>;
}

export type ExportResult =
  | { readonly kind: 'download'; readonly download: ExportDownload }
  | { readonly kind: 'operation'; readonly operation: ExportOperation };

/** Server datasets use application-owned providers, never a whole-dataset fetch. */
export interface ExportProvider<Query> {
  readonly capabilities: ExportCapabilities;
  start(
    request: ExportRequest<Query>,
    signal: AbortSignal,
  ): Promise<ExportResult>;
}

/** Bounded browser-local data only; generation adapters are bounded and replaceable. */
export interface ClientExportAdapter<Row> {
  readonly format: ExportFormat;
  readonly maxRows: number;
  generate(
    rows: readonly Row[],
    columns: readonly ExportColumn<Row>[],
    options: {
      readonly filename: string;
      readonly separator?: ',' | ';' | '\t';
      readonly formulaHandling?: 'escape' | 'preserve';
      readonly orientation?: 'portrait' | 'landscape';
    },
    signal: AbortSignal,
  ): Promise<ExportDownload>;
}

/** Printing is a separate action, not an asynchronous file export format. */
export interface PrintAdapter<Query> {
  print(scope: ExportScope<Query>, signal: AbortSignal): Promise<void>;
}

export type OperationResult =
  ExportResult | { readonly kind: 'status'; readonly status: OperationStatus };
export interface BulkRequest<Query> {
  readonly action: string;
  readonly selection: RowSelection<Query>;
  readonly operationKey: string;
}
export interface BulkProvider<Query> {
  readonly actions: readonly { key: string; label: string }[];
  readonly cancellation: boolean;
  start(
    request: BulkRequest<Query>,
    signal: AbortSignal,
  ): Promise<OperationResult>;
}

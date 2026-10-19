import type {
  ExportColumn,
  ExportProvider,
  ExportScope,
  ClientExportAdapter,
} from '../runa/ui/exports';
import type { TableColumn, TableQuery } from '../runa/ui/tables';
import { clientTableRows } from './table';
import { createCsvAdapter } from './exportFiles';
import { createXlsxAdapter, createPdfAdapter } from './binaryExports';
export function clientExportRows<Row>(
  rows: readonly Row[],
  columns: readonly TableColumn<Row>[],
  scope: ExportScope<TableQuery>,
  rowKey: (row: Row) => string,
): Row[] {
  if (scope.kind === 'all') return [...rows];
  if (scope.kind === 'filtered')
    return clientTableRows(rows, columns, scope.query);
  const selection = scope.selection;
  if (selection.mode === 'explicit') {
    const ids = new Set(selection.publicIds),
      result = rows.filter((row) => ids.has(rowKey(row)));
    if (result.length !== ids.size) throw new Error('selection_stale');
    return result;
  }
  const matching = clientTableRows(rows, columns, selection.query);
  if (matching.length !== selection.matchingCount)
    throw new Error('selection_stale');
  return matching.filter(
    (row) => !selection.excludedPublicIds.includes(rowKey(row)),
  );
}
export function createClientExportProvider<Row>(options: {
  rows: () => readonly Row[];
  columns: () => readonly ExportColumn<Row>[];
  tableColumns: () => readonly TableColumn<Row>[];
  rowKey: (row: Row) => string;
  adapters?: readonly ClientExportAdapter<Row>[];
}): ExportProvider<TableQuery> {
  const adapters = options.adapters ?? [
    createCsvAdapter<Row>(),
    createXlsxAdapter<Row>(),
    createPdfAdapter<Row>(),
  ];
  return {
    capabilities: {
      execution: 'client',
      formats: adapters.map((a) => a.format),
      scopes: ['all', 'filtered', 'selected'],
      cancellation: true,
    },
    async start(request, signal) {
      signal.throwIfAborted();
      const adapter = adapters.find((a) => a.format === request.format);
      if (!adapter) throw new Error('unsupported_export');
      const available = options.columns(),
        columns = request.columnKeys.map((key) =>
          available.find((c) => c.key === key),
        );
      if (
        !columns.length ||
        columns.some((c) => !c) ||
        new Set(request.columnKeys).size !== columns.length
      )
        throw new Error('invalid_export_columns');
      const rows = clientExportRows(
        options.rows(),
        options.tableColumns(),
        request.scope,
        options.rowKey,
      );
      const download = await adapter.generate(
        rows,
        columns as ExportColumn<Row>[],
        {
          filename: request.filename,
          ...(request.orientation ? { orientation: request.orientation } : {}),
        },
        signal,
      );
      return { kind: 'download', download };
    },
  };
}

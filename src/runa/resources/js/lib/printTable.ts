import printStyles from '../../css/export-print.css?url';
import type { ExportColumn, PrintAdapter } from '../runa/ui/exports';
import type { TableColumn, TableQuery } from '../runa/ui/tables';
import { clientExportRows } from './clientExports';
import { assertExportBounds } from './exportFiles';

export function populatePrintDocument<Row>(
  doc: Document,
  rows: readonly Row[],
  columns: readonly ExportColumn<Row>[],
  title: string,
) {
  doc.title = title;
  const heading = doc.createElement('h1');
  heading.textContent = title;
  const table = doc.createElement('table'),
    head = doc.createElement('thead'),
    body = doc.createElement('tbody'),
    headers = doc.createElement('tr');
  for (const column of columns) {
    const th = doc.createElement('th');
    th.scope = 'col';
    th.textContent = column.label;
    headers.append(th);
  }
  head.append(headers);
  for (const row of rows) {
    const tr = doc.createElement('tr');
    for (const column of columns) {
      const td = doc.createElement('td');
      td.textContent = String(column.value(row) ?? '');
      tr.append(td);
    }
    body.append(tr);
  }
  table.append(head, body);
  doc.body.replaceChildren(heading, table);
}
export function createClientPrintAdapter<Row>(options: {
  rows: () => readonly Row[];
  columns: () => readonly ExportColumn<Row>[];
  tableColumns: () => readonly TableColumn<Row>[];
  rowKey: (row: Row) => string;
  title: () => string;
}): PrintAdapter<TableQuery> {
  return {
    async print(scope, signal) {
      const rows = clientExportRows(
          options.rows(),
          options.tableColumns(),
          scope,
          options.rowKey,
        ),
        columns = options.columns();
      assertExportBounds(rows, columns, signal);
      const frame = document.createElement('iframe');
      frame.className = 'runa-print-frame';
      frame.title = options.title();
      document.body.append(frame);
      const doc = frame.contentDocument,
        target = frame.contentWindow;
      if (!doc || !target) {
        frame.remove();
        throw new Error('print_unavailable');
      }
      const cleanup = () => {
        frame.remove();
        signal.removeEventListener('abort', cleanup);
        globalThis.clearTimeout(timer);
      };
      const timer = globalThis.setTimeout(cleanup, 60000);
      signal.addEventListener('abort', cleanup, { once: true });
      target.addEventListener('afterprint', cleanup, { once: true });
      try {
        populatePrintDocument(doc, rows, columns, options.title());
        await new Promise<void>((resolve, reject) => {
          const link = doc.createElement('link');
          link.rel = 'stylesheet';
          link.href = printStyles;
          const timeout = globalThis.setTimeout(
            () => finish(new Error('print_styles_failed')),
            5000,
          );
          const cancel = () => finish(new Error('print_cancelled'));
          const finish = (error?: Error) => {
            globalThis.clearTimeout(timeout);
            signal.removeEventListener('abort', cancel);
            link.onload = null;
            link.onerror = null;
            if (error) reject(error);
            else resolve();
          };
          link.onload = () => finish();
          link.onerror = () => finish(new Error('print_styles_failed'));
          signal.addEventListener('abort', cancel, { once: true });
          doc.head.append(link);
        });
        signal.throwIfAborted();
        target.focus();
        target.print();
      } catch (cause) {
        cleanup();
        throw cause;
      }
    },
  };
}

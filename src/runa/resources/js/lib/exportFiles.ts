/* eslint-disable no-control-regex -- Reject control characters in filenames and spreadsheet formula prefixes. */
import type {
  ClientExportAdapter,
  ExportColumn,
  ExportDownload,
} from '../runa/ui/exports';

export const exportMimeTypes = {
  csv: 'text/csv;charset=utf-8',
  xlsx: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  pdf: 'application/pdf',
} as const;
export function safeExportFilename(name: string, extension: string): string {
  const clean =
    name
      .replace(/[\\/\u0000-\u001f<>:"|?*]/g, '_')
      .replace(/\.+$/g, '')
      .slice(0, 120) || 'export';
  return `${clean.replace(/\.(csv|xlsx|pdf)$/i, '')}.${extension}`;
}
export function assertExportBounds<Row>(
  rows: readonly Row[],
  columns: readonly ExportColumn<Row>[],
  signal: AbortSignal,
  maxRows = 2000,
) {
  signal.throwIfAborted();
  if (
    rows.length > maxRows ||
    columns.length === 0 ||
    columns.length > 50 ||
    (rows.length + 1) * columns.length > 20000
  )
    throw new Error('export_limit');
  let textLength = columns.reduce(
    (sum, column) => sum + column.label.length,
    0,
  );
  if (
    rows.some((row) =>
      columns.some((column) => {
        const value = column.value(row);
        textLength += String(value ?? '').length;
        return typeof value === 'number'
          ? !Number.isFinite(value)
          : typeof value === 'string' && value.length > 32767;
      }),
    )
  )
    throw new Error('invalid_export_cell');
  if (textLength > 500000) throw new Error('export_limit');
}
export function csvCell(
  value: string | number | boolean | null,
  preserve = false,
): string {
  let text = value === null ? '' : String(value);
  if (
    !preserve &&
    typeof value === 'string' &&
    /^[\s\u0000-\u001f]*[=+@-]/u.test(text)
  )
    text = `'${text}`;
  return `"${text.replaceAll('"', '""')}"`;
}
export function createCsvAdapter<Row>(): ClientExportAdapter<Row> {
  return {
    format: 'csv',
    maxRows: 2000,
    async generate(rows, columns, options, signal) {
      assertExportBounds(rows, columns, signal);
      const separator = options.separator ?? ';';
      const lines = [
        columns.map((c) => csvCell(c.label)),
        ...rows.map((row) =>
          columns.map((c) =>
            csvCell(c.value(row), options.formulaHandling === 'preserve'),
          ),
        ),
      ];
      const blob = new Blob(
        ['\ufeff', lines.map((line) => line.join(separator)).join('\r\n')],
        { type: exportMimeTypes.csv },
      );
      return {
        filename: safeExportFilename(options.filename, 'csv'),
        mimeType: exportMimeTypes.csv,
        source: blob,
      };
    },
  };
}
export function downloadExport(download: ExportDownload): void {
  const isBlob = download.source instanceof Blob;
  const url = isBlob
    ? URL.createObjectURL(download.source as Blob)
    : download.source.href;
  const parsed = new URL(url, window.location.href);
  if (
    !isBlob &&
    (parsed.origin !== window.location.origin ||
      !['http:', 'https:'].includes(parsed.protocol) ||
      parsed.username ||
      parsed.password)
  )
    throw new Error('unsafe_download_url');
  const anchor = document.createElement('a');
  anchor.href = url;
  anchor.download = download.filename;
  anchor.rel = 'noopener';
  anchor.click();
  if (isBlob) globalThis.setTimeout(() => URL.revokeObjectURL(url), 1000);
}

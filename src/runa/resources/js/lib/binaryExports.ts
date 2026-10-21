import type { Cell } from 'write-excel-file/browser';
import { isDateOnly, isInstant } from './temporal';
import type { ClientExportAdapter } from '../runa/ui/exports';
import {
  assertExportBounds,
  exportMimeTypes,
  safeExportFilename,
} from './exportFiles';

export function createXlsxAdapter<Row>(): ClientExportAdapter<Row> {
  return {
    format: 'xlsx',
    maxRows: 2000,
    async generate(rows, columns, options, signal) {
      assertExportBounds(rows, columns, signal);
      // Keep every ZIP entry below fflate's worker threshold: CSP forbids blob workers.
      const values = [
        columns.map((c) => c.label),
        ...rows.map((row) => columns.map((c) => c.value(row))),
      ];
      const escapedBytes = new TextEncoder().encode(
        values
          .flat()
          .map((v) => String(v ?? '').replace(/[&<>"']/g, '______'))
          .join(''),
      ).length;
      if ((rows.length + 1) * columns.length > 1500 || escapedBytes > 20000)
        throw new Error('export_limit');
      const data: Cell[][] = [
        columns.map((c) => ({
          value: c.label,
          type: String,
          fontWeight: 'bold' as const,
        })),
        ...rows.map((_row, rowIndex) =>
          columns.map((c, columnIndex) => {
            const value = values[rowIndex + 1]![columnIndex] ?? null;
            if (
              typeof value === 'string' &&
              (c.format === 'date' || c.format === 'datetime')
            ) {
              if (!(c.format === 'date' ? isDateOnly(value) : isInstant(value)))
                throw new Error('invalid_export_cell');
              return {
                value: new Date(
                  c.format === 'date' ? `${value}T00:00:00Z` : value,
                ),
                type: Date,
                format:
                  c.format === 'date' ? 'yyyy-mm-dd' : 'yyyy-mm-dd hh:mm:ss',
              };
            }
            return {
              value: value ?? '',
              type:
                typeof value === 'number'
                  ? Number
                  : typeof value === 'boolean'
                    ? Boolean
                    : String,
              ...(c.format === 'number' ? { format: '#,##0.00' } : {}),
            };
          }),
        ),
      ];
      const { default: write } = await import('write-excel-file/browser');
      signal.throwIfAborted();
      const blob = await write(
        data,
        {
          sheet: 'Data',
          columns: columns.map(() => ({ width: 24 })),
        },
        {
          features: [
            {
              files: {
                transform: {
                  'xl/worksheets/sheet{id}.xml': {
                    transform(content) {
                      if (new TextEncoder().encode(content).length >= 150000)
                        throw new Error('export_limit');
                      return content;
                    },
                  },
                },
              },
            },
          ],
        },
      ).toBlob();
      signal.throwIfAborted();
      return {
        filename: safeExportFilename(options.filename, 'xlsx'),
        mimeType: exportMimeTypes.xlsx,
        source: blob,
      };
    },
  };
}
export function createPdfAdapter<Row>(): ClientExportAdapter<Row> {
  return {
    format: 'pdf',
    maxRows: 2000,
    async generate(rows, columns, options, signal) {
      assertExportBounds(rows, columns, signal);
      const body = [
        columns.map((c) => ({ text: c.label, bold: true })),
        ...rows.map((row) =>
          columns.map((c) => ({ text: String(c.value(row) ?? '') })),
        ),
      ];
      const [{ default: pdf }, { default: fonts }] = await Promise.all([
        import('pdfmake/build/pdfmake.min.js'),
        import('pdfmake/build/vfs_fonts.js'),
      ]);
      signal.throwIfAborted();
      pdf.addVirtualFileSystem(fonts);
      pdf.setUrlAccessPolicy(() => false);
      const blob = await pdf
        .createPdf({
          pageSize: 'A4',
          pageOrientation: options.orientation ?? 'portrait',
          defaultStyle: { font: 'Roboto', fontSize: 9 },
          content: [
            {
              table: {
                headerRows: 1,
                widths: columns.map(() => '*'),
                body,
              },
              layout: 'lightHorizontalLines',
            },
          ],
        })
        .getBlob();
      signal.throwIfAborted();
      return {
        filename: safeExportFilename(options.filename, 'pdf'),
        mimeType: exportMimeTypes.pdf,
        source: blob,
      };
    },
  };
}

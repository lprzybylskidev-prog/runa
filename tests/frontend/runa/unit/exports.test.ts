import { expect, it, vi } from '@runa/testing/vitest';
import { effectScope } from 'vue';
import { mount, flushPromises } from '@runa/testing/vue';
import {
  Table,
  OperationPanel,
  createCsvAdapter,
  createXlsxAdapter,
  createPdfAdapter,
  createClientExportProvider,
  createApiExportProvider,
  decodeOperationStatus,
  selectionCount,
  toggleSelection,
  tableQueryIdentity,
  useOperation,
  type ExportColumn,
  type TableQuery,
  type RowSelection,
} from '../../../../src/runa/resources/js/runa/ui';
import { csvCell } from '../../../../src/runa/resources/js/lib/exportFiles';
import { populatePrintDocument } from '../../../../src/runa/resources/js/lib/printTable';
const query: TableQuery = { page: 1, perPage: 1, sort: null, filters: {} };
const rows = [
  {
    id: 'a',
    name: 'Zażółć gęślą jaźń',
    amount: 12.5,
    active: true,
    date: '2026-10-19',
  },
  { id: 'b', name: '=SUM(A1)', amount: -2, active: false, date: '2026-10-20' },
];
type Row = (typeof rows)[number];
const columns: ExportColumn<Row>[] = [
  { key: 'name', label: 'Nazwa', value: (r) => r.name },
  { key: 'amount', label: 'Kwota', value: (r) => r.amount, format: 'number' },
  { key: 'active', label: 'Aktywny', value: (r) => r.active },
  { key: 'date', label: 'Data', value: (r) => r.date, format: 'date' },
];
const signal = () => new AbortController().signal;
async function blob(source: Blob | URL) {
  if (!(source instanceof Blob)) throw new Error('Expected a real file');
  return source;
}
async function unzip(file: Blob) {
  const bytes = new Uint8Array(await file.arrayBuffer()),
    view = new DataView(bytes.buffer),
    output: Record<string, string> = {};
  // Read central-directory sizes: local entries may use data descriptors.
  for (let i = 0; i + 46 < bytes.length; i++) {
    if (view.getUint32(i, true) !== 0x02014b50) continue;
    const method = view.getUint16(i + 10, true),
      size = view.getUint32(i + 20, true),
      length = view.getUint16(i + 28, true),
      offset = view.getUint32(i + 42, true);
    const name = new TextDecoder().decode(bytes.slice(i + 46, i + 46 + length));
    const start =
      offset +
      30 +
      view.getUint16(offset + 26, true) +
      view.getUint16(offset + 28, true);
    const compressed = bytes.slice(start, start + size),
      data =
        method === 0
          ? compressed
          : new Uint8Array(
              await new Response(
                new Blob([compressed])
                  .stream()
                  .pipeThrough(new DecompressionStream('deflate-raw')),
              ).arrayBuffer(),
            );
    output[name] = new TextDecoder().decode(data);
  }
  return output;
}
it('keeps compact inverse selections across pages and excludes selected rows', () => {
  let selection: RowSelection<TableQuery> = {
    mode: 'all-matching',
    query,
    matchingCount: 43,
    excludedPublicIds: [],
  };
  selection = toggleSelection(selection, ['a', 'b'], false);
  expect(selectionCount(selection)).toBe(41);
  selection = toggleSelection(selection, ['a'], true);
  expect(selectionCount(selection)).toBe(42);
  expect(tableQueryIdentity(query)).toBe(
    tableQueryIdentity({ ...query, page: 3, perPage: 20 }),
  );
  expect(tableQueryIdentity(query)).not.toBe(
    tableQueryIdentity({ ...query, search: 'x' }),
  );
});
it('invalidates all-matching on a query change and disables unknown totals', async () => {
  const wrapper = mount(Table<Row>, {
    props: {
      mode: 'client',
      caption: 'Projects',
      rows,
      columns: columns.map((c) => ({
        ...c,
        value: (row: Row) => String(c.value(row) ?? ''),
      })),
      rowKey: (r) => r.id,
      query,
      selectable: true,
      selection: {
        mode: 'all-matching',
        query,
        matchingCount: 2,
        excludedPublicIds: [],
      },
    },
  });
  await wrapper.setProps({ query: { ...query, search: 'a' } });
  expect(wrapper.emitted('update:selection')?.at(-1)).toEqual([
    { mode: 'explicit', publicIds: [] },
  ]);
  wrapper.unmount();
});
it('writes CSV with UTF-8 BOM, quoting, safe formulas and real negative numbers', async () => {
  expect(csvCell(' \t=1')).toBe('"\' \t=1"');
  expect(csvCell(-2)).toBe('"-2"');
  expect(csvCell('a;"b\n')).toBe('"a;""b\n"');
  const result = await createCsvAdapter<Row>().generate(
    rows,
    columns,
    { filename: '../report' },
    signal(),
  );
  const file = await blob(result.source);
  expect(
    Array.from(new Uint8Array(await file.arrayBuffer()).slice(0, 3)),
  ).toEqual([239, 187, 191]);
  expect(await file.text()).toContain('"\'=SUM(A1)";"-2";"false"');
  expect(result.filename).not.toContain('/');
  expect(result.mimeType).toBe('text/csv;charset=utf-8');
});
it('writes genuine XLSX headers, string formulas, numbers, booleans and dates', async () => {
  const result = await createXlsxAdapter<Row>().generate(
    rows,
    columns,
    { filename: 'test' },
    signal(),
  );
  const file = await blob(result.source),
    files = await unzip(file);
  expect(
    Array.from(new Uint8Array(await file.arrayBuffer()).slice(0, 2)),
  ).toEqual([80, 75]);
  expect(files['xl/sharedStrings.xml']).toContain('Zażółć gęślą jaźń');
  expect(files['xl/sharedStrings.xml']).toContain('=SUM(A1)');
  expect(files['xl/sharedStrings.xml']).toContain('Nazwa');
  expect(files['xl/worksheets/sheet1.xml']).toContain('<v>12.5</v>');
  expect(files['xl/worksheets/sheet1.xml']).toContain('t="b"');
  expect(files['xl/worksheets/sheet1.xml']).not.toContain('<f>');
  expect(files['xl/styles.xml']).toContain('yyyy-mm-dd');
  await expect(
    createXlsxAdapter<Row>().generate(
      Array.from({ length: 500 }, () => rows[0]!),
      columns,
      { filename: 'large' },
      signal(),
    ),
  ).rejects.toThrow('export_limit');
});
it('writes paginated PDF with embedded Unicode fonts and both orientations', async () => {
  for (const orientation of ['portrait', 'landscape'] as const) {
    const result = await createPdfAdapter<Row>().generate(
      Array.from({ length: 120 }, () => rows[0]!),
      columns,
      { filename: 'test', orientation },
      signal(),
    );
    const file = await blob(result.source),
      text = new TextDecoder('latin1').decode(await file.arrayBuffer());
    expect(text.startsWith('%PDF-')).toBe(true);
    expect((text.match(/\/Type \/Page\b/g) ?? []).length).toBeGreaterThan(1);
    expect(text).toContain(
      orientation === 'portrait'
        ? '/MediaBox [0 0 595.28 841.89]'
        : '/MediaBox [0 0 841.89 595.28]',
    );
    expect(text).toContain('/ToUnicode');
    expect(text).toContain('/FontFile2');
    const bytes = new Uint8Array(await file.arrayBuffer());
    let unicode = '';
    for (const match of text.matchAll(/stream\r?\n/g)) {
      const start = match.index + match[0].length,
        end = text.indexOf('\nendstream', start);
      if (end < start) continue;
      try {
        const decoded = await new Response(
          new Blob([bytes.slice(start, end)])
            .stream()
            .pipeThrough(new DecompressionStream('deflate')),
        ).text();
        if (decoded.includes('begincmap')) unicode += decoded;
      } catch {
        /* Some PDF streams are not deflated. */
      }
    }
    expect(unicode.toLowerCase()).toContain('<017c>');
    expect(unicode.toLowerCase()).toContain('<0142>');
    expect(unicode.toLowerCase()).toContain('<0105>');
  }
}, 20000);
it('exports filtered/selected client rows before pagination and rejects missing IDs', async () => {
  const provider = createClientExportProvider({
    rows: () => rows,
    columns: () => columns,
    tableColumns: () =>
      columns.map((c) => ({
        ...c,
        value: (row: Row) => String(c.value(row) ?? ''),
      })),
    rowKey: (row) => row.id,
  });
  const result = await provider.start(
    {
      format: 'csv',
      scope: { kind: 'filtered', query },
      columnKeys: ['name'],
      filename: 'test',
    },
    signal(),
  );
  if (result.kind !== 'download') throw new Error('Expected download');
  expect(await (await blob(result.download.source)).text()).toContain('SUM');
  await expect(
    provider.start(
      {
        format: 'csv',
        scope: {
          kind: 'selected',
          selection: { mode: 'explicit', publicIds: ['missing'] },
        },
        columnKeys: ['name'],
        filename: 'test',
      },
      signal(),
    ),
  ).rejects.toThrow('selection_stale');
});
it('sends inverse selection metadata only and polls an authorized operation', async () => {
  const fetch = vi
    .spyOn(window, 'fetch')
    .mockResolvedValueOnce(
      new Response(
        JSON.stringify({
          kind: 'operation',
          operation: { public_id: 'job', status_path: '/exports/job' },
        }),
        { headers: { 'Content-Type': 'application/json' } },
      ),
    )
    .mockResolvedValueOnce(
      new Response(
        JSON.stringify({
          state: 'completed',
          download: {
            filename: 'test.pdf',
            mime_type: 'application/pdf',
            url: '/api/exports/job/download',
          },
        }),
        { headers: { 'Content-Type': 'application/json' } },
      ),
    );
  const provider = createApiExportProvider<TableQuery>({
    path: '/exports',
    capabilities: {
      formats: ['pdf'],
      scopes: ['selected'],
      cancellation: false,
    },
  });
  const result = await provider.start(
    {
      format: 'pdf',
      scope: {
        kind: 'selected',
        selection: {
          mode: 'all-matching',
          query,
          excludedPublicIds: ['a'],
          matchingCount: 1000000,
        },
      },
      columnKeys: ['name'],
      filename: 'test',
      operationKey: 'stable',
    },
    signal(),
  );
  if (result.kind !== 'operation') throw new Error('Expected operation');
  await result.operation.poll(signal());
  expect(fetch).toHaveBeenCalledTimes(2);
  expect(fetch.mock.calls.map((call) => call[0])).toEqual([
    '/api/exports',
    '/api/exports/job',
  ]);
  expect(String(fetch.mock.calls[0]?.[1]?.body).length).toBeLessThan(500);
  expect(
    new Headers(fetch.mock.calls[0]?.[1]?.headers).get('Idempotency-Key'),
  ).toBe('stable');
});
it('resumes a failed poll, prevents duplicate starts, and cancels without stale completion', async () => {
  const scope = effectScope(),
    controller = scope.run(() => useOperation(0))!;
  const poll = vi
    .fn()
    .mockRejectedValueOnce(new Error('offline'))
    .mockResolvedValue({ state: 'completed', succeeded: 2 });
  const start = vi.fn(async () => ({
    kind: 'operation' as const,
    operation: { publicId: 'job', poll },
  }));
  await controller.run(start);
  expect(controller.canRetry.value).toBe(true);
  await controller.retry();
  expect(start).toHaveBeenCalledTimes(1);
  expect(controller.status.value?.state).toBe('completed');
  let resolve: ((value: { state: 'completed' }) => void) | undefined;
  const cancel = vi.fn(async () => {});
  const pending = controller.run(async () => ({
    kind: 'operation',
    operation: {
      publicId: 'other',
      poll: () =>
        new Promise((r) => {
          resolve = r;
        }),
      cancel,
    },
  }));
  await flushPromises();
  await controller.run(start);
  await controller.cancel();
  resolve?.({ state: 'completed' });
  await pending;
  expect(cancel).toHaveBeenCalledOnce();
  expect(controller.status.value?.state).toBe('cancelled');
  scope.stop();
});
it('renders authorization failure and partial retry state without trusting server messages', async () => {
  const fetch = vi.spyOn(window, 'fetch').mockResolvedValue(
    new Response('{}', {
      status: 403,
      headers: { 'Content-Type': 'application/json' },
    }),
  );
  const scope = effectScope(),
    operation = scope.run(() => useOperation(0))!;
  const provider = createApiExportProvider<TableQuery>({
    path: '/exports',
    capabilities: { formats: ['csv'], scopes: ['all'], cancellation: false },
  });
  await operation.run(
    (s) =>
      provider.start(
        {
          format: 'csv',
          scope: { kind: 'all' },
          columnKeys: ['name'],
          filename: 'test',
        },
        s,
      ),
    true,
  );
  expect(operation.status.value).toMatchObject({
    state: 'failed',
    error: { code: 'forbidden', retryable: false },
  });
  const panel = mount(OperationPanel, { props: { operation } });
  expect(panel.text()).toContain('Brak uprawnień');
  expect(panel.findAll('button')).toHaveLength(0);
  panel.unmount();
  expect(fetch).toHaveBeenCalledOnce();
  scope.stop();
});
it('prints plain text, repeated headers and all scoped rows without HTML injection', () => {
  const doc = document.implementation.createHTMLDocument();
  populatePrintDocument(
    doc,
    [{ name: '<script>alert(1)</script>' }],
    [{ key: 'name', label: '<b>Name</b>', value: (r) => r.name }],
    '<img src=x>',
  );
  expect(doc.querySelectorAll('script,img,b')).toHaveLength(0);
  expect(doc.querySelector('thead th')?.textContent).toBe('<b>Name</b>');
  expect(doc.querySelector('tbody')?.textContent).toContain('<script>');
});

it('disables all-matching for uncertain counts and keeps explicit page selection bounded', async () => {
  const wrapper = mount(Table<Row>, {
    props: {
      mode: 'server',
      caption: 'Projects',
      columns: columns.map((c) => ({
        ...c,
        value: (row: Row) => String(c.value(row) ?? ''),
      })),
      rowKey: (row) => row.id,
      query,
      selectable: true,
      selection: { mode: 'explicit', publicIds: [] },
      english: true,
      provider: async () => ({
        rows: [rows[0]!],
        total: null,
        matching: null,
        counts: 'unknown',
        hasNext: true,
        capabilities: { sort: [], filter: [], maxPerPage: 100 },
      }),
    },
  });
  await flushPromises();
  await wrapper.setProps({ selection: { mode: 'explicit', publicIds: ['a'] } });
  expect(
    wrapper
      .findAll('button')
      .find((b) => b.text() === 'Select all matching')
      ?.attributes('disabled'),
  ).toBeDefined();
  await wrapper.setProps({ selection: { mode: 'explicit', publicIds: [] } });
  await wrapper.find('thead input[type=checkbox]').setValue(true);
  expect(wrapper.emitted('update:selection')?.at(-1)).toEqual([
    { mode: 'explicit', publicIds: ['a'] },
  ]);
  wrapper.unmount();
});
it('preserves partial counts and resumes when cancellation cannot be confirmed', async () => {
  const scope = effectScope(),
    operation = scope.run(() => useOperation(0))!;
  const retry = vi.fn(async () => ({
    kind: 'status' as const,
    status: { state: 'completed' as const, succeeded: 3 },
  }));
  await operation.run(async () => ({
    kind: 'operation',
    operation: {
      publicId: 'partial',
      poll: async () => ({
        state: 'partial',
        succeeded: 2,
        failed: 1,
        error: { code: 'partial', message: '', retryable: true },
      }),
      retry,
    },
  }));
  expect(operation.status.value).toMatchObject({
    state: 'partial',
    succeeded: 2,
    failed: 1,
  });
  await operation.retry();
  expect(retry).toHaveBeenCalledOnce();
  let release: ((value: { state: 'completed' }) => void) | undefined;
  const poll = vi.fn(
    () =>
      new Promise<{ state: 'completed' }>((r) => {
        release = r;
      }),
  );
  const pending = operation.run(async () => ({
    kind: 'operation',
    operation: {
      publicId: 'cancel',
      poll,
      cancel: async () => {
        throw new Error('offline');
      },
    },
  }));
  await flushPromises();
  await operation.cancel();
  expect(operation.status.value).toMatchObject({
    state: 'failed',
    error: { code: 'cancel_failed' },
  });
  expect(operation.canRetry.value).toBe(true);
  release?.({ state: 'completed' });
  await pending;
  expect(operation.status.value?.state).toBe('failed');
  scope.stop();
});
it('rejects cross-origin downloads and malformed statuses from an API provider', async () => {
  const fetch = vi.spyOn(window, 'fetch').mockResolvedValue(
    new Response(
      JSON.stringify({
        kind: 'download',
        download: {
          filename: 'test.pdf',
          mime_type: 'application/pdf',
          url: 'https://example.org/file',
        },
      }),
      { headers: { 'Content-Type': 'application/json' } },
    ),
  );
  const provider = createApiExportProvider<TableQuery>({
    path: '/exports',
    capabilities: { formats: ['pdf'], scopes: ['all'], cancellation: false },
  });
  await expect(
    provider.start(
      {
        format: 'pdf',
        scope: { kind: 'all' },
        columnKeys: ['name'],
        filename: 'test',
      },
      signal(),
    ),
  ).rejects.toThrow('unsafe_download_url');
  expect(fetch).toHaveBeenCalledOnce();
  expect(() =>
    decodeOperationStatus({ state: 'running', progress: 101 }),
  ).toThrow('invalid_operation_response');
  expect(() =>
    decodeOperationStatus({
      state: 'partial',
      succeeded: -1,
      failed: 0,
      error: { code: 'x', retryable: true },
    }),
  ).toThrow('invalid_operation_response');
});

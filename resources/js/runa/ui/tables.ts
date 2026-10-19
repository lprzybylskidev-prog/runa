export interface TableQuery {
  page: number;
  perPage: number;
  sort: { key: string; direction: 'asc' | 'desc' } | null;
  filters: Record<string, string>;
}
export interface TableColumn<Row> {
  key: string;
  label: string;
  value: (row: Row) => string | number | null;
  compare?: (a: Row, b: Row) => number;
  filter?: (row: Row, value: string) => boolean;
  sortable?: boolean;
}
export interface TablePage<Row> {
  rows: readonly Row[];
  total: number | null;
  matching: number | null;
  counts: 'exact' | 'stale' | 'unknown';
  hasNext: boolean;
  capabilities: {
    sort: readonly string[];
    filter: readonly string[];
    maxPerPage: number;
  };
}
export type TableProvider<Row> = (
  query: TableQuery,
  signal: globalThis.AbortSignal,
) => Promise<TablePage<Row>>;

export interface TableQuery {
  search?: string;
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
  searchable?: boolean;
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

export interface TableColumnState {
  key: string;
  visible: boolean;
  /** CSS pixels, bounded to 80–800. Omit to retain automatic layout. */
  width?: number;
}
export interface TableViewSnapshot {
  columns: TableColumnState[];
  query: TableQuery;
}
export interface SavedTableView {
  id: string;
  name: string;
  snapshot: TableViewSnapshot;
}
export interface TableViewPreferences {
  current: TableViewSnapshot;
  views: SavedTableView[];
  activeId: string | null;
  defaultId: string | null;
}

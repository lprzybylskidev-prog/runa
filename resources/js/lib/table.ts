import { request } from './http';
import type {
  TableColumn,
  TablePage,
  TableProvider,
  TableQuery,
} from '../runa/ui/tables';

export function normalizeTableQuery(
  query: TableQuery,
  sort: readonly string[],
  filter: readonly string[],
  maxPerPage = 100,
): TableQuery {
  return {
    ...(query.search?.trim() ? { search: query.search.trim() } : {}),
    page: Number.isFinite(query.page) ? Math.max(1, Math.floor(query.page)) : 1,
    perPage: Number.isFinite(query.perPage)
      ? Math.max(
          1,
          Math.min(Math.max(1, maxPerPage), Math.floor(query.perPage)),
        )
      : 20,
    sort:
      query.sort &&
      sort.includes(query.sort.key) &&
      ['asc', 'desc'].includes(query.sort.direction)
        ? { ...query.sort }
        : null,
    filters: Object.fromEntries(
      Object.entries(query.filters).filter(
        ([key, value]) => filter.includes(key) && value !== '',
      ),
    ),
  };
}

/** Only for bounded, already loaded browser datasets. Never fetch all server rows. */
export function clientTablePage<Row>(
  rows: readonly Row[],
  columns: readonly TableColumn<Row>[],
  query: TableQuery,
): TablePage<Row> {
  const capabilities = {
    sort: columns.filter((c) => c.sortable).map((c) => c.key),
    filter: columns.filter((c) => c.filter).map((c) => c.key),
    maxPerPage: 100,
  };
  const state = normalizeTableQuery(
    query,
    capabilities.sort,
    capabilities.filter,
    capabilities.maxPerPage,
  );
  const terms =
    state.search?.toLocaleLowerCase().split(/\s+/).filter(Boolean) ?? [];
  const matching = rows.filter(
    (row) =>
      terms.every((term) =>
        columns.some(
          (c) =>
            c.searchable !== false &&
            String(c.value(row) ?? '')
              .toLocaleLowerCase()
              .includes(term),
        ),
      ) &&
      Object.entries(state.filters).every(([key, value]) =>
        columns.find((c) => c.key === key)?.filter?.(row, value),
      ),
  );
  const column = columns.find((c) => c.key === state.sort?.key);
  if (column && state.sort) {
    const direction = state.sort.direction === 'asc' ? 1 : -1;
    matching.sort(
      (a, b) =>
        direction *
        (column.compare
          ? column.compare(a, b)
          : String(column.value(a) ?? '').localeCompare(
              String(column.value(b) ?? ''),
            )),
    );
  }
  return {
    rows: matching.slice(
      (state.page - 1) * state.perPage,
      state.page * state.perPage,
    ),
    total: rows.length,
    matching: matching.length,
    counts: 'exact',
    hasNext: state.page * state.perPage < matching.length,
    capabilities,
  };
}

/** Decoder validates data + meta; backend must independently enforce these allowlists and limits. */
export function createApiTableProvider<Row>(options: {
  path: string;
  sort: readonly string[];
  filter: readonly string[];
  maxPerPage: number;
  searchable?: boolean;
  decode: (payload: unknown) => TablePage<Row>;
}): TableProvider<Row> {
  return async (query, signal) => {
    const state = normalizeTableQuery(
      query,
      options.sort,
      options.filter,
      options.maxPerPage,
    );
    const params: Record<string, string | number> = {
      page: state.page,
      per_page: state.perPage,
    };
    if (state.search && options.searchable !== false)
      params['filter[search]'] = state.search;
    if (state.sort)
      params.sort = `${state.sort.direction === 'desc' ? '-' : ''}${state.sort.key}`;
    for (const [key, value] of Object.entries(state.filters))
      params[`filter[${key}]`] = value;
    return options.decode(await request(options.path, { signal }, params));
  };
}

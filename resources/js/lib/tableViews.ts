import type {
  TableColumn,
  TableColumnState,
  TableQuery,
  TableViewPreferences,
  TableViewSnapshot,
} from '../runa/ui/tables';
import { normalizeTableQuery } from './table';

export function normalizeTableColumns(
  value: unknown,
  keys: readonly string[],
): TableColumnState[] {
  const seen = new Set<string>();
  const result: TableColumnState[] = [];
  if (Array.isArray(value))
    for (const item of value) {
      if (
        !item ||
        typeof item !== 'object' ||
        typeof item.key !== 'string' ||
        !keys.includes(item.key) ||
        seen.has(item.key)
      )
        continue;
      seen.add(item.key);
      const column: TableColumnState = {
        key: item.key,
        visible: item.visible !== false,
      };
      if (typeof item.width === 'number' && Number.isFinite(item.width))
        column.width = Math.round(Math.max(80, Math.min(800, item.width)));
      result.push(column);
    }
  for (const key of keys) {
    if (!seen.has(key)) {
      result.push({ key, visible: true });
      seen.add(key);
    }
  }
  if (result.length && result.every((column) => !column.visible))
    result[0]!.visible = true;
  return result;
}
function object(value: unknown): value is Record<string, unknown> {
  return !!value && typeof value === 'object' && !Array.isArray(value);
}
export function normalizeTableSnapshot<Row>(
  value: unknown,
  columns: readonly TableColumn<Row>[],
  fallback: TableQuery,
): TableViewSnapshot {
  const raw = object(value) ? value : {};
  const query = object(raw.query) ? raw.query : {};
  const sort =
    object(query.sort) &&
    typeof query.sort.key === 'string' &&
    (query.sort.direction === 'asc' || query.sort.direction === 'desc')
      ? { key: query.sort.key, direction: query.sort.direction }
      : 'sort' in query
        ? null
        : fallback.sort;
  const filters = object(query.filters)
    ? Object.fromEntries(
        Object.entries(query.filters).filter(
          (entry): entry is [string, string] => typeof entry[1] === 'string',
        ),
      )
    : fallback.filters;
  return {
    columns: normalizeTableColumns(
      raw.columns,
      columns.map((c) => c.key),
    ),
    query: normalizeTableQuery(
      {
        page: 1,
        perPage:
          typeof query.perPage === 'number' ? query.perPage : fallback.perPage,
        sort: sort as TableQuery['sort'],
        filters,
      },
      columns.filter((c) => c.sortable).map((c) => c.key),
      columns.filter((c) => c.filter).map((c) => c.key),
    ),
  };
}
export function normalizeTableViews<Row>(
  value: unknown,
  columns: readonly TableColumn<Row>[],
  fallback: TableQuery,
): TableViewPreferences {
  const raw = object(value) ? value : {};
  const ids = new Set<string>(),
    names = new Set<string>();
  const views: TableViewPreferences['views'] = [];
  if (Array.isArray(raw.views))
    for (const item of raw.views.slice(0, 50)) {
      if (
        !object(item) ||
        typeof item.id !== 'string' ||
        !item.id ||
        typeof item.name !== 'string'
      )
        continue;
      const name = item.name.trim().slice(0, 80);
      if (!name || ids.has(item.id) || names.has(name.toLocaleLowerCase()))
        continue;
      ids.add(item.id);
      names.add(name.toLocaleLowerCase());
      views.push({
        id: item.id,
        name,
        snapshot: normalizeTableSnapshot(item.snapshot, columns, fallback),
      });
    }
  return {
    current: normalizeTableSnapshot(raw.current, columns, fallback),
    views,
    activeId:
      typeof raw.activeId === 'string' && ids.has(raw.activeId)
        ? raw.activeId
        : null,
    defaultId:
      typeof raw.defaultId === 'string' && ids.has(raw.defaultId)
        ? raw.defaultId
        : null,
  };
}

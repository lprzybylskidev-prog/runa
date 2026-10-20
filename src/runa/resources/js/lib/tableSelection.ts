import type { RowSelection } from '../runa/ui/exports';
import type { TableQuery } from '../runa/ui/tables';

/** Pagination does not change membership. Sort changes deliberately invalidate all-matching state. */
export function tableQueryIdentity(query: TableQuery): string {
  return JSON.stringify({
    search: query.search?.trim() ?? '',
    sort: query.sort,
    filters: Object.entries(query.filters)
      .filter(([, value]) => value !== '')
      .sort(([a], [b]) => a.localeCompare(b)),
  });
}
export function selectionContains<Query>(
  selection: RowSelection<Query>,
  id: string,
): boolean {
  return selection.mode === 'explicit'
    ? selection.publicIds.includes(id)
    : !selection.excludedPublicIds.includes(id);
}
export function selectionCount<Query>(selection: RowSelection<Query>): number {
  return selection.mode === 'explicit'
    ? new Set(selection.publicIds).size
    : Math.max(
        0,
        selection.matchingCount - new Set(selection.excludedPublicIds).size,
      );
}
export function toggleSelection<Query>(
  selection: RowSelection<Query>,
  ids: readonly string[],
  checked: boolean,
): RowSelection<Query> {
  const values = new Set(
    selection.mode === 'explicit'
      ? selection.publicIds
      : selection.excludedPublicIds,
  );
  for (const id of ids) {
    if (selection.mode === 'explicit' ? checked : !checked) values.add(id);
    else values.delete(id);
  }
  return selection.mode === 'explicit'
    ? { mode: 'explicit', publicIds: [...values] }
    : { ...selection, excludedPublicIds: [...values] };
}

import { computed, ref, watch } from 'vue';
import type { TablePage, TableQuery } from '@/runa/ui';
import { normalizeTableQuery } from '@/runa/ui';
import type {
  AdminError,
  AdminRecord,
  AdminTableController,
} from './contracts';
import type { AdminPageController } from './contracts';
import { useAdminResource } from './useAdminResource';
import { pageOwners } from './pageContext';
import { sessionStamp } from './useAdmin';

function initialQuery(): TableQuery {
  return { page: 1, perPage: 25, sort: null, filters: {} };
}

export function useAdminTable(
  page: AdminPageController,
  sectionKey: string,
): AdminTableController {
  const resource = useAdminResource(page, sectionKey);
  const query = ref<TableQuery>(initialQuery());
  const validationError = ref<AdminError>();
  const section = computed(() =>
    page.page.value?.sections.find((s) => s.key === sectionKey),
  );
  const columns = computed(() =>
    (section.value?.fields ?? []).map((field) => ({
      key: field.key,
      label: field.label,
      sortable: field.sortable && !field.sensitive,
      searchable: false,
      // The server owns matching; this function only declares a filter key to Table.
      ...(field.filterable && !field.sensitive ? { filter: () => true } : {}),
      value: (record: AdminRecord): string | number | null => {
        const value = record.values[field.key];
        if (value === null || value === undefined) return null;
        if (field.sensitive)
          return typeof value === 'object' &&
            !Array.isArray(value) &&
            value.set === true
            ? 'Set'
            : 'Not set';
        if (typeof value === 'boolean') return value ? 'Yes' : 'No';
        if (typeof value === 'string' || typeof value === 'number')
          return value;
        return Array.isArray(value) ? value.join(', ') : null;
      },
    })),
  );
  let cursors = new Map<number, string | undefined>([[1, undefined]]);
  let signature = '';
  let requestedPage = 1;
  let normalizing = false;
  function normalized(): TableQuery {
    const next = normalizeTableQuery(
      query.value,
      columns.value.filter((c) => c.sortable).map((c) => c.key),
      columns.value.filter((c) => c.filter).map((c) => c.key),
      100,
    );
    delete next.search;
    return next;
  }
  async function refresh() {
    validationError.value = undefined;
    const next = normalized();
    const key = JSON.stringify([next.perPage, next.sort, next.filters]);
    if (key !== signature) {
      signature = key;
      cursors = new Map([[1, undefined]]);
      next.page = 1;
    }
    // Never manufacture a cursor or scan intervening pages for a numbered jump.
    if (!cursors.has(next.page)) next.page = 1;
    if (JSON.stringify(next) !== JSON.stringify(query.value)) {
      normalizing = true;
      query.value = next;
      normalizing = false;
    }
    requestedPage = next.page;
    const filters: Record<string, string | number | boolean | null> = {};
    for (const [name, raw] of Object.entries(next.filters)) {
      const field = section.value?.fields.find((f) => f.key === name);
      // Typed filters must keep their owning wire type, rather than PHP GET strings.
      let value: string | number | boolean = raw;
      if (field?.type === 'integer') {
        if (!/^-?\d+$/.test(raw) || !Number.isSafeInteger(Number(raw))) {
          validationError.value = {
            reason: 'admin_input_invalid',
            message: 'Enter a valid integer filter.',
          };
          return;
        }
        value = Number(raw);
      } else if (field?.type === 'boolean') {
        if (raw !== 'true' && raw !== 'false') {
          validationError.value = {
            reason: 'admin_input_invalid',
            message: 'Choose a valid boolean filter.',
          };
          return;
        }
        value = raw === 'true';
      }
      filters[name] = value;
    }
    const cursor = cursors.get(next.page);
    await resource.read({
      kind: 'page',
      limit: next.perPage,
      ...(cursor !== undefined ? { cursor } : {}),
      filters,
      sort: next.sort ? [next.sort] : [],
    });
  }
  watch(
    resource.result,
    (result) => {
      if (!result || !('items' in result) || 'field' in result) return;
      // A refresh can change the next cursor; later visited cursors are then obsolete.
      const previous = cursors.get(requestedPage + 1);
      if (previous !== (result.next ?? undefined)) {
        for (const key of cursors.keys())
          if (key > requestedPage) cursors.delete(key);
      }
      if (result.next !== null) cursors.set(requestedPage + 1, result.next);
      else
        for (const key of cursors.keys())
          if (key > requestedPage) cursors.delete(key);
    },
    { flush: 'sync' },
  );
  const admin = pageOwners.get(page);
  let owner: string | undefined;
  watch(
    [
      () => page.page.value,
      () => page.state.value,
      () => admin && sessionStamp(admin.session),
    ],
    () => {
      const data = page.page.value;
      const identity = JSON.stringify([
        data?.owner,
        data?.key,
        admin && sessionStamp(admin.session),
      ]);
      const changedOwner = identity !== owner;
      owner = identity;
      validationError.value = undefined;
      signature = '';
      cursors = new Map([[1, undefined]]);
      query.value = changedOwner ? initialQuery() : { ...query.value, page: 1 };
    },
    { immediate: true, flush: 'sync' },
  );
  watch(
    query,
    () => {
      if (
        !normalizing &&
        page.state.value === 'ready' &&
        section.value &&
        !section.value.diagnostic
      )
        void refresh();
    },
    { deep: true, immediate: true, flush: 'sync' },
  );

  const provider = computed(() => {
    const result = resource.result.value,
      state = resource.state.value;
    const invalid = validationError.value;
    const fields = columns.value;
    return async (
      _query: TableQuery,
      signal: AbortSignal,
    ): Promise<TablePage<AdminRecord>> => {
      if (
        invalid ||
        signal.aborted ||
        (state !== 'ready' && state !== 'loading')
      )
        throw new Error('Admin collection unavailable.');
      const collection =
        result && 'items' in result && !('field' in result)
          ? result
          : undefined;
      const rows = collection?.items ?? [];
      const ids = rows.map((row) => row.public_id);
      if (ids.some((id) => id === null) || new Set(ids).size !== ids.length)
        throw new TypeError('Invalid Admin collection identity.');
      return {
        rows,
        total: collection?.total ?? null,
        matching: collection?.total ?? null,
        // Exact totals do not imply that arbitrary numbered cursor jumps are possible.
        counts: 'unknown',
        hasNext: collection?.next != null,
        capabilities: {
          sort: fields.filter((c) => c.sortable).map((c) => c.key),
          filter: fields.filter((c) => c.filter).map((c) => c.key),
          maxPerPage: 100,
        },
      };
    };
  });
  return {
    section,
    resource,
    query,
    columns,
    provider,
    refresh,
    error: computed(() => validationError.value ?? resource.error.value),
  };
}

import { useAdminLocale } from './localization';
import { computed, inject, onScopeDispose, ref, toValue, watch } from 'vue';
import type { MaybeRefOrGetter } from 'vue';
import type { TablePage, TableQuery, PreferenceStore } from '@/runa/ui';
import {
  createPreferenceStore,
  normalizeTableQuery,
  useTableViews,
  tableQueryIdentity,
} from '@/runa/ui';
import type { OperationResult, ExportOperation, RowSelection } from '@/runa/ui';
import type {
  AdminText,
  AdminError,
  AdminRecord,
  AdminTableController,
  AdminCollectionAdapters,
} from './contracts';
import type { AdminPageController } from './contracts';
import { useAdminResource } from './useAdminResource';
import { pageOwners } from './pageContext';
import { sessionStamp, adminPresentationIdentity } from './useAdmin';
import { pageRuntime } from './runtime';
import { adminCollectionsKey } from './collections';

function initialQuery(): TableQuery {
  return { page: 1, perPage: 25, sort: null, filters: {} };
}

export function useAdminTable(
  page: AdminPageController,
  sectionKey: string,
  options: {
    adapters?: MaybeRefOrGetter<AdminCollectionAdapters>;
    select?: (publicId: string | null) => Promise<boolean>;
  } = {},
): AdminTableController {
  const { t: tr } = useAdminLocale();
  const resource = useAdminResource(page, sectionKey);
  const exportResource = useAdminResource(page, sectionKey);
  let currentRead: import('./contracts').AdminRecordQuery | undefined;
  const query = ref<TableQuery>(initialQuery());
  const validationError = ref<AdminError>();
  const runtime = pageRuntime(page);
  const selection = ref<RowSelection<TableQuery>>({
    mode: 'explicit',
    publicIds: [],
  });
  const selected = ref<string | null | undefined>();
  const confirmation = ref<AdminText>();
  let confirmResult: ((accepted: boolean) => void) | undefined;
  const registry = inject(
    adminCollectionsKey,
    computed<Readonly<Record<string, AdminCollectionAdapters>>>(() => ({})),
  );
  const sourceAdapters = computed<AdminCollectionAdapters>(
    () =>
      toValue(options.adapters) ??
      registry.value[
        `${page.page.value?.owner}.${page.page.value?.key}.${sectionKey}`
      ] ??
      {},
  );
  const memory = new Map<string, string>(),
    subscribers = new Set<() => void>();
  const memoryStore = {
    ...createPreferenceStore({
      read: (key) => memory.get(key) ?? null,
      write: (key, value) => {
        memory.set(key, value);
      },
      remove: (key) => {
        memory.delete(key);
      },
    }),
    subscribe: (listener: () => void) => {
      subscribers.add(listener);
      return () => {
        subscribers.delete(listener);
      };
    },
  };
  const store: PreferenceStore = {
    read: (scope, key, definition) => {
      const adapter = sourceAdapters.value.preferences;
      return (adapter?.store ?? memoryStore).read(
        adapter?.scope ?? scope,
        key,
        definition,
      );
    },
    write: (scope, key, definition, value) => {
      const adapter = sourceAdapters.value.preferences;
      (adapter?.store ?? memoryStore).write(
        adapter?.scope ?? scope,
        key,
        definition,
        value,
      );
    },
    reset: (scope, key) => {
      const adapter = sourceAdapters.value.preferences;
      (adapter?.store ?? memoryStore).reset(adapter?.scope ?? scope, key);
    },
    subscribe: (listener) => {
      subscribers.add(listener);
      let unsubscribe: (() => void) | undefined;
      const stop = watch(
        () => sourceAdapters.value.preferences,
        (adapter) => {
          unsubscribe?.();
          unsubscribe = adapter?.store.subscribe?.(listener);
          listener();
        },
      );
      return () => {
        subscribers.delete(listener);
        unsubscribe?.();
        stop();
      };
    },
  };
  const section = computed(() =>
    page.page.value?.sections.find((s) => s.key === sectionKey),
  );
  const columns = computed(() =>
    (section.value?.fields ?? []).map((field) => ({
      key: field.key,
      label: tr(field.label),
      sortable: field.sortable && !field.sensitive,
      searchable: false,
      ...(field.type === 'datetime' ? { filterType: 'datetime' as const } : {}),
      // The server owns matching; this function only declares a filter key to Table.
      ...(field.filterable && !field.sensitive ? { filter: () => true } : {}),
      ...(field.choices.length
        ? {
            filterOptions: field.choices.map((value) => ({
              value,
              label: tr(value),
            })),
          }
        : field.type === 'boolean'
          ? {
              filterOptions: [
                { value: 'true', label: tr('Yes') },
                { value: 'false', label: tr('No') },
              ],
            }
          : {}),
      value: (record: AdminRecord): string | number | null => {
        const value = record.values[field.key];
        if (value === null || value === undefined) return null;
        if (field.sensitive)
          return typeof value === 'object' &&
            !Array.isArray(value) &&
            value.set === true
            ? tr('Set')
            : tr('Not set');
        if (typeof value === 'boolean') return value ? tr('Yes') : tr('No');
        if (typeof value === 'string' || typeof value === 'number')
          return value;
        return Array.isArray(value) ? value.join(', ') : null;
      },
    })),
  );
  const views = useTableViews({
    columns: () => columns.value,
    query,
    scope: {
      applicationId: 'runa',
      moduleKey: 'runa.admin',
      viewKey: sectionKey,
    },
    store,
  });
  function confirm(accepted: boolean) {
    const resolve = confirmResult;
    confirmation.value = undefined;
    confirmResult = undefined;
    resolve?.(accepted);
  }
  function context() {
    const admin = pageOwners.get(page);
    return JSON.stringify([
      page.page.value?.owner,
      page.page.value?.key,
      page.page.value?.revision,
      admin && sessionStamp(admin.session),
    ]);
  }
  function checkSelection(value: RowSelection<TableQuery>) {
    const ids =
      value.mode === 'explicit' ? value.publicIds : value.excludedPublicIds;
    if (
      ids.length > 10000 ||
      ids.some(
        (id) =>
          !/^[a-f0-9]{8}-[a-f0-9]{4}-7[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i.test(
            id,
          ),
      )
    )
      throw new Error('Invalid selection.');
    if (
      value.mode === 'all-matching' &&
      (!Number.isSafeInteger(value.matchingCount) ||
        value.matchingCount < 0 ||
        tableQueryIdentity(value.query) !== tableQueryIdentity(query.value))
    )
      throw new Error('Selection changed.');
  }
  function wrapOperation(
    result: OperationResult,
    stamp: string,
  ): OperationResult {
    if (result.kind !== 'operation') return result;
    const operation = result.operation;
    const assertCurrent = () => {
      if (context() !== stamp || page.state.value !== 'ready')
        throw new Error('Operation context changed.');
    };
    const wrapped: ExportOperation = {
      publicId: operation.publicId,
      poll: async (signal) => {
        assertCurrent();
        const status = await operation.poll(signal);
        assertCurrent();
        return status;
      },
      ...(operation.cancel
        ? {
            cancel: async (signal: AbortSignal) => {
              assertCurrent();
              await operation.cancel!(signal);
              assertCurrent();
            },
          }
        : {}),
      ...(operation.retry
        ? {
            retry: async (signal: AbortSignal) => {
              assertCurrent();
              const next = await operation.retry!(signal);
              assertCurrent();
              return wrapOperation(next, stamp);
            },
          }
        : {}),
    };
    return { kind: 'operation', operation: wrapped };
  }
  const adapters = computed<AdminCollectionAdapters>(() => {
    const source = sourceAdapters.value,
      stamp = context();
    const visible = new Set(
      views.columns.value
        .filter((column) => column.visible)
        .map((column) => column.key),
    );
    const permitted = new Set(
      (section.value?.fields ?? [])
        .filter((field) => !field.sensitive && visible.has(field.key))
        .map((field) => field.key),
    );
    const assertCurrent = (signal: AbortSignal) => {
      if (
        signal.aborted ||
        context() !== stamp ||
        page.state.value !== 'ready' ||
        section.value?.diagnostic
      )
        throw new Error('Collection context changed.');
    };
    return {
      ...(section.value?.export_allowed &&
      source.exports?.capabilities.execution === 'server'
        ? {
            exports: {
              capabilities: source.exports.capabilities,
              start: async (input, signal) => {
                assertCurrent(signal);
                if (
                  !input.columnKeys.length ||
                  input.columnKeys.some((key) => !permitted.has(key))
                )
                  throw new Error('Export field unavailable.');
                if (input.scope.kind === 'selected')
                  checkSelection(input.scope.selection);
                const result = await source.exports!.start(input, signal);
                assertCurrent(signal);
                return wrapOperation(
                  result,
                  stamp,
                ) as import('@/runa/ui').ExportResult;
              },
            },
          }
        : {}),
      ...(section.value?.export_allowed && source.print
        ? {
            print: {
              print: async (scope, signal) => {
                assertCurrent(signal);
                if (scope.kind === 'selected') checkSelection(scope.selection);
                await source.print!.print(scope, signal);
                assertCurrent(signal);
              },
            },
          }
        : {}),
      ...(source.bulk
        ? {
            bulk: {
              actions: source.bulk.actions.filter((action) =>
                section.value?.commands.some(
                  (command) =>
                    command.key === action.key &&
                    command.bulk &&
                    command.available &&
                    command.mode === 'independent',
                ),
              ),
              cancellation: source.bulk.cancellation,
              start: async (input, signal) => {
                assertCurrent(signal);
                checkSelection(input.selection);
                const command = section.value?.commands.find(
                  (command) =>
                    command.key === input.action &&
                    command.bulk &&
                    command.available,
                );
                if (!command || runtime.busy.value || confirmResult)
                  throw new Error('Bulk action unavailable.');
                const snapshot = JSON.stringify([query.value, selection.value]);
                confirmation.value = command.title;
                const accepted = await new Promise<boolean>((resolve) => {
                  confirmResult = resolve;
                });
                assertCurrent(signal);
                if (
                  !accepted ||
                  snapshot !== JSON.stringify([query.value, selection.value]) ||
                  runtime.busy.value
                )
                  throw new Error(
                    'Bulk action cancelled or selection changed.',
                  );
                runtime.busy.value = true;
                try {
                  const result = await source.bulk!.start(input, signal);
                  assertCurrent(signal);
                  runtime.changed.value++;
                  return wrapOperation(result, stamp);
                } finally {
                  runtime.busy.value = false;
                }
              },
            },
          }
        : {}),
    };
  });
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
    currentRead = {
      kind: 'page',
      limit: next.perPage,
      ...(cursor !== undefined ? { cursor } : {}),
      filters,
      sort: next.sort ? [next.sort] : [],
    };
    await resource.read(currentRead);
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
        admin && adminPresentationIdentity(admin.session),
      ]);
      const changedOwner = identity !== owner;
      owner = identity;
      validationError.value = undefined;
      signature = '';
      cursors = new Map([[1, undefined]]);
      confirm(false);
      selection.value = { mode: 'explicit', publicIds: [] };
      if (changedOwner) {
        selected.value = undefined;
        if (!sourceAdapters.value.preferences) {
          memory.clear();
          for (const subscriber of subscribers) subscriber();
        }
      }
      query.value = changedOwner ? initialQuery() : { ...query.value, page: 1 };
    },
    { immediate: true, flush: 'sync' },
  );
  watch(
    () => tableQueryIdentity(query.value),
    () => {
      if (selection.value.mode === 'all-matching')
        selection.value = { mode: 'explicit', publicIds: [] };
    },
  );
  watch(runtime.changed, () => {
    selection.value = { mode: 'explicit', publicIds: [] };
  });
  onScopeDispose(() => confirm(false));
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
    async exportRows(signal) {
      if (!currentRead || !section.value?.export_allowed || resource.busy.value)
        throw new Error('permission_denied');
      const stamp = context(),
        snapshot = JSON.stringify(query.value);
      await exportResource.read({ ...currentRead, export: true }, { signal });
      if (
        signal.aborted ||
        context() !== stamp ||
        snapshot !== JSON.stringify(query.value)
      )
        throw new Error('selection_stale');
      const result = exportResource.result.value;
      if (
        exportResource.error.value ||
        !result ||
        !('items' in result) ||
        'field' in result
      )
        throw new Error('permission_denied');
      return result.items;
    },
    selection,
    selected,
    views,
    adapters,
    confirmation,
    confirm,
    async select(publicId) {
      if (
        runtime.busy.value ||
        (options.select && !(await options.select(publicId)))
      )
        return false;
      selected.value = publicId;
      return true;
    },
    error: computed(() => validationError.value ?? resource.error.value),
  };
}

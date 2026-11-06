import { computed, ref, watch } from 'vue';
import { formValuesEqual } from '@/runa/ui';
import { useAdminResource } from './useAdminResource';
import type {
  AdminFormController,
  AdminJson,
  AdminPageController,
} from './contracts';

export interface AdminSettingsGroup {
  key: string;
  title: string | { pl: string; en: string };
  fields: string[];
}
export function useAdminSettingsSnapshots(
  page: AdminPageController,
  form: AdminFormController,
  settingsSection = 'policy',
  snapshotsSection = 'snapshots',
) {
  const selected = ref('');
  const previous = ref<Record<string, AdminJson>>();
  const loadedName = ref('');
  const resource = useAdminResource(page, snapshotsSection);
  const section = computed(() =>
    page.page.value?.sections.find((s) => s.key === settingsSection),
  );
  const fields = computed(
    () =>
      section.value?.fields.filter((f) => !f.readonly && !f.sensitive) ?? [],
  );
  const catalog = computed<{ public_id: string; name: string }[]>(() => {
    const raw = form.records.value[snapshotsSection]?.values.snapshots;
    if (typeof raw !== 'string') return [];
    try {
      const rows: unknown = JSON.parse(raw);
      return Array.isArray(rows)
        ? rows.filter(
            (row): row is { public_id: string; name: string } =>
              row !== null &&
              typeof row === 'object' &&
              typeof row.public_id === 'string' &&
              typeof row.name === 'string',
          )
        : [];
    } catch {
      return [];
    }
  });
  const changes = computed(() =>
    fields.value
      .filter((field) => {
        const path = form.fieldPath(settingsSection, field.key);
        return (
          previous.value &&
          Object.hasOwn(previous.value, path) &&
          !formValuesEqual(
            previous.value[path]!,
            form.values.value[path] ?? null,
          )
        );
      })
      .map((field) => field.key),
  );
  function clear() {
    previous.value = undefined;
    loadedName.value = '';
  }
  watch(
    () => [page.state.value, form.records.value[settingsSection]] as const,
    ([state, record]) => {
      if (state !== 'ready' || !record) {
        clear();
        selected.value = '';
        resource.clear();
      }
    },
    { flush: 'sync' },
  );
  async function load() {
    const id = selected.value;
    if (
      !id ||
      previous.value ||
      form.busy.value ||
      form.conflicts.value.length ||
      !form.records.value[settingsSection]
    )
      return;
    const before = JSON.parse(JSON.stringify(form.values.value)) as Record<
      string,
      AdminJson
    >;
    await resource.read({ kind: 'record', public_id: id });
    const result = resource.result.value;
    if (
      selected.value !== id ||
      page.state.value !== 'ready' ||
      !result ||
      !('values' in result) ||
      result.public_id !== id ||
      !formValuesEqual(before, form.values.value)
    )
      return;
    if (result.revision !== form.records.value[settingsSection]?.revision) {
      await form.refresh();
      return;
    }
    const raw = result.values.values;
    if (typeof raw !== 'string') return;
    const values: unknown = JSON.parse(raw);
    if (!values || typeof values !== 'object' || Array.isArray(values)) return;
    const patch = values as Record<string, AdminJson>;
    if (
      Object.keys(patch).length !== fields.value.length ||
      fields.value.some((f) => !Object.hasOwn(patch, f.key))
    )
      return;
    previous.value = before;
    loadedName.value =
      catalog.value.find((s) => s.public_id === id)?.name ?? '';
    for (const field of fields.value)
      form.set(form.fieldPath(settingsSection, field.key), patch[field.key]!);
  }
  function cancel() {
    if (!previous.value || form.busy.value) return;
    for (const field of fields.value) {
      const path = form.fieldPath(settingsSection, field.key);
      if (Object.hasOwn(previous.value, path))
        form.set(path, previous.value[path]!);
    }
    clear();
  }
  async function capture(name: string) {
    form.set(form.fieldPath(snapshotsSection, 'name'), name.trim());
    const result = await form.execute(snapshotsSection, 'capture');
    if (result?.status === 'committed') return true;
    return false;
  }
  async function remove() {
    if (!selected.value || previous.value) return;
    form.set(form.fieldPath(snapshotsSection, 'snapshot_id'), selected.value);
    const result = await form.execute(snapshotsSection, 'delete');
    if (result?.status === 'committed') selected.value = '';
    const path = form.fieldPath(snapshotsSection, 'snapshot_id');
    if (form.values.value[path] !== null) form.set(path, null);
  }
  return {
    selected,
    previous,
    loadedName,
    catalog,
    changes,
    resource,
    load,
    cancel,
    capture,
    remove,
    clear,
  };
}

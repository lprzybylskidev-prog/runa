<script setup lang="ts">
import { computed, nextTick, onScopeDispose, reactive, ref, watch } from 'vue';
import { useAdminLocale, useAdminResource } from '@/runa/admin';
import type { AdminSectionPresentationProps } from '@/runa/admin';
import {
  actionIcons,
  Button,
  ContentLoading,
  FormNotifications,
} from '@/runa/ui';
const props = defineProps<
  AdminSectionPresentationProps & {
    field: 'roles' | 'permissions';
    active: boolean;
    command?: 'save' | 'store';
  }
>();
const { locale, t } = useAdminLocale();
const text = (pl: string, en: string) => t({ pl, en });
const resource = useAdminResource(props.page, props.section.key);
const selectedResource = useAdminResource(props.page, props.section.key);
const query = ref('');
const assignedOnly = ref(false);
const cursor = ref<string>();
const previous = ref<(string | undefined)[]>([]);
const labels = reactive(new Map<string, string>());
const loaded = ref(false);
const waiting = ref(false);
const rows = ref<HTMLElement>();
const resolved = reactive(new Set<string>());
const record = computed(
  () => props.controller.records.value[props.section.key],
);
const path = computed(() =>
  props.controller.fieldPath(props.section.key, props.field),
);
const selected = computed(() => {
  const value = props.controller.values.value[path.value];
  return Array.isArray(value)
    ? value.filter((id): id is string => typeof id === 'string')
    : [];
});
const readonly = computed(
  () =>
    props.section.readonly ||
    props.section.fields.find((field) => field.key === props.field)?.readonly ||
    !props.section.commands.some(
      (command) =>
        command.key === (props.command ?? 'save') && command.available,
    ),
);
const result = computed(() =>
  resource.result.value && 'field' in resource.result.value
    ? resource.result.value
    : undefined,
);
const options = computed(() => result.value?.items ?? []);
const next = computed(() => result.value?.next);
const title = computed(() =>
  props.field === 'roles'
    ? text('Role', 'Roles')
    : text('Uprawnienia bezpośrednie', 'Direct permissions'),
);
let timer: ReturnType<typeof setTimeout> | undefined;
let epoch = 0;
let hydrationEpoch = 0;
onScopeDispose(() => {
  epoch++;
  hydrationEpoch++;
  if (timer) clearTimeout(timer);
});
async function read(reset = false) {
  if (timer) {
    clearTimeout(timer);
    timer = undefined;
  }
  if (reset) {
    cursor.value = undefined;
    previous.value = [];
  }
  const id = record.value?.public_id;
  if (!props.active || !record.value || props.page.state.value !== 'ready')
    return;
  const own = ++epoch;
  const focusedRow = rows.value?.contains(document.activeElement);
  const language = locale.value;
  await resource.read({
    kind: 'options',
    ...(id ? { public_id: id } : {}),
    field: props.field,
    limit: 25,
    ...(cursor.value ? { cursor: cursor.value } : {}),
    filters: {
      search: query.value,
      language,
      ...(assignedOnly.value
        ? { selected: JSON.stringify(selected.value) }
        : {}),
    },
  });
  if (
    own !== epoch ||
    record.value?.public_id !== id ||
    locale.value !== language
  )
    return;
  for (const item of options.value) labels.set(item.value, item.label);
  loaded.value = true;
  waiting.value = false;
  await nextTick();
  if (focusedRow && document.activeElement === document.body)
    rows.value?.querySelector<HTMLInputElement>('input')?.focus();
  if (
    !options.value.length &&
    cursor.value &&
    previous.value.length &&
    !resource.error.value
  )
    back();
}
async function hydrate() {
  const id = record.value?.public_id;
  const ids = selected.value.filter(
    (value) => !resolved.has(value) && !labels.has(value),
  );
  const language = locale.value;
  if (!props.active || !record.value || !ids.length) return;
  const own = ++hydrationEpoch;
  await selectedResource.read({
    kind: 'options',
    ...(id ? { public_id: id } : {}),
    field: props.field,
    limit: 100,
    filters: { selected: JSON.stringify(ids), language },
  });
  if (
    own !== hydrationEpoch ||
    record.value?.public_id !== id ||
    locale.value !== language
  )
    return;
  const data = selectedResource.result.value;
  if (data && 'field' in data) {
    for (const item of data.items) labels.set(item.value, item.label);
    for (const id of ids) {
      if (!data.items.some((item) => item.value === id)) labels.delete(id);
      resolved.add(id);
    }
  }
}
watch(
  [() => record.value?.public_id, locale],
  () => {
    epoch++;
    hydrationEpoch++;
    if (timer) clearTimeout(timer);
    waiting.value = false;
    labels.clear();
    resolved.clear();
    resource.clear();
    selectedResource.clear();
    loaded.value = false;
    query.value = '';
    assignedOnly.value = false;
    void read(true);
    void hydrate();
  },
  { immediate: true },
);
watch(
  () => props.active,
  (active) => {
    if (active) {
      void read();
      void hydrate();
    }
  },
);
watch(
  () => record.value?.revision,
  (value, before) => {
    if (!before || value === before) return;
    resolved.clear();
    labels.clear();
    void read();
    void hydrate();
  },
);
function searchNow() {
  if (timer) clearTimeout(timer);
  waiting.value = true;
  void read(true);
}
watch(query, () => {
  waiting.value = true;
  if (timer) clearTimeout(timer);
  timer = setTimeout(() => {
    void read(true);
  }, 200);
});
watch(assignedOnly, () => {
  void read(true);
});
watch(
  () => selected.value.join('\0'),
  () => {
    void hydrate();
    if (assignedOnly.value) void read();
  },
);
function update(id: string, checked: boolean | null) {
  if (
    readonly.value ||
    props.controller.busy.value ||
    resource.busy.value ||
    waiting.value
  )
    return;
  const values = new Set(selected.value);
  if (checked) {
    if (values.size >= 100 && !values.has(id)) return;
    values.add(id);
  } else values.delete(id);
  props.controller.set(path.value, [...values]);
}
function selectPage(checked: boolean) {
  if (
    readonly.value ||
    props.controller.busy.value ||
    resource.busy.value ||
    waiting.value
  )
    return;
  const ids = new Set(selected.value);
  for (const option of visibleOptions.value) {
    if (checked && !option.unavailable && ids.size < 100) ids.add(option.value);
    else if (!checked) ids.delete(option.value);
  }
  props.controller.set(path.value, [...ids]);
}
function forward() {
  if (!next.value) return;
  previous.value = [...previous.value, cursor.value];
  cursor.value = next.value;
  void read();
}
function back() {
  if (!previous.value.length) return;
  const history = [...previous.value];
  cursor.value = history.pop();
  previous.value = history;
  void read();
}
const visibleOptions = computed(() => {
  const rows = options.value.map((option) => ({
    ...option,
    unavailable: false,
  }));
  if (assignedOnly.value && !cursor.value && !query.value.trim())
    for (const id of selected.value)
      if (resolved.has(id) && !labels.has(id))
        rows.push({
          value: id,
          label: text('Niedostępne przypisanie', 'Unavailable assignment'),
          unavailable: true,
        });
  return rows;
});
const selectedVisible = computed(
  () =>
    visibleOptions.value.filter((option) =>
      selected.value.includes(option.value),
    ).length,
);
const allVisible = computed(
  () =>
    !!visibleOptions.value.length &&
    selectedVisible.value === visibleOptions.value.length,
);
const locked = computed(
  () =>
    readonly.value ||
    props.controller.busy.value ||
    resource.busy.value ||
    waiting.value,
);
</script>
<template>
  <section class="min-w-0" :data-assignment-field="field" :aria-label="title">
    <div
      class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-3 py-2 sm:flex-nowrap"
    >
      <div class="relative min-w-0 flex-1 basis-full sm:max-w-80 sm:basis-auto">
        <svg
          aria-hidden="true"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.6"
          class="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-muted"
        >
          <circle cx="10.5" cy="10.5" r="6.5" />
          <path d="m16 16 5 5" />
        </svg>
        <input
          v-model="query"
          type="search"
          data-size="sm"
          :aria-label="
            field === 'roles'
              ? text('Szukaj ról', 'Search roles')
              : text('Szukaj uprawnień', 'Search permissions')
          "
          :placeholder="
            field === 'roles'
              ? text('Szukaj ról…', 'Search roles…')
              : text('Szukaj uprawnień…', 'Search permissions…')
          "
          maxlength="160"
          autocomplete="off"
          class="runa-field-control assignment-search"
          @keydown.enter.prevent="searchNow"
        />
      </div>
      <div
        class="flex shrink-0 gap-0.5 rounded bg-line/20 p-0.5"
        role="group"
        :aria-label="text('Zakres katalogu', 'Catalogue scope')"
      >
        <button
          type="button"
          class="min-h-8 cursor-pointer rounded px-3 text-xs transition-colors hover:bg-primary/10 active:bg-primary/15 disabled:cursor-default motion-reduce:transition-none focus-visible:outline-2 focus-visible:outline-accent"
          :class="
            !assignedOnly
              ? 'bg-surface text-ink shadow-sm'
              : 'text-muted hover:text-ink'
          "
          :aria-pressed="!assignedOnly"
          @click="assignedOnly = false"
        >
          {{ text('Wszystkie', 'All') }}
        </button>
        <button
          type="button"
          class="min-h-8 cursor-pointer rounded px-3 text-xs transition-colors hover:bg-primary/10 active:bg-primary/15 disabled:cursor-default motion-reduce:transition-none focus-visible:outline-2 focus-visible:outline-accent"
          :class="
            assignedOnly
              ? 'bg-surface text-ink shadow-sm'
              : 'text-muted hover:text-ink'
          "
          :aria-pressed="assignedOnly"
          @click="assignedOnly = true"
        >
          {{ text('Przypisane', 'Assigned') }}
        </button>
      </div>
    </div>
    <FormNotifications
      v-if="active"
      :locale="locale"
      :error="
        t(
          resource.error.value?.message ??
            selectedResource.error.value?.message ??
            '',
        )
      "
    >
      <template #actions
        ><Button
          :icon="actionIcons.refresh"
          intent="inspect"
          :busy="resource.busy.value || selectedResource.busy.value"
          size="sm"
          @click="
            read();
            hydrate();
          "
          >{{ t('Retry loading') }}</Button
        ></template
      >
    </FormNotifications>
    <ContentLoading
      :loading="!loaded"
      :refreshing="loaded && (resource.busy.value || waiting)"
      :lines="5"
      :label="t('Loading record details')"
    >
      <div
        ref="rows"
        class="max-h-80 min-w-0 overflow-y-auto overscroll-contain"
        :aria-label="title"
        role="group"
        data-assignment-rows
      >
        <label
          v-for="option in visibleOptions"
          :key="option.value"
          class="flex min-h-11 min-w-0 sm:min-h-10 items-center gap-3 border-b border-line/60 px-3 py-2 text-sm last:border-0 has-[:focus-visible]:outline-2 has-[:focus-visible]:-outline-offset-2 has-[:focus-visible]:outline-accent"
          :class="[
            selected.includes(option.value) ? 'bg-primary/5' : '',
            locked ||
            (!selected.includes(option.value) && selected.length >= 100)
              ? 'cursor-default'
              : 'cursor-pointer hover:bg-primary/5',
          ]"
          :data-assignment-option="option.value"
        >
          <input
            type="checkbox"
            class="runa-choice-input shrink-0 assignment-choice"
            :checked="selected.includes(option.value)"
            :disabled="
              locked ||
              (!selected.includes(option.value) && selected.length >= 100)
            "
            @change="
              update(option.value, ($event.target as HTMLInputElement).checked)
            "
          />
          <span class="min-w-0 flex-1 break-words">{{ option.label }}</span>
          <span v-if="option.unavailable" class="text-xs text-muted">{{
            text('Niedostępne', 'Unavailable')
          }}</span>
        </label>
        <div
          v-if="!visibleOptions.length"
          class="flex min-h-32 items-center justify-center px-4 py-6 text-center text-sm text-muted"
        >
          {{
            query.trim()
              ? text(
                  'Nie znaleziono pasujących pozycji.',
                  'No matching entries.',
                )
              : assignedOnly
                ? text('Brak przypisań.', 'No assignments.')
                : text('Brak dostępnych pozycji.', 'No available entries.')
          }}
        </div>
      </div>
    </ContentLoading>
    <div
      class="flex flex-wrap items-center justify-between gap-2 border-t border-line px-3 py-1.5 text-xs text-muted"
    >
      <div class="flex min-h-9 flex-wrap items-center gap-x-4 gap-y-2">
        <label
          v-if="!readonly && visibleOptions.length"
          class="flex min-h-9 cursor-pointer items-center gap-2 has-[:disabled]:cursor-default"
        >
          <input
            type="checkbox"
            class="runa-choice-input assignment-choice"
            :aria-label="text('Zaznacz widoczne', 'Select visible')"
            :checked="allVisible"
            :indeterminate="selectedVisible > 0 && !allVisible"
            :disabled="locked || (!allVisible && selected.length >= 100)"
            @change="selectPage(($event.target as HTMLInputElement).checked)"
          />
          {{ text('Zaznacz widoczne', 'Select visible') }}
        </label>
        <span aria-live="polite">
          <span class="sr-only">{{ text('Wybrano', 'Selected') }}</span>
          <strong class="font-medium text-ink tabular-nums">{{
            selected.length
          }}</strong>
          / 100</span
        >
      </div>
      <div v-if="next || previous.length" class="flex items-center gap-2">
        <button
          type="button"
          class="flex size-8 cursor-pointer items-center justify-center rounded text-ink transition-colors enabled:hover:bg-primary/5 enabled:active:bg-primary/10 disabled:cursor-default motion-reduce:transition-none focus-visible:outline-2 focus-visible:outline-accent disabled:opacity-40"
          :disabled="!previous.length || resource.busy.value || waiting"
          :aria-label="text('Poprzednia strona', 'Previous page')"
          @click="back"
        >
          <svg
            aria-hidden="true"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.6"
            class="size-4"
          >
            <path d="m14 6-6 6 6 6" />
          </svg>
        </button>
        <span class="tabular-nums"
          >{{ text('Strona', 'Page') }} {{ previous.length + 1 }}</span
        >
        <button
          type="button"
          class="flex size-8 cursor-pointer items-center justify-center rounded text-ink transition-colors enabled:hover:bg-primary/5 enabled:active:bg-primary/10 disabled:cursor-default motion-reduce:transition-none focus-visible:outline-2 focus-visible:outline-accent disabled:opacity-40"
          :disabled="!next || resource.busy.value || waiting"
          :aria-label="text('Następna strona', 'Next page')"
          @click="forward"
        >
          <svg
            aria-hidden="true"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.6"
            class="size-4"
          >
            <path d="m10 6 6 6-6 6" />
          </svg>
        </button>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Shared small-field chrome owns padding; reserve the compact search prefix. */
.assignment-search[data-size='sm'] {
  padding-inline-start: calc(var(--spacing) * 8);
}
/* Native checkbox paint does not inherit the clickable row cursor by default. */
.assignment-choice {
  cursor: inherit;
}
</style>

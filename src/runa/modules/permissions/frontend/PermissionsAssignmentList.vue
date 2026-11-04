<script setup lang="ts">
import { computed, onScopeDispose, reactive, ref, watch } from 'vue';
import { useAdminLocale, useAdminResource } from '@/runa/admin';
import type { AdminSectionPresentationProps } from '@/runa/admin';
import {
  Badge,
  Button,
  Checkbox,
  ContentLoading,
  FormNotifications,
  Switch,
  TextInput,
} from '@/runa/ui';
const props = defineProps<
  AdminSectionPresentationProps & { field: 'roles' | 'permissions' }
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
      (command) => command.key === 'save' && command.available,
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
onScopeDispose(() => {
  epoch++;
  if (timer) clearTimeout(timer);
});
async function read(reset = false) {
  if (reset) {
    cursor.value = undefined;
    previous.value = [];
  }
  const id = record.value?.public_id;
  if (!id || props.page.state.value !== 'ready') return;
  const own = ++epoch;
  const language = locale.value;
  await resource.read({
    kind: 'options',
    public_id: id,
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
}
async function hydrate() {
  const id = record.value?.public_id;
  const ids = selected.value.filter((value) => !labels.has(value));
  const language = locale.value;
  if (!id || !ids.length) return;
  await selectedResource.read({
    kind: 'options',
    public_id: id,
    field: props.field,
    limit: 100,
    filters: { selected: JSON.stringify(ids), language },
  });
  if (record.value?.public_id !== id || locale.value !== language) return;
  const data = selectedResource.result.value;
  if (data && 'field' in data) {
    for (const item of data.items) labels.set(item.value, item.label);
    for (const id of ids) resolved.add(id);
  }
}
watch(
  [() => record.value?.public_id, locale],
  () => {
    epoch++;
    if (timer) clearTimeout(timer);
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
watch(query, () => {
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
    if (assignedOnly.value) void read(true);
  },
);
function update(id: string, checked: boolean | null) {
  if (readonly.value || props.controller.busy.value) return;
  const values = new Set(selected.value);
  if (checked) {
    if (values.size >= 100 && !values.has(id)) return;
    values.add(id);
  } else values.delete(id);
  props.controller.set(path.value, [...values]);
}
function selectPage(checked: boolean) {
  if (readonly.value || props.controller.busy.value) return;
  const ids = new Set(selected.value);
  for (const option of options.value) {
    if (checked && ids.size < 100) ids.add(option.value);
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
</script>
<template>
  <section
    class="min-w-0 space-y-4"
    :data-assignment-field="field"
    :aria-label="title"
  >
    <div class="flex items-center justify-between gap-3">
      <h3 class="text-sm font-semibold">{{ title }}</h3>
      <Badge size="sm">{{ selected.length }}/100</Badge>
    </div>
    <div
      v-if="selected.length"
      class="max-h-40 space-y-2 overflow-y-auto rounded border border-line p-3"
      :aria-label="text('Przypisane', 'Assigned')"
    >
      <div
        v-for="id in selected"
        :key="id"
        class="flex min-w-0 items-center justify-between gap-2 text-sm"
      >
        <ContentLoading
          :loading="
            !labels.has(id) &&
            !resolved.has(id) &&
            !selectedResource.error.value
          "
          :lines="1"
          :label="t('Loading record details')"
          class="min-w-0 flex-1"
          ><span class="break-words">{{
            labels.get(id) ??
            text('Niedostępne przypisanie', 'Unavailable assignment')
          }}</span></ContentLoading
        ><Button
          v-if="!readonly"
          size="sm"
          :disabled="controller.busy.value"
          :aria-label="
            text('Usuń przypisanie: ', 'Remove assignment: ') +
            (labels.get(id) ?? '')
          "
          @click="update(id, false)"
          >{{ text('Usuń', 'Remove') }}</Button
        >
      </div>
    </div>
    <TextInput
      v-model="query"
      :label="
        field === 'roles'
          ? text('Szukaj ról', 'Search roles')
          : text('Szukaj uprawnień', 'Search permissions')
      "
      :maxlength="160"
    />
    <Switch
      v-model="assignedOnly"
      :label="text('Tylko przypisane', 'Assigned only')"
    />
    <FormNotifications
      :locale="locale"
      :error="
        t(
          resource.error.value?.message ??
            selectedResource.error.value?.message ??
            '',
        )
      "
      ><template #actions
        ><Button
          :busy="resource.busy.value || selectedResource.busy.value"
          size="sm"
          @click="
            read();
            hydrate();
          "
          >{{ t('Retry loading') }}</Button
        ></template
      ></FormNotifications
    >
    <ContentLoading
      :loading="!loaded"
      :refreshing="loaded && resource.busy.value"
      shape="detail"
      :label="t('Loading record details')"
    >
      <div
        class="max-h-72 min-w-0 divide-y divide-line overflow-y-auto rounded border border-line"
      >
        <div v-for="option in options" :key="option.value" class="p-3">
          <Checkbox
            :label="option.label"
            :model-value="selected.includes(option.value)"
            :disabled="
              readonly ||
              controller.busy.value ||
              (!selected.includes(option.value) && selected.length >= 100)
            "
            @update:model-value="update(option.value, $event)"
          />
        </div>
        <p v-if="!options.length" class="p-3 text-sm text-muted">
          {{ text('Brak wyników.', 'No results.') }}
        </p>
      </div>
    </ContentLoading>
    <div class="flex flex-wrap items-center gap-2">
      <Button
        v-if="!readonly && options.length"
        size="sm"
        :disabled="
          controller.busy.value || resource.busy.value || selected.length >= 100
        "
        @click="selectPage(true)"
        >{{ text('Zaznacz widoczne', 'Select visible') }}</Button
      ><Button
        v-if="
          !readonly && options.some((option) => selected.includes(option.value))
        "
        size="sm"
        :disabled="controller.busy.value || resource.busy.value"
        @click="selectPage(false)"
        >{{ text('Odznacz widoczne', 'Deselect visible') }}</Button
      ><Button
        size="sm"
        :disabled="!previous.length || resource.busy.value"
        @click="back"
        >{{ text('Poprzednie', 'Previous') }}</Button
      ><Button
        size="sm"
        :disabled="!next || resource.busy.value"
        @click="forward"
        >{{ text('Następne', 'Next') }}</Button
      >
    </div>
  </section>
</template>

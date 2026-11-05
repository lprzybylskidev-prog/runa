<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { AdminField, useAdminLocale, useAdminResource } from '@/runa/admin';
import type {
  AdminFieldLookup,
  AdminSectionPresentationProps,
} from '@/runa/admin';
import { Button, Dialog, FormNotifications } from '@/runa/ui';
const props = defineProps<AdminSectionPresentationProps & { dirty: boolean }>();
const emit = defineEmits<{
  submit: [section: string, command: string, fields?: string[]];
}>();
const { locale, t } = useAdminLocale();
const text = (pl: string, en: string) => t({ pl, en });
const resource = useAdminResource(props.page, props.section.key);
const method = ref<'copy' | 'preset'>();
const sourceKey = computed(() =>
  method.value === 'copy' ? 'source_user' : 'preset',
);
const path = (key: string) =>
  props.controller.fieldPath(props.section.key, key);
const field = computed(() =>
  props.section.fields.find((field) => field.key === sourceKey.value),
);
const mode = computed(() =>
  props.section.fields.find((field) => field.key === 'mode'),
);
const methods = computed(() =>
  (['copy', 'preset'] as const).filter(
    (key) =>
      !props.section.readonly &&
      props.section.commands.some(
        (command) => command.key === key && command.available,
      ) &&
      props.section.fields.some(
        (field) =>
          field.key === (key === 'copy' ? 'source_user' : 'preset') &&
          !field.readonly,
      ),
  ),
);
const blocked = computed(
  () =>
    props.dirty ||
    props.controller.busy.value ||
    props.controller.conflicts.value.length > 0,
);
const lookup: AdminFieldLookup = {
  searchable: true,
  provider: async (request) => {
    const key = sourceKey.value;
    const id = props.controller.records.value[props.section.key]?.public_id;
    const language = locale.value;
    if (!id || request.signal.aborted) throw new Error('Lookup unavailable.');
    await resource.read(
      {
        kind: 'options',
        field: key,
        public_id: id,
        limit: request.limit,
        ...(request.cursor ? { cursor: request.cursor } : {}),
        filters: { search: request.query, language },
      },
      { signal: request.signal },
    );
    const result = resource.result.value;
    if (
      request.signal.aborted ||
      props.controller.records.value[props.section.key]?.public_id !== id ||
      sourceKey.value !== key ||
      locale.value !== language ||
      !result ||
      !('field' in result) ||
      result.field !== key
    )
      throw new Error('Lookup unavailable.');
    return { options: result.items, nextCursor: result.next };
  },
};
function close() {
  method.value = undefined;
  resource.clear();
  props.controller.set(path('source_user'), null);
  props.controller.set(path('preset'), null);
  props.controller.set(path('mode'), 'add');
}
function open(key: 'copy' | 'preset') {
  close();
  method.value = key;
}
function submit() {
  if (
    !method.value ||
    blocked.value ||
    !props.controller.values.value[path(sourceKey.value)]
  )
    return;
  const command = method.value;
  method.value = undefined;
  emit('submit', props.section.key, command, []);
}
watch(
  [
    () => props.controller.records.value[props.section.key]?.public_id,
    () => props.page.state.value,
  ],
  close,
);
</script>
<template>
  <div
    v-if="methods.length"
    class="space-y-4 border-t border-line pt-4"
    data-access-import
  >
    <div class="flex flex-wrap gap-2">
      <Button
        v-for="key in methods"
        :key="key"
        intent="mixed"
        size="sm"
        :disabled="blocked"
        :aria-expanded="method === key"
        @click="open(key)"
        >{{
          key === 'copy'
            ? text('Kopiuj od użytkownika', 'Copy from user')
            : text('Użyj szablonu', 'Use preset')
        }}</Button
      >
    </div>
    <p v-if="dirty" class="text-sm text-muted">
      {{
        text(
          'Zapisz lub cofnij bieżące zmiany, aby skopiować dostęp albo zastosować szablon.',
          'Save or revert your current changes before copying access or applying a preset.',
        )
      }}
    </p>
    <Dialog
      :model-value="!!method && !!field && !!mode"
      :title="
        method === 'copy'
          ? text('Kopiuj od użytkownika', 'Copy from user')
          : text('Użyj szablonu', 'Use preset')
      "
      :locale="locale"
      :busy="controller.busy.value"
      @update:model-value="!$event && close()"
    >
      <div v-if="method && field && mode" class="space-y-4">
        <FormNotifications
          :locale="locale"
          :error="t(resource.error.value?.message ?? '')"
        />
        <div class="grid min-w-0 gap-4">
          <AdminField
            :key="sourceKey + ':' + locale"
            :field="field"
            :readonly="section.readonly || field.readonly"
            :model-value="controller.values.value[path(sourceKey)] ?? null"
            :lookup="lookup"
            :busy="controller.busy.value"
            :errors="controller.errors.value[path(sourceKey)] ?? []"
            @update:model-value="controller.set(path(sourceKey), $event)"
            @availability="controller.setAvailable(path(sourceKey), $event)"
          />
          <AdminField
            :field="mode"
            :readonly="section.readonly || mode.readonly"
            :model-value="controller.values.value[path('mode')] ?? 'add'"
            :busy="controller.busy.value"
            :errors="controller.errors.value[path('mode')] ?? []"
            @update:model-value="controller.set(path('mode'), $event)"
            @availability="controller.setAvailable(path('mode'), $event)"
          />
        </div>
      </div>
      <template #footer>
        <Button
          :intent="
            controller.values.value[path('mode')] === 'replace'
              ? 'discard'
              : 'commit'
          "
          :disabled="blocked || !controller.values.value[path(sourceKey)]"
          @click="submit"
          >{{ text('Zastosuj', 'Apply') }}</Button
        >
        <Button
          intent="neutral"
          :disabled="controller.busy.value"
          @click="close"
          >{{ text('Anuluj', 'Cancel') }}</Button
        >
      </template>
    </Dialog>
  </div>
</template>

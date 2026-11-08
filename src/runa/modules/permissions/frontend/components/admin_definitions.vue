<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import {
  AdminComponentBoundary,
  AdminResourceTable,
  AdminSectionContent,
  useAdminLocale,
  useAdminTable,
} from '@/runa/admin';
import type { AdminPageProps } from '@/runa/admin';
import { actionIcons, Button, Card, Dialog } from '@/runa/ui';
const props = defineProps<AdminPageProps>();
const { locale, t } = useAdminLocale();
const text = (pl: string, en: string) => t({ pl, en });
const open = ref(false);
const table = useAdminTable(props.controller, 'catalogue', {
  select: async (id) => {
    if (!(await props.form.selectSubject(id))) return false;
    open.value = true;
    return true;
  },
});
const sections = computed(
  () =>
    props.controller.page.value?.sections.filter(
      (section) => section.key !== 'catalogue',
    ) ?? [],
);
const editor = computed(() =>
  sections.value.find((section) => section.key === 'editor'),
);
const record = computed(() => props.form.records.value.editor);
const canCreate = computed(
  () =>
    editor.value?.commands.some(
      (command) => command.key === 'store' && command.available,
    ) ?? false,
);
const preset = computed(() => props.controller.page.value?.key === 'presets');
const title = computed(() =>
  record.value?.public_id
    ? t(editor.value?.title ?? 'Details')
    : preset.value
      ? text('Nowy szablon', 'New preset')
      : text('Nowa rola', 'New role'),
);
async function close() {
  if (!(await props.form.beforeLeave())) return;
  props.form.reset();
  open.value = false;
  table.selected.value = undefined;
}
watch(
  () => props.controller.state.value,
  (state) => {
    if (state !== 'ready') open.value = false;
  },
);
watch(
  () => record.value?.public_id,
  (id, previous) => {
    if (previous && id === null) {
      open.value = false;
      table.selected.value = undefined;
    }
  },
);
</script>
<template>
  <div class="space-y-6">
    <h1 class="text-xl font-semibold">
      {{ t(controller.page.value?.title ?? '') }}
    </h1>
    <Card>
      <template #header
        ><h2>{{ t(controller.page.value?.title ?? '') }}</h2></template
      >
      <template #actions>
        <Button
          v-if="canCreate"
          :icon="actionIcons.create"
          intent="commit"
          size="sm"
          :disabled="form.busy.value"
          @click="table.select(null)"
          >{{
            preset
              ? text('Utwórz szablon', 'Create preset')
              : text('Utwórz rolę', 'Create role')
          }}</Button
        >
      </template>
      <AdminResourceTable :controller="table">
        <template #actions="{ row }">
          <Button
            :icon="actionIcons.inspect"
            intent="inspect"
            size="sm"
            :disabled="form.busy.value"
            @click="table.select(row.public_id)"
            >{{ t('Details') }}</Button
          >
        </template>
      </AdminResourceTable>
    </Card>
    <Dialog
      :model-value="open"
      :title="title"
      :locale="locale"
      size="xl"
      :busy="form.busy.value"
      @update:model-value="!$event && close()"
    >
      <div class="space-y-5">
        <AdminComponentBoundary
          v-for="section in sections"
          :key="section.key"
          :identity="controller.page.value!.revision + ':' + section.key"
          :on-failure="(failed) => form.setAvailable(section.key, !failed)"
        >
          <AdminSectionContent
            :page="controller"
            :section="section"
            :controller="form"
            :components="components"
          />
        </AdminComponentBoundary>
      </div>
    </Dialog>
  </div>
</template>

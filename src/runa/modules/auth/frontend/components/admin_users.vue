<script setup lang="ts">
import { useAdminLocale } from '@/runa/admin';
const { locale: adminLocale, t: tr } = useAdminLocale();
import { computed, ref, watch } from 'vue';
import { Button, Card, Dialog } from '@/runa/ui';
import {
  AdminComponentBoundary,
  AdminForm,
  AdminResourceTable,
  AdminSectionContent,
  useAdminTable,
} from '@/runa/admin';
import type { AdminPageProps } from '@/runa/admin';
const props = defineProps<AdminPageProps>();
const open = ref(false);
const table = useAdminTable(props.controller, 'directory', {
  select: async (id) => {
    if (!(await props.form.selectSubject(id))) return false;
    open.value = true;
    return true;
  },
});
const directory = computed(() =>
  props.controller.page.value?.sections.some(
    (section) => section.key === 'directory',
  ),
);
const sections = computed(
  () =>
    props.controller.page.value?.sections.filter(
      (section) => section.key !== 'directory',
    ) ?? [],
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
</script>
<template>
  <div class="space-y-6">
    <h1 class="text-xl font-semibold">
      {{ tr(controller.page.value?.title ?? '') }}
    </h1>
    <Card v-if="directory">
      <template #header
        ><h2>{{ tr('User directory') }}</h2></template
      >
      <Button
        v-if="
          sections.some(
            (section) =>
              section.key === 'creation' &&
              section.commands.some(
                (command) => command.intent === 'create' && command.available,
              ),
          )
        "
        class="mb-4"
        size="sm"
        :disabled="form.busy.value"
        @click="table.select(null)"
        >{{ tr('Create account') }}</Button
      >
      <AdminResourceTable :controller="table">
        <template #actions="{ row }"
          ><Button
            size="sm"
            :disabled="form.busy.value"
            @click="table.select(row.public_id)"
            >{{ tr('Details') }}</Button
          ></template
        >
      </AdminResourceTable>
    </Card>
    <Dialog
      :model-value="open"
      :title="tr('Details')"
      :locale="adminLocale"
      size="xl"
      :busy="form.busy.value"
      @update:model-value="!$event && close()"
    >
      <AdminForm :controller="form">
        <div class="space-y-6">
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
      </AdminForm>
    </Dialog>
  </div>
</template>

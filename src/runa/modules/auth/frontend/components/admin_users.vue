<script setup lang="ts">
import { useAdminLocale } from '@/runa/admin';
const { locale: adminLocale, t: tr } = useAdminLocale();
import { computed, ref } from 'vue';
import { Alert, Button, Card, Select, TextInput } from '@/runa/ui';
import {
  AdminComponentBoundary,
  AdminForm,
  AdminResourceTable,
  AdminSectionContent,
  useAdminTable,
} from '@/runa/admin';
import type { AdminPageProps } from '@/runa/admin';
const props = defineProps<AdminPageProps>();
const search = ref<string | null>(null);
const state = ref('current');
const table = useAdminTable(props.controller, 'directory', {
  select: (id) => props.form.selectSubject(id),
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
function filter() {
  const filters: Record<string, string> = { state: state.value ?? 'current' };
  if (search.value?.trim()) filters.display_name = search.value.trim();
  table.query.value = { ...table.query.value, page: 1, filters };
}
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
      <div class="mb-4 grid gap-4 sm:grid-cols-3">
        <TextInput
          v-model="search"
          :label="tr('Name or authorized login')"
          :maxlength="160"
          @keydown.enter.prevent="filter"
        />
        <Select
          v-model="state"
          :locale="adminLocale"
          :label="tr('Account state')"
          :options="[
            { value: 'current', label: tr('Current accounts') },
            { value: 'deleted', label: tr('Deleted accounts') },
            { value: 'all', label: tr('All accounts') },
          ]"
        />
        <div class="flex items-end">
          <Button @click="filter">{{ tr('Filter accounts') }}</Button>
        </div>
      </div>
      <AdminResourceTable :controller="table">
        <template #actions="{ row }"
          ><Button
            size="sm"
            :disabled="form.busy.value"
            @click="table.select(row.public_id)"
            >{{ tr('Open account') }}</Button
          ></template
        >
      </AdminResourceTable>
    </Card>
    <Alert tone="info">
      {{
        table.selected.value
          ? `Selected account: ${table.selected.value}`
          : tr(
              'Account operations initially show your current account. Select another account from the directory.',
            )
      }}
      {{
        tr(
          'Lifecycle, email, invitation, security and policy actions run independently. Profile and access use the shared Save.',
        )
      }}
    </Alert>
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
  </div>
</template>

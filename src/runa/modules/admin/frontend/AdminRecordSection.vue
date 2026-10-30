<script setup lang="ts">
import { computed, watch } from 'vue';
import { Alert, Button } from '@/runa/ui';
import type { AdminComponentRegistry, AdminPageController } from './contracts';
import { useAdminResource } from './useAdminResource';
import AdminField from './AdminField.vue';
import AdminLoading from './AdminLoading.vue';
const props = defineProps<{
  page: AdminPageController;
  section: string;
  components?: AdminComponentRegistry | undefined;
}>();
const descriptor = computed(() =>
  props.page.page.value?.sections.find(
    (section) => section.key === props.section,
  ),
);
const resource = useAdminResource(props.page, props.section);
const record = computed(() => {
  const result = resource.result.value;
  return result && 'values' in result ? result : undefined;
});
const fields = computed(
  () =>
    descriptor.value?.fields.filter(
      (field) => record.value && Object.hasOwn(record.value.values, field.key),
    ) ?? [],
);
function refresh() {
  const publicId = descriptor.value?.record?.public_id;
  return resource.read({
    kind: 'record',
    ...(publicId ? { public_id: publicId } : {}),
  });
}
watch(
  () => [props.page.state.value, descriptor.value?.record?.public_id] as const,
  () => {
    if (props.page.state.value === 'ready') void refresh();
  },
  { immediate: true },
);
</script>
<template>
  <AdminLoading v-if="resource.busy.value" />
  <Alert
    v-else-if="resource.error.value || resource.state.value !== 'ready'"
    tone="warning"
  >
    {{
      resource.error.value?.message ?? 'This record is currently unavailable.'
    }}
    <Button size="sm" @click="refresh">Retry</Button>
  </Alert>
  <div v-else-if="record" class="grid min-w-0 gap-4 sm:grid-cols-2">
    <AdminField
      v-for="field in fields"
      :key="field.key"
      :field="field"
      :model-value="record.values[field.key] ?? null"
      :errors="[]"
      :readonly="true"
      :busy="false"
      :components="components"
    />
    <p v-if="!Object.keys(record.values).length" class="text-sm text-muted">
      No values are available.
    </p>
  </div>
</template>

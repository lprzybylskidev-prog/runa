<script setup lang="ts">
import { computed } from 'vue';
import { Alert, Card } from '@/runa/ui';
import { AdminSection } from '@/runa/admin';
import type { AdminSectionPresentationProps } from '@/runa/admin';
const props = defineProps<AdminSectionPresentationProps>();
const record = computed(
  () => props.controller.records.value[props.section.key],
);
const locked = computed(
  () =>
    record.value?.values.system === true ||
    typeof record.value?.values.module === 'string',
);
const section = computed(() => ({
  ...props.section,
  readonly: props.section.readonly || locked.value,
  fields: props.section.fields
    .filter((field) => field.key !== 'review_summary')
    .map((field) => ({
      ...field,
      readonly:
        field.readonly ||
        (field.key === 'code' && record.value?.public_id != null) ||
        (field.key === 'source_user' && record.value?.public_id != null),
    })),
  commands: locked.value
    ? []
    : props.section.commands.filter(
        (command) => record.value?.public_id != null || command.key === 'store',
      ),
}));
</script>
<template>
  <Card>
    <Alert v-if="locked" tone="info" class="mb-4"
      >Source and system definitions are read-only.</Alert
    >
    <AdminSection :section="section" :controller="controller" />
  </Card>
</template>

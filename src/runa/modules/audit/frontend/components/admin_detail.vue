<script setup lang="ts">
import { useAdminLocale } from '@/runa/admin';
const { t: tr } = useAdminLocale();
import { computed, watch } from 'vue';
import { Alert, Button, Card } from '@/runa/ui';
import { useAdminResource } from '@/runa/admin';
import type { AdminSectionPresentationProps } from '@/runa/admin';
const props = defineProps<AdminSectionPresentationProps>();
void props.controller.activateSection(props.section.key);
const source = computed(
  () => props.controller.records.value[props.section.key],
);
const resource = useAdminResource(props.page, props.section.key);
watch(source, () => resource.clear());
const record = computed(() => {
  const current = resource.result.value;
  return current && 'values' in current ? current : source.value;
});
async function next() {
  const cursor = record.value?.values.payload_next;
  const id = source.value?.public_id;
  if (id && typeof cursor === 'string')
    await resource.read({ kind: 'record', public_id: id, cursor });
}
const entities = computed<
  { module: string; type: string; public_id: string }[]
>(() => {
  const raw = record.value?.values.entities;
  if (typeof raw !== 'string') return [];
  try {
    const parsed: unknown = JSON.parse(raw);
    return Array.isArray(parsed)
      ? parsed.filter(
          (item): item is { module: string; type: string; public_id: string } =>
            item !== null &&
            typeof item === 'object' &&
            typeof item.module === 'string' &&
            typeof item.type === 'string' &&
            typeof item.public_id === 'string',
        )
      : [];
  } catch {
    return [];
  }
});
function history(entity: { module: string; type: string; public_id: string }) {
  return (
    '/admin/runa.audit/history?' +
    new URLSearchParams({
      entity_module: entity.module,
      entity_type: entity.type,
      entity_public_id: entity.public_id,
    }).toString()
  );
}
</script>
<template>
  <Card>
    <p v-if="!record?.public_id" class="text-sm text-muted">
      {{ tr('Select an event to read its immutable details.') }}
    </p>
    <template v-else>
      <Alert v-if="resource.error.value" tone="warning">{{
        tr(resource.error.value.message)
      }}</Alert>
      <dl class="grid gap-4 sm:grid-cols-2">
        <div
          v-for="field in section.fields.filter(
            (field) =>
              !['entities', 'payload_next', 'payload_part'].includes(field.key),
          )"
          :key="field.key"
          :class="
            ['changes', 'context', 'execution'].includes(field.key)
              ? 'sm:col-span-2'
              : ''
          "
        >
          <dt class="text-xs font-semibold text-muted">
            {{ tr(field.label) }}
          </dt>
          <dd class="whitespace-pre-wrap break-words text-sm">
            {{ record.values[field.key] ?? '—' }}
          </dd>
        </div>
      </dl>
      <p class="mt-4 text-sm text-muted">
        {{ tr('Payload part') }} {{ record.values.payload_part ?? '1 / 1' }}.
        {{ tr('Large JSON payloads continue across parts.') }}
      </p>
      <div class="mt-3 flex gap-2">
        <Button
          size="sm"
          :disabled="resource.busy.value || !record.values.payload_next"
          @click="next"
          >{{ tr('Next payload part') }}</Button
        >
        <Button
          size="sm"
          :disabled="resource.busy.value"
          @click="resource.clear()"
          >{{ tr('First payload part') }}</Button
        >
      </div>
      <ul class="mt-4 space-y-2" :aria-label="tr('Related entity history')">
        <li
          v-for="entity in entities"
          :key="entity.module + ':' + entity.type + ':' + entity.public_id"
        >
          <a class="text-primary underline" :href="history(entity)"
            >{{ tr('History:') }} {{ entity.module }} / {{ entity.type }} /
            {{ entity.public_id }}</a
          >
        </li>
      </ul>
    </template>
  </Card>
</template>

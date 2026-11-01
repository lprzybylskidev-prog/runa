<script setup lang="ts">
import { useAdminLocale } from '@/runa/admin';
const { locale: adminLocale, t: tr } = useAdminLocale();
import { computed, watch } from 'vue';
import {
  formatApplicationInstant,
  FormNotifications,
  Button,
  Card,
  ValuePreview,
  TextLink,
} from '@/runa/ui';
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
const groups = computed(() =>
  [
    { title: 'Event', keys: ['occurred_at', 'module', 'action', 'outcome'] },
    { title: 'Actor', keys: ['actor_label', 'actor_kind', 'actor_public_id'] },
    { title: 'Technical identifiers', keys: ['correlation_id'] },
  ]
    .map((group) => ({
      ...group,
      fields: group.keys.flatMap((key) => {
        const field = props.section.fields.find((field) => field.key === key);
        return field && record.value?.values[key] !== undefined ? [field] : [];
      }),
    }))
    .filter((group) => group.fields.length),
);
const payloads = computed(() =>
  props.section.fields.filter(
    (field) =>
      ['changes', 'context', 'execution'].includes(field.key) &&
      record.value?.values[field.key] !== undefined,
  ),
);
const multipleParts = computed(() => {
  const part = record.value?.values.payload_part;
  return typeof part === 'string' && part !== '1 / 1';
});
function display(key: string) {
  const field = props.section.fields.find((field) => field.key === key);
  const value = record.value?.values[key];
  if (typeof value !== 'string') return '—';
  if (field?.type === 'datetime')
    return formatApplicationInstant(value, adminLocale.value);
  return field?.choices.includes(value) ? tr(value) : value;
}
</script>
<template>
  <div class="min-w-0 space-y-4">
    <p v-if="!record?.public_id" class="text-sm text-muted">
      {{ tr('Select an event to read its immutable details.') }}
    </p>
    <template v-else>
      <FormNotifications
        :locale="adminLocale"
        :error="resource.error.value ? tr(resource.error.value.message) : ''"
      />
      <div class="grid min-w-0 gap-4 md:grid-cols-2">
        <Card
          v-for="group in groups"
          :key="group.title"
          :class="
            group.title === 'Technical identifiers' ? 'md:col-span-2' : ''
          "
        >
          <template #header
            ><h3>{{ tr(group.title) }}</h3></template
          >
          <dl class="grid min-w-0 gap-4 sm:grid-cols-2">
            <div
              v-for="field in group.fields"
              :key="field.key"
              class="min-w-0"
              :class="field.key === 'actor_public_id' ? 'sm:col-span-2' : ''"
            >
              <dt class="text-xs font-semibold text-muted">
                {{ tr(field.label) }}
              </dt>
              <dd class="mt-1 min-w-0">
                <ValuePreview
                  :text="display(field.key)"
                  :locale="adminLocale"
                />
              </dd>
            </div>
          </dl>
        </Card>
      </div>
      <Card v-if="payloads.length">
        <template #header
          ><h3>{{ tr('Event data') }}</h3></template
        >
        <div class="grid min-w-0 gap-4 sm:grid-cols-2">
          <div
            v-for="field in payloads"
            :key="field.key"
            class="min-w-0"
            :class="
              field.key === 'execution' ||
              !['[]', '{}', '', null].includes(
                record.values[field.key] as string | null,
              )
                ? 'sm:col-span-2'
                : ''
            "
          >
            <h4 class="mb-2 text-xs font-semibold text-muted">
              {{ tr(field.label) }}
            </h4>
            <p
              v-if="
                ['[]', '{}', '', null].includes(
                  record.values[field.key] as string | null,
                )
              "
              class="text-sm text-muted"
            >
              {{ tr('No data.') }}
            </p>
            <ValuePreview
              v-else
              :key="record.public_id + ':' + record.values.payload_part"
              :text="String(record.values[field.key])"
              format="json"
              :locale="adminLocale"
            />
          </div>
        </div>
      </Card>
      <div
        v-if="multipleParts"
        class="space-y-3 rounded border border-line p-4"
      >
        <p class="text-xs text-muted">
          {{ tr('Payload part') }} {{ record.values.payload_part }}.
          {{ tr('Large JSON payloads continue across parts.') }}
        </p>
        <div class="flex flex-wrap gap-2">
          <Button
            size="sm"
            :disabled="resource.busy.value || !record.values.payload_next"
            @click="next"
            >{{ tr('Next payload part') }}</Button
          >
          <Button
            size="sm"
            :disabled="
              resource.busy.value ||
              record.values.payload_part === '1 / 1' ||
              String(record.values.payload_part).startsWith('1 / ')
            "
            @click="resource.clear()"
            >{{ tr('First payload part') }}</Button
          >
        </div>
      </div>
      <Card v-if="entities.length">
        <template #header
          ><h3>{{ tr('Related entity history') }}</h3></template
        >
        <p class="mb-3 text-xs text-muted">
          {{ tr('Open Audit history filtered to this entity.') }}
        </p>
        <ul class="space-y-3" :aria-label="tr('Related entity history')">
          <li
            v-for="entity in entities"
            :key="entity.module + ':' + entity.type + ':' + entity.public_id"
            class="min-w-0 rounded border border-line px-3 py-2"
          >
            <p class="text-xs text-muted [overflow-wrap:anywhere]">
              {{ entity.module }} / {{ entity.type }}
            </p>
            <p class="text-xs [overflow-wrap:anywhere]">
              {{ entity.public_id }}
            </p>
            <TextLink :href="history(entity)"
              >{{ tr('View entity history') }} →</TextLink
            >
          </li>
        </ul>
      </Card>
    </template>
  </div>
</template>

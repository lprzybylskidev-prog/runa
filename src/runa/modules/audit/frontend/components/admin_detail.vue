<script setup lang="ts">
import { useAdminLocale } from '@/runa/admin';
const { locale: adminLocale, t: tr } = useAdminLocale();
import { computed, ref, watch } from 'vue';
import {
  formatApplicationInstant,
  FormNotifications,
  Button,
  Card,
  ValuePreview,
  TextLink,
  Spinner,
} from '@/runa/ui';
import { useAdminResource } from '@/runa/admin';
import type { AdminSectionPresentationProps } from '@/runa/admin';
const props = defineProps<AdminSectionPresentationProps>();
const initialSettled = ref(false);
void props.controller.activateSection(props.section.key).finally(() => {
  initialSettled.value = true;
});
async function retry() {
  initialSettled.value = false;
  try {
    await props.controller.refresh();
  } finally {
    initialSettled.value = true;
  }
}
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
function history(key: string) {
  if (!['correlation_id', 'actor_public_id'].includes(key)) return undefined;
  const value = record.value?.values[key];
  if (typeof value !== 'string' || !value) return undefined;
  return (
    '/admin/runa.audit/history?' +
    new URLSearchParams({ [key]: value }).toString()
  );
}
const groups = computed(() =>
  [
    {
      title: 'Event',
      keys: ['occurred_at', 'module', 'action', 'outcome', 'correlation_id'],
    },
    { title: 'Actor', keys: ['actor_label', 'actor_kind', 'actor_public_id'] },
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
    <div
      v-if="!record && !initialSettled"
      aria-busy="true"
      class="flex min-h-32 items-center justify-center"
    >
      <Spinner :label="tr('Loading event details')" />
    </div>
    <div v-else-if="!record" class="space-y-3">
      <p class="text-sm text-muted">
        {{ tr('Unable to load the current record.') }}
      </p>
      <Button size="sm" @click="retry">{{ tr('Retry loading') }}</Button>
    </div>
    <p v-else-if="!record.public_id" class="text-sm text-muted">
      {{ tr('Select an event to read its immutable details.') }}
    </p>
    <template v-else>
      <FormNotifications
        :locale="adminLocale"
        :error="resource.error.value ? tr(resource.error.value.message) : ''"
      />
      <div class="grid min-w-0 gap-4 md:grid-cols-2">
        <Card v-for="group in groups" :key="group.title">
          <template #header
            ><h3>{{ tr(group.title) }}</h3></template
          >
          <dl class="grid min-w-0 gap-4 sm:grid-cols-2">
            <div
              v-for="field in group.fields"
              :key="field.key"
              class="min-w-0"
              :class="
                ['actor_public_id', 'correlation_id'].includes(field.key)
                  ? 'sm:col-span-2'
                  : ''
              "
            >
              <dt class="text-xs font-semibold text-muted">
                {{ tr(field.label) }}
              </dt>
              <dd class="mt-1 min-w-0">
                <TextLink
                  v-if="history(field.key)"
                  :href="history(field.key)!"
                  class="max-w-full [overflow-wrap:anywhere]"
                  :aria-label="
                    tr(
                      field.key === 'correlation_id'
                        ? 'Show related events'
                        : 'Show actor history',
                    ) +
                    ': ' +
                    display(field.key)
                  "
                  >{{ display(field.key) }}</TextLink
                >
                <ValuePreview
                  v-else
                  :text="display(field.key)"
                  :locale="adminLocale"
                />
              </dd>
            </div>
          </dl>
        </Card>
      </div>
      <Card v-if="payloads.length" :aria-busy="resource.busy.value">
        <template #actions
          ><Spinner
            v-if="resource.busy.value"
            size="sm"
            :label="tr('Loading event details')"
        /></template>
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
    </template>
  </div>
</template>

<script setup lang="ts">
import {
  computed,
  onMounted,
  onScopeDispose,
  ref,
  shallowRef,
  watch,
} from 'vue';
import {
  ViewBoundary,
  useViewReadiness,
  Button,
  Checkbox,
  Select,
  TextInput,
} from '@/runa/ui';
import { useAdminLocale } from './localization';
import { useAdminInitialReadiness } from './initialReadiness';
import AdminOperationResult from './AdminOperationResult.vue';
import type {
  AdminOperationData,
  AdminOperationsController,
} from './useAdminOperations';
const { locale: adminLocale, t: tr } = useAdminLocale();
const props = defineProps<{
  controller: AdminOperationsController;
  module: string;
}>();
const emit = defineEmits<{ saved: [] }>();
const loaded = shallowRef<AdminOperationData>();
const result = shallowRef<AdminOperationData>();
const json = ref('{}'),
  key = ref(''),
  enabled = ref(false),
  parseError = ref('');
let draftRevision = 0;
const integrations = computed(() => {
  const source = loaded.value?.integrations;
  return source && typeof source === 'object' && !Array.isArray(source)
    ? source
    : {};
});
function changed() {
  draftRevision++;
  parseError.value = '';
  props.controller.dirty.value = true;
}
async function load() {
  if (!(await props.controller.beforeLeave())) return;
  const own = draftRevision;
  const response = await props.controller.read('integrations', {
    module: props.module,
  });
  if (!response || own !== draftRevision) return;
  loaded.value = response;
  key.value = '';
  enabled.value = false;
  json.value = '{}';
  props.controller.dirty.value = false;
  parseError.value = '';
}
async function selectIntegration(value: string) {
  if (!(await props.controller.beforeLeave())) return;
  const entry = integrations.value[value];
  if (!entry || typeof entry !== 'object' || Array.isArray(entry)) return;
  draftRevision++;
  key.value = value;
  enabled.value = entry.enabled === true;
  json.value = JSON.stringify(entry.config ?? {}, null, 2);
  props.controller.dirty.value = false;
}
async function save() {
  if (!loaded.value || !key.value || !props.controller.dirty.value) return;
  let value: AdminOperationData;
  try {
    const parsed: unknown = JSON.parse(json.value);
    if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed))
      throw new TypeError();
    value = parsed as AdminOperationData;
    parseError.value = '';
  } catch {
    parseError.value = 'Enter a valid JSON object.';
    return;
  }
  const response = await props.controller.execute('integrations.configure', {
    module: props.module,
    generation: loaded.value.generation ?? null,
    key: key.value,
    enabled: enabled.value,
    config: value,
  });
  if (response) {
    props.controller.dirty.value = false;
    result.value = response;
    loaded.value = undefined;
    json.value = '{}';
    await load();
    emit('saved');
  }
}
watch(
  () => props.controller.contextRevision.value,
  () => {
    draftRevision++;
    loaded.value = undefined;
    result.value = undefined;
    json.value = '{}';
    key.value = '';
    enabled.value = false;
    parseError.value = '';
  },
);
const initialSettled = ref(false);
const readiness = useViewReadiness({
  identity: 'editor',
  required: initialSettled,
});
useAdminInitialReadiness(() => initialSettled.value);
onMounted(async () => {
  try {
    await load();
  } finally {
    initialSettled.value = true;
  }
});
onScopeDispose(() => {
  props.controller.dirty.value = false;
});
</script>
<template>
  <ViewBoundary
    :readiness="readiness"
    :label="tr('Loading the administrator panel')"
  >
    <div class="space-y-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="font-semibold">{{ tr('Optional integrations') }}</h3>
        <Button :disabled="controller.busy.value" @click="load">{{
          tr('Reload current state')
        }}</Button>
      </div>
      <AdminOperationResult v-if="result" :result="result" />
      <template v-if="loaded">
        <Select
          :label="tr('Integration')"
          :model-value="key"
          :options="
            Object.keys(integrations).map((value) => ({ value, label: value }))
          "
          :disabled="controller.busy.value"
          :locale="adminLocale"
          @update:model-value="selectIntegration(String($event))"
        />
        <Checkbox
          v-if="key"
          :label="tr('Enabled')"
          v-model="enabled"
          :disabled="controller.busy.value"
          @update:model-value="changed"
        />
        <TextInput
          v-if="key"
          :label="tr('Non-sensitive integration configuration (JSON)')"
          type="textarea"
          v-model="json"
          :error="tr(parseError)"
          :disabled="controller.busy.value"
          @update:model-value="changed"
        />
        <Button
          :disabled="controller.busy.value || !controller.dirty.value || !key"
          @click="save"
          >{{ tr('Save integration') }}</Button
        >
      </template>
    </div>
  </ViewBoundary>
</template>

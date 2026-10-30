<script setup lang="ts">
import AdminOperationResult from './AdminOperationResult.vue';
import {
  computed,
  onMounted,
  onScopeDispose,
  ref,
  shallowRef,
  watch,
} from 'vue';
import {
  Alert,
  Button,
  Checkbox,
  NumberInput,
  Select,
  TextInput,
} from '@/runa/ui';
import type { AdminJson } from './contracts';
import type {
  AdminOperationData,
  AdminOperationsController,
} from './useAdminOperations';
const props = defineProps<{
  controller: AdminOperationsController;
  module: string;
  kind: 'settings' | 'health' | 'integrations';
  writable?: boolean;
}>();
const emit = defineEmits<{ saved: [] }>();
const loaded = shallowRef<AdminOperationData>();
const draft = ref<Record<string, AdminJson>>({});
const edited = ref(new Set<string>());
const raw = ref<Record<string, string>>({});
const invalidArrays = ref(new Set<string>());
const json = ref('{}'),
  key = ref(''),
  enabled = ref(false);
const parseError = ref('');
const settingRows = computed(() => {
  const source = loaded.value?.settings;
  if (!source || typeof source !== 'object' || Array.isArray(source)) return [];
  return Object.entries(source).flatMap(([key, entry]) => {
    if (!entry || typeof entry !== 'object' || Array.isArray(entry)) return [];
    const definition = entry.definition;
    if (
      !definition ||
      typeof definition !== 'object' ||
      Array.isArray(definition)
    )
      return [];
    const names = definition.names;
    return [
      {
        key,
        definition,
        entry,
        label:
          names &&
          typeof names === 'object' &&
          !Array.isArray(names) &&
          typeof names.en === 'string'
            ? names.en
            : key,
      },
    ];
  });
});
const integrations = computed(() => {
  const source = loaded.value?.integrations;
  return source && typeof source === 'object' && !Array.isArray(source)
    ? source
    : {};
});
function choices(options: AdminJson | undefined) {
  const items =
    options &&
    typeof options === 'object' &&
    !Array.isArray(options) &&
    Array.isArray(options.choices)
      ? options.choices
      : [];
  return items.map((value) => ({
    value: JSON.stringify(value),
    label: String(value),
  }));
}
function enumEdit(
  name: string,
  options: AdminJson | undefined,
  value: string | null,
) {
  const items =
    options &&
    typeof options === 'object' &&
    !Array.isArray(options) &&
    Array.isArray(options.choices)
      ? options.choices
      : [];
  const selected = items.find((item) => JSON.stringify(item) === value);
  if (selected !== undefined) edit(name, selected);
}
function arrayEdit(name: string, value: string | null) {
  raw.value[name] = value ?? '';
  try {
    const parsed: unknown = JSON.parse(value ?? '');
    if (!Array.isArray(parsed)) throw new TypeError();
    invalidArrays.value.delete(name);
    parseError.value = '';
    edit(name, parsed as AdminJson[]);
  } catch {
    invalidArrays.value.add(name);
    parseError.value = 'Enter a valid JSON array.';
    props.controller.dirty.value = true;
  }
}
function edit(name: string, value: AdminJson) {
  if (value === null) {
    raw.value[name] = 'null';
    invalidArrays.value.delete(name);
  }
  draft.value[name] = value;
  edited.value.add(name);
  props.controller.dirty.value = true;
}
function changed() {
  parseError.value = '';
  props.controller.dirty.value = true;
}
async function load() {
  if (!(await props.controller.beforeLeave())) return;
  const response = await props.controller.read(
    props.kind === 'health' ? 'module' : props.kind,
    { module: props.module },
  );
  if (!response) return;
  loaded.value = response;
  draft.value = {};
  raw.value = {};
  invalidArrays.value = new Set();
  edited.value = new Set();
  props.controller.dirty.value = false;
  parseError.value = '';
  if (props.kind === 'settings') {
    for (const row of settingRows.value) {
      draft.value[row.key] = row.definition.sensitive
        ? null
        : (row.entry.value ?? null);
      raw.value[row.key] = JSON.stringify(draft.value[row.key]);
    }
  } else if (props.kind === 'health')
    json.value = JSON.stringify(response.health_overrides ?? {}, null, 2);
  else {
    key.value = '';
    enabled.value = false;
    json.value = '{}';
  }
}
async function selectIntegration(value: string) {
  if (!(await props.controller.beforeLeave())) return;
  const entry = integrations.value[value];
  if (!entry || typeof entry !== 'object' || Array.isArray(entry)) return;
  key.value = value;
  enabled.value = entry.enabled === true;
  json.value = JSON.stringify(entry.config ?? {}, null, 2);
  props.controller.dirty.value = false;
}
function parse(): AdminOperationData | undefined {
  try {
    const value: unknown = JSON.parse(json.value);
    if (!value || typeof value !== 'object' || Array.isArray(value))
      throw new TypeError();
    parseError.value = '';
    return value as AdminOperationData;
  } catch {
    parseError.value = 'Enter a valid JSON object.';
    return;
  }
}
async function save() {
  if (
    !loaded.value ||
    !props.controller.dirty.value ||
    props.writable === false ||
    invalidArrays.value.size > 0
  )
    return;
  let response: AdminOperationData | undefined;
  if (props.kind === 'settings') {
    const patch = Object.fromEntries(
      [...edited.value].map((key) => [key, draft.value[key] ?? null]),
    );
    response = await props.controller.execute('settings.update', {
      module: props.module,
      generation: loaded.value.generation ?? null,
      revision: loaded.value.revision ?? null,
      patch,
    });
  } else {
    const value = parse();
    if (!value) return;
    response = await props.controller.execute(
      props.kind === 'health' ? 'health.configure' : 'integrations.configure',
      {
        module: props.module,
        generation: loaded.value.generation ?? null,
        ...(props.kind === 'health'
          ? { overrides: value }
          : { key: key.value, enabled: enabled.value, config: value }),
      },
    );
  }
  if (response) {
    props.controller.dirty.value = false;
    // Native pending/recovery state stays visible; never label it committed.
    result.value = response;
    loaded.value = undefined;
    draft.value = {};
    edited.value = new Set();
    json.value = '{}';
    const transition = response.transition;
    if (
      response.status === 'committed' ||
      typeof response.generation === 'number' ||
      (transition &&
        typeof transition === 'object' &&
        !Array.isArray(transition) &&
        transition.status === 'succeeded')
    )
      await load();
    emit('saved');
  }
}
const result = shallowRef<AdminOperationData>();
watch(
  () => props.controller.contextRevision.value,
  () => {
    loaded.value = undefined;
    result.value = undefined;
    draft.value = {};
    raw.value = {};
    edited.value = new Set();
    invalidArrays.value = new Set();
    json.value = '{}';
    key.value = '';
    enabled.value = false;
    parseError.value = '';
  },
);
onMounted(load);
onScopeDispose(() => {
  props.controller.dirty.value = false;
});
</script>
<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <h3 class="font-semibold">
        {{
          kind === 'settings'
            ? 'Module settings'
            : kind === 'health'
              ? 'Health policy'
              : 'Optional integrations'
        }}
      </h3>
      <Button :disabled="controller.busy.value" @click="load"
        >Reload current state</Button
      >
    </div>
    <AdminOperationResult v-if="result" :result="result" />
    <template v-if="loaded">
      <Alert v-if="kind === 'settings' && loaded.editable === false" tone="info"
        >Auth policies use their dedicated reviewed policy page.</Alert
      >
      <template v-if="kind === 'settings'">
        <p v-if="!settingRows.length" class="text-sm text-muted">
          No settings are declared.
        </p>
        <div v-for="row in settingRows" :key="row.key" class="space-y-2">
          <p v-if="row.definition.sensitive" class="text-xs text-muted">
            {{ row.label }}: {{ row.entry.set ? 'Set' : 'Not set' }}. Only an
            explicit replacement is submitted.
          </p>
          <Checkbox
            v-if="row.definition.type === 'boolean'"
            :label="row.label"
            :model-value="draft[row.key] === true"
            :disabled="
              controller.busy.value ||
              loaded.editable === false ||
              writable === false
            "
            @update:model-value="edit(row.key, $event)"
          />
          <NumberInput
            v-else-if="row.definition.type === 'integer'"
            :label="row.label"
            :model-value="String(draft[row.key] ?? '')"
            kind="integer"
            locale="en"
            :disabled="
              controller.busy.value ||
              loaded.editable === false ||
              writable === false
            "
            @update:model-value="
              edit(
                row.key,
                $event !== null &&
                  /^-?\d+$/.test($event) &&
                  Number.isSafeInteger(Number($event))
                  ? Number($event)
                  : $event,
              )
            "
          />
          <Select
            v-else-if="row.definition.type === 'enum'"
            :label="row.label"
            :model-value="JSON.stringify(draft[row.key])"
            :options="choices(row.definition.options)"
            locale="en"
            :disabled="
              controller.busy.value ||
              loaded.editable === false ||
              writable === false
            "
            @update:model-value="
              enumEdit(row.key, row.definition.options, $event)
            "
          />
          <TextInput
            v-else-if="row.definition.type === 'array'"
            :label="row.label + ' (JSON)'"
            type="textarea"
            :model-value="raw[row.key] ?? ''"
            :error="parseError"
            :disabled="
              controller.busy.value ||
              loaded.editable === false ||
              writable === false
            "
            @update:model-value="arrayEdit(row.key, $event)"
          />
          <TextInput
            v-else-if="
              ['string', 'decimal'].includes(String(row.definition.type))
            "
            :label="row.label"
            :type="row.definition.sensitive ? 'password' : 'text'"
            :model-value="
              typeof draft[row.key] === 'string'
                ? (draft[row.key] as string)
                : draft[row.key] === null
                  ? null
                  : JSON.stringify(draft[row.key])
            "
            :disabled="
              controller.busy.value ||
              loaded.editable === false ||
              writable === false
            "
            autocomplete="off"
            @update:model-value="edit(row.key, $event)"
          />
          <p v-else class="break-words text-sm text-muted">
            {{ row.label }} uses a module-specific control. Supply an
            operational editor replacement for this declared type.
          </p>
          <Button
            v-if="
              row.definition.nullable &&
              loaded.editable !== false &&
              writable !== false
            "
            :disabled="controller.busy.value"
            @click="edit(row.key, null)"
            >Clear {{ row.label }}</Button
          >
        </div>
      </template>
      <template v-else-if="kind !== 'health' || loaded.health_policy">
        <template v-if="kind === 'integrations'">
          <Select
            label="Integration"
            :model-value="key"
            :options="
              Object.keys(integrations).map((value) => ({
                value,
                label: value,
              }))
            "
            :disabled="controller.busy.value"
            locale="en"
            @update:model-value="selectIntegration(String($event))"
          />
          <Checkbox
            v-if="key"
            label="Enabled"
            v-model="enabled"
            :disabled="controller.busy.value"
            @update:model-value="changed"
          />
        </template>
        <TextInput
          v-if="kind === 'health' || key"
          :label="
            kind === 'health'
              ? 'Source-validated health overrides (JSON)'
              : 'Non-sensitive integration configuration (JSON)'
          "
          type="textarea"
          v-model="json"
          :error="parseError"
          :disabled="controller.busy.value"
          @update:model-value="changed"
        />
      </template>
      <p v-else class="text-sm text-muted">
        No editable health policy is available.
      </p>
      <Button
        v-if="loaded.editable !== false && writable !== false"
        :disabled="controller.busy.value || !controller.dirty.value"
        @click="save"
        >Save {{ kind }}</Button
      >
    </template>
  </div>
</template>

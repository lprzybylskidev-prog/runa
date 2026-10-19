<script setup lang="ts" generic="Multiple extends boolean = false">
import { computed, nextTick, onScopeDispose, ref, watch } from 'vue';
import AppFormField from './AppFormField.vue';
import AppButton from './AppButton.vue';
import { useFloatingLayer } from '../composables/useFloatingLayer';
import type { SelectOption, SelectionProvider } from '../runa/ui/selection';
defineOptions({ inheritAttrs: false });
const props = withDefaults(
  defineProps<{
    label: string;
    size?: 'sm' | 'md';
    modelValue: Multiple extends true ? string[] : string;
    multiple?: Multiple | undefined;
    options?: readonly SelectOption[];
    selectedOptions?: readonly SelectOption[];
    provider?: SelectionProvider | undefined;
    searchable?: boolean;
    clearable?: boolean;
    disabled?: boolean;
    readonly?: boolean;
    required?: boolean;
    id?: string;
    helper?: string;
    warning?: string;
    error?: string | readonly string[];
    locale?: 'pl' | 'en';
    pageSize?: number;
    debounce?: number;
  }>(),
  {
    size: 'md',
    multiple: undefined,
    provider: undefined,
    options: () => [],
    selectedOptions: () => [],
    searchable: false,
    clearable: false,
    disabled: false,
    readonly: false,
    required: false,
    id: '',
    helper: '',
    warning: '',
    error: '',
    locale: 'pl',
    pageSize: 40,
    debounce: 250,
  },
);
const emit = defineEmits<{
  'update:modelValue': [value: Multiple extends true ? string[] : string];
  selection: [options: SelectOption[]];
}>();
const open = ref(false);
const anchor = ref<globalThis.HTMLElement>();
const panel = ref<globalThis.HTMLElement>();
const search = ref<globalThis.HTMLInputElement>();
const query = ref('');
const items = ref<SelectOption[]>([]);
const cursor = ref<string | null>(null);
const loading = ref(false);
const failed = ref(false);
const active = ref(-1);
const limit = computed(() =>
  Math.max(1, Math.min(100, Math.floor(props.pageSize) || 40)),
);
const visibleCount = ref(40);
const cache = new Map<string, SelectOption>();
const values = computed<string[]>(() =>
  Array.isArray(props.modelValue)
    ? props.modelValue
    : props.modelValue
      ? [props.modelValue]
      : [],
);
const labels = computed(() =>
  values.value
    .map(
      (value) =>
        props.selectedOptions.find((o) => o.value === value)?.label ??
        props.options.find((o) => o.value === value)?.label ??
        items.value.find((o) => o.value === value)?.label ??
        cache.get(value)?.label ??
        value,
    )
    .join(', '),
);
const text = (pl: string, en: string) => (props.locale === 'en' ? en : pl);
const filtered = computed(() =>
  props.provider
    ? items.value
    : props.options.filter((o) =>
        o.label
          .toLocaleLowerCase(props.locale)
          .includes(query.value.toLocaleLowerCase(props.locale)),
      ),
);
// A window of results is rendered; loading another page never mounts an unbounded list.
const offset = ref(0);
const shown = computed(() =>
  filtered.value.slice(offset.value, offset.value + visibleCount.value),
);
const canMore = computed(() =>
  props.provider
    ? cursor.value !== null
    : offset.value + visibleCount.value < filtered.value.length,
);
let controller: globalThis.AbortController | undefined;
let generation = 0;
let retryAppend = false;
let timer: ReturnType<typeof globalThis.setTimeout> | undefined;
function cancel() {
  generation++;
  controller?.abort();
  globalThis.clearTimeout(timer);
  loading.value = false;
}
async function load(append = false) {
  if (!props.provider) return;
  retryAppend = append;
  controller?.abort();
  const request = ++generation;
  controller = new AbortController();
  loading.value = true;
  failed.value = false;
  try {
    const result = await props.provider({
      query: query.value,
      cursor: append ? cursor.value : null,
      limit: limit.value,
      signal: controller.signal,
    });
    if (request !== generation || !open.value) return;
    const unique = new Map<string, SelectOption>();
    for (const option of result.options.slice(0, limit.value))
      unique.set(option.value, option);
    items.value = [...unique.values()];
    for (const option of items.value)
      if (values.value.includes(option.value)) cache.set(option.value, option);
    cursor.value = result.nextCursor ?? null;
    active.value = -1;
  } catch {
    if (request === generation) failed.value = true;
  } finally {
    if (request === generation) loading.value = false;
  }
}
function close(restore = false) {
  open.value = false;
  cancel();
  if (restore) anchor.value?.querySelector('button')?.focus();
}
const { position, ready, teleportTarget } = useFloatingLayer({
  open,
  anchor,
  panel,
  placement: () => 'bottom',
  dismiss: close,
});
watch(open, async (value) => {
  if (!value) {
    cancel();
    return;
  }
  query.value = '';
  offset.value = 0;
  visibleCount.value = limit.value;
  active.value = -1;
  items.value = [];
  cursor.value = null;
  void load();
  await nextTick();
  (
    search.value ??
    panel.value?.querySelector<globalThis.HTMLElement>('[role=listbox]') ??
    panel.value
  )?.focus();
});
watch(query, () => {
  cancel();
  offset.value = 0;
  active.value = -1;
  items.value = [];
  cursor.value = null;
  failed.value = false;
  if (open.value && props.provider) {
    loading.value = true;
    timer = globalThis.setTimeout(
      () => void load(),
      Math.max(0, props.debounce),
    );
  }
});
watch(
  () => [props.disabled, props.readonly, props.provider],
  () => close(),
);
function update(next: string[]) {
  if (
    props.disabled ||
    props.readonly ||
    anchor.value?.querySelector('button')?.matches(':disabled')
  )
    return;
  const result = props.multiple ? next : (next[0] ?? '');
  emit(
    'update:modelValue',
    result as Multiple extends true ? string[] : string,
  );
  emit(
    'selection',
    next.map(
      (value) =>
        props.options.find((o) => o.value === value) ??
        items.value.find((o) => o.value === value) ??
        cache.get(value) ?? { value, label: value },
    ),
  );
}
function choose(option: SelectOption) {
  if (option.disabled || loading.value) return;
  for (const key of cache.keys())
    if (!values.value.includes(key)) cache.delete(key);
  cache.set(option.value, option);
  update(
    props.multiple
      ? values.value.includes(option.value)
        ? values.value.filter((v) => v !== option.value)
        : [...values.value, option.value]
      : [option.value],
  );
  if (!props.multiple) close(true);
}
function more() {
  if (loading.value) return;
  if (props.provider) void load(true);
  else {
    offset.value += limit.value;
    active.value = -1;
  }
}
function keyboard(event: globalThis.KeyboardEvent) {
  if (event.isComposing) return;
  if (event.key === 'Tab') {
    close();
    anchor.value?.querySelector('button')?.focus();
    return;
  }
  if (event.target instanceof globalThis.HTMLButtonElement) return;
  const enabled = shown.value
    .map((o, i) => (o.disabled ? -1 : i))
    .filter((i) => i >= 0);
  if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
    event.preventDefault();
    const index = enabled.indexOf(active.value);
    active.value =
      event.key === 'Home'
        ? (enabled[0] ?? -1)
        : event.key === 'End'
          ? (enabled.at(-1) ?? -1)
          : (enabled[
              Math.max(
                0,
                Math.min(
                  enabled.length - 1,
                  index + (event.key === 'ArrowUp' ? -1 : 1),
                ),
              )
            ] ?? -1);
    void nextTick(() =>
      panel.value
        ?.querySelector('[data-active="true"]')
        ?.scrollIntoView({ block: 'nearest' }),
    );
  } else if (event.key === 'Enter' && active.value >= 0) {
    event.preventDefault();
    choose(shown.value[active.value]!);
  }
}
onScopeDispose(cancel);
</script>
<template>
  <AppFormField
    :id="id"
    :label="label"
    :helper="helper"
    :warning="warning"
    :error="error"
    :required="required"
  >
    <template #default="{ id: controlId, describedBy, invalid }">
      <div ref="anchor" class="selection-anchor flex min-w-0 gap-2">
        <button
          :id="controlId"
          v-bind="$attrs"
          type="button"
          :data-size="size"
          class="runa-field-control selection-trigger min-w-0 flex-1 text-left"
          :disabled="disabled"
          :aria-readonly="readonly || undefined"
          :aria-invalid="invalid || undefined"
          :aria-required="required || undefined"
          :aria-describedby="describedBy"
          aria-haspopup="dialog"
          :aria-expanded="open"
          :aria-controls="open ? `${controlId}-panel` : undefined"
          @click="!readonly && (open = !open)"
          @keydown.down.prevent="!readonly && (open = true)"
        >
          <span class="block truncate">{{
            labels || text('Wybierz…', 'Choose…')
          }}</span>
        </button>
        <AppButton
          v-if="clearable && values.length"
          :size="size"
          :disabled="disabled || readonly"
          :aria-label="text('Wyczyść wybór', 'Clear selection')"
          @click="update([])"
          >×</AppButton
        >
      </div>
      <Teleport :to="teleportTarget">
        <section
          v-if="open"
          :id="`${controlId}-panel`"
          ref="panel"
          role="dialog"
          :aria-label="label"
          tabindex="-1"
          :data-size="size"
          class="selection-panel"
          :class="{ 'selection-ready': ready }"
          :style="position"
          @keydown="keyboard"
        >
          <input
            v-if="searchable || provider"
            ref="search"
            v-model="query"
            :data-size="size"
            class="runa-field-control mb-2"
            :aria-label="text('Szukaj: ', 'Search: ') + label"
            role="combobox"
            aria-autocomplete="list"
            aria-expanded="true"
            :aria-controls="`${controlId}-list`"
            :aria-activedescendant="
              active >= 0 ? `${controlId}-option-${active}` : undefined
            "
          />
          <div
            :id="`${controlId}-list`"
            role="listbox"
            :aria-label="label"
            :aria-multiselectable="multiple || undefined"
            :aria-busy="loading"
            :tabindex="searchable || provider ? undefined : 0"
            :aria-activedescendant="
              active >= 0 ? `${controlId}-option-${active}` : undefined
            "
            class="max-h-60 overflow-auto"
          >
            <div
              v-for="(option, index) in shown"
              :id="`${controlId}-option-${index}`"
              :key="option.value"
              role="option"
              :aria-selected="values.includes(option.value)"
              :aria-disabled="option.disabled || undefined"
              :data-active="active === index"
              class="selection-option"
              @pointerdown.prevent
              @click="choose(option)"
            >
              <span aria-hidden="true">{{
                values.includes(option.value) ? '✓' : '·'
              }}</span>
              {{ option.label }}
            </div>
          </div>
          <p role="status" class="p-2 text-xs text-muted">
            {{
              loading
                ? text('Ładowanie…', 'Loading…')
                : failed
                  ? text(
                      'Nie udało się pobrać opcji.',
                      'Unable to load options.',
                    )
                  : !shown.length
                    ? text('Brak wyników.', 'No results.')
                    : ''
            }}
          </p>
          <div class="flex flex-wrap gap-2">
            <AppButton v-if="failed" :size="size" @click="load(retryAppend)">{{
              text('Ponów', 'Retry')
            }}</AppButton>
            <AppButton
              v-if="!provider && offset > 0"
              :size="size"
              @click="
                offset = Math.max(0, offset - limit);
                active = -1;
              "
              >{{ text('Poprzednie', 'Previous') }}</AppButton
            >
            <AppButton
              v-if="canMore"
              :size="size"
              :disabled="loading"
              @click="more"
              >{{ text('Kolejne wyniki', 'More results') }}</AppButton
            >
            <AppButton :size="size" @click="close(true)">{{
              text('Gotowe', 'Done')
            }}</AppButton>
          </div>
        </section>
      </Teleport>
    </template>
  </AppFormField>
</template>
<style scoped>
.selection-anchor {
  position: relative;
}
.selection-trigger::after {
  content: '';
  position: absolute;
  right: 16px;
  top: 50%;
  width: 7px;
  height: 7px;
  border-right: 1.5px solid currentColor;
  border-bottom: 1.5px solid currentColor;
  transform: translateY(-50%) rotate(45deg);
  pointer-events: none;
}
.selection-trigger {
  position: relative;
  padding-right: 36px;
}
.selection-panel {
  position: fixed;
  z-index: 40;
  width: min(360px, calc(100vw - 16px));
  max-height: calc(100dvh - 16px);
  overflow: auto;
  padding: 12px;
  border: 1px solid var(--runa-control-border);
  border-radius: 4px;
  background: var(--runa-surface-raised);
  color: var(--runa-text);
  visibility: hidden;
}
.selection-ready {
  visibility: visible;
}
.selection-option {
  min-height: 44px;
  padding: 12px;
  font-size: 12px;
  cursor: pointer;
  overflow-wrap: anywhere;
  border-radius: 4px;
}
.selection-option:hover,
.selection-option[data-active='true'] {
  background: var(--runa-surface-hover);
}
.selection-option[aria-selected='true'] {
  background: var(--runa-primary-subtle);
  color: var(--runa-primary-text);
}
.selection-option[aria-disabled='true'] {
  color: var(--runa-disabled-text);
  cursor: default;
}

.selection-panel[data-size='sm'] {
  padding: 8px;
}
.selection-panel[data-size='sm'] .selection-option {
  min-height: 36px;
  padding: 8px 10px;
  font-size: 12px;
}
.selection-trigger[data-size='sm'] {
  padding-right: 32px;
}
.selection-trigger[data-size='sm']::after {
  right: 12px;
}
</style>

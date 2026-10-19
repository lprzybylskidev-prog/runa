<script setup lang="ts">
import { computed, ref, useId } from 'vue';
import type { TabItem } from '../runa/ui/containers';
const props = defineProps<{
  modelValue: string;
  items: readonly TabItem[];
  label: string;
}>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
const id = useId();
const list = ref<globalThis.HTMLElement>();
const active = computed(
  () =>
    props.items.find(
      (item) => item.id === props.modelValue && !item.disabled,
    ) ?? props.items.find((item) => !item.disabled),
);
function keydown(event: globalThis.KeyboardEvent) {
  const enabled = props.items.filter((item) => !item.disabled);
  const index = enabled.findIndex(
    (item) => item.id === (event.target as globalThis.HTMLElement).dataset.tab,
  );
  let next: number;
  if (event.key === 'Home') next = 0;
  else if (event.key === 'End') next = enabled.length - 1;
  else if (event.key === 'ArrowRight') next = (index + 1) % enabled.length;
  else if (event.key === 'ArrowLeft')
    next = (index - 1 + enabled.length) % enabled.length;
  else return;
  event.preventDefault();
  const item = enabled[next];
  if (item) {
    emit('update:modelValue', item.id);
    list.value
      ?.querySelector<globalThis.HTMLElement>(
        `[data-tab-index="${props.items.indexOf(item)}"]`,
      )
      ?.focus();
  }
}
</script>
<template>
  <div>
    <div
      ref="list"
      role="tablist"
      :aria-label="label"
      class="flex gap-1 overflow-x-auto border-b border-line"
      @keydown="keydown"
    >
      <button
        v-for="(item, index) in items"
        :id="`${id}-tab-${index}`"
        :key="item.id"
        type="button"
        role="tab"
        :data-tab="item.id"
        :data-tab-index="index"
        :disabled="item.disabled"
        :aria-selected="active?.id === item.id"
        :aria-controls="`${id}-panel-${index}`"
        :tabindex="active?.id === item.id ? 0 : -1"
        class="min-h-11 shrink-0 border-b-2 px-4 text-sm transition-colors hover:bg-[var(--runa-surface-hover)] focus-visible:outline-2 focus-visible:outline-[var(--runa-focus-ring)] disabled:opacity-50 motion-reduce:transition-none"
        :class="
          active?.id === item.id
            ? 'border-primary text-primary'
            : 'border-transparent text-muted'
        "
        @click="emit('update:modelValue', item.id)"
      >
        {{ item.label }}
      </button>
    </div>
    <section
      v-for="(item, index) in items"
      :id="`${id}-panel-${index}`"
      :key="item.id"
      role="tabpanel"
      :aria-labelledby="`${id}-tab-${index}`"
      :hidden="active?.id !== item.id"
      tabindex="0"
      class="runa-panel-reveal py-5 text-sm focus-visible:outline-2 focus-visible:outline-[var(--runa-focus-ring)]"
    >
      <slot :name="item.id" :item="item" />
    </section>
  </div>
</template>

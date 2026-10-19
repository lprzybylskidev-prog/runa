<script setup lang="ts">
import { useId } from 'vue';
withDefaults(
  defineProps<{
    title: string;
    description?: string;
    columns?: 1 | 2 | 3;
    collapsible?: boolean;
    collapsed?: boolean;
  }>(),
  { description: '', columns: 1, collapsible: false, collapsed: false },
);
const emit = defineEmits<{ 'update:collapsed': [value: boolean] }>();
const id = useId();
</script>
<template>
  <fieldset
    class="min-w-0 rounded border border-line bg-[var(--runa-container-body-background)]"
  >
    <legend class="max-w-full px-2 text-sm font-semibold">
      <button
        v-if="collapsible"
        type="button"
        class="min-h-11 rounded px-2 text-left hover:bg-[var(--runa-surface-hover)] focus-visible:outline-2 focus-visible:outline-[var(--runa-focus-ring)]"
        :aria-expanded="!collapsed"
        :aria-controls="id"
        @click="emit('update:collapsed', !collapsed)"
      >
        {{ title }}
        <span aria-hidden="true">{{ collapsed ? '+' : '−' }}</span></button
      ><span v-else>{{ title }}</span>
    </legend>
    <div :id="id" :hidden="collapsible && collapsed" class="p-5 pt-3">
      <p v-if="description" class="mb-5 text-xs leading-relaxed text-muted">
        {{ description }}
      </p>
      <div
        class="grid min-w-0 gap-5"
        :class="{
          'md:grid-cols-2': columns === 2,
          'md:grid-cols-3': columns === 3,
        }"
      >
        <slot />
      </div>
    </div>
  </fieldset>
</template>

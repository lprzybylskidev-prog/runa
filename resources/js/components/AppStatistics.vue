<script setup lang="ts">
import AppButton from './AppButton.vue';
import AppButtonGroup from './AppButtonGroup.vue';
import type { StatisticsMode } from '../runa/ui/loading';
defineProps<{
  modelValue: StatisticsMode;
  label: string;
  globalLabel: string;
  filteredLabel: string;
  disabled?: boolean;
}>();
const emit = defineEmits<{ 'update:modelValue': [mode: StatisticsMode] }>();
</script>
<template>
  <div class="min-w-0 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <h2 class="text-sm font-semibold">{{ label }}</h2>
      <AppButtonGroup :label="label" data-print-hide
        ><AppButton
          size="sm"
          :aria-pressed="modelValue === 'global'"
          :disabled="disabled"
          @click="emit('update:modelValue', 'global')"
          >{{ globalLabel }}</AppButton
        ><AppButton
          size="sm"
          :aria-pressed="modelValue === 'filtered'"
          :disabled="disabled"
          @click="emit('update:modelValue', 'filtered')"
          >{{ filteredLabel }}</AppButton
        ></AppButtonGroup
      >
    </div>
    <div class="grid min-w-0 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <slot :mode="modelValue" />
    </div>
  </div>
</template>

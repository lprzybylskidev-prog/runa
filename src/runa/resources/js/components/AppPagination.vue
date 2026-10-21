<script setup lang="ts">
import { computed } from 'vue';
import type { ControlSize } from '../runa/ui/controls';
import AppButton from './AppButton.vue';
const props = defineProps<{
  modelValue: number;
  pages: number;
  label: string;
  previousLabel: string;
  nextLabel: string;
  disabled?: boolean;
  size?: ControlSize;
}>();
const emit = defineEmits<{ 'update:modelValue': [value: number] }>();
const count = computed(() =>
  Number.isFinite(props.pages) ? Math.max(1, Math.floor(props.pages)) : 1,
);
const page = computed(() =>
  Number.isFinite(props.modelValue)
    ? Math.max(1, Math.min(count.value, Math.floor(props.modelValue)))
    : 1,
);
const visible = computed(() =>
  [...new Set([1, page.value - 1, page.value, page.value + 1, count.value])]
    .filter((n) => n >= 1 && n <= count.value)
    .sort((a, b) => a - b),
);
</script>
<template>
  <nav :aria-label="label" class="flex flex-wrap items-center gap-1">
    <AppButton
      :size="size ?? 'md'"
      :disabled="disabled || page === 1"
      :aria-label="previousLabel"
      @click="emit('update:modelValue', page - 1)"
      >‹</AppButton
    ><template v-for="(value, index) in visible" :key="value"
      ><span
        v-if="index && value > visible[index - 1]! + 1"
        aria-hidden="true"
        class="px-1 text-muted"
        >…</span
      ><AppButton
        :size="size ?? 'md'"
        :disabled="disabled"
        :aria-label="`${label}: ${value}`"
        :aria-current="page === value ? 'page' : undefined"
        :variant="page === value ? 'soft' : 'ghost'"
        @click="emit('update:modelValue', value)"
        >{{ value }}</AppButton
      ></template
    ><AppButton
      :size="size ?? 'md'"
      :disabled="disabled || page === count"
      :aria-label="nextLabel"
      @click="emit('update:modelValue', page + 1)"
      >›</AppButton
    >
  </nav>
</template>

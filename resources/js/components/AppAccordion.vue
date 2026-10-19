<script setup lang="ts">
import { useId } from 'vue';
withDefaults(
  defineProps<{ modelValue: boolean; label: string; disabled?: boolean }>(),
  { disabled: false },
);
const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>();
const id = useId();
</script>
<template>
  <section
    class="rounded border border-line bg-[var(--runa-container-body-background)] text-sm"
  >
    <h3>
      <button
        type="button"
        class="flex min-h-11 w-full items-center justify-between gap-3 rounded bg-[var(--runa-surface-raised)] text-ink p-4 text-left font-semibold transition-colors hover:bg-[var(--runa-surface-hover)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--runa-focus-ring)] disabled:cursor-not-allowed disabled:opacity-50"
        :disabled="disabled"
        :aria-expanded="modelValue"
        :aria-controls="id"
        @click="emit('update:modelValue', !modelValue)"
      >
        {{ label }}<span aria-hidden="true">{{ modelValue ? '−' : '+' }}</span>
      </button>
    </h3>
    <div :id="id" :hidden="!modelValue" class="border-t border-line p-4">
      <slot />
    </div>
  </section>
</template>

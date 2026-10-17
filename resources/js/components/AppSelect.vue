<script setup lang="ts">
defineProps<{
  label: string;
  modelValue: string;
  options: readonly { value: string; label: string }[];
}>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
function update(event: Event) {
  if (event.target instanceof HTMLSelectElement)
    emit('update:modelValue', event.target.value);
}
</script>

<template>
  <label class="flex flex-col gap-1.5 text-xs font-medium text-muted">
    {{ label }}
    <select
      :value="modelValue"
      class="min-h-11 rounded border border-control bg-input px-3 py-2 text-sm text-ink hover:border-primary"
      @change="update"
    >
      <option
        v-for="option in options"
        :key="option.value"
        :value="option.value"
      >
        {{ option.label }}
      </option>
    </select>
  </label>
</template>

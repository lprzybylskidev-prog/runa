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
    <span class="app-select-wrap"
      ><select
        :value="modelValue"
        class="runa-field-control app-select"
        @change="update"
      >
        <option
          v-for="option in options"
          :key="option.value"
          :value="option.value"
        >
          {{ option.label }}
        </option>
      </select></span
    >
  </label>
</template>

<style scoped>
.app-select-wrap {
  position: relative;
  display: block;
}
.app-select {
  padding-right: 36px;
  cursor: pointer;
}
.app-select-wrap::after {
  content: '';
  position: absolute;
  right: 16px;
  top: 50%;
  width: 7px;
  height: 7px;
  border-right: 1.5px solid currentColor;
  border-bottom: 1.5px solid currentColor;
  transform: translateY(-70%) rotate(45deg);
  pointer-events: none;
}
</style>

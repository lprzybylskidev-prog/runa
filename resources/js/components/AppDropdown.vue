<script setup lang="ts">
import AppPopover from './AppPopover.vue';
import AppButton from './AppButton.vue';
defineProps<{ modelValue: boolean; label: string; disabled?: boolean }>();
const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>();
</script>
<template>
  <AppPopover
    :model-value="modelValue"
    :label="label"
    @update:model-value="emit('update:modelValue', $event)"
    ><template #trigger="{ toggle, open, panelId }"
      ><AppButton
        :disabled="disabled"
        :aria-expanded="open"
        :aria-controls="open ? panelId : undefined"
        aria-haspopup="dialog"
        @click="toggle"
        >{{ label }}<span aria-hidden="true" class="ml-2">⌄</span></AppButton
      ></template
    ><template #default="{ close }"><slot :close="close" /></template
  ></AppPopover>
</template>

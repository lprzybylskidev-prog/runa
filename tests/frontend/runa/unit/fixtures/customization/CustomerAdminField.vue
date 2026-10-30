<script setup lang="ts">
import { TextInput } from '@/runa/ui';
import type { AdminFieldEmits, AdminFieldProps } from '@/runa/admin';
defineProps<AdminFieldProps>();
const emit = defineEmits<AdminFieldEmits>();
</script>
<template>
  <TextInput
    :label="'Customer ' + field.label"
    :model-value="typeof modelValue === 'string' ? modelValue : null"
    :error="errors"
    :readonly="readonly"
    :disabled="busy"
    @update:model-value="emit('update:modelValue', $event)"
  />
</template>

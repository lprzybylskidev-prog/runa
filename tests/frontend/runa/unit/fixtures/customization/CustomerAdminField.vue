<script setup lang="ts">
import { useAdminLocale } from '@/runa/admin';
const { t } = useAdminLocale();
import { TextInput } from '@/runa/ui';
import type { AdminFieldEmits, AdminFieldProps } from '@/runa/admin';
defineProps<AdminFieldProps>();
const emit = defineEmits<AdminFieldEmits>();
</script>
<template>
  <TextInput
    :label="'Customer ' + t(field.label)"
    :model-value="typeof modelValue === 'string' ? modelValue : null"
    :error="errors.map(t)"
    :readonly="readonly"
    :disabled="busy"
    @update:model-value="emit('update:modelValue', $event)"
  />
</template>

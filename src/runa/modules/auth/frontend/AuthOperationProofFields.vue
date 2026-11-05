<script setup lang="ts">
import { computed } from 'vue';
import { Button, TextInput } from '@/runa/ui';
import type { OperationProofController } from './useOperationProof';
import { operationProofCopy } from './operationProofCopy';
import { useLocale } from '../../../resources/js/composables/useLocale';
const props = defineProps<{
  controller: OperationProofController;
  locale?: 'pl' | 'en' | undefined;
  submit?: boolean;
}>();
const emit = defineEmits<{ confirm: []; cancel: [] }>();
const { locale: applicationLocale } = useLocale();
function setPassword(value: string | null) {
  const password = props.controller.password;
  password.value = value ?? '';
}
const copy = computed(() =>
  operationProofCopy(props.locale ?? applicationLocale.value),
);
</script>
<template>
  <div class="space-y-4">
    <TextInput
      :model-value="controller.password.value"
      type="password"
      name="current_password"
      autocomplete="current-password"
      :label="copy.password"
      :disabled="controller.busy.value"
      @update:model-value="setPassword"
    />
    <div class="flex flex-wrap gap-2">
      <Button
        intent="commit"
        :type="submit ? 'submit' : 'button'"
        :busy="controller.busy.value"
        @click="!submit && emit('confirm')"
        >{{ copy.confirm }}</Button
      >
      <Button
        intent="cancel"
        type="button"
        variant="outline"
        :disabled="controller.busy.value"
        @click="emit('cancel')"
        >{{ copy.cancel }}</Button
      >
    </div>
  </div>
</template>

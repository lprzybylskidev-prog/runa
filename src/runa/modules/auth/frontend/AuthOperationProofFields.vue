<script setup lang="ts">
import { computed } from 'vue';
import { Button, RadioGroup, TextInput } from '@/runa/ui';
import type { OperationProofController } from './useOperationProof';
import { operationProofCopy } from './operationProofCopy';
import { useLocale } from '../../../resources/js/composables/useLocale';
const props = defineProps<{
  controller: OperationProofController;
  locale?: 'pl' | 'en' | undefined;
  submit?: boolean;
}>();
const emit = defineEmits<{ confirm: []; cancel: [] }>();
const { locale } = useLocale();
const copy = computed(() => operationProofCopy(props.locale ?? locale.value));
</script>
<template>
  <div class="space-y-4">
    <TextInput
      v-if="['password', 'both'].includes(controller.requirement.value)"
      :model-value="controller.password.value"
      type="password"
      name="current_password"
      autocomplete="current-password"
      :label="copy.password"
      :disabled="controller.busy.value"
      @update:model-value="controller.password.value = $event ?? ''"
    />
    <template
      v-if="['second_factor', 'both'].includes(controller.requirement.value)"
    >
      <RadioGroup
        :model-value="controller.method.value"
        :label="copy.method"
        :disabled="controller.busy.value"
        :options="
          controller.methods.value.map((value) => ({
            value,
            label: copy[value],
          }))
        "
        @update:model-value="
          controller.method.value = $event as 'totp' | 'email' | 'recovery'
        "
      />
      <TextInput
        :model-value="controller.code.value"
        :label="copy.code"
        name="verification_code"
        autocomplete="one-time-code"
        :disabled="controller.busy.value"
        @update:model-value="controller.code.value = $event ?? ''"
      />
      <Button
        type="button"
        v-if="controller.method.value === 'email'"
        :disabled="controller.busy.value"
        @click="controller.sendEmail"
        >{{ copy.send }}</Button
      >
    </template>
    <div class="flex flex-wrap gap-2">
      <Button
        :type="submit ? 'submit' : 'button'"
        :busy="controller.busy.value"
        @click="!submit && emit('confirm')"
        >{{ copy.confirm }}</Button
      >
      <Button
        type="button"
        :disabled="controller.busy.value"
        @click="emit('cancel')"
        >{{ copy.cancel }}</Button
      >
    </div>
  </div>
</template>

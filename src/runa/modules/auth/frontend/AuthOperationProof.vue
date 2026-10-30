<script setup lang="ts">
import { Alert, Button, FormSection, RadioGroup, TextInput } from '@/runa/ui';
import type { OperationProofController } from './useOperationProof';
defineProps<{ controller: OperationProofController }>();
</script>
<template>
  <div v-if="controller.requirement.value !== 'none'" class="space-y-3">
    <Button
      v-if="!controller.open.value"
      :disabled="controller.busy.value"
      @click="controller.begin"
    >
      {{
        controller.confirmed.value
          ? 'Confirm a fresh operation proof'
          : 'Confirm required operation proof'
      }}
    </Button>
    <FormSection v-else title="Operation proof" :columns="1">
      <TextInput
        v-if="['password', 'both'].includes(controller.requirement.value)"
        :model-value="controller.password.value"
        type="password"
        autocomplete="current-password"
        label="Current password"
        :disabled="controller.busy.value"
        @update:model-value="controller.password.value = $event ?? ''"
      />
      <template
        v-if="['second_factor', 'both'].includes(controller.requirement.value)"
      >
        <RadioGroup
          :model-value="controller.method.value"
          label="Verification method"
          :disabled="controller.busy.value"
          :options="
            controller.methods.value.map((value) => ({
              value,
              label:
                value === 'totp'
                  ? 'Authenticator'
                  : value === 'email'
                    ? 'Email'
                    : 'Recovery code',
            }))
          "
          @update:model-value="
            controller.method.value = $event as 'totp' | 'email' | 'recovery'
          "
        />
        <TextInput
          :model-value="controller.code.value"
          label="Verification code"
          autocomplete="one-time-code"
          :disabled="controller.busy.value"
          @update:model-value="controller.code.value = $event ?? ''"
        />
        <Button
          v-if="controller.method.value === 'email'"
          :disabled="controller.busy.value"
          @click="controller.sendEmail"
          >Send code</Button
        >
      </template>
      <div class="flex flex-wrap gap-2">
        <Button :busy="controller.busy.value" @click="controller.confirm"
          >Confirm proof</Button
        >
        <Button :disabled="controller.busy.value" @click="controller.reset"
          >Cancel proof</Button
        >
      </div>
    </FormSection>
    <p
      v-if="controller.confirmed.value"
      role="status"
      class="text-sm text-muted"
    >
      Proof confirmed. Submit the operation explicitly.
    </p>
    <Alert v-if="controller.error.value" tone="warning">{{
      controller.error.value
    }}</Alert>
  </div>
</template>

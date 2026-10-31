<script setup lang="ts">
import { computed } from 'vue';
import { Alert, Button, FormSection } from '@/runa/ui';
import type { OperationProofController } from './useOperationProof';
import AuthOperationProofFields from './AuthOperationProofFields.vue';
import { useLocale } from '../../../resources/js/composables/useLocale';
import { operationProofCopy } from './operationProofCopy';
const props = defineProps<{
  controller: OperationProofController;
  locale?: 'pl' | 'en' | undefined;
}>();
const { locale } = useLocale();
const copy = computed(() => operationProofCopy(props.locale ?? locale.value));
</script>
<template>
  <div v-if="controller.requirement.value !== 'none'" class="space-y-3">
    <Button
      v-if="!controller.open.value"
      :disabled="controller.busy.value"
      @click="controller.begin"
    >
      {{ controller.confirmed.value ? copy.fresh : copy.open }}
    </Button>
    <FormSection v-else :title="copy.title" :columns="1">
      <AuthOperationProofFields
        :controller="controller"
        :locale="props.locale"
        @confirm="controller.confirm"
        @cancel="controller.reset"
      />
    </FormSection>
    <p
      v-if="controller.confirmed.value"
      role="status"
      class="text-sm text-muted"
    >
      {{ copy.confirmed }}
    </p>
    <Alert v-if="controller.error.value" tone="warning">{{
      controller.error.value
    }}</Alert>
  </div>
</template>

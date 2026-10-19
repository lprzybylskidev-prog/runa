<script setup lang="ts">
import { activateMotionElement, deactivateMotionElement } from '../lib/motion';
import { computed } from 'vue';
import AppAlert from './AppAlert.vue';
import AppButton from './AppButton.vue';
import AppProgress from './AppProgress.vue';
import type { OperationController } from '../composables/useOperation';
import { downloadExport } from '../lib/exportFiles';
const props = withDefaults(
  defineProps<{ operation: OperationController; english?: boolean }>(),
  { english: false },
);
const state = computed(() => props.operation.status.value);
const labels = computed(() =>
  props.english
    ? {
        queued: 'Queued',
        running: 'Processing',
        completed: 'Completed',
        failed: 'Operation failed',
        partial: 'Partially completed',
        cancelled: 'Cancelled',
      }
    : {
        queued: 'W kolejce',
        running: 'Przetwarzanie',
        completed: 'Gotowe',
        failed: 'Operacja nie powiodła się',
        partial: 'Częściowo zakończono',
        cancelled: 'Anulowano',
      },
);
const errorText = computed(() => {
  const current = state.value;
  if (!current || (current.state !== 'failed' && current.state !== 'partial'))
    return '';
  const code = current.error.code;
  if (code === 'forbidden')
    return props.english
      ? 'You do not have permission.'
      : 'Brak uprawnień do wykonania operacji.';
  if (code === 'export_limit')
    return props.english
      ? 'This result requires a server export. Narrow your selection.'
      : 'Ten wynik wymaga eksportu serwerowego. Zawęź zaznaczenie.';
  if (code === 'selection_stale')
    return props.english
      ? 'Results changed. Select the rows again.'
      : 'Wyniki zmieniły się. Zaznacz wiersze ponownie.';
  if (code === 'cancel_failed')
    return props.english
      ? 'Cancellation was not confirmed. Retry to check the operation.'
      : 'Nie potwierdzono anulowania. Ponów, aby sprawdzić operację.';
  return props.english
    ? 'Try again or contact the administrator.'
    : 'Spróbuj ponownie lub skontaktuj się z administratorem.';
});
</script>
<template>
  <Transition
    name="runa-feedback"
    @before-enter="activateMotionElement"
    @before-leave="deactivateMotionElement"
  >
    <AppAlert
      v-if="state"
      :tone="
        state.state === 'failed'
          ? 'danger'
          : state.state === 'partial'
            ? 'warning'
            : state.state === 'completed'
              ? 'success'
              : 'neutral'
      "
      :title="labels[state.state]"
    >
      <p v-if="errorText" class="text-xs">{{ errorText }}</p>
      <p v-if="state.state === 'partial'" class="text-xs">
        {{ english ? 'Succeeded' : 'Wykonano' }}: {{ state.succeeded }} ·
        {{ english ? 'Failed' : 'Błędy' }}: {{ state.failed }}
      </p>
      <p
        v-if="state.state === 'completed' && state.succeeded !== undefined"
        class="text-xs"
      >
        {{ english ? 'Succeeded' : 'Wykonano' }}: {{ state.succeeded }}
      </p>
      <AppProgress
        v-if="state.state === 'queued' || state.state === 'running'"
        :label="labels[state.state]"
        :value="state.state === 'running' ? (state.progress ?? null) : null"
      />
      <div class="mt-2 flex flex-wrap gap-2">
        <AppButton
          v-if="operation.canCancel.value"
          size="sm"
          @click="operation.cancel()"
          >{{ english ? 'Cancel' : 'Anuluj' }}</AppButton
        >
        <AppButton
          v-if="operation.canRetry.value"
          size="sm"
          @click="operation.retry()"
          >{{ english ? 'Retry' : 'Spróbuj ponownie' }}</AppButton
        >
        <AppButton
          v-if="operation.download.value"
          size="sm"
          @click="downloadExport(operation.download.value)"
          >{{ english ? 'Download' : 'Pobierz' }}</AppButton
        >
      </div>
    </AppAlert>
  </Transition>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Alert } from '@/runa/ui';
import type { AdminOperationData } from './useAdminOperations';
const props = defineProps<{ result: AdminOperationData }>();
const detail = computed(() => {
  const value = props.result.transition ?? props.result.work;
  return value && typeof value === 'object' && !Array.isArray(value)
    ? value
    : props.result;
});
const status = computed(() =>
  String(detail.value.status ?? detail.value.health ?? 'Response received'),
);
const failed = computed(() =>
  ['failed', 'refused', 'recovery_required', 'unhealthy', 'unknown'].includes(
    status.value,
  ),
);
const identity = computed(
  () => detail.value.operationId ?? detail.value.workId,
);
</script>
<template>
  <Alert :tone="failed ? 'warning' : 'info'">
    <dl class="space-y-2 text-sm" aria-live="polite">
      <div>
        <dt class="font-semibold">Current outcome</dt>
        <dd class="break-words">{{ status.replaceAll('_', ' ') }}</dd>
      </div>
      <div v-if="identity">
        <dt class="font-semibold">Operation UUID</dt>
        <dd class="break-all">{{ identity }}</dd>
      </div>
      <div v-if="detail.module || detail.moduleId">
        <dt class="font-semibold">Module</dt>
        <dd class="break-words">{{ detail.module ?? detail.moduleId }}</dd>
      </div>
      <div v-if="detail.generation !== undefined && detail.generation !== null">
        <dt class="font-semibold">Registry generation</dt>
        <dd>{{ detail.generation }}</dd>
      </div>
      <div v-if="detail.progress">
        <dt class="font-semibold">Progress</dt>
        <dd class="break-words">{{ detail.progress }}</dd>
      </div>
      <div v-if="detail.cancellationRequested">
        <dt class="font-semibold">Cancellation</dt>
        <dd>Requested; completion is not confirmed.</dd>
      </div>
      <div v-if="Array.isArray(detail.reasons) && detail.reasons.length">
        <dt class="font-semibold">Reasons</dt>
        <dd class="break-words">{{ detail.reasons.join(', ') }}</dd>
      </div>
    </dl>
  </Alert>
</template>

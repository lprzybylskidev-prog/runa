<script setup lang="ts">
import { useAdminLocale } from './localization';
const { locale, t: tr } = useAdminLocale();
import { computed } from 'vue';
import { adminReasonMessage } from './operationMessages';
import type { FeedbackTone } from '@/runa/ui';
import { Alert, getModulePresentation } from '@/runa/ui';
import type { AdminOperationData } from './useAdminOperations';
const props = defineProps<{ result: AdminOperationData }>();
const detail = computed(() => {
  const value = props.result.transition ?? props.result.work;
  return value && typeof value === 'object' && !Array.isArray(value)
    ? value
    : props.result;
});
const status = computed(() =>
  String(detail.value.status ?? detail.value.health ?? tr('Response received')),
);
const diagnostic = computed(
  () =>
    typeof props.result.health === 'string' &&
    !props.result.transition &&
    !props.result.work,
);
const tone = computed<FeedbackTone>(() => {
  if (['failed', 'refused', 'unhealthy'].includes(status.value))
    return 'danger';
  if (
    detail.value.blocked ||
    ['recovery_required', 'unknown', 'degraded'].includes(status.value)
  )
    return 'warning';
  if (['healthy', 'succeeded', 'committed'].includes(status.value))
    return 'success';
  return 'info';
});
const reasons = computed(() =>
  Array.isArray(detail.value.reasons)
    ? detail.value.reasons
        .filter((value): value is string => typeof value === 'string')
        .map((value) => tr(adminReasonMessage(value)))
    : [],
);
const progress = computed(() => {
  const value = detail.value.progress;
  if (typeof value === 'string' || typeof value === 'number')
    return [String(value)];
  if (!value || typeof value !== 'object') return [];
  return Object.entries(value).flatMap(([key, value]) => {
    if (typeof value !== 'string' && typeof value !== 'number') return [];
    const message = tr(adminReasonMessage(String(value)));
    return [
      /^[0-9]+$/.test(key)
        ? message
        : getModulePresentation(key).name[locale.value] + ': ' + message,
    ];
  });
});
const identity = computed(
  () => detail.value.operationId ?? detail.value.workId,
);
</script>
<template>
  <Alert :tone="tone">
    <dl class="space-y-2 text-sm" aria-live="polite">
      <div>
        <dt class="font-semibold">
          {{ tr(diagnostic ? 'Diagnostic result' : 'Current outcome') }}
        </dt>
        <dd class="break-words">{{ tr(status) }}</dd>
      </div>
      <div v-if="identity">
        <dt class="font-semibold">{{ tr('Operation UUID') }}</dt>
        <dd class="break-all">{{ identity }}</dd>
      </div>
      <div v-if="!diagnostic && (detail.module || detail.moduleId)">
        <dt class="font-semibold">{{ tr('Module') }}</dt>
        <dd class="break-words">{{ detail.module ?? detail.moduleId }}</dd>
      </div>
      <div v-if="detail.generation !== undefined && detail.generation !== null">
        <dt class="font-semibold">{{ tr('Registry generation') }}</dt>
        <dd>{{ detail.generation }}</dd>
      </div>
      <div v-if="progress.length">
        <dt class="font-semibold">{{ tr('Progress') }}</dt>
        <dd>
          <ul class="space-y-1">
            <li v-for="(item, index) in progress" :key="index">{{ item }}</li>
          </ul>
        </dd>
      </div>
      <div v-if="detail.cancellationRequested">
        <dt class="font-semibold">{{ tr('Cancellation') }}</dt>
        <dd>{{ tr('Requested; completion is not confirmed.') }}</dd>
      </div>
      <div v-if="reasons.length">
        <dt class="font-semibold">{{ tr('Reasons') }}</dt>
        <dd>
          <ul class="space-y-1">
            <li v-for="reason in reasons" :key="reason">{{ reason }}</li>
          </ul>
        </dd>
      </div>
    </dl>
  </Alert>
</template>

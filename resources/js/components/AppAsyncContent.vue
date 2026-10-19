<script setup lang="ts">
import AppSpinner from './AppSpinner.vue';
import AppAlert from './AppAlert.vue';
import AppButton from './AppButton.vue';
import AppEmptyState from './AppEmptyState.vue';
import type { AsyncContentState } from '../runa/ui/loading';
withDefaults(
  defineProps<{
    state: AsyncContentState;
    loadingLabel?: string;
    errorTitle?: string;
    errorMessage?: string;
    emptyTitle?: string;
    emptyDescription?: string;
    retryLabel?: string;
  }>(),
  {
    errorMessage: '',
    emptyDescription: '',
    retryLabel: '',
    loadingLabel: 'Loading',
    errorTitle: 'Unable to load content',
    emptyTitle: 'No results',
  },
);
const emit = defineEmits<{ retry: [] }>();
</script>
<template>
  <div :aria-busy="state === 'loading'" class="min-w-0">
    <div v-if="state === 'loading'">
      <slot name="loading"
        ><div class="flex items-center gap-3 py-6 text-sm text-muted">
          <AppSpinner :label="loadingLabel" /><span aria-hidden="true">{{
            loadingLabel
          }}</span>
        </div></slot
      >
    </div>
    <div v-else-if="state === 'error'">
      <slot name="error" :retry="() => emit('retry')"
        ><AppAlert tone="danger" :title="errorTitle"
          >{{ errorMessage
          }}<template v-if="retryLabel" #actions
            ><AppButton @click="emit('retry')">{{
              retryLabel
            }}</AppButton></template
          ></AppAlert
        ></slot
      >
    </div>
    <div v-else-if="state === 'empty'" role="status">
      <slot name="empty"
        ><AppEmptyState
          :title="emptyTitle"
          :description="emptyDescription ?? ''"
      /></slot>
    </div>
    <div v-else>
      <slot name="success"><slot /></slot>
    </div>
  </div>
</template>

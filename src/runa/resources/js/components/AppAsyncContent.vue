<script setup lang="ts">
import { useLocale } from '../composables/useLocale';
import { useContentMotion } from '../composables/useContentMotion';
import { computed, ref } from 'vue';
import AppContentLoading from './AppContentLoading.vue';
import AppAlert from './AppAlert.vue';
import AppButton from './AppButton.vue';
import AppEmptyState from './AppEmptyState.vue';
import type { ControlSize } from '../runa/ui/controls';
import type { AsyncContentState } from '../runa/ui/loading';
const props = withDefaults(
  defineProps<{
    state: AsyncContentState;
    actionSize?: ControlSize;
    loadingLabel?: string;
    errorTitle?: string;
    errorMessage?: string;
    emptyTitle?: string;
    emptyDescription?: string;
    retryLabel?: string;
  }>(),
  {
    actionSize: 'md',
    errorMessage: '',
    emptyDescription: '',
    retryLabel: '',
    loadingLabel: '',
    errorTitle: '',
    emptyTitle: '',
  },
);
const emit = defineEmits<{ retry: [] }>();
const content = ref<globalThis.HTMLElement>();
useContentMotion(content, () =>
  props.state === 'refreshing' ? 'success' : props.state,
);
const { t } = useLocale();
const loadingLabel = computed(() => props.loadingLabel || t('loading'));
const errorTitle = computed(() => props.errorTitle || t('unableToLoadContent'));
const emptyTitle = computed(() => props.emptyTitle || t('noResults'));
</script>
<template>
  <div
    ref="content"
    :aria-busy="state === 'loading' || state === 'refreshing'"
    class="min-w-0"
  >
    <div v-if="state === 'loading'">
      <slot name="loading"><AppContentLoading :label="loadingLabel" /></slot>
    </div>
    <div v-else-if="state === 'error'">
      <slot name="error" :retry="() => emit('retry')"
        ><AppAlert tone="danger" :title="errorTitle"
          >{{ errorMessage
          }}<template v-if="retryLabel" #actions
            ><AppButton
              intent="inspect"
              :size="actionSize"
              @click="emit('retry')"
              >{{ retryLabel }}</AppButton
            ></template
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
    <AppContentLoading
      v-else
      :loading="false"
      :refreshing="state === 'refreshing'"
      :label="loadingLabel"
    >
      <slot name="success"><slot /></slot>
    </AppContentLoading>
  </div>
</template>

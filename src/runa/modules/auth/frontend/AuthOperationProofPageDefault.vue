<script setup lang="ts">
import { computed, onMounted, nextTick, ref, toRef } from 'vue';
import { MinimalLayout, Form, FormNotifications } from '@/runa/ui';
import {
  useLocale,
  provideLocale,
} from '../../../resources/js/composables/useLocale';
import { useAuthPresentation } from './presentation';
import { operationProofCopy } from './operationProofCopy';
import AuthOperationProofFields from './AuthOperationProofFields.vue';
import type {
  AuthOperationProofPageProps,
  AuthOperationProofPageSlots,
  AuthOperationProofPageEmits,
} from './contracts/presentation';
const props = defineProps<AuthOperationProofPageProps>();
defineSlots<AuthOperationProofPageSlots>();
const emit = defineEmits<AuthOperationProofPageEmits>();
const presentation = useAuthPresentation();
provideLocale(toRef(props, 'locale'));
const { locale } = useLocale(toRef(props, 'locale'));
const copy = computed(() => operationProofCopy(props.locale ?? locale.value));
const heading = ref<HTMLElement>();
onMounted(() => {
  if (!props.controller.open.value && !props.controller.confirmed.value)
    void props.controller.begin();
  void nextTick(() => heading.value?.focus());
});
async function confirm() {
  if (props.controller.busy.value || props.controller.confirmed.value) return;
  await props.controller.confirm();
  if (props.controller.confirmed.value) emit('confirmed');
}
function cancel() {
  props.controller.reset();
  emit('cancel');
}
</script>
<template>
  <component
    :is="presentation.minimalLayout ?? MinimalLayout"
    :home-href="homeHref ?? '/'"
  >
    <div class="space-y-6">
      <header class="space-y-2">
        <h1
          ref="heading"
          tabindex="-1"
          class="auth-heading text-2xl font-semibold text-ink"
        >
          {{ title ?? copy.title }}
        </h1>
        <p class="text-sm text-muted">{{ description ?? copy.description }}</p>
      </header>
      <Form
        :busy="controller.busy.value"
        novalidate
        class="space-y-4"
        @submit.prevent="confirm"
      >
        <slot :controller="controller"
          ><AuthOperationProofFields
            :controller="controller"
            :locale="props.locale"
            submit
            @cancel="cancel"
        /></slot>
        <FormNotifications
          :error="controller.error.value"
          :locale="props.locale ?? locale"
        />
        <p
          v-if="controller.confirmed.value"
          role="status"
          class="text-sm text-muted"
        >
          {{ copy.confirmed }}
        </p>
      </Form>
    </div>
  </component>
</template>

<style scoped>
/* A programmatic reading target, not an interactive/tab-stop control. */
.auth-heading:focus {
  outline: none;
}
</style>

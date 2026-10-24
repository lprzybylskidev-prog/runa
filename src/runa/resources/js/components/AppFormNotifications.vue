<script setup lang="ts">
import { watch } from 'vue';
import AppToastViewport from './AppToastViewport.vue';
import { useToasts } from '../composables/useToasts';
const props = withDefaults(
  defineProps<{
    error?: string;
    success?: string;
    invalid?: boolean;
    locale?: 'pl' | 'en';
    attempt?: number;
  }>(),
  { error: '', success: '', invalid: false, locale: 'pl', attempt: 0 },
);
const { messages, show, dismiss } = useToasts();
let current: string | undefined;
watch(
  () => [props.error, props.success, props.invalid, props.attempt] as const,
  ([error, success, invalid]) => {
    const result =
      error || invalid
        ? {
            title:
              error ||
              (props.locale === 'pl'
                ? 'Sprawdź zaznaczone pola formularza.'
                : 'Check the highlighted form fields.'),
            description: error && success ? success : '',
            tone: 'danger' as const,
            duration: 0,
          }
        : success
          ? { title: success, tone: 'success' as const, duration: 5000 }
          : undefined;
    if (!result) {
      if (current) dismiss(current);
      current = undefined;
    } else if (
      current &&
      messages.value.some((message) => message.id === current)
    ) {
      // Replace the current outcome in place, without a second entering/exiting toast.
      messages.value = [{ ...result, id: current }];
    } else current = show(result);
  },
  { immediate: true },
);
</script>
<template>
  <AppToastViewport
    :messages="messages"
    :label="locale === 'pl' ? 'Powiadomienia' : 'Notifications'"
    :close-label="locale === 'pl' ? 'Zamknij' : 'Close'"
    @dismiss="dismiss"
  >
    <template v-if="(error || invalid) && $slots.actions" #actions
      ><slot name="actions"
    /></template>
  </AppToastViewport>
</template>

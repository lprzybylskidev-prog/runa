<script setup lang="ts">
import { nextTick } from 'vue';
import AppAlert from './AppAlert.vue';
import AppButton from './AppButton.vue';
withDefaults(
  defineProps<{
    current: string;
    locale?: 'pl' | 'en';
    busy?: boolean;
    allowKeep?: boolean;
  }>(),
  { locale: 'pl', busy: false, allowKeep: true },
);
const emit = defineEmits<{ accept: []; keep: [] }>();
async function resolve(keep = false) {
  const form = document.activeElement?.closest('form');
  if (keep) emit('keep');
  else emit('accept');
  await nextTick();
  if (form?.isConnected && document.activeElement === document.body)
    form
      .querySelector<globalThis.HTMLElement>(
        'input:not([type=hidden]):not(:disabled):not([readonly]), textarea:not(:disabled):not([readonly]), button[type=submit]:not(:disabled)',
      )
      ?.focus();
}
</script>
<template>
  <AppAlert
    tone="warning"
    :title="
      locale === 'pl'
        ? 'Te dane zmieniły się podczas edycji'
        : 'These values changed while you were editing'
    "
    data-form-conflict
  >
    <p>
      {{
        locale === 'pl'
          ? 'Twoje zmiany są zachowane. Aktualne dane:'
          : 'Your edits are preserved. Current values:'
      }}
    </p>
    <p class="whitespace-pre-wrap [overflow-wrap:anywhere]">{{ current }}</p>
    <slot />
    <div class="mt-3 flex flex-wrap gap-2">
      <AppButton :disabled="busy" @click="resolve()">{{
        locale === 'pl' ? 'Użyj aktualnych danych' : 'Use current values'
      }}</AppButton>
      <AppButton v-if="allowKeep" :disabled="busy" @click="resolve(true)">{{
        locale === 'pl' ? 'Zachowaj moje zmiany' : 'Keep my edits'
      }}</AppButton>
    </div>
  </AppAlert>
</template>

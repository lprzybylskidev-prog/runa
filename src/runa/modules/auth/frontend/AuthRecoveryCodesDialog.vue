<script setup lang="ts">
import { Dialog, Button } from '@/runa/ui';
import { ref, watch, nextTick } from 'vue';
const props = withDefaults(
  defineProps<{
    codes: readonly string[];
    locale?: 'pl' | 'en';
    busy?: boolean;
  }>(),
  { locale: 'pl', busy: false },
);
defineEmits<{ acknowledge: [] }>();
const dialog = ref<{ focus: () => void }>();
watch(
  () => props.busy,
  async (busy) => {
    if (!busy && props.codes.length) {
      await nextTick();
      dialog.value?.focus();
    }
  },
  { flush: 'post' },
);
</script>
<template>
  <Dialog
    ref="dialog"
    :model-value="codes.length > 0"
    :title="locale === 'pl' ? 'Kody odzyskiwania' : 'Recovery codes'"
    :locale="locale"
    :busy="busy"
    :closable="false"
    :close-on-escape="false"
    :close-on-outside="false"
    initial-focus="[data-recovery-acknowledge]"
    size="lg"
  >
    <div class="space-y-4 print:hidden">
      <p class="text-sm leading-7">
        {{
          locale === 'pl'
            ? 'Zapisz kody odzyskiwania w bezpiecznym miejscu. Każdego kodu możesz użyć tylko raz. Po zamknięciu tego okna nie wyświetlimy ich ponownie.'
            : 'Save recovery codes in a safe place. Each code can be used only once. They will not be shown again after closing this window.'
        }}
      </p>
      <ul
        :aria-label="locale === 'pl' ? 'Kody odzyskiwania' : 'Recovery codes'"
        class="grid min-w-0 gap-3 rounded border border-line bg-surface p-4 font-mono text-sm"
      >
        <li
          v-for="code in codes"
          :key="code"
          class="select-all [overflow-wrap:anywhere]"
          data-recovery-code
        >
          <code>{{ code }}</code>
        </li>
      </ul>
    </div>
    <template #footer>
      <Button
        variant="outline"
        :busy="busy"
        data-recovery-acknowledge
        @click="$emit('acknowledge')"
        >{{
          locale === 'pl' ? 'Kody zostały zapisane' : 'I saved the codes'
        }}</Button
      >
    </template>
  </Dialog>
</template>

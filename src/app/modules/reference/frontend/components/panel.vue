<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue';
import { Button, Card } from '@/runa/ui';
import { request } from '@/runa/http';

defineOptions({ name: 'ReferencePanel' });

const props = withDefaults(defineProps<{ locale?: 'pl' | 'en' }>(), {
  locale: 'pl',
});
const copy = computed(() =>
  props.locale === 'pl'
    ? {
        title: 'Moduł referencyjny',
        load: 'Sprawdź połączenie',
        busy: 'Sprawdzanie…',
        failed: 'Nie udało się odczytać odpowiedzi. Spróbuj ponownie.',
      }
    : {
        title: 'Reference module',
        load: 'Check connection',
        busy: 'Checking…',
        failed: 'The response could not be read. Try again.',
      },
);
const busy = ref(false);
const message = ref('');
const failed = ref(false);
let controller: AbortController | undefined;
onBeforeUnmount(() => controller?.abort());
async function load() {
  if (busy.value) return;
  controller = new AbortController();
  busy.value = true;
  failed.value = false;
  message.value = '';
  try {
    const result = await request('/app/reference/ping', {
      signal: controller.signal,
    });
    if (
      typeof result !== 'object' ||
      result === null ||
      !('message' in result) ||
      typeof result.message !== 'string'
    )
      throw new Error('Invalid response.');
    message.value = result.message;
  } catch {
    if (!controller.signal.aborted) failed.value = true;
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <Card>
    <template #header
      ><h2>{{ copy.title }}</h2></template
    >
    <div class="flex flex-col items-start gap-4" :aria-busy="busy">
      <Button :disabled="busy" @click="load">{{
        busy ? copy.busy : copy.load
      }}</Button>
      <p role="status" class="break-words text-muted">
        {{ failed ? copy.failed : message }}
      </p>
    </div>
  </Card>
</template>

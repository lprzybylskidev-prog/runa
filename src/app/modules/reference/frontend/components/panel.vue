<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue';
import { actionIcons, Button, Card, Form, FormNotifications } from '@/runa/ui';
import { request } from '@/runa/http';

defineOptions({ name: 'ReferencePanel' });

const props = withDefaults(defineProps<{ locale?: 'pl' | 'en' }>(), {
  locale: 'pl',
});
defineSlots<{
  default(props: {
    busy: boolean;
    message: string;
    load: () => Promise<void>;
  }): unknown;
}>();
const copy = computed(() =>
  props.locale === 'pl'
    ? {
        title: 'Moduł referencyjny',
        load: 'Sprawdź połączenie',
        failed: 'Nie udało się odczytać odpowiedzi. Spróbuj ponownie.',
        response: 'Odpowiedź API',
      }
    : {
        title: 'Reference module',
        load: 'Check connection',
        failed: 'The response could not be read. Try again.',
        response: 'API response',
      },
);
const busy = ref(false);
const message = ref('');
const failed = ref(false);
const attempt = ref(0);
let controller: AbortController | undefined;
onBeforeUnmount(() => controller?.abort());
async function load() {
  if (busy.value) return;
  controller = new AbortController();
  busy.value = true;
  attempt.value++;
  failed.value = false;
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
    if (!controller.signal.aborted) message.value = result.message;
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
    <Form :label="copy.title" :busy="busy" @submit="load">
      <template #feedback>
        <FormNotifications
          :locale="locale"
          :error="failed ? copy.failed : ''"
          :attempt="attempt"
        />
      </template>
      <slot :busy="busy" :message="message" :load="load">
        <dl v-if="message" class="min-w-0 space-y-1">
          <dt class="text-xs text-muted">{{ copy.response }}</dt>
          <dd role="status" class="break-words text-ink">{{ message }}</dd>
        </dl>
      </slot>
      <template #actions>
        <Button
          :icon="actionIcons.load"
          type="submit"
          intent="inspect"
          :busy="busy"
          >{{ copy.load }}</Button
        >
      </template>
    </Form>
  </Card>
</template>

<script setup lang="ts">
import { onMounted, onScopeDispose, watch } from 'vue';
import AppAlert from './AppAlert.vue';
import type { ToastMessage } from '../runa/ui/feedback';
const props = defineProps<{ message: ToastMessage; closeLabel: string }>();
const emit = defineEmits<{ close: [id: string] }>();
let remaining = props.message.duration;
let started = 0;
let timer: ReturnType<typeof globalThis.setTimeout> | undefined;
let hovered = false;
let focused = false;
let mounted = false;
function stop() {
  if (timer !== undefined) {
    globalThis.clearTimeout(timer);
    timer = undefined;
    remaining = Math.max(0, remaining - (Date.now() - started));
  }
}
function start() {
  if (
    !mounted ||
    hovered ||
    focused ||
    props.message.duration <= 0 ||
    timer !== undefined
  )
    return;
  started = Date.now();
  timer = globalThis.setTimeout(() => {
    timer = undefined;
    emit('close', props.message.id);
  }, remaining);
}
function hover(value: boolean) {
  hovered = value;
  if (value) stop();
  else start();
}
function focus(event: globalThis.FocusEvent) {
  if (event.type === 'focusin') {
    focused = true;
    stop();
  } else {
    focused =
      event.relatedTarget instanceof globalThis.Node &&
      event.currentTarget instanceof globalThis.HTMLElement &&
      event.currentTarget.contains(event.relatedTarget);
    if (!focused) start();
  }
}
watch(
  () =>
    [
      props.message.id,
      props.message.duration,
      props.message.title,
      props.message.description,
    ] as const,
  () => {
    stop();
    remaining = props.message.duration;
    start();
  },
);
onMounted(() => {
  mounted = true;
  start();
});
onScopeDispose(stop);
</script>
<template>
  <div
    class="app-toast"
    :data-toast-id="message.id"
    @pointerenter="hover(true)"
    @pointerleave="hover(false)"
    @focusin="focus"
    @focusout="focus"
  >
    <AppAlert
      :announce="false"
      :tone="message.tone"
      :title="message.title"
      dismissible
      :close-label="closeLabel"
      @close="emit('close', message.id)"
    >
      {{ message.description }}
      <template v-if="$slots.actions" #actions
        ><slot name="actions"
      /></template>
    </AppAlert>
  </div>
</template>
<style scoped>
.app-toast {
  pointer-events: auto;
  box-shadow: 0 8px 24px
    color-mix(
      in srgb,
      var(--runa-shadow) var(--runa-shadow-opacity),
      transparent
    );
}
</style>

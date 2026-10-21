<script setup lang="ts">
import { activateMotionElement, deactivateMotionElement } from '../lib/motion';
import { computed, provide, ref, toRef, useId } from 'vue';
import AppAlert from './AppAlert.vue';
import AppButton from './AppButton.vue';
import { useModalLayer } from '../composables/useModalLayer';
import { layerIndex, modalPortal } from '../lib/layers';
import type { DialogProps, DialogCloseReason } from '../runa/ui/dialogs';
defineOptions({ inheritAttrs: false });
const props = withDefaults(
  defineProps<DialogProps & { role?: 'dialog' | 'alertdialog' }>(),
  {
    description: '',
    size: 'md',
    busy: false,
    error: '',
    closeOnEscape: true,
    closeOnOutside: true,
    closable: true,
    initialFocus: '',
    locale: 'pl',
    role: 'dialog',
  },
);
const emit = defineEmits<{
  'update:modelValue': [value: boolean];
  close: [reason: DialogCloseReason];
}>();
const root = ref<globalThis.HTMLElement>();
const panel = ref<globalThis.HTMLElement>();
const key = Symbol('dialog');
const id = useId();
provide(modalPortal, root);
const zIndex = computed(() => 60 + Math.max(0, layerIndex(key)) * 10);
function close(reason: DialogCloseReason = 'close') {
  if (props.busy || !props.closable || !props.modelValue) return;
  if (
    (reason === 'escape' && !props.closeOnEscape) ||
    (reason === 'outside' && !props.closeOnOutside)
  )
    return;
  emit('close', reason);
  emit('update:modelValue', false);
}
const layer = useModalLayer({
  key,
  open: toRef(props, 'modelValue'),
  root,
  panel,
  initialFocus: () => props.initialFocus,
  escape: () => close('escape'),
});
let pressedOutside = false;
function down(event: globalThis.PointerEvent) {
  pressedOutside = layer.isTop() && event.target === event.currentTarget;
}
function up(event: globalThis.PointerEvent) {
  if (pressedOutside && event.target === event.currentTarget && layer.isTop())
    close('outside');
  pressedOutside = false;
}
function leaving(element: globalThis.Element) {
  deactivateMotionElement(element);
}
defineExpose({ focus: layer.focus });
</script>
<template>
  <Teleport to="body"
    ><Transition
      name="dialog-fade"
      @before-enter="activateMotionElement"
      @before-leave="leaving"
    >
      <div
        v-if="modelValue"
        ref="root"
        class="dialog-backdrop"
        :style="{ zIndex }"
        @pointerdown="down"
        @pointerup="up"
        @pointercancel="pressedOutside = false"
      >
        <section
          ref="panel"
          :role="role"
          aria-modal="true"
          :aria-labelledby="`${id}-title`"
          :aria-describedby="description ? `${id}-description` : undefined"
          :aria-busy="busy"
          tabindex="-1"
          class="dialog-panel"
          :class="`dialog-${size}`"
        >
          <header
            class="dialog-header"
            :class="{ 'dialog-header-title-only': !description }"
          >
            <div class="min-w-0">
              <h2
                :id="`${id}-title`"
                class="text-sm font-semibold [overflow-wrap:anywhere]"
              >
                {{ title }}
              </h2>
              <p
                v-if="description"
                :id="`${id}-description`"
                class="mt-2 text-xs leading-relaxed text-muted"
              >
                {{ description }}
              </p>
            </div>
            <AppButton
              v-if="closable"
              icon-only
              size="sm"
              :disabled="busy"
              :aria-label="locale === 'pl' ? 'Zamknij dialog' : 'Close dialog'"
              @click="close()"
              >×</AppButton
            >
          </header>
          <div data-dialog-content class="dialog-content">
            <fieldset :disabled="busy" class="min-w-0 space-y-4">
              <slot :close="close" />
            </fieldset>
            <AppAlert v-if="error" tone="danger" class="mt-4">{{
              error
            }}</AppAlert>
          </div>
          <footer v-if="$slots.footer" class="dialog-footer">
            <slot name="footer" :close="close" :busy="busy" />
          </footer>
        </section>
      </div> </Transition
  ></Teleport>
</template>
<style scoped>
.dialog-backdrop {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  background: color-mix(
    in srgb,
    var(--runa-overlay) var(--runa-overlay-opacity, 52%),
    transparent
  );
}
.dialog-panel {
  display: flex;
  flex-direction: column;
  width: 100%;
  max-height: calc(100dvh - 32px);
  border: 1px solid var(--runa-control-border);
  border-radius: 4px;
  background: var(--runa-container-body-background);
  color: var(--runa-text);
  box-shadow: 0 12px 40px
    color-mix(
      in srgb,
      var(--runa-shadow) var(--runa-shadow-opacity),
      transparent
    );
}
.dialog-sm {
  max-width: 380px;
}
.dialog-md {
  max-width: 560px;
}
.dialog-lg {
  max-width: 800px;
}
.dialog-xl {
  max-width: 1120px;
}
.dialog-full {
  max-width: none;
  height: calc(100dvh - 32px);
}
.dialog-header {
  display: flex;
  flex-shrink: 0;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 20px;
  background: var(--runa-surface-raised);
  border-bottom: 1px solid var(--runa-border);
  border-radius: 3px 3px 0 0;
}
.dialog-header-title-only {
  align-items: center;
}
.dialog-content {
  min-height: 0;
  overflow: auto;
  overscroll-behavior: contain;
  padding: 20px;
  font-size: 12px;
  line-height: 1.75;
}
.dialog-footer {
  display: flex;
  flex-shrink: 0;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 12px;
  padding: 16px 20px;
  background: var(--runa-surface-raised);
  border-top: 1px solid var(--runa-border);
  border-radius: 0 0 3px 3px;
}
.dialog-panel:focus-visible {
  outline: 2px solid var(--runa-focus-ring);
  outline-offset: 2px;
}
.dialog-fade-enter-active,
.dialog-fade-leave-active {
  transition: opacity 140ms ease-out;
}
.dialog-fade-enter-from,
.dialog-fade-leave-to {
  opacity: 0;
}
@media (prefers-reduced-motion: reduce) {
  .dialog-fade-enter-active,
  .dialog-fade-leave-active {
    transition: none;
  }
}
</style>

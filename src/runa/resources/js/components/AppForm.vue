<script setup lang="ts">
import { nextTick, ref, useId } from 'vue';
const props = withDefaults(
  defineProps<{
    busy?: boolean;
    valid?: boolean;
    dirty?: boolean;
    label?: string;
    description?: string;
    columns?: 1 | 2 | 3;
  }>(),
  {
    busy: false,
    valid: true,
    dirty: false,
    label: '',
    description: '',
    columns: 1,
  },
);
const emit = defineEmits<{ submit: [event: SubmitEvent]; invalid: [] }>();
const id = useId();
const form = ref<globalThis.HTMLFormElement>();
function focusInvalid() {
  const target =
    form.value?.querySelector<globalThis.HTMLElement>(
      '[aria-invalid="true"]',
    ) ??
    form.value?.querySelector<globalThis.HTMLElement>('[data-form-feedback]');
  target?.focus({ preventScroll: false });
}
async function submit(event: globalThis.SubmitEvent) {
  if (props.busy) return;
  if (props.valid) emit('submit', event);
  else emit('invalid');
  await nextTick();
  if (!props.busy && form.value?.querySelector('[aria-invalid="true"]'))
    focusInvalid();
}
defineExpose({ focusInvalid });
</script>
<template>
  <form
    ref="form"
    novalidate
    :aria-busy="busy"
    :aria-label="label || undefined"
    :aria-describedby="description ? id : undefined"
    :data-dirty="dirty"
    class="space-y-6"
    @submit.prevent="submit"
  >
    <p v-if="description" :id="id" class="text-sm leading-relaxed text-muted">
      {{ description }}
    </p>
    <div
      v-if="$slots.feedback"
      data-form-feedback
      tabindex="-1"
      class="focus-visible:outline-2 focus-visible:outline-[var(--runa-focus-ring)]"
    >
      <slot name="feedback" />
    </div>
    <fieldset :disabled="busy" class="min-w-0">
      <div
        class="grid min-w-0 gap-5"
        :class="{
          'md:grid-cols-2': columns === 2,
          'md:grid-cols-3': columns === 3,
        }"
      >
        <slot />
      </div>
      <div
        v-if="$slots.actions"
        class="mt-6 flex flex-wrap items-center gap-3 border-t border-line pt-5"
      >
        <slot name="actions" />
      </div>
    </fieldset>
  </form>
</template>

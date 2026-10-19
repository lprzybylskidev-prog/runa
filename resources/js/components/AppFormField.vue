<script setup lang="ts">
import { computed, useId } from 'vue';
const props = withDefaults(
  defineProps<{
    label: string;
    id?: string;
    helper?: string;
    warning?: string;
    error?: string | readonly string[];
    required?: boolean;
  }>(),
  { id: '', helper: '', warning: '', error: '', required: false },
);
const generated = useId();
const controlId = computed(() => props.id || generated);
const messages = computed(() =>
  typeof props.error === 'string'
    ? props.error
      ? [props.error]
      : []
    : props.error.filter(Boolean),
);
const describedBy = computed(
  () =>
    [
      props.helper && `${controlId.value}-helper`,
      props.warning && `${controlId.value}-warning`,
      messages.value.length && `${controlId.value}-error`,
    ]
      .filter(Boolean)
      .join(' ') || undefined,
);
</script>
<template>
  <div class="min-w-0 space-y-2">
    <slot
      v-if="$slots.label"
      :id="controlId"
      name="label"
      :required="required"
    />
    <label
      v-else
      :for="controlId"
      class="block text-xs font-semibold text-muted"
      >{{ label
      }}<span v-if="required" aria-hidden="true" class="ml-1">*</span></label
    ><slot
      :id="controlId"
      :described-by="describedBy"
      :invalid="messages.length > 0"
    />
    <p
      v-if="helper"
      :id="`${controlId}-helper`"
      class="text-xs leading-relaxed text-muted"
    >
      {{ helper }}
    </p>
    <p
      v-if="warning"
      :id="`${controlId}-warning`"
      class="text-xs leading-relaxed text-[var(--runa-warning-text)]"
    >
      {{ warning }}
    </p>
    <ul
      v-if="messages.length"
      :id="`${controlId}-error`"
      class="space-y-1 text-xs text-[var(--runa-danger-text)]"
    >
      <li v-for="(message, index) in messages" :key="index">{{ message }}</li>
    </ul>
  </div>
</template>

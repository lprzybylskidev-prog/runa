<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import AppFormField from './AppFormField.vue';
import AppButton from './AppButton.vue';
import { validateFiles } from '../lib/files';
import type { FileIssue } from '../runa/ui/forms';
const props = withDefaults(
  defineProps<{
    modelValue: readonly globalThis.File[];
    label: string;
    name?: string;
    multiple?: boolean;
    accept?: string;
    maxFiles?: number | undefined;
    maxBytes?: number | undefined;
    disabled?: boolean;
    readonly?: boolean;
    required?: boolean;
    helper?: string;
    error?: string | readonly string[];
    chooseLabel?: string;
    removeLabel?: string;
    emptyLabel?: string;
    countMessage?: string;
    sizeMessage?: string;
    typeMessage?: string;
  }>(),
  {
    name: '',
    multiple: false,
    accept: '',
    maxFiles: undefined,
    maxBytes: undefined,
    disabled: false,
    readonly: false,
    required: false,
    helper: '',
    error: '',
    chooseLabel: 'Choose files',
    removeLabel: 'Remove',
    emptyLabel: 'No files selected',
    countMessage: 'Too many files.',
    sizeMessage: 'File is too large.',
    typeMessage: 'File type is not accepted.',
  },
);
const emit = defineEmits<{
  'update:modelValue': [files: readonly globalThis.File[]];
  issues: [issues: readonly FileIssue[]];
}>();
const picker = ref<globalThis.HTMLInputElement>();
const issues = computed(() =>
  validateFiles(props.modelValue, {
    multiple: props.multiple,
    accept: props.accept,
    ...(props.maxFiles === undefined ? {} : { maxFiles: props.maxFiles }),
    ...(props.maxBytes === undefined ? {} : { maxBytes: props.maxBytes }),
  }),
);
watch(issues, (value) => emit('issues', value), { immediate: true });
const messages = computed(() => [
  ...(typeof props.error === 'string'
    ? props.error
      ? [props.error]
      : []
    : props.error),
  ...issues.value.map(
    (issue) =>
      `${issue.fileName ? `${issue.fileName}: ` : ''}${issue.code === 'count' ? props.countMessage : issue.code === 'size' ? props.sizeMessage : props.typeMessage}`,
  ),
]);
function select(event: Event) {
  if (
    props.disabled ||
    props.readonly ||
    !(event.target instanceof globalThis.HTMLInputElement)
  )
    return;
  emit('update:modelValue', Array.from(event.target.files ?? []));
  event.target.value = '';
}
function remove(index: number) {
  if (!props.disabled && !props.readonly)
    emit(
      'update:modelValue',
      props.modelValue.filter((_, i) => i !== index),
    );
}
</script>
<template>
  <AppFormField
    :label="label"
    :helper="helper"
    :error="messages"
    :required="required"
    ><template #default="{ id, describedBy, invalid }"
      ><input
        :id="id"
        ref="picker"
        type="file"
        :name="name || undefined"
        :multiple="multiple"
        :accept="accept || undefined"
        :disabled="disabled || readonly"
        :required="required"
        :aria-describedby="describedBy"
        tabindex="-1"
        class="sr-only"
        @change="select"
      /><AppButton
        v-if="!readonly"
        :disabled="disabled"
        :aria-describedby="describedBy"
        :aria-invalid="invalid || undefined"
        :aria-label="`${chooseLabel}: ${label}`"
        @click="picker?.click()"
        >{{ chooseLabel }}</AppButton
      >
      <p v-if="!modelValue.length" class="text-xs text-muted">
        {{ emptyLabel }}
      </p>
      <ul v-else class="space-y-2">
        <li
          v-for="(file, index) in modelValue"
          :key="`${index}-${file.name}`"
          class="flex min-w-0 items-center justify-between gap-2 rounded border border-line px-3 py-2 text-xs"
        >
          <span class="min-w-0 break-all"
            >{{ file.name }}
            <span class="text-muted">({{ file.size }} B)</span></span
          ><AppButton
            v-if="!readonly"
            size="sm"
            variant="ghost"
            :disabled="disabled"
            :aria-label="`${removeLabel}: ${file.name}`"
            @click="remove(index)"
            >×</AppButton
          >
        </li>
      </ul></template
    ></AppFormField
  >
</template>

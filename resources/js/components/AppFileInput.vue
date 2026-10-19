<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { activateMotionElement, deactivateMotionElement } from '../lib/motion';
import AppFormField from './AppFormField.vue';
import AppButton from './AppButton.vue';
import { validateFiles } from '../lib/files';
import type { FileIssue } from '../runa/ui/forms';
const props = withDefaults(
  defineProps<{
    modelValue: readonly globalThis.File[];
    label: string;
    locale?: 'pl' | 'en';
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
    locale: 'pl',
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
const root = ref<globalThis.HTMLElement>();
const dragDepth = ref(0);
const dragging = ref(false);
const text = (pl: string, en: string) => (props.locale === 'pl' ? pl : en);
const number = (value: number) =>
  new Intl.NumberFormat(props.locale === 'pl' ? 'pl-PL' : 'en-GB', {
    maximumFractionDigits: 1,
  }).format(value);
function size(bytes: number) {
  const index =
    bytes > 0 ? Math.min(3, Math.floor(Math.log(bytes) / Math.log(1024))) : 0;
  return `${number(bytes / 1024 ** index)} ${['B', 'KB', 'MB', 'GB'][index]}`;
}
const totalBytes = computed(() =>
  props.modelValue.reduce((sum, file) => sum + file.size, 0),
);
const constraints = computed(() =>
  [
    props.accept
      ? props.accept
          .split(',')
          .map((rule) =>
            rule.trim() === 'image/*'
              ? text('Obrazy', 'Images')
              : rule.trim().replace(/^\./, '').toUpperCase(),
          )
          .join(' · ')
      : text('Dowolny typ pliku', 'Any file type'),
    props.maxBytes === undefined
      ? ''
      : `${text('Do', 'Up to')} ${size(props.maxBytes)} / ${text('plik', 'file')}`,
    !props.multiple
      ? text('1 plik', '1 file')
      : props.maxFiles === undefined
        ? text('Wiele plików', 'Multiple files')
        : `${text('Limit', 'Limit')}: ${props.maxFiles}`,
  ].filter(Boolean),
);
function blocked() {
  return (
    props.disabled || props.readonly || !!picker.value?.matches(':disabled')
  );
}
function resetDrag() {
  dragDepth.value = 0;
  dragging.value = false;
}
watch(() => [props.disabled, props.readonly], resetDrag);
function fileDrag(event: globalThis.DragEvent) {
  return Array.from(event.dataTransfer?.types ?? []).includes('Files');
}
function drag(event: globalThis.DragEvent) {
  if (!fileDrag(event)) return;
  event.preventDefault();
  if (blocked()) {
    if (event.dataTransfer) event.dataTransfer.dropEffect = 'none';
    resetDrag();
    return;
  }
  if (event.dataTransfer) event.dataTransfer.dropEffect = 'copy';
  if (event.type === 'dragenter') dragDepth.value++;
  dragging.value = true;
}
function leave() {
  dragDepth.value = Math.max(0, dragDepth.value - 1);
  if (!dragDepth.value) dragging.value = false;
}
function receive(files: readonly globalThis.File[]) {
  if (blocked() || !files.length) return;
  emit(
    'update:modelValue',
    props.multiple ? [...props.modelValue, ...files] : [...files],
  );
}
function drop(event: globalThis.DragEvent) {
  if (!fileDrag(event)) return;
  event.preventDefault();
  resetDrag();
  receive(Array.from(event.dataTransfer?.files ?? []));
}
function extension(file: globalThis.File) {
  const value = file.name.includes('.')
    ? file.name.split('.').at(-1)?.slice(0, 5).toUpperCase()
    : '';
  return value || text('PLIK', 'FILE');
}
function invalidFile(file: globalThis.File) {
  return (
    validateFiles([file], {
      accept: props.accept,
      ...(props.maxBytes === undefined ? {} : { maxBytes: props.maxBytes }),
    }).length > 0
  );
}
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
  receive(Array.from(event.target.files ?? []));
  event.target.value = '';
}
async function remove(index: number) {
  if (blocked()) return;
  emit(
    'update:modelValue',
    props.modelValue.filter((_, i) => i !== index),
  );
  await nextTick();
  const controls = Array.from(
    root.value?.querySelectorAll<globalThis.HTMLElement>(
      '[data-file-remove]',
    ) ?? [],
  ).filter((control) => !control.closest('[inert]'));
  (
    controls?.[Math.min(index, controls.length - 1)] ??
    root.value?.querySelector<globalThis.HTMLElement>('.file-dropzone')
  )?.focus({ preventScroll: true });
}
const fileIds = new WeakMap<globalThis.File, number>();
let nextFileId = 0;
const fileKeys = computed(() => {
  const occurrences = new Map<globalThis.File, number>();
  return props.modelValue.map((file) => {
    if (!fileIds.has(file)) fileIds.set(file, ++nextFileId);
    const occurrence = occurrences.get(file) ?? 0;
    occurrences.set(file, occurrence + 1);
    return `${fileIds.get(file)}-${occurrence}`;
  });
});
</script>
<template>
  <AppFormField
    :label="label"
    :helper="helper"
    :error="messages"
    :required="required"
  >
    <template #default="{ id, describedBy, invalid }">
      <div
        ref="root"
        class="file-input space-y-3"
        :data-disabled="disabled || undefined"
        :data-readonly="readonly || undefined"
        @dragenter="drag"
        @dragover="drag"
        @dragleave="leave"
        @drop="drop"
      >
        <input
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
        />
        <AppButton
          v-if="!readonly"
          :disabled="disabled"
          :aria-describedby="describedBy"
          :aria-invalid="invalid || undefined"
          :aria-label="`${chooseLabel}: ${label}`"
          class="file-dropzone"
          :class="{ 'file-dragging': dragging }"
          @click="picker?.click()"
        >
          <span class="file-upload-icon" aria-hidden="true"
            ><svg
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.5"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path
                d="M12 15V3m-4 4 4-4 4 4M4 14v5a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5"
              /></svg
          ></span>
          <span class="file-dropzone-title">{{
            dragging
              ? multiple
                ? text('Upuść pliki tutaj', 'Drop files here')
                : text('Upuść plik tutaj', 'Drop file here')
              : chooseLabel
          }}</span>
          <span class="file-dropzone-hint">{{
            text(
              multiple
                ? 'lub przeciągnij je w to miejsce'
                : 'lub przeciągnij plik w to miejsce',
              multiple
                ? 'or drag and drop them here'
                : 'or drag and drop a file here',
            )
          }}</span>
          <span class="file-constraints"
            ><span v-for="constraint in constraints" :key="constraint">{{
              constraint
            }}</span></span
          >
        </AppButton>
        <div
          v-if="modelValue.length"
          class="flex flex-wrap items-center justify-between gap-2 text-xs text-muted"
          role="status"
          aria-live="polite"
          aria-atomic="true"
        >
          <span
            >{{ text('Wybrane pliki', 'Selected files') }}:
            <strong class="font-semibold text-ink">{{
              modelValue.length
            }}</strong
            ><span v-if="multiple && maxFiles !== undefined">
              / {{ maxFiles }}</span
            ></span
          ><span>{{ size(totalBytes) }}</span>
        </div>
        <p v-else class="py-1 text-xs text-muted" role="status">
          {{ emptyLabel }}
        </p>
        <TransitionGroup
          tag="ul"
          name="runa-list"
          class="space-y-2 empty:hidden"
          :aria-label="text('Wybrane pliki', 'Selected files')"
          @before-enter="activateMotionElement"
          @before-leave="deactivateMotionElement"
        >
          <li
            v-for="(file, index) in modelValue"
            :key="fileKeys[index]"
            class="file-row"
            :class="{ 'file-row-invalid': invalidFile(file) }"
          >
            <span class="file-type" aria-hidden="true"
              ><svg
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path
                  d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9zM14 3v6h6M8 13h8M8 17h5"
                /></svg
              ><span>{{ extension(file) }}</span></span
            >
            <div class="min-w-0 flex-1">
              <p class="break-all text-xs font-semibold text-ink">
                {{ file.name }}
              </p>
              <p
                class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted"
              >
                <span>{{ size(file.size) }}</span
                ><span
                  v-if="invalidFile(file)"
                  class="text-[var(--runa-danger-text)]"
                  >{{ text('Sprawdź plik', 'Check file') }}</span
                >
              </p>
            </div>
            <AppButton
              v-if="!readonly"
              data-file-remove
              size="sm"
              variant="ghost"
              icon-only
              :disabled="disabled"
              :aria-label="`${removeLabel}: ${file.name}`"
              @click="remove(index)"
              ><svg
                aria-hidden="true"
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path
                  d="M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3"
                /></svg
            ></AppButton>
          </li>
        </TransitionGroup>
      </div>
    </template>
  </AppFormField>
</template>
<style scoped>
.file-dropzone.app-button {
  display: flex;
  flex-direction: column;
  width: 100%;
  min-width: 0;
  gap: 8px;
  padding: 24px 16px 16px;
  border-style: dashed;
  background: var(--runa-input-background);
  color: var(--runa-text);
}
.file-dropzone.app-button:not(:disabled):hover,
.file-dropzone.app-button.file-dragging {
  background: var(--runa-primary-subtle);
  border-color: var(--runa-primary-solid);
}
.file-dropzone.app-button:focus-visible {
  outline: 0;
  border-color: var(--runa-focus-ring);
  box-shadow: inset 0 0 0 1px var(--runa-focus-ring);
}
.file-dropzone.app-button[aria-invalid='true'] {
  border-color: var(--runa-danger-text);
}
.file-upload-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border: 1px solid var(--runa-control-border);
  border-radius: 4px;
  background: var(--runa-surface-raised);
  color: var(--runa-primary-text);
}
.file-dropzone-title {
  font-size: 13px;
  font-weight: 600;
  text-align: center;
}
.file-dropzone-hint {
  font-size: 11px;
  color: var(--runa-text-muted);
  text-align: center;
}
.file-constraints {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 6px;
  margin-top: 8px;
  font-size: 10px;
  color: var(--runa-text-secondary);
}
.file-constraints > span {
  padding: 3px 6px;
  border: 1px solid var(--runa-control-border);
  border-radius: 3px;
}
.file-row {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
  padding: 12px;
  border: 1px solid var(--runa-control-border);
  border-radius: 4px;
  background: var(--runa-surface-raised);
}
.file-row-invalid {
  border-color: var(--runa-danger-text);
}
.file-type {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  gap: 4px;
  width: 44px;
  min-height: 52px;
  color: var(--runa-primary-text);
  background: var(--runa-primary-subtle);
  border-radius: 3px;
  font-size: 9px;
}
.file-dropzone.app-button:disabled {
  background: var(--runa-disabled-background);
  color: var(--runa-disabled-text);
}
.file-dropzone.app-button:disabled .file-upload-icon,
.file-input[data-disabled] .file-type {
  color: var(--runa-disabled-text);
  background: var(--runa-disabled-background);
}
@media (forced-colors: active) {
  .file-dropzone.app-button:focus-visible {
    outline: 2px solid Highlight;
    outline-offset: 2px;
  }
  .file-dropzone.app-button.file-dragging {
    outline: 2px dashed Highlight;
  }
  .file-row-invalid {
    border-color: Mark;
  }
}
</style>

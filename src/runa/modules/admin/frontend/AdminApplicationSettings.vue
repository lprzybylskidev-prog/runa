<script setup lang="ts">
import { useAdminLocale } from './localization';
const { locale: adminLocale, t: tr } = useAdminLocale();
import { onMounted, ref } from 'vue';
import { useAdminInitialReadiness } from './initialReadiness';
import {
  Alert,
  FileInput,
  ViewBoundary,
  useViewReadiness,
  Button,
  Checkbox,
  NumberInput,
  Select,
  TextInput,
} from '@/runa/ui';
import type { AdminOperationsController } from './useAdminOperations';
import { useAdminApplicationSettings } from './useAdminApplicationSettings';
import type { AdminApplicationSetting } from './useAdminApplicationSettings';
function optionLabel(field: AdminApplicationSetting, value: string) {
  const names: Record<string, string> =
    field.key === 'presentation.locale'
      ? { pl: 'Polish', en: 'English' }
      : field.key === 'presentation.theme'
        ? { system: 'System', light: 'Light', dark: 'Dark' }
        : {};
  return tr(names[value] ?? value);
}
const props = defineProps<{ controller: AdminOperationsController }>();
const settings = useAdminApplicationSettings(props.controller);
defineExpose({ settings });
const initialSettled = ref(false);
const readiness = useViewReadiness({
  identity: 'editor',
  required: initialSettled,
});
useAdminInitialReadiness(() => initialSettled.value);
onMounted(async () => {
  try {
    await settings.refresh();
  } finally {
    initialSettled.value = true;
  }
});
</script>
<template>
  <ViewBoundary
    :readiness="readiness"
    :label="tr('Loading the administrator panel')"
  >
    <slot :controller="settings">
      <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-end gap-3">
          <Button :disabled="controller.busy.value" @click="settings.refresh">{{
            tr('Refresh current settings')
          }}</Button>
        </div>
        <p class="text-sm text-muted">
          {{
            tr(
              'Presentation changes take effect without restart. Reset restores source defaults.',
            )
          }}
        </p>
        <Alert v-if="settings.invalid.value" tone="warning">{{
          tr(settings.invalid.value)
        }}</Alert>
        <Alert v-if="settings.saved.value" tone="success">{{
          tr('Application settings saved.')
        }}</Alert>
        <Alert v-if="!settings.valid.value" tone="warning">{{
          tr('Correct the setting values before saving.')
        }}</Alert>
        <p
          v-if="settings.loaded.value && !settings.fields.value.length"
          class="text-sm text-muted"
        >
          {{ tr('No application settings are available.') }}
        </p>
        <div
          v-for="field in settings.fields.value"
          :key="field.key"
          class="grid max-w-3xl gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end"
        >
          <div class="min-w-0">
            <template
              v-if="
                field.key === 'presentation.logo' ||
                field.key === 'presentation.favicon'
              "
            >
              <FileInput
                :label="tr(field.title)"
                :model-value="
                  settings.imageFiles.value[
                    field.key === 'presentation.logo' ? 'logo' : 'favicon'
                  ]
                "
                accept="image/png,image/jpeg,image/webp"
                :choose-label="tr('Choose image')"
                :empty-label="tr('No image selected')"
                :remove-label="tr('Remove image')"
                :max-bytes="4194304"
                :locale="adminLocale"
                :disabled="
                  controller.busy.value ||
                  settings.imageBusy.value ||
                  !field.writable
                "
                :helper="
                  tr(
                    field.key === 'presentation.logo'
                      ? 'A favicon is generated automatically from the logo.'
                      : 'Leave empty to use the favicon generated from the logo.',
                  )
                "
                @update:model-value="
                  settings.selectImage(
                    field.key === 'presentation.logo' ? 'logo' : 'favicon',
                    $event,
                  )
                "
              />
              <img
                v-if="
                  settings.previewImage(
                    field.key === 'presentation.logo' ? 'logo' : 'favicon',
                  )
                "
                :src="
                  settings.previewImage(
                    field.key === 'presentation.logo' ? 'logo' : 'favicon',
                  )
                "
                :alt="tr(field.title)"
                class="mt-3 max-h-20 max-w-48 object-contain"
              />
              <Select
                v-if="
                  field.key === 'presentation.logo' &&
                  field.choices.some((value) => value !== 'uploaded')
                "
                :label="tr('Deployed logo')"
                :locale="adminLocale"
                :model-value="String(settings.draft.value[field.key] ?? '')"
                :options="
                  field.choices
                    .filter((value) => value !== 'uploaded')
                    .map((value) => ({ value, label: value }))
                "
                :disabled="controller.busy.value || !field.writable"
                @update:model-value="settings.edit(field.key, $event)"
              />
            </template>
            <Checkbox
              v-else-if="field.type === 'boolean'"
              :label="tr(field.title)"
              :model-value="settings.draft.value[field.key] === true"
              :disabled="controller.busy.value || !field.writable"
              @update:model-value="settings.edit(field.key, $event)"
            />
            <NumberInput
              v-else-if="field.type === 'integer'"
              :label="tr(field.title)"
              :model-value="String(settings.draft.value[field.key] ?? '')"
              kind="integer"
              :locale="adminLocale"
              :disabled="controller.busy.value || !field.writable"
              @update:model-value="
                settings.edit(
                  field.key,
                  $event !== null &&
                    /^-?\d+$/.test($event) &&
                    Number.isSafeInteger(Number($event))
                    ? Number($event)
                    : $event,
                )
              "
            />
            <Select
              v-else-if="field.type === 'enum' || field.type === 'timezone'"
              :label="tr(field.title)"
              :model-value="
                typeof settings.draft.value[field.key] === 'string'
                  ? String(settings.draft.value[field.key])
                  : String(field.defaultValue ?? '')
              "
              :options="
                field.choices.map((value) => ({
                  value,
                  label: optionLabel(field, value),
                }))
              "
              :locale="adminLocale"
              :disabled="controller.busy.value || !field.writable"
              @update:model-value="settings.edit(field.key, $event)"
            />
            <TextInput
              v-else
              :label="tr(field.title)"
              :model-value="
                typeof settings.draft.value[field.key] === 'string'
                  ? String(settings.draft.value[field.key])
                  : null
              "
              :maxlength="field.maxLength"
              :disabled="controller.busy.value || !field.writable"
              @update:model-value="settings.edit(field.key, $event)"
            />
          </div>
          <p v-if="!field.writable" class="text-xs text-muted">
            {{ tr('Read only.') }}
          </p>
          <Button
            v-else
            :disabled="controller.busy.value || settings.imageBusy.value"
            :aria-label="tr('Restore defaults') + ': ' + tr(field.title)"
            variant="outline"
            class="justify-self-start sm:mt-6 sm:self-start"
            @click="settings.edit(field.key, null)"
            >{{ tr('Restore defaults') }}</Button
          >
          <Alert
            class="sm:col-span-2"
            v-if="settings.conflicts.value.has(field.key)"
            tone="warning"
          >
            {{ tr(field.title) }} {{ tr('changed elsewhere. Current value:') }}
            {{ settings.base.value[field.key] ?? tr('Source default') }}.
            <div class="mt-2 flex flex-wrap gap-3">
              <Button
                :disabled="controller.busy.value"
                @click="settings.resolve(field.key, false)"
                >{{ tr('Use current value') }}</Button
              >
              <Button
                :disabled="controller.busy.value"
                @click="settings.resolve(field.key, true)"
                >{{ tr('Keep my value') }}</Button
              >
            </div>
          </Alert>
        </div>
        <Alert v-if="settings.imageError.value" tone="warning">{{
          tr(settings.imageError.value)
        }}</Alert>
        <Button
          v-if="settings.fields.value.some((field) => field.writable)"
          :disabled="
            controller.busy.value ||
            !settings.dirty.value ||
            !settings.valid.value ||
            settings.conflicts.value.size > 0
          "
          @click="settings.save"
          >{{ tr('Save application settings') }}</Button
        >
      </div>
    </slot>
  </ViewBoundary>
</template>

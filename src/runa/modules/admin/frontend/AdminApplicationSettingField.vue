<script setup lang="ts">
import {
  actionIcons,
  Alert,
  Button,
  Checkbox,
  FileInput,
  NumberInput,
  Select,
  TextInput,
} from '@/runa/ui';
import type { AdminOperationsController } from './useAdminOperations';
import type {
  AdminApplicationSetting,
  useAdminApplicationSettings,
} from './useAdminApplicationSettings';
import { useAdminLocale } from './localization';
const { locale: adminLocale, t: tr } = useAdminLocale();
defineProps<{
  field: AdminApplicationSetting;
  settings: ReturnType<typeof useAdminApplicationSettings>;
  controller: AdminOperationsController;
}>();
function optionLabel(field: AdminApplicationSetting, value: string) {
  const names: Record<string, string> =
    field.key === 'presentation.locale'
      ? { pl: 'Polish', en: 'English' }
      : field.key === 'presentation.theme'
        ? { system: 'System', light: 'Light', dark: 'Dark' }
        : {};
  return tr(names[value] ?? value);
}
</script>
<template>
  <div class="grid min-w-0 grid-cols-[minmax(0,1fr)_auto] items-start gap-2">
    <div class="min-w-0">
      <template
        v-if="
          field.key === 'presentation.logo' ||
          field.key === 'presentation.favicon'
        "
      >
        <FileInput
          :label="tr(field.title)"
          :error="tr(settings.fieldErrors.value[field.key] ?? '')"
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
            controller.busy.value || settings.imageBusy.value || !field.writable
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
        :error="tr(settings.fieldErrors.value[field.key] ?? '')"
        :model-value="settings.draft.value[field.key] === true"
        :disabled="controller.busy.value || !field.writable"
        @update:model-value="settings.edit(field.key, $event)"
      />
      <NumberInput
        v-else-if="field.type === 'integer'"
        :label="tr(field.title)"
        :error="tr(settings.fieldErrors.value[field.key] ?? '')"
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
        :searchable="field.type === 'timezone'"
        :label="tr(field.title)"
        :error="tr(settings.fieldErrors.value[field.key] ?? '')"
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
        :error="tr(settings.fieldErrors.value[field.key] ?? '')"
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
      icon="rotate-2"
      intent="discard"
      :disabled="controller.busy.value || settings.imageBusy.value"
      :aria-label="tr('Restore defaults') + ': ' + tr(field.title)"
      variant="outline"
      icon-only
      :title="tr('Restore defaults') + ': ' + tr(field.title)"
      :class="field.type === 'boolean' ? 'self-center' : 'mt-6'"
      @click="settings.edit(field.key, null)"
    />
    <Alert
      v-if="settings.conflicts.value.has(field.key)"
      class="col-span-2"
      tone="warning"
    >
      {{ tr(field.title) }} {{ tr('changed elsewhere. Current value:') }}
      {{ settings.base.value[field.key] ?? tr('Source default') }}.
      <div class="mt-2 flex flex-wrap gap-3">
        <Button
          :icon="actionIcons.discard"
          intent="discard"
          :disabled="controller.busy.value"
          @click="settings.resolve(field.key, false)"
          >{{ tr('Use current value') }}</Button
        >
        <Button
          :icon="actionIcons.confirm"
          intent="commit"
          variant="outline"
          :disabled="controller.busy.value"
          @click="settings.resolve(field.key, true)"
          >{{ tr('Keep my value') }}</Button
        >
      </div>
    </Alert>
  </div>
</template>

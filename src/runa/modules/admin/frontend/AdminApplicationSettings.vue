<script setup lang="ts">
import { useAdminLocale } from './localization';
const { locale: adminLocale, t: tr } = useAdminLocale();
import { onMounted } from 'vue';
import {
  Alert,
  Button,
  Checkbox,
  NumberInput,
  Select,
  TextInput,
} from '@/runa/ui';
import type { AdminOperationsController } from './useAdminOperations';
import { useAdminApplicationSettings } from './useAdminApplicationSettings';
const props = defineProps<{ controller: AdminOperationsController }>();
const settings = useAdminApplicationSettings(props.controller);
defineExpose({ settings });
onMounted(settings.refresh);
</script>
<template>
  <slot :controller="settings">
    <div class="space-y-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="font-semibold">{{ tr('Application settings') }}</h3>
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
        class="space-y-2"
      >
        <Checkbox
          v-if="field.type === 'boolean'"
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
          v-else-if="field.type === 'enum'"
          :label="tr(field.title)"
          :model-value="
            typeof settings.draft.value[field.key] === 'string'
              ? String(settings.draft.value[field.key])
              : ''
          "
          :options="field.choices.map((value) => ({ value, label: tr(value) }))"
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
        <p v-if="!field.writable" class="text-xs text-muted">
          {{ tr('Read only.') }}
        </p>
        <Button
          v-else
          :disabled="controller.busy.value"
          @click="settings.edit(field.key, null)"
          >{{ tr('Reset') }} {{ tr(field.title) }}</Button
        >
        <Alert v-if="settings.conflicts.value.has(field.key)" tone="warning">
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
</template>

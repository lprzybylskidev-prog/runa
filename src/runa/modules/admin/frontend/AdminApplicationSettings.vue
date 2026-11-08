<script setup lang="ts">
import { useAdminLocale } from './localization';
const { locale: adminLocale, t: tr } = useAdminLocale();
import { computed, onMounted, ref } from 'vue';
import AdminApplicationSettingField from './AdminApplicationSettingField.vue';
import { useAdminInitialReadiness } from './initialReadiness';
import {
  actionIcons,
  Form,
  FormSection,
  FormNotifications,
  ViewBoundary,
  useViewReadiness,
  Button,
} from '@/runa/ui';
import type { AdminOperationsController } from './useAdminOperations';
import { useAdminApplicationSettings } from './useAdminApplicationSettings';
const props = defineProps<{ controller: AdminOperationsController }>();
const settings = useAdminApplicationSettings(props.controller);
defineExpose({ settings });
const identityKeys = [
  'presentation.name',
  'presentation.logo',
  'presentation.favicon',
];
const defaultKeys = [
  'presentation.locale',
  'presentation.theme',
  'presentation.timezone',
];
const groups = computed(() =>
  [
    {
      key: 'identity',
      title: 'Application identity',
      description: 'Name, logo and favicon.',
      fields: identityKeys.flatMap((key) =>
        settings.fields.value.filter((field) => field.key === key),
      ),
    },
    {
      key: 'defaults',
      title: 'Default preferences',
      description: 'Language, theme and time zone.',
      fields: defaultKeys.flatMap((key) =>
        settings.fields.value.filter((field) => field.key === key),
      ),
    },
    {
      key: 'additional',
      title: 'Additional settings',
      description: '',
      fields: settings.fields.value.filter(
        (field) =>
          !identityKeys.includes(field.key) && !defaultKeys.includes(field.key),
      ),
    },
  ].filter((group) => group.fields.length > 0),
);
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
      <Form
        :busy="controller.busy.value"
        :valid="settings.valid.value"
        :dirty="settings.dirty.value"
        @submit="settings.save"
        @invalid="settings.save"
      >
        <FormNotifications
          :locale="adminLocale"
          :attempt="settings.feedbackAttempt.value"
          :error="
            tr(
              settings.imageError.value ||
                settings.invalid.value ||
                controller.error.value,
            )
          "
          :invalid="settings.submitted.value && !settings.valid.value"
          :success="
            settings.saved.value ? tr('Application settings saved.') : ''
          "
        >
          <template #actions>
            <Button
              v-if="settings.invalid.value || controller.error.value"
              :icon="actionIcons.refresh"
              intent="inspect"
              :busy="controller.refreshing.value"
              :disabled="controller.busy.value"
              @click="settings.refresh"
              >{{ tr('Retry') }}</Button
            >
          </template>
        </FormNotifications>
        <p
          v-if="settings.loaded.value && !settings.fields.value.length"
          class="text-sm text-muted"
        >
          {{ tr('No application settings are available.') }}
        </p>
        <div
          v-if="groups.length"
          class="grid min-w-0 items-start gap-6 lg:grid-cols-2"
        >
          <FormSection
            v-for="group in groups"
            :key="group.key"
            :title="tr(group.title)"
            :description="tr(group.description)"
            :columns="group.key === 'defaults' ? 1 : 2"
            :class="{ 'lg:col-span-2': group.key === 'additional' }"
          >
            <AdminApplicationSettingField
              v-for="field in group.fields"
              :key="field.key"
              :field="field"
              :settings="settings"
              :controller="controller"
              :class="{ 'md:col-span-2': field.key === 'presentation.name' }"
            />
          </FormSection>
        </div>
        <div
          class="flex flex-wrap items-center justify-end gap-3 border-t border-line pt-5"
        >
          <Button
            :icon="actionIcons.refresh"
            intent="inspect"
            :busy="controller.refreshing.value"
            :disabled="controller.busy.value"
            @click="settings.refresh"
            >{{ tr('Refresh current settings') }}</Button
          >

          <Button
            v-if="settings.fields.value.some((field) => field.writable)"
            :icon="actionIcons.save"
            intent="commit"
            :disabled="
              controller.busy.value ||
              !settings.dirty.value ||
              settings.imageBusy.value ||
              settings.conflicts.value.size > 0
            "
            type="submit"
            >{{ tr('Save application settings') }}</Button
          >
        </div>
      </Form>
    </slot>
  </ViewBoundary>
</template>

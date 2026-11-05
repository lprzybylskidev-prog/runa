<script setup lang="ts">
import { computed, ref, watch, useId } from 'vue';
import {
  Alert,
  Button,
  Card,
  ContentLoading,
  Dialog,
  Form,
  FormConflict,
  FormNotifications,
  Select,
  TextInput,
  Tooltip,
} from '@/runa/ui';
import AdminField from './AdminField.vue';
import AdminForm from './AdminForm.vue';
import { useAdminLocale, adminText } from './localization';
import { useAdminInitialReadiness } from './initialReadiness';
import { useAdminSettingsSnapshots } from './useAdminSettingsSnapshots';
import type { AdminSettingsGroup } from './useAdminSettingsSnapshots';
import type {
  AdminFormController,
  AdminJson,
  AdminPageController,
} from './contracts';
const props = withDefaults(
  defineProps<{
    controller: AdminPageController;
    form: AdminFormController;
    groups: AdminSettingsGroup[];
    settingsSection?: string;
    snapshotsSection?: string;
  }>(),
  { settingsSection: 'policy', snapshotsSection: 'snapshots' },
);
const { locale, t } = useAdminLocale();
const label = (pl: string, en: string) => (locale.value === 'pl' ? pl : en);
const snapshots = useAdminSettingsSnapshots(
  props.controller,
  props.form,
  props.settingsSection,
  props.snapshotsSection,
);
const fieldPrefix = useId();
const expandedHelp = ref(new Set<string>());
const fieldId = (key: string) => `${fieldPrefix}-${key}`;
function toggleHelp(key: string) {
  const next = new Set(expandedHelp.value);
  if (next.has(key)) next.delete(key);
  else next.add(key);
  expandedHelp.value = next;
}
const ready = ref(false);
useAdminInitialReadiness(() => ready.value);
void Promise.all([
  props.form.activateSection(props.settingsSection),
  props.form.activateSection(props.snapshotsSection),
]).finally(() => {
  ready.value = true;
});
const section = computed(() =>
  props.controller.page.value?.sections.find(
    (s) => s.key === props.settingsSection,
  ),
);
const snapshotSection = computed(() =>
  props.controller.page.value?.sections.find(
    (s) => s.key === props.snapshotsSection,
  ),
);
const record = computed(() => props.form.records.value[props.settingsSection]);
const writable = computed(
  () =>
    section.value &&
    !section.value.readonly &&
    section.value.commands.some((c) => c.key === 'publish' && c.available),
);
const snapshotWritable = computed(
  () => snapshotSection.value && !snapshotSection.value.readonly,
);
const loading = computed(() => !ready.value && !record.value);
const groups = computed(() =>
  props.groups
    .map((group) => ({
      ...group,
      fields: group.fields
        .map((key) => section.value?.fields.find((f) => f.key === key))
        .filter((f) => f !== undefined),
    }))
    .filter((g) => g.fields.length),
);
const blocked = computed(
  () =>
    props.form.busy.value ||
    !record.value ||
    !!props.form.conflicts.value.length ||
    !!props.form.operations.value[props.settingsSection],
);
const saveOpen = ref(false);
const reviewOpen = ref(false);
const name = ref('');
const captureAttempt = ref(0);
watch(saveOpen, (open) => {
  const path = props.form.fieldPath(props.snapshotsSection, 'name');
  if (!open && props.form.values.value[path] !== null)
    props.form.set(path, null);
});
function display(value: AdminJson | undefined) {
  if (value === null || value === undefined || value === '') return '—';
  if (typeof value === 'boolean') return t(value ? 'Yes' : 'No');
  return t(String(value));
}
function oldValue(key: string) {
  return (
    label('Poprzednio: ', 'Previously: ') +
    display(
      snapshots.previous.value?.[
        props.form.fieldPath(props.settingsSection, key)
      ],
    )
  );
}
async function capture() {
  captureAttempt.value++;
  if (await snapshots.capture(name.value)) {
    saveOpen.value = false;
    name.value = '';
  }
}
async function review() {
  if (await props.form.reviewCommand(props.settingsSection, 'publish'))
    reviewOpen.value = true;
}
async function apply() {
  reviewOpen.value = false;
  const result = await props.form.execute(props.settingsSection, 'publish');
  if (result?.status === 'committed') {
    snapshots.clear();
    await props.form.refresh();
  }
}
const reviewed = computed(
  () => props.form.commandReviews.value[`${props.settingsSection}:publish`],
);
</script>
<template>
  <AdminForm :controller="form">
    <div class="min-w-0 space-y-4">
      <h1 class="text-xl font-semibold">
        {{ t(controller.page.value?.title ?? '') }}
      </h1>
      <Card>
        <div class="flex flex-wrap items-end gap-3">
          <div class="w-full min-w-0 sm:max-w-2xl sm:flex-[1_1_30rem]">
            <Select
              v-model="snapshots.selected.value"
              :label="label('Migawka ustawień', 'Settings snapshot')"
              :locale="locale"
              :options="
                snapshots.catalog.value.map((s) => ({
                  value: s.public_id,
                  label: s.name,
                }))
              "
              :disabled="form.busy.value || !!snapshots.previous.value"
              clearable
            />
          </div>
          <Button
            intent="inspect"
            :disabled="
              blocked ||
              !snapshots.selected.value ||
              !!snapshots.previous.value ||
              !writable
            "
            :busy="snapshots.resource.busy.value"
            @click="snapshots.load"
            >{{ label('Wczytaj', 'Load') }}</Button
          >
          <Button
            v-if="snapshotWritable"
            intent="danger"
            variant="outline"
            :disabled="
              form.busy.value ||
              !snapshots.selected.value ||
              !!snapshots.previous.value
            "
            @click="snapshots.remove"
            >{{ label('Usuń migawkę', 'Delete snapshot') }}</Button
          >
          <Button
            v-if="snapshotWritable"
            intent="commit"
            variant="outline"
            :disabled="blocked || !form.records.value[snapshotsSection]"
            @click="
              name = '';
              saveOpen = true;
            "
            >{{ label('Zapisz migawkę', 'Save snapshot') }}</Button
          >
        </div>
        <FormNotifications
          :locale="locale"
          :error="
            snapshots.resource.error.value
              ? t(snapshots.resource.error.value.message)
              : ''
          "
        />
        <p v-if="snapshots.previous.value" class="mt-3 text-sm text-muted">
          {{ label('Podgląd migawki: ', 'Snapshot preview: ')
          }}{{ snapshots.loadedName.value }}.
          {{
            label(
              'Wyróżnione pola różnią się od formularza sprzed wczytania.',
              'Highlighted fields differ from the form before loading.',
            )
          }}
        </p>
      </Card>
      <ContentLoading
        v-if="loading"
        shape="form"
        :label="label('Wczytywanie ustawień', 'Loading settings')"
      />
      <Alert v-else-if="!record" tone="warning"
        >{{
          label('Nie udało się wczytać ustawień.', 'Unable to load settings.')
        }}
        <Button intent="inspect" @click="form.refresh">{{
          t('Retry')
        }}</Button></Alert
      >
      <div v-else class="columns-1 gap-4 xl:columns-2">
        <Card
          v-for="group in groups"
          :key="group.key"
          class="mb-4 break-inside-avoid"
        >
          <template #header
            ><h2 class="font-semibold">
              {{ adminText(group.title, locale) }}
            </h2></template
          >
          <div class="min-w-0 divide-y divide-line">
            <div
              v-for="field in group.fields"
              :key="field.key"
              class="grid min-w-0 items-center gap-x-4 gap-y-2 py-2.5 first:pt-0 last:pb-0 sm:grid-cols-[minmax(0,1fr)_minmax(10rem,0.9fr)]"
              :data-setting-key="field.key"
              :class="
                snapshots.changes.value.includes(field.key)
                  ? 'my-2 rounded bg-primary/5 px-3 py-3! ring-1 ring-inset ring-primary/40'
                  : ''
              "
            >
              <div class="flex min-w-0 items-center gap-2">
                <label
                  :for="fieldId(field.key)"
                  class="text-xs font-semibold leading-relaxed"
                  >{{ t(field.label) }}</label
                >
                <Tooltip v-if="field.description" :text="t(field.description)">
                  <template #default="{ descriptionId }">
                    <button
                      type="button"
                      class="flex size-6 shrink-0 items-center justify-center rounded-full border border-line text-xs text-muted hover:text-ink focus-visible:outline-2 focus-visible:outline-primary"
                      :aria-label="
                        label('Opis opcji: ', 'Setting description: ') +
                        t(field.label)
                      "
                      :aria-describedby="
                        expandedHelp.has(field.key)
                          ? fieldId(field.key) + '-help'
                          : descriptionId
                      "
                      :aria-expanded="expandedHelp.has(field.key)"
                      :aria-controls="
                        expandedHelp.has(field.key)
                          ? fieldId(field.key) + '-help'
                          : undefined
                      "
                      @click="toggleHelp(field.key)"
                    >
                      ?
                    </button>
                  </template>
                </Tooltip>
              </div>
              <AdminField
                :id="fieldId(field.key)"
                hide-label
                :field="field"
                :model-value="
                  form.values.value[
                    form.fieldPath(settingsSection, field.key)
                  ] ?? null
                "
                :errors="
                  form.errors.value[
                    form.fieldPath(settingsSection, field.key)
                  ] ?? []
                "
                :readonly="section?.readonly ?? true"
                :busy="form.busy.value"
                @update:model-value="
                  form.set(form.fieldPath(settingsSection, field.key), $event)
                "
                @availability="
                  form.setAvailable(
                    form.fieldPath(settingsSection, field.key),
                    $event,
                  )
                "
              />
              <p
                v-if="field.description && expandedHelp.has(field.key)"
                :id="fieldId(field.key) + '-help'"
                class="text-xs leading-relaxed text-muted sm:col-span-2"
              >
                {{ t(field.description) }}
              </p>
              <Tooltip
                v-if="snapshots.changes.value.includes(field.key)"
                :text="oldValue(field.key)"
                placement="bottom"
                align="start"
                class="w-fit justify-self-start sm:col-span-2"
              >
                <template #default="{ descriptionId }"
                  ><button
                    type="button"
                    class="text-xs font-semibold text-primary underline decoration-dotted underline-offset-4"
                    :aria-describedby="descriptionId"
                    :aria-label="oldValue(field.key)"
                  >
                    {{ label('Zmieniono', 'Changed') }}
                  </button></template
                >
              </Tooltip>
            </div>
          </div>
        </Card>
      </div>
      <FormConflict
        v-for="conflict in form.conflicts.value"
        :key="conflict.path"
        :locale="locale"
        :current="display(conflict.remote)"
        :busy="form.busy.value"
        @accept="form.resolve(conflict.path, 'remote')"
        @keep="form.resolve(conflict.path, 'local')"
      />
      <Alert v-if="form.operations.value[settingsSection]" tone="warning">{{
        t(
          'The owning operation has not committed. Check its status before submitting again.',
        )
      }}</Alert>
    </div>
    <template #review />
    <template #actions>
      <Button
        v-if="writable"
        intent="commit"
        :disabled="
          blocked || !form.dirty.value || !!snapshots.resource.busy.value
        "
        @click="review"
        >{{
          snapshots.previous.value
            ? label('Zastosuj migawkę', 'Apply snapshot')
            : label('Zapisz ustawienia', 'Save settings')
        }}</Button
      >
      <Button
        v-if="snapshots.previous.value"
        intent="cancel"
        variant="outline"
        :disabled="form.busy.value"
        @click="snapshots.cancel"
        >{{ label('Anuluj', 'Cancel') }}</Button
      >
      <Button
        v-else-if="form.dirty.value"
        intent="discard"
        variant="outline"
        :disabled="form.busy.value"
        @click="form.reset"
        >{{ t('Discard changes') }}</Button
      >
    </template>
  </AdminForm>
  <Dialog
    v-model="saveOpen"
    :locale="locale"
    :title="label('Zapisz migawkę', 'Save snapshot')"
    :description="
      label(
        'Migawka zapisze obecnie zatwierdzone ustawienia. Niezapisane zmiany formularza nie zostaną uwzględnione.',
        'The snapshot saves the currently committed settings. Unsaved form changes are excluded.',
      )
    "
    :busy="form.busy.value"
  >
    <Form :busy="form.busy.value" @submit="capture">
      <TextInput
        v-model="name"
        :label="label('Nazwa migawki', 'Snapshot name')"
        :maxlength="80"
        :disabled="form.busy.value"
        :error="
          form.errors.value[form.fieldPath(snapshotsSection, 'name')] ?? []
        "
      />
      <FormNotifications
        :locale="locale"
        :attempt="captureAttempt"
        :error="form.error.value ? t(form.error.value.message) : ''"
      />
      <template #actions
        ><Button
          intent="commit"
          type="submit"
          :busy="form.busy.value"
          :disabled="!name.trim() || name.trim().length > 80"
          >{{ label('Zapisz', 'Save') }}</Button
        ></template
      >
    </Form>
  </Dialog>
  <Dialog
    v-model="reviewOpen"
    :locale="locale"
    :title="
      label(
        'Czy na pewno zapisać ustawienia?',
        'Are you sure you want to save these settings?',
      )
    "
  >
    <template #footer
      ><Button
        intent="commit"
        :disabled="blocked || !reviewed"
        @click="apply"
        >{{ label('Zatwierdź', 'Confirm') }}</Button
      ><Button intent="cancel" variant="outline" @click="reviewOpen = false">{{
        label('Anuluj', 'Cancel')
      }}</Button></template
    >
  </Dialog>
</template>

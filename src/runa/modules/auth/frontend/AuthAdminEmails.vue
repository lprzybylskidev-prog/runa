<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { useAdminLocale } from '@/runa/admin';
import type {
  AdminJson,
  AdminSectionData,
  AdminSectionPresentationProps,
} from '@/runa/admin';
import {
  Alert,
  Button,
  Card,
  ContentLoading,
  Dialog,
  Form,
  FormConflict,
  RadioGroup,
  TextInput,
} from '@/runa/ui';
import AuthSecondaryEmailFields from './AuthSecondaryEmailFields.vue';
const props = defineProps<
  AdminSectionPresentationProps & { sources: AdminSectionData[] }
>();
const emit = defineEmits<{
  submit: [section: string, command: string, fields?: string[]];
  operation: [
    section: string,
    command: string,
    values: Record<string, AdminJson>,
  ];
}>();
const { locale, t } = useAdminLocale();
const text = (pl: string, en: string) => t({ pl, en });
const primary = computed(() =>
  props.sources.find((source) => source.key === 'primary_email'),
);
const secondary = computed(() =>
  props.sources.find((source) => source.key === 'secondary'),
);
const settled = ref(false);
const refreshing = ref(false);
async function retry() {
  if (refreshing.value) return;
  refreshing.value = true;
  try {
    await props.controller.refresh();
  } finally {
    refreshing.value = false;
  }
}
void Promise.all(
  props.sources.map((source) => props.controller.activateSection(source.key)),
).finally(() => {
  settled.value = true;
});
const missing = computed(() =>
  props.sources.some((source) => !props.controller.records.value[source.key]),
);
const get = (section: string, key: string) =>
  props.controller.values.value[props.controller.fieldPath(section, key)] ??
  null;
const set = (section: string, key: string, value: AdminJson) =>
  props.controller.set(props.controller.fieldPath(section, key), value);
const available = (section: AdminSectionData | undefined, command: string) =>
  section?.commands.some((item) => item.key === command && item.available) ===
  true;
const addresses = computed(() => {
  try {
    const value: unknown = JSON.parse(
      String(get('secondary', 'addresses') ?? '[]'),
    );
    return Array.isArray(value)
      ? value.filter(
          (
            row,
          ): row is { public_id: string; email: string; verified: boolean } =>
            !!row &&
            typeof row === 'object' &&
            typeof row.public_id === 'string' &&
            typeof row.email === 'string' &&
            typeof row.verified === 'boolean',
        )
      : [];
  } catch {
    return [];
  }
});
const editing = ref<'primary'>();
const method = ref('change');
const attempted = ref(false);
const activeSection = primary;
const fieldKey = computed(() =>
  method.value === 'replace' ? 'replacement_email' : 'email',
);
const email = computed({
  get: () => String(get(activeSection.value?.key ?? '', fieldKey.value) ?? ''),
  set: (value) => {
    if (activeSection.value)
      set(activeSection.value.key, fieldKey.value, value);
  },
});
const issue = computed(() =>
  !email.value.trim()
    ? text('Wypełnij to pole.', 'Complete this field.')
    : !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value) ||
        email.value.length > 254
      ? text('Podaj poprawny adres e-mail.', 'Enter a valid email address.')
      : '',
);
const conflicts = computed(() =>
  props.controller.conflicts.value.filter((conflict) =>
    activeSection.value?.fields.some(
      (field) =>
        props.controller.fieldPath(activeSection.value!.key, field.key) ===
        conflict.path,
    ),
  ),
);
function open() {
  attempted.value = false;
  editing.value = 'primary';
  method.value = available(primary.value, 'change') ? 'change' : 'replace';
}
const secondaryEmail = computed({
  get: () => String(get('secondary', 'email') ?? ''),
  set: (value: string) => set('secondary', 'email', value),
});
const secondaryEditing = computed(() =>
  typeof get('secondary', 'address_id') === 'string'
    ? String(get('secondary', 'address_id'))
    : null,
);
const secondaryConflicts = computed(() =>
  props.controller.conflicts.value.filter((conflict) =>
    conflict.path.startsWith('secondary.'),
  ),
);
const secondaryForm = ref<{ focusEmail: () => void }>();
async function editSecondary(publicId: string) {
  if (props.controller.busy.value) return;
  const address = addresses.value.find((row) => row.public_id === publicId);
  if (!address) return;
  set('secondary', 'address_id', address.public_id);
  secondaryEmail.value = address.email;
  await nextTick();
  secondaryForm.value?.focusEmail();
}
function cancelSecondary() {
  if (props.controller.busy.value) return;
  for (const key of ['address_id', 'email'])
    set(
      'secondary',
      key,
      props.controller.records.value.secondary?.values[key] ?? null,
    );
}
function submitSecondary() {
  const command = secondaryEditing.value ? 'change' : 'add';
  if (
    props.controller.busy.value ||
    secondaryConflicts.value.length ||
    !available(secondary.value, command)
  )
    return;
  emit('submit', 'secondary', command, []);
}
function resendSecondary(publicId: string) {
  const address = addresses.value.find((row) => row.public_id === publicId);
  if (
    !address ||
    props.controller.busy.value ||
    !available(secondary.value, 'change')
  )
    return;
  // A separate reviewed command must not overwrite an unrelated dirty editor.
  emit('operation', 'secondary', 'change', {
    address_id: publicId,
    email: address.email,
  });
}
watch(
  [
    () => props.controller.records.value.primary_email?.public_id,
    () => props.controller.records.value.secondary?.public_id,
    () => props.page.state.value,
  ],
  (next, before) => {
    if (
      next[2] !== 'ready' ||
      (before &&
        before.some(
          (value, index) => index < 2 && value && value !== next[index],
        ))
    )
      editing.value = undefined;
  },
);
function close() {
  if (props.controller.busy.value) return;
  const section = activeSection.value;
  if (section) {
    for (const key of ['email', 'replacement_email', 'address_id'])
      if (section.fields.some((field) => field.key === key))
        set(
          section.key,
          key,
          props.controller.records.value[section.key]?.values[key] ?? null,
        );
  }
  editing.value = undefined;
}
function submit() {
  attempted.value = true;
  if (issue.value || conflicts.value.length || !activeSection.value) return;
  const section = activeSection.value.key;
  const command = method.value;
  if (!available(activeSection.value, command)) return;
  editing.value = undefined;
  emit('submit', section, command, []);
}
function remove(publicId: string) {
  emit('operation', 'secondary', 'remove', { address_id: publicId });
}
</script>
<template>
  <Card data-user-section="emails">
    <template #header
      ><h2>{{ text('Adresy e-mail', 'Email addresses') }}</h2></template
    >
    <ContentLoading
      :loading="!settled"
      :refreshing="refreshing"
      shape="detail"
      :label="t('Loading record details')"
      class="space-y-5"
    >
      <Alert v-if="missing" tone="warning"
        >{{ t('Unable to load the current record.')
        }}<Button
          intent="inspect"
          :busy="refreshing"
          size="sm"
          @click="retry"
          >{{ t('Retry loading') }}</Button
        ></Alert
      >
      <div
        v-if="primary && controller.records.value.primary_email"
        class="flex flex-wrap items-center justify-between gap-3"
      >
        <div class="min-w-0">
          <h3 class="text-xs font-semibold text-muted">
            {{ text('Główny adres', 'Primary email') }}
          </h3>
          <p class="mt-1 break-words">
            {{ get('primary_email', 'current_email') ?? '—' }}
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button
            v-if="
              get('primary_email', 'verified') === false &&
              available(primary, 'verify')
            "
            intent="commit"
            variant="outline"
            size="sm"
            :disabled="controller.busy.value"
            @click="emit('operation', 'primary_email', 'verify', {})"
            >{{
              text('Potwierdź jako administrator', 'Verify as administrator')
            }}</Button
          >
          <Button
            v-if="available(primary, 'change') || available(primary, 'replace')"
            intent="inspect"
            size="sm"
            variant="outline"
            :disabled="
              controller.busy.value || !controller.records.value.primary_email
            "
            @click="open()"
            >{{ text('Zmień adres', 'Change email') }}</Button
          >
        </div>
      </div>
      <AuthSecondaryEmailFields
        v-if="secondary && controller.records.value.secondary"
        ref="secondaryForm"
        :locale="locale"
        :addresses="addresses"
        :limit="Number(get('secondary', 'secondary_email_limit') ?? 0)"
        :email="secondaryEmail"
        :editing="secondaryEditing"
        :busy="controller.busy.value"
        :can-add="available(secondary, 'add')"
        :can-change="available(secondary, 'change')"
        :can-remove="available(secondary, 'remove')"
        :can-verify="available(secondary, 'verify')"
        :conflicted="!!secondaryConflicts.length"
        :error="
          (
            controller.errors.value[
              controller.fieldPath('secondary', 'email')
            ] ?? []
          ).map(t)
        "
        @update:email="secondaryEmail = $event"
        @edit="editSecondary"
        @resend="resendSecondary"
        @verify="
          emit('operation', 'secondary', 'verify', { address_id: $event })
        "
        @remove="remove"
        @submit="submitSecondary"
        @cancel="cancelSecondary"
      >
        <template #before-field>
          <FormConflict
            v-for="conflict in secondaryConflicts"
            :key="conflict.path"
            :locale="locale"
            :current="String(conflict.remote ?? '—')"
            :busy="controller.busy.value"
            @accept="controller.resolve(conflict.path, 'remote')"
            @keep="controller.resolve(conflict.path, 'local')"
          />
        </template>
      </AuthSecondaryEmailFields>
    </ContentLoading>
  </Card>
  <Dialog
    :model-value="!!editing"
    :locale="locale"
    :title="text('Zmień główny adres e-mail', 'Change primary email')"
    :busy="controller.busy.value"
    @update:model-value="!$event && close()"
  >
    <Form
      :busy="controller.busy.value"
      :valid="!issue && !conflicts.length"
      @invalid="attempted = true"
      @submit="submit"
    >
      <RadioGroup
        v-if="available(primary, 'change') && available(primary, 'replace')"
        v-model="method"
        :label="text('Potwierdzenie adresu', 'Email verification')"
        :options="[
          {
            value: 'change',
            label: text(
              'Wyślij link potwierdzający',
              'Send a confirmation link',
            ),
          },
          {
            value: 'replace',
            label: text(
              'Potwierdź jako administrator',
              'Verify as administrator',
            ),
          },
        ]"
      />
      <TextInput
        v-model="email"
        type="email"
        autocomplete="email"
        :label="text('Adres e-mail', 'Email address')"
        required
        :maxlength="254"
        :error="[
          ...(attempted && issue ? [issue] : []),
          ...(activeSection
            ? (
                controller.errors.value[
                  controller.fieldPath(activeSection.key, fieldKey)
                ] ?? []
              ).map(t)
            : []),
        ]"
      />
      <FormConflict
        v-for="conflict in conflicts"
        :key="conflict.path"
        :locale="locale"
        :current="String(conflict.remote ?? '—')"
        :busy="controller.busy.value"
        @accept="controller.resolve(conflict.path, 'remote')"
        @keep="controller.resolve(conflict.path, 'local')"
      />
      <template #actions
        ><Button intent="commit" type="submit">{{
          method === 'change'
            ? text('Wyślij link zmiany', 'Send change link')
            : text('Zapisz adres', 'Save email')
        }}</Button
        ><Button intent="cancel" @click="close">{{
          t('Cancel')
        }}</Button></template
      >
    </Form>
  </Dialog>
</template>

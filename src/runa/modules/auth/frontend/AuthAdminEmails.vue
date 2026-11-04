<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useAdminLocale } from '@/runa/admin';
import type {
  AdminJson,
  AdminSectionData,
  AdminSectionPresentationProps,
} from '@/runa/admin';
import {
  Alert,
  BooleanBadge,
  Button,
  Card,
  ContentLoading,
  Dialog,
  Form,
  FormConflict,
  RadioGroup,
  TextInput,
} from '@/runa/ui';
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
const editing = ref<'primary' | 'secondary'>();
const method = ref('change');
const attempted = ref(false);
const activeSection = computed(() =>
  editing.value === 'primary' ? primary.value : secondary.value,
);
const fieldKey = computed(() =>
  editing.value === 'primary' && method.value === 'replace'
    ? 'replacement_email'
    : 'email',
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
function open(
  kind: 'primary' | 'secondary',
  address?: { public_id: string; email: string },
) {
  attempted.value = false;
  editing.value = kind;
  if (kind === 'primary')
    method.value = available(primary.value, 'change') ? 'change' : 'replace';
  else {
    set('secondary', 'address_id', address?.public_id ?? null);
    set('secondary', 'email', address?.email ?? '');
  }
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
  const command =
    editing.value === 'primary'
      ? method.value
      : get('secondary', 'address_id')
        ? 'change'
        : 'add';
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
      shape="detail"
      :label="t('Loading record details')"
      class="space-y-5"
    >
      <Alert v-if="missing" tone="warning"
        >{{ t('Unable to load the current record.')
        }}<Button size="sm" @click="controller.refresh">{{
          t('Retry loading')
        }}</Button></Alert
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
        <Button
          v-if="available(primary, 'change') || available(primary, 'replace')"
          size="sm"
          variant="outline"
          :disabled="
            controller.busy.value || !controller.records.value.primary_email
          "
          @click="open('primary')"
          >{{ text('Zmień adres', 'Change email') }}</Button
        >
      </div>
      <div
        v-if="secondary && controller.records.value.secondary"
        class="space-y-3"
        data-secondary-emails
      >
        <div class="flex flex-wrap items-center justify-between gap-3">
          <h3 class="text-sm font-semibold">
            {{ text('Dodatkowe adresy', 'Secondary emails') }}
          </h3>
          <Button
            v-if="available(secondary, 'add')"
            size="sm"
            variant="outline"
            :disabled="
              controller.busy.value || !controller.records.value.secondary
            "
            @click="open('secondary')"
            >{{ text('Dodaj adres', 'Add email') }}</Button
          >
        </div>
        <div
          v-for="address in addresses"
          :key="address.public_id"
          class="flex flex-wrap items-center justify-between gap-3 border-b border-line py-3 last:border-0"
        >
          <div class="min-w-0 space-y-1">
            <p class="break-words">{{ address.email }}</p>
            <div class="flex items-center gap-2 text-xs text-muted">
              {{ text('Potwierdzony', 'Verified') }}
              <BooleanBadge :value="address.verified" :locale="locale" />
            </div>
          </div>
          <div class="flex flex-wrap gap-2">
            <Button
              v-if="available(secondary, 'change')"
              size="sm"
              variant="outline"
              :disabled="controller.busy.value"
              @click="open('secondary', address)"
              >{{ text('Edytuj', 'Edit') }}</Button
            ><Button
              v-if="available(secondary, 'remove')"
              size="sm"
              variant="outline"
              tone="danger"
              :disabled="controller.busy.value"
              @click="remove(address.public_id)"
              >{{ text('Usuń', 'Remove') }}</Button
            >
          </div>
        </div>
        <p v-if="!addresses.length" class="text-sm text-muted">
          {{
            text('Brak dodatkowych adresów.', 'No secondary email addresses.')
          }}
        </p>
      </div>
    </ContentLoading>
  </Card>
  <Dialog
    :model-value="!!editing"
    :locale="locale"
    :title="
      editing === 'primary'
        ? text('Zmień główny adres e-mail', 'Change primary email')
        : get('secondary', 'address_id')
          ? text('Edytuj adres e-mail', 'Edit email')
          : text('Dodaj adres e-mail', 'Add email')
    "
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
        v-if="
          editing === 'primary' &&
          available(primary, 'change') &&
          available(primary, 'replace')
        "
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
            ? (controller.errors.value[
                controller.fieldPath(activeSection.key, fieldKey)
              ] ?? [])
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
        ><Button type="submit" variant="outline">{{
          editing === 'primary' && method === 'change'
            ? text('Wyślij link zmiany', 'Send change link')
            : text('Zapisz adres', 'Save email')
        }}</Button
        ><Button @click="close">{{ t('Cancel') }}</Button></template
      >
    </Form>
  </Dialog>
</template>

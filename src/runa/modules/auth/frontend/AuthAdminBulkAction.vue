<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { AdminField, AdminSection, useAdminLocale } from '@/runa/admin';
import type {
  AdminFieldValue,
  AdminComponentRegistry,
  AdminSectionPresentationProps,
} from '@/runa/admin';
import { authBulkGroup } from './bulkGroups';
import { Button, ContentLoading, FormNotifications, Select } from '@/runa/ui';
import AuthOperationProofFields from './AuthOperationProofFields.vue';

const props = defineProps<
  AdminSectionPresentationProps & {
    components?: AdminComponentRegistry | undefined;
  }
>();
const { locale, t: tr } = useAdminLocale();
type OperationChoice = {
  value: string;
  label: string;
  command: AdminSectionPresentationProps['section']['commands'][number];
  enabled: boolean | null;
  clearDate?: boolean;
};
const options = computed(() =>
  props.section.commands.flatMap<OperationChoice>((command) => {
    if (command.key === 'set_attempt_lock')
      return [
        {
          value: 'set_attempt_lock:set',
          label: tr({
            pl: 'Ustaw blokadę do terminu',
            en: 'Lock until a deadline',
          }),
          command,
          enabled: null,
          clearDate: false,
        },
        {
          value: 'set_attempt_lock:clear',
          label: tr({ pl: 'Usuń blokadę', en: 'Clear lock' }),
          command,
          enabled: null,
          clearDate: true,
        },
      ];
    if (command.key === 'set_security_lock')
      return [
        {
          value: 'set_security_lock:on',
          label: tr({ pl: 'Włącz blokadę', en: 'Enable lock' }),
          command,
          enabled: true,
        },
        {
          value: 'set_security_lock:off',
          label: tr({ pl: 'Wyłącz blokadę', en: 'Disable lock' }),
          command,
          enabled: false,
        },
      ];
    return [
      { value: command.key, label: tr(command.title), command, enabled: null },
    ];
  }),
);
const selected = ref(options.value[0]?.value ?? '');
const choice = computed(() =>
  options.value.find((option) => option.value === selected.value),
);
const command = computed(() => choice.value?.command);
const grouped = computed(
  () => !!authBulkGroup(props.section.commands[0]?.key ?? ''),
);
function selectOperation(value: string) {
  proof.value?.reset();
  proofStep.value = false;
  selected.value = value;
  // Changing only the operation must invalidate cached reviews as well as parameter edits.
  const path = props.controller.fieldPath(props.section.key, 'public_ids');
  props.controller.set(path, props.controller.values.value[path] ?? []);
  if (choice.value?.clearDate)
    props.controller.set(
      props.controller.fieldPath(props.section.key, 'at'),
      null,
    );
  if (choice.value?.enabled !== null && choice.value?.enabled !== undefined)
    props.controller.set(
      props.controller.fieldPath(props.section.key, 'enabled'),
      choice.value.enabled,
    );
}
const proof = computed(() =>
  command.value
    ? props.controller.proof(props.section.key, command.value.key)
    : undefined,
);
const review = computed(
  () =>
    props.controller.commandReviews.value[
      `${props.section.key}:${command.value?.key}`
    ],
);
const summary = computed(() => {
  const value = review.value?.changes.summary;
  return value && typeof value === 'object' && !Array.isArray(value)
    ? value
    : {};
});
const proofStep = ref(false);
const proofRegion = ref<HTMLElement>();
const fields = computed(() =>
  props.section.fields
    .filter(
      (field) =>
        !['public_ids', 'summary'].includes(field.key) &&
        !(grouped.value && field.key === 'enabled') &&
        !(choice.value?.clearDate && field.key === 'at') &&
        command.value?.inputs.includes(field.key),
    )
    .map((field) =>
      ['at', 'enabled'].includes(field.key)
        ? { ...field, label: command.value?.title ?? field.label }
        : field,
    ),
);
const signature = computed(() =>
  JSON.stringify([
    selected.value,
    command.value?.inputs.map(
      (key) =>
        props.controller.values.value[
          props.controller.fieldPath(props.section.key, key)
        ],
    ),
  ]),
);
let attempted = '';
watch(
  [signature, () => props.controller.busy.value],
  ([value, busy]) => {
    if (
      busy ||
      !command.value ||
      !props.controller.records.value[props.section.key] ||
      attempted === value
    )
      return;
    attempted = value;
    void props.controller.reviewCommand(props.section.key, command.value.key);
  },
  { immediate: true },
);
function set(key: string, value: AdminFieldValue) {
  proofStep.value = false;
  proof.value?.reset();
  props.controller.set(
    props.controller.fieldPath(props.section.key, key),
    value,
  );
}
async function execute() {
  if (!command.value || !review.value) return;
  await props.controller.execute(props.section.key, command.value.key, {
    confirmReviewed: true,
  });
  proofStep.value = false;
}
async function submit() {
  if (!review.value || !command.value) return;
  if (proof.value && proof.value.requirement.value !== 'none') {
    proofStep.value = true;
    await proof.value.begin();
    await nextTick();
    proofRegion.value?.querySelector('input')?.focus();
  } else await execute();
}
async function confirm() {
  await proof.value?.confirm();
  if (proof.value?.confirmed.value) await execute();
}
function cancelProof() {
  proof.value?.reset();
  proofStep.value = false;
}
</script>
<template>
  <AdminSection :section="section" :controller="controller" embedded>
    <div
      v-if="proofStep && proof"
      ref="proofRegion"
      class="space-y-4 sm:col-span-2"
      data-bulk-proof
      @keydown.enter.prevent="confirm"
    >
      <h2 class="font-semibold">
        {{ tr({ pl: 'Potwierdź hasłem', en: 'Confirm with password' }) }}
      </h2>
      <AuthOperationProofFields
        :controller="proof"
        :locale="locale"
        @confirm="confirm"
        @cancel="cancelProof"
      />
      <FormNotifications :locale="locale" :error="proof.error.value" />
    </div>
    <div v-else class="space-y-5 sm:col-span-2" data-bulk-review>
      <Select
        v-if="grouped"
        :label="tr({ pl: 'Operacja', en: 'Operation' })"
        :model-value="selected"
        :options="options"
        :locale="locale"
        :disabled="controller.busy.value || section.readonly"
        @update:model-value="selectOperation"
      />
      <AdminField
        v-for="field in fields"
        :key="field.key"
        :field="field"
        :model-value="
          controller.values.value[
            controller.fieldPath(section.key, field.key)
          ] ?? null
        "
        :errors="
          controller.errors.value[
            controller.fieldPath(section.key, field.key)
          ] ?? []
        "
        :readonly="section.readonly"
        :busy="controller.busy.value"
        :components="components"
        @update:model-value="set(field.key, $event)"
        @availability="
          controller.setAvailable(
            controller.fieldPath(section.key, field.key),
            $event,
          )
        "
      />
      <ContentLoading
        v-if="controller.busy.value && !review"
        shape="form"
        :label="tr({ pl: 'Sprawdzanie skutków', en: 'Checking impact' })"
      />
      <dl v-if="review" class="grid gap-4 sm:grid-cols-2" aria-live="polite">
        <div
          v-for="item in [
            {
              key: 'affected_accounts',
              label: { pl: 'Konta objęte zmianą', en: 'Affected accounts' },
            },
            {
              key: 'sessions',
              label: { pl: 'Unieważniane sesje', en: 'Sessions to revoke' },
            },
            {
              key: 'trusted_devices',
              label: { pl: 'Unieważniane urządzenia', en: 'Devices to revoke' },
            },
          ].filter((item) => typeof summary[item.key] === 'number')"
          :key="item.key"
        >
          <dt class="text-sm text-muted">{{ tr(item.label) }}</dt>
          <dd class="text-lg font-semibold">{{ summary[item.key] }}</dd>
        </div>
      </dl>
      <p v-if="summary.affected_accounts === 0" class="text-sm text-muted">
        {{
          tr({
            pl: 'Żadne z wybranych kont nie wymaga tej zmiany.',
            en: 'None of the selected accounts needs this change.',
          })
        }}
      </p>
      <div class="flex flex-wrap gap-2">
        <Button
          v-if="!review && command"
          :disabled="controller.busy.value"
          @click="controller.reviewCommand(section.key, command.key)"
          >{{ tr({ pl: 'Sprawdź skutki', en: 'Review impact' }) }}</Button
        >
        <Button
          :disabled="
            controller.busy.value ||
            !review ||
            summary.affected_accounts === 0 ||
            section.readonly ||
            !command?.available
          "
          @click="submit"
          >{{ tr({ pl: 'Zatwierdź', en: 'Confirm' }) }}</Button
        >
      </div>
    </div>
  </AdminSection>
</template>

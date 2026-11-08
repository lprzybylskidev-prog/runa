<script setup lang="ts">
import { computed, ref } from 'vue';
import { useAdminLocale } from '@/runa/admin';
import type { AdminJson, AdminSectionPresentationProps } from '@/runa/admin';
import {
  actionIcons,
  Alert,
  Badge,
  Button,
  Card,
  ContentLoading,
} from '@/runa/ui';
import AuthPolicyExceptionFacts from './AuthPolicyExceptionFacts.vue';
import { policyRuleNames, usePolicyExceptions } from './policyExceptions';
const props = defineProps<AdminSectionPresentationProps>();
const emit = defineEmits<{
  operation: [
    section: string,
    command: string,
    values: Record<string, AdminJson>,
  ];
}>();
const { t } = useAdminLocale();
const { active } = usePolicyExceptions(props.controller, props.section.key);
const settled = ref(false);
const refreshing = ref(false);
async function load(retry = false) {
  refreshing.value = retry;
  try {
    if (retry) await props.controller.refresh();
    else await props.controller.activateSection(props.section.key);
  } finally {
    settled.value = true;
    refreshing.value = false;
  }
}
void load();
const record = computed(
  () => props.controller.records.value[props.section.key],
);
const keys = computed(() => [
  ...new Set([
    ...(props.section.fields.find((field) => field.key === 'key')?.choices ??
      []),
    ...active.value.map((row) => row.key),
  ]),
]);
const writable = computed(
  () =>
    !props.section.readonly &&
    props.section.commands.some(
      (command) => command.key === 'set' && command.available,
    ),
);
const choices = computed(
  () =>
    props.section.fields.find((field) => field.key === 'key')?.choices ?? [],
);
function operation(key: string, revoke = false) {
  const current = active.value.find((row) => row.key === key);
  emit('operation', props.section.key, 'set', {
    key,
    revoke,
    value: revoke ? null : (current?.value ?? null),
    reason: revoke ? '' : (current?.reason ?? ''),
    expires_at: revoke ? null : (current?.expires_at ?? null),
  });
}
</script>
<template>
  <Card data-user-section="exceptions">
    <template #header
      ><h2>
        {{ t({ pl: 'Zasady użytkownika', en: 'User rules' }) }}
      </h2></template
    >
    <ContentLoading
      :loading="!settled && !record"
      :refreshing="refreshing"
      shape="detail"
      :label="t('Loading record details')"
    >
      <Alert v-if="!record" tone="warning"
        >{{ t('Unable to load the current record.') }}
        <Button
          :icon="actionIcons.refresh"
          intent="inspect"
          :busy="refreshing"
          size="sm"
          @click="load(true)"
          >{{ t('Retry loading') }}</Button
        ></Alert
      >
      <template v-else>
        <div class="divide-y divide-line">
          <section
            v-for="key in keys"
            :key="key"
            class="space-y-3 py-4 first:pt-0"
            :data-policy-rule="key"
          >
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div class="flex flex-wrap items-center gap-2">
                <h3 class="text-sm font-semibold">
                  {{ t(policyRuleNames[key] ?? key) }}
                </h3>
                <Badge
                  :tone="
                    active.some((row) => row.key === key) ? 'info' : 'neutral'
                  "
                  >{{
                    active.some((row) => row.key === key)
                      ? t({ pl: 'Własna zasada', en: 'Custom rule' })
                      : t({ pl: 'Zasada ogólna', en: 'Global rule' })
                  }}</Badge
                >
              </div>
              <div v-if="writable" class="flex flex-wrap gap-2">
                <Button
                  v-if="choices.includes(key)"
                  :icon="actionIcons.edit"
                  intent="commit"
                  size="sm"
                  variant="outline"
                  :disabled="controller.busy.value"
                  :aria-label="
                    t({ pl: 'Ustaw: ', en: 'Set: ' }) +
                    t(policyRuleNames[key] ?? key)
                  "
                  @click="operation(key)"
                  >{{
                    active.some((row) => row.key === key)
                      ? t({ pl: 'Edytuj', en: 'Edit' })
                      : t({ pl: 'Ustaw własną', en: 'Set custom' })
                  }}</Button
                >
                <Button
                  v-if="active.some((row) => row.key === key)"
                  icon="rotate-2"
                  intent="caution"
                  size="sm"
                  variant="outline"
                  :disabled="controller.busy.value"
                  @click="operation(key, true)"
                  >{{
                    t({ pl: 'Przywróć ogólną', en: 'Restore global' })
                  }}</Button
                >
              </div>
            </div>
            <template
              v-for="exception in active.filter((row) => row.key === key)"
              :key="exception.public_id"
              ><AuthPolicyExceptionFacts :exception="exception"
            /></template>
          </section>
        </div>
      </template>
    </ContentLoading>
  </Card>
</template>

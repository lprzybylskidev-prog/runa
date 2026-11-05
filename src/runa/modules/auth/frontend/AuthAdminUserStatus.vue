<script setup lang="ts">
import { computed, ref } from 'vue';
import { AdminField, useAdminLocale } from '@/runa/admin';
import type {
  AdminSectionData,
  AdminSectionPresentationProps,
  AdminComponentRegistry,
} from '@/runa/admin';
import AuthPolicyExceptionFacts from './AuthPolicyExceptionFacts.vue';
import { policyRuleNames, usePolicyExceptions } from './policyExceptions';
import { Alert, Button, Card, ContentLoading } from '@/runa/ui';
const props = defineProps<
  AdminSectionPresentationProps & {
    sources: AdminSectionData[];
    exceptionsSource?: AdminSectionData | undefined;
    components?: AdminComponentRegistry | undefined;
  }
>();
const { t } = useAdminLocale();
const { active } = usePolicyExceptions(props.controller, 'exceptions');
const settled = ref(false);
const refreshing = ref(false);
async function load(retry = false) {
  refreshing.value = retry;
  try {
    if (retry) await props.controller.refresh();
    else
      await Promise.all(
        [
          ...props.sources,
          ...(props.exceptionsSource ? [props.exceptionsSource] : []),
        ].map((source) => props.controller.activateSection(source.key)),
      );
  } finally {
    settled.value = true;
    refreshing.value = false;
  }
}
void load();
const facts = computed(() => {
  const seen = new Set<string>();
  return props.sources.flatMap((source) =>
    source.fields.flatMap((field) => {
      if (
        (source.key === 'primary_email' &&
          !['current_email', 'verified', 'verification_required'].includes(
            field.key,
          )) ||
        !field.readonly ||
        [
          'display_name',
          'first_name',
          'surname',
          'login',
          'has_avatar',
          'avatar_initials',
          'avatar_max_kib',
          'operation',
          'at',
          'enabled',
          'replacement_email',
        ].includes(field.key)
      )
        return [];
      const key = field.key === 'current_email' ? 'email' : field.key;
      if (seen.has(key)) return [];
      seen.add(key);
      return [{ source, field }];
    }),
  );
});
const factGroups = computed(() => {
  const definitions = [
    {
      key: 'lifecycle',
      title: { pl: 'Aktywność konta', en: 'Account activity' },
      fields: [
        'state',
        'active',
        'deleted',
        'activate_at',
        'deactivate_at',
        'created_at',
        'updated_at',
        'deleted_at',
      ],
    },
    {
      key: 'locks',
      title: { pl: 'Blokady', en: 'Locks' },
      fields: [
        'security_locked',
        'attempt_locked_until',
        'operator_recovery_pending',
      ],
    },
    {
      key: 'email',
      title: { pl: 'Adres e-mail', en: 'Email address' },
      fields: [
        'email',
        'current_email',
        'verified',
        'email_verified_at',
        'verification_required',
      ],
    },
    {
      key: 'credentials',
      title: {
        pl: 'Hasło i uwierzytelnianie',
        en: 'Password and authentication',
      },
      fields: [
        'password_set',
        'password_changed_at',
        'password_change_required',
        'password_expires_at',
        'password_expired',
        'two_factor_enabled',
        'two_factor_required',
        'totp_enrolled',
        'email_factor_enrolled',
        'recovery_codes_remaining',
        'totp_setup_pending',
        'totp_pending_until',
      ],
    },
    {
      key: 'sessions',
      title: { pl: 'Sesje i urządzenia', en: 'Sessions and devices' },
      fields: [
        'logged_in',
        'session_count',
        'trusted_device_count',
        'last_login_at',
        'last_activity_at',
        'authenticated_at',
        'activity_at',
      ],
    },
    {
      key: 'invitations',
      title: { pl: 'Zaproszenie', en: 'Invitation' },
      fields: [
        'invitation_pending',
        'invitation_sent_at',
        'invitation_expires_at',
      ],
    },
  ];
  const groups = definitions.map((group) => ({
    ...group,
    facts: facts.value
      .filter(({ field }) => group.fields.includes(field.key))
      .sort(
        (a, b) =>
          group.fields.indexOf(a.field.key) - group.fields.indexOf(b.field.key),
      ),
  }));
  const other = facts.value.filter(
    ({ field }) =>
      !definitions.some((group) => group.fields.includes(field.key)),
  );
  if (other.length)
    groups.push({
      key: 'other',
      title: { pl: 'Pozostałe informacje', en: 'Other information' },
      fields: [],
      facts: other,
    });
  return groups.filter((group) => group.facts.length);
});
const missing = computed(() =>
  [
    ...props.sources,
    ...(props.exceptionsSource ? [props.exceptionsSource] : []),
  ].some((source) => !props.controller.records.value[source.key]),
);
</script>
<template>
  <Card data-user-section="status">
    <template #header
      ><h2>{{ t({ pl: 'Stan konta', en: 'Account status' }) }}</h2></template
    >
    <ContentLoading
      :loading="!settled && missing"
      :refreshing="refreshing"
      shape="detail"
      :label="t('Loading record details')"
    >
      <Alert v-if="missing" tone="warning"
        >{{ t('Unable to load the current record.') }}
        <Button :busy="refreshing" size="sm" @click="load(true)">{{
          t('Retry loading')
        }}</Button></Alert
      >
      <div class="divide-y divide-line">
        <section
          v-for="group in factGroups"
          :key="group.key"
          class="py-4 first:pt-0 last:pb-0"
          :aria-label="t(group.title)"
          :data-status-group="group.key"
        >
          <h3 class="mb-3 text-xs font-semibold text-muted">
            {{ t(group.title) }}
          </h3>
          <div
            class="grid min-w-0 gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3"
          >
            <AdminField
              v-for="{ source, field } in group.facts"
              :key="source.key + ':' + field.key"
              :field="field"
              :model-value="
                controller.values.value[
                  controller.fieldPath(source.key, field.key)
                ] ?? null
              "
              :errors="[]"
              readonly
              :busy="controller.busy.value"
              :components="components"
            />
          </div>
        </section>
        <section
          v-if="exceptionsSource && active.length"
          class="py-4 last:pb-0"
          data-status-group="custom_rules"
          :aria-label="t({ pl: 'Własne zasady', en: 'Custom rules' })"
        >
          <h3 class="mb-3 text-xs font-semibold text-muted">
            {{ t({ pl: 'Własne zasady', en: 'Custom rules' }) }}
          </h3>
          <div class="grid min-w-0 gap-5 lg:grid-cols-2">
            <div
              v-for="exception in active"
              :key="exception.public_id"
              class="min-w-0 space-y-3"
              :data-status-rule="exception.key"
            >
              <h4 class="text-sm font-semibold">
                {{ t(policyRuleNames[exception.key] ?? exception.key) }}
              </h4>
              <AuthPolicyExceptionFacts :exception="exception" />
            </div>
          </div>
        </section>
      </div>
    </ContentLoading>
  </Card>
</template>

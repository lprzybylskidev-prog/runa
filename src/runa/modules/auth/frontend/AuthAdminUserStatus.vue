<script setup lang="ts">
import { computed, ref } from 'vue';
import { AdminField, useAdminLocale } from '@/runa/admin';
import type {
  AdminSectionData,
  AdminSectionPresentationProps,
  AdminComponentRegistry,
} from '@/runa/admin';
import { Alert, Button, Card, ContentLoading } from '@/runa/ui';
const props = defineProps<
  AdminSectionPresentationProps & {
    sources: AdminSectionData[];
    components?: AdminComponentRegistry | undefined;
  }
>();
const { t } = useAdminLocale();
const settled = ref(false);
const refreshing = ref(false);
async function load(retry = false) {
  refreshing.value = retry;
  try {
    if (retry) await props.controller.refresh();
    else
      await Promise.all(
        props.sources.map((source) =>
          props.controller.activateSection(source.key),
        ),
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
const missing = computed(() =>
  props.sources.some((source) => !props.controller.records.value[source.key]),
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
      <div class="grid min-w-0 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <AdminField
          v-for="{ source, field } in facts"
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
    </ContentLoading>
  </Card>
</template>

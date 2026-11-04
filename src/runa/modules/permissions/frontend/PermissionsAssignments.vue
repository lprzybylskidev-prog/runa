<script setup lang="ts">
import { computed, ref } from 'vue';
import { useAdminLocale } from '@/runa/admin';
import type { AdminSectionPresentationProps } from '@/runa/admin';
import {
  Card,
  Button,
  ContentLoading,
  Form,
  FormConflict,
  formValuesEqual,
} from '@/runa/ui';
import PermissionsAssignmentList from './PermissionsAssignmentList.vue';
const props = defineProps<AdminSectionPresentationProps>();
const emit = defineEmits<{
  submit: [section: string, command: string, fields?: string[]];
}>();
const { locale, t } = useAdminLocale();
const settled = ref(false);
void props.controller.activateSection(props.section.key).finally(() => {
  settled.value = true;
});
const record = computed(
  () => props.controller.records.value[props.section.key],
);
const conflicts = computed(() =>
  props.controller.conflicts.value.filter((conflict) =>
    ['roles', 'permissions'].some(
      (key) =>
        props.controller.fieldPath(props.section.key, key) === conflict.path,
    ),
  ),
);
const writable = computed(
  () =>
    !props.section.readonly &&
    props.section.commands.some(
      (command) => command.key === 'save' && command.available,
    ),
);
const fields = computed(() =>
  (['roles', 'permissions'] as const).filter((key) =>
    props.section.fields.some((field) => field.key === key),
  ),
);
const dirty = computed(() =>
  ['roles', 'permissions'].some(
    (key) =>
      !formValuesEqual(
        props.controller.values.value[
          props.controller.fieldPath(props.section.key, key)
        ] ?? [],
        record.value?.values[key] ?? [],
      ),
  ),
);
function revert() {
  for (const key of ['roles', 'permissions'])
    props.controller.set(
      props.controller.fieldPath(props.section.key, key),
      record.value?.values[key] ?? [],
    );
  for (const conflict of conflicts.value)
    props.controller.resolve(conflict.path, 'remote');
}
</script>
<template>
  <Card data-user-section="assignments">
    <template #header
      ><h2>
        {{ t({ pl: 'Role i uprawnienia', en: 'Roles and permissions' }) }}
      </h2></template
    >
    <ContentLoading
      :loading="!record && !settled"
      shape="form"
      :label="t('Loading record details')"
    >
      <Form
        v-if="record"
        :label="t(section.title)"
        :busy="controller.busy.value"
        :valid="!conflicts.length"
        @submit="emit('submit', section.key, 'save', [])"
      >
        <FormConflict
          v-for="conflict in conflicts"
          :key="conflict.path"
          :locale="locale"
          :current="
            t({
              pl: 'Przypisania zostały zmienione w innym miejscu.',
              en: 'Assignments changed elsewhere.',
            })
          "
          :busy="controller.busy.value"
          @accept="controller.resolve(conflict.path, 'remote')"
          @keep="controller.resolve(conflict.path, 'local')"
        />
        <div class="grid min-w-0 gap-6 lg:grid-cols-2">
          <PermissionsAssignmentList
            v-for="field in fields"
            :key="field"
            :page="page"
            :section="section"
            :controller="controller"
            :field="field"
          />
        </div>
        <template v-if="dirty && writable" #actions
          ><Button
            type="submit"
            variant="outline"
            :disabled="!!conflicts.length"
            >{{ t({ pl: 'Sprawdź zmiany', en: 'Review changes' }) }}</Button
          ><Button @click="revert">{{
            t({ pl: 'Cofnij zmiany', en: 'Revert changes' })
          }}</Button></template
        >
      </Form>
      <div v-else class="space-y-3">
        <p>{{ t('Unable to load the current record.') }}</p>
        <Button @click="controller.refresh">{{ t('Retry loading') }}</Button>
      </div>
    </ContentLoading>
  </Card>
</template>

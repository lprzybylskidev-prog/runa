<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useAdminLocale } from '@/runa/admin';
import type { AdminSectionPresentationProps } from '@/runa/admin';
import {
  actionIcons,
  Card,
  Button,
  ContentLoading,
  Form,
  FormConflict,
  formValuesEqual,
} from '@/runa/ui';
import PermissionsAssignmentList from './PermissionsAssignmentList.vue';
import PermissionsAccessImport from './PermissionsAccessImport.vue';
const props = defineProps<AdminSectionPresentationProps>();
const emit = defineEmits<{
  submit: [section: string, command: string, fields?: string[]];
}>();
const { locale, t } = useAdminLocale();
const settled = ref(false);
async function retry() {
  settled.value = false;
  try {
    await props.controller.refresh();
  } finally {
    settled.value = true;
  }
}
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
const active = ref<'roles' | 'permissions'>('roles');
const count = (key: string) => {
  const value =
    props.controller.values.value[
      props.controller.fieldPath(props.section.key, key)
    ];
  return Array.isArray(value) ? value.length : 0;
};
watch(
  fields,
  (value) => {
    if (!value.includes(active.value) && value[0]) active.value = value[0];
  },
  { immediate: true },
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
        :valid="writable && dirty && !conflicts.length"
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
        <div class="min-w-0 rounded border border-line" data-access-editor>
          <div
            class="flex gap-1 border-b border-line px-2"
            role="group"
            :aria-label="t({ pl: 'Rodzaj przypisania', en: 'Assignment type' })"
          >
            <button
              v-for="field in fields"
              :key="field"
              type="button"
              class="flex min-h-10 min-w-0 flex-1 cursor-pointer disabled:cursor-default hover:bg-primary/5 active:bg-primary/10 motion-reduce:transition-none items-center justify-center gap-2 border-b-2 px-3 py-2 text-xs font-medium sm:flex-none transition-colors focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-accent"
              :class="
                active === field
                  ? 'border-primary text-primary'
                  : 'border-transparent text-muted hover:bg-primary/5 hover:text-ink'
              "
              :aria-pressed="active === field"
              :data-access-kind="field"
              @click="active = field"
            >
              <span>{{
                field === 'roles'
                  ? t({ pl: 'Role', en: 'Roles' })
                  : t({
                      pl: 'Uprawnienia bezpośrednie',
                      en: 'Direct permissions',
                    })
              }}</span>
              <span
                class="min-w-5 rounded px-1.5 py-0.5 text-center text-xs tabular-nums"
                :class="
                  active === field
                    ? 'bg-primary/10 text-primary'
                    : 'bg-line/30 text-muted'
                "
                >{{ count(field) }}</span
              >
            </button>
          </div>
          <PermissionsAssignmentList
            v-for="field in fields"
            v-show="active === field"
            :key="field"
            :active="active === field"
            :page="page"
            :section="section"
            :controller="controller"
            :field="field"
          />
        </div>
        <PermissionsAccessImport
          :page="page"
          :section="section"
          :controller="controller"
          :dirty="dirty"
          @submit="
            (section, command, fields) =>
              emit('submit', section, command, fields)
          "
        />
        <template v-if="dirty && writable" #actions
          ><Button
            :icon="actionIcons.inspect"
            intent="inspect"
            type="submit"
            variant="outline"
            :disabled="!!conflicts.length"
            >{{ t({ pl: 'Sprawdź zmiany', en: 'Review changes' }) }}</Button
          ><Button
            :icon="actionIcons.discard"
            intent="discard"
            @click="revert"
            >{{ t({ pl: 'Cofnij zmiany', en: 'Revert changes' }) }}</Button
          ></template
        >
      </Form>
      <div v-else class="space-y-3">
        <p>{{ t('Unable to load the current record.') }}</p>
        <Button
          :icon="actionIcons.refresh"
          intent="inspect"
          :busy="controller.busy.value"
          @click="retry"
          >{{ t('Retry loading') }}</Button
        >
      </div>
    </ContentLoading>
  </Card>
</template>

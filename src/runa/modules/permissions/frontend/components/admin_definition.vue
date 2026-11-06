<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import {
  AdminField,
  useAdminLocale,
  useAdminPresentation,
  validateAdminField,
} from '@/runa/admin';
import type { AdminSectionPresentationProps } from '@/runa/admin';
import {
  Button,
  Card,
  ConfirmDialog,
  ContentLoading,
  Form,
  FormConflict,
  FormNotifications,
  ValuePreview,
  formValuesEqual,
} from '@/runa/ui';
import PermissionsAssignmentList from '../PermissionsAssignmentList.vue';
const props = defineProps<AdminSectionPresentationProps>();
const { locale, t } = useAdminLocale();
const presentation = useAdminPresentation();
const memberField = (key: string) =>
  props.section.fields.find((field) => field.key === key)!;
const text = (pl: string, en: string) => t({ pl, en });
const settled = ref(false);
const form = ref<InstanceType<typeof Form>>();
async function load() {
  try {
    await props.controller.activateSection(props.section.key);
  } finally {
    settled.value = true;
  }
}
void load();
const record = computed(
  () => props.controller.records.value[props.section.key],
);
const locked = computed(
  () =>
    props.section.readonly ||
    record.value?.values.system === true ||
    typeof record.value?.values.module === 'string',
);
const force = computed(() => props.section.key === 'force');
const fields = computed(() =>
  props.section.fields
    .filter(
      (field) =>
        ![
          'roles',
          'permissions',
          'review_summary',
          'module',
          'system',
          'search',
          'language',
          'selected',
        ].includes(field.key),
    )
    .map((field) => ({
      ...field,
      readonly:
        field.readonly || (field.key === 'code' && !!record.value?.public_id),
    })),
);
const members = computed(() =>
  (['roles', 'permissions'] as const).filter((key) =>
    props.section.fields.some((field) => field.key === key),
  ),
);
const active = ref<'roles' | 'permissions'>('permissions');
const path = (key: string) =>
  props.controller.fieldPath(props.section.key, key);
const canStore = computed(
  () =>
    !locked.value &&
    props.section.commands.some(
      (command) => command.key === 'store' && command.available,
    ),
);
const conflicts = computed(() =>
  props.controller.conflicts.value.filter((conflict) =>
    props.section.fields.some((field) => path(field.key) === conflict.path),
  ),
);
const dirty = computed(() =>
  props.section.fields.some(
    (field) =>
      !field.readonly &&
      !formValuesEqual(
        props.controller.values.value[path(field.key)] ?? null,
        record.value?.values[field.key] ?? null,
      ),
  ),
);
const commands = computed(() =>
  locked.value || !record.value?.public_id
    ? []
    : props.section.commands.filter(
        (command) => command.available && command.destructive,
      ),
);
function revert() {
  for (const field of props.section.fields)
    props.controller.set(
      path(field.key),
      record.value?.values[field.key] ?? null,
    );
  for (const conflict of conflicts.value)
    props.controller.resolve(conflict.path, 'remote');
}
const pending = ref<string>();
const signature = () =>
  JSON.stringify([
    record.value?.public_id,
    record.value?.revision,
    props.section.fields.map(
      (field) => props.controller.values.value[path(field.key)],
    ),
  ]);
let pinned = '';
const review = computed(() =>
  pending.value
    ? props.controller.commandReviews.value[
        `${props.section.key}:${pending.value}`
      ]
    : undefined,
);
const destructive = computed(
  () =>
    props.section.commands.find((command) => command.key === pending.value)
      ?.destructive === true,
);
const attempt = ref(0);
async function submit(command = 'store') {
  attempt.value++;
  if (command === 'store') {
    let valid = true;
    for (const field of fields.value.filter((field) => !field.readonly)) {
      const errors = [
        ...validateAdminField(
          field,
          props.controller.values.value[path(field.key)] ?? null,
        ),
      ];
      const formErrors = props.controller.errors;
      formErrors.value[path(field.key)] = errors;
      if (errors.length) valid = false;
    }
    if (!valid) {
      await nextTick();
      form.value?.focusInvalid();
      return;
    }
  }
  if (conflicts.value.length || props.controller.busy.value) return;
  if (await props.controller.reviewCommand(props.section.key, command)) {
    pending.value = command;
    pinned = signature();
  }
}
function cancelReview() {
  if (pending.value)
    props.controller.proof(props.section.key, pending.value).reset();
  pending.value = undefined;
}
async function confirm() {
  if (!pending.value || signature() !== pinned) {
    cancelReview();
    return;
  }
  const outcome = await props.controller.execute(
    props.section.key,
    pending.value,
    { confirmReviewed: true },
  );
  cancelReview();
  if (outcome?.status === 'committed') {
    await props.controller.selectSubject(outcome.record?.public_id ?? null);
    const notice = props.controller.notice;
    notice.value = 'Changes saved.';
  }
}
watch([() => props.page.state.value, () => record.value?.public_id], () =>
  cancelReview(),
);
</script>
<template>
  <Card v-if="!force || commands.length">
    <template #header
      ><h2>
        {{
          force
            ? text('Usuwanie powiązanej roli', 'Referenced role deletion')
            : text('Dane podstawowe i uprawnienia', 'Details and permissions')
        }}
      </h2></template
    >
    <FormNotifications
      v-if="!force"
      :locale="locale"
      :attempt="attempt"
      :error="t(controller.error.value?.message ?? '')"
      :success="t(controller.notice.value)"
    />
    <ContentLoading
      :loading="!record && !settled"
      shape="form"
      :label="t('Loading record details')"
    >
      <Form
        v-if="record"
        ref="form"
        :label="t(section.title)"
        :busy="controller.busy.value"
        :valid="canStore && (dirty || !record.public_id) && !conflicts.length"
        @submit="submit()"
      >
        <div v-if="!force" class="grid min-w-0 gap-5 md:grid-cols-2">
          <div
            v-for="field in fields"
            :key="field.key"
            class="min-w-0"
            :class="field.key === 'code' ? 'md:col-span-2' : undefined"
          >
            <AdminField
              :field="field"
              :model-value="controller.values.value[path(field.key)] ?? null"
              :errors="controller.errors.value[path(field.key)] ?? []"
              :readonly="locked || field.readonly"
              :busy="controller.busy.value"
              @update:model-value="controller.set(path(field.key), $event)"
              @availability="controller.setAvailable(path(field.key), $event)"
            />
          </div>
          <dl
            v-if="record.values.module || record.values.system"
            class="text-sm md:col-span-2"
          >
            <dt class="font-medium">{{ text('Źródło', 'Source') }}</dt>
            <dd>{{ record.values.module ?? text('System', 'System') }}</dd>
          </dl>
        </div>
        <FormConflict
          v-for="conflict in conflicts"
          :key="conflict.path"
          :locale="locale"
          :current="
            text(
              'Definicja została zmieniona w innym miejscu.',
              'The definition changed elsewhere.',
            )
          "
          :busy="controller.busy.value"
          @accept="controller.resolve(conflict.path, 'remote')"
          @keep="controller.resolve(conflict.path, 'local')"
        />
        <div
          v-if="!force && members.length"
          class="min-w-0 rounded border border-line"
        >
          <div
            class="flex gap-1 border-b border-line p-2"
            role="group"
            :aria-label="text('Zawartość definicji', 'Definition members')"
          >
            <Button
              v-for="member in members"
              :key="member"
              intent="neutral"
              size="sm"
              :variant="active === member ? 'soft' : 'ghost'"
              :aria-pressed="active === member"
              @click="active = member"
              >{{
                member === 'roles'
                  ? text('Role', 'Roles')
                  : text('Uprawnienia', 'Permissions')
              }}</Button
            >
          </div>
          <div
            v-for="member in members"
            v-show="active === member"
            :key="member"
            class="min-w-0"
          >
            <AdminField
              v-if="memberField(member).component || presentation.field"
              :field="memberField(member)"
              :model-value="controller.values.value[path(member)] ?? []"
              :errors="controller.errors.value[path(member)] ?? []"
              :readonly="locked || memberField(member).readonly"
              :busy="controller.busy.value"
              :lookup="controller.lookup(section.key, member)"
              @update:model-value="controller.set(path(member), $event)"
              @availability="controller.setAvailable(path(member), $event)"
            />
            <PermissionsAssignmentList
              v-else
              :active="active === member"
              :page="page"
              :section="{ ...section, readonly: locked }"
              :controller="controller"
              :field="member"
              command="store"
            />
          </div>
        </div>
        <div
          v-if="commands.length"
          class="flex flex-wrap gap-2"
          :class="!force ? 'border-t border-line pt-4' : ''"
        >
          <Button
            v-for="command in commands"
            :key="command.key"
            intent="danger"
            :disabled="controller.busy.value || !!conflicts.length"
            @click="submit(command.key)"
            >{{
              force
                ? text(
                    'Usuń rolę wraz z przypisaniami',
                    'Delete role and assignments',
                  )
                : text('Usuń', 'Delete')
            }}</Button
          >
        </div>
        <template #actions>
          <template v-if="canStore && (dirty || !record.public_id)">
            <Button
              intent="commit"
              type="submit"
              :disabled="!!conflicts.length"
              >{{ text('Zapisz', 'Save') }}</Button
            >
            <Button
              intent="discard"
              :disabled="controller.busy.value"
              @click="revert"
              >{{ text('Cofnij zmiany', 'Revert changes') }}</Button
            >
          </template>
          <span v-else />
        </template>
      </Form>
      <Button
        v-else
        intent="inspect"
        :busy="controller.busy.value"
        @click="controller.refresh()"
        >{{ t('Retry loading') }}</Button
      >
    </ContentLoading>
    <ConfirmDialog
      :model-value="!!pending"
      :title="
        destructive
          ? text('Czy na pewno usunąć?', 'Confirm deletion?')
          : text('Czy na pewno zapisać zmiany?', 'Save changes?')
      "
      :locale="locale"
      :confirm-intent="destructive ? 'danger' : 'commit'"
      :busy="controller.busy.value"
      @update:model-value="!$event && cancelReview()"
      @confirm="confirm"
      @cancel="cancelReview"
    >
      <ValuePreview
        v-if="review"
        :text="JSON.stringify(review.changes)"
        format="json"
        :locale="locale"
      />
    </ConfirmDialog>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Alert, Button, FormConflict, FormSection } from '@/runa/ui';
import type { AdminSectionProps, AdminSectionSlots } from './contracts';
import { useAdminPresentation } from './presentation';
import AdminField from './AdminField.vue';
import AdminAction from './AdminAction.vue';
import AdminCommandProof from './AdminCommandProof.vue';
const props = defineProps<AdminSectionProps>();
defineSlots<AdminSectionSlots>();
void props.controller.activateSection(props.section.key);
const presentation = useAdminPresentation();
const record = computed(
  () => props.controller.records.value[props.section.key],
);
const fields = computed(() => {
  const paths = new Set(Object.keys(props.controller.values.value));
  return props.section.fields.filter((field) =>
    paths.has(props.controller.fieldPath(props.section.key, field.key)),
  );
});
const commands = computed(() =>
  props.section.commands.filter(
    (command) =>
      command.mode === 'independent' &&
      !command.bulk &&
      (command.intent === 'create'
        ? record.value?.public_id === null
        : command.intent === 'save' || command.intent === 'delete'
          ? record.value?.public_id !== null
          : true),
  ),
);
const conflicts = computed(() =>
  props.controller.conflicts.value.filter((conflict) =>
    props.section.fields.some(
      (field) =>
        props.controller.fieldPath(props.section.key, field.key) ===
        conflict.path,
    ),
  ),
);
</script>
<template>
  <FormSection :title="section.title" :columns="2">
    <slot :section="section" :controller="controller">
      <Alert
        v-if="controller.operations.value[section.key]"
        tone="info"
        class="sm:col-span-2"
      >
        {{
          controller.operations.value[section.key]!.status === 'pending'
            ? 'This operation is pending.'
            : 'This operation requires recovery.'
        }}
        Check its status through the owning module before submitting another
        operation.
      </Alert>
      <Alert
        v-if="section.diagnostic || !record"
        tone="warning"
        class="sm:col-span-2"
      >
        {{
          section.diagnostic
            ? 'This section is currently unavailable.'
            : 'Loading the current record or waiting for a successful read.'
        }}
        <Button
          size="sm"
          :disabled="controller.busy.value"
          @click="controller.refresh"
          >Retry loading</Button
        >
      </Alert>
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
        :readonly="section.readonly || field.readonly"
        :busy="controller.busy.value"
        :lookup="
          field.lookup ? controller.lookup(section.key, field.key) : undefined
        "
        :components="components"
        @update:model-value="
          controller.set(controller.fieldPath(section.key, field.key), $event)
        "
        @availability="
          controller.setAvailable(
            controller.fieldPath(section.key, field.key),
            $event,
          )
        "
      />
      <FormConflict
        v-for="conflict in conflicts"
        :key="conflict.path"
        locale="en"
        :current="JSON.stringify(conflict.remote)"
        :busy="controller.busy.value"
        class="sm:col-span-2"
        @accept="controller.resolve(conflict.path, 'remote')"
        @keep="controller.resolve(conflict.path, 'local')"
      />
      <div
        v-for="command in commands"
        :key="command.key"
        class="space-y-3 sm:col-span-2"
      >
        <template
          v-if="
            controller.commandReviews.value[`${section.key}:${command.key}`]
          "
        >
          <p class="text-sm font-semibold">Reviewed differences</p>
          <dl class="space-y-2 text-sm">
            <div
              v-for="(value, key) in controller.commandReviews.value[
                `${section.key}:${command.key}`
              ]!.changes"
              :key="key"
            >
              <dt class="font-semibold">{{ key }}</dt>
              <dd class="break-words">{{ JSON.stringify(value) }}</dd>
            </div>
          </dl>
        </template>
        <AdminCommandProof
          v-if="
            command.requirement !== 'none' ||
            (controller.commandReviews.value[`${section.key}:${command.key}`]
              ?.requirement !== undefined &&
              controller.commandReviews.value[`${section.key}:${command.key}`]
                ?.requirement !== 'none')
          "
          :command="command"
          :page="controller"
          :section="section.key"
        />
        <div class="flex flex-wrap gap-2">
          <Button
            v-if="command.preview_required"
            :disabled="controller.busy.value || !command.available"
            @click="controller.reviewCommand(section.key, command.key)"
            >Review {{ command.title }}</Button
          >
          <component
            :is="presentation.action ?? AdminAction"
            :command="command"
            :busy="controller.busy.value"
            :disabled="
              !record ||
              !!controller.operations.value[section.key] ||
              !!conflicts.length ||
              !!section.diagnostic ||
              (command.preview_required &&
                !controller.commandReviews.value[
                  `${section.key}:${command.key}`
                ])
            "
            @invoke="controller.execute(section.key, command.key)"
          />
        </div>
      </div>
      <AdminCommandProof
        v-for="command in section.commands.filter(
          (command) =>
            command.mode === 'atomic' &&
            command.intent === 'save' &&
            (command.requirement !== 'none' ||
              controller.review.value?.participants.some(
                (participant) =>
                  participant.id === section.key &&
                  participant.review.requirement !== 'none',
              )),
        )"
        :key="`atomic-proof-${command.key}`"
        :command="command"
        :page="controller"
        :section="section.key"
      />
    </slot>
  </FormSection>
</template>

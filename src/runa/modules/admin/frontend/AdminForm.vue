<script setup lang="ts">
import { computed, nextTick, ref } from 'vue';
import {
  Alert,
  Button,
  ConfirmDialog,
  Form,
  FormNotifications,
} from '@/runa/ui';
import type {
  AdminFormEmits,
  AdminFormProps,
  AdminFormSlots,
} from './contracts';
const props = defineProps<AdminFormProps>();
defineSlots<AdminFormSlots>();
const emit = defineEmits<AdminFormEmits>();
const form = ref<InstanceType<typeof Form>>();
const attempt = ref(0);
const atomic = computed(() =>
  props.controller.sections.value.some((section) =>
    section.commands.some(
      (command) => command.mode === 'atomic' && command.intent === 'save',
    ),
  ),
);
async function save() {
  attempt.value++;
  const result = await props.controller.save();
  if (result) {
    emit('saved', result);
  } else {
    await nextTick();
    form.value?.focusInvalid();
  }
}
</script>
<template>
  <Form
    ref="form"
    label="Administration editor"
    :busy="controller.busy.value"
    :dirty="controller.dirty.value"
    :valid="!controller.blocked.value && !controller.conflicts.value.length"
    @submit="save"
  >
    <slot :controller="controller" />
    <template #feedback>
      <FormNotifications
        locale="en"
        :attempt="attempt"
        :error="controller.error.value?.message ?? ''"
        :success="controller.notice.value"
      />
      <Alert v-if="controller.blocked.value && atomic" tone="warning"
        >Required editor content is unavailable. Saving is blocked.</Alert
      >
    </template>
    <template #actions>
      <slot name="review" :controller="controller">
        <div v-if="controller.review.value" class="space-y-2 text-sm">
          <p class="font-semibold">Reviewed differences</p>
          <div
            v-for="participant in controller.review.value.participants"
            :key="participant.id"
          >
            <p>{{ participant.id }}</p>
            <pre class="whitespace-pre-wrap break-words">{{
              JSON.stringify(participant.review.changes, null, 2)
            }}</pre>
          </div>
        </div>
      </slot>
      <slot name="actions" :controller="controller">
        <Button
          v-if="atomic"
          :disabled="
            controller.blocked.value || !!controller.conflicts.value.length
          "
          @click="controller.preview"
          >Review changes</Button
        >
        <Button
          v-if="atomic"
          type="submit"
          variant="outline"
          :disabled="
            controller.blocked.value || !!controller.conflicts.value.length
          "
          >Save</Button
        >
        <Button v-if="controller.dirty.value" @click="controller.reset"
          >Discard changes</Button
        >
      </slot>
    </template>
  </Form>
  <ConfirmDialog
    locale="en"
    :model-value="!!controller.confirmation.value"
    :title="controller.confirmation.value?.title ?? 'Confirm'"
    :description="controller.confirmation.value?.description ?? ''"
    @confirm="controller.confirm(true)"
    @cancel="controller.confirm(false)"
    @update:model-value="!$event && controller.confirm(false)"
  />
</template>

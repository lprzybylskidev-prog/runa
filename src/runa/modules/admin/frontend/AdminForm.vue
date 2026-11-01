<script setup lang="ts">
import { useAdminLocale } from './localization';
const { locale: adminLocale, t: tr } = useAdminLocale();
import { computed, nextTick, ref } from 'vue';
import {
  ValuePreview,
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
    :label="tr('Administrator panel editor')"
    :busy="controller.busy.value"
    :dirty="controller.dirty.value"
    :valid="!controller.blocked.value && !controller.conflicts.value.length"
    @submit="save"
  >
    <slot :controller="controller" />
    <template #feedback>
      <FormNotifications
        :locale="adminLocale"
        :attempt="attempt"
        :error="tr(controller.error.value?.message ?? '')"
        :success="tr(controller.notice.value)"
      />
      <Alert v-if="controller.blocked.value && atomic" tone="warning">{{
        tr('Required editor content is unavailable. Saving is blocked.')
      }}</Alert>
    </template>
    <template #actions>
      <slot name="review" :controller="controller">
        <div v-if="controller.review.value" class="space-y-2 text-sm">
          <p class="font-semibold">{{ tr('Reviewed differences') }}</p>
          <div
            v-for="participant in controller.review.value.participants"
            :key="participant.id"
          >
            <p>{{ participant.id }}</p>
            <ValuePreview
              :text="JSON.stringify(participant.review.changes)"
              format="json"
              :locale="adminLocale"
            />
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
          >{{ tr('Review changes') }}</Button
        >
        <Button
          v-if="atomic"
          type="submit"
          variant="outline"
          :disabled="
            controller.blocked.value || !!controller.conflicts.value.length
          "
          >{{ tr('Save') }}</Button
        >
        <Button v-if="controller.dirty.value" @click="controller.reset">{{
          tr('Discard changes')
        }}</Button>
      </slot>
    </template>
  </Form>
  <ConfirmDialog
    :locale="adminLocale"
    :model-value="!!controller.confirmation.value"
    :title="tr(controller.confirmation.value?.title ?? tr('Confirm'))"
    :description="tr(controller.confirmation.value?.description ?? '')"
    @confirm="controller.confirm(true)"
    @cancel="controller.confirm(false)"
    @update:model-value="!$event && controller.confirm(false)"
  />
</template>

<script setup lang="ts">
import AppDialog from './AppDialog.vue';
import AppButton from './AppButton.vue';
import type { ConfirmDialogProps, DialogCloseReason } from '../runa/ui/dialogs';
const props = withDefaults(defineProps<ConfirmDialogProps>(), {
  confirmLabel: '',
  cancelLabel: '',
  confirmIntent: 'commit',
  description: '',
  size: 'md',
  busy: false,
  loading: false,
  loadingLabel: '',
  loadingShape: 'form',
  error: '',
  closeOnEscape: true,
  closeOnOutside: true,
  closable: true,
  initialFocus: '',
  locale: 'pl',
});
const emit = defineEmits<{
  'update:modelValue': [value: boolean];
  confirm: [];
  cancel: [reason: DialogCloseReason];
}>();
</script>
<template>
  <AppDialog
    v-bind="$props"
    :initial-focus="initialFocus || '[data-dialog-cancel]'"
    @update:model-value="emit('update:modelValue', $event)"
    @close="emit('cancel', $event)"
  >
    <template v-if="$slots.loading" #loading><slot name="loading" /></template>
    <template #default="{ close }"><slot :close="close" /></template>
    <template #footer="{ close }"
      ><AppButton
        intent="cancel"
        data-dialog-cancel
        :disabled="busy || !closable"
        @click="close()"
        >{{ cancelLabel || (locale === 'pl' ? 'Anuluj' : 'Cancel') }}</AppButton
      ><AppButton
        :intent="confirmIntent"
        :busy="busy"
        :disabled="loading"
        variant="solid"
        @click="!props.busy && !props.loading && emit('confirm')"
        >{{
          confirmLabel || (locale === 'pl' ? 'Potwierdź' : 'Confirm')
        }}</AppButton
      ></template
    >
  </AppDialog>
</template>

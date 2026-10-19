<script setup lang="ts">
import AppDialog from './AppDialog.vue';
import AppButton from './AppButton.vue';
import type { ConfirmDialogProps, DialogCloseReason } from '../runa/ui/dialogs';
const props = withDefaults(defineProps<ConfirmDialogProps>(), {
  confirmLabel: '',
  cancelLabel: '',
  confirmTone: 'primary',
  description: '',
  size: 'md',
  busy: false,
  error: '',
  closeOnEscape: true,
  closeOnOutside: false,
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
    <template #default="{ close }"><slot :close="close" /></template>
    <template #footer="{ close }"
      ><AppButton
        data-dialog-cancel
        :disabled="busy || !closable"
        @click="close()"
        >{{ cancelLabel || (locale === 'pl' ? 'Anuluj' : 'Cancel') }}</AppButton
      ><AppButton
        :busy="busy"
        variant="solid"
        :tone="confirmTone"
        @click="!props.busy && emit('confirm')"
        >{{
          confirmLabel || (locale === 'pl' ? 'Potwierdź' : 'Confirm')
        }}</AppButton
      ></template
    >
  </AppDialog>
</template>

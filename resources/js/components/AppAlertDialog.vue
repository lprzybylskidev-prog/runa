<script setup lang="ts">
import AppDialog from './AppDialog.vue';
import AppButton from './AppButton.vue';
import type { DialogProps } from '../runa/ui/dialogs';
const props = withDefaults(
  defineProps<DialogProps & { acceptLabel?: string }>(),
  {
    description: '',
    size: 'md',
    busy: false,
    error: '',
    closeOnEscape: true,
    closeOnOutside: false,
    closable: true,
    initialFocus: '',
    locale: 'pl',
    acceptLabel: '',
  },
);
const emit = defineEmits<{
  'update:modelValue': [value: boolean];
  acknowledge: [];
}>();
function accept() {
  if (props.busy) return;
  emit('acknowledge');
  emit('update:modelValue', false);
}
</script>
<template>
  <AppDialog
    v-bind="$props"
    role="alertdialog"
    :initial-focus="initialFocus || '[data-dialog-accept]'"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <slot />
    <template #footer
      ><AppButton
        data-dialog-accept
        variant="solid"
        :busy="busy"
        @click="accept"
        >{{
          acceptLabel || (locale === 'pl' ? 'Rozumiem' : 'Understood')
        }}</AppButton
      ></template
    >
  </AppDialog>
</template>

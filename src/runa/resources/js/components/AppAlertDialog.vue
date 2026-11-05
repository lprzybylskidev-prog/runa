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
    loading: false,
    loadingLabel: '',
    loadingShape: 'form',
    error: '',
    closeOnEscape: true,
    closeOnOutside: true,
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
  if (props.busy || props.loading) return;
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
    <template v-if="$slots.loading" #loading><slot name="loading" /></template>
    <slot />
    <template #footer
      ><AppButton
        intent="neutral"
        data-dialog-accept
        variant="solid"
        :busy="busy"
        :disabled="loading"
        @click="accept"
        >{{
          acceptLabel || (locale === 'pl' ? 'Rozumiem' : 'Understood')
        }}</AppButton
      ></template
    >
  </AppDialog>
</template>

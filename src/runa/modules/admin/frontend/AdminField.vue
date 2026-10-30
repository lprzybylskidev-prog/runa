<script setup lang="ts">
import { computed } from 'vue';
import type {
  AdminFieldEmits,
  AdminFieldProps,
  AdminFieldValue,
} from './contracts';
import { resolveAdminComponent } from './components';
import { useAdminPresentation } from './presentation';
import AdminFieldControl from './AdminFieldControl.vue';
import AdminComponentBoundary from './AdminComponentBoundary.vue';
const props = defineProps<AdminFieldProps>();
const emit = defineEmits<AdminFieldEmits>();
const presentation = useAdminPresentation();
const resolved = computed(() =>
  resolveAdminComponent(props.field.component, props.components),
);
const replacement = computed(
  () =>
    resolved.value.component ?? presentation.value.field ?? AdminFieldControl,
);
const locked = computed(
  () => props.readonly || props.field.readonly || !resolved.value.available,
);
function update(value: AdminFieldValue) {
  if (!locked.value && !props.busy) emit('update:modelValue', value);
}
</script>
<template>
  <AdminComponentBoundary
    :identity="field.key + ':' + (field.component?.build_id ?? '')"
  >
    <component
      :is="replacement"
      :field="field"
      :model-value="modelValue"
      :errors="errors"
      :readonly="locked"
      :busy="busy"
      :lookup="lookup"
      @update:model-value="update"
    />
  </AdminComponentBoundary>
</template>

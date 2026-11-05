<script setup lang="ts">
import { computed, onScopeDispose, watch } from 'vue';
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
watch(
  () => resolved.value.available,
  (available) => emit('availability', available),
  { immediate: true },
);
onScopeDispose(() => emit('availability', true));
function update(value: AdminFieldValue) {
  if (!locked.value && !props.busy) emit('update:modelValue', value);
}
</script>
<template>
  <AdminComponentBoundary
    :identity="field.key + ':' + (field.component?.build_id ?? '')"
    :on-failure="
      (failed) => emit('availability', !failed && resolved.available)
    "
  >
    <component
      :is="replacement"
      :field="field"
      :id="id"
      :hide-label="hideLabel"
      :model-value="modelValue"
      :errors="errors"
      :readonly="locked"
      :busy="busy"
      :lookup="lookup"
      @update:model-value="update"
    />
  </AdminComponentBoundary>
</template>

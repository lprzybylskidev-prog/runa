<script setup lang="ts">
import { computed } from 'vue';
import type {
  ModuleSlotProps,
  ModuleSlotSlots,
} from '../runa/modules/presentation';
defineSlots<ModuleSlotSlots>();
const props = withDefaults(defineProps<ModuleSlotProps>(), {
  values: () => ({}),
});
const entries = computed(() => {
  const host = props.view?.slots[props.name];
  if (
    !host ||
    Object.keys(props.values).length !==
      Object.keys(host.contract.props).length ||
    Object.entries(host.contract.props).some(
      ([key, type]) =>
        typeof props.values[key] !== type ||
        (type === 'number' && !Number.isFinite(props.values[key])),
    )
  )
    return [];
  return host.contributions.filter(
    (entry) =>
      props.components[entry.component]?.manifestHash === entry.manifestHash,
  );
});
</script>
<template>
  <template v-for="entry in entries" :key="entry.id">
    <component :is="components[entry.component]!.component" v-bind="values" />
  </template>
  <slot v-if="!entries.length" />
</template>

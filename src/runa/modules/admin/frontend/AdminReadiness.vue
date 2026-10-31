<script setup lang="ts">
import {
  nextTick,
  onMounted,
  onScopeDispose,
  provide,
  ref,
  shallowReactive,
  watch,
} from 'vue';
import { useViewReadiness } from '@/runa/ui';
import { adminInitialReadinessKey } from './initialReadiness';
import AdminLoading from './AdminLoading.vue';
import { useAdminPresentation } from './presentation';
const emit = defineEmits<{ ready: [value: boolean] }>();
const dependencies = shallowReactive(new Set<() => boolean>());
const settled = ref(false);
const mounted = ref(false);
onMounted(() => {
  mounted.value = true;
});
const readiness = useViewReadiness({
  identity: 'admin-content',
  required: settled,
});
const presentation = useAdminPresentation();
provide(adminInitialReadinessKey, {
  ready: readiness.ready,
  register(dependency) {
    dependencies.add(dependency);
    return () => dependencies.delete(dependency);
  },
});
let generation = 0;
watch(
  () => mounted.value && [...dependencies].every((dependency) => dependency()),
  async (complete) => {
    const own = ++generation;
    // Let newly projected sections/editors mount and declare their dependencies.
    await nextTick();
    if (
      complete &&
      own === generation &&
      [...dependencies].every((dependency) => dependency())
    )
      settled.value = true;
  },
  { immediate: true, flush: 'post' },
);
watch(readiness.ready, (value) => emit('ready', value), { immediate: true });
onScopeDispose(() => {
  generation++;
});
</script>
<template>
  <component
    v-if="!readiness.ready.value"
    :is="presentation.loading ?? AdminLoading"
  />
  <div
    v-show="readiness.ready.value"
    :inert="!readiness.ready.value || undefined"
    :aria-hidden="!readiness.ready.value || undefined"
    data-admin-content
  >
    <slot />
  </div>
</template>

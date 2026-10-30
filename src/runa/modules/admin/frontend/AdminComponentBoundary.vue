<script setup lang="ts">
import { onErrorCaptured, onScopeDispose, ref, watch } from 'vue';
import AdminUnavailable from './AdminUnavailable.vue';
const props = defineProps<{
  identity: string;
  onFailure?: ((failed: boolean) => void) | undefined;
}>();
const failed = ref(false);
watch(
  () => props.identity,
  () => {
    failed.value = false;
    props.onFailure?.(false);
  },
);
onErrorCaptured(() => {
  failed.value = true;
  props.onFailure?.(true);
  return false;
});
onScopeDispose(() => props.onFailure?.(false));
</script>
<template><AdminUnavailable v-if="failed" /><slot v-else /></template>

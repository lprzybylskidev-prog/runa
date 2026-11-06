<script setup lang="ts">
import { onErrorCaptured, onScopeDispose, ref, watch } from 'vue';
import AdminUnavailable from './AdminUnavailable.vue';
import AdminLoading from './AdminLoading.vue';
import { useAdminInitialReadiness } from './initialReadiness';
const props = defineProps<{
  identity: string;
  onFailure?: ((failed: boolean) => void) | undefined;
}>();
const failed = ref(false);
const resolved = ref(false);
useAdminInitialReadiness(() => failed.value || resolved.value);
let timeout: ReturnType<typeof setTimeout> | undefined;
function clearTimeoutOwner() {
  clearTimeout(timeout);
  timeout = undefined;
}
function pending() {
  resolved.value = false;
  clearTimeoutOwner();
  timeout = setTimeout(() => {
    failed.value = true;
    props.onFailure?.(true);
  }, 15000);
}
function resolve() {
  clearTimeoutOwner();
  resolved.value = true;
}
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
onScopeDispose(() => {
  clearTimeoutOwner();
  props.onFailure?.(false);
});
</script>
<template>
  <AdminUnavailable v-if="failed" />
  <Suspense v-else :key="identity" @pending="pending" @resolve="resolve">
    <div class="contents"><slot /></div>
    <template #fallback><AdminLoading /></template>
  </Suspense>
</template>

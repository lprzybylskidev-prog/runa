<script setup lang="ts">
import { onErrorCaptured, ref, watch } from 'vue';
import AdminUnavailable from './AdminUnavailable.vue';
const props = defineProps<{ identity: string }>();
const failed = ref(false);
watch(
  () => props.identity,
  () => {
    failed.value = false;
  },
);
onErrorCaptured(() => {
  failed.value = true;
  return false;
});
</script>
<template><AdminUnavailable v-if="failed" /><slot v-else /></template>

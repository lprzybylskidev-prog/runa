<script setup lang="ts">
defineProps<{ label: string }>();
</script>
<template>
  <div role="group" :aria-label="label" class="button-group"><slot /></div>
</template>
<style scoped>
.button-group {
  display: inline-flex;
  max-width: 100%;
}
.button-group :deep(.app-button) {
  border-radius: 0;
  position: relative;
  border-color: var(--runa-control-border);
}
.button-group :deep(.app-button + .app-button) {
  margin-left: -1px;
}
.button-group :deep(.app-button:first-child) {
  border-radius: 4px 0 0 4px;
}
.button-group :deep(.app-button:last-child) {
  border-radius: 0 4px 4px 0;
}
.button-group :deep(.app-button:only-child) {
  border-radius: 4px;
}
.button-group :deep(.app-button:focus-visible) {
  position: relative;
  z-index: 1;
}
</style>

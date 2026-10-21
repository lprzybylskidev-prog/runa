<script setup lang="ts">
defineProps<{ open: boolean }>();
</script>
<template>
  <div
    class="runa-collapse"
    :class="{ 'is-open': open }"
    :inert="!open"
    :aria-hidden="!open || undefined"
  >
    <div class="runa-collapse-clip"><slot /></div>
  </div>
</template>
<style scoped>
.runa-collapse {
  display: grid;
  grid-template-rows: 0fr;
  opacity: 0;
  transition:
    grid-template-rows 160ms ease-out,
    opacity 160ms ease-out;
}
.runa-collapse.is-open {
  grid-template-rows: 1fr;
  opacity: 1;
}
.runa-collapse-clip {
  min-height: 0;
  overflow: hidden;
}
@media (prefers-reduced-motion: reduce) {
  .runa-collapse {
    transition: none;
  }
}
@media print {
  .runa-collapse {
    transition: none;
  }
}
</style>

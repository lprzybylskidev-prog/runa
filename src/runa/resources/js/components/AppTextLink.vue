<script setup lang="ts">
defineProps<{ href: string }>();
</script>
<template>
  <a :href="href" class="runa-text-link"><slot /></a>
</template>
<style scoped>
.runa-text-link {
  display: inline-flex;
  min-height: 44px;
  align-items: center;
  color: var(--runa-text-secondary);
  font-size: 12px;
  text-decoration: none;
  text-underline-offset: 4px;
  transition: color 160ms ease-out;
}
.runa-text-link:hover {
  color: var(--runa-primary-text);
  text-decoration: underline;
}
.runa-text-link:focus-visible {
  color: var(--runa-primary-text);
  outline: 2px solid var(--runa-focus-ring);
  outline-offset: 4px;
  border-radius: 2px;
}
@media (prefers-reduced-motion: reduce) {
  .runa-text-link {
    transition: none;
  }
}
</style>

<script setup lang="ts">
import version from '../../../VERSION?raw';
defineProps<{ versionLabel: string }>();
</script>

<template>
  <footer
    class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-6 text-xs text-muted sm:px-10"
  >
    <span>© {{ new Date().getFullYear() }} Łukasz Przybylski</span>
    <div class="flex flex-wrap items-center gap-5">
      <a
        class="footer-link"
        href="https://github.com/lprzybylskidev-prog/runa"
        target="_blank"
        rel="noopener noreferrer"
        >GitHub</a
      >
      <span>{{ versionLabel }} {{ version.trim() }}</span>
    </div>
  </footer>
</template>

<style scoped>
.footer-link {
  text-decoration: underline;
  text-underline-offset: 4px;
}
.footer-link:hover {
  color: var(--runa-primary-text);
}
</style>

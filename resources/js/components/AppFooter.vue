<script setup lang="ts">
import { safeBrandUrl, useIdentity } from '../runa/ui/identity';
import type { FooterOptions } from '../runa/ui/identity';
withDefaults(
  defineProps<{
    options?: FooterOptions | undefined;
    poweredByLabel?: string;
  }>(),
  { options: undefined, poweredByLabel: 'Powered by' },
);
const identity = useIdentity();
</script>

<template>
  <footer
    v-if="options?.visible !== false"
    class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-6 text-xs text-muted sm:px-10"
  >
    <span v-if="options?.copyright !== false">{{
      options?.copyrightText ??
      `© ${new Date().getFullYear()} Łukasz Przybylski`
    }}</span>
    <div class="flex min-w-0 flex-wrap items-center gap-x-5 gap-y-2">
      <a
        v-if="
          options?.repository !== false &&
          safeBrandUrl(
            options?.repositoryUrl ??
              'https://github.com/lprzybylskidev-prog/runa',
          )
        "
        class="footer-link"
        :href="
          options?.repositoryUrl ??
          'https://github.com/lprzybylskidev-prog/runa'
        "
        target="_blank"
        rel="noopener noreferrer"
        >{{ options?.repositoryLabel ?? 'GitHub' }}</a
      >
      <span class="break-words">
        <span v-if="options?.application !== false"
          >{{ identity.applicationName }} v{{
            identity.applicationVersion
          }}</span
        >
        <span
          v-if="options?.application !== false && options?.platform !== false"
          aria-hidden="true"
        >
          |
        </span>
        <span v-if="options?.platform !== false">
          {{ poweredByLabel }} Runa v{{ identity.platformVersion }}</span
        >
      </span>
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

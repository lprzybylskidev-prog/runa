<script setup lang="ts">
import { useLocale } from '../../../../../src/runa/resources/js/composables/useLocale';
import { useTheme } from '../../../../../src/runa/resources/js/composables/useTheme';
const { locale } = useLocale();
const { theme } = useTheme();
</script>
<template>
  <button
    data-language
    type="button"
    @click="locale = locale === 'pl' ? 'en' : 'pl'"
  >
    {{ locale }}
  </button>
  <button
    data-theme
    type="button"
    @click="theme = theme === 'dark' ? 'light' : 'dark'"
  >
    {{ theme }}
  </button>
</template>

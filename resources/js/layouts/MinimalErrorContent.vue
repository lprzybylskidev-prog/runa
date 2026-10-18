<script setup lang="ts">
import AppButton from '../components/AppButton.vue';
defineProps<{
  status: number;
  locale: 'pl' | 'en';
  theme: 'system' | 'light' | 'dark';
  code: string;
  title: string;
  description: string;
  home: string;
  retry: string;
}>();
</script>

<template>
  <p class="error-code mb-4 text-xs text-primary">
    HTTP {{ status }} / {{ code }}
  </p>
  <h1 class="text-2xl font-semibold leading-tight sm:text-3xl">{{ title }}</h1>
  <p class="mt-4 text-sm leading-7 text-muted">{{ description }}</p>
  <div class="mt-6 flex flex-wrap gap-3 text-sm">
    <form action="/" method="get">
      <AppButton type="submit">{{ home }}</AppButton>
    </form>
    <form v-if="[419, 429, 500, 503].includes(status)" method="get">
      <input type="hidden" name="lang" :value="locale" />
      <input type="hidden" name="theme" :value="theme" />
      <AppButton type="submit">{{ retry }}</AppButton>
    </form>
  </div>
  <p
    data-error-request-id="__RUNA_REQUEST_ID__"
    class="mt-8 break-words text-xs text-muted"
  >
    Request ID: __RUNA_REQUEST_ID__
  </p>
</template>

<style scoped>
[data-error-request-id=''] {
  display: none;
}
</style>

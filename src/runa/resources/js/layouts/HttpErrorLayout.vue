<script setup lang="ts">
import MinimalLayout from './MinimalLayout.vue';
import type { HttpErrorLayoutProps } from '../runa/ui/errors';
defineProps<HttpErrorLayoutProps>();
</script>
<template>
  <MinimalLayout :static-preferences="staticPreferences" :footer="footer"
    ><slot
  /></MinimalLayout>
</template>

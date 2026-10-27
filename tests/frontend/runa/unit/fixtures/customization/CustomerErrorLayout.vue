<script setup lang="ts">
import { HttpErrorLayout } from '@/runa/ui';
import type { HttpErrorLayoutProps } from '@/runa/ui';
defineProps<HttpErrorLayoutProps>();
</script>
<template>
  <div data-customer-error>
    <p>Customer support · {{ error.status }}</p>
    <HttpErrorLayout
      :static-preferences="staticPreferences"
      :footer="footer"
      :error="error"
      ><slot
    /></HttpErrorLayout>
  </div>
</template>

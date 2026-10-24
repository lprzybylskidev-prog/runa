<script setup lang="ts">
import { MinimalLayout } from '@/runa/ui';
</script>
<template>
  <MinimalLayout :illustration="false">
    <template #header><span data-customer-brand>Customer brand</span></template>
    <template #default="scope"><slot v-bind="scope" /></template>
  </MinimalLayout>
</template>

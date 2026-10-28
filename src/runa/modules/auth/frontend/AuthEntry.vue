<script setup lang="ts">
import AuthEntryFlow from './AuthEntryFlow.vue';
import { useAuthPresentation } from './presentation';
const presentation = useAuthPresentation();
import type { AuthEntryProps } from './contracts/presentation';
defineProps<AuthEntryProps>();
</script>
<template>
  <component
    :is="presentation.entry ?? AuthEntryFlow"
    :key="page"
    :page="page"
    :authenticated="authenticated"
  />
</template>

<script setup lang="ts">
import AuthEntryFlow from './AuthEntryFlow.vue';
import { useAuthPresentation } from './presentation';
const presentation = useAuthPresentation();
import type { EntrySession, EntryPage } from './entry';
defineProps<{
  page: EntryPage;
  authenticated?: ((session: EntrySession) => void) | undefined;
}>();
</script>
<template>
  <component
    :is="presentation.entry ?? AuthEntryFlow"
    :key="page"
    :page="page"
    :authenticated="authenticated"
  />
</template>

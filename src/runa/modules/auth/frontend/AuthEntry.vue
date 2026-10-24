<script setup lang="ts">
import AuthEntryFlow from './AuthEntryFlow.vue';
import type { EntrySession, EntryPage } from './entry';
defineProps<{
  page: EntryPage;
  authenticated?: ((session: EntrySession) => void) | undefined;
}>();
</script>
<template>
  <AuthEntryFlow :key="page" :page="page" :authenticated="authenticated" />
</template>

<script setup lang="ts">
import AuthEntryFlow from './AuthEntryFlow.vue';
import type { EntryPage } from './entry';
defineProps<{ page: EntryPage }>();
</script>
<template><AuthEntryFlow :key="page" :page="page" /></template>

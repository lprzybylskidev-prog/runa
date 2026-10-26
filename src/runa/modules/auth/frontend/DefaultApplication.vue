<script setup lang="ts">
import AuthEntry from './AuthEntry.vue';
import AuthApplication from './AuthApplication.vue';
import AuthWelcome from './AuthWelcome.vue';
import { entryPage } from './entry';
const path = window.location.pathname;
const page = entryPage(path);
</script>
<template>
  <AuthEntry v-if="page" :page="page" />
  <AuthApplication
    v-else-if="path === '/app' || path === '/account'"
    :account="path === '/account'"
  />
  <AuthWelcome v-else />
</template>

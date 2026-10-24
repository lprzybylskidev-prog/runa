<script setup lang="ts">
import {
  AuthApplication,
  AuthEntry,
  AuthWelcome,
  entryPage,
} from '@/runa/auth';
const path = window.location.pathname;
const page = entryPage(path);
const view = new window.URLSearchParams(window.location.search).get('view');
</script>
<template>
  <AuthEntry v-if="page" :page="page" />
  <AuthWelcome v-else-if="path === '/' || view === 'welcome'" />
  <AuthApplication
    v-else
    :account="path === '/account' || view === 'account'"
  />
</template>

<script setup lang="ts">
import {
  AuthApplication,
  AuthEntry,
  AuthWelcome,
  entryPage,
} from '@/runa/auth';
const path = window.location.pathname;
const authPage = entryPage(path);
</script>
<template>
  <AuthEntry v-if="authPage" :page="authPage" />
  <AuthApplication
    v-else-if="path === '/app' || path === '/account'"
    :account="path === '/account'"
  />
  <AuthWelcome v-else />
</template>

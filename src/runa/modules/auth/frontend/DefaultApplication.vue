<script setup lang="ts">
import AuthEntry from './AuthEntry.vue';
import AuthApplication from './AuthApplication.vue';
import AuthWelcome from './AuthWelcome.vue';
import { entryPage } from './entry';
import { AdminApplication } from '@/runa/admin';
import type { AdminComponentRegistry } from '@/runa/admin';
defineProps<{ adminComponents?: AdminComponentRegistry | undefined }>();
const path = window.location.pathname;
const page = entryPage(path);
</script>
<template>
  <AuthEntry v-if="page" :page="page" />
  <AdminApplication
    v-else-if="path === '/admin' || path.startsWith('/admin/')"
    :components="adminComponents"
  />
  <AuthApplication
    v-else-if="path === '/app' || path === '/account'"
    :account="path === '/account'"
  />
  <AuthWelcome v-else />
</template>

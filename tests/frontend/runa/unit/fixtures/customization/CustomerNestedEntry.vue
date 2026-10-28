<script setup lang="ts">
import { AuthEntry, AuthPresentationProvider } from '@/runa/auth';
import type { AuthEntryProps, AuthPresentation } from '@/runa/auth';
defineProps<
  AuthEntryProps & { parent: AuthPresentation; components: AuthPresentation }
>();
</script>
<template>
  <AuthPresentationProvider :components="parent">
    <AuthPresentationProvider :components="components">
      <AuthEntry :page="page" :authenticated="authenticated" />
    </AuthPresentationProvider>
  </AuthPresentationProvider>
</template>

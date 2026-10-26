<script setup lang="ts">
import {
  AuthAccount,
  AuthApplication,
  AuthEntry,
  AuthPresentationProvider,
} from '@/runa/auth';
import type { AuthPresentation, EntryPage, EntrySession } from '@/runa/auth';
defineProps<{
  components: AuthPresentation;
  mode: 'entry' | 'account' | 'application';
  page?: EntryPage;
  accountId?: string;
  authenticated?: ((session: EntrySession) => void) | undefined;
}>();
</script>
<template>
  <AuthPresentationProvider :components="components">
    <AuthEntry
      v-if="mode === 'entry'"
      :page="page ?? 'login'"
      :authenticated="authenticated"
    />
    <AuthAccount
      v-else-if="mode === 'account' && accountId"
      :account-id="accountId"
    />
    <AuthApplication v-else v-slot="{ session }"
      ><h1 data-customer-page>{{ session.public_id }}</h1></AuthApplication
    >
  </AuthPresentationProvider>
</template>

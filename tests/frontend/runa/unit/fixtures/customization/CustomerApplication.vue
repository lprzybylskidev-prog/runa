<script setup lang="ts">
import { AuthEntry, useApplicationSession } from '@/runa/auth';
import type { ApplicationSessionController } from '@/runa/auth';
import { request } from '@/runa/http';
const checkAccess = () => request('/runa/auth/session').catch(() => undefined);
const {
  session,
  paused,
  authenticated,
  warning,
  logout,
}: ApplicationSessionController = useApplicationSession();
</script>
<template>
  <AuthEntry v-if="paused" page="login" :authenticated="authenticated" />
  <main v-else-if="session" :key="session.public_id" data-customer-application>
    <h1>{{ session.display_name }}</h1>
    <p v-if="warning" role="status">Session expires soon</p>
    <button type="button" @click="checkAccess">Check access</button>
    <button type="button" @click="logout">Sign out</button>
  </main>
</template>

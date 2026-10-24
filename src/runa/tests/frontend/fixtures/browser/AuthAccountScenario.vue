<script setup lang="ts">
import {
  AuthApplication,
  AuthEntry,
  AuthWelcome,
  entryPage,
} from '@/runa/auth';
import { AuthPresentationProvider } from '@/runa/auth';
import { Button } from '@/runa/ui';
import { request } from '@/runa/http';
import CustomerMinimalLayout from '../customization/CustomerMinimalLayout.vue';
import CustomerEntry from '../customization/CustomerEntry.vue';
import CustomerApplication from '../customization/CustomerApplication.vue';
const mode = new window.URLSearchParams(window.location.search).get(
  'presentation',
);
const components = mode
  ? {
      minimalLayout: CustomerMinimalLayout,
      ...(mode === 'replacement' ? { entry: CustomerEntry } : {}),
    }
  : {};
const checkAccess = () => request('/runa/auth/session').catch(() => undefined);
const path = window.location.pathname;
const page = entryPage(path);
const view = new window.URLSearchParams(window.location.search).get('view');
</script>
<template>
  <AuthPresentationProvider :components="components">
    <AuthEntry v-if="page" :page="page" />
    <AuthWelcome v-else-if="path === '/' || view === 'welcome'" />
    <CustomerApplication v-else-if="mode === 'replacement'" />
    <AuthApplication v-else-if="mode === 'composed'" v-slot="{ session }">
      <section data-customer-content>
        <h1>{{ session.display_name }}</h1>
        <Button @click="checkAccess">Check access</Button>
      </section>
    </AuthApplication>
    <AuthApplication
      v-else
      :account="path === '/account' || view === 'account'"
    />
  </AuthPresentationProvider>
</template>

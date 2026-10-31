<script setup lang="ts">
import { computed, defineComponent, h, provide } from 'vue';
import type { VNode } from 'vue';
import {
  ApplicationSessionShell,
  AuthPresentationProvider,
  useAuthPresentation,
} from '@/runa/auth';
import { Alert, Button, MinimalLayout } from '@/runa/ui';
import type { NavigationEntry } from '@/runa/ui';
import type { AdminApplicationProps, AdminController } from './contracts';
import { provideLocale } from '../../../resources/js/composables/useLocale';
import { useAdmin } from './useAdmin';
import { useAdminPresentation } from './presentation';
import AdminLayout from './AdminLayout.vue';
import AdminLoading from './AdminLoading.vue';
import AdminEntryProof from './AdminEntryProof.vue';
import AdminPageContent from './AdminPageContent.vue';
import AdminDashboard from './AdminDashboard.vue';
import { adminCollectionsKey } from './collections';
const props = defineProps<AdminApplicationProps>();
provide(
  adminCollectionsKey,
  computed(() => props.collections ?? {}),
);
defineSlots<{
  default?(scope: { controller: AdminController }): VNode[];
  loading?(): VNode[];
}>();
provideLocale(computed(() => 'en'));
const controller = useAdmin({
  navigate: props.navigate,
  session: props.session,
});
const { state, bootstrap, location } = controller;
const presentation = useAdminPresentation();
const authPresentation = useAuthPresentation();
const layout = defineComponent({
  inheritAttrs: false,
  setup: (_, context) => () =>
    h(
      presentation.value.layout ?? AdminLayout,
      { ...context.attrs, controller },
      context.slots,
    ),
});
const navigation = computed<NavigationEntry[]>(() => [
  {
    key: 'dashboard',
    routeKey: 'admin.dashboard',
    moduleKey: 'runa.admin',
    label: 'Administrator panel',
    href: '/admin',
    icon: 'adjustments-horizontal',
    availability: 'available',
  },
  ...(bootstrap.value?.owners.map((owner) => ({
    key: owner.id,
    routeKey: owner.id + '.' + owner.default_page,
    moduleKey: owner.id,
    label: owner.title,
    href: '/admin/' + owner.id + '/' + owner.default_page,
    availability: 'available' as const,
  })) ?? []),
]);
const topNavigation = computed<NavigationEntry[]>(
  () =>
    bootstrap.value?.owners
      .find((owner) => owner.id === location.value?.owner)
      ?.pages.map((page) => ({
        key: page.key,
        routeKey: location.value!.owner + '.' + page.key,
        moduleKey: location.value!.owner,
        label: page.title,
        href: page.href,
        availability: 'available',
      })) ?? [],
);
const current = computed(() => ({
  moduleKey: location.value?.owner ?? 'runa.admin',
  routeKey: location.value
    ? location.value.owner + '.' + location.value.page
    : 'admin.dashboard',
  href: location.value
    ? '/admin/' + location.value.owner + '/' + location.value.page
    : '/admin',
}));
// A malformed Admin URL is never silently interpreted as the dashboard.
const validPath = computed(
  () => window.location.pathname === '/admin' || !!location.value,
);
</script>
<template>
  <AuthPresentationProvider :components="{ applicationLayout: layout }">
    <ApplicationSessionShell
      :session="controller.session"
      :navigation="navigation"
      :top-navigation="topNavigation"
      :current="current"
      locale="en"
    >
      <template v-if="state !== 'ready'" #admission>
        <AdminEntryProof
          v-if="state === 'proof_required'"
          :controller="controller"
        />
        <component v-else :is="authPresentation.minimalLayout ?? MinimalLayout">
          <Alert
            v-if="
              !controller.session.loading.value &&
              !controller.session.session.value
            "
            tone="danger"
          >
            Session could not be loaded.
            <Button @click="controller.session.refresh">Retry</Button>
          </Alert>
          <slot v-else-if="state === 'loading'" name="loading"
            ><component :is="presentation.loading ?? AdminLoading"
          /></slot>
          <Alert v-else tone="warning">
            {{
              controller.error.value?.message ??
              'The administrator panel is unavailable.'
            }}
            <Button @click="controller.refresh">Retry</Button>
          </Alert>
        </component>
      </template>
      <Alert v-if="!validPath" tone="warning"
        >The administrator panel is unavailable.</Alert
      >
      <slot v-else-if="bootstrap" :controller="controller">
        <AdminPageContent
          v-if="location"
          :key="location.owner + ':' + location.page"
          :admin="controller"
          :location="location"
          :components="components"
        />
        <AdminDashboard
          v-else
          :bootstrap="bootstrap"
          :admin="controller"
          :components="components"
        />
      </slot>
    </ApplicationSessionShell>
  </AuthPresentationProvider>
</template>

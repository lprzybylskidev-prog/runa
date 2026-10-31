<script setup lang="ts">
import { computed } from 'vue';
import ApplicationSessionShell from './ApplicationSessionShell.vue';
import AuthAccount from './AuthAccount.vue';
import AuthAccountLoading from './AuthAccountLoading.vue';
import { Skeleton } from '@/runa/ui';
import { useAuthPresentation } from './presentation';
import { useApplicationSession } from './useApplicationSession';
import type {
  AuthApplicationProps,
  AuthApplicationSlots,
} from './contracts/presentation';
const props = withDefaults(defineProps<AuthApplicationProps>(), {
  account: false,
});
defineSlots<AuthApplicationSlots>();
const presentation = useAuthPresentation();
const controller = props.session ?? useApplicationSession();
const current = computed(
  () =>
    props.current ?? {
      routeKey: props.account ? 'account' : 'application',
      moduleKey: props.account ? 'runa.auth' : 'application',
      href: props.account ? '/account' : '/app',
    },
);
</script>
<template>
  <ApplicationSessionShell
    :session="controller"
    :current="current"
    :navigation="navigation"
    :top-navigation="topNavigation"
    :locale="locale"
    :account-menu="accountMenu"
  >
    <template v-if="$slots.admission" #admission
      ><slot name="admission"
    /></template>
    <template v-if="$slots['account-menu']" #account-menu="scope"
      ><slot name="account-menu" v-bind="scope"
    /></template>
    <template #loading
      ><slot name="loading" :account="account"
        ><component
          :is="presentation.accountLoading ?? AuthAccountLoading"
          v-if="account" /><Skeleton v-else :lines="3" /></slot
    ></template>
    <template #default="{ session: identity }">
      <AuthAccount
        v-if="account"
        :key="identity.public_id"
        :account-id="identity.public_id"
      />
      <slot v-else :session="identity" />
    </template>
  </ApplicationSessionShell>
</template>

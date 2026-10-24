<script setup lang="ts">
import AuthAccountDefault from './AuthAccountDefault.vue';
import { useAuthPresentation } from './presentation';
defineProps<{ accountId: string }>();
const presentation = useAuthPresentation();
</script>
<template>
  <component
    :is="presentation.account ?? AuthAccountDefault"
    :key="accountId"
    :account-id="accountId"
  />
</template>

<script setup lang="ts">
import { useAuthAccount } from '@/runa/auth';
const props = defineProps<{ accountId: string }>();
const { profile, busy, failure, tabs, selectTab, tab } = useAuthAccount(
  props.accountId,
);
</script>
<template>
  <section data-customer-account :aria-busy="busy">
    <h1>{{ profile?.display_name }}</h1>
    <p role="status">{{ failure }}</p>
    <button
      v-for="item in tabs"
      :key="item"
      type="button"
      @click="selectTab(item)"
    >
      {{ item }}
    </button>
    <p>{{ tab }}</p>
  </section>
</template>

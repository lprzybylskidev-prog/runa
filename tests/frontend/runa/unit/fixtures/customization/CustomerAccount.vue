<script setup lang="ts">
import { useAuthAccount } from '@/runa/auth';
import { TextInput } from '@/runa/ui';
import type { AuthAccountProps, AuthAccountController } from '@/runa/auth';
const props = defineProps<AuthAccountProps>();
const {
  profile,
  busy,
  failure,
  tabs,
  selectTab,
  tab,
  fields,
  ready,
}: AuthAccountController = useAuthAccount(props.accountId);
</script>
<template>
  <section data-customer-account :data-account="accountId" :aria-busy="busy">
    <TextInput
      v-if="ready"
      :model-value="fields.name ?? ''"
      name="customer-name"
      label="Draft name"
      @update:model-value="fields.name = $event ?? ''"
    />
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

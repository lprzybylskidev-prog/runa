<script setup lang="ts">
import { MinimalLayout } from '@/runa/ui';
import { AuthEntry, entryPage } from '@/runa/auth';
const authPage = entryPage(window.location.pathname);
</script>

<template>
  <AuthEntry v-if="authPage" :page="authPage" />
  <MinimalLayout v-else>
    <template #default="{ t }">
      <h1
        class="max-w-xl text-4xl font-semibold leading-tight tracking-tight sm:text-5xl xl:text-6xl"
      >
        {{ t('title') }}
      </h1>
      <p
        class="mt-6 max-w-lg text-sm leading-7 text-muted sm:text-base sm:leading-8"
      >
        {{ t('description') }}
      </p>
    </template>
  </MinimalLayout>
</template>

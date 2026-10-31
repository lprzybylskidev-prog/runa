<script setup lang="ts">
import { useAdminLocale } from './localization';
const { t: tr } = useAdminLocale();
import { Skeleton } from '@/runa/ui';
import type { VNode } from 'vue';
defineSlots<{ default?(): VNode[] }>();
</script>
<template>
  <div aria-busy="true" class="space-y-4">
    <slot
      ><p class="sr-only" role="status">
        {{ tr('Loading the administrator panel') }}
      </p>
      <Skeleton :lines="3"
    /></slot>
  </div>
</template>

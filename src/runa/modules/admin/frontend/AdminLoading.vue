<script setup lang="ts">
import { useAdminLocale } from './localization';
const { t: tr } = useAdminLocale();
import { ContentLoading } from '@/runa/ui';
import type { VNode } from 'vue';
defineSlots<{ default?(): VNode[] }>();
</script>
<template>
  <ContentLoading :label="tr('Loading the administrator panel')"
    ><template v-if="$slots.default" #placeholder><slot /></template
  ></ContentLoading>
</template>

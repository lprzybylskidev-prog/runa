<script setup lang="ts">
import { useAdminLocale } from './localization';
const { t: tr } = useAdminLocale();
import { Alert } from '@/runa/ui';
defineOptions({ inheritAttrs: false });
</script>
<template>
  <Alert tone="warning">{{
    tr('This administration component is unavailable.')
  }}</Alert>
</template>

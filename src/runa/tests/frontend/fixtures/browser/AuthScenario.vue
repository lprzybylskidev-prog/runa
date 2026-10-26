<script setup lang="ts">
import { AuthEntry, entryPage } from '@/runa/auth';
const page =
  entryPage(
    '/auth/' + new window.URLSearchParams(window.location.search).get('flow'),
  ) ?? 'login';
</script>
<template><AuthEntry :page="page" /></template>

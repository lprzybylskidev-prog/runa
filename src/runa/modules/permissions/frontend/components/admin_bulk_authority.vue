<script setup lang="ts">
import { AuthAdminBulkAction } from '@/runa/auth';
import type { AdminSectionPresentationProps } from '@/runa/admin';
defineProps<AdminSectionPresentationProps>();
</script>
<template>
  <AuthAdminBulkAction
    :page="page"
    :section="section"
    :controller="controller"
  />
</template>

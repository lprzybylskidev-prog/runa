<script setup lang="ts">
import { useAdminLocale } from './localization';
const { t: tr } = useAdminLocale();
import { AuthOperationProofPage } from '@/runa/auth';
import type { AdminController } from './contracts';
defineProps<{ controller: AdminController }>();
</script>
<template>
  <AuthOperationProofPage
    :controller="controller.entryProof"
    :title="tr('Administrator panel')"
    :description="tr('Confirm your password to enter the administrator panel.')"
    home-href="/app"
    @confirmed="controller.refresh"
    @cancel="controller.cancelEntry"
  />
</template>

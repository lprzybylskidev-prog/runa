<script setup lang="ts">
import { AuthOperationProofPage } from '@/runa/auth';
import type { AdminController } from './contracts';
defineProps<{ controller: AdminController }>();
</script>
<template>
  <AuthOperationProofPage
    :controller="controller.entryProof"
    locale="en"
    title="Administrator panel"
    description="Confirm your password to enter the administrator panel."
    home-href="/app"
    @confirmed="controller.refresh"
    @cancel="controller.cancelEntry"
  />
</template>

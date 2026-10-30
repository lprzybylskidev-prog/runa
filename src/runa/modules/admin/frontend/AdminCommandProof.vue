<script setup lang="ts">
import { AuthOperationProof } from '@/runa/auth';
import type { AdminCommandData, AdminFormController } from './contracts';
const props = defineProps<{
  command: AdminCommandData;
  page: AdminFormController;
  section: string;
}>();
const proof = props.page.proof(props.section, props.command.key);
</script>
<template><AuthOperationProof :controller="proof" /></template>

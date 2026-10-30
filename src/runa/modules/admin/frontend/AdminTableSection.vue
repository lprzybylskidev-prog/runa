<script setup lang="ts">
import type { AdminPageController } from './contracts';
import { useAdminTable } from './useAdminTable';
import AdminResourceTable from './AdminResourceTable.vue';
const props = defineProps<{ page: AdminPageController; section: string }>();
// The owning dispatcher keys this scope by section identity and view.
const table = useAdminTable(props.page, props.section);
</script>
<template><AdminResourceTable :controller="table" /></template>

<script setup lang="ts">
import { AdminSection } from '@/runa/admin';
import type { AdminSectionPresentationProps } from '@/runa/admin';
defineProps<AdminSectionPresentationProps>();
</script>
<template>
  <section data-customer-section class="space-y-3">
    <p class="text-sm text-muted">Application administration</p>
    <AdminSection :controller="controller" :section="section" />
  </section>
</template>

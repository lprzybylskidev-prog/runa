<script setup lang="ts">
import { computed, provide } from 'vue';
import { authPresentationKey, useAuthPresentation } from './presentation';
import type { AuthPresentation } from './presentation';
const props = defineProps<{ components: AuthPresentation }>();
const parent = useAuthPresentation();
provide(
  authPresentationKey,
  computed(() => ({ ...parent.value, ...props.components })),
);
</script>
<template><slot /></template>

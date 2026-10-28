<script setup lang="ts">
import { computed, provide, toRaw } from 'vue';
import { authPresentationKey, useAuthPresentation } from './presentation';
import type { AuthPresentation } from './presentation';
const props = defineProps<{ components: AuthPresentation }>();
const parent = useAuthPresentation();
provide(
  authPresentationKey,
  computed<AuthPresentation>(() =>
    Object.fromEntries(
      Object.entries({ ...parent.value, ...props.components }).map(
        ([name, component]) => [name, component ? toRaw(component) : component],
      ),
    ),
  ),
);
</script>
<template><slot /></template>

<script setup lang="ts">
import AuthWelcomeDefault from './AuthWelcomeDefault.vue';
import { useAuthPresentation } from './presentation';
const presentation = useAuthPresentation();
</script>
<template>
  <component :is="presentation.welcome ?? AuthWelcomeDefault" />
</template>

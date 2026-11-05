<script setup lang="ts">
import type { ButtonVariant, ButtonTone } from '../runa/ui/controls';
withDefaults(
  defineProps<{ href: string; variant?: ButtonVariant; tone?: ButtonTone }>(),
  { variant: 'outline', tone: 'neutral' },
);
</script>
<template>
  <a
    :href="href"
    class="app-button"
    :data-variant="variant"
    :data-tone="tone"
    data-size="md"
    ><slot
  /></a>
</template>

<style scoped src="../../css/button.css"></style>

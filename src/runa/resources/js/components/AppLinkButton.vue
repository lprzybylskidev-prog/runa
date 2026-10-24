<script setup lang="ts">
import type { ButtonVariant, ControlTone } from '../runa/ui/controls';
withDefaults(
  defineProps<{ href: string; variant?: ButtonVariant; tone?: ControlTone }>(),
  { variant: 'outline', tone: 'primary' },
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

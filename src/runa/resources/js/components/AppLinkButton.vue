<script setup lang="ts">
import AppIcon from './AppIcon.vue';
import type { IconName } from '../runa/ui/icons';
import type { ButtonVariant, ButtonTone } from '../runa/ui/controls';
withDefaults(
  defineProps<{
    href: string;
    icon?: IconName | undefined;
    variant?: ButtonVariant;
    tone?: ButtonTone;
  }>(),
  { variant: 'outline', tone: 'neutral', icon: undefined },
);
</script>
<template>
  <a
    :href="href"
    class="app-button"
    :data-variant="variant"
    :data-tone="tone"
    data-size="md"
    ><AppIcon v-if="icon" :name="icon" :size="20" class="button-icon" /><slot
  /></a>
</template>

<style scoped src="../../css/button.css"></style>

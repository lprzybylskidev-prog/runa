<script setup lang="ts">
import AppIcon from './AppIcon.vue';
import type { IconName } from '../runa/ui/icons';
defineProps<{ title: string; description?: string; icon?: IconName }>();
</script>
<template>
  <div
    class="flex flex-col items-center gap-3 rounded border border-dashed border-line px-5 py-8 text-center"
  >
    <slot name="icon"
      ><AppIcon v-if="icon" :name="icon" :size="28" class="text-muted"
    /></slot>
    <h3 class="text-sm font-semibold">{{ title }}</h3>
    <p
      v-if="description"
      class="max-w-prose text-xs leading-relaxed text-muted"
    >
      {{ description }}
    </p>
    <div v-if="$slots.actions" class="mt-2 flex flex-wrap justify-center gap-2">
      <slot name="actions" />
    </div>
  </div>
</template>

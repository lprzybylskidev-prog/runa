<script setup lang="ts">
import type { BreadcrumbItem } from '../runa/ui/containers';
import { safeBrandUrl } from '../runa/ui/identity';
defineProps<{ items: readonly BreadcrumbItem[]; label: string }>();
const emit = defineEmits<{
  navigate: [href: string, event: globalThis.MouseEvent];
}>();
</script>
<template>
  <nav :aria-label="label">
    <ol class="flex flex-wrap items-center gap-2 text-xs text-muted">
      <li
        v-for="(item, index) in items"
        :key="index"
        class="inline-flex items-center gap-2"
      >
        <span v-if="index" aria-hidden="true">/</span
        ><a
          v-if="
            index < items.length - 1 && item.href && safeBrandUrl(item.href)
          "
          :href="item.href"
          class="underline underline-offset-4 hover:text-primary focus-visible:outline-2 focus-visible:outline-[var(--runa-focus-ring)]"
          @click="emit('navigate', item.href!, $event)"
          >{{ item.label }}</a
        ><span
          v-else
          :aria-current="index === items.length - 1 ? 'page' : undefined"
          >{{ item.label }}</span
        >
      </li>
    </ol>
  </nav>
</template>

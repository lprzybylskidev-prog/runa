<script setup lang="ts">
import { computed, shallowRef, watch } from 'vue';
import type { NavigationEntry, RouteContext } from '../runa/ui/navigation';
import { registerPageNavigation } from '../composables/usePageNavigationHost';
import AppButton from './AppButton.vue';
const props = withDefaults(
  defineProps<{
    entries: readonly NavigationEntry[];
    current?: RouteContext | undefined;
    label?: string;
    disabled?: boolean;
  }>(),
  { current: undefined, label: 'Page navigation', disabled: false },
);
const emit = defineEmits<{
  navigate: [href: string, event: globalThis.MouseEvent];
}>();
// The layout renders this publisher in its slot. Publish stable value snapshots:
// subscribing the parent directly to slot-created prop objects causes render loops.
const publishedEntries = shallowRef<readonly NavigationEntry[]>([]);
const publishedCurrent = shallowRef<RouteContext>();
watch(
  () => JSON.stringify([props.entries, props.current, props.disabled]),
  () => {
    publishedEntries.value = props.disabled
      ? props.entries.map((entry) => ({
          ...entry,
          availability: 'disabled' as const,
        }))
      : props.entries;
    publishedCurrent.value = props.current;
  },
  { immediate: true },
);
function leaves(items: readonly NavigationEntry[]): readonly NavigationEntry[] {
  return items.flatMap((item) =>
    item.availability === 'hidden'
      ? []
      : item.children?.length
        ? leaves(item.children).map((child) => ({
            ...child,
            availability:
              item.availability === 'disabled'
                ? ('disabled' as const)
                : child.availability,
          }))
        : [item],
  );
}
const fallbackEntries = computed(() => leaves(publishedEntries.value));
const hosted = registerPageNavigation({
  entries: () => publishedEntries.value,
  current: () => publishedCurrent.value,
  navigate: (href, event) => emit('navigate', href, event),
});
</script>
<template>
  <nav v-if="!hosted" class="flex flex-wrap gap-2" :aria-label="label">
    <AppButton
      v-for="entry in fallbackEntries"
      :key="entry.key"
      :variant="
        entry.routeKey === publishedCurrent?.routeKey ? 'solid' : 'outline'
      "
      :aria-current="
        entry.routeKey === publishedCurrent?.routeKey ? 'page' : undefined
      "
      :disabled="entry.availability !== 'available'"
      @click="emit('navigate', entry.href, $event)"
      >{{ entry.label }}</AppButton
    >
  </nav>
</template>

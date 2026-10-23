<script setup lang="ts">
import { computed } from 'vue';
import { createPageResolver } from '../lib/pageResolver';
import {
  modulePages,
  type ModuleFrontendView,
  type ModuleComponents,
} from '../lib/moduleFrontend';
const props = withDefaults(
  defineProps<{
    view: ModuleFrontendView | null;
    components: ModuleComponents;
    href: string;
    locale?: 'pl' | 'en';
    unavailableText?: string;
  }>(),
  { locale: 'pl', unavailableText: 'Moduł jest niedostępny.' },
);
const page = computed(() => {
  if (!props.view) return undefined;
  const match = createPageResolver(
    modulePages(props.view, props.locale),
  ).resolve(props.href);
  return props.view.pages.find((page) => page.routeKey === match?.routeKey);
});
const component = computed(() => {
  const entry = page.value;
  const built = entry ? props.components[entry.component] : undefined;
  return entry?.availability === 'available' &&
    built?.manifestHash === entry.manifestHash
    ? built.component
    : undefined;
});
</script>
<template>
  <component :is="component" v-if="component" :key="page!.routeKey" />
  <slot v-else name="unavailable" :reason="page?.reason ?? 'page_unavailable'"
    ><p role="status">{{ unavailableText }}</p></slot
  >
</template>

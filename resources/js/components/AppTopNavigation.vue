<script setup lang="ts">
import {
  computed,
  nextTick,
  onMounted,
  onScopeDispose,
  ref,
  useId,
  watch,
} from 'vue';
import AppNavigation from './AppNavigation.vue';
import type { NavigationEntry, RouteContext } from '../runa/ui/navigation';
const props = defineProps<{
  entries: readonly NavigationEntry[];
  current?: RouteContext | undefined;
}>();
const emit = defineEmits<{
  navigate: [href: string, event: globalThis.MouseEvent];
}>();
const id = useId();
const openKey = ref<string>();
const panel = ref<globalThis.HTMLElement>();
let trigger: globalThis.HTMLElement | undefined;
const position = ref({ left: '8px', top: '8px', maxHeight: '300px' });
const expanded = ref<string[]>([]);
const visible = computed(() =>
  props.entries.filter((entry) => entry.availability !== 'hidden'),
);
watch(
  () => props.current?.routeKey,
  () => close(),
);
const opened = computed(() =>
  visible.value.find((entry) => entry.key === openKey.value),
);
function navigable(entry: NavigationEntry): boolean {
  return (
    entry.availability === 'available' &&
    entry.href.startsWith('/') &&
    !entry.href.startsWith('//') &&
    !/[\\\s]/u.test(entry.href)
  );
}
function children(entry: NavigationEntry): boolean {
  return !!entry.children?.some((child) => child.availability !== 'hidden');
}
function active(entry: NavigationEntry): boolean {
  return (
    entry.routeKey === props.current?.routeKey || !!entry.children?.some(active)
  );
}
function close(restore = false) {
  openKey.value = undefined;
  expanded.value = [];
  if (restore) trigger?.focus({ preventScroll: true });
}
function focusable(): globalThis.HTMLElement[] {
  return Array.from(
    panel.value?.querySelectorAll<globalThis.HTMLElement>(
      'a[href], button:not([disabled])',
    ) ?? [],
  ).filter((element) => element.getClientRects().length > 0);
}
function place() {
  const bounds = trigger?.getBoundingClientRect();
  const navBounds = trigger?.closest('nav')?.getBoundingClientRect();
  if (!bounds) return;
  const width = Math.min(248, window.innerWidth - 16);
  const top = (navBounds?.bottom ?? bounds.bottom) + 8;
  position.value = {
    left: `${Math.max(8, Math.min(bounds.left, window.innerWidth - width - 8))}px`,
    top: `${Math.max(8, top)}px`,
    maxHeight: `${Math.max(80, window.innerHeight - top - 8)}px`,
  };
}
async function toggle(entry: NavigationEntry, event: globalThis.MouseEvent) {
  if (openKey.value === entry.key) {
    close(true);
    return;
  }
  trigger =
    event.currentTarget instanceof window.HTMLElement
      ? event.currentTarget
      : undefined;
  openKey.value = entry.key;
  expanded.value = [];
  place();
  await nextTick();
  focusable()[0]?.focus({ preventScroll: true });
}
function navigate(href: string, event: globalThis.MouseEvent) {
  emit('navigate', href, event);
  if (event.defaultPrevented) close();
}
function pointerdown(event: globalThis.PointerEvent) {
  if (
    event.target instanceof window.Node &&
    !panel.value?.contains(event.target) &&
    !trigger?.contains(event.target)
  )
    close();
}
function keydown(event: globalThis.KeyboardEvent) {
  if (!openKey.value) return;
  if (event.key === 'Escape') {
    event.preventDefault();
    close(true);
  } else if (event.key === 'Tab') {
    const items = focusable();
    if (event.shiftKey && document.activeElement === items[0]) {
      event.preventDefault();
      close(true);
    } else if (!event.shiftKey && document.activeElement === items.at(-1)) {
      event.preventDefault();
      const all = Array.from(
        document.querySelectorAll<globalThis.HTMLElement>(
          'a[href], button:not([disabled]), select, input, [tabindex="0"]',
        ),
      ).filter(
        (element) =>
          element.getClientRects().length > 0 &&
          !element.closest('[inert]') &&
          !panel.value?.contains(element),
      );
      const next = all[all.indexOf(trigger!) + 1];
      close();
      (next ?? trigger)?.focus();
    }
  }
}
function scroll(event: Event) {
  if (
    !openKey.value ||
    (event.target instanceof window.Node && panel.value?.contains(event.target))
  )
    return;
  if (event.target === trigger?.closest('.shell-top-nav')) {
    place();
    return;
  }
  close(panel.value?.contains(document.activeElement) ?? false);
}
function resize() {
  close(panel.value?.contains(document.activeElement) ?? false);
}
onMounted(() => {
  document.addEventListener('pointerdown', pointerdown);
  document.addEventListener('keydown', keydown);
  document.addEventListener('scroll', scroll, true);
  window.addEventListener('resize', resize);
});
onScopeDispose(() => {
  document.removeEventListener('pointerdown', pointerdown);
  document.removeEventListener('keydown', keydown);
  document.removeEventListener('scroll', scroll, true);
  window.removeEventListener('resize', resize);
});
</script>
<template>
  <ul class="shell-nav-list shell-nav-horizontal">
    <li v-for="entry in visible" :key="entry.key">
      <button
        v-if="navigable(entry) && children(entry)"
        type="button"
        :class="[
          'shell-nav-link shell-top-trigger',
          { 'is-active': active(entry) },
        ]"
        :aria-expanded="openKey === entry.key"
        :aria-controls="`${id}-panel`"
        @click="toggle(entry, $event)"
      >
        {{ entry.label }}
        <span aria-hidden="true" class="shell-disclosure-chevron" />
      </button>
      <a
        v-else-if="navigable(entry)"
        :href="entry.href"
        :class="['shell-nav-link', { 'is-active': active(entry) }]"
        :aria-current="
          entry.routeKey === current?.routeKey ? 'page' : undefined
        "
        @click="navigate(entry.href, $event)"
        >{{ entry.label }}</a
      >
      <span v-else class="shell-nav-link is-disabled" aria-disabled="true">{{
        entry.label
      }}</span>
    </li>
  </ul>
  <Teleport to="body">
    <div
      v-if="opened"
      :id="`${id}-panel`"
      ref="panel"
      class="shell-top-dropdown"
      :style="position"
      role="region"
      :aria-label="opened.label"
    >
      <AppNavigation
        context="dropdown"
        :entries="opened.children ?? []"
        :current="current"
        :expanded="expanded"
        @navigate="navigate"
        @toggle="
          (key) =>
            (expanded = expanded.includes(key)
              ? expanded.filter((value) => value !== key)
              : [...expanded, key])
        "
      />
    </div>
  </Teleport>
</template>

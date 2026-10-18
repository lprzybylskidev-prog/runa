<script setup lang="ts">
import { computed, useId } from 'vue';
import AppIcon from './AppIcon.vue';
import AppTooltip from './AppTooltip.vue';
import type { NavigationEntry, RouteContext } from '../runa/ui/navigation';
const props = withDefaults(
  defineProps<{
    entries: readonly NavigationEntry[];
    current?: RouteContext | undefined;
    expanded: readonly string[];
    collapsed?: boolean;
    horizontal?: boolean;
    depth?: number;
  }>(),
  { current: undefined, collapsed: false, horizontal: false, depth: 0 },
);
const emit = defineEmits<{
  navigate: [href: string, event: globalThis.MouseEvent];
  toggle: [key: string];
}>();
const id = useId();
const visible = computed(() =>
  props.entries.filter((entry) => entry.availability !== 'hidden'),
);
function navigable(entry: NavigationEntry): boolean {
  return (
    entry.availability === 'available' &&
    entry.href.startsWith('/') &&
    !entry.href.startsWith('//') &&
    !/[\\\s]/u.test(entry.href)
  );
}
function active(entry: NavigationEntry): boolean {
  return (
    entry.routeKey === props.current?.routeKey ||
    (props.depth === 0 &&
      !props.horizontal &&
      entry.moduleKey === props.current?.moduleKey)
  );
}
</script>
<template>
  <ul
    :class="[
      'shell-nav-list',
      { 'shell-nav-horizontal': horizontal, 'shell-nav-nested': depth > 0 },
    ]"
  >
    <li
      v-for="entry in visible"
      :key="entry.key"
      :class="{ 'shell-nav-group': entry.children?.length }"
    >
      <div class="shell-nav-row">
        <AppTooltip
          :text="collapsed ? entry.label : ''"
          :placement="collapsed ? 'right' : 'bottom'"
        >
          <template #default="{ descriptionId }">
            <a
              v-if="navigable(entry)"
              :href="entry.href"
              :aria-label="collapsed ? entry.label : undefined"
              :aria-describedby="collapsed ? descriptionId : undefined"
              :aria-current="
                entry.routeKey === current?.routeKey ? 'page' : undefined
              "
              :class="[
                'shell-nav-link',
                { 'is-active': active(entry), 'is-collapsed': collapsed },
              ]"
              @click="emit('navigate', entry.href, $event)"
            >
              <AppIcon
                v-if="!horizontal"
                :name="entry.icon ?? 'layers-intersect'"
              />
              <span v-if="!collapsed">{{ entry.label }}</span>
            </a>
            <span
              v-else
              class="shell-nav-link is-disabled"
              aria-disabled="true"
              :aria-label="entry.label"
            >
              <AppIcon
                v-if="!horizontal"
                :name="entry.icon ?? 'layers-intersect'"
              />
              <span v-if="!collapsed">{{ entry.label }}</span>
            </span>
          </template>
        </AppTooltip>
        <button
          v-if="
            entry.children?.some((child) => child.availability !== 'hidden') &&
            navigable(entry)
          "
          type="button"
          class="shell-group-toggle"
          :aria-label="entry.label"
          :aria-expanded="!collapsed && expanded.includes(entry.key)"
          :aria-controls="`${id}-${entry.key}`"
          @click="emit('toggle', entry.key)"
        >
          <span aria-hidden="true">{{
            !collapsed && expanded.includes(entry.key) ? '−' : '+'
          }}</span>
        </button>
      </div>
      <div
        v-if="entry.children"
        v-show="!collapsed && expanded.includes(entry.key)"
        :id="`${id}-${entry.key}`"
      >
        <AppNavigation
          :entries="entry.children"
          :current="current"
          :expanded="expanded"
          :horizontal="horizontal"
          :depth="depth + 1"
          @navigate="(href, event) => emit('navigate', href, event)"
          @toggle="(key) => emit('toggle', key)"
        />
      </div>
    </li>
  </ul>
</template>

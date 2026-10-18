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
    context?: 'sidebar' | 'dropdown';
    depth?: number;
  }>(),
  { current: undefined, collapsed: false, context: 'sidebar', depth: 0 },
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
      props.context === 'sidebar' &&
      entry.moduleKey === props.current?.moduleKey)
  );
}
</script>
<template>
  <ul :class="['shell-nav-list', { 'shell-nav-nested': depth > 0 }]">
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
            <button
              v-if="
                navigable(entry) &&
                entry.children?.some((child) => child.availability !== 'hidden')
              "
              type="button"
              :class="[
                'shell-nav-link shell-sidebar-disclosure',
                { 'is-active': active(entry), 'is-collapsed': collapsed },
              ]"
              :aria-label="collapsed ? entry.label : undefined"
              :aria-describedby="collapsed ? descriptionId : undefined"
              :aria-expanded="!collapsed && expanded.includes(entry.key)"
              :aria-controls="collapsed ? undefined : `${id}-${entry.key}`"
              @click="emit('toggle', entry.key)"
            >
              <AppIcon
                v-if="context === 'sidebar'"
                :name="entry.icon ?? 'layers-intersect'"
              />
              <span v-if="!collapsed">{{ entry.label }}</span>
              <span
                v-if="!collapsed"
                class="shell-disclosure-chevron"
                aria-hidden="true"
              />
            </button>
            <a
              v-else-if="navigable(entry)"
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
                v-if="context === 'sidebar'"
                :name="entry.icon ?? 'layers-intersect'"
              />
              <span v-if="!collapsed">{{ entry.label }}</span>
            </a>
            <span
              v-else
              :class="[
                'shell-nav-link is-disabled',
                { 'is-collapsed': collapsed },
              ]"
              aria-disabled="true"
              :aria-label="entry.label"
            >
              <AppIcon
                v-if="context === 'sidebar'"
                :name="entry.icon ?? 'layers-intersect'"
              />
              <span v-if="!collapsed">{{ entry.label }}</span>
            </span>
          </template>
        </AppTooltip>
      </div>
      <template v-if="!collapsed">
        <Transition name="shell-expand">
          <div
            v-if="entry.children"
            v-show="expanded.includes(entry.key)"
            :id="`${id}-${entry.key}`"
            class="shell-expansion"
            :inert="!expanded.includes(entry.key)"
          >
            <div class="shell-expansion-inner">
              <AppNavigation
                :entries="entry.children"
                :current="current"
                :expanded="expanded"
                :context="context"
                :depth="depth + 1"
                @navigate="(href, event) => emit('navigate', href, event)"
                @toggle="(key) => emit('toggle', key)"
              />
            </div>
          </div>
        </Transition>
      </template>
    </li>
  </ul>
</template>

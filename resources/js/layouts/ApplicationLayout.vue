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
import AppBackdrop from '../components/AppBackdrop.vue';
import AppButton from '../components/AppButton.vue';
import AppFooter from '../components/AppFooter.vue';
import AppLoadingOverlay from '../components/AppLoadingOverlay.vue';
import AppNavigation from '../components/AppNavigation.vue';
import AppPreferenceControls from '../components/AppPreferenceControls.vue';
import RunaLogo from '../components/RunaLogo.vue';
import { useDisplayPreferences } from '../composables/useDisplayPreferences';
import { usePreference } from '../composables/usePreference';
import { useIdentity } from '../runa/ui/identity';
import type { Locale } from '../locales/messages';
import type { FooterOptions } from '../runa/ui/identity';
import type { NavigationEntry, RouteContext } from '../runa/ui/navigation';
import type { PreferenceStore } from '../lib/preferenceStore';
const props = withDefaults(
  defineProps<{
    navigation: readonly NavigationEntry[];
    topNavigation?: readonly NavigationEntry[];
    current?: RouteContext | undefined;
    title: string;
    description?: string;
    sidebar?: boolean;
    topbar?: boolean;
    width?: 'contained' | 'fluid';
    footer?: FooterOptions | undefined;
    preferenceStore?: PreferenceStore | undefined;
    userName?: string;
    userInitials?: string;
  }>(),
  {
    current: undefined,
    topNavigation: () => [],
    description: '',
    sidebar: true,
    topbar: true,
    width: 'contained',
    footer: undefined,
    preferenceStore: undefined,
    userName: 'Runa',
    userInitials: 'RU',
  },
);
const emit = defineEmits<{
  navigate: [href: string, event: globalThis.MouseEvent];
  localeChange: [locale: Locale];
}>();
const identity = useIdentity();
const {
  locale,
  t,
  theme,
  themeLabel,
  languageLabel,
  changingPreference,
  coverVisible,
  changePreference,
  applyPreference,
  finishPreferenceChange,
} = useDisplayPreferences();
watch(locale, (value) => emit('localeChange', value), { immediate: true });
const scope = {
  applicationId: identity.value.applicationId,
  moduleKey: 'shell',
  viewKey: 'navigation',
};
const collapsed = usePreference(
  scope,
  'collapsed',
  {
    version: 1,
    defaultValue: false,
    validate: (value: unknown): value is boolean => typeof value === 'boolean',
  },
  props.preferenceStore,
);
const expanded = usePreference(
  scope,
  'expanded',
  {
    version: 1,
    defaultValue: [] as string[],
    validate: (value: unknown): value is string[] =>
      Array.isArray(value) &&
      value.length <= 200 &&
      value.every((key) => typeof key === 'string' && key.length < 200),
  },
  props.preferenceStore,
);
const topExpanded = ref<string[]>([]);
const drawer = ref(false);
const sidebarElement = ref<globalThis.HTMLElement>();
const drawerTrigger = ref<globalThis.HTMLButtonElement>();
const profile = ref(false);
const profileElement = ref<globalThis.HTMLElement>();
const profileTrigger = ref<globalThis.HTMLButtonElement>();
const mobile = ref(window.matchMedia('(max-width: 63.999rem)').matches);
const drawerId = useId();
const contentId = useId();
const profileId = useId();
const logoFailed = ref(false);
watch(
  () => identity.value.logo?.src,
  () => {
    logoFailed.value = false;
  },
);
const sidebarCollapsed = computed(() => collapsed.value && !mobile.value);
function toggleGroup(key: string) {
  if (sidebarCollapsed.value) collapsed.value = false;
  expanded.value = expanded.value.includes(key)
    ? expanded.value.filter((value) => value !== key)
    : [...expanded.value, key];
}
function toggleTop(key: string) {
  topExpanded.value = topExpanded.value.includes(key) ? [] : [key];
}
async function closeDrawer(restore = true) {
  drawer.value = false;
  await nextTick();
  if (restore) drawerTrigger.value?.focus({ preventScroll: true });
}
async function openDrawer() {
  drawer.value = true;
  await nextTick();
  sidebarElement.value
    ?.querySelector<globalThis.HTMLElement>('button')
    ?.focus();
}
function navigate(href: string, event: globalThis.MouseEvent) {
  if (
    event.button !== 0 ||
    event.ctrlKey ||
    event.metaKey ||
    event.shiftKey ||
    event.altKey
  )
    return;
  emit('navigate', href, event);
  if (event.defaultPrevented) {
    void closeDrawer(false);
    topExpanded.value = [];
    void nextTick(() =>
      document.getElementById(contentId)?.focus({ preventScroll: true }),
    );
  }
}
function keydown(event: globalThis.KeyboardEvent) {
  if (event.key === 'Escape') {
    if (drawer.value) {
      event.preventDefault();
      void closeDrawer();
    }
    if (profile.value) {
      profile.value = false;
      profileTrigger.value?.focus();
    }
    if (topExpanded.value.length && event.target instanceof window.Element) {
      event.target
        .closest('.shell-top-nav .shell-nav-group')
        ?.querySelector<globalThis.HTMLElement>('.shell-group-toggle')
        ?.focus();
    }
    topExpanded.value = [];
  }
  if (event.key === 'Tab' && drawer.value && mobile.value) {
    const focusable = Array.from(
      sidebarElement.value?.querySelectorAll<globalThis.HTMLElement>(
        'a[href], button:not([disabled])',
      ) ?? [],
    ).filter((element) => element.getClientRects().length > 0);
    const first = focusable[0];
    const last = focusable.at(-1);
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last?.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first?.focus();
    }
  }
}
function pointerdown(event: globalThis.PointerEvent) {
  if (
    event.target instanceof window.Node &&
    !profileElement.value?.contains(event.target)
  )
    profile.value = false;
  if (
    event.target instanceof window.Element &&
    !event.target.closest('.shell-top-nav')
  )
    topExpanded.value = [];
}
let previousOverflow = '';
watch(drawer, (open) => {
  if (open) {
    previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
  } else document.body.style.overflow = previousOverflow;
});
watch(
  () => props.sidebar,
  (visible) => {
    if (!visible && drawer.value) void closeDrawer();
  },
);
onMounted(() => {
  document.addEventListener('keydown', keydown);
  document.addEventListener('pointerdown', pointerdown);
});
const media = window.matchMedia('(max-width: 63.999rem)');
function resize() {
  mobile.value = media.matches;
  if (!mobile.value && drawer.value) void closeDrawer(false);
}
media.addEventListener('change', resize);
onScopeDispose(() => {
  document.removeEventListener('keydown', keydown);
  document.removeEventListener('pointerdown', pointerdown);
  media.removeEventListener('change', resize);
  if (drawer.value) document.body.style.overflow = previousOverflow;
});
</script>
<template>
  <div
    :class="[
      'application-page',
      {
        'has-sidebar': sidebar,
        'sidebar-collapsed': sidebarCollapsed,
        'workspace-fluid': width === 'fluid',
      },
    ]"
    :inert="changingPreference"
    :aria-busy="changingPreference"
  >
    <AppBackdrop />
    <a :href="`#${contentId}`" class="skip-link">{{ t('skip') }}</a>
    <button
      v-if="drawer && sidebar"
      class="shell-drawer-backdrop"
      type="button"
      tabindex="-1"
      :aria-label="t('closeNavigation')"
      @click="closeDrawer()"
    />
    <aside
      v-if="sidebar"
      :id="drawerId"
      ref="sidebarElement"
      :class="['shell-sidebar', { 'drawer-open': drawer }]"
      :role="mobile && drawer ? 'dialog' : undefined"
      :aria-modal="mobile && drawer ? true : undefined"
      :aria-label="t('modules')"
      :inert="mobile && !drawer"
    >
      <div class="shell-brand-row">
        <a href="/" class="shell-brand" :aria-label="identity.applicationName">
          <img
            v-if="identity.logo && !logoFailed"
            :src="identity.logo.src"
            :alt="identity.logo.accessibleName"
            class="h-7 max-w-24 object-contain"
            @error="logoFailed = true"
          />
          <RunaLogo v-else />
          <span v-if="!sidebarCollapsed"
            >{{ identity.applicationName
            }}<span class="text-primary" aria-hidden="true">.</span></span
          >
        </a>
        <button
          v-if="mobile"
          type="button"
          class="shell-group-toggle"
          :aria-label="t('closeNavigation')"
          @click="closeDrawer()"
        >
          ×
        </button>
      </div>
      <div class="shell-sidebar-scroll">
        <p v-if="!sidebarCollapsed" class="shell-eyebrow">
          {{ t('workspace') }}
        </p>
        <nav :aria-label="t('modules')">
          <AppNavigation
            :entries="navigation"
            :current="current"
            :expanded="expanded"
            :collapsed="sidebarCollapsed"
            @navigate="navigate"
            @toggle="toggleGroup"
          />
        </nav>
        <slot name="sidebar-bottom" :t="t" :collapsed="sidebarCollapsed" />
      </div>
      <div class="shell-sidebar-bottom">
        <AppButton
          v-if="!mobile"
          :aria-label="
            sidebarCollapsed ? t('expandNavigation') : t('collapseNavigation')
          "
          :aria-expanded="!sidebarCollapsed"
          @click="collapsed = !collapsed"
        >
          <span aria-hidden="true">{{ sidebarCollapsed ? '»' : '«' }}</span
          ><span v-if="!sidebarCollapsed" class="ml-3">{{
            t('collapseNavigation')
          }}</span>
        </AppButton>
        <span v-if="!sidebarCollapsed" class="shell-platform"
          >RUNA / {{ identity.platformVersion }}</span
        >
      </div>
    </aside>
    <div class="shell-body" :inert="drawer && mobile">
      <header class="shell-header" :class="{ 'shell-header-minimal': !topbar }">
        <div class="shell-header-start">
          <button
            v-if="sidebar && mobile"
            ref="drawerTrigger"
            type="button"
            class="shell-mobile-trigger"
            :aria-label="t('openNavigation')"
            :aria-expanded="drawer"
            :aria-controls="drawerId"
            @click="openDrawer"
          >
            ☰
          </button>
          <a v-if="!sidebar" href="/" class="shell-brand"
            ><RunaLogo /><span>{{ identity.applicationName }}</span></a
          >
          <nav v-if="topbar" class="shell-top-nav" :aria-label="t('areas')">
            <AppNavigation
              :entries="topNavigation"
              :current="current"
              :expanded="topExpanded"
              horizontal
              @navigate="navigate"
              @toggle="toggleTop"
            />
          </nav>
        </div>
        <div class="shell-header-controls" data-print-hide>
          <AppPreferenceControls
            :locale="locale"
            :theme="theme"
            :theme-label="themeLabel"
            :language-label="languageLabel"
            :t="t"
            @change="changePreference"
          />
          <div v-if="topbar" ref="profileElement" class="shell-profile">
            <button
              ref="profileTrigger"
              type="button"
              class="shell-avatar"
              :aria-label="`${t('userMenu')}: ${userName}`"
              :aria-expanded="profile"
              :aria-controls="profileId"
              @click="profile = !profile"
            >
              {{ userInitials }}
            </button>
            <div v-if="profile" :id="profileId" class="shell-profile-panel">
              <slot name="user-menu" :t="t"
                ><p class="font-semibold">{{ userName }}</p>
                <p class="mt-2 text-xs text-muted">
                  {{ t('accountUnavailable') }}
                </p></slot
              >
            </div>
          </div>
        </div>
      </header>
      <main :id="contentId" tabindex="-1" class="shell-workspace">
        <header class="shell-heading">
          <div>
            <slot name="heading" :t="t"
              ><h1>{{ title }}</h1>
              <p v-if="description" class="shell-description">
                {{ description }}
              </p></slot
            >
          </div>
          <div v-if="$slots.actions" class="shell-actions" data-print-hide>
            <slot name="actions" :t="t" />
          </div>
        </header>
        <div v-if="$slots.breadcrumbs" class="shell-breadcrumbs">
          <slot name="breadcrumbs" :t="t" />
        </div>
        <section
          v-if="$slots.statistics"
          class="shell-statistics"
          :aria-label="t('statistics')"
        >
          <slot name="statistics" :t="t" />
        </section>
        <div v-if="$slots.toolbar" class="shell-toolbar" data-print-hide>
          <slot name="toolbar" :t="t" />
        </div>
        <div
          :class="[
            'shell-content-grid',
            { 'has-supplementary': $slots.supplementary },
          ]"
        >
          <div class="shell-content">
            <slot name="content" :t="t"><slot :t="t" /></slot>
          </div>
          <aside
            v-if="$slots.supplementary"
            class="shell-supplementary"
            :aria-label="t('supplementary')"
          >
            <slot name="supplementary" :t="t" />
          </aside>
        </div>
      </main>
      <slot v-if="footer?.visible !== false" name="footer" :t="t"
        ><AppFooter :options="footer" :powered-by-label="t('poweredBy')"
      /></slot>
    </div>
  </div>
  <AppLoadingOverlay
    :active="coverVisible"
    :label="t('applyingPreferences')"
    @covered="applyPreference"
    @revealed="finishPreferenceChange"
  />
</template>

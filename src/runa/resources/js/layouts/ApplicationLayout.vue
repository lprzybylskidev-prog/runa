<script setup lang="ts">
import { initializePreferenceSession } from '../composables/useAccountPreferenceStatus';
import AppPreferenceFeedback from '../components/AppPreferenceFeedback.vue';
import { accountPreferences } from '../lib/preferenceStore';
import { lockDocumentScroll } from '../lib/scrollLock';
import {
  computed,
  nextTick,
  onMounted,
  onScopeDispose,
  ref,
  useId,
  watch,
} from 'vue';
import AppButton from '../components/AppButton.vue';
import AppPopover from '../components/AppPopover.vue';
import AppBackdrop from '../components/AppBackdrop.vue';
import AppFooter from '../components/AppFooter.vue';
import AppLoadingOverlay from '../components/AppLoadingOverlay.vue';
import AppTopNavigation from '../components/AppTopNavigation.vue';
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
watch(
  () => identity.value.applicationId,
  (app) => accountPreferences.setApplication(app),
  { immediate: true },
);
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
const drawer = ref(false);
const sidebarElement = ref<globalThis.HTMLElement>();
const drawerTrigger = ref<InstanceType<typeof AppButton>>();
const mobile = ref(window.matchMedia('(max-width: 63.999rem)').matches);
const drawerId = useId();
const contentId = useId();
const logoFailed = ref(false);
watch(
  () => identity.value.logo?.src,
  () => {
    logoFailed.value = false;
  },
);
const sidebarCollapsed = computed(() => collapsed.value && !mobile.value);
function toggleGroup(key: string) {
  if (sidebarCollapsed.value) {
    collapsed.value = false;
    if (!expanded.value.includes(key))
      expanded.value = [...expanded.value, key];
    return;
  }
  expanded.value = expanded.value.includes(key)
    ? expanded.value.filter((value) => value !== key)
    : [...expanded.value, key];
}
async function closeDrawer(restore = true) {
  drawer.value = false;
  await nextTick();
  if (restore) drawerTrigger.value?.$el.focus({ preventScroll: true });
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
const topNavElement = ref<globalThis.HTMLElement>();
const topOverflow = ref({ left: false, right: false });
let topNavObserver: globalThis.ResizeObserver | undefined;
function updateTopOverflow() {
  const element = topNavElement.value;
  topOverflow.value = {
    left: !!element && element.scrollLeft > 1,
    right:
      !!element &&
      element.scrollWidth - element.clientWidth - element.scrollLeft > 1,
  };
}
watch(
  topNavElement,
  (element) => {
    topNavObserver?.disconnect();
    if (element) {
      topNavObserver = new globalThis.ResizeObserver(updateTopOverflow);
      topNavObserver.observe(element);
      if (element.firstElementChild)
        topNavObserver.observe(element.firstElementChild);
    }
    updateTopOverflow();
  },
  { flush: 'post' },
);
watch(
  () => props.topNavigation,
  async () => {
    await nextTick();
    updateTopOverflow();
  },
);
onScopeDispose(() => topNavObserver?.disconnect());
function scrollTopNavigation(event: globalThis.WheelEvent) {
  if (event.ctrlKey || !(event.currentTarget instanceof window.HTMLElement))
    return;
  const navigation = event.currentTarget;
  if (navigation.scrollWidth <= navigation.clientWidth) return;
  const delta =
    Math.abs(event.deltaX) > Math.abs(event.deltaY)
      ? event.deltaX
      : event.deltaY;
  if (!delta) return;
  event.preventDefault();
  const unit =
    event.deltaMode === 1
      ? 16
      : event.deltaMode === 2
        ? navigation.clientWidth
        : 1;
  navigation.scrollLeft += delta * unit;
}
let unlockDrawerScroll: (() => void) | undefined;
watch(drawer, (open) => {
  unlockDrawerScroll?.();
  unlockDrawerScroll = open ? lockDocumentScroll() : undefined;
});
watch(
  () => props.sidebar,
  (visible) => {
    if (!visible && drawer.value) void closeDrawer();
  },
);
onMounted(() => {
  initializePreferenceSession();
  document.addEventListener('keydown', keydown);
});
const media = window.matchMedia('(max-width: 63.999rem)');
function resize() {
  mobile.value = media.matches;
  if (!mobile.value && drawer.value) void closeDrawer(false);
}
media.addEventListener('change', resize);
onScopeDispose(() => {
  document.removeEventListener('keydown', keydown);
  media.removeEventListener('change', resize);
  unlockDrawerScroll?.();
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
    <Transition name="shell-backdrop">
      <button
        v-if="drawer && sidebar"
        class="shell-drawer-backdrop"
        type="button"
        tabindex="-1"
        :aria-label="t('closeNavigation')"
        @click="closeDrawer()"
      />
    </Transition>
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
          <span class="brand-mark">
            <img
              v-if="identity.logo && !logoFailed"
              :src="identity.logo.src"
              :alt="identity.logo.accessibleName"
              class="max-h-7 max-w-7 object-contain"
              @error="logoFailed = true"
            />
            <RunaLogo v-else />
          </span>
          <span v-if="!sidebarCollapsed"
            >{{ identity.applicationName
            }}<span class="text-primary" aria-hidden="true">.</span></span
          >
        </a>
        <AppButton
          v-if="mobile"
          icon-only
          class="shell-group-toggle"
          :aria-label="t('closeNavigation')"
          @click="closeDrawer()"
        >
          ×
        </AppButton>
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
        <slot
          v-if="!sidebarCollapsed"
          name="sidebar-bottom"
          :t="t"
          :collapsed="sidebarCollapsed"
        />
      </div>
      <div class="shell-sidebar-bottom">
        <span class="shell-platform"
          ><template v-if="!sidebarCollapsed">RUNA / </template
          >{{ identity.platformVersion }}</span
        >
      </div>
    </aside>
    <div class="shell-body" :inert="drawer && mobile">
      <header class="shell-header" :class="{ 'shell-header-minimal': !topbar }">
        <div class="shell-header-start">
          <AppButton
            v-if="sidebar"
            ref="drawerTrigger"
            icon-only
            class="shell-sidebar-toggle"
            size="sm"
            :aria-label="
              mobile
                ? t('openNavigation')
                : sidebarCollapsed
                  ? t('expandNavigation')
                  : t('collapseNavigation')
            "
            :aria-expanded="mobile ? drawer : !sidebarCollapsed"
            :aria-controls="drawerId"
            @click="mobile ? openDrawer() : (collapsed = !collapsed)"
          >
            <span class="shell-toggle-icon" aria-hidden="true"
              ><span /><span /><span
            /></span>
          </AppButton>
          <a v-if="!sidebar" href="/" class="shell-brand"
            ><span class="brand-mark"><RunaLogo /></span
            ><span>{{ identity.applicationName }}</span></a
          >
          <div
            v-if="topbar"
            class="shell-top-nav-frame"
            :class="{
              'has-overflow-left': topOverflow.left,
              'has-overflow-right': topOverflow.right,
            }"
          >
            <nav
              ref="topNavElement"
              class="shell-top-nav"
              :aria-label="t('areas')"
              @scroll="updateTopOverflow"
              @wheel="scrollTopNavigation"
            >
              <AppTopNavigation
                :entries="topNavigation"
                :current="current"
                @navigate="navigate"
              />
            </nav>
          </div>
        </div>
        <div class="shell-header-controls" data-print-hide>
          <AppPreferenceControls
            size="sm"
            :locale="locale"
            :theme="theme"
            :theme-label="themeLabel"
            :language-label="languageLabel"
            :t="t"
            @change="changePreference"
          />
          <AppPopover
            v-if="topbar"
            :label="`${t('userMenu')}: ${userName}`"
            align="end"
          >
            <template #trigger="{ toggle, open, panelId }">
              <AppButton
                icon-only
                class="shell-avatar-button"
                :aria-label="`${t('userMenu')}: ${userName}`"
                :aria-expanded="open"
                :aria-controls="panelId"
                aria-haspopup="dialog"
                @click="toggle"
                >{{ userInitials }}</AppButton
              >
            </template>
            <div class="shell-profile-panel">
              <slot name="user-menu" :t="t"
                ><p class="font-semibold">{{ userName }}</p>
                <p class="mt-2 text-xs text-muted">
                  {{ t('accountUnavailable') }}
                </p></slot
              >
            </div>
          </AppPopover>
        </div>
      </header>
      <main :id="contentId" tabindex="-1" class="shell-workspace">
        <AppPreferenceFeedback :locale="locale" />
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

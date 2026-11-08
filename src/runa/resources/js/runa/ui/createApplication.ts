import {
  createApp,
  h,
  onMounted,
  onScopeDispose,
  computed,
  shallowRef,
} from 'vue';
import { applyApplicationDisplayDefaults } from './displayDefaults';
import { useTheme } from '../../composables/useTheme';
import { useLocale } from '../../composables/useLocale';
import { useImageReadiness } from '../../composables/useImageReadiness';
import type { Component } from 'vue';
import IdentityProvider from '../../components/AppIdentityProvider.vue';
import { defaultIdentity } from './identity';
import { loadApplicationIdentity } from './loadIdentity';
import { loadApplicationPresentation } from './presentation';
import type { ApplicationPresentation } from './presentation';
import { observeRequests } from '../../lib/http';

/** Per-application public branding hydration; disposed with the application root. */
export function createApplication(root: Component) {
  return createApp({
    setup() {
      useLocale();
      useTheme();
      const identity = shallowRef(defaultIdentity);
      const presentation = shallowRef<ApplicationPresentation>();
      const logoSource = computed(() =>
        presentation.value
          ? (presentation.value.logo?.src ?? '')
          : (identity.value.logo?.src ?? ''),
      );
      const logo = useImageReadiness(logoSource);
      let alive = true,
        epoch = 0;
      let abort = new AbortController();
      let pending: Promise<void> | undefined;
      let reread = false;
      const sourceIcons = Array.from(
        document.querySelectorAll<HTMLLinkElement>('link[rel~="icon"]'),
      ).map((element) => ({
        element,
        href: element.getAttribute('href'),
        type: element.getAttribute('type'),
        sizes: element.getAttribute('sizes'),
      }));
      const dynamicIcon = document.createElement('link');
      dynamicIcon.rel = 'icon';
      dynamicIcon.type = 'image/png';
      dynamicIcon.sizes.value = '64x64';
      function applyIcon(src?: string | null) {
        dynamicIcon.remove();
        if (src) {
          for (const { element } of sourceIcons) element.remove();
          dynamicIcon.href = src;
          document.head.append(dynamicIcon);
        } else
          for (const { element } of sourceIcons)
            if (!element.isConnected) document.head.append(element);
      }
      function refresh(): Promise<void> {
        if (!alive) return Promise.resolve();
        if (pending) return pending;
        const own = ++epoch;
        abort.abort();
        abort = new AbortController();
        pending = loadApplicationPresentation(
          AbortSignal.any([abort.signal, AbortSignal.timeout(5000)]),
        )
          .then((next) => {
            // A failed background read keeps the last confirmed public presentation.
            if (alive && own === epoch && next) {
              presentation.value = next;
              applyApplicationDisplayDefaults(next.defaults);
              applyIcon(next.favicon);
            }
          })
          .finally(() => {
            pending = undefined;
            if (alive && reread) {
              reread = false;
              void refresh();
            }
          });
        return pending;
      }
      function published() {
        if (pending) {
          // The pending read may precede this publication. Discard it and fetch
          // current data once after it settles. Repeated invalidations coalesce
          // into that same trailing read.
          epoch++;
          reread = true;
        } else void refresh();
      }
      void loadApplicationIdentity().then((resolved) => {
        if (alive) {
          identity.value = resolved;
        }
      });
      let sessionIdentity: string | undefined;
      const stop = observeRequests((event) => {
        if (
          event.phase === 'end' &&
          event.status === 200 &&
          event.path === '/runa/auth/session'
        ) {
          const payload = event.payload as {
            data?: { public_id?: string; session?: { public_id?: string } };
          };
          const next = payload?.data?.public_id;
          if (typeof next === 'string') {
            const stamp = JSON.stringify([
              next,
              payload.data?.session?.public_id,
            ]);
            const changed =
              sessionIdentity !== undefined && sessionIdentity !== stamp;
            sessionIdentity = stamp;
            if (changed) published();
          }
        }
        if (
          event.phase === 'end' &&
          event.status !== undefined &&
          event.status >= 200 &&
          event.status < 300 &&
          (event.path === '/admin/operations/application.update' ||
            /^\/runa\/auth\/(?:login|logout)$/.test(event.path))
        )
          published();
      });
      onMounted(() => {
        void refresh();
      });
      onScopeDispose(() => {
        alive = false;
        epoch++;
        abort.abort();
        stop();
        applyIcon(null);
        applyApplicationDisplayDefaults();
      });
      return () => {
        const { logo: sourceLogo, ...canonical } = identity.value;
        const selected = presentation.value;
        return h(
          IdentityProvider,
          {
            identity: selected
              ? {
                  ...canonical,
                  applicationName: selected.name,
                  ...(selected.logo && logo.src.value
                    ? { logo: { ...selected.logo, src: logo.src.value } }
                    : {}),
                }
              : {
                  ...canonical,
                  ...(sourceLogo && logo.src.value
                    ? { logo: { ...sourceLogo, src: logo.src.value } }
                    : {}),
                },
          },
          { default: () => h(root) },
        );
      };
    },
  });
}

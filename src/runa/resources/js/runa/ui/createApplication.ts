import {
  createApp,
  h,
  onMounted,
  onScopeDispose,
  computed,
  shallowRef,
} from 'vue';
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
      async function refresh() {
        const own = ++epoch;
        abort.abort();
        abort = new AbortController();
        const next = await loadApplicationPresentation(
          AbortSignal.any([abort.signal, AbortSignal.timeout(5000)]),
        );
        if (alive && own === epoch) {
          // A failed background read keeps the last confirmed public presentation.
          if (next) presentation.value = next;
        }
      }
      void loadApplicationIdentity().then((resolved) => {
        if (alive) {
          identity.value = resolved;
        }
      });
      const stop = observeRequests((event) => {
        if (
          event.phase === 'end' &&
          event.status !== undefined &&
          event.status >= 200 &&
          event.status < 300 &&
          (event.path === '/admin/operations/application.update' ||
            /^\/runa\/auth\/(?:session|login|logout|step-up)$/.test(event.path))
        )
          void refresh();
      });
      function visible() {
        if (document.visibilityState === 'visible') void refresh();
      }
      onMounted(() => {
        void refresh();
        window.addEventListener('focus', refresh);
        window.addEventListener('popstate', refresh);
        document.addEventListener('visibilitychange', visible);
      });
      onScopeDispose(() => {
        alive = false;
        epoch++;
        abort.abort();
        stop();
        window.removeEventListener('focus', refresh);
        window.removeEventListener('popstate', refresh);
        document.removeEventListener('visibilitychange', visible);
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

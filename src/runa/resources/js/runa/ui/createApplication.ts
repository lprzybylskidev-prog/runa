import {
  createApp,
  h,
  onMounted,
  onScopeDispose,
  ref,
  computed,
  shallowRef,
} from 'vue';
import { useTheme } from '../../composables/useTheme';
import { useLocale } from '../../composables/useLocale';
import AppSkeleton from '../../components/AppSkeleton.vue';
import ViewBoundary from '../../components/AppViewBoundary.vue';
import { useViewReadiness } from '../../composables/useViewReadiness';
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
      const { locale } = useLocale();
      useTheme();
      const identity = shallowRef(defaultIdentity);
      const presentation = shallowRef<ApplicationPresentation>();
      const identitySettled = ref(false),
        presentationSettled = ref(false);
      const logoSource = computed(() =>
        presentation.value
          ? (presentation.value.logo?.src ?? '')
          : (identity.value.logo?.src ?? ''),
      );
      const logo = useImageReadiness(logoSource);
      const readiness = useViewReadiness({
        identity: 'application',
        required: () =>
          identitySettled.value &&
          presentationSettled.value &&
          logo.settled.value,
      });
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
          presentationSettled.value = true;
        }
      }
      void loadApplicationIdentity().then((resolved) => {
        if (alive) {
          identity.value = resolved;
          identitySettled.value = true;
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
          {
            default: () =>
              h(
                ViewBoundary,
                {
                  readiness,
                  label:
                    locale.value === 'pl'
                      ? 'Wczytywanie aplikacji'
                      : 'Loading application',
                },
                {
                  loading: () =>
                    h(
                      'div',
                      { class: 'min-h-dvh p-6 sm:p-10' },
                      h(AppSkeleton, { lines: 3 }),
                    ),
                  default: () => h(root),
                },
              ),
          },
        );
      };
    },
  });
}

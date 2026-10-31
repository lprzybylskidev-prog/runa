import { createApp, h, onMounted, onScopeDispose, shallowRef } from 'vue';
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
      const identity = shallowRef(defaultIdentity);
      const presentation = shallowRef<ApplicationPresentation>();
      let alive = true,
        epoch = 0;
      let abort = new AbortController();
      async function refresh() {
        const own = ++epoch;
        abort.abort();
        abort = new AbortController();
        const next = await loadApplicationPresentation(abort.signal);
        if (alive && own === epoch) presentation.value = next;
      }
      void loadApplicationIdentity().then((resolved) => {
        if (alive) identity.value = resolved;
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
        const { logo, ...canonical } = identity.value;
        const selected = presentation.value;
        return h(
          IdentityProvider,
          {
            identity: selected
              ? {
                  ...canonical,
                  applicationName: selected.name,
                  ...(selected.logo ? { logo: selected.logo } : {}),
                }
              : { ...canonical, ...(logo ? { logo } : {}) },
          },
          { default: () => h(root) },
        );
      };
    },
  });
}

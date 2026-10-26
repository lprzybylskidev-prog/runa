import { createApp, h, shallowRef } from 'vue';
import type { Component } from 'vue';
import IdentityProvider from '../../components/AppIdentityProvider.vue';
import { defaultIdentity } from './identity';
import { loadApplicationIdentity } from './loadIdentity';

/** Non-blocking public identity hydration; callers may install plugins before mounting. */
export function createApplication(root: Component) {
  const identity = shallowRef(defaultIdentity);
  void loadApplicationIdentity().then((resolved) => {
    identity.value = resolved;
  });
  return createApp({
    render: () =>
      h(
        IdentityProvider,
        { identity: identity.value },
        { default: () => h(root) },
      ),
  });
}

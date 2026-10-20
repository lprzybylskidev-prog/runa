import { createApp } from 'vue';
import App from './App.vue';
import {
  defaultIdentity,
  IdentityProvider,
  loadApplicationIdentity,
} from '@/runa/ui';
import { h, shallowRef } from 'vue';
import '../../../runa/resources/css/app.css';

const identity = shallowRef(defaultIdentity);
void loadApplicationIdentity().then((resolved) => {
  identity.value = resolved;
});
createApp({
  render: () =>
    h(
      IdentityProvider,
      { identity: identity.value },
      { default: () => h(App) },
    ),
}).mount('#app');

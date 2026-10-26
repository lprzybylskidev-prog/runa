import { createApp } from 'vue';
import ApplicationScenario from './ApplicationScenario.vue';
import MinimalScenario from './MinimalScenario.vue';
import ComponentHarness from './ComponentHarness.vue';
import AuthAccountScenario from './AuthAccountScenario.vue';
import AuthScenario from './AuthScenario.vue';
import './harness.css';
const params = new window.URLSearchParams(window.location.search);
createApp(
  window.location.pathname.startsWith('/__fixtures/application/')
    ? ApplicationScenario
    : ['/', '/app', '/account', '/auth/login'].includes(
          window.location.pathname,
        ) || params.get('preview') === 'auth-account'
      ? AuthAccountScenario
      : params.get('preview') === 'auth'
        ? AuthScenario
        : params.get('preview') === 'minimal'
          ? MinimalScenario
          : ComponentHarness,
).mount('#app');

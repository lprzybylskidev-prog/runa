import { createApp } from 'vue';
import ApplicationScenario from './ApplicationScenario.vue';
import MinimalScenario from './MinimalScenario.vue';
import ComponentHarness from './ComponentHarness.vue';
import './harness.css';
const params = new window.URLSearchParams(window.location.search);
createApp(
  window.location.pathname.startsWith('/__fixtures/application/')
    ? ApplicationScenario
    : params.get('preview') === 'minimal'
      ? MinimalScenario
      : ComponentHarness,
).mount('#app');

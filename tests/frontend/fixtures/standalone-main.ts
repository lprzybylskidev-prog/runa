import { createApp } from 'vue';
import StandaloneConsumer from './StandaloneConsumer.vue';
import './standalone.css';
const params = new URLSearchParams(window.location.search);
const english = params.get('locale') === 'en';
document.documentElement.lang = english ? 'en' : 'pl';
document.documentElement.dataset.theme =
  params.get('theme') === 'dark' ? 'dark' : 'light';
createApp(StandaloneConsumer, { english }).mount('#app');

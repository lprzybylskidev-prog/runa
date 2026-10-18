import { createApp } from 'vue';
import ApplicationReview from './ApplicationReview.vue';
import ReviewCatalog from './ReviewCatalog.vue';
import './review.css';

createApp(
  window.location.pathname.startsWith('/__review/application/')
    ? ApplicationReview
    : ReviewCatalog,
).mount('#app');

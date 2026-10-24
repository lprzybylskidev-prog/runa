import App from './App.vue';
import { createApplication } from '@/runa/ui';
import '@/runa/ui/styles.css';

createApplication(App).mount('#app');

import { mergeConfig } from 'vite';
import config from './vite.config.mjs';

// Separate entry/output: neither the production build nor its Docker COPY ships it.
export default mergeConfig(config, {
  root: 'resources/review',
  base: '/ui-review/',
  build: { outDir: '../../public/ui-review', emptyOutDir: true },
});

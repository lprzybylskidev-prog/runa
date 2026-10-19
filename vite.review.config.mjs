import { build, mergeConfig } from 'vite';
import { fileURLToPath } from 'node:url';
import config from './vite.config.mjs';

// Independent builds prevent shared catalog/layout chunks entering the bare consumer.
export default mergeConfig(config, {
  root: 'resources/review',
  base: '/ui-review/',
  build: { outDir: '../../public/ui-review', emptyOutDir: true },
  plugins: [
    {
      name: 'review-independent-consumer',
      async closeBundle() {
        await build(
          mergeConfig(config, {
            configFile: false,
            root: 'resources/review',
            base: '/ui-review/standalone/',
            build: {
              outDir: '../../public/ui-review/standalone',
              emptyOutDir: true,
              rolldownOptions: {
                input: fileURLToPath(
                  new URL(
                    './resources/review/standalone.html',
                    import.meta.url,
                  ),
                ),
              },
            },
          }),
        );
      },
    },
  ],
});

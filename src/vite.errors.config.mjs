import { readFileSync } from 'node:fs';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

const platform = JSON.parse(
  readFileSync(new URL('./runa/platform.json', import.meta.url), 'utf8'),
);
if (
  platform.schema_version !== 1 ||
  platform.id !== 'runa' ||
  platform.name !== 'Runa' ||
  !/^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$/.test(platform.version)
)
  throw new Error('Invalid platform manifest identity/version.');

export default defineConfig({
  base: '/runa-error/',
  publicDir: false,
  plugins: [vue(), tailwindcss()],
  build: {
    ssr: 'runa/resources/errors/render.mjs',
    outDir: '.runtime/error-renderer',
    emptyOutDir: true,
    ssrEmitAssets: true,
    cssCodeSplit: false,
    rollupOptions: {
      output: {
        entryFileNames: 'render.mjs',
        assetFileNames: '[name][extname]',
      },
    },
  },
});

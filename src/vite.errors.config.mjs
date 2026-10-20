import { readFileSync } from 'node:fs';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

const platform = JSON.parse(
  readFileSync(new URL('./runa/platform.json', import.meta.url), 'utf8'),
);
const releaseVersion = readFileSync(
  new URL('../VERSION', import.meta.url),
  'utf8',
).trim();
if (
  platform.schema_version !== 1 ||
  platform.id !== 'runa' ||
  platform.name !== 'Runa' ||
  platform.version !== releaseVersion
)
  throw new Error('Platform manifest must match repository VERSION.');

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

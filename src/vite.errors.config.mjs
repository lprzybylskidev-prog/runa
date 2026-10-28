import { existsSync, readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
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
  // The private renderer lives outside src/node_modules; ship a self-contained build tool.
  ssr: { noExternal: true },
  resolve: {
    alias: {
      '@/runa': fileURLToPath(
        new URL('./runa/resources/js/runa', import.meta.url),
      ),
      '@': fileURLToPath(new URL('./app/resources/js', import.meta.url)),
      'virtual:runa-error-layout': fileURLToPath(
        new URL(
          existsSync(
            new URL('./app/resources/js/errorLayout.ts', import.meta.url),
          )
            ? './app/resources/js/errorLayout.ts'
            : './runa/resources/js/layouts/HttpErrorLayout.vue',
          import.meta.url,
        ),
      ),
    },
  },
  publicDir: false,
  plugins: [vue(), tailwindcss()],
  build: {
    ssr: 'runa/build/errors/render.mjs',
    outDir: fileURLToPath(
      new URL('../.runtime/frontend/errors/renderer', import.meta.url),
    ),
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

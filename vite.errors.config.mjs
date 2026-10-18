import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  base: '/runa-error/',
  publicDir: false,
  plugins: [vue(), tailwindcss()],
  build: {
    ssr: 'resources/errors/render.mjs',
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

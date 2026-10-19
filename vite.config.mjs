import { defineConfig } from 'vite';
import { fileURLToPath } from 'node:url';
import { readFileSync } from 'node:fs';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  root: 'resources',
  resolve: {
    alias: { '@': fileURLToPath(new URL('./resources/js', import.meta.url)) },
  },
  base: '/build/',
  publicDir: false,
  plugins: [
    vue(),
    tailwindcss(),
    {
      name: 'runa-export-notices',
      generateBundle() {
        this.emitFile({
          type: 'asset',
          fileName: 'licenses/export-dependencies.txt',
          source: readFileSync(
            new URL(
              './resources/licenses/export-dependencies.txt',
              import.meta.url,
            ),
            'utf8',
          ),
        });
        this.emitFile({
          type: 'asset',
          fileName: 'licenses/Roboto-OFL.txt',
          source: readFileSync(
            new URL('./resources/licenses/Roboto-OFL.txt', import.meta.url),
            'utf8',
          ),
        });
      },
    },
  ],
  build: {
    outDir: '../public/build',
    emptyOutDir: true,
    license: { fileName: 'licenses/dependencies.md' },
  },
  server: {
    port: 5173,
    strictPort: true,
    proxy: { '/api': 'http://nginx:8080' },
  },
});

import { defineConfig } from 'vite';
import { fileURLToPath } from 'node:url';
import { readFileSync } from 'node:fs';
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
  root: 'app/resources',
  resolve: {
    alias: {
      '@/runa': fileURLToPath(
        new URL('./runa/resources/js/runa', import.meta.url),
      ),
      '@runa': fileURLToPath(new URL('./runa/resources/js', import.meta.url)),
      '@': fileURLToPath(new URL('./app/resources/js', import.meta.url)),
    },
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
              './runa/resources/licenses/export-dependencies.txt',
              import.meta.url,
            ),
            'utf8',
          ),
        });
        this.emitFile({
          type: 'asset',
          fileName: 'licenses/Roboto-OFL.txt',
          source: readFileSync(
            new URL(
              './runa/resources/licenses/Roboto-OFL.txt',
              import.meta.url,
            ),
            'utf8',
          ),
        });
      },
    },
  ],
  build: {
    outDir: '../../public/build',
    emptyOutDir: true,
    license: { fileName: 'licenses/dependencies.md' },
  },
  server: {
    port: 5173,
    strictPort: true,
    proxy: { '/api': 'http://nginx:8080' },
  },
});

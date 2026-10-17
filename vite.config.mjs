import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  root: 'resources',
  base: '/build/',
  publicDir: false,
  plugins: [vue(), tailwindcss()],
  build: { outDir: '../public/build', emptyOutDir: true },
  server: {
    port: 5173,
    strictPort: true,
    proxy: { '/api': 'http://nginx:8080' },
  },
});

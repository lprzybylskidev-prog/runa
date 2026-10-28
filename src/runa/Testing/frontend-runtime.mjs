import { createRequire } from 'node:module';
export { build, mergeConfig } from 'vite';
const require = createRequire(new URL('../../package.json', import.meta.url));
export const testAliases = [
  {
    find: /^@runa\/testing\/vue$/,
    replacement: require.resolve('./runa/Testing/vue.ts'),
  },
  {
    find: /^@runa\/testing\/vitest$/,
    replacement: require.resolve('./runa/Testing/vitest.ts'),
  },
  {
    find: /^vue$/,
    replacement: require.resolve('vue/dist/vue.runtime.esm-bundler.js'),
  },
];

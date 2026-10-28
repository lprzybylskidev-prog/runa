import { fileURLToPath } from 'node:url';
import js from '@eslint/js';
import boundary from './runa/frontend/api-boundary.mjs';
import ts from 'typescript-eslint';
import vue from 'eslint-plugin-vue';

export default ts.config(
  {
    ignores: [
      '**/vendor/**',
      '**/node_modules/**',
      'src/public/build/**',
      '.runtime/**',
      'artifacts/**',
    ],
  },
  {
    basePath: fileURLToPath(new URL('../', import.meta.url)),
    files: [
      'src/app/**/*.{js,ts,vue,mjs}',
      'tests/frontend/app/**/*.{js,ts,vue,mjs}',
    ],
    plugins: { runa: boundary },
    rules: { 'runa/public-imports': 'error' },
  },
  js.configs.recommended,
  ...ts.configs.recommended,
  ...vue.configs['flat/recommended'],
  {
    basePath: fileURLToPath(new URL('../', import.meta.url)),
    files: ['**/*.vue'],
    // Core assignment analysis cannot see references from Vue templates.
    rules: { 'no-useless-assignment': 'off' },
    languageOptions: {
      parserOptions: { parser: ts.parser, extraFileExtensions: ['.vue'] },
    },
  },
  {
    basePath: fileURLToPath(new URL('../', import.meta.url)),
    files: [
      'src/{app,runa}/resources/**/*.{ts,vue}',
      'src/{app,runa}/modules/**/*.{ts,vue}',
      'tests/frontend/{app,runa}/unit/**/*.{ts,vue}',
      'src/runa/Testing/**/*.ts',
    ],
    languageOptions: {
      globals: Object.fromEntries(
        [
          'Event',
          'File',
          'Blob',
          'URL',
          'Image',
          'HTMLCanvasElement',
          'HTMLImageElement',
          'PointerEvent',
          'WheelEvent',
          'KeyboardEvent',
          'TouchEvent',
          'window',
          'document',
          'fetch',
          'localStorage',
          'AbortController',
          'RequestInit',
          'Headers',
          'Response',
          'SubmitEvent',
          'HTMLSelectElement',
          'MediaQueryListEvent',
        ].map((name) => [name, 'readonly']),
      ),
    },
  },
  {
    basePath: fileURLToPath(new URL('../', import.meta.url)),
    files: ['**/*.mjs', 'tests/frontend/{app,runa}/e2e/**/*.ts'],
    languageOptions: {
      globals: {
        process: 'readonly',
        console: 'readonly',
        URL: 'readonly',
        setTimeout: 'readonly',
      },
    },
  },
  // Prettier owns layout; Vue lint retains correctness/accessibility diagnostics.
  {
    rules: {
      'vue/html-indent': 'off',
      'vue/html-self-closing': 'off',
      'vue/max-attributes-per-line': 'off',
      'vue/singleline-html-element-content-newline': 'off',
      'vue/multiline-html-element-content-newline': 'off',
      'vue/html-closing-bracket-newline': 'off',
    },
  },
);

import js from '@eslint/js';
import ts from 'typescript-eslint';
import vue from 'eslint-plugin-vue';

export default ts.config(
  {
    ignores: [
      'vendor/**',
      'node_modules/**',
      'public/build/**',
      '../.runtime/**',
      'artifacts/**',
    ],
  },
  js.configs.recommended,
  ...ts.configs.recommended,
  ...vue.configs['flat/recommended'],
  {
    files: ['**/*.vue'],
    // Core assignment analysis cannot see references from Vue templates.
    rules: { 'no-useless-assignment': 'off' },
    languageOptions: {
      parserOptions: { parser: ts.parser, extraFileExtensions: ['.vue'] },
    },
  },
  {
    files: [
      '{app,runa}/resources/**/*.{ts,vue}',
      '{app,runa}/modules/**/*.{ts,vue}',
      '{app,runa}/tests/frontend/**/*.{ts,vue}',
    ],
    languageOptions: {
      globals: Object.fromEntries(
        [
          'Event',
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
    files: ['**/*.mjs', '{app,runa}/tests/e2e/**/*.ts'],
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

import path from 'node:path';
import { fileURLToPath } from 'node:url';

const src = fileURLToPath(new URL('../../', import.meta.url));
const platform = path.join(src, 'runa');
const publicFiles = new Set(
  [
    'http/index.ts',
    'auth/index.ts',
    'modules/index.ts',
    'modules/presentation.ts',
    ...[
      'index',
      'advancedForms',
      'layouts',
      'navigation',
      'loading',
      'tables',
      'errors',
      'identity',
      'loadIdentity',
      'preferences',
      'dialogs',
      'feedback',
      'selection',
      'exports',
      'icons',
      'forms',
      'containers',
      'createApplication',
      'controls',
    ].map((name) => `ui/${name}.ts`),
    'ui/styles.css',
  ].map((name) => path.join(platform, 'resources/js/runa', name)),
);
for (const name of [
  'vue.ts',
  'vitest.ts',
  'playwright.ts',
  'browserProblems.ts',
  'frontend-runtime.mjs',
]) {
  publicFiles.add(path.join(platform, 'Testing', name));
}
publicFiles.add(path.join(platform, 'frontend/module-components.mjs'));

export function publicImport(specifier, filename) {
  if (specifier === 'virtual:runa-module-components') return true;
  let target;
  if (specifier.startsWith('@/runa/'))
    target = path.resolve(platform, 'resources/js/runa', specifier.slice(7));
  else if (specifier.startsWith('@runa/testing/'))
    target = path.resolve(platform, 'Testing', specifier.slice(14));
  else if (specifier.startsWith('@runa/'))
    target = path.resolve(platform, 'resources/js', specifier.slice(6));
  else if (specifier.startsWith('.'))
    target = path.resolve(path.dirname(filename), specifier);
  else if (path.isAbsolute(specifier)) target = path.normalize(specifier);
  else return true;
  if (!target.startsWith(platform + path.sep)) return true;
  return [target, target + '.ts', target + '/index.ts'].some((candidate) =>
    publicFiles.has(candidate),
  );
}

export default {
  rules: {
    'public-imports': {
      meta: {
        type: 'problem',
        schema: [],
        messages: {
          private:
            'Use a supported Runa facade or documented development entrypoint.',
        },
      },
      create(context) {
        function check(node) {
          if (
            typeof node?.value === 'string' &&
            !publicImport(node.value, context.filename)
          ) {
            context.report({ node, messageId: 'private' });
          }
        }
        return {
          ImportDeclaration(node) {
            check(node.source);
          },
          ExportNamedDeclaration(node) {
            check(node.source);
          },
          ExportAllDeclaration(node) {
            check(node.source);
          },
          ImportExpression(node) {
            check(node.source);
          },
          TSImportType(node) {
            check(node.argument?.literal ?? node.argument ?? node.source);
          },
          CallExpression(node) {
            if (
              node.callee.type === 'Identifier' &&
              node.callee.name === 'require'
            )
              check(node.arguments[0]);
          },
        };
      },
    },
  },
};

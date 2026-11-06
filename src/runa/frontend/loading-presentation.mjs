import path from 'node:path';
import { fileURLToPath } from 'node:url';
const components = fileURLToPath(
  new URL('../resources/js/components/', import.meta.url),
);
const indicatorOwners = new Set(
  [
    'AppSpinner.vue',
    'AppButton.vue',
    'AppTable.vue',
    'AppLoadingOverlay.vue',
  ].map((name) => path.join(components, name)),
);
export default {
  rules: {
    'content-loading': {
      meta: {
        type: 'problem',
        docs: {
          description:
            'Keep circular indicators in their shared owners; content uses pulsing placeholders.',
        },
        schema: [],
        messages: {
          spinner:
            'Content reads use ContentLoading, Dialog loading or AsyncContent. Circular indicators belong to shared Table, Button and preference-cover owners.',
        },
      },
      create(context) {
        if (indicatorOwners.has(path.resolve(context.filename))) return {};
        const visitors = {
          ImportDeclaration(node) {
            if (
              /AppSpinner\.vue$/.test(node.source.value) ||
              node.specifiers.some(
                (specifier) =>
                  specifier.type === 'ImportSpecifier' &&
                  (specifier.imported.name ?? specifier.imported.value) ===
                    'Spinner',
              )
            )
              context.report({ node, messageId: 'spinner' });
          },
          MemberExpression(node) {
            const name = node.computed
              ? node.property.value
              : node.property.name;
            if (name === 'Spinner')
              context.report({ node, messageId: 'spinner' });
          },
        };
        const services = context.sourceCode.parserServices;
        return services.defineTemplateBodyVisitor
          ? services.defineTemplateBodyVisitor(
              {
                VElement(node) {
                  if (/(^|\.)(Spinner|AppSpinner)$/i.test(node.rawName))
                    context.report({ node, messageId: 'spinner' });
                },
              },
              visitors,
            )
          : visitors;
      },
    },
  },
};

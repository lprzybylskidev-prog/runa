import { createSSRApp, h } from 'vue';
import { renderToString } from 'vue/server-renderer';
import {
  mkdir,
  readdir,
  readFile,
  writeFile,
  copyFile,
  rm,
} from 'node:fs/promises';
import { resolve } from 'node:path';
import ErrorLayout from 'virtual:runa-error-layout';
import IdentityProvider from '../js/components/AppIdentityProvider.vue';
import MinimalErrorContent from '../js/layouts/MinimalErrorContent.vue';
import copy from './messages.json';
import '../css/app.css';

const codes = copy.codes;
// The package build runs from src; all private generated state belongs to the workspace root.
const errorRuntime = resolve('../.runtime/frontend/errors');
const templates = resolve(errorRuntime, 'templates');
const assets = resolve('public/runa-error');
await rm(templates, { recursive: true, force: true });
await rm(assets, { recursive: true, force: true });
await mkdir(templates, { recursive: true });
await mkdir(assets, { recursive: true });
for (const file of await readdir(resolve(errorRuntime, 'renderer'))) {
  if (/\.(css|png|woff2)$/.test(file)) {
    await copyFile(
      resolve(errorRuntime, 'renderer', file),
      resolve(assets, file.endsWith('.css') ? 'styles.css' : file),
    );
  }
}
await copyFile(
  'runa/resources/fonts/LICENSE.txt',
  resolve(assets, 'font-license.txt'),
);
// CSS-only system theme: use the actual shared dark token block, including artwork values.
const css = await readFile(resolve(assets, 'styles.css'), 'utf8');
const dark = css.match(/:root\[data-theme=["']?dark["']?\]\{([^}]+)\}/);
if (!dark) throw new Error('Shared dark palette not emitted.');
await writeFile(
  resolve(assets, 'styles.css'),
  css +
    `\n@media(prefers-color-scheme:dark){:root[data-theme=system]{${dark[1]}}}\n`,
);
for (const locale of ['pl', 'en']) {
  for (const theme of ['system', 'light', 'dark']) {
    for (const [status, code] of Object.entries(codes)) {
      for (const variant of ['runa', 'custom']) {
        const identity = {
          applicationId: variant,
          applicationName:
            variant === 'runa' ? 'Runa' : '__RUNA_APPLICATION_NAME__',
          applicationVersion: '__RUNA_APPLICATION_VERSION__',
          platformVersion: '__RUNA_PLATFORM_VERSION__',
        };
        const app = createSSRApp({
          render: () =>
            h(
              IdentityProvider,
              { identity },
              {
                default: () =>
                  h(
                    ErrorLayout,
                    {
                      error: {
                        status: Number(status),
                        locale,
                        theme,
                        code,
                        title: copy[locale].statuses[status][0],
                        description: copy[locale].statuses[status][1],
                        home: copy[locale].home,
                        retry: copy[locale].retry,
                      },
                      staticPreferences: { locale, theme },
                      footer: {
                        copyrightText: '© __RUNA_YEAR__ Łukasz Przybylski',
                      },
                    },
                    {
                      default: () =>
                        h(MinimalErrorContent, {
                          status: Number(status),
                          locale,
                          theme,
                          code,
                          title: copy[locale].statuses[status][0],
                          description: copy[locale].statuses[status][1],
                          home: copy[locale].home,
                          retry: copy[locale].retry,
                        }),
                    },
                  ),
              },
            ),
        });
        const html = await renderToString(app);
        if (/\sstyle=|<script\b|\son[a-z]+\s*=/i.test(html))
          throw new Error(
            'Static shell must satisfy the normal CSP without JavaScript.',
          );
        await writeFile(
          resolve(templates, `${status}-${locale}-${theme}-${variant}.html`),
          html,
        );
      }
    }
  }
}
console.info(
  'Built native/static error shells directly from MinimalLayout.vue.',
);

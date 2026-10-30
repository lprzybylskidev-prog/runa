import { test } from 'node:test';
import assert from 'node:assert/strict';
import {
  mkdtempSync,
  mkdirSync,
  writeFileSync,
  rmSync,
  symlinkSync,
} from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { createHash } from 'node:crypto';
import {
  discoverModuleComponents,
  discoverComponentBuilds,
  moduleComponentsPlugin,
} from '../../../../src/runa/frontend/module-components.mjs';
test('deterministic owned build keys, production development exclusion and opt-in loading', () => {
  const root = mkdtempSync(join(tmpdir(), 'runa-frontend-build-'));
  try {
    for (const code of ['zeta', 'alpha', 'module_probe', 'reference']) {
      const path = join(root, 'app/modules', code);
      mkdirSync(join(path, 'frontend/components'), { recursive: true });
      writeFileSync(
        join(path, 'module.json'),
        JSON.stringify({ schema_version: 1, id: `app.${code}` }),
      );
      writeFileSync(
        join(path, 'frontend/components/panel.vue'),
        '<template>Panel</template>',
      );
    }
    assert.deepEqual(
      discoverModuleComponents(root).map(([key]) => key),
      ['app.alpha.panel', 'app.zeta.panel'],
    );
    assert.equal(discoverModuleComponents(root, true).length, 3);
    assert.ok(
      !discoverModuleComponents(root, true).some(([key]) =>
        key.startsWith('app.module_probe.'),
      ),
    );
    const plugin = moduleComponentsPlugin(root);
    plugin.configResolved({ command: 'build', mode: 'production' });
    assert.equal(plugin.load('unrelated'), undefined);
    assert.ok(
      !plugin.load('\0virtual:runa-module-components').includes('module_probe'),
    );
    const path = join(root, 'app/modules/alpha/frontend/components/panel.vue');
    rmSync(path);
    symlinkSync(
      join(root, 'app/modules/zeta/frontend/components/panel.vue'),
      path,
    );
    assert.throws(() => discoverModuleComponents(root), /symlink/);
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
});

test('component evidence binds owned source content and registry snapshot without exposing import paths', () => {
  const root = mkdtempSync(join(tmpdir(), 'runa-component-evidence-'));
  try {
    const directory = join(root, 'app/resources/js/components/admin');
    mkdirSync(directory, { recursive: true });
    writeFileSync(join(directory, 'panel.vue'), '<template>Panel</template>');
    const digest = (value) => createHash('sha256').update(value).digest('hex');
    const manifest = discoverComponentBuilds(root);
    assert.deepEqual(manifest.owners.app, {
      manifest_hash: null,
      build_id: digest(
        'app\njs/components/admin/panel.vue:' +
          digest('<template>Panel</template>') +
          '\n',
      ),
      sources: {
        'js/components/admin/panel.vue': digest('<template>Panel</template>'),
      },
      components: ['app.panel'],
    });
    assert.ok(!JSON.stringify(manifest).includes(root));
    const plugin = moduleComponentsPlugin(root);
    plugin.configResolved({ command: 'build', mode: 'production' });
    const compiled = plugin.load('\0virtual:runa-module-components');
    assert.ok(compiled.includes(JSON.stringify(manifest)));
    const assets = [];
    plugin.generateBundle.call({ emitFile: (value) => assets.push(value) });
    assert.equal(assets[0].fileName, 'runa-components.json');
    assert.deepEqual(JSON.parse(assets[0].source), manifest);
    writeFileSync(join(directory, 'panel.vue'), '<template>Changed</template>');
    assert.notEqual(
      discoverComponentBuilds(root).owners.app.build_id,
      manifest.owners.app.build_id,
    );
    assert.throws(
      () => plugin.generateBundle.call({ emitFile() {} }),
      /changed during build/,
    );
    rmSync(join(directory, 'panel.vue'));
    symlinkSync(join(root, 'app/resources'), join(directory, 'panel.vue'));
    assert.throws(() => discoverComponentBuilds(root), /symlink/);
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
});

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
import {
  discoverModuleComponents,
  moduleComponentsPlugin,
} from '../../frontend/module-components.mjs';
test('deterministic owned build keys, production development exclusion and opt-in loading', () => {
  const root = mkdtempSync(join(tmpdir(), 'runa-frontend-build-'));
  try {
    for (const code of ['zeta', 'alpha', 'module_probe']) {
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

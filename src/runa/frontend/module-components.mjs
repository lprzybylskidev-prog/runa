import { readdirSync, readFileSync, realpathSync, existsSync } from 'node:fs';
import { resolve } from 'node:path';
import { createHash } from 'node:crypto';

/** Opt-in virtual registry; only local owned source, never DB imports. */
export function discoverModuleComponents(root, development = false) {
  const entries = [];
  const owned = (path) => {
    if (realpathSync(path) !== path)
      throw new Error('Module build paths cannot traverse symlinks.');
    return path;
  };
  for (const owner of ['app', 'runa']) {
    const directory = resolve(root, owner, 'modules');
    if (!existsSync(directory)) continue;
    owned(directory);
    for (const code of readdirSync(directory).sort()) {
      if (code.startsWith('.')) continue;
      const path = owned(resolve(directory, code));
      const source = readFileSync(owned(resolve(path, 'module.json')), 'utf8');
      const manifest = JSON.parse(source);
      const manifestHash = createHash('sha256').update(source).digest('hex');
      if (
        manifest.schema_version !== 1 ||
        manifest.id !== `${owner}.${code}` ||
        !/^[a-z][a-z0-9]*(_[a-z][a-z0-9]*)*$/.test(code)
      )
        throw new Error('Invalid module build identity.');
      if (['module_probe', 'reference'].includes(code) && !development)
        continue;
      const frontend = resolve(path, 'frontend');
      if (!existsSync(frontend)) continue;
      owned(frontend);
      const components = resolve(frontend, 'components');
      if (!existsSync(components)) continue;
      owned(components);
      for (const name of readdirSync(components).sort()) {
        if (!/^[a-z][a-z0-9_]*\.vue$/.test(name))
          throw new Error(
            'Module components require snake_case Vue filenames.',
          );
        entries.push([
          `${owner}.${code}.${name.slice(0, -4)}`,
          owned(resolve(components, name)),
          manifestHash,
        ]);
      }
    }
  }
  return entries;
}

export function moduleComponentsPlugin(root) {
  const id = 'virtual:runa-module-components';
  const resolved = '\0' + id;
  let development = false;
  return {
    name: 'runa-module-components',
    configResolved(config) {
      development = config.command === 'serve' || config.mode === 'development';
    },
    resolveId(source) {
      if (source === id) return resolved;
    },
    load(source) {
      if (source !== resolved) return;
      const entries = discoverModuleComponents(root, development);
      return (
        'export default {' +
        entries
          .map(
            ([key, file, hash]) =>
              `${JSON.stringify(key)}: {manifestHash: ${JSON.stringify(hash)}, load: () => import(${JSON.stringify(file)})}`,
          )
          .join(',') +
        '};'
      );
    },
  };
}

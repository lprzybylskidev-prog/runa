import {
  readdirSync,
  readFileSync,
  realpathSync,
  existsSync,
  statSync,
} from 'node:fs';
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
      if (code === 'module_probe' || (code === 'reference' && !development))
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

/** Public asset evidence; no imports, absolute paths or secrets are serialized. */
export function discoverComponentBuilds(root, development = false) {
  const entries = discoverModuleComponents(root, development);
  const owners = {};
  const digest = (value) => createHash('sha256').update(value).digest('hex');
  const owned = (path) => {
    if (realpathSync(path) !== path)
      throw new Error('Component build paths cannot traverse symlinks.');
    return path;
  };
  const add = (owner, directory, manifestHash, keys) => {
    const sources = {};
    const walk = (path, prefix = '') => {
      owned(path);
      for (const name of readdirSync(path).sort()) {
        const file = owned(resolve(path, name));
        const relative = prefix + name;
        if (statSync(file).isDirectory()) walk(file, relative + '/');
        else if (statSync(file).isFile())
          sources[relative] = digest(readFileSync(file));
        else throw new Error('Invalid component build source.');
      }
    };
    walk(directory);
    const sorted = Object.fromEntries(
      Object.entries(sources).sort(([a], [b]) =>
        Buffer.compare(Buffer.from(a), Buffer.from(b)),
      ),
    );
    const buildId = digest(
      (manifestHash ?? 'app') +
        '\n' +
        Object.entries(sorted)
          .map(([path, hash]) => path + ':' + hash + '\n')
          .join(''),
    );
    if (Object.keys(sorted).length > 16384)
      throw new Error('Component source limit exceeded.');
    owners[owner] = {
      manifest_hash: manifestHash,
      build_id: buildId,
      sources: sorted,
      components: keys.sort(),
    };
  };
  const moduleOwners = [
    ...new Set(entries.map(([key]) => key.split('.').slice(0, 2).join('.'))),
  ];
  for (const owner of moduleOwners) {
    const selected = entries.filter(([key]) => key.startsWith(owner + '.'));
    const [scope, code] = owner.split('.');
    add(
      owner,
      resolve(root, scope, 'modules', code, 'frontend'),
      selected[0][2],
      selected.map(([key]) => key),
    );
  }
  const appDirectory = resolve(root, 'app/resources/js/components/admin');
  if (existsSync(appDirectory)) {
    owned(appDirectory);
    const keys = readdirSync(appDirectory)
      .sort()
      .map((name) => {
        if (!/^[a-z][a-z0-9_]*\.vue$/.test(name))
          throw new Error('Invalid App component filename.');
        owned(resolve(appDirectory, name));
        return 'app.' + name.slice(0, -4);
      });
    add('app', resolve(root, 'app/resources'), null, keys);
  }
  const manifest = { schema_version: 1, owners };
  if (Buffer.byteLength(JSON.stringify(manifest)) > 4194304)
    throw new Error('Component build evidence limit exceeded.');
  return manifest;
}

export function moduleComponentsPlugin(root) {
  const id = 'virtual:runa-module-components';
  const resolved = '\0' + id;
  let development = false;
  let evidence;
  return {
    name: 'runa-module-components',
    configResolved(config) {
      development = config.command === 'serve' || config.mode === 'development';
    },
    buildStart() {
      evidence = undefined;
    },
    generateBundle() {
      const current = discoverComponentBuilds(root, development);
      if (evidence && JSON.stringify(evidence) !== JSON.stringify(current))
        throw new Error('Component sources changed during build.');
      this.emitFile({
        type: 'asset',
        fileName: 'runa-components.json',
        source: JSON.stringify(current),
      });
    },
    resolveId(source) {
      if (source === id) return resolved;
    },
    load(source) {
      if (source !== resolved) return;
      const entries = discoverModuleComponents(root, development);
      evidence = discoverComponentBuilds(root, development);
      return (
        'export const builds = ' +
        JSON.stringify(evidence) +
        ';export default {' +
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

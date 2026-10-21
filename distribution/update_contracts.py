"""Pure source/compatibility contracts for the external owner updater."""
import ast
import hashlib
import json
import os
from pathlib import Path
import re

OWNER_PATHS = ('distribution', 'tools/maintainer', 'bootstrap.py', 'bin/project', 'bin/install-prod',
               'src/runa/tests', 'src/runa/tools', 'docs/runa/maintainer', 'src/runa/management/InstallCommand.php', 'src/runa/management/ProjectCommand.php')
COMPOSED = {'src/composer.json', 'src/composer.lock', 'src/package.json', 'src/pnpm-lock.yaml'}
ENVIRONMENT = {'.env', '.env.testing', '.env.dev.local', '.env.dev.database', '.env.prod', '.env.prod.database', '.env.prod.compose'}
VERSION = r'(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)'
SEMVER = VERSION + r'(?:-(?:0|[1-9][0-9]*|[0-9]*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9][0-9]*|[0-9]*[a-zA-Z-][0-9a-zA-Z-]*))*)?(?:\+[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*)?'


class Blocker(ValueError):
    def __init__(self, code, message):
        self.code = code
        super().__init__(message)


def regular(root, name, limit=2 * 1024 ** 2):
    path = root / name
    if any(p.is_symlink() for p in [path, *path.parents]) or not path.is_file() or path.stat().st_size > limit:
        raise Blocker('unsafe-input', 'Required input must be a bounded regular file: ' + name)
    return path.read_bytes()


def document(root, name, limit=2 * 1024 ** 2):
    try:
        def unique(pairs):
            result = {}
            for key, value in pairs:
                if key in result:
                    raise ValueError()
                result[key] = value
            return result
        value = json.loads(regular(root, name, limit), object_pairs_hook=unique)
        if not isinstance(value, dict):
            raise ValueError()
        return value
    except (ValueError, UnicodeError):
        raise Blocker('invalid-json', 'Invalid JSON object: ' + name) from None


def stable(value):
    if not isinstance(value, str) or not re.fullmatch(VERSION, value) or any(len(p) > 9 for p in value.split('.')):
        raise Blocker('invalid-version', 'Platform compatibility requires bounded stable X.Y.Z versions.')
    return tuple(map(int, value.split('.')))


def supports(constraint, version):
    target = stable(version)
    if not isinstance(constraint, str) or not 0 < len(constraint) <= 256:
        raise Blocker('unsupported-constraint', 'Unsupported application compatibility constraint.')
    result = False
    for alternative in constraint.split('||'):
        tokens = alternative.split()
        if not tokens:
            raise Blocker('unsupported-constraint', 'Unsupported application compatibility constraint.')
        matches = True
        for token in tokens:
            match = re.fullmatch(r'(\^|~|>=|<=|>|<|=)?(' + VERSION + ')', token)
            if not match:
                raise Blocker('unsupported-constraint', 'Unsupported application compatibility constraint.')
            op, value = match[1] or '=', stable(match[2])
            upper = ((value[0], value[1] + 1, 0) if op == '~' else
                     (value[0] + 1, 0, 0) if value[0] else (0, value[1] + 1, 0) if value[1] else (0, 0, value[2] + 1))
            matches = matches and {'=': target == value, '>': target > value, '<': target < value,
                                   '>=': target >= value, '<=': target <= value,
                                   '^': value <= target < upper, '~': value <= target < upper}[op]
        result = result or matches
    return result


def platform(root):
    value = document(root, 'src/runa/platform.json', 16384)
    version = regular(root, 'VERSION', 128).decode().strip()
    stable(version)
    if type(value.get('schema_version')) is not int or set(value) != {'schema_version', 'id', 'name', 'version'} or value != {'schema_version': 1, 'id': 'runa', 'name': 'Runa', 'version': version}:
        raise Blocker('platform-identity', 'Platform manifest and canonical VERSION disagree or have unsupported identity/schema.')
    return version


def customer(path):
    return path.startswith(('src/app/', 'docs/app/')) or path in COMPOSED


def owner(path):
    return any(path == prefix or path.startswith(prefix + '/') for prefix in OWNER_PATHS)


def generated(path):
    if path in ENVIRONMENT or path == '.devcontainer/compose.resources.yaml':
        return True
    if path.startswith(('.git/', '.runtime/', 'src/vendor/', 'src/node_modules/', 'src/.runtime/', 'artifacts/', 'coverage/', '.pnpm-store/')):
        return True
    if re.fullmatch(r'src/bootstrap/cache/(?:config|packages|services|events|routes-v\d+)\.php', path):
        return True
    if re.fullmatch(r'src/storage/framework/views/[a-f0-9]{40}\.php', path):
        return True
    if path.startswith(('src/storage/', 'src/public/build/', 'src/public/runa-error/', 'src/runa/resources/views/errors/generated/')):
        return not path.lower().endswith(('.php', '.phtml', '.phar'))
    return '__pycache__' in Path(path).parts and path.endswith('.pyc')


def production_paths(reference):
    # Read a verified release's literal projection data; never import/execute it.
    program = ast.parse(regular(reference, 'tools/application/operations.py').decode())
    for node in program.body:
        if isinstance(node, ast.Assign) and any(isinstance(t, ast.Name) and t.id == 'SPARSE' for t in node.targets):
            paths = ast.literal_eval(node.value)
            if not isinstance(paths, list) or not paths or any(not isinstance(p, str) or not p.startswith('/') or any(c in p for c in '*?!\\') or '..' in Path(p).parts for p in paths):
                break
            return paths
    raise Blocker('unsupported-projection', 'Original release has no supported literal production projection.')


def inventory(root, expected_names=None):
    result = {}
    def visit(folder):
        for path in sorted(folder.iterdir()):
            name = path.relative_to(root).as_posix()
            # Expected tracked files take priority elsewhere; ignored subtrees
            # here are strictly runtime/dependency containers, never source roots.
            if name == '.git':
                if path.is_symlink() or not path.is_dir():
                    raise Blocker('unsafe-git', 'Installation Git metadata must be a real directory.')
                continue
            if path.is_symlink():
                result[name] = ('120000', os.readlink(path).encode())
            elif path.is_dir():
                if name in ('.runtime', 'src/vendor', 'src/node_modules', 'src/.runtime', 'artifacts', 'coverage', '.pnpm-store'):
                    continue
                if customer(name + '/'):
                    continue
                visit(path)
            elif path.is_file():
                if expected_names is not None and name not in expected_names and generated(name):
                    continue
                digest = hashlib.sha256()
                size = path.stat().st_size
                if size > 512 * 1024 ** 2:
                    raise Blocker('source-size', 'Source file exceeds the supported size: ' + name)
                if expected_names is None or (name in expected_names and len(expected_names[name]) == 3 and size == expected_names[name][2]):
                    with path.open('rb') as stream:
                        for chunk in iter(lambda: stream.read(65536), b''):
                            digest.update(chunk)
                result[name] = ('100755' if path.stat().st_mode & 0o111 else '100644', digest.digest(), size)
            else:
                result[name] = ('unsupported', b'')
            if len(result) > 50000:
                raise Blocker('source-size', 'Source inventory exceeds 50000 files.')
    visit(root)
    return result


def compare(root, reference, profile):
    expected_all = inventory(reference)
    projection = production_paths(reference) if profile == 'prod' else None
    expected = {p: value for p, value in expected_all.items() if not customer(p) and not owner(p)
                and (projection is None or any(p == item.strip('/') or (item.endswith('/') and p.startswith(item.lstrip('/'))) for item in projection))}
    actual = inventory(root, expected)
    differences = []
    for name in sorted(expected.keys() | actual.keys()):
        if name not in expected:
            if not customer(name) and (actual[name][0] == '120000' or not generated(name)):
                differences.append({'path': name, 'kind': 'unexpected'})
            continue
        if name not in actual:
            differences.append({'path': name, 'kind': 'missing'})
        elif actual[name] != expected[name]:
            differences.append({'path': name, 'kind': 'type-or-mode' if actual[name][0] != expected[name][0] else 'changed'})
    return differences


def changes(original, target):
    before, after = inventory(original), inventory(target)
    return [{'path': p, 'kind': 'added' if p not in before else 'removed' if p not in after else 'changed'}
            for p in sorted(before.keys() | after.keys()) if not owner(p) and not customer(p) and before.get(p) != after.get(p)]


def compose(platform, application, kind):
    sections = ('require', 'require-dev') if kind == 'composer' else ('dependencies', 'devDependencies')
    if set(application) - set(sections):
        raise Blocker('dependency-conflict', 'Unsupported application dependency fields.')
    result = json.loads(json.dumps(platform))
    owned = {}
    for section in sections:
        for source in (platform, application):
            values = source.get(section, {})
            if not isinstance(values, dict):
                raise Blocker('dependency-conflict', 'Dependency declarations must be objects.')
            for name, value in values.items():
                if not isinstance(value, str) or not value.strip() or (name in owned and owned[name] != (section, value)):
                    raise Blocker('dependency-conflict', 'Conflicting dependency declaration: ' + name)
                owned[name] = (section, value)
                result.setdefault(section, {})[name] = value
    return result

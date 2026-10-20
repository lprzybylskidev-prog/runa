#!/usr/bin/env python3
"""Check vendored data offline or prepare a read-only upstream diff candidate."""
import argparse
import difflib
import hashlib
import json
from pathlib import Path
import re
import subprocess
import urllib.request

ROOT = Path(__file__).resolve().parents[3]
MANIFEST = ROOT / 'src/runa/lang/SOURCE.json'
FRAMEWORK = 'src/vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php'


def catalog(path):
    result = subprocess.run(['php', '-r', 'echo json_encode(require $argv[1], JSON_THROW_ON_ERROR);', str(path)],
                            cwd=ROOT, check=True, capture_output=True, text=True)
    return json.loads(result.stdout)


def flatten(data, prefix=''):
    result = {}
    for key, value in data.items():
        name = prefix + key
        if isinstance(value, dict):
            result.update(flatten(value, name + '.'))
        elif isinstance(value, str):
            result[name] = value
        elif value != []:
            raise ValueError('Unexpected catalog value at ' + name)
    return result


def placeholders(value):
    return sorted(set(re.findall(r':[a-zA-Z_][a-zA-Z_0-9]*', value)))


def english():
    data = catalog(ROOT / FRAMEWORK)
    # Laravel's example custom rule is not an actual framework message.
    data['custom'] = {}
    data['attributes'] = {}
    return data


def verify(data):
    expected, actual = flatten(english()), flatten(data)
    if set(expected) != set(actual):
        raise ValueError('Catalog key drift: ' + str(sorted(set(expected) ^ set(actual))))
    for key, value in expected.items():
        if not actual[key].strip() or placeholders(value) != placeholders(actual[key]):
            raise ValueError('Missing message or placeholder mismatch: ' + key)
    return len(expected)


def fetch(path, commit):
    url = 'https://raw.githubusercontent.com/Laravel-Lang/lang/' + commit + '/' + path
    with urllib.request.urlopen(url, timeout=30) as response:
        return response.read()


def prepare(commit, version):
    if not re.fullmatch(r'[0-9a-f]{40}', commit) or not re.fullmatch(r'\d+\.\d+\.\d+', version):
        raise ValueError('Use an exact 40-character commit SHA and numeric tag.')
    # Resolve the exact tag through Git without running upstream code.
    result = subprocess.run(['git', 'ls-remote', '--tags', 'https://github.com/Laravel-Lang/lang.git',
                             'refs/tags/' + version, 'refs/tags/' + version + '^{}'],
                            check=True, capture_output=True, text=True)
    refs = [line.split()[0] for line in result.stdout.splitlines()]
    if not refs or refs[-1] != commit:
        raise ValueError('Upstream tag does not resolve to the supplied commit.')
    raw = fetch('locales/pl/php.json', commit)
    translations = json.loads(raw)
    data = english()

    def translate(node, prefix=''):
        for key, value in node.items():
            name = prefix + key
            if isinstance(value, dict):
                translate(value, name + '.')
            else:
                if name not in translations:
                    raise ValueError('Upstream is missing required message: ' + name)
                node[key] = translations[name]
    translate(data)
    count = verify(data)
    encoded = json.dumps(data, ensure_ascii=False)
    php = subprocess.run(['php', '-r', '$d=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR); '
                         'echo "<?php\\n\\n// Vendored translation data; see lang/SOURCE.json and lang/LICENSE.laravel-lang.\\nreturn ",var_export($d,true),";\\n";'],
                         input=encoded, text=True, check=True, capture_output=True).stdout
    php = '\n'.join(line.rstrip() for line in php.splitlines()) + '\n'
    target = ROOT / '.runtime/localization' / commit
    target.mkdir(parents=True, exist_ok=True)
    (target / 'validation.php').write_text(php)
    license_data = fetch('LICENSE', commit)
    (target / 'LICENSE.laravel-lang').write_bytes(license_data)
    metadata = {'repository': 'https://github.com/Laravel-Lang/lang', 'version': version,
                'tag': version, 'commit': commit, 'imported_at': None,
                'upstream_paths': ['locales/pl/php.json', 'LICENSE'],
                'source_sha256': hashlib.sha256(raw).hexdigest(),
                'catalog_sha256': hashlib.sha256(php.encode()).hexdigest(),
                'message_count': count,
                'license_sha256': hashlib.sha256(license_data).hexdigest(),
                'imported_files': ['lang/pl/validation.php', 'lang/LICENSE.laravel-lang']}
    metadata['framework_version'] = next(p['version'] for p in json.loads((ROOT / 'src/composer.lock').read_text())['packages'] if p['name'] == 'laravel/framework')
    (target / 'source.json').write_text(json.dumps(metadata, indent=2) + '\n')
    current = ROOT / 'src/runa/lang/pl/validation.php'
    if current.exists():
        print(''.join(difflib.unified_diff(current.read_text().splitlines(True), php.splitlines(True),
                                         fromfile='local/lang/pl/validation.php', tofile='candidate/validation.php')))
    print('Prepared', count, 'messages in', target, '(working catalog unchanged).')


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    commands = parser.add_subparsers(dest='command', required=True)
    commands.add_parser('check', help='Check keys/placeholders and report local modifications; no network')
    candidate = commands.add_parser('prepare', help='Prepare candidate data/license/metadata under .runtime; never overwrite local files')
    candidate.add_argument('--commit', required=True)
    candidate.add_argument('--version', required=True)
    args = parser.parse_args()
    if args.command == 'prepare':
        prepare(args.commit, args.version)
    else:
        metadata = json.loads(MANIFEST.read_text())
        path = ROOT / 'src/runa/lang/pl/validation.php'
        count = verify(catalog(path))
        modified = hashlib.sha256(path.read_bytes()).hexdigest() != metadata['catalog_sha256']
        if not (ROOT / 'src/runa/lang/LICENSE.laravel-lang').is_file():
            raise ValueError('MIT attribution is missing.')
        if hashlib.sha256((ROOT / 'src/runa/lang/LICENSE.laravel-lang').read_bytes()).hexdigest() != metadata['license_sha256']:
            raise ValueError('MIT license notice differs from the imported source.')
        print(f'PASS: {count} messages; local changes since import: {modified}. Review before updating.')


if __name__ == '__main__':
    try:
        main()
    except (ValueError, OSError, subprocess.CalledProcessError) as error:
        raise SystemExit('Error: ' + str(error))

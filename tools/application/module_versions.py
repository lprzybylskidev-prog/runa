"""Module-local stable SemVer/changelog checks against an explicit Git baseline.

No semantic break inference, source execution, version mutation or remote operations.
"""
import argparse
import json
from pathlib import Path
import re
import subprocess

VERSION = re.compile(r'(0|[1-9][0-9]{0,8})\.(0|[1-9][0-9]{0,8})\.(0|[1-9][0-9]{0,8})')


def version(value):
    if not isinstance(value, str) or not VERSION.fullmatch(value):
        raise ValueError('Module version must be bounded stable X.Y.Z.')
    return tuple(map(int, value.split('.')))


def assess(previous, current, changed, changelog):
    """Require a monotonic bump and a current changelog entry for implementation changes."""
    current_version = version(current)
    errors = []
    if previous is not None:
        previous_version = version(previous)
        if current_version < previous_version:
            errors.append('module_version_downgrade')
        if changed and current_version <= previous_version:
            errors.append('changed_module_requires_version_bump')
    if (previous is None or changed or previous != current) and not re.search(
            r'^##\s+\[?' + re.escape(current) + r'\]?(?:\s|$)', changelog, re.M):
        errors.append('module_version_requires_changelog_entry')
    return errors


def check(root, owner, base, *, run=subprocess.run):
    if owner not in ('app', 'runa'):
        raise ValueError('Select app or runa module ownership.')
    def git(*args, check=True):
        return run(['git', '-C', str(root), *args], check=check, capture_output=True).stdout
    # Resolve refs as data, rejecting option-like revisions and invalid/shallow baselines explicitly.
    baseline = git('rev-parse', '--verify', '--end-of-options', base + '^{commit}').decode().strip()
    changes = set(git('diff', '--name-only', '-z', baseline, '--', f'src/{owner}/modules').decode().split('\0'))
    changes.update(git('ls-files', '--others', '--exclude-standard', '-z', '--', f'src/{owner}/modules').decode().split('\0'))
    results = []
    directory = root / 'src' / owner / 'modules'
    for manifest in sorted(directory.glob('*/module.json')):
        if manifest.is_symlink() or manifest.parent.is_symlink():
            raise ValueError('Module version paths cannot traverse symlinks.')
        data = json.loads(manifest.read_text())
        relative = manifest.relative_to(root).as_posix()
        old = run(['git', '-C', str(root), 'show', baseline + ':' + relative], capture_output=True)
        previous = json.loads(old.stdout)['version'] if old.returncode == 0 else None
        prefix = manifest.parent.relative_to(root).as_posix() + '/'
        changed = any(path.startswith(prefix) and not path[len(prefix):].startswith('tests/') for path in changes)
        docs = root / ('docs/app/modules' if owner == 'app' else 'docs/runa/handbook/modules') / manifest.parent.name / 'CHANGELOG.md'
        if docs.is_symlink() or any(parent.is_symlink() for parent in docs.parents):
            raise ValueError('Module changelog paths cannot traverse symlinks.')
        errors = assess(previous, data['version'], changed, docs.read_text() if docs.is_file() else '')
        results.append({'module': data['id'], 'previous': previous, 'current': data['version'], 'errors': errors})
    return results


def main(arguments=None):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--owner', required=True, choices=['app', 'runa'])
    parser.add_argument('--base', default='main', help='Existing baseline ref; use the project default branch for normal roadmap checks.')
    args = parser.parse_args(arguments)
    try:
        results = check(Path(__file__).resolve().parents[2], args.owner, args.base)
        print(json.dumps(results, indent=2))
        return int(any(result['errors'] for result in results))
    except (ValueError, OSError, KeyError, subprocess.CalledProcessError):
        print('Module version check failed: inspect baseline, manifests and central changelogs.')
        return 1


if __name__ == '__main__':
    raise SystemExit(main())

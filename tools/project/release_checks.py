"""Read-only roadmap/version/changelog checks before and after release metadata edits."""
from pathlib import Path
import re
import subprocess

VERSION = r'(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)'


def git(root, *args):
    return subprocess.run(['git', *args], cwd=root, check=True, text=True, capture_output=True).stdout.strip()


def version(text):
    if not re.fullmatch(VERSION, text):
        raise ValueError('Invalid canonical X.Y.Z version: ' + text)
    return tuple(map(int, text.split('.')))


def expected(base, kind, target=None):
    major, minor, patch = version(base)
    if kind == 'feature':
        return f'{major}.{minor + 1}.0'
    if kind in ('fix', 'maintenance'):
        return f'{major}.{minor}.{patch + 1}'
    if kind == 'release' and target:
        if version(target) <= (major, minor, patch):
            raise ValueError('Explicit release target must increase the version.')
        return target
    raise ValueError('Invalid release_type or missing explicit target_version.')


def metadata(path):
    text = path.read_text()
    if not text.startswith('---\n') or '\n---\n' not in text[4:]:
        raise ValueError('Missing roadmap frontmatter.')
    front = text.split('\n---\n', 1)[0][4:]
    result = dict(re.findall(r'^([a-z_]+): ([^\n]+)$', front, re.M))
    if not re.fullmatch(r'RM-\d{4}-\d{3}', result.get('id', '')) or not path.name.startswith(result['id'] + '-'):
        raise ValueError('Invalid roadmap ID/path.')
    return result, text


def validate(root):
    root = Path(root)
    current = (root / 'VERSION').read_text().strip()
    version(current)
    branch = git(root, 'branch', '--show-current')
    changelog = (root / 'CHANGELOG.md').read_text()
    entries = re.findall(r'^## \[?(\d+\.\d+\.\d+)\]?[^\n]*$', changelog, re.M)
    if len(entries) != len(set(entries)):
        raise ValueError('Duplicate changelog version.')
    for entry in entries:
        version(entry)
    if any(version(a) <= version(b) for a, b in zip(entries, entries[1:])):
        raise ValueError('Changelog versions must be newest first.')
    active = list((root / 'docs/roadmaps/active').glob('*.md'))
    if len(active) > 1:
        raise ValueError('Multiple active roadmaps.')
    if active:
        data, text = metadata(active[0])
        if data.get('status') != 'active' or data.get('branch') != branch:
            raise ValueError('Active roadmap status/branch mismatch.')
        base = git(root, 'show', 'main:VERSION')
        target = expected(base, data.get('release_type'), data.get('target_version'))
        if current != base:
            raise ValueError('Active roadmap VERSION must match main until release finalization.')
        if entries and entries[0] != current:
            raise ValueError('Changelog head does not match current VERSION.')
        if current != '0.0.0' and not entries:
            raise ValueError('Current release is absent from changelog.')
        pending = re.findall(r'^\s*- \[ \] (.+)$', text, re.M)
        if len(pending) != 1 or not all(word in pending[0].lower() for word in ('final', 'verification')):
            raise ValueError('Only the final verification item may remain pending.')
        tags = git(root, 'tag', '--list', 'v' + target)
        if tags:
            raise ValueError('Computed release tag already exists; never overwrite it.')
        from docs_checks import validate as validate_docs
        validate_docs(root)
        print(f'PASS: active release metadata; {data["release_type"]} {base} -> {target}; final item remains open.')
        return target
    if not entries or entries[0] != current:
        raise ValueError('Final VERSION and changelog head differ.')
    heading = re.search(r'^## \[?' + re.escape(current) + r'\]?[^\n]*$', changelog, re.M)
    entry = changelog[heading.end():].split('\n## ', 1)[0]
    ids = set(re.findall(r'RM-\d{4}-\d{3}', entry))
    if len(ids) != 1:
        raise ValueError('Release changelog must identify exactly one roadmap.')
    identifier = ids.pop()
    paths = list((root / 'docs/roadmaps/closed').glob(identifier + '-*.md'))
    if len(paths) != 1:
        raise ValueError('Matching closed roadmap is missing/ambiguous.')
    data, text = metadata(paths[0])
    if data.get('status') != 'closed' or not re.fullmatch(r'\d{4}-\d{2}-\d{2}', data.get('closed_at', '')):
        raise ValueError('Invalid roadmap closure metadata.')
    if branch not in ('main', data.get('branch')):
        raise ValueError('Closed release branch mismatch.')
    if re.search(r'^\s*- \[ \]', text, re.M):
        raise ValueError('Closed roadmap contains open checklist items.')
    report = re.search(r'^## Final report\n(.+)', text, re.M | re.S)
    if not report or identifier not in report[1] or current not in report[1]:
        raise ValueError('Closed roadmap final report must identify the roadmap and version.')
    if data.get('release_type') not in ('feature', 'fix', 'maintenance', 'release'):
        raise ValueError('Invalid closed release_type.')
    if branch != 'main':
        base = git(root, 'show', 'main:VERSION')
        if current != expected(base, data['release_type'], data.get('target_version')):
            raise ValueError('Final version does not match roadmap release_type.')
    from docs_checks import validate as validate_docs
    validate_docs(root)
    if git(root, 'tag', '--list', 'v' + current):
        if git(root, 'cat-file', '-t', 'refs/tags/v' + current) != 'tag':
            raise ValueError('Release tag must be annotated.')
        tagged = git(root, 'rev-parse', 'v' + current + '^{commit}')
        if tagged != git(root, 'rev-parse', 'HEAD'):
            ancestor = subprocess.run(['git', 'merge-base', '--is-ancestor', tagged, 'HEAD'], cwd=root).returncode == 0
            # Compare full trees, including release metadata, without rename detection.
            # Only draft Markdown may follow a release on main; never excuse runtime drift.
            changed = git(root, 'diff', '--no-renames', '--name-only', '-z', tagged, 'HEAD').split('\0')
            if branch != 'main' or not ancestor or any(
                name and not re.fullmatch(r'docs/roadmaps/draft/[^/]+\.md', name) for name in changed
            ):
                raise ValueError('Existing release tag does not identify HEAD or a main-only draft-planning baseline.')
    print(f'PASS: closed release metadata {identifier} / {current}; publication is checked separately.')
    return current

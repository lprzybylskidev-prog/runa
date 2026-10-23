"""Customer documentation completeness, placement and local-link diagnostics."""
from pathlib import Path
import re

REQUIRED = ('.agents/README.md', '.agents/skills/README.md', 'AGENTS.md', 'README.md', 'docs/README.md', 'docs/app/README.md',
            'docs/runa/handbook/README.md', 'docs/runa/handbook/workflows/roadmaps.md',
            'docs/runa/handbook/workflows/git.md', 'docs/runa/handbook/workflows/releases.md',
            'docs/runa/handbook/workflows/roadmap-template.md', 'docs/runa/handbook/quality/README.md',
            'docs/runa/handbook/testing/strategy.md', 'docs/runa/handbook/security/baseline.md',
            'docs/runa/handbook/ai/knowledge-system.md', 'docs/runa/handbook/ai/laravel-boost.md') + tuple(
                '.agents/skills/' + name + '/SKILL.md' for name in
                ('backend', 'frontend', 'laravel', 'vue', 'tailwind', 'postgresql', 'docker', 'security', 'rag'))


def links(root, paths, public=True):
    root = Path(root).resolve()
    for path in paths:
        fence = None
        for line in path.read_text().splitlines():
            marker = re.match(r'^\s*(`{3,}|~{3,})', line)
            if marker:
                if fence is None:
                    fence = marker[1][0]
                elif marker[1][0] == fence:
                    fence = None
                continue
            if fence:
                continue
            for match in re.finditer(r'!?\[[^\]]*\]\((<[^>]+>|[^\s)]+)(?:\s+"[^"]*")?\)', line):
                target = match[1].strip('<>').split('#', 1)[0]
                if not target or re.match(r'^[a-zA-Z][a-zA-Z0-9+.-]*:', target):
                    continue
                destination = (path.parent / target).resolve()
                if not destination.is_relative_to(root):
                    raise ValueError('Documentation link escapes checkout: ' + str(path.relative_to(root)))
                relative = destination.relative_to(root).as_posix()
                if public and relative.startswith(('docs/runa/maintainer/', 'tools/maintainer/', 'distribution/')):
                    raise ValueError('Customer documentation links to owner-only material: ' + relative)
                if not destination.exists():
                    raise ValueError('Broken documentation link: ' + str(path.relative_to(root)) + ' -> ' + target)


def validate(root):
    root = Path(root).resolve()
    for name in REQUIRED:
        path = root / name
        if not path.is_file() or path.is_symlink() or any(p.is_symlink() for p in path.parents if p != root.parent):
            raise ValueError('Missing regular public documentation source: ' + name)
    for folder, allowed in (('docs', {'README.md', 'app', 'runa'}), ('docs/runa', {'handbook', 'maintainer'})):
        for path in (root / folder).iterdir():
            if path.name not in allowed or path.is_symlink():
                raise ValueError('Invalid documentation placement: ' + str(path.relative_to(root)))
    receipt = root / '.git/runa-bootstrap.json'
    if (receipt.exists() or receipt.is_symlink()) and (root / 'docs/runa/maintainer').exists():
        raise ValueError('Customer source contains owner-only documentation.')
    for state in ('draft', 'active', 'paused', 'closed'):
        path = root / 'docs/app/roadmaps' / state
        if not path.is_dir() or path.is_symlink():
            raise ValueError('Missing regular application roadmap directory: ' + state)
    if (root / 'src/app/docs').exists() or (root / 'src/app/docs').is_symlink():
        raise ValueError('Application documentation belongs in docs/app, not src/app/docs.')
    for owner in ('app', 'runa'):
        for path in (root / ('src/' + owner + '/modules')).glob('*/docs'):
            raise ValueError('Module documentation belongs in central docs directories, not ' + str(path.relative_to(root)))
    paths = [root / name for name in ('AGENTS.md', 'README.md', 'docs/README.md')]
    for folder in ('docs/app', 'docs/runa/handbook', '.agents'):
        for path in (root / folder).rglob('*.md'):
            if path.is_symlink() or any(p.is_symlink() for p in path.parents if p != root.parent):
                raise ValueError('Documentation source traverses a symlink: ' + str(path.relative_to(root)))
            # Closed application records are immutable historical evidence.
            if not path.relative_to(root).as_posix().startswith('docs/app/roadmaps/closed/'):
                paths.append(path)
    links(root, paths)
    print('PASS: application documentation, roadmap directories, placement and public local links.')

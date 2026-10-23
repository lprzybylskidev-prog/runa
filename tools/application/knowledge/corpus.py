"""Allowlisted source discovery and structural, model-budgeted chunking."""
import hashlib
import re
import subprocess
import uuid
from pathlib import Path
import sys

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from documentation import maintainer_checkout

NAMESPACE = uuid.UUID('b8857f5f-4537-596f-a80b-dc263a9e83c4')


def digest(value):
    return hashlib.sha256(value.encode()).hexdigest()


def identity(value):
    return str(uuid.uuid5(NAMESPACE, value))


def sources(root, config):
    # Intersect the allowlist with Git's tracked files: scratch/untracked/private files
    # cannot become knowledge merely by appearing under docs/.
    result = subprocess.run(['git', 'ls-files', '-z'], cwd=root, check=True, capture_output=True)
    paths = []
    owner = maintainer_checkout(root)
    for name in result.stdout.decode().split('\0'):
        if not name:
            continue
        # Authored customer instructions are a separate product, never owner operating rules.
        if name.startswith('docs/runa/maintainer/customer-agent/'):
            continue
        if not owner and (name.startswith('docs/runa/maintainer/') or name.startswith('tools/maintainer/')):
            continue
        path = root / name
        parts = path.parts
        if any(part in ('.env', 'vendor', 'node_modules', '.runtime', 'artifacts', 'scratch') or part.startswith('.env.') for part in parts):
            continue
        allowed = name in config['files'] or (path.suffix == '.md' and any(name.startswith(p + '/') for p in config['markdown_roots']))
        if not allowed:
            continue
        if path.is_symlink() or any(p.is_symlink() for p in path.parents if p != root.parent):
            raise ValueError(f'Symlink in allowlisted source: {name}')
        if not path.exists():
            continue
        paths.append(path)
    return sorted(set(paths))


def read_source(path):
    raw = path.read_bytes()
    if b'\0' in raw:
        raise ValueError(f'Binary knowledge source: {path.name}')
    text = raw.decode('utf-8')
    if not text.strip():
        raise ValueError(f'Empty knowledge source: {path.name}')
    return text.replace('\r\n', '\n')


def sections(text, markdown=True):
    heading_path = []
    heading_levels = []
    lines = []
    fence = None
    for line in text.splitlines():
        marker = re.match(r'^\s{0,3}(`{3,}|~{3,})', line)
        if marker:
            character = marker[1][0]
            if fence is None:
                fence = (character, len(marker[1]))
            elif fence[0] == character and len(marker[1]) >= fence[1] and not line.strip().strip(character):
                fence = None
        if not markdown and (re.match(r'^[a-zA-Z_][\w-]*:', line) or re.match(r'^  - id:', line)):
            if '\n'.join(lines).strip():
                yield heading_path[:], '\n'.join(lines).strip()
            heading_path = [line.strip().rstrip(':')]
            lines = []
        heading = re.match(r'^(#{1,6})\s+(.+?)\s*#*$', line) if markdown and fence is None else None
        if heading:
            if '\n'.join(lines).strip():
                yield heading_path[:], '\n'.join(lines).strip()
            level = len(heading[1])
            while heading_levels and heading_levels[-1][0] >= level:
                heading_levels.pop()
            heading_levels.append((level, heading[2]))
            heading_path = [title for _, title in heading_levels]
            lines = []
        else:
            lines.append(line)
    if fence:
        raise ValueError('Unclosed code fence in knowledge source.')
    if '\n'.join(lines).strip():
        yield heading_path[:], '\n'.join(lines).strip()


def metadata(path, root, text):
    name = path.relative_to(root).as_posix()
    location = re.fullmatch(r'docs/(app/roadmaps|runa/maintainer/roadmaps)/(draft|active|paused|closed)/([^/]+)\.md', name)
    identifier, status = None, None
    if location:
        header = re.match(r'\A---\n(.*?)\n---\n', text, re.S)
        if not header:
            raise ValueError('Missing roadmap frontmatter: ' + name)
        ids = re.findall(r'^id: (RM-\d{4}-\d{3})$', header[1], re.M)
        states = re.findall(r'^status: (draft|active|paused|closed)$', header[1], re.M)
        if len(ids) != 1 or len(states) != 1 or states[0] != location[2] or not re.fullmatch(re.escape(ids[0]) + r'(?:-.+)?', location[3]):
            raise ValueError('Invalid roadmap identity/status/location: ' + name)
        identifier, status = ids[0], states[0]
    kind = 'roadmap' if identifier else ('skill' if name.startswith('.agents/skills/') else ('config' if path.suffix != '.md' else 'documentation'))
    return {'document_id': identity((('roadmap:' if location[1].startswith('runa/') else 'application-roadmap:') + identifier) if identifier else 'source:' + name),
            'source_path': name, 'document_type': kind, 'scope': ('application' if name.startswith('docs/app/') else ('maintainer' if name.startswith('docs/runa/maintainer/') else (name.split('/')[3] if name.startswith('docs/runa/handbook/') and len(name.split('/')) > 4 else 'project'))),
            'roadmap_id': identifier, 'status': status,
            'tags': [kind], 'source_hash': digest(text)}


def chunks(path, root, text, provider, config):
    meta = metadata(path, root, text)
    # Strip roadmap frontmatter from semantic content, retaining metadata above.
    content = re.sub(r'\A---\n.*?\n---\n', '', text, count=1, flags=re.S)
    result = []
    occurrences = {}
    for headings, body in sections(content, path.suffix == '.md'):
        if meta['status'] == 'closed' and 'Implementation plan' in headings:
            continue
        context = ' > '.join(headings)
        if context and provider.counts([context])[0] > 100:
            raise ValueError(f'Heading hierarchy exceeds token budget: {meta["source_path"]}')
        # Keep whole sections when they fit. Split oversized sections at paragraph,
        # then line/word boundaries; preserve all text and no silent truncation.
        queue = [body]
        pieces = []
        while queue:
            piece = queue.pop(0)
            embedded = (context + '\n\n' + piece).strip()
            if provider.counts([embedded])[0] <= config['chunk_tokens']:
                pieces.append((piece, embedded))
                continue
            midpoint = len(piece) // 2
            candidates = [m.start() for m in re.finditer(r'\n\s*\n', piece) if 0 < m.start() < len(piece) - 1]
            if not candidates:
                candidates = [m.start() for m in re.finditer(r'\s+', piece) if 0 < m.start() < len(piece) - 1]
            cut = min(candidates, key=lambda n: abs(n - midpoint)) if candidates else midpoint
            if cut < 1:
                raise ValueError('Cannot split source within model token budget.')
            queue[0:0] = [piece[:cut].strip(), piece[cut:].strip()]
        for piece, embedded in pieces:
            content_hash = digest(embedded)
            key = (context, content_hash)
            ordinal = occurrences.get(key, 0)
            occurrences[key] = ordinal + 1
            chunk_id = identity(f'{meta["document_id"]}:{context}:{content_hash}:{ordinal}')
            result.append({'id': chunk_id, 'payload': {**meta, 'kind': 'chunk', 'chunk_id': chunk_id,
                           'chunk_schema_version': config['schema_version'], 'heading_path': headings,
                           'section': context, 'content': embedded, 'content_hash': content_hash}})
    if not result:
        raise ValueError(f'No indexable sections: {meta["source_path"]}')
    return meta, result

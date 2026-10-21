"""Read-only infrastructure review; reports names and hashes, never environment values."""
import hashlib
import json
from pathlib import Path
import re

from update_contracts import Blocker, document, regular
import update_runtime

GENERATED = '.devcontainer/compose.resources.yaml'


def local_path(root, value, base=None):
    path = (base or root) / value
    path = path.absolute()
    try:
        relative = path.resolve().relative_to(root.resolve()).as_posix()
    except (ValueError, OSError):
        raise Blocker('customization-review-unavailable', 'Infrastructure references must resolve inside the customer project.') from None
    if path.is_symlink() or any(p.is_symlink() for p in path.parents):
        raise Blocker('customization-review-unavailable', 'Infrastructure references must not traverse symlinks.')
    return relative


def files(root):
    names = set()
    for pattern in ('compose*.yaml', 'compose*.yml', 'docker-compose*.yaml', 'docker-compose*.yml', '.devcontainer/compose*.yaml', '.devcontainer/compose*.yml'):
        names.update(p.relative_to(root).as_posix() for p in root.glob(pattern) if p.relative_to(root).as_posix() != GENERATED)
    workspace = root / '.devcontainer/devcontainer.json'
    if workspace.exists() or workspace.is_symlink():
        value = document(root, '.devcontainer/devcontainer.json')
        refs = value.get('dockerComposeFile', [])
        if isinstance(refs, str): refs = [refs]
        if not isinstance(refs, list) or any(not isinstance(item, str) for item in refs):
            raise Blocker('customization-review-unavailable', 'Invalid devcontainer Compose references.')
        names.update(local_path(root, item, workspace.parent) for item in refs)
    return names - {GENERATED}


def configuration(root, name):
    regular(root, name)
    args = ['docker', 'compose', '--project-directory', str(root), '--env-file', '/dev/null', '-f', str(root / name),
            'config', '--no-interpolate', '--no-env-resolution', '--no-normalize', '--no-consistency', '--format', 'json']
    try:
        value = json.loads(update_runtime.command(args, root))
        services = value['services']
        if not isinstance(services, dict) or any(not re.fullmatch(r'[A-Za-z0-9][A-Za-z0-9_.-]{0,127}', name) for name in services):
            raise ValueError()
        return services
    except (KeyError, TypeError, ValueError, Blocker):
        raise Blocker('customization-review-unavailable', 'Compose definitions could not be compared safely; validate referenced files before updating.') from None


def review(root, original):
    roots = (original, root)
    names = files(original) | files(root)
    descriptors = [{}, {}]
    identities = [{}, {}]
    for index, source in enumerate(roots):
        for name in sorted(names):
            if not (source / name).exists() and not (source / name).is_symlink():
                continue
            content = regular(source, name)
            identities[index][name] = hashlib.sha256(content).hexdigest()
            services = configuration(source, name)
            for service, definition in services.items():
                # Normalize checkout-local paths, retaining literal/unexpanded settings privately.
                encoded = json.dumps(definition, sort_keys=True).replace(str(source), '<project>')
                descriptors[index][(name, service)] = encoded
                build = definition.get('build')
                if isinstance(build, str): build = {'context': build}
                if isinstance(build, dict) and 'dockerfile_inline' not in build:
                    context = build.get('context', str(source))
                    if not isinstance(context, str) or '$' in context or '$' in str(build.get('dockerfile', 'Dockerfile')):
                        raise Blocker('customization-review-unavailable', 'Parameterized build paths require explicit local definitions for reliable review.')
                    dockerfile = local_path(source, build.get('dockerfile', 'Dockerfile'), Path(context) if Path(context).is_absolute() else source / context)
                    if (source / dockerfile).exists():
                        identities[index][dockerfile] = hashlib.sha256(regular(source, dockerfile)).hexdigest()
                        descriptors[index][(dockerfile, service)] = identities[index][dockerfile]
        for folder in ('docker', '.devcontainer'):
            if (source / folder).is_symlink():
                raise Blocker('customization-review-unavailable', 'Container configuration roots must not be symlinks.')
            for path in (source / folder).rglob('*'):
                if path.is_file() or path.is_symlink():
                    name = path.relative_to(source).as_posix()
                    if name != GENERATED:
                        identities[index][name] = hashlib.sha256(regular(source, name)).hexdigest()
    changed = sorted(name for name in identities[0].keys() | identities[1].keys() if identities[0].get(name) != identities[1].get(name))
    affected = {service for key in descriptors[0].keys() | descriptors[1].keys()
                for name, service in [key] if descriptors[0].get(key) != descriptors[1].get(key)}
    workspace = any(name.startswith('.devcontainer/') for name in changed)
    # Other shared container inputs (nginx/postgres scripts, auxiliary Dockerfiles)
    # can affect every declared service; report conservatively without reading env values.
    mapped = {name for group in descriptors for name, _ in group}
    if any(name.startswith('docker/') and name not in mapped for name in changed):
        affected.update(service for group in descriptors for _, service in group)
    fingerprint = hashlib.sha256(json.dumps([[name, identities[0].get(name), identities[1].get(name)] for name in changed], separators=(',', ':')).encode()).hexdigest()
    return {'required': bool(changed), 'services': sorted(affected), 'devcontainer': workspace, 'fingerprint': fingerprint}


def decision(reviewed, choice, previous=None):
    """Explicit consent applies only to these exact differences; never to platform source."""
    if not reviewed['required']:
        return None
    if choice == 'abort':
        raise Blocker('customization-aborted', 'Operator aborted the infrastructure customization review; installation unchanged.')
    if previous is not None and previous != reviewed['fingerprint']:
        raise Blocker('customization-changed', 'Infrastructure differences changed since review; obtain a new explicit decision.')
    if choice != 'continue':
        raise Blocker('customization-decision-required', 'Infrastructure customization requires explicit continue/abort before mutation.')
    return reviewed['fingerprint']
